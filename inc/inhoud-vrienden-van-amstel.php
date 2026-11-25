<?php
/**
 * Try-out stoelverkoop (november 2026): de pagina /vrienden-van-amstel-live/ waar je een losse stoel koopt
 * in de bus naar De Vrienden van Amstel LIVE 2027 in Rotterdam Ahoy. Opstappen in Oisterwijk, Tilburg en Breda,
 * € 25 per persoon heen en terug, betalen vooraf via Tikkie. Uitroltaken vval-stoelen-v1 en vval-agenda-link-v1.
 *
 * 25 nov 2026 (vval-stoelen-v2, vval-agenda-link-v2): route van Den Bosch naar Breda met een prijs per opstapplek,
 * Den Bosch € 37,50 tot Breda € 25. De ritten die al op de pagina staan blijven staan.
 *
 * 25 nov 2026 (vval-stoelen-v3): alle 14 avonden open, 39 stoelen, doorgang vanaf 15 verkochte stoelen;
 * de Tikkie gaat pas uit als een avond het minimum haalt. Berkel-Enschot (Druiventros, € 30) erbij.
 * Annuleren kan niet, doorgeven wel. Geen groepskorting.
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

	/* Ritten en aantallen die al op de pagina staan (en mogelijk zijn aangepast) blijven staan. */
	$huidig = array();
	foreach ( (array) get_field( 'blokken', $id ) as $blok ) {
		if ( 'stoelen' === ( $blok['acf_fc_layout'] ?? '' ) ) {
			$huidig = $blok;
			break;
		}
	}
	/* Alle 14 concertavonden open, 39 stoelen, rijdt vanaf 15 (besluit 25 nov). Een avond die LGB op vol of gesloten zette, blijft zo. */
	$status = array();
	foreach ( (array) ( $huidig['ritten'] ?? array() ) as $rit ) {
		$status[ preg_replace( '/\D/', '', (string) ( $rit['datum'] ?? '' ) ) ] = (string) ( $rit['status'] ?? 'open' );
	}
	$ritten = array();
	foreach ( array( '20270114', '20270115', '20270116', '20270117', '20270118', '20270120', '20270121', '20270122', '20270123', '20270124', '20270125', '20270127', '20270128', '20270129' ) as $datum ) {
		$ritten[] = array( 'datum' => $datum, 'label' => '', 'stoelen' => 39, 'minimum' => 15, 'status' => $status[ $datum ] ?? 'open' );
	}

	thema_seo_titel_zetten( 'Bus naar Vrienden van Amstel LIVE 2027, vanaf € 25 retour | Lekker Geel Busje', $id );
	thema_seo_omschrijving_zetten( 'Met de gele bus naar Vrienden van Amstel LIVE in Ahoy. Instappen in Den Bosch, Helvoirt, Haaren, Oisterwijk, Berkel-Enschot, Tilburg of Breda, heen en terug vanaf € 25. Reserveer je stoel in een minuut.', $id );

	$reviews                = thema_blok_reviews();
	$reviews['kop']         = 'Zij zongen al mee *in de bus*';
	$reviews['weergave']    = 'compact';
	$reviews['achtergrond'] = 'geel';

	$faq           = thema_blok_faq();
	$faq['kop']    = 'Vragen over de *bus naar Ahoy*';
	$faq['tekst']  = 'Staat je vraag er niet tussen? Bel of app ons gerust.';
	$faq['vragen'] = thema_faq_rijen( array(
		array( 'Wat kost een stoel in de bus?', 'Dat hangt af van waar je instapt: hoe dichter bij Rotterdam, hoe lager de prijs. Den Bosch € 37,50, Helvoirt € 35, Haaren € 32,50, Oisterwijk en Berkel-Enschot € 30, Tilburg € 27,50 en Breda € 25. Altijd per persoon, heen en terug.' ),
		array( 'Zit mijn concertkaartje bij de prijs?', 'Nee. De prijs is voor je stoel in de bus, heen en terug. Je kaartje voor Vrienden van Amstel LIVE koop je zelf via de officiële kaartverkoop.' ),
		array( 'Hoe betaal ik mijn stoel?', 'Bij je aanmelding betaal je nog niets. Zodra er op jouw avond 15 stoelen zijn verkocht, rijdt de bus en krijg je een Tikkie op je mobiele nummer. Betaal je die binnen 2 dagen, dan staat je stoel vast. Je hebt geen account nodig.' ),
		array( 'Kan ik meteen voor mijn vrienden boeken?', 'Ja, tot 10 stoelen per aanmelding. Je krijgt één Tikkie voor het hele bedrag. De namen van je vrienden mag je ook later doorgeven.' ),
		array( 'Welke avonden rijdt de bus?', 'Je kunt kiezen uit alle 14 avonden van Vrienden van Amstel LIVE, van 14 tot en met 29 januari. Een avond rijdt zodra er 15 stoelen zijn verkocht; bij de avond zie je of dat al zo is.' ),
		array( 'Hoe laat vertrekt de bus en hoe laat zijn we terug?', 'De bus vertrekt rond 16:30 bij station Den Bosch en rijdt via Helvoirt (16:45), Haaren (16:52), Oisterwijk (17:02), Berkel-Enschot (17:12) en Tilburg (17:25) naar Breda (17:55). Na afloop rijden we rond 00:15 terug vanaf Ahoy: rond 00:55 ben je in Breda, 01:25 in Tilburg, 01:33 in Berkel-Enschot, 01:43 in Oisterwijk, 01:50 in Haaren, 01:57 in Helvoirt en 02:12 in Den Bosch. De precieze tijden krijg je een week van tevoren.' ),
		array( 'Waar stap ik precies in?', 'Den Bosch bij het station, Helvoirt bij het busstation, Haaren bij het gemeentehuis, Oisterwijk bij het station, Berkel-Enschot bij De Druiventros, Tilburg bij Stappegoor en Breda bij het station aan de Belcrumzijde. Je terugrit eindigt op dezelfde plek.' ),
		array( 'Mag je drinken in de bus?', 'Ja, dat mag. Eigen drank meenemen mag niet: je bestelt je drankjes bij het gastvrouwke.' ),
		array( 'Wat als mijn avond niet doorgaat?', 'Dan heb je niets betaald. Je krijgt pas een Tikkie als er op jouw avond 15 stoelen zijn verkocht. Halen we dat niet, dan hoor je dat op tijd van ons en kun je kiezen voor een andere avond die wel rijdt.' ),
		array( 'Ik kan toch niet, wat nu?', 'Een betaalde stoel kun je niet annuleren, maar wel doorgeven aan een vriend of vriendin. Laat ons de nieuwe naam weten, dan zit er niemand alleen en is je stoel niet verloren.' ),
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
			'sub_2'         => 'vanaf € 25.',
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
			'punten'          => "Heen en terug vanaf € 25 per persoon\nZeven opstapplekken, van Den Bosch tot Breda\nPas betalen als je avond rijdt",
			'evenement'       => 'Vrienden van Amstel LIVE 2027',
			'bestemming'      => 'Rotterdam Ahoy',
			'prijs'           => 25,
			'max_per_boeking' => 10,
			'krap_vanaf'      => 15,
			'sluit_dagen'     => isset( $huidig['sluit_dagen'] ) && '' !== $huidig['sluit_dagen'] ? (int) $huidig['sluit_dagen'] : 2,
			'ritten'          => $ritten,
			'opstapplekken'   => array(
				array( 'plaats' => 'Den Bosch', 'plek' => 'Station \'s-Hertogenbosch', 'heen' => '16:30', 'terug' => '02:12', 'prijs' => 37.5 ),
				array( 'plaats' => 'Helvoirt', 'plek' => 'Busstation', 'heen' => '16:45', 'terug' => '01:57', 'prijs' => 35 ),
				array( 'plaats' => 'Haaren', 'plek' => 'Gemeentehuis', 'heen' => '16:52', 'terug' => '01:50', 'prijs' => 32.5 ),
				array( 'plaats' => 'Oisterwijk', 'plek' => 'Station Oisterwijk', 'heen' => '17:02', 'terug' => '01:43', 'prijs' => 30 ),
				array( 'plaats' => 'Berkel-Enschot', 'plek' => 'De Druiventros', 'heen' => '17:12', 'terug' => '01:33', 'prijs' => 30 ),
				array( 'plaats' => 'Tilburg', 'plek' => 'Stappegoor', 'heen' => '17:25', 'terug' => '01:25', 'prijs' => 27.5 ),
				array( 'plaats' => 'Breda', 'plek' => 'Station Breda, Belcrumzijde', 'heen' => '17:55', 'terug' => '00:55', 'prijs' => 25 ),
			),
			'terugreis'       => 'Hoe dichter bij Rotterdam je instapt, hoe lager de prijs. Na afloop staat de bus rond 00:15 klaar bij Ahoy en rijden we de route in omgekeerde volgorde terug, naar de plek waar je bent ingestapt. Alle tijden zijn richttijden.',
			'akkoord'         => 'Iedereen in mijn groep is 18 jaar of ouder, heeft een eigen concertkaartje en gaat akkoord met de <a href="' . esc_url( $regels ) . '" target="_blank">huisregels</a>.',
			'betaalnoot'      => 'Nu nog niets betalen. Zodra er op jouw avond 15 stoelen zijn verkocht, rijdt de bus en krijg je een Tikkie.',
			'na_aanmelden'    => "Zodra er op jouw avond 15 stoelen zijn verkocht, rijdt de bus en krijg je een Tikkie op je mobiele nummer.\nBetaal de Tikkie binnen 2 dagen, dan staat je stoel vast. Annuleren kan daarna niet, doorgeven aan iemand anders wel.\nEen week voor het concert krijg je de precieze opstapplek en tijd.\nOp de avond zelf: wees 10 minuten voor vertrek bij de bus, met je concertkaartje op je telefoon.",
			'ontvanger'       => '',
			'leeg'            => 'Alle ritten naar Vrienden van Amstel LIVE zijn vol of gesloten. Gaan jullie met een eigen groep? Huur dan de hele bus.',
			'achtergrond'     => 'standaard',
			'anker'           => 'stoelen',
		),
		array(
			'acf_fc_layout' => 'over_ons',
			'eyebrow'       => 'De rit',
			'kop'           => 'De kroeg begint *in de bus.*',
			'tekst_1'       => 'Met de trein ben je een van de duizenden op het perron, met de auto moet er iemand Bob zijn, en parkeren bij Ahoy is een avondje op zich. Met een stoel in de gele bus stap je bij jou in de buurt in, en zetten we je in Rotterdam zo dicht mogelijk bij de ingang af.',
			'tekst_2'       => 'Onderweg zingen we alvast in. Professionele verlichting, lasers, rook, microfoons en een geluidsinstallatie die er staat, en het gastvrouwke zorgt voor je drankje. Na afloop staat de bus op een afgesproken plek klaar en hoeft niemand nog te rijden.',
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
				array( 'tijd' => '16:30', 'icoon' => 'bus', 'titel' => 'Instappen', 'tekst' => 'De bus start bij station Den Bosch en rijdt via Helvoirt, Haaren, Oisterwijk, Berkel-Enschot en Tilburg naar Breda (17:55). Zoek de gele bus, die mis je niet.' ),
				array( 'tijd' => 'Onderweg', 'icoon' => 'muziek', 'titel' => 'Inzingen', 'tekst' => 'Licht aan, meezingers op en een drankje van het gastvrouwke. Zo kom je al in de stemming aan.' ),
				array( 'tijd' => '18:45', 'icoon' => 'route', 'titel' => 'Aankomst bij Ahoy', 'tekst' => 'We zetten je zo dicht mogelijk bij de ingang af en vertellen meteen waar de bus straks weer staat.' ),
				array( 'tijd' => 'Concert', 'icoon' => 'feest', 'titel' => 'De grootste kroeg van Nederland', 'tekst' => 'Jij zingt de hele avond mee. Wij wachten op je.' ),
				array( 'tijd' => '00:15', 'icoon' => 'glas', 'titel' => 'Terug naar de bus', 'tekst' => 'Op de afgesproken plek staat de bus klaar. Geen rij voor de trein, geen zoektocht naar de auto.' ),
				array( 'tijd' => 'Daarna', 'icoon' => 'huis', 'titel' => 'Weer thuis', 'tekst' => 'Rond 00:55 in Breda, 01:25 in Tilburg, 01:33 in Berkel-Enschot, 01:43 in Oisterwijk, 01:50 in Haaren, 01:57 in Helvoirt en 02:12 in Den Bosch. Met de verhalen van de avond.' ),
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
			update_sub_field( array( 'blokken', $i + 1, 'items', $j + 1, 'link' ), array( 'title' => 'Stoel in de bus vanaf € 25', 'url' => get_permalink( $pagina ), 'target' => '' ), $festival->ID );
			update_sub_field( array( 'blokken', $i + 1, 'items', $j + 1, 'tekst' ), 'De grootste kroeg van Nederland, met meezingers van begin tot eind. Pak een losse stoel in de bus vanaf Den Bosch, Helvoirt, Haaren, Oisterwijk, Berkel-Enschot, Tilburg of Breda.', $festival->ID );
			$gezet++;
		}
	}
	clean_post_cache( $festival->ID );
	return $gezet;
}
