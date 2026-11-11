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

/**
 * Nieuwe foto's met hun alt-tekst. De bestanden stonden tijdelijk in assets/fotoselectie en zijn na het plaatsen weer uit het thema gehaald.
 */
function thema_fotoselectie_lijst() {
	return array(
		'gasten-lachen-aan-tafel-in-sfeerbus' => 'Gasten lachen samen aan tafel in de sfeerbus van Lekker Geel Busje',
		'vrouw-danst-en-proost-in-sfeerbus' => 'Vrouw danst en proost met een drankje in de sfeerbus',
		'gastheer-deelt-drankjes-uit-aan-de-bar' => 'Gastheer deelt drankjes uit aan de bar in de bus',
		'vriendengroep-aan-tafel-in-sfeerbus' => 'Vriendengroep zit gezellig aan tafel in de sfeerbus',
		'proosten-in-het-gangpad-van-de-bus' => 'Gasten proosten staand in het gangpad van de bus',
		'handen-in-de-lucht-in-de-gele-bus' => 'Groep feest met de handen in de lucht in de gele bus',
		'gasten-stappen-in-lekker-geel-busje' => 'Vrolijke gasten stappen in bij Lekker Geel Busje',
		'high-five-bij-het-instappen' => 'Gastheer geeft gasten een high five bij het instappen',
		'gastvrouw-verwelkomt-gasten-bij-de-bus' => 'Gastvrouw verwelkomt gasten bij de deur van de bus',
		'chauffeur-zwaait-vanuit-de-gele-bus' => 'Chauffeur zwaait vanuit het raam van de gele bus',
		'gastheer-en-gastvrouw-voor-de-gele-bus' => 'Gastheer en gastvrouw van Lekker Geel Busje voor de gele bus',
		'portret-gastheer-lekker-geel-busje' => 'Portret van de gastheer van Lekker Geel Busje bij de bus',
		'gastheer-bij-de-gele-sfeerbus' => 'Gastheer met de armen over elkaar bij de gele sfeerbus',
		'gastvrouw-schenkt-drankje-aan-de-bar' => 'Gastvrouw geeft een drankje aan een gast aan de bar in de bus',
		'drankjes-halen-aan-de-bar-in-de-bus' => 'Gasten halen een drankje aan de bar in de sfeerbus',
		'gele-sfeerbus-bij-paleis-raadhuis-tilburg' => 'Gele sfeerbus van Lekker Geel Busje op het plein bij het Paleis-Raadhuis in Tilburg',
		'discolicht-en-feest-in-de-sfeerbus' => 'Feestende groep onder roze discolicht in de sfeerbus',
		'dansen-met-discolicht-in-de-partybus' => 'Vriendinnen dansen onder het discolicht in de partybus',
		'vriendinnen-feesten-in-de-bus' => 'Vriendinnen feesten met een drankje in de bus',
		'feest-met-armen-omhoog-in-de-bus' => 'Uitgelaten feest met de armen omhoog in de bus',
		'gasten-delen-shotjes-uit-in-de-bus' => 'Gast deelt shotjes uit aan de groep in de bus',
		'meezingen-met-microfoon-in-sfeerbus' => 'Vriendinnen zingen mee met een microfoon in de sfeerbus',
		'selfie-in-de-feestbus-met-rood-licht' => 'Stel maakt een selfie in de feestbus met rode verlichting',
		'selfie-met-lichtstaven-in-de-bus' => 'Vriendinnen maken een selfie met lichtstaven in de bus',
		'tropisch-themafeest-in-de-sfeerbus' => 'Selfie tijdens een tropisch themafeest in de sfeerbus',
		'groepsselfie-met-lichtstaven-in-partybus' => 'Groepsselfie met lichtstaven in de partybus',
		'gele-sfeerbus-in-de-avond-op-het-plein' => 'Gele sfeerbus met de lichten aan op het plein in de avond',
		'verlichte-sfeerbus-van-buiten' => 'De verlichte sfeerbus van buiten gezien in de avond',
		'feest-met-microfoons-en-bloemenkransen' => 'Feest met microfoons en bloemenkransen in de bus',
		'feest-met-blauw-licht-in-de-sfeerbus' => 'Gasten feesten onder blauwe verlichting in de sfeerbus',
	);
}

