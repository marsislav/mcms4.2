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

  /* ---- animations (configured in Settings > Design) ---- */
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

  /* ---- animated grain: 10 pre-rendered noise frames played at 25 fps (Settings > Design) ---- */
  (function () {
    var canvas = document.querySelector('[data-mgrain]');
    if (!canvas) { return; }
    if (window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches) { canvas.remove(); return; }
    var ctx = canvas.getContext('2d'), frames = [], frame = 0, timer = 0, resizeT = 0;
    function rgb(hex) {
      hex = String(hex || '#ffffff').replace('#', '');
      if (hex.length === 3) { hex = hex.split('').map(function (x) { return x + x; }).join(''); }
      var n = parseInt(hex, 16) || 0xffffff;
      return { r: (n >> 16) & 255, g: (n >> 8) & 255, b: n & 255 };
    }
    function setup() {
      clearTimeout(timer);
      var c = rgb(canvas.getAttribute('data-color'));
      var opacity = parseFloat(canvas.getAttribute('data-opacity')) || 0.09;
      var density = parseFloat(canvas.getAttribute('data-density')) || 0.6;
      var w = canvas.width = window.innerWidth, h = canvas.height = window.innerHeight;
      frames = [];
      for (var f = 0; f < 10; f++) {
        var img = ctx.createImageData(w, h), px = new Uint32Array(img.data.buffer);
        for (var i = 0; i < px.length; i++) {
          if (Math.random() < density) { px[i] = (Math.floor(Math.random() * opacity * 255) << 24) | (c.b << 16) | (c.g << 8) | c.r; }
        }
        frames.push(img);
      }
      (function loop() {
        frame = (frame + 1) % frames.length;
        ctx.putImageData(frames[frame], 0, 0);
        timer = setTimeout(function () { requestAnimationFrame(loop); }, 1000 / 25);
      })();
    }
    setup();
    window.addEventListener('resize', function () { clearTimeout(resizeT); resizeT = setTimeout(setup, 200); });
  })();

  /* ---- background videos: WebM / MP4 behind an element (Settings > Design > Element backgrounds) ---- */
  (function () {
    var cfg = null;
    try { cfg = JSON.parse(document.body.getAttribute('data-mbg') || 'null'); } catch (e) { cfg = null; }
    if (!cfg || !cfg.i || !cfg.i.length) { return; }
    // reduced motion, data saver, or a phone (unless allowed in settings) -> still poster only
    var still = (window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches) ||
                (navigator.connection && navigator.connection.saveData) ||
                (!cfg.m && window.matchMedia && matchMedia('(max-width: 767px)').matches);
    var io = (!still && 'IntersectionObserver' in window) ? new IntersectionObserver(function (es) {
      es.forEach(function (e) {
        var v = e.target.querySelector('video');
        if (!v) { return; }
        if (e.isIntersecting) { var p = v.play(); if (p && p.catch) { p.catch(function () {}); } } else { v.pause(); }
      });
    }) : null;
    cfg.i.forEach(function (it) {
      var els;
      try { els = document.querySelectorAll(it.s); } catch (e) { return; }
      Array.prototype.slice.call(els, 0, 6).forEach(function (el) {
        if (el.querySelector(':scope > .mbg-video')) { return; }
        var w = document.createElement('div');
        w.className = 'mbg-video'; w.setAttribute('aria-hidden', 'true');
        if (it.p) { w.style.backgroundImage = 'url("' + it.p + '")'; }
        if (!still) {
          var v = document.createElement('video');
          v.muted = true; v.defaultMuted = true; v.loop = true; v.autoplay = true; v.playsInline = true;
          v.setAttribute('muted', ''); v.setAttribute('playsinline', ''); v.setAttribute('disablepictureinpicture', '');
          v.preload = 'auto';
          if (it.p) { v.poster = it.p; }
          v.src = it.v;
          w.appendChild(v);
        }
        el.classList.add('has-mbg');
        if (getComputedStyle(el).position === 'static') { el.classList.add('has-mbg-rel'); }
        el.insertBefore(w, el.firstChild);
        if (io) { io.observe(w); }
      });
    });
  })();

  /* ---- smooth scroll (Lenis, Settings > Design) ---- */
  (function () {
    var me = document.currentScript || document.querySelector('script[data-smooth]');
    var dur = me && parseFloat(me.getAttribute('data-smooth'));
    if (!dur || typeof window.Lenis !== 'function') { return; }
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) { return; }
    var lenis = new window.Lenis({
      duration: dur,
      easing: function (t) { return Math.min(1, 1.001 - Math.pow(2, -10 * t)); },
      smoothWheel: true
    });
    window.mcmsLenis = lenis;
    var raf = function (time) { lenis.raf(time); requestAnimationFrame(raf); };
    requestAnimationFrame(raf);

    // in-page anchors (#id) glide too; keep room for a sticky header
    document.addEventListener('click', function (e) {
      var a = e.target.closest && e.target.closest('a[href*="#"]');
      if (!a || e.defaultPrevented || e.metaKey || e.ctrlKey || e.shiftKey || a.target === '_blank') { return; }
      var u; try { u = new URL(a.href, location.href); } catch (err) { return; }
      if (u.origin !== location.origin || u.pathname !== location.pathname || u.search !== location.search || u.hash.length < 2) { return; }
      var target; try { target = document.querySelector(decodeURIComponent(u.hash)); } catch (err) { return; }
      if (!target) { return; }
      e.preventDefault();
      var hdr = document.querySelector('.site-header');
      var pos = hdr ? getComputedStyle(hdr).position : '';
      var off = (pos === 'sticky' || pos === 'fixed') ? -hdr.offsetHeight - 8 : 0;
      lenis.scrollTo(target, { offset: off });
      history.pushState(null, '', u.hash);
    });
  })();

  /* ---- progressive blur: 8 layers, blur doubles per layer (Settings > Design) ---- */
  var pblur = document.querySelector('[data-pblur]');
  if (pblur && (CSS.supports('backdrop-filter', 'blur(1px)') || CSS.supports('-webkit-backdrop-filter', 'blur(1px)'))) {
    var LAYERS = 8, FIRST = 0.15625, STEP = 100 / LAYERS;
    for (var i = 0; i < LAYERS; i++) {
      var px = FIRST * Math.pow(2, i);
      var mask = 'linear-gradient(to top, rgba(0,0,0,0) ' + (i * STEP) + '%, #000 ' + ((i + 1) * STEP) + '%)';
      var layer = document.createElement('div');
      layer.style.backdropFilter = layer.style.webkitBackdropFilter = 'blur(' + px + 'px)';
      layer.style.maskImage = layer.style.webkitMaskImage = mask;
      pblur.appendChild(layer);
    }
    // fade out when scrolled to the very bottom of the page
    var pbTicking = false;
    var pbUpdate = function () {
      pbTicking = false;
      var el = document.documentElement;
      var atBottom = (window.innerHeight + window.pageYOffset) >= (Math.max(el.scrollHeight, document.body.scrollHeight) - 2);
      pblur.classList.toggle('is-hidden', atBottom);
    };
    var pbQueue = function () { if (!pbTicking) { pbTicking = true; requestAnimationFrame(pbUpdate); } };
    window.addEventListener('scroll', pbQueue, { passive: true });
    window.addEventListener('resize', pbQueue);
    window.addEventListener('load', pbQueue);
    pbUpdate();
  }

  /* ---- reading progress bar (look: Settings > Design) ---- */
  var bar = document.querySelector('[data-read-progress] span');
  if (bar) {
    var art = document.querySelector('article.entry') || document.querySelector('main') || document.body;
    var ticking = false;
    var update = function () {
      ticking = false;
      var r = art.getBoundingClientRect();
      var total = r.height - window.innerHeight;
      var done = total > 0 ? Math.min(1, Math.max(0, -r.top / total)) : (r.bottom <= window.innerHeight ? 1 : 0);
      bar.style.transform = 'scaleX(' + done.toFixed(4) + ')';
    };
    window.addEventListener('scroll', function () { if (!ticking) { ticking = true; requestAnimationFrame(update); } }, { passive: true });
    window.addEventListener('resize', update);
    update();
  }

  /* ---- GDPR cookie consent ---- */
  var scriptEl = document.currentScript;
  var NONCE = scriptEl ? (scriptEl.nonce || scriptEl.getAttribute('nonce') || '') : '';
  var banner = document.getElementById('cc-banner');
  var reopen = document.querySelector('.cc-reopen');
  var COOKIE = 'mcms_consent';
  var readConsent = function () {
    var m = document.cookie.match(/(?:^|;\s*)mcms_consent=([^;]*)/);
    if (!m) { return null; }
    var o = { analytics: false, marketing: false };
    decodeURIComponent(m[1]).split(',').forEach(function (k) { if (k in o) { o[k] = true; } });
    return o;
  };
  var injected = {};
  var injectScripts = function (cat) {
    if (injected[cat]) { return; }
    var tpl = document.querySelector('template[data-cc-scripts="' + cat + '"]');
    if (!tpl) { return; }
    injected[cat] = true;
    Array.prototype.forEach.call(tpl.content.childNodes, function (node) {
      if (node.nodeType !== 1) { return; }
      if (node.tagName === 'SCRIPT') {
        var sc = document.createElement('script');
        Array.prototype.forEach.call(node.attributes, function (a) { if (a.name !== 'nonce') { sc.setAttribute(a.name, a.value); } });
        if (NONCE) { sc.setAttribute('nonce', NONCE); }
        sc.text = node.text;
        document.body.appendChild(sc);
      } else {
        document.body.appendChild(document.importNode(node, true));
      }
    });
  };
  var applyConsent = function (c) { if (c) { ['analytics', 'marketing'].forEach(function (k) { if (c[k]) { injectScripts(k); } }); } };
  if (banner) {
    var days = parseInt(banner.getAttribute('data-cc-days'), 10) || 180;
    var prefs = banner.querySelector('.cc-prefs');
    var boxes = banner.querySelectorAll('[data-cc-cat]');
    var save = function (c) {
      var list = ['necessary'];
      ['analytics', 'marketing'].forEach(function (k) { if (c[k]) { list.push(k); } });
      document.cookie = COOKIE + '=' + encodeURIComponent(list.join(',')) + ';max-age=' + (days * 86400) + ';path=/;SameSite=Lax' + (location.protocol === 'https:' ? ';Secure' : '');
      banner.hidden = true;
      if (reopen) { reopen.hidden = false; }
      applyConsent(c);
    };
    var current = readConsent();
    if (current) {
      boxes.forEach(function (b) { b.checked = !!current[b.getAttribute('data-cc-cat')]; });
      if (reopen) { reopen.hidden = false; }
      applyConsent(current);
    } else {
      banner.hidden = false;
    }
    document.addEventListener('click', function (e) {
      var b = e.target.closest('[data-cc]');
      if (!b) { return; }
      var act = b.getAttribute('data-cc');
      if (act === 'accept') { save({ analytics: true, marketing: true }); }
      else if (act === 'reject') { save({ analytics: false, marketing: false }); }
      else if (act === 'save') {
        var c = { analytics: false, marketing: false };
        boxes.forEach(function (x) { c[x.getAttribute('data-cc-cat')] = x.checked; });
        save(c);
      } else if (act === 'prefs') { if (prefs) { prefs.hidden = !prefs.hidden; } }
      else if (act === 'open') { banner.hidden = false; if (prefs) { prefs.hidden = false; } }
    });
  }

  /* ---- live search: <input data-live="/search/live"> ---- */
  var li = document.querySelector('input[data-live]');
  if (li && window.fetch) {
    var box = document.createElement('div');
    box.className = 'live-results'; box.hidden = true; box.setAttribute('role', 'listbox');
    li.setAttribute('autocomplete', 'off');
    li.parentNode.style.position = 'relative';
    li.parentNode.appendChild(box);
    var min = parseInt(li.getAttribute('data-live-min'), 10) || 2;
    var timer = null, seq = 0;
    var esc = function (t) { var d = document.createElement('div'); d.textContent = t; return d.innerHTML; };
    li.addEventListener('input', function () {
      clearTimeout(timer);
      var q = li.value.trim();
      if (q.length < min) { box.hidden = true; return; }
      timer = setTimeout(function () {
        var n = ++seq;
        fetch(li.getAttribute('data-live') + '?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
          .then(function (r) { return r.ok ? r.json() : []; })
          .then(function (items) {
            if (n !== seq) { return; }
            box.innerHTML = items.map(function (i) { return '<a role="option" href="' + esc(i.url) + '">' + esc(i.title) + (i.type ? ' <small>' + esc(i.type) + '</small>' : '') + '</a>'; }).join('');
            box.hidden = !items.length;
          })
          .catch(function () { box.hidden = true; });
      }, 200);
    });
    document.addEventListener('click', function (e) { if (!box.contains(e.target) && e.target !== li) { box.hidden = true; } });
    li.addEventListener('keydown', function (e) { if (e.key === 'Escape') { box.hidden = true; } });
  }
})();
