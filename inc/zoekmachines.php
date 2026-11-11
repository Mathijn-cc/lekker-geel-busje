<?php
/**
 * Zoekmachines: eigen title en meta description per pagina, Open Graph, en het Service-schema op een gelegenheid.
 * Staat er een SEO-plugin aan (Rank Math of Yoast), dan doet dit bestand niets en neemt de plugin het over.
 */

defined( 'ABSPATH' ) || exit;

function thema_seo_plugin_actief() {
	return defined( 'RANK_MATH_VERSION' ) || defined( 'WPSEO_VERSION' );
}

/**
 * Velden in een eigen vak onder de blokken, op pagina's en gelegenheden.
 */
add_action( 'acf/include_fields', function () {
	if ( ! function_exists( 'acf_add_local_field_group' ) || thema_seo_plugin_actief() ) {
		return;
	}
	acf_add_local_field_group( array(
		'key'        => 'group_zoekmachines',
		'title'      => 'Zoekmachines',
		'fields'     => array(
			thema_veld( 'seo', 'seo_titel', 'Titel in Google', 'text', array(
				'maxlength'    => 70,
				'instructions' => 'Leeg: paginanaam | Lekker Geel Busje. Houd het onder de 60 tekens.',
			) ),
			thema_veld( 'seo', 'seo_omschrijving', 'Omschrijving in Google', 'textarea', array(
				'rows'         => 3,
				'maxlength'    => 170,
				'instructions' => 'Eén of twee zinnen, 120 tot 155 tekens. Komt ook in de voorvertoning op WhatsApp en social media.',
			) ),
		),
		'location'   => array(
			array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'page' ) ),
			array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'gelegenheid' ) ),
			array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'post' ) ),
		),
		'menu_order' => 30,
		'position'   => 'normal',
		'style'      => 'default',
	) );
} );

/**
 * Een gewone streep als scheidingsteken in de title, geen en-dash.
 */
add_filter( 'document_title_separator', function ( $teken ) {
	return thema_seo_plugin_actief() ? $teken : '|';
} );

add_filter( 'pre_get_document_title', function ( $titel ) {
	if ( thema_seo_plugin_actief() || ! function_exists( 'get_field' ) ) {
		return $titel;
	}
	$id = thema_seo_id();
	if ( ! $id ) {
		if ( is_category() ) {
			return single_cat_title( '', false ) . ' | Blog | ' . get_bloginfo( 'name' );
		}
		return $titel;
	}
	$eigen = trim( (string) get_field( 'seo_titel', $id ) );
	if ( '' !== $eigen ) {
		$pagina = (int) get_query_var( 'paged' );
		return $pagina > 1 ? $eigen . ' | Pagina ' . $pagina : $eigen;
	}
	return $titel;
} );

/**
 * Het bericht waar de SEO-velden van komen: de pagina of het artikel zelf, en op het blogoverzicht de berichtenpagina.
 */
function thema_seo_id() {
	if ( is_home() && ! is_front_page() ) {
		return (int) get_option( 'page_for_posts' );
	}
	return is_singular() ? get_queried_object_id() : 0;
}

/**
 * De omschrijving van de huidige pagina. Zonder eigen tekst: de ondertitel van een gelegenheid, anders de slogan.
 */
function thema_seo_omschrijving() {
	$tekst = '';
	$id    = thema_seo_id();
	if ( $id && function_exists( 'get_field' ) ) {
		$tekst = (string) get_field( 'seo_omschrijving', $id );
		if ( '' === trim( $tekst ) && is_singular( 'gelegenheid' ) ) {
			$tekst = (string) get_field( 'ondertitel', $id );
		}
		if ( '' === trim( $tekst ) && is_singular( 'post' ) ) {
			$tekst = has_excerpt( $id ) ? get_the_excerpt( $id ) : wp_trim_words( wp_strip_all_tags( get_post_field( 'post_content', $id ) ), 26, '…' );
		}
	}
	if ( '' === trim( $tekst ) && is_category() ) {
		$tekst = category_description();
	}
	if ( '' === trim( $tekst ) ) {
		$tekst = get_bloginfo( 'description' );
	}
	return trim( wp_strip_all_tags( $tekst ) );
}

add_action( 'wp_head', function () {
	if ( thema_seo_plugin_actief() ) {
		return;
	}
	$omschrijving = thema_seo_omschrijving();
	if ( $omschrijving ) {
		printf( '<meta name="description" content="%s">' . "\n", esc_attr( $omschrijving ) );
	}
	if ( ! is_singular() && ! is_front_page() && ! is_home() ) {
		return;
	}
	$id   = thema_seo_id() ?: get_queried_object_id();
	$url  = is_front_page() ? home_url( '/' ) : get_permalink( $id );
	$foto = has_post_thumbnail( $id ) ? get_the_post_thumbnail_url( $id, 'breed' ) : '';
	$og   = array(
		'og:locale'      => 'nl_NL',
		'og:type'        => is_singular( 'post' ) ? 'article' : 'website',
		'og:site_name'   => get_bloginfo( 'name' ),
		'og:title'       => wp_get_document_title(),
		'og:description' => $omschrijving,
		'og:url'         => $url,
		'og:image'       => $foto,
	);
	foreach ( array_filter( $og ) as $eigenschap => $waarde ) {
		printf( '<meta property="%s" content="%s">' . "\n", esc_attr( $eigenschap ), esc_attr( $waarde ) );
	}
	if ( $foto ) {
		echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
	}
}, 2 );

/**
 * Service-schema op een gelegenheid: wat je boekt, bij wie en waar.
 */
add_action( 'wp_head', function () {
	if ( thema_seo_plugin_actief() || ! is_singular( 'gelegenheid' ) ) {
		return;
	}
	$id     = get_queried_object_id();
	$naam   = get_the_title( $id );
	$s      = thema_instellingen();
	$schema = array(
		'@context'    => 'https://schema.org',
		'@type'       => 'Service',
		'name'        => 'Partybus voor je ' . mb_strtolower( $naam ),
		'serviceType' => 'Partybus huren',
		'description' => thema_seo_omschrijving(),
		'url'         => get_permalink( $id ),
		'provider'    => array_filter( array(
			'@type'     => 'LocalBusiness',
			'@id'       => home_url( '/#bedrijf' ),
			'name'      => get_bloginfo( 'name' ),
			'url'       => home_url( '/' ),
			'telephone' => $s['telefoon'] ?? '',
			'email'     => $s['email'] ?? '',
		) ),
		'areaServed'  => array( '@type' => 'Country', 'name' => 'Nederland' ),
	);
	if ( has_post_thumbnail( $id ) ) {
		$schema['image'] = get_the_post_thumbnail_url( $id, 'breed' );
	}
	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "</script>\n";
}, 21 );
