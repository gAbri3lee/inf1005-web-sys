(() => {
  'use strict';

  const select = document.querySelector('.js-spa-treatment-select');
  const items = Array.from(document.querySelectorAll('.js-spa-treatment-item'));

  if (!select || items.length === 0) {
    return;
  }

  function syncActiveState(selectedValue) {
    items.forEach((item) => {
      const isActive = item.getAttribute('data-treatment-name') === selectedValue;
      item.classList.toggle('is-active', isActive);
      item.setAttribute('aria-pressed', isActive ? 'true' : 'false');
    });
  }

  items.forEach((item) => {
    item.addEventListener('click', () => {
      const treatmentName = item.getAttribute('data-treatment-name');
      if (!treatmentName) {
        return;
      }

      select.value = treatmentName;
      syncActiveState(treatmentName);
    });
  });

  select.addEventListener('change', () => {
    syncActiveState(select.value);
  });

  syncActiveState(select.value);
})();
