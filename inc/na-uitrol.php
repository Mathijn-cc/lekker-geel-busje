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
		'beelden-opnieuw-v1'     => 'thema_beelden_opnieuw',
		'vloeiend-scrollen-uit-v1' => 'thema_vloeiend_scrollen_uit',
		'beelden-opnieuw-v2'     => 'thema_beelden_opnieuw',
		'webp-kopieen-v4'        => 'thema_webp_kopieen',
		'menu-prijzen-faq-weg-v1' => 'thema_menu_prijzen_faq_weg',
		'faq-copy-v3'            => 'thema_faq_copy_zetten',
		'vrijgezellenfeest-v1'    => 'thema_plaats_vrijgezellenfeest',
		'blog-v1'                 => 'thema_blog_opzetten',
		'vrijgezellenfeest-v2'    => 'thema_plaats_vrijgezellenfeest',
		'fotos-inventaris-v1'     => 'thema_fotos_inventaris',
		'fotoselectie-uploaden-v1' => 'thema_fotoselectie_uploaden',
		'fotoselectie-toewijzen-v1' => 'thema_fotoselectie_toewijzen',
		'citaat-over-ons-v1'      => 'thema_citaat_over_ons',
		'vrijgezellenfeest-fotos-v1' => 'thema_vrijgezellenfeest_fotos_terug',
		'blog-fotos-v1'           => 'thema_blog_fotos',
		'fotoselectie-opruimen-v1' => 'thema_fotoselectie_opruimen',
		'fotos-inventaris-v2'      => 'thema_fotos_inventaris',
		'fotoselectie-over-ons-v1' => 'thema_fotoselectie_over_ons_hero',
		'blog-tips-en-tricks-v1'  => 'thema_blog_tips_en_tricks',
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

/**
 * Alle formaten van de afbeeldingen opnieuw maken: de nieuwe kaartformaten (kaart-s, kaart-m)
 * en de lagere kwaliteit (82/78) gelden anders alleen voor nieuwe uploads.
 */
function thema_beelden_opnieuw() {
	require_once ABSPATH . 'wp-admin/includes/image.php';
	if ( function_exists( 'set_time_limit' ) ) {
		set_time_limit( 0 );
	}
	$ids    = get_posts( array(
		'post_type'      => 'attachment',
		'post_mime_type' => array( 'image/jpeg', 'image/png', 'image/webp' ),
		'post_status'    => 'inherit',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	) );
	$klaar  = 0;
	$fouten = array();
	foreach ( $ids as $id ) {
		$bestand = wp_get_original_image_path( $id );
		if ( ! $bestand || ! file_exists( $bestand ) ) {
			$fouten[] = $id;
			continue;
		}
		$meta = wp_generate_attachment_metadata( $id, $bestand );
		if ( is_wp_error( $meta ) || empty( $meta ) ) {
			$fouten[] = $id;
			continue;
		}
		wp_update_attachment_metadata( $id, $meta );
		$klaar++;
	}
	return array( 'opnieuw' => $klaar, 'fouten' => $fouten );
}

/**
 * Vloeiend scrollen (Lenis) standaard uit: het kost scroll-responsiviteit en levert hier weinig op.
 * Daarna is het per omgeving gewoon weer aan te zetten in de thema-instellingen.
 */
function thema_vloeiend_scrollen_uit() {
	$s = get_option( THEMA_OPTIE, array() );
	if ( ! is_array( $s ) ) {
		return 'geen instellingen';
	}
	$s['vloeiend_scrollen'] = 0;
	update_option( THEMA_OPTIE, $s );
	return 'uit';
}

/**
 * WebP-kopieen naast elk formaat zetten (bestand.jpg.webp), in de naamgeving van SiteGround Speed Optimizer.
 * SiteGround levert die kopie uit aan browsers met WebP-ondersteuning, maar maakt hem niet aan voor formaten
 * die later opnieuw gegenereerd zijn. Elke kopie wordt direct uit het origineel geschaald, niet uit de
 * verkleinde JPEG, zodat er maar een keer gecomprimeerd wordt en de foto scherp blijft.
 */
