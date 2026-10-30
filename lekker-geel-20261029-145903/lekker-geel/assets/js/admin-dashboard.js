/**
 * Thema-instellingen: tabs, live voorbeelden en invoerhulp.
 */
( function ( $ ) {
	'use strict';

	var data = window.themaDashboard || { fonts: {}, eyebrows: {} };
	var root = document.querySelector( '[data-dashboard]' );
	if ( ! root ) {
		return;
	}
	var formulier = root.querySelector( '[data-formulier]' );
	var balk = root.querySelector( '[data-opslaanbalk]' );
	var podium = root.querySelector( '[data-podium]' );

	/* ---------------------------------------------------------------
	 * Hulp: huidige waarde van een instelling.
	 * ------------------------------------------------------------ */
	function waarde( sleutel ) {
		var velden = root.querySelectorAll( '[data-sleutel="' + sleutel + '"]' );
		var uit = null;
		velden.forEach( function ( veld ) {
			if ( veld.type === 'radio' ) {
				if ( veld.checked ) {
					uit = veld.value;
				}
			} else if ( veld.type === 'checkbox' ) {
				uit = veld.checked;
			} else {
				uit = veld.value;
			}
		} );
		return uit;
	}

	/* ---------------------------------------------------------------
	 * Tabs.
	 * ------------------------------------------------------------ */
	var tabs = root.querySelectorAll( '[data-tab]' );
	var panelen = root.querySelectorAll( '[data-paneel]' );

	function toonTab( id ) {
		if ( ! root.querySelector( '[data-paneel="' + id + '"]' ) ) {
			id = 'algemeen';
		}
		tabs.forEach( function ( tab ) {
			tab.classList.toggle( 'is-actief', tab.dataset.tab === id );
			tab.setAttribute( 'aria-current', tab.dataset.tab === id ? 'page' : 'false' );
		} );
		panelen.forEach( function ( paneel ) {
			paneel.classList.toggle( 'is-actief', paneel.dataset.paneel === id );
		} );
		try {
			window.localStorage.setItem( 'themaTab', id );
		} catch ( e ) {}
		if ( id === 'animaties' ) {
			speelAnimaties();
		}
	}

	tabs.forEach( function ( tab ) {
		tab.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			history.replaceState( null, '', '#' + tab.dataset.tab );
			toonTab( tab.dataset.tab );
		} );
	} );

	var start = window.location.hash.replace( '#', '' );
	if ( ! start ) {
		try {
			start = window.localStorage.getItem( 'themaTab' ) || 'algemeen';
		} catch ( e ) {
			start = 'algemeen';
		}
	}

	/* ---------------------------------------------------------------
	 * Niet-opgeslagen wijzigingen.
	 * ------------------------------------------------------------ */
	var gewijzigd = false;
	function markeer() {
		gewijzigd = true;
		balk && balk.classList.add( 'is-zichtbaar' );
	}
	if ( formulier ) {
		formulier.addEventListener( 'input', markeer );
		formulier.addEventListener( 'change', markeer );
		formulier.addEventListener( 'submit', function () {
			gewijzigd = false;
		} );
	}
	window.addEventListener( 'beforeunload', function ( e ) {
		if ( gewijzigd ) {
			e.preventDefault();
			e.returnValue = '';
		}
	} );

	var melding = root.querySelector( '[data-melding]' );
	if ( melding ) {
		window.setTimeout( function () {
			melding.style.transition = 'opacity .4s ease';
			melding.style.opacity = '0';
			window.setTimeout( function () { melding.remove(); }, 450 );
		}, 4500 );
	}

	root.querySelectorAll( '[data-bevestig]' ).forEach( function ( form ) {
		form.addEventListener( 'submit', function ( e ) {
			if ( ! window.confirm( form.dataset.bevestig ) ) {
				e.preventDefault();
			}
		} );
	} );

	/* ---------------------------------------------------------------
	 * Kleuren: kiezer en hex-veld synchroon.
	 * ------------------------------------------------------------ */
	root.querySelectorAll( '.kleurveld' ).forEach( function ( veld ) {
		var kiezer = veld.querySelector( 'input[type="color"]' );
		var hex = veld.querySelector( '.kleurveld-hex' );
		var staal = veld.querySelector( '.kleurveld-staal' );

		kiezer.addEventListener( 'input', function () {
			hex.value = kiezer.value;
			staal.style.setProperty( '--staal', kiezer.value );
			werkBij();
			markeer();
		} );
		hex.addEventListener( 'input', function () {
			var v = hex.value.trim();
			if ( v && v[ 0 ] !== '#' ) {
				v = '#' + v;
			}
			if ( /^#[0-9a-f]{6}$/i.test( v ) ) {
				kiezer.value = v;
				staal.style.setProperty( '--staal', v );
				werkBij();
			}
		} );
	} );

	/* ---------------------------------------------------------------
	 * Schuifregelaars.
	 * ------------------------------------------------------------ */
	function vulSchuif( schuif ) {
		var min = parseFloat( schuif.min );
		var max = parseFloat( schuif.max );
		var pct = max > min ? ( ( schuif.value - min ) / ( max - min ) ) * 100 : 0;
		schuif.style.setProperty( '--vulling', pct + '%' );
		var uit = schuif.parentElement.querySelector( 'output' );
		if ( uit ) {
			uit.textContent = schuif.value + ( schuif.dataset.eenheid || '' );
		}
	}
	root.querySelectorAll( 'input[type="range"]' ).forEach( function ( schuif ) {
		vulSchuif( schuif );
		schuif.addEventListener( 'input', function () {
			vulSchuif( schuif );
		} );
	} );

	/* ---------------------------------------------------------------
	 * Media kiezen.
	 * ------------------------------------------------------------ */
	root.querySelectorAll( '[data-media]' ).forEach( function ( veld ) {
		var invoer = veld.querySelector( 'input[type="hidden"]' );
		var voorbeeld = veld.querySelector( '.mediaveld-voorbeeld' );
		var frame = null;

		veld.querySelector( '[data-media-kies]' ).addEventListener( 'click', function () {
			if ( ! window.wp || ! wp.media ) {
				return;
			}
			if ( ! frame ) {
				frame = wp.media( { title: 'Kies een afbeelding', button: { text: 'Gebruiken' }, library: { type: 'image' }, multiple: false } );
				frame.on( 'select', function () {
					var bijlage = frame.state().get( 'selection' ).first().toJSON();
					var url = bijlage.sizes && bijlage.sizes.medium ? bijlage.sizes.medium.url : bijlage.url;
					invoer.value = bijlage.id;
					voorbeeld.innerHTML = '<img src="' + url + '" alt="">';
					veld.classList.add( 'heeft-beeld' );
					markeer();
				} );
			}
			frame.open();
		} );

		veld.querySelector( '[data-media-weg]' ).addEventListener( 'click', function () {
			invoer.value = 0;
			voorbeeld.innerHTML = '<span class="mediaveld-leeg">Nog niets gekozen</span>';
			veld.classList.remove( 'heeft-beeld' );
			markeer();
		} );
	} );

	/* ---------------------------------------------------------------
	 * Velden tonen of verbergen op basis van een keuze.
	 * ------------------------------------------------------------ */
	function voorwaarden() {
		root.querySelectorAll( '[data-toon-bij]' ).forEach( function ( el ) {
			var huidig = waarde( el.dataset.toonBij );
			var toon = ( el.dataset.waarden || '' ).split( ' ' ).indexOf( huidig ) > -1;
			el.hidden = ! toon;
		} );
	}

	/* ---------------------------------------------------------------
	 * Lettertypes: Truculenta heeft maar één dikte.
	 * ------------------------------------------------------------ */
	function fontBereik() {
		var gewicht = root.querySelector( '[data-sleutel="kop_gewicht"]' );
		if ( gewicht ) {
			gewicht.closest( '.veld' ).classList.toggle( 'is-uit', waarde( 'font_kop' ) === 'truculenta' );
		}
	}

	/* ---------------------------------------------------------------
	 * Live voorbeelden: CSS-variabelen en classes op het dashboard.
	 * ------------------------------------------------------------ */
	var easing = {
		zacht: 'cubic-bezier(.2, .7, .2, 1)',
		veer: 'cubic-bezier(.34, 1.56, .64, 1)',
		strak: 'cubic-bezier(.65, 0, .35, 1)',
		lineair: 'linear'
	};

	function zet( naam, w ) {
		root.style.setProperty( naam, w );
	}

	function wisselClass( el, voorvoegsel, nieuw ) {
		if ( ! el ) {
			return;
		}
		Array.from( el.classList ).forEach( function ( c ) {
			if ( c.indexOf( voorvoegsel ) === 0 ) {
				el.classList.remove( c );
			}
		} );
		el.classList.add( voorvoegsel + nieuw );
	}

	function werkBij() {
		[ 'geel', 'paars', 'diep', 'roze', 'creme', 'zand' ].forEach( function ( k ) {
			var w = waarde( 'kleur_' + k );
			if ( /^#[0-9a-f]{6}$/i.test( w || '' ) ) {
				zet( '--kleur-' + k, w );
			}
		} );
		var achter = waarde( 'achtergrond' );
		if ( /^#[0-9a-f]{6}$/i.test( achter || '' ) ) {
			zet( '--achtergrond', achter );
			root.querySelectorAll( '[data-achtergrond]' ).forEach( function ( knop ) {
				var actief = knop.dataset.achtergrond.toLowerCase() === achter.toLowerCase();
				knop.classList.toggle( 'is-actief', actief );
				knop.setAttribute( 'aria-pressed', actief ? 'true' : 'false' );
			} );
		}

		var kop = data.fonts[ waarde( 'font_kop' ) ] || {};
		var accent = data.fonts[ waarde( 'font_accent' ) ] || {};
		var tekst = data.fonts[ waarde( 'font_tekst' ) ] || {};
		if ( kop.stack ) {
			zet( '--kop-letter', kop.stack );
		}
		if ( accent.stack ) {
			zet( '--accent-letter', accent.stack );
			zet( '--accent-stijl', accent.stijl || 'normal' );
		}
		if ( tekst.stack ) {
			zet( '--tekst-letter', tekst.stack );
		}
		zet( '--kop-schaal', ( ( kop.schaal || 1 ) * ( parseInt( waarde( 'kop_grootte' ), 10 ) || 100 ) / 100 ).toFixed( 3 ) );
		zet( '--kop-gewicht', waarde( 'font_kop' ) === 'truculenta' ? 900 : waarde( 'kop_gewicht' ) );

		var e = ( data.eyebrows || {} )[ waarde( 'eyebrow' ) ];
		if ( e ) {
			zet( '--eyebrow-tekst', e[ 0 ] );
			zet( '--eyebrow-hoek', e[ 1 ] );
		}

		zet( '--animatie-duur', waarde( 'animatie_duur' ) + 'ms' );
		zet( '--animatie-vertraging', waarde( 'animatie_vertraging' ) + 'ms' );
		zet( '--animatie-easing', easing[ waarde( 'animatie_easing' ) ] );

		if ( podium ) {
			wisselClass( podium, 'koppen-', waarde( 'kop_animatie' ) );
			wisselClass( podium, 'elementen-', waarde( 'element_animatie' ) );
			wisselClass( podium, 'accordeon-', waarde( 'faq_uitklap' ) );
			var lijst = podium.querySelector( '[data-accordeon]' );
			if ( lijst ) {
				lijst.toggleAttribute( 'data-een-open', !! waarde( 'faq_een_open' ) );
			}
		}
		root.classList.toggle( 'is-rustig', ! waarde( 'animaties' ) );
	}

	/* Achtergrondstalen zetten de eigen kleur. */
	root.querySelectorAll( '[data-achtergrond]' ).forEach( function ( knop ) {
		knop.addEventListener( 'click', function () {
			var hex = root.querySelector( '.kleurveld-hex[data-sleutel="achtergrond"]' );
			var kiezer = root.querySelector( '[data-kleur-voor="achtergrond"]' );
			if ( hex ) {
				hex.value = knop.dataset.achtergrond;
				hex.dispatchEvent( new Event( 'input', { bubbles: true } ) );
				hex.dispatchEvent( new Event( 'change', { bubbles: true } ) );
			}
			if ( kiezer ) {
				kiezer.value = knop.dataset.achtergrond;
			}
		} );
	} );

	/* ---------------------------------------------------------------
	 * Animatievoorbeelden.
	 * ------------------------------------------------------------ */
	var anim = window.themaAnimaties;

	function speelAnimaties() {
		if ( anim && podium ) {
			anim.speelAf( podium );
		}
	}

	if ( anim ) {
		root.querySelectorAll( '.animatie-proef [data-kop], .voorbeeld-podium [data-kop]' ).forEach( anim.splits );
		root.querySelectorAll( '.animatie-proef' ).forEach( function ( proef ) {
			proef.querySelectorAll( '[data-kop], [data-animeer]' ).forEach( function ( el ) {
				el.classList.add( 'is-zichtbaar' );
			} );
			var kaart = proef.closest( '.keuzekaart' );
			if ( kaart ) {
				kaart.addEventListener( 'mouseenter', function () {
					anim.speelAf( proef );
				} );
				kaart.addEventListener( 'click', function () {
					anim.speelAf( proef );
				} );
			}
		} );
	}

	var opnieuw = root.querySelector( '[data-opnieuw]' );
	if ( opnieuw ) {
		opnieuw.addEventListener( 'click', speelAnimaties );
	}

	var animatieSleutels = [ 'kop_animatie', 'element_animatie', 'animatie_duur', 'animatie_vertraging', 'animatie_easing', 'faq_uitklap' ];
	var uitstel = null;

	if ( formulier ) {
		formulier.addEventListener( 'change', function ( e ) {
			var sleutel = e.target.dataset ? e.target.dataset.sleutel : '';
			if ( sleutel === 'font_kop' ) {
				fontBereik();
			}
			voorwaarden();
			werkBij();
			if ( animatieSleutels.indexOf( sleutel ) > -1 ) {
				window.clearTimeout( uitstel );
				uitstel = window.setTimeout( speelAnimaties, 120 );
			}
		} );
		formulier.addEventListener( 'input', function ( e ) {
			if ( e.target.type === 'range' ) {
				werkBij();
			}
		} );
	}

	fontBereik();
	voorwaarden();
	werkBij();
	toonTab( start );
}( window.jQuery ) );
