<?php
/**
 * Thema-instellingen: schema, standaardwaarden, lettertypes en CSS-variabelen.
 * De keuzes volgen het stijlpaneel uit het homepage-ontwerp.
 */

defined( 'ABSPATH' ) || exit;

const THEMA_OPTIE = 'thema_instellingen';

/**
 * Alle lokaal meegeleverde lettertypes. Nooit van Google geladen.
 * 'rollen' bepaalt waar een lettertype gekozen kan worden.
 */
function thema_fonts() {
	return array(
		'anybody'    => array(
			'naam'      => 'Anybody',
			'fallback'  => 'sans-serif',
			'rollen'    => array( 'kop' ),
			'gewicht'   => '100 900',
			'breedte'   => '50% 150%',
			'schaal'    => 0.84,
			'bestanden' => array( 'normal' => 'anybody-latin-standard-normal.woff2' ),
		),
		'bricolage'  => array(
			'naam'      => 'Bricolage Grotesque',
			'fallback'  => 'sans-serif',
			'rollen'    => array( 'kop' ),
			'gewicht'   => '200 800',
			'breedte'   => '75% 100%',
			'schaal'    => 1,
			'bestanden' => array( 'normal' => 'bricolage-grotesque-latin-standard-normal.woff2' ),
		),
		'schibsted'  => array(
			'naam'      => 'Schibsted Grotesk',
			'fallback'  => 'sans-serif',
			'rollen'    => array( 'kop', 'tekst' ),
			'gewicht'   => '400 900',
			'breedte'   => '',
			'schaal'    => 1,
			'bestanden' => array(
				'normal' => 'schibsted-grotesk-latin-wght-normal.woff2',
				'italic' => 'schibsted-grotesk-latin-wght-italic.woff2',
			),
		),
		'truculenta' => array(
			'naam'      => 'Truculenta',
			'fallback'  => 'sans-serif',
			'rollen'    => array( 'kop', 'accent' ),
			'gewicht'   => '900',
			'breedte'   => '',
			'schaal'    => 1,
			'accent_schaal' => 1,
			'stijl'     => 'normal',
			'bestanden' => array( 'normal' => 'truculenta-latin-900-normal.woff2' ),
		),
		'hanken'     => array(
			'naam'      => 'Hanken Grotesk',
			'fallback'  => 'sans-serif',
			'rollen'    => array( 'tekst' ),
			'gewicht'   => '100 900',
			'breedte'   => '',
			'schaal'    => 1,
			'bestanden' => array( 'normal' => 'hanken-grotesk-latin-wght-normal.woff2' ),
		),
		'instrument' => array(
			'naam'      => 'Instrument Serif',
			'fallback'  => 'serif',
			'rollen'    => array( 'accent' ),
			'gewicht'   => '400',
			'breedte'   => '',
			'schaal'    => 1,
			'accent_schaal' => 1.2,
			'stijl'     => 'italic',
			'bestanden' => array(
				'normal' => 'instrument-serif-latin-400-normal.woff2',
				'italic' => 'instrument-serif-latin-400-italic.woff2',
			),
		),
		'leckerli'   => array(
			'naam'      => 'Leckerli One',
			'fallback'  => 'cursive',
			'rollen'    => array( 'accent' ),
			'gewicht'   => '400',
			'breedte'   => '',
			'schaal'    => 1,
			'accent_schaal' => .95,
			'stijl'     => 'normal',
			'bestanden' => array( 'normal' => 'leckerli-one-latin-400-normal.woff2' ),
		),
		'caveat'     => array(
			'naam'      => 'Caveat Brush',
			'fallback'  => 'cursive',
			'rollen'    => array( 'accent' ),
			'gewicht'   => '400',
			'breedte'   => '',
			'schaal'    => 1,
			'accent_schaal' => 1.12,
			'stijl'     => 'normal',
			'bestanden' => array( 'normal' => 'caveat-brush-latin-400-normal.woff2' ),
		),
	);
}

function thema_fonts_voor( $rol ) {
	return array_filter( thema_fonts(), fn( $f ) => in_array( $rol, $f['rollen'], true ) );
}

/**
 * Schema: type, standaard, keuzes of bereik.
 */
