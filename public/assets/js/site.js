/* mCMS public theme — tiny progressive enhancements */
(function () {
  'use strict';

  // Close the mobile menu after choosing a link
  var toggle = document.getElementById('menu-toggle');
  if (toggle) {
    document.addEventListener('click', function (e) {
      if (toggle.checked && e.target.closest('.primary a')) { toggle.checked = false; }
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') { toggle.checked = false; }
    });
  }

  // Wrap wide tables so they scroll horizontally instead of breaking the layout
  document.querySelectorAll('.prose table').forEach(function (t) {
    if (t.parentNode.classList.contains('table-scroll')) { return; }
    var w = document.createElement('div'); w.className = 'table-scroll';
    t.parentNode.insertBefore(w, t); w.appendChild(t);
  });
})();
