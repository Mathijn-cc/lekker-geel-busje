<?php
/**
 * Kruimelpad: als BreadcrumbList in de structured data, en een terug-link op subpagina's.
 * Zichtbare kruimels zijn bewust weggelaten: de site is ondiep en het menu staat altijd in beeld.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Het kruimelpad van de huidige pagina als lijst van naam en url. Leeg op de homepage.
 */
function thema_kruimelpad() {
	if ( is_front_page() ) {
		return array();
	}
	$kruims = array( array( 'naam' => 'Home', 'url' => home_url( '/' ) ) );
	$blog   = function_exists( 'thema_blog_pagina' ) ? thema_blog_pagina() : null;

	/* Blogoverzicht en onderwerpen. */
	if ( is_home() || is_category() ) {
		if ( $blog ) {
			$kruims[] = array( 'naam' => get_the_title( $blog ), 'url' => get_permalink( $blog ) );
		}
		if ( is_category() ) {
			$cat      = get_queried_object();
			$kruims[] = array( 'naam' => $cat->name, 'url' => get_category_link( $cat ) );
		}
		return $kruims;
	}
	if ( ! is_singular() ) {
		return array();
	}
	$post = get_queried_object();

	if ( is_singular( 'post' ) ) {
		if ( $blog ) {
			$kruims[] = array( 'naam' => get_the_title( $blog ), 'url' => get_permalink( $blog ) );
		}
		$cat = function_exists( 'thema_blog_categorie' ) ? thema_blog_categorie( $post ) : null;
		if ( $cat && 'algemeen' !== $cat->slug ) {
			$kruims[] = array( 'naam' => $cat->name, 'url' => get_category_link( $cat ) );
		}
	} elseif ( is_singular( 'gelegenheid' ) ) {
		$hub = get_page_by_path( 'gelegenheden' );
		if ( $hub ) {
			$kruims[] = array( 'naam' => get_the_title( $hub ), 'url' => get_permalink( $hub ) );
		}
	} elseif ( is_page() ) {
		foreach ( array_reverse( get_post_ancestors( $post ) ) as $ouder ) {
			$kruims[] = array( 'naam' => get_the_title( $ouder ), 'url' => get_permalink( $ouder ) );
		}
	}
	$kruims[] = array( 'naam' => get_the_title( $post ), 'url' => get_permalink( $post ) );
	return $kruims;
}

add_action( 'wp_head', function () {
	$kruims = thema_kruimelpad();
	if ( count( $kruims ) < 2 ) {
		return;
	}
	$items = array();
	foreach ( $kruims as $i => $kruim ) {
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => $i + 1,
			'name'     => wp_strip_all_tags( html_entity_decode( $kruim['naam'], ENT_QUOTES, 'UTF-8' ) ),
			'item'     => $kruim['url'],
		);
	}
	$schema = array(
		'@context'        => 'https://schema.org',
		'@type'           => 'BreadcrumbList',
		'itemListElement' => $items,
	);
	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "</script>\n";
}, 20 );

/**
 * Terug-link naar de bovenliggende pagina, voor in de hero van een subpagina (bijvoorbeeld een locatie).
 */
function thema_terug_link() {
	if ( is_singular( 'gelegenheid' ) ) {
		$hub = get_page_by_path( 'gelegenheden' );
		return $hub ? sprintf(
			'<a class="terugpil" href="%s" data-hero-fade><span aria-hidden="true">←</span> %s</a>',
			esc_url( get_permalink( $hub ) ),
			esc_html( get_the_title( $hub ) )
		) : '';
	}
	if ( ! is_page() || is_front_page() ) {
		return '';
	}
	$ouder = wp_get_post_parent_id( get_queried_object_id() );
	if ( ! $ouder ) {
		return '';
	}
	return sprintf(
		'<a class="terugpil" href="%s" data-hero-fade><span aria-hidden="true">←</span> %s</a>',
		esc_url( get_permalink( $ouder ) ),
		esc_html( get_the_title( $ouder ) )
	);
}
