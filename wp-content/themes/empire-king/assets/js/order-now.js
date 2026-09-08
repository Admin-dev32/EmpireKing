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
});
