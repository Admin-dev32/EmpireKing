document.addEventListener('DOMContentLoaded', () => {
	const dialog = document.querySelector('#mobile-navigation');
	const openButton = document.querySelector('.header-menu-toggle');
	const closeButton = document.querySelector('.mobile-navigation__close');
	const header = document.querySelector('[data-header]');

	if (!dialog || !openButton || !closeButton || typeof dialog.showModal !== 'function') {
		return;
	}

	let isClosing = false;
	let closeTimer;
	let exitAnimationHandler;

	const clearCloseSequence = () => {
		window.clearTimeout(closeTimer);
		if (exitAnimationHandler) {
			dialog.removeEventListener('animationend', exitAnimationHandler);
			exitAnimationHandler = undefined;
		}
		isClosing = false;
		dialog.classList.remove('is-closing');
	};

	const finishClose = () => {
		if (!isClosing && !dialog.open) {
			return;
		}

		if (dialog.open) {
			dialog.close();
		} else {
			clearCloseSequence();
		}
	};

	const closeMenu = (immediately = false) => {
		if (!dialog.open || isClosing) {
			return;
		}

		if (immediately || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
			finishClose();
			return;
		}

		isClosing = true;
		dialog.classList.add('is-closing');
		exitAnimationHandler = (event) => {
			if (event.target === dialog && event.animationName === 'header-menu-surface-exit') {
				finishClose();
			}
		};
		dialog.addEventListener('animationend', exitAnimationHandler);
		closeTimer = window.setTimeout(finishClose, 280);
	};

	openButton.addEventListener('click', () => {
		if (dialog.open) {
			return;
		}

		clearCloseSequence();
		dialog.showModal();
		openButton.setAttribute('aria-expanded', 'true');
	});

	closeButton.addEventListener('click', () => closeMenu());
	dialog.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => closeMenu()));
	dialog.addEventListener('cancel', (event) => {
		event.preventDefault();
		closeMenu();
	});
	dialog.addEventListener('close', () => {
		clearCloseSequence();
		openButton.setAttribute('aria-expanded', 'false');
	});

	window.addEventListener('resize', () => {
		if (window.matchMedia('(min-width: 48rem)').matches) {
			closeMenu(true);
		}
	});

	if (header) {
		let scrollTicking = false;
		const updateHeaderState = () => {
			header.classList.toggle('is-scrolled', window.scrollY > 8);
			scrollTicking = false;
		};

		updateHeaderState();
		window.addEventListener('scroll', () => {
			if (!scrollTicking) {
				window.requestAnimationFrame(updateHeaderState);
				scrollTicking = true;
			}
		}, { passive: true });
	}
});
