<?php
/**
 * Lekker Geel, thema-functies.
 */

defined( 'ABSPATH' ) || exit;

define( 'THEMA_VERSIE', '2.14.0' );
define( 'THEMA_PAD', get_template_directory() );
define( 'THEMA_URL', get_template_directory_uri() );

require THEMA_PAD . '/inc/instellingen.php';
require THEMA_PAD . '/inc/helpers.php';
require THEMA_PAD . '/inc/gelegenheden.php';
require THEMA_PAD . '/inc/kruimels.php';
require THEMA_PAD . '/inc/menu.php';
require THEMA_PAD . '/inc/acf-velden.php';
require THEMA_PAD . '/inc/acf-admin.php';
require THEMA_PAD . '/inc/dashboard.php';
require THEMA_PAD . '/inc/voorbeeldinhoud.php';
require THEMA_PAD . '/inc/na-uitrol.php';
require THEMA_PAD . '/inc/offerteformulier.php';

/**
 * Basis thema-ondersteuning.
 */
add_action( 'after_setup_theme', function () {
	load_theme_textdomain( 'lekker-geel', THEMA_PAD . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
	add_theme_support( 'responsive-embeds' );

	add_image_size( 'kaart', 1000, 1250, true );
	/* Kleinere kaartformaten in dezelfde verhouding, zodat de browser via srcset een passend formaat kiest. */
	add_image_size( 'kaart-s', 400, 500, true );
	add_image_size( 'kaart-m', 700, 875, true );
	add_image_size( 'breed', 2560, 0, false );

	register_nav_menus( array(
		'hoofdmenu' => 'Hoofdmenu',
		'footer_1'  => 'Footer kolom 1',
		'footer_2'  => 'Footer kolom 2',
		'footer_3'  => 'Footer kolom 3',
		'juridisch' => 'Footer juridisch',
	) );
} );

/**
 * Pagina's en gelegenheden werken met blokken, niet met de blokeditor.
 */
add_filter( 'use_block_editor_for_post_type', function ( $gebruik, $type ) {
	return in_array( $type, array( 'page', 'gelegenheid' ), true ) ? false : $gebruik;
}, 10, 2 );

add_action( 'init', function () {
	remove_post_type_support( 'page', 'editor' );
}, 20 );

/**
 * Front-end stijlen en scripts. Alles lokaal: lettertypes, GSAP en Lenis.
 */
add_action( 'wp_enqueue_scripts', function () {
	$s = thema_instellingen();

	wp_enqueue_style( 'thema', THEMA_URL . '/assets/css/thema.css', array(), THEMA_VERSIE );
	wp_enqueue_style( 'thema-animaties', THEMA_URL . '/assets/css/animaties.css', array( 'thema' ), THEMA_VERSIE );
	wp_add_inline_style( 'thema-animaties', thema_css_variabelen() );

	/* Alle scripts in de footer en uitgesteld (defer): de volgorde blijft gelijk, de HTML wordt niet geblokkeerd. */
	$laden       = array( 'in_footer' => true, 'strategy' => 'defer' );
	$afhankelijk = array( 'thema-animaties', 'thema-accordeon' );
	if ( $s['animaties'] ) {
		wp_enqueue_script( 'gsap', THEMA_URL . '/assets/vendor/gsap.min.js', array(), '3.15.0', $laden );
		wp_enqueue_script( 'gsap-scrolltrigger', THEMA_URL . '/assets/vendor/ScrollTrigger.min.js', array( 'gsap' ), '3.15.0', $laden );
		$afhankelijk[] = 'gsap-scrolltrigger';
	}
	if ( $s['vloeiend_scrollen'] && $s['animaties'] ) {
		wp_enqueue_script( 'lenis', THEMA_URL . '/assets/vendor/lenis.min.js', array(), '1', $laden );
		$afhankelijk[] = 'lenis';
	}

	wp_enqueue_script( 'thema-animaties', THEMA_URL . '/assets/js/animaties.js', array(), THEMA_VERSIE, $laden );
	wp_enqueue_script( 'thema-accordeon', THEMA_URL . '/assets/js/accordeon.js', array(), THEMA_VERSIE, $laden );
	wp_enqueue_script( 'thema', THEMA_URL . '/assets/js/thema.js', $afhankelijk, THEMA_VERSIE, $laden );
	wp_add_inline_script( 'thema', 'window.themaInstellingen = ' . wp_json_encode( array(
		'animaties'      => (bool) $s['animaties'],
		'scrollEffecten' => (bool) $s['scroll_effecten'],
		'cursor'         => $s['animaties'] ? $s['cursor'] : 'uit',
		'plaatsInterval' => (int) $s['plaats_interval'],
		'heroInterval'   => (int) $s['hero_interval'],
		'bandSnelheid'   => (int) $s['band_snelheid'],
		'reviewSnelheid' => (int) $s['reviews_snelheid'],
		'faqEenOpen'     => (bool) $s['faq_een_open'],
		'vloeiend'       => (bool) $s['vloeiend_scrollen'],
	) ) . ';', 'before' );

	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'wp-block-library-theme' );
	wp_dequeue_style( 'global-styles' );
	wp_dequeue_style( 'classic-theme-styles' );
}, 20 );

