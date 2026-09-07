document.addEventListener('DOMContentLoaded', () => {
	const section = document.querySelector('.home-menu-glimpse');
	if (!section) return;
	const tabs = Array.from(section.querySelectorAll('[data-menu-glimpse-category]'));
	const panels = Array.from(section.querySelectorAll('[data-menu-glimpse-panel]'));
	const status = section.querySelector('[data-menu-glimpse-status]');
	const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
	if (!tabs.length || !panels.length) return;
	section.classList.add('is-enhanced');
	const FRAME_DURATION_MS = 900;
	const IMAGE_READY_TIMEOUT_MS = 400;
	let timer = null;
	let playbackRun = 0;
	const prepareFrames = frames => Promise.all(frames.map(frame => {
		const image = frame.querySelector('img');
		if (!image) return Promise.resolve();
		const decode = () => Promise.race([
			typeof image.decode === 'function' ? image.decode().catch(() => undefined) : Promise.resolve(),
			new Promise(resolve => window.setTimeout(resolve, IMAGE_READY_TIMEOUT_MS)),
		]);
		if (image.complete) return decode();
		return Promise.race([
			new Promise(resolve => image.addEventListener('load', () => decode().then(resolve), { once: true })),
			new Promise(resolve => image.addEventListener('error', resolve, { once: true })),
			new Promise(resolve => window.setTimeout(resolve, IMAGE_READY_TIMEOUT_MS)),
		]);
	}));
	const showCategory = (key, announce = true) => {
		window.clearTimeout(timer);
		const run = ++playbackRun;
		const panel = panels.find(item => item.dataset.menuGlimpsePanel === key);
		if (!panel) return;
		tabs.forEach(tab => tab.setAttribute('aria-selected', String(tab.dataset.menuGlimpseCategory === key)));
		panels.forEach(item => { item.hidden = item !== panel; });
		const frames = Array.from(panel.querySelectorAll('[data-menu-glimpse-frame]'));
		let index = 0;
		const render = () => {
			frames.forEach((frame, frameIndex) => frame.classList.toggle('is-current', frameIndex === index));
			if (index < frames.length - 1 && !reducedMotion.matches) timer = window.setTimeout(() => {
				if (run !== playbackRun) return;
				index += 1;
				render();
			}, FRAME_DURATION_MS);
		};
		if (reducedMotion.matches) {
			index = frames.length - 1;
			render();
		} else {
			frames.forEach((frame, frameIndex) => frame.classList.toggle('is-current', frameIndex === 0));
			prepareFrames(frames).finally(() => {
				if (run !== playbackRun) return;
				render();
			});
		}
		if (announce && status) status.textContent = `${panel.querySelector('h3').textContent} selected`;
	};
	tabs.forEach(tab => tab.addEventListener('click', () => showCategory(tab.dataset.menuGlimpseCategory)));
	reducedMotion.addEventListener('change', () => showCategory(tabs.find(tab => tab.getAttribute('aria-selected') === 'true').dataset.menuGlimpseCategory, false));
	showCategory(tabs.find(tab => tab.getAttribute('aria-selected') === 'true').dataset.menuGlimpseCategory, false);
});
