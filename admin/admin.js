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

  // Media library: drag-and-drop zone with image preview, client shrink,
  // DataTransfer replacement and unsupported-format filtering.
  var mediaDrop = $('kci-media-drop');
  var mediaInput = $('kci-media-input');
  var mediaPreview = $('kci-media-preview');
  var mediaForm = $('kci-media-form');
  var MEDIA_MAX_BYTES = 1572864;
  var MEDIA_MAX_SIDE = 2400;
  var MEDIA_EXPORT_SIDE = 2000;
  var MEDIA_EXPORT_QUALITY = 0.85;
  var MEDIA_UNSUPPORTED_MSG = 'This format is not supported. Please choose a JPEG, PNG or WebP picture. On iPhone, set the camera format to Most Compatible or share the photo as JPEG.';
  var mediaBusy = false;
  var mediaNotes = {};
  var mediaRejected = [];
  function mediaKey(file) {
    if (!file) return '';
    return String(file.name || '') + '|' + String(file.size || 0) + '|' + String(file.type || '');
  }
  function mediaFormatSize(bytes) {
    bytes = Number(bytes) || 0;
    if (bytes < 0) bytes = 0;
    if (bytes >= 1048576) {
      var mb = bytes / 1048576;
      var r1 = Math.round(mb * 10) / 10;
      if (Math.abs(r1 - Math.round(r1)) < 0.05) return String(Math.round(r1)) + ' MB';
      return String(r1.toFixed(1)) + ' MB';
    }
    if (bytes >= 1024) {
      var kb = Math.round(bytes / 1024);
      if (kb < 1) kb = 1;
      return String(kb) + ' KB';
    }
    return String(Math.round(bytes)) + ' bytes';
  }
  function mediaBaseName(name) {
    var base = String(name || 'photo');
    base = base.split('\\').join('/').split('/').pop() || 'photo';
    var dot = base.lastIndexOf('.');
    var stem = dot > 0 ? base.slice(0, dot) : base;
    if (!stem) stem = 'photo';
    return stem;
  }
  function mediaAllowedType(type, name) {
    var t = String(type || '').toLowerCase();
    if (t === 'image/jpeg' || t === 'image/png' || t === 'image/webp') return true;
    if (!t) {
      var n = String(name || '').toLowerCase();
      if (/\.jpe?g$/.test(n) || /\.png$/.test(n) || /\.webp$/.test(n)) return true;
    }
    return false;
  }
  function mediaToast(message) {
    var toast = $('kci-toast');
    if (!toast) return;
    toast.textContent = message;
    toast.removeAttribute('hidden');
    window.clearTimeout(toast._kciTimer || 0);
    toast._kciTimer = window.setTimeout(function () { toast.setAttribute('hidden', ''); }, 2200);
  }
  function mediaHasTransparency(bitmap, w, h) {
    try {
      var cw = Math.min(64, w);
      var ch = Math.min(64, h);
      if (cw < 1 || ch < 1) return false;
      var probe = document.createElement('canvas');
      probe.width = cw;
      probe.height = ch;
      var ctx = probe.getContext('2d');
      if (!ctx) return false;
      ctx.drawImage(bitmap, 0, 0, cw, ch);
      var px = null;
      try { px = ctx.getImageData(0, 0, cw, ch); } catch (ignored) { return false; }
      if (!px || !px.data) return false;
      var data = px.data;
      for (var i = 3; i < data.length; i += 16) {
        if (data[i] < 254) return true;
      }
      return false;
    } catch (ignored2) { return false; }
  }
  function mediaNewFile(blob, baseStem, ext, type) {
    try {
      return new File([blob], baseStem + ext, { type: type });
    } catch (ignored) {
      return null;
    }
  }
  function mediaShrinkOne(file) {
    if (!file || !mediaAllowedType(file.type, file.name)) {
      return Promise.resolve({ file: file, note: '' });
    }
    var needBySize = (file.size || 0) > MEDIA_MAX_BYTES;
    var decode = null;
    try {
      if (typeof createImageBitmap !== 'function') {
        return Promise.resolve({ file: file, note: '' });
      }
      try {
        decode = createImageBitmap(file, { imageOrientation: 'from-image' });
      } catch (oldSig) {
        decode = createImageBitmap(file);
      }
    } catch (ignored) { return Promise.resolve({ file: file, note: '' }); }
    return Promise.resolve(decode).then(function (bitmap) {
      var closeBm = function () { try { if (bitmap && bitmap.close) bitmap.close(); } catch (ignoredC) {} };
      if (!bitmap) return { file: file, note: '' };
      var w = bitmap.width || 0;
      var h = bitmap.height || 0;
      if (!w || !h) { closeBm(); return { file: file, note: '' }; }
      var needs = needBySize || w > MEDIA_MAX_SIDE || h > MEDIA_MAX_SIDE;
      if (!needs) { closeBm(); return { file: file, note: '' }; }
      var isPng = String(file.type || '').toLowerCase() === 'image/png';
      var transparent = isPng ? mediaHasTransparency(bitmap, w, h) : false;
      var longest = Math.max(w, h);
      var scale = longest > MEDIA_EXPORT_SIDE ? (MEDIA_EXPORT_SIDE / longest) : 1;
      if (scale > 1) scale = 1;
      var outW = Math.max(1, Math.round(w * scale));
      var outH = Math.max(1, Math.round(h * scale));
      var canvas = document.createElement('canvas');
      canvas.width = outW;
      canvas.height = outH;
      var ctx = canvas.getContext('2d');
      if (!ctx) { closeBm(); return { file: file, note: '' }; }
      var useWebp = transparent;
      var outType = useWebp ? 'image/webp' : 'image/jpeg';
      var outExt = useWebp ? '.webp' : '.jpg';
      if (!useWebp) {
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, outW, outH);
      }
      try { ctx.drawImage(bitmap, 0, 0, outW, outH); } catch (drawErr) {
        closeBm();
        return { file: file, note: '' };
      }
      closeBm();
      if (typeof canvas.toBlob !== 'function') return { file: file, note: '' };
      return new Promise(function (resolve) {
        var settled = false;
        var finish = function (blob) {
          if (settled) return;
          settled = true;
          if (!blob) { resolve({ file: file, note: '' }); return; }
          var made = mediaNewFile(blob, mediaBaseName(file.name), outExt, outType);
          if (!made) { resolve({ file: file, note: '' }); return; }
          var note = 'Shrunk from ' + mediaFormatSize(file.size) + ' to ' + mediaFormatSize(made.size);
          resolve({ file: made, note: note });
        };
        try {
          canvas.toBlob(finish, outType, MEDIA_EXPORT_QUALITY);
        } catch (ignoredT) { finish(null); }
        window.setTimeout(function () { finish(null); }, 15000);
      });
    }, function () {
      return { file: file, note: '' };
    });
  }
  function mediaRenderPreview(files) {
    if (!mediaPreview || !mediaInput) return;
    while (mediaPreview.firstChild) mediaPreview.removeChild(mediaPreview.firstChild);
    mediaRejected.forEach(function (msg) {
      var p = document.createElement('p');
      p.className = 'kci-media__note kci-media__note--error';
      p.setAttribute('role', 'alert');
      p.textContent = msg;
      mediaPreview.appendChild(p);
    });
    var list = files ? Array.prototype.slice.call(files, 0, 10) : [];
    list.forEach(function (file) {
      if (!file) return;
      var isImg = file.type && file.type.indexOf('image/') === 0;
      if (!isImg) {
        var nm = String(file.name || '').toLowerCase();
        if (!(/\.jpe?g$/.test(nm) || /\.png$/.test(nm) || /\.webp$/.test(nm))) return;
      }
      var card = document.createElement('div');
      card.className = 'kci-media__pick';
      var img = document.createElement('img');
      img.alt = file.name || 'Selected picture preview';
      try {
        var url = URL.createObjectURL(file);
        img.src = url;
        img.onload = function () { try { URL.revokeObjectURL(url); } catch (ignored) {} };
      } catch (ignored) {
        var reader = new FileReader();
        reader.onload = function () { img.src = reader.result; };
        try { reader.readAsDataURL(file); } catch (ignored2) { return; }
      }
      card.appendChild(img);
      var key = mediaKey(file);
      if (mediaNotes[key]) {
        var note = document.createElement('small');
        note.className = 'kci-media__note';
        note.textContent = mediaNotes[key];
        card.appendChild(note);
      }
      mediaPreview.appendChild(card);
    });
  }
  function mediaProcessSelection(rawList, done) {
    var files = rawList ? Array.prototype.slice.call(rawList, 0, 10) : [];
    mediaRejected = [];
    var kept = [];
    files.forEach(function (f) {
      if (!f) return;
      if (!mediaAllowedType(f.type, f.name)) {
        mediaRejected.push((f.name || 'That file') + ': ' + MEDIA_UNSUPPORTED_MSG);
        return;
      }
      kept.push(f);
    });
    var finish = function (finalFiles) {
      try {
        if (typeof DataTransfer === 'function' && mediaInput) {
          var dt = new DataTransfer();
          finalFiles.forEach(function (f) { try { dt.items.add(f); } catch (ignored) {} });
          mediaInput.files = dt.files;
        }
      } catch (ignored2) { /* keep original selection */ }
      mediaRenderPreview(mediaInput && mediaInput.files && mediaInput.files.length ? mediaInput.files : finalFiles);
      mediaBusy = false;
      if (typeof done === 'function') done();
    };
    if (!kept.length) {
      try {
        if (typeof DataTransfer === 'function' && mediaInput) {
          mediaInput.files = new DataTransfer().files;
        } else if (mediaInput) { mediaInput.value = ''; }
      } catch (ignored3) { if (mediaInput) { try { mediaInput.value = ''; } catch (ignored4) {} } }
      mediaBusy = false;
      mediaRenderPreview([]);
      if (typeof done === 'function') done();
      return;
    }
    var canShrink = (typeof createImageBitmap === 'function');
    if (!canShrink) {
      finish(kept);
      return;
    }
    mediaBusy = true;
    mediaRenderPreview(kept);
    var out = new Array(kept.length);
    var pending = kept.length;
    kept.forEach(function (f, idx) {
      mediaShrinkOne(f).then(function (r) {
        var nf = (r && r.file) ? r.file : f;
        out[idx] = nf;
        if (r && r.note) mediaNotes[mediaKey(nf)] = r.note;
        pending -= 1;
        if (pending <= 0) finish(out);
      }, function () {
        out[idx] = f;
        pending -= 1;
        if (pending <= 0) finish(out);
      });
    });
  }
  if (mediaDrop && mediaInput) {
    mediaInput.addEventListener('change', function () { mediaProcessSelection(mediaInput.files); });
    ['dragenter', 'dragover'].forEach(function (name) {
      mediaDrop.addEventListener(name, function (ev) {
        ev.preventDefault();
        mediaDrop.classList.add('is-dragover');
        if (ev.dataTransfer) ev.dataTransfer.dropEffect = 'copy';
      });
    });
    ['dragleave', 'drop'].forEach(function (name) {
      mediaDrop.addEventListener(name, function (ev) {
        if (name === 'drop') return;
        ev.preventDefault();
        mediaDrop.classList.remove('is-dragover');
      });
    });
    mediaDrop.addEventListener('drop', function (ev) {
      ev.preventDefault();
      mediaDrop.classList.remove('is-dragover');
      var dt = ev.dataTransfer;
      if (dt && dt.files && dt.files.length) {
        mediaProcessSelection(dt.files);
      }
    });
    mediaDrop.addEventListener('click', function (ev) {
      if (ev.target === mediaInput) return;
      mediaInput.click();
    });
    mediaDrop.addEventListener('keydown', function (ev) {
      if (ev.key === 'Enter' || ev.key === ' ') { ev.preventDefault(); mediaInput.click(); }
    });
    if (mediaForm) {
      mediaForm.addEventListener('submit', function (ev) {
        if (mediaBusy) {
          ev.preventDefault();
          mediaToast('Please wait while pictures are prepared.');
          return;
        }
        if (mediaInput && mediaInput.files && mediaInput.files.length) {
          var bad = [];
          var okAny = false;
          Array.prototype.forEach.call(mediaInput.files, function (f) {
            if (!f) return;
            if (mediaAllowedType(f.type, f.name)) { okAny = true; }
            else { bad.push(f.name || 'That file'); }
          });
          if (!okAny && bad.length) {
            ev.preventDefault();
            mediaProcessSelection(mediaInput.files);
          }
        }
      });
    }
  }

  // Media library: copy-link buttons with clipboard fallback + toast.
  document.addEventListener('click', function (ev) {
    var btn = ev.target && ev.target.closest ? ev.target.closest('[data-copy-link]') : null;
    if (!btn) return;
    var link = btn.getAttribute('data-copy-link') || '';
    var done = function () { mediaToast('Link copied to clipboard.'); };
    var fallback = function () {
      try {
        var ta = document.createElement('textarea');
        ta.value = link;
        ta.setAttribute('readonly', '');
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        var ok = document.execCommand('copy');
        document.body.removeChild(ta);
        if (ok) { done(); return; }
      } catch (ignored) { /* fall through */ }
      window.prompt('Copy this link:', link);
    };
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(link).then(done, fallback);
    } else {
      fallback();
    }
  });

  /* Shared picture inputs (admin/includes/image_picker.php): shrink the
     chosen file in the browser when supported (data-kci-shrink) and show it
     in the field's preview img (data-kci-preview) right away. */
  (function () {
    'use strict';
    var pickers = document.querySelectorAll('input[type="file"][data-kci-shrink]');
    if (!pickers.length) return;
    var DEFAULT_IMG = '../kci_image/im2.webp';
    function shrinkBlob(file, maxSide) {
      if (typeof createImageBitmap !== 'function' || typeof OffscreenCanvas !== 'function') return Promise.resolve(null);
      return createImageBitmap(file).then(function (bmp) {
        var scale = Math.min(1, maxSide / Math.max(bmp.width, bmp.height));
        var w = Math.max(1, Math.round(bmp.width * scale));
        var h = Math.max(1, Math.round(bmp.height * scale));
        var canvas = new OffscreenCanvas(w, h);
        var ctx = canvas.getContext('2d');
        if (!ctx) return null;
        ctx.drawImage(bmp, 0, 0, w, h);
        if (bmp.close) bmp.close();
        if (!canvas.convertToBlob) return null;
        var type = (file.type === 'image/png') ? 'image/png' : 'image/webp';
        return canvas.convertToBlob({ type: type, quality: 0.85 });
      }, function () { return null; });
    }
    Array.prototype.forEach.call(pickers, function (pickInput) {
      pickInput.addEventListener('change', function () {
        var pickFile = pickInput.files && pickInput.files[0];
        var pickId = pickInput.getAttribute('data-kci-preview') || '';
        var pickImg = pickId ? document.getElementById(pickId) : null;
        if (!pickFile) { if (pickImg) pickImg.src = DEFAULT_IMG; return; }
        var pickUrl = null;
        try { pickUrl = URL.createObjectURL(pickFile); } catch (ignoredPick) { pickUrl = null; }
        if (pickImg && pickUrl) pickImg.src = pickUrl;
        if (pickFile.size <= 400000) return;
        shrinkBlob(pickFile, 1600).then(function (blob) {
          if (!blob) return;
          try {
            var dt = new DataTransfer();
            dt.items.add(new File([blob], pickFile.name || 'photo.webp', { type: blob.type }));
            pickInput.files = dt.files;
          } catch (ignoredShrink) { /* keep the original file */ }
        });
      });
    });
  })();
})();

