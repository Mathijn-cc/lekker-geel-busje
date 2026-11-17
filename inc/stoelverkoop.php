<?php
/**
 * Stoelverkoop: losse stoelen verkopen voor een vaste rit (try-out Vrienden van Amstel LIVE, november 2026).
 *
 * - Het blok Stoelen (template-parts/blokken/stoelen.php) bevat de ritten, opstapplekken en de prijs.
 * - Een aanmelding wordt een stoelboeking (eigen berichttype, niet openbaar) met status
 *   Nieuw, Tikkie verstuurd, Betaald of Geannuleerd. Geannuleerde stoelen komen weer vrij.
 * - Betalen gaat buiten de site om: Lekker Geel Busje stuurt per aanmelding een Tikkie.
 * - Geen nonce in het formulier: de pagina staat in de cache van SiteGround, een nonce verloopt dan.
 *   Spam houden we tegen met een verborgen veld en een minimale invultijd.
 */

defined( 'ABSPATH' ) || exit;

const THEMA_STOEL_TYPE = 'stoelboeking';

/**
 * Statussen van een boeking.
 */
function thema_stoel_statussen() {
	return array(
		'nieuw'       => 'Nieuw, Tikkie nog sturen',
		'tikkie'      => 'Tikkie verstuurd',
		'betaald'     => 'Betaald',
		'geannuleerd' => 'Geannuleerd',
	);
}

/* --------------------------------------------------------------------------
 * Berichttype
 * ----------------------------------------------------------------------- */

add_action( 'init', function () {
	register_post_type( THEMA_STOEL_TYPE, array(
		'labels'              => array(
			'name'               => 'Stoelverkoop',
			'singular_name'      => 'Stoelboeking',
			'menu_name'          => 'Stoelverkoop',
			'all_items'          => 'Alle boekingen',
			'edit_item'          => 'Boeking bekijken',
			'search_items'       => 'Boekingen zoeken',
			'not_found'          => 'Nog geen boekingen.',
			'not_found_in_trash' => 'Geen boekingen in de prullenbak.',
		),
		'public'              => false,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_rest'        => false,
		'exclude_from_search' => true,
		'menu_position'       => 26,
		'menu_icon'           => 'dashicons-tickets-alt',
		'supports'            => array( 'title' ),
		'capability_type'     => 'post',
		'capabilities'        => array( 'create_posts' => 'do_not_allow' ),
		'map_meta_cap'        => true,
	) );
} );

/* --------------------------------------------------------------------------
 * Instellingen uit het blok
 * ----------------------------------------------------------------------- */

/**
 * Het blok Stoelen van een pagina, met opgeschoonde ritten en opstapplekken. Null als er geen is.
 */
function thema_stoelen_config( $pagina ) {
	static $cache = array();
	$pagina = (int) $pagina;
	if ( isset( $cache[ $pagina ] ) ) {
		return $cache[ $pagina ];
	}
	$cache[ $pagina ] = null;
	if ( ! $pagina || ! function_exists( 'get_field' ) ) {
		return null;
	}
	foreach ( (array) get_field( 'blokken', $pagina ) as $blok ) {
		if ( 'stoelen' === ( $blok['acf_fc_layout'] ?? '' ) ) {
			$cache[ $pagina ] = thema_stoelen_normaliseer( $blok, $pagina );
			break;
		}
	}
	return $cache[ $pagina ];
}

