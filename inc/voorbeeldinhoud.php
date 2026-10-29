<?php
/**
 * Inhoud uit het ontwerp: homepage, gelegenheden, contact, juridisch en menu's.
 * Wordt alleen geplaatst via de knop in Thema > Gereedschap.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Beeld uit /assets/voorbeeld in de mediabibliotheek zetten (eenmalig).
 */
function thema_voorbeeld_beeld( $bestand, $alt = '' ) {
	$bekend = get_option( 'thema_ontwerp_beelden', array() );
	if ( ! empty( $bekend[ $bestand ] ) && get_post( $bekend[ $bestand ] ) ) {
		return (int) $bekend[ $bestand ];
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$bron = THEMA_PAD . '/assets/voorbeeld/' . $bestand;
	if ( ! file_exists( $bron ) ) {
		return 0;
	}
	$tijdelijk = wp_tempnam( $bestand );
	copy( $bron, $tijdelijk );

	$titel = ucfirst( str_replace( array( '-', '.jpg', '.png' ), array( ' ', '', '' ), $bestand ) );
	$id    = media_handle_sideload( array( 'name' => $bestand, 'tmp_name' => $tijdelijk ), 0, $titel );
	if ( is_wp_error( $id ) ) {
		@unlink( $tijdelijk ); // phpcs:ignore
		return 0;
	}
	update_post_meta( $id, '_wp_attachment_image_alt', $alt ?: $titel );

	$bekend[ $bestand ] = $id;
	update_option( 'thema_ontwerp_beelden', $bekend, false );
	return (int) $id;
}

function thema_link( $titel, $url ) {
	return array( 'title' => $titel, 'url' => $url, 'target' => '' );
}

/**
 * Pagina of bericht ophalen of aanmaken.
 */
function thema_voorbeeld_post( $type, $slug, $titel, $extra = array() ) {
	$bestaand = get_page_by_path( $slug, OBJECT, $type );
	if ( $bestaand ) {
		if ( 'publish' !== $bestaand->post_status ) {
			wp_update_post( array( 'ID' => $bestaand->ID, 'post_status' => 'publish' ) );
		}
		return (int) $bestaand->ID;
	}
	$id = wp_insert_post( array_merge( array(
		'post_type'   => $type,
		'post_name'   => $slug,
		'post_title'  => $titel,
		'post_status' => 'publish',
	), $extra ) );
	return is_wp_error( $id ) ? 0 : (int) $id;
}

/**
 * Teksten uit het ontwerp, op één plek.
 */
function thema_ontwerp_teksten() {
	return array(
		'voordelen'    => array(
			array( 'Voldoende zitplaatsen', 'stoel', 'zitplaatsen.jpg', 'Elke bus in het wagenpark heeft er minstens 39. Groot genoeg voor een compleet gezelschap, klein genoeg om gezellig te blijven.' ),
			array( 'Tot 88 personen', 'groep', 'groep-in-bus.jpg', 'Onze verlengde bus telt zelfs 53 zitplaatsen plus 35 staanplaatsen. Daarmee gaat een heel bedrijf of een hele vriendengroep in een keer mee.' ),
			array( 'Geluid en sfeerverlichting', 'speaker', 'feest-disco.jpg', 'Een top geluidssysteem, feestverlichting en een koeling met koude drank aan boord. DJ Booth nodig? Regelen we!' ),
			array( 'Chauffeur en gastvrouwke', 'bus', 'gastheer.jpg', 'Bij elke rit rijdt er iemand mee voor de bediening, de veiligheid en je vragen.' ),
			array( 'Wij zijn de BOB', 'feest', 'dansen.jpg', 'Iedereen mag meedoen, niemand hoeft te rijden. Heen en terug, op een vooraf afgesproken tijdstip.' ),
			array( 'Veilig en streng gekeurd', 'schild', 'bus-plein.jpg', 'Omgebouwde OV-bussen die altijd goed onderhouden zijn en streng gekeurd worden.' ),
		),
		'reviews'      => array(
			array( 'Noud Tromp', 'Al meerdere keren met veel plezier met Lekkergeelbusje naar diverse feesten en evenementen. Inmiddels onze vaste vervoerder!' ),
			array( 'Axel', 'Vanaf het eerste moment zat de sfeer er goed in. Het was een van de beste ervaringen van mijn leven.' ),
			array( 'Jet Kanters', 'Bussen zijn super netjes en de mogelijkheid om in de bus drankjes te kopen wordt zeer gewaardeerd!' ),
			array( 'Ivo Scheer', 'Mooi schone bus, gezellige muziek, drankjes aan boord en de chauffeur was super vriendelijk en hulpvaardig.' ),
			array( 'Martijn & Evelien', 'Wij hadden een vrijgezellenfeest dus de hele dag de bus mee. Zeker aan te raden.' ),
			array( 'Jaap Sauer', 'Super toffe chauffeur en gastvrouw. Echt super relaxte reis gehad!' ),
			array( 'Marc van Amelsvoort', 'Keurige bus met goede geluidsinstallatie en gezellige crew. Top geregeld.' ),
		),
		'vragen'       => array(
			array( 'Hoe ver van tevoren moet ik boeken?', 'Hoe eerder, hoe beter. Vrijdagen, zaterdagen en het festivalseizoen zijn snel vol.' ),
			array( 'Mag je drinken in de bus?', 'Ja. Aan boord is een koeling met koude drank, onze gastheer/vrouw zorgt voor de drankjes.' ),
			array( 'Rijden jullie ook buiten Brabant?', 'Zeker. Vanuit Oisterwijk rijden we heel Brabant en ver daarbuiten, tot een voetbalwedstrijd in Oostenrijk aan toe.' ),
			array( 'Wat als we later terug willen?', 'We rijden terug op een vooraf afgesproken tijdstip. Wil je later? Overleg het met de chauffeur, dan kijken we wat kan.' ),
			array( 'Kan ik eigen muziek meenemen?', 'Ja, sluit je eigen muziek aan op het geluidssysteem. DJ Booth nodig? Regelen we!' ),
			array( 'Wat gebeurt er bij schade?', 'Dat staat duidelijk in onze huisregels en algemene voorwaarden.' ),
		),
		'gelegenheden' => array(
			array( 'vrijgezellenfeest', 'Vrijgezellenfeest', 'vrienden-proosten.jpg', 'De laatste rit als vrijgezel, met de hele crew.' ),
			array( 'bedrijfsuitje', 'Bedrijfsuitje', 'bus-plein.jpg', 'Samen heen en terug, niemand hoeft te rijden.' ),
			array( 'bruiloft', 'Bruiloft', 'proosten.jpg', 'Van ceremonie naar feest, in stijl.' ),
			array( 'trip-met-vrienden', 'Trip met vrienden', 'selfie.jpg', 'Een dagje weg begint al in de bus.' ),
			array( 'festival', 'Festival', 'feest-blauw.jpg', 'Voor de deur opgehaald, voor de deur terug.' ),
		),
	);
}

/* --------------------------------------------------------------------------
 * Blokken die op meer pagina's terugkomen.
 * ----------------------------------------------------------------------- */

function thema_blok_offerte() {
	return array(
		'acf_fc_layout' => 'offerte',
		'label'         => 'Duurt nog geen minuut',
		'kop'           => 'Waar gaat jullie *feestje* naartoe?',
		'zin_1'         => 'Op',
		'zin_2'         => 'gaan we met',
		'zin_3'         => 'personen van',
		'zin_4'         => 'naar',
		'ph_van'        => 'jullie plaats',
		'ph_naar'       => 'de bestemming',
		'ph_personen'   => 'aantal',
		'zekerheden'    => "Vrijblijvend\nBinnen 2 werkdagen een prijs\nAll-in, geen verrassingen",
		'knop_open'     => 'Vraag direct je prijs aan',
		'knop_verder'   => 'Ga verder',
		'open'          => 0,
		'anker'         => 'offerte',
	);
}

function thema_blok_faq() {
	$t = thema_ontwerp_teksten();
	return array(
		'acf_fc_layout' => 'faq',
		'kop'           => 'Vragen voordat je *instapt*',
		'knop'          => '',
		'tekst'         => 'Staat je vraag er niet tussen? Bel ons gerust, we denken graag met je mee.',
		'vragen'        => array_map( fn( $v ) => array( 'vraag' => $v[0], 'antwoord' => $v[1] ), $t['vragen'] ),
		'schema'        => 1,
		'anker'         => 'faq',
	);
}

function thema_blok_slot() {
	return array(
		'acf_fc_layout' => 'slot_cta',
		'kop'           => 'Ook een lekker',
		'pil'           => 'fijn feestje?',
		'tekst'         => 'Vertel kort wat je van plan bent, in 2 werkdagen heb jij een prijs.',
		'knop'          => thema_link( 'Offerte aanvragen', home_url( '/#offerte' ) ),
		'anker'         => 'contact',
	);
}

function thema_blok_gelegenheden( $weergave = 'schuiven' ) {
	return array(
		'acf_fc_layout' => 'gelegenheden',
		'kop'           => "Wat is jouw reden\n*voor een feestje?*",
		'weergave'      => $weergave,
		'keuze'         => array(),
		'anker'         => 'gelegenheden',
	);
}

function thema_blok_reviews() {
	$t = thema_ontwerp_teksten();
	return array(
		'acf_fc_layout' => 'reviews',
		'kop'           => 'Deze *sfeergangers* gingen je voor',
		'score'         => '5.0',
		'score_label'   => 'Google reviews',
		'reviews'       => array_map( fn( $r ) => array( 'naam' => $r[0], 'tekst' => $r[1] ), $t['reviews'] ),
		'anker'         => 'reviews',
	);
}

/* --------------------------------------------------------------------------
 * Gravity Forms: offerteformulier dat de zin uit het offerteblok overneemt.
 * ----------------------------------------------------------------------- */

function thema_voorbeeld_formulier() {
	if ( ! class_exists( 'GFAPI' ) ) {
		return 0;
	}
	foreach ( GFAPI::get_forms() as $form ) {
		if ( 'Offerte aanvragen' === $form['title'] ) {
			return (int) $form['id'];
		}
	}
	$i      = 0;
	$veld   = function ( $type, $label, $extra = array() ) use ( &$i ) {
		$i++;
		return array_merge( array( 'id' => $i, 'type' => $type, 'label' => $label ), $extra );
	};
	$form   = array(
		'title'                => 'Offerte aanvragen',
		'description'          => '',
		'labelPlacement'       => 'top_label',
		'button'               => array( 'type' => 'text', 'text' => 'Verstuur aanvraag' ),
		'fields'               => array(
			$veld( 'date', 'Datum', array( 'allowsPrepopulate' => true, 'inputName' => 'datum', 'dateType' => 'datepicker', 'dateFormat' => 'ymd_dash', 'layoutGridColumnSpan' => 6, 'isRequired' => true ) ),
			$veld( 'number', 'Aantal personen', array( 'allowsPrepopulate' => true, 'inputName' => 'personen', 'rangeMin' => 1, 'layoutGridColumnSpan' => 6, 'isRequired' => true ) ),
			$veld( 'text', 'Ophalen in', array( 'allowsPrepopulate' => true, 'inputName' => 'van', 'layoutGridColumnSpan' => 6, 'isRequired' => true ) ),
			$veld( 'text', 'Bestemming', array( 'allowsPrepopulate' => true, 'inputName' => 'naar', 'layoutGridColumnSpan' => 6 ) ),
			$veld( 'text', 'Gelegenheid', array( 'allowsPrepopulate' => true, 'inputName' => 'gelegenheid' ) ),
			$veld( 'text', 'Naam', array( 'layoutGridColumnSpan' => 6, 'isRequired' => true ) ),
			$veld( 'phone', 'Telefoon', array( 'phoneFormat' => 'international', 'layoutGridColumnSpan' => 6 ) ),
			$veld( 'email', 'E-mail', array( 'isRequired' => true ) ),
			$veld( 'textarea', 'Vertel kort wat jullie van plan zijn' ),
		),
		'confirmations'        => array(
			'bevestiging' => array(
				'id'        => 'bevestiging',
				'name'      => 'Standaard',
				'isDefault' => true,
				'type'      => 'message',
				'message'   => 'Ontvangen! Binnen 2 werkdagen heb je een vaste all-in prijs in je mail.',
			),
		),
		'notifications'        => array(
			'melding' => array(
				'id'      => 'melding',
				'name'    => 'Melding aan Lekker Geel Busje',
				'to'      => '{admin_email}',
				'subject' => 'Nieuwe offerteaanvraag van {Naam:6}',
				'message' => '{all_fields}',
				'event'   => 'form_submission',
				'isActive' => true,
			),
		),
	);
	$id = GFAPI::add_form( $form );
	return is_wp_error( $id ) ? 0 : (int) $id;
}

/* --------------------------------------------------------------------------
 * Plaatsen.
 * ----------------------------------------------------------------------- */

function thema_plaats_voorbeeldinhoud() {
	if ( ! function_exists( 'update_field' ) ) {
		return false;
	}
	if ( ! get_option( 'permalink_structure' ) ) {
		update_option( 'permalink_structure', '/%postname%/' );
	}

	$alts = array(
		'bus-avond.jpg'         => 'De gele bus in de avond',
		'bus-plein.jpg'         => 'De gele bus op een plein',
		'dansen.jpg'            => 'Dansen in de bus',
		'feest-blauw.jpg'       => 'Feest in blauw licht',
		'feest-disco.jpg'       => 'Discolicht in de bus',
		'gastheer.jpg'          => 'Gastheer deelt drankjes uit',
		'groep-in-bus.jpg'      => 'Groep viert feest in de bus',
		'proosten.jpg'          => 'Lachen en proosten in de bus',
		'selfie.jpg'            => 'Selfie met vrienden in de bus',
		'slingers.jpg'          => 'Feest met slingers',
		'uitstappen.jpg'        => 'Dansend de gele bus uit',
		'vrienden-proosten.jpg' => 'Vrienden proosten in de bus',
		'zitplaatsen.jpg'       => 'Zitplaatsen in de bus',
	);
	$b = array();
	foreach ( $alts as $bestand => $alt ) {
		$b[ $bestand ] = thema_voorbeeld_beeld( $bestand, $alt );
	}
	$t = thema_ontwerp_teksten();

	/* Gelegenheden. */
	$gel_ids = array();
	foreach ( $t['gelegenheden'] as $i => $g ) {
		$id = thema_voorbeeld_post( 'gelegenheid', $g[0], $g[1], array( 'menu_order' => $i ) );
		if ( ! $id ) {
			continue;
		}
		wp_update_post( array( 'ID' => $id, 'menu_order' => $i, 'post_title' => $g[1] ) );
		set_post_thumbnail( $id, $b[ $g[2] ] );
		update_field( 'field_gel_ondertitel', $g[3], $id );
		update_field( 'field_gel_tekst', '<p>[Tekst voor de pagina ' . esc_html( $g[1] ) . ': wat maakt de rit bijzonder, wat regelen we, waar rijden we vaak heen.]</p>', $id );
		update_field( 'field_gel_voordelen', array(
			array( 'titel' => 'Opgehaald en thuisgebracht', 'tekst' => 'Op een vooraf afgesproken tijd en plek, heen en terug.' ),
			array( 'titel' => 'Gastvrouwke aan boord', 'tekst' => 'Voor de drankjes, de veiligheid en al je vragen.' ),
			array( 'titel' => 'Eén vaste all-in prijs', 'tekst' => 'Chauffeur, brandstof en btw zitten er altijd bij.' ),
		), $id );
		update_field( 'field_blokken', array( thema_blok_offerte(), thema_blok_faq() ), $id );
		$gel_ids[] = $id;
	}

	/* Homepage. */
	$home_id = (int) get_option( 'page_on_front' );
	if ( ! $home_id || ! get_post( $home_id ) ) {
		$home_id = thema_voorbeeld_post( 'page', 'home', 'Home' );
	}
	$voordelen = array_map( fn( $v ) => array( 'titel' => $v[0], 'icoon' => $v[1], 'beeld' => $b[ $v[2] ], 'tekst' => $v[3] ), $t['voordelen'] );
	update_field( 'field_blokken', array(
		array(
			'acf_fc_layout' => 'hero',
			'kop_1'         => 'Partybus',
			'kop_2'         => 'huren in',
			'plaatsen'      => "Brabant\nTilburg\nBreda\nDen Bosch\nEindhoven\nOisterwijk\nOss\nHelmond\nLimburg\nZeeland\nUtrecht",
			'sub_1'         => 'Jouw feest begint',
			'sub_2'         => 'in de bus!',
			'knop'          => thema_link( 'Plan je feestje', home_url( '/#offerte' ) ),
			'toon_score'    => 1,
			'stijl'         => 'standaard',
			'fotos'         => array( $b['feest-disco.jpg'], $b['selfie.jpg'], $b['bus-avond.jpg'] ),
			'beeld'         => $b['groep-in-bus.jpg'],
			'extra'         => array( $b['dansen.jpg'], $b['gastheer.jpg'], $b['proosten.jpg'], $b['slingers.jpg'] ),
			'bento_klein'   => 'Chauffeur en gastvrouwke',
			'bento_groot'   => 'Tot 88 personen',
			'bento_link'    => thema_link( 'Onze bussen', home_url( '/#bussen' ) ),
			'anker'         => 'top',
		),
		thema_blok_offerte(),
		array(
			'acf_fc_layout' => 'lopende_band',
			'items'         => "Offerte in 2 werkdagen\nVolle bak gezelligheid\nAll-in, geen verrassingen\n5,0 op Google",
			'anker'         => '',
		),
		array(
			'acf_fc_layout' => 'over_ons',
			'eyebrow'       => 'Over ons',
			'kop'           => 'Busje in, *sfeertje aan!*',
			'tekst_1'       => 'Bij Lekker Geel Busje begint je feestje zodra je instapt. Daar gaan we voor. Samen op weg naar je leuke event. Of nog even doorfeesten op de weg terug. De volumeknop open, feestverlichting aan en we gaan.',
			'tekst_2'       => 'Onze gele bussen vallen overal op. Maar wat nog veel opvallender is, zijn onze collega’s aan boord. De chauffeur die weet wanneer het gas erop moet en wanneer juist even niet. En een gastheer/vrouw die zorgt voor de drankjes, jullie veiligheid en alle vragen beantwoordt.',
			'knop'          => thema_link( 'Meer over onze bussen', home_url( '/#bussen' ) ),
			'beelden'       => array( $b['uitstappen.jpg'], $b['proosten.jpg'], $b['slingers.jpg'] ),
			'anker'         => 'overons',
		),
		array(
			'acf_fc_layout' => 'brede_foto',
			'beeld'         => $b['groep-in-bus.jpg'],
			'hoogte'        => 'normaal',
			'focus'         => 'center 40%',
			'anker'         => '',
		),
		array(
			'acf_fc_layout' => 'aan_boord',
			'eyebrow'       => 'Aan boord van onze bussen',
			'kop'           => 'Wat kun je verwachten van onze bussen?',
			'tekst'         => 'Boek je een Lekker Geel Busje, dan ben je gegarandeerd van veilig vervoer. We rijden met oude OV-bussen die altijd goed onderhouden zijn geweest en streng gekeurd worden. Ze zijn van feest voorzien: een top geluidssysteem, feestverlichting en een koeling met koude drank.',
			'anker'         => 'bussen',
		),
		array(
			'acf_fc_layout' => 'voordelen',
			'items'         => $voordelen,
			'knop'          => thema_link( 'Vraag een offerte aan', home_url( '/#offerte' ) ),
			'anker'         => '',
		),
		thema_blok_gelegenheden(),
		array(
			'acf_fc_layout' => 'werkgebied',
			'kop'           => 'We komen *overal!*',
			'legenda'       => 'Thuisbasis Oisterwijk',
			'tekst'         => 'Vanuit Oisterwijk rijden we heel Brabant en ver daarbuiten. Soms een ritje om de hoek. Soms naar een voetbalwedstrijd in Oostenrijk. Wij draaien onze bus er niet voor om!',
			'steden'        => "Oisterwijk | Thuis\nUtrecht | 55 min\nRotterdam | 1 uur\nAmsterdam | 1 u 15\nMaastricht | 1 u 15\nGroningen | 2 u 45\nAntwerpen | 1 uur\nBrussel | 1 u 30\nDüsseldorf | 1 u 45\nKeulen | 2 uur\nOostenrijk | Ja, ook!",
			'anker'         => 'locaties',
		),
		array(
			'acf_fc_layout' => 'brede_foto',
			'beeld'         => $b['vrienden-proosten.jpg'],
			'hoogte'        => 'hoog',
			'focus'         => 'center 45%',
			'anker'         => '',
		),
		array(
			'acf_fc_layout'    => 'prijs',
			'kop'              => 'Waar hangt de prijs *vanaf?*',
			'knop'             => thema_link( 'Vraag een prijs aan', home_url( '/#offerte' ) ),
			'tekst'            => 'Geen tarievenlijst, omdat elke rit anders is. Wel volledige openheid over wat meetelt, wat er altijd bij zit en hoe snel je het weet.',
			'factoren_label'   => 'Je prijs hangt af van',
			'inbegrepen_label' => 'Inbegrepen',
			'factoren'         => "Grootte van groep\nAfstand en rijtijd\nDag en seizoen\nWachttijd en extra’s",
			'inbegrepen'       => "Chauffeur\nGastheer/vrouw\nBrandstof\nBtw",
			'uitkomst'         => 'Eén vaste all-in prijs, binnen 2 werkdagen',
			'stijl'            => 'standaard',
			'anker'            => 'prijzen',
		),
		thema_blok_reviews(),
		array(
			'acf_fc_layout' => 'video',
			'poster'        => $b['feest-blauw.jpg'],
			'bestand'       => '',
			'embed'         => '',
			'anker'         => 'video',
		),
		thema_blok_faq(),
		thema_blok_slot(),
	), $home_id );
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $home_id );

	/* Overzicht van de gelegenheden. */
	$hub_id = thema_voorbeeld_post( 'page', 'gelegenheden', 'Gelegenheden', array( 'menu_order' => 2 ) );
	$gel    = thema_blok_gelegenheden( 'raster' );
	$gel['kop'] = "Wat is jouw reden\n*voor een feestje?*";
	update_field( 'field_blokken', array(
		array(
			'acf_fc_layout' => 'hero',
			'kop_1'         => 'Partybus',
			'kop_2'         => 'voor je',
			'plaatsen'      => "vrijgezellenfeest\nbedrijfsuitje\nbruiloft\ntrip met vrienden\nfestival",
			'sub_1'         => 'Jouw feest begint',
			'sub_2'         => 'in de bus!',
			'knop'          => thema_link( 'Plan je feestje', home_url( '/#offerte' ) ),
			'toon_score'    => 1,
			'stijl'         => 'klassiek',
			'fotos'         => array(),
			'beeld'         => $b['vrienden-proosten.jpg'],
			'extra'         => array(),
			'anker'         => 'top',
		),
		$gel,
		thema_blok_reviews(),
		thema_blok_faq(),
		thema_blok_slot(),
	), $hub_id );

	/* Contact met het offerteformulier. */
	$form_id    = thema_voorbeeld_formulier();
	$contact_id = thema_voorbeeld_post( 'page', 'contact', 'Contact', array( 'menu_order' => 9 ) );
	update_field( 'field_blokken', array(
		array(
			'acf_fc_layout' => 'formulier',
			'kop'           => 'Vraag je *prijs* aan',
			'intro'         => 'Vertel ons wanneer, waarheen en met hoeveel je bent. Binnen 2 werkdagen heb je een vaste all-in prijs.',
			'formulier'     => $form_id ? (string) $form_id : '',
			'anker'         => 'formulier',
		),
		thema_blok_faq(),
	), $contact_id );

	/* Juridische pagina's. */
	$juridisch = array();
	foreach ( array( 'privacyverklaring' => 'Privacyverklaring', 'algemene-voorwaarden' => 'Algemene voorwaarden', 'huisregels' => 'Huisregels' ) as $slug => $titel ) {
		$id = thema_voorbeeld_post( 'page', $slug, $titel, array( 'menu_order' => 20 ) );
		if ( $id ) {
			update_field( 'field_blokken', array(
				array(
					'acf_fc_layout' => 'tekst',
					'eyebrow'       => 'Lekker Geel Busje',
					'kop'           => $titel,
					'tekst'         => '<p>[Tekst voor ' . esc_html( mb_strtolower( $titel ) ) . ' volgt.]</p>',
					'breedte'       => 'smal',
					'anker'         => '',
				),
			), $id );
			$juridisch[ $slug ] = $id;
		}
	}
	if ( ! empty( $juridisch['privacyverklaring'] ) ) {
		update_option( 'wp_page_for_privacy_policy', $juridisch['privacyverklaring'] );
	}

	thema_voorbeeld_menus( $hub_id, $contact_id, $gel_ids, $juridisch );
	return true;
}

