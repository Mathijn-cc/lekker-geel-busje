<?php
/**
 * Bruiloft (november 2026): de landingspagina /gelegenheden/bruiloft/ en zes artikelen in het onderwerp
 * Trouwen (/blog/onderwerp/trouwen/). Geplaatst door de uitroltaken bruiloft-blog-v1, bruiloft-v1 en
 * bruiloft-volgorde-v1. Wie de tekst later in WordPress aanpast, houdt die aanpassing.
 *
 * Zoekwoorden (SE Ranking, NL, 17 nov 2026):
 * - Pagina: bruiloft 8.000 (moeilijkheid 75), vervoer bruiloft 30, bus huren bruiloft 30 (9).
 * - Artikelen: checklist bruiloft 590 + bruiloft checklist 590 (14), getuige bruiloft 590 (5), speech bruiloft 320 (6),
 *   taken ceremoniemeester 210 + ceremoniemeester taken 170 (13), bruiloft ideeen 320 + 260 (6), spelletjes bruiloft 110 (12),
 *   wat kost een bruiloft 480 + bruiloft kosten 480 (17 tot 19), gouden bruiloft 590 (10), 40 jarig huwelijk 660 (12),
 *   zilveren bruiloft 480 (12), 25 jarig huwelijk 470 (17), koperen bruiloft 320 (12).
 */

defined( 'ABSPATH' ) || exit;

function thema_bruiloft_link( $slug ) {
	$p = get_page_by_path( $slug, OBJECT, 'post' );
	return $p ? get_permalink( $p ) : home_url( '/blog/' . $slug . '/' );
}

/**
 * Uitroltaak bruiloft-blog-v1: onderwerp Trouwen en de zes artikelen.
 */
function thema_bruiloft_blog() {
	$uit  = array();
	$term = get_category_by_slug( 'trouwen' );
	if ( $term ) {
		$cat = (int) $term->term_id;
	} else {
		$nieuw = wp_insert_term( 'Trouwen', 'category', array(
			'slug'        => 'trouwen',
			'description' => 'Alles voor het bruidspaar, de getuigen en de ceremoniemeester: een checklist voor de trouwdag, ideeën, wat een bruiloft kost en hoe je een huwelijksjubileum viert.',
		) );
		$cat = is_wp_error( $nieuw ) ? 0 : (int) $nieuw['term_id'];
	}
	$uit['onderwerp'] = $cat;
	$gel = get_page_by_path( 'bruiloft', OBJECT, 'gelegenheid' );
	$gel = $gel ? (int) $gel->ID : 0;
	foreach ( thema_bruiloft_artikelen() as $artikel ) {
		$uit[ $artikel['slug'] ] = thema_blog_artikel_plaatsen( $artikel, $cat, $gel );
	}
	delete_option( 'rewrite_rules' );
	return $uit;
}

/**
 * Uitroltaak bruiloft-volgorde-v1: publicatiedatums zo dat de landingspagina checklist, ceremoniemeester en getuige toont.
 */
function thema_bruiloft_volgorde() {
	$uit      = array();
	$volgorde = array(
		'checklist-bruiloft',
		'ceremoniemeester-taken',
		'getuige-bruiloft-taken-speech',
		'bruiloft-ideeen',
		'wat-kost-een-bruiloft',
		'huwelijksjubileum-vieren',
	);
	/* Ruim in het verleden, ook als de site op UTC staat: een datum in de toekomst wordt anders een ingepland bericht. */
	$basis = strtotime( '2026-11-17 11:30:00' );
	foreach ( $volgorde as $i => $slug ) {
		$p = get_page_by_path( $slug, OBJECT, 'post' );
		if ( ! $p ) {
			continue;
		}
		$lokaal = gmdate( 'Y-m-d H:i:s', $basis - $i * 5 * MINUTE_IN_SECONDS );
		wp_update_post( array(
			'ID'            => $p->ID,
			'post_date'     => $lokaal,
			'post_date_gmt' => get_gmt_from_date( $lokaal ),
			'post_status'   => 'publish',
			'edit_date'     => true,
		) );
		$uit[ $slug ] = $lokaal;
	}
	return $uit;
}

/**
 * Uitroltaak bruiloft-v1: de blokken van de landingspagina.
 */
