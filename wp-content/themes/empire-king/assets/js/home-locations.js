document.addEventListener('DOMContentLoaded', () => {
	const stage = document.querySelector('[data-locations-stage]');
	const scene = document.querySelector('[data-locations-scene]');
	const mapElement = document.querySelector('#home-locations-map');
	const dock = document.querySelector('[data-locations-dock]');
	const choices = Array.from(document.querySelectorAll('[data-location-key]'));
	const panels = Array.from(document.querySelectorAll('[data-location-panel]'));
	const resetButtons = Array.from(document.querySelectorAll('[data-locations-reset]'));

	if (!scene || !stage || !mapElement || !dock || !choices.length) return;

	const locations = {
		'avenue-h': { label: 'H', name: 'Avenue H', position: { lat: 34.7161, lng: -118.1494 } },
		'avenue-i': { label: 'I', name: 'Avenue I', position: { lat: 34.7046, lng: -118.147 } },
	};
	let map;
	let bounds;
	let mapInitialized = false;
	let dragState;
	let suppressedPointer;
	let settleFrame;
	const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
	const markers = {};

	const setDockAvailability = (phase, opacity = 0) => {
		const isActive = phase === 'active';
		dock.dataset.sceneState = phase;
		dock.style.setProperty('--locations-opacity', String(opacity));
		dock.classList.toggle('is-active', isActive);
		dock.setAttribute('aria-hidden', String(!isActive));
		dock.inert = !isActive;
		if (!isActive) abortReveal();
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
		abortReveal();
		dock.dataset.selected = locationKey;
		setSplit(locationKey === 'avenue-h' ? 100 : 0);
		choices.forEach((choice) => {
			const isSelected = choice.dataset.locationKey === locationKey;
			choice.setAttribute('aria-pressed', String(isSelected));
			choice.setAttribute('aria-expanded', String(isSelected));
		});
		panels.forEach((panel) => { panel.hidden = panel.dataset.locationPanel !== locationKey; });
		updateMapSelection(locationKey);
	};

	const showBothLocations = () => {
		abortReveal();
		delete dock.dataset.selected;
		setSplit(50);
		choices.forEach((choice) => {
			choice.setAttribute('aria-pressed', 'false');
			choice.setAttribute('aria-expanded', 'false');
		});
		panels.forEach((panel) => { panel.hidden = true; });
		updateMapSelection();
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
		showBothLocations();
	};

	choices.forEach((choice) => choice.addEventListener('click', () => selectLocation(choice.dataset.locationKey)));
	resetButtons.forEach((button) => button.addEventListener('click', showBothLocations));


	// One physical divider; selection remains owned by the existing canonical functions.
	let split = 50;
	const setSplit = (value) => {
		split = Math.max(0, Math.min(100, value));
		dock.style.setProperty('--locations-split', split + '%');
	};
	const releaseCapture = (state) => {
		if (state.surface.hasPointerCapture(state.pointerId)) state.surface.releasePointerCapture(state.pointerId);
	};
	const abortReveal = () => {
		window.cancelAnimationFrame(settleFrame);
		const state = dragState;
		dragState = undefined;
		if (state) releaseCapture(state);
		dock.classList.remove('is-revealing', 'is-dragging');
		setSplit(dock.dataset.selected === 'avenue-h' ? 100 : dock.dataset.selected === 'avenue-i' ? 0 : 50);
		dock.style.removeProperty('--locations-reveal-height');
		dock.style.removeProperty('--locations-copy-width');
	};
	const settle = (state, commit) => {
		dock.classList.remove('is-dragging');
		const from = split;
		const to = commit ? (state.reset ? 50 : state.locationKey === 'avenue-h' ? 100 : 0) : state.initialSplit;
		const started = performance.now();
		const finish = () => {
			abortReveal();
			if (commit) {
				if (state.reset) showBothLocations();
				else selectLocation(state.locationKey);
			}
			if (dock.contains(document.activeElement)) {
				const focusTarget = commit
					? (state.reset ? choices.find(c => c.dataset.locationKey === state.locationKey) : panels.find(p => p.dataset.locationPanel === state.locationKey).querySelector('a'))
					: state.surface;
				focusTarget.focus({ preventScroll: true });
			}
		};
		if (reducedMotion.matches) { finish(); return; }
		const tick = (now) => {
			const t = Math.min(1, (now - started) / 240);
			setSplit(from + (to - from) * (1 - Math.pow(1 - t, 3)));
			if (t < 1) settleFrame = window.requestAnimationFrame(tick);
			else finish();
		};
		settleFrame = window.requestAnimationFrame(tick);
	};
	const startDrag = (event) => {
		if (dragState || !event.isPrimary || event.button !== 0 || !dock.classList.contains('is-active')) return;
		if (event.target.closest('a, [data-locations-reset]')) return;
		suppressedPointer = undefined;
		const surface = event.currentTarget;
		const selected = dock.dataset.selected;
		const key = selected || surface.dataset.locationKey;
		if (!key || (selected && surface.dataset.locationPanel !== selected)) return;
		const rect = dock.getBoundingClientRect();
		dragState = {
			surface, pointerId: event.pointerId, locationKey: key, reset: Boolean(selected),
			direction: (key === 'avenue-h' ? 1 : -1) * (selected ? -1 : 1),
			startX: event.clientX, startY: event.clientY, initialSplit: split, horizontal: false,
			rect, grabOffset: event.clientX - (rect.left + rect.width * split / 100),
			samples: [{ x: event.clientX, time: event.timeStamp }],
		};
		surface.setPointerCapture(event.pointerId);
	};
	const moveDrag = (event) => {
		const state = dragState;
		if (!state || state.pointerId !== event.pointerId) return;
		const dx = event.clientX - state.startX;
		const dy = event.clientY - state.startY;
		if (!state.horizontal) {
			if (Math.abs(dy) >= 8 && Math.abs(dy) >= Math.abs(dx)) { abortReveal(); return; }
			if (Math.abs(dx) < 8) return;
			if (Math.abs(dy) >= Math.abs(dx) || dx * state.direction <= 0) { abortReveal(); return; }
			state.horizontal = true;
			dock.style.setProperty('--locations-reveal-height', dock.clientHeight + 'px');
			dock.style.setProperty('--locations-copy-width', state.rect.width / 2 + 'px');
			dock.classList.add('is-revealing', 'is-dragging');
		}
		event.preventDefault();
		// Absolute pointer X, with a fixed grab offset so off-divider grabs never jump.
		const pointerSplit = (event.clientX - state.grabOffset - state.rect.left) / state.rect.width * 100;
		setSplit(state.locationKey === 'avenue-h'
			? Math.max(50, Math.min(100, pointerSplit))
			: Math.max(0, Math.min(50, pointerSplit)));
		state.samples.push({ x: event.clientX, time: event.timeStamp });
		while (state.samples.length > 2 && event.timeStamp - state.samples[0].time > 100) state.samples.shift();
	};
	const finishDrag = (event, cancelled = false) => {
		const state = dragState;
		if (!state || state.pointerId !== event.pointerId) return;
		if (!state.horizontal) { abortReveal(); return; }
		if (!cancelled) moveDrag(event);
		suppressedPointer = state.pointerId;
		releaseCapture(state);
		const sample = state.samples[0];
		const velocity = (event.clientX - sample.x) * state.direction / Math.max(1, event.timeStamp - sample.time);
		const distance = Math.max(0, (event.clientX - state.startX) * state.direction);
		const crossed = state.reset
			? (state.locationKey === 'avenue-h' ? split <= 75 : split >= 25)
			: (state.locationKey === 'avenue-h' ? split >= 75 : split <= 25);
		const commit = !cancelled && (crossed || (distance >= 24 && velocity >= 0.45));
		settle(state, commit);
	};
	choices.concat(panels).forEach((surface) => {
		surface.addEventListener('pointerdown', startDrag);
		surface.addEventListener('pointermove', moveDrag);
		surface.addEventListener('pointerup', event => finishDrag(event));
		surface.addEventListener('pointercancel', event => finishDrag(event, true));
	});
	dock.addEventListener('click', (event) => {
		if (event.detail === 0 || suppressedPointer === undefined) return;
		if (event.pointerId !== undefined && event.pointerId !== suppressedPointer) return;
		event.preventDefault();
		event.stopPropagation();
		suppressedPointer = undefined;
	}, true);


	// One controller samples the bounded sticky scene, never the next section.
	// Exit opacity follows the internal runway, so a fast scroll cannot outrun a timer.
	let sceneFrame;
	const updateScene = () => {
		sceneFrame = undefined;
		const rect = scene.getBoundingClientRect();
		const top = parseFloat(getComputedStyle(stage).top) || 0;
		const remaining = rect.bottom - top - stage.offsetHeight;
		const pinned = rect.top <= top && remaining > 0;
		// Also reserve viewport space below shorter desktop stages.
		const exitRemaining = rect.bottom - Math.max(window.innerHeight, top + stage.offsetHeight);
		const opacity = Math.max(0, Math.min(1, (exitRemaining - 64) / 160));
		const phase = !pinned || opacity === 0 ? 'inactive' : opacity < 1 ? 'exiting' : 'active';
		setDockAvailability(phase, reducedMotion.matches && phase === 'exiting' ? 0 : opacity);
		if (rect.top < window.innerHeight && rect.bottom > 0) initializeMap();
	};
	const scheduleScene = () => {
		if (sceneFrame === undefined) sceneFrame = window.requestAnimationFrame(updateScene);
	};
	const sizeScene = () => {
		const header = document.querySelector('.site-header');
		const top = header ? header.getBoundingClientRect().height + 8 : 8;
		scene.style.setProperty('--locations-top', top + 'px');
		scheduleScene();
	};
	window.addEventListener('scroll', scheduleScene, { passive: true });
	window.addEventListener('resize', sizeScene);
	window.addEventListener('pageshow', sizeScene);
	reducedMotion.addEventListener('change', scheduleScene);
	setDockAvailability('inactive');
	sizeScene();
});
