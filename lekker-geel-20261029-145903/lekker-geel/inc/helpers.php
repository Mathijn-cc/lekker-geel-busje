<?php
/**
 * Hulpfuncties voor templates.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Tekst met accentwoorden: *zo* wordt het accentlettertype.
 */
function thema_accent( $tekst, $class = 'accent' ) {
	$html = esc_html( (string) $tekst );
	$html = preg_replace( '/\*(.+?)\*/u', '<span class="' . esc_attr( $class ) . '">$1</span>', $html );
	return nl2br( $html, false );
}

/**
 * Kop met accent. Krijgt data-kop voor de scroll-animatie.
 */
function thema_kop( $tekst, $tag = 'h2', $class = '', $extra = '' ) {
	if ( '' === trim( (string) $tekst ) ) {
		return '';
	}
	return sprintf( '<%1$s class="kop %2$s" data-kop%4$s>%3$s</%1$s>', tag_escape( $tag ), esc_attr( $class ), thema_accent( $tekst ), $extra );
}

/**
 * Eyebrow met de hoekjes uit het logo.
 */
function thema_eyebrow( $tekst ) {
	if ( ! $tekst ) {
		return '';
	}
	return '<span class="eyebrow" data-animeer>' . esc_html( $tekst ) . '</span>';
}

/**
 * Lijst uit een tekstvak: een item per regel.
 */
function thema_lijst( $tekst ) {
	return array_values( array_filter( array_map( 'trim', explode( "\n", (string) $tekst ) ) ) );
}

/**
 * Ronde knop met pijl. Varianten: paars, geel, creme.
 */
function thema_knop( $tekst, $url, $variant = 'paars', $extra = '' ) {
	if ( ! $tekst || ! $url ) {
		return '';
	}
	return sprintf(
		'<a class="knop-rond knop-rond-%1$s" href="%2$s"%3$s><span class="knop-tekst">%4$s</span><span class="knop-rond-pijl" aria-hidden="true">%5$s</span></a>',
		esc_attr( $variant ),
		esc_url( $url ),
		$extra,
		esc_html( $tekst ),
		thema_icoon( 'chevron' )
	);
}

/**
 * Schuine knop uit het ontwerp (geel of paars). Met de instelling Rond wordt hij een pil.
 */
function thema_knop_schuin( $tekst, $url, $variant = 'geel', $extra = '' ) {
	if ( ! $tekst || ! $url ) {
		return '';
	}
	return sprintf(
		'<a class="knop-schuin knop-schuin-%1$s" href="%2$s"%3$s><span class="knop-tekst">%4$s</span><span class="knop-pijl" aria-hidden="true">%5$s</span></a>',
		esc_attr( $variant ),
		esc_url( $url ),
		$extra,
		esc_html( $tekst ),
		thema_icoon( 'chevron' )
	);
}

/**
 * Knop op basis van een ACF-linkveld.
 */
function thema_knop_link( $link, $variant = 'paars', $schuin = false ) {
	if ( empty( $link['url'] ) ) {
		return '';
	}
	$doel = ! empty( $link['target'] ) ? ' target="' . esc_attr( $link['target'] ) . '" rel="noopener"' : '';
	if ( $schuin ) {
		return thema_knop_schuin( $link['title'] ?: 'Lees meer', $link['url'], $variant, $doel );
	}
	return thema_knop( $link['title'] ?: 'Lees meer', $link['url'], $variant, $doel );
}

/**
 * Knop van een blok, met de knopstijl uit de tab Weergave (vorm en kleur).
 */
function thema_blok_knop( $link, $blok, $kleur = 'paars', $schuin = false ) {
	$vorm = $blok['knop_vorm'] ?? 'standaard';
	if ( 'rond' === $vorm ) {
		$schuin = false;
	} elseif ( 'schuin' === $vorm ) {
		$schuin = true;
	}
	$kleur = in_array( $blok['knop_kleur'] ?? '', array( 'paars', 'wit' ), true ) ? $blok['knop_kleur'] : 'geel';
	return thema_knop_link( $link, $kleur, $schuin );
}

