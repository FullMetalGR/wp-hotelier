/* WebHotelier calendar widget. Vanilla JS. */
( function () {
	'use strict';

	var CFG = window.WH_Public || { root: '', nonce: '' };

	/* Encode a value as text so it can never inject markup (same approach as
	 * public.js): assign to textContent and read back the escaped innerHTML. */
	function esc( s ) {
		var d = document.createElement( 'div' );
		d.textContent = s == null ? '' : String( s );
		return d.innerHTML;
	}

	function api( path, params ) {
		var url = CFG.root.replace( /\/$/, '' ) + path;
		var qs = Object.keys( params || {} )
			.filter( function ( k ) {
				return params[ k ] !== '' && params[ k ] != null;
			} )
			.map( function ( k ) {
				return encodeURIComponent( k ) + '=' + encodeURIComponent( params[ k ] );
			} )
			.join( '&' );
		if ( qs ) {
			url += ( url.indexOf( '?' ) === -1 ? '?' : '&' ) + qs;
		}
		return fetch( url, {
			headers: { 'X-WP-Nonce': CFG.nonce, Accept: 'application/json' },
			credentials: 'same-origin'
		} ).then( function ( r ) {
			return r.json();
		} ).then( function ( b ) {
			return b.data || b;
		} );
	}

	/* Normalise the days payload (live list or legacy map) into an array of
	 * { date, available, price } rows. */
	function normalise( days ) {
		var rows = [];
		if ( Array.isArray( days ) ) {
			days.forEach( function ( d ) {
				if ( ! d ) {
					return;
				}
				rows.push( {
					date: d.date != null ? d.date : '',
					available: ( ( parseInt( d.allot, 10 ) || 0 ) > 0 ) && !! d.checkin,
					price: d.price
				} );
			} );
		} else if ( days && typeof days === 'object' ) {
			Object.keys( days ).forEach( function ( date ) {
				var d = days[ date ] || {};
				var avail;
				if ( 'allot' in d || 'checkin' in d ) {
					avail = ( ( parseInt( d.allot, 10 ) || 0 ) > 0 ) && !! d.checkin;
				} else {
					avail = !! d.available;
				}
				rows.push( {
					date: d.date != null ? d.date : date,
					available: avail,
					price: d.price
				} );
			} );
		}
		return rows;
	}

	function render( el, data ) {
		var rows = normalise( data && data.days );
		var currency = ( data && data.currency ) || '';
		var grid = el.querySelector( '.wh-calendar__grid' );
		if ( ! grid ) {
			grid = document.createElement( 'div' );
			grid.className = 'wh-calendar__grid';
			el.appendChild( grid );
		}
		var html = '';
		rows.forEach( function ( row ) {
			var cls = row.available ? 'wh-calendar__day--available' : 'wh-calendar__day--unavailable';
			/* Every interpolated API value is passed through esc(). */
			html += '<div class="wh-calendar__day ' + cls + '" data-date="' + esc( row.date ) + '">';
			html += '<span class="wh-calendar__date">' + esc( row.date ) + '</span>';
			if ( row.available && row.price != null ) {
				html += '<span class="wh-calendar__price">' + esc( currency + ' ' + row.price ) + '</span>';
			}
			html += '</div>';
		} );
		grid.innerHTML = html;
	}

	function init() {
		var cals = document.querySelectorAll( '[data-wh-calendar]' );
		Array.prototype.forEach.call( cals, function ( el ) {
			// SSR already rendered the initial window; this refresh is optional and
			// only runs when the element requests live data.
			if ( el.getAttribute( 'data-live' ) !== '1' ) {
				return;
			}
			var params = {
				property: el.getAttribute( 'data-property' ) || '',
				fromd: el.getAttribute( 'data-fromd' ) || '',
				tod: el.getAttribute( 'data-tod' ) || ''
			};
			api( '/calendar', params ).then( function ( data ) {
				render( el, data );
			} ).catch( function () {} );
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
