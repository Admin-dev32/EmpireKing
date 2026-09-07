document.addEventListener('DOMContentLoaded', () => {
	const section = document.querySelector('.featured-favorites');
	if (!section) return;
	const categories = Array.from(section.querySelectorAll('[data-featured-category]'));
	const products = Array.from(section.querySelectorAll('[data-featured-item]'));
	const previous = section.querySelector('[data-featured-previous]');
	const next = section.querySelector('[data-featured-next]');
	const status = section.querySelector('[data-featured-status]');
	const mobile = window.matchMedia('(max-width: 48rem)');
	if (!categories.length || !products.length) return;
	let activeCategory = categories[0];
	let index = 0;

	const render = (announce = true) => {
		const items = products.filter((product) => product.dataset.featuredItem === activeCategory.dataset.featuredCategory);
		index = items.length ? index % items.length : 0;
		const visibleCount = Math.min(mobile.matches ? 1 : 2, items.length);
		// Return focus to the active category if resizing would hide a focused CTA.
		const focusedProduct = products.find((product) => product.contains(document.activeElement));
		const visible = Array.from({ length: visibleCount }, (_, offset) => items[(index + offset) % items.length]);
		if (focusedProduct && !visible.includes(focusedProduct)) activeCategory.focus();
		products.forEach((product) => { product.hidden = true; });
		visible.forEach((product, slot) => {
			product.hidden = false;
			product.dataset.slot = String(slot);
			product.style.order = String(slot);
		});
		categories.forEach((category) => category.setAttribute('aria-pressed', String(category === activeCategory)));
		previous.hidden = next.hidden = items.length < 2;
		if (announce) status.textContent = visible.map((product) => product.querySelector('h3').textContent).join(', ');
	};
	categories.forEach((category) => category.addEventListener('click', () => {
		activeCategory = category;
		index = 0;
		render();
	}));
	previous.addEventListener('click', () => {
		const count = products.filter((product) => product.dataset.featuredItem === activeCategory.dataset.featuredCategory).length;
		index = (index - 1 + count) % count;
		render();
	});
	next.addEventListener('click', () => { index += 1; render(); });
	mobile.addEventListener('change', () => render(false));
	render(false);
});