function thema_plaats_bruiloft() {
	if ( ! function_exists( 'update_field' ) ) {
		return 0;
	}
	$post = get_page_by_path( 'bruiloft', OBJECT, 'gelegenheid' );
	if ( ! $post ) {
		return 0;
	}
	$id = (int) $post->ID;
	$b  = array();
	foreach ( array(
		'feest-met-microfoons-en-bloemenkransen',
		'gastvrouw-verwelkomt-gasten-bij-de-bus',
		'verlichte-sfeerbus-van-buiten',
		'gasten-stappen-in-lekker-geel-busje',
		'gastheer-deelt-drankjes-uit-aan-de-bar',
		'proosten-in-het-gangpad-van-de-bus',
	) as $naam ) {
		$b[ $naam ] = thema_beeld_op_naam( $naam );
	}
	$locaties = get_page_by_path( 'locaties' );
	$locaties = $locaties ? get_permalink( $locaties ) : home_url( '/locaties/' );
	$regels   = get_page_by_path( 'huisregels' );
	$regels   = $regels ? get_permalink( $regels ) : home_url( '/huisregels/' );
	$bussen   = get_page_by_path( 'onze-bussen' );
	$bussen   = $bussen ? get_permalink( $bussen ) : home_url( '/onze-bussen/' );
	$vrijgezel = get_page_by_path( 'vrijgezellenfeest', OBJECT, 'gelegenheid' );
	$vrijgezel = $vrijgezel ? get_permalink( $vrijgezel ) : home_url( '/gelegenheden/vrijgezellenfeest/' );

	update_field( 'field_gel_ondertitel', 'Van ceremonie naar feest, met al jullie gasten.', $id );
	update_field( 'field_seo_seo_titel', 'Partybus en gastenvervoer voor je bruiloft | Lekker Geel Busje', $id );
	update_field( 'field_seo_seo_omschrijving', 'Trouwvervoer voor al jullie gasten: van ceremonie naar feest, daggasten en avondgasten, en na afloop veilig naar huis. Binnen 1 werkdag een vaste prijs.', $id );

	$reviews             = thema_blok_reviews();
	$reviews['kop']      = 'Zij stapten al in, *dit vonden ze ervan*';
	$reviews['weergave'] = 'compact';

	$lijst = function_exists( 'thema_faq_per_pagina' ) ? ( thema_faq_per_pagina()['gelegenheid/bruiloft'] ?? array() ) : array();
	$eigen = array(
		array( 'Wat kost een partybus voor een bruiloft?', 'Dat hangt af van het aantal gasten, de afstand en rijtijd, de dag en hoe lang de bus erbij blijft. Binnen 1 werkdag heb je een vaste all-in prijs, met chauffeur, gastvrouwke, licht en geluid, brandstof en btw erin. Zo zetten jullie het vervoer meteen in de trouwbegroting.' ),
	);
	$extra = array(
		array( 'Regelen jullie ook vervoer voor daggasten en avondgasten?', 'Ja. Daggasten rijden mee naar de ceremonie en het diner, avondgasten halen we later op bij een verzamelpunt. Zet de tijden en plekken in je aanvraag, dan maken we er één planning van voor het trouwvervoer.' ),
		array( 'Kan het bruidspaar ook mee in de bus?', 'Natuurlijk. Veel stellen rijden een stuk mee met hun gasten, bijvoorbeeld van de ceremonie naar het feest. Maar ook als jullie zelf met een trouwauto gaan, rijden wij de gasten.' ),
	);
	$vragen        = function_exists( 'thema_faq_rijen' ) ? thema_faq_rijen( array_merge( $eigen, $lijst, $extra ) ) : array();
	$faq           = thema_blok_faq();
	$faq['kop']    = 'Vragen over een partybus voor je *bruiloft*';
	$faq['vragen'] = $vragen;

	$offerte          = thema_blok_offerte();
	$offerte['label'] = 'Binnen 1 werkdag je prijs';
	$offerte['kop']   = 'Zeggen jullie ja? *Rijden wij de gasten.*';

	$trouwen      = get_category_by_slug( 'trouwen' );
	$naar_offerte = thema_link( 'Vraag je prijs aan', '#offerte' );

	update_field( 'field_blokken', array(
		array(
			'acf_fc_layout' => 'hero',
			'kop_1'         => 'Partybus voor je',
			'kop_2'         => 'bruiloft',
			'plaatsen'      => '',
			'sub_1'         => 'Van ceremonie naar feest,',
			'sub_2'         => 'met al jullie gasten.',
			'knop'          => $naar_offerte,
			'knop_tonen'    => 1,
			'toon_score'    => 1,
			'stijl'         => 'foto',
			'fotos'         => array_values( array_filter( array( $b['feest-met-microfoons-en-bloemenkransen'], $b['gastvrouw-verwelkomt-gasten-bij-de-bus'], $b['verlichte-sfeerbus-van-buiten'] ) ) ),
			'vorm'          => 'auto',
			'hoogte'        => 'compact',
			'anker'         => 'top',
		),
		array(
			'acf_fc_layout' => 'over_ons',
			'eyebrow'       => 'Voor het bruidspaar',
			'kop'           => 'Iedereen erbij, *niemand achter het stuur.*',
			'tekst_1'       => 'Op een trouwdag liggen de ceremonie, de receptie en het feest vaak op verschillende plekken. Met tientallen gasten die allemaal zelf rijden, betekent dat parkeerplekken zoeken, gasten die te laat binnenkomen en een tante die de weg kwijt is. Met het trouwvervoer van Lekker Geel Busje rijdt iedereen samen. We halen de gasten op bij een verzamelpunt of het hotel, rijden van ceremonie naar feest en brengen iedereen na het laatste nummer weer veilig terug.',
			'tekst_2'       => 'Zo wordt onze bus jullie trouwbus: niemand hoeft de Bob te zijn en iedereen kan proosten op het bruidspaar. Aan boord zorgt het gastvrouwke voor de drankjes, en met professionele verlichting en een goede geluidsinstallatie zit de feeststemming er al in voordat jullie gasten binnen zijn. Binnen 1 werkdag heb je een vaste all-in prijs, zodat het vervoer meteen in de trouwbegroting staat.',
			'knop'          => thema_link( 'Bekijk onze bussen', $bussen ),
			'beelden'       => array_values( array_filter( array( $b['gasten-stappen-in-lekker-geel-busje'], $b['gastheer-deelt-drankjes-uit-aan-de-bar'], $b['proosten-in-het-gangpad-van-de-bus'] ) ) ),
			'achtergrond'   => 'standaard',
			'anker'         => 'bruidspaar',
		),
		array(
			'acf_fc_layout' => 'opdrachten',
			'eyebrow'       => 'Op jullie trouwdag',
			'kop'           => 'Trouwvervoer voor *al jullie gasten*',
			'intro'         => 'Van de eerste gast die instapt tot de laatste die thuis wordt gebracht: zo regelen we het vervoer op jullie dag.',
			'items'         => array(
				array( 'soort' => 'Verzamelpunt', 'titel' => 'Gasten ophalen', 'tekst' => 'Bij het hotel, een station of een centraal punt. Zo komt iedereen tegelijk en op tijd aan bij de ceremonie.' ),
				array( 'soort' => 'Onderweg', 'titel' => 'Van ceremonie naar feest', 'tekst' => 'Liggen de ceremonie en het feest op verschillende plekken? De bus rijdt alle gasten in één keer door, terwijl jullie op de foto gaan.' ),
				array( 'soort' => 'Pendel', 'titel' => 'Daggasten en avondgasten', 'tekst' => 'Vervoer voor de daggasten overdag en voor de avondgasten later op de dag. Of daggasten tussendoor naar het hotel: we rijden zo vaak als nodig.' ),
				array( 'soort' => 'Na het feest', 'titel' => 'Veilig naar huis', 'tekst' => 'Iedereen kan proosten op het bruidspaar. Na het laatste nummer brengen we de gasten naar huis of naar het hotel.' ),
				array( 'soort' => 'Grote bruiloft', 'titel' => 'Meerdere bussen', 'tekst' => 'Meer dan 88 gasten? Dan rijden er meerdere bussen tegelijk. Reserveer op tijd, want trouwdata zitten vroeg vol.' ),
				array( 'soort' => 'Later', 'titel' => 'Huwelijksjubileum', 'tekst' => 'Zilveren of gouden bruiloft? Dan halen we de hele familie op voor het feest, en brengen we iedereen weer thuis.' ),
			),
			'noot'          => '',
			'achtergrond'   => 'paars',
			'anker'         => 'trouwdag',
		),
		array(
			'acf_fc_layout' => 'route',
			'eyebrow'       => 'Voorbeelddag',
			'kop'           => 'Zo kan jullie *trouwdag* eruitzien',
			'intro'         => 'Zo ziet het vervoer op een trouwdag er bijvoorbeeld uit. Elke bruiloft is anders: vertel ons jullie planning, dan rijden wij de route.',
			'stappen'       => array(
				array( 'tijd' => '13:00', 'icoon' => 'bus', 'titel' => 'Gasten ophalen', 'tekst' => 'De bus haalt de daggasten op bij het hotel of een verzamelpunt. Iedereen in zijn mooiste outfit, niemand die een parkeerplek hoeft te zoeken.' ),
				array( 'tijd' => '14:00', 'icoon' => 'hart', 'titel' => 'De ceremonie', 'tekst' => 'Alle gasten zitten op tijd. De bus wacht tot het jawoord is gegeven en de felicitaties achter de rug zijn.' ),
				array( 'tijd' => '16:00', 'icoon' => 'glas', 'titel' => 'Door naar de receptie', 'tekst' => 'Op naar de feestlocatie. Terwijl jullie op de foto gaan, proosten de gasten onderweg alvast op het bruidspaar.' ),
				array( 'tijd' => '18:00', 'icoon' => 'bestek', 'titel' => 'Het diner', 'tekst' => 'Samen aan tafel. De bus brengt tussendoor gasten naar het hotel als dat nodig is.' ),
				array( 'tijd' => '20:30', 'icoon' => 'muziek', 'titel' => 'Avondgasten erbij', 'tekst' => 'We halen de avondgasten op bij een verzamelpunt, zodat ze precies op tijd zijn voor de openingsdans.' ),
				array( 'tijd' => 'Einde avond', 'icoon' => 'huis', 'titel' => 'Iedereen veilig thuis', 'tekst' => 'Na het laatste nummer staat de bus klaar en brengen we de gasten naar huis of naar het hotel.' ),
			),
			'slot'          => '<p>Organiseer je eerst nog het <a href="' . esc_url( $vrijgezel ) . '">vrijgezellenfeest</a>? Ook dan rijden we graag. We rijden door heel Nederland en daarbuiten.</p>',
			'knop'          => $naar_offerte,
			'achtergrond'   => 'standaard',
			'anker'         => 'voorbeelddag',
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
			'eyebrow'       => 'Voor de trouwdag',
			'kop'           => 'Alles voor een *zorgeloze trouwdag*',
			'intro'         => 'Een checklist voor het bruidspaar en tips voor de ceremoniemeester en de getuigen. Zo weet iedereen wat er wanneer moet gebeuren.',
			'keuze'         => array(),
			'onderwerp'     => $trouwen ? (int) $trouwen->term_id : 0,
			'aantal'        => 3,
			'knop'          => $trouwen ? thema_link( 'Alle artikelen over trouwen', get_category_link( $trouwen ) ) : '',
			'achtergrond'   => 'standaard',
			'anker'         => 'organiseren',
		),
		$faq,
		$offerte,
	), $id );

	$duim = $b['feest-met-microfoons-en-bloemenkransen'] ?? 0;
	if ( $duim && ! has_post_thumbnail( $id ) ) {
		set_post_thumbnail( $id, $duim );
	}
	clean_post_cache( $id );
	return array( 'pagina' => $id );
}