/**
 * Preload van de gekozen lettertypes, en de js-class voor animaties zonder flits.
 */
add_action( 'wp_head', function () {
	$s = thema_instellingen();
	/* Bij animaties staat de hero-intro meteen in de beginstand, zodat er na het laden niets verdwijnt en opnieuw verschijnt. Vangnet: na 3 seconden altijd zichtbaar. */
	$intro = $s['animaties'] ? "if(!window.matchMedia('(prefers-reduced-motion: reduce)').matches){d.classList.add('intro-wacht');setTimeout(function(){d.classList.remove('intro-wacht');},3000);}" : '';
	echo "<script>(function(d){d.classList.add('js');" . $intro . "}(document.documentElement));</script>\n";
	foreach ( thema_actieve_fonts() as $sleutel => $font ) {
		/* Het accentlettertype wordt in zijn eigen stijl gebruikt (Instrument Serif cursief): dat bestand voorladen, niet het rechte. */
		$stijl   = $sleutel === $s['font_accent'] ? ( $font['stijl'] ?? 'normal' ) : 'normal';
		$bestand = $font['bestanden'][ $stijl ] ?? ( $font['bestanden']['normal'] ?? reset( $font['bestanden'] ) );
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
			esc_url( THEMA_URL . '/assets/fonts/' . $bestand )
		);
	}
}, 1 );

/**
 * Body-classes voor menu, animaties en vormtaal.
 */
add_filter( 'body_class', function ( $classes ) {
	return array_merge( $classes, thema_body_classes() );
} );

/**
 * Favicon/logo fallback en kleine opschoning.
 */
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );

/**
 * Head opschonen: global styles van de blokeditor (9 kB inline, niet gebruikt), oEmbed-, RSD-, shortlink- en REST-links.
 * wp_dequeue_style( 'global-styles' ) haalt de inline stijl niet weg, daarom de acties zelf.
 */
remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
remove_action( 'wp_footer', 'wp_enqueue_global_styles', 1 );
remove_action( 'wp_body_open', 'wp_global_styles_render_svg_filters' );
remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wp_shortlink_wp_head' );
remove_action( 'template_redirect', 'wp_shortlink_header', 11 );
remove_action( 'wp_head', 'rest_output_link_wp_head' );
remove_action( 'template_redirect', 'rest_output_link_header', 11 );

/**
 * Gebruikersnamen niet publiek: geen auteurssitemap en geen gebruikerslijst via de REST API voor bezoekers.
 */
add_filter( 'wp_sitemaps_add_provider', function ( $provider, $naam ) {
	return 'users' === $naam ? false : $provider;
}, 10, 2 );
add_filter( 'rest_endpoints', function ( $endpoints ) {
	if ( ! is_user_logged_in() ) {
		unset( $endpoints['/wp/v2/users'], $endpoints['/wp/v2/users/(?P<id>[\\d]+)'] );
	}
	return $endpoints;
} );
add_filter( 'excerpt_length', fn() => 24 );


/**
 * Beeldkwaliteit: 82 (JPEG) en 78 (WebP) is visueel gelijk aan 90, maar 30 tot 40% lichter. Grote foto's terug naar 2560 px.
 */
add_filter( 'wp_editor_set_quality', function ( $kwaliteit, $mime ) {
	return 'image/webp' === $mime ? 78 : 82;
}, 10, 2 );
add_filter( 'big_image_size_threshold', function () {
	return 2560;
} );
