<?php
/**
 * Try-out stoelverkoop (november 2026): de pagina /vrienden-van-amstel-live/ waar je een losse stoel koopt
 * in de bus naar De Vrienden van Amstel LIVE 2027 in Rotterdam Ahoy. Opstappen in Oisterwijk, Tilburg en Breda,
 * € 25 per persoon heen en terug, betalen vooraf via Tikkie. Uitroltaken vval-stoelen-v1 en vval-agenda-link-v1.
 *
 * Concertdata 2027 (gecheckt 17 nov 2026): 14 tot en met 18, 20 tot en met 25 en 27 tot en met 29 januari 2027.
 * Voor de try-out staan de vier weekendavonden open (vr 15, za 16, vr 22, za 23 januari). Ritten, tijden,
 * opstapplekken en aantallen past Lekker Geel Busje zelf aan in het blok Stoelen op de pagina.
 *
 * Zoekwoorden (SE Ranking, NL, 17 nov 2026): vrienden van amstel live 220, vrienden van amstel parkeren 70,
 * vrienden van amstel live tijden 70, vrienden van amstel live vervoer 10, ahoy parkeren 1.500 (moeilijkheid 87).
 * Het zoekvolume op vervoer is klein: de pagina draait vooral op delen via WhatsApp, social en advertenties.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Uitroltaak vval-stoelen-v1: de pagina met alle blokken.
 */
