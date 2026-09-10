document.addEventListener('DOMContentLoaded', () => {
	const rail = document.querySelector('[data-order-now-categories]');
	const cards = Array.from(document.querySelectorAll('[data-order-now-product]'));
	const status = document.querySelector('[data-order-now-status]');

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
	const consumeSheetErrors = async () => {
		if (!config.noticeUrl || !config.noticeNonce) return '';
		try {
			const body = new URLSearchParams({ security: config.noticeNonce });
			const response = await fetch(config.noticeUrl, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
				body,
			});
			const result = await response.json();
			if (response.ok && result.success && Array.isArray(result.data?.messages) && result.data.messages.length) {
				return result.data.messages.join(' ');
			}
		} catch (error) {
			// The generic failure message remains available if notice retrieval is unavailable.
		}
		return '';
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

	const renderProductSheet = (html, editData = null) => {
		disposeApf();
		$(content).empty();
		content.innerHTML = html;
		initializeApf();

		const form = content.querySelector('form.cart');
		const variationForm = content.querySelector('.variations_form');
		const button = form?.querySelector('.single_add_to_cart_button');

		if (variationForm) {
			if (button && !editData) button.disabled = true;
			$(variationForm).on('show_variation', (variationEvent, variation, purchasable) => {
				if (button) {
					button.disabled = !purchasable || adding;
					if (form?.dataset.cartItemKey) button.textContent = 'Save Changes';
				}
			}).on('hide_variation reset_data', () => {
				if (button) button.disabled = true;
			});
			$(variationForm).wc_variation_form();
		}

		if (editData) {
			if (form) {
				form.dataset.cartItemKey = editData.cart_item_key;
			}
			if (button) {
				button.textContent = 'Save Changes';
				button.disabled = false;
				button.classList.remove('disabled');
			}

			// 1. Restore saved quantity
			if (editData.quantity && form) {
				const qtyInput = form.querySelector('input.qty, input[name="quantity"]');
				if (qtyInput) {
					qtyInput.value = editData.quantity;
					qtyInput.dispatchEvent(new Event('change', { bubbles: true }));
					$(qtyInput).trigger('change');
				}
			}

			// 2. Restore saved variation attributes
			if (variationForm && editData.variation) {
				Object.entries(editData.variation).forEach(([attrName, attrVal]) => {
					const select = variationForm.querySelector(`select[name="${attrName}"]`);
					if (select) {
						select.value = attrVal;
						select.dispatchEvent(new Event('change', { bubbles: true }));
						$(select).trigger('change');
					}
				});
				$(variationForm).trigger('check_variations');
				if (button) {
					button.textContent = 'Save Changes';
				}
			}

			// 3. Restore saved APF modifier options
			if (editData.wapf && Array.isArray(editData.wapf)) {
				editData.wapf.forEach((field) => {
					if (!field.id || field.raw === undefined || field.raw === null) return;
					const fieldId = field.id;
					const rawVal = field.raw;

					const inputs = Array.from(content.querySelectorAll(
						`[data-field-id="${fieldId}"], [name="wapf[field_${fieldId}]"], [name="wapf[field_${fieldId}][]"]`
					));

					inputs.forEach((input) => {
						const tag = input.tagName.toLowerCase();
						const type = input.type ? input.type.toLowerCase() : '';

						if (type === 'radio') {
							if (input.value === String(rawVal)) {
								input.checked = true;
								input.dispatchEvent(new Event('change', { bubbles: true }));
								$(input).trigger('change');
							}
						} else if (type === 'checkbox') {
							const isChecked = Array.isArray(rawVal)
								? rawVal.map(String).includes(input.value)
								: (String(rawVal) === input.value || rawVal === true || rawVal === 'yes' || rawVal === '1');
							input.checked = isChecked;
							if (isChecked) {
								input.dispatchEvent(new Event('change', { bubbles: true }));
								$(input).trigger('change');
							}
						} else if (tag === 'select') {
							input.value = String(rawVal);
							input.dispatchEvent(new Event('change', { bubbles: true }));
							$(input).trigger('change');
						} else if (tag === 'textarea' || type === 'text' || type === 'number') {
							input.value = String(rawVal);
							input.dispatchEvent(new Event('input', { bubbles: true }));
							input.dispatchEvent(new Event('change', { bubbles: true }));
							$(input).trigger('change');
						}
					});
				});
			}
		}
	};

	const makePreview = (trigger) => {
		const card = trigger.closest('[data-order-now-product], .ek-cart-card');
		const preview = document.createElement('div');
		preview.className = 'product ek-order-now__sheet-product ek-order-now__sheet-preview';
		const imgEl = card?.querySelector('.ek-order-now__product-image img, .ek-cart-card__image img');
		if (imgEl) {
			const imageContainer = document.createElement('div');
			imageContainer.className = 'ek-order-now__sheet-image';
			imageContainer.append(imgEl.cloneNode(true));
			preview.append(imageContainer);
		}
		const title = document.createElement('h2');
		title.id = 'order-now-product-title';
		title.textContent = card?.querySelector('.ek-cart-card__title, [data-order-now-open]')?.textContent.trim() || trigger.textContent.trim();
		preview.append(title);
		const price = card?.querySelector('.ek-order-now__product-price, .ek-cart-card__price');
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

	const openProductSheet = async (trigger) => {
		if (sheet.open) return;
		opener = trigger;
		feedback.textContent = '';
		content.replaceChildren(makePreview(trigger));
		scrollPosition = { x: window.scrollX, y: window.scrollY };
		document.documentElement.style.setProperty('--ek-order-scroll-y', `-${scrollPosition.y}px`);
		document.documentElement.classList.add('ek-order-sheet-open');
		sheet.showModal();
		sheet.scrollTop = 0;
		closeButton.focus({ preventScroll: true });

		const productId = trigger.dataset.orderNowOpen || trigger.dataset.productId;
		const cartItemKey = trigger.dataset.cartEditItem || null;

		if (!cartItemKey && productSheets.has(productId)) {
			renderProductSheet(productSheets.get(productId));
			return;
		}

		loadRequest?.abort();
		const request = new AbortController();
		loadRequest = request;
		const timeout = window.setTimeout(() => request.abort(), 15000);
		try {
			const url = new URL(config.sheetUrl, window.location.href);
			if (productId) url.searchParams.set('product_id', productId);
			if (cartItemKey) url.searchParams.set('cart_item_key', cartItemKey);
			const response = await fetch(url, { signal: request.signal, credentials: 'same-origin' });
			const result = await response.json();
			if (!response.ok || !result.success) throw new Error('Product unavailable');
			if (!sheet.open || loadRequest !== request) return;
			if (!cartItemKey) {
				productSheets.set(productId, result.data.html);
			}
			renderProductSheet(result.data.html, result.data.edit_data || null);
		} catch (error) {
			if (sheet.open && loadRequest === request) feedback.textContent = 'Product details could not be loaded. Close and try again.';
		} finally {
			window.clearTimeout(timeout);
			if (loadRequest === request) loadRequest = null;
		}
	};

	document.querySelectorAll('[data-order-now-open], [data-cart-edit-item]').forEach((el) => {
		el.setAttribute('aria-haspopup', 'dialog');
	});

	document.addEventListener('click', (event) => {
		if (event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
		const trigger = event.target.closest('[data-order-now-open], [data-cart-edit-item]');
		if (!trigger) return;
		event.preventDefault();
		openProductSheet(trigger);
	});

	sheet.addEventListener('submit', async (event) => {
		const form = event.target.closest('form.cart');
		if (!form || !content.contains(form)) return;
		event.preventDefault();
		if (adding) return;
		const button = form.querySelector('.single_add_to_cart_button');
		const data = new FormData(form);
		const variable = form.classList.contains('variations_form');
		const parentId = Number(form.closest('[data-product-id]')?.dataset.productId || 0);
		const variationId = variable ? Number(data.get('variation_id') || 0) : 0;
		const productId = variable ? variationId : parentId;

		if (!productId || !button || button.disabled || button.classList.contains('disabled') || !form.reportValidity()) {
			feedback.textContent = 'Please choose the available product options before adding to cart.';
			return;
		}

		data.delete('add-to-cart');
		const isEdit = Boolean(form.dataset.cartItemKey);
		// Woo's add_to_cart resolves a variable product only when product_id is its selected variation ID.
		data.set('product_id', String(isEdit ? parentId : productId));
		if (variable && variationId) {
			data.set('variation_id', String(variationId));
		}

		if (isEdit) {
			data.set('cart_item_key', form.dataset.cartItemKey);
			if (config.editNonce) {
				data.set('security', config.editNonce);
			}
		}

		adding = true;
		button.disabled = true;
		button.classList.add('is-busy');
		button.setAttribute('aria-label', isEdit ? 'Saving changes' : 'Adding to cart');
		form.setAttribute('aria-busy', 'true');
		const controller = new AbortController();
		addRequest = controller;
		const timeout = window.setTimeout(() => controller.abort(), 20000);
		try {
			const targetUrl = isEdit ? config.editCartUrl : config.cartUrl;
			const response = await fetch(targetUrl, { method: 'POST', body: data, credentials: 'same-origin', signal: controller.signal });
			const result = await response.json();

			if (isEdit) {
				if (!response.ok || !result.success) {
					throw new Error(result.data?.message || 'Could not update item.');
				}
				window.location.reload();
				return;
			}

			if (!response.ok || result.error || !result.fragments) {
				throw new Error((await consumeSheetErrors()) || 'Cart rejected');
			}
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
			if (sheet.open && content.contains(form)) {
				feedback.textContent = error.message || (isEdit ? 'Could not update this item.' : 'Could not add this item. Availability or quantity may have changed.');
			}
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

	// Auto-open product customization sheet when directed to /order-now/?product_id=ID
	const searchParams = new URLSearchParams(window.location.search);
	const autoProductId = searchParams.get('product_id');
	if (autoProductId) {
		const autoTrigger = document.querySelector(`[data-order-now-open="${autoProductId}"]`);
		if (autoTrigger) {
			window.setTimeout(() => {
				autoTrigger.click();
			}, 100);
		}
	}
});
