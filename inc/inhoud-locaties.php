<?php
/**
 * Plaatspagina's onder /locaties/ (Maastricht, Rotterdam, Amsterdam, Den Bosch), in de opbouw van Tilburg (pagina 192).
 */

defined( 'ABSPATH' ) || exit;

/* --------------------------------------------------------------------------
 * Bouwen van de plaatspagina's.
 * ----------------------------------------------------------------------- */

/**
 * Eerste werkende link uit een lijst. Een kandidaat is een url of array( url, woord ): dan moet
 * het woord ook in de pagina staan. Zo komt er nooit een dode of verkeerde link op de pagina.
 */
function thema_locatie_link( $kandidaten ) {
	foreach ( (array) $kandidaten as $k ) {
		$url   = is_array( $k ) ? $k[0] : $k;
		$woord = is_array( $k ) ? $k[1] : '';
		$r     = wp_remote_get( $url, array( 'timeout' => 12, 'redirection' => 5, 'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_0) AppleWebKit/537.36 Chrome/124 Safari/537.36' ) );
		if ( is_wp_error( $r ) || 200 !== (int) wp_remote_retrieve_response_code( $r ) ) {
			continue;
		}
		if ( $woord && false === stripos( wp_remote_retrieve_body( $r ), $woord ) ) {
			continue;
		}
		return $url;
	}
	return '';
}

/**
 * Foto van Wikimedia Commons in de mediabibliotheek, met naamsvermelding in het bijschrift.
 * Staat hij er al (zelfde bronbestand), dan wordt die gebruikt.
 */
