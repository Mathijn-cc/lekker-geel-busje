<?php
/**
 * Veelgestelde vragen per pagina, herschreven vanuit het merkfundament (vrolijk, persoonlijk, respectvol).
 * Wordt eenmalig per omgeving in de blokken gezet via de uitroltaak faq-copy-v1 (inc/na-uitrol.php).
 * Alleen de vragen veranderen; kop, tekst en knop van het blok blijven staan.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Losse vragen die op meerdere pagina's terugkomen.
 */
function thema_faq_vragen() {
	return array(
		'boeken'      => array( 'Hoe ver van tevoren moet ik boeken?', 'Hoe eerder, hoe beter. Vrijdagen, zaterdagen en het festivalseizoen zitten snel vol. Staat de datum vast? Vraag dan meteen je offerte aan, dan houden we de bus voor je vrij.' ),
		'drinken'     => array( 'Mag je drinken in de bus?', 'Zeker. Aan boord staat een koeling vol koude drankjes en het gastvrouwke schenkt in. Proosten kan dus al voordat je er bent.' ),
		'eigen-drank' => array( 'Mogen we eigen drank meenemen?', 'Nee, en dat hoeft ook niet. De koeling aan boord ligt vol koude drank en het gastvrouwke zorgt dat niemand droog staat.' ),
		'buiten'      => array( 'Rijden jullie ook buiten Brabant?', 'Zeker. Vanuit Oisterwijk rijden we door heel Brabant en ver daarbuiten. Zelfs een voetbalwedstrijd in Oostenrijk was geen brug te ver.' ),
		'later'       => array( 'Wat als we later terug willen?', 'We spreken vooraf een tijd af voor de terugrit. Is het feest dan nog lang niet klaar? Overleg het met de chauffeur, dan kijken we samen wat kan.' ),
		'muziek'      => array( 'Kunnen we onze eigen muziek draaien?', 'Natuurlijk. Sluit je eigen playlist aan op het geluidssysteem en de bus is jullie dansvloer. Liever een DJ Booth? Regelen we.' ),
		'schade'      => array( 'Wat gebeurt er bij schade?', 'Dat hopen we natuurlijk niet. Gaat er toch iets mis, dan lees je in de huisregels en de algemene voorwaarden precies hoe we dat samen oplossen.' ),
		'roken'       => array( 'Mag er gerookt worden in de bus?', 'Nee, in de bus mag je niet roken. Alleen de rookmachine mag dat.' ),
		'huisregels'  => array( 'Zijn er huisregels?', 'Een paar, en ze zijn simpel: niet roken, geen eigen drank en respect voor de bus en de crew. Zo blijft het voor iedereen geelzellig.' ),
		'personen'    => array( 'Met hoeveel personen kunnen we mee?', 'Elke bus heeft minstens 39 zitplaatsen. In onze verlengde bus gaan tot 88 personen mee, met 53 zitplaatsen en 35 staanplaatsen. Zo past de hele groep in één bus en hoeft niemand achter het stuur.' ),
		'stops'       => array( 'Kunnen we onderweg meerdere stops maken?', 'Zeker, daar is een busje voor. Zet de stops in je aanvraag, dan nemen we ze mee in de route en de offerte.' ),
		'wachten'     => array( 'Wacht de bus op ons?', 'Dat kan. De bus wacht tijdens jullie activiteit of komt op een afgesproken tijd terug. Wachttijd staat gewoon in de offerte, dus je weet vooraf waar je aan toe bent.' ),
		'ophalen'     => array( 'Waar halen jullie ons op?', 'Waar je maar wilt: thuis, bij het werk of op een centraal punt. Als het nodig is ook op meerdere opstapplekken.' ),
		'prijs'       => array( 'Wat kost een rit?', 'Dat hangt af van de grootte van je groep, de afstand en rijtijd, de dag en het seizoen en eventuele wachttijd. Binnen 2 werkdagen heb je een vaste all-in prijs, met chauffeur, gastvrouwke of gastheer, brandstof en btw erbij.' ),
	);
}

/**
 * Welke vragen op welke pagina staan. Sleutel: pad van de pagina, of gelegenheid/{slug}. Waarde: lijst van
 * sleutels uit thema_faq_vragen(), of een eigen array( vraag, antwoord ) voor een vraag die alleen daar past.
 */
