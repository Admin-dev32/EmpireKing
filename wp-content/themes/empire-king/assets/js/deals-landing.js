document.addEventListener('DOMContentLoaded', () => {
	const pills = document.querySelector('.ek-deals__pills');
	if (pills) {
		const links = Array.from(pills.querySelectorAll('a[href^="#"]'));
		const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
		const linkForHash = (hash) => links.find((link) => link.hash === hash);

		const bringActivePillIntoView = (link) => {
			const pillStart = link.offsetLeft;
			const pillEnd = pillStart + link.offsetWidth;
			const railStart = pills.scrollLeft;
			const railEnd = railStart + pills.clientWidth;

			if (pillStart >= railStart && pillEnd <= railEnd) return;

			pills.scrollTo({
				left: Math.max( 0, pillStart - ( pills.clientWidth - link.offsetWidth ) / 2 ),
				behavior: reducedMotion.matches ? 'auto' : 'smooth',
			});
		};

		const setActivePill = (link) => {
			links.forEach((pill) => {
				const isActive = pill === link;
				pill.classList.toggle('is-active', isActive);
				if (isActive) pill.setAttribute('aria-current', 'location');
				else pill.removeAttribute('aria-current');
			});
			if (link) bringActivePillIntoView(link);
		};

		const syncActivePill = () => setActivePill(linkForHash(window.location.hash) || links[0]);

		links.forEach((link) => {
			link.addEventListener('click', (event) => {
				if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

				const target = document.getElementById(link.hash.slice(1));
				if (!target) return;

				event.preventDefault();
				setActivePill(link);
				target.scrollIntoView({ behavior: reducedMotion.matches ? 'auto' : 'smooth', block: 'start' });
				if (window.location.hash !== link.hash) window.history.pushState(null, '', link.hash);
			});
		});

		window.addEventListener('hashchange', syncActivePill);
		window.addEventListener('popstate', syncActivePill);
		syncActivePill();
	}

	const welcome = document.querySelector('.ek-deals-welcome');
	const target = document.querySelector('#deals-start');
	if (!welcome || !target) return;

	const isDealsDeepLink = /^#(?:deals-start|all-deals|deal-category-|deal-)/.test(window.location.hash);
	if (isDealsDeepLink) return;

	const cta = welcome.querySelector('.ek-deals-welcome__deals-link');
	const heading = target.querySelector('h1');
	const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
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
		if (reducedMotion) {
			finishClose();
			return;
		}
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
		if (event.target !== welcome || event.propertyName !== 'transform') return;
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
		}, reducedMotion ? 0 : 750);
	});
});