function thema_stoelen_normaliseer( $blok, $pagina ) {
	$zone    = wp_timezone();
	$vandaag = new DateTimeImmutable( 'today', $zone );
	$sluit   = max( 0, (int) ( $blok['sluit_dagen'] ?? 1 ) );

	$ritten = array();
	foreach ( (array) ( $blok['ritten'] ?? array() ) as $rit ) {
		$sleutel = preg_replace( '/\D/', '', (string) ( $rit['datum'] ?? '' ) );
		$datum   = 8 === strlen( $sleutel ) ? DateTimeImmutable::createFromFormat( '!Ymd', $sleutel, $zone ) : false;
		if ( ! $datum || $datum < $vandaag ) {
			continue; // Voorbije ritten tonen we niet meer.
		}
		$stoelen = max( 0, (int) ( $rit['stoelen'] ?? 0 ) );
		$bezet   = thema_stoelen_bezet( $pagina, $sleutel );
		$vrij    = max( 0, $stoelen - $bezet );
		$status  = $rit['status'] ?? 'open';
		$te_laat = $datum->modify( '-' . $sluit . ' days' ) < $vandaag;
		if ( 'open' === $status && ( $te_laat || ! $vrij ) ) {
			$status = $vrij ? 'gesloten' : 'vol';
		}
		$ritten[ $sleutel ] = array(
			'sleutel'  => $sleutel,
			'datum'    => $datum,
			'label'    => trim( (string) ( $rit['label'] ?? '' ) ),
			'stoelen'  => $stoelen,
			'minimum'  => max( 0, (int) ( $rit['minimum'] ?? 0 ) ),
			'bezet'    => $bezet,
			'vrij'     => $vrij,
			'status'   => in_array( $status, array( 'open', 'vol', 'gesloten' ), true ) ? $status : 'open',
		);
	}
	ksort( $ritten );

	$opstap = array();
	foreach ( (array) ( $blok['opstapplekken'] ?? array() ) as $plek ) {
		$plaats = trim( (string) ( $plek['plaats'] ?? '' ) );
		if ( '' === $plaats ) {
			continue;
		}
		$opstap[ sanitize_title( $plaats ) ] = array(
			'sleutel' => sanitize_title( $plaats ),
			'plaats'  => $plaats,
			'plek'    => trim( (string) ( $plek['plek'] ?? '' ) ),
			'heen'    => trim( (string) ( $plek['heen'] ?? '' ) ),
			'terug'   => trim( (string) ( $plek['terug'] ?? '' ) ),
		);
	}

	return array(
		'pagina'     => (int) $pagina,
		'evenement'  => trim( (string) ( $blok['evenement'] ?? '' ) ) ?: get_the_title( $pagina ),
		'bestemming' => trim( (string) ( $blok['bestemming'] ?? '' ) ),
		'prijs'      => max( 0, (float) ( $blok['prijs'] ?? 25 ) ),
		'max'        => max( 1, (int) ( $blok['max_per_boeking'] ?? 10 ) ),
		'krap'       => max( 0, (int) ( $blok['krap_vanaf'] ?? 15 ) ),
		'ritten'     => $ritten,
		'opstap'     => $opstap,
		'stappen'    => thema_lijst( $blok['na_aanmelden'] ?? '' ),
		'ontvanger'  => is_email( $blok['ontvanger'] ?? '' ) ? $blok['ontvanger'] : thema_instellingen( 'email' ),
		'blok'       => $blok,
	);
}

/**
 * Bezette stoelen op een rit: alle boekingen behalve geannuleerde.
 */
function thema_stoelen_bezet( $pagina, $rit ) {
	global $wpdb;
	$som = $wpdb->get_var( $wpdb->prepare(
		"SELECT SUM( CAST( aantal.meta_value AS UNSIGNED ) )
		FROM {$wpdb->posts} p
		INNER JOIN {$wpdb->postmeta} pagina ON pagina.post_id = p.ID AND pagina.meta_key = '_stoel_pagina' AND pagina.meta_value = %s
		INNER JOIN {$wpdb->postmeta} rit ON rit.post_id = p.ID AND rit.meta_key = '_stoel_rit' AND rit.meta_value = %s
		INNER JOIN {$wpdb->postmeta} aantal ON aantal.post_id = p.ID AND aantal.meta_key = '_stoel_aantal'
		LEFT JOIN {$wpdb->postmeta} status ON status.post_id = p.ID AND status.meta_key = '_stoel_status'
		WHERE p.post_type = %s AND p.post_status = 'publish' AND ( status.meta_value IS NULL OR status.meta_value <> 'geannuleerd' )",
		(string) (int) $pagina,
		(string) $rit,
		THEMA_STOEL_TYPE
	) );
	return (int) $som;
}

/**
 * Datum van een rit voor mensen: vrijdag 15 januari.
 */
function thema_stoelen_datum( $datum, $formaat = 'l j F' ) {
	return wp_date( $formaat, $datum->getTimestamp(), $datum->getTimezone() );
}

function thema_stoelen_euro( $bedrag ) {
	$bedrag = (float) $bedrag;
	return '€ ' . ( floor( $bedrag ) == $bedrag ? number_format_i18n( $bedrag ) : number_format_i18n( $bedrag, 2 ) ); // phpcs:ignore
}

/* --------------------------------------------------------------------------
 * Aanmelden
 * ----------------------------------------------------------------------- */

add_action( 'admin_post_nopriv_thema_stoelen', 'thema_stoelen_aanmelden' );
add_action( 'admin_post_thema_stoelen', 'thema_stoelen_aanmelden' );

