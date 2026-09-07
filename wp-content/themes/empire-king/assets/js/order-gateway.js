document.addEventListener('DOMContentLoaded', () => {
	const gateway = document.querySelector('.order-gateway');
	const dialog = document.querySelector('#order-location-selector');
	const closeButton = document.querySelector('.order-location-dialog__close');
	const dialogTitle = document.querySelector('#order-location-dialog-title');
	const dialogDescription = document.querySelector('#order-location-dialog-description');
	const tabs = Array.from(document.querySelectorAll('[role="tab"][data-order-mode]'));
	const panels = Array.from(document.querySelectorAll('[role="tabpanel"]'));
	const openButtons = Array.from(document.querySelectorAll('[data-open-location-selector]'));
	const locationButtons = Array.from(document.querySelectorAll('.order-location-option'));
	const locationControls = Array.from(document.querySelectorAll('.order-location-control'));
	const orderButtons = Array.from(document.querySelectorAll('[data-order-submit]'));
	const blogOrderButtons = Array.from(document.querySelectorAll('[data-blog-direct-order]'));
	const mapContainer = document.querySelector('#order-location-map');
	const status = document.querySelector('.order-gateway__status');

	if (!dialog || !closeButton || !dialogTitle || !dialogDescription || typeof dialog.showModal !== 'function' || (!gateway && !blogOrderButtons.length)) {
		return;
	}

	let activeMode = 'pickup';
	let selectedLocation = '';
	let isClosing = false;
	let closeTimer;
	let exitAnimationHandler;
	let mapInstance;
	let mapBounds;
	let isProceedingToOrder = false;
	let selectionIntent = 'home';
	const orderRoutes = window.empireKingOrderGateway && window.empireKingOrderGateway.routes ? window.empireKingOrderGateway.routes : {};
	const locations = {
		'Avenue H': '1036 W Avenue H, Lancaster, CA 93534',
		'Avenue I': '810 W Ave I, Lancaster, CA',
	};
	const mapLocations = [
		{ name: 'Avenue H', label: 'H', position: { lat: 34.7161, lng: -118.1494 } },
		{ name: 'Avenue I', label: 'I', position: { lat: 34.7046, lng: -118.1470 } },
	];

	const initializeMap = () => {
		if (!mapContainer) return;

		if (!window.google || !window.google.maps) {
			mapContainer.hidden = true;
			return;
		}

		if (mapInstance) {
			window.google.maps.event.trigger(mapInstance, 'resize');
			mapInstance.fitBounds(mapBounds, 36);
			return;
		}

		mapInstance = new window.google.maps.Map(mapContainer, {
			backgroundColor: '#f7f2e8',
			clickableIcons: false,
			disableDefaultUI: true,
			gestureHandling: 'cooperative',
			mapTypeControl: false,
			streetViewControl: false,
			styles: [
				{ featureType: 'poi.business', stylers: [{ visibility: 'off' }] },
				{ featureType: 'poi.attraction', stylers: [{ visibility: 'off' }] },
				{ featureType: 'transit', stylers: [{ visibility: 'off' }] },
				{ featureType: 'landscape', stylers: [{ color: '#f4f0e7' }] },
				{ featureType: 'road', elementType: 'geometry', stylers: [{ color: '#ffffff' }] },
				{ featureType: 'road', elementType: 'labels.text.fill', stylers: [{ color: '#6b655d' }] },
			],
			zoomControl: true,
		});

		mapBounds = new window.google.maps.LatLngBounds();
		mapLocations.forEach((location) => {
			const marker = new window.google.maps.Marker({
				icon: {
					fillColor: '#b21f2d',
					fillOpacity: 1,
					path: window.google.maps.SymbolPath.CIRCLE,
					scale: 13,
					strokeColor: '#ffffff',
					strokeWeight: 3,
				},
				label: { color: '#ffffff', fontSize: '12px', fontWeight: '800', text: location.label },
				map: mapInstance,
				position: location.position,
				title: `Select ${location.name}`,
			});
			marker.addListener('click', () => selectLocation(location.name));
			mapBounds.extend(location.position);
		});
		mapInstance.fitBounds(mapBounds, 36);
	};

	const modeLabel = () => (activeMode === 'delivery' ? 'Delivery' : 'Pickup');
	const setActiveTab = (mode, shouldFocus = false) => {
		activeMode = mode;
		tabs.forEach((tab) => {
			const isActive = tab.dataset.orderMode === mode;
			tab.setAttribute('aria-selected', String(isActive));
			tab.tabIndex = isActive ? 0 : -1;
			if (isActive && shouldFocus) tab.focus();
		});
		panels.forEach((panel) => { panel.hidden = panel.getAttribute('aria-labelledby') !== `${mode}-tab`; });
		updateSelection();
	};

	const updateSelection = () => {
		locationButtons.forEach((button) => button.setAttribute('aria-pressed', String(button.dataset.location === selectedLocation)));
		locationControls.forEach((control) => {
			const isSelected = Boolean(selectedLocation);
			const unselected = control.querySelector('.order-location-control__unselected');
			const selected = control.querySelector('.order-location-control__selected');
			const change = control.querySelector('.order-location-control__change');
			unselected.hidden = isSelected;
			selected.hidden = !isSelected;
			change.hidden = !isSelected;
			if (isSelected) {
				selected.querySelector('.order-location-control__mode').textContent = control.dataset.openLocationSelector === 'delivery' ? 'Delivery From' : 'Pickup From';
				selected.querySelector('strong').textContent = selectedLocation;
				selected.querySelector('.order-location-control__address').textContent = locations[selectedLocation];
			}
		});
	};

	const setOrderStatus = (message = '') => {
		if (status) status.textContent = message;
	};

	const clearCloseSequence = () => {
		window.clearTimeout(closeTimer);
		if (exitAnimationHandler) dialog.removeEventListener('animationend', exitAnimationHandler);
		exitAnimationHandler = undefined;
		isClosing = false;
		dialog.classList.remove('is-closing');
	};

	const finishClose = () => {
		if (dialog.open) dialog.close();
		else clearCloseSequence();
	};

	const closeSelector = (immediately = false) => {
		if (!dialog.open || isClosing) return;
		if (immediately || window.matchMedia('(prefers-reduced-motion: reduce)').matches) { finishClose(); return; }
		isClosing = true;
		dialog.classList.add('is-closing');
		exitAnimationHandler = (event) => { if (event.target === dialog && event.animationName === 'order-dialog-exit') finishClose(); };
		dialog.addEventListener('animationend', exitAnimationHandler);
		closeTimer = window.setTimeout(finishClose, 280);
	};

	const selectLocation = (locationName) => {
		selectedLocation = locationName;
		setOrderStatus();
		updateSelection();
		if (selectionIntent === 'blog-direct-order') {
			const pickupUrl = orderRoutes[locationName] && orderRoutes[locationName].pickup;
			if (pickupUrl && !isProceedingToOrder) {
				isProceedingToOrder = true;
				window.location.assign(pickupUrl);
			}
			return;
		}
		closeSelector();
	};

	const submitOrder = (mode) => {
		if (!selectedLocation) {
			openSelector(mode);
			return;
		}

		if (mode === 'delivery') {
			setOrderStatus(`Delivery ordering for ${selectedLocation} is not configured in this development prototype.`);
			return;
		}

		const pickupUrl = orderRoutes[selectedLocation] && orderRoutes[selectedLocation].pickup;
		if (!pickupUrl) {
			setOrderStatus(`Pickup ordering for ${selectedLocation} is not configured in this development prototype.`);
			return;
		}

		if (isProceedingToOrder) return;
		isProceedingToOrder = true;
		window.location.assign(pickupUrl);
	};

	const openSelector = (mode, intent = 'home') => {
		if (dialog.open) return;
		selectionIntent = intent;
		setActiveTab(mode);
		clearCloseSequence();
		dialogTitle.textContent = `Choose Your ${modeLabel()} Location`;
		dialogDescription.textContent = "Choose the location you'd like to order from.";
		updateSelection();
		dialog.showModal();
		window.requestAnimationFrame(initializeMap);
	};

	tabs.forEach((tab, index) => {
		tab.addEventListener('click', () => setActiveTab(tab.dataset.orderMode));
		tab.addEventListener('keydown', (event) => {
			if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
			event.preventDefault();
			const nextIndex = event.key === 'Home' ? 0 : event.key === 'End' ? tabs.length - 1 : (index + (event.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length;
			setActiveTab(tabs[nextIndex].dataset.orderMode, true);
		});
	});
	openButtons.forEach((button) => button.addEventListener('click', () => openSelector(button.dataset.openLocationSelector)));
	orderButtons.forEach((button) => button.addEventListener('click', () => submitOrder(button.dataset.orderSubmit)));
	blogOrderButtons.forEach((button) => button.addEventListener('click', (event) => {
		event.preventDefault();
		openSelector('pickup', 'blog-direct-order');
	}));
	closeButton.addEventListener('click', () => closeSelector());
	dialog.addEventListener('cancel', (event) => { event.preventDefault(); closeSelector(); });
	dialog.addEventListener('close', () => {
		selectionIntent = 'home';
		clearCloseSequence();
	});
	locationButtons.forEach((button) => button.addEventListener('click', () => selectLocation(button.dataset.location)));
});
