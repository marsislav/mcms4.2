/* mCMS — CKEditor 5 plugin "FontAwesomeIcons": insert Font Awesome Free icons into text.
 * Stored as  <span class="fa-solid fa-house" aria-hidden="true"></span>  (allowed by the Sanitizer).
 * Icon list: assets/vendor/fontawesome/icons.json (name, label, styles s/r/b, search terms).
 * Defines window.McmsFontAwesome (used by editor.js). */
(function () {
  'use strict';
  var CK = window.CKEDITOR;
  if (!CK) { return; }

  var Plugin = CK.Plugin, Widget = CK.Widget, Command = CK.Command, ButtonView = CK.ButtonView;
  var toWidget = CK.toWidget;

  // …/assets/js/editor-fa.js  ->  …/assets/vendor/fontawesome/icons.json
  var script = document.currentScript && document.currentScript.src;
  var ICONS_URL = script ? script.replace(/\/js\/editor-fa\.js.*$/, '/vendor/fontawesome/icons.json') : '/assets/vendor/fontawesome/icons.json';
  var STYLE_CLASS = { s: 'fa-solid', r: 'fa-regular', b: 'fa-brands' };
  var STYLE_LABEL = { s: 'Solid', r: 'Regular', b: 'Brands' };
  var CLASS_OK = /^fa-[a-z0-9-]{1,60}$/;

  var iconsPromise = null;
  function loadIcons() {
    if (!iconsPromise) {
      iconsPromise = fetch(ICONS_URL, { credentials: 'same-origin' })
        .then(function (r) { if (!r.ok) { throw new Error('http ' + r.status); } return r.json(); });
    }
    return iconsPromise;
  }

  /* ---------------- picker dialog ---------------- */
  var dialog = null;
  function openPicker(onPick) {
    if (!dialog) { dialog = buildDialog(); }
    dialog.open(onPick);
  }

  function buildDialog() {
    var wrap = document.createElement('div');
    wrap.className = 'modal'; wrap.hidden = true;
    var box = document.createElement('div');
    box.className = 'modal-box'; box.setAttribute('role', 'dialog'); box.setAttribute('aria-modal', 'true'); box.setAttribute('aria-label', 'Икони');
    var head = document.createElement('div'); head.className = 'modal-head';
    var title = document.createElement('strong'); title.textContent = 'Икони (Font Awesome)';
    var q = document.createElement('input'); q.type = 'search'; q.placeholder = 'Търсене (напр. home, user, arrow)…'; q.className = 'picker-q';
    var style = document.createElement('select'); style.className = 'fa-style';
    [['all', 'Всички'], ['s', 'Solid'], ['r', 'Regular'], ['b', 'Brands']].forEach(function (o) {
      var op = document.createElement('option'); op.value = o[0]; op.textContent = o[1]; style.appendChild(op);
    });
    var close = document.createElement('button'); close.type = 'button'; close.className = 'btn sm picker-close'; close.textContent = '✕';
    head.appendChild(title); head.appendChild(q); head.appendChild(style); head.appendChild(close);
    var grid = document.createElement('div'); grid.className = 'picker-grid fa-grid';
    var foot = document.createElement('div'); foot.className = 'modal-foot';
    var more = document.createElement('button'); more.type = 'button'; more.className = 'btn sm'; more.textContent = 'Още…'; more.hidden = true;
    foot.appendChild(more);
    box.appendChild(head); box.appendChild(grid); box.appendChild(foot); wrap.appendChild(box);
    document.body.appendChild(wrap);

    var state = { icons: null, matches: [], shown: 0, onPick: null, timer: null };
    var PAGE = 180;

    function hide() { wrap.hidden = true; document.body.classList.remove('modal-open'); }

    function matches() {
      var term = q.value.trim().toLowerCase(), st = style.value, out = [];
      state.icons.forEach(function (ic) {
        if (st !== 'all' && ic[2].indexOf(st) === -1) { return; }
        if (term && (ic[0] + ' ' + ic[1] + ' ' + ic[3]).toLowerCase().indexOf(term) === -1) { return; }
        out.push(ic);
      });
      return out;
    }

    function chosenStyle(ic) {
      var st = style.value;
      if (st !== 'all' && ic[2].indexOf(st) !== -1) { return st; }
      return ic[2].indexOf('s') !== -1 ? 's' : ic[2].charAt(0);
    }

    function render(reset) {
      if (reset) { grid.textContent = ''; state.shown = 0; state.matches = matches(); }
      var slice = state.matches.slice(state.shown, state.shown + PAGE);
      slice.forEach(function (ic) {
        var cls = STYLE_CLASS[chosenStyle(ic)] + ' fa-' + ic[0];
        var b = document.createElement('button');
        b.type = 'button'; b.className = 'picker-item fa-item'; b.title = ic[1] + ' (' + cls + ')';
        var i = document.createElement('span'); i.className = cls; i.setAttribute('aria-hidden', 'true');
        var n = document.createElement('small'); n.textContent = ic[0];
        b.appendChild(i); b.appendChild(n);
        b.addEventListener('click', function () { var cb = state.onPick; hide(); if (cb) { cb(cls); } });
        grid.appendChild(b);
      });
      state.shown += slice.length;
      more.hidden = state.shown >= state.matches.length;
      if (!state.matches.length) { grid.textContent = 'Няма намерени икони.'; }
    }

    function refresh() { clearTimeout(state.timer); state.timer = setTimeout(function () { if (state.icons) { render(true); } }, 150); }
    q.addEventListener('input', refresh);
    style.addEventListener('change', function () { render(true); });
    more.addEventListener('click', function () { render(false); });
    close.addEventListener('click', hide);
    wrap.addEventListener('click', function (e) { if (e.target === wrap) { hide(); } });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !wrap.hidden) { hide(); } });

    return {
      open: function (onPick) {
        state.onPick = onPick; wrap.hidden = false; document.body.classList.add('modal-open'); q.value = '';
        grid.textContent = 'Зареждане…';
        loadIcons().then(function (icons) { state.icons = icons; render(true); q.focus(); })
          .catch(function () { grid.textContent = 'Списъкът с икони не може да се зареди (assets/vendor/fontawesome/icons.json).'; });
      }
    };
  }

  /* ---------------- command ---------------- */
  class InsertFaIconCommand extends Command {
    refresh() {
      var pos = this.editor.model.document.selection.getFirstPosition();
      this.isEnabled = this.editor.model.schema.checkChild(pos, 'faIcon');
    }
    execute(faClass) {
      if (typeof faClass !== 'string') { return; }
      var cls = faClass.split(/\s+/).filter(function (c) { return CLASS_OK.test(c); }).join(' ');
      if (!cls) { return; }
      var model = this.editor.model;
      model.change(function (writer) {
        model.insertObject(writer.createElement('faIcon', { faClass: cls }), null, null, { setSelection: 'after' });
      });
    }
  }

  /* ---------------- plugin ---------------- */
  class FontAwesomeIcons extends Plugin {
    static get pluginName() { return 'FontAwesomeIcons'; }
    static get requires() { return [Widget]; }

    init() {
      var editor = this.editor, conversion = editor.conversion;

      editor.model.schema.register('faIcon', { inheritAllFrom: '$inlineObject', allowAttributes: ['faClass'] });

      conversion.for('upcast').elementToElement({
        view: { name: 'span', classes: /^fa-(solid|regular|brands)$/ },
        model: function (view, api) {
          var cls = Array.from(view.getClassNames()).filter(function (c) { return CLASS_OK.test(c); }).join(' ');
          return api.writer.createElement('faIcon', { faClass: cls });
        },
        converterPriority: 'high'
      });

      conversion.for('dataDowncast').elementToElement({
        model: 'faIcon',
        view: function (m, api) {
          return api.writer.createEmptyElement('span', { class: m.getAttribute('faClass'), 'aria-hidden': 'true' });
        }
      });
      conversion.for('editingDowncast').elementToElement({
        model: 'faIcon',
        view: function (m, api) {
          var span = api.writer.createContainerElement('span', { class: m.getAttribute('faClass') + ' mc-fa-widget' });
          return toWidget(span, api.writer, { label: 'икона' });
        }
      });

      editor.commands.add('insertFaIcon', new InsertFaIconCommand(editor));

      editor.ui.componentFactory.add('faIcons', function (locale) {
        var view = new ButtonView(locale), cmd = editor.commands.get('insertFaIcon');
        view.set({ label: 'Икона (Font Awesome)', tooltip: true, withText: false,
          icon: '<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10 1.5l2.5 5.4 5.9.7-4.4 4 1.2 5.8L10 14.5 4.8 17.4 6 11.6 1.6 7.6l5.9-.7z"/></svg>' });
        view.bind('isEnabled').to(cmd);
        view.on('execute', function () {
          openPicker(function (cls) { editor.execute('insertFaIcon', cls); editor.editing.view.focus(); });
        });
        return view;
      });
    }
  }

  window.McmsFontAwesome = FontAwesomeIcons;
})();