function thema_stoelen_aanmelden() {
	$in     = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification
	$pagina = absint( $in['pagina'] ?? 0 );
	$terug  = $pagina ? get_permalink( $pagina ) : home_url( '/' );
	$config = thema_stoelen_config( $pagina );
	if ( ! $config || ! $terug ) {
		wp_safe_redirect( home_url( '/' ) );
		exit;
	}

	/* Spam: het verborgen veld moet leeg zijn en het invullen duurt langer dan drie seconden. */
	$begin = (int) ( $in['begin'] ?? 0 );
	if ( ! empty( $in['website'] ) || ( $begin && time() - $begin < 3 ) ) {
		wp_safe_redirect( $terug . '#stoelen' );
		exit;
	}

	$waarden = array(
		'rit'          => preg_replace( '/\D/', '', (string) ( $in['rit'] ?? '' ) ),
		'opstap'       => sanitize_title( (string) ( $in['opstap'] ?? '' ) ),
		'aantal'       => absint( $in['aantal'] ?? 0 ),
		'naam'         => sanitize_text_field( (string) ( $in['naam'] ?? '' ) ),
		'email'        => sanitize_email( (string) ( $in['email'] ?? '' ) ),
		'telefoon'     => sanitize_text_field( (string) ( $in['telefoon'] ?? '' ) ),
		'meereizigers' => sanitize_textarea_field( (string) ( $in['meereizigers'] ?? '' ) ),
		'akkoord'      => ! empty( $in['akkoord'] ),
	);

	$fouten = array();
	$rit    = $config['ritten'][ $waarden['rit'] ] ?? null;
	if ( ! $rit ) {
		$fouten['rit'] = 'Kies een avond.';
	} elseif ( 'open' !== $rit['status'] ) {
		$fouten['rit'] = 'vol' === $rit['status'] ? 'Deze avond is helaas vol. Kies een andere avond.' : 'Voor deze avond kun je je niet meer aanmelden. Kies een andere avond.';
	}
	if ( ! isset( $config['opstap'][ $waarden['opstap'] ] ) ) {
		$fouten['opstap'] = 'Kies waar je instapt.';
	}
	if ( $waarden['aantal'] < 1 || $waarden['aantal'] > $config['max'] ) {
		$fouten['aantal'] = 'Kies 1 tot ' . $config['max'] . ' stoelen.';
	} elseif ( $rit && 'open' === $rit['status'] && $waarden['aantal'] > $rit['vrij'] ) {
		$fouten['aantal'] = 1 === $rit['vrij'] ? 'Er is nog 1 stoel vrij op deze avond.' : 'Er zijn nog ' . $rit['vrij'] . ' stoelen vrij op deze avond.';
	}
	if ( mb_strlen( $waarden['naam'] ) < 2 ) {
		$fouten['naam'] = 'Vul je naam in.';
	}
	if ( ! is_email( $waarden['email'] ) ) {
		$fouten['email'] = 'Vul een geldig e-mailadres in, daar sturen we de bevestiging naartoe.';
	}
	$cijfers = preg_replace( '/\D/', '', $waarden['telefoon'] );
	if ( ! preg_match( '/^\+?[0-9\s().\/-]+$/', $waarden['telefoon'] ) || strlen( $cijfers ) < 9 || strlen( $cijfers ) > 15 ) {
		$fouten['telefoon'] = 'Vul je mobiele nummer in, zoals 06 12345678. Daar sturen we de Tikkie naartoe.';
	}
	if ( ! $waarden['akkoord'] ) {
		$fouten['akkoord'] = 'Vink aan dat je 18 jaar of ouder bent en akkoord gaat met de huisregels.';
	}

	if ( $fouten ) {
		$sleutel = strtolower( wp_generate_password( 12, false ) );
		set_transient( 'thema_stoelen_' . $sleutel, array( 'waarden' => $waarden, 'fouten' => $fouten ), 15 * MINUTE_IN_SECONDS );
		wp_safe_redirect( add_query_arg( 'stoelfout', $sleutel, $terug ) . '#stoelen' );
		exit;
	}

	$opstap = $config['opstap'][ $waarden['opstap'] ];
	$bedrag = $waarden['aantal'] * $config['prijs'];
	$code   = strtoupper( wp_generate_password( 6, false ) );
	$titel  = sprintf(
		'%s, %d %s, %s, %s',
		$waarden['naam'],
		$waarden['aantal'],
		1 === $waarden['aantal'] ? 'stoel' : 'stoelen',
		thema_stoelen_datum( $rit['datum'], 'D j M' ),
		$opstap['plaats']
	);

	$id = wp_insert_post( array(
		'post_type'   => THEMA_STOEL_TYPE,
		'post_status' => 'publish',
		'post_title'  => $titel,
	), true );
	if ( is_wp_error( $id ) ) {
		wp_safe_redirect( add_query_arg( 'stoelfout', 'opslaan', $terug ) . '#stoelen' );
		exit;
	}
	$meta = array(
		'_stoel_pagina'       => (string) $pagina,
		'_stoel_rit'          => $waarden['rit'],
		'_stoel_opstap'       => $waarden['opstap'],
		'_stoel_opstap_naam'  => $opstap['plaats'],
		'_stoel_aantal'       => $waarden['aantal'],
		'_stoel_prijs'        => $config['prijs'],
		'_stoel_bedrag'       => $bedrag,
		'_stoel_naam'         => $waarden['naam'],
		'_stoel_email'        => $waarden['email'],
		'_stoel_telefoon'     => $waarden['telefoon'],
		'_stoel_meereizigers' => $waarden['meereizigers'],
		'_stoel_status'       => 'nieuw',
		'_stoel_code'         => $code,
	);
	foreach ( $meta as $sleutel => $waarde ) {
		update_post_meta( $id, $sleutel, $waarde );
	}

	thema_stoelen_mails( $id, $config, $rit, $opstap );

	wp_safe_redirect( add_query_arg( 'stoel', $code, $terug ) . '#stoelen' );
	exit;
}

