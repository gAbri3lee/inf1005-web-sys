(() => {
	'use strict';

	const FALLBACK_IMAGE_SRC = 'assets/images/HotelHomePage.webp';

	function attachImageFallback(img) {
		if (!img) return;
		img.addEventListener('error', function () {
			if (img.getAttribute('src') === FALLBACK_IMAGE_SRC) return;
			img.setAttribute('src', FALLBACK_IMAGE_SRC);
		});
	}

	const qs = (sel, root = document) => root.querySelector(sel);
	const qsa = (sel, root = document) => Array.from(root.querySelectorAll(sel));

	qsa('img.room-image').forEach(attachImageFallback);

	const modalEl = qs('#roomDetailsModal');
	let bsModal = null;
	const canUseBootstrapModal = Boolean(modalEl && window.bootstrap && window.bootstrap.Modal);
	if (canUseBootstrapModal) {
		try {
			bsModal = new window.bootstrap.Modal(modalEl);
		} catch (_) {
			bsModal = null;
		}
	}

	const modalContent = modalEl ? qs('.modal-content', modalEl) : null;
	let springTimeout = null;
	if (modalEl && modalContent) {
		modalEl.addEventListener('show.bs.modal', () => {
			modalContent.classList.remove('is-springing');
			void modalContent.offsetWidth;
			modalContent.classList.add('is-springing');
			if (springTimeout) {
				clearTimeout(springTimeout);
			}
			springTimeout = setTimeout(() => {
				modalContent.classList.remove('is-springing');
				springTimeout = null;
			}, 700);
		});
	}

	const cards = qsa('.js-room-card');
	const groups = qsa('.js-room-group');
	const emptyEl = qs('.js-empty');
	const countEl = qs('.js-room-count');

	const occChecks = qsa('.js-filter-occupancy');
	const viewChecks = qsa('.js-filter-view');
	const accessibleCheck = qs('.js-filter-accessible');
	const clearBtn = qs('.js-clear-filters');

	const modalName = modalEl ? qs('.js-room-name', modalEl) : null;
	const modalShort = modalEl ? qs('.js-room-short', modalEl) : null;
	const modalPrice = modalEl ? qs('.js-room-price', modalEl) : null;
	const modalRoomId = modalEl ? qs('.js-room-id', modalEl) : null;
	const carouselIndicators = modalEl ? qs('.js-room-carousel-indicators', modalEl) : null;
	const carouselInner = modalEl ? qs('.js-room-carousel-inner', modalEl) : null;

	const listOverview = modalEl ? qs('.js-room-overview', modalEl) : null;
	const listBenefits = modalEl ? qs('.js-room-benefits', modalEl) : null;
	const listBedding = modalEl ? qs('.js-room-bedding', modalEl) : null;
	const listFeatures = modalEl ? qs('.js-room-features', modalEl) : null;
	const listBath = modalEl ? qs('.js-room-bath', modalEl) : null;
	const listFurnish = modalEl ? qs('.js-room-furnish', modalEl) : null;

	const checkInEl = modalEl ? qs('.js-check-in', modalEl) : null;
	const checkOutEl = modalEl ? qs('.js-check-out', modalEl) : null;
	const estTotalEl = modalEl ? qs('.js-est-total', modalEl) : null;
	const viewTotalBtn = modalEl ? qs('.js-view-total', modalEl) : null;

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
		if (!ul) return;
		ul.innerHTML = '';
		(items || []).forEach((text) => {
			const li = document.createElement('li');
			li.textContent = String(text);
			ul.appendChild(li);
		});
	}

	function buildCarousel(images) {
		if (!carouselIndicators || !carouselInner) return;
		carouselIndicators.innerHTML = '';
		carouselInner.innerHTML = '';

		const list = Array.isArray(images) ? images : [];
		const safeList = list.length ? list : [FALLBACK_IMAGE_SRC];

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
			attachImageFallback(img);
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
		if (!estTotalEl || !viewTotalBtn) return;

		if (!activeRoom) {
			estTotalEl.textContent = 'Select dates';
			viewTotalBtn.disabled = true;
			return;
		}

		if (!checkInEl || !checkOutEl) return;
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
		if (!checkInEl || !checkOutEl) return;
		const today = new Date();
		const yyyy = today.getFullYear();
		const mm = String(today.getMonth() + 1).padStart(2, '0');
		const dd = String(today.getDate()).padStart(2, '0');
		const min = `${yyyy}-${mm}-${dd}`;
		checkInEl.min = min;
		checkOutEl.min = min;
	}

	function openRoom(room) {
		if (!modalEl || !bsModal) return;
		activeRoom = room;
		if (modalName) modalName.textContent = room?.name || 'Room';
		if (modalShort) modalShort.textContent = room?.description || '';
		if (modalPrice) modalPrice.textContent = `${money(Number(room?.price_per_night || 0))} / night`;
		if (modalRoomId) modalRoomId.value = String(room?.id || '');

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

		if (checkInEl) checkInEl.value = '';
		if (checkOutEl) checkOutEl.value = '';
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
		const leaveMs = 220;
		const enterMs = 420;
		const timeouts = applyFilters._timeouts || (applyFilters._timeouts = new WeakMap());
		let groupUpdateTimeout = applyFilters._groupUpdateTimeout || null;

		function clearTimer(el) {
			const t = timeouts.get(el);
			if (t) {
				clearTimeout(t);
				timeouts.delete(el);
			}
		}

		cards.forEach((cardEl) => {
			const ok = matchesFilters(cardEl);
			const isHidden = cardEl.classList.contains('d-none');

			clearTimer(cardEl);
			cardEl.classList.remove('filter-enter', 'filter-leave');

			if (ok) {
				shown += 1;
				if (isHidden) {
					cardEl.classList.remove('d-none');
					void cardEl.offsetWidth;
					cardEl.classList.add('filter-enter');
					const tid = setTimeout(() => {
						cardEl.classList.remove('filter-enter');
						timeouts.delete(cardEl);
					}, enterMs + 30);
					timeouts.set(cardEl, tid);
				}
			} else {
				if (!isHidden) {
					const tid = setTimeout(() => {
						cardEl.classList.add('d-none');
						cardEl.classList.remove('filter-leave');
						timeouts.delete(cardEl);
					}, leaveMs);
					timeouts.set(cardEl, tid);
				}
			}
		});

		function updateGroupVisibility() {
			groups.forEach((groupEl) => {
				const visibleCards = qsa('.js-room-card', groupEl).filter((c) => !c.classList.contains('d-none'));
				groupEl.classList.toggle('d-none', visibleCards.length === 0);
			});
		}

		updateGroupVisibility();
		if (groupUpdateTimeout) {
			clearTimeout(groupUpdateTimeout);
		}
		groupUpdateTimeout = setTimeout(updateGroupVisibility, leaveMs + 50);
		applyFilters._groupUpdateTimeout = groupUpdateTimeout;

		if (countEl) countEl.textContent = String(shown);
		if (emptyEl) emptyEl.classList.toggle('d-none', shown !== 0);
	}

	cards.forEach((cardEl) => {
		const btn = qs('.js-open-room', cardEl);
		if (!btn) return;
		btn.addEventListener('click', () => {
			if (!modalEl || !bsModal) return;
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

	if (checkInEl) checkInEl.addEventListener('change', () => {
		// Keep check-out >= check-in
		if (checkInEl.value) {
			checkOutEl.min = checkInEl.value;
			if (checkOutEl.value && checkOutEl.value < checkInEl.value) {
				checkOutEl.value = '';
			}
		}
		updateEstimate();
	});
	if (checkOutEl) checkOutEl.addEventListener('change', updateEstimate);

	applyFilters();
})();
