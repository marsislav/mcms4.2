/* mCMS admin i18n: T('Bulgarian text') returns the text in the current panel language.
   The dictionary is printed by admin/partials/i18n.blade.php as <script type="application/json" id="mcms-i18n">. */
(function () {
  'use strict';
  var dict = {};
  try {
    var el = document.getElementById('mcms-i18n');
    if (el) { dict = JSON.parse(el.textContent) || {}; }
  } catch (e) { dict = {}; }
  window.T = function (text, repl) {
    var out = Object.prototype.hasOwnProperty.call(dict, text) ? dict[text] : text;
    if (repl) { Object.keys(repl).forEach(function (k) { out = out.split(':' + k).join(repl[k]); }); }
    return out;
  };
  window.T.lang = document.documentElement.getAttribute('lang') === 'bg' ? 'bg' : 'en';
})();
