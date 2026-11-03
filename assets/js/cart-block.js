/**
 * Block cart: grey out the "Proceed to checkout" button while the cart
 * does not meet the multiple-of-X rule.
 *
 * The rule itself is enforced on the server; this only mirrors it in the UI.
 * The state is read from the WooCommerce cart data store, so it updates
 * immediately when quantities change.
 */
( function () {
	'use strict';

	var data = window.wp && window.wp.data;
	if ( ! data ) {
		return;
	}

	var ERROR_CODE = 'rsdev_ccs_invalid_quantity';
	var BODY_CLASS = 'rsdev-ccs-cart-invalid';
	var BUTTON = '.wc-block-cart__submit-button';

	function hasOurError( errors ) {
		return Array.isArray( errors ) && errors.some( function ( error ) {
			return error && ERROR_CODE === error.code;
		} );
	}

	function isInvalid() {
		var store = data.select( 'wc/store/cart' );
		if ( ! store ) {
			return false;
		}

		// Validation errors returned with the cart (what the cart block shows as notices).
		var cart = 'function' === typeof store.getCartData ? store.getCartData() : null;
		if ( cart && hasOurError( cart.errors ) ) {
			return true;
		}

		// Errors from a failed request, e.g. when placing an order is refused.
		return 'function' === typeof store.getCartErrors && hasOurError( store.getCartErrors() );
	}

	function update() {
		var invalid = isInvalid();

		document.body.classList.toggle( BODY_CLASS, invalid );

		document.querySelectorAll( BUTTON ).forEach( function ( button ) {
			if ( invalid ) {
				button.setAttribute( 'aria-disabled', 'true' );
			} else {
				button.removeAttribute( 'aria-disabled' );
			}
		} );
	}

	// Block clicks (and Enter on the link) before WooCommerce handles them.
	document.addEventListener(
		'click',
		function ( event ) {
			if (
				document.body.classList.contains( BODY_CLASS ) &&
				event.target instanceof Element &&
				event.target.closest( BUTTON )
			) {
				event.preventDefault();
				event.stopPropagation();
			}
		},
		true
	);

	data.subscribe( update );

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', update );
	} else {
		update();
	}
} )();
