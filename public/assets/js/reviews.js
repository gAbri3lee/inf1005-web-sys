(function () {
	'use strict';

	const FALLBACK_IMAGE_SRC = 'assets/images/HotelHomePage.png';

	const STORAGE_KEY = 'reviews_active_category';
	const browse = document.querySelector('#reviews-browse');
	const buttons = Array.from(document.querySelectorAll('.js-review-filter'));
	const items = Array.from(document.querySelectorAll('.js-review-item'));
	const totalEl = document.querySelector('.js-reviews-total');
	const averageEl = document.querySelector('.js-reviews-average');
	const averageStarsEl = document.querySelector('.js-reviews-average-stars');
	const reviewRevealItems = Array.from(document.querySelectorAll('.reviews-page .reveal-up'));
	const reviewImages = Array.from(document.querySelectorAll('.reviews-page img.review-image'));

	if (reviewImages.length) {
		reviewImages.forEach((img) => {
			img.addEventListener('error', function () {
				if (!img || img.getAttribute('src') === FALLBACK_IMAGE_SRC) return;
				img.setAttribute('src', FALLBACK_IMAGE_SRC);
			});
		});
	}

	if (reviewRevealItems.length) {
		requestAnimationFrame(() => {
			reviewRevealItems.forEach((item) => item.classList.add('visible'));
		});
	}

	if (!browse || !buttons.length) {
		return;
	}

	function getItemRating(itemEl) {
		const stars = itemEl.querySelector('.review-stars');
		const label = stars ? (stars.getAttribute('aria-label') || '') : '';
		const m = String(label).match(/(\d+(?:\.\d+)?)/);
		if (m) return Number(m[1]);
		return NaN;
	}

	function renderAverageStars(display) {
		if (display == null || Number.isNaN(display)) return '';
		const v = Math.max(0, Math.min(5, Math.round(Number(display) * 2) / 2));
		let out = `<span class="review-stars" aria-label="${v.toFixed(1)} out of 5 stars">`;
		for (let i = 1; i <= 5; i += 1) {
			let state = 'star';
			if (v >= i) state = 'star filled';
			else if (v >= (i - 0.5)) state = 'star half';
			out += `<span class="${state}" aria-hidden="true">★</span>`;
		}
		out += '</span>';
		return out;
	}

	function updateStats() {
		if (!totalEl && !averageEl && !averageStarsEl) return;

		const visibleItems = items.filter((el) => !el.classList.contains('d-none'));
		const total = visibleItems.length;
		if (totalEl) totalEl.textContent = String(total);

		const ratings = visibleItems.map(getItemRating).filter((n) => Number.isFinite(n) && n > 0);
		if (!ratings.length) {
			if (averageEl) averageEl.textContent = '—';
			if (averageStarsEl) averageStarsEl.innerHTML = '';
			return;
		}

		const avg = ratings.reduce((a, b) => a + b, 0) / ratings.length;
		if (averageEl) averageEl.textContent = `${avg.toFixed(1)} / 5`;
		if (averageStarsEl) averageStarsEl.innerHTML = renderAverageStars(avg);
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
	updateStats();

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
				updateStats();
				return;
			}

			const cat = btn.getAttribute('data-category') || '';
			active = cat;
			persistCategory(active);
			setButtonStates(active);
			applyFilter(active);
			updateStats();
		});
	});
})();