function thema_plaats_vval_stoelen() {
	if ( ! function_exists( 'update_field' ) ) {
		return 0;
	}
	$id = thema_voorbeeld_post( 'page', 'vrienden-van-amstel-live', 'Bus naar Vrienden van Amstel LIVE' );
	if ( ! $id ) {
		return 0;
	}

	$b = array();
	foreach ( array(
		'handen-in-de-lucht-in-de-gele-bus',
		'feest-met-blauw-licht-in-de-sfeerbus',
		'verlichte-sfeerbus-van-buiten',
		'gasten-delen-shotjes-uit-in-de-bus',
		'feest-met-armen-omhoog-in-de-bus',
		'discolicht-en-feest-in-de-sfeerbus',
	) as $naam ) {
		$b[ $naam ] = thema_beeld_op_naam( $naam );
	}
	$regels = get_page_by_path( 'huisregels' );
	$regels = $regels ? get_permalink( $regels ) : home_url( '/huisregels/' );
	$bussen = get_page_by_path( 'onze-bussen' );
	$bussen = $bussen ? get_permalink( $bussen ) : home_url( '/onze-bussen/' );
	$kies   = thema_link( 'Kies je stoel', '#stoelen' );

	thema_seo_titel_zetten( 'Bus naar Vrienden van Amstel LIVE 2027: € 25 retour | Lekker Geel Busje', $id );
	thema_seo_omschrijving_zetten( 'Met de gele bus naar Vrienden van Amstel LIVE in Ahoy. Instappen in Oisterwijk, Tilburg of Breda, heen en terug voor € 25 per persoon. Reserveer je stoel in een minuut.', $id );

	$reviews                = thema_blok_reviews();
	$reviews['kop']         = 'Zij zongen al mee *in de bus*';
	$reviews['weergave']    = 'compact';
	$reviews['achtergrond'] = 'geel';

	$faq           = thema_blok_faq();
	$faq['kop']    = 'Vragen over de *bus naar Ahoy*';
	$faq['tekst']  = 'Staat je vraag er niet tussen? Bel of app ons gerust.';
	$faq['vragen'] = thema_faq_rijen( array(
		array( 'Zit mijn concertkaartje bij de prijs?', 'Nee. De € 25 is voor je stoel in de bus, heen en terug. Je kaartje voor Vrienden van Amstel LIVE koop je zelf via de officiële kaartverkoop.' ),
		array( 'Hoe betaal ik mijn stoel?', 'Na je aanmelding krijg je binnen 1 werkdag een Tikkie op je mobiele nummer. Betaal je die binnen 2 dagen, dan staat je stoel vast. Je hebt geen account nodig.' ),
		array( 'Kan ik meteen voor mijn vrienden boeken?', 'Ja, tot 10 stoelen per aanmelding. Je krijgt één Tikkie voor het hele bedrag. De namen van je vrienden mag je ook later doorgeven.' ),
		array( 'Hoe laat vertrekt de bus en hoe laat zijn we terug?', 'De bus vertrekt rond 17:00 in Oisterwijk, 17:20 in Tilburg en 17:55 in Breda. Na afloop rijden we rond 00:15 terug vanaf Ahoy: rond 00:55 ben je in Breda, 01:25 in Tilburg en 01:45 in Oisterwijk. De precieze tijden krijg je een week van tevoren.' ),
		array( 'Waar stap ik precies in?', 'In Oisterwijk, Tilburg en Breda stopt de bus op een vaste opstapplek. De precieze plek krijg je bij je bevestiging, zodat je weet waar je moet zijn.' ),
		array( 'Mag je drinken in de bus?', 'Ja, dat mag. Eigen drank meenemen mag niet: je bestelt je drankjes bij het gastvrouwke.' ),
		array( 'Wat als mijn avond niet doorgaat?', 'Een avond rijdt zeker zodra er genoeg stoelen gereserveerd zijn; dat zie je bij de avond staan. Gaat een rit toch niet door, dan hoor je dat op tijd van ons en krijg je je geld terug.' ),
		array( 'Ik kan toch niet, wat nu?', 'Geef je stoel door aan een vriend of vriendin en laat ons de nieuwe naam weten. Dan zit er niemand alleen en is je stoel niet verloren.' ),
		array( 'Is er een minimumleeftijd?', 'Ja. Vrienden van Amstel LIVE is 18+, en dat geldt ook voor de bus.' ),
		array( 'Wij gaan met een grote groep, kan dat ook?', 'Zeker. Gaan jullie met 35 personen of meer? Dan huur je de hele bus, met je eigen opstapplek en vertrektijd. Onderaan deze pagina vraag je binnen een minuut een prijs aan.' ),
	) );

	$offerte                = thema_blok_offerte();
	$offerte['label']       = 'Binnen 1 werkdag je prijs';
	$offerte['achtergrond'] = 'geel';
	$offerte['kop']         = 'Met je eigen groep? *Huur de hele bus.*';

	update_field( 'field_blokken', array(
		array(
			'acf_fc_layout' => 'hero',
			'kop_1'         => 'Bus naar Vrienden',
			'kop_2'         => 'van Amstel LIVE',
			'plaatsen'      => '',
			'sub_1'         => 'Heen en terug',
			'sub_2'         => 'voor € 25.',
			'knop'          => $kies,
			'knop_tonen'    => 1,
			'toon_score'    => 1,
			'stijl'         => 'foto',
			'fotos'         => array_values( array_filter( array( $b['handen-in-de-lucht-in-de-gele-bus'], $b['verlichte-sfeerbus-van-buiten'], $b['feest-met-blauw-licht-in-de-sfeerbus'] ) ) ),
			'vorm'          => 'auto',
			'hoogte'        => 'compact',
			'anker'         => 'top',
		),
		array(
			'acf_fc_layout'   => 'stoelen',
			'eyebrow'         => 'Stoel reserveren',
			'kop'             => 'Pak je stoel *in de gele bus*',
			'intro'           => 'Kaartjes voor de grootste kroeg van Nederland binnen? Dan regelen wij de rit. Kies je avond, je opstapplek en met hoeveel je gaat. Je bent in een minuut klaar.',
			'punten'          => "Heen en terug voor € 25 per persoon\nInstappen in Oisterwijk, Tilburg of Breda\nBetalen via Tikkie, geen account nodig",
			'evenement'       => 'Vrienden van Amstel LIVE 2027',
			'bestemming'      => 'Rotterdam Ahoy',
			'prijs'           => 25,
			'max_per_boeking' => 10,
			'krap_vanaf'      => 15,
			'sluit_dagen'     => 2,
			'ritten'          => array(
				array( 'datum' => '20270115', 'label' => '', 'stoelen' => 39, 'minimum' => 20, 'status' => 'open' ),
				array( 'datum' => '20270116', 'label' => '', 'stoelen' => 39, 'minimum' => 20, 'status' => 'open' ),
				array( 'datum' => '20270122', 'label' => '', 'stoelen' => 39, 'minimum' => 20, 'status' => 'open' ),
				array( 'datum' => '20270123', 'label' => '', 'stoelen' => 39, 'minimum' => 20, 'status' => 'open' ),
			),
			'opstapplekken'   => array(
				array( 'plaats' => 'Oisterwijk', 'plek' => 'Precieze plek volgt bij je bevestiging', 'heen' => '17:00', 'terug' => '01:45' ),
				array( 'plaats' => 'Tilburg', 'plek' => 'Precieze plek volgt bij je bevestiging', 'heen' => '17:20', 'terug' => '01:25' ),
				array( 'plaats' => 'Breda', 'plek' => 'Precieze plek volgt bij je bevestiging', 'heen' => '17:55', 'terug' => '00:55' ),
			),
			'terugreis'       => 'Na afloop staat de bus rond 00:15 klaar bij Ahoy en rijden we in omgekeerde volgorde terug. Alle tijden zijn richttijden.',
			'akkoord'         => 'Iedereen in mijn groep is 18 jaar of ouder, heeft een eigen concertkaartje en gaat akkoord met de <a href="' . esc_url( $regels ) . '" target="_blank">huisregels</a>.',
			'betaalnoot'      => 'Nu nog niets betalen. Je krijgt binnen 1 werkdag een Tikkie, na betaling staat je stoel vast.',
			'na_aanmelden'    => "Binnen 1 werkdag krijg je een Tikkie op je mobiele nummer.\nBetaal de Tikkie binnen 2 dagen, dan staat je stoel vast.\nEen week voor het concert krijg je de precieze opstapplek en tijd.\nOp de avond zelf: wees 10 minuten voor vertrek bij de bus, met je concertkaartje op je telefoon.",
			'ontvanger'       => '',
			'leeg'            => 'Alle ritten naar Vrienden van Amstel LIVE zijn vol of gesloten. Gaan jullie met een eigen groep? Huur dan de hele bus.',
			'achtergrond'     => 'standaard',
			'anker'           => 'stoelen',
		),
		array(
			'acf_fc_layout' => 'over_ons',
			'eyebrow'       => 'De rit',
			'kop'           => 'De kroeg begint *in de bus.*',
			'tekst_1'       => 'Met de trein ben je een van de duizenden op het perron, met de auto moet er iemand BOB zijn, en parkeren bij Ahoy is een avondje op zich. Met een stoel in de gele bus stap je bij jou in de buurt in, en zetten we je in Rotterdam zo dicht mogelijk bij de ingang af.',
			'tekst_2'       => 'Onderweg zingen we alvast in. Professionele verlichting, lasers, rook en een geluidsinstallatie die er staat, en het gastvrouwke zorgt voor je drankje. Na afloop staat de bus op een afgesproken plek klaar en hoeft niemand nog te rijden.',
			'knop'          => thema_link( 'Bekijk onze bussen', $bussen ),
			'beelden'       => array_values( array_filter( array( $b['gasten-delen-shotjes-uit-in-de-bus'], $b['feest-met-armen-omhoog-in-de-bus'], $b['discolicht-en-feest-in-de-sfeerbus'] ) ) ),
			'achtergrond'   => 'standaard',
			'anker'         => 'de-rit',
		),
		array(
			'acf_fc_layout' => 'route',
			'eyebrow'       => 'Je avond',
			'kop'           => 'Zo ziet je avond *eruit*',
			'intro'         => 'Van instappen tot thuis, zonder dat jij op de klok hoeft te letten.',
			'stappen'       => array(
				array( 'tijd' => '17:00', 'icoon' => 'bus', 'titel' => 'Instappen', 'tekst' => 'Eerst in Oisterwijk, daarna Tilburg (17:20) en Breda (17:55). Zoek de gele bus, die mis je niet.' ),
				array( 'tijd' => 'Onderweg', 'icoon' => 'muziek', 'titel' => 'Inzingen', 'tekst' => 'Licht aan, meezingers op en een drankje van het gastvrouwke. Zo kom je al in de stemming aan.' ),
				array( 'tijd' => '18:45', 'icoon' => 'route', 'titel' => 'Aankomst bij Ahoy', 'tekst' => 'We zetten je zo dicht mogelijk bij de ingang af en vertellen meteen waar de bus straks weer staat.' ),
				array( 'tijd' => 'Concert', 'icoon' => 'feest', 'titel' => 'De grootste kroeg van Nederland', 'tekst' => 'Jij zingt de hele avond mee. Wij wachten op je.' ),
				array( 'tijd' => '00:15', 'icoon' => 'glas', 'titel' => 'Terug naar de bus', 'tekst' => 'Op de afgesproken plek staat de bus klaar. Geen rij voor de trein, geen zoektocht naar de auto.' ),
				array( 'tijd' => 'Daarna', 'icoon' => 'huis', 'titel' => 'Weer thuis', 'tekst' => 'Rond 00:55 in Breda, 01:25 in Tilburg en 01:45 in Oisterwijk. Met de verhalen van de avond.' ),
			),
			'slot'          => '<p>Alle tijden zijn richttijden. De precieze tijden en opstapplek krijg je een week van tevoren.</p>',
			'knop'          => $kies,
			'achtergrond'   => 'standaard',
			'anker'         => 'je-avond',
		),
		array(
			'acf_fc_layout' => 'lopende_band',
			'items'         => "Niet roken in de bus\nGeen eigen drank, wel een volle koeling\nRespect voor de bus en elkaar\nIedereen 18+",
			'knop'          => thema_link( 'Lees onze huisregels', $regels ),
			'variant'       => 'paars',
			'anker'         => '',
		),
		$reviews,
		$faq,
		$offerte,
	), $id );

	$duim = $b['handen-in-de-lucht-in-de-gele-bus'] ?? 0;
	if ( $duim && ! has_post_thumbnail( $id ) ) {
		set_post_thumbnail( $id, $duim );
	}
	clean_post_cache( $id );
	return array( 'pagina' => $id );
}