/**
 * Bevestiging naar de klant en een melding naar Lekker Geel Busje.
 */
function thema_stoelen_mails( $id, $config, $rit, $opstap ) {
	$m       = fn( $k ) => get_post_meta( $id, '_stoel_' . $k, true );
	$aantal  = (int) $m( 'aantal' );
	$stoelen = $aantal . ' ' . ( 1 === $aantal ? 'stoel' : 'stoelen' );
	$avond   = thema_stoelen_datum( $rit['datum'] );
	$bedrag  = thema_stoelen_euro( $m( 'bedrag' ) );
	$site    = get_bloginfo( 'name' );
	$voornaam = strtok( (string) $m( 'naam' ), ' ' );

	$regels = array(
		'Hoi ' . $voornaam . ',',
		'',
		'Gaaf dat je meegaat! We hebben ' . $stoelen . ' voor je vastgehouden in de bus naar ' . $config['evenement'] . '.',
		'',
		'Avond:        ' . ucfirst( $avond ),
		'Instappen:    ' . $opstap['plaats'] . ( $opstap['heen'] ? ', vertrek ' . $opstap['heen'] : '' ),
		$opstap['plek'] ? 'Opstapplek:   ' . $opstap['plek'] : null,
		'Stoelen:      ' . $aantal . ' x ' . thema_stoelen_euro( $config['prijs'] ) . ' = ' . $bedrag,
		'Je code:      ' . $m( 'code' ),
		'',
		'Zo gaat het verder:',
	);
	foreach ( $config['stappen'] as $i => $stap ) {
		$regels[] = ( $i + 1 ) . '. ' . $stap;
	}
	$regels = array_merge( $regels, array(
		'',
		'Let op: je ticket voor het concert zelf regel je apart, de stoel in de bus is alleen het vervoer.',
		'',
		'Vragen? Bel ' . thema_instellingen( 'telefoon' ) . ' of beantwoord deze mail.',
		'',
		'Tot in de bus!',
		$site,
	) );

	wp_mail(
		$m( 'email' ),
		'Je stoel in de bus naar ' . $config['evenement'] . ' is gereserveerd',
		implode( "\n", array_filter( $regels, fn( $r ) => null !== $r ) ),
		array( 'Reply-To: ' . $site . ' <' . $config['ontvanger'] . '>' )
	);

	$intern = array(
		'Nieuwe aanmelding voor ' . $config['evenement'] . '. Stuur een Tikkie van ' . $bedrag . '.',
		'',
		'Naam:         ' . $m( 'naam' ),
		'Mobiel:       ' . $m( 'telefoon' ),
		'E-mail:       ' . $m( 'email' ),
		'Avond:        ' . ucfirst( $avond ),
		'Instappen:    ' . $opstap['plaats'],
		'Stoelen:      ' . $stoelen,
		'Bedrag:       ' . $bedrag,
		'Code:         ' . $m( 'code' ),
		$m( 'meereizigers' ) ? "Meereizigers:\n" . $m( 'meereizigers' ) : null,
		'',
		'Nu bezet op deze avond: ' . thema_stoelen_bezet( $config['pagina'], $rit['sleutel'] ) . ' van ' . $rit['stoelen'] . '.',
		'',
		'Bekijken en status bijwerken: ' . admin_url( 'post.php?post=' . (int) $id . '&action=edit' ),
	);
	wp_mail(
		$config['ontvanger'],
		'Stoelverkoop: ' . $stoelen . ', ' . $avond . ', ' . $opstap['plaats'],
		implode( "\n", array_filter( $intern, fn( $r ) => null !== $r ) ),
		array( 'Reply-To: ' . $m( 'naam' ) . ' <' . $m( 'email' ) . '>' )
	);
}

