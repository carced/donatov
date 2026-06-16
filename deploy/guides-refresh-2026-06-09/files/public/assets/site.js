(function () {
  const toggle = document.getElementById('nav-toggle');
  const nav = document.getElementById('site-nav');
  if (toggle && nav) {
    toggle.addEventListener('click', () => {
      const open = nav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      document.body.classList.toggle('nav-open', open);
    });
    nav.querySelectorAll('a').forEach((link) => {
      link.addEventListener('click', () => {
        nav.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('nav-open');
      });
    });
  }

  document.querySelectorAll('[data-referral-slide]').forEach((el) => {
    el.addEventListener('click', (e) => {
      const href = el.getAttribute('href');
      if (!href || href.indexOf('/referral') === -1) {
        return;
      }
      if (el.tagName === 'A' && !e.metaKey && !e.ctrlKey && !e.shiftKey) {
        e.preventDefault();
        window.location.href = '/referral?from=free#referral-panel';
      }
    });
  });

  if (window.location.pathname === '/referral') {
    const panel = document.getElementById('referral-panel');
    if (panel) {
      requestAnimationFrame(() => {
        panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        if (new URLSearchParams(window.location.search).get('from') === 'free') {
          panel.classList.add('referral-panel--highlight');
          window.setTimeout(() => panel.classList.remove('referral-panel--highlight'), 2400);
        }
      });
    }
  }

  function initGameSearch() {
    const input = document.getElementById('game-search');
    const grid = document.querySelector('[data-catalog-grid]');
    if (!input || !grid) {
      return;
    }
    const cards = grid.querySelectorAll('.good-card-wrap');
    const empty = document.getElementById('game-search-empty');

    input.addEventListener('input', function () {
      const q = input.value.trim().toLowerCase();
      let visible = 0;
      cards.forEach(function (card) {
        const hay = (card.getAttribute('data-game-search') || '').toLowerCase();
        const show = q === '' || hay.indexOf(q) !== -1;
        card.classList.toggle('is-search-hidden', !show);
        if (show) {
          visible += 1;
        }
      });
      if (empty) {
        empty.hidden = q === '' || visible > 0;
      }
    });
  }

  function initGuideSearch() {
    const input = document.getElementById('guide-search');
    const grid = document.querySelector('[data-guides-grid]');
    if (!input || !grid) {
      return;
    }
    const cards = Array.from(grid.querySelectorAll('.guide-card'));
    const empty = document.getElementById('guide-search-empty');

    input.addEventListener('input', function () {
      const q = input.value.trim().toLowerCase();
      const visible = [];

      cards.forEach(function (card) {
        const hay = (card.getAttribute('data-guide-search') || '').toLowerCase();
        const show = q === '' || hay.indexOf(q) !== -1;
        card.classList.toggle('is-search-hidden', !show);
        if (show) {
          visible.push(card);
        }
      });

      if (q !== '') {
        visible.sort(function (a, b) {
          const ta = (a.getAttribute('data-guide-title') || '').toLowerCase();
          const tb = (b.getAttribute('data-guide-title') || '').toLowerCase();
          return ta.localeCompare(tb, undefined, { sensitivity: 'base' });
        });
        visible.forEach(function (card) {
          grid.appendChild(card);
        });
      }

      if (empty) {
        empty.hidden = q === '' || visible.length > 0;
      }
    });
  }

  initGameSearch();
  initGuideSearch();
})();