/**
 * Concept van de pagina Onze bussen (/bussen), uit bestaande blokken.
 */
function thema_plaats_bussenpagina() {
	if ( ! function_exists( 'update_field' ) ) {
		return 0;
	}
	$bestaand = get_page_by_path( 'bussen', OBJECT, 'page' );
	$id       = $bestaand ? (int) $bestaand->ID : (int) wp_insert_post( array(
		'post_type'   => 'page',
		'post_name'   => 'bussen',
		'post_title'  => 'Onze bussen',
		'post_status' => 'draft',
		'menu_order'  => 3,
	) );
	if ( ! $id ) {
		return 0;
	}
	$bestanden = array( 'bus-plein.jpg', 'bus-avond.jpg', 'feest-disco.jpg', 'gastheer.jpg', 'dansen.jpg', 'proosten.jpg', 'zitplaatsen.jpg', 'groep-in-bus.jpg', 'selfie.jpg', 'feest-blauw.jpg', 'uitstappen.jpg', 'slingers.jpg', 'vrienden-proosten.jpg' );
	$b         = array();
	foreach ( $bestanden as $bestand ) {
		$b[ $bestand ] = thema_voorbeeld_beeld( $bestand );
	}
	$t         = thema_ontwerp_teksten();
	$voordelen = array_map( fn( $v ) => array( 'titel' => $v[0], 'icoon' => $v[1], 'beeld' => $b[ $v[2] ], 'tekst' => $v[3] ), $t['voordelen'] );
	$vragen    = array(
		array( 'Hoeveel mensen kunnen er mee?', 'Onze bussen nemen groepen tot 88 personen mee. Vertel ons hoeveel jullie zijn, dan zorgen wij voor de juiste bus.' ),
		array( 'Zijn licht en geluid inbegrepen?', 'Ja. Het geluidssysteem, de feestverlichting, de rookmachine en de lasers zitten er altijd bij. Daar betaal je niets extra voor.' ),
		array( 'Hoe zit het met de veiligheid?', 'Er rijdt altijd een gastheer of gastvrouw mee. Die zorgt voor de drankjes, de sfeer en de veiligheid in de bus, zodat de chauffeur zijn aandacht volledig bij de weg kan houden. De bussen zelf zijn oude OV-bussen die goed onderhouden en streng gekeurd worden.' ),
		array( 'Wie rijdt er met ons mee?', 'Een chauffeur en een gastvrouwke. De chauffeur stelt zich vooraf aan je voor, zodat je weet met wie je op pad gaat.' ),
		array( 'Mogen we eigen drank meenemen?', 'Nee. Aan boord is een koeling met koude drank en het gastvrouwke zorgt voor de drankjes.' ),
		array( 'Mag er gerookt worden in de bus?', 'Nee, in de bus wordt niet gerookt. Zo blijft het voor iedereen gezellig.' ),
		array( 'Kunnen we onze eigen muziek draaien?', 'Ja, sluit je eigen muziek aan op het geluidssysteem. DJ Booth nodig? Regelen we!' ),
	);
	$faq           = thema_blok_faq();
	$faq['kop']    = 'Vragen over *de bussen*';
	$faq['vragen'] = array_map( fn( $v ) => array( 'vraag' => $v[0], 'antwoord' => $v[1] ), $vragen );

	update_field( 'field_blokken', array(
		array(
			'acf_fc_layout' => 'hero',
			'kop_1'         => 'Onze',
			'kop_2'         => 'partybussen',
			'plaatsen'      => '',
			'sub_1'         => 'Echte OV-bussen,',
			'sub_2'         => 'vol feest',
			'knop'          => thema_link( 'Plan je feestje', home_url( '/#offerte' ) ),
			'toon_score'    => 1,
			'stijl'         => 'klassiek',
			'beeld'         => $b['bus-plein.jpg'],
			'anker'         => 'top',
		),
		array(
			'acf_fc_layout' => 'aan_boord',
			'eyebrow'       => 'Het wagenpark',
			'kop'           => 'Geel van buiten, *feest van binnen*',
			'tekst'         => 'Al onze bussen zijn oude OV-bussen die altijd goed onderhouden zijn geweest en streng gekeurd worden. We bouwden ze om tot sfeerbus, met een top geluidssysteem, feestverlichting en een koeling met koude drank. Plek voor groepen tot 88 personen.',
			'achtergrond'   => 'standaard',
			'anker'         => 'wagenpark',
		),
		array(
			'acf_fc_layout' => 'voordelen',
			'items'         => $voordelen,
			'knop'          => thema_link( 'Vraag een offerte aan', home_url( '/#offerte' ) ),
			'achtergrond'   => 'standaard',
			'anker'         => '',
		),
		array(
			'acf_fc_layout' => 'lopende_band',
			'items'         => "Streng gekeurd\nChauffeur en gastvrouwke\nKoude drank aan boord\nTot 88 personen",
			'variant'       => 'subtiel',
			'anker'         => '',
		),
		array(
			'acf_fc_layout' => 'over_ons',
			'eyebrow'       => 'Aan boord',
			'kop'           => 'Wij rijden, *jullie feesten*',
			'tekst_1'       => 'In elke bus rijden twee collega’s mee. De chauffeur die weet wanneer het gas erop moet en wanneer juist even niet. En een gastvrouwke dat zorgt voor de drankjes, de veiligheid en al je vragen. De chauffeur stelt zich vooraf aan je voor, dus je weet met wie je op pad gaat.',
			'tekst_2'       => 'Een paar afspraken houden het voor iedereen leuk: we roken niet in de bus, nemen geen eigen drank mee en hebben respect voor de bus en voor elkaar.',
			'knop'          => thema_link( 'Lees de huisregels', home_url( '/huisregels/' ) ),
			'beelden'       => array( $b['gastheer.jpg'], $b['dansen.jpg'], $b['proosten.jpg'] ),
			'achtergrond'   => 'standaard',
			'anker'         => 'aan-boord',
		),
		array(
			'acf_fc_layout' => 'brede_foto',
			'beeld'         => $b['feest-disco.jpg'],
			'hoogte'        => 'normaal',
			'focus'         => 'center 45%',
			'anker'         => '',
		),
		array(
			'acf_fc_layout'    => 'prijs',
			'kop'              => 'Wat zit er *altijd bij?*',
			'knop'             => thema_link( 'Vraag een prijs aan', home_url( '/#offerte' ) ),
			'tekst'            => 'Welke bus je ook boekt, je krijgt een vaste all-in prijs. Wat meetelt is je groep, de afstand, de dag en de wachttijd.',
			'factoren_label'   => 'Je prijs hangt af van',
			'inbegrepen_label' => 'Inbegrepen',
			'factoren'         => "Grootte van groep\nAfstand en rijtijd\nDag en seizoen\nWachttijd en extra’s",
			'inbegrepen'       => "Chauffeur\nGastheer/vrouw\nLicht en geluid\nBrandstof\nBtw",
			'uitkomst'         => 'Eén vaste all-in prijs, binnen 2 werkdagen',
			'stijl'            => 'standaard',
			'achtergrond'      => 'standaard',
			'anker'            => 'prijzen',
		),
		$faq,
		thema_blok_slot(),
	), $id );
	return $id;
}

