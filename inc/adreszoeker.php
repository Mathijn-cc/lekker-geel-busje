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
 * Vertrekadres en bestemming tonen een volledige regel (straat 1, 1234 AB Plaats);
 * daaronder worden straat + huisnummer, postcode en plaats los opgeslagen in
 * verborgen velden, plus een optioneel naamveld (bijv. Ziggo Dome).
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
 * Velden opzoeken: op ID, of op parameternaam (inputName) voor de velden
 * die de uitroltaak toevoegt.
 * ----------------------------------------------------------------------- */

function thema_adres_veld_id( $form, $verwijzing ) {
	if ( is_int( $verwijzing ) || ctype_digit( (string) $verwijzing ) ) {
		return thema_offerte_veld( $form, (int) $verwijzing ) ? (int) $verwijzing : 0;
	}
	foreach ( $form['fields'] as $veld ) {
		if ( (string) $veld->inputName === (string) $verwijzing ) {
			return (int) $veld->id;
		}
	}
	return 0;
}

/**
 * De adresinstellingen met echte veld-ID's in plaats van verwijzingen.
 */
function thema_adres_instellingen( $form ) {
	$v   = thema_offerte_velden();
	$uit = array();
	foreach ( (array) ( $v['adressen'] ?? array() ) as $id => $instelling ) {
		if ( ! thema_offerte_veld( $form, $id ) ) {
			continue;
		}
		$delen = array();
		foreach ( (array) ( $instelling['delen'] ?? array() ) as $deel => $verwijzing ) {
			$delen[ $deel ] = thema_adres_veld_id( $form, $verwijzing );
		}
		$uit[ (int) $id ] = array(
			'soort'     => $instelling['soort'],
			'plaats'    => isset( $instelling['plaats'] ) ? thema_adres_veld_id( $form, $instelling['plaats'] ) : 0,
			'delen'     => array_filter( $delen ) ? $delen : array(),
			'naam'      => isset( $instelling['naam'] ) ? thema_adres_veld_id( $form, $instelling['naam'] ) : 0,
			'verplicht' => $instelling['verplicht'] ?? '',
			'uitleg'    => $instelling['uitleg'] ?? '',
			'voorbeeld' => $instelling['voorbeeld'] ?? '',
		);
	}
	return $uit;
}

/* --------------------------------------------------------------------------
 * Scripts: alleen laden waar het offerteformulier staat.
 * ----------------------------------------------------------------------- */

