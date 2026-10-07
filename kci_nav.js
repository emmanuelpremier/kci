/**
 * kci_nav.js — Mobile menu close-on-scroll behavior.
 *
 * Adds closing behavior to the existing inline mobile menu scripts.
 * Pure vanilla JavaScript, no libraries. Works through footer.php on
 * every page. Does NOT edit or duplicate the existing inline menu
 * scripts (the #menuToggle click handler + dropdown toggle) — it only
 * makes the open menu close when the visitor scrolls, taps/clicks
 * outside the menu, or presses Escape.
 */
(function () {
	'use strict';

	var mainNav = document.getElementById('mainNav');
	var menuToggle = document.getElementById('menuToggle');

	if (!mainNav || !menuToggle) {
		return;
	}

	// Do nothing on desktop.
	if (window.innerWidth > 900) {
		return;
	}

	var openY = 0;
	var touchStartTarget = null;

	/**
	 * closeMenu(): remove "open" from #mainNav, "active" from #menuToggle
	 * and "dropdown-open" from every <li> inside #mainNav.
	 */
	function closeMenu() {
		mainNav.classList.remove('open');
		menuToggle.classList.remove('active');

		var dropdowns = mainNav.querySelectorAll('li.dropdown-open');
		for (var i = 0; i < dropdowns.length; i++) {
			dropdowns[i].classList.remove('dropdown-open');
		}

		menuToggle.setAttribute('aria-expanded', 'false');
	}

	// --- MutationObserver on #mainNav's class attribute -------------------
	// When "open" is added, remember window.scrollY as openY;
	// keep aria-expanded on #menuToggle in sync ("true" when open,
	// "false" otherwise).
	var observer = new MutationObserver(function (mutations) {
		var isOpen = mainNav.classList.contains('open');
		mutations.forEach(function (mutation) {
			if (mutation.type === 'attributes' &&
				mutation.attributeName === 'class') {
				if (isOpen) {
					openY = window.scrollY;
					menuToggle.setAttribute('aria-expanded', 'true');
				} else {
					menuToggle.setAttribute('aria-expanded', 'false');
				}
			}
		});
	});

	observer.observe(mainNav, {
		attributes: true,
		attributeFilter: ['class']
	});

	// --- Window scroll (passive listener) ----------------------------------
	// If the menu is open and Math.abs(window.scrollY - openY) > 12, close
	// it. Ignore scrolling that starts as a touch inside #mainNav so a tall
	// menu that scrolls itself does not close.
	window.addEventListener('scroll', function () {
		if (window.innerWidth > 900) {
			return;
		}
		if (mainNav.classList.contains('open')) {
			var scrolledEnough = Math.abs(window.scrollY - openY) > 12;
			var startedTouchInside =
				touchStartTarget !== null && mainNav.contains(touchStartTarget);
			if (scrolledEnough && !startedTouchInside) {
				closeMenu();
			}
		}
	}, { passive: true });

	// Track the touchstart target so scrolling that starts inside the menu
	// (a tall menu scrolling itself) is ignored.
	mainNav.addEventListener('touchstart', function (e) {
		touchStartTarget = e.target;
	}, { passive: true });

	mainNav.addEventListener('touchend', function (e) {
		touchStartTarget = null;
	}, { passive: true });

	mainNav.addEventListener('touchcancel', function (e) {
		touchStartTarget = null;
	}, { passive: true });

	// --- Escape key ---------------------------------------------------------
	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape' && mainNav.classList.contains('open')) {
			closeMenu();
		}
	});

	// --- Tap or click outside #mainNav and #menuToggle ----------------------
	document.addEventListener('click', function (e) {
		var target = e.target;
		var isInside =
			mainNav.contains(target) || menuToggle.contains(target);
		if (!isInside && mainNav.classList.contains('open')) {
			closeMenu();
		}
	});
})();
