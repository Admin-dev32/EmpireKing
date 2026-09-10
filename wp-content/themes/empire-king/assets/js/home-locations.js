document.addEventListener('DOMContentLoaded', () => {
	const mapElement = document.querySelector('#home-locations-map');
	if (!mapElement || !window.google?.maps) return;

	const position = { lat: 34.7161, lng: -118.1494 };
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

	const markerOptions = {
		map,
		position,
		title: 'Empire King Burger — Avenue H',
	};
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
});