function thema_schema() {
	return array(
		// Algemeen.
		'logo'                => array( 'id', 0 ),
		'logo_hoogte'         => array( 'int', 60, 36, 90 ),
		'telefoon'            => array( 'text', '085 006 2061' ),
		'email'               => array( 'email', 'info@lekkergeelbusje.nl' ),
		'whatsapp'            => array( 'text', '' ),
		'adres'               => array( 'textarea', "Straatnaam en huisnummer\nPostcode Oisterwijk" ),
		'kvk'                 => array( 'text', '' ),
		'btw'                 => array( 'text', '' ),
		'offerte_link'        => array( 'url', '' ),
		'menu_knop'           => array( 'text', 'Offerte aanvragen' ),
		'menu_knop_kort'      => array( 'text', 'Offerte' ),
		'menu_knop_hover'     => array( 'text', 'Gratis en vrijblijvend' ),
		'menu_kaart_tekst'    => array( 'text', 'Jouw feest begint in de bus!' ),
		'score'               => array( 'text', '5,0' ),
		'score_label'         => array( 'text', 'op Google' ),
		'score_bron'          => array( 'keuze', 'vast', array( 'vast', 'trustindex' ) ),
		'ti_carrousel'        => array( 'widget', '' ),
		'ti_rating'           => array( 'widget', '' ),
		'instagram'           => array( 'url', 'https://www.instagram.com/lekkergeelbusje' ),
		'facebook'            => array( 'url', '' ),
		'tiktok'              => array( 'url', '' ),
		'youtube'             => array( 'url', '' ),

		// Footer.
		'footer_cta_kop'      => array( 'text', 'Klaar om *in te stappen?*' ),
		'footer_cta_tekst'    => array( 'textarea', 'Vertel ons wanneer, waarheen en met hoeveel je bent. Binnen 2 werkdagen heb je een vaste all-in prijs.' ),
		'footer_tekst'        => array( 'textarea', 'Bij Lekker Geel Busje begint je feestje zodra je instapt. Daar gaan we voor.' ),
		'footer_copy'         => array( 'text', '© {jaar} Lekker Geel Busje' ),
		'footer_maker'        => array( 'bool', 1 ),

		// Stijl, gelijk aan het stijlpaneel.
		'hero_stijl'          => array( 'keuze', 'foto', array( 'foto', 'collage', 'bento', 'klassiek' ) ),
		'menu_stijl'          => array( 'keuze', 'zwevend', array( 'zwevend', 'balk', 'overlay' ) ),
		'prijs_stijl'         => array( 'keuze', 'kaartje', array( 'kaartje', 'lijst', 'tegels' ) ),
		'knoppen'             => array( 'keuze', 'rond', array( 'rond', 'schuin' ) ),
		'eyebrow'             => array( 'keuze', 'oranje', array( 'oranje', 'allesoranje', 'paars', 'zacht' ) ),
		'achtergrond'         => array( 'kleur', '#faf5ee' ),

		// Kleuren.
		'kleur_geel'          => array( 'kleur', '#ffaf00' ),
		'kleur_paars'         => array( 'kleur', '#38065e' ),
		'kleur_diep'          => array( 'kleur', '#26043f' ),
		'kleur_roze'          => array( 'kleur', '#e0479e' ),
		'kleur_creme'         => array( 'kleur', '#faf5ee' ),
		'kleur_zand'          => array( 'kleur', '#f1e9dc' ),

		// Typografie.
		'font_kop'            => array( 'keuze', 'anybody', array_keys( thema_fonts_voor( 'kop' ) ) ),
		'font_tekst'          => array( 'keuze', 'schibsted', array_keys( thema_fonts_voor( 'tekst' ) ) ),
		'font_accent'         => array( 'keuze', 'instrument', array_keys( thema_fonts_voor( 'accent' ) ) ),
		'kop_grootte'         => array( 'int', 100, 80, 120 ),
		'kop_gewicht'         => array( 'int', 800, 500, 900 ),

		// Animaties.
		'animaties'           => array( 'bool', 1 ),
		'kop_animatie'        => array( 'keuze', 'masker', array( 'geen', 'masker', 'woorden', 'letters', 'omhoog', 'vervaging' ) ),
		'element_animatie'    => array( 'keuze', 'omhoog', array( 'geen', 'vervagen', 'omhoog', 'schalen', 'schuiven', 'kantelen' ) ),
		'animatie_duur'       => array( 'int', 1000, 200, 1800 ),
		'animatie_vertraging' => array( 'int', 90, 0, 300 ),
		'animatie_easing'     => array( 'keuze', 'zacht', array( 'zacht', 'veer', 'strak', 'lineair' ) ),
		'scroll_effecten'     => array( 'bool', 1 ),
		'voordelen_uitklap'   => array( 'keuze', 'soepel', array( 'soepel', 'veer' ) ),
		'faq_uitklap'         => array( 'keuze', 'soepel', array( 'direct', 'soepel', 'veer' ) ),
		'faq_een_open'        => array( 'bool', 1 ),
		'cursor'              => array( 'keuze', 'ring', array( 'ring', 'ster', 'uit' ) ),
		'plaats_interval'     => array( 'int', 2400, 1200, 6000 ),
		'band_snelheid'       => array( 'int', 100, 25, 300 ),
		'reviews_snelheid'    => array( 'int', 100, 25, 300 ),
		'hero_interval'       => array( 'int', 6000, 3000, 12000 ),
		'vloeiend_scrollen'   => array( 'bool', 0 ),
	);
}

