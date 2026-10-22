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

  /* ---- confirm on a single button (data-confirm-btn) and "select all" checkboxes (data-check-all) ---- */
  document.addEventListener('click', function (e) {
    var b = e.target.closest && e.target.closest('[data-confirm-btn]');
    if (b && !window.confirm(b.getAttribute('data-confirm-btn'))) { e.preventDefault(); }
  });
  document.addEventListener('change', function (e) {
    if (!e.target.matches || !e.target.matches('[data-check-all]')) { return; }
    var table = e.target.closest('table');
    $$('tbody input[type=checkbox]', table).forEach(function (c) { c.checked = e.target.checked; });
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


  /* ---- settings page tabs (General / Design); #design in the URL opens the design tab ---- */
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
      try { var tabName = btn.getAttribute('data-stab'); history.replaceState(null, '', tabName === 'general' ? location.pathname + location.search : '#' + tabName); } catch (x) { /* ignore */ }
    });
    var names = $$('[data-stab]', bar).map(function (b) { return b.getAttribute('data-stab'); });
    var fromHash = location.hash.slice(1);
    show(names.indexOf(fromHash) !== -1 ? fromHash : 'general');
  });

  /* ---- colour fields: picker <-> hex text, empty = theme default ---- */
  $$('.colorfield').forEach(function (box) {
    var pick = $('[data-color-pick]', box), text = $('[data-color-text]', box), clear = $('[data-color-clear]', box);
    if (!pick || !text || !clear) { return; }
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

  /* ---- media upload (AJAX) ---- */
  var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
  /** Upload File objects to the media library; resolves {data:[media…], errors:[…]}. */
  function uploadFiles(files) {
    var fd = new FormData();
    Array.prototype.forEach.call(files, function (f) { fd.append('files[]', f); });
    return fetch(adminBase + '/media/upload', {
      method: 'POST', body: fd, credentials: 'same-origin',
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf }
    }).then(function (r) {
      return r.json().catch(function () { return {}; }).then(function (j) {
        if (r.status === 419) { throw new Error(T('Сесията е изтекла — презаредете страницата.')); }
        if (r.status === 413) { throw new Error(T('Файлът е твърде голям за сървъра.')); }
        if (!r.ok && !(j.errors && j.errors.length)) { throw new Error((j.message) || (T('Грешка ') + r.status)); }
        return { data: j.data || [], errors: j.errors || [] };
      });
    });
  }
  window.mcmsUploadFiles = uploadFiles;

  /* ---- media picker ---- */
  var picker = null;

  function buildPicker() {
    var wrap = document.createElement('div');
    wrap.className = 'modal'; wrap.hidden = true;
    wrap.innerHTML =
      T('<div class="modal-box" role="dialog" aria-modal="true" aria-label="Медия">') +
      T('<div class="modal-head"><strong>Избор на файл</strong>') +
      T('<input type="search" placeholder="Търсене…" class="picker-q">') +
      T('<button type="button" class="btn sm primary picker-upload">⬆ Качи</button><input type="file" class="picker-file" multiple hidden>') +
      '<button type="button" class="btn sm picker-close">✕</button></div>' +
      '<div class="picker-msg" role="status" hidden></div>' +
      '<div class="picker-grid"></div>' +
      T('<div class="modal-foot"><button type="button" class="btn sm picker-more" hidden>Още…</button></div></div>');
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
          if (!res.data.length && replace) { grid.textContent = T('Няма файлове.'); }
          state.next = res.next; more.hidden = !res.next;
        })
        .catch(function () { grid.textContent = T('Грешка при зареждане.'); });
    }
    function query() {
      var u = adminBase + '/media/picker?q=' + encodeURIComponent(q.value) + (state.imagesOnly ? '&images=1' : '');
      load(u, true);
    }
    function close() { wrap.hidden = true; document.body.classList.remove('modal-open'); }

    /* upload from the picker: one file chosen -> use it at once; several -> refresh the grid */
    var msg = $('.picker-msg', wrap), fileIn = $('.picker-file', wrap), upBtn = $('.picker-upload', wrap), box = $('.modal-box', wrap);
    function say(text, bad) { msg.hidden = !text; msg.textContent = text || ''; msg.className = 'picker-msg' + (bad ? ' bad' : ''); }
    function doUpload(files) {
      files = Array.prototype.filter.call(files, function (f) { return !state.imagesOnly || /^image\//.test(f.type); });
      if (!files.length) { say(state.imagesOnly ? T('Тук се качват само изображения.') : '', true); return; }
      say(T('Качване…')); upBtn.disabled = true;
      uploadFiles(files).then(function (res) {
        upBtn.disabled = false;
        if (res.errors.length) { say(res.errors.join(' | '), true); } else { say(''); }
        if (res.data.length === 1 && !res.errors.length) { var cb = state.onPick; close(); if (cb) { cb(res.data[0]); } return; }
        if (res.data.length) { query(); }
      }).catch(function (e) { upBtn.disabled = false; say(e.message || T('Качването е неуспешно.'), true); });
    }
    upBtn.addEventListener('click', function () { fileIn.click(); });
    fileIn.addEventListener('change', function () { if (fileIn.files.length) { doUpload(fileIn.files); } fileIn.value = ''; });
    ['dragenter', 'dragover'].forEach(function (ev) { box.addEventListener(ev, function (e) { e.preventDefault(); box.classList.add('drop'); }); });
    ['dragleave', 'drop'].forEach(function (ev) { box.addEventListener(ev, function (e) { e.preventDefault(); box.classList.remove('drop'); }); });
    box.addEventListener('drop', function (e) { if (e.dataTransfer && e.dataTransfer.files.length) { doUpload(e.dataTransfer.files); } });

    q.addEventListener('input', function () { clearTimeout(state.timer); state.timer = setTimeout(query, 250); });
    more.addEventListener('click', function () { if (state.next) { load(state.next, false); } });
    $('.picker-close', wrap).addEventListener('click', close);
    wrap.addEventListener('click', function (e) { if (e.target === wrap) { close(); } });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !wrap.hidden) { close(); } });

    return {
      open: function (onPick, imagesOnly) {
        state.onPick = onPick; state.imagesOnly = !!imagesOnly; q.value = ''; say('');
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
    var up = $('[data-media-upload]', f);
    if (up) {
      var fi = document.createElement('input'); fi.type = 'file'; fi.accept = 'image/jpeg,image/png,image/webp,image/gif'; fi.hidden = true; f.appendChild(fi);
      var note = document.createElement('small'); note.className = 'muted'; note.hidden = true; f.appendChild(note);
      var set = function (m) { input.value = m.id; img.src = m.thumb; img.hidden = false; input.dispatchEvent(new Event('input', { bubbles: true })); };
      var go = function (file) {
        if (!file) { return; }
        note.hidden = false; note.textContent = T('Качване…'); up.disabled = true;
        uploadFiles([file]).then(function (res) {
          up.disabled = false;
          if (res.data.length) { set(res.data[0]); note.hidden = true; } else { note.textContent = res.errors.join(' | ') || T('Качването е неуспешно.'); }
        }).catch(function (e) { up.disabled = false; note.textContent = e.message || T('Качването е неуспешно.'); });
      };
      up.addEventListener('click', function () { fi.click(); });
      fi.addEventListener('change', function () { go(fi.files[0]); fi.value = ''; });
      f.addEventListener('dragover', function (e) { e.preventDefault(); f.classList.add('drop'); });
      f.addEventListener('dragleave', function () { f.classList.remove('drop'); });
      f.addEventListener('drop', function (e) {
        e.preventDefault(); f.classList.remove('drop');
        var file = e.dataTransfer && e.dataTransfer.files[0];
        if (file && /^image\//.test(file.type)) { go(file); }
      });
    }
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
        var x = document.createElement('button'); x.type = 'button'; x.setAttribute('data-chip-remove', ''); x.setAttribute('aria-label', T('Махни')); x.textContent = '×'; chip.appendChild(x);
        chips.appendChild(chip);
      }
      input.value = '';
    });
  });


  /* widgets repeater (Settings → Footer / Sidebar) */
  (function () {
    var lists = $$('[data-widget-list]');
    if (!lists.length) { return; }
    var counter = 0;

    function tpl(type) {
      var t = document.querySelector('template[data-widget-tpl="' + type + '"]');
      return t ? t.innerHTML : '';
    }
    function addTo(list, type) {
      var html = tpl(type);
      if (!html) { return; }
      var col = list.getAttribute('data-col');
      html = html.replace(/__C__/g, col).replace(/__I__/g, 'n' + Date.now().toString(36) + (counter++));
      var tmp = document.createElement('div'); tmp.innerHTML = html.trim();
      var card = tmp.firstElementChild;
      if (card) { list.appendChild(card); card.scrollIntoView({ block: 'nearest' }); }
    }
    function syncTitle(card) {
      var inp = card.querySelector('input[name*="[title]"]');
      var span = card.querySelector('.wtitle');
      if (inp && span) { span.textContent = inp.value; }
    }

    document.addEventListener('click', function (e) {
      var t = e.target;
      if (!t.closest) { return; }
      var add = t.closest('[data-widget-add]');
      if (add) {
        var area = add.closest('[data-widget-area]');
        addTo(area.querySelector('[data-widget-list]'), area.querySelector('[data-widget-type]').value);
        return;
      }
      var card = t.closest('[data-widget]');
      if (!card) { return; }
      if (t.closest('[data-w-up]')) {
        e.preventDefault(); var p = card.previousElementSibling; if (p) { card.parentNode.insertBefore(card, p); }
      } else if (t.closest('[data-w-down]')) {
        e.preventDefault(); var n = card.nextElementSibling; if (n) { card.parentNode.insertBefore(n, card); }
      } else if (t.closest('[data-w-del]')) {
        e.preventDefault();
        if (window.confirm(T('Да се премахне ли този уиджет?'))) { card.parentNode.removeChild(card); }
      } else if (t.closest('[data-fa-pick]')) {
        var mode = t.closest('[data-fa-pick]').getAttribute('data-fa-pick');
        if (!window.mcmsFaPicker) { window.alert(T('Изборът на икони не е зареден (assets/js/editor-fa.js).')); return; }
        window.mcmsFaPicker(function (cls) {
          if (mode === 'icon') {
            var inp = card.querySelector('[data-fa-input]'); if (inp) { inp.value = cls; }
          } else {
            var ta = card._lastBody || card.querySelector('[data-w-body]');
            if (!ta) { return; }
            var ins = '<span class="' + cls + '" aria-hidden="true"></span> ';
            var s = ta.selectionStart == null ? ta.value.length : ta.selectionStart;
            var en = ta.selectionEnd == null ? s : ta.selectionEnd;
            ta.value = ta.value.slice(0, s) + ins + ta.value.slice(en);
            ta.focus(); ta.selectionStart = ta.selectionEnd = s + ins.length;
          }
        });
      }
    });
    document.addEventListener('focusin', function (e) {
      var ta = e.target;
      if (ta && ta.matches && ta.matches('[data-w-body]')) { var c = ta.closest('[data-widget]'); if (c) { c._lastBody = ta; } }
    });
    document.addEventListener('input', function (e) {
      var c = e.target.closest && e.target.closest('[data-widget]');
      if (c && /\[title\]/.test(e.target.name || '')) { syncTitle(c); }
    });

    /* footer: show only the chosen number of columns */
    var sel = document.querySelector('[data-fcols]');
    function cols() {
      var n = parseInt(sel.value, 10) || 0;
      $$('[data-fcol]').forEach(function (s) { s.hidden = parseInt(s.getAttribute('data-fcol'), 10) > n; });
    }
    if (sel) { sel.addEventListener('change', cols); cols(); }
  })();

  /* rich-text editor (CKEditor 5, assets/js/editor.js) uses the same media picker */
  window.mcmsOpenPicker = openPicker;

  /* visible warning if the rich-text editor did not start (missing files, blocked script…) */
  window.addEventListener('load', function () {
    setTimeout(function () {
      $$('textarea[data-editor]').forEach(function (ta) {
        if (ta.getAttribute('data-editor-ready')) { return; }
        var n = document.createElement('div');
        n.className = 'flash err';
        n.textContent = T('Редакторът CKEditor не е зареден (полето работи като обикновен HTML). Проверете дали public/assets/vendor/ckeditor5/ckeditor5.umd.js е качен (≈1.9 MB), опреснете с Ctrl+F5 и вижте конзолата (F12).');
        ta.parentNode.insertBefore(n, ta);
      });
    }, 4000);
  });

  /* ---- contact form builder: add / remove / reorder fields, show options per type ---- */
  $$('[data-cf-form]').forEach(function (form) {
    var list = form.querySelector('[data-cf-list]');
    var tpl = form.querySelector('[data-cf-tpl]');
    var next = list.children.length + 1000;
    var sync = function (row) {
      var type = row.querySelector('[data-cf-type]').value;
      row.querySelectorAll('[data-cf-for]').forEach(function (el) {
        var show = el.getAttribute('data-cf-for').split(' ').indexOf(type) !== -1;
        el.hidden = !show;
        el.querySelectorAll('input,select,textarea').forEach(function (i) { i.disabled = !show; });
      });
      var opt = row.querySelector('[data-cf-type] option:checked');
      var tl = row.querySelector('.cf-type-label');
      if (tl && opt) { tl.textContent = opt.textContent; }
    };
    var title = function (row) {
      var first = row.querySelector('[data-cf-label]');
      var t = row.querySelector('.cf-title');
      if (first && t && first.value.trim()) { t.textContent = first.value.trim(); }
    };
    var wire = function (row) {
      sync(row);
      row.querySelector('[data-cf-type]').addEventListener('change', function () { sync(row); });
      row.querySelectorAll('[data-cf-label]').forEach(function (i) { i.addEventListener('input', function () { title(row); }); });
    };
    list.querySelectorAll('[data-cf-row]').forEach(wire);
    form.addEventListener('click', function (e) {
      var row = e.target.closest('[data-cf-row]');
      if (e.target.closest('[data-cf-add]') && tpl) {
        var holder = document.createElement('div');
        holder.innerHTML = tpl.innerHTML.replace(/__I__/g, String(next++));
        var el = holder.firstElementChild;
        list.appendChild(el); wire(el);
        var f = el.querySelector('input:not([type=hidden])'); if (f) { f.focus(); }
        return;
      }
      if (!row) { return; }
      var btn = e.target.closest('[data-cf-up],[data-cf-down],[data-cf-del]');
      if (!btn) { return; }
      e.preventDefault();
      if (btn.hasAttribute('data-cf-del')) {
        if (window.confirm(T('Да премахна ли полето?'))) { row.remove(); }
      } else if (btn.hasAttribute('data-cf-up') && row.previousElementSibling) {
        list.insertBefore(row, row.previousElementSibling);
      } else if (btn.hasAttribute('data-cf-down') && row.nextElementSibling) {
        list.insertBefore(row.nextElementSibling, row);
      }
    });
    // renumber so the submitted order is the visual order
    form.addEventListener('submit', function () {
      list.querySelectorAll('[data-cf-row]').forEach(function (row, idx) {
        row.querySelectorAll('[name^="fields["]').forEach(function (i) {
          i.name = i.name.replace(/^fields\[[^\]]*\]/, 'fields[' + idx + ']');
        });
      });
    });
  });
})();
