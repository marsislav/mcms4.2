/* mCMS admin — vanilla JS, no inline code (CSP friendly) */
(function () {
  'use strict';

  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };
  var adminBase = (document.querySelector('meta[name="mcms-admin"]') || {}).content || '';

  /* ---- confirm dialogs on forms ---- */
  document.addEventListener('submit', function (e) {
    var msg = e.target && e.target.getAttribute && e.target.getAttribute('data-confirm');
    if (msg && !window.confirm(msg)) { e.preventDefault(); }
  });

  /* ---- language tabs ---- */
  $$('[data-tabs]').forEach(function (bar) {
    var panels = $$('[data-panel]');
    bar.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-tab]');
      if (!btn) { return; }
      $$('[data-tab]', bar).forEach(function (b) { b.classList.toggle('active', b === btn); });
      panels.forEach(function (p) { p.hidden = p.getAttribute('data-panel') !== btn.getAttribute('data-tab'); });
    });
  });


  /* ---- settings page tabs (Общи / Дизайн); #design in the URL opens the design tab ---- */
  $$('[data-stabs]').forEach(function (bar) {
    var panels = $$('[data-spanel]');
    var show = function (name) {
      $$('[data-stab]', bar).forEach(function (b) { b.classList.toggle('active', b.getAttribute('data-stab') === name); });
      panels.forEach(function (p) { p.hidden = p.getAttribute('data-spanel') !== name; });
    };
    bar.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-stab]');
      if (!btn) { return; }
      show(btn.getAttribute('data-stab'));
      try { history.replaceState(null, '', btn.getAttribute('data-stab') === 'design' ? '#design' : location.pathname + location.search); } catch (x) { /* ignore */ }
    });
    show(location.hash === '#design' ? 'design' : 'general');
  });

  /* ---- colour fields: picker <-> hex text, empty = theme default ---- */
  $$('.colorfield').forEach(function (box) {
    var pick = $('[data-color-pick]', box), text = $('[data-color-text]', box), clear = $('[data-color-clear]', box);
    var hex = function (v) { return /^#?([0-9a-f]{3}|[0-9a-f]{6})$/i.test(v) ? (v.charAt(0) === '#' ? v : '#' + v) : null; };
    pick.addEventListener('input', function () { text.value = pick.value; });
    text.addEventListener('input', function () {
      var h = hex(text.value.trim());
      if (h && h.length === 7) { pick.value = h.toLowerCase(); }
    });
    text.addEventListener('blur', function () {
      var h = hex(text.value.trim());
      text.value = h || (text.value.trim() === '' ? '' : text.value);
    });
    clear.addEventListener('click', function () { text.value = ''; pick.value = '#888888'; });
  });

  /* ---- top-bar: show only as many column editors as selected, hide all when off ---- */
  (function () {
    var on = $('[data-topbar-on]'), box = $('[data-topbar-box]'), cols = $('[data-topbar-cols]');
    if (!on || !box || !cols) { return; }
    var apply = function () {
      box.hidden = !on.checked;
      $$('[data-topbar-col]', box).forEach(function (f) {
        f.hidden = parseInt(f.getAttribute('data-topbar-col'), 10) > parseInt(cols.value, 10);
      });
    };
    on.addEventListener('change', apply); cols.addEventListener('change', apply); apply();
  })();

  /* ---- menu row: show only the relevant target field ---- */
  $$('[data-menu-type]').forEach(function (sel) {
    var row = sel.closest('tr');
    var apply = function () {
      $$('[data-for]', row).forEach(function (el) { el.hidden = el.getAttribute('data-for') !== sel.value; });
    };
    sel.addEventListener('change', apply);
    apply();
  });

  /* ---- click-to-select URL fields ---- */
  $$('.copy-url').forEach(function (i) { i.addEventListener('focus', function () { i.select(); }); });

  /* ---- auto-growing textareas ---- */
  $$('textarea.autogrow').forEach(function (t) {
    var fit = function () { t.style.height = 'auto'; t.style.height = Math.min(t.scrollHeight, 220) + 'px'; };
    t.addEventListener('input', fit); fit();
  });

  /* ---- warn about unsaved changes ---- */
  var editorForm = $('.editor-form');
  if (editorForm) {
    var dirty = false;
    editorForm.addEventListener('input', function () { dirty = true; });
    editorForm.addEventListener('submit', function () { dirty = false; });
    window.addEventListener('beforeunload', function (e) { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
  }

  /* ---- media picker ---- */
  var picker = null;

  function buildPicker() {
    var wrap = document.createElement('div');
    wrap.className = 'modal'; wrap.hidden = true;
    wrap.innerHTML =
      '<div class="modal-box" role="dialog" aria-modal="true" aria-label="Медия">' +
      '<div class="modal-head"><strong>Избор на файл</strong>' +
      '<input type="search" placeholder="Търсене…" class="picker-q"><button type="button" class="btn sm picker-close">✕</button></div>' +
      '<div class="picker-grid"></div>' +
      '<div class="modal-foot"><button type="button" class="btn sm picker-more" hidden>Още…</button></div></div>';
    document.body.appendChild(wrap);

    var state = { next: null, onPick: null, imagesOnly: false, timer: null };
    var grid = $('.picker-grid', wrap), more = $('.picker-more', wrap), q = $('.picker-q', wrap);

    function load(url, replace) {
      fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { if (!r.ok) { throw new Error('http ' + r.status); } return r.json(); })
        .then(function (res) {
          if (replace) { grid.textContent = ''; }
          res.data.forEach(function (m) {
            var b = document.createElement('button');
            b.type = 'button'; b.className = 'picker-item'; b.title = m.name;
            if (m.image) { var im = document.createElement('img'); im.src = m.thumb; im.alt = ''; im.loading = 'lazy'; b.appendChild(im); }
            else { b.textContent = 'PDF'; }
            b.addEventListener('click', function () { var cb = state.onPick; close(); if (cb) { cb(m); } });
            grid.appendChild(b);
          });
          if (!res.data.length && replace) { grid.textContent = 'Няма файлове.'; }
          state.next = res.next; more.hidden = !res.next;
        })
        .catch(function () { grid.textContent = 'Грешка при зареждане.'; });
    }
    function query() {
      var u = adminBase + '/media/picker?q=' + encodeURIComponent(q.value) + (state.imagesOnly ? '&images=1' : '');
      load(u, true);
    }
    function close() { wrap.hidden = true; document.body.classList.remove('modal-open'); }

    q.addEventListener('input', function () { clearTimeout(state.timer); state.timer = setTimeout(query, 250); });
    more.addEventListener('click', function () { if (state.next) { load(state.next, false); } });
    $('.picker-close', wrap).addEventListener('click', close);
    wrap.addEventListener('click', function (e) { if (e.target === wrap) { close(); } });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !wrap.hidden) { close(); } });

    return {
      open: function (onPick, imagesOnly) {
        state.onPick = onPick; state.imagesOnly = !!imagesOnly; q.value = '';
        wrap.hidden = false; document.body.classList.add('modal-open'); query(); q.focus();
      }
    };
  }
  function openPicker(cb, imagesOnly) { picker = picker || buildPicker(); picker.open(cb, imagesOnly); }

  $$('[data-media-field]').forEach(function (f) {
    var input = $('input[type=hidden]', f), img = $('.media-preview', f);
    $('[data-media-choose]', f).addEventListener('click', function () {
      openPicker(function (m) { input.value = m.id; img.src = m.thumb; img.hidden = false; input.dispatchEvent(new Event('input', { bubbles: true })); }, true);
    });
    $('[data-media-clear]', f).addEventListener('click', function () {
      input.value = ''; img.hidden = true; img.removeAttribute('src');
    });
  });


  /* ---- tags: click a known tag to add it to the field ---- */
  $$('[data-tag-add]').forEach(function (b) {
    b.addEventListener('click', function () {
      var input = $('[data-tags-input]');
      if (!input) { return; }
      var name = b.getAttribute('data-tag-add');
      var list = input.value.split(',').map(function (x) { return x.trim(); }).filter(Boolean);
      if (!list.some(function (x) { return x.toLowerCase() === name.toLowerCase(); })) { list.push(name); }
      input.value = list.join(', ');
      input.dispatchEvent(new Event('input', { bubbles: true }));
    });
  });

  /* ---- related posts picker (datalist -> chips with hidden inputs) ---- */
  $$('[data-related]').forEach(function (box) {
    var chips = $('[data-chips]', box), input = $('[data-related-input]', box);
    var MAX = 12;
    box.addEventListener('click', function (e) {
      var rm = e.target.closest('[data-chip-remove]');
      if (rm) { rm.parentNode.remove(); input.dispatchEvent(new Event('input', { bubbles: true })); }
    });
    input.addEventListener('input', function () {
      var m = /^(.*)\s\(#(\d+)\)$/.exec(input.value);
      if (!m) { return; }
      var id = m[2];
      var exists = $$('input[name="related[]"]', chips).some(function (i) { return i.value === id; });
      if (!exists && $$('.chip', chips).length < MAX) {
        var chip = document.createElement('span'); chip.className = 'chip sel';
        chip.appendChild(document.createTextNode(m[1]));
        var h = document.createElement('input'); h.type = 'hidden'; h.name = 'related[]'; h.value = id; chip.appendChild(h);
        var x = document.createElement('button'); x.type = 'button'; x.setAttribute('data-chip-remove', ''); x.setAttribute('aria-label', 'Махни'); x.textContent = '×'; chip.appendChild(x);
        chips.appendChild(chip);
      }
      input.value = '';
    });
  });

  /* rich-text editor (CKEditor 5, assets/js/editor.js) uses the same media picker */
  window.mcmsOpenPicker = openPicker;

  /* visible warning if the rich-text editor did not start (missing files, blocked script…) */
  window.addEventListener('load', function () {
    setTimeout(function () {
      $$('textarea[data-editor]').forEach(function (ta) {
        if (ta.getAttribute('data-editor-ready')) { return; }
        var n = document.createElement('div');
        n.className = 'flash err';
        n.textContent = 'Редакторът CKEditor не е зареден (полето работи като обикновен HTML). Проверете дали public/assets/vendor/ckeditor5/ckeditor5.umd.js е качен (≈1.9 MB), опреснете с Ctrl+F5 и вижте конзолата (F12).';
        ta.parentNode.insertBefore(n, ta);
      });
    }, 4000);
  });
})();