function thema_faq_per_pagina() {
	return array(
		'home'               => array( 'boeken', 'drinken', 'buiten', 'muziek', 'later', 'roken', 'schade' ),

		'onze-bussen'        => array(
			array( 'Hoeveel mensen kunnen er mee?', 'Tot 88 personen in één bus. Elke bus heeft minstens 39 zitplaatsen en onze verlengde bus heeft er 53, plus 35 staanplaatsen. Vertel hoeveel jullie zijn, dan kiezen wij de bus die past.' ),
			array( 'Zijn licht en geluid inbegrepen?', 'Altijd. Geluidssysteem, feestverlichting, lasers en rookmachine gaan standaard mee. Daar betaal je geen cent extra voor.' ),
			array( 'Hoe zit het met de veiligheid?', 'Goed geregeld. Onze bussen krijgen het onderhoud dat nodig is voor alle veiligheidseisen. Het gastvrouwke houdt een oogje op de groep, zodat de chauffeur zijn aandacht bij de weg kan houden. Zo blijft het feest voor iedereen leuk.' ),
			array( 'Wie rijdt er met ons mee?', 'Een chauffeur en een gastvrouwke van ons eigen team. De chauffeur stelt zich vooraf aan je voor, dus je stapt nooit in bij een vreemde.' ),
			'eigen-drank',
			'roken',
			'muziek',
		),

		'over-ons'           => array(
			array( 'Is een rit met Lekker Geel Busje veilig?', 'Ja. Onze bussen krijgen het onderhoud dat nodig is voor alle veiligheidseisen en er zijn voldoende zitplaatsen. De chauffeur houdt zijn ogen op de weg, het gastvrouwke op de groep. Zo blijft het feest voor iedereen leuk.' ),
			array( 'Zijn de bussen van jullie zelf?', 'Ja. Je stapt altijd in een van onze eigen gele bussen, met onze eigen chauffeurs en gastvrouwkes. Geen ingehuurde touringcar, wel volle bak geelzelligheid.' ),
			array( 'Zitten licht, lasers en rook bij de prijs in?', 'Ja. Professionele verlichting, lasers, rookmachine en een stevige geluidsinstallatie zitten altijd in de prijs. Sfeer kost bij ons niets extra.' ),
			array( 'Wat doet het gastvrouwke?', 'Het gastvrouwke rijdt bij elke rit mee. Ze schenkt de drankjes uit de koeling, let op de veiligheid en heeft op elke vraag een antwoord. Zo kan de chauffeur zich op de weg richten en jij op het feest.' ),
			array( 'Wat bedoelen jullie met een sfeerbus?', 'Een partybus waarin de sfeer voorop staat. Licht, geluid en drankjes zijn er volop, maar het verschil maken de mensen: een chauffeur die zich vooraf voorstelt, een gastvrouwke aan boord en een groep met iets te vieren. Busje in, sfeertje aan.' ),
			array( 'Rijden jullie ook naar het buitenland?', 'Ja. Vanuit Brabant rijden we door heel Nederland en over de grens. Vertel waar jullie heen willen, dan krijg je een vaste prijs.' ),
			array( 'Mogen we eigen drank meenemen?', 'Nee. Aan boord staat een koeling vol koude drank en het gastvrouwke schenkt in. De andere spelregels lees je op de pagina Huisregels.' ),
		),

		'offerte-aanvragen'  => array(
			array( 'Is een offerte aanvragen vrijblijvend?', 'Helemaal. Je zit nergens aan vast tot je de offerte zelf bevestigt.' ),
			array( 'Hoe snel hoor ik iets?', 'Binnen 2 werkdagen heb je een vaste all-in prijs in je mail. Haast? Bel ons gerust, dan kijken we meteen met je mee.' ),
			array( 'Wat zit er in de prijs?', 'De chauffeur, een gastvrouwke of gastheer, brandstof en btw. Licht, geluid, lasers en rook gaan er standaard bij. Geen kosten achteraf.' ),
			array( 'Waarom staan er geen prijzen op de site?', 'Omdat geen feestrit hetzelfde is. Groepsgrootte, afstand en rijtijd, dag en seizoen, wachttijd en extra wensen bepalen samen de prijs. Daarom rekenen we hem per rit voor je uit, zonder verrassingen achteraf.' ),
			'boeken',
			'later',
			'huisregels',
		),

		'locaties/tilburg'   => array(
			array( 'Waar halen jullie ons op?', 'Waar je maar wilt: thuis, bij het werk of op een centraal punt, en als het moet op meerdere opstapplekken. Onze bussen staan in Oisterwijk, dus Tilburg ligt voor ons om de hoek.' ),
			array( 'Waar zetten jullie ons af in Tilburg?', 'Zo dicht mogelijk bij je eerste stop, vaak tot voor de deur. Rond de Heuvel en de Korte Heuvel is het centrum grotendeels voetgangersgebied. Daar spreken we vooraf een plek af op een paar minuten lopen, en daar halen we jullie ook weer op.' ),
			array( 'Kunnen we in Tilburg meerdere stops maken?', 'Zeker, daar is een busje voor. Eerst proeven bij Schrobbelèr of La Trappe, dan eten aan de Piushaven en daarna door naar de Korte Heuvel? Zet de stops in je aanvraag, dan nemen we ze mee in de route en de offerte.' ),
			'wachten',
			array( 'Rijden jullie ook tijdens de Tilburgse Kermis en Roze Maandag?', 'Ja, maar dan wil half Brabant naar Tilburg. Het zijn de drukste dagen van het jaar, ook voor ons, dus vraag ruim op tijd een offerte aan. Door afsluitingen in het centrum stemmen we de afzetplek vooraf met je af.' ),
			array( 'Wat kost een partybus naar Tilburg?', 'Dat hangt af van de grootte van je groep, de ophaalplek, de rijtijd, de dag en eventuele wachttijd. Binnen 2 werkdagen heb je een vaste all-in prijs, met chauffeur, gastvrouwke of gastheer, brandstof en btw erbij.' ),
			'personen',
		),

		'contact'            => array(
			array( 'Hoe snel reageren jullie?', 'Binnen 2 werkdagen, meestal sneller. Een vraag over een rit die al gepland staat? Bel ons, dan heb je meteen iemand aan de lijn.' ),
			array( 'Hoe vraag ik een prijs aan?', 'Via het offerteformulier. Vertel wanneer, waarheen en met hoeveel jullie zijn, dan krijg je binnen 2 werkdagen een vaste all-in prijs.' ),
			'boeken',
			'buiten',
			'huisregels',
			'schade',
		),

		'gelegenheden'       => array(
			array( 'Voor welke feestjes kunnen we jullie boeken?', 'Voor alles waar je met een groep naartoe gaat: een vrijgezellenfeest, bedrijfsuitje, bruiloft, festival of gewoon een dag op pad met vrienden. Staat jouw feestje er niet tussen? Vraag het gerust, de kans is groot dat we er al eens heen reden.' ),
			'stops',
			'wachten',
			'personen',
			'drinken',
			'boeken',
		),

		'gelegenheid/vrijgezellenfeest' => array(
			array( 'Kunnen we op de vrijgezellendag meerdere stops maken?', 'Zeker. Een activiteit overdag, eten en daarna de stad in? Zet de stops in je aanvraag, dan maken wij er één feestrit van. De bus wacht of komt op een afgesproken tijd terug.' ),
			'drinken',
			array( 'Kunnen we onze eigen muziek draaien?', 'Natuurlijk. Sluit de playlist van de vrijgezel aan op het geluidssysteem, of juist die van de vrienden. Liever een DJ Booth? Regelen we.' ),
			'later',
			array( 'Hoe ver van tevoren moeten we boeken?', 'Vrijgezellenfeesten vallen vaak op vrijdag of zaterdag, en die zitten snel vol. Weet je de datum? Vraag dan meteen je offerte aan.' ),
			array( 'Zijn er huisregels?', 'Een paar simpele: niet roken, geen eigen drank en respect voor de bus en de crew. Zo blijft de laatste rit als vrijgezel voor iedereen een feest.' ),
		),

		'gelegenheid/bedrijfsuitje'     => array(
			array( 'Kunnen collega\'s op verschillende plekken opstappen?', 'Ja. We halen op bij kantoor, op een centraal punt of op meerdere opstapplekken. Geef ze door in je aanvraag, dan zit iedereen op tijd in de bus.' ),
			array( 'Passen we met de hele afdeling in één bus?', 'Tot 88 personen gaan mee in onze verlengde bus, en elke bus heeft minstens 39 zitplaatsen. Samen heen, samen terug en niemand hoeft te rijden.' ),
			array( 'Krijgen we vooraf een vaste prijs?', 'Ja. Binnen 2 werkdagen heb je een vaste all-in prijs, met chauffeur, gastvrouwke of gastheer, brandstof en btw. Handig voor de begroting, geen kosten achteraf.' ),
			'wachten',
			'drinken',
			'boeken',
		),

		'gelegenheid/bruiloft'          => array(
			array( 'Kunnen jullie onze gasten van de ceremonie naar het feest rijden?', 'Zeker. Geef de route en de tijden door, dan rijden we jullie gasten van de ene plek naar de andere, met meerdere stops als dat nodig is. Zo begint het feest al onderweg.' ),
			array( 'Hoeveel gasten passen er in een bus?', 'Elke bus heeft minstens 39 zitplaatsen. In onze verlengde bus gaan tot 88 personen mee. Vertel met hoeveel jullie zijn, dan kiezen wij de bus die past.' ),
			'wachten',
			'later',
			'drinken',
			array( 'Hoe ver van tevoren moeten we boeken?', 'Een trouwdatum staat vaak lang vast, en de zaterdagen in het trouwseizoen gaan snel. Vraag dus op tijd een offerte aan, dan houden we de bus voor jullie vrij.' ),
		),

		'gelegenheid/trip-met-vrienden' => array(
			'stops',
			'muziek',
			'drinken',
			'later',
			'buiten',
			'huisregels',
		),

		'gelegenheid/festival'          => array(
			array( 'Waar zetten jullie ons af bij het festival?', 'Zo dicht mogelijk bij de ingang. Waar precies, stemmen we vooraf met je af, en op die plek halen we jullie na afloop ook weer op.' ),
			array( 'Wat als we na het festival later terug willen?', 'We spreken vooraf een tijd af voor de terugrit. Loopt het uit? Overleg het met de chauffeur, dan kijken we samen wat kan.' ),
			array( 'Hoe ver van tevoren moeten we boeken?', 'In het festivalseizoen zijn de weekenden snel vol. Heb je je tickets? Vraag dan meteen je offerte aan.' ),
			'personen',
			'drinken',
			'muziek',
		),
	);
}