function thema_webp_kopieen( $alleen = array() ) {
	if ( ! wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) ) {
		return 'geen webp-ondersteuning';
	}
	if ( function_exists( 'set_time_limit' ) ) {
		set_time_limit( 0 );
	}
	/* WordPress zet de kwaliteit terug naar het filter zodra het uitvoerformaat wisselt; daarom via het filter. */
	$kwaliteit = function ( $q, $mime ) {
		return 'image/webp' === $mime ? 80 : $q;
	};
	add_filter( 'wp_editor_set_quality', $kwaliteit, 99, 2 );
	$ids     = get_posts( array(
		'post_type'      => 'attachment',
		'post_mime_type' => array( 'image/jpeg', 'image/png' ),
		'post_status'    => 'inherit',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'post__in'       => $alleen ? array_map( 'intval', (array) $alleen ) : array(),
	) );
	$gemaakt = 0;
	$fouten  = 0;
	foreach ( $ids as $id ) {
		$hoofd  = get_attached_file( $id );
		$bron   = wp_get_original_image_path( $id );
		$meta   = wp_get_attachment_metadata( $id );
		if ( ! $hoofd || ! $bron || ! file_exists( $bron ) || empty( $meta['width'] ) ) {
			continue;
		}
		$map    = trailingslashit( dirname( $hoofd ) );
		$doelen = array( $hoofd => array( (int) $meta['width'], (int) $meta['height'] ) );
		foreach ( (array) ( $meta['sizes'] ?? array() ) as $maat ) {
			if ( ! empty( $maat['file'] ) ) {
				$doelen[ $map . $maat['file'] ] = array( (int) $maat['width'], (int) $maat['height'] );
			}
		}
		foreach ( $doelen as $bestand => $wh ) {
			$editor = wp_get_image_editor( $bron );
			if ( is_wp_error( $editor ) ) {
				$fouten++;
				continue;
			}
			$grootte = $editor->get_size();
			if ( $wh[0] < $grootte['width'] ) {
				/* Zelfde breedte en hoogte als de JPEG van dit formaat, met dezelfde uitsnede uit het midden. */
				$editor->resize( $wh[0], $wh[1], true );
			}
			$uit = $editor->save( $bestand . '.webp', 'image/webp' );
			if ( is_wp_error( $uit ) ) {
				$fouten++;
				continue;
			}
			if ( ! empty( $uit['path'] ) && $uit['path'] !== $bestand . '.webp' && file_exists( $uit['path'] ) ) {
				rename( $uit['path'], $bestand . '.webp' );
			}
			$gemaakt++;
		}
	}
	remove_filter( 'wp_editor_set_quality', $kwaliteit, 99 );
	return array( 'webp' => $gemaakt, 'fouten' => $fouten );
}

/**
 * Geen losse links meer naar Prijzen en Veelgestelde vragen in de menu's: de vragen staan op de pagina's
 * waar ze bij horen, en een aparte vragenpagina levert in Google niets extra's op.
 */
function thema_menu_prijzen_faq_weg() {
	$weg = 0;
	foreach ( wp_get_nav_menus() as $menu ) {
		foreach ( (array) wp_get_nav_menu_items( $menu->term_id ) as $item ) {
			if ( in_array( strtolower( trim( $item->title ) ), array( 'prijzen', 'veelgestelde vragen' ), true ) ) {
				wp_delete_post( $item->ID, true );
				$weg++;
			}
		}
	}
	return array( 'verwijderd' => $weg );
}

/**
 * Foto + quote van Robbie op Over ons: de foto in de mediabibliotheek (map Fotoselectie) en het blok
 * voor de route ("Zo gaat een rit bij ons"). Staat het blok er al, dan gebeurt er niets.
 */
function thema_citaat_over_ons() {
	if ( ! function_exists( 'get_field' ) ) {
		return 'geen acf';
	}
	$pagina = get_page_by_path( 'over-ons', OBJECT, 'page' );
	if ( ! $pagina ) {
		return 'geen pagina';
	}
	$rijen = (array) get_field( 'field_blokken', $pagina->ID, false );
	foreach ( $rijen as $rij ) {
		if ( 'citaat' === ( $rij['acf_fc_layout'] ?? '' ) ) {
			return 'staat er al';
		}
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$naam = 'robbie-en-joyce-bij-de-gele-sfeerbus';
	$alt  = 'Robbie en Joyce van den Biggelaar bij de gele sfeerbus van Lekker Geel Busje';
	$bron = THEMA_PAD . '/assets/fotoselectie/' . $naam . '.jpg';
	$foto = 0;
	if ( file_exists( $bron ) ) {
		$tijdelijk = wp_tempnam( $naam . '.jpg' );
		copy( $bron, $tijdelijk );
		$foto = media_handle_sideload( array( 'name' => $naam . '.jpg', 'tmp_name' => $tijdelijk ), 0, $alt );
		if ( is_wp_error( $foto ) ) {
			@unlink( $tijdelijk ); // phpcs:ignore
			$foto = 0;
		} else {
			update_post_meta( $foto, '_wp_attachment_image_alt', $alt );
			wp_update_post( array( 'ID' => $foto, 'post_content' => 'Foto: Jules van Iperen' ) );
			$map = function_exists( 'thema_fotoselectie_map' ) ? thema_fotoselectie_map() : 0;
			if ( $map ) {
				wp_set_object_terms( $foto, array( $map ), 'happyfiles_category' );
			}
			if ( function_exists( 'thema_webp_kopieen' ) ) {
				thema_webp_kopieen( array( $foto ) );
			}
		}
	}

	$blok = array(
		'acf_fc_layout'            => 'citaat',
		'field_citaat_foto'        => $foto ? (string) $foto : '',
		'field_citaat_citaat'      => 'Elke rit kijk ik nog even achterom. Zie ik de hele bus meezingen, dan weet ik het weer: *dit is het mooiste werk dat er is.*',
		'field_citaat_naam'        => 'Robbie van den Biggelaar',
		'field_citaat_rol'         => 'Eigenaar Lekker Geel Busje',
		'field_citaat_eyebrow'     => '',
		'field_citaat_foto_kant'   => 'links',
		'field_citaat_achtergrond' => 'standaard',
		'field_citaat_anker'       => 'robbie',
	);
	$plek = count( $rijen );
	foreach ( array_values( $rijen ) as $i => $rij ) {
		if ( 'route' === ( $rij['acf_fc_layout'] ?? '' ) ) {
			$plek = $i;
			break;
		}
	}
	$rijen = array_values( $rijen );
	array_splice( $rijen, $plek, 0, array( $blok ) );
	update_field( 'field_blokken', $rijen, $pagina->ID );
	clean_post_cache( $pagina->ID );
	return array( 'foto' => (int) $foto, 'plek' => $plek );
}