/* Events module (event-edit.php): live preview card, character counters,
   blob-URL picture preview, slug preview on the add form and the slug
   "Change link" toggle on the edit form. Runs only on pages with
   #eventPreview, so every other admin page is untouched. */
(function () {
  'use strict';
  function ev$(id) { return document.getElementById(id); }
  var eventPreview = ev$('eventPreview');
  if (!eventPreview) return;

  var evTitle = ev$('event-title');
  var evDate = ev$('event-date');
  var evLoc = ev$('event-location');
  var evDesc = ev$('event-description');
  var evBody = ev$('event-content');
  var evOutTitle = ev$('eventPreviewTitle');
  var evOutDate = ev$('eventPreviewDate');
  var evOutLoc = ev$('eventPreviewLoc');
  var evOutDesc = ev$('eventPreviewDesc');
  var evOutImg = ev$('eventPreviewImg');
  var evDescCount = ev$('event-desc-count');
  var evBodyCount = ev$('event-content-count');
  var evMonths = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

  function evFormatDate(raw) {
    var value = String(raw || '');
    if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) return '';
    var parts = value.split('-');
    var y = parseInt(parts[0], 10);
    var m = parseInt(parts[1], 10);
    var d = parseInt(parts[2], 10);
    if (!y || !m || !d || m < 1 || m > 12) return '';
    return evMonths[m - 1] + ' ' + d + ', ' + y;
  }

  function evRender() {
    if (evOutTitle) evOutTitle.textContent = (evTitle && evTitle.value.trim()) ? evTitle.value.trim() : 'Your event title';
    if (evOutDate) evOutDate.textContent = (evDate && evDate.value.trim()) ? evFormatDate(evDate.value.trim()) : 'Pick a date';
    if (evOutLoc) evOutLoc.textContent = (evLoc && evLoc.value.trim()) ? evLoc.value.trim() : 'Kingdomite Church International';
    if (evOutDesc) evOutDesc.textContent = (evDesc && evDesc.value.trim()) ? evDesc.value.trim() : 'Your short description appears here.';
    if (evDescCount) evDescCount.textContent = String(evDesc ? evDesc.value.length : 0);
    if (evBodyCount) evBodyCount.textContent = String(evBody ? evBody.value.length : 0);
  }

  [evTitle, evLoc, evDesc, evBody].forEach(function (input) {
    if (input) input.addEventListener('input', evRender);
  });
  if (evDate) evDate.addEventListener('change', evRender);

  var evFile = ev$('image-file');
  var evRemove = document.querySelector('input[name="image_remove"]');
  var evBlobUrl = null;
  if (evFile) {
    evFile.addEventListener('change', function () {
      var file = evFile.files && evFile.files[0];
      if (!file || !evOutImg) return;
      if (evBlobUrl) { URL.revokeObjectURL(evBlobUrl); evBlobUrl = null; }
      try { evBlobUrl = URL.createObjectURL(file); } catch (ignoredBlob) { evBlobUrl = null; }
      if (evBlobUrl) evOutImg.src = evBlobUrl;
    });
  }
  if (evRemove) {
    evRemove.addEventListener('change', function () {
      if (evRemove.checked && evOutImg) evOutImg.src = '../kci_image/im2.webp';
    });
  }

  var evSlugOut = ev$('event-slug-preview-value');
  if (evSlugOut && evTitle) {
    var evSyncSlug = function () {
      var value = evTitle.value;
      try { value = value.normalize ? value.normalize('NFD').replace(/[\u0300-\u036f]/g, '') : value; } catch (ignoredNorm) { /* keep raw */ }
      value = value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+/, '').slice(0, 60).replace(/-+$/, '');
      evSlugOut.textContent = value || 'event';
    };
    evTitle.addEventListener('input', evSyncSlug);
    evSyncSlug();
  }

  var evSlugToggle = ev$('event-slug-toggle');
  if (evSlugToggle) {
    evSlugToggle.addEventListener('click', function () {
      var editBox = ev$('event-slug-edit');
      var locked = ev$('event-slug-locked');
      var flag = ev$('event-change-slug');
      var slugInput = ev$('event-slug');
      if (editBox) editBox.removeAttribute('hidden');
      if (locked) locked.setAttribute('hidden', '');
      if (flag) flag.value = '1';
      evSlugToggle.setAttribute('hidden', '');
      if (slugInput) slugInput.focus();
    });
  }

  evRender();
})();