/**
 * Een boeking op code, voor de bevestiging op de pagina.
 */
function thema_stoelen_boeking_op_code( $code, $pagina ) {
	$code = strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', (string) $code ) );
	if ( strlen( $code ) < 6 ) {
		return null;
	}
	$ids = get_posts( array(
		'post_type'      => THEMA_STOEL_TYPE,
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'meta_query'     => array(
			array( 'key' => '_stoel_code', 'value' => $code ),
			array( 'key' => '_stoel_pagina', 'value' => (string) (int) $pagina ),
		),
	) );
	return $ids ? (int) $ids[0] : null;
}

/* Bevestiging en foutmeldingen nooit uit de cache. */
add_action( 'template_redirect', function () {
	if ( isset( $_GET['stoel'] ) || isset( $_GET['stoelfout'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		nocache_headers();
		header( 'X-Robots-Tag: noindex' );
	}
} );

/* --------------------------------------------------------------------------
 * Beheer
 * ----------------------------------------------------------------------- */

add_filter( 'manage_' . THEMA_STOEL_TYPE . '_posts_columns', function () {
	return array(
		'cb'           => '<input type="checkbox" />',
		'title'        => 'Boeking',
		'stoel_avond'  => 'Avond',
		'stoel_opstap' => 'Instappen',
		'stoel_aantal' => 'Stoelen',
		'stoel_bedrag' => 'Bedrag',
		'stoel_mobiel' => 'Mobiel',
		'stoel_status' => 'Status',
		'date'         => 'Aangemeld',
	);
} );

add_action( 'manage_' . THEMA_STOEL_TYPE . '_posts_custom_column', function ( $kolom, $id ) {
	$m = fn( $k ) => get_post_meta( $id, '_stoel_' . $k, true );
	switch ( $kolom ) {
		case 'stoel_avond':
			$d = DateTimeImmutable::createFromFormat( '!Ymd', (string) $m( 'rit' ), wp_timezone() );
			echo esc_html( $d ? thema_stoelen_datum( $d, 'D j M Y' ) : $m( 'rit' ) );
			break;
		case 'stoel_opstap':
			echo esc_html( $m( 'opstap_naam' ) );
			break;
		case 'stoel_aantal':
			echo (int) $m( 'aantal' );
			break;
		case 'stoel_bedrag':
			echo esc_html( thema_stoelen_euro( $m( 'bedrag' ) ) );
			break;
		case 'stoel_mobiel':
			echo '<a href="' . esc_url( thema_telefoon_link( $m( 'telefoon' ) ) ) . '">' . esc_html( $m( 'telefoon' ) ) . '</a>';
			break;
		case 'stoel_status':
			$status = $m( 'status' ) ?: 'nieuw';
			printf( '<span class="stoel-status stoel-status-%s">%s</span>', esc_attr( $status ), esc_html( thema_stoel_statussen()[ $status ] ?? $status ) );
			break;
	}
}, 10, 2 );

/* Snel de status wijzigen vanuit de lijst. */
add_filter( 'post_row_actions', function ( $acties, $post ) {
	if ( THEMA_STOEL_TYPE !== $post->post_type || ! current_user_can( 'edit_post', $post->ID ) ) {
		return $acties;
	}
	unset( $acties['inline hide-if-no-js'], $acties['view'] );
	$huidig = get_post_meta( $post->ID, '_stoel_status', true ) ?: 'nieuw';
	foreach ( array( 'tikkie' => 'Tikkie verstuurd', 'betaald' => 'Betaald', 'geannuleerd' => 'Annuleren' ) as $status => $label ) {
		if ( $status === $huidig ) {
			continue;
		}
		$url = wp_nonce_url( admin_url( 'admin-post.php?action=thema_stoel_status&boeking=' . $post->ID . '&status=' . $status ), 'stoel_status_' . $post->ID );
		$acties[ 'stoel_' . $status ] = '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
	}
	return $acties;
}, 10, 2 );

add_action( 'admin_post_thema_stoel_status', function () {
	$id     = absint( $_GET['boeking'] ?? 0 );
	$status = sanitize_key( $_GET['status'] ?? '' );
	check_admin_referer( 'stoel_status_' . $id );
	if ( ! current_user_can( 'edit_post', $id ) || ! isset( thema_stoel_statussen()[ $status ] ) ) {
		wp_die( 'Dat mag niet.' );
	}
	update_post_meta( $id, '_stoel_status', $status );
	wp_safe_redirect( wp_get_referer() ?: admin_url( 'edit.php?post_type=' . THEMA_STOEL_TYPE ) );
	exit;
} );

/* Filter op avond en status boven de lijst. */
add_action( 'restrict_manage_posts', function ( $type ) {
	if ( THEMA_STOEL_TYPE !== $type ) {
		return;
	}
	global $wpdb;
	$ritten = $wpdb->get_col( "SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_stoel_rit' ORDER BY meta_value" );
	$gekozen = sanitize_text_field( wp_unslash( $_GET['stoel_rit'] ?? '' ) ); // phpcs:ignore
	echo '<select name="stoel_rit"><option value="">Alle avonden</option>';
	foreach ( $ritten as $rit ) {
		$d = DateTimeImmutable::createFromFormat( '!Ymd', (string) $rit, wp_timezone() );
		printf( '<option value="%s"%s>%s</option>', esc_attr( $rit ), selected( $gekozen, $rit, false ), esc_html( $d ? thema_stoelen_datum( $d, 'D j M Y' ) : $rit ) );
	}
	echo '</select>';
	$status = sanitize_key( $_GET['stoel_status'] ?? '' ); // phpcs:ignore
	echo '<select name="stoel_status"><option value="">Alle statussen</option>';
	foreach ( thema_stoel_statussen() as $sleutel => $label ) {
		printf( '<option value="%s"%s>%s</option>', esc_attr( $sleutel ), selected( $status, $sleutel, false ), esc_html( $label ) );
	}
	echo '</select>';
} );

add_action( 'pre_get_posts', function ( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() || THEMA_STOEL_TYPE !== $query->get( 'post_type' ) ) {
		return;
	}
	$meta = array();
	$rit  = preg_replace( '/\D/', '', (string) ( $_GET['stoel_rit'] ?? '' ) ); // phpcs:ignore
	if ( $rit ) {
		$meta[] = array( 'key' => '_stoel_rit', 'value' => $rit );
	}
	$status = sanitize_key( $_GET['stoel_status'] ?? '' ); // phpcs:ignore
	if ( $status ) {
		$meta[] = array( 'key' => '_stoel_status', 'value' => $status );
	}
	if ( $meta ) {
		$query->set( 'meta_query', $meta );
	}
} );

/* Bekijken en bewerken: gegevens en status in een kader. */
add_action( 'add_meta_boxes_' . THEMA_STOEL_TYPE, function () {
	add_meta_box( 'stoel_gegevens', 'Boeking', function ( $post ) {
		$m      = fn( $k ) => get_post_meta( $post->ID, '_stoel_' . $k, true );
		$d      = DateTimeImmutable::createFromFormat( '!Ymd', (string) $m( 'rit' ), wp_timezone() );
		$status = $m( 'status' ) ?: 'nieuw';
		wp_nonce_field( 'stoel_opslaan', 'stoel_nonce' );
		$rijen = array(
			'Avond'        => $d ? ucfirst( thema_stoelen_datum( $d, 'l j F Y' ) ) : $m( 'rit' ),
			'Instappen'    => $m( 'opstap_naam' ),
			'Stoelen'      => (int) $m( 'aantal' ) . ' x ' . thema_stoelen_euro( $m( 'prijs' ) ),
			'Bedrag'       => thema_stoelen_euro( $m( 'bedrag' ) ),
			'Naam'         => $m( 'naam' ),
			'Mobiel'       => $m( 'telefoon' ),
			'E-mail'       => $m( 'email' ),
			'Meereizigers' => $m( 'meereizigers' ),
			'Code'         => $m( 'code' ),
		);
		echo '<table class="form-table" role="presentation"><tbody>';
		foreach ( $rijen as $label => $waarde ) {
			printf( '<tr><th scope="row">%s</th><td>%s</td></tr>', esc_html( $label ), nl2br( esc_html( (string) $waarde ) ) );
		}
		echo '<tr><th scope="row"><label for="stoel_status">Status</label></th><td><select id="stoel_status" name="stoel_status">';
		foreach ( thema_stoel_statussen() as $sleutel => $label ) {
			printf( '<option value="%s"%s>%s</option>', esc_attr( $sleutel ), selected( $status, $sleutel, false ), esc_html( $label ) );
		}
		echo '</select><p class="description">Geannuleerd: de stoelen komen weer vrij op de site.</p></td></tr>';
		echo '</tbody></table>';
	}, THEMA_STOEL_TYPE, 'normal', 'high' );
} );

add_action( 'save_post_' . THEMA_STOEL_TYPE, function ( $id ) {
	if ( ! isset( $_POST['stoel_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['stoel_nonce'] ), 'stoel_opslaan' ) || ! current_user_can( 'edit_post', $id ) ) {
		return;
	}
	$status = sanitize_key( $_POST['stoel_status'] ?? '' );
	if ( isset( thema_stoel_statussen()[ $status ] ) ) {
		update_post_meta( $id, '_stoel_status', $status );
	}
} );

/* Overzicht per avond en de passagierslijst als CSV. */
add_action( 'admin_menu', function () {
	add_submenu_page(
		'edit.php?post_type=' . THEMA_STOEL_TYPE,
		'Overzicht per avond',
		'Overzicht per avond',
		'edit_posts',
		'stoelverkoop-overzicht',
		'thema_stoelen_overzicht'
	);
} );

function thema_stoelen_overzicht() {
	global $wpdb;
	$paginas = $wpdb->get_col( "SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_stoel_pagina'" );
	echo '<div class="wrap"><h1>Stoelverkoop per avond</h1>';
	echo '<p>Bezet telt alle boekingen behalve geannuleerde. De CSV is de passagierslijst voor de chauffeur en het gastvrouwke.</p>';
	if ( ! $paginas ) {
		echo '<p>Nog geen boekingen.</p></div>';
		return;
	}
	foreach ( $paginas as $pagina ) {
		$config = thema_stoelen_config( (int) $pagina );
		printf( '<h2>%s</h2>', esc_html( $config ? $config['evenement'] : get_the_title( (int) $pagina ) ) );
		$ritten = $wpdb->get_col( $wpdb->prepare(
			"SELECT DISTINCT r.meta_value FROM {$wpdb->postmeta} r INNER JOIN {$wpdb->postmeta} p ON p.post_id = r.post_id AND p.meta_key = '_stoel_pagina' AND p.meta_value = %s WHERE r.meta_key = '_stoel_rit' ORDER BY r.meta_value",
			$pagina
		) );
		echo '<table class="widefat striped"><thead><tr><th>Avond</th><th>Instappen</th><th>Bezet</th><th>Betaald</th><th>Nog Tikkie sturen</th><th></th></tr></thead><tbody>';
		foreach ( $ritten as $rit ) {
			$boekingen = thema_stoelen_boekingen( (int) $pagina, $rit );
			$per       = array();
			foreach ( $boekingen as $b ) {
				if ( 'geannuleerd' === $b['status'] ) {
					continue;
				}
				$plek = $b['opstap'];
				$per[ $plek ] = $per[ $plek ] ?? array( 'bezet' => 0, 'betaald' => 0, 'nieuw' => 0 );
				$per[ $plek ]['bezet'] += $b['aantal'];
				if ( 'betaald' === $b['status'] ) {
					$per[ $plek ]['betaald'] += $b['aantal'];
				}
				if ( 'nieuw' === $b['status'] ) {
					$per[ $plek ]['nieuw'] += $b['aantal'];
				}
			}
			$d      = DateTimeImmutable::createFromFormat( '!Ymd', (string) $rit, wp_timezone() );
			$totaal = array_sum( array_column( $per, 'bezet' ) );
			$cap    = $config['ritten'][ $rit ]['stoelen'] ?? null;
			$csv    = wp_nonce_url( admin_url( 'admin-post.php?action=thema_stoelen_csv&pagina=' . (int) $pagina . '&rit=' . $rit ), 'stoelen_csv' );
			printf(
				'<tr><th scope="row"><strong>%s</strong></th><td><em>totaal</em></td><td><strong>%s</strong></td><td>%d</td><td>%d</td><td><a class="button" href="%s">Passagierslijst (CSV)</a></td></tr>',
				esc_html( $d ? ucfirst( thema_stoelen_datum( $d, 'l j F Y' ) ) : $rit ),
				esc_html( $totaal . ( $cap ? ' van ' . $cap : '' ) ),
				array_sum( array_column( $per, 'betaald' ) ),
				array_sum( array_column( $per, 'nieuw' ) ),
				esc_url( $csv )
			);
			foreach ( $per as $plek => $tel ) {
				printf( '<tr><td></td><td>%s</td><td>%d</td><td>%d</td><td>%d</td><td></td></tr>', esc_html( $plek ), $tel['bezet'], $tel['betaald'], $tel['nieuw'] );
			}
		}
		echo '</tbody></table>';
	}
	echo '</div>';
}

function thema_stoelen_boekingen( $pagina, $rit ) {
	$ids = get_posts( array(
		'post_type'      => THEMA_STOEL_TYPE,
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'orderby'        => 'date',
		'order'          => 'ASC',
		'meta_query'     => array(
			array( 'key' => '_stoel_pagina', 'value' => (string) (int) $pagina ),
			array( 'key' => '_stoel_rit', 'value' => (string) $rit ),
		),
	) );
	$uit = array();
	foreach ( $ids as $id ) {
		$m     = fn( $k ) => get_post_meta( $id, '_stoel_' . $k, true );
		$uit[] = array(
			'opstap'       => $m( 'opstap_naam' ),
			'aantal'       => (int) $m( 'aantal' ),
			'bedrag'       => (float) $m( 'bedrag' ),
			'naam'         => $m( 'naam' ),
			'telefoon'     => $m( 'telefoon' ),
			'email'        => $m( 'email' ),
			'meereizigers' => $m( 'meereizigers' ),
			'status'       => $m( 'status' ) ?: 'nieuw',
			'code'         => $m( 'code' ),
		);
	}
	return $uit;
}

add_action( 'admin_post_thema_stoelen_csv', function () {
	check_admin_referer( 'stoelen_csv' );
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( 'Dat mag niet.' );
	}
	$pagina = absint( $_GET['pagina'] ?? 0 );
	$rit    = preg_replace( '/\D/', '', (string) ( $_GET['rit'] ?? '' ) );
	$rijen  = thema_stoelen_boekingen( $pagina, $rit );
	usort( $rijen, fn( $a, $b ) => strcmp( $a['opstap'], $b['opstap'] ) );
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="passagiers-' . $rit . '.csv"' );
	$uit = fopen( 'php://output', 'w' );
	fwrite( $uit, "\xEF\xBB\xBF" ); // Excel herkent zo de tekens.
	$statussen = thema_stoel_statussen();
	fputcsv( $uit, array( 'Instappen', 'Naam', 'Stoelen', 'Meereizigers', 'Mobiel', 'E-mail', 'Bedrag', 'Status', 'Code' ), ';' );
	foreach ( $rijen as $r ) {
		if ( 'geannuleerd' === $r['status'] ) {
			continue;
		}
		fputcsv( $uit, array( $r['opstap'], $r['naam'], $r['aantal'], str_replace( "\n", ', ', $r['meereizigers'] ), $r['telefoon'], $r['email'], number_format( $r['bedrag'], 2, ',', '' ), $statussen[ $r['status'] ] ?? $r['status'], $r['code'] ), ';' );
	}
	fclose( $uit ); // phpcs:ignore
	exit;
} );

/* Kleuren van de status in de lijst. */
add_action( 'admin_head', function () {
	$scherm = get_current_screen();
	if ( ! $scherm || THEMA_STOEL_TYPE !== $scherm->post_type ) {
		return;
	}
	echo '<style>
/**Beheer -- Stoelverkoop**/
/*Stoelverkoop -- Statuslabel*/
body.wp-admin table.wp-list-table td.column-stoel_status span.stoel-status {
  display:       inline-block;
  padding:       2px 10px;
  border-radius: 999px;
  font-weight:   600;
  font-size:     12px;
  background:    #f0f0f1;
}
body.wp-admin table.wp-list-table td.column-stoel_status span.stoel-status-nieuw {
  background: #ffaf00;
  color:      #38065e;
}
body.wp-admin table.wp-list-table td.column-stoel_status span.stoel-status-tikkie {
  background: #e8dcf5;
  color:      #38065e;
}
body.wp-admin table.wp-list-table td.column-stoel_status span.stoel-status-betaald {
  background: #d7f2dc;
  color:      #0a5c1f;
}
body.wp-admin table.wp-list-table td.column-stoel_status span.stoel-status-geannuleerd {
  color:           #777777;
  text-decoration: line-through;
}
</style>';
} );
