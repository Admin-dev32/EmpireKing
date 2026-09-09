document.addEventListener('DOMContentLoaded', () => {
	const dialog = document.querySelector('[data-order-upsell]');
	const config = window.empireKingOrderUpsell;
	if (!dialog || typeof dialog.showModal !== 'function' || !config?.recommendationsUrl || !config?.cartUrl) return;

	const products = dialog.querySelector('[data-order-upsell-products]');
	const status = dialog.querySelector('[data-order-upsell-status]');
	const close = dialog.querySelector('[data-order-upsell-close]');
	let trigger = null;
	let loading = false;
	let handingOff = false;

	const goToCart = () => { window.location.assign(config.cartUrl); };
	const closeDialog = () => { if (dialog.open) dialog.close(); };
	close.addEventListener('click', closeDialog);
	dialog.addEventListener('cancel', (event) => {
		event.preventDefault();
		closeDialog();
	});
	dialog.addEventListener('close', () => {
		document.documentElement.classList.remove('ek-order-upsell-open');
		if (!handingOff) trigger?.focus({ preventScroll: true });
		handingOff = false;
	});

	// Close first during capture so the existing delegated product-sheet handler opens cleanly.
	dialog.addEventListener('click', (event) => {
		if (!event.target.closest('[data-order-now-open]')) return;
		handingOff = true;
		closeDialog();
	}, true);

	document.addEventListener('click', async (event) => {
		const reviewLink = event.target.closest('[data-order-upsell-trigger]');
		if (!reviewLink || loading || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
		event.preventDefault();
		trigger = reviewLink;
		loading = true;
		reviewLink.setAttribute('aria-busy', 'true');
		try {
			const response = await fetch(config.recommendationsUrl, { credentials: 'same-origin' });
			const result = await response.json();
			if (!response.ok || !result.success || !result.data?.count || !result.data.html) {
				goToCart();
				return;
			}
			products.innerHTML = result.data.html;
			status.textContent = `${result.data.count} recommendations ready.`;
			document.documentElement.classList.add('ek-order-upsell-open');
			dialog.showModal();
			close.focus({ preventScroll: true });
		} catch (error) {
			// Recommendations are optional: any loading failure must preserve cart access.
			goToCart();
		} finally {
			loading = false;
			reviewLink.removeAttribute('aria-busy');
		}
	});
});
