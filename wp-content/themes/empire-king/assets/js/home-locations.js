document.addEventListener('DOMContentLoaded', () => {
	const mapElement = document.querySelector('#home-locations-map');
	if (!mapElement || !window.google?.maps) return;

	const location = window.empireKingLocation;
	if (!location) return;

	const renderMap = (position) => {
		const map = new window.google.maps.Map(mapElement, {
			backgroundColor: '#f7f2e8',
			center: position,
			clickableIcons: false,
			disableDefaultUI: true,
			gestureHandling: 'cooperative',
			mapTypeControl: false,
			streetViewControl: false,
			zoom: 15,
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

		const markerOptions = { map, position, title: location.label };
		const markerLogoUrl = mapElement.dataset.markerLogoUrl;
		if (markerLogoUrl) {
			const markerSize = 60;
			markerOptions.icon = {
				url: markerLogoUrl,
				scaledSize: new window.google.maps.Size(markerSize, markerSize),
				anchor: new window.google.maps.Point(markerSize / 2, markerSize / 2),
			};
		}
		new window.google.maps.Marker(markerOptions);
		mapElement.classList.add('is-ready');
	};

	const latitude = Number(location.latitude);
	const longitude = Number(location.longitude);
	const hasCoordinates = String(location.latitude).trim() !== ''
		&& String(location.longitude).trim() !== ''
		&& Number.isFinite(latitude)
		&& Number.isFinite(longitude)
		&& latitude >= -90 && latitude <= 90
		&& longitude >= -180 && longitude <= 180;

	if (hasCoordinates) {
		renderMap({ lat: latitude, lng: longitude });
		return;
	}

	if (!location.address) return;
	new window.google.maps.Geocoder().geocode({ address: location.address }, (results, status) => {
		if (status !== 'OK' || !results?.[0]) return;
		renderMap(results[0].geometry.location);
	});
});
