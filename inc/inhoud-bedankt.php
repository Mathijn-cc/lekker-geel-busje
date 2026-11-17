<?php
/**
 * Bedankpagina's (november 2026): /bedankt-voor-je-vraag/ na het contactformulier en
 * /bedankt-voor-je-aanvraag/ na het offerteformulier. Uitroltaken bedankt-v1 en bedankt-bevestiging-v1.
 *
 * - Niet in Google (noindex) en niet in de sitemap: het zijn eindpunten, geen landingspagina's.
 * - Gravity Forms stuurt na het versturen door naar deze pagina's. Daarmee zijn ze ook het meetpunt
 *   voor conversies (Google Ads, GA4): een paginaweergave is een verstuurd formulier.
 */

defined( 'ABSPATH' ) || exit;

/** Spotify-playlist Lekker Geel Busje: feest op wielen (moet in Spotify op openbaar staan). */
const THEMA_BEDANKT_PLAYLIST = 'https://open.spotify.com/playlist/5QXsFM5TNPaD9T4q3z8RKW';

/**
 * Slug per formulier.
 */
function thema_bedankt_slugs() {
	return array(
		'contact' => 'bedankt-voor-je-vraag',
		'offerte' => 'bedankt-voor-je-aanvraag',
	);
}

function thema_is_bedankt( $post = null ) {
	$post = get_post( $post );
	return $post && 'page' === $post->post_type && in_array( $post->post_name, thema_bedankt_slugs(), true );
}

/* Niet in de zoekresultaten, wel links volgen. */
add_filter( 'wp_robots', function ( $robots ) {
	if ( is_page() && thema_is_bedankt() ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
	}
	return $robots;
} );
add_filter( 'rank_math/frontend/robots', function ( $robots ) {
	if ( is_page() && thema_is_bedankt() ) {
		$robots['index'] = 'noindex';
	}
	return $robots;
} );
add_filter( 'wpseo_robots', function ( $robots ) {
	return is_page() && thema_is_bedankt() ? 'noindex, follow' : $robots;
} );

/* Niet in de sitemap. */
add_filter( 'wp_sitemaps_posts_query_args', function ( $args, $post_type ) {
	if ( 'page' !== $post_type ) {
		return $args;
	}
	$ids = array();
	foreach ( thema_bedankt_slugs() as $slug ) {
		$p = get_page_by_path( $slug );
		if ( $p ) {
			$ids[] = (int) $p->ID;
		}
	}
	if ( $ids ) {
		$args['post__not_in'] = array_merge( (array) ( $args['post__not_in'] ?? array() ), $ids );
	}
	return $args;
}, 10, 2 );

/**
 * De nummers die in het playlistblok in de kijker staan. De volledige lijst staat in Spotify.
 */
function thema_bedankt_playlist( $anker = 'playlist' ) {
	return array(
		'acf_fc_layout' => 'playlist',
		'eyebrow'       => 'Alvast in de stemming',
		'kop'           => 'Zet *het busgeluid* alvast aan',
		'tekst'         => 'Een rit zonder muziek? Ondenkbaar. Daarom maakten we een playlist met een knipoog naar de bus: Hollandse meezingers, 80s en 90s classics, house en hardstyle. Zet hem aan in de groepsapp, in de auto of gewoon thuis.',
		'spotify'       => THEMA_BEDANKT_PLAYLIST,
		'titel'         => 'Lekker Geel Busje: feest op wielen',
		'knop'          => 'Open in Spotify',
		'nummers'       => implode( "\n", array(
			'Vengaboys | We like to Party! (The Vengabus)',
			'The Who | Magic Bus',
			'The Beatles | Yellow Submarine',
			'Snollebollekes | Links rechts',
			'Billy Ocean | Get Outta My Dreams, Get into My Car',
			'2 Unlimited | No Limit',
			'Fisher | Losing It',
			'Da Tweekaz | Jägermeister',
		) ),
		'achtergrond'   => 'paars',
		'anker'         => $anker,
	);
}

/**
 * Pagina aanmaken of bijwerken. Geeft het ID terug.
 */
