document.addEventListener('DOMContentLoaded', function () {
  const searchInput = document.getElementById('faqSearch');
  const buttons = document.querySelectorAll('.faq-category-btn');
  const items = document.querySelectorAll('.faq-item');
  const resultCount = document.getElementById('faqResultCount');
  const currentCategory = document.getElementById('faqCurrentCategory');
  const emptyState = document.getElementById('faqEmptyState');

  let activeCategory = 'booking';

  function updateFAQ() {
    const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
    let visibleCount = 0;

    items.forEach(function (item) {
      const itemCategory = item.getAttribute('data-category');
      const itemText = item.textContent.toLowerCase();

      const matchesCategory = activeCategory === itemCategory;
      const matchesSearch = searchTerm === '' || itemText.includes(searchTerm);

      if (matchesCategory && matchesSearch) {
        item.style.display = 'block';
        visibleCount++;
      } else {
        item.style.display = 'none';
      }
    });

    const activeButton = document.querySelector('.faq-category-btn.active');
    if (activeButton && currentCategory) {
      currentCategory.textContent = activeButton.textContent.trim();
    }

    if (resultCount) {
      resultCount.textContent = visibleCount === 1 ? '1 question' : visibleCount + ' questions';
    }

    if (emptyState) {
      emptyState.style.display = visibleCount === 0 ? 'block' : 'none';
    }
  }

  buttons.forEach(function (button) {
    button.addEventListener('click', function () {
      buttons.forEach(function (btn) {
        btn.classList.remove('active');
      });

      this.classList.add('active');
      activeCategory = this.getAttribute('data-category');
      updateFAQ();
    });
  });

  if (searchInput) {
    searchInput.addEventListener('input', updateFAQ);
  }

  updateFAQ();
});