/**
 * De zes artikelen. Algemeen gehouden: geen plaatsnamen of namen van zaken.
 */
function thema_bruiloft_artikelen() {
	$landing    = home_url( '/gelegenheden/bruiloft/' );
	$checklist  = thema_bruiloft_link( 'checklist-bruiloft' );
	$getuige    = thema_bruiloft_link( 'getuige-bruiloft-taken-speech' );
	$cm         = thema_bruiloft_link( 'ceremoniemeester-taken' );
	$ideeen     = thema_bruiloft_link( 'bruiloft-ideeen' );
	$kosten     = thema_bruiloft_link( 'wat-kost-een-bruiloft' );
	$jubileum   = thema_bruiloft_link( 'huwelijksjubileum-vieren' );
	$vrijgezel  = home_url( '/gelegenheden/vrijgezellenfeest/' );

	return array(

		/* 1. Checklist. */
		array(
			'slug'         => 'checklist-bruiloft',
			'titel'        => 'Checklist bruiloft: wat regel je wanneer?',
			'seo_titel'    => 'Checklist bruiloft: wat regel je wanneer? | Lekker Geel Busje',
			'samenvatting' => 'De complete checklist voor jullie bruiloft: wat regel je een jaar, een half jaar, een maand en een week van tevoren? Met een lijst om zelf af te vinken.',
			'beeld'        => 'gastvrouw-verwelkomt-gasten-bij-de-bus',
			'kort'         => "Een jaar van tevoren: datum, budget, gastenlijst en de trouwlocatie.\nEen half jaar van tevoren: fotograaf, muziek, kleding en vervoer voor de gasten.\nDe laatste maand: draaiboek, tafelschikking en alles doorgeven aan de ceremoniemeester.",
			'faq_kop'      => 'Vragen over de *planning*',
			'offerte_kop'  => 'Vervoer voor jullie gasten *geregeld?*',
			'vragen'       => array(
				array( 'Hoe lang van tevoren begin je met het plannen van een bruiloft?', 'De meeste stellen beginnen ongeveer een jaar van tevoren. Populaire trouwlocaties, fotografen en vervoer op vrijdagen en zaterdagen in het voorjaar en de zomer zijn vaak ruim van tevoren volgeboekt.' ),
				array( 'Wat regel je als eerste?', 'De datum, het budget en een eerste gastenlijst. Daarmee weet je hoe groot de locatie moet zijn en wat je per onderdeel kunt uitgeven.' ),
				array( 'Wat is het verschil tussen daggasten en avondgasten?', 'Daggasten zijn er de hele dag bij, vaak vanaf de ceremonie tot en met het diner. Avondgasten komen alleen naar het feest. Vermeld het duidelijk op de uitnodiging, zodat iedereen weet wanneer hij verwacht wordt.' ),
			),
			'blokken'      => array(
				array(
					'acf_fc_layout' => 'checklist',
					'eyebrow'       => 'Checklist',
					'kop'           => 'Vink het *zelf af*',
					'intro'         => 'Je vinkjes blijven op dit apparaat bewaard, dus kom gerust later terug om verder te gaan.',
					'items'         => array(
						array( 'wanneer' => 'Een jaar van tevoren', 'taak' => 'Datum kiezen en het budget afspreken', 'link' => '' ),
						array( 'wanneer' => 'Een jaar van tevoren', 'taak' => 'Eerste gastenlijst maken, met daggasten en avondgasten', 'link' => '' ),
						array( 'wanneer' => 'Een jaar van tevoren', 'taak' => 'Trouwlocatie en ceremonie vastleggen', 'link' => '' ),
						array( 'wanneer' => 'Negen maanden van tevoren', 'taak' => 'Getuigen en ceremoniemeester vragen', 'link' => '' ),
						array( 'wanneer' => 'Negen maanden van tevoren', 'taak' => 'Fotograaf en muziek boeken', 'link' => '' ),
						array( 'wanneer' => 'Een half jaar van tevoren', 'taak' => 'Vervoer voor de gasten aanvragen', 'link' => array( 'title' => 'Vraag je prijs aan', 'url' => '#offerte', 'target' => '' ) ),
						array( 'wanneer' => 'Een half jaar van tevoren', 'taak' => 'Trouwkleding uitzoeken', 'link' => '' ),
						array( 'wanneer' => 'Vier maanden van tevoren', 'taak' => 'Uitnodigingen versturen', 'link' => '' ),
						array( 'wanneer' => 'Twee maanden van tevoren', 'taak' => 'Ringen, bloemen en taart regelen', 'link' => '' ),
						array( 'wanneer' => 'Een maand van tevoren', 'taak' => 'Tafelschikking en dagplanning maken', 'link' => '' ),
						array( 'wanneer' => 'Twee weken van tevoren', 'taak' => 'Draaiboek delen met de ceremoniemeester', 'link' => '' ),
						array( 'wanneer' => 'De week ervoor', 'taak' => 'Tijden en adressen bevestigen bij alle leveranciers', 'link' => '' ),
						array( 'wanneer' => 'Op de dag zelf', 'taak' => 'Genieten. De gasten worden gereden.', 'link' => '' ),
					),
					'knop'          => '',
					'achtergrond'   => 'standaard',
					'anker'         => 'afvinken',
				),
			),
			'tekst'        => <<<HTML
<p>Jullie gaan trouwen. Gefeliciteerd! Tussen het aanzoek en het jawoord zit een hoop regelwerk, en dat gaat het makkelijkst als je weet wat je wanneer doet. Met deze checklist plan je de bruiloft stap voor stap, van een jaar van tevoren tot de dag zelf. Onderaan staat dezelfde lijst om zelf af te vinken.</p>

<h2>Een jaar van tevoren</h2>
<p>Dit is het moment voor de grote beslissingen. Wat jullie nu vastleggen, bepaalt de rest.</p>
<ul>
<li><strong>Datum kiezen.</strong> Vrijdag is de populairste trouwdag, en mei, juni en september de populairste maanden. Kies je zo'n datum, wacht dan niet te lang met vastleggen.</li>
<li><strong>Budget afspreken.</strong> Hoeveel willen jullie uitgeven, en betaalt er familie mee? Lees ook <a href="{$kosten}">wat een bruiloft kost</a>.</li>
<li><strong>Gastenlijst maken.</strong> Maak meteen onderscheid tussen daggasten en avondgasten. Het aantal bepaalt hoe groot de locatie moet zijn.</li>
<li><strong>Trouwlocatie en ceremonie vastleggen.</strong> Trouwen jullie op het gemeentehuis, op een trouwlocatie of in de kerk? En liggen ceremonie en feest op dezelfde plek of niet?</li>
</ul>

<h2>Negen maanden van tevoren</h2>
<ul>
<li><strong>Getuigen en ceremoniemeester vragen.</strong> Zij krijgen een belangrijke rol, dus geef ze de tijd. Tips voor hen staan in <a href="{$getuige}">getuige op een bruiloft</a> en <a href="{$cm}">de taken van de ceremoniemeester</a>.</li>
<li><strong>Fotograaf en muziek boeken.</strong> Goede fotografen, bands en dj's zitten in het trouwseizoen snel vol.</li>
<li><strong>Thema of stijl kiezen.</strong> Dat helpt bij alle keuzes die nog komen, van uitnodiging tot bloemen.</li>
</ul>

<h2>Een half jaar van tevoren</h2>
<ul>
<li><strong>Vervoer voor de gasten regelen.</strong> Liggen ceremonie en feest op verschillende plekken, of wil je dat niemand na het feest nog hoeft te rijden? Met een <a href="{$landing}">partybus voor je bruiloft</a> rijden alle gasten samen, met chauffeur en gastvrouwke aan boord. Binnen 1 werkdag heb je een vaste all-in prijs.</li>
<li><strong>Trouwkleding uitzoeken.</strong> Reken op meerdere passessies, zeker bij maatwerk.</li>
<li><strong>Overnachting regelen</strong> voor gasten die van ver komen, bijvoorbeeld een blok kamers in een hotel in de buurt.</li>
</ul>

<h2>Vier maanden van tevoren</h2>
<ul>
<li><strong>Uitnodigingen versturen,</strong> met een duidelijke datum waarop iedereen laat weten of hij komt. Zet erbij of iemand daggast of avondgast is, en hoe het vervoer geregeld is.</li>
<li><strong>Menu proeven</strong> en de catering vastleggen.</li>
</ul>

<h2>Twee maanden van tevoren</h2>
<ul>
<li><strong>Ringen, bloemen en taart regelen.</strong></li>
<li><strong>Openingsdans kiezen en oefenen,</strong> als jullie die willen.</li>
<li><strong>Speeches afstemmen:</strong> wie spreekt er, en wanneer?</li>
</ul>

<h2>De laatste maand</h2>
<ul>
<li><strong>Tafelschikking en dagplanning maken.</strong> Zet per onderdeel de tijd, de plek en wie het aanspreekpunt is.</li>
<li><strong>Draaiboek delen met de ceremoniemeester,</strong> zodat jullie op de dag zelf nergens meer aan hoeven te denken.</li>
<li><strong>Tijden bevestigen</strong> bij de locatie, de fotograaf, de muziek en het vervoer.</li>
</ul>

<h2>Op de dag zelf</h2>
<p>Geef je telefoon aan de ceremoniemeester en geniet. Alles is geregeld: de gasten worden gereden, de ceremoniemeester bewaakt de tijd en jullie hoeven alleen nog ja te zeggen.</p>
HTML,
		),

		/* 2. Getuige. */
		array(
			'slug'         => 'getuige-bruiloft-taken-speech',
			'titel'        => 'Getuige op een bruiloft: taken, speech en tips',
			'seo_titel'    => 'Getuige bruiloft: taken, speech en tips | Lekker Geel Busje',
			'samenvatting' => 'Ben je gevraagd als getuige? Wat je officieel en in de praktijk doet, hoe je een goede speech schrijft en wat je regelt voor het vrijgezellenfeest.',
			'beeld'        => 'proosten-in-het-gangpad-van-de-bus',
			'kort'         => "Officieel zet je een handtekening onder de huwelijksakte, met een geldig identiteitsbewijs.\nIn de praktijk ben je steun en toeverlaat van het bruidspaar, vaak ook voor het vrijgezellenfeest.\nEen goede speech duurt drie tot vijf minuten en eindigt met een toost.",
			'faq_kop'      => 'Vragen over de *getuige*',
			'offerte_kop'  => 'Vervoer voor de bruiloft *geregeld?*',
			'vragen'       => array(
				array( 'Wat doet een getuige bij een bruiloft?', 'Officieel bevestig je met je handtekening dat het huwelijk is gesloten. In de praktijk ben je ook de steun van het bruidspaar: je denkt mee in de voorbereiding, organiseert vaak het vrijgezellenfeest en houdt soms een speech.' ),
				array( 'Hoeveel getuigen mag je hebben?', 'Bij een huwelijk in Nederland zijn minimaal twee en maximaal vier getuigen. Ze moeten meerderjarig zijn en een geldig identiteitsbewijs meenemen.' ),
				array( 'Hoe lang duurt een speech op een bruiloft?', 'Drie tot vijf minuten is genoeg. Kort en persoonlijk werkt beter dan lang en volledig. Eindig met een toost op het bruidspaar.' ),
			),
			'blokken'      => array(),
			'tekst'        => <<<HTML
<p>Je bent gevraagd als getuige. Een eer, want het bruidspaar kiest daar mensen voor die ze het meest vertrouwen. Maar wat doe je eigenlijk als getuige? Hier lees je wat je officieel moet doen, wat er in de praktijk van je verwacht wordt en hoe je een speech schrijft waar het bruidspaar nog lang over praat.</p>

<h2>Wat doet een getuige officieel?</h2>
<p>Bij het sluiten van het huwelijk zet je je handtekening onder de huwelijksakte. Daarmee bevestig je dat het huwelijk volgens de regels is gesloten. In Nederland zijn er minimaal twee en maximaal vier getuigen, die meerderjarig moeten zijn. Neem op de dag een geldig identiteitsbewijs mee, want daar wordt om gevraagd. Het bruidspaar geeft je gegevens vooraf door aan de gemeente.</p>

<h2>Wat doet een getuige in de praktijk?</h2>
<ul>
<li><strong>Meedenken in de voorbereiding.</strong> Een luisterend oor bij keuzes en twijfels, en hulp bij klusjes als de uitnodigingen of de bedankjes.</li>
<li><strong>Het vrijgezellenfeest organiseren.</strong> Dat valt vaak onder de getuigen. Tips vind je bij ons <a href="{$vrijgezel}">vrijgezellenfeest</a>.</li>
<li><strong>Steun op de dag zelf.</strong> Zakdoekjes, een oplader, een pleister en een rustig moment als het bruidspaar dat nodig heeft.</li>
<li><strong>Samenwerken met de ceremoniemeester.</strong> Die bewaakt de planning, jij het bruidspaar. Lees ook <a href="{$cm}">de taken van de ceremoniemeester</a>.</li>
</ul>

<h2>Zo schrijf je een goede speech</h2>
<p>Een speech hoort er bij veel getuigen bij. Met deze opbouw kom je er altijd uit:</p>
<ol>
<li><strong>Stel jezelf kort voor.</strong> Wie ben je en hoe ken je het bruidspaar? Eén of twee zinnen.</li>
<li><strong>Vertel één goed verhaal.</strong> Een herinnering die laat zien wie de bruid of bruidegom is. Eén verhaal raakt meer dan tien losse anekdotes.</li>
<li><strong>Maak de brug naar het stel.</strong> Wat zie je als ze samen zijn? Waarom passen ze zo goed bij elkaar?</li>
<li><strong>Sluit af met een wens en een toost.</strong> Vraag iedereen het glas te heffen op het bruidspaar.</li>
</ol>
<p>Een paar tips: houd het op drie tot vijf minuten, oefen hardop, en laat grappen weg die alleen een paar mensen in de zaal snappen. Gênante verhalen over exen horen niet in een trouwspeech. Spreek met de ceremoniemeester af wanneer je aan de beurt bent.</p>

<h2>Wat neem je mee op de trouwdag?</h2>
<ul>
<li>Je identiteitsbewijs, voor de handtekening.</li>
<li>Je speech, op papier of op kaartjes.</li>
<li>Een klein noodpakket: zakdoekjes, pleisters, veiligheidsspelden en een powerbank.</li>
<li>De ringen, als dat met het bruidspaar zo is afgesproken.</li>
</ul>

<h2>Na afloop</h2>
<p>Zorg dat het bruidspaar aan het eind van de avond nergens meer aan hoeft te denken. Rijden de gasten met een <a href="{$landing}">partybus voor de bruiloft</a> naar huis, dan is dat al geregeld. Help eventueel met het verzamelen van cadeaus en kaarten, zodat die veilig bij het bruidspaar terechtkomen.</p>
HTML,
		),

		/* 3. Ceremoniemeester. */
		array(
			'slug'         => 'ceremoniemeester-taken',
			'titel'        => 'Ceremoniemeester: taken en tips voor een soepele trouwdag',
			'seo_titel'    => 'Ceremoniemeester: taken en tips | Lekker Geel Busje',
			'samenvatting' => 'Wat doet een ceremoniemeester op een bruiloft? Alle taken voor, tijdens en na de trouwdag, plus tips om de planning te bewaken zonder dat iemand het merkt.',
			'beeld'        => 'gastheer-en-gastvrouw-voor-de-gele-bus',
			'kort'         => "De ceremoniemeester is het aanspreekpunt van de dag, zodat het bruidspaar nergens aan hoeft te denken.\nMet een draaiboek met tijden, adressen en telefoonnummers houd je overzicht.\nSpreek vooraf met alle leveranciers af wie wat doet, ook met het vervoer.",
			'faq_kop'      => 'Vragen over de *ceremoniemeester*',
			'offerte_kop'  => 'Vervoer op de trouwdag *geregeld?*',
			'vragen'       => array(
				array( 'Wat doet een ceremoniemeester op een bruiloft?', 'Hij of zij is het aanspreekpunt van de dag. De ceremoniemeester bewaakt de planning, stemt af met de leveranciers, kondigt speeches en momenten aan en lost problemen op voordat het bruidspaar ze merkt.' ),
				array( 'Wie vraag je als ceremoniemeester?', 'Iemand die rustig blijft, goed kan organiseren en de gasten een beetje kent. Vaak is het een goede vriend, een familielid of een collega. Het mag ook een professional zijn.' ),
				array( 'Is de ceremoniemeester hetzelfde als de getuige?', 'Nee. De getuige tekent de huwelijksakte en steunt het bruidspaar, de ceremoniemeester regelt de dag. Het kan dezelfde persoon zijn, maar het is veel werk voor één persoon.' ),
			),
			'blokken'      => array(),
			'tekst'        => <<<HTML
<p>Een goede ceremoniemeester merk je bijna niet. Alles loopt op tijd, iedereen weet waar hij moet zijn en het bruidspaar hoeft de hele dag nergens aan te denken. Ben jij gevraagd voor deze rol? Hier lees je welke taken erbij horen, voor, tijdens en na de trouwdag.</p>

<h2>Voor de trouwdag</h2>
<ul>
<li><strong>Kennismaken met het bruidspaar.</strong> Wat vinden ze belangrijk, wat willen ze per se niet, en welke verrassingen mogen er wel of niet zijn?</li>
<li><strong>Het draaiboek maken of aanvullen.</strong> Per onderdeel de tijd, de plek, het adres en het aanspreekpunt. Begin met de <a href="{$checklist}">checklist voor de bruiloft</a> als het bruidspaar die nog niet heeft.</li>
<li><strong>Contact met alle leveranciers.</strong> Locatie, catering, fotograaf, muziek en vervoer krijgen jouw nummer als aanspreekpunt.</li>
<li><strong>Speeches en optredens verzamelen.</strong> Wie wil iets zeggen of doen, hoe lang duurt het en wanneer past het in het programma?</li>
<li><strong>Gasten informeren.</strong> Wat is de dresscode, waar is het verzamelpunt en hoe is het vervoer geregeld?</li>
</ul>

<h2>Op de trouwdag</h2>
<ul>
<li><strong>Als eerste aanwezig.</strong> Check de zaal, de muziek en of alles klaarstaat.</li>
<li><strong>De tijd bewaken.</strong> Seintjes aan de fotograaf, de keuken en de bus. Uitloop is normaal, maar houd de grote lijnen vast.</li>
<li><strong>Aankondigen.</strong> De binnenkomst van het bruidspaar, de speeches, de taart en de openingsdans.</li>
<li><strong>Problemen oplossen.</strong> Een gast die de weg kwijt is, een leverancier die te laat is: jij regelt het, het bruidspaar merkt er niets van.</li>
<li><strong>De gasten op tijd in de bus.</strong> Rijdt er vervoer van ceremonie naar feest, geef dan een seintje als het tijd is om in te stappen.</li>
</ul>

<h2>Na afloop</h2>
<ul>
<li><strong>Afsluiten met de locatie.</strong> Spullen van het bruidspaar, cadeaus en kaarten veilig meenemen.</li>
<li><strong>Gasten naar huis.</strong> Controleer of iedereen vervoer heeft. Met een <a href="{$landing}">partybus voor de bruiloft</a> staat de bus op een afgesproken tijd klaar en hoeft niemand meer te rijden.</li>
</ul>

<h2>Tips van een ervaren ceremoniemeester</h2>
<ul>
<li><strong>Houd het draaiboek op één pagina,</strong> met de telefoonnummers bovenaan.</li>
<li><strong>Werk met een assistent,</strong> bijvoorbeeld een van de getuigen. Lees ook wat <a href="{$getuige}">de getuige</a> doet.</li>
<li><strong>Plan buffers in.</strong> Elke overgang kost meer tijd dan je denkt, zeker met een grote groep gasten.</li>
<li><strong>Eet en drink zelf ook.</strong> Het is een lange dag.</li>
<li><strong>Geniet mee.</strong> Je bent ook gast, en het bruidspaar heeft je niet voor niets gevraagd.</li>
</ul>
HTML,
		),

		/* 4. Ideeën. */
		array(
			'slug'         => 'bruiloft-ideeen',
			'titel'        => 'Bruiloft ideeën: 20 tips voor een persoonlijke trouwdag',
			'seo_titel'    => 'Bruiloft ideeën: 20 tips voor jullie trouwdag | Lekker Geel Busje',
			'samenvatting' => 'Twintig ideeën om jullie bruiloft persoonlijk te maken, van de ceremonie en het diner tot het feest, spelletjes voor de gasten en een zorgeloze thuisreis.',
			'beeld'        => 'feest-met-microfoons-en-bloemenkransen',
			'kort'         => "Persoonlijke details maken meer indruk dan een groot budget.\nBetrek de gasten met een spel of opdracht tijdens het diner of het feest.\nRegel de thuisreis, dan kan iedereen tot het laatste nummer blijven.",
			'faq_kop'      => 'Vragen over *ideeën*',
			'offerte_kop'  => 'Ideeën klaar? *Wij rijden de gasten.*',
			'vragen'       => array(
				array( 'Hoe maak je een bruiloft persoonlijk?', 'Met details die over jullie gaan: jullie eigen geloften, een gerecht uit jullie eerste vakantie, een nummer met een verhaal. Kleine keuzes maken meer indruk dan dure aankleding.' ),
				array( 'Welke spelletjes kun je doen op een bruiloft?', 'Een quiz over het bruidspaar, een fotojacht voor de gasten of het schoenenspel waarbij het bruidspaar vragen beantwoordt door een schoen omhoog te houden. Houd ze kort en tussen de onderdelen door.' ),
				array( 'Hoe zorg je dat gasten tot het eind blijven?', 'Met een goed feest en een zorgeloze thuisreis. Als gasten niet zelf hoeven te rijden, blijven ze langer en kunnen ze gewoon meeproosten.' ),
			),
			'blokken'      => array(),
			'tekst'        => <<<HTML
<p>Een bruiloft wordt pas echt jullie bruiloft met de details die over jullie gaan. Dat hoeft niet duur te zijn. Hier vind je twintig ideeën, verdeeld over de dag, om jullie trouwdag persoonlijk te maken en de gasten erbij te betrekken.</p>

<h2>De ceremonie</h2>
<ul>
<li><strong>Eigen geloften:</strong> een paar zinnen die je zelf schrijft, raken meer dan elke standaardtekst.</li>
<li><strong>Een vriend als trouwambtenaar:</strong> bij veel gemeenten kun je iemand voor één dag laten beëdigen als buitengewoon ambtenaar.</li>
<li><strong>Muziek met een verhaal:</strong> het nummer van jullie eerste date, live gespeeld door een vriend.</li>
<li><strong>Een moment voor wie er niet bij kan zijn:</strong> een kaars of een foto voor dierbaren die er niet meer zijn.</li>
</ul>

<h2>De receptie en het diner</h2>
<ul>
<li><strong>Een gerecht met een verhaal:</strong> iets van jullie eerste vakantie of het favoriete gerecht van oma.</li>
<li><strong>Tafels met namen:</strong> vernoem de tafels naar plekken waar jullie samen zijn geweest.</li>
<li><strong>Een gastenboek met een draai:</strong> een fotoboek, een wensboom of kaartjes met advies voor het huwelijk.</li>
<li><strong>Persoonlijke bedankjes:</strong> iets kleins dat bij jullie past, zoals een zakje zaden of een eigen etiket op een flesje.</li>
</ul>

<h2>Spelletjes voor de gasten</h2>
<ul>
<li><strong>De schoenenquiz:</strong> het bruidspaar zit rug aan rug en houdt bij elke vraag de schoen omhoog van wie het antwoord is.</li>
<li><strong>Quiz over het bruidspaar:</strong> per tafel een kaart met vragen, de winnende tafel mag als eerste naar het buffet.</li>
<li><strong>Fotojacht:</strong> een lijstje foto-opdrachten op elke tafel, de mooiste foto's komen in het album.</li>
<li><strong>Raad het nummer:</strong> de band of dj speelt een paar seconden van een nummer, de gasten raden welk liedje het is.</li>
</ul>

<h2>Het feest</h2>
<ul>
<li><strong>Een openingsdans die bij jullie past:</strong> romantisch, grappig of samen met de hele zaal. Oefen hem een paar keer, dan geniet je er meer van.</li>
<li><strong>Een verzoekjeslijst:</strong> vraag gasten bij het aanmelden om één nummer dat de dansvloer vol krijgt.</li>
<li><strong>Een late snack:</strong> rond elf uur iets hartigs maakt de avond nog langer.</li>
<li><strong>Een fotohoek:</strong> een achtergrond, wat attributen en een camera of telefoon op een statief.</li>
</ul>

<h2>Voor de gasten</h2>
<ul>
<li><strong>Een duidelijke dagplanning:</strong> op de uitnodiging of op een bord bij de ingang, zodat iedereen weet wat er komt.</li>
<li><strong>Overnachting in de buurt:</strong> een blok kamers voor gasten die van ver komen.</li>
<li><strong>Vervoer voor iedereen:</strong> met een <a href="{$landing}">partybus voor de bruiloft</a> rijden de gasten samen van ceremonie naar feest en na afloop naar huis. Niemand hoeft de Bob te zijn.</li>
<li><strong>Een verrassing van de getuigen:</strong> laat ruimte in het programma voor een speech of optreden. Tips voor hen staan in <a href="{$getuige}">getuige op een bruiloft</a>.</li>
</ul>
<p>Ideeën gekozen? Zet ze in de planning met onze <a href="{$checklist}">checklist voor de bruiloft</a>.</p>
HTML,
		),

		/* 5. Kosten. Cijfers gecheckt op 17 nov 2026. */
		array(
			'slug'         => 'wat-kost-een-bruiloft',
			'titel'        => 'Wat kost een bruiloft? Gemiddelde kosten en een begroting',
			'seo_titel'    => 'Wat kost een bruiloft? Gemiddelde kosten | Lekker Geel Busje',
			'samenvatting' => 'Wat kost een bruiloft gemiddeld, en waar gaat het geld naartoe? De kosten per onderdeel, van ceremonie tot vervoer, met tips om grip op de begroting te houden.',
			'beeld'        => 'gele-sfeerbus-in-de-avond-op-het-plein',
			'kort'         => "Een bruiloft met ongeveer honderd gasten kost in 2026 al snel 15.000 tot 25.000 euro.\nLocatie en catering zijn de grootste kostenpost.\nVraag overal vaste prijzen, dan blijft de begroting kloppen.",
			'faq_kop'      => 'Vragen over de *kosten*',
			'offerte_kop'  => 'Vervoer in de begroting? *Vraag je prijs aan.*',
			'vragen'       => array(
				array( 'Wat kost een bruiloft gemiddeld?', 'Voor een bruiloft met ongeveer honderd gasten moet je in 2026 rekenen op zo\'n 15.000 tot 25.000 euro, exclusief huwelijksreis. Het bedrag hangt vooral af van het aantal gasten en de locatie.' ),
				array( 'Wat kost trouwen op het gemeentehuis?', 'Dat verschilt per gemeente. In 2026 kost een ceremonie op vrijdagmiddag gemiddeld zo\'n 500 euro en op zaterdag bijna 800 euro. Op doordeweekse dagen kan het goedkoper, soms zelfs gratis.' ),
				array( 'Wat kost vervoer voor de gasten?', 'Dat hangt af van het aantal gasten, de afstand en hoe lang de bus erbij blijft. Bij Lekker Geel Busje heb je binnen 1 werkdag een vaste all-in prijs, dus je weet vooraf precies wat het kost.' ),
			),
			'blokken'      => array(),
			'tekst'        => <<<HTML
<p>Trouwen kan bijna gratis, maar een complete bruiloft met familie en vrienden is een flinke uitgave. Hoeveel precies, hangt af van jullie keuzes. Hier lees je wat een bruiloft gemiddeld kost, waar het geld naartoe gaat en hoe je grip houdt op de begroting.</p>

<h2>Wat kost een bruiloft gemiddeld?</h2>
<p>Voor een gemiddelde Nederlandse bruiloft met ongeveer honderd gasten moet je in 2026 rekenen op zo'n 15.000 tot 25.000 euro, exclusief huwelijksreis (<a href="https://www.welingelichtekringen.nl/samenleving/hoe-duur-is-trouwen" target="_blank" rel="noopener">Welingelichte Kringen</a>). Volgens <a href="https://financialfocus.abnamro.nl/expertise/huwelijksmarkt-familie-tradities-en-prijskaartjes/" target="_blank" rel="noopener">ABN AMRO</a> lag het gemiddelde in 2024 al op ruim 23.000 euro, zo'n 30 procent meer dan vijf jaar eerder.</p>

<h2>Waar gaat het geld naartoe?</h2>
<p>Een indicatie van de kosten per onderdeel, gebaseerd op cijfers die <a href="https://financialfocus.abnamro.nl/expertise/huwelijksmarkt-familie-tradities-en-prijskaartjes/" target="_blank" rel="noopener">ABN AMRO</a> in 2024 publiceerde:</p>
<ul>
<li><strong>Locatie met catering:</strong> rond de 6.000 euro, bij zo'n dertig daggasten en ruim tachtig feestgasten. Dit is bijna altijd de grootste post.</li>
<li><strong>Trouwjurk:</strong> ruim 1.400 euro. <strong>Pak:</strong> rond de 975 euro.</li>
<li><strong>Fotograaf:</strong> rond de 1.400 euro.</li>
<li><strong>Ringen:</strong> rond de 800 euro.</li>
<li><strong>Muziek:</strong> een dj kost van 500 euro tot ruim 2.000 euro.</li>
<li><strong>Ceremonie:</strong> afhankelijk van de gemeente. In 2026 betaal je voor een ceremonie op vrijdagmiddag gemiddeld zo'n 506 euro en op zaterdag bijna 794 euro (<a href="https://www.welingelichtekringen.nl/samenleving/wat-kost-trouwen-van-gratis-jawoord-tot-bruiloft-van-eur25000" target="_blank" rel="noopener">Welingelichte Kringen</a>).</li>
<li><strong>Huwelijksreis:</strong> vaak nog eens een paar duizend euro.</li>
</ul>
<p>Vergeet daarnaast de kleinere posten niet: uitnodigingen, bloemen, taart, haar en make-up, bedankjes en vervoer.</p>

<h2>Vergeet het vervoer niet</h2>
<p>Vervoer staat zelden in een lijstje met gemiddelden, maar het hoort wel in de begroting. Liggen de ceremonie en het feest op verschillende plekken, of willen jullie dat gasten na het feest niet meer zelf hoeven te rijden? Met een <a href="{$landing}">partybus voor de bruiloft</a> rijden alle gasten samen, met chauffeur en gastvrouwke aan boord. Binnen 1 werkdag heb je een vaste all-in prijs, zodat je precies weet welk bedrag je opneemt. Komen er meer dan 88 gasten, dan rijden er meerdere bussen.</p>

<h2>Zo houd je grip op de begroting</h2>
<ul>
<li><strong>Begin met de gastenlijst.</strong> Het aantal gasten bepaalt de locatie, de catering en dus het grootste deel van de kosten.</li>
<li><strong>Kies jullie prioriteiten.</strong> Wat is voor jullie het belangrijkst: het eten, de muziek, de foto's? Geef daar meer aan uit en bespaar op de rest.</li>
<li><strong>Vraag vaste prijzen.</strong> Laat leveranciers een prijs geven die alles omvat, zodat je achteraf niet verrast wordt.</li>
<li><strong>Kijk naar de dag.</strong> Een doordeweekse dag of een datum buiten het hoogseizoen is vaak goedkoper.</li>
<li><strong>Houd een buffer aan</strong> van ongeveer tien procent voor onverwachte kosten.</li>
</ul>
<p>Begroting rond? Zet alles in de planning met onze <a href="{$checklist}">checklist voor de bruiloft</a>.</p>

<h2>Bronnen</h2>
<ul>
<li><a href="https://www.welingelichtekringen.nl/samenleving/hoe-duur-is-trouwen" target="_blank" rel="noopener">Welingelichte Kringen: hoe duur is trouwen? (2026)</a></li>
<li><a href="https://www.welingelichtekringen.nl/samenleving/wat-kost-trouwen-van-gratis-jawoord-tot-bruiloft-van-eur25000" target="_blank" rel="noopener">Welingelichte Kringen: wat kost trouwen, van gratis jawoord tot bruiloft (2026)</a></li>
<li><a href="https://financialfocus.abnamro.nl/expertise/huwelijksmarkt-familie-tradities-en-prijskaartjes/" target="_blank" rel="noopener">ABN AMRO Financial Focus: huwelijksmarkt, familietradities en prijskaartjes (2024)</a></li>
</ul>
<p><em>Bijgewerkt op 17 november 2026. Prijzen veranderen per jaar en verschillen per regio en leverancier.</em></p>
HTML,
		),

		/* 6. Huwelijksjubileum. */
		array(
			'slug'         => 'huwelijksjubileum-vieren',
			'titel'        => 'Huwelijksjubileum vieren: van koperen tot gouden bruiloft',
			'seo_titel'    => 'Gouden, zilveren en koperen bruiloft | Lekker Geel Busje',
			'samenvatting' => 'Hoe heet 12,5, 25, 40 of 50 jaar getrouwd, en hoe vier je het? Alle bekende huwelijksjubilea op een rij, met ideeën voor een feest met de hele familie.',
			'beeld'        => 'gasten-lachen-aan-tafel-in-sfeerbus',
			'kort'         => "12,5 jaar is koperen, 25 jaar zilveren, 40 jaar robijnen en 50 jaar gouden.\nBij een groot jubileum komt vaak de hele familie samen, van kleinkinderen tot oude vrienden.\nRegel vervoer, dan kan iedereen meevieren zonder zelf te rijden.",
			'faq_kop'      => 'Vragen over het *jubileum*',
			'offerte_kop'  => 'Familie bij elkaar? *Wij rijden.*',
			'vragen'       => array(
				array( 'Hoe heet 40 jaar getrouwd?', 'Een robijnen huwelijk. De rode robijn staat voor liefde en passie, wat goed past bij veertig jaar samen.' ),
				array( 'Hoe heet 50 jaar getrouwd?', 'Een gouden bruiloft. Het is een van de belangrijkste huwelijksjubilea en wordt vaak groot gevierd met de hele familie.' ),
				array( 'Hoe vier je een gouden of zilveren bruiloft?', 'Met de mensen die ertoe doen: familie, vrienden en soms buren. Een diner, een feest of een dagje uit werkt goed. Regel vervoer, zodat ook oudere gasten en wie van ver komt makkelijk mee kunnen.' ),
			),
			'blokken'      => array(),
			'tekst'        => <<<HTML
<p>Een huwelijksjubileum is een mooi moment om stil te staan bij al die jaren samen, en om het te vieren met de mensen die erbij horen. Maar hoe heet 40 jaar getrouwd ook alweer? Hier vind je de bekendste huwelijksjubilea op een rij, met ideeën om ze te vieren.</p>

<h2>De bekendste huwelijksjubilea</h2>
<ul>
<li><strong>12,5 jaar:</strong> koperen bruiloft.</li>
<li><strong>25 jaar:</strong> zilveren bruiloft.</li>
<li><strong>40 jaar:</strong> robijnen bruiloft.</li>
<li><strong>45 jaar:</strong> saffieren bruiloft.</li>
<li><strong>50 jaar:</strong> gouden bruiloft.</li>
<li><strong>60 jaar:</strong> diamanten bruiloft.</li>
<li><strong>65 jaar:</strong> briljanten bruiloft.</li>
<li><strong>70 jaar:</strong> platina bruiloft.</li>
</ul>
<p>Over de bekendste namen bestaat weinig discussie: koper bij 12,5 jaar, zilver bij 25, goud bij 50 en diamant bij 60. Voor de jaren daartussen worden soms verschillende namen gebruikt.</p>

<h2>Koperen bruiloft: 12,5 jaar</h2>
<p>Halverwege het zilveren jubileum. Veel stellen vieren het met vrienden, vaak informeel: een etentje, een borrel of een feest in de tuin. Een mooi moment om de trouwdag nog eens over te doen, met de mensen die er toen ook bij waren.</p>

<h2>Zilveren bruiloft: 25 jaar</h2>
<p>Een kwart eeuw samen, en vaak het eerste jubileum dat echt groot wordt gevierd. De kinderen zijn groter, de vriendenkring is breed. Denk aan een feestavond, een diner met familie en vrienden of een dagje uit met iedereen die erbij hoort.</p>

<h2>Robijnen bruiloft: 40 jaar</h2>
<p>Veertig jaar getrouwd is een prestatie. Het feest is vaak iets rustiger dan bij 25 jaar, maar niet minder bijzonder. Een lunch of diner met de familie, met een speech van de kinderen, valt altijd goed.</p>

<h2>Gouden bruiloft: 50 jaar</h2>
<p>Een halve eeuw samen. Bij een gouden bruiloft komt vaak de hele familie samen, van kleinkinderen tot oude vrienden. Steeds meer stellen bereiken deze mijlpaal: volgens het CBS vierden in 2018 bijna 53.000 echtparen hun gouden huwelijk (<a href="https://gemeente.nu/bedrijfsvoering/burgerzaken/meer-dan-50-000-gouden-huwelijken" target="_blank" rel="noopener">Gemeente.nu</a>). Veel gemeenten sturen een felicitatie, en bij een diamanten of platina jubileum komt soms de burgemeester langs.</p>

<h2>Ideeën om het jubileum te vieren</h2>
<ul>
<li><strong>Een diner met de familie:</strong> met een speech van de kinderen of kleinkinderen.</li>
<li><strong>Een feestavond:</strong> met muziek uit het jaar van de bruiloft.</li>
<li><strong>Een dagje uit:</strong> naar een plek die belangrijk is voor het stel, met de hele familie.</li>
<li><strong>Een fotowand of diashow:</strong> met foto's van de trouwdag tot nu.</li>
<li><strong>Een boek met herinneringen:</strong> laat iedereen vooraf een verhaal of foto aanleveren.</li>
<li><strong>De trouwdag opnieuw:</strong> de geloften hernieuwen, met de familie erbij.</li>
</ul>

<h2>De hele familie erbij, zonder zorgen over de rit</h2>
<p>Bij een groot jubileum komen gasten vaak van ver, en zijn er oudere familieleden die liever niet in het donker rijden. Met een bus halen we de hele familie op, rijden we naar het restaurant of de feestlocatie en brengen we iedereen weer thuis. Het gastvrouwke zorgt aan boord voor de drankjes. Lees meer op onze pagina over een <a href="{$landing}">partybus voor je bruiloft</a>, die ook geldt voor een huwelijksjubileum.</p>
<p>Plannen jullie zelf een bruiloft? Bekijk dan onze <a href="{$checklist}">checklist voor de bruiloft</a> en <a href="{$ideeen}">20 ideeën voor een persoonlijke trouwdag</a>.</p>
HTML,
		),
	);
}

/**
 * Uitroltaak bruiloft-offertekop-v1: de offertekop direct in de opgeslagen blokken zetten en teruglezen.
 */
function thema_bruiloft_offertekop() {
	$post = get_page_by_path( 'bruiloft', OBJECT, 'gelegenheid' );
	if ( ! $post ) {
		return 'geen pagina';
	}
	$uit = array();
	foreach ( (array) get_post_meta( $post->ID, 'blokken', true ) as $i => $layout ) {
		if ( 'offerte' === $layout ) {
			$oud = get_post_meta( $post->ID, "blokken_{$i}_kop", true );
			update_post_meta( $post->ID, "blokken_{$i}_kop", 'Zeggen jullie ja? *Rijden wij de gasten.*' );
			$uit[] = array( 'blok' => $i, 'oud' => $oud, 'nu' => get_post_meta( $post->ID, "blokken_{$i}_kop", true ) );
		}
	}
	clean_post_cache( $post->ID );
	wp_cache_flush();
	return $uit;
}
