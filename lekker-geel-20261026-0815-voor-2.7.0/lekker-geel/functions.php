<?php
/**
 * Lekker Geel, thema-functies.
 */

defined( 'ABSPATH' ) || exit;

define( 'THEMA_VERSIE', '2.6.0' );
define( 'THEMA_PAD', get_template_directory() );
define( 'THEMA_URL', get_template_directory_uri() );

require THEMA_PAD . '/inc/instellingen.php';
require THEMA_PAD . '/inc/helpers.php';
require THEMA_PAD . '/inc/gelegenheden.php';
require THEMA_PAD . '/inc/menu.php';
require THEMA_PAD . '/inc/acf-velden.php';
require THEMA_PAD . '/inc/acf-admin.php';
require THEMA_PAD . '/inc/dashboard.php';
require THEMA_PAD . '/inc/voorbeeldinhoud.php';

/**
 * Basis thema-ondersteuning.
 */
add_action( 'after_setup_theme', function () {
	load_theme_textdomain( 'lekker-geel', THEMA_PAD . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
	add_theme_support( 'responsive-embeds' );

	add_image_size( 'kaart', 800, 1000, true );
	add_image_size( 'breed', 1920, 1080, false );

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

	$afhankelijk = array( 'thema-animaties', 'thema-accordeon' );
	if ( $s['animaties'] ) {
		wp_enqueue_script( 'gsap', THEMA_URL . '/assets/vendor/gsap.min.js', array(), '3.15.0', true );
		wp_enqueue_script( 'gsap-scrolltrigger', THEMA_URL . '/assets/vendor/ScrollTrigger.min.js', array( 'gsap' ), '3.15.0', true );
		$afhankelijk[] = 'gsap-scrolltrigger';
	}
	if ( $s['vloeiend_scrollen'] && $s['animaties'] ) {
		wp_enqueue_script( 'lenis', THEMA_URL . '/assets/vendor/lenis.min.js', array(), '1', true );
		$afhankelijk[] = 'lenis';
	}

	wp_enqueue_script( 'thema-animaties', THEMA_URL . '/assets/js/animaties.js', array(), THEMA_VERSIE, true );
	wp_enqueue_script( 'thema-accordeon', THEMA_URL . '/assets/js/accordeon.js', array(), THEMA_VERSIE, true );
	wp_enqueue_script( 'thema', THEMA_URL . '/assets/js/thema.js', $afhankelijk, THEMA_VERSIE, true );
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
	echo "<script>document.documentElement.classList.add('js');</script>\n";
	foreach ( thema_actieve_fonts() as $font ) {
		$bestand = $font['bestanden']['normal'] ?? reset( $font['bestanden'] );
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
add_filter( 'excerpt_length', fn() => 24 );
