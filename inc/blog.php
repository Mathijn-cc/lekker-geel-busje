<?php
/**
 * Blog: /blog/ als overzicht, /blog/{slug}/ als artikel, /blog/onderwerp/{categorie}/ per onderwerp.
 * Leestijd, inhoudsopgave, publicatie- en bijwerkdatum, BlogPosting-schema en robots-regels voor dunne overzichten.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', function () {
	add_theme_support( 'automatic-feed-links' );
	add_post_type_support( 'post', 'excerpt' );
} );

/**
 * Velden van een artikel: korte samenvatting bovenaan, auteur en de gelegenheid waar het artikel bij hoort.
 */
add_action( 'acf/include_fields', function () {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}
	acf_add_local_field_group( array(
		'key'        => 'group_blog',
		'title'      => 'Artikel',
		'fields'     => array(
			thema_veld( 'blog', 'in_het_kort', 'In het kort', 'textarea', array(
				'rows'         => 3,
				'instructions' => 'Twee of drie korte punten, één per regel. Staat bovenaan het artikel; Google en AI-assistenten lezen dit als samenvatting.',
			) ),
			thema_veld( 'blog', 'auteur', 'Geschreven door', 'text', array_merge( thema_breed( 50 ), array( 'default_value' => 'Team Lekker Geel Busje', 'instructions' => 'Leeg: Team Lekker Geel Busje.' ) ) ),
			thema_veld( 'blog', 'auteur_rol', 'Rol of functie', 'text', array_merge( thema_breed( 50 ), array( 'default_value' => 'Rijdt groepen door heel Nederland en daarbuiten' ) ) ),
			thema_veld( 'blog', 'gelegenheid', 'Hoort bij gelegenheid', 'post_object', array(
				'post_type'     => array( 'gelegenheid' ),
				'return_format' => 'id',
				'allow_null'    => 1,
				'instructions'  => 'Onder het artikel komt een kaart naar deze gelegenheid, en de offerte krijgt de gelegenheid alvast mee.',
			) ),
		),
		'location'   => array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'post' ) ) ),
		'menu_order' => 0,
		'position'   => 'acf_after_title',
		'style'      => 'default',
	) );

	acf_add_local_field_group( array(
		'key'        => 'group_blogpagina',
		'title'      => 'Blogoverzicht',
		'fields'     => array(
			thema_veld( 'blogpagina', 'blog_kop', 'Kop (H1)', 'text', array(
				'instructions'  => THEMA_ACCENT_UITLEG . ' De inhoud van deze pagina staat als intro onder de kop; de blokken komen onder de artikelen.',
				'default_value' => 'Tips en ideeën voor *jullie feestje*',
			) ),
		),
		'location'   => array( array( array( 'param' => 'page_type', 'operator' => '==', 'value' => 'posts_page' ) ) ),
		'menu_order' => 0,
		'position'   => 'acf_after_title',
		'style'      => 'default',
	) );
} );

/**
 * Leestijd in minuten, op 200 woorden per minuut (Nederlandse leessnelheid op een scherm).
 */
function thema_leestijd( $post = null ) {
	$post   = get_post( $post );
	$tekst  = wp_strip_all_tags( strip_shortcodes( (string) $post->post_content ) );
	$woorden = count( preg_split( '/\s+/u', trim( $tekst ), -1, PREG_SPLIT_NO_EMPTY ) );
	if ( function_exists( 'get_field' ) ) {
		$blokken = (array) get_field( 'blokken', $post->ID, false );
		array_walk_recursive( $blokken, function ( $waarde ) use ( &$woorden ) {
			if ( is_string( $waarde ) && strlen( $waarde ) > 20 && false !== strpos( $waarde, ' ' ) ) {
				$woorden += count( preg_split( '/\s+/u', trim( wp_strip_all_tags( $waarde ) ), -1, PREG_SPLIT_NO_EMPTY ) );
			}
		} );
	}
	return max( 1, (int) ceil( $woorden / 200 ) );
}

function thema_woorden( $post = null ) {
	$post = get_post( $post );
	return count( preg_split( '/\s+/u', trim( wp_strip_all_tags( (string) $post->post_content ) ), -1, PREG_SPLIT_NO_EMPTY ) );
}