function thema_afbeelding( $beeld, $maat = 'large', $class = '', $lazy = true, $attr = array() ) {
	$id = is_array( $beeld ) ? ( $beeld['ID'] ?? 0 ) : (int) $beeld;
	if ( ! $id ) {
		return '';
	}
	return wp_get_attachment_image( $id, $maat, false, array_merge( array(
		'class'   => $class,
		'loading' => $lazy ? 'lazy' : 'eager',
	), $attr ) );
}

function thema_sectie_open( $blok, $layout, $extra = '', $attr = '' ) {
	$anker = ! empty( $blok['anker'] ) ? ' id="' . esc_attr( sanitize_title( $blok['anker'] ) ) . '"' : '';
	return sprintf( '<section class="sectie sectie-%1$s %2$s"%3$s%4$s>', esc_attr( $layout ), esc_attr( $extra ), $anker, $attr );
}

/**
 * Hoofdkop van een blok op het gekozen niveau zetten (h1, h2 of h3). Tussenkoppen blijven zoals ze zijn.
 */
function thema_blok_kop( $html, $niveau ) {
	if ( ! preg_match( '/<(h[1-6])\b(?=[^>]*(?:data-kop|class="hero-kop|class="slot-kop))/', $html, $m, PREG_OFFSET_CAPTURE ) ) {
		return $html;
	}
	$oud   = $m[1][0];
	$begin = $m[0][1];
	$einde = strpos( $html, '</' . $oud . '>', $begin );
	if ( false === $einde || $oud === $niveau ) {
		return $html;
	}
	return substr( $html, 0, $begin ) . '<' . $niveau . substr( $html, $begin + 3, $einde - $begin - 3 ) . '</' . $niveau . '>' . substr( $html, $einde + 5 );
}

/**
 * Achtergrond en ruimte uit de tabs Weergave en Geavanceerd op de eerste sectie zetten.
 */
function thema_blok_klassen( $html, $layout, $blok ) {
	$klassen       = array();
	$achtergronden = function_exists( 'thema_blok_achtergronden' ) ? thema_blok_achtergronden() : array();
	$kleur         = null;

	if ( isset( $achtergronden[ $layout ] ) ) {
		$kleur     = in_array( $blok['achtergrond'] ?? '', array( 'standaard', 'geel', 'paars' ), true ) ? $blok['achtergrond'] : $achtergronden[ $layout ];
		$klassen[] = 'achtergrond-' . $kleur;
	}
	foreach ( array( 'boven', 'onder' ) as $kant ) {
		$waarde = $blok[ 'ruimte_' . $kant ] ?? 'normaal';
		if ( in_array( $waarde, array( 'klein', 'geen', 'groot', 'extra' ), true ) ) {
			$klassen[] = 'ruimte-' . $kant . '-' . $waarde;
		}
	}
	if ( ! $klassen ) {
		return $html;
	}

	return preg_replace_callback( '/<section\b[^>]*>/', function ( $tag ) use ( $klassen, $kleur ) {
		$tag = $tag[0];
		$tag = preg_replace( '/class="([^"]*)"/', 'class="$1 ' . esc_attr( implode( ' ', $klassen ) ) . '"', $tag, 1 );
		if ( null !== $kleur ) {
			$tag = str_replace( ' data-donker', '', $tag );
			if ( 'paars' === $kleur ) {
				$tag = substr( $tag, 0, -1 ) . ' data-donker>';
			}
		}
		return $tag;
	}, $html, 1 );
}

function thema_blokken( $post_id = null ) {
	if ( ! function_exists( 'get_field' ) ) {
		echo '<div class="melding">Activeer Advanced Custom Fields PRO om de blokken te tonen.</div>';
		return false;
	}
	$blokken = get_field( 'blokken', $post_id ?: get_the_ID() );
	if ( empty( $blokken ) ) {
		return false;
	}
	foreach ( $blokken as $index => $blok ) {
		$layout = sanitize_key( $blok['acf_fc_layout'] ?? '' );
		if ( $layout && locate_template( 'template-parts/blokken/' . $layout . '.php' ) ) {
			ob_start();
			get_template_part( 'template-parts/blokken/' . $layout, null, array( 'blok' => $blok, 'index' => $index ) );
			$niveau = $blok['kopniveau'] ?? 'auto';
			if ( ! in_array( $niveau, array( 'h1', 'h2', 'h3' ), true ) ) {
				$niveau = 0 === $index ? 'h1' : 'h2';
			}
			echo thema_blok_klassen( thema_blok_kop( ob_get_clean(), $niveau ), $layout, $blok ); // phpcs:ignore
		}
	}
	return true;
}

