/* WebHotelier admin scripts. Vanilla JS, no dependencies. */
( function () {
	'use strict';

	var cfg = window.WHAdmin || {};

	/* ---------------------------------------------------------------
	 * Helper: POST form-encoded data to admin-ajax and parse JSON.
	 * ------------------------------------------------------------- */
	function ajaxPost( action, data, done ) {
		var body = 'action=' + encodeURIComponent( action );
		Object.keys( data || {} ).forEach( function ( k ) {
			var v = data[ k ];
			if ( v && typeof v === 'object' ) {
				Object.keys( v ).forEach( function ( pk ) {
					body += '&' + encodeURIComponent( k + '[' + pk + ']' ) + '=' + encodeURIComponent( v[ pk ] );
				} );
			} else {
				body += '&' + encodeURIComponent( k ) + '=' + encodeURIComponent( v == null ? '' : v );
			}
		} );

		var xhr = new XMLHttpRequest();
		xhr.open( 'POST', cfg.ajaxUrl || ( window.ajaxurl || '' ), true );
		xhr.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded' );
		xhr.onreadystatechange = function () {
			if ( xhr.readyState !== 4 ) { return; }
			var parsed = null;
			try { parsed = JSON.parse( xhr.responseText ); } catch ( e ) { parsed = null; }
			done( parsed, xhr.status );
		};
		xhr.send( body );
	}

	/* ---------------------------------------------------------------
	 * Settings: Test Connection (action: wh_test_connection)
	 * ------------------------------------------------------------- */
	function initTestConnection() {
		var btn = document.getElementById( 'wh-test-connection' );
		var out = document.getElementById( 'wh-test-result' );
		if ( ! btn || ! out ) { return; }

		btn.addEventListener( 'click', function () {
			out.className = 'wh-test-result';
			out.textContent = 'Testing…';
			var nonce = btn.getAttribute( 'data-nonce' ) || cfg.testNonce || '';
			ajaxPost( 'wh_test_connection', { nonce: nonce }, function ( res ) {
				if ( res && res.success && res.data ) {
					out.className = 'wh-test-result is-ok';
					out.textContent = res.data.message || 'Connected.';
				} else if ( res && res.data ) {
					out.className = 'wh-test-result is-error';
					out.textContent = ( res.data.error_code ? '[' + res.data.error_code + '] ' : '' ) + ( res.data.message || res.data.error_msg || 'Connection failed.' );
				} else {
					out.className = 'wh-test-result is-error';
					out.textContent = 'Connection failed.';
				}
			} );
		} );
	}

	/* ---------------------------------------------------------------
	 * API Explorer: build param inputs + run (action: wh_explorer_run)
	 * ------------------------------------------------------------- */
	function initExplorer() {
		var form = document.getElementById( 'wh-explorer-form' );
		var pick = document.getElementById( 'wh-explorer-endpoint' );
		var box = document.getElementById( 'wh-explorer-params' );
		var runBtn = document.getElementById( 'wh-explorer-run' );
		var out = document.getElementById( 'wh-explorer-output' );
		var meta = document.getElementById( 'wh-explorer-meta' );
		var regEl = document.getElementById( 'wh-explorer-registry' );
		if ( ! form || ! pick || ! box || ! runBtn || ! out ) { return; }

		var registry = [];
		if ( regEl ) {
			try { registry = JSON.parse( regEl.textContent || '[]' ); } catch ( e ) { registry = []; }
		}

		function findDef( key ) {
			for ( var i = 0; i < registry.length; i++ ) {
				if ( registry[ i ].key === key ) { return registry[ i ]; }
			}
			return null;
		}

		function buildParams() {
			box.innerHTML = '';
			var def = findDef( pick.value );
			if ( ! def || ! def.params ) { return; }
			def.params.forEach( function ( p ) {
				var id = 'wh-exp-p-' + p;
				var label = document.createElement( 'label' );
				label.setAttribute( 'for', id );
				label.textContent = p;
				var input = document.createElement( 'input' );
				input.type = 'text';
				input.id = id;
				input.setAttribute( 'data-param', p );
				box.appendChild( label );
				box.appendChild( input );
			} );
		}

		pick.addEventListener( 'change', buildParams );
		buildParams();

		runBtn.addEventListener( 'click', function () {
			var def = findDef( pick.value );
			if ( ! def ) { return; }
			var params = {};
			box.querySelectorAll( 'input[data-param]' ).forEach( function ( el ) {
				params[ el.getAttribute( 'data-param' ) ] = el.value;
			} );

			out.className = 'wh-explorer-output';
			out.textContent = 'Running…';
			if ( meta ) { meta.textContent = ''; }

			var nonceEl = document.getElementById( 'wh-explorer-nonce' );
			var nonce = ( nonceEl && nonceEl.value ) || cfg.explorerNonce || '';

			ajaxPost( 'wh_explorer_run', { nonce: nonce, endpoint: def.key, params: params }, function ( res ) {
				if ( res && res.success && res.data ) {
					if ( meta ) {
						meta.innerHTML = '<strong>' + ( res.data.method || '' ) + '</strong> ' +
							( res.data.url || '' ) +
							' <span class="wh-explorer-ms">(' + ( res.data.ms != null ? res.data.ms : '?' ) + ' ms)</span>';
					}
					out.className = 'wh-explorer-output';
					out.textContent = res.data.json || '';
				} else if ( res && res.data ) {
					if ( meta ) {
						meta.innerHTML = '<strong>' + ( res.data.method || '' ) + '</strong> ' + ( res.data.url || '' );
					}
					out.className = 'wh-explorer-output is-error';
					out.textContent = ( res.data.error_code ? '[' + res.data.error_code + '] ' : '' ) + ( res.data.message || res.data.error_msg || 'Request failed.' );
				} else {
					out.className = 'wh-explorer-output is-error';
					out.textContent = 'Request failed.';
				}
			} );
		} );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		initTestConnection();
		initExplorer();
	} );
} )();
