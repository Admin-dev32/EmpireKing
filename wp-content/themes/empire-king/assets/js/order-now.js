document.addEventListener('DOMContentLoaded', () => {
	const rail = document.querySelector('[data-order-now-categories]');
	const cards = Array.from(document.querySelectorAll('[data-order-now-product]'));
	const status = document.querySelector('[data-order-now-status]');
	const storeSelector = document.querySelector('[data-order-now-store]');

	if (rail && cards.length) rail.addEventListener('click', (event) => {
		const category = event.target.closest('[data-order-now-category]');
		if (!category || !rail.contains(category)) return;
		event.preventDefault();
		const slug = category.dataset.orderNowCategory;
		let count = 0;
		rail.querySelectorAll('[data-order-now-category]').forEach((chip) => chip.classList.toggle('is-active', chip === category));
		cards.forEach((card) => {
			const matches = card.dataset.categories.split(' ').includes(slug);
			card.hidden = !matches;
			if (matches) count += 1;
		});
		if (status) status.textContent = `${count} ${count === 1 ? 'item' : 'items'} in ${category.textContent.trim()}.`;
	});

	if (storeSelector) {
		storeSelector.querySelector('[data-order-now-current]')?.addEventListener('click', () => { storeSelector.open = false; });
		storeSelector.addEventListener('keydown', (event) => { if (event.key === 'Escape') storeSelector.open = false; });
	}

	const sheet = document.querySelector('[data-order-now-sheet]');
	const config = window.empireKingOrderNow;
	const $ = window.jQuery;
	if (!sheet || typeof sheet.showModal !== 'function' || !config || !$?.fn.wc_variation_form) return;
	const content = sheet.querySelector('[data-order-now-sheet-content]');
	const feedback = sheet.querySelector('[data-order-now-sheet-status]');
	const closeButton = sheet.querySelector('[data-order-now-close]');
	const productSheets = new Map();
	let opener = null;
	let scrollPosition = { x: 0, y: 0 };
	let loadRequest = null;
	let addRequest = null;
	let successTimer = null;
	let adding = false;
	let disposeApf = () => {};

	const updateCartLabel = () => {
		const badge = document.querySelector('.header-cart-link__count[data-cart-label]');
		if (badge) badge.closest('a')?.setAttribute('aria-label', badge.dataset.cartLabel);
	};
	$(document.body).on('added_to_cart wc_fragments_refreshed wc_fragments_loaded', updateCartLabel);
	updateCartLabel();
	const clearSuccessTimer = () => {
		if (successTimer) window.clearTimeout(successTimer);
		successTimer = null;
	};
	const closeSheet = () => { if (sheet.open) sheet.close(); };
	closeButton.addEventListener('click', closeSheet);
	sheet.addEventListener('cancel', (event) => { event.preventDefault(); closeSheet(); });
	let backdropPress = false;
	const outsideSheet = (event) => {
		const bounds = sheet.getBoundingClientRect();
		return event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom;
	};
	sheet.addEventListener('pointerdown', (event) => { backdropPress = event.target === sheet && outsideSheet(event); });
	sheet.addEventListener('click', (event) => {
		if (backdropPress && event.target === sheet && outsideSheet(event)) closeSheet();
		backdropPress = false;
	});
	sheet.addEventListener('close', () => {
		loadRequest?.abort();
		addRequest?.abort();
		clearSuccessTimer();
		disposeApf();
		document.documentElement.classList.remove('ek-order-sheet-open');
		document.documentElement.style.removeProperty('--ek-order-scroll-y');
		window.scrollTo({ left: scrollPosition.x, top: scrollPosition.y, behavior: 'instant' });
		opener?.focus({ preventScroll: true });
	});

	const initializeApf = () => {
		const root = content.querySelector('.product');
		if (!root?.querySelector('.wapf-wrapper') || typeof window._wapf !== 'function') return;
		// APF 1.7.1 accepts jQuery but queries globally. Scope its queries to this
		// product, including its delegated quantity listener on .woocommerce.
		const scopedJQuery = Object.assign((selector) => {
			if (selector === '.woocommerce') return $(root);
			return typeof selector === 'string' ? $(root).find(selector) : $(selector);
		}, $);
		// Its AJAX-variation path binds directly to document and has no teardown.
		// Track only handlers added by this synchronous initializer; never remove
		// Woo's or another component's found_variation handlers.
		const documentHandlers = () => $._data(document, 'events')?.found_variation || [];
		const before = new Set(documentHandlers().map((entry) => entry.handler));
		window._wapf(scopedJQuery);
		const added = documentHandlers().filter((entry) => !before.has(entry.handler));
		disposeApf = () => {
			added.forEach((entry) => $(document).off('found_variation', entry.handler));
			$(root).find('*').addBack().off();
			disposeApf = () => {};
		};
	};

	const renderProductSheet = (html) => {
		disposeApf();
		$(content).empty();
		content.innerHTML = html;
		initializeApf();
		const variationForm = content.querySelector('.variations_form');
		if (!variationForm) return;
		const button = variationForm.querySelector('.single_add_to_cart_button');
		if (button) button.disabled = true;
		$(variationForm).on('show_variation', (variationEvent, variation, purchasable) => {
			if (button) button.disabled = !purchasable || adding;
		}).on('hide_variation reset_data', () => { if (button) button.disabled = true; });
		$(variationForm).wc_variation_form();
	};

	const makePreview = (link) => {
		const card = link.closest('[data-order-now-product]');
		const preview = document.createElement('div');
		preview.className = 'product ek-order-now__sheet-product ek-order-now__sheet-preview';
		const image = card?.querySelector('.ek-order-now__product-image')?.cloneNode(true);
		if (image) {
			image.classList.add('ek-order-now__sheet-image');
			preview.append(image);
		}
		const title = document.createElement('h2');
		title.id = 'order-now-product-title';
		title.textContent = link.textContent.trim();
		preview.append(title);
		const price = card?.querySelector('.ek-order-now__product-price');
		if (price) preview.append(price.cloneNode(true));
		const description = card?.querySelector('.ek-order-now__product-description');
		if (description) {
			const previewDescription = description.cloneNode(true);
			previewDescription.classList.add('ek-order-now__sheet-description');
			preview.append(previewDescription);
		}
		const controls = document.createElement('div');
		controls.className = 'ek-order-now__controls-skeleton';
		controls.setAttribute('aria-hidden', 'true');
		controls.innerHTML = '<span></span><span></span>';
		preview.append(controls);
		return preview;
	};

	document.querySelectorAll('[data-order-now-open]').forEach((link) => {
		link.setAttribute('aria-haspopup', 'dialog');
		link.addEventListener('click', async (event) => {
			if (event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
			event.preventDefault();
			if (sheet.open) return;
			opener = link;
			feedback.textContent = '';
			content.replaceChildren(makePreview(link));
			scrollPosition = { x: window.scrollX, y: window.scrollY };
			document.documentElement.style.setProperty('--ek-order-scroll-y', `-${scrollPosition.y}px`);
			document.documentElement.classList.add('ek-order-sheet-open');
			sheet.showModal();
			sheet.scrollTop = 0;
			closeButton.focus({ preventScroll: true });
			const productId = link.dataset.orderNowOpen;
			if (productSheets.has(productId)) {
				renderProductSheet(productSheets.get(productId));
				return;
			}
			loadRequest?.abort();
			const request = new AbortController();
			loadRequest = request;
			const timeout = window.setTimeout(() => request.abort(), 15000);
			try {
				const url = new URL(config.sheetUrl, window.location.href);
				url.searchParams.set('product_id', productId);
				const response = await fetch(url, { signal: request.signal, credentials: 'same-origin' });
				const result = await response.json();
				if (!response.ok || !result.success) throw new Error('Product unavailable');
				if (!sheet.open || loadRequest !== request) return;
				productSheets.set(productId, result.data.html);
				renderProductSheet(result.data.html);
			} catch (error) {
				if (sheet.open && loadRequest === request) feedback.textContent = 'Product details could not be loaded. Close and try again.';
			} finally {
				window.clearTimeout(timeout);
				if (loadRequest === request) loadRequest = null;
			}
		});
	});

	sheet.addEventListener('submit', async (event) => {
		const form = event.target.closest('form.cart');
		if (!form || !content.contains(form)) return;
		event.preventDefault();
		if (adding) return;
		const button = form.querySelector('.single_add_to_cart_button');
		const data = new FormData(form);
		const variable = form.classList.contains('variations_form');
		const productId = variable ? Number(data.get('variation_id')) : Number(form.closest('[data-product-id]').dataset.productId);
		if (!productId || !button || button.disabled || button.classList.contains('disabled') || !form.reportValidity()) {
			feedback.textContent = 'Please choose the available product options before adding to cart.';
			return;
		}
		data.delete('add-to-cart');
		data.set('product_id', String(productId));
		adding = true;
		button.disabled = true;
		button.classList.add('is-busy');
		button.setAttribute('aria-label', 'Adding to cart');
		form.setAttribute('aria-busy', 'true');
		const controller = new AbortController();
		addRequest = controller;
		const timeout = window.setTimeout(() => controller.abort(), 20000);
		try {
			const response = await fetch(config.cartUrl, { method: 'POST', body: data, credentials: 'same-origin', signal: controller.signal });
			const result = await response.json();
			if (!response.ok || result.error || !result.fragments) throw new Error('Cart rejected');
			$(document.body).trigger('added_to_cart', [result.fragments, result.cart_hash]);
			updateCartLabel();
			if (sheet.open && content.contains(form)) {
				content.innerHTML = '<div class="ek-order-now__success" tabindex="-1"><svg aria-hidden="true" viewBox="0 0 48 48"><circle cx="24" cy="24" r="21"/><path d="m14 24 6.5 6.5L34 17"/></svg><h2>Item added</h2><p>Keep browsing to add more items.</p><button type="button" data-order-now-continue>Continue Browsing</button></div>';
				feedback.textContent = 'Item added. Keep browsing to add more items.';
				content.querySelector('[data-order-now-continue]')?.addEventListener('click', () => {
					clearSuccessTimer();
					closeSheet();
				});
				content.querySelector('.ek-order-now__success')?.focus({ preventScroll: true });
				successTimer = window.setTimeout(closeSheet, 1900);
			}
		} catch (error) {
			if (sheet.open && content.contains(form)) feedback.textContent = 'Could not add this item. Availability or quantity may have changed.';
		} finally {
			window.clearTimeout(timeout);
			if (addRequest === controller) addRequest = null;
			adding = false;
			form.removeAttribute('aria-busy');
			button.classList.remove('is-busy');
			button.removeAttribute('aria-label');
			button.disabled = button.classList.contains('disabled');
			const currentVariationForm = content.querySelector('.variations_form');
			if (currentVariationForm) $(currentVariationForm).trigger('check_variations');
		}
	});
});
