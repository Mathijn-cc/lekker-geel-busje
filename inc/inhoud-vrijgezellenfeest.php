<?php
/**
 * Inhoud van de landingspagina /gelegenheden/vrijgezellenfeest/ (november 2026).
 * Geplaatst door de uitroltaak vrijgezellenfeest-v1. Wie de tekst later in WordPress aanpast,
 * houdt die aanpassing: de taak draait per omgeving maar één keer.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Een beeld uit de mediabibliotheek op bestandsnaam, ook als het niet via de voorbeeldinhoud is geplaatst.
 */
function thema_beeld_op_naam( $bestand ) {
	$id = function_exists( 'thema_voorbeeld_beeld' ) ? thema_voorbeeld_beeld( $bestand ) : 0;
	if ( $id ) {
		return $id;
	}
	$gevonden = get_posts( array(
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'meta_query'     => array( array( 'key' => '_wp_attached_file', 'value' => pathinfo( $bestand, PATHINFO_FILENAME ), 'compare' => 'LIKE' ) ),
	) );
	return $gevonden ? (int) $gevonden[0] : 0;
}

function thema_plaats_vrijgezellenfeest() {
	if ( ! function_exists( 'update_field' ) ) {
		return 0;
	}
	$post = get_page_by_path( 'vrijgezellenfeest', OBJECT, 'gelegenheid' );
	if ( ! $post ) {
		return 0;
	}
	$id = (int) $post->ID;
	$b  = array();
	foreach ( array( 'vrienden-proosten.jpg', 'dansen.jpg', 'uitstappen.jpg', 'groep-in-bus.jpg', 'proosten.jpg', 'slingers.jpg' ) as $bestand ) {
		$b[ $bestand ] = thema_beeld_op_naam( $bestand );
	}
	$locaties = get_page_by_path( 'locaties' );
	$locaties = $locaties ? get_permalink( $locaties ) : home_url( '/locaties/' );
	$onderwerp = get_category_by_slug( 'vrijgezellenfeest' );
	$regels   = get_page_by_path( 'huisregels' );
	$regels   = $regels ? get_permalink( $regels ) : home_url( '/huisregels/' );
	$bussen   = get_page_by_path( 'onze-bussen' );
	$bussen   = $bussen ? get_permalink( $bussen ) : home_url( '/onze-bussen/' );

	update_field( 'field_gel_ondertitel', 'De laatste rit als vrijgezel, met de hele crew.', $id );
	update_field( 'field_seo_seo_titel', 'Partybus voor je vrijgezellenfeest | Lekker Geel Busje', $id );
	update_field( 'field_seo_seo_omschrijving', 'Partybus voor je vrijgezellenfeest, met chauffeur, gastvrouwke, licht en geluid. Met tips, een voorbeelddag en opdrachten voor in de bus.', $id );

	/* Echte reviews; die over een vrijgezellenfeest staat vooraan. */
	$reviews        = thema_blok_reviews();
	$reviews['kop'] = 'Groepen die je *voorgingen*';
	usort( $reviews['reviews'], fn( $x, $y ) => (int) ( false !== stripos( $y['tekst'], 'vrijgezel' ) ) - (int) ( false !== stripos( $x['tekst'], 'vrijgezel' ) ) );
	$reviews['weergave'] = 'compact';

	/* De vragen uit inc/faq-copy.php (merkfundament), aangevuld met wat de organisator nog meer zoekt. */
	$eigen  = array(
		array( 'Wat kost een partybus voor een vrijgezellenfeest?', 'Dat hangt af van de grootte van de groep, de afstand en rijtijd, de dag en eventuele wachttijd. Binnen 2 werkdagen heb je een vaste all-in prijs, met chauffeur, gastvrouwke, licht en geluid, brandstof en btw erin. Deel je die door de groep, dan weet iedereen vooraf precies wat zijn deel is.' ),
		'personen',
		'ophalen',
		array( 'Waar rijden jullie naartoe?', 'Overal waar jullie feest is. We rijden door heel Nederland en daarbuiten, dus kies gerust de stad, het festival of de activiteit die bij de vrijgezel past. Vertel waar jullie heen willen, dan krijg je een vaste prijs.' ),
		array( 'Is een partybus veilig voor een vrijgezellenfeest?', 'Je rijdt in een van onze eigen bussen, die het onderhoud krijgen dat nodig is voor alle veiligheidseisen. De chauffeur houdt zijn aandacht bij de weg en het gastvrouwke let op de groep. Zo blijft het feest leuk voor iedereen, ook voor de vrijgezel.' ),
	);
	$lijst  = function_exists( 'thema_faq_per_pagina' ) ? ( thema_faq_per_pagina()['gelegenheid/vrijgezellenfeest'] ?? array() ) : array();
	$vragen = function_exists( 'thema_faq_rijen' ) ? thema_faq_rijen( array_merge( array( $eigen[0] ), $lijst, array_slice( $eigen, 1 ) ) ) : array();
	$faq           = thema_blok_faq();
	$faq['kop']    = 'Vragen over een partybus voor je *vrijgezellenfeest*';
	$faq['vragen'] = $vragen;

	$offerte          = thema_blok_offerte();
	$offerte['label'] = 'Binnen 2 werkdagen je prijs';
	$offerte['kop']   = 'Klaar voor de *laatste rit als vrijgezel?*';

	$naar_offerte = thema_link( 'Vraag je prijs aan', '#offerte' );

	update_field( 'field_blokken', array(
		array(
			'acf_fc_layout' => 'hero',
			'kop_1'         => 'Partybus voor je',
			'kop_2'         => 'vrijgezellenfeest',
			'plaatsen'      => '',
			'sub_1'         => 'De laatste rit als vrijgezel,',
			'sub_2'         => 'met de hele crew.',
			'knop'          => $naar_offerte,
			'knop_tonen'    => 1,
			'toon_score'    => 1,
			'stijl'         => 'foto',
			'fotos'         => array_values( array_filter( array( $b['vrienden-proosten.jpg'], $b['dansen.jpg'], $b['uitstappen.jpg'] ) ) ),
			'vorm'          => 'auto',
			'hoogte'        => 'compact',
			'anker'         => 'top',
		),
		array(
			'acf_fc_layout' => 'over_ons',
			'eyebrow'       => 'Voor de organisator',
			'kop'           => 'Jij regelt het feest, *wij de rit.*',
			'tekst_1'       => 'Regel jij het vrijgezellenfeest van je beste vriendin, je broer of je collega? Dan heb je al genoeg aan je hoofd. Met een partybus van Lekker Geel Busje is het vervoer in één keer geregeld: we halen de groep op, rijden jullie van activiteit naar restaurant en stapadres, en brengen iedereen veilig weer thuis. Niemand hoeft de Bob te zijn en niemand raakt onderweg kwijt.',
			'tekst_2'       => 'En de rit zelf hoort gewoon bij het feest. In onze sfeerbus zitten professionele verlichting, lasers, rook en een geluidsinstallatie die er staat, allemaal inbegrepen. Het gastvrouwke zorgt voor de drankjes en let op de groep, de chauffeur houdt zijn aandacht bij de weg. Jij hoeft alleen nog te genieten.',
			'knop'          => thema_link( 'Bekijk onze bussen', $bussen ),
			'beelden'       => array_values( array_filter( array( $b['groep-in-bus.jpg'], $b['proosten.jpg'], $b['slingers.jpg'] ) ) ),
			'achtergrond'   => 'standaard',
			'anker'         => 'organisator',
		),
		array(
			'acf_fc_layout' => 'route',
			'eyebrow'       => 'Voorbeelddag',
			'kop'           => 'Zo kan jullie *vrijgezellendag* eruitzien',
			'intro'         => 'Zo ziet een vrijgezellendag met de bus er bijvoorbeeld uit. Elke groep is anders: vertel ons jullie plan, dan rijden wij de route.',
			'stappen'       => array(
				array( 'tijd' => '13:00', 'icoon' => 'bus', 'titel' => 'Ophalen als een ster', 'tekst' => 'De gele bus staat voor de deur, of haalt de groep op meerdere opstapplekken op. Muziek aan, eerste toost, en de vrijgezel weet meteen: dit wordt een dag om nooit te vergeten.' ),
				array( 'tijd' => '14:00', 'icoon' => 'beker', 'titel' => 'Activiteit in de middag', 'tekst' => 'Een proeverij, een workshop of een spel waarbij de vrijgezel flink aan de bak moet. De bus wacht, of komt op een afgesproken tijd terug.' ),
				array( 'tijd' => '17:30', 'icoon' => 'bestek', 'titel' => 'Samen eten', 'tekst' => 'Op naar het restaurant. Geen gezoek naar parkeerplekken, geen groepjes die de weg kwijt zijn.' ),
				array( 'tijd' => '20:30', 'icoon' => 'muziek', 'titel' => 'Feestrit naar de stad', 'tekst' => 'Licht, lasers en rook aan. Tussen eten en stappen is de bus jullie eigen feestzaal op wielen, met de opdrachten verderop op deze pagina.' ),
				array( 'tijd' => '21:00', 'icoon' => 'glas', 'titel' => 'De stad in', 'tekst' => 'De bus zet jullie af midden in het uitgaansleven. Daar wacht de kroeg of club waar de groep de avond afsluit.' ),
				array( 'tijd' => 'Einde avond', 'icoon' => 'huis', 'titel' => 'Iedereen veilig thuis', 'tekst' => 'Op de afgesproken tijd staat de bus klaar. Op de terugweg komen de verhalen, en iedereen wordt netjes afgezet.' ),
			),
			'slot'          => '<p>Nog op zoek naar een bestemming? Bij onze <a href="' . esc_url( $locaties ) . '">bestemmingen</a> vind je ideeën voor een dag uit met de groep. We rijden door heel Nederland en daarbuiten.</p>',
			'knop'          => $naar_offerte,
			'achtergrond'   => 'standaard',
			'anker'         => 'voorbeelddag',
		),
		array(
			'acf_fc_layout' => 'opdrachten',
			'eyebrow'       => 'Voor onderweg',
			'kop'           => 'Acht opdrachten voor *in de bus*',
			'intro'         => 'Spelletjes voor een vrijgezellenfeest die je gewoon zittend doet, want tijdens de rit blijft iedereen op zijn plek. Bereid ze voor, dan vliegt de tijd tussen twee stops voorbij.',
			'items'         => array(
				array( 'soort' => 'Quiz', 'titel' => 'Hoe goed ken jij je partner?', 'tekst' => 'Stel vooraf tien vragen aan de partner van de vrijgezel en neem de antwoorden op. In de bus raadt de vrijgezel. Elk fout antwoord wordt een opdracht bij de volgende stop.' ),
				array( 'soort' => 'Muziek', 'titel' => 'Zing de volgende regel', 'tekst' => 'Zet een nummer na een paar seconden stil. Wie zingt de volgende regel goed? De vrijgezel mag als eerste, de rest van de bus zingt mee.' ),
				array( 'soort' => 'Kennismaken', 'titel' => 'Van wie is dit feitje?', 'tekst' => 'Iedereen schrijft vooraf een onbekend feitje over zichzelf op. Lees ze voor in de bus en laat de vrijgezel raden van wie het is. Ideaal als vrienden, familie en collega’s elkaar nog niet kennen.' ),
				array( 'soort' => 'Lachen', 'titel' => 'Het grote liefdesverhaal', 'tekst' => 'Vertel om de beurt één zin van het verhaal hoe het stel elkaar leerde kennen. Het mag kloppen, het mag ook helemaal verzonnen zijn.' ),
				array( 'soort' => 'Hele dag', 'titel' => 'Vrijgezellenbingo', 'tekst' => 'Maak bingokaarten met dingen die vandaag gaan gebeuren: iemand die zijn jas vergeet, een toost op het bruidspaar, een selfie met een vreemde. Wie heeft als eerste een volle rij?' ),
				array( 'soort' => 'Aandenken', 'titel' => 'Tips voor het huwelijk', 'tekst' => 'Iedereen schrijft een advies voor het huwelijk op een kaartje. De vrijgezel leest ze voor in de bus, en de kaartjes gaan mee naar huis als aandenken.' ),
				array( 'soort' => 'Foto', 'titel' => 'De fotojacht', 'tekst' => 'Geef de groep een lijstje foto’s voor onderweg: iedereen in één selfie, iets geels, de vrijgezel met een vreemde hoed. Op de terugweg kiezen jullie samen de beste.' ),
				array( 'soort' => 'Proosten', 'titel' => 'Toost met een verhaal', 'tekst' => 'Bij elke rit brengt één iemand een toost uit met een mooi of gênant verhaal over de vrijgezel. Kort houden, glazen omhoog.' ),
			),
			'noot'          => 'Alles zittend te doen en niets wat de chauffeur afleidt. Zo blijft het leuk voor iedereen.',
			'achtergrond'   => 'paars',
			'anker'         => 'opdrachten',
		),
		array(
			'acf_fc_layout' => 'lopende_band',
			'items'         => "Niet roken in de bus\nGeen eigen drank, wel een volle koeling\nRespect voor de bus en elkaar\nVoldoende zitplaatsen voor iedereen",
			'knop'          => thema_link( 'Lees onze huisregels', $regels ),
			'variant'       => 'paars',
			'anker'         => '',
		),
		$reviews,
		array(
			'acf_fc_layout' => 'artikelen',
			'eyebrow'       => 'Voor de organisator',
			'kop'           => 'Organiseer jij het *vrijgezellenfeest?*',
			'intro'         => 'Tips voor een geslaagde dag en een checklist om af te vinken. Zo weet je zeker dat alles op tijd geregeld is.',
			'keuze'         => array(),
			'onderwerp'     => $onderwerp ? (int) $onderwerp->term_id : 0,
			'aantal'        => 3,
			'knop'          => '',
			'achtergrond'   => 'standaard',
			'anker'         => 'organiseren',
		),
		$faq,
		$offerte,
	), $id );

	return $id;
}
