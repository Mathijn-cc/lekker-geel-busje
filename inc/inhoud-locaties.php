<?php
/**
 * Plaatspagina's onder /locaties/ (Maastricht, Rotterdam, Amsterdam, Den Bosch), in de opbouw van Tilburg (pagina 192).
 */

defined( 'ABSPATH' ) || exit;

/**
 * Uitroltaak tilburg-uitlezen-v1: de blokken van Tilburg als voorbeeld in het uitrollog (leest alleen).
 */
function thema_tilburg_uitlezen() {
	$p = get_page_by_path( 'locaties/tilburg' );
	if ( ! $p || ! function_exists( 'get_field' ) ) {
		return 'geen tilburg';
	}
	$kort = function ( $w ) use ( &$kort ) {
		if ( is_array( $w ) ) {
			if ( isset( $w['ID'], $w['url'], $w['mime_type'] ) ) {
				return 'beeld:' . $w['ID'] . ':' . basename( $w['url'] );
			}
			return array_map( $kort, $w );
		}
		return $w;
	};
	return array(
		'id'      => $p->ID,
		'ouder'   => $p->post_parent,
		'sjabloon'=> get_page_template_slug( $p->ID ),
		'titel'   => $p->post_title,
		'seo'     => array( get_post_meta( $p->ID, 'rank_math_title', true ), get_post_meta( $p->ID, 'rank_math_description', true ) ),
		'blokken' => $kort( get_field( 'blokken', $p->ID ) ),
	);
}
