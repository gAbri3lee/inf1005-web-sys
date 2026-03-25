(() => {
	'use strict';

	const qs = (sel, root = document) => root.querySelector(sel);
	const qsa = (sel, root = document) => Array.from(root.querySelectorAll(sel));

	const modalEl = qs('#roomDetailsModal');
	if (!modalEl) return;

	const bsModal = new bootstrap.Modal(modalEl);

	const cards = qsa('.js-room-card');
	const groups = qsa('.js-room-group');
	const emptyEl = qs('.js-empty');
	const countEl = qs('.js-room-count');

	const occChecks = qsa('.js-filter-occupancy');
	const viewChecks = qsa('.js-filter-view');
	const accessibleCheck = qs('.js-filter-accessible');
	const clearBtn = qs('.js-clear-filters');

	const modalName = qs('.js-room-name', modalEl);
	const modalShort = qs('.js-room-short', modalEl);
	const modalPrice = qs('.js-room-price', modalEl);
	const modalRoomId = qs('.js-room-id', modalEl);
	const carouselIndicators = qs('.js-room-carousel-indicators', modalEl);
	const carouselInner = qs('.js-room-carousel-inner', modalEl);

	const listOverview = qs('.js-room-overview', modalEl);
	const listBenefits = qs('.js-room-benefits', modalEl);
	const listBedding = qs('.js-room-bedding', modalEl);
	const listFeatures = qs('.js-room-features', modalEl);
	const listBath = qs('.js-room-bath', modalEl);
	const listFurnish = qs('.js-room-furnish', modalEl);

	const checkInEl = qs('.js-check-in', modalEl);
	const checkOutEl = qs('.js-check-out', modalEl);
	const estTotalEl = qs('.js-est-total', modalEl);
	const viewTotalBtn = qs('.js-view-total', modalEl);

	let activeRoom = null;

	function money(n) {
		try {
			return n.toLocaleString(undefined, { style: 'currency', currency: 'USD' });
		} catch {
			return `$${Number(n).toFixed(2)}`;
		}
	}

	function parseRoom(cardEl) {
		const raw = cardEl.getAttribute('data-room') || '';
		try {
			return JSON.parse(raw);
		} catch {
			return null;
		}
	}

	function setList(ul, items) {
		ul.innerHTML = '';
		(items || []).forEach((text) => {
			const li = document.createElement('li');
			li.textContent = String(text);
			ul.appendChild(li);
		});
	}

	function buildCarousel(images) {
		carouselIndicators.innerHTML = '';
		carouselInner.innerHTML = '';

		const list = Array.isArray(images) ? images : [];
		const safeList = list.length ? list : ['assets/images/HotelHomePage.png'];

		safeList.forEach((src, idx) => {
			const btn = document.createElement('button');
			btn.type = 'button';
			btn.setAttribute('data-bs-target', '#roomCarousel');
			btn.setAttribute('data-bs-slide-to', String(idx));
			btn.setAttribute('aria-label', `Slide ${idx + 1}`);
			if (idx === 0) {
				btn.classList.add('active');
				btn.setAttribute('aria-current', 'true');
			}
			carouselIndicators.appendChild(btn);

			const item = document.createElement('div');
			item.className = 'carousel-item' + (idx === 0 ? ' active' : '');

			const img = document.createElement('img');
			img.className = 'd-block w-100';
			img.loading = 'lazy';
			img.src = String(src);
			img.alt = activeRoom?.name ? `${activeRoom.name} image ${idx + 1}` : `Room image ${idx + 1}`;

			item.appendChild(img);
			carouselInner.appendChild(item);
		});
	}

	function daysBetweenInclusive(checkIn, checkOut) {
		const a = new Date(checkIn);
		const b = new Date(checkOut);
		if (Number.isNaN(a.getTime()) || Number.isNaN(b.getTime())) return 0;
		const diff = b.getTime() - a.getTime();
		const nights = Math.ceil(diff / (1000 * 60 * 60 * 24));
		return Math.max(0, nights);
	}

	function updateEstimate() {
		if (!activeRoom) {
			estTotalEl.textContent = 'Select dates';
			viewTotalBtn.disabled = true;
			return;
		}

		const checkIn = checkInEl.value;
		const checkOut = checkOutEl.value;
		const nights = daysBetweenInclusive(checkIn, checkOut);
		if (!checkIn || !checkOut || nights <= 0) {
			estTotalEl.textContent = 'Select dates';
			viewTotalBtn.disabled = true;
			return;
		}

		const total = Number(activeRoom.price_per_night || 0) * nights;
		estTotalEl.textContent = `${money(total)} (${nights} night${nights === 1 ? '' : 's'})`;
		viewTotalBtn.disabled = false;
	}

	function setDateMins() {
		const today = new Date();
		const yyyy = today.getFullYear();
		const mm = String(today.getMonth() + 1).padStart(2, '0');
		const dd = String(today.getDate()).padStart(2, '0');
		const min = `${yyyy}-${mm}-${dd}`;
		checkInEl.min = min;
		checkOutEl.min = min;
	}

	function openRoom(room) {
		activeRoom = room;
		modalName.textContent = room?.name || 'Room';
		modalShort.textContent = room?.description || '';
		modalPrice.textContent = `${money(Number(room?.price_per_night || 0))} / night`;
		modalRoomId.value = String(room?.id || '');

		setList(listOverview, [
			`${room?.view || '—'} view`,
			`${room?.occupancy || '—'} guest occupancy`,
			room?.accessible ? 'Wheelchair friendly' : 'Not wheelchair friendly',
			room?.size ? `Size: ${room.size}` : null,
		].filter(Boolean));

		setList(listBenefits, room?.benefits || []);
		setList(listBedding, [
			room?.bed ? room.bed : null,
			room?.occupancy ? `Maximum occupancy: ${room.occupancy}` : null,
		].filter(Boolean));
		setList(listFeatures, room?.features || []);
		setList(listBath, room?.bathroom || []);
		setList(listFurnish, room?.furnishings || []);

		buildCarousel(room?.images || []);

		checkInEl.value = '';
		checkOutEl.value = '';
		setDateMins();
		updateEstimate();

		bsModal.show();
	}

	function selectedValues(checks) {
		return checks.filter((c) => c.checked).map((c) => c.value);
	}

	function matchesFilters(cardEl) {
		const occSelected = selectedValues(occChecks);
		const viewSelected = selectedValues(viewChecks);
		const mustBeAccessible = Boolean(accessibleCheck && accessibleCheck.checked);

		const occ = cardEl.getAttribute('data-occupancy') || '';
		const view = cardEl.getAttribute('data-view') || '';
		const accessible = cardEl.getAttribute('data-accessible') === '1';

		if (occSelected.length && !occSelected.includes(occ)) return false;
		if (viewSelected.length && !viewSelected.includes(view)) return false;
		if (mustBeAccessible && !accessible) return false;
		return true;
	}

	function applyFilters() {
		let shown = 0;
		cards.forEach((cardEl) => {
			const ok = matchesFilters(cardEl);
			cardEl.classList.toggle('d-none', !ok);
			if (ok) shown += 1;
		});

		groups.forEach((groupEl) => {
			const visibleCards = qsa('.js-room-card', groupEl).filter((c) => !c.classList.contains('d-none'));
			groupEl.classList.toggle('d-none', visibleCards.length === 0);
		});

		if (countEl) countEl.textContent = String(shown);
		if (emptyEl) emptyEl.classList.toggle('d-none', shown !== 0);
	}

	cards.forEach((cardEl) => {
		const btn = qs('.js-open-room', cardEl);
		if (!btn) return;
		btn.addEventListener('click', () => {
			const room = parseRoom(cardEl);
			if (room) openRoom(room);
		});
	});

	[...occChecks, ...viewChecks].forEach((el) => el.addEventListener('change', applyFilters));
	if (accessibleCheck) accessibleCheck.addEventListener('change', applyFilters);
	if (clearBtn) {
		clearBtn.addEventListener('click', () => {
			[...occChecks, ...viewChecks].forEach((el) => (el.checked = false));
			if (accessibleCheck) accessibleCheck.checked = false;
			applyFilters();
		});
	}

	checkInEl.addEventListener('change', () => {
		// Keep check-out >= check-in
		if (checkInEl.value) {
			checkOutEl.min = checkInEl.value;
			if (checkOutEl.value && checkOutEl.value < checkInEl.value) {
				checkOutEl.value = '';
			}
		}
		updateEstimate();
	});
	checkOutEl.addEventListener('change', updateEstimate);

	applyFilters();
})();
