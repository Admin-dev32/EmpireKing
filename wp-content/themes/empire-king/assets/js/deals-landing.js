document.addEventListener('DOMContentLoaded', () => {
	const welcome = document.querySelector('.ek-deals-welcome');
	const target = document.querySelector('#deals-start');
	if (!welcome || !target) return;

	const isDealsDeepLink = /^#(?:deals-start|all-deals|deal-category-|deal-)/.test(window.location.hash);
	if (isDealsDeepLink) return;

	const cta = welcome.querySelector('.ek-deals-welcome__deals-link');
	const heading = target.querySelector('h1');
	const underlying = [document.querySelector('.site-header'), document.querySelector('.ek-deals'), document.querySelector('.site-footer')].filter(Boolean);
	let isClosing = false;
	let closeTimer;

	const setUnderlyingInert = (isInert) => {
		underlying.forEach((element) => {
			element.inert = isInert;
			if (isInert) element.setAttribute('aria-hidden', 'true');
			else element.removeAttribute('aria-hidden');
		});
	};

	const finishClose = () => {
		window.clearTimeout(closeTimer);
		welcome.hidden = true;
		welcome.classList.remove('is-open', 'is-closing');
		document.documentElement.classList.remove('ek-deals-welcome-open');
		document.body.classList.remove('ek-deals-welcome-open');
		setUnderlyingInert(false);
		heading?.focus({ preventScroll: true });
		document.removeEventListener('keydown', onKeyDown);
	};

	const dismiss = () => {
		if (isClosing) return;
		isClosing = true;
		target.scrollIntoView({ block: 'start' });
		welcome.classList.remove('is-open');
		welcome.classList.add('is-closing');
		closeTimer = window.setTimeout(finishClose, 800);
	};

	const onKeyDown = (event) => {
		if (event.key === 'Escape') {
			event.preventDefault();
			dismiss();
		}
	};

	welcome.addEventListener('transitionend', (event) => {
		if (event.target !== welcome) return;
		if (isClosing) finishClose();
	});
	cta?.addEventListener('click', (event) => {
		event.preventDefault();
		dismiss();
	});
	setUnderlyingInert(true);
	document.documentElement.classList.add('ek-deals-welcome-open');
	document.body.classList.add('ek-deals-welcome-open');
	welcome.hidden = false;
	document.addEventListener('keydown', onKeyDown);
	window.requestAnimationFrame(() => {
		welcome.classList.add('is-open');
		window.setTimeout(() => {
			if (!isClosing && !welcome.hidden) cta?.focus({ preventScroll: true });
		}, 750);
	});
});
