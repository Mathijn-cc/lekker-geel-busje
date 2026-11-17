<?php
/**
 * Adreszoeker voor het offerteformulier.
 *
 * Bezoekers typen een adres, plaats of bedrijfsnaam en kiezen uit een lijst,
 * zoals bij de grote busbemiddelaars. De zoekopdracht loopt via de server:
 *
 * 1. Google Places (nieuw), als er een API-sleutel is. Vindt ook bedrijven,
 *    zalen en uitgaansgelegenheden, ook buiten Nederland.
 * 2. PDOK Locatieserver (Kadaster) als terugval: gratis, geen sleutel, alleen
 *    Nederlandse adressen en plaatsen, geen bedrijfsnamen.
 *
 * De sleutel blijft op de server en komt nooit in de browser. Zet hem in de
 * thema-instellingen (Algemeen, Menu en offerte) of als constante
 * THEMA_GOOGLE_PLACES_SLEUTEL in wp-config.php.
 *
 * Welke velden een zoeklijst krijgen staat in thema_offerte_velden(), onder 'adressen'.
 * De velden blijven gewone tekstvelden: zelf typen zonder te kiezen kan altijd.
 */

defined( 'ABSPATH' ) || exit;

const THEMA_ADRES_PDOK = 'https://api.pdok.nl/bzk/locatieserver/search/v3_1/suggest';

/**
 * De Google-sleutel: eerst wp-config, dan de thema-instellingen.
 */
function thema_adres_sleutel() {
	if ( defined( 'THEMA_GOOGLE_PLACES_SLEUTEL' ) && THEMA_GOOGLE_PLACES_SLEUTEL ) {
		return (string) THEMA_GOOGLE_PLACES_SLEUTEL;
	}
	return trim( (string) thema_instellingen( 'google_places_sleutel' ) );
}

/**
 * Gebruiken we Google? Niet zonder sleutel, en even niet na een fout
 * (verkeerde sleutel, quotum op), zodat PDOK het dan overneemt.
 */
function thema_adres_google_aan() {
	return '' !== thema_adres_sleutel() && ! get_transient( 'thema_adres_google_pauze' );
}

function thema_adres_google_fout( $antwoord ) {
	$code = is_wp_error( $antwoord ) ? $antwoord->get_error_message() : wp_remote_retrieve_response_code( $antwoord ) . ' ' . wp_remote_retrieve_body( $antwoord );
	error_log( 'Adreszoeker: Google Places gaf een fout, tien minuten terugval op PDOK. ' . mb_substr( $code, 0, 300 ) );
	set_transient( 'thema_adres_google_pauze', 1, 10 * MINUTE_IN_SECONDS );
}

/* --------------------------------------------------------------------------
 * Scripts: alleen laden waar het offerteformulier staat.
 * ----------------------------------------------------------------------- */