function thema_standaard() {
	$uit = array();
	foreach ( thema_schema() as $sleutel => $def ) {
		$uit[ $sleutel ] = $def[1];
	}
	return $uit;
}

function thema_instellingen( $sleutel = null ) {
	static $cache = null;
	if ( null === $cache ) {
		$opgeslagen = get_option( THEMA_OPTIE, array() );
		$cache      = wp_parse_args( is_array( $opgeslagen ) ? $opgeslagen : array(), thema_standaard() );
		// Waarden uit een oudere versie die niet meer bestaan, vallen terug op de standaard.
		foreach ( thema_schema() as $k => $def ) {
			if ( 'keuze' === $def[0] && ! in_array( $cache[ $k ], $def[2], true ) ) {
				$cache[ $k ] = $def[1];
			}
		}
	}
	return null !== $sleutel ? ( $cache[ $sleutel ] ?? null ) : $cache;
}

function thema_opschonen( $invoer ) {
	$uit    = array();
	$invoer = is_array( $invoer ) ? $invoer : array();

	foreach ( thema_schema() as $sleutel => $def ) {
		$waarde = $invoer[ $sleutel ] ?? null;
		switch ( $def[0] ) {
			case 'bool':
				$uit[ $sleutel ] = empty( $waarde ) ? 0 : 1;
				break;
			case 'int':
				$getal           = is_numeric( $waarde ) ? (int) $waarde : $def[1];
				$uit[ $sleutel ] = max( $def[2], min( $def[3], $getal ) );
				break;
			case 'id':
				$uit[ $sleutel ] = absint( $waarde );
				break;
			case 'kleur':
				$kleur           = sanitize_hex_color( $waarde );
				$uit[ $sleutel ] = $kleur ? strtolower( $kleur ) : $def[1];
				break;
			case 'keuze':
				$uit[ $sleutel ] = in_array( $waarde, $def[2], true ) ? $waarde : $def[1];
				break;
			case 'url':
				$uit[ $sleutel ] = esc_url_raw( (string) $waarde );
				break;
			case 'email':
				$uit[ $sleutel ] = sanitize_email( (string) $waarde );
				break;
			case 'textarea':
				$uit[ $sleutel ] = sanitize_textarea_field( (string) $waarde );
				break;
			case 'widget':
				$uit[ $sleutel ] = thema_widget_opschonen( (string) $waarde );
				break;
			default:
				$uit[ $sleutel ] = sanitize_text_field( (string) $waarde );
		}
	}
	return $uit;
}

/**
 * Lettertypes die op de front-end nodig zijn.
 */
function thema_actieve_fonts() {
	$s     = thema_instellingen();
	$fonts = thema_fonts();
	$uit   = array();
	foreach ( array( 'font_kop', 'font_tekst', 'font_accent' ) as $rol ) {
		if ( isset( $fonts[ $s[ $rol ] ] ) ) {
			$uit[ $s[ $rol ] ] = $fonts[ $s[ $rol ] ];
		}
	}
	return $uit;
}

function thema_font_face( $fonts ) {
	$css = '';
	foreach ( $fonts as $font ) {
		foreach ( $font['bestanden'] as $stijl => $bestand ) {
			$css .= sprintf(
				"@font-face{font-family:'%s';font-style:%s;font-weight:%s;%sfont-display:swap;src:url('%s') format('woff2');}\n",
				$font['naam'],
				$stijl,
				$font['gewicht'],
				$font['breedte'] ? 'font-stretch:' . $font['breedte'] . ';' : '',
				THEMA_URL . '/assets/fonts/' . $bestand
			);
		}
	}
	return $css;
}

function thema_font_stack( $sleutel ) {
	$fonts = thema_fonts();
	$font  = $fonts[ $sleutel ] ?? $fonts['schibsted'];
	return sprintf( "'%s',%s", $font['naam'], $font['fallback'] );
}

/**
 * Kleuren van de eyebrow (tekst en hoekjes), zoals in het stijlpaneel.
 */
function thema_eyebrow_kleuren( $keuze ) {
	$s = thema_instellingen();
	$map = array(
		'oranje'      => array( $s['kleur_geel'], $s['kleur_paars'] ),
		'allesoranje' => array( $s['kleur_geel'], $s['kleur_geel'] ),
		'paars'       => array( $s['kleur_paars'], $s['kleur_paars'] ),
		'zacht'       => array( 'rgba(56,6,94,.6)', $s['kleur_paars'] ),
	);
	return $map[ $keuze ] ?? $map['oranje'];
}

