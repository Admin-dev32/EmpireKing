document.addEventListener('DOMContentLoaded', () => {
	const section = document.querySelector('.home-stories');
	if (!section) return;
	const cards = Array.from(section.querySelectorAll('.home-stories__card'));
	if (cards.length < 2) return;
	const previous = section.querySelector('[data-stories-previous]');
	const next = section.querySelector('[data-stories-next]');
	const pagination = section.querySelector('.home-stories__pagination');
	const dots = Array.from(pagination.querySelectorAll('button'));
	const status = section.querySelector('[data-stories-status]');
	const phone = window.matchMedia('(max-width: 40rem)');
	const tablet = window.matchMedia('(max-width: 64rem)');
	let index = 0;
	const render = (announce = true) => {
		index = (index + cards.length) % cards.length;
		const count = Math.min(cards.length, phone.matches ? 1 : tablet.matches ? 2 : 3);
		const visible = Array.from({ length: count }, (_, slot) => cards[(index + slot) % cards.length]);
		const focused = cards.find((card) => card.contains(document.activeElement));
		if (focused && !visible.includes(focused)) dots[index].focus();
		// Reorder the existing nodes so reading/tab order matches the visual order.
		cards.forEach((card) => { card.hidden = !visible.includes(card); });
		visible.forEach((card) => card.parentElement.appendChild(card));
		dots.forEach((dot, slot) => dot.setAttribute('aria-current', String(slot === index)));
		if (announce) status.textContent = visible.map((card) => card.querySelector('h3').textContent).join(', ');
	};
	previous.hidden = next.hidden = pagination.hidden = false;
	previous.addEventListener('click', () => { index -= 1; render(); });
	next.addEventListener('click', () => { index += 1; render(); });
	dots.forEach((dot, slot) => dot.addEventListener('click', () => { index = slot; render(); }));
	phone.addEventListener('change', () => render(false));
	tablet.addEventListener('change', () => render(false));
	render(false);
});
