(function () {
	'use strict';

	const STORAGE_KEY = 'reviews_active_category';
	const browse = document.querySelector('#reviews-browse');
	const buttons = Array.from(document.querySelectorAll('.js-review-filter'));
	const items = Array.from(document.querySelectorAll('.js-review-item'));
	const reviewRevealItems = Array.from(document.querySelectorAll('.reviews-page .reveal-up'));

	if (reviewRevealItems.length) {
		requestAnimationFrame(() => {
			reviewRevealItems.forEach((item) => item.classList.add('visible'));
		});
	}

	if (!browse || !buttons.length || !items.length) {
		return;
	}

	function getItemCategories(itemEl) {
		const raw = (itemEl.getAttribute('data-categories') || '').trim();
		if (!raw) return [];
		return raw.split(',').map((s) => s.trim()).filter(Boolean);
	}

	function setButtonStates(activeCategory) {
		buttons.forEach((btn) => {
			const cat = btn.getAttribute('data-category') || '';
			const isActive = cat === activeCategory;
			btn.classList.toggle('btn-gold', isActive);
			btn.classList.toggle('btn-outline-secondary', !isActive);
			btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
		});
	}

	function applyFilter(activeCategory) {
		const leaveMs = 220;
		const enterMs = 420;
		const timeouts = applyFilter._timeouts || (applyFilter._timeouts = new WeakMap());

		function clearTimer(el) {
			const t = timeouts.get(el);
			if (t) {
				clearTimeout(t);
				timeouts.delete(el);
			}
		}

		items.forEach((itemEl) => {
			const cats = getItemCategories(itemEl);
			const ok = !activeCategory || cats.includes(activeCategory);
			const isHidden = itemEl.classList.contains('d-none');

			clearTimer(itemEl);
			itemEl.classList.remove('filter-enter', 'filter-leave');

			if (ok) {
				if (isHidden) {
					itemEl.classList.remove('d-none');
					void itemEl.offsetWidth;
					itemEl.classList.add('filter-enter');
					const tid = setTimeout(() => {
						itemEl.classList.remove('filter-enter');
						timeouts.delete(itemEl);
					}, enterMs + 30);
					timeouts.set(itemEl, tid);
				}
			} else {
				if (!isHidden) {
					itemEl.classList.add('filter-leave');
					const tid = setTimeout(() => {
						itemEl.classList.add('d-none');
						itemEl.classList.remove('filter-leave');
						timeouts.delete(itemEl);
					}, leaveMs);
					timeouts.set(itemEl, tid);
				}
			}
		});
	}

	function readInitialCategory() {
		let stored = '';
		try {
			stored = sessionStorage.getItem(STORAGE_KEY) || '';
		} catch (_) {
			stored = '';
		}
		if (stored) return stored;
		return (browse.getAttribute('data-initial-category') || '').trim();
	}

	function persistCategory(category) {
		try {
			sessionStorage.setItem(STORAGE_KEY, category);
		} catch (_) {
		}
	}

	let active = readInitialCategory();
	if (!buttons.some((b) => (b.getAttribute('data-category') || '') === active)) {
		active = '';
	}
	setButtonStates(active);
	applyFilter(active);

	buttons.forEach((btn) => {
		btn.addEventListener('click', function () {
			if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
				const cat = btn.getAttribute('data-category') || '';
				active = cat;
				persistCategory(active);
				setButtonStates(active);
				items.forEach((itemEl) => {
					const cats = getItemCategories(itemEl);
					const ok = !active || cats.includes(active);
					itemEl.classList.toggle('d-none', !ok);
					itemEl.classList.remove('filter-enter', 'filter-leave');
				});
				return;
			}

			const cat = btn.getAttribute('data-category') || '';
			active = cat;
			persistCategory(active);
			setButtonStates(active);
			applyFilter(active);
		});
	});
})();
