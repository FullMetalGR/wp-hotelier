/* WebHotelier public front-end. Vanilla JS, no dependencies. */
( function () {
	'use strict';

	var CFG = window.WH_Public || { root: '', nonce: '', i18n: {} };
	var I18N = CFG.i18n || {};

	function api( path, params ) {
		var url = CFG.root.replace( /\/$/, '' ) + path;
		if ( params ) {
			var qs = Object.keys( params )
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
		}
		return fetch( url, {
			headers: { 'X-WP-Nonce': CFG.nonce, Accept: 'application/json' },
			credentials: 'same-origin'
		} ).then( function ( r ) {
			return r.json().then( function ( body ) {
				if ( ! r.ok ) {
					throw body;
				}
				return body.data || body;
			} );
		} );
	}

	function esc( s ) {
		var d = document.createElement( 'div' );
		d.textContent = s == null ? '' : String( s );
		return d.innerHTML;
	}

	function rateCard( rate ) {
		var room = esc( rate.room || rate.type || '' );
		var name = esc( rate.rate || '' );
		var engine = rate.url && rate.url.engine ? rate.url.engine : '';
		var price = '';
		if ( rate.pricing && rate.pricing.price != null ) {
			price = esc( ( rate.pricing.currency || '' ) + ' ' + rate.pricing.price );
		}
		var photo = rate.url && rate.url.photo ? rate.url.photo : '';
		var html = '<article class="wh-rate-card">';
		if ( photo ) {
			html += '<div class="wh-rate-card__media"><img src="' + esc( photo ) + '" alt="" loading="lazy"></div>';
		}
		html += '<div class="wh-rate-card__body"><h3 class="wh-rate-card__room">' + room + '</h3>';
		html += '<p class="wh-rate-card__rate">' + name + '</p></div>';
		html += '<div class="wh-rate-card__footer">';
		if ( price ) {
			html += '<span class="wh-rate-card__price">' + price + '</span>';
		}
		if ( engine ) {
			html += '<a class="wh-btn wh-rate-card__book" href="' + esc( engine ) + '" rel="noopener" target="_blank">Book</a>';
		}
		html += '</div></article>';
		return html;
	}

	function renderAvailability( container, data ) {
		var rates = ( data && data.rates ) || [];
		if ( ! rates.length ) {
			container.innerHTML = '<p class="wh-availability__empty">' + esc( I18N.noResults || 'No availability.' ) + '</p>';
			return;
		}
		container.innerHTML = rates.map( rateCard ).join( '' );
	}

	function bindSearch() {
		var forms = document.querySelectorAll( 'form[data-wh-search]' );
		Array.prototype.forEach.call( forms, function ( form ) {
			var results = document.querySelector( '[data-wh-results]' );
			if ( ! results ) {
				return; // No in-page results target: let the GET submit navigate.
			}
			form.addEventListener( 'submit', function ( e ) {
				e.preventDefault();
				results.innerHTML = '<p class="wh-loading">' + esc( I18N.searching || 'Searching…' ) + '</p>';
				var params = {};
				Array.prototype.forEach.call( form.elements, function ( el ) {
					if ( el.name && el.value ) {
						params[ el.name ] = el.value;
					}
				} );
				api( '/availability', params )
					.then( function ( data ) {
						renderAvailability( results, data );
					} )
					.catch( function () {
						results.innerHTML = '<p class="wh-error">' + esc( I18N.error || 'Error.' ) + '</p>';
					} );
			} );
		} );
	}

	function bindLookup() {
		var widgets = document.querySelectorAll( '[data-wh-lookup]' );
		Array.prototype.forEach.call( widgets, function ( widget ) {
			var form = widget.querySelector( '.wh-booking-lookup__form' );
			var out = widget.querySelector( '.wh-booking-lookup__result' );
			if ( ! form || ! out ) {
				return;
			}
			form.addEventListener( 'submit', function ( e ) {
				e.preventDefault();
				out.textContent = I18N.searching || 'Searching…';
				var params = {
					res_id: ( form.res_id || {} ).value || '',
					email: ( form.email || {} ).value || '',
					lastName: ( form.lastName || {} ).value || ''
				};
				api( '/lookup', params )
					.then( function ( data ) {
						out.innerHTML =
							'<div class="wh-lookup-result"><p>' +
							esc( ( data.res_id || '' ) + ' — ' + ( data.status || '' ) ) +
							'</p></div>';
					} )
					.catch( function () {
						out.textContent = I18N.notFound || 'Not found.';
					} );
			} );
		} );
	}

	function bindHandoff() {
		var nodes = document.querySelectorAll( '[data-wh-handoff]' );
		Array.prototype.forEach.call( nodes, function ( node ) {
			var open = node.getAttribute( 'data-open' );
			var url = node.getAttribute( 'data-url' );
			if ( ! url ) {
				return;
			}
			if ( open === 'redirect' ) {
				window.location.href = url;
			} else if ( open === 'newtab' ) {
				window.open( url, '_blank', 'noopener' );
			}
			// iframe handled by SSR.
		} );
	}

	function init() {
		bindSearch();
		bindLookup();
		bindHandoff();
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
