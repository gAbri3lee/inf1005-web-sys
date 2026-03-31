document.addEventListener('DOMContentLoaded', function () {
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const localLinks = document.querySelectorAll('a[href^="#"]');

  function focusSectionFromHash(hash) {
    if (!hash) {
      return;
    }

    const target = document.querySelector(hash);
    if (!target) {
      return;
    }

    const heading = target.querySelector('h2, h3, h4') || target;
    if (!heading.hasAttribute('tabindex')) {
      heading.setAttribute('tabindex', '-1');
    }

    heading.focus({ preventScroll: true });
  }

  localLinks.forEach(function (link) {
    link.addEventListener('click', function (event) {
      const href = link.getAttribute('href');

      if (!href || href === '#') {
        return;
      }

      const target = document.querySelector(href);
      if (!target) {
        return;
      }

      event.preventDefault();

      target.scrollIntoView({
        behavior: prefersReducedMotion ? 'auto' : 'smooth',
        block: 'start',
      });

      if (window.history && typeof window.history.replaceState === 'function') {
        window.history.replaceState(null, '', href);
      }

      window.requestAnimationFrame(function () {
        focusSectionFromHash(href);
      });
    });
  });

  if (window.location.hash) {
    focusSectionFromHash(window.location.hash);
  }
});