/**
 * CSS-variabelen uit de instellingen. Vult de :root uit thema.css aan.
 */
function thema_css_variabelen() {
	$s      = thema_instellingen();
	$fonts  = thema_fonts();
	$kop    = $fonts[ $s['font_kop'] ] ?? $fonts['anybody'];
	$accent = $fonts[ $s['font_accent'] ] ?? $fonts['instrument'];
	$easing = array(
		'zacht'   => 'cubic-bezier(.2,.7,.2,1)',
		'veer'    => 'cubic-bezier(.34,1.56,.64,1)',
		'strak'   => 'cubic-bezier(.65,0,.35,1)',
		'lineair' => 'linear',
	);
	$eyebrow = thema_eyebrow_kleuren( $s['eyebrow'] );

	$vars = array(
		'--kleur-geel'          => $s['kleur_geel'],
		'--kleur-paars'         => $s['kleur_paars'],
		'--kleur-diep'          => $s['kleur_diep'],
		'--kleur-roze'          => $s['kleur_roze'],
		'--kleur-creme'         => $s['kleur_creme'],
		'--kleur-zand'          => $s['kleur_zand'],
		'--achtergrond'         => $s['achtergrond'],
		'--kop-letter'          => thema_font_stack( $s['font_kop'] ),
		'--tekst-letter'        => thema_font_stack( $s['font_tekst'] ),
		'--accent-letter'       => thema_font_stack( $s['font_accent'] ),
		'--accent-stijl'        => $accent['stijl'] ?? 'normal',
		'--accent-schaal'       => $accent['accent_schaal'] ?? 1,
		'--kop-schaal'          => round( $kop['schaal'] * ( (int) $s['kop_grootte'] / 100 ), 3 ),
		'--kop-gewicht'         => 'truculenta' === $s['font_kop'] ? 900 : (int) $s['kop_gewicht'],
		'--eyebrow-tekst'       => $eyebrow[0],
		'--eyebrow-hoek'        => $eyebrow[1],
		'--logo-hoogte'         => (int) $s['logo_hoogte'] . 'px',
		'--animatie-duur'       => (int) $s['animatie_duur'] . 'ms',
		'--animatie-vertraging' => (int) $s['animatie_vertraging'] . 'ms',
		'--animatie-easing'     => $easing[ $s['animatie_easing'] ],
	);

	$regels = '';
	foreach ( $vars as $naam => $waarde ) {
		$regels .= "\t{$naam}: {$waarde};\n";
	}
	return thema_font_face( thema_actieve_fonts() ) . ":root {\n{$regels}}\n";
}

function thema_body_classes() {
	$s = thema_instellingen();
	$c = array(
		'menu-' . $s['menu_stijl'],
		'hero-stijl-' . $s['hero_stijl'],
		'koppen-' . ( $s['animaties'] ? $s['kop_animatie'] : 'geen' ),
		'elementen-' . ( $s['animaties'] ? $s['element_animatie'] : 'geen' ),
		'voordelen-' . $s['voordelen_uitklap'],
		'accordeon-' . $s['faq_uitklap'],
		'kop-' . $s['font_kop'],
		'knoppen-' . $s['knoppen'],
	);
	if ( $s['animaties'] && 'uit' !== $s['cursor'] ) {
		$c[] = 'met-cursor';
		$c[] = 'cursor-' . $s['cursor'];
	}
	if ( ! $s['animaties'] ) {
		$c[] = 'zonder-animaties';
	}
	return $c;
}

/**
 * Trustindex: van een embedcode alleen het widget-ID bewaren. Een shortcode blijft een shortcode.
 */
function thema_widget_opschonen( $waarde ) {
	$waarde = trim( wp_unslash( $waarde ) );
	if ( '' === $waarde ) {
		return '';
	}
	if ( preg_match( '/loader\.js\?([A-Za-z0-9]+)/', $waarde, $m ) ) {
		return $m[1];
	}
	if ( '[' === $waarde[0] ) {
		return sanitize_text_field( $waarde );
	}
	return preg_replace( '/[^A-Za-z0-9]/', '', $waarde );
}

/**
 * Trustindex-widget tonen. Een widget-ID laadt pas als het bijna in beeld is.
 */
function thema_trustindex( $sleutel, $class = '' ) {
	$waarde = (string) thema_instellingen( $sleutel );
	if ( '' === $waarde ) {
		return '';
	}
	if ( '[' === $waarde[0] ) {
		return '<div class="trustindex ' . esc_attr( $class ) . '">' . do_shortcode( $waarde ) . '</div>';
	}
	return '<div class="trustindex ' . esc_attr( $class ) . '" data-trustindex="' . esc_attr( $waarde ) . '"></div>';
}

