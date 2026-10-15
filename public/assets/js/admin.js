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
      '<div class="modal-box" role="dialog" aria-modal="true" aria-label="Media">' +
      '<div class="modal-head"><strong>Choose a file</strong>' +
      '<input type="search" placeholder="Search…" class="picker-q"><button type="button" class="btn sm picker-close">✕</button></div>' +
      '<div class="picker-grid"></div>' +
      '<div class="modal-foot"><button type="button" class="btn sm picker-more" hidden>More…</button></div></div>';
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
          if (!res.data.length && replace) { grid.textContent = 'No files.'; }
          state.next = res.next; more.hidden = !res.next;
        })
        .catch(function () { grid.textContent = 'Error while loading.'; });
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

  /* ---- minimal HTML editor toolbar ---- */
  var tools = [
    ['B', 'Bold', ['<strong>', '</strong>']],
    ['I', 'Italic', ['<em>', '</em>']],
    ['H2', 'Heading 2', ['<h2>', '</h2>']],
    ['H3', 'Heading 3', ['<h3>', '</h3>']],
    ['¶', 'Paragraph', ['<p>', '</p>']],
    ['• List', 'Unordered list', ['<ul>\n  <li>', '</li>\n</ul>']],
    ['1. List', 'Ordered list', ['<ol>\n  <li>', '</li>\n</ol>']],
    ['❝', 'Quote', ['<blockquote>', '</blockquote>']],
    ['</>', 'Code', ['<code>', '</code>']],
    ['—', 'Divider', ['<hr>', '']],
    ['Link', 'Insert link', 'link'],
    ['Image', 'Insert image from media', 'image']
  ];

  function wrapSel(ta, before, after) {
    var s = ta.selectionStart, e = ta.selectionEnd, v = ta.value;
    ta.value = v.slice(0, s) + before + v.slice(s, e) + after + v.slice(e);
    ta.focus();
    ta.selectionStart = s + before.length; ta.selectionEnd = e + before.length;
    ta.dispatchEvent(new Event('input', { bubbles: true }));
  }
  function esc(s) { return s.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;'); }

  $$('textarea[data-editor]').forEach(function (ta) {
    var bar = document.createElement('div'); bar.className = 'editor-bar';
    tools.forEach(function (t) {
      var b = document.createElement('button'); b.type = 'button'; b.className = 'btn sm'; b.textContent = t[0]; b.title = t[1];
      b.addEventListener('click', function () {
        if (t[2] === 'link') {
          var url = window.prompt('Address (https://… or /path):', 'https://');
          if (url) { wrapSel(ta, '<a href="' + esc(url) + '">', '</a>'); }
        } else if (t[2] === 'image') {
          openPicker(function (m) {
            var alt = m.alt || '';
            var tag = m.image ? '<img src="' + esc(m.url) + '" alt="' + esc(alt) + '">' : '<a href="' + esc(m.url) + '">' + esc(m.name) + '</a>';
            wrapSel(ta, tag, '');
          }, false);
        } else { wrapSel(ta, t[2][0], t[2][1]); }
      });
      bar.appendChild(b);
    });
    ta.parentNode.insertBefore(bar, ta);
  });
})();
