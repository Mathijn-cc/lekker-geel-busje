<?php
/**
 * Fotoselectie (shoot Jules van Iperen, juni 2026): inventaris van het beeldgebruik,
 * het plaatsen van de nieuwe foto's in de mediabibliotheek (map Fotoselectie) en de toewijzing per blok.
 * Wordt aangeroepen vanuit de uitroltaken in inc/na-uitrol.php.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Alle beeldvelden (ACF image en gallery) per bericht, met layout en kop van het blok.
 */
function thema_fotos_beeldvelden() {
	$uit   = array();
	$posts = get_posts( array(
		'post_type'      => array( 'page', 'post', 'gelegenheid' ),
		'post_status'    => array( 'publish', 'draft', 'private', 'future' ),
		'posts_per_page' => -1,
	) );
	foreach ( $posts as $p ) {
		$meta    = get_post_meta( $p->ID );
		$layouts = isset( $meta['blokken'][0] ) ? (array) maybe_unserialize( $meta['blokken'][0] ) : array();
		$velden  = array();
		foreach ( $meta as $sleutel => $waarde ) {
			if ( 0 !== strpos( $sleutel, 'blokken_' ) || ! isset( $meta[ '_' . $sleutel ][0] ) || ! function_exists( 'acf_get_field' ) ) {
				continue;
			}
			$veld = acf_get_field( $meta[ '_' . $sleutel ][0] );
			if ( ! $veld || ! in_array( $veld['type'], array( 'image', 'gallery' ), true ) ) {
				continue;
			}
			preg_match( '/^blokken_(\d+)_/', $sleutel, $m );
			$i        = isset( $m[1] ) ? (int) $m[1] : -1;
			$velden[] = array(
				'sleutel' => $sleutel,
				'type'    => $veld['type'],
				'layout'  => $layouts[ $i ] ?? '',
				'kop'     => isset( $meta[ 'blokken_' . $i . '_kop' ][0] ) ? wp_strip_all_tags( $meta[ 'blokken_' . $i . '_kop' ][0] ) : '',
				'waarde'  => maybe_unserialize( $waarde[0] ),
			);
		}
		$uit[] = array(
			'id'      => $p->ID,
			'type'    => $p->post_type,
			'status'  => $p->post_status,
			'slug'    => $p->post_name,
			'titel'   => $p->post_title,
			'uitgel'  => (int) get_post_thumbnail_id( $p->ID ),
			'layouts' => $layouts,
			'velden'  => $velden,
		);
	}
	return $uit;
}

function thema_fotos_inventaris() {
	global $wpdb;
	$bijlagen = array();
	foreach ( get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'posts_per_page' => -1 ) ) as $a ) {
		$bijlagen[ $a->ID ] = basename( (string) get_attached_file( $a->ID ) );
	}
	$instellingen = get_option( THEMA_OPTIE, array() );
	$beeld_inst   = array();
	foreach ( (array) $instellingen as $k => $v ) {
		if ( is_numeric( $v ) && isset( $bijlagen[ (int) $v ] ) ) {
			$beeld_inst[ $k ] = (int) $v;
		}
	}
	$tabellen = $wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $wpdb->prefix ) . '%' ) );
	return array(
		'plugins'      => get_option( 'active_plugins' ),
		'taxonomieen'  => get_object_taxonomies( 'attachment' ),
		'tabellen'     => $tabellen,
		'bijlagen'     => $bijlagen,
		'instellingen' => $beeld_inst,
		'berichten'    => thema_fotos_beeldvelden(),
	);
}
