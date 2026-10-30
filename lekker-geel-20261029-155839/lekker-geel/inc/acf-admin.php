<?php
/**
 * Prettige beheerervaring voor de flexibele blokken.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Icoon en korte omschrijving per blok, voor de blokkiezer en de blokkoppen.
 */
function thema_blok_info() {
	return array(
		'hero'          => array( 'vonk', 'Grote opening bovenaan de pagina, met wisselende plaats.' ),
		'offerte'       => array( 'timer', 'Offerte invullen als zin: datum, personen, plaats en bestemming.' ),
		'lopende_band'  => array( 'route', 'Lopende tekst, als paarse band of subtiel zonder achtergrond.' ),
		'over_ons'      => array( 'hart', 'Kop, tekst en knop naast een stapel van drie foto\'s.' ),
		'brede_foto'    => array( 'feest', 'Schermbrede foto die openklapt tijdens scrollen.' ),
		'fotoslider'    => array( 'feest', 'Schermbrede fotoslider met grote schuine knoppen onderaan.' ),
		'aan_boord'     => array( 'bus', 'Label en grote kop links, alinea rechts. Handig als opening van een sectie.' ),
		'voordelen'     => array( 'stoel', 'Voordelen als tabs met foto, lopen vanzelf door.' ),
		'gelegenheden'  => array( 'groep', 'Kaarten van de gelegenheden, meeschuivend of in een raster.' ),
		'werkgebied'    => array( 'route', 'Kaart van Nederland met plaatsen en reistijden.' ),
		'prijs'         => array( 'kalender', 'Waar de prijs van afhangt en wat er altijd bij zit.' ),
		'reviews'       => array( 'hart', 'Lopende band met reviews en de Google-score.' ),
		'video'         => array( 'speel', 'Video of poster, in een kader of over de volle breedte.' ),
		'faq'           => array( 'muziek', 'Uitklapbare vragen, met FAQ-schema voor Google.' ),
		'slot_cta'      => array( 'telefoon', 'Gele afsluiter met kop en knop, onderaan de pagina.' ),
		'tekst'         => array( 'mail', 'Vrije tekst met opmaak, smal, breed of in twee kolommen.' ),
		'formulier'     => array( 'beker', 'Een Gravity Forms formulier met kop en intro.' ),
		'contact'       => array( 'telefoon', 'Bel- en mailkaarten, formulier en adres. Gegevens komen uit de thema-instellingen.' ),
	);
}

add_action( 'admin_enqueue_scripts', function () {
	$scherm = get_current_screen();
	if ( ! $scherm || ! in_array( $scherm->post_type, array( 'page', 'gelegenheid' ), true ) || 'post' !== $scherm->base ) {
		return;
	}

	wp_enqueue_style( 'thema-admin-blokken', THEMA_URL . '/assets/css/admin-blokken.css', array(), THEMA_VERSIE );
	wp_add_inline_style( 'thema-admin-blokken', thema_font_face( array( thema_fonts()['schibsted'], thema_fonts()['anybody'] ) ) );
	wp_enqueue_script( 'thema-admin-blokken', THEMA_URL . '/assets/js/admin-blokken.js', array( 'jquery' ), THEMA_VERSIE, true );

	$info = array();
	foreach ( thema_blok_info() as $layout => $gegevens ) {
		$info[ $layout ] = array(
			'icoon' => thema_icoon( $gegevens[0], 20 ),
			'tekst' => $gegevens[1],
		);
	}
	$info['_iconen'] = array();
	foreach ( thema_icoon_keuzes() as $naam => $label ) {
		$info['_iconen'][ $naam ] = thema_icoon( $naam, 22 );
	}
	wp_localize_script( 'thema-admin-blokken', 'themaBlokken', $info );
} );

/**
 * Bloktitel in de beheerder: label plus de ingevulde kop.
 */
add_filter( 'acf/fields/flexible_content/layout_title/name=blokken', function ( $titel, $veld, $layout, $i ) {
	$kop = get_sub_field( 'kop' ) ?: trim( get_sub_field( 'kop_1' ) . ' ' . get_sub_field( 'kop_2' ) );
	$kop = $kop ? wp_strip_all_tags( str_replace( '*', '', $kop ) ) : '';

	return sprintf(
		'<span class="blok-label">%s</span>%s',
		esc_html( $layout['label'] ),
		$kop ? '<span class="blok-kop">' . esc_html( wp_trim_words( $kop, 9 ) ) . '</span>' : ''
	);
}, 10, 4 );

/**
 * Toelichting bovenaan het bewerkscherm.
 */
add_action( 'edit_form_after_title', function ( $post ) {
	if ( ! in_array( $post->post_type, array( 'page', 'gelegenheid' ), true ) ) {
		return;
	}
	printf(
		'<div class="blokken-intro"><span class="blokken-intro-icoon">%s</span><div><strong>Bouw deze pagina met blokken.</strong> Voeg blokken toe, sleep ze in de gewenste volgorde en klap ze in voor overzicht. Kleuren, lettertypes en animaties regel je in de <a href="%s">thema-instellingen</a>.</div></div>',
		thema_icoon( 'vonk', 20 ),
		esc_url( admin_url( 'admin.php?page=thema-instellingen' ) )
	);
} );