/**
 * Map Fotoselectie in HappyFiles (taxonomie happyfiles_category), aanmaken als hij er nog niet is.
 */
function thema_fotoselectie_map() {
	$tax = 'happyfiles_category';
	if ( ! taxonomy_exists( $tax ) ) {
		return 0;
	}
	foreach ( get_terms( array( 'taxonomy' => $tax, 'hide_empty' => false ) ) as $t ) {
		if ( 'fotoselectie' === strtolower( trim( $t->name ) ) ) {
			return (int) $t->term_id;
		}
	}
	$nieuw = wp_insert_term( 'Fotoselectie', $tax, array( 'slug' => 'fotoselectie' ) );
	return is_wp_error( $nieuw ) ? 0 : (int) $nieuw['term_id'];
}

/**
 * De foto's in de mediabibliotheek zetten (eenmalig per bestand), in de map Fotoselectie.
 */
function thema_fotoselectie_uploaden() {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	if ( function_exists( 'set_time_limit' ) ) {
		set_time_limit( 0 );
	}
	$map    = thema_fotoselectie_map();
	$bekend = (array) get_option( 'thema_fotoselectie', array() );
	$nieuw  = array();
	foreach ( thema_fotoselectie_lijst() as $naam => $alt ) {
		if ( ! empty( $bekend[ $naam ] ) && get_post( $bekend[ $naam ] ) ) {
			continue;
		}
		$bron = THEMA_PAD . '/assets/fotoselectie/' . $naam . '.jpg';
		if ( ! file_exists( $bron ) ) {
			continue;
		}
		$tijdelijk = wp_tempnam( $naam . '.jpg' );
		copy( $bron, $tijdelijk );
		$id = media_handle_sideload( array( 'name' => $naam . '.jpg', 'tmp_name' => $tijdelijk ), 0, $alt );
		if ( is_wp_error( $id ) ) {
			@unlink( $tijdelijk ); // phpcs:ignore
			continue;
		}
		update_post_meta( $id, '_wp_attachment_image_alt', $alt );
		wp_update_post( array( 'ID' => $id, 'post_content' => 'Foto: Jules van Iperen' ) );
		if ( $map ) {
			wp_set_object_terms( $id, array( $map ), 'happyfiles_category' );
		}
		$bekend[ $naam ] = (int) $id;
		$nieuw[]         = (int) $id;
		update_option( 'thema_fotoselectie', $bekend, false );
	}
	$webp = $nieuw && function_exists( 'thema_webp_kopieen' ) ? thema_webp_kopieen( $nieuw ) : null;
	return array( 'map' => $map, 'ids' => $bekend, 'webp' => $webp, 'context' => thema_fotos_context() );
}

/**
 * Per blok de layout, weergave, kop en de titels van de items, om de foto's passend te kiezen.
 */
function thema_fotos_context() {
	$uit = array();
	foreach ( get_posts( array( 'post_type' => array( 'page', 'gelegenheid' ), 'post_status' => 'publish', 'posts_per_page' => -1 ) ) as $p ) {
		$layouts = (array) get_post_meta( $p->ID, 'blokken', true );
		$blokken = array();
		foreach ( $layouts as $i => $layout ) {
			$b = array( 'l' => $layout, 'w' => get_post_meta( $p->ID, "blokken_{$i}_weergave", true ), 'kop' => wp_strip_all_tags( (string) get_post_meta( $p->ID, "blokken_{$i}_kop", true ) ) );
			$n = (int) get_post_meta( $p->ID, "blokken_{$i}_items", true );
			for ( $j = 0; $j < $n; $j++ ) {
				$b['items'][] = wp_strip_all_tags( (string) get_post_meta( $p->ID, "blokken_{$i}_items_{$j}_titel", true ) );
			}
			$blokken[] = $b;
		}
		$uit[ $p->post_name ] = $blokken;
	}
	return $uit;
}

/**
 * Welke foto waar staat, per bericht-ID op de staging. Waarden zijn de bestandsnamen uit thema_fotoselectie_lijst().
 * Elke foto staat hooguit een keer per pagina; over de hele site komt een foto twee tot vier keer terug.
 */