function thema_commons_beeld( $bestand, $naam, $alt ) {
	$al = get_posts( array(
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'meta_key'       => 'thema_commons_bron',
		'meta_value'     => $bestand,
	) );
	if ( $al ) {
		return (int) $al[0];
	}
	$api = add_query_arg( array(
		'action' => 'query', 'format' => 'json', 'titles' => $bestand, 'prop' => 'imageinfo',
		'iiprop' => 'url|extmetadata', 'iiurlwidth' => 2560, 'iiextmetadatafilter' => 'Artist|LicenseShortName|LicenseUrl',
	), 'https://commons.wikimedia.org/w/api.php' );
	$r   = wp_remote_get( $api, array( 'timeout' => 20, 'user-agent' => 'LekkerGeelBusje/1.0 (staging; info@lekkergeelbusje.nl)' ) );
	$d   = is_wp_error( $r ) ? array() : json_decode( wp_remote_retrieve_body( $r ), true );
	$p   = ! empty( $d['query']['pages'] ) ? reset( $d['query']['pages'] ) : array();
	$ii  = $p['imageinfo'][0] ?? array();
	$url = $ii['thumburl'] ?? ( $ii['url'] ?? '' );
	if ( ! $url ) {
		return 0;
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$tmp = download_url( $url, 60 );
	if ( is_wp_error( $tmp ) ) {
		return 0;
	}
	$id = media_handle_sideload( array( 'name' => $naam . '.jpg', 'tmp_name' => $tmp ), 0, $alt );
	if ( is_wp_error( $id ) ) {
		@unlink( $tmp ); // phpcs:ignore
		return 0;
	}
	$maker   = trim( wp_strip_all_tags( $ii['extmetadata']['Artist']['value'] ?? '' ) ) ?: 'Onbekend';
	$licentie = $ii['extmetadata']['LicenseShortName']['value'] ?? '';
	$lic_url = $ii['extmetadata']['LicenseUrl']['value'] ?? '';
	$pagina  = $ii['descriptionurl'] ?? 'https://commons.wikimedia.org/wiki/' . rawurlencode( str_replace( ' ', '_', $bestand ) );
	$credit  = 'Foto: <a href="' . esc_url( $pagina ) . '" target="_blank" rel="noopener">' . esc_html( $maker ) . '</a> via Wikimedia Commons';
	if ( $licentie ) {
		$credit .= ', ' . ( $lic_url ? '<a href="' . esc_url( $lic_url ) . '" target="_blank" rel="noopener">' . esc_html( $licentie ) . '</a>' : esc_html( $licentie ) );
	}
	wp_update_post( array( 'ID' => $id, 'post_excerpt' => $credit, 'post_title' => $alt ) );
	update_post_meta( $id, '_wp_attachment_image_alt', $alt );
	update_post_meta( $id, 'thema_commons_bron', $bestand );
	return (int) $id;
}

/**
 * Een tip (kaart). $foto is array( 'commons', bestand, naam, alt ) of array( 'eigen', bestandsnaam ).
 */
function thema_locatie_tip( $t ) {
	$beeld     = 0;
	$tijdelijk = false;
	if ( 'commons' === ( $t['foto'][0] ?? '' ) ) {
		$beeld = thema_commons_beeld( $t['foto'][1], $t['foto'][2], $t['foto'][3] );
	}
	if ( ! $beeld && ! empty( $t['reserve'] ) ) {
		$beeld     = thema_beeld_op_naam( $t['reserve'] );
		$tijdelijk = true;
	}
	return array(
		'titel'     => $t['titel'],
		'soort'     => $t['soort'],
		'beeld'     => $beeld,
		'tijdelijk' => $tijdelijk,
		'tekst'     => $t['tekst'],
		'info'      => $t['info'] ?? '',
		'link'      => thema_locatie_link( $t['link'] ?? array() ),
		'linktekst' => $t['linktekst'] ?? 'Bekijk website',
	);
}

/**
 * Pagina onder /locaties/ aanmaken of bijwerken met de opbouw van Tilburg.
 */
function thema_locatie_pagina( $c ) {
	if ( ! function_exists( 'update_field' ) ) {
		return 'geen acf';
	}
	$hub = get_page_by_path( 'locaties' );
	if ( ! $hub ) {
		return 'geen hub';
	}
	$titel = 'Met de partybus naar ' . $c['plaats'];
	$post  = get_page_by_path( 'locaties/' . $c['slug'] );
	$id    = $post ? (int) $post->ID : (int) wp_insert_post( array(
		'post_type'   => 'page',
		'post_status' => 'publish',
		'post_title'  => $titel,
		'post_name'   => $c['slug'],
		'post_parent' => (int) $hub->ID,
		'menu_order'  => $c['volgorde'] ?? 0,
	) );
	if ( ! $id ) {
		return 'niet aangemaakt';
	}
	if ( function_exists( 'thema_seo_titel_zetten' ) ) {
		thema_seo_titel_zetten( $c['seo_titel'], $id );
		thema_seo_omschrijving_zetten( $c['seo_omschrijving'], $id );
	}
	$offerte_url = home_url( '/offerte-aanvragen/' );
	$plan        = thema_link( 'Plan je dagje ' . $c['plaats'], $offerte_url );
	$standaard   = array(
		'achtergrond' => 'standaard', 'achtergrond_1' => 'paars', 'achtergrond_2' => 'standaard', 'achtergrond_richting' => 'boven',
		'ruimte_boven' => 'normaal', 'ruimte_onder' => 'normaal', 'kopniveau' => 'auto', 'anker' => '',
	);
	$blok = function ( $velden ) use ( $standaard ) {
		return array_merge( $standaard, $velden );
	};
	$fotos = array_values( array_filter( array_map( 'thema_beeld_op_naam', $c['hero_fotos'] ) ) );
	$tips  = array_map( 'thema_locatie_tip', $c['tips'] );
	$links = array();
	foreach ( $tips as $t ) {
		$links[ $t['titel'] ] = $t['link'];
	}

	$blokken   = array();
	$blokken[] = array(
		'acf_fc_layout' => 'hero', 'kop_1' => 'Partybus huren', 'kop_2' => 'naar ' . $c['plaats'], 'plaatsen' => implode( "\n", $c['hero_wissel'] ),
		'sub_1' => 'Jouw feest begint', 'sub_2' => 'al in de bus!', 'knop' => $plan, 'knop_tonen' => 1, 'toon_score' => 1,
		'stijl' => 'collage', 'fotos' => $fotos, 'bento_klein' => 'Chauffeur en gastvrouwke', 'bento_groot' => 'Tot 88 personen',
		'vorm' => 'auto', 'hoogte' => 'compact', 'knop_vorm' => 'standaard', 'knop_kleur' => 'geel', 'ruimte_onder' => 'groot', 'kopniveau' => 'auto', 'anker' => 'top',
	);
	$blokken[] = $blok( array( 'acf_fc_layout' => 'aan_boord', 'eyebrow' => $c['plaats'] . ' als bestemming', 'kop' => $c['intro_kop'], 'tekst' => $c['intro'], 'plaatsing' => 'boven', 'anker' => 'bussen' ) );
	$blokken[] = $blok( array( 'acf_fc_layout' => 'lopende_band', 'items' => "Geen gedoe met treintijden.\nGeen BOB nodig.\nNiemand die eerder weg moet.", 'knop' => '', 'variant' => 'paars', 'tekstkleur' => 'kleurrijk', 'beweging' => 'vanzelf', 'rijen' => '1', 'schuin' => '0', 'knop_vorm' => 'standaard', 'knop_kleur' => 'geel', 'ruimte_boven' => 'groot' ) );
	$blokken[] = $blok( array(
		'acf_fc_layout' => 'route', 'eyebrow' => 'Zo kan het', 'kop' => 'Een dagje ' . $c['plaats'] . ' *met de bus*',
		'intro' => 'Zo ziet een rit naar ' . $c['plaats'] . ' er bijvoorbeeld uit. Jullie bepalen de route, wij zorgen dat iedereen er komt.',
		'stappen' => $c['route'],
		'slot' => '<p>Vier je een <a href="' . esc_url( home_url( '/gelegenheden/vrijgezellenfeest/' ) ) . '">vrijgezellenfeest</a> of een <a href="' . esc_url( home_url( '/gelegenheden/bedrijfsuitje/' ) ) . '">bedrijfsuitje</a> in ' . esc_html( $c['plaats'] ) . '? Dan denken we graag mee over de route. Bekijk ook <a href="' . esc_url( home_url( '/bussen/' ) ) . '">onze bussen</a>: in de grootste passen tot 88 personen.</p>',
		'knop' => thema_link( 'Plan je route', $offerte_url ), 'kop_uitlijning' => 'midden',
	) );
	$blokken[] = $blok( array( 'acf_fc_layout' => 'tips', 'eyebrow' => 'Activiteiten', 'kop' => $c['tips_kop'], 'intro' => $c['tips_intro'], 'items' => $tips, 'kolommen' => '3', 'kop_uitlijning' => 'links' ) );
	if ( ! empty( $c['vrijgezel'] ) ) {
		$blokken[] = $blok( array(
			'acf_fc_layout' => 'opdrachten', 'eyebrow' => 'Vrijgezellenfeest', 'kop' => $c['vrijgezel']['kop'], 'intro' => $c['vrijgezel']['intro'],
			'items' => $c['vrijgezel']['items'],
			'noot' => $c['vrijgezel']['noot'],
			'achtergrond' => 'paars', 'anker' => 'vrijgezellenfeest',
		) );
	}
	$blokken[] = $blok( array( 'acf_fc_layout' => 'lopende_band', 'items' => implode( "\n", $c['band'] ), 'knop' => '', 'variant' => 'subtiel', 'tekstkleur' => 'kleurrijk', 'beweging' => 'scroll', 'rijen' => '1', 'schuin' => '-2', 'knop_vorm' => 'standaard', 'knop_kleur' => 'geel' ) );
	foreach ( $c['gebieden'] as $g ) {
		$plekken = array();
		foreach ( $g['plekken'] as $p ) {
			$plekken[] = array( 'naam' => $p['naam'], 'soort' => $p['soort'], 'tekst' => $p['tekst'], 'adres' => $p['adres'] ?? '', 'link' => thema_locatie_link( $p['link'] ?? array() ) );
		}
		$blokken[] = $blok( array( 'acf_fc_layout' => 'gebied', 'eyebrow' => $g['eyebrow'], 'kop' => $g['kop'], 'tekst' => $g['tekst'], 'plekken' => $plekken ) );
	}
	$blokken[] = $blok( array( 'acf_fc_layout' => 'gelegenheden', 'kop' => "Wat vier jij\n*in " . $c['plaats'] . '?*', 'weergave' => 'schuiven', 'keuze' => '', 'achtergrond' => 'paars', 'anker' => 'gelegenheden' ) );
	$blokken[] = $blok( array( 'acf_fc_layout' => 'reviews', 'kop' => 'Deze feestgangers<br>*reden al mee*', 'weergave' => 'compact', 'achtergrond' => 'geel', 'anker' => 'reviews' ) );
	$blokken[] = $blok( array(
		'acf_fc_layout' => 'faq', 'kop' => 'Vragen over *' . $c['plaats'] . '*', 'knop' => '',
		'tekst' => 'Staat je vraag er niet tussen? Bel ons gerust, we denken graag met je mee.',
		'vragen' => array_map( fn( $v ) => array( 'vraag' => $v[0], 'antwoord' => $v[1] ), $c['faq'] ),
		'schema' => 1, 'knop_vorm' => 'standaard', 'knop_kleur' => 'geel', 'anker' => 'faq',
	) );
	$offerte = array_merge( $standaard, thema_blok_offerte() );
	$offerte['kop']         = 'Met je groep *naar ' . $c['plaats'] . '?*';
	$offerte['naar_waarde'] = $c['plaats'];
	$offerte['achtergrond'] = 'geel';
	$offerte['anker']       = 'offerte';
	$blokken[] = $offerte;

	update_field( 'field_blokken', $blokken, $id );
	return array( 'id' => $id, 'url' => get_permalink( $id ), 'fotos' => count( $fotos ), 'links' => $links );
}
