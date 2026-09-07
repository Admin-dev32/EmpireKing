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
	const CATEGORY_TRANSITION_MS = 200;
	const IMAGE_READY_TIMEOUT_MS = 400;
	let timer = null;
	let categoryTransitionTimer = null;
	let playbackRun = 0;
	let activePanel = panels.find(panel => !panel.hidden) || panels[0];

	const prepareImage = (image, useTimeout = true) => {
		if (!image) return Promise.resolve();
		image.loading = 'eager';
		const decode = () => {
			const decoded = typeof image.decode === 'function' ? image.decode().catch(() => undefined) : Promise.resolve();
			return useTimeout ? Promise.race([decoded, new Promise(resolve => window.setTimeout(resolve, IMAGE_READY_TIMEOUT_MS))]) : decoded;
		};
		if (image.complete) return decode();
		const loaded = Promise.race([
			new Promise(resolve => image.addEventListener('load', () => decode().then(resolve), { once: true })),
			new Promise(resolve => image.addEventListener('error', resolve, { once: true })),
		]);
		return useTimeout ? Promise.race([loaded, new Promise(resolve => window.setTimeout(resolve, IMAGE_READY_TIMEOUT_MS))]) : loaded;
	};

	const prepareFrames = frames => Promise.all(frames.map(frame => prepareImage(frame.querySelector('img'))));
	const prepareInitialVisual = panel => Promise.all([
		prepareImage(panel.querySelector('.home-menu-glimpse__background'), false),
		prepareImage(panel.querySelector('.home-menu-glimpse__frame--food img'), false),
	]);
	const resetFrames = panel => Array.from(panel.querySelectorAll('[data-menu-glimpse-frame]')).forEach((frame, index) => {
		frame.classList.toggle('is-current', index === 0);
	});

	const clearCategoryTransition = () => {
		window.clearTimeout(categoryTransitionTimer);
		categoryTransitionTimer = null;
		panels.forEach((panel) => {
			panel.classList.remove('is-category-entering', 'is-category-visible', 'is-category-leaving');
			panel.hidden = panel !== activePanel;
		});
		activePanel.hidden = false;
	};

	const startPlayback = (panel, run) => {
		const frames = Array.from(panel.querySelectorAll('[data-menu-glimpse-frame]'));
		let index = reducedMotion.matches ? frames.length - 1 : 0;
		const render = () => {
			frames.forEach((frame, frameIndex) => frame.classList.toggle('is-current', frameIndex === index));
			if (index < frames.length - 1 && !reducedMotion.matches) timer = window.setTimeout(() => {
				if (run !== playbackRun) return;
				index += 1;
				render();
			}, FRAME_DURATION_MS);
		};
		if (!reducedMotion.matches) prepareFrames(frames.slice(1));
		render();
	};

	const showCategory = (key, announce = true) => {
		window.clearTimeout(timer);
		const run = ++playbackRun;
		const panel = panels.find(item => item.dataset.menuGlimpsePanel === key);
		if (!panel) return;
		tabs.forEach(tab => tab.setAttribute('aria-selected', String(tab.dataset.menuGlimpseCategory === key)));
		clearCategoryTransition();
		resetFrames(panel);
		prepareInitialVisual(panel).finally(() => {
			if (run !== playbackRun) return;
			if (panel === activePanel) {
				startPlayback(panel, run);
				return;
			}

			const outgoingPanel = activePanel;
			panel.hidden = false;
			panel.classList.add('is-category-entering');
			void panel.offsetWidth;
			if (reducedMotion.matches) {
				panel.classList.remove('is-category-entering');
				outgoingPanel.hidden = true;
				activePanel = panel;
				startPlayback(panel, run);
				return;
			}

			window.requestAnimationFrame(() => {
				if (run !== playbackRun) return;
				panel.classList.add('is-category-visible');
				outgoingPanel.classList.add('is-category-leaving');
				categoryTransitionTimer = window.setTimeout(() => {
					if (run !== playbackRun) return;
					outgoingPanel.hidden = true;
					outgoingPanel.classList.remove('is-category-leaving');
					panel.classList.remove('is-category-entering', 'is-category-visible');
					activePanel = panel;
					startPlayback(panel, run);
				}, CATEGORY_TRANSITION_MS);
			});
		});
		if (announce && status) status.textContent = `${panel.querySelector('h3').textContent} selected`;
	};

	const prewarmCategory = (key) => {
		const panel = panels.find(item => item.dataset.menuGlimpsePanel === key);
		if (panel) prepareInitialVisual(panel);
	};

	tabs.forEach((tab) => {
		tab.addEventListener('click', () => showCategory(tab.dataset.menuGlimpseCategory));
		tab.addEventListener('pointerenter', () => prewarmCategory(tab.dataset.menuGlimpseCategory));
		tab.addEventListener('focus', () => prewarmCategory(tab.dataset.menuGlimpseCategory));
	});
	reducedMotion.addEventListener('change', () => showCategory(tabs.find(tab => tab.getAttribute('aria-selected') === 'true').dataset.menuGlimpseCategory, false));
	showCategory(tabs.find(tab => tab.getAttribute('aria-selected') === 'true').dataset.menuGlimpseCategory, false);
});
