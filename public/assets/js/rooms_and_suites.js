(() => {
	'use strict';

	const FALLBACK_IMAGE_SRC = 'assets/images/HotelHomePage.webp';
	const FILTER_LEAVE_MS = 220;
	const FILTER_ENTER_MS = 420;
	const MODAL_SPRING_MS = 700;

	const qs = (selector, root = document) => root.querySelector(selector);
	const qsa = (selector, root = document) => Array.from(root.querySelectorAll(selector));

	function attachImageFallback(image) {
		if (!image) {
			return;
		}

		image.addEventListener('error', () => {
			if (image.getAttribute('src') === FALLBACK_IMAGE_SRC) {
				return;
			}

			image.setAttribute('src', FALLBACK_IMAGE_SRC);
		});
	}

	function readRoomCatalog() {
		const source = qs('#rooms-catalog-data');
		if (!source) {
			return [];
		}

		try {
			const rooms = JSON.parse(source.textContent || '[]');
			return Array.isArray(rooms) ? rooms : [];
		} catch (_) {
			return [];
		}
	}

	function formatMoney(amount) {
		try {
			return amount.toLocaleString(undefined, { style: 'currency', currency: 'USD' });
		} catch (_) {
			return `$${Number(amount).toFixed(2)}`;
		}
	}

	function nightsBetween(checkIn, checkOut) {
		const start = new Date(checkIn);
		const end = new Date(checkOut);

		if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) {
			return 0;
		}

		return Math.max(0, Math.ceil((end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24)));
	}

	function setList(listEl, items) {
		if (!listEl) {
			return;
		}

		listEl.innerHTML = '';
		(items || []).forEach((item) => {
			const value = String(item || '').trim();
			if (value === '') {
				return;
			}

			const li = document.createElement('li');
			li.textContent = value;
			listEl.appendChild(li);
		});
	}

	const rooms = readRoomCatalog();
	const roomsById = new Map(rooms.map((room) => [String(room.id || ''), room]));

	qsa('img.room-image').forEach(attachImageFallback);

	const modalEl = qs('#roomDetailsModal');
	const modalContent = modalEl ? qs('.modal-content', modalEl) : null;
	const carouselIndicators = modalEl ? qs('.js-room-carousel-indicators', modalEl) : null;
	const carouselInner = modalEl ? qs('.js-room-carousel-inner', modalEl) : null;
	const modalName = modalEl ? qs('.js-room-name', modalEl) : null;
	const modalShort = modalEl ? qs('.js-room-short', modalEl) : null;
	const modalPrice = modalEl ? qs('.js-room-price', modalEl) : null;
	const modalRoomId = modalEl ? qs('.js-room-id', modalEl) : null;
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
	const cards = qsa('.js-room-card');
	const groups = qsa('.js-room-group');
	const emptyEl = qs('.js-empty');
	const countEl = qs('.js-room-count');
	const occChecks = qsa('.js-filter-occupancy');
	const viewChecks = qsa('.js-filter-view');
	const accessibleCheck = qs('.js-filter-accessible');
	const clearBtn = qs('.js-clear-filters');

	let activeRoom = null;
	let modalSpringTimeout = null;
	let groupVisibilityTimeout = null;
	const cardTimeouts = new WeakMap();

	let bootstrapModal = null;
	if (modalEl && window.bootstrap && window.bootstrap.Modal) {
		try {
			bootstrapModal = new window.bootstrap.Modal(modalEl);
		} catch (_) {
			bootstrapModal = null;
		}
	}

	if (modalEl && modalContent) {
		modalEl.addEventListener('show.bs.modal', () => {
			modalContent.classList.remove('is-springing');
			void modalContent.offsetWidth;
			modalContent.classList.add('is-springing');

			if (modalSpringTimeout) {
				clearTimeout(modalSpringTimeout);
			}

			modalSpringTimeout = setTimeout(() => {
				modalContent.classList.remove('is-springing');
				modalSpringTimeout = null;
			}, MODAL_SPRING_MS);
		});
	}

	function buildCarousel(room) {
		if (!carouselIndicators || !carouselInner) {
			return;
		}

		carouselIndicators.innerHTML = '';
		carouselInner.innerHTML = '';

		const images = Array.isArray(room?.images) && room.images.length ? room.images : [FALLBACK_IMAGE_SRC];
		images.forEach((src, index) => {
			const indicator = document.createElement('button');
			indicator.type = 'button';
			indicator.setAttribute('data-bs-target', '#roomCarousel');
			indicator.setAttribute('data-bs-slide-to', String(index));
			indicator.setAttribute('aria-label', `Slide ${index + 1}`);
			if (index === 0) {
				indicator.classList.add('active');
				indicator.setAttribute('aria-current', 'true');
			}
			carouselIndicators.appendChild(indicator);

			const item = document.createElement('div');
			item.className = `carousel-item${index === 0 ? ' active' : ''}`;

			const image = document.createElement('img');
			image.className = 'd-block w-100';
			image.loading = 'lazy';
			image.src = String(src);
			image.alt = room?.name ? `${room.name} image ${index + 1}` : `Room image ${index + 1}`;
			attachImageFallback(image);

			item.appendChild(image);
			carouselInner.appendChild(item);
		});
	}

	function updateEstimate() {
		if (!estTotalEl || !viewTotalBtn) {
			return;
		}

		if (!activeRoom || !checkInEl || !checkOutEl) {
			estTotalEl.textContent = 'Select dates';
			viewTotalBtn.disabled = true;
			return;
		}

		const nights = nightsBetween(checkInEl.value, checkOutEl.value);
		if (!checkInEl.value || !checkOutEl.value || nights <= 0) {
			estTotalEl.textContent = 'Select dates';
			viewTotalBtn.disabled = true;
			return;
		}

		const total = Number(activeRoom.price_per_night || 0) * nights;
		estTotalEl.textContent = `${formatMoney(total)} (${nights} night${nights === 1 ? '' : 's'})`;
		viewTotalBtn.disabled = false;
	}

	function setDateMinimums() {
		if (!checkInEl || !checkOutEl) {
			return;
		}

		const today = new Date();
		const year = today.getFullYear();
		const month = String(today.getMonth() + 1).padStart(2, '0');
		const day = String(today.getDate()).padStart(2, '0');
		const minDate = `${year}-${month}-${day}`;

		checkInEl.min = minDate;
		checkOutEl.min = checkInEl.value || minDate;
	}

	function openRoom(roomId) {
		if (!bootstrapModal) {
			return;
		}

		const room = roomsById.get(String(roomId || ''));
		if (!room) {
			return;
		}

		activeRoom = room;

		if (modalName) {
			modalName.textContent = room.name || 'Room';
		}
		if (modalShort) {
			modalShort.textContent = room.description || '';
		}
		if (modalPrice) {
			modalPrice.textContent = `${formatMoney(Number(room.price_per_night || 0))} / night`;
		}
		if (modalRoomId) {
			modalRoomId.value = String(room.id || '');
		}

		setList(listOverview, [
			room.view ? `${room.view} view` : 'View not listed',
			room.occupancy ? `${room.occupancy} guest occupancy` : 'Occupancy not listed',
			room.accessible ? 'Wheelchair friendly' : 'Not wheelchair friendly',
			room.size ? `Size: ${room.size}` : '',
		]);
		setList(listBenefits, room.benefits || []);
		setList(listBedding, [
			room.bed || '',
			room.occupancy ? `Maximum occupancy: ${room.occupancy}` : '',
		]);
		setList(listFeatures, room.features || []);
		setList(listBath, room.bathroom || []);
		setList(listFurnish, room.furnishings || []);
		buildCarousel(room);

		if (checkInEl) {
			checkInEl.value = '';
		}
		if (checkOutEl) {
			checkOutEl.value = '';
		}

		setDateMinimums();
		updateEstimate();
		bootstrapModal.show();
	}

	function selectedValues(inputs) {
		return inputs.filter((input) => input.checked).map((input) => input.value);
	}

	function matchesFilters(cardEl) {
		const selectedOccupancies = selectedValues(occChecks);
		const selectedViews = selectedValues(viewChecks);
		const accessibleOnly = Boolean(accessibleCheck?.checked);
		const occupancy = cardEl.getAttribute('data-occupancy') || '';
		const view = cardEl.getAttribute('data-view') || '';
		const accessible = cardEl.getAttribute('data-accessible') === '1';

		if (selectedOccupancies.length && !selectedOccupancies.includes(occupancy)) {
			return false;
		}
		if (selectedViews.length && !selectedViews.includes(view)) {
			return false;
		}
		if (accessibleOnly && !accessible) {
			return false;
		}

		return true;
	}

	function clearCardTimer(cardEl) {
		const timer = cardTimeouts.get(cardEl);
		if (!timer) {
			return;
		}

		clearTimeout(timer);
		cardTimeouts.delete(cardEl);
	}

	function showCard(cardEl) {
		clearCardTimer(cardEl);
		cardEl.classList.remove('filter-leave');

		if (!cardEl.classList.contains('d-none')) {
			return;
		}

		cardEl.classList.remove('d-none');
		void cardEl.offsetWidth;
		cardEl.classList.add('filter-enter');

		const timer = setTimeout(() => {
			cardEl.classList.remove('filter-enter');
			cardTimeouts.delete(cardEl);
		}, FILTER_ENTER_MS + 30);
		cardTimeouts.set(cardEl, timer);
	}

	function hideCard(cardEl) {
		clearCardTimer(cardEl);
		cardEl.classList.remove('filter-enter');

		if (cardEl.classList.contains('d-none')) {
			cardEl.classList.remove('filter-leave');
			return;
		}

		cardEl.classList.add('filter-leave');
		const timer = setTimeout(() => {
			cardEl.classList.add('d-none');
			cardEl.classList.remove('filter-leave');
			cardTimeouts.delete(cardEl);
		}, FILTER_LEAVE_MS);
		cardTimeouts.set(cardEl, timer);
	}

	function updateGroupVisibility() {
		groups.forEach((groupEl) => {
			const visibleCards = qsa('.js-room-card', groupEl).filter((cardEl) => !cardEl.classList.contains('d-none'));
			groupEl.classList.toggle('d-none', visibleCards.length === 0);
		});
	}

	function applyFilters() {
		let shownCount = 0;

		cards.forEach((cardEl) => {
			if (matchesFilters(cardEl)) {
				shownCount += 1;
				showCard(cardEl);
			} else {
				hideCard(cardEl);
			}
		});

		updateGroupVisibility();
		if (groupVisibilityTimeout) {
			clearTimeout(groupVisibilityTimeout);
		}
		groupVisibilityTimeout = setTimeout(updateGroupVisibility, FILTER_LEAVE_MS + 40);

		if (countEl) {
			countEl.textContent = String(shownCount);
		}
		if (emptyEl) {
			emptyEl.classList.toggle('d-none', shownCount !== 0);
		}
	}

	cards.forEach((cardEl) => {
		const trigger = qs('.js-open-room', cardEl);
		if (!trigger) {
			return;
		}

		trigger.addEventListener('click', () => {
			openRoom(cardEl.getAttribute('data-room-id'));
		});
	});

	[...occChecks, ...viewChecks].forEach((input) => {
		input.addEventListener('change', applyFilters);
	});

	if (accessibleCheck) {
		accessibleCheck.addEventListener('change', applyFilters);
	}

	if (clearBtn) {
		clearBtn.addEventListener('click', () => {
			[...occChecks, ...viewChecks].forEach((input) => {
				input.checked = false;
			});
			if (accessibleCheck) {
				accessibleCheck.checked = false;
			}
			applyFilters();
		});
	}

	if (checkInEl && checkOutEl) {
		checkInEl.addEventListener('change', () => {
			if (checkInEl.value) {
				checkOutEl.min = checkInEl.value;
				if (checkOutEl.value && checkOutEl.value < checkInEl.value) {
					checkOutEl.value = '';
				}
			}
			updateEstimate();
		});

		checkOutEl.addEventListener('change', updateEstimate);
	}

	setDateMinimums();
	applyFilters();
})();
