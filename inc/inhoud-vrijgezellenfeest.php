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
	$tilburg  = get_page_by_path( 'locaties/tilburg' );
	$tilburg  = $tilburg ? get_permalink( $tilburg ) : home_url( '/locaties/tilburg/' );
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
		array( 'Waar rijden jullie naartoe?', 'Overal waar jullie feest is. Tilburg, Breda, Den Bosch, Eindhoven of juist Amsterdam: we rijden door heel Nederland en daarbuiten. Vertel waar jullie heen willen, dan krijg je een vaste prijs.' ),
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
			'intro'         => 'Een voorbeeld van een dag naar Tilburg, voor een groep vriendinnen of vrienden. Elke groep is anders: vertel ons jullie plan, dan rijden wij de route.',
			'stappen'       => array(
				array( 'tijd' => '13:00', 'icoon' => 'bus', 'titel' => 'Ophalen als een ster', 'tekst' => 'De gele bus staat voor de deur, of haalt de groep op meerdere opstapplekken op. Muziek aan, eerste toost, en de vrijgezel weet meteen: dit wordt een dag om nooit te vergeten.' ),
				array( 'tijd' => '14:00', 'icoon' => 'beker', 'titel' => 'Proeven of spelen', 'tekst' => 'Een proeverij bij La Trappe of Schrobbelèr, of een activiteit waarbij de vrijgezel flink aan de bak moet. De bus wacht, of komt op een afgesproken tijd terug.' ),
				array( 'tijd' => '17:30', 'icoon' => 'bestek', 'titel' => 'Samen eten', 'tekst' => 'Op naar een restaurant aan de Piushaven. Geen gezoek naar parkeerplekken, geen groepjes die de weg kwijt zijn.' ),
				array( 'tijd' => '20:30', 'icoon' => 'muziek', 'titel' => 'Feestrit naar de stad', 'tekst' => 'Licht, lasers en rook aan. Tussen eten en stappen is de bus jullie eigen feestzaal op wielen, met de opdrachten verderop op deze pagina.' ),
				array( 'tijd' => '21:00', 'icoon' => 'glas', 'titel' => 'Stappen in het centrum', 'tekst' => 'Het Piusplein en de Korte Heuvel liggen naast elkaar. Daar is de hele avond een kroeg waar jullie groep past.' ),
				array( 'tijd' => 'Einde avond', 'icoon' => 'huis', 'titel' => 'Iedereen veilig thuis', 'tekst' => 'Op de afgesproken tijd staat de bus klaar. Op de terugweg komen de verhalen, en iedereen wordt netjes afgezet.' ),
			),
			'slot'          => '<p>Meer plekken, proeverijen en stapadressen vind je op onze pagina over een <a href="' . esc_url( $tilburg ) . '">vrijgezellenfeest in Tilburg</a>. Liever naar Breda, Den Bosch, Eindhoven of juist Amsterdam? We rijden door heel Nederland en daarbuiten.</p>',
			'knop'          => $naar_offerte,
			'achtergrond'   => 'standaard',
			'anker'         => 'voorbeelddag',
		),
		array(
			'acf_fc_layout' => 'tips',
			'eyebrow'       => 'Tips voor de organisator',
			'kop'           => 'Acht tips voor een *geslaagd vrijgezellenfeest*',
			'intro'         => 'Van planning tot playlist: zo houd je het leuk voor de groep en overzichtelijk voor jezelf.',
			'kolommen'      => '2',
			'items'         => array(
				array( 'soort' => 'Planning', 'titel' => 'Begin op tijd', 'tekst' => 'Een vrijgezellenfeest valt vaak op een vrijdag of zaterdag, en die zitten snel vol. Prik de datum met de getuigen en de partner, en vraag de bus aan zodra die vaststaat. Dan heb je de meeste keus.' ),
				array( 'soort' => 'Groep', 'titel' => 'Weet met hoeveel je bent', 'tekst' => 'Vraag iedereen vroeg om een ja of nee, met een duidelijke deadline. Met een vast aantal krijg je een vaste prijs, en reserveer je makkelijker bij een activiteit of restaurant.' ),
				array( 'soort' => 'Budget', 'titel' => 'Maak de kosten vooraf duidelijk', 'tekst' => 'Tel bus, activiteit en eten bij elkaar op en deel het door het aantal deelnemers. Stuur iedereen vooraf een betaalverzoek. Dan hoef je achteraf niemand achter de broek te zitten.' ),
				array( 'soort' => 'De vrijgezel', 'titel' => 'Houd het leuk voor de vrijgezel', 'tekst' => 'Een verrassing is leuk, een vrijgezel die zich de hele dag schaamt niet. Kies opdrachten en activiteiten die bij haar of hem passen, en vraag de partner gerust om tips.' ),
				array( 'soort' => 'Programma', 'titel' => 'Plan niet te veel', 'tekst' => 'Een middag, een diner en een avond stappen is genoeg voor een topdag. De ritten ertussen zijn je natuurlijke pauzes: even zitten, iets drinken en bijkletsen.' ),
				array( 'soort' => 'Sfeer', 'titel' => 'Kies een thema of dresscode', 'tekst' => 'Iedereen in het geel, een bijpassend shirt of een sjerp voor de vrijgezel. Op de foto’s in de bus en in de stad ziet dat er meteen feestelijk uit.' ),
				array( 'soort' => 'Muziek', 'titel' => 'Maak een gezamenlijke playlist', 'tekst' => 'Vraag iedereen een paar nummers toe te voegen, met de favorieten van de vrijgezel erin. Zo zit de sfeer er vanaf de eerste kilometer in.' ),
				array( 'soort' => 'Afspraken', 'titel' => 'Deel tijden en adressen vooraf', 'tekst' => 'Zet in de groepsapp hoe laat de bus vertrekt bij elke stop. Dan hoef jij niet de hele dag te bellen en te zoeken, en is iedereen op tijd terug.' ),
			),
			'achtergrond'   => 'standaard',
			'anker'         => 'tips',
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
			'acf_fc_layout' => 'checklist',
			'eyebrow'       => 'Checklist',
			'kop'           => 'Alles geregeld *voor de grote dag?*',
			'intro'         => 'Vink af wat je al gedaan hebt. Je vinkjes blijven op dit apparaat bewaard, dus kom gerust later terug.',
			'items'         => array(
				array( 'wanneer' => 'Zo snel mogelijk', 'taak' => 'Datum prikken met de getuigen en de partner', 'link' => '' ),
				array( 'wanneer' => 'Zo snel mogelijk', 'taak' => 'Gastenlijst maken en iedereen om een ja of nee vragen', 'link' => '' ),
				array( 'wanneer' => 'Als de datum staat', 'taak' => 'Partybus aanvragen met datum, aantal en ophaalplek', 'link' => thema_link( 'Vraag je prijs aan', '#offerte' ) ),
				array( 'wanneer' => 'Een maand van tevoren', 'taak' => 'Activiteit en restaurant reserveren', 'link' => '' ),
				array( 'wanneer' => 'Een maand van tevoren', 'taak' => 'De bijdrage per persoon innen', 'link' => '' ),
				array( 'wanneer' => 'Een paar weken van tevoren', 'taak' => 'Thema of dresscode doorgeven aan de groep', 'link' => '' ),
				array( 'wanneer' => 'De week ervoor', 'taak' => 'Playlist en opdrachten voor in de bus klaarzetten', 'link' => '' ),
				array( 'wanneer' => 'De week ervoor', 'taak' => 'Tijden en adressen delen in de groepsapp', 'link' => '' ),
				array( 'wanneer' => 'Op de dag zelf', 'taak' => 'Genieten. De rit is geregeld.', 'link' => '' ),
			),
			'knop'          => '',
			'achtergrond'   => 'standaard',
			'anker'         => 'checklist',
		),
		$faq,
		$offerte,
	), $id );

	return $id;
}