/**
 * Publicatiedatum en, als het artikel later is aangepast, de bijwerkdatum. Als <time> met een machineleesbare datum.
 */
function thema_blog_datums( $post = null, $met_bijgewerkt = true ) {
	$post  = get_post( $post );
	$uit   = sprintf( '<time datetime="%s">%s</time>', esc_attr( get_the_date( 'c', $post ) ), esc_html( get_the_date( 'j F Y', $post ) ) );
	$later = (int) get_post_modified_time( 'U', true, $post ) - (int) get_post_time( 'U', true, $post ) > DAY_IN_SECONDS;
	if ( $met_bijgewerkt && $later ) {
		$uit .= sprintf( ' <span class="blog-bijgewerkt">· bijgewerkt <time datetime="%s">%s</time></span>', esc_attr( get_the_modified_date( 'c', $post ) ), esc_html( get_the_modified_date( 'j F Y', $post ) ) );
	}
	return $uit;
}

/**
 * De eerste categorie van een artikel, voor het label en het kruimelpad.
 */
function thema_blog_categorie( $post = null ) {
	$cats = get_the_category( get_post( $post )->ID );
	return $cats ? $cats[0] : null;
}

function thema_blog_auteur( $post = null ) {
	$post = get_post( $post );
	$naam = function_exists( 'get_field' ) ? trim( (string) get_field( 'auteur', $post->ID ) ) : '';
	$rol  = function_exists( 'get_field' ) ? trim( (string) get_field( 'auteur_rol', $post->ID ) ) : '';
	return array( 'naam' => $naam ?: 'Team Lekker Geel Busje', 'rol' => $rol );
}

/**
 * Inhoud van een artikel met een id op elke H2, en de inhoudsopgave die daarbij hoort.
 */
function thema_blog_inhoud( $post = null ) {
	$post  = get_post( $post );
	$html  = apply_filters( 'the_content', $post->post_content ); // phpcs:ignore
	$lijst = array();
	$html  = preg_replace_callback( '/<h2([^>]*)>(.*?)<\/h2>/is', function ( $m ) use ( &$lijst ) {
		$tekst = trim( wp_strip_all_tags( $m[2] ) );
		if ( preg_match( '/\sid="([^"]+)"/', $m[1], $id ) ) {
			$anker = $id[1];
			$attr  = $m[1];
		} else {
			$anker = sanitize_title( $tekst );
			$basis = $anker;
			$n     = 2;
			while ( isset( $lijst[ $anker ] ) ) {
				$anker = $basis . '-' . $n++;
			}
			$attr = $m[1] . ' id="' . esc_attr( $anker ) . '"';
		}
		$lijst[ $anker ] = $tekst;
		return '<h2' . $attr . '>' . $m[2] . '</h2>';
	}, $html );
	return array( 'html' => $html, 'inhoud' => $lijst );
}

/**
 * Artikelen ophalen voor een kaartenblok of de gerelateerde artikelen.
 */
function thema_blog_artikelen( $ids = array(), $categorie = 0, $aantal = 3, $uitsluiten = 0 ) {
	$args = array(
		'post_type'           => 'post',
		'posts_per_page'      => $aantal,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	);
	$ids = array_values( array_filter( array_map( 'intval', (array) $ids ) ) );
	if ( $ids ) {
		$args['post__in']       = $ids;
		$args['orderby']        = 'post__in';
		$args['posts_per_page'] = count( $ids );
	} elseif ( $categorie ) {
		$args['cat'] = (int) $categorie;
	}
	if ( $uitsluiten ) {
		$args['post__not_in'] = array( (int) $uitsluiten );
	}
	return get_posts( $args );
}

/**
 * De overzichtspagina van de blog (Instellingen > Lezen > Berichtenpagina).
 */
function thema_blog_pagina() {
	$id = (int) get_option( 'page_for_posts' );
	return $id ? get_post( $id ) : null;
}

/**
 * Robots: interne zoekresultaten, auteurs- en datumoverzichten en tags uit de zoekmachines. Die zijn dun en dubbel.
 */