/**
 * Achtergronden van de homepage vastleggen zoals in het ontwerp.
 */
function thema_zet_achtergronden( $post_id ) {
	$rijen = (array) get_post_meta( $post_id, 'blokken', true );
	$kleur = thema_blok_achtergronden();
	foreach ( $rijen as $i => $layout ) {
		if ( isset( $kleur[ $layout ] ) ) {
			update_post_meta( $post_id, 'blokken_' . $i . '_achtergrond', $kleur[ $layout ] );
			update_post_meta( $post_id, '_blokken_' . $i . '_achtergrond', 'field_' . $layout . '_achtergrond' );
		}
	}
	return $rijen;
}

/**
 * Menu leegmaken of aanmaken, en de items toevoegen.
 */
function thema_vul_menu( $naam, $items ) {
	$menu = wp_get_nav_menu_object( $naam );
	$id   = $menu ? $menu->term_id : wp_create_nav_menu( $naam );
	if ( is_wp_error( $id ) ) {
		return 0;
	}
	foreach ( (array) wp_get_nav_menu_items( $id ) as $oud ) {
		wp_delete_post( $oud->ID, true );
	}
	foreach ( $items as $item ) {
		if ( isset( $item['post'] ) ) {
			wp_update_nav_menu_item( $id, 0, array(
				'menu-item-object-id' => $item['post'],
				'menu-item-object'    => get_post_type( $item['post'] ),
				'menu-item-type'      => 'post_type',
				'menu-item-title'     => $item['titel'] ?? '',
				'menu-item-status'    => 'publish',
			) );
		} else {
			wp_update_nav_menu_item( $id, 0, array(
				'menu-item-title'  => $item['titel'],
				'menu-item-url'    => $item['url'],
				'menu-item-type'   => 'custom',
				'menu-item-status' => 'publish',
			) );
		}
	}
	return (int) $id;
}