function thema_fotoselectie_plan() {
	return array(
		27 => array( // home
			'blokken_0_beeld' => 'vriendinnen-feesten-in-de-bus',
			'blokken_0_fotos' => array( 'dansen-met-discolicht-in-de-partybus', 'selfie-in-de-feestbus-met-rood-licht', 'gele-sfeerbus-bij-paleis-raadhuis-tilburg' ),
			'blokken_0_extra' => array( 'gasten-stappen-in-lekker-geel-busje', 'gastvrouw-schenkt-drankje-aan-de-bar', 'vrouw-danst-en-proost-in-sfeerbus', 'gastvrouw-verwelkomt-gasten-bij-de-bus' ),
			'blokken_3_beelden' => array( 'gasten-delen-shotjes-uit-in-de-bus', 'proosten-in-het-gangpad-van-de-bus', 'gastheer-deelt-drankjes-uit-aan-de-bar' ),
			'blokken_6_items_0_beeld' => 'gasten-lachen-aan-tafel-in-sfeerbus',
			'blokken_6_items_1_beeld' => 'discolicht-en-feest-in-de-sfeerbus',
			'blokken_6_items_2_beeld' => 'feest-met-blauw-licht-in-de-sfeerbus',
			'blokken_6_items_3_beeld' => 'gastheer-en-gastvrouw-voor-de-gele-bus',
			'blokken_6_items_4_beeld' => 'chauffeur-zwaait-vanuit-de-gele-bus',
			'blokken_6_items_5_beeld' => 'gele-sfeerbus-in-de-avond-op-het-plein',
			'blokken_8_beeld' => 'verlichte-sfeerbus-van-buiten',
			'blokken_11_poster' => 'tropisch-themafeest-in-de-sfeerbus',
		),
		198 => array( // over-ons
			'blokken_0_fotos' => array( 'gastheer-en-gastvrouw-voor-de-gele-bus', 'high-five-bij-het-instappen', 'gastheer-deelt-drankjes-uit-aan-de-bar', 'gastvrouw-schenkt-drankje-aan-de-bar', 'portret-gastheer-lekker-geel-busje' ),
			'blokken_1_beelden' => array( 'gasten-stappen-in-lekker-geel-busje', 'gastvrouw-verwelkomt-gasten-bij-de-bus', 'feest-met-armen-omhoog-in-de-bus' ),
			'blokken_3_items_0_beeld' => 'gele-sfeerbus-in-de-avond-op-het-plein',
			'blokken_6_items_0_beeld' => 'verlichte-sfeerbus-van-buiten',
			'blokken_6_items_1_beeld' => 'vriendengroep-aan-tafel-in-sfeerbus',
			'blokken_6_items_2_beeld' => 'chauffeur-zwaait-vanuit-de-gele-bus',
			'blokken_6_items_3_beeld' => 'drankjes-halen-aan-de-bar-in-de-bus',
			'blokken_6_items_4_beeld' => 'vriendinnen-feesten-in-de-bus',
		),
		124 => array( // onze-bussen
			'blokken_0_beeld' => 'gele-sfeerbus-in-de-avond-op-het-plein',
			'blokken_0_fotos' => array( 'gele-sfeerbus-bij-paleis-raadhuis-tilburg', 'verlichte-sfeerbus-van-buiten', 'discolicht-en-feest-in-de-sfeerbus' ),
			'blokken_2_items_0_beeld' => 'gasten-lachen-aan-tafel-in-sfeerbus',
			'blokken_2_items_1_beeld' => 'handen-in-de-lucht-in-de-gele-bus',
			'blokken_2_items_2_beeld' => 'dansen-met-discolicht-in-de-partybus',
			'blokken_2_items_3_beeld' => 'gastvrouw-verwelkomt-gasten-bij-de-bus',
			'blokken_2_items_4_beeld' => 'chauffeur-zwaait-vanuit-de-gele-bus',
			'blokken_2_items_5_beeld' => 'gastheer-bij-de-gele-sfeerbus',
			'blokken_4_beelden' => array( 'selfie-in-de-feestbus-met-rood-licht', 'feest-met-microfoons-en-bloemenkransen', 'vrouw-danst-en-proost-in-sfeerbus' ),
			'blokken_5_fotos' => array( 'feest-met-blauw-licht-in-de-sfeerbus', 'tropisch-themafeest-in-de-sfeerbus', 'groepsselfie-met-lichtstaven-in-partybus', 'gasten-delen-shotjes-uit-in-de-bus', 'selfie-met-lichtstaven-in-de-bus' ),
		),
		26 => array( // gelegenheden
			'blokken_0_beeld' => 'high-five-bij-het-instappen',
			'blokken_0_fotos' => array( 'tropisch-themafeest-in-de-sfeerbus', 'selfie-in-de-feestbus-met-rood-licht', 'feest-met-blauw-licht-in-de-sfeerbus' ),
		),
		140 => array( // offerte-aanvragen
			'blokken_3_items_0_beeld' => 'gastheer-deelt-drankjes-uit-aan-de-bar',
			'blokken_3_items_1_beeld' => 'portret-gastheer-lekker-geel-busje',
			'blokken_3_items_2_beeld' => 'gasten-stappen-in-lekker-geel-busje',
			'blokken_4_beeld' => 'discolicht-en-feest-in-de-sfeerbus',
		),
		192 => array( // tilburg
			'blokken_0_fotos' => array( 'gele-sfeerbus-bij-paleis-raadhuis-tilburg', 'feest-met-armen-omhoog-in-de-bus', 'gasten-lachen-aan-tafel-in-sfeerbus' ),
			'blokken_2_items_2_beeld' => 'gasten-delen-shotjes-uit-in-de-bus',
			'blokken_2_items_3_beeld' => 'feest-met-blauw-licht-in-de-sfeerbus',
			'blokken_2_items_4_beeld' => 'discolicht-en-feest-in-de-sfeerbus',
		),
		21 => array( // vrijgezellenfeest
			'_thumbnail_id' => 'selfie-met-lichtstaven-in-de-bus',
			'blokken_0_fotos' => array( 'vriendinnen-feesten-in-de-bus', 'tropisch-themafeest-in-de-sfeerbus', 'dansen-met-discolicht-in-de-partybus' ),
			'blokken_1_beelden' => array( 'proosten-in-het-gangpad-van-de-bus', 'meezingen-met-microfoon-in-sfeerbus', 'vrouw-danst-en-proost-in-sfeerbus' ),
		),
		69 => array( // trip-met-vrienden
			'_thumbnail_id' => 'groepsselfie-met-lichtstaven-in-partybus',
		),
		71 => array( // festival
			'_thumbnail_id' => 'handen-in-de-lucht-in-de-gele-bus',
		),
		23 => array( // bruiloft
			'_thumbnail_id' => 'feest-met-microfoons-en-bloemenkransen',
		),
		22 => array( // bedrijfsuitje
			'_thumbnail_id' => 'vriendengroep-aan-tafel-in-sfeerbus',
		),
	);
}

