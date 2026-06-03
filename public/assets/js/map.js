/* WebHotelier Leaflet map initializer. Vanilla JS. */
( function () {
	'use strict';

	function initMap( el ) {
		if ( typeof window.L === 'undefined' || el.getAttribute( 'data-provider' ) !== 'leaflet' ) {
			return; // No Leaflet (or different provider) — leave SSR fallback.
		}
		var markers;
		try {
			markers = JSON.parse( el.getAttribute( 'data-markers' ) || '[]' );
		} catch ( e ) {
			markers = [];
		}
		if ( ! markers.length ) {
			return;
		}
		var zoom = parseInt( el.getAttribute( 'data-zoom' ), 10 ) || 13;
		var first = markers[ 0 ];
		var map = window.L.map( el ).setView( [ first.lat, first.lon ], zoom );
		window.L.tileLayer( 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
			attribution: '&copy; OpenStreetMap contributors',
			maxZoom: 19
		} ).addTo( map );
		markers.forEach( function ( m ) {
			var mk = window.L.marker( [ m.lat, m.lon ] ).addTo( map );
			if ( m.label ) {
				mk.bindPopup( m.label );
			}
		} );
	}

	function init() {
		var maps = document.querySelectorAll( '[data-wh-map]' );
		Array.prototype.forEach.call( maps, initMap );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
