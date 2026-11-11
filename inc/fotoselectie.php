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
 * Nieuwe foto's met hun alt-tekst. De bestanden staan tijdelijk in assets/fotoselectie en gaan na het plaatsen uit het thema.
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