function thema_logo_url() {
	$id = (int) thema_instellingen( 'logo' );
	return $id ? wp_get_attachment_image_url( $id, 'medium' ) : THEMA_URL . '/assets/img/logo.svg';
}

function thema_logo( $class = 'logo' ) {
	return sprintf(
		'<a class="%s" href="%s" rel="home"><img src="%s" alt="%s" width="220" height="60"></a>',
		esc_attr( $class ),
		esc_url( home_url( '/' ) ),
		esc_url( thema_logo_url() ),
		esc_attr( get_bloginfo( 'name' ) )
	);
}

function thema_offerte_url( $params = array() ) {
	$url = thema_instellingen( 'offerte_link' );
	if ( ! $url ) {
		$url = home_url( '/#offerte' );
	}
	return $params ? add_query_arg( $params, $url ) : $url;
}

function thema_telefoon_link( $nummer = null ) {
	$nummer = $nummer ?? thema_instellingen( 'telefoon' );
	return 'tel:' . preg_replace( '/[^0-9+]/', '', (string) $nummer );
}

function thema_vervang( $tekst ) {
	return str_replace( '{jaar}', gmdate( 'Y' ), (string) $tekst );
}

/**
 * Iconen uit het ontwerp (lijnstijl, currentColor).
 */
function thema_iconen() {
	return array(
		'chevron'   => '<path d="M9.5 6l6 6-6 6"/>',
		'pijl-op'   => '<path d="M7 17 17 7"/><path d="M8 7h9v9"/>',
		'terug'     => '<path d="M15 6l-6 6 6 6"/>',
		'telefoon'  => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>',
		'mail'      => '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
		'stoel'     => '<path d="M19 9V6a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v3"/><path d="M3 16a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-5a2 2 0 0 0-4 0v1.5a.5.5 0 0 1-.5.5h-9a.5.5 0 0 1-.5-.5V11a2 2 0 0 0-4 0z"/><path d="M5 18v2"/><path d="M19 18v2"/>',
		'groep'     => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
		'speaker'   => '<rect width="16" height="20" x="4" y="2" rx="2"/><path d="M12 6h.01"/><circle cx="12" cy="14" r="4"/><path d="M12 14h.01"/>',
		'bus'       => '<path d="M8 6v6"/><path d="M15 6v6"/><path d="M2 12h19.6"/><path d="M18 18h3s.5-1.7.8-2.8c.1-.4.2-.8.2-1.2 0-.4-.1-.8-.2-1.2l-1.4-5C20.1 6.8 19.1 6 18 6H4a2 2 0 0 0-2 2v10h3"/><circle cx="7" cy="18" r="2"/><path d="M9 18h5"/><circle cx="16" cy="18" r="2"/>',
		'feest'     => '<path d="M5.8 11.3 2 22l10.7-3.79"/><path d="M4 3h.01"/><path d="M22 8h.01"/><path d="M15 2h.01"/><path d="M22 20h.01"/><path d="m22 2-2.24.75a2.9 2.9 0 0 0-1.96 3.12c.1.86-.57 1.63-1.45 1.63h-.38c-.86 0-1.6.6-1.76 1.44L14 10"/><path d="m22 13-.82-.33c-.86-.34-1.82.2-1.98 1.11c-.11.7-.72 1.22-1.43 1.22H17"/><path d="m11 2 .33.82c.34.86-.2 1.82-1.11 1.98C9.52 4.9 9 5.52 9 6.23V7"/><path d="M11 13c1.93 1.93 2.83 4.17 2 5-.83.83-3.07-.07-5-2-1.93-1.93-2.83-4.17-2-5 .83-.83 3.07.07 5 2Z"/>',
		'schild'    => '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/>',
		'route'     => '<circle cx="6" cy="19" r="3"/><path d="M9 19h8.5a3.5 3.5 0 0 0 0-7h-11a3.5 3.5 0 0 1 0-7H15"/><circle cx="18" cy="5" r="3"/>',
		'kalender'  => '<path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/>',
		'klok'      => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
		'timer'     => '<path d="M10 2h4"/><path d="m12 14 3-3"/><circle cx="12" cy="14" r="8"/>',
		'muziek'    => '<path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/>',
		'beker'     => '<path d="M6 4h12l-1.6 15.2a1 1 0 0 1-1 .8H8.6a1 1 0 0 1-1-.8L6 4z"/><path d="M6.6 9.5h10.8"/>',
		'hart'      => '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>',
		'speel'     => '<path d="M7 4v16l13-8z" fill="currentColor"/>',
		'instagram' => '<rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/>',
		'facebook'  => '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>',
		'tiktok'    => '<path d="M14 3.5v11.2a3.3 3.3 0 1 1-3.3-3.3M14 3.5c.4 2.4 2.3 4.2 4.7 4.4"/>',
		'youtube'   => '<rect x="2.5" y="6" width="19" height="12" rx="4"/><path d="M10.5 9.5v5l4-2.5z"/>',
		'whatsapp'  => '<path d="M3 21l1.65-4.95A9 9 0 1 1 8.1 19.6z"/><path d="M9 9.5c0 3 2.5 5.5 5.5 5.5l1.2-1.4-1.9-1.1-.9.8c-1-.4-1.9-1.3-2.3-2.3l.8-.9-1.1-1.9z"/>',
		'vonk'      => '<path d="M12 3v4M12 17v4M3 12h4M17 12h4M6 6l2.5 2.5M15.5 15.5 18 18M18 6l-2.5 2.5M8.5 15.5 6 18"/>',
		'sluiten'   => '<path d="M6 6l12 12M18 6 6 18"/>',
		'huis'      => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9v11a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1V9"/>',
		'bestek'    => '<path d="M7 2v9"/><path d="M4 2v6a3 3 0 0 0 6 0V2"/><path d="M7 11v11"/><path d="M18 22V2c-2.2 1.3-3.5 3.7-3.5 6.5V13H18"/>',
		'maan'      => '<path d="M20.5 14.5A8.5 8.5 0 1 1 9.5 3.5a7 7 0 0 0 11 11z"/>',
		'extern'    => '<path d="M14 4h6v6"/><path d="M20 4 11 13"/><path d="M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/>',
		'glas'      => '<path d="M8 22h8"/><path d="M12 15v7"/><path d="M5 3h14l-1 6a6 6 0 0 1-12 0z"/><path d="M5.6 7h12.8"/>',
	);
}