add_action( 'gform_enqueue_scripts', function ( $form ) {
	$v = thema_offerte_velden();
	if ( (int) $form['id'] !== (int) $v['formulier'] ) {
		return;
	}
	$f      = (int) $form['id'];
	$sleut  = function ( $id ) use ( $f ) {
		return $id ? $f . '_' . (int) $id : '';
	};
	$velden = array();
	foreach ( thema_adres_instellingen( $form ) as $id => $a ) {
		$velden[ $sleut( $id ) ] = array(
			'soort'  => $a['soort'],
			'plaats' => $sleut( $a['plaats'] ),
			'delen'  => $a['delen'] ? array_map( $sleut, $a['delen'] ) : null,
			'naam'   => $sleut( $a['naam'] ),
		);
	}
	if ( ! $velden ) {
		return;
	}
	wp_enqueue_script( 'thema-adreszoeker', THEMA_URL . '/assets/js/adreszoeker.js', array(), THEMA_VERSIE, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	wp_add_inline_script( 'thema-adreszoeker', 'window.themaAdressen = ' . wp_json_encode( array(
		'zoek'    => esc_url_raw( rest_url( 'thema/v1/adres/zoek' ) ),
		'details' => esc_url_raw( rest_url( 'thema/v1/adres/details' ) ),
		'velden'  => $velden,
	) ) . ';', 'before' );
} );

/* --------------------------------------------------------------------------
 * Uitleg en voorbeeld bij de velden. Ook bij validatie, zodat de tekst na een fout blijft.
 * ----------------------------------------------------------------------- */

foreach ( array( 'gform_pre_render', 'gform_pre_validation' ) as $haak ) {
	add_filter( $haak, 'thema_adres_uitleg' );
}

function thema_adres_uitleg( $form ) {
	$v = thema_offerte_velden();
	if ( (int) $form['id'] !== (int) $v['formulier'] ) {
		return $form;
	}
	foreach ( thema_adres_instellingen( $form ) as $id => $a ) {
		$veld = thema_offerte_veld( $form, $id );
		if ( $a['uitleg'] ) {
			$veld->description = $a['uitleg'];
		}
		if ( $a['voorbeeld'] ) {
			$veld->placeholder = $a['voorbeeld'];
		}
	}
	return $form;
}

/* --------------------------------------------------------------------------
 * Losse delen: altijd gevuld, ook als iemand niets uit de lijst kiest.
 * Dan halen we straat, postcode en plaats zo goed mogelijk uit de regel.
 * ----------------------------------------------------------------------- */

/**
 * "Kerkstraat 3, 5061 EG Oisterwijk" naar straat, postcode en plaats.
 */
function thema_adres_ontleed( $regel ) {
	$regel    = trim( preg_replace( '/\s+/', ' ', (string) $regel ) );
	$postcode = '';
	if ( preg_match( '/\b(\d{4})\s?([A-Za-z]{2})\b/', $regel, $m ) ) {
		$postcode = $m[1] . ' ' . strtoupper( $m[2] );
		$regel    = str_replace( $m[0], ',', $regel );
	}
	$stukken = array_values( array_filter( array_map( 'trim', explode( ',', $regel ) ), function ( $stuk ) {
		return '' !== $stuk && ! preg_match( '/^(nederland|netherlands|the netherlands|nl|belgi(e|ë)|belgium|duitsland|deutschland|germany|luxemburg|luxembourg|frankrijk|france)$/iu', $stuk );
	} ) );
	// Buitenlandse postcode voor de plaats, zoals 2000 Antwerpen of 47533 Kleve.
	$laatste = count( $stukken ) - 1;
	if ( $laatste >= 0 && preg_match( '/^(\d{4,5})\s+(\D.*)$/u', $stukken[ $laatste ], $m ) && ( $laatste > 0 || '' === $postcode ) ) {
		$postcode            = $postcode ?: $m[1];
		$stukken[ $laatste ] = $m[2];
	}
	$straat = '';
	$plaats = '';
	if ( count( $stukken ) > 1 ) {
		$straat = preg_match( '/\d/', $stukken[0] ) ? $stukken[0] : '';
		$plaats = end( $stukken );
	} elseif ( 1 === count( $stukken ) ) {
		if ( preg_match( '/\d/', $stukken[0] ) ) {
			$straat = $stukken[0];
		} else {
			$plaats = $stukken[0];
		}
	}
	return array( 'straat' => $straat, 'postcode' => $postcode, 'plaats' => $plaats );
}

add_filter( 'gform_field_validation', function ( $resultaat, $waarde, $form, $veld ) {
	$v = thema_offerte_velden();
	if ( (int) $form['id'] !== (int) $v['formulier'] || ! $resultaat['is_valid'] ) {
		return $resultaat;
	}
	$a = thema_adres_instellingen( $form )[ (int) $veld->id ] ?? null;
	if ( ! $a || 'plaats' !== $a['verplicht'] || '' === trim( (string) $waarde ) || empty( $a['delen']['plaats'] ) ) {
		return $resultaat;
	}
	$plaats = trim( (string) rgpost( 'input_' . $a['delen']['plaats'] ) );
	if ( '' === $plaats && '' === thema_adres_ontleed( $waarde )['plaats'] ) {
		$resultaat['is_valid'] = false;
		$resultaat['message']  = 'We missen nog de plaats. Kies het adres uit de lijst, of typ het zo: Kerkstraat 3, 5061 EG Oisterwijk.';
	}
	return $resultaat;
}, 10, 4 );

add_action( 'gform_pre_submission', function ( $form ) {
	$v = thema_offerte_velden();
	if ( (int) $form['id'] !== (int) $v['formulier'] ) {
		return;
	}
	foreach ( thema_adres_instellingen( $form ) as $id => $a ) {
		if ( ! $a['delen'] ) {
			continue;
		}
		$regel = (string) rgpost( 'input_' . $id );
		if ( '' === trim( $regel ) ) {
			continue;
		}
		// De regel is leidend: wat eruit te halen valt, wint. Wat er niet in staat
		// (bijv. de plaats bij alleen "Kerkstraat 3"), blijft zoals de adreszoeker het zette.
		$ontleed = thema_adres_ontleed( $regel );
		foreach ( $a['delen'] as $deel => $deel_id ) {
			if ( $deel_id && '' !== $ontleed[ $deel ] ) {
				$_POST[ 'input_' . $deel_id ] = $ontleed[ $deel ];
			}
		}
	}
} );

/* --------------------------------------------------------------------------
 * Uitroltaak adresvelden-v1: voegt de naamvelden en verborgen velden toe aan
 * het offerteformulier. Vertrekplaats (72) wordt een verborgen veld; de
 * waarde blijft dezelfde. Draait een keer per omgeving.
 * ----------------------------------------------------------------------- */

function thema_adres_velden_toevoegen() {
	if ( ! class_exists( 'GFAPI' ) || ! class_exists( 'GF_Fields' ) ) {
		return 'Gravity Forms niet actief';
	}
	$v    = thema_offerte_velden();
	$form = GFAPI::get_form( $v['formulier'] );
	if ( ! $form ) {
		return 'formulier niet gevonden';
	}
	if ( thema_adres_veld_id( $form, 'vertrek_straat' ) ) {
		return 'al gedaan';
	}

	$volgende = 1 + max( array_map( function ( $veld ) {
		return (int) $veld->id;
	}, $form['fields'] ) );
	$maak = function ( $type, $label, $naam, $buur, $extra = array() ) use ( &$volgende, $form ) {
		return GF_Fields::create( array_merge( array(
			'id'                => $volgende++,
			'formId'            => (int) $form['id'],
			'type'              => $type,
			'label'             => $label,
			'inputName'         => $naam,
			'allowsPrepopulate' => true,
			'pageNumber'        => (int) $buur->pageNumber,
		), $extra ) );
	};
	$tekst = function ( $label, $naam, $buur, $uitleg, $voorbeeld, $groep ) use ( $maak ) {
		return $maak( 'text', $label, $naam, $buur, array(
			'isRequired'           => false,
			'description'          => $uitleg,
			'placeholder'          => $voorbeeld,
			'layoutGridColumnSpan' => 6,
			'layoutGroupId'        => $groep,
			'size'                 => 'large',
		) );
	};

	$velden = array();
	$gedaan = array();
	foreach ( $form['fields'] as $veld ) {
		$id = (int) $veld->id;

		// Vertrekplaats wordt verborgen: de adreszoeker of de server vult hem.
		if ( (int) $v['plaats'] === $id ) {
			$velden[] = GF_Fields::create( array(
				'id'                => $id,
				'formId'            => (int) $form['id'],
				'type'              => 'hidden',
				'label'             => $veld->label,
				'inputName'         => 'vertrek_plaats',
				'allowsPrepopulate' => true,
				'pageNumber'        => (int) $veld->pageNumber,
			) );
			$gedaan[] = 'vertrekplaats verborgen';
			continue;
		}

		$velden[] = $veld;

		if ( (int) $v['vertrekadres'] === $id ) {
			$veld->layoutGridColumnSpan = 6;
			$groep    = $veld->layoutGroupId ?: substr( md5( 'vertrek' ), 0, 8 );
			$veld->layoutGroupId = $groep;
			$velden[] = $tekst( 'Naam ophaallocatie', 'vertrek_naam', $veld, 'Optioneel, bijvoorbeeld een café, zaal of bedrijf.', 'bijv. Café De Lindeboom', $groep );
			$velden[] = $maak( 'hidden', 'Vertrek straat + huisnummer', 'vertrek_straat', $veld );
			$velden[] = $maak( 'hidden', 'Vertrek postcode', 'vertrek_postcode', $veld );
			$gedaan[] = 'vertrekvelden';
		}

		if ( (int) $v['bestemming'] === $id ) {
			$groep = substr( md5( 'bestemming' ), 0, 8 );
			$veld->layoutGridColumnSpan = 6;
			$veld->layoutGroupId        = $groep;
			$velden[] = $tekst( 'Naam bestemming', 'bestemming_naam', $veld, 'Optioneel, bijvoorbeeld de zaal of het festival.', 'bijv. Ziggo Dome of Rebirth Festival', $groep );
			$velden[] = $maak( 'hidden', 'Bestemming straat + huisnummer', 'bestemming_straat', $veld );
			$velden[] = $maak( 'hidden', 'Bestemming postcode', 'bestemming_postcode', $veld );
			$velden[] = $maak( 'hidden', 'Bestemming plaats', 'bestemming_plaats', $veld );
			$gedaan[] = 'bestemmingvelden';
		}
	}
	$form['fields'] = $velden;
	$uitkomst       = GFAPI::update_form( $form );
	return is_wp_error( $uitkomst ) ? $uitkomst->get_error_message() : $gedaan;
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

/* --------------------------------------------------------------------------
 * Uitroltaak adresvelden-v2 en -v3: indeling van stap 2. Datum en tijd naast elkaar,
 * adres- en naamvelden over de volle breedte, een lijntje tussen vertrek,
 * bestemming en terugreis (klasse veld-scheiding, zie thema.css).
 * ----------------------------------------------------------------------- */

function thema_offerte_indeling() {
	if ( ! class_exists( 'GFAPI' ) ) {
		return 'Gravity Forms niet actief';
	}
	$v    = thema_offerte_velden();
	$form = GFAPI::get_form( $v['formulier'] );
	if ( ! $form || empty( $v['indeling'] ) ) {
		return 'formulier of indeling niet gevonden';
	}
	$in     = $v['indeling'];
	$gedaan = array();

	foreach ( $in['rijen'] as $rij ) {
		$groep = substr( md5( 'rij-' . implode( '-', $rij ) ), 0, 8 );
		$span  = (int) floor( 12 / count( $rij ) );
		foreach ( $rij as $verwijzing ) {
			$veld = thema_offerte_veld( $form, thema_adres_veld_id( $form, $verwijzing ) );
			if ( $veld ) {
				$veld->layoutGroupId        = $groep;
				$veld->layoutGridColumnSpan = $span;
				$gedaan[]                   = $veld->id . ':' . $span;
			}
		}
	}
	foreach ( $in['vol'] as $verwijzing ) {
		$veld = thema_offerte_veld( $form, thema_adres_veld_id( $form, $verwijzing ) );
		if ( $veld ) {
			$veld->layoutGroupId        = substr( md5( 'vol-' . $veld->id ), 0, 8 );
			$veld->layoutGridColumnSpan = 12;
			$gedaan[]                   = $veld->id . ':12';
		}
	}
	foreach ( $in['scheiding'] as $verwijzing ) {
		$veld = thema_offerte_veld( $form, thema_adres_veld_id( $form, $verwijzing ) );
		if ( $veld && false === strpos( (string) $veld->cssClass, 'veld-scheiding' ) ) {
			$veld->cssClass = trim( $veld->cssClass . ' veld-scheiding' );
			$gedaan[]       = $veld->id . ':scheiding';
		}
	}
	foreach ( (array) ( $in['uitleg_boven'] ?? array() ) as $verwijzing ) {
		$veld = thema_offerte_veld( $form, thema_adres_veld_id( $form, $verwijzing ) );
		if ( $veld ) {
			$veld->descriptionPlacement = 'above';
			$gedaan[]                   = $veld->id . ':uitleg boven';
		}
	}
	$uitkomst = GFAPI::update_form( $form );
	return is_wp_error( $uitkomst ) ? $uitkomst->get_error_message() : $gedaan;
}
