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

	new window.google.maps.Marker({
		icon: {
			fillColor: '#b21f2d',
			fillOpacity: 1,
			path: window.google.maps.SymbolPath.CIRCLE,
			scale: 16,
			strokeColor: '#f4c842',
			strokeWeight: 4,
		},
		label: { color: '#ffffff', fontSize: '12px', fontWeight: '800', text: 'H' },
		map,
		position,
		title: 'Empire King Burger — Avenue H',
	});

	mapElement.classList.add('is-ready');
});
