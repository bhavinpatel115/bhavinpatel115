/* UK Utility Analytics: shared behaviour. No dependencies. */
(function () {
  'use strict';
  var d = document, w = window;
  var cfg = w.UUA || {};
  var reduce = w.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* header shadow + mobile sticky CTA */
  var header = d.querySelector('.site-header');
  var sticky = d.querySelector('.sticky-cta');
  var stopAt = d.getElementById('contact');
  function onScroll() {
    var y = w.scrollY;
    if (header) header.classList.toggle('scrolled', y > 8);
    if (sticky) {
      var hide = stopAt && stopAt.getBoundingClientRect().top < w.innerHeight;
      sticky.classList.toggle('show', y > 480 && !hide);
    }
  }
  w.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  /* services disclosure menu (desktop) */
  d.querySelectorAll('.has-menu').forEach(function (item) {
    var btn = item.querySelector('.nav-btn'), menu = item.querySelector('.menu');
    function set(open) { btn.setAttribute('aria-expanded', open); menu.classList.toggle('open', open); }
    btn.addEventListener('click', function () { set(btn.getAttribute('aria-expanded') !== 'true'); });
    item.addEventListener('keydown', function (e) { if (e.key === 'Escape') { set(false); btn.focus(); } });
    d.addEventListener('click', function (e) { if (!item.contains(e.target)) set(false); });
    item.addEventListener('focusout', function (e) { if (!item.contains(e.relatedTarget)) set(false); });
  });

  /* mobile navigation */
  var toggle = d.querySelector('.menu-toggle'), mnav = d.getElementById('mobile-nav');
  if (toggle && mnav) {
    var open = function (v) {
      toggle.setAttribute('aria-expanded', v);
      toggle.setAttribute('aria-label', v ? 'Close menu' : 'Open menu');
      mnav.classList.toggle('open', v);
      d.body.style.overflow = v ? 'hidden' : '';
    };
    toggle.addEventListener('click', function () { open(toggle.getAttribute('aria-expanded') !== 'true'); });
    mnav.querySelectorAll('a').forEach(function (a) { a.addEventListener('click', function () { open(false); }); });
    d.addEventListener('keydown', function (e) { if (e.key === 'Escape' && mnav.classList.contains('open')) { open(false); toggle.focus(); } });
    w.addEventListener('resize', function () { if (w.innerWidth >= 1040) open(false); });
  }

  /* reveal on scroll, highlighter, process steps, invoice annotations */
  var targets = d.querySelectorAll('.reveal, .hl, .process li, .anim');
  if ('IntersectionObserver' in w && !reduce) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (!e.isIntersecting) return;
        e.target.classList.add(e.target.classList.contains('anim') ? 'go' : 'in');
        io.unobserve(e.target);
      });
    }, { rootMargin: '0px 0px -10% 0px', threshold: 0.15 });
    targets.forEach(function (t) { io.observe(t); });
  } else {
    targets.forEach(function (t) { t.classList.add('in', 'go'); });
  }

  /* FAQ: keep one answer open per list */
  d.querySelectorAll('.faq').forEach(function (list) {
    var items = list.querySelectorAll('details');
    items.forEach(function (it) {
      it.addEventListener('toggle', function () {
        if (it.open) items.forEach(function (o) { if (o !== it) o.open = false; });
      });
    });
  });

  /* enquiry forms */
  d.querySelectorAll('form[data-enquiry]').forEach(function (form) {
    var fields = form.querySelectorAll('[required]');
    function validate(el) {
      var ok = el.checkValidity(), wrap = el.closest('.field');
      if (wrap) wrap.classList.toggle('invalid', !ok);
      el.setAttribute('aria-invalid', ok ? 'false' : 'true');
      return ok;
    }
    fields.forEach(function (el) {
      el.addEventListener('blur', function () { if (el.value) validate(el); });
      el.addEventListener('input', function () { if (el.closest('.field').classList.contains('invalid')) validate(el); });
      el.addEventListener('change', function () { if (el.tagName === 'SELECT') validate(el); });
    });
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      if (form.querySelector('.hp input').value) return; /* bot trap */
      var bad = Array.prototype.filter.call(fields, function (el) { return !validate(el); });
      if (bad.length) { bad[0].focus(); return; }
      var data = new FormData(form);
      data.delete('company_website');
      var btn = form.querySelector('button[type="submit"]');
      var status = form.querySelector('.form-status p');
      var endpoint = cfg.formEndpoint;
      if (endpoint) {
        btn.disabled = true;
        btn.textContent = 'Sending…';
        fetch(endpoint, { method: 'POST', body: data, headers: { Accept: 'application/json' } })
          .then(function (r) { if (!r.ok) throw new Error(r.status); form.classList.add('done'); form.querySelector('.form-status').focus(); })
          .catch(function () {
            btn.disabled = false;
            btn.textContent = 'Submit enquiry';
            alert('Sorry, your enquiry could not be sent. Please email ' + cfg.email + ' instead.');
          });
        return;
      }
      /* no form backend configured: hand over to the visitor's email app */
      var lines = [
        'Name: ' + data.get('name'),
        'Company: ' + data.get('company'),
        'Email: ' + data.get('email'),
        'Phone: ' + (data.get('phone') || 'Not given'),
        'Invoices per month: ' + data.get('volume'),
        'Service: ' + data.get('service'),
        '',
        data.get('message') || ''
      ];
      w.location.href = 'mailto:' + cfg.email + '?subject=' + encodeURIComponent('Quote request: ' + data.get('company')) + '&body=' + encodeURIComponent(lines.join('\n'));
      if (status) status.textContent = 'Your email app should now open with your enquiry ready to send. If it does not, email us at ' + cfg.email + '.';
      form.classList.add('done');
      form.querySelector('.form-status').focus();
    });
  });

  /* rate-check calculator (contract rate verification page) */
  var calc = d.getElementById('rate-check');
  if (calc) {
    var modes = {
      sc: { unit: 'p/day', qty: 'Days in billing period', qtyUnit: ' days', c: [50, 300, 0.1, 128.5], b: [50, 300, 0.1, 164.2], q: [1, 92, 1, 30], perYear: function (q) { return 365 / q; } },
      ur: { unit: 'p/kWh', qty: 'Consumption in billing period', qtyUnit: ' kWh', c: [5, 60, 0.01, 24.62], b: [5, 60, 0.01, 25.4], q: [500, 100000, 500, 48000], perYear: function () { return 12; } }
    };
    var iC = d.getElementById('rc-contract'), iB = d.getElementById('rc-billed'), iQ = d.getElementById('rc-qty');
    var mode = 'sc';
    var gbp = function (v) { return '£' + v.toLocaleString('en-GB', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };
    var setR = function (el, a) { el.min = a[0]; el.max = a[1]; el.step = a[2]; el.value = a[3]; };
    var run = function () {
      var m = modes[mode], c = +iC.value, b = +iB.value, q = +iQ.value, diff = (b - c) * q / 100;
      d.getElementById('rc-o-contract').textContent = c + m.unit;
      d.getElementById('rc-o-billed').textContent = b + m.unit;
      d.getElementById('rc-o-qty').textContent = q.toLocaleString('en-GB') + m.qtyUnit;
      var same = Math.abs(diff) < 0.005;
      d.getElementById('rc-diff').textContent = gbp(Math.abs(diff));
      d.getElementById('rc-dir').textContent = same ? 'Invoice matches the contract price' : diff > 0 ? 'Billed above the contract price' : 'Billed below the contract price';
      d.getElementById('rc-year').textContent = gbp(Math.abs(diff * m.perYear(q)));
      var v = d.getElementById('rc-verdict');
      v.textContent = same ? 'No exception' : 'Would be raised as an exception';
      v.classList.toggle('ok', same);
    };
    var setMode = function (k) {
      mode = k;
      calc.querySelectorAll('.seg button').forEach(function (b) { b.setAttribute('aria-pressed', b.dataset.mode === k); });
      setR(iC, modes[k].c); setR(iB, modes[k].b); setR(iQ, modes[k].q);
      d.getElementById('rc-l-qty').textContent = modes[k].qty;
      run();
    };
    calc.querySelectorAll('.seg button').forEach(function (b) { b.addEventListener('click', function () { setMode(b.dataset.mode); }); });
    [iC, iB, iQ].forEach(function (el) { el.addEventListener('input', run); });
    setMode('sc');
  }
})();
