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
