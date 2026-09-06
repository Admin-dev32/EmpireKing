document.addEventListener('DOMContentLoaded', () => {
	const dialog = document.querySelector('#mobile-navigation');
	const openButton = document.querySelector('.header-menu-toggle');
	const closeButton = document.querySelector('.mobile-navigation__close');
	const header = document.querySelector('[data-header]');

	if (!dialog || !openButton || !closeButton || typeof dialog.showModal !== 'function') {
		return;
	}

	const closeMenu = () => {
		if (dialog.open) {
			dialog.close();
		}
	};

	openButton.addEventListener('click', () => {
		dialog.showModal();
		openButton.setAttribute('aria-expanded', 'true');
	});

	closeButton.addEventListener('click', closeMenu);
	dialog.querySelectorAll('a').forEach((link) => link.addEventListener('click', closeMenu));
	dialog.addEventListener('close', () => openButton.setAttribute('aria-expanded', 'false'));

	window.addEventListener('resize', () => {
		if (window.matchMedia('(min-width: 48rem)').matches) {
			closeMenu();
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