/**
 * De vragen van een pagina als rijen voor de ACF-repeater.
 */
function thema_faq_rijen( $lijst ) {
	$los  = thema_faq_vragen();
	$rijen = array();
	foreach ( $lijst as $item ) {
		$v = is_array( $item ) ? $item : ( $los[ $item ] ?? null );
		if ( $v ) {
			$rijen[] = array( 'vraag' => $v[0], 'antwoord' => $v[1] );
		}
	}
	return $rijen;
}

/**
 * Uitroltaak: de vragen in elk FAQ-blok van de gekoppelde pagina's vervangen.
 */
function thema_faq_copy_zetten() {
	if ( ! function_exists( 'get_field' ) || ! function_exists( 'update_field' ) ) {
		return 'ACF ontbreekt';
	}
	$uit = array();
	foreach ( thema_faq_per_pagina() as $sleutel => $lijst ) {
		if ( 'home' === $sleutel ) {
			$post = get_post( (int) get_option( 'page_on_front' ) );
		} elseif ( 0 === strpos( $sleutel, 'gelegenheid/' ) ) {
			$post = get_page_by_path( substr( $sleutel, 12 ), OBJECT, 'gelegenheid' );
		} else {
			$post = get_page_by_path( $sleutel );
		}
		if ( ! $post ) {
			$uit[ $sleutel ] = 'niet gevonden';
			continue;
		}
		$blokken = get_field( 'blokken', $post->ID, false );
		if ( ! is_array( $blokken ) ) {
			$uit[ $sleutel ] = 'geen blokken';
			continue;
		}
		$gezet = 0;
		foreach ( array_values( $blokken ) as $i => $blok ) {
			if ( 'faq' === ( $blok['acf_fc_layout'] ?? '' ) ) {
				update_sub_field( array( 'blokken', $i + 1, 'vragen' ), thema_faq_rijen( $lijst ), $post->ID );
				$gezet++;
			}
		}
		$uit[ $sleutel ] = $gezet ? $gezet . ' blok' : 'geen faq-blok';
	}
	return $uit;
}
