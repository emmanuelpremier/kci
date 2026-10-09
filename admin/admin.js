/* admin/admin.js — drawer, user menu, show/hide password. Plain JS, no deps. */
(function () {
  'use strict';

  function $(id) { return document.getElementById(id); }

  // Mobile drawer (sidebar) — toggles .is-drawer-open on body.kci-admin.
  var burger = $('kci-hamburger');
  var overlay = $('kci-overlay');
  function setDrawer(open) {
    var root = document.body;
    if (!root || !root.classList.contains('kci-admin')) return;
    root.classList.toggle('is-drawer-open', !!open);
    if (burger) burger.setAttribute('aria-expanded', open ? 'true' : 'false');
    if (overlay) {
      if (open) overlay.removeAttribute('hidden');
      else overlay.setAttribute('hidden', '');
    }
  }
  if (burger) {
    burger.addEventListener('click', function () {
      var open = !document.body.classList.contains('is-drawer-open');
      setDrawer(open);
    });
  }
  if (overlay) overlay.addEventListener('click', function () { setDrawer(false); });
  document.addEventListener('keydown', function (ev) {
    if (ev.key === 'Escape') { setDrawer(false); setUserMenu(false); }
  });

  // User dropdown menu.
  var userBtn = $('kci-user-btn');
  var userMenu = $('kci-user-menu');
  function setUserMenu(open) {
    if (!userBtn || !userMenu) return;
    if (open) userMenu.removeAttribute('hidden');
    else userMenu.setAttribute('hidden', '');
    userBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
  }
  if (userBtn && userMenu) {
    userBtn.addEventListener('click', function (ev) {
      ev.stopPropagation();
      setUserMenu(userMenu.hasAttribute('hidden'));
    });
    document.addEventListener('click', function (ev) {
      if (!userMenu.hasAttribute('hidden') && !userMenu.contains(ev.target) && ev.target !== userBtn && !userBtn.contains(ev.target)) {
        setUserMenu(false);
      }
    });
  }

  // Show / hide password on login + password pages.
  function wireShowPass(btnId, inputId) {
    var btn = $(btnId);
    var input = $(inputId);
    if (!btn || !input) return;
    btn.addEventListener('click', function () {
      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.setAttribute('aria-pressed', show ? 'true' : 'false');
      btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
      var icon = btn.querySelector('i');
      if (icon) {
        icon.classList.toggle('fa-eye', !show);
        icon.classList.toggle('fa-eye-slash', show);
      }
      input.focus();
    });
  }
  wireShowPass('kci-showpass', 'kci-password');
  wireShowPass('kci-showpass-new', 'kci-password-new');
  wireShowPass('kci-showpass-confirm', 'kci-password-confirm');
})();
