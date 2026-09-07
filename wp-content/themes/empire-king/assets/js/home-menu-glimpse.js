document.addEventListener('DOMContentLoaded', () => {
	const section = document.querySelector('.home-menu-glimpse');
	if (!section) return;
	const tabs = Array.from(section.querySelectorAll('[data-menu-glimpse-category]'));
	const panels = Array.from(section.querySelectorAll('[data-menu-glimpse-panel]'));
	const status = section.querySelector('[data-menu-glimpse-status]');
	const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
	if (!tabs.length || !panels.length) return;
	section.classList.add('is-enhanced');
	let timer = null;
	const showCategory = (key, announce = true) => {
		window.clearTimeout(timer);
		const panel = panels.find(item => item.dataset.menuGlimpsePanel === key);
		if (!panel) return;
		tabs.forEach(tab => tab.setAttribute('aria-selected', String(tab.dataset.menuGlimpseCategory === key)));
		panels.forEach(item => { item.hidden = item !== panel; });
		const frames = Array.from(panel.querySelectorAll('[data-menu-glimpse-frame]'));
		let index = 0;
		const render = () => {
			frames.forEach((frame, frameIndex) => frame.classList.toggle('is-current', frameIndex === index));
			if (index < frames.length - 1 && !reducedMotion.matches) timer = window.setTimeout(() => { index += 1; render(); }, 1350);
		};
		if (reducedMotion.matches) index = frames.length - 1;
		render();
		if (announce && status) status.textContent = `${panel.querySelector('h3').textContent} selected`;
	};
	tabs.forEach(tab => tab.addEventListener('click', () => showCategory(tab.dataset.menuGlimpseCategory)));
	reducedMotion.addEventListener('change', () => showCategory(tabs.find(tab => tab.getAttribute('aria-selected') === 'true').dataset.menuGlimpseCategory, false));
	showCategory(tabs.find(tab => tab.getAttribute('aria-selected') === 'true').dataset.menuGlimpseCategory, false);
});
