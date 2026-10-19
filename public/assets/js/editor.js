/* mCMS — CKEditor 5 (GPL build, free plugins only) for <textarea data-editor>.
 * Loaded after assets/vendor/ckeditor5/ckeditor5.umd.js. No inline code (CSP friendly).
 * If CKEditor fails to load, the textareas simply stay plain HTML fields. */
(function () {
  'use strict';

  var CK = window.CKEDITOR;
  var areas = Array.prototype.slice.call(document.querySelectorAll('textarea[data-editor]'));
  if (!CK || !areas.length) { return; }

  var P = CK.Plugin, ButtonView = CK.ButtonView;

  /* ---- own plugin: insert from the mCMS media library ---- */
  class MediaLibrary extends P {
    static get pluginName() { return 'MediaLibrary'; }
    init() {
      var editor = this.editor;
      editor.ui.componentFactory.add('mediaLibrary', function (locale) {
        var view = new ButtonView(locale);
        view.set({ label: T('Медия'), tooltip: true, withText: true });
        if (CK.icons && CK.icons.IconImage) { view.set({ icon: CK.icons.IconImage, withText: false, label: T('Медия (библиотека)') }); }
        view.on('execute', function () {
          if (typeof window.mcmsOpenPicker !== 'function') { return; }
          window.mcmsOpenPicker(function (m) {
            editor.focus();
            if (m.image) {
              editor.execute('insertImage', { source: [{ src: m.url, alt: m.alt || '' }] });
            } else {
              editor.model.change(function (w) {
                editor.model.insertContent(w.createText(m.name || m.url, { linkHref: m.url }));
              });
            }
          }, false);
        });
        return view;
      });
    }
  }

  /* ---- plugin list: everything free in the ckeditor5 package that makes sense here ----
   * Not included on purpose: Emoji (loads its data from a CDN), premium/cloud (CKBox, EasyImage, AI, comments…), upload adapters
   * (replaced by SimpleUploadAdapter -> our own /media/editor-upload endpoint), HtmlEmbed / GeneralHtmlSupport (raw HTML is sanitised server-side anyway),
   * TodoList / Bookmark (need <input> / id which the sanitiser strips), Markdown. */
  var names = [
    'Essentials', 'Paragraph', 'Heading', 'Autoformat', 'TextTransformation', 'PasteFromOffice', 'SelectAll', 'Undo',
    'Bold', 'Italic', 'Underline', 'Strikethrough', 'Subscript', 'Superscript', 'Code', 'RemoveFormat',
    'Font', 'FontFamily', 'FontSize', 'FontColor', 'FontBackgroundColor', 'Highlight',
    'Link', 'AutoLink', 'LinkImage',
    'List', 'ListProperties', 'BlockQuote', 'CodeBlock', 'HorizontalLine', 'PageBreak',
    'Alignment', 'Indent', 'IndentBlock',
    'Table', 'TableToolbar', 'TableProperties', 'TableCellProperties', 'TableColumnResize', 'TableCaption',
    'Image', 'ImageBlock', 'ImageInline', 'ImageCaption', 'ImageStyle', 'ImageToolbar', 'ImageResize',
    'ImageTextAlternative', 'ImageInsert', 'ImageInsertViaUrl', 'AutoImage',
    'ImageUpload', 'ImageUploadUI', 'ImageUploadEditing', 'ImageUploadProgress', 'SimpleUploadAdapter',
    'MediaEmbed', 'AutoMediaEmbed',
    'SpecialCharacters', 'SpecialCharactersEssentials', 'SpecialCharactersArrows', 'SpecialCharactersCurrency',
    'SpecialCharactersLatin', 'SpecialCharactersMathematical', 'SpecialCharactersText',
    'FindAndReplace', 'ShowBlocks', 'SourceEditing', 'WordCount', 'AccessibilityHelp', 'Fullscreen'
  ];
  var plugins = [];
  var missing = [];
  names.forEach(function (n) { if (CK[n]) { plugins.push(CK[n]); } else { missing.push(n); } });
  plugins.push(MediaLibrary);
  if (window.McmsGridLayout) { plugins.push(window.McmsGridLayout); }
  if (window.McmsFontAwesome) { plugins.push(window.McmsFontAwesome); }
  if (missing.length && window.console) { console.warn(T('CKEditor: липсват плъгини:'), missing.join(', ')); }

  var meta = function (n) { var m = document.querySelector('meta[name="' + n + '"]'); return m ? m.content : ''; };
  var cls = function (name, className, title) { return { name: name, className: className, title: title }; };

  var config = {
    licenseKey: 'GPL',
    language: T.lang,
    plugins: plugins,
    /* Image upload (drag & drop, paste, toolbar button): stored in the media library by the server (re-encoded, random name) */
    simpleUpload: {
      uploadUrl: meta('mcms-admin') + '/media/editor-upload',
      headers: { 'X-CSRF-TOKEN': meta('csrf-token'), 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    },
    toolbar: {
      shouldNotGroupWhenFull: false,
      items: [
        'undo', 'redo', '|', 'heading', '|',
        'bold', 'italic', 'underline', 'strikethrough', 'subscript', 'superscript', 'code', 'removeFormat', '|',
        'fontFamily', 'fontSize', 'fontColor', 'fontBackgroundColor', 'highlight', '|',
        'link', 'mediaLibrary', 'uploadImage', 'insertImage', 'mediaEmbed', 'insertTable', 'gridLayout', 'gridColSpan', 'gridAddCol', 'gridRemoveCol', 'gridRemoveRow', '|',
        'bulletedList', 'numberedList', 'outdent', 'indent', 'alignment', '|',
        'blockQuote', 'codeBlock', 'horizontalLine', 'pageBreak', 'specialCharacters', 'faIcons', '|',
        'findAndReplace', 'selectAll', 'showBlocks', 'sourceEditing', 'fullscreen', 'accessibilityHelp'
      ]
    },
    heading: {
      options: [
        { model: 'paragraph', title: T('Параграф'), class: 'ck-heading_paragraph' },
        { model: 'heading2', view: 'h2', title: T('Заглавие 2'), class: 'ck-heading_heading2' },
        { model: 'heading3', view: 'h3', title: T('Заглавие 3'), class: 'ck-heading_heading3' },
        { model: 'heading4', view: 'h4', title: T('Заглавие 4'), class: 'ck-heading_heading4' }
      ]
    },
    alignment: {
      options: [
        cls('left', 'text-left'), cls('center', 'text-center'), cls('right', 'text-right'), cls('justify', 'text-justify')
      ]
    },
    fontSize: { options: [12, 14, 'default', 18, 22, 28, 36] },
    fontFamily: {
      options: [
        'default', 'Arial, Helvetica, sans-serif', 'Georgia, serif', 'Courier New, Courier, monospace',
        'Tahoma, Geneva, sans-serif', 'Times New Roman, Times, serif', 'Verdana, Geneva, sans-serif'
      ]
    },
    link: {
      addTargetToExternalLinks: true,
      defaultProtocol: 'https://',
      decorators: { openInNewTab: { mode: 'manual', label: T('Отвори в нов прозорец'), attributes: { target: '_blank' } } }
    },
    list: { properties: { styles: true, startIndex: true, reversed: false } },
    image: {
      toolbar: [
        'imageTextAlternative', 'toggleImageCaption', '|',
        'imageStyle:inline', 'imageStyle:block', 'imageStyle:alignLeft', 'imageStyle:alignRight', 'imageStyle:side', '|',
        'resizeImage', 'linkImage'
      ],
      upload: { types: ['jpeg', 'png', 'gif', 'webp'] },
      insert: { integrations: ['upload', 'url'], type: 'auto' }
    },
    table: {
      contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells', 'tableProperties', 'tableCellProperties', 'toggleTableCaption']
    },
    codeBlock: {
      languages: [
        { language: 'plaintext', label: T('Обикновен текст') }, { language: 'html', label: 'HTML' },
        { language: 'css', label: 'CSS' }, { language: 'javascript', label: 'JavaScript' },
        { language: 'php', label: 'PHP' }, { language: 'sql', label: 'SQL' }, { language: 'bash', label: 'Bash' },
        { language: 'json', label: 'JSON' }
      ]
    },
    /* Stored as <oembed url="…"> (validated by the server Sanitizer) and turned into an iframe on render. */
    mediaEmbed: {
      previewsInData: false,
      providers: [
        {
          name: 'youtube',
          url: [
            /^(?:m\.)?youtube\.com\/watch\?v=([\w-]{6,20})/, /^youtube\.com\/shorts\/([\w-]{6,20})/,
            /^youtube\.com\/embed\/([\w-]{6,20})/, /^youtu\.be\/([\w-]{6,20})/
          ],
          html: function (m) {
            return '<div style="position:relative;padding-bottom:56.25%;height:0">' +
              '<iframe src="https://www.youtube-nocookie.com/embed/' + m[1] + '" style="position:absolute;top:0;left:0;width:100%;height:100%" frameborder="0" allowfullscreen></iframe></div>';
          }
        },
        {
          name: 'vimeo',
          url: [/^vimeo\.com\/(\d{4,12})/],
          html: function (m) {
            return '<div style="position:relative;padding-bottom:56.25%;height:0">' +
              '<iframe src="https://player.vimeo.com/video/' + m[1] + '" style="position:absolute;top:0;left:0;width:100%;height:100%" frameborder="0" allowfullscreen></iframe></div>';
          }
        }
      ]
    },
    typing: { transformations: { include: ['symbols', 'mathematical', 'typography'] } },
    placeholder: T('Пишете тук…')
  };

  var editors = [];

  function counter(editor, host) {
    var box = document.createElement('div');
    box.className = 'editor-count';
    host.parentNode.insertBefore(box, host.nextSibling);
    var wc = editor.plugins.has('WordCount') ? editor.plugins.get('WordCount') : null;
    if (!wc) { return; }
    var upd = function () { box.textContent = T('Думи: ') + wc.words + T(' · Знаци: ') + wc.characters; };
    wc.on('update', upd);
    upd();
  }

  areas.forEach(function (ta) {
    CK.ClassicEditor.create(ta, config).then(function (editor) {
      editors.push(editor);
      editor.model.document.on('change:data', function () {
        // let the "unsaved changes" guard in admin.js know
        ta.dispatchEvent(new Event('input', { bubbles: true }));
      });
      counter(editor, editor.ui.view.element);
      ta.setAttribute('data-editor-ready', '1');
    }).catch(function (err) {
      if (window.console) { console.error('CKEditor:', err); }
    });
  });

  /* Before submit make sure HTML-source mode is flushed into the editor, then into the textarea. */
  document.addEventListener('submit', function () {
    editors.forEach(function (ed) {
      try {
        if (ed.plugins.has('SourceEditing')) {
          var se = ed.plugins.get('SourceEditing');
          if (se.isSourceEditingMode) { se.updateEditorData(); }
        }
        ed.updateSourceElement();
      } catch (e) { /* ignore */ }
    });
  }, true);

  window.mcmsEditors = editors;
})();
