/**
 * Uitklappen van vragen. De animatie zit in de CSS (accordeon-direct, -soepel, -veer).
 * Ook gebruikt in het voorbeeld in het dashboard.
 */
( function () {
	'use strict';

	function zet( item, open ) {
		var knop = item.querySelector( '.faq-vraag' );
		item.classList.toggle( 'is-open', open );
		if ( knop ) {
			knop.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		}
	}

	document.addEventListener( 'click', function ( e ) {
		var knop = e.target.closest( '.faq-vraag' );
		if ( ! knop ) {
			return;
		}
		var item = knop.closest( '.faq-item' );
		var groep = item.closest( '[data-accordeon]' );
		var open = ! item.classList.contains( 'is-open' );

		if ( open && groep && groep.hasAttribute( 'data-een-open' ) ) {
			groep.querySelectorAll( '.faq-item.is-open' ).forEach( function ( ander ) {
				if ( ander !== item ) {
					zet( ander, false );
				}
			} );
		}
		zet( item, open );
	} );
}() );
