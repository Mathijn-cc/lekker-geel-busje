/**
 * Prettigere bediening van de flexibele blokken.
 */
( function ( $ ) {
	'use strict';

	var info = window.themaBlokken || {};

	function versierLayout( $layout ) {
		var naam = $layout.attr( 'data-layout' );
		var $handle = $layout.find( '> .acf-fc-layout-actions-wrap > .acf-fc-layout-handle, > .acf-fc-layout-handle' ).first();
		if ( ! info[ naam ] || $handle.children( '.blok-icoon' ).length ) {
			return;
		}
		$handle.prepend( '<span class="blok-icoon" aria-hidden="true">' + info[ naam ].icoon + '</span>' );
	}

	function veld() {
		return $( '#acf-group_blokken .acf-field-flexible-content' ).first();
	}

	function werkTellerBij() {
		var aantal = veld().find( '> .acf-input > .acf-flexible-content > .values > .layout' ).length;
		$( '.blokken-teller' ).text( aantal === 1 ? '1 blok' : aantal + ' blokken' );
	}

	function werkbalk() {
		var $veld = veld();
		if ( ! $veld.length || $veld.find( '.blokken-werkbalk' ).length ) {
			return;
		}
		var $balk = $(
			'<div class="blokken-werkbalk">' +
				'<span class="blokken-teller"></span>' +
				'<span class="blokken-werkbalk-knoppen">' +
					'<button type="button" data-actie="in">Alles inklappen</button>' +
					'<button type="button" data-actie="uit">Alles uitklappen</button>' +
				'</span>' +
			'</div>'
		);
		$veld.find( '> .acf-input' ).prepend( $balk );

		$balk.on( 'click', 'button', function () {
			var inklappen = $( this ).data( 'actie' ) === 'in';
			$veld.find( '> .acf-input > .acf-flexible-content > .values > .layout' ).each( function () {
				$( this ).toggleClass( '-collapsed', inklappen );
			} );
		} );
		werkTellerBij();
	}

	/**
	 * Blokkiezer met iconen en uitleg.
	 */
	function versierKiezer( popup ) {
		var $popup = $( popup );
		if ( $popup.hasClass( 'blokkiezer' ) ) {
			return;
		}
		$popup.addClass( 'blokkiezer' );
		$popup.find( 'a[data-layout]' ).each( function () {
			var $a = $( this );
			var naam = $a.attr( 'data-layout' );
			if ( ! info[ naam ] ) {
				return;
			}
			var label = $a.text();
			$a.html(
				'<span class="blok-icoon" aria-hidden="true">' + info[ naam ].icoon + '</span>' +
				'<span class="blok-naam">' + label + '</span>' +
				'<span class="blok-uitleg">' + info[ naam ].tekst + '</span>'
			);
		} );
	}

	/**
	 * Icoonkeuze als tegels met icoon en label, in plaats van een keuzelijst.
	 */
	function iconkiezers( $ctx ) {
		var iconen = info._iconen || {};
		$ctx.find( '.acf-field.icoonveld select' ).each( function () {
			var $select = $( this );
			$select.siblings( '.iconkiezer' ).remove();
			var $kiezer = $( '<div class="iconkiezer" role="radiogroup"></div>' );
			$select.find( 'option' ).each( function () {
				var waarde = this.value;
				if ( ! waarde ) {
					return;
				}
				var gekozen = $select.val() === waarde;
				$kiezer.append(
					'<button type="button" class="iconkiezer-keuze' + ( gekozen ? ' is-gekozen' : '' ) + '" data-waarde="' + waarde + '" role="radio" aria-checked="' + ( gekozen ? 'true' : 'false' ) + '">' +
						'<span class="iconkiezer-icoon">' + ( iconen[ waarde ] || '' ) + '</span>' +
						'<span class="iconkiezer-label">' + $( this ).text() + '</span>' +
					'</button>'
				);
			} );
			$select.addClass( 'iconkiezer-select' ).after( $kiezer );
		} );
	}

	$( document ).on( 'click', '.iconkiezer-keuze', function () {
		var $knop = $( this );
		var $kiezer = $knop.closest( '.iconkiezer' );
		$kiezer.find( '.iconkiezer-keuze' ).removeClass( 'is-gekozen' ).attr( 'aria-checked', 'false' );
		$knop.addClass( 'is-gekozen' ).attr( 'aria-checked', 'true' );
		$kiezer.siblings( 'select' ).val( $knop.data( 'waarde' ) ).trigger( 'change' );
	} );

	if ( window.MutationObserver ) {
		new MutationObserver( function ( lijst ) {
			lijst.forEach( function ( m ) {
				m.addedNodes.forEach( function ( node ) {
					if ( node.nodeType === 1 ) {
						if ( node.classList.contains( 'acf-fc-popup' ) ) {
							versierKiezer( node );
						} else if ( node.querySelector ) {
							var p = node.querySelector( '.acf-fc-popup' );
							if ( p ) {
								versierKiezer( p );
							}
						}
					}
				} );
			} );
		} ).observe( document.body, { childList: true, subtree: true } );
	}

	if ( window.acf ) {
		acf.addAction( 'ready', function () {
			veld().find( '.layout' ).each( function () {
				versierLayout( $( this ) );
			} );
			iconkiezers( veld() );
			// Bij meer dan twee blokken starten we ingeklapt: dat geeft overzicht.
			var $layouts = veld().find( '> .acf-input > .acf-flexible-content > .values > .layout' );
			if ( $layouts.length > 2 ) {
				$layouts.addClass( '-collapsed' );
			}
		} );

		acf.addAction( 'append', function ( $el ) {
			if ( $el.hasClass( 'layout' ) ) {
				versierLayout( $el );
			}
			$el.find( '.layout' ).each( function () {
				versierLayout( $( this ) );
			} );
			iconkiezers( $el );
			werkTellerBij();
		} );

		acf.addAction( 'remove', function () {
			window.setTimeout( werkTellerBij, 400 );
		} );
	}
}( window.jQuery ) );
