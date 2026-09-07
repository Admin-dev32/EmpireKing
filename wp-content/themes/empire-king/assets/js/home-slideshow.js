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
	let isPaused = reducedMotion;
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
		if (!isPaused) intervalId = window.setInterval(() => showSlide(activeIndex + 1), 5500);
	};

	const setPaused = (paused) => {
		isPaused = paused;
		toggle.setAttribute('aria-pressed', String(paused));
		toggle.setAttribute('aria-label', paused ? 'Play slideshow' : 'Pause slideshow');
		toggle.firstElementChild.textContent = paused ? '▶' : 'Ⅱ';
		startAutoplay();
	};

	previous.addEventListener('click', () => showSlide(activeIndex - 1));
	next.addEventListener('click', () => showSlide(activeIndex + 1));
	dots.forEach((dot, index) => dot.addEventListener('click', () => showSlide(index)));
	toggle.addEventListener('click', () => setPaused(!isPaused));
	slideshow.addEventListener('focusin', () => setPaused(true));
	startAutoplay();
});
