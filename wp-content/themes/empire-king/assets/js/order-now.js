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
	let opener = null;
	let scrollPosition = { x: 0, y: 0 };
	let loadRequest = null;
	let adding = false;

	const updateCartLabel = () => {
		const badge = document.querySelector('.header-cart-link__count[data-cart-label]');
		if (badge) badge.closest('a')?.setAttribute('aria-label', badge.dataset.cartLabel);
	};
	$(document.body).on('added_to_cart wc_fragments_refreshed wc_fragments_loaded', updateCartLabel);
	updateCartLabel();

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
		document.documentElement.classList.remove('ek-order-sheet-open');
		document.documentElement.style.removeProperty('--ek-order-scroll-y');
		window.scrollTo({ ...scrollPosition, left: scrollPosition.x, top: scrollPosition.y, behavior: 'instant' });
		opener?.focus({ preventScroll: true });
	});

	document.querySelectorAll('[data-order-now-open]').forEach((link) => {
		link.setAttribute('aria-haspopup', 'dialog');
		link.addEventListener('click', async (event) => {
			if (event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
			event.preventDefault();
			if (sheet.open) return;
			opener = link;
			feedback.textContent = '';
			const title = document.createElement('h2');
			title.id = 'order-now-product-title';
			title.textContent = link.textContent.trim();
			content.replaceChildren(title);
			feedback.textContent = 'Loading product details…';
			scrollPosition = { x: window.scrollX, y: window.scrollY };
			document.documentElement.style.setProperty('--ek-order-scroll-y', `-${scrollPosition.y}px`);
			document.documentElement.classList.add('ek-order-sheet-open');
			sheet.showModal();
			sheet.scrollTop = 0;
			closeButton.focus({ preventScroll: true });
			loadRequest?.abort();
			const request = new AbortController();
			loadRequest = request;
			const timeout = window.setTimeout(() => request.abort(), 15000);
			try {
				const url = new URL(config.sheetUrl, window.location.href);
				url.searchParams.set('product_id', link.dataset.orderNowOpen);
				const response = await fetch(url, { signal: request.signal, credentials: 'same-origin' });
				const result = await response.json();
				if (!response.ok || !result.success) throw new Error('Product unavailable');
				if (!sheet.open || loadRequest !== request) return;
				// Trusted, same-origin markup rendered by native WooCommerce templates.
				content.innerHTML = result.data.html;
				const variationForm = content.querySelector('.variations_form');
				if (variationForm) {
					const button = variationForm.querySelector('.single_add_to_cart_button');
					if (button) button.disabled = true;
					$(variationForm).on('show_variation', (variationEvent, variation, purchasable) => {
						if (button) button.disabled = !purchasable || adding;
					}).on('hide_variation reset_data', () => { if (button) button.disabled = true; });
					$(variationForm).wc_variation_form();
				}
				feedback.textContent = '';
			} catch (error) {
				if (sheet.open && loadRequest === request) feedback.textContent = 'Product details could not be loaded. Close and try again.';
			} finally {
				window.clearTimeout(timeout);
			}
		});
	});

	sheet.addEventListener('submit', async (event) => {
		const form = event.target.closest('form.cart');
		if (!form || !content.contains(form)) return;
		event.preventDefault();
		if (adding) { feedback.textContent = 'An item is still being added. Please wait.'; return; }
		const button = form.querySelector('.single_add_to_cart_button');
		const data = new FormData(form);
		const variable = form.classList.contains('variations_form');
		const productId = variable ? Number(data.get('variation_id')) : Number(form.closest('[data-product-id]').dataset.productId);
		if (!productId || !button || button.disabled || button.classList.contains('disabled') || !form.reportValidity()) {
			feedback.textContent = 'Please choose the available product options before adding to cart.';
			return;
		}
		// The native AJAX endpoint accepts a simple ID or Woo's resolved variation ID.
		// Omit add-to-cart to avoid also invoking Woo's non-AJAX form handler.
		data.delete('add-to-cart');
		data.set('product_id', String(productId));
		adding = true;
		button.disabled = true;
		form.setAttribute('aria-busy', 'true');
		feedback.textContent = 'Adding to cart…';
		const controller = new AbortController();
		const timeout = window.setTimeout(() => controller.abort(), 20000);
		try {
			const response = await fetch(config.cartUrl, { method: 'POST', body: data, credentials: 'same-origin', signal: controller.signal });
			const result = await response.json();
			if (!response.ok || result.error || !result.fragments) throw new Error('Cart rejected');
			// Woo's event handler applies fragments; its session/cache listeners also receive the update.
			$(document.body).trigger('added_to_cart', [result.fragments, result.cart_hash]);
			updateCartLabel();
			if (sheet.open && content.contains(form)) feedback.textContent = 'Added to your cart.';
		} catch (error) {
			if (sheet.open && content.contains(form)) feedback.textContent = 'Could not confirm the add. Check your cart before trying again; availability or quantity may have changed.';
		} finally {
			window.clearTimeout(timeout);
			adding = false;
			form.removeAttribute('aria-busy');
			button.disabled = button.classList.contains('disabled');
			// A different product may have opened while this request completed.
			const currentVariationForm = content.querySelector('.variations_form');
			if (currentVariationForm) $(currentVariationForm).trigger('check_variations');
		}
	});
});