function thema_icoon( $naam, $maat = 16, $dikte = null ) {
	$iconen = thema_iconen();
	if ( ! isset( $iconen[ $naam ] ) ) {
		return '';
	}
	$dikte = $dikte ?? ( in_array( $naam, array( 'chevron', 'terug', 'sluiten' ), true ) ? 2.6 : 2 );
	return sprintf(
		'<svg class="icoon" width="%1$d" height="%1$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="%2$s" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%3$s</svg>',
		$maat,
		esc_attr( $dikte ),
		$iconen[ $naam ]
	);
}

function thema_icoon_keuzes() {
	return array(
		'stoel'    => 'Stoel',
		'groep'    => 'Groep',
		'speaker'  => 'Speaker',
		'bus'      => 'Bus',
		'feest'    => 'Feest',
		'schild'   => 'Schild',
		'route'    => 'Route',
		'kalender' => 'Kalender',
		'klok'     => 'Klok',
		'muziek'   => 'Muziek',
		'beker'    => 'Beker',
		'hart'     => 'Hart',
		'vonk'     => 'Vonk',
		'huis'     => 'Huis',
		'bestek'   => 'Bestek',
		'glas'     => 'Glas',
		'maan'     => 'Maan',
	);
}

/**
 * Sterren-pil met score, gebruikt in hero's.
 */
