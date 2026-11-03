/**
 * Settings page: live message preview and the "multiplier is 1" warning.
 *
 * Mirrors Rsdev_CCS_Settings::get_message(): an empty message falls back to the
 * default text, and every "%s" is replaced by the multiplier.
 */
( function () {
	'use strict';

	function init() {
		var enabled = document.getElementById( 'ccsp_enable_plugin' );
		var multiplier = document.getElementById( 'ccsp_multiplier' );
		var message = document.getElementById( 'ccsp_error_message' );
		var preview = document.getElementById( 'rsdev-ccs-preview' );
		var warning = document.getElementById( 'rsdev-ccs-multiplier-warning' );

		if ( ! multiplier ) {
			return;
		}

		function getMultiplier() {
			var value = parseInt( multiplier.value, 10 );
			return isNaN( value ) || value < 1 ? 1 : value;
		}

		function update() {
			if ( preview && message ) {
				var template = '' === message.value.trim() ? preview.getAttribute( 'data-default-template' ) || '' : message.value;
				preview.textContent = template.split( '%s' ).join( String( getMultiplier() ) );
			}

			if ( warning ) {
				var isEnabled = enabled ? enabled.checked : true;
				warning.style.display = isEnabled && 1 === getMultiplier() ? '' : 'none';
			}
		}

		[ enabled, multiplier, message ].forEach( function ( field ) {
			if ( field ) {
				field.addEventListener( 'input', update );
				field.addEventListener( 'change', update );
			}
		} );

		update();
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
