document.addEventListener('DOMContentLoaded', function () {
	const searchInput = document.getElementById('faqSearch');
	const buttons = Array.from(document.querySelectorAll('.faq-category-btn'));
	const items = Array.from(document.querySelectorAll('.faq-item'));
	const resultCount = document.getElementById('faqResultCount');
	const currentCategory = document.getElementById('faqCurrentCategory');
	const emptyState = document.getElementById('faqEmptyState');
	const loadMoreButton = document.getElementById('faqLoadMore');

	if (!buttons.length || !items.length) {
		return;
	}

	const INITIAL_VISIBLE = 5;
	const LOAD_MORE_STEP = 5;

	let activeCategory = 'all';
	let visibleLimit = INITIAL_VISIBLE;

	function getLabelForCategory(category) {
		if (category === 'all') {
			return 'All topics';
		}

		const activeButton = buttons.find((button) => button.getAttribute('data-category') === category);
		return activeButton ? activeButton.querySelector('span')?.textContent?.trim() || activeButton.textContent.trim() : 'Selected category';
	}

	function closeHiddenAccordions(item) {
		const collapseEl = item.querySelector('.accordion-collapse');
		const button = item.querySelector('.accordion-button');

		if (button) {
			button.classList.add('collapsed');
			button.setAttribute('aria-expanded', 'false');
		}

		if (!collapseEl) {
			return;
		}

		if (window.bootstrap && window.bootstrap.Collapse) {
			window.bootstrap.Collapse.getOrCreateInstance(collapseEl, { toggle: false }).hide();
		} else {
			collapseEl.classList.remove('show');
		}
	}

	function openFirstVisibleItem(visibleItems) {
		if (!visibleItems.length) {
			return;
		}

		const alreadyOpen = visibleItems.some((item) => {
			const collapseEl = item.querySelector('.accordion-collapse');
			return collapseEl && collapseEl.classList.contains('show');
		});

		if (alreadyOpen) {
			return;
		}

		const first = visibleItems[0];
		const button = first.querySelector('.accordion-button');
		const collapseEl = first.querySelector('.accordion-collapse');

		if (!button || !collapseEl) {
			return;
		}

		if (window.bootstrap && window.bootstrap.Collapse) {
			window.bootstrap.Collapse.getOrCreateInstance(collapseEl, { toggle: false }).show();
		} else {
			button.classList.remove('collapsed');
			button.setAttribute('aria-expanded', 'true');
			collapseEl.classList.add('show');
		}
	}

	function updateLoadMore(totalMatches) {
		if (!loadMoreButton) {
			return;
		}

		if (totalMatches > visibleLimit) {
			loadMoreButton.hidden = false;
			loadMoreButton.textContent = 'Show more questions';
		} else {
			loadMoreButton.hidden = true;
		}
	}

	function updateFAQ(resetLimit) {
		if (resetLimit) {
			visibleLimit = INITIAL_VISIBLE;
		}

		const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
		const searching = searchTerm !== '';

		const matchedItems = items.filter(function (item) {
			const itemCategory = item.getAttribute('data-category') || '';
			const itemText = item.textContent.toLowerCase();
			const matchesCategory = searching ? true : (activeCategory === 'all' || itemCategory === activeCategory);
			const matchesSearch = searchTerm === '' || itemText.includes(searchTerm);
			return matchesCategory && matchesSearch;
		});

		const visibleItems = [];

		items.forEach(function (item) {
			item.hidden = true;
			closeHiddenAccordions(item);
		});

		matchedItems.forEach(function (item, index) {
			const shouldShow = index < visibleLimit;
			item.hidden = !shouldShow;
			if (shouldShow) {
				visibleItems.push(item);
			}
		});

		if (currentCategory) {
			currentCategory.textContent = searching ? 'Search results across all topics' : getLabelForCategory(activeCategory);
		}

		if (resultCount) {
			const count = matchedItems.length;
			resultCount.textContent = count === 1 ? '1 question' : count + ' questions';
		}

		if (emptyState) {
			emptyState.hidden = matchedItems.length !== 0;
		}

		updateLoadMore(matchedItems.length);
		openFirstVisibleItem(visibleItems);
	}

	buttons.forEach(function (button) {
		button.addEventListener('click', function () {
			activeCategory = this.getAttribute('data-category') || 'all';

			buttons.forEach(function (btn) {
				const isActive = btn === button;
				btn.classList.toggle('active', isActive);
				btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
			});

			updateFAQ(true);
		});
	});

	if (searchInput) {
		searchInput.addEventListener('input', function () {
			updateFAQ(true);
		});
	}

	if (loadMoreButton) {
		loadMoreButton.addEventListener('click', function () {
			visibleLimit += LOAD_MORE_STEP;
			updateFAQ(false);
		});
	}

	updateFAQ(true);
});
