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
	const tourToggleBtn = modalEl ? qs('.js-tour-toggle', modalEl) : null;
	const tourLabelEl = modalEl ? qs('.js-tour-label', modalEl) : null;
	const tourEl = modalEl ? qs('.js-room-tour', modalEl) : null;
	const tourTrackEl = modalEl ? qs('.js-room-tour-track', modalEl) : null;
	const tourHintEl = modalEl ? qs('.js-room-tour-hint', modalEl) : null;
	const mediaAreaEl = modalEl ? qs('.room-media-area', modalEl) : null;

	let activeRoom = null;
	let modalSpringTimeout = null;
	let groupVisibilityTimeout = null;
	const cardTimeouts = new WeakMap();
	// Faux 3D tour state for wide room photos.
	const TOUR_SENSITIVITY = 0.18;          // tour units shifted per pixel dragged
	const TOUR_MIN = 0;
	const TOUR_MAX = 360;

	let tourYaw = 180;                      // current horizontal tour position, centered
	let tourDragStartX = null;
	let tourDragStartYaw = 0;
	let tourImg = null;
	let tourImgLoaded = false;
	let tourCanvas = null;
	let tourCtx = null;
	let tourHintTimeout = null;
	let activeTourMode = 'panorama';
	let tourScenes = [];
	let tourSceneMap = new Map();
	let activeTourSceneId = '';
	let tourInteractiveRoot = null;
	let tourSceneImageEl = null;
	let tourSceneInfoEl = null;
	let tourBackBtn = null;
	let tourHotspotsEl = null;

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

	// ---------------------------------------------------------------------------
	// Proportion-preserving room tour — pans a wide room photo horizontally
	// within the viewport while keeping its natural aspect ratio.
	// ---------------------------------------------------------------------------

	function clamp(value, min, max) {
		return Math.min(max, Math.max(min, value));
	}

	function renderTour() {
		if (!tourCanvas || !tourCtx || !tourImgLoaded || !tourImg) {
			return;
		}

		const cw = tourCanvas.width;
		const ch = tourCanvas.height;
		const imgW = tourImg.naturalWidth;
		const imgH = tourImg.naturalHeight;
		if (!imgW || !imgH) {
			return;
		}

		tourCtx.fillStyle = '#111827';
		tourCtx.fillRect(0, 0, cw, ch);

		const scale = Math.max(cw / imgW, ch / imgH);
		const destW = imgW * scale;
		const destH = imgH * scale;
		const overflowX = Math.max(0, destW - cw);
		const overflowY = Math.max(0, destH - ch);
		const xProgress = clamp((tourYaw - TOUR_MIN) / (TOUR_MAX - TOUR_MIN), 0, 1);
		const destX = overflowX > 0 ? -overflowX * xProgress : (cw - destW) / 2;
		const destY = overflowY > 0 ? -overflowY / 2 : (ch - destH) / 2;

		tourCtx.drawImage(tourImg, destX, destY, destW, destH);
	}

	function resizeTourCanvas() {
		if (!tourCanvas || !tourEl) {
			return;
		}
		tourCanvas.width = tourEl.clientWidth || tourEl.offsetWidth;
		tourCanvas.height = tourEl.clientHeight || tourEl.offsetHeight;
		renderTour();
	}

	function buildTour(room) {
		if (!tourTrackEl) {
			return;
		}

		tourYaw = 180;
		tourImgLoaded = false;
		activeTourMode = 'panorama';
		tourScenes = Array.isArray(room?.tour_scenes) ? room.tour_scenes : [];
		tourSceneMap = new Map(tourScenes.map((scene) => [String(scene?.id || ''), scene]));
		activeTourSceneId = '';
		tourInteractiveRoot = null;
		tourSceneImageEl = null;
		tourSceneInfoEl = null;
		tourBackBtn = null;
		tourHotspotsEl = null;
		tourTrackEl.innerHTML = '';

		if (tourScenes.length > 0) {
			buildInteractiveTour();
			return;
		}

		// Create canvas
		tourCanvas = document.createElement('canvas');
		tourCanvas.className = 'room-tour-canvas';
		tourCanvas.setAttribute('aria-hidden', 'true');
		tourCtx = tourCanvas.getContext('2d');
		tourTrackEl.appendChild(tourCanvas);

		// Load the primary room image
		const src = typeof room?.tour_image === 'string' && room.tour_image.trim() !== ''
			? room.tour_image.trim()
			: (Array.isArray(room?.images) && room.images.length
				? String(room.images[0])
				: FALLBACK_IMAGE_SRC);

		tourImg = new Image();
		tourImg.crossOrigin = 'anonymous';
		tourImg.onload = () => {
			tourImgLoaded = true;
			resizeTourCanvas();
		};
		tourImg.onerror = () => {
			tourImg.src = FALLBACK_IMAGE_SRC;
		};
		tourImg.src = src;
	}

	function showTourScene(sceneId) {
		const scene = tourSceneMap.get(String(sceneId || ''));
		if (!scene || !tourInteractiveRoot || !tourSceneImageEl || !tourSceneInfoEl || !tourHotspotsEl) {
			return;
		}

		activeTourSceneId = String(scene.id || '');
		tourSceneImageEl.src = String(scene.image || FALLBACK_IMAGE_SRC);
		tourSceneImageEl.alt = scene.title ? `${scene.title} in the 3D tour` : 'Room tour view';
		tourSceneImageEl.style.objectFit = scene.fit === 'contain' ? 'contain' : 'cover';
		attachImageFallback(tourSceneImageEl);

		if (tourInteractiveRoot) {
			tourInteractiveRoot.style.background = scene.fit === 'contain'
				? String(scene.background || '#f2ede4')
				: '';
		}

		tourSceneInfoEl.innerHTML = '';

		const titleEl = document.createElement('strong');
		titleEl.className = 'room-tour-scene-title';
		titleEl.textContent = String(scene.title || 'Room POV');
		tourSceneInfoEl.appendChild(titleEl);

		if (scene.description) {
			const descEl = document.createElement('span');
			descEl.className = 'room-tour-scene-desc';
			descEl.textContent = String(scene.description);
			tourSceneInfoEl.appendChild(descEl);
		}

		if (tourBackBtn) {
			const backTarget = String(scene.back_target || '');
			tourBackBtn.hidden = backTarget === '';
			tourBackBtn.dataset.target = backTarget;
		}

		tourHotspotsEl.innerHTML = '';
		(Array.isArray(scene.hotspots) ? scene.hotspots : []).forEach((hotspot) => {
			const targetId = String(hotspot?.target || '');
			if (!tourSceneMap.has(targetId)) {
				return;
			}

			const button = document.createElement('button');
			button.type = 'button';
			button.className = 'room-tour-hotspot';
			button.style.left = `${Number(hotspot?.x || 0)}%`;
			button.style.top = `${Number(hotspot?.y || 0)}%`;
			button.dataset.target = targetId;
			button.setAttribute('aria-label', `Go to ${String(hotspot?.label || targetId)} view`);
			button.innerHTML = `<span>${String(hotspot?.label || 'View')}</span>`;
			tourHotspotsEl.appendChild(button);
		});
	}

	function buildInteractiveTour() {
		if (!tourTrackEl || tourScenes.length === 0) {
			return;
		}

		activeTourMode = 'hotspots';

		tourInteractiveRoot = document.createElement('div');
		tourInteractiveRoot.className = 'room-tour-interactive';

		tourSceneImageEl = document.createElement('img');
		tourSceneImageEl.className = 'room-tour-scene-image';
		tourSceneImageEl.loading = 'lazy';

		tourHotspotsEl = document.createElement('div');
		tourHotspotsEl.className = 'room-tour-hotspots';

		tourSceneInfoEl = document.createElement('div');
		tourSceneInfoEl.className = 'room-tour-scene-info';

		tourBackBtn = document.createElement('button');
		tourBackBtn.type = 'button';
		tourBackBtn.className = 'room-tour-back';
		tourBackBtn.textContent = 'Back to room';
		tourBackBtn.hidden = true;

		tourInteractiveRoot.appendChild(tourSceneImageEl);
		tourInteractiveRoot.appendChild(tourHotspotsEl);
		tourInteractiveRoot.appendChild(tourSceneInfoEl);
		tourInteractiveRoot.appendChild(tourBackBtn);
		tourTrackEl.appendChild(tourInteractiveRoot);

		tourHotspotsEl.addEventListener('click', (event) => {
			const button = event.target.closest('.room-tour-hotspot');
			if (!button) {
				return;
			}

			showTourScene(button.dataset.target);
		});

		tourBackBtn.addEventListener('click', () => {
			if (tourBackBtn && tourBackBtn.dataset.target) {
				showTourScene(tourBackBtn.dataset.target);
			}
		});

		showTourScene(tourScenes[0]?.id || '');
	}

	function enterTour() {
		if (!mediaAreaEl || !tourToggleBtn || !tourLabelEl || !tourEl) {
			return;
		}

		mediaAreaEl.classList.add('is-touring');
		tourToggleBtn.setAttribute('aria-pressed', 'true');
		tourLabelEl.textContent = 'Photos';
		if (activeTourMode === 'panorama') {
			resizeTourCanvas();
		}

		if (tourHintEl) {
			tourHintEl.textContent = activeTourMode === 'hotspots'
				? 'Tap the highlighted areas to move through the room'
				: '← Drag to look around →';
			tourHintEl.classList.add('is-visible');
			if (tourHintTimeout) {
				clearTimeout(tourHintTimeout);
			}
			tourHintTimeout = setTimeout(() => {
				tourHintEl.classList.remove('is-visible');
				tourHintTimeout = null;
			}, 2600);
		}
	}

	function exitTour() {
		if (!mediaAreaEl || !tourToggleBtn || !tourLabelEl) {
			return;
		}

		mediaAreaEl.classList.remove('is-touring');
		tourToggleBtn.setAttribute('aria-pressed', 'false');
		tourLabelEl.textContent = '3D Tour';

		if (tourHintEl) {
			tourHintEl.classList.remove('is-visible');
		}
		if (tourHintTimeout) {
			clearTimeout(tourHintTimeout);
			tourHintTimeout = null;
		}
	}

	function getTourClientX(e) {
		return e.touches && e.touches.length ? e.touches[0].clientX : e.clientX;
	}

	if (tourEl) {
		const onDragStart = (e) => {
			if (!mediaAreaEl?.classList.contains('is-touring') || activeTourMode !== 'panorama') {
				return;
			}
			tourDragStartX = getTourClientX(e);
			tourDragStartYaw = tourYaw;
			tourEl.classList.add('is-dragging');
		};

		const onDragMove = (e) => {
			if (tourDragStartX === null) {
				return;
			}
			const dx = getTourClientX(e) - tourDragStartX;
			// Dragging right moves toward the left side of the photo; clamp to bounds.
			tourYaw = clamp(tourDragStartYaw - dx * TOUR_SENSITIVITY, TOUR_MIN, TOUR_MAX);
			renderTour();
		};

		const onDragEnd = () => {
			if (tourDragStartX === null) {
				return;
			}
			tourDragStartX = null;
			tourEl.classList.remove('is-dragging');
		};

		tourEl.addEventListener('mousedown', onDragStart);
		tourEl.addEventListener('touchstart', onDragStart, { passive: true });
		document.addEventListener('mousemove', onDragMove);
		document.addEventListener('touchmove', onDragMove, { passive: true });
		document.addEventListener('mouseup', onDragEnd);
		document.addEventListener('touchend', onDragEnd);
	}

	window.addEventListener('resize', () => {
		if (mediaAreaEl?.classList.contains('is-touring') && activeTourMode === 'panorama') {
			resizeTourCanvas();
		}
	});

	if (tourToggleBtn) {
		tourToggleBtn.addEventListener('click', () => {
			if (mediaAreaEl?.classList.contains('is-touring')) {
				exitTour();
			} else {
				enterTour();
			}
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
		buildTour(room);
		exitTour();

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
