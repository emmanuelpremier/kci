/* ============================================================
   kci-reveal.js — the shared scroll-reveal engine used by every
   KCI page (Home, About, Events, Event Detail, First-Timer,
   Giving, Location, Ministries, Ministry Detail, Quotes, Serve).
   It used to be copy-pasted inline into each page; it now lives
   here once so every page animates exactly the same way.

   Pages configure it through attributes on <body>:

     data-reveal-ready="home-anim-ready"
         The class added to <body> before the first paint. Every
         hidden "before" state in kci.css is gated behind it, so
         when JavaScript is disabled, unsupported or fails, the
         content simply stays visible.
     data-reveal-targets=".home-page .home-reveal, ..."
         Comma-separated selector list observed by the single
         IntersectionObserver.
     data-reveal-margin="0px 0px -6% 0px"        (optional;
         default "0px 0px -6% 0px").
     data-reveal-safety="load" | "resize"        (optional)
         "resize": reveal anything already in view again after a
         viewport change. "load": same, plus one pass as soon as
         the DOM is ready.
     data-reveal-reflow=".ministries-filters"    (optional)
         Re-run the safety pass after clicks on this element
         (used by the Ministries category filter, which re-shows
         cards and reflows the grid).

   It is included directly after <body> so the priming class is
   added before the first paint (the parser blocks on this file,
   exactly like the old inline script). Observation starts at
   DOMContentLoaded - the same moment the old bottom-of-page
   inline scripts used to run.

   Fail-safe: if anything goes wrong (bad selector, no matching
   targets, no IntersectionObserver) the priming class is removed
   again, so no content can ever be left invisible.
   ============================================================ */
(function () {
	'use strict';

	var body = document.body;
	if (!body) return;

	var readyClass = body.getAttribute('data-reveal-ready');
	/* Page does not use the reveal system: nothing to do. */
	if (!readyClass) return;

	/* Never hide anything when motion is reduced or unsupported. */
	var reduced = window.matchMedia &&
		window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	if (reduced || !('IntersectionObserver' in window)) return;

	body.classList.add(readyClass);

	function unlock() {
		body.classList.remove(readyClass);
	}

	var spec = body.getAttribute('data-reveal-targets');
	if (!spec) { unlock(); return; }

	var rootMargin = body.getAttribute('data-reveal-margin') ||
		'0px 0px -6% 0px';
	var safety = body.getAttribute('data-reveal-safety') || '';
	var reflow = body.getAttribute('data-reveal-reflow');

	function start() {
		var targets;

		try {
			targets = document.querySelectorAll(spec);
		} catch (error) {
			unlock();
			return;
		}

		/* Nothing matched: never leave the page in its hidden state. */
		if (!targets.length) { unlock(); return; }

		function reveal(element) {
			if (element.classList.contains('is-visible')) return;
			element.classList.add('is-visible');
		}

		/* Safety net: nothing is ever left invisible. Anything already
		   on screen after a layout change (an image, font or viewport
		   resize shifting content) is revealed at once. Elements hidden
		   by a filter are skipped, so a filtered-out card can never
		   appear before it matches the active category. */
		function revealInView() {
			var viewport = window.innerHeight ||
				document.documentElement.clientHeight;
			targets.forEach(function (element) {
				if (element.classList.contains('is-visible')) return;
				if (element.classList.contains('is-hidden')) return;
				var rect = element.getBoundingClientRect();
				if (rect.width === 0 && rect.height === 0) return;
				if (rect.top <= viewport * 0.94 && rect.bottom >= 0) reveal(element);
			});
		}

		var observer;

		try {
			observer = new IntersectionObserver(function (entries) {
				entries.forEach(function (entry) {
					if (!entry.isIntersecting) return;
					/* Each element reveals once and is unobserved straight
					   afterwards; the observer itself stays in use for the
					   elements that have not arrived yet. */
					reveal(entry.target);
					observer.unobserve(entry.target);
				});
			}, { threshold: 0.15, rootMargin: rootMargin });

			targets.forEach(function (target) {
				observer.observe(target);
			});
		} catch (error) {
			/* Never leave content hidden if anything goes wrong. */
			unlock();
			return;
		}

		if (safety === 'load') revealInView();

		if (safety === 'load' || safety === 'resize') {
			var resizeTimer = null;
			window.addEventListener('resize', function () {
				if (resizeTimer) window.clearTimeout(resizeTimer);
				resizeTimer = window.setTimeout(revealInView, 120);
			});
		}

		if (reflow) {
			try {
				var reflowHost = document.querySelector(reflow);
				if (reflowHost) {
					reflowHost.addEventListener('click', function () {
						window.requestAnimationFrame(revealInView);
						window.setTimeout(revealInView, 350);
					});
				}
			} catch (error) { /* selector trouble: reveals still run */ }
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', start);
	} else {
		start();
	}
})();