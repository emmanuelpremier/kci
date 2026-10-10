/* admin/admin.js — drawer, user menu, show/hide password, confirm dialog.
   Plain JS, no deps. */
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

  // Giving Account live preview — activates only on giving-settings.php
  // (the only page with #givingPreview). Mirrors the typed bank name,
  // account number (grouped 9013 194 092), account name and card style.
  var preview = $('givingPreview');
  if (preview) {
    var bankInput = $('giving-bank');
    var numberInput = $('giving-number');
    var nameInput = $('giving-name');
    var bankOut = $('giving-preview-bank');
    var numberOut = $('giving-preview-number');
    var nameOut = $('giving-preview-name');
    var themes = ['green', 'orange', 'blue', 'purple', 'black'];

    // Group exactly 10 digits as 9013 194 092; anything else shows raw.
    function groupDigits(value) {
      var digits = String(value || '').replace(/[^0-9]/g, '');
      if (digits.length === 10) {
        return digits.slice(0, 4) + ' ' + digits.slice(4, 7) + ' ' + digits.slice(7);
      }
      return String(value || '');
    }

    function applyTheme(theme) {
      themes.forEach(function (name) {
        preview.classList.toggle('giving-preview--' + name, name === theme);
      });
    }

    function renderPreview() {
      if (bankOut) {
        bankOut.textContent = (bankInput && bankInput.value.trim()) ? bankInput.value.trim() : 'Bank name';
      }
      if (numberOut) {
        numberOut.textContent = (numberInput && numberInput.value.trim()) ? groupDigits(numberInput.value.trim()) : '0000 000 000';
      }
      if (nameOut) {
        nameOut.textContent = (nameInput && nameInput.value.trim()) ? nameInput.value.trim().toUpperCase() : 'ACCOUNT NAME';
      }
    }

    [bankInput, nameInput].forEach(function (input) {
      if (input) input.addEventListener('input', renderPreview);
    });
    if (numberInput) {
      numberInput.addEventListener('input', function () {
        var digits = numberInput.value.replace(/[^0-9]/g, '').slice(0, 10);
        if (digits !== numberInput.value) numberInput.value = digits;
        renderPreview();
      });
    }
    var themeInputs = document.querySelectorAll('input[name="card_theme"]');
    themeInputs.forEach(function (input) {
      input.addEventListener('change', function () {
        if (input.checked) applyTheme(input.value);
      });
    });
    renderPreview();
  }

  // Confirm dialog for destructive / review actions (e.g. Serve Accept,
  // Reject and Delete). Any form carrying data-confirm is intercepted and
  // the dialog built from its attributes: data-confirm (message),
  // data-confirm-title (heading) and data-confirm-note="1" (optional note
  // textarea, e.g. a rejection reason, submitted as admin_note).
  // Keyboard accessible: focuses the dialog, traps Tab, closes on Esc,
  // and returns focus to the original submit button.
  var confirmDialog = null;
  var confirmState = { form: null, trigger: null, noteInput: null };

  function getConfirmDialog() {
    if (confirmDialog) return confirmDialog;
    var overlay = document.createElement('div');
    overlay.className = 'kci-modal';
    overlay.setAttribute('hidden', '');
    overlay.innerHTML = ''
      + '<div class="kci-modal__backdrop" data-close="1"></div>'
      + '<div class="kci-modal__box" role="dialog" aria-modal="true" aria-labelledby="kci-modal-title">'
      + '<h2 class="kci-modal__title" id="kci-modal-title"></h2>'
      + '<p class="kci-modal__text" id="kci-modal-text"></p>'
      + '<label class="kci-field kci-modal__note" id="kci-modal-note" hidden>'
      + '<span>Optional note</span>'
      + '<textarea name="admin_note" maxlength="500" rows="3" placeholder="Add a short note (optional)"></textarea>'
      + '</label>'
      + '<div class="kci-actions kci-modal__actions">'
      + '<button type="button" class="kci-btn kci-btn--ghost kci-btn--sm" data-close="1">Cancel</button>'
      + '<button type="button" class="kci-btn kci-btn--primary kci-btn--sm" id="kci-modal-confirm">Confirm</button>'
      + '</div>'
      + '</div>';
    document.body.appendChild(overlay);
    confirmDialog = overlay;
    return confirmDialog;
  }

  function focusables(box) {
    var nodes = box.querySelectorAll('button, textarea, input, select, a[href], [tabindex]:not([tabindex="-1"])');
    return Array.prototype.filter.call(nodes, function (el) {
      return !el.disabled && el.offsetParent !== null;
    });
  }
  function openConfirm(form, trigger) {
    var dialog = getConfirmDialog();
    var box = dialog.querySelector('.kci-modal__box');
    var title = form.getAttribute('data-confirm-title') || 'Are you sure?';
    var text = form.getAttribute('data-confirm') || 'Do you want to continue?';
    var withNote = form.getAttribute('data-confirm-note') === '1';
    dialog.querySelector('#kci-modal-title').textContent = title;
    dialog.querySelector('#kci-modal-text').textContent = text;
    var noteWrap = dialog.querySelector('#kci-modal-note');
    var noteField = noteWrap.querySelector('textarea');
    if (withNote) {
      noteWrap.removeAttribute('hidden');
      noteField.value = '';
    } else {
      noteWrap.setAttribute('hidden', '');
      noteField.value = '';
    }
    confirmState = { form: form, trigger: trigger, noteInput: withNote ? noteField : null };
    dialog.removeAttribute('hidden');
    document.body.classList.add('kci-modal-open');
    var focusList = focusables(box);
    var first = withNote ? noteField : focusList[0];
    if (first) first.focus();
  }

  function closeConfirm() {
    var dialog = getConfirmDialog();
    dialog.setAttribute('hidden', '');
    document.body.classList.remove('kci-modal-open');
    var trigger = confirmState.trigger;
    confirmState = { form: null, trigger: null, noteInput: null };
    if (trigger && document.contains(trigger)) trigger.focus();
  }
  function submitConfirmed() {
    var form = confirmState.form;
    var note = confirmState.noteInput;
    if (!form) { closeConfirm(); return; }
    if (note) {
      var existing = form.querySelector('input[name="admin_note"]');
      if (existing && existing.parentNode) existing.parentNode.removeChild(existing);
      var hidden = document.createElement('input');
      hidden.type = 'hidden';
      hidden.name = 'admin_note';
      hidden.value = note.value;
      form.appendChild(hidden);
    }
    var trigger = confirmState.trigger;
    confirmState = { form: null, trigger: null, noteInput: null };
    getConfirmDialog().setAttribute('hidden', '');
    document.body.classList.remove('kci-modal-open');
    if (typeof form.requestSubmit === 'function') {
      form.dataset.kciConfirmed = '1';
      form.requestSubmit(trigger && trigger.type === 'submit' ? trigger : undefined);
    } else {
      form.submit();
    }
  }

  document.addEventListener('submit', function (ev) {
    var form = ev.target;
    if (!form || !form.hasAttribute || !form.hasAttribute('data-confirm')) return;
    if (form.dataset.kciConfirmed === '1') {
      delete form.dataset.kciConfirmed;
      return;
    }
    ev.preventDefault();
    var active = document.activeElement;
    var trigger = (active && form.contains(active))
      ? active
      : form.querySelector('button[type="submit"], input[type="submit"]');
    openConfirm(form, trigger);
  });

  document.addEventListener('click', function (ev) {
    if (!confirmDialog || confirmDialog.hasAttribute('hidden')) return;
    if (ev.target && ev.target.id === 'kci-modal-confirm') {
      submitConfirmed();
      return;
    }
    var closer = ev.target && ev.target.closest ? ev.target.closest('[data-close]') : null;
    if (closer && confirmDialog.contains(closer)) closeConfirm();
  });

  document.addEventListener('keydown', function (ev) {
    if (!confirmDialog || confirmDialog.hasAttribute('hidden')) return;
    if (ev.key === 'Escape') {
      ev.preventDefault();
      closeConfirm();
      return;
    }
    if (ev.key === 'Tab') {
      var box = confirmDialog.querySelector('.kci-modal__box');
      var list = focusables(box);
      if (list.length === 0) { ev.preventDefault(); return; }
      var first = list[0];
      var last = list[list.length - 1];
      if (ev.shiftKey && document.activeElement === first) {
        ev.preventDefault();
        last.focus();
      } else if (!ev.shiftKey && document.activeElement === last) {
        ev.preventDefault();
        first.focus();
      }
    }
  });
})();