/**
 * Uitroltaak vval-agenda-link-v1: in de festivalagenda linkt Vrienden van Amstel LIVE naar de stoelverkoop.
 */
function thema_vval_agenda_link() {
	if ( ! function_exists( 'get_field' ) ) {
		return 0;
	}
	$festival = get_page_by_path( 'festival', OBJECT, 'gelegenheid' );
	$pagina   = get_page_by_path( 'vrienden-van-amstel-live' );
	if ( ! $festival || ! $pagina ) {
		return 0;
	}
	$blokken = get_field( 'blokken', $festival->ID );
	if ( ! is_array( $blokken ) ) {
		return 0;
	}
	$gezet = 0;
	foreach ( $blokken as $i => $blok ) {
		if ( 'agenda' !== ( $blok['acf_fc_layout'] ?? '' ) ) {
			continue;
		}
		$items = $blok['items'] ?? array();
		foreach ( (array) $items as $j => $item ) {
			if ( false === stripos( (string) ( $item['titel'] ?? '' ), 'Vrienden van Amstel' ) ) {
				continue;
			}
			update_sub_field( array( 'blokken', $i + 1, 'items', $j + 1, 'link' ), array( 'title' => 'Stoel in de bus voor € 25', 'url' => get_permalink( $pagina ), 'target' => '' ), $festival->ID );
			update_sub_field( array( 'blokken', $i + 1, 'items', $j + 1, 'tekst' ), 'De grootste kroeg van Nederland, met meezingers van begin tot eind. Pak een losse stoel in de bus vanaf Oisterwijk, Tilburg of Breda.', $festival->ID );
			$gezet++;
		}
	}
	clean_post_cache( $festival->ID );
	return $gezet;
}
