document.addEventListener('DOMContentLoaded', () => {
	const section = document.querySelector('#locations');
	const mapElement = section?.querySelector('#home-locations-map');
	const dock = section?.querySelector('[data-locations-dock]');
	if (!section || !mapElement || !dock) return;
	const choices = Array.from(dock.querySelectorAll('[data-location-key]'));
	const panels = Array.from(dock.querySelectorAll('[data-location-panel]'));
	const resetButtons = Array.from(dock.querySelectorAll('[data-locations-reset]'));
	const splitGrid = dock.querySelector('[data-locations-choices]');
	const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
	const locations = {
		'avenue-h': { label: 'H', name: 'Avenue H', position: { lat: 34.7161, lng: -118.1494 } },
		'avenue-i': { label: 'I', name: 'Avenue I', position: { lat: 34.7046, lng: -118.147 } },
	};
	let map, bounds;
	let mapInitialized = false;
	const markers = {};
	let split = 50;
	let gesture = null;
	let settleCleanup = null;
	let suppressedClick = false;
	let updateFrame = null;

	const setSplit = (value) => {
		split = value;
		dock.style.setProperty('--locations-split', value + '%');
	};
	const selectedSplit = () => dock.dataset.selected === 'avenue-h' ? 100 : dock.dataset.selected === 'avenue-i' ? 0 : 50;
	const clearInteraction = () => {
		const previous = gesture;
		gesture = null;
		if (previous && dock.hasPointerCapture(previous.pointerId)) dock.releasePointerCapture(previous.pointerId);
		if (settleCleanup) settleCleanup();
		dock.classList.remove('is-dragging');
		delete dock.dataset.splitPreview;
		dock.style.removeProperty('--locations-preview-height');
		dock.style.removeProperty('--locations-tile-width');
		setSplit(selectedSplit());
	};

	const markerIcon = (isSelected) => ({
		fillColor: '#b21f2d',
		fillOpacity: isSelected ? 1 : 0.9,
		path: window.google.maps.SymbolPath.CIRCLE,
		scale: isSelected ? 18 : 14,
		strokeColor: '#f4c842',
		strokeWeight: isSelected ? 5 : 4,
	});

	const updateMapSelection = (locationKey = '') => {
		Object.entries(markers).forEach(([key, marker]) => marker.setIcon(markerIcon(key === locationKey)));
		if (!map || !locationKey) {
			if (map && bounds) map.fitBounds(bounds, 64);
			return;
		}
		map.panTo(locations[locationKey].position);
		map.setZoom(15);
	};

	const selectLocation = (locationKey) => {
		if (!locations[locationKey]) return;
		clearInteraction();
		dock.dataset.selected = locationKey;
		setSplit(locationKey === 'avenue-h' ? 100 : 0);
		choices.forEach((choice) => {
			const isSelected = choice.dataset.locationKey === locationKey;
			choice.setAttribute('aria-pressed', String(isSelected));
			choice.setAttribute('aria-expanded', String(isSelected));
		});
		panels.forEach((panel) => { panel.hidden = panel.dataset.locationPanel !== locationKey; });
		updateMapSelection(locationKey);
		scheduleDockUpdate();
	};

	const showBothLocations = () => {
		clearInteraction();
		delete dock.dataset.selected;
		setSplit(50);
		choices.forEach((choice) => {
			choice.setAttribute('aria-pressed', 'false');
			choice.setAttribute('aria-expanded', 'false');
		});
		panels.forEach((panel) => { panel.hidden = true; });
		updateMapSelection();
		scheduleDockUpdate();
	};

	const initializeMap = () => {
		if (mapInitialized) return;
		mapInitialized = true;
		if (!window.google || !window.google.maps) return;

		map = new window.google.maps.Map(mapElement, {
			backgroundColor: '#f7f2e8',
			clickableIcons: false,
			disableDefaultUI: true,
			gestureHandling: 'cooperative',
			mapTypeControl: false,
			streetViewControl: false,
			zoomControl: true,
			styles: [
				{ featureType: 'poi.business', stylers: [{ visibility: 'off' }] },
				{ featureType: 'poi.attraction', stylers: [{ visibility: 'off' }] },
				{ featureType: 'transit', stylers: [{ visibility: 'off' }] },
				{ featureType: 'landscape', stylers: [{ color: '#f4f0e7' }] },
				{ featureType: 'road', elementType: 'geometry', stylers: [{ color: '#ffffff' }] },
				{ featureType: 'road', elementType: 'labels.text.fill', stylers: [{ color: '#6b655d' }] },
			],
		});
		bounds = new window.google.maps.LatLngBounds();
		Object.entries(locations).forEach(([key, location]) => {
			const marker = new window.google.maps.Marker({
				icon: markerIcon(false),
				label: { color: '#ffffff', fontSize: '12px', fontWeight: '800', text: location.label },
				map,
				position: location.position,
				title: `Select ${location.name}`,
			});
			marker.addListener('click', () => selectLocation(key));
			markers[key] = marker;
			bounds.extend(location.position);
		});
		mapElement.classList.add('is-ready');
		updateMapSelection(dock.dataset.selected);
		scheduleDockUpdate();
	};


	// Sole visibility authority. The runway belongs to Locations, never its sibling.
	const updateLocationsDock = () => {
		const locationsRect = section.getBoundingClientRect();
		const mapRect = mapElement.getBoundingClientRect();
		const bottomOffset = parseFloat(getComputedStyle(dock).bottom) || 16;
		const exitDistance = dock.offsetHeight + bottomOffset + 120;
		const visible = mapRect.top <= window.innerHeight * 0.7
			&& locationsRect.bottom > window.innerHeight + exitDistance;
		if (!visible && (gesture || settleCleanup)) clearInteraction();
		dock.classList.toggle('is-visible', visible);
		dock.inert = !visible;
		dock.setAttribute('aria-hidden', String(!visible));
		// A fast fling can cross any distance during a timed fade. Finish immediately
		// inside the runway in that case, before the next section can be exposed.
		const atBoundary = locationsRect.bottom <= window.innerHeight + 32;
		dock.style.visibility = atBoundary ? 'hidden' : '';
		dock.style.transitionDuration = atBoundary ? '0s' : '';
		if (!mapInitialized && mapRect.top < window.innerHeight && mapRect.bottom > 0) initializeMap();
	};
	const scheduleDockUpdate = () => {
		if (updateFrame !== null) return;
		updateFrame = window.requestAnimationFrame(() => {
			updateFrame = null;
			updateLocationsDock();
		});
	};

	const settle = (state, commit) => {
		const target = commit ? (state.closing ? 50 : state.key === 'avenue-h' ? 100 : 0) : state.startSplit;
		const focusWasInside = dock.contains(document.activeElement);
		const finish = () => {
			clearInteraction();
			if (commit) {
				if (state.closing) showBothLocations();
				else selectLocation(state.key);
				if (focusWasInside) {
					const destination = state.closing
						? choices.find(button => button.dataset.locationKey === state.key)
						: panels.find(panel => panel.dataset.locationPanel === state.key).querySelector('a');
					destination.focus({ preventScroll: true });
				}
			}
			scheduleDockUpdate();
		};
		if (reducedMotion.matches || split === target) { finish(); return; }
		const end = event => {
			if (event.target === splitGrid && event.propertyName === 'grid-template-columns') finish();
		};
		const timer = window.setTimeout(finish, 280);
		settleCleanup = () => {
			window.clearTimeout(timer);
			splitGrid.removeEventListener('transitionend', end);
			settleCleanup = null;
		};
		splitGrid.addEventListener('transitionend', end);
		// Flush the last direct width before enabling the release-only transition.
		splitGrid.getBoundingClientRect();
		dock.classList.remove('is-dragging');
		setSplit(target);
	};
	dock.addEventListener('pointerdown', event => {
		// A fresh physical tap must never inherit click suppression from an old drag.
		suppressedClick = false;
		if (!event.isPrimary || event.button !== 0 || gesture || settleCleanup || dock.inert) return;
		if (event.target.closest('a, [data-locations-reset]')) return;
		const surface = event.target.closest('[data-location-key], [data-location-panel]');
		if (!surface) return;
		const key = dock.dataset.selected || surface.dataset.locationKey;
		if (!key) return;
		const width = dock.clientWidth;
		gesture = {
			pointerId: event.pointerId, key, closing: Boolean(dock.dataset.selected),
			startX: event.clientX, startY: event.clientY, startSplit: split,
			startSplitPx: split / 100 * width, width, dragging: false,
			direction: (key === 'avenue-h' ? 1 : -1) * (dock.dataset.selected ? -1 : 1),
			lastX: event.clientX, lastTime: event.timeStamp, velocity: 0,
		};
	});
	const movePointer = event => {
		const state = gesture;
		if (!state || event.pointerId !== state.pointerId) return;
		const dx = event.clientX - state.startX;
		const dy = event.clientY - state.startY;
		if (!state.dragging) {
			if (Math.abs(dy) >= 8 && Math.abs(dy) >= Math.abs(dx)) { clearInteraction(); return; }
			if (Math.abs(dx) < 8) return;
			if (Math.abs(dx) <= Math.abs(dy) || dx * state.direction <= 0) { clearInteraction(); return; }
			state.dragging = true;
			dock.style.setProperty('--locations-preview-height', dock.clientHeight + 'px');
			dock.style.setProperty('--locations-tile-width', state.width / 2 + 'px');
			dock.dataset.splitPreview = state.key;
			dock.classList.add('is-dragging');
			dock.setPointerCapture(event.pointerId);
		}
		event.preventDefault();
		const splitPx = state.startSplitPx + event.clientX - state.startX;
		const percent = splitPx / state.width * 100;
		setSplit(state.key === 'avenue-h' ? Math.max(50, Math.min(100, percent)) : Math.max(0, Math.min(50, percent)));
		const elapsed = event.timeStamp - state.lastTime;
		if (elapsed > 0 && event.clientX !== state.lastX) {
			state.velocity = (event.clientX - state.lastX) * state.direction / elapsed;
			state.lastX = event.clientX;
			state.lastTime = event.timeStamp;
		}
	};
	const finishPointer = (event, cancelled = false) => {
		const state = gesture;
		if (!state || event.pointerId !== state.pointerId) return;
		if (!state.dragging) { clearInteraction(); return; }
		if (!cancelled) movePointer(event);
		gesture = null; // Release must not re-enter through lostpointercapture.
		suppressedClick = true;
		if (dock.hasPointerCapture(state.pointerId)) dock.releasePointerCapture(state.pointerId);
		const crossed = state.closing ? (state.key === 'avenue-h' ? split <= 75 : split >= 25)
			: (state.key === 'avenue-h' ? split >= 75 : split <= 25);
		const fast = event.timeStamp - state.lastTime < 100 && state.velocity >= 0.45
			&& (event.clientX - state.startX) * state.direction >= 24;
		settle(state, !cancelled && (crossed || fast));
	};
	dock.addEventListener('pointermove', movePointer);
	dock.addEventListener('pointerup', event => finishPointer(event));
	dock.addEventListener('pointercancel', event => finishPointer(event, true));
	dock.addEventListener('lostpointercapture', event => finishPointer(event, true));
	dock.addEventListener('click', event => {
		if (!suppressedClick || event.detail === 0) return;
		suppressedClick = false;
		event.preventDefault();
		event.stopPropagation();
	}, true);
	choices.forEach(button => button.addEventListener('click', () => selectLocation(button.dataset.locationKey)));
	resetButtons.forEach(button => button.addEventListener('click', showBothLocations));

	window.addEventListener('scroll', scheduleDockUpdate, { passive: true });
	window.addEventListener('resize', () => { clearInteraction(); scheduleDockUpdate(); });
	window.addEventListener('pageshow', scheduleDockUpdate);
	window.addEventListener('load', scheduleDockUpdate);
	reducedMotion.addEventListener('change', () => { clearInteraction(); scheduleDockUpdate(); });
	setSplit(50);
	updateLocationsDock();
});