add_filter( 'wp_robots', function ( $robots ) {
	if ( is_search() || is_author() || is_date() || is_tag() ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
	}
	return $robots;
} );

/**
 * Een auteursoverzicht heeft geen eigen inhoud: door naar de blog.
 */
add_action( 'template_redirect', function () {
	if ( is_author() || is_date() ) {
		$blog = thema_blog_pagina();
		wp_safe_redirect( $blog ? get_permalink( $blog ) : home_url( '/' ), 301 );
		exit;
	}
} );

/**
 * Canonical op het blogoverzicht en de onderwerpen, ook op pagina 2 en verder (WordPress doet dit alleen bij losse pagina's).
 */
add_action( 'wp_head', function () {
	if ( ( is_home() && ! is_front_page() ) || is_category() ) {
		printf( '<link rel="canonical" href="%s">' . "\n", esc_url( get_pagenum_link( max( 1, (int) get_query_var( 'paged' ) ) ) ) );
	}
}, 3 );

/**
 * BlogPosting-schema op een artikel.
 */
add_action( 'wp_head', function () {
	if ( ! is_singular( 'post' ) || ( function_exists( 'thema_seo_plugin_actief' ) && thema_seo_plugin_actief() ) ) {
		return;
	}
	$id     = get_queried_object_id();
	$auteur = thema_blog_auteur( $id );
	$cat    = thema_blog_categorie( $id );
	$team   = 'Team Lekker Geel Busje' === $auteur['naam'];
	$schema = array(
		'@context'         => 'https://schema.org',
		'@type'            => 'BlogPosting',
		'headline'         => wp_strip_all_tags( get_the_title( $id ) ),
		'description'      => function_exists( 'thema_seo_omschrijving' ) ? thema_seo_omschrijving() : get_the_excerpt( $id ),
		'datePublished'    => get_the_date( 'c', $id ),
		'dateModified'     => get_the_modified_date( 'c', $id ),
		'inLanguage'       => 'nl-NL',
		'wordCount'        => thema_woorden( $id ),
		'timeRequired'     => 'PT' . thema_leestijd( $id ) . 'M',
		'mainEntityOfPage' => array( '@type' => 'WebPage', '@id' => get_permalink( $id ) ),
		'author'           => $team
			? array( '@type' => 'Organization', 'name' => get_bloginfo( 'name' ), 'url' => home_url( '/' ) )
			: array_filter( array( '@type' => 'Person', 'name' => $auteur['naam'], 'jobTitle' => $auteur['rol'] ) ),
		'publisher'        => array(
			'@type' => 'Organization',
			'@id'   => home_url( '/#bedrijf' ),
			'name'  => get_bloginfo( 'name' ),
			'url'   => home_url( '/' ),
			'logo'  => array( '@type' => 'ImageObject', 'url' => thema_logo_url() ),
		),
	);
	if ( $cat ) {
		$schema['articleSection'] = $cat->name;
	}
	if ( has_post_thumbnail( $id ) ) {
		$beeld           = wp_get_attachment_image_src( get_post_thumbnail_id( $id ), 'breed' );
		$schema['image'] = array( '@type' => 'ImageObject', 'url' => $beeld[0], 'width' => $beeld[1], 'height' => $beeld[2] );
	}
	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "</script>\n";
}, 21 );

/**
 * Open Graph voor artikelen: type article met de datums.
 */
add_action( 'wp_head', function () {
	if ( ! is_singular( 'post' ) || ( function_exists( 'thema_seo_plugin_actief' ) && thema_seo_plugin_actief() ) ) {
		return;
	}
	$id = get_queried_object_id();
	printf( '<meta property="article:published_time" content="%s">' . "\n", esc_attr( get_the_date( 'c', $id ) ) );
	printf( '<meta property="article:modified_time" content="%s">' . "\n", esc_attr( get_the_modified_date( 'c', $id ) ) );
	$cat = thema_blog_categorie( $id );
	if ( $cat ) {
		printf( '<meta property="article:section" content="%s">' . "\n", esc_attr( $cat->name ) );
	}
}, 4 );