function thema_bedankt_pagina( $slug, $titel ) {
	$bestaand = get_page_by_path( $slug, OBJECT, 'page' );
	if ( $bestaand ) {
		return (int) $bestaand->ID;
	}
	return (int) wp_insert_post( array(
		'post_type'      => 'page',
		'post_name'      => $slug,
		'post_title'     => $titel,
		'post_status'    => 'publish',
		'comment_status' => 'closed',
		'menu_order'     => 99,
	) );
}

/**
 * Uitroltaak bedankt-v1: beide bedankpagina's met hun blokken.
 */
function thema_plaats_bedankt() {
	if ( ! function_exists( 'update_field' ) ) {
		return 0;
	}
	$slugs = thema_bedankt_slugs();
	$b     = array();
	foreach ( array(
		'gastvrouw-verwelkomt-gasten-bij-de-bus',
		'meezingen-met-microfoon-in-sfeerbus',
		'verlichte-sfeerbus-van-buiten',
		'proosten-in-het-gangpad-van-de-bus',
		'feest-met-armen-omhoog-in-de-bus',
		'high-five-bij-het-instappen',
	) as $naam ) {
		$b[ $naam ] = thema_beeld_op_naam( $naam );
	}
	$fotos = function ( $namen ) use ( $b ) {
		return array_values( array_filter( array_map( fn( $n ) => $b[ $n ] ?? 0, $namen ) ) );
	};

	$offerte  = thema_offerte_url();
	$regels   = get_page_by_path( 'huisregels' );
	$regels   = $regels ? get_permalink( $regels ) : home_url( '/huisregels/' );
	$tips     = get_category_by_slug( 'tips-en-tricks' );
	$tips_url = $tips ? get_category_link( $tips ) : home_url( '/blog/' );
	$uit      = array();

	/* ---------------------------------------------------------------- Na het contactformulier */

	$id = thema_bedankt_pagina( $slugs['contact'], 'Bedankt voor je vraag' );
	if ( $id ) {
		update_field( 'field_seo_seo_titel', 'Bedankt voor je vraag | Lekker Geel Busje', $id );
		update_field( 'field_seo_seo_omschrijving', 'Je vraag is binnen. Binnen 1 werkdag heb je antwoord van Lekker Geel Busje.', $id );

		$artikelen = array(
			'acf_fc_layout' => 'artikelen',
			'eyebrow'       => 'Tips en tricks',
			'kop'           => 'Handig voor *je feestje*',
			'intro'         => 'Spelletjes voor in de bus, ideeën voor de groep en alles wat je wilt weten voordat je instapt.',
			'keuze'         => array(),
			'onderwerp'     => $tips ? (int) $tips->term_id : 0,
			'aantal'        => 3,
			'knop'          => thema_link( 'Alle tips en tricks', $tips_url ),
			'achtergrond'   => 'standaard',
			'anker'         => 'tips',
		);

		update_field( 'field_blokken', array(
			array(
				'acf_fc_layout' => 'hero',
				'kop_1'         => 'Bedankt!',
				'kop_2'         => 'Je vraag is binnen',
				'plaatsen'      => '',
				'sub_1'         => 'Antwoord binnen 1 werkdag,',
				'sub_2'         => 'meestal sneller.',
				'knop'          => '',
				'knop_tonen'    => 0,
				'toon_score'    => 1,
				'stijl'         => 'foto',
				'fotos'         => $fotos( array( 'gastvrouw-verwelkomt-gasten-bij-de-bus', 'meezingen-met-microfoon-in-sfeerbus', 'verlichte-sfeerbus-van-buiten' ) ),
				'vorm'          => 'auto',
				'hoogte'        => 'compact',
				'anker'         => 'top',
			),
			array(
				'acf_fc_layout' => 'route',
				'eyebrow'       => 'Wat er nu gebeurt',
				'kop'           => 'Zo gaat het *verder*',
				'intro'         => 'Je vraag komt rechtstreeks bij ons binnen, er zit geen callcenter tussen. Dit kun je verwachten.',
				'stappen'       => array(
					array( 'tijd' => 'Nu', 'icoon' => 'mail', 'titel' => 'Je vraag ligt bij ons', 'tekst' => 'Je hoeft niets meer te doen. We lezen elke vraag zelf en zoeken het antwoord voor je uit.' ),
					array( 'tijd' => 'Binnen 1 werkdag', 'icoon' => 'klok', 'titel' => 'Je krijgt antwoord', 'tekst' => 'Meestal al eerder, per mail op het adres dat je gaf. Niets gezien? Kijk dan even bij je ongewenste mail.' ),
					array( 'tijd' => 'Haast?', 'icoon' => 'telefoon', 'titel' => 'Bel of app ons gerust', 'tekst' => 'Gaat het om een rit die al snel is, of wil je even sparren? Bel of app ons, dan schakelen we meteen.' ),
				),
				'slot'          => '<p>Wil je ondertussen al weten wat jullie rit kost? Via het <a href="' . esc_url( $offerte ) . '">offerteformulier</a> heb je binnen 1 werkdag een vaste all-in prijs.</p>',
				'knop'          => thema_link( 'Vraag je prijs aan', $offerte ),
				'achtergrond'   => 'standaard',
				'anker'         => 'verder',
			),
			thema_bedankt_playlist(),
			array(
				'acf_fc_layout' => 'gelegenheden',
				'kop'           => "Waar gaat jullie\n*feestje* naartoe?",
				'weergave'      => 'raster',
				'keuze'         => array(),
				'achtergrond'   => 'standaard',
				'anker'         => 'gelegenheden',
			),
			$artikelen,
			array(
				'acf_fc_layout' => 'slot_cta',
				'kop'           => 'Plannen gemaakt?',
				'pil'           => 'Wij rijden!',
				'tekst'         => 'Vraag vrijblijvend je prijs aan. Binnen 1 werkdag weet je waar je aan toe bent.',
				'knop'          => thema_link( 'Vraag je prijs aan', $offerte ),
				'anker'         => 'prijs',
			),
		), $id );
		clean_post_cache( $id );
		$uit['contact'] = $id;
	}

	/* ---------------------------------------------------------------- Na het offerteformulier */

	$id = thema_bedankt_pagina( $slugs['offerte'], 'Bedankt voor je aanvraag' );
	if ( $id ) {
		update_field( 'field_seo_seo_titel', 'Bedankt voor je aanvraag | Lekker Geel Busje', $id );
		update_field( 'field_seo_seo_omschrijving', 'Je aanvraag is binnen. Binnen 1 werkdag heb je een vaste all-in prijs van Lekker Geel Busje in je mail.', $id );

		$faq           = thema_blok_faq();
		$faq['kop']    = 'Vragen terwijl *je wacht*';
		$faq['tekst']  = '';
		$faq['schema'] = 0;
		$faq['anker']  = 'vragen';
		$faq['vragen'] = array_map( fn( $v ) => array( 'vraag' => $v[0], 'antwoord' => $v[1] ), array(
			array( 'Wanneer krijg ik de prijs?', 'Binnen 1 werkdag, meestal sneller. De prijs komt per mail op het adres dat je hebt ingevuld. Niets gezien? Kijk dan even bij je ongewenste mail, of bel ons gerust.' ),
			array( 'Wat zit er in de prijs?', 'Alles voor de rit: chauffeur, gastvrouwke of gastheer, brandstof en btw. Ook de professionele verlichting, lasers, rookmachine en geluidsinstallatie zitten er altijd in. Eén vaste all-in prijs, zonder kosten achteraf.' ),
			array( 'Mogen we drinken in de bus?', 'Ja, drinken in de bus mag. Je bestelt je drankjes bij ons gastvrouwke, uit een koeling vol koude drank. Eigen drank meenemen is niet toegestaan.' ),
			array( 'We zijn met een grote groep. Kan dat?', 'Ja. Er kunnen meerdere bussen tegelijk rijden, dus ook met een grote groep gaat iedereen mee. Hoe eerder je vastlegt, hoe groter de kans dat jullie datum nog vrij is.' ),
			array( 'Kan de factuur op bedrijfsnaam?', 'Ja, we kunnen factureren op bedrijfsnaam. Zet het er even bij als je op de prijs reageert.' ),
			array( 'Ik wil iets aanpassen aan mijn aanvraag. Hoe?', 'Bel, mail of app ons met je naam en de datum van de rit, dan passen we het aan. Het formulier opnieuw invullen hoeft niet.' ),
		) );

		$reviews             = thema_blok_reviews();
		$reviews['kop']      = 'Zij gingen *je voor*';
		$reviews['weergave'] = 'compact';

		update_field( 'field_blokken', array(
			array(
				'acf_fc_layout' => 'hero',
				'kop_1'         => 'Yes!',
				'kop_2'         => 'Je aanvraag is binnen',
				'plaatsen'      => '',
				'sub_1'         => 'Binnen 1 werkdag',
				'sub_2'         => 'je vaste prijs in je mail.',
				'knop'          => '',
				'knop_tonen'    => 0,
				'toon_score'    => 1,
				'stijl'         => 'foto',
				'fotos'         => $fotos( array( 'proosten-in-het-gangpad-van-de-bus', 'feest-met-armen-omhoog-in-de-bus', 'high-five-bij-het-instappen' ) ),
				'vorm'          => 'auto',
				'hoogte'        => 'compact',
				'anker'         => 'top',
			),
			array(
				'acf_fc_layout' => 'route',
				'eyebrow'       => 'Van aanvraag naar feestrit',
				'kop'           => 'Dit gebeurt er *nu*',
				'intro'         => 'Geen gedoe en geen kleine lettertjes. Zo gaat het van je aanvraag naar de dag zelf.',
				'stappen'       => array(
					array( 'tijd' => 'Vandaag', 'icoon' => 'kalender', 'titel' => 'We checken jullie datum', 'tekst' => 'We kijken of er op jullie datum en tijd een bus vrij is. Grote groep? Dan rijden er gewoon meerdere bussen tegelijk.' ),
					array( 'tijd' => 'Binnen 1 werkdag', 'icoon' => 'mail', 'titel' => 'Je vaste prijs in je mail', 'tekst' => 'Een all-in prijs op maat. Chauffeur, gastvrouwke, licht, lasers, rook, geluid, brandstof en btw zitten er allemaal in.' ),
					array( 'tijd' => 'Jij beslist', 'icoon' => 'vink', 'titel' => 'Akkoord? Dan staat de bus vast', 'tekst' => 'Laat weten dat jullie erbij zijn, dan leggen we de bus voor jullie vast. Vragen over de prijs of de route? Stel ze gerust.' ),
					array( 'tijd' => 'Voor de rit', 'icoon' => 'telefoon', 'titel' => 'Kennismaken met je chauffeur', 'tekst' => 'Je chauffeur stelt zich vooraf aan je voor. Zo weet je met wie je op pad gaat en stem je de laatste details af.' ),
					array( 'tijd' => 'De grote dag', 'icoon' => 'feest', 'titel' => 'Instappen en feesten', 'tekst' => 'De bus staat op tijd klaar, het gastvrouwke heet jullie welkom en de muziek gaat aan. Het feest begint in de bus.' ),
				),
				'slot'          => '<p>Iets vergeten of wil je iets wijzigen? Bel, mail of app ons, dan passen we je aanvraag aan.</p>',
				'knop'          => '',
				'achtergrond'   => 'standaard',
				'anker'         => 'verder',
			),
			array(
				'acf_fc_layout' => 'checklist',
				'eyebrow'       => 'Voor de regelaar',
				'kop'           => 'Alvast regelen *met je groep*',
				'intro'         => 'Terwijl wij de prijs maken, maak jij de groep warm. Vink af wat je al geregeld hebt, de vinkjes blijven op je telefoon bewaard.',
				'items'         => array(
					array( 'wanneer' => 'Vandaag', 'taak' => 'Zet de datum in de groepsapp, zodat iedereen hem vrijhoudt', 'link' => '' ),
					array( 'wanneer' => 'Deze week', 'taak' => 'Tel de koppen: met hoeveel gaan jullie mee?', 'link' => '' ),
					array( 'wanneer' => 'Deze week', 'taak' => 'Spreek een opstapplek en een tijd af voor de hele groep', 'link' => '' ),
					array( 'wanneer' => 'Voor de rit', 'taak' => 'Deel de huisregels in de groepsapp', 'link' => thema_link( 'Huisregels', $regels ) ),
					array( 'wanneer' => 'Voor de rit', 'taak' => 'Eigen drank blijft thuis: drankjes bestel je bij het gastvrouwke', 'link' => '' ),
					array( 'wanneer' => 'Voor de rit', 'taak' => 'Zet de groep in de stemming met onze playlist', 'link' => thema_link( 'Naar de playlist', '#playlist' ) ),
					array( 'wanneer' => 'Extra', 'taak' => 'Spelletjes en ideeën voor onderweg', 'link' => thema_link( 'Tips en tricks', $tips_url ) ),
				),
				'knop'          => '',
				'achtergrond'   => 'standaard',
				'anker'         => 'checklist',
			),
			thema_bedankt_playlist(),
			$faq,
			$reviews,
		), $id );
		clean_post_cache( $id );
		$uit['offerte'] = $id;
	}

	return $uit;
}

