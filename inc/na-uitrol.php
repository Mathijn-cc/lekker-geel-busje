<?php
/**
 * Eenmalige taken na een uitrol. De workflow roept na elke uitrol de actie thema_na_uitrol aan
 * (wp eval). Elke taak draait één keer per omgeving; wat gedaan is, staat in de optie thema_uitroltaken.
 * Nieuwe taak: voeg een sleutel toe met een nieuwe naam (bijv. over-ons-v2), nooit een bestaande hergebruiken.
 */

defined( 'ABSPATH' ) || exit;

function thema_uitroltaken() {
	return array(
		'over-ons-v1' => 'thema_plaats_overonspagina',
		'stickers-naar-bento-v1' => 'thema_stickers_naar_bento',
		'beelden-opnieuw-v1'     => 'thema_beelden_opnieuw',
		'vloeiend-scrollen-uit-v1' => 'thema_vloeiend_scrollen_uit',
		'webp-kopieen-v1'        => 'thema_webp_kopieen',
	);
}

add_action( 'thema_na_uitrol', function () {
	$gedaan = (array) get_option( 'thema_uitroltaken', array() );
	foreach ( thema_uitroltaken() as $sleutel => $functie ) {
		if ( isset( $gedaan[ $sleutel ] ) || ! function_exists( $functie ) ) {
			continue;
		}
		$uitkomst          = call_user_func( $functie );
		$gedaan[ $sleutel ] = array( 'tijd' => gmdate( 'c' ), 'uitkomst' => $uitkomst );
		update_option( 'thema_uitroltaken', $gedaan, false );
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::log( 'Uitroltaak ' . $sleutel . ': ' . wp_json_encode( $uitkomst ) );
		}
	}
} );

/**
 * De weergave Stickers is vervangen door Bento: opgeslagen blokken omzetten, zodat opnieuw opslaan ze niet terugzet naar Tabs.
 */
function thema_stickers_naar_bento() {
	global $wpdb;
	return (int) $wpdb->query(
		$wpdb->prepare(
			"UPDATE {$wpdb->postmeta} SET meta_value = %s WHERE meta_key LIKE %s AND meta_value = %s",
			'bento',
			$wpdb->esc_like( 'blokken_' ) . '%' . $wpdb->esc_like( '_weergave' ),
			'stickers'
		)
	);
}

/**
 * Alle formaten van de afbeeldingen opnieuw maken: de nieuwe kaartformaten (kaart-s, kaart-m)
 * en de lagere kwaliteit (82/78) gelden anders alleen voor nieuwe uploads.
 */
function thema_beelden_opnieuw() {
	require_once ABSPATH . 'wp-admin/includes/image.php';
	if ( function_exists( 'set_time_limit' ) ) {
		set_time_limit( 0 );
	}
	$ids    = get_posts( array(
		'post_type'      => 'attachment',
		'post_mime_type' => array( 'image/jpeg', 'image/png', 'image/webp' ),
		'post_status'    => 'inherit',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	) );
	$klaar  = 0;
	$fouten = array();
	foreach ( $ids as $id ) {
		$bestand = wp_get_original_image_path( $id );
		if ( ! $bestand || ! file_exists( $bestand ) ) {
			$fouten[] = $id;
			continue;
		}
		$meta = wp_generate_attachment_metadata( $id, $bestand );
		if ( is_wp_error( $meta ) || empty( $meta ) ) {
			$fouten[] = $id;
			continue;
		}
		wp_update_attachment_metadata( $id, $meta );
		$klaar++;
	}
	return array( 'opnieuw' => $klaar, 'fouten' => $fouten );
}

/**
 * Vloeiend scrollen (Lenis) standaard uit: het kost scroll-responsiviteit en levert hier weinig op.
 * Daarna is het per omgeving gewoon weer aan te zetten in de thema-instellingen.
 */
function thema_vloeiend_scrollen_uit() {
	$s = get_option( THEMA_OPTIE, array() );
	if ( ! is_array( $s ) ) {
		return 'geen instellingen';
	}
	$s['vloeiend_scrollen'] = 0;
	update_option( THEMA_OPTIE, $s );
	return 'uit';
}

/**
 * WebP-kopieen naast elk formaat zetten (bestand.jpg.webp), in de naamgeving van SiteGround Speed Optimizer.
 * SiteGround levert die kopie automatisch uit aan browsers met WebP-ondersteuning, maar maakt hem niet aan
 * voor formaten die later opnieuw gegenereerd zijn. Bestaande kopieen worden overschreven, zodat ook die
 * van de lichtere bronbestanden komen.
 */
function thema_webp_kopieen() {
	if ( ! wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) ) {
		return 'geen webp-ondersteuning';
	}
	if ( function_exists( 'set_time_limit' ) ) {
		set_time_limit( 0 );
	}
	$ids      = get_posts( array(
		'post_type'      => 'attachment',
		'post_mime_type' => array( 'image/jpeg', 'image/png' ),
		'post_status'    => 'inherit',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	) );
	$gemaakt  = 0;
	$fouten   = 0;
	foreach ( $ids as $id ) {
		$hoofd = get_attached_file( $id );
		$meta  = wp_get_attachment_metadata( $id );
		if ( ! $hoofd || empty( $meta ) ) {
			continue;
		}
		$map       = trailingslashit( dirname( $hoofd ) );
		$bestanden = array( $hoofd );
		foreach ( (array) ( $meta['sizes'] ?? array() ) as $maat ) {
			if ( ! empty( $maat['file'] ) ) {
				$bestanden[] = $map . $maat['file'];
			}
		}
		foreach ( array_unique( $bestanden ) as $bestand ) {
			if ( ! file_exists( $bestand ) ) {
				continue;
			}
			$editor = wp_get_image_editor( $bestand );
			if ( is_wp_error( $editor ) ) {
				$fouten++;
				continue;
			}
			$editor->set_quality( 78 );
			$uit = $editor->save( $bestand . '.webp', 'image/webp' );
			if ( is_wp_error( $uit ) ) {
				$fouten++;
				continue;
			}
			/* WordPress plakt zelf .webp achter de naam als die niet op .webp eindigt; zet hem terug op de SiteGround-naam. */
			if ( ! empty( $uit['path'] ) && $uit['path'] !== $bestand . '.webp' && file_exists( $uit['path'] ) ) {
				rename( $uit['path'], $bestand . '.webp' );
			}
			$gemaakt++;
		}
	}
	return array( 'webp' => $gemaakt, 'fouten' => $fouten );
}
