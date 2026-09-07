document.addEventListener('DOMContentLoaded', () => {
	const slideshow = document.querySelector('.home-slideshow');
	if (!slideshow) return;

	const slides = Array.from(slideshow.querySelectorAll('.home-slideshow__slide'));
	const dots = Array.from(slideshow.querySelectorAll('.home-slideshow__dot'));
	const previous = slideshow.querySelector('.home-slideshow__arrow--previous');
	const next = slideshow.querySelector('.home-slideshow__arrow--next');
	const toggle = slideshow.querySelector('.home-slideshow__toggle');
	const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	let activeIndex = 0;
	let isManuallyPaused = false;
	let hasFocus = false;
	let intervalId;

	if (slides.length < 2) return;

	const showSlide = (index) => {
		activeIndex = (index + slides.length) % slides.length;
		slides.forEach((slide, slideIndex) => {
			const isActive = slideIndex === activeIndex;
			slide.classList.toggle('is-active', isActive);
			slide.setAttribute('aria-hidden', String(!isActive));
		});
		dots.forEach((dot, dotIndex) => dot.setAttribute('aria-current', String(dotIndex === activeIndex)));
	};

	const stopAutoplay = () => {
		window.clearInterval(intervalId);
		intervalId = undefined;
	};

	const startAutoplay = () => {
		stopAutoplay();
		if (!reducedMotion && !isManuallyPaused && !hasFocus) {
			intervalId = window.setInterval(() => showSlide(activeIndex + 1), 5500);
		}
	};

	const updateToggle = () => {
		const isStopped = reducedMotion || isManuallyPaused;
		toggle.setAttribute('aria-pressed', String(isStopped));
		toggle.setAttribute('aria-label', isStopped ? 'Play slideshow' : 'Pause slideshow');
		toggle.firstElementChild.textContent = isStopped ? '▶' : 'Ⅱ';
	};

	const setManuallyPaused = (paused) => {
		isManuallyPaused = paused;
		updateToggle();
		startAutoplay();
	};

	previous.addEventListener('click', () => showSlide(activeIndex - 1));
	next.addEventListener('click', () => showSlide(activeIndex + 1));
	dots.forEach((dot, index) => dot.addEventListener('click', () => showSlide(index)));
	toggle.addEventListener('click', () => setManuallyPaused(!isManuallyPaused));
	slideshow.addEventListener('focusin', () => {
		hasFocus = true;
		stopAutoplay();
	});
	slideshow.addEventListener('focusout', (event) => {
		if (slideshow.contains(event.relatedTarget)) return;
		hasFocus = false;
		startAutoplay();
	});
	updateToggle();
	startAutoplay();
});