/**
 * ID van het formulier in het contactblok op /contact/.
 */
function thema_contact_formulier_id() {
	$contact = get_page_by_path( 'contact' );
	if ( $contact && function_exists( 'get_field' ) ) {
		foreach ( (array) get_field( 'blokken', $contact->ID ) as $blok ) {
			if ( 'contact' === ( $blok['acf_fc_layout'] ?? '' ) && ! empty( $blok['formulier'] ) ) {
				return (int) $blok['formulier'];
			}
		}
	}
	return (int) apply_filters( 'thema_contact_formulier', 4 );
}

/**
 * Uitroltaak bedankt-bevestiging-v1: de standaardbevestiging van het contact- en offerteformulier
 * wordt een doorsturing naar de bedankpagina. De oude bevestigingen blijven bewaard in de optie
 * thema_bedankt_oude_bevestigingen, zodat ze terug te zetten zijn.
 */
function thema_bedankt_bevestigingen() {
	if ( ! class_exists( 'GFAPI' ) || ! class_exists( 'GFFormsModel' ) ) {
		return 'Gravity Forms niet actief';
	}
	$slugs   = thema_bedankt_slugs();
	$offerte = function_exists( 'thema_offerte_velden' ) ? (int) thema_offerte_velden()['formulier'] : 3;
	$paren   = array(
		thema_contact_formulier_id() => $slugs['contact'],
		$offerte                     => $slugs['offerte'],
	);
	$oud = (array) get_option( 'thema_bedankt_oude_bevestigingen', array() );
	$uit = array();
	foreach ( $paren as $form_id => $slug ) {
		$pagina = get_page_by_path( $slug );
		$form   = $form_id ? GFAPI::get_form( $form_id ) : false;
		if ( ! $pagina || ! $form ) {
			$uit[ $form_id ] = 'overgeslagen';
			continue;
		}
		$bevestigingen = (array) ( $form['confirmations'] ?? array() );
		if ( ! isset( $oud[ $form_id ] ) ) {
			$oud[ $form_id ] = $bevestigingen;
		}
		foreach ( $bevestigingen as $sleutel => $b ) {
			if ( ! empty( $b['isDefault'] ) ) {
				$bevestigingen[ $sleutel ]['type']        = 'page';
				$bevestigingen[ $sleutel ]['pageId']      = (int) $pagina->ID;
				$bevestigingen[ $sleutel ]['url']         = '';
				$bevestigingen[ $sleutel ]['queryString'] = '';
			}
		}
		GFFormsModel::update_form_meta( $form_id, $bevestigingen, 'confirmations' );
		$uit[ $form_id ] = (int) $pagina->ID;
	}
	update_option( 'thema_bedankt_oude_bevestigingen', $oud, false );
	return $uit;
}

/**
 * Uitroltaak bedankt-playlist-v1: de Spotify-link in het playlistblok van beide bedankpagina's zetten.
 */
function thema_bedankt_playlist_link() {
	if ( ! function_exists( 'update_sub_field' ) ) {
		return 0;
	}
	$uit = array();
	foreach ( thema_bedankt_slugs() as $slug ) {
		$pagina = get_page_by_path( $slug );
		if ( ! $pagina ) {
			continue;
		}
		foreach ( (array) get_field( 'blokken', $pagina->ID ) as $i => $blok ) {
			if ( 'playlist' === ( $blok['acf_fc_layout'] ?? '' ) && empty( $blok['spotify'] ) ) {
				update_sub_field( array( 'blokken', $i + 1, 'spotify' ), THEMA_BEDANKT_PLAYLIST, $pagina->ID );
				$uit[ $slug ] = $i + 1;
			}
		}
		clean_post_cache( $pagina->ID );
	}
	return $uit;
}
