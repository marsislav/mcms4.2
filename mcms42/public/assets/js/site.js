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

  /* ---- animations (configured in Settings > Дизайн) ---- */
  var TYPES = ['fade', 'slide-up', 'slide-down', 'slide-left', 'slide-right', 'zoom'];
  var SELECTORS = {
    header: '.site-header',
    logo: '.logo',
    menu: '.primary > ul > li',
    cards: '.card',
    images: '.prose img, .prose figure.image',
    headings: '.prose h2, .prose h3',
    footer: '.site-footer .foot > *'
  };
  var root = document.documentElement;
  var KEY = 'mcms-anim-off';
  var stored = null;
  try { stored = localStorage.getItem(KEY); } catch (e) { /* storage blocked */ }
  if (stored === '1') { root.classList.add('anim-off'); }

  var toggleBtn = document.querySelector('[data-anim-toggle]');
  if (toggleBtn) {
    var sync = function () {
      var off = root.classList.contains('anim-off');
      toggleBtn.setAttribute('aria-pressed', off ? 'true' : 'false');
      toggleBtn.textContent = toggleBtn.getAttribute(off ? 'data-off' : 'data-on');
    };
    toggleBtn.addEventListener('click', function () {
      root.classList.toggle('anim-off');
      try { localStorage.setItem(KEY, root.classList.contains('anim-off') ? '1' : '0'); } catch (e) { /* ignore */ }
      sync();
    });
    sync();
  }

  var cfg = null;
  try { cfg = JSON.parse(document.body.getAttribute('data-anim') || 'null'); } catch (e) { cfg = null; }
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (cfg && !reduce && !root.classList.contains('anim-off') && 'IntersectionObserver' in window) {
    var ms = parseInt(getComputedStyle(root).getPropertyValue('--anim-ms'), 10) || 600;
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (!en.isIntersecting) { return; }
        var el = en.target;
        io.unobserve(el);
        var d = parseInt(el.getAttribute('data-rv-d') || '0', 10);
        setTimeout(function () {
          el.classList.add('rv-in');
          // once shown, drop the helper attributes so the element's normal CSS (hover etc.) applies again
          setTimeout(function () { el.removeAttribute('data-rv'); el.removeAttribute('data-rv-d'); el.classList.remove('rv-in'); }, ms + 80);
        }, d);
      });
    }, { threshold: 0.12 });

    Object.keys(cfg).forEach(function (key) {
      var type = cfg[key];
      if (!SELECTORS[key] || TYPES.indexOf(type) === -1) { return; }
      var seen = new Map();
      document.querySelectorAll(SELECTORS[key]).forEach(function (el) {
        var n = seen.get(el.parentNode) || 0; // small stagger between siblings
        seen.set(el.parentNode, n + 1);
        el.setAttribute('data-rv', type);
        el.setAttribute('data-rv-d', String(Math.min(n, 6) * 70));
        io.observe(el);
      });
    });
  }
})();
