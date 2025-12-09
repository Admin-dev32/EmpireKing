// assets/js/base.js

document.addEventListener('DOMContentLoaded', function () {
    initBurgerMenu();
    initScrollAnimations();
    initOrderPageNavigation();
});

function initBurgerMenu() {
    var body = document.body;
    var burger = document.querySelector('.ek-burger');
    var drawer = document.querySelector('.ek-drawer');
    var overlay = document.querySelector('.ek-drawer-overlay');
    var closeBtn = document.querySelector('.ek-drawer-close');

    if (!burger || !drawer || !overlay) {
        return;
    }

    function openDrawer() {
        body.classList.add('drawer-open');
        body.style.overflow = 'hidden';
    }

    function closeDrawer() {
        body.classList.remove('drawer-open');
        body.style.overflow = '';
    }

    burger.addEventListener('click', openDrawer);
    if (closeBtn) {
        closeBtn.addEventListener('click', closeDrawer);
    }
    overlay.addEventListener('click', closeDrawer);
}

function initScrollAnimations() {
    var animatedEls = document.querySelectorAll('.animate-on-scroll');

    if (!animatedEls.length) {
        return;
    }

    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            root: null,
            rootMargin: '0px 0px -10% 0px',
            threshold: 0.1
        });

        animatedEls.forEach(function (el) { observer.observe(el); });
    } else {
        animatedEls.forEach(function (el) { el.classList.add('is-visible'); });
    }
}

function initOrderPageNavigation() {
    var orderMain = document.querySelector('.ek-main-order');

    if (!orderMain) {
        return;
    }

    var currentCat = orderMain.getAttribute('data-current-cat') || '';
    var catSections = document.querySelectorAll('[data-cat-section]');
    var catPills = document.querySelectorAll('.ek-order-cat-pill');

    if (currentCat) {
        var targetSection = document.querySelector('[data-cat-section="' + currentCat + '"]');
        if (targetSection) {
            setTimeout(function () {
                targetSection.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }, 250);
        }
    }

    if (!catSections.length || !catPills.length) {
        return;
    }

    function setActivePill(slug) {
        catPills.forEach(function (pill) {
            pill.classList.remove('is-active');
        });
        var match = document.querySelector('.ek-order-cat-pill[data-cat="' + slug + '"]');
        if (match) {
            match.classList.add('is-active');
        }
    }

    if ('IntersectionObserver' in window) {
        var sectionObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    var slug = entry.target.getAttribute('data-cat-section');
                    if (slug) {
                        setActivePill(slug);
                    }
                }
            });
        }, {
            root: null,
            rootMargin: '0px 0px -50% 0px',
            threshold: 0.2
        });

        catSections.forEach(function (section) { sectionObserver.observe(section); });
    }
}
