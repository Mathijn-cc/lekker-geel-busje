<?php
/**
 * Menu's: items worden in eigen markup gezet, zodat ze passen bij het ontwerp.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Bovenste niveau van een menulocatie als lijst [titel, url, actief].
 */
function thema_menu_items( $locatie ) {
	$locaties = get_nav_menu_locations();
	if ( empty( $locaties[ $locatie ] ) ) {
		return array();
	}
	$items = wp_get_nav_menu_items( $locaties[ $locatie ] );
	if ( ! $items ) {
		return array();
	}
	_wp_menu_item_classes_by_context( $items );

	$uit = array();
	foreach ( $items as $item ) {
		if ( (int) $item->menu_item_parent !== 0 ) {
			continue;
		}
		$uit[] = array(
			'titel'  => $item->title,
			'url'    => $item->url,
			'actief' => in_array( 'current-menu-item', (array) $item->classes, true ) && 'custom' !== $item->type,
			'doel'   => $item->target,
		);
	}
	return $uit;
}

function thema_menu_naam( $locatie ) {
	$locaties = get_nav_menu_locations();
	if ( empty( $locaties[ $locatie ] ) ) {
		return '';
	}
	$menu = wp_get_nav_menu_object( $locaties[ $locatie ] );
	return $menu ? $menu->name : '';
}

/**
 * Terugval voor het hoofdmenu als er nog niets is ingesteld.
 */
function thema_hoofdmenu_items() {
	$items = thema_menu_items( 'hoofdmenu' );
	if ( $items ) {
		return $items;
	}
	return array(
		array( 'titel' => 'Gelegenheden', 'url' => home_url( '/#gelegenheden' ), 'actief' => false, 'doel' => '' ),
		array( 'titel' => 'Bussen', 'url' => home_url( '/#bussen' ), 'actief' => false, 'doel' => '' ),
		array( 'titel' => 'Over ons', 'url' => home_url( '/#overons' ), 'actief' => false, 'doel' => '' ),
		array( 'titel' => 'Veelgestelde vragen', 'url' => home_url( '/#faq' ), 'actief' => false, 'doel' => '' ),
		array( 'titel' => 'Contact', 'url' => home_url( '/#contact' ), 'actief' => false, 'doel' => '' ),
	);
}
