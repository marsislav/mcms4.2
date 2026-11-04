/* mCMS — CKEditor 5 plugins "Gallery" and "ImageLightbox".
 *
 * Gallery (toolbar button "Gallery"): a block with any number of images, a caption per image and many display options.
 * Stored HTML (plain, sanitiser friendly — only classes, no scripts, no data attributes):
 *   <div class="mc-gallery mc-g-grid mc-g-c3 mc-g-gap-m mc-g-r-43 mc-g-cap-below mc-g-h-zoom mc-g-rad-s mc-g-lb">
 *     <figure class="mc-g-item"><a href="FULL.jpg"><img src="THUMB.jpg" alt="…" loading="lazy"></a><figcaption>Caption</figcaption></figure> …
 *   </div>
 * The public site renders it with assets/css/gallery.css and opens the lightbox (assets/js/lightbox.js) when the class mc-g-lb is present.
 *
 * ImageLightbox: a button in the image toolbar that marks a single image: <img data-lightbox="1"> opens in the lightbox, "0" never does.
 *
 * Defines window.McmsGallery and window.McmsImageLightbox (used by editor.js). */
(function () {
  'use strict';
  var CK = window.CKEDITOR;
  if (!CK) { return; }

  var Plugin = CK.Plugin, Command = CK.Command, Widget = CK.Widget, ButtonView = CK.ButtonView;
  var toWidget = CK.toWidget;
  var tr = function (t, r) { return typeof window.T === 'function' ? window.T(t, r) : t; };

  /* ------------------------------------------------------------------ options <-> classes */
  var DEFS = {
    layout: { prefix: 'mc-g-', values: ['grid', 'masonry', 'rows', 'slider'], def: 'grid' },
    cols: { prefix: 'mc-g-c', values: ['1', '2', '3', '4', '5', '6'], def: '3' },
    gap: { prefix: 'mc-g-gap-', values: ['0', 's', 'm', 'l'], def: 'm' },
    ratio: { prefix: 'mc-g-r-', values: ['auto', 'sq', '43', '32', '169', '34'], def: '43' },
    cap: { prefix: 'mc-g-cap-', values: ['none', 'below', 'hover', 'over'], def: 'below' },
    align: { prefix: 'mc-g-ca-', values: ['l', 'c'], def: 'l' },
    hover: { prefix: 'mc-g-h-', values: ['none', 'zoom', 'dim', 'lift'], def: 'zoom' },
    round: { prefix: 'mc-g-rad-', values: ['0', 's', 'm', 'l'], def: 's' }
  };
  var FLAGS = { shadow: 'mc-g-shadow', lb: 'mc-g-lb' };

  function defaultOpts() {
    var o = {};
    Object.keys(DEFS).forEach(function (k) { o[k] = DEFS[k].def; });
    o.shadow = false;
    o.lb = true;
    return o;
  }

  function optsToClass(o) {
    var c = ['mc-gallery'];
    Object.keys(DEFS).forEach(function (k) {
      var d = DEFS[k], v = d.values.indexOf(String(o[k])) !== -1 ? String(o[k]) : d.def;
      c.push(d.prefix + v);
    });
    Object.keys(FLAGS).forEach(function (k) { if (o[k]) { c.push(FLAGS[k]); } });
    return c.join(' ');
  }

  function classToOpts(names) {
    var o = defaultOpts();
    o.lb = false;
    names.forEach(function (n) {
      if (n === FLAGS.shadow) { o.shadow = true; return; }
      if (n === FLAGS.lb) { o.lb = true; return; }
      Object.keys(DEFS).forEach(function (k) {
        var d = DEFS[k];
        if (n.indexOf(d.prefix) === 0) {
          var v = n.slice(d.prefix.length);
          if (d.values.indexOf(v) !== -1) { o[k] = v; }
        }
      });
    });
    return o;
  }

  /* ------------------------------------------------------------------ rendering (data HTML and editor preview) */
  function cleanText(s, max) { return String(s == null ? '' : s).replace(/[\u0000-\u001f]+/g, ' ').trim().slice(0, max); }

  /* only site-relative (/uploads/…) or http(s) addresses; everything else (javascript:, data:, //host) is dropped */
  function safeSrc(u) {
    u = String(u || '').trim();
    if (/^\/(?!\/)/.test(u) || /^https?:\/\//i.test(u)) { return u.slice(0, 500); }
    return '';
  }

  function fill(root, items, opts, editing) {
    var doc = root.ownerDocument;
    items.forEach(function (it) {
      var full = safeSrc(it.full || it.src), thumb = safeSrc(it.thumb || it.src || it.full);
      if (!thumb) { return; }
      var fig = doc.createElement('figure');
      fig.className = 'mc-g-item';
      var a = doc.createElement('a');
      a.setAttribute('href', full || thumb);
      var img = doc.createElement('img');
      // one- and two-column galleries show the big picture, the rest the small one
      img.setAttribute('src', Number(opts.cols) <= 2 && full ? full : thumb);
      img.setAttribute('alt', cleanText(it.alt, 190));
      img.setAttribute('loading', 'lazy');
      a.appendChild(img);
      fig.appendChild(a);
      var cap = cleanText(it.cap, 300);
      if (cap) {
        var fc = doc.createElement('figcaption');
        fc.textContent = cap;
        fig.appendChild(fc);
      }
      root.appendChild(fig);
    });
  }

  /* ------------------------------------------------------------------ parsing (HTML -> model) */
  function walk(el, fn) {
    Array.from(el.getChildren()).forEach(function (ch) {
      if (ch.is('element')) { fn(ch); walk(ch, fn); }
    });
  }
  function textOf(el) {
    var out = '';
    Array.from(el.getChildren()).forEach(function (ch) {
      if (ch.is('$text')) { out += ch.data; } else if (ch.is('element')) { out += textOf(ch); }
    });
    return out;
  }
  function parseGallery(view) {
    var items = [];
    walk(view, function (node) {
      if (node.name !== 'figure') { return; }
      var a = null, img = null, cap = null;
      walk(node, function (n) {
        if (n.name === 'a' && !a) { a = n; }
        if (n.name === 'img' && !img) { img = n; }
        if (n.name === 'figcaption' && !cap) { cap = n; }
      });
      if (!img) { return; }
      var src = img.getAttribute('src') || '';
      items.push({
        thumb: src,
        full: (a && a.getAttribute('href')) || src,
        alt: img.getAttribute('alt') || '',
        cap: cap ? cleanText(textOf(cap), 300) : ''
      });
    });
    return { items: items, opts: classToOpts(Array.from(view.getClassNames())) };
  }

  function readGallery(el) {
    var items = [], opts = defaultOpts();
    try { items = JSON.parse(el.getAttribute('mcItems') || '[]'); } catch (e) { items = []; }
    try { opts = Object.assign(defaultOpts(), JSON.parse(el.getAttribute('mcOpts') || '{}')); } catch (e) { opts = defaultOpts(); }
    return { items: Array.isArray(items) ? items : [], opts: opts };
  }

  /* ------------------------------------------------------------------ dialog */
  function h(tag, attrs, kids) {
    var e = document.createElement(tag);
    Object.keys(attrs || {}).forEach(function (k) {
      if (k === 'text') { e.textContent = attrs[k]; } else if (k === 'class') { e.className = attrs[k]; } else { e.setAttribute(k, attrs[k]); }
    });
    (kids || []).forEach(function (c) { e.appendChild(c); });
    return e;
  }

  function choices() {
    return {
      layout: [['grid', tr('Grid')], ['masonry', tr('Masonry (columns)')], ['rows', tr('Rows (mosaic)')], ['slider', tr('Slider (swipe)')]],
      cols: [['1', '1'], ['2', '2'], ['3', '3'], ['4', '4'], ['5', '5'], ['6', '6']],
      gap: [['0', tr('None')], ['s', tr('Small')], ['m', tr('Medium')], ['l', tr('Large')]],
      ratio: [['auto', tr('Original')], ['sq', '1 : 1'], ['43', '4 : 3'], ['32', '3 : 2'], ['169', '16 : 9'], ['34', '3 : 4']],
      cap: [['below', tr('Below the image')], ['hover', tr('On the image, on hover')], ['over', tr('On the image, always')], ['none', tr('Hidden')]],
      align: [['l', tr('Left')], ['c', tr('Centered')]],
      hover: [['zoom', tr('Zoom')], ['dim', tr('Darken')], ['lift', tr('Lift')], ['none', tr('None')]],
      round: [['0', tr('Square')], ['s', tr('Slightly rounded')], ['m', tr('Rounded')], ['l', tr('Very rounded')]]
    };
  }
  var LABELS = function () {
    return {
      layout: tr('Layout'), cols: tr('Columns'), gap: tr('Space between images'), ratio: tr('Image shape (grid / slider)'),
      cap: tr('Captions'), align: tr('Caption alignment'), hover: tr('Hover effect'), round: tr('Corners')
    };
  };

  function openDialog(initial, done) {
    var state = { items: initial.items.map(function (x) { return Object.assign({}, x); }), opts: Object.assign(defaultOpts(), initial.opts) };
    var wrap = h('div', { class: 'modal mc-gdlg', role: 'presentation' });
    wrap.setAttribute('style', 'z-index:40');
    var box = h('div', { class: 'modal-box', role: 'dialog', 'aria-modal': 'true', 'aria-label': tr('Gallery') });
    var head = h('div', { class: 'modal-head' }, [h('strong', { text: tr('Gallery') })]);
    var addLib = h('button', { type: 'button', class: 'btn sm primary', text: '＋ ' + tr('From media library') });
    var addUp = h('button', { type: 'button', class: 'btn sm', text: '⬆ ' + tr('Upload') });
    var fileIn = h('input', { type: 'file', accept: 'image/jpeg,image/png,image/webp,image/gif', multiple: '', hidden: '' });
    var close = h('button', { type: 'button', class: 'btn sm', text: '✕', 'aria-label': tr('Close') });
    head.appendChild(h('span', { class: 'muted', text: '' }));
    head.appendChild(addLib); head.appendChild(addUp); head.appendChild(fileIn); head.appendChild(close);
    var msg = h('div', { class: 'picker-msg', role: 'status', hidden: '' });
    var body = h('div', { class: 'mc-gdlg-body' });
    var list = h('div', { class: 'mc-gdlg-list' });
    var side = h('div', { class: 'mc-gdlg-opts' });
    body.appendChild(list); body.appendChild(side);
    var foot = h('div', { class: 'modal-foot' });
    var ok = h('button', { type: 'button', class: 'btn primary', text: tr('Save gallery') });
    var cancel = h('button', { type: 'button', class: 'btn', text: tr('Cancel') });
    foot.appendChild(ok); foot.appendChild(document.createTextNode(' ')); foot.appendChild(cancel);
    box.appendChild(head); box.appendChild(msg); box.appendChild(body); box.appendChild(foot);
    wrap.appendChild(box);
    document.body.appendChild(wrap);
    document.body.classList.add('modal-open');

    function say(t, bad) { msg.hidden = !t; msg.textContent = t || ''; msg.className = 'picker-msg' + (bad ? ' bad' : ''); }

    function addMedia(m) {
      if (!m || !m.image) { return; }
      state.items.push({ full: m.url, thumb: m.thumb || m.url, alt: m.alt || '', cap: '' });
      drawList();
    }

    function drawList() {
      list.textContent = '';
      if (!state.items.length) { list.appendChild(h('p', { class: 'muted', text: tr('No images yet. Add them from the media library or upload new ones.') })); return; }
      state.items.forEach(function (it, i) {
        var row = h('div', { class: 'mc-gdlg-row', draggable: 'true' });
        row.appendChild(h('img', { src: it.thumb || it.full, alt: '' }));
        var fields = h('div', { class: 'mc-gdlg-fields' });
        var cap = h('textarea', { rows: '2', maxlength: '300', placeholder: tr('Caption (optional)') });
        cap.value = it.cap || '';
        cap.addEventListener('input', function () { it.cap = cap.value; });
        var alt = h('input', { type: 'text', maxlength: '190', placeholder: tr('Alt text (for screen readers / SEO)') });
        alt.value = it.alt || '';
        alt.addEventListener('input', function () { it.alt = alt.value; });
        fields.appendChild(cap); fields.appendChild(alt);
        var tools = h('div', { class: 'mc-gdlg-tools' });
        var mk = function (label, title, fn, disabled) {
          var b = h('button', { type: 'button', class: 'btn sm', text: label, title: title, 'aria-label': title });
          if (disabled) { b.disabled = true; }
          b.addEventListener('click', fn);
          tools.appendChild(b);
        };
        mk('↑', tr('Move up'), function () { state.items.splice(i - 1, 0, state.items.splice(i, 1)[0]); drawList(); }, i === 0);
        mk('↓', tr('Move down'), function () { state.items.splice(i + 1, 0, state.items.splice(i, 1)[0]); drawList(); }, i === state.items.length - 1);
        mk('✕', tr('Remove'), function () { state.items.splice(i, 1); drawList(); });
        row.appendChild(fields); row.appendChild(tools);
        row.addEventListener('dragstart', function (e) { e.dataTransfer.setData('text/plain', String(i)); row.classList.add('drag'); });
        row.addEventListener('dragend', function () { row.classList.remove('drag'); });
        row.addEventListener('dragover', function (e) { e.preventDefault(); });
        row.addEventListener('drop', function (e) {
          e.preventDefault();
          var from = parseInt(e.dataTransfer.getData('text/plain'), 10);
          if (isNaN(from) || from === i) { return; }
          state.items.splice(i, 0, state.items.splice(from, 1)[0]);
          drawList();
        });
        list.appendChild(row);
      });
    }

    function drawOptions() {
      side.textContent = '';
      var ch = choices(), lb = LABELS();
      Object.keys(DEFS).forEach(function (k) {
        var sel = h('select', {});
        ch[k].forEach(function (pair) {
          var o = h('option', { value: pair[0], text: pair[1] });
          if (String(state.opts[k]) === pair[0]) { o.selected = true; }
          sel.appendChild(o);
        });
        sel.addEventListener('change', function () { state.opts[k] = sel.value; });
        side.appendChild(h('label', {}, [document.createTextNode(lb[k]), sel]));
      });
      var flag = function (key, text) {
        var cb = h('input', { type: 'checkbox' });
        cb.checked = !!state.opts[key];
        cb.addEventListener('change', function () { state.opts[key] = cb.checked; });
        side.appendChild(h('label', { class: 'check' }, [cb, document.createTextNode(' ' + text)]));
      };
      flag('lb', tr('Open images in the lightbox on click'));
      flag('shadow', tr('Soft shadow'));
    }

    function shut() { wrap.remove(); if (!document.querySelector('.modal:not([hidden])')) { document.body.classList.remove('modal-open'); } }

    addLib.addEventListener('click', function () {
      if (typeof window.mcmsOpenPicker === 'function') { window.mcmsOpenPicker(addMedia, true); }
    });
    addUp.addEventListener('click', function () { fileIn.click(); });
    fileIn.addEventListener('change', function () {
      if (!fileIn.files.length || typeof window.mcmsUploadFiles !== 'function') { return; }
      say(tr('Uploading…'));
      window.mcmsUploadFiles(Array.prototype.slice.call(fileIn.files)).then(function (res) {
        res.data.forEach(addMedia);
        say(res.errors.length ? res.errors.join(' | ') : '', !!res.errors.length);
      }).catch(function (e) { say(e.message || tr('Upload failed.'), true); });
      fileIn.value = '';
    });
    box.addEventListener('dragover', function (e) { e.preventDefault(); });
    box.addEventListener('drop', function (e) {
      if (!e.dataTransfer || !e.dataTransfer.files.length || typeof window.mcmsUploadFiles !== 'function') { return; }
      e.preventDefault();
      var files = Array.prototype.filter.call(e.dataTransfer.files, function (f) { return /^image\//.test(f.type); });
      if (!files.length) { return; }
      say(tr('Uploading…'));
      window.mcmsUploadFiles(files).then(function (res) { res.data.forEach(addMedia); say(res.errors.join(' | '), !!res.errors.length); })
        .catch(function (err) { say(err.message || tr('Upload failed.'), true); });
    });
    ok.addEventListener('click', function () {
      if (!state.items.length) { say(tr('Add at least one image.'), true); return; }
      shut(); done({ items: state.items, opts: state.opts });
    });
    cancel.addEventListener('click', shut);
    close.addEventListener('click', shut);
    wrap.addEventListener('mousedown', function (e) { if (e.target === wrap) { shut(); } });
    wrap.addEventListener('keydown', function (e) { if (e.key === 'Escape') { e.stopPropagation(); shut(); } });

    drawList(); drawOptions();
    ok.focus();
  }

  /* ------------------------------------------------------------------ Gallery plugin */
  class GalleryCommand extends Command {
    refresh() {
      var sel = this.editor.model.document.selection;
      this.isEnabled = this.editor.model.schema.findAllowedParent(sel.getFirstPosition(), 'mcGallery') !== null || !!this._selected();
    }
    _selected() {
      var el = this.editor.model.document.selection.getSelectedElement();
      return el && el.is('element', 'mcGallery') ? el : null;
    }
    execute(data) {
      var editor = this.editor, old = this._selected();
      if (!data || !data.items) { return; }
      var attrs = { mcItems: JSON.stringify(data.items), mcOpts: JSON.stringify(data.opts) };
      editor.model.change(function (writer) {
        var el = writer.createElement('mcGallery', attrs);
        if (old) {
          var pos = writer.createPositionBefore(old);
          writer.remove(old);
          editor.model.insertContent(el, pos);
        } else if (typeof editor.model.insertObject === 'function') {
          editor.model.insertObject(el, null, null, { setSelection: 'on' });
          return;
        } else {
          editor.model.insertContent(el);
        }
        writer.setSelection(el, 'on');
      });
    }
  }

  function editGallery(editor) {
    var cmd = editor.commands.get('mcGallery');
    var cur = cmd._selected();
    var data = cur ? readGallery(cur) : { items: [], opts: defaultOpts() };
    if (!cur) { data.opts.lb = (document.querySelector('meta[name="mcms-lightbox-default"]') || {}).content !== '0'; }
    openDialog(data, function (res) { editor.execute('mcGallery', res); editor.editing.view.focus(); });
  }

  class Gallery extends Plugin {
    static get pluginName() { return 'McmsGallery'; }
    static get requires() { return [Widget]; }

    init() {
      var editor = this.editor, schema = editor.model.schema, conversion = editor.conversion;

      schema.register('mcGallery', { inheritAllFrom: '$blockObject', allowAttributes: ['mcItems', 'mcOpts'] });

      /* HTML -> model: the whole <div class="mc-gallery"> is read at once; its children are never converted one by one */
      conversion.for('upcast').add(function (dispatcher) {
        dispatcher.on('element:div', function (evt, data, api) {
          var view = data.viewItem;
          if (!view.hasClass('mc-gallery') || !api.consumable.test(view, { name: true })) { return; }
          var g = parseGallery(view);
          var el = api.writer.createElement('mcGallery', { mcItems: JSON.stringify(g.items), mcOpts: JSON.stringify(g.opts) });
          if (!api.safeInsert(el, data.modelCursor)) { return; }
          api.consumable.consume(view, { name: true });
          api.consumable.consume(view, { classes: Array.from(view.getClassNames()) });
          api.updateConversionResult(el, data);
        }, { priority: 'high' });
      });

      var make = function (editing) {
        return function (modelEl, api) {
          var g = readGallery(modelEl), writer = api.writer;
          var raw = writer.createRawElement('div', { class: optsToClass(g.opts) }, function (dom) {
            fill(dom, g.items, g.opts, editing);
            if (editing) {
              dom.classList.add('mc-g-editing');
              var b = document.createElement('button');
              b.type = 'button'; b.className = 'mc-g-edit'; b.textContent = '✎ ' + tr('Edit gallery');
              b.addEventListener('click', function (e) {
                e.preventDefault(); e.stopPropagation();
                editor.model.change(function (w) { w.setSelection(modelEl, 'on'); });
                editGallery(editor);
              });
              dom.insertBefore(b, dom.firstChild);
              dom.addEventListener('dblclick', function () {
                editor.model.change(function (w) { w.setSelection(modelEl, 'on'); });
                editGallery(editor);
              });
            }
          });
          if (!editing) { return raw; }
          // a widget root must be a container element, so the raw preview sits inside one
          var holder = writer.createContainerElement('div', { class: 'mc-g-widget' });
          writer.insert(writer.createPositionAt(holder, 0), raw);
          return toWidget(holder, writer, { label: tr('Gallery') });
        };
      };
      conversion.for('dataDowncast').elementToElement({ model: 'mcGallery', view: make(false) });
      conversion.for('editingDowncast').elementToElement({ model: 'mcGallery', view: make(true) });

      editor.commands.add('mcGallery', new GalleryCommand(editor));

      editor.ui.componentFactory.add('mcGallery', function (locale) {
        var v = new ButtonView(locale), cmd = editor.commands.get('mcGallery');
        v.set({
          label: tr('Gallery'), tooltip: true, withText: false,
          icon: '<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M2 3h7v6H2zM11 3h7v6h-7zM2 11h7v6H2zM11 11h7v6h-7z"/></svg>'
        });
        v.bind('isEnabled').to(cmd);
        v.on('execute', function () { editGallery(editor); });
        return v;
      });
    }
  }

  /* ------------------------------------------------------------------ single image: "open in lightbox" switch */
  var LB_ON = '1', LB_OFF = '0';

  class LightboxCommand extends Command {
    refresh() {
      var el = this._image();
      this.isEnabled = !!el;
      this.value = el ? this._effective(el) : false;
    }
    _image() {
      var utils = this.editor.plugins.get('ImageUtils');
      return utils.getClosestSelectedImageElement(this.editor.model.document.selection);
    }
    _effective(el) {
      var v = el.getAttribute('mcLightbox');
      if (v === LB_ON) { return true; }
      if (v === LB_OFF) { return false; }
      return (document.querySelector('meta[name="mcms-lightbox-all"]') || {}).content === '1';
    }
    execute(force) {
      var el = this._image(), self = this;
      if (!el) { return; }
      var on = typeof force === 'boolean' ? force : !this._effective(el);
      this.editor.model.change(function (writer) { writer.setAttribute('mcLightbox', on ? LB_ON : LB_OFF, el); });
      self.refresh();
    }
  }

  class ImageLightbox extends Plugin {
    static get pluginName() { return 'McmsImageLightbox'; }
    static get requires() { return ['ImageUtils']; }

    init() {
      var editor = this.editor, schema = editor.model.schema, conversion = editor.conversion;
      var utils = editor.plugins.get('ImageUtils');

      ['imageBlock', 'imageInline'].forEach(function (name) {
        if (schema.isRegistered(name)) { schema.extend(name, { allowAttributes: ['mcLightbox'] }); }
      });

      conversion.for('upcast').attributeToAttribute({
        view: { name: 'img', key: 'data-lightbox', value: /^[01]$/ },
        model: { key: 'mcLightbox', value: function (v) { return v.getAttribute('data-lightbox') === '1' ? LB_ON : LB_OFF; } }
      });

      ['imageBlock', 'imageInline'].forEach(function (name) {
        conversion.for('downcast').add(function (dispatcher) {
          dispatcher.on('attribute:mcLightbox:' + name, function (evt, data, api) {
            if (!api.consumable.consume(data.item, evt.name)) { return; }
            var viewEl = api.mapper.toViewElement(data.item);
            var img = viewEl ? utils.findViewImgElement(viewEl) : null;
            if (!img) { return; }
            if (data.attributeNewValue) { api.writer.setAttribute('data-lightbox', data.attributeNewValue, img); } else { api.writer.removeAttribute('data-lightbox', img); }
          });
        });
      });

      editor.commands.add('mcImageLightbox', new LightboxCommand(editor));

      editor.ui.componentFactory.add('mcImageLightbox', function (locale) {
        var v = new ButtonView(locale), cmd = editor.commands.get('mcImageLightbox');
        v.set({
          label: tr('Open in lightbox'), tooltip: true, withText: true, isToggleable: true
        });
        v.bind('isEnabled').to(cmd);
        v.bind('isOn').to(cmd, 'value');
        v.on('execute', function () { editor.execute('mcImageLightbox'); editor.editing.view.focus(); });
        return v;
      });

      /* images uploaded by drag & drop / paste follow the default from Settings > Media */
      if (editor.plugins.has('ImageUploadEditing')) {
        editor.plugins.get('ImageUploadEditing').on('uploadComplete', function (evt, d) {
          var def = (document.querySelector('meta[name="mcms-lightbox-default"]') || {}).content === '1';
          if (def && d && d.imageElement) {
            editor.model.change(function (writer) { writer.setAttribute('mcLightbox', LB_ON, d.imageElement); });
          }
        });
      }
    }
  }

  window.McmsGallery = Gallery;
  window.McmsImageLightbox = ImageLightbox;
})();
