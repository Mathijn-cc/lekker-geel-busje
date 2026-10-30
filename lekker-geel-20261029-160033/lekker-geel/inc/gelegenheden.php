<?php
/**
 * Berichttype gelegenheid. Hub: de pagina /gelegenheden, detail: /gelegenheden/{slug}.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', function () {
	register_post_type( 'gelegenheid', array(
		'labels'        => array(
			'name'               => 'Gelegenheden',
			'singular_name'      => 'Gelegenheid',
			'add_new'            => 'Nieuwe gelegenheid',
			'add_new_item'       => 'Nieuwe gelegenheid toevoegen',
			'edit_item'          => 'Gelegenheid bewerken',
			'new_item'           => 'Nieuwe gelegenheid',
			'view_item'          => 'Gelegenheid bekijken',
			'search_items'       => 'Gelegenheden zoeken',
			'not_found'          => 'Geen gelegenheden gevonden',
			'all_items'          => 'Alle gelegenheden',
			'menu_name'          => 'Gelegenheden',
		),
		'public'        => true,
		'show_in_rest'  => true,
		'has_archive'   => false,
		'hierarchical'  => false,
		'menu_position' => 21,
		'menu_icon'     => 'dashicons-heart',
		'supports'      => array( 'title', 'thumbnail', 'page-attributes', 'revisions' ),
		'rewrite'       => array( 'slug' => 'gelegenheden', 'with_front' => false ),
	) );
} );

add_action( 'after_switch_theme', function () {
	flush_rewrite_rules();
} );

/**
 * Admin-kolommen: kaartbeeld en volgorde.
 */
add_filter( 'manage_gelegenheid_posts_columns', function ( $kolommen ) {
	$nieuw = array();
	foreach ( $kolommen as $sleutel => $label ) {
		if ( 'title' === $sleutel ) {
			$nieuw['kaartbeeld'] = '';
		}
		$nieuw[ $sleutel ] = $label;
	}
	$nieuw['volgorde'] = 'Volgorde';
	return $nieuw;
} );

add_action( 'manage_gelegenheid_posts_custom_column', function ( $kolom, $post_id ) {
	if ( 'kaartbeeld' === $kolom ) {
		echo get_the_post_thumbnail( $post_id, array( 56, 56 ), array( 'style' => 'border-radius:10px;object-fit:cover;width:56px;height:56px' ) );
	}
	if ( 'volgorde' === $kolom ) {
		echo (int) get_post_field( 'menu_order', $post_id );
	}
}, 10, 2 );

/**
 * Gelegenheden ophalen in de juiste volgorde.
 */
function thema_gelegenheden( $ids = array(), $uitsluiten = 0, $aantal = -1 ) {
	$args = array(
		'post_type'      => 'gelegenheid',
		'posts_per_page' => $aantal,
		'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
		'no_found_rows'  => true,
	);
	$ids = array_values( array_filter( array_map( 'intval', (array) $ids ) ) );
	if ( $ids ) {
		$args['post__in'] = $ids;
		$args['orderby']  = 'post__in';
	}
	if ( $uitsluiten ) {
		$args['post__not_in'] = array( (int) $uitsluiten );
	}
	return get_posts( $args );
}