function thema_fotoselectie_toewijzen() {
	$ids    = (array) get_option( 'thema_fotoselectie', array() );
	$gezet  = 0;
	$missen = array();
	foreach ( thema_fotoselectie_plan() as $post_id => $velden ) {
		if ( ! get_post( $post_id ) ) {
			$missen[] = $post_id;
			continue;
		}
		foreach ( $velden as $sleutel => $namen ) {
			$lijst = array();
			foreach ( (array) $namen as $naam ) {
				if ( ! empty( $ids[ $naam ] ) ) {
					$lijst[] = (string) (int) $ids[ $naam ];
				}
			}
			if ( ! $lijst ) {
				$missen[] = $post_id . ':' . $sleutel;
				continue;
			}
			if ( '_thumbnail_id' === $sleutel ) {
				set_post_thumbnail( $post_id, (int) $lijst[0] );
			} elseif ( metadata_exists( 'post', $post_id, '_' . $sleutel ) ) {
				update_post_meta( $post_id, $sleutel, is_array( $namen ) ? $lijst : $lijst[0] );
			} else {
				$missen[] = $post_id . ':' . $sleutel;
				continue;
			}
			$gezet++;
		}
		clean_post_cache( $post_id );
	}
	return array( 'gezet' => $gezet, 'missen' => $missen );
}
