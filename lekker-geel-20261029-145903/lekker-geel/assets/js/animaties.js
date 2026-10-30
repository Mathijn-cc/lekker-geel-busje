/**
 * Scroll-animaties voor koppen en elementen.
 * Gedeeld door de website en het voorbeeld in het instellingendashboard.
 */
( function () {
	'use strict';

	var bewegingUit = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	/**
	 * Splitst een kop in woorden en letters. Accentwoorden en regelafbrekingen blijven behouden.
	 * Schermlezers krijgen de volledige tekst via een verborgen kopie.
	 */
	function splits( kop ) {
		if ( kop.dataset.gesplitst ) {
			return;
		}
		kop.dataset.gesplitst = '1';

		var volledig = kop.textContent.replace( /\s+/g, ' ' ).trim();
		var teller = { woord: 0, letter: 0 };
		var omhulsel = document.createElement( 'span' );
		omhulsel.className = 'kop-gesplitst';
		omhulsel.setAttribute( 'aria-hidden', 'true' );

		function verwerk( bron, doel ) {
			Array.prototype.forEach.call( bron.childNodes, function ( node ) {
				if ( node.nodeType === 3 ) {
					node.textContent.split( /(\s+)/ ).forEach( function ( deel ) {
						if ( ! deel ) {
							return;
						}
						if ( /^\s+$/.test( deel ) ) {
							doel.appendChild( document.createTextNode( ' ' ) );
							return;
						}
						var woord = document.createElement( 'span' );
						woord.className = 'woord';
						var binnen = document.createElement( 'span' );
						binnen.className = 'woord-binnen';
						binnen.style.setProperty( '--w', teller.woord++ );
						Array.from( deel ).forEach( function ( teken ) {
							var letter = document.createElement( 'span' );
							letter.className = 'letter';
							letter.style.setProperty( '--l', teller.letter++ );
							letter.textContent = teken;
							binnen.appendChild( letter );
						} );
						woord.appendChild( binnen );
						doel.appendChild( woord );
					} );
				} else if ( node.nodeType === 1 ) {
					if ( node.tagName === 'BR' ) {
						doel.appendChild( document.createElement( 'br' ) );
						return;
					}
					var kloon = node.cloneNode( false );
					doel.appendChild( kloon );
					verwerk( node, kloon );
				}
			} );
		}

		verwerk( kop, omhulsel );

		var sr = document.createElement( 'span' );
		sr.className = 'sr-tekst';
		sr.textContent = volledig;

		kop.textContent = '';
		kop.appendChild( sr );
		kop.appendChild( omhulsel );
	}

	/**
	 * Na afloop van de animatie mag het element weer zijn eigen overgangen gebruiken (hover).
	 */
	function markeerKlaar( el ) {
		var stijl = window.getComputedStyle( el );
		var duur = parseFloat( stijl.getPropertyValue( '--animatie-duur' ) ) || 700;
		var stap = parseFloat( stijl.getPropertyValue( '--animatie-vertraging' ) ) || 0;
		var volgorde = parseFloat( el.style.getPropertyValue( '--volgorde' ) ) || 0;
		window.clearTimeout( el._klaar );
		el._klaar = window.setTimeout( function () {
			el.classList.add( 'is-klaar' );
		}, duur + stap * volgorde + 60 );
	}

	/**
	 * Start de observer voor een stuk van de pagina.
	 */
	function start( root, opties ) {
		root = root || document;
		opties = opties || {};

		var koppen = root.querySelectorAll( '[data-kop]' );
		var elementen = root.querySelectorAll( '[data-animeer]' );

		koppen.forEach( splits );

		if ( bewegingUit || ! ( 'IntersectionObserver' in window ) ) {
			koppen.forEach( function ( el ) { el.classList.add( 'is-zichtbaar' ); } );
			elementen.forEach( function ( el ) { el.classList.add( 'is-zichtbaar' ); } );
			return null;
		}

		var observer = new IntersectionObserver( function ( items ) {
			var volgorde = 0;
			items.forEach( function ( item ) {
				var el = item.target;
				if ( item.isIntersecting ) {
					if ( el.hasAttribute( 'data-animeer' ) ) {
						el.style.setProperty( '--volgorde', volgorde++ );
					}
					el.classList.add( 'is-zichtbaar' );
					if ( el.hasAttribute( 'data-animeer' ) ) {
						markeerKlaar( el );
					}
					if ( ! opties.herhalen ) {
						observer.unobserve( el );
					}
				} else if ( opties.herhalen && item.boundingClientRect.top > 0 ) {
					window.clearTimeout( el._klaar );
					el.classList.remove( 'is-zichtbaar', 'is-klaar' );
				}
			} );
		}, {
			threshold: Math.min( 0.5, Math.max( 0, opties.drempel || 0.15 ) ),
			rootMargin: '0px 0px -4% 0px'
		} );

		koppen.forEach( function ( el ) { observer.observe( el ); } );
		elementen.forEach( function ( el ) { observer.observe( el ); } );
		return observer;
	}

	/**
	 * Speelt alles binnen root opnieuw af (voor het dashboardvoorbeeld).
	 */
	function speelAf( root ) {
		var items = root.querySelectorAll( '[data-kop], [data-animeer]' );
		items.forEach( function ( el ) {
			window.clearTimeout( el._klaar );
			el.classList.remove( 'is-zichtbaar', 'is-klaar' );
		} );
		void root.offsetWidth;
		window.requestAnimationFrame( function () {
			window.requestAnimationFrame( function () {
				var volgorde = 0;
				items.forEach( function ( el ) {
					if ( el.hasAttribute( 'data-animeer' ) ) {
						el.style.setProperty( '--volgorde', volgorde++ );
					}
					el.classList.add( 'is-zichtbaar' );
					if ( el.hasAttribute( 'data-animeer' ) ) {
						markeerKlaar( el );
					}
				} );
			} );
		} );
	}

	window.themaAnimaties = { splits: splits, start: start, speelAf: speelAf };
}() );
