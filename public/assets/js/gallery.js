/* mCMS public gallery + lightbox. No dependencies, CSP friendly (no inline code). Settings come from
 * Settings > Media > Lightbox as JSON in data-lb on this script tag, e.g. {"fx":"fade","loop":1,"count":1,"caps":1,"thumbs":0,"bg":"dark","op":92,"backdrop":1,"zoom":1,"auto":0,"dl":0,"all":0}.
 *  - galleries:  .mc-gallery.mc-g-lb  -> every figure link opens in the lightbox (arrows, swipe, captions from <figcaption>)
 *  - one image:  <img data-lightbox="1"> opens too ("0" never does); with "all" every image inside .prose opens unless it is "0" or already a link.
 *  - sliders (.mc-g-slider) get previous / next buttons. */
(function () {
  'use strict';
  var me = document.currentScript;
  var cfg = {};
  try { cfg = JSON.parse((me && me.getAttribute('data-lb')) || '{}') || {}; } catch (e) { cfg = {}; }
  var T = Object.assign({ close: 'Close', prev: 'Previous', next: 'Next', zoom: 'Zoom', play: 'Slideshow', dl: 'Download', of: ' / ' }, (function () {
    try { return JSON.parse((me && me.getAttribute('data-lb-t')) || '{}') || {}; } catch (e) { return {}; }
  })());

  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };

  /* ---------------- slider buttons ---------------- */
  $$('.mc-g-slider').forEach(function (g) {
    var wrap = document.createElement('div');
    wrap.className = 'mc-g-sw';
    g.parentNode.insertBefore(wrap, g);
    wrap.appendChild(g);
    var step = function (dir) { g.scrollBy({ left: dir * Math.max(120, g.clientWidth * 0.9), behavior: 'smooth' }); };
    [['mc-g-prev', '‹', T.prev, -1], ['mc-g-next', '›', T.next, 1]].forEach(function (b) {
      var btn = document.createElement('button');
      btn.type = 'button'; btn.className = 'mc-g-nav ' + b[0]; btn.textContent = b[1]; btn.setAttribute('aria-label', b[2]);
      btn.addEventListener('click', function () { step(b[3]); });
      wrap.appendChild(btn);
    });
  });

  if (cfg.off) { return; }

  /* ---------------- lightbox ---------------- */
  var lb = null, items = [], index = 0, timer = null, lastFocus = null;

  function itemsFor(link) {
    var g = link.closest('.mc-gallery.mc-g-lb');
    var out = [], at = 0;
    if (g) {
      $$('.mc-g-item', g).forEach(function (fig) {
        var a = $('a', fig), im = $('img', fig), cap = $('figcaption', fig);
        if (!a || !im) { return; }
        if (a === link) { at = out.length; }
        out.push({ src: a.getAttribute('href') || im.currentSrc || im.src, thumb: im.currentSrc || im.src, alt: im.alt || '', cap: cap ? cap.textContent.trim() : '' });
      });
      return { list: out, at: at };
    }
    return null;
  }

  function singles(img) {
    var all = $$('img[data-lightbox="1"]').filter(function (i) { return !i.closest('.mc-gallery'); });
    if (cfg.all) {
      $$('.prose img').forEach(function (i) {
        if (all.indexOf(i) === -1 && i.getAttribute('data-lightbox') !== '0' && !i.closest('.mc-gallery') && (!i.closest('a') || isImageUrl(i.closest('a').getAttribute('href')))) { all.push(i); }
      });
    }
    var list = [], at = 0;
    all.forEach(function (i) {
      var a = i.closest('a'), href = a && isImageUrl(a.getAttribute('href')) ? a.getAttribute('href') : (i.currentSrc || i.src);
      var fig = i.closest('figure'), cap = fig ? $('figcaption', fig) : null;
      if (i === img) { at = list.length; }
      list.push({ src: href, thumb: i.currentSrc || i.src, alt: i.alt || '', cap: cap ? cap.textContent.trim() : (i.alt || '') });
    });
    return { list: list, at: at };
  }
  function isImageUrl(u) { return /\.(jpe?g|png|gif|webp|avif)(\?|#|$)/i.test(u || ''); }

  function el(tag, cls, attrs) {
    var e = document.createElement(tag);
    if (cls) { e.className = cls; }
    Object.keys(attrs || {}).forEach(function (k) { if (k === 'text') { e.textContent = attrs[k]; } else { e.setAttribute(k, attrs[k]); } });
    return e;
  }

  function build() {
    var root = el('div', 'mc-lb', { role: 'dialog', 'aria-modal': 'true', 'aria-label': T.zoom, 'data-fx': cfg.fx || 'fade' });
    var op = Math.max(30, Math.min(100, parseInt(cfg.op, 10) || 92)) / 100;
    if (cfg.bg === 'light') { root.classList.add('light'); root.style.setProperty('--lb-bg', 'rgba(255,255,255,' + op + ')'); }
    else if (cfg.bg === 'black') { root.style.setProperty('--lb-bg', 'rgba(0,0,0,' + Math.max(op, 0.97) + ')'); }
    else { root.style.setProperty('--lb-bg', 'rgba(8,10,14,' + op + ')'); }
    var top = el('div', 'mc-lb-top');
    top.appendChild(el('span', 'mc-lb-count', { 'aria-live': 'polite' }));
    if (+cfg.auto > 0) { top.appendChild(el('button', 'mc-lb-btn', { type: 'button', 'data-lb-play': '', 'aria-pressed': 'false', 'aria-label': T.play, title: T.play, text: '▶' })); }
    if (+cfg.zoom) { top.appendChild(el('button', 'mc-lb-btn', { type: 'button', 'data-lb-zoom': '', 'aria-label': T.zoom, title: T.zoom, text: '⤢' })); }
    if (+cfg.dl) { top.appendChild(el('a', 'mc-lb-btn', { 'data-lb-dl': '', 'aria-label': T.dl, title: T.dl, text: '⬇', download: '' })); }
    top.appendChild(el('button', 'mc-lb-btn', { type: 'button', 'data-lb-close': '', 'aria-label': T.close, title: T.close, text: '✕' }));
    var stage = el('div', 'mc-lb-stage');
    stage.appendChild(el('button', 'mc-lb-btn mc-lb-arrow mc-lb-prev', { type: 'button', 'aria-label': T.prev, text: '‹' }));
    stage.appendChild(el('img', 'mc-lb-img', { alt: '' }));
    stage.appendChild(el('button', 'mc-lb-btn mc-lb-arrow mc-lb-next', { type: 'button', 'aria-label': T.next, text: '›' }));
    root.appendChild(top); root.appendChild(stage);
    if (+cfg.caps) { root.appendChild(el('div', 'mc-lb-cap', { 'aria-live': 'polite' })); }
    if (+cfg.thumbs) { root.appendChild(el('div', 'mc-lb-thumbs')); }

    root.addEventListener('click', function (e) {
      var t = e.target;
      if (t.closest('[data-lb-close]')) { close(); }
      else if (t.closest('.mc-lb-prev')) { go(-1); }
      else if (t.closest('.mc-lb-next')) { go(1); }
      else if (t.closest('[data-lb-play]')) { toggleAuto(); }
      else if (t.closest('[data-lb-zoom]') || t.closest('.mc-lb-img')) { if (+cfg.zoom) { root.classList.toggle('zoomed'); } }
      else if (t.closest('.mc-lb-thumbs button')) { show(+t.closest('button').getAttribute('data-i')); }
      else if (+cfg.backdrop !== 0 && (t === root || t.classList.contains('mc-lb-stage'))) { close(); }
    });
    /* swipe */
    var x0 = null;
    stage.addEventListener('touchstart', function (e) { x0 = e.touches.length === 1 ? e.touches[0].clientX : null; }, { passive: true });
    stage.addEventListener('touchend', function (e) {
      if (x0 === null || root.classList.contains('zoomed')) { return; }
      var dx = e.changedTouches[0].clientX - x0; x0 = null;
      if (Math.abs(dx) > 50) { go(dx < 0 ? 1 : -1); }
    }, { passive: true });
    return root;
  }

  function show(i, dir) {
    var n = items.length;
    if (!n) { return; }
    if (i < 0) { i = cfg.loop === 0 || cfg.loop === '0' ? 0 : n - 1; }
    if (i >= n) { i = cfg.loop === 0 || cfg.loop === '0' ? n - 1 : 0; }
    index = i;
    var it = items[i], img = $('.mc-lb-img', lb);
    lb.classList.remove('zoomed');
    img.classList.remove('dir-next', 'dir-prev');
    if (dir) { img.classList.add(dir > 0 ? 'dir-next' : 'dir-prev'); }
    img.classList.add('fx-out');
    var swap = function () {
      img.onload = function () { img.classList.remove('fx-out'); };
      img.onerror = function () { img.classList.remove('fx-out'); };
      img.src = it.src;
      img.alt = it.alt;
      if (img.complete) { img.classList.remove('fx-out'); }
    };
    if ((cfg.fx || 'fade') === 'none') { swap(); } else { setTimeout(swap, 120); }
    var count = $('.mc-lb-count', lb);
    if (+cfg.count !== 0) { count.textContent = (i + 1) + T.of + n; } else { count.textContent = ''; }
    var cap = $('.mc-lb-cap', lb); if (cap) { cap.textContent = it.cap; }
    var dl = $('[data-lb-dl]', lb); if (dl) { dl.setAttribute('href', it.src); }
    $$('.mc-lb-thumbs button', lb).forEach(function (b) {
      var cur = +b.getAttribute('data-i') === i;
      if (cur) { b.setAttribute('aria-current', 'true'); b.scrollIntoView({ block: 'nearest', inline: 'center' }); } else { b.removeAttribute('aria-current'); }
    });
    [items[(i + 1) % n], items[(i - 1 + n) % n]].forEach(function (nx) { if (nx) { var p = new Image(); p.src = nx.src; } });
    var atEdge = cfg.loop === 0 || cfg.loop === '0';
    $('.mc-lb-prev', lb).hidden = atEdge && i === 0;
    $('.mc-lb-next', lb).hidden = atEdge && i === n - 1;
  }
  function go(d) { show(index + d, d); }

  function toggleAuto(force) {
    var btn = $('[data-lb-play]', lb), on = typeof force === 'boolean' ? force : !timer;
    clearInterval(timer); timer = null;
    if (on) { timer = setInterval(function () { go(1); }, Math.max(1, +cfg.auto) * 1000); }
    if (btn) { btn.setAttribute('aria-pressed', on ? 'true' : 'false'); btn.textContent = on ? '❚❚' : '▶'; }
  }

  function onKey(e) {
    if (!lb) { return; }
    if (e.key === 'Escape') { close(); }
    else if (e.key === 'ArrowRight') { go(1); }
    else if (e.key === 'ArrowLeft') { go(-1); }
    else if (e.key === ' ' && +cfg.auto > 0 && e.target.tagName !== 'BUTTON') { e.preventDefault(); toggleAuto(); }
    else if (e.key === 'Tab') {
      var f = $$('button:not([hidden]),a[href]', lb);
      if (!f.length) { return; }
      var first = f[0], last = f[f.length - 1];
      if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
      else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    }
  }

  function open(list, at, from) {
    if (!list.length) { return; }
    close(true);
    items = list; lastFocus = from || document.activeElement;
    lb = build();
    if (items.length < 2) { lb.classList.add('mc-lb-single'); }
    var th = $('.mc-lb-thumbs', lb);
    if (th && items.length > 1) {
      items.forEach(function (it, i) {
        var b = el('button', '', { type: 'button', 'data-i': String(i), 'aria-label': String(i + 1) });
        var im = el('img', '', { src: it.thumb, alt: '', loading: 'lazy' });
        b.appendChild(im); th.appendChild(b);
      });
    }
    document.body.appendChild(lb);
    document.documentElement.style.overflow = 'hidden';
    document.addEventListener('keydown', onKey);
    requestAnimationFrame(function () { lb.classList.add('on'); });
    show(at);
    var cb = $('[data-lb-close]', lb); if (cb) { cb.focus(); }
  }

  function close(quick) {
    if (!lb) { return; }
    toggleAuto(false);
    var gone = lb; lb = null;
    document.removeEventListener('keydown', onKey);
    document.documentElement.style.overflow = '';
    gone.classList.remove('on');
    if (quick === true) { gone.remove(); } else { setTimeout(function () { gone.remove(); }, 250); }
    if (lastFocus && lastFocus.focus) { try { lastFocus.focus({ preventScroll: true }); } catch (e) { /* ignore */ } }
  }

  document.addEventListener('click', function (e) {
    if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) { return; }
    var t = e.target;
    if (!t.closest || t.closest('.mc-lb')) { return; }
    var link = t.closest('.mc-gallery.mc-g-lb .mc-g-item a');
    if (link) {
      var g = itemsFor(link);
      if (g) { e.preventDefault(); open(g.list, g.at, link); }
      return;
    }
    var img = t.tagName === 'IMG' ? t : null;
    if (!img || img.closest('.mc-gallery')) { return; }
    var mark = img.getAttribute('data-lightbox');
    var a = img.closest('a');
    var eligible = mark === '1' || (+cfg.all && mark !== '0' && img.closest('.prose') && (!a || isImageUrl(a.getAttribute('href'))));
    if (!eligible || (a && !isImageUrl(a.getAttribute('href')))) { return; }
    var s = singles(img);
    if (s.list.length) { e.preventDefault(); open(s.list, s.at, img); }
  });
})();
