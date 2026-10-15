/* mCMS — CKEditor 5 plugin "GridLayout": rows with 1–12 columns (responsive).
 *
 * Stored HTML (plain, sanitiser friendly — only classes):
 *   <div class="mc-row mc-cols-3"><div class="mc-col mc-span-1">…</div> … </div>
 * `mc-cols-N`  = number of grid tracks in the row (1–12)
 * `mc-span-K`  = how many tracks a column occupies (1–N)
 * CSS (assets/css/grid.css) makes it 2 columns on tablets and 1 column on phones.
 *
 * Defines window.McmsGridLayout (used by editor.js). */
(function () {
  'use strict';
  var CK = window.CKEDITOR;
  if (!CK) { return; }

  var MAX = 12;
  var Plugin = CK.Plugin, Command = CK.Command, Widget = CK.Widget, ButtonView = CK.ButtonView;
  var toWidget = CK.toWidget, toWidgetEditable = CK.toWidgetEditable;

  var clamp = function (n, lo, hi) { n = parseInt(n, 10); if (isNaN(n)) { n = lo; } return Math.max(lo, Math.min(hi, n)); };

  function rowOf(editor) {
    var sel = editor.model.document.selection;
    var el = sel.getSelectedElement();
    if (el && el.is('element', 'gridRow')) { return el; }
    var parent = sel.getFirstPosition().parent;
    return parent.findAncestor ? parent.findAncestor('gridRow', { includeSelf: true }) : null;
  }
  function colOf(editor) {
    var parent = editor.model.document.selection.getFirstPosition().parent;
    return parent.findAncestor ? parent.findAncestor('gridCol', { includeSelf: true }) : null;
  }
  function placeCursorIn(writer, col) {
    var target = col.getChild(0) || col;
    writer.setSelection(writer.createPositionAt(target, 0));
  }

  /* ---------------- commands ---------------- */
  class InsertGridRowCommand extends Command {
    refresh() {
      var editor = this.editor;
      this.isEnabled = !colOf(editor) &&
        editor.model.schema.findAllowedParent(editor.model.document.selection.getFirstPosition(), 'gridRow') !== null;
    }
    execute(count) {
      var editor = this.editor, n = clamp(count, 1, MAX);
      editor.model.change(function (writer) {
        var row = writer.createElement('gridRow', { columns: n });
        for (var i = 0; i < n; i++) {
          var col = writer.createElement('gridCol', { span: 1 });
          writer.appendElement('paragraph', col);
          writer.append(col, row);
        }
        editor.model.insertContent(row);
        placeCursorIn(writer, row.getChild(0));
      });
    }
  }

  class GridColSpanCommand extends Command {
    refresh() {
      var col = colOf(this.editor);
      this.isEnabled = !!col;
      this.value = col ? col.getAttribute('span') : null;
    }
    execute(span) {
      var col = colOf(this.editor);
      if (!col) { return; }
      var max = col.parent.getAttribute('columns') || 1;
      this.editor.model.change(function (writer) { writer.setAttribute('span', clamp(span, 1, max), col); });
    }
  }

  class GridAddColCommand extends Command {
    refresh() {
      var row = rowOf(this.editor);
      this.isEnabled = !!row && (row.getAttribute('columns') || 1) < MAX;
    }
    execute() {
      var editor = this.editor, row = rowOf(editor), cur = colOf(editor);
      if (!row) { return; }
      editor.model.change(function (writer) {
        var col = writer.createElement('gridCol', { span: 1 });
        writer.appendElement('paragraph', col);
        writer.insert(col, cur ? writer.createPositionAfter(cur) : writer.createPositionAt(row, 'end'));
        writer.setAttribute('columns', (row.getAttribute('columns') || 1) + 1, row);
        placeCursorIn(writer, col);
      });
    }
  }

  class GridRemoveColCommand extends Command {
    refresh() {
      var row = rowOf(this.editor);
      this.isEnabled = !!row && !!colOf(this.editor) && (row.getAttribute('columns') || 1) > 1;
    }
    execute() {
      var editor = this.editor, row = rowOf(editor), col = colOf(editor);
      if (!row || !col) { return; }
      editor.model.change(function (writer) {
        var idx = col.index, left = (row.getAttribute('columns') || 1) - 1;
        writer.remove(col);
        writer.setAttribute('columns', left, row);
        Array.prototype.slice.call(row.getChildren()).forEach(function (c) {
          if ((c.getAttribute('span') || 1) > left) { writer.setAttribute('span', left, c); }
        });
        placeCursorIn(writer, row.getChild(Math.min(idx, row.childCount - 1)));
      });
    }
  }

  class GridRemoveRowCommand extends Command {
    refresh() { this.isEnabled = !!rowOf(this.editor); }
    execute() {
      var editor = this.editor, row = rowOf(editor);
      if (!row) { return; }
      editor.model.change(function (writer) { writer.remove(row); });
    }
  }

  /* ---------------- plugin ---------------- */
  class GridLayout extends Plugin {
    static get pluginName() { return 'GridLayout'; }
    static get requires() { return [Widget]; }

    init() {
      var editor = this.editor, schema = editor.model.schema, conversion = editor.conversion;

      schema.register('gridRow', { allowWhere: '$block', isLimit: true, allowAttributes: ['columns'] });
      schema.register('gridCol', { allowIn: 'gridRow', allowContentOf: '$root', isLimit: true, allowAttributes: ['span'] });
      schema.addChildCheck(function (context, def) {
        if (def.name === 'gridRow' && Array.from(context.getNames()).indexOf('gridCol') !== -1) { return false; } // no nested rows
      });

      /* upcast: HTML -> model */
      conversion.for('upcast').elementToElement({
        view: { name: 'div', classes: 'mc-row' },
        model: function (view, api) {
          var n = 1;
          Array.from(view.getClassNames()).forEach(function (c) { var m = /^mc-cols-(\d{1,2})$/.exec(c); if (m) { n = clamp(m[1], 1, MAX); } });
          return api.writer.createElement('gridRow', { columns: n });
        },
        converterPriority: 'high'
      });
      conversion.for('upcast').elementToElement({
        view: { name: 'div', classes: 'mc-col' },
        model: function (view, api) {
          var n = 1;
          Array.from(view.getClassNames()).forEach(function (c) { var m = /^mc-span-(\d{1,2})$/.exec(c); if (m) { n = clamp(m[1], 1, MAX); } });
          return api.writer.createElement('gridCol', { span: n });
        },
        converterPriority: 'high'
      });

      /* downcast: model -> HTML (data) and editing view */
      conversion.for('dataDowncast').elementToElement({
        model: 'gridRow',
        view: function (m, api) { return api.writer.createContainerElement('div', { class: 'mc-row' }); }
      });
      conversion.for('dataDowncast').elementToElement({
        model: 'gridCol',
        view: function (m, api) { return api.writer.createContainerElement('div', { class: 'mc-col' }); }
      });
      conversion.for('editingDowncast').elementToElement({
        model: 'gridRow',
        view: function (m, api) { return toWidget(api.writer.createContainerElement('div', { class: 'mc-row' }), api.writer, { label: 'Колони' }); }
      });
      conversion.for('editingDowncast').elementToElement({
        model: 'gridCol',
        view: function (m, api) { return toWidgetEditable(api.writer.createEditableElement('div', { class: 'mc-col' }), api.writer); }
      });

      /* attributes -> classes (works on insert and on later changes) */
      var classFor = function (modelName, attr, prefix) {
        return function (dispatcher) {
          dispatcher.on('attribute:' + attr + ':' + modelName, function (evt, data, conv) {
            if (!conv.consumable.consume(data.item, evt.name)) { return; }
            var view = conv.mapper.toViewElement(data.item);
            if (!view) { return; }
            if (data.attributeOldValue != null) { conv.writer.removeClass(prefix + data.attributeOldValue, view); }
            if (data.attributeNewValue != null) { conv.writer.addClass(prefix + data.attributeNewValue, view); }
          });
        };
      };
      conversion.for('downcast').add(classFor('gridRow', 'columns', 'mc-cols-'));
      conversion.for('downcast').add(classFor('gridCol', 'span', 'mc-span-'));

      /* a column must never be completely empty */
      editor.model.document.registerPostFixer(function (writer) {
        var changed = false;
        editor.model.document.differ.getChanges().forEach(function (ch) {
          if (ch.type === 'insert' && ch.name === 'gridCol') {
            var el = ch.position.nodeAfter;
            if (el && el.is('element', 'gridCol') && !el.childCount) { writer.appendElement('paragraph', el); changed = true; }
          }
        });
        return changed;
      });

      editor.commands.add('insertGridRow', new InsertGridRowCommand(editor));
      editor.commands.add('gridColSpan', new GridColSpanCommand(editor));
      editor.commands.add('gridAddCol', new GridAddColCommand(editor));
      editor.commands.add('gridRemoveCol', new GridRemoveColCommand(editor));
      editor.commands.add('gridRemoveRow', new GridRemoveRowCommand(editor));

      this._ui();
    }

    _ui() {
      var editor = this.editor, f = editor.ui.componentFactory;
      var ICON = {
        layout: '<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M2 3h4.6v14H2zM7.7 3h4.6v14H7.7zM13.4 3H18v14h-4.6z"/></svg>',
        span: '<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M2 4h16v2H2zM2 14h16v2H2zM5 8.5h10v3H5zM3 7l-2 3 2 3zM17 7l2 3-2 3z"/></svg>',
        add: '<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M2 3h6v14H2zM12 3h6v6h-6zM14.5 11h1v2h2v1h-2v2h-1v-2h-2v-1h2z"/></svg>',
        remove: '<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M2 3h6v14H2zM12 3h6v14h-6zM13.5 9.2h3v1.6h-3z" fill-rule="evenodd"/></svg>',
        row: '<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M2 3h16v14H2zM3.5 4.5v11h13v-11zM7 7l1-1 2 2 2-2 1 1-2 2 2 2-1 1-2-2-2 2-1-1 2-2z" fill-rule="evenodd"/></svg>'
      };

      function list(items) {
        var c = new CK.Collection();
        items.forEach(function (it) { c.add({ type: 'button', model: new CK.ViewModel(Object.assign({ withText: true }, it)) }); });
        return c;
      }

      /* insert: 1–12 columns */
      f.add('gridLayout', function (locale) {
        var dd = CK.createDropdown(locale), cmd = editor.commands.get('insertGridRow');
        dd.buttonView.set({ label: 'Колони (ред)', icon: ICON.layout, tooltip: true });
        dd.bind('isEnabled').to(cmd);
        var items = [];
        for (var n = 1; n <= MAX; n++) {
          items.push({ label: n + (n === 1 ? ' колона' : n < 5 ? ' колони' : ' колони'), commandParam: n });
        }
        CK.addListToDropdown(dd, list(items), { ariaLabel: 'Брой колони' });
        dd.on('execute', function (evt) { editor.execute('insertGridRow', evt.source.commandParam); editor.editing.view.focus(); });
        return dd;
      });

      /* width of the current column: 1–12 tracks */
      f.add('gridColSpan', function (locale) {
        var dd = CK.createDropdown(locale), cmd = editor.commands.get('gridColSpan');
        dd.buttonView.set({ label: 'Ширина на колоната', icon: ICON.span, tooltip: true });
        dd.bind('isEnabled').to(cmd);
        var items = [];
        for (var n = 1; n <= MAX; n++) { items.push({ label: 'Заема ' + n + (n === 1 ? ' част' : ' части'), commandParam: n }); }
        var coll = list(items);
        coll.forEach(function (item) { item.model.bind('isOn').to(cmd, 'value', function (v) { return v === item.model.commandParam; }); });
        CK.addListToDropdown(dd, coll, { ariaLabel: 'Ширина на колоната' });
        dd.on('execute', function (evt) { editor.execute('gridColSpan', evt.source.commandParam); editor.editing.view.focus(); });
        return dd;
      });

      var btn = function (name, label, icon, cmdName) {
        f.add(name, function (locale) {
          var v = new ButtonView(locale), cmd = editor.commands.get(cmdName);
          v.set({ label: label, icon: icon, tooltip: true });
          v.bind('isEnabled').to(cmd);
          v.on('execute', function () { editor.execute(cmdName); editor.editing.view.focus(); });
          return v;
        });
      };
      btn('gridAddCol', 'Добави колона', ICON.add, 'gridAddCol');
      btn('gridRemoveCol', 'Премахни колоната', ICON.remove, 'gridRemoveCol');
      btn('gridRemoveRow', 'Изтрий целия ред с колони', ICON.row, 'gridRemoveRow');
    }
  }

  window.McmsGridLayout = GridLayout;
})();
