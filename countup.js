/* countup.js — featured-ministry stats count-up (min.php).
   Plain JavaScript, no libraries. One IntersectionObserver
   (threshold 0.4) on .featured-ministry__stats, runs once.
   Numbers count 0 -> data-count-to over ~1.8s with ease-out,
   keeping data-count-suffix fixed; always ends on the exact
   final text. With prefers-reduced-motion, without
   IntersectionObserver/requestAnimationFrame, or without JS,
   the server-rendered final values stay visible untouched. */
(function () {
  var statsRow = document.querySelector('.ministries-page .featured-ministry__stats');
  if (!statsRow) return;

  var numbers = statsRow.querySelectorAll('.featured-ministry__num[data-count-to]');
  if (!numbers.length) return;

  function finalText(el) {
    return String(el.getAttribute('data-count-to') || '') +
      String(el.getAttribute('data-count-suffix') || '');
  }

  /* Reduced motion or no observer/rAF: show final values now. */
  var reduced = window.matchMedia &&
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (reduced || !('IntersectionObserver' in window) ||
      !('requestAnimationFrame' in window)) {
    numbers.forEach(function (el) { el.textContent = finalText(el); });
    return;
  }

  var DURATION = 1800;
  var started = false;

  /* easeOutCubic: fast start, gentle landing. */
  function easeOut(t) { return 1 - Math.pow(1 - t, 3); }

  function animate() {
    var start = null;
    function frame(now) {
      if (start === null) start = now;
      var progress = Math.min((now - start) / DURATION, 1);
      var eased = easeOut(progress);
      numbers.forEach(function (el) {
        var target = parseInt(el.getAttribute('data-count-to'), 10);
        var suffix = el.getAttribute('data-count-suffix') || '';
        if (isNaN(target)) {
          el.textContent = finalText(el);
          return;
        }
        if (progress >= 1) {
          el.textContent = String(target) + suffix;
        } else {
          el.textContent = String(Math.round(target * eased)) + suffix;
        }
      });
      if (progress < 1) {
        window.requestAnimationFrame(frame);
      } else {
        /* Guarantee the exact final text (no rounding drift). */
        numbers.forEach(function (el) { el.textContent = finalText(el); });
      }
    }
    /* Start every number at 0 only when animation can run. */
    numbers.forEach(function (el) {
      var suffix = el.getAttribute('data-count-suffix') || '';
      el.textContent = '0' + suffix;
    });
    window.requestAnimationFrame(frame);
  }

  var observer = new IntersectionObserver(function (entries, obs) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting && !started) {
        started = true;
        animate();
        obs.unobserve(entry.target);
      }
    });
  }, { threshold: 0.4 });

  observer.observe(statsRow);
})();
