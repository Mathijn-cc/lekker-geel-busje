/**
 * Adreszoeker voor het offerteformulier.
 * Typ een adres, plaats of bedrijfsnaam en kies uit de lijst. Het veld blijft een
 * gewoon tekstveld: niets kiezen en zelf verder typen kan altijd.
 * Zoeken loopt via de eigen server (inc/adreszoeker.php): Google Places, anders PDOK.
 * Werkt via event delegation, dus ook na het wisselen van formulierpagina's (AJAX).
 */
( function () {
	'use strict';

	var cfg = window.themaAdressen;
	if ( ! cfg || ! cfg.velden ) {
		return;
	}

	var WACHT = 220;
	var MIN = 2;
	var teller = 0;

	function sessieToken() {
		if ( window.crypto && crypto.randomUUID ) {
			return crypto.randomUUID();
		}
		return 'xxxxxxxxxxxx4xxxyxxxxxxxxxxxxxxx'.replace( /[xy]/g, function ( c ) {
			var r = Math.random() * 16 | 0;
			return ( c === 'x' ? r : ( r & 3 | 8 ) ).toString( 16 );
		} );
	}

	function sleutelVan( invoer ) {
		var m = /^input_(\d+_\d+)$/.exec( invoer.id || '' );
		return m && cfg.velden[ m[ 1 ] ] ? m[ 1 ] : null;
	}

	function zetWaarde( invoer, waarde ) {
		invoer.value = waarde;
		invoer.dispatchEvent( new Event( 'input', { bubbles: true } ) );
		/* Ook jQuery-handlers (voorwaardelijke logica van Gravity Forms) vangen dit op. */
		invoer.dispatchEvent( new Event( 'change', { bubbles: true } ) );
	}

	function maakEl( tag, klasse, tekst ) {
		var el = document.createElement( tag );
		if ( klasse ) {
			el.className = klasse;
		}
		if ( tekst ) {
			el.textContent = tekst;
		}
		return el;
	}

	/* ------------------------------------------------------------ Een zoeker per veld */

	function Zoeker( invoer, instelling ) {
		this.invoer = invoer;
		this.soort = instelling.soort;
		this.plaatsVeld = instelling.plaats || '';
		this.delen = instelling.delen || null;
		this.naamVeld = instelling.naam || '';
		this.wacht = null;
		this.autoNaam = '';
		this.resultaten = [];
		this.actief = -1;
		this.timer = null;
		this.vraag = 0;
		this.sessie = '';
		this.laatste = '';

		var id = 'adreslijst-' + ( ++teller );
		this.lijst = maakEl( 'ul', 'adreslijst' );
		this.lijst.id = id;
		this.lijst.setAttribute( 'role', 'listbox' );
		this.lijst.hidden = true;

		this.status = maakEl( 'span', 'adreszoeker-status' );
		this.status.setAttribute( 'role', 'status' );
		this.status.setAttribute( 'aria-live', 'polite' );

		var vak = invoer.parentNode;
		vak.classList.add( 'adreszoeker' );
		vak.appendChild( this.lijst );
		vak.appendChild( this.status );

		invoer.setAttribute( 'autocomplete', 'off' );
		invoer.setAttribute( 'role', 'combobox' );
		invoer.setAttribute( 'aria-autocomplete', 'list' );
		invoer.setAttribute( 'aria-expanded', 'false' );
		invoer.setAttribute( 'aria-controls', id );

		invoer.addEventListener( 'input', this.bijTypen.bind( this ) );
		invoer.addEventListener( 'keydown', this.bijToets.bind( this ) );
		invoer.addEventListener( 'blur', this.sluit.bind( this ) );
		invoer.addEventListener( 'focus', function () {
			if ( this.resultaten.length && this.invoer.value === this.laatste ) {
				this.open();
			}
		}.bind( this ) );

		/* Mousedown in plaats van click: anders sluit blur de lijst eerst. */
		this.lijst.addEventListener( 'mousedown', function ( e ) {
			var optie = e.target.closest( '[data-index]' );
			if ( optie ) {
				e.preventDefault();
				this.kies( +optie.getAttribute( 'data-index' ) );
			}
		}.bind( this ) );
	}

	Zoeker.prototype.bijTypen = function () {
		/* Waarden die wij zelf zetten, starten geen nieuwe zoekopdracht. */
		if ( this.stil ) {
			return;
		}
		var q = this.invoer.value.trim();
		clearTimeout( this.timer );
		/* Zelf aanpassen: straat en postcode kloppen niet meer. De plaats blijft staan
		   (bijv. uit de offertezin); de server leest bij versturen alles opnieuw uit de regel. */
		this.zetDelen( null, true );
		/* Straat gekozen zonder huisnummer: zoek verder binnen dezelfde plaats. */
		if ( this.wacht && q.toLowerCase().indexOf( this.wacht.straat.toLowerCase() ) === 0 ) {
			q = q + ' ' + this.wacht.plaats;
		} else {
			this.wacht = null;
		}
		if ( q.length < MIN ) {
			this.resultaten = [];
			this.sluit();
			return;
		}
		this.timer = setTimeout( this.zoek.bind( this, q ), WACHT );
	};

	Zoeker.prototype.zoek = function ( q ) {
		var nr = ++this.vraag;
		if ( ! this.sessie ) {
			this.sessie = sessieToken();
		}
		var url = cfg.zoek + ( cfg.zoek.indexOf( '?' ) > -1 ? '&' : '?' ) +
			'q=' + encodeURIComponent( q ) + '&soort=' + encodeURIComponent( this.soort ) + '&sessie=' + this.sessie;
		this.invoer.parentNode.classList.add( 'is-zoeken' );
		fetch( url, { headers: { Accept: 'application/json' } } )
			.then( function ( r ) {
				return r.ok ? r.json() : { resultaten: [] };
			} )
			.catch( function () {
				return { resultaten: [] };
			} )
			.then( function ( data ) {
				if ( nr !== this.vraag ) {
					return;
				}
				this.invoer.parentNode.classList.remove( 'is-zoeken' );
				this.laatste = this.invoer.value;
				this.resultaten = data.resultaten || [];
				this.bron = data.bron || '';
				this.teken();
			}.bind( this ) );
	};

	Zoeker.prototype.teken = function () {
		var lijst = this.lijst;
		lijst.textContent = '';
		this.actief = -1;
		this.invoer.removeAttribute( 'aria-activedescendant' );

		if ( ! this.resultaten.length ) {
			this.sluit();
			return;
		}
		this.resultaten.forEach( function ( r, i ) {
			var li = maakEl( 'li', 'adreslijst-optie' );
			li.id = lijst.id + '-' + i;
			li.setAttribute( 'role', 'option' );
			li.setAttribute( 'aria-selected', 'false' );
			li.setAttribute( 'data-index', i );
			li.appendChild( maakEl( 'span', 'adreslijst-titel', r.titel ) );
			if ( r.sub ) {
				li.appendChild( maakEl( 'span', 'adreslijst-sub', r.sub ) );
			}
			li.appendChild( maakEl( 'span', 'adreslijst-label', r.label ) );
			lijst.appendChild( li );
		} );
		if ( this.bron === 'google' ) {
			var bron = maakEl( 'li', 'adreslijst-bron', 'Google Maps' );
			bron.setAttribute( 'role', 'presentation' );
			bron.setAttribute( 'translate', 'no' );
			lijst.appendChild( bron );
		}
		this.status.textContent = this.resultaten.length + ( this.resultaten.length === 1 ? ' suggestie' : ' suggesties' ) + ', kies met de pijltjestoetsen.';
		this.open();
	};

	Zoeker.prototype.open = function () {
		if ( document.activeElement !== this.invoer ) {
			return;
		}
		this.lijst.hidden = false;
		this.invoer.setAttribute( 'aria-expanded', 'true' );
		this.invoer.parentNode.classList.add( 'is-open' );
	};

	Zoeker.prototype.sluit = function () {
		this.lijst.hidden = true;
		this.invoer.setAttribute( 'aria-expanded', 'false' );
		this.invoer.removeAttribute( 'aria-activedescendant' );
		this.invoer.parentNode.classList.remove( 'is-open' );
	};

	Zoeker.prototype.markeer = function ( i ) {
		var opties = this.lijst.querySelectorAll( '[data-index]' );
		if ( ! opties.length ) {
			return;
		}
		this.actief = ( i + opties.length ) % opties.length;
		opties.forEach( function ( o, n ) {
			o.setAttribute( 'aria-selected', n === this.actief ? 'true' : 'false' );
		}, this );
		this.invoer.setAttribute( 'aria-activedescendant', opties[ this.actief ].id );
		opties[ this.actief ].scrollIntoView( { block: 'nearest' } );
	};

	Zoeker.prototype.bijToets = function ( e ) {
		var open = ! this.lijst.hidden;
		switch ( e.key ) {
			case 'ArrowDown':
				if ( ! open && this.resultaten.length ) {
					this.open();
				}
				this.markeer( this.actief + 1 );
				e.preventDefault();
				break;
			case 'ArrowUp':
				this.markeer( this.actief - 1 );
				e.preventDefault();
				break;
			case 'Enter':
				/* Enter in een open lijst kiest, en verstuurt het formulier niet. */
				if ( open && this.actief > -1 ) {
					e.preventDefault();
					this.kies( this.actief );
				}
				break;
			case 'Escape':
				if ( open ) {
					e.preventDefault();
					this.sluit();
				}
				break;
			case 'Tab':
				if ( open && this.actief > -1 ) {
					this.kies( this.actief );
				}
				break;
		}
	};

	Zoeker.prototype.kies = function ( i ) {
		var r = this.resultaten[ i ];
		if ( ! r ) {
			return;
		}
		this.sluit();
		if ( r.delen ) {
			this.vul( r, r.delen );
			return;
		}
		/* Google: details (straat, nummer, postcode, plaats) apart ophalen. Sluit de sessie af. */
		var sessie = this.sessie;
		this.sessie = '';
		this.invoer.parentNode.classList.add( 'is-zoeken' );
		var url = cfg.details + ( cfg.details.indexOf( '?' ) > -1 ? '&' : '?' ) + 'id=' + encodeURIComponent( r.id ) + '&sessie=' + sessie;
		fetch( url, { headers: { Accept: 'application/json' } } )
			.then( function ( res ) {
				return res.ok ? res.json() : null;
			} )
			.catch( function () {
				return null;
			} )
			.then( function ( delen ) {
				this.invoer.parentNode.classList.remove( 'is-zoeken' );
				if ( delen ) {
					this.vul( r, delen );
				} else {
					/* Geen details: neem de tekst uit de suggestie over. */
					this.zet( [ r.titel, r.sub ].filter( Boolean ).join( ', ' ) );
				}
			}.bind( this ) );
	};

	function veld( sleutel ) {
		return sleutel ? document.getElementById( 'input_' + sleutel ) : null;
	}

	/**
	 * Vul de verborgen velden met straat + huisnummer, postcode en plaats. Null maakt ze leeg.
	 */
	Zoeker.prototype.zetDelen = function ( d, plaatsLaten ) {
		if ( ! this.delen ) {
			return;
		}
		var waarden = d ? {
			straat: [ d.straat, d.huisnummer ].filter( Boolean ).join( ' ' ),
			postcode: d.postcode || '',
			plaats: [ d.plaats, d.land ].filter( Boolean ).join( ', ' )
		} : { straat: '', postcode: '', plaats: '' };
		Object.keys( this.delen ).forEach( function ( deel ) {
			var el = veld( this.delen[ deel ] );
			if ( plaatsLaten && deel === 'plaats' ) {
				return;
			}
			if ( el && el.value !== waarden[ deel ] ) {
				zetWaarde( el, waarden[ deel ] );
			}
		}, this );
	};

	/**
	 * Naam van een bedrijf of locatie in het naamveld, als daar nog niets eigens staat.
	 */
	Zoeker.prototype.zetNaam = function ( naam ) {
		var el = veld( this.naamVeld );
		if ( ! el || ( el.value && el.value !== this.autoNaam ) ) {
			return;
		}
		zetWaarde( el, naam );
		this.autoNaam = naam;
		if ( naam ) {
			el.parentNode.classList.add( 'is-vanzelf' );
			setTimeout( function () {
				el.parentNode.classList.remove( 'is-vanzelf' );
			}, 1600 );
		}
	};

	Zoeker.prototype.zet = function ( waarde ) {
		this.stil = true;
		zetWaarde( this.invoer, waarde );
		this.stil = false;
		this.laatste = waarde;
		this.resultaten = [];
	};

	/**
	 * Zet de gekozen locatie in het veld, en de plaats in het plaatsveld.
	 * Bedrijven en locaties houden hun naam, zodat de chauffeur weet waar hij moet zijn.
	 */
	Zoeker.prototype.vul = function ( r, d ) {
		if ( this.delen ) {
			this.vulRegel( r, d );
			return;
		}
		var naam = d.bedrijf && this.soort !== 'woonadres' ? r.titel : '';
		var straat = [ d.straat, d.huisnummer ].filter( Boolean ).join( ' ' );
		var plaats = [ d.postcode, d.plaats ].filter( Boolean ).join( ' ' );
		var waarde;
		/* Alleen een straat gekozen bij een adresveld: de cursor komt erachter voor het huisnummer. */
		var mistNummer = ( this.soort === 'adres' || this.soort === 'woonadres' ) && !! d.straat && ! d.huisnummer && ! naam;

		if ( this.soort === 'plaats' ) {
			waarde = [ d.plaats || r.titel, d.land ].filter( Boolean ).join( ', ' );
		} else if ( this.soort === 'volledig' ) {
			waarde = [ naam, straat, plaats, d.land ].filter( Boolean ).join( ', ' );
			if ( ! straat && ! naam ) {
				waarde = [ r.titel, r.sub ].filter( Boolean ).join( ', ' );
			}
		} else {
			/* Adresveld: straat, nummer en postcode. De plaats gaat naar het plaatsveld. */
			waarde = [ naam, straat, d.postcode ].filter( Boolean ).join( ', ' ) || r.titel;
			if ( ! this.plaatsVeld && d.plaats ) {
				waarde = [ naam, straat, plaats, d.land ].filter( Boolean ).join( ', ' );
			}
		}

		if ( mistNummer ) {
			waarde = d.straat + ' ';
		}
		this.zet( waarde );

		if ( this.plaatsVeld && d.plaats ) {
			var doel = document.getElementById( 'input_' + this.plaatsVeld );
			if ( doel ) {
				var z = doel._adreszoeker;
				doel.parentNode.classList.add( 'is-vanzelf' );
				setTimeout( function () {
					doel.parentNode.classList.remove( 'is-vanzelf' );
				}, 1600 );
				if ( z ) {
					z.zet( [ d.plaats, d.land ].filter( Boolean ).join( ', ' ) );
				} else {
					zetWaarde( doel, [ d.plaats, d.land ].filter( Boolean ).join( ', ' ) );
				}
			}
		}

		if ( mistNummer ) {
			var positie = d.straat.length + 1;
			this.invoer.focus();
			this.invoer.setSelectionRange( positie, positie );
			this.status.textContent = 'Vul nog het huisnummer aan.';
		} else {
			this.status.textContent = 'Gekozen: ' + waarde;
		}
	};

	/**
	 * Vertrekadres en bestemming: zichtbaar een volledige regel, eronder losse velden.
	 * De naam van een bedrijf of locatie gaat naar het naamveld.
	 */
	Zoeker.prototype.vulRegel = function ( r, d ) {
		var straat = [ d.straat, d.huisnummer ].filter( Boolean ).join( ' ' );
		var plaats = [ d.postcode, d.plaats ].filter( Boolean ).join( ' ' );
		var regel = [ straat, plaats, d.land ].filter( Boolean ).join( ', ' ) || [ r.titel, r.sub ].filter( Boolean ).join( ', ' );

		if ( d.bedrijf ) {
			this.zetNaam( r.titel );
		}

		/* Alleen een straat gekozen: huisnummer aanvullen, de zoeklijst blijft in dezelfde plaats. */
		if ( d.straat && ! d.huisnummer && ! d.bedrijf ) {
			this.zet( d.straat + ' ' );
			this.zetDelen( { straat: '', postcode: '', plaats: d.plaats, land: d.land } );
			this.wacht = { straat: d.straat, plaats: d.plaats || '' };
			var positie = d.straat.length + 1;
			this.invoer.focus();
			this.invoer.setSelectionRange( positie, positie );
			this.status.textContent = 'Vul nog het huisnummer aan.';
			return;
		}

		this.wacht = null;
		this.zet( regel );
		this.zetDelen( d );
		this.status.textContent = 'Gekozen: ' + regel;
	};

	/* ------------------------------------------------------------ Starten bij de eerste focus */

	function start( invoer ) {
		if ( invoer._adreszoeker || invoer.tagName !== 'INPUT' ) {
			return invoer._adreszoeker;
		}
		var sleutel = sleutelVan( invoer );
		if ( ! sleutel ) {
			return null;
		}
		invoer._adreszoeker = new Zoeker( invoer, cfg.velden[ sleutel ] );
		return invoer._adreszoeker;
	}

	document.addEventListener( 'focusin', function ( e ) {
		if ( e.target && e.target.matches && e.target.matches( 'input[id^="input_"]' ) ) {
			start( e.target );
		}
	} );

	/* Velden die al focus hebben (autofocus) of die het plaatsveld zijn, meteen klaarzetten. */
	function alles() {
		Object.keys( cfg.velden ).forEach( function ( sleutel ) {
			var invoer = document.getElementById( 'input_' + sleutel );
			if ( invoer ) {
				start( invoer );
			}
		} );
	}
	alles();
	document.addEventListener( 'gform/post_render', alles );
	if ( window.jQuery ) {
		window.jQuery( document ).on( 'gform_post_render', alles );
	}
} )();