add_action( 'gform_enqueue_scripts', function ( $form ) {
	$v = thema_offerte_velden();
	if ( (int) $form['id'] !== (int) $v['formulier'] || empty( $v['adressen'] ) ) {
		return;
	}
	$velden = array();
	foreach ( $v['adressen'] as $id => $instelling ) {
		$velden[ (int) $form['id'] . '_' . (int) $id ] = array(
			'soort'  => $instelling['soort'],
			'plaats' => isset( $instelling['plaats'] ) ? (int) $form['id'] . '_' . (int) $instelling['plaats'] : '',
		);
	}
	wp_enqueue_script( 'thema-adreszoeker', THEMA_URL . '/assets/js/adreszoeker.js', array(), THEMA_VERSIE, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	wp_add_inline_script( 'thema-adreszoeker', 'window.themaAdressen = ' . wp_json_encode( array(
		'zoek'    => esc_url_raw( rest_url( 'thema/v1/adres/zoek' ) ),
		'details' => esc_url_raw( rest_url( 'thema/v1/adres/details' ) ),
		'velden'  => $velden,
	) ) . ';', 'before' );
} );

/* --------------------------------------------------------------------------
 * Uitleg en voorbeeld bij de velden: laat zien dat alleen het eerste veld
 * getypt hoeft te worden. Ook bij validatie, zodat de tekst na een fout blijft.
 * ----------------------------------------------------------------------- */

foreach ( array( 'gform_pre_render', 'gform_pre_validation' ) as $haak ) {
	add_filter( $haak, 'thema_adres_uitleg' );
}

function thema_adres_uitleg( $form ) {
	$v = thema_offerte_velden();
	if ( (int) $form['id'] !== (int) $v['formulier'] || empty( $v['adressen'] ) ) {
		return $form;
	}
	foreach ( $v['adressen'] as $id => $instelling ) {
		$veld = thema_offerte_veld( $form, $id );
		if ( ! $veld ) {
			continue;
		}
		if ( ! empty( $instelling['uitleg'] ) ) {
			$veld->description = $instelling['uitleg'];
		}
		if ( ! empty( $instelling['voorbeeld'] ) ) {
			$veld->placeholder = $instelling['voorbeeld'];
		}
	}
	return $form;
}

/* --------------------------------------------------------------------------
 * REST: zoeken en details.
 * ----------------------------------------------------------------------- */

add_action( 'rest_api_init', function () {
	register_rest_route( 'thema/v1', '/adres/zoek', array(
		'methods'             => 'GET',
		'callback'            => 'thema_adres_zoek',
		'permission_callback' => '__return_true',
		'args'                => array(
			'q'      => array( 'type' => 'string', 'required' => true ),
			'soort'  => array( 'type' => 'string', 'default' => 'volledig', 'enum' => array( 'adres', 'woonadres', 'plaats', 'volledig' ) ),
			'sessie' => array( 'type' => 'string', 'default' => '' ),
		),
	) );
	register_rest_route( 'thema/v1', '/adres/details', array(
		'methods'             => 'GET',
		'callback'            => 'thema_adres_details',
		'permission_callback' => '__return_true',
		'args'                => array(
			'id'     => array( 'type' => 'string', 'required' => true ),
			'sessie' => array( 'type' => 'string', 'default' => '' ),
		),
	) );
} );

/**
 * Ruime grens per bezoeker, tegen misbruik van het endpoint (en de sleutel).
 */
function thema_adres_te_vaak() {
	$ip    = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : '';
	$sleut = 'thema_adres_' . md5( $ip . wp_salt() );
	$tel   = (int) get_transient( $sleut );
	if ( $tel >= 300 ) {
		return true;
	}
	set_transient( $sleut, $tel + 1, 10 * MINUTE_IN_SECONDS );
	return false;
}

function thema_adres_sessie( $sessie ) {
	return preg_match( '/^[A-Za-z0-9_-]{8,36}$/', (string) $sessie ) ? (string) $sessie : '';
}

function thema_adres_zoek( WP_REST_Request $verzoek ) {
	$q = trim( wp_strip_all_tags( (string) $verzoek['q'] ) );
	$q = mb_substr( preg_replace( '/\s+/', ' ', $q ), 0, 100 );
	if ( mb_strlen( $q ) < 2 ) {
		return array( 'bron' => '', 'resultaten' => array() );
	}
	if ( thema_adres_te_vaak() ) {
		return new WP_Error( 'te_vaak', 'Even rustig aan.', array( 'status' => 429 ) );
	}
	$soort = (string) $verzoek['soort'];

	if ( thema_adres_google_aan() ) {
		$resultaten = thema_adres_google_zoek( $q, $soort, thema_adres_sessie( $verzoek['sessie'] ) );
		if ( null !== $resultaten ) {
			return array( 'bron' => 'google', 'resultaten' => $resultaten );
		}
	}
	return array( 'bron' => 'pdok', 'resultaten' => thema_adres_pdok_zoek( $q, $soort ) );
}

function thema_adres_details( WP_REST_Request $verzoek ) {
	$id = (string) $verzoek['id'];
	if ( ! preg_match( '/^[A-Za-z0-9_-]{10,400}$/', $id ) || '' === thema_adres_sleutel() ) {
		return new WP_Error( 'onbekend', 'Onbekende locatie.', array( 'status' => 400 ) );
	}
	if ( thema_adres_te_vaak() ) {
		return new WP_Error( 'te_vaak', 'Even rustig aan.', array( 'status' => 429 ) );
	}
	$args = array( 'languageCode' => 'nl', 'regionCode' => 'nl' );
	if ( thema_adres_sessie( $verzoek['sessie'] ) ) {
		$args['sessionToken'] = thema_adres_sessie( $verzoek['sessie'] );
	}
	$antwoord = wp_remote_get( add_query_arg( $args, 'https://places.googleapis.com/v1/places/' . rawurlencode( $id ) ), array(
		'timeout' => 5,
		'headers' => array(
			'X-Goog-Api-Key'   => thema_adres_sleutel(),
			// Alleen velden uit de goedkoopste categorie (Essentials). De naam komt uit de suggestie.
			'X-Goog-FieldMask' => 'addressComponents,formattedAddress,types',
			'Referer'          => home_url( '/' ),
		),
	) );
	if ( is_wp_error( $antwoord ) || 200 !== wp_remote_retrieve_response_code( $antwoord ) ) {
		thema_adres_google_fout( $antwoord );
		return new WP_Error( 'mislukt', 'Details ophalen lukte niet.', array( 'status' => 502 ) );
	}
	$plek  = json_decode( wp_remote_retrieve_body( $antwoord ), true );
	$delen = array();
	foreach ( (array) ( $plek['addressComponents'] ?? array() ) as $deel ) {
		foreach ( (array) ( $deel['types'] ?? array() ) as $type ) {
			if ( ! isset( $delen[ $type ] ) ) {
				$delen[ $type ] = array( (string) ( $deel['longText'] ?? '' ), (string) ( $deel['shortText'] ?? '' ) );
			}
		}
	}
	$types   = (array) ( $plek['types'] ?? array() );
	$adresje = array( 'street_address', 'premise', 'subpremise', 'route', 'locality', 'postal_code', 'political' );
	return thema_adres_delen(
		$delen['route'][0] ?? '',
		trim( ( $delen['street_number'][0] ?? '' ) . ( isset( $delen['subpremise'] ) ? ' ' . $delen['subpremise'][0] : '' ) ),
		$delen['postal_code'][0] ?? '',
		$delen['locality'][0] ?? ( $delen['postal_town'][0] ?? ( $delen['administrative_area_level_2'][0] ?? '' ) ),
		$delen['country'][0] ?? '',
		strtoupper( $delen['country'][1] ?? '' ),
		(bool) array_diff( $types, $adresje ) && ( in_array( 'establishment', $types, true ) || in_array( 'point_of_interest', $types, true ) )
	);
}

/**
 * Een adres in losse delen, gelijk voor Google en PDOK.
 */
function thema_adres_delen( $straat, $huisnummer, $postcode, $plaats, $land, $landcode, $bedrijf = false ) {
	$postcode = (string) $postcode;
	if ( preg_match( '/^(\d{4})\s?([A-Za-z]{2})$/', $postcode, $m ) ) {
		$postcode = $m[1] . ' ' . strtoupper( $m[2] );
	}
	return array(
		'straat'     => (string) $straat,
		'huisnummer' => (string) $huisnummer,
		'postcode'   => $postcode,
		'plaats'     => (string) $plaats,
		'land'       => 'NL' === $landcode ? '' : (string) $land,
		'bedrijf'    => (bool) $bedrijf,
	);
}

/**
 * Google Places Autocomplete (nieuw). Null bij een fout, dan neemt PDOK het over.
 */
function thema_adres_google_zoek( $q, $soort, $sessie ) {
	$body = array(
		'input'        => $q,
		'languageCode' => 'nl',
		'regionCode'   => 'nl',
		// Voorkeur voor Brabant, zonder andere plekken uit te sluiten.
		'locationBias' => array(
			'circle' => array(
				'center' => array( 'latitude' => 51.58, 'longitude' => 5.19 ),
				'radius' => 50000.0,
			),
		),
	);
	if ( 'plaats' === $soort ) {
		$body['includedPrimaryTypes'] = array( '(cities)' );
	} elseif ( 'woonadres' === $soort ) {
		$body['includedPrimaryTypes'] = array( 'street_address', 'premise', 'subpremise', 'route' );
	}
	if ( $sessie ) {
		$body['sessionToken'] = $sessie;
	}
	$antwoord = wp_remote_post( 'https://places.googleapis.com/v1/places:autocomplete', array(
		'timeout' => 4,
		'headers' => array(
			'Content-Type'   => 'application/json',
			'X-Goog-Api-Key' => thema_adres_sleutel(),
			'Referer'        => home_url( '/' ),
		),
		'body'    => wp_json_encode( $body ),
	) );
	if ( is_wp_error( $antwoord ) || 200 !== wp_remote_retrieve_response_code( $antwoord ) ) {
		thema_adres_google_fout( $antwoord );
		return null;
	}
	$data       = json_decode( wp_remote_retrieve_body( $antwoord ), true );
	$resultaten = array();
	foreach ( (array) ( $data['suggestions'] ?? array() ) as $suggestie ) {
		$p = $suggestie['placePrediction'] ?? null;
		if ( ! $p || empty( $p['placeId'] ) ) {
			continue;
		}
		$titel = (string) ( $p['structuredFormat']['mainText']['text'] ?? ( $p['text']['text'] ?? '' ) );
		$sub   = preg_replace( '/,\s*(Nederland|Netherlands)$/u', '', (string) ( $p['structuredFormat']['secondaryText']['text'] ?? '' ) );
		$types = (array) ( $p['types'] ?? array() );
		if ( 'plaats' === $soort ) {
			$label = 'Plaats';
		} elseif ( array_intersect( $types, array( 'establishment', 'point_of_interest' ) ) ) {
			$label = 'Locatie';
		} elseif ( array_intersect( $types, array( 'locality', 'postal_town', 'administrative_area_level_2', 'sublocality' ) ) ) {
			$label = 'Plaats';
		} else {
			$label = 'Adres';
		}
		$resultaten[] = array(
			'id'    => (string) $p['placeId'],
			'titel' => $titel,
			'sub'   => $sub,
			'label' => $label,
			// Plaatsen hebben genoeg aan de naam; geen extra (betaalde) aanvraag.
			'delen' => 'Plaats' === $label ? thema_adres_delen( '', '', '', $titel, '', 'NL' ) : null,
		);
	}
	return array_slice( $resultaten, 0, 6 );
}

/**
 * PDOK Locatieserver. Antwoorden worden een dag bewaard: adressen veranderen niet per uur.
 */
function thema_adres_pdok_zoek( $q, $soort ) {
	$typen = array(
		'plaats'    => 'type:woonplaats',
		'woonadres' => 'type:(adres OR weg)',
	);
	$args = array(
		'q'    => $q,
		'fq'   => $typen[ $soort ] ?? 'type:(adres OR weg OR woonplaats)',
		'rows' => 6,
		'fl'   => 'id,type,weergavenaam,straatnaam,huis_nlt,postcode,woonplaatsnaam',
	);
	$cache = 'thema_adres_pdok_' . md5( wp_json_encode( $args ) );
	$docs  = get_transient( $cache );
	if ( false === $docs ) {
		$antwoord = wp_remote_get( add_query_arg( array_map( 'rawurlencode', $args ), THEMA_ADRES_PDOK ), array( 'timeout' => 4 ) );
		if ( is_wp_error( $antwoord ) || 200 !== wp_remote_retrieve_response_code( $antwoord ) ) {
			return array();
		}
		$data = json_decode( wp_remote_retrieve_body( $antwoord ), true );
		$docs = (array) ( $data['response']['docs'] ?? array() );
		set_transient( $cache, $docs, DAY_IN_SECONDS );
	}
	$resultaten = array();
	foreach ( $docs as $doc ) {
		$type   = (string) ( $doc['type'] ?? '' );
		$plaats = (string) ( $doc['woonplaatsnaam'] ?? '' );
		if ( 'woonplaats' === $type ) {
			$titel = $plaats;
			$sub   = trim( preg_replace( '/^[^,]*,\s*/', '', (string) ( $doc['weergavenaam'] ?? '' ) ) );
			$label = 'Plaats';
		}
		$delen = thema_adres_delen( $doc['straatnaam'] ?? '', $doc['huis_nlt'] ?? '', $doc['postcode'] ?? '', $plaats, '', 'NL' );
		if ( 'woonplaats' !== $type ) {
			$titel = trim( $delen['straat'] . ' ' . $delen['huisnummer'] );
			$sub   = trim( $delen['postcode'] . ' ' . $plaats );
			$label = 'Adres';
		}
		$resultaten[] = array(
			'id'    => (string) ( $doc['id'] ?? '' ),
			'titel' => $titel,
			'sub'   => $sub,
			'label' => $label,
			'delen' => $delen,
		);
	}
	return $resultaten;
}