/**
 * Pil met de score van Trustindex. Eerst de vaste score, het script vult de echte score en het aantal in.
 */
function thema_rating_pil( $class = '' ) {
	$s = thema_instellingen();
	return sprintf(
		'<span class="score-pil score-pil-trustindex %1$s" data-rating data-label="%2$s"><span class="sterren">★★★★★</span><span class="score-tekst" data-rating-tekst>%3$s %4$s</span><span class="score-aantal" data-rating-aantal></span>%5$s</span>',
		$class,
		esc_attr( $s['score_label'] ),
		esc_html( $s['score'] ?: '5,0' ),
		esc_html( $s['score_label'] ),
		thema_trustindex( 'ti_rating', 'trustindex-bron' )
	);
}

function thema_score_pil( $class = '' ) {
	$s = thema_instellingen();
	if ( 'trustindex' === $s['score_bron'] && $s['ti_rating'] ) {
		return thema_rating_pil( $class . '" data-hero-fade="' );
	}
	if ( ! $s['score'] ) {
		return '';
	}
	return sprintf(
		'<span class="score-pil %s" data-hero-fade><span class="sterren">★★★★★</span>%s %s</span>',
		esc_attr( $class ),
		esc_html( $s['score'] ),
		esc_html( $s['score_label'] )
	);
}

/**
 * Kantel en verschuiving van gelegenheidskaarten, zoals in het ontwerp.
 */
function thema_kaart_kantel( $i ) {
	$kantels = array( '-4deg', '3.5deg', '-2.5deg', '4deg', '-3deg' );
	$ys      = array( '14px', '-14px', '20px', '-10px', '12px' );
	return array( $kantels[ $i % 5 ], $ys[ $i % 5 ] );
}

/**
 * Stijl van een hero-blok: eigen keuze of die uit de instellingen.
 */
function thema_hero_stijl( $blok ) {
	$eigen = $blok['stijl'] ?? 'standaard';
	return ( $eigen && 'standaard' !== $eigen ) ? $eigen : thema_instellingen( 'hero_stijl' );
}

/**
 * Begint de pagina met een donkere hero? Dan is het menu eerst licht.
 */
function thema_start_donker() {
	if ( is_singular( 'gelegenheid' ) ) {
		return true;
	}
	if ( ! is_singular() || ! function_exists( 'get_field' ) ) {
		return false;
	}
	$blokken = get_field( 'blokken' );
	if ( empty( $blokken[0] ) || 'hero' !== ( $blokken[0]['acf_fc_layout'] ?? '' ) ) {
		return false;
	}
	return 'foto' === thema_hero_stijl( $blokken[0] );
}

/**
 * Foto met naamsvermelding uit het bijschrift en eventueel het label Tijdelijke foto.
 */
function thema_foto_met_credit( $id, $maat = 'breed', $tijdelijk = false, $class = '' ) {
	$id = (int) $id;
	if ( ! $id ) {
		return '';
	}
	$credit = wp_kses( (string) wp_get_attachment_caption( $id ), array( 'a' => array( 'href' => true, 'target' => true, 'rel' => true ) ) );
	$html   = '<figure class="foto-credit ' . esc_attr( $class ) . '">' . thema_afbeelding( $id, $maat );
	if ( $tijdelijk && current_user_can( 'edit_pages' ) ) {
		$html .= '<span class="foto-tijdelijk">Tijdelijke foto</span>';
	}
	if ( $credit ) {
		$GLOBALS['thema_fotobronnen'][ $id ] = $credit;
	}
	return $html . '</figure>';
}

/**
 * Tekst met lege regels als alinea's.
 */
function thema_alineas( $tekst, $class = 'tekst-groot' ) {
	$uit = '';
	foreach ( preg_split( '/\R{2,}/', trim( (string) $tekst ) ) as $alinea ) {
		if ( '' !== trim( $alinea ) ) {
			$uit .= '<p class="' . esc_attr( $class ) . '" data-animeer>' . nl2br( esc_html( trim( $alinea ) ), false ) . '</p>';
		}
	}
	return $uit;
}