function thema_voorbeeld_menus( $hub_id, $contact_id, $gel_ids, $juridisch ) {
	$home     = home_url( '/' );
	$locaties = get_theme_mod( 'nav_menu_locations', array() );

	$locaties['hoofdmenu'] = thema_vul_menu( 'Hoofdmenu', array(
		array( 'titel' => 'Partybus huren', 'url' => $home ),
		array( 'post' => $hub_id, 'titel' => 'Gelegenheden' ),
		array( 'titel' => 'Bussen', 'url' => $home . '#bussen' ),
		array( 'titel' => 'Over ons', 'url' => $home . '#overons' ),
		array( 'post' => $contact_id, 'titel' => 'Contact' ),
	) );

	$locaties['footer_1'] = thema_vul_menu( 'Snel naar', array(
		array( 'titel' => 'Partybus huren', 'url' => $home ),
		array( 'titel' => 'Onze bussen', 'url' => $home . '#bussen' ),
		array( 'titel' => 'Prijzen', 'url' => $home . '#prijzen' ),
		array( 'titel' => 'Veelgestelde vragen', 'url' => $home . '#faq' ),
		array( 'titel' => 'Over ons', 'url' => $home . '#overons' ),
		array( 'post' => $contact_id, 'titel' => 'Contact' ),
	) );

	$locaties['footer_2'] = thema_vul_menu( 'Gelegenheden', array_map( fn( $id ) => array( 'post' => $id ), $gel_ids ) );

	$locaties['footer_3'] = thema_vul_menu( 'Populaire locaties', array_map(
		fn( $plaats ) => array( 'titel' => $plaats, 'url' => $home . '#locaties' ),
		array( 'Breda', 'Tilburg', 'Oss', 'Eindhoven', 'Den Bosch', 'Oisterwijk' )
	) );

	$locaties['juridisch'] = thema_vul_menu( 'Juridisch', array_map( fn( $id ) => array( 'post' => $id ), array_values( $juridisch ) ) );

	// Oude menu's van de vorige opzet opruimen.
	foreach ( array( 'Informatie' ) as $oud ) {
		$menu = wp_get_nav_menu_object( $oud );
		if ( $menu ) {
			wp_delete_nav_menu( $menu->term_id );
		}
	}

	set_theme_mod( 'nav_menu_locations', array_filter( $locaties ) );
}
