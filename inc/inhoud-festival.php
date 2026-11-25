<?php
/**
 * Festival (november 2026): de landingspagina /gelegenheden/festival/ met een agenda die Lekker Geel Busje zelf
 * beheert (blok Agenda), en vier artikelen in het onderwerp Festivals (/blog/onderwerp/festivals/).
 * Uitroltaken festival-blog-v1, festival-volgorde-v1 en festival-v1.
 *
 * Zoekwoorden (SE Ranking, NL, 17 nov 2026):
 * - Pagina: festivalbus 140 (34), bus naar festival 140 (33), vervoer zwarte cross 90 (6), partybus defqon 50 (6).
 * - Artikelen: festivalpaklijst 480 + paklijst festival 260 + festival checklist 140 (6 tot 7), gehoorbescherming
 *   festival 390 (20), festivalkleding 660 (12), hardstylefestivals 360 (11), festivals brabant 590 (20).
 *
 * Festivaldata 2027 gecheckt op 17 nov 2026 (festivalinfo.nl, festivalfans.nl en andere festivalsites).
 */

defined( 'ABSPATH' ) || exit;

function thema_festival_link( $slug ) {
	$p = get_page_by_path( $slug, OBJECT, 'post' );
	return $p ? get_permalink( $p ) : home_url( '/blog/' . $slug . '/' );
}

/**
 * Uitroltaak festival-blog-v1: onderwerp Festivals en de vier artikelen.
 */
function thema_festival_blog() {
	$uit  = array();
	$term = get_category_by_slug( 'festivals' );
	if ( $term ) {
		$cat = (int) $term->term_id;
	} else {
		$nieuw = wp_insert_term( 'Festivals', 'category', array(
			'slug'        => 'festivals',
			'description' => 'Alles voor een festivaldag met de groep: een paklijst, wat je aantrekt en de festivals waar we het vaakst naartoe rijden. Zo begint het feest al in de bus.',
		) );
		$cat = is_wp_error( $nieuw ) ? 0 : (int) $nieuw['term_id'];
	}
	$uit['onderwerp'] = $cat;
	$gel = get_page_by_path( 'festival', OBJECT, 'gelegenheid' );
	$gel = $gel ? (int) $gel->ID : 0;
	foreach ( thema_festival_artikelen() as $artikel ) {
		$uit[ $artikel['slug'] ] = thema_blog_artikel_plaatsen( $artikel, $cat, $gel );
	}
	delete_option( 'rewrite_rules' );
	return $uit;
}

/**
 * Uitroltaak festival-volgorde-v1: datums ruim in het verleden, de eerste drie staan op de landingspagina.
 */
function thema_festival_volgorde() {
	$uit      = array();
	$volgorde = array( 'festival-paklijst', 'hardstyle-festivals', 'festivals-brabant', 'festival-kleding' );
	$basis    = strtotime( '2026-11-17 09:30:00' );
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
 * De festivals in de agenda. Lekker Geel Busje beheert dit daarna zelf in het blok Agenda op de festivalpagina.
 */
function thema_festival_agenda_items() {
	$link = fn( $url ) => $url ? array( 'title' => 'Bekijk de website', 'url' => $url, 'target' => '_blank' ) : '';
	return array(
		array( 'titel' => 'De Vrienden van Amstel LIVE', 'plek' => 'Rotterdam Ahoy', 'start' => '20270114', 'eind' => '20270130', 'datum_tekst' => 'Diverse avonden in januari 2027', 'tekst' => 'De grootste kroeg van Nederland, met Nederlandstalige meezingers van begin tot eind. Met de hele groep heen en samen weer terug.', 'link' => '' ),
		array( 'titel' => 'Paaspop', 'plek' => 'Schijndel', 'start' => '20270326', 'eind' => '20270328', 'datum_tekst' => '', 'tekst' => 'De 50ste editie van het gezelligste festival van Brabant, in het paasweekend.', 'link' => $link( 'https://www.paaspop.nl' ) ),
		array( 'titel' => 'Intents Festival', 'plek' => 'Oisterwijk', 'start' => '20270604', 'eind' => '20270606', 'datum_tekst' => '', 'tekst' => 'Hardstyle en hardcore bij ons om de hoek: Intents is in onze eigen woonplaats.', 'link' => $link( 'https://www.intentsfestival.nl' ) ),
		array( 'titel' => 'Defqon.1', 'plek' => 'Biddinghuizen', 'start' => '20270624', 'eind' => '20270627', 'datum_tekst' => '', 'tekst' => 'Vier dagen hardstyle op de Holy Grounds. We brengen je groep tot aan het terrein.', 'link' => $link( 'https://www.defqon1.com' ) ),
		array( 'titel' => 'Zwarte Cross', 'plek' => 'Lichtenvoorde', 'start' => '20270715', 'eind' => '20270718', 'datum_tekst' => '', 'tekst' => 'Muziek, motorcross en complete waanzin in de Achterhoek. Een lange rit, dus een feestrit.', 'link' => $link( 'https://www.zwartecross.nl' ) ),
		array( 'titel' => 'Decibel Outdoor', 'plek' => 'Hilvarenbeek', 'start' => '20270827', 'eind' => '20270829', 'datum_tekst' => 'Eind augustus 2027', 'tekst' => 'Het hardstyle-feest waarmee de zomer eindigt, op een kwartiertje van onze bussen.', 'link' => $link( 'https://www.decibeloutdoor.com' ) ),
	);
}

/**
 * Uitroltaak festival-v1: de blokken van de landingspagina.
 */
function thema_plaats_festival() {
	if ( ! function_exists( 'update_field' ) ) {
		return 0;
	}
	$post = get_page_by_path( 'festival', OBJECT, 'gelegenheid' );
	if ( ! $post ) {
		return 0;
	}
	$id = (int) $post->ID;
	$b  = array();
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

	update_field( 'field_gel_ondertitel', 'Voor de deur opgehaald, voor de deur terug.', $id );
	thema_seo_titel_zetten( 'Festivalbus: met de partybus naar je festival | Lekker Geel Busje', $id );
	thema_seo_omschrijving_zetten( 'Met de hele groep naar Paaspop, Intents, Defqon.1 of Zwarte Cross? De festivalbus haalt je op, zet je af bij het terrein en brengt je thuis. Binnen 1 werkdag een vaste prijs.', $id );

	$reviews                = thema_blok_reviews();
	$reviews['kop']         = 'Zij stapten al in, *dit vonden ze ervan*';
	$reviews['weergave']    = 'compact';
	$reviews['achtergrond'] = 'geel';

	$lijst = function_exists( 'thema_faq_per_pagina' ) ? ( thema_faq_per_pagina()['gelegenheid/festival'] ?? array() ) : array();
	$eigen = array(
		array( 'Wat kost een festivalbus?', 'Dat hangt af van de grootte van de groep, de afstand naar het festival, de dag en hoe lang jullie blijven. Binnen 1 werkdag heb je een vaste all-in prijs, met chauffeur, gastvrouwke, licht en geluid, brandstof en btw erin. Deel je die door de groep, dan weet iedereen precies wat zijn deel is.' ),
	);
	$extra = array(
		array( 'Rijden jullie ook naar festivals ver weg of in het buitenland?', 'Ja. We rijden door heel Nederland en daarbuiten. Hoe langer de rit, hoe langer het feest in de bus.' ),
		array( 'Rijden jullie bij een meerdaags festival elke dag?', 'Dat kan. We brengen jullie op de eerste dag en halen jullie op de laatste dag op, of we rijden elke dag heen en terug. Zet het in je aanvraag, dan maken we er één vaste prijs van.' ),
	);
	$vragen        = function_exists( 'thema_faq_rijen' ) ? thema_faq_rijen( array_merge( $eigen, $lijst, $extra ) ) : array();
	$faq           = thema_blok_faq();
	$faq['kop']    = 'Vragen over de *festivalbus*';
	$faq['vragen'] = $vragen;

	$offerte                = thema_blok_offerte();
	$offerte['label']       = 'Binnen 1 werkdag je prijs';
	$offerte['achtergrond'] = 'geel';
	$offerte['kop']         = 'Tickets binnen? *Regel de bus.*';

	$festivals    = get_category_by_slug( 'festivals' );
	$naar_offerte = thema_link( 'Vraag je prijs aan', '#offerte' );

	update_field( 'field_blokken', array(
		array(
			'acf_fc_layout' => 'hero',
			'kop_1'         => 'Partybus naar je',
			'kop_2'         => 'festival',
			'plaatsen'      => '',
			'sub_1'         => 'Voor de deur opgehaald,',
			'sub_2'         => 'voor de deur terug.',
			'knop'          => $naar_offerte,
			'knop_tonen'    => 1,
			'toon_score'    => 1,
			'stijl'         => 'foto',
			'fotos'         => array_values( array_filter( array( $b['handen-in-de-lucht-in-de-gele-bus'], $b['feest-met-blauw-licht-in-de-sfeerbus'], $b['verlichte-sfeerbus-van-buiten'] ) ) ),
			'vorm'          => 'auto',
			'hoogte'        => 'compact',
			'anker'         => 'top',
		),
		array(
			'acf_fc_layout' => 'over_ons',
			'eyebrow'       => 'Festivalbus',
			'kop'           => 'Het festival begint *in de bus.*',
			'tekst_1'       => 'Met een groep naar een festival gaan is pas leuk als iedereen er tegelijk is. Geen gedoe met treinen die vol zitten, shuttles waar je uren op wacht of een parkeerplaats op een halfuur lopen. Met de festivalbus van Lekker Geel Busje halen we de hele groep op, zetten we jullie zo dicht mogelijk bij de ingang af en staan we na afloop op een afgesproken plek klaar.',
			'tekst_2'       => 'En de rit erheen is meteen de warming-up. Professionele verlichting, lasers, rook, microfoons en een geluidsinstallatie die er staat, allemaal inbegrepen. Zet jullie eigen playlist op, bestel een drankje bij het gastvrouwke en kom aan in de stemming. Na afloop hoeft niemand nog te rijden.',
			'knop'          => thema_link( 'Bekijk onze bussen', $bussen ),
			'beelden'       => array_values( array_filter( array( $b['gasten-delen-shotjes-uit-in-de-bus'], $b['feest-met-armen-omhoog-in-de-bus'], $b['discolicht-en-feest-in-de-sfeerbus'] ) ) ),
			'achtergrond'   => 'standaard',
			'anker'         => 'festivalbus',
		),
		array(
			'acf_fc_layout'      => 'agenda',
			'eyebrow'            => 'Festivalagenda',
			'kop'                => 'Hier rijden we *veel naartoe*',
			'tekst'              => 'Een greep uit de festivals waar groepen het vaakst met ons naartoe gaan. Staat jouw festival er niet tussen? We rijden overal heen, dus vraag het gerust.',
			'knop'               => $naar_offerte,
			'items'              => thema_festival_agenda_items(),
			'verlopen_verbergen' => 1,
			'achtergrond'        => 'standaard',
			'anker'              => 'agenda',
		),
		array(
			'acf_fc_layout' => 'route',
			'eyebrow'       => 'Voorbeelddag',
			'kop'           => 'Zo ziet jullie *festivaldag* eruit',
			'intro'         => 'Zo ziet een festivaldag met de bus er bijvoorbeeld uit. Vertel ons welk festival en hoe laat, dan rijden wij de route.',
			'stappen'       => array(
				array( 'tijd' => '10:30', 'icoon' => 'bus', 'titel' => 'Instappen', 'tekst' => 'De bus haalt de groep op, op één plek of bij meerdere opstapplekken. Outfits gecheckt, tickets op de telefoon, muziek aan.' ),
				array( 'tijd' => '11:00', 'icoon' => 'muziek', 'titel' => 'Warming-up onderweg', 'tekst' => 'Licht, lasers en jullie eigen playlist. Het gastvrouwke zorgt voor de drankjes, de chauffeur voor de route.' ),
				array( 'tijd' => '12:00', 'icoon' => 'route', 'titel' => 'Afzetten bij het terrein', 'tekst' => 'Zo dicht mogelijk bij de ingang. We spreken meteen de plek af waar we jullie straks weer ophalen.' ),
				array( 'tijd' => 'Festival', 'icoon' => 'feest', 'titel' => 'Feesten', 'tekst' => 'Jullie genieten van het festival. De bus staat op tijd klaar, zonder dat iemand op de klok hoeft te letten.' ),
				array( 'tijd' => '23:30', 'icoon' => 'glas', 'titel' => 'Ophalen', 'tekst' => 'Op de afgesproken plek staat de bus klaar. Geen rij voor de shuttle, geen zoektocht naar de auto.' ),
				array( 'tijd' => 'Daarna', 'icoon' => 'huis', 'titel' => 'Iedereen veilig thuis', 'tekst' => 'Op de terugweg komen de verhalen, en iedereen wordt netjes afgezet.' ),
			),
			'slot'          => '<p>We rijden door heel Nederland en daarbuiten, ook naar meerdaagse festivals.</p>',
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
			'eyebrow'       => 'Voor de festivalganger',
			'kop'           => 'Klaar voor *het festival*',
			'intro'         => 'Een paklijst om af te vinken en de festivals waar je dit jaar bij moet zijn. Zo ben je goed voorbereid.',
			'keuze'         => array(),
			'onderwerp'     => $festivals ? (int) $festivals->term_id : 0,
			'aantal'        => 3,
			'knop'          => $festivals ? thema_link( 'Alle artikelen over festivals', get_category_link( $festivals ) ) : '',
			'achtergrond'   => 'standaard',
			'anker'         => 'tips',
		),
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
 * De vier artikelen.
 */
function thema_festival_artikelen() {
	$landing  = home_url( '/gelegenheden/festival/' );
	$paklijst = thema_festival_link( 'festival-paklijst' );
	$hard     = thema_festival_link( 'hardstyle-festivals' );
	$brabant  = thema_festival_link( 'festivals-brabant' );
	$kleding  = thema_festival_link( 'festival-kleding' );

	return array(

		/* 1. Paklijst. */
		array(
			'slug'         => 'festival-paklijst',
			'titel'        => 'Festivalpaklijst: wat neem je mee naar een festival?',
			'seo_titel'    => 'Festivalpaklijst: wat neem je mee? | Lekker Geel Busje',
			'samenvatting' => 'De complete festivalpaklijst voor een dagfestival en een meerdaags festival met camping. Met een checklist om af te vinken, zodat je niets vergeet.',
			'beeld'        => 'selfie-met-lichtstaven-in-de-bus',
			'kort'         => "Voor een dagfestival: ticket, legitimatie, oordoppen, een powerbank en een regenjas.\nGa je kamperen? Neem dan ook een tent, slaapzak en iets warms voor de nacht mee.\nCheck altijd de huisregels van het festival: niet alles mag mee naar binnen.",
			'faq_kop'      => 'Vragen over de *paklijst*',
			'offerte_kop'  => 'Tas gepakt? *Wij rijden.*',
			'vragen'       => array(
				array( 'Wat neem je mee naar een festival?', 'Je ticket, een geldige legitimatie, een betaalpas of telefoon, oordoppen, een powerbank, een regenponcho en zonnebrand. Laat waardevolle spullen thuis en check welke tassen en flessen het festival toestaat.' ),
				array( 'Heb je oordoppen nodig op een festival?', 'Ja, neem altijd gehoorbescherming mee. Het geluid op een festival is uren achter elkaar hard. Goede festivaloordoppen dempen het geluid zonder dat de muziek dof klinkt.' ),
				array( 'Mag je eten en drinken meenemen naar een festival?', 'Dat verschilt per festival. Bij veel festivals mag je een lege fles of een klein pakje drinken mee, maar geen eigen eten. Kijk vooraf op de site van het festival wat er mag.' ),
			),
			'blokken'      => array(
				array(
					'acf_fc_layout' => 'checklist',
					'eyebrow'       => 'Paklijst',
					'kop'           => 'Vink het *zelf af*',
					'intro'         => 'Je vinkjes blijven op dit apparaat bewaard, dus pak gerust in een paar rondes in.',
					'items'         => array(
						array( 'wanneer' => 'Altijd mee', 'taak' => 'Ticket op je telefoon of geprint', 'link' => '' ),
						array( 'wanneer' => 'Altijd mee', 'taak' => 'Geldige legitimatie', 'link' => '' ),
						array( 'wanneer' => 'Altijd mee', 'taak' => 'Betaalpas of telefoon om te betalen', 'link' => '' ),
						array( 'wanneer' => 'Altijd mee', 'taak' => 'Oordoppen', 'link' => '' ),
						array( 'wanneer' => 'Altijd mee', 'taak' => 'Powerbank met kabel', 'link' => '' ),
						array( 'wanneer' => 'Bij elk weer', 'taak' => 'Regenponcho', 'link' => '' ),
						array( 'wanneer' => 'Bij elk weer', 'taak' => 'Zonnebrand en een zonnebril', 'link' => '' ),
						array( 'wanneer' => 'Handig', 'taak' => 'Klein heuptasje of crossbody', 'link' => '' ),
						array( 'wanneer' => 'Handig', 'taak' => 'Lege drinkfles, als het festival dat toestaat', 'link' => '' ),
						array( 'wanneer' => 'Meerdaags', 'taak' => 'Tent, slaapzak en matje', 'link' => '' ),
						array( 'wanneer' => 'Meerdaags', 'taak' => 'Warme trui voor de nacht en droge sokken', 'link' => '' ),
						array( 'wanneer' => 'Vooraf', 'taak' => 'Vervoer met de groep geregeld', 'link' => array( 'title' => 'Vraag je prijs aan', 'url' => '#offerte', 'target' => '' ) ),
					),
					'knop'          => '',
					'achtergrond'   => 'standaard',
					'anker'         => 'afvinken',
				),
			),
			'tekst'        => <<<HTML
<p>Niks zo vervelend als halverwege de dag ontdekken dat je telefoon leeg is of dat je oordoppen nog thuis op tafel liggen. Met deze festivalpaklijst neem je precies mee wat je nodig hebt, voor een dagfestival en voor een meerdaags festival met camping. Onderaan staat de lijst om zelf af te vinken.</p>

<h2>Altijd mee</h2>
<ul>
<li><strong>Ticket:</strong> op je telefoon, met een screenshot of print als back-up.</li>
<li><strong>Legitimatie:</strong> bij veel festivals verplicht, zeker met een leeftijdsgrens.</li>
<li><strong>Betaalpas of telefoon:</strong> de meeste festivals werken met pinnen of een betaalpolsbandje.</li>
<li><strong>Oordoppen:</strong> uren hard geluid is slecht voor je oren. Goede festivaloordoppen dempen het volume zonder dat de muziek dof klinkt.</li>
<li><strong>Powerbank:</strong> foto's, de groepsapp en je ticket kosten batterij.</li>
</ul>

<h2>Voor elk weer</h2>
<ul>
<li><strong>Regenponcho:</strong> licht, klein en een stuk handiger dan een paraplu.</li>
<li><strong>Zonnebrand en zonnebril:</strong> ook als het bewolkt begint.</li>
<li><strong>Petje of hoedje:</strong> tegen de zon en makkelijk terug te vinden in een menigte.</li>
<li><strong>Schoenen waar je de hele dag op kunt staan:</strong> een festivalterrein is groot. Meer tips lees je in <a href="{$kleding}">wat trek je aan naar een festival</a>.</li>
</ul>

<h2>Handig om mee te nemen</h2>
<ul>
<li><strong>Klein tasje of heuptas:</strong> handen vrij, spullen dichtbij.</li>
<li><strong>Lege drinkfles:</strong> bij veel festivals kun je gratis water tappen. Check de regels.</li>
<li><strong>Zakdoekjes en handgel:</strong> dank je later.</li>
<li><strong>Afspreekplek met de groep:</strong> geen spul, wel belangrijk. Spreek een vaste plek af voor als iemand kwijt is.</li>
</ul>

<h2>Voor een meerdaags festival</h2>
<ul>
<li><strong>Tent, slaapzak en matje,</strong> en oefen het opzetten thuis.</li>
<li><strong>Warme trui en droge sokken:</strong> de nachten op een camping zijn kouder dan je denkt.</li>
<li><strong>Toilettas:</strong> tandenborstel, droogshampoo en een klein handdoekje.</li>
<li><strong>Zaklamp of hoofdlamp</strong> om je tent terug te vinden.</li>
</ul>

<h2>Wat laat je thuis?</h2>
<p>Waardevolle sieraden, grote tassen en spullen die het festival niet toestaat, zoals glas en eigen drank. Elk festival heeft eigen huisregels, dus kijk vooraf op de site.</p>

<h2>En het vervoer?</h2>
<p>Met een groep naar een festival is het vervoer de grootste puzzel. Met de <a href="{$landing}">festivalbus van Lekker Geel Busje</a> stapt de hele groep samen in, zetten we jullie zo dicht mogelijk bij de ingang af en staan we na afloop op een afgesproken plek klaar. Zo hoeft niemand na een lange festivaldag nog te rijden.</p>
HTML,
		),

		/* 2. Hardstylefestivals. */
		array(
			'slug'         => 'hardstyle-festivals',
			'titel'        => 'Hardstylefestivals in Nederland: de grootste op een rij',
			'seo_titel'    => 'Hardstylefestivals in Nederland | Lekker Geel Busje',
			'samenvatting' => 'De bekendste hardstyle- en hardcorefestivals in Nederland en net over de grens, van Defqon.1 en Intents tot Decibel Outdoor en Qlimax. Plus tips om er met de groep te komen.',
			'beeld'        => 'feest-met-blauw-licht-in-de-sfeerbus',
			'kort'         => "De grote hardstylefestivals zijn in de zomer, met Qlimax als hoogtepunt binnen in het najaar.\nTickets gaan vaak maanden van tevoren in de verkoop en zijn snel weg.\nRegel het vervoer voor de groep op tijd, zeker bij festivals buiten de Randstad.",
			'faq_kop'      => 'Vragen over *hardstylefestivals*',
			'offerte_kop'  => 'Hardstyle met de groep? *Wij rijden.*',
			'vragen'       => array(
				array( 'Wat is het grootste hardstylefestival van Nederland?', 'Defqon.1 is het bekendste en grootste hardstylefestival van Nederland. Het vindt elk jaar in juni plaats in Biddinghuizen, met meerdere dagen en podia.' ),
				array( 'Wanneer zijn de hardstylefestivals?', 'De meeste buitenfestivals vallen tussen juni en eind augustus. In het najaar en de winter zijn er grote binnenevenementen, zoals Qlimax.' ),
				array( 'Hoe kom je met een groep naar een hardstylefestival?', 'Met een eigen bus rijdt de hele groep samen, word je dicht bij de ingang afgezet en sta je na afloop niet in de rij voor een shuttle. Vraag op tijd een prijs aan, de festivalweekenden zitten snel vol.' ),
			),
			'blokken'      => array(),
			'tekst'        => <<<HTML
<p>Nederland is het land van de hardstyle. Elk jaar komen tienduizenden mensen van over de hele wereld naar onze festivals voor de harde beats, de vuurshows en de eindshows. Hier vind je de bekendste hardstyle- en hardcorefestivals op een rij, met tips om er met de groep te komen.</p>

<h2>Defqon.1</h2>
<p>Het grootste hardstylefestival van Nederland, elk jaar in juni op het festivalterrein in Biddinghuizen. Meerdere dagen, tientallen podia en een eindshow waar mensen een jaar naar uitkijken. De 2027-editie staat gepland voor 24 tot en met 27 juni.</p>

<h2>Intents Festival</h2>
<p>Intents in Oisterwijk is bekend om zijn enorme podia en producties, met de nadruk op de hardere stijlen. Het festival is meestal rond het Pinksterweekend, in 2027 van 4 tot en met 6 juni. Voor ons is het een feest om de hoek: onze bussen staan in Oisterwijk.</p>

<h2>Decibel Outdoor</h2>
<p>De Loudest City on Earth in Hilvarenbeek sluit eind augustus de hardstyle-zomer af. Drie dagen harde muziek, met een grote camping erbij.</p>

<h2>Dominator</h2>
<p>Het hardcorefestival in de zomer, aan het water in Brabant. Bekend om de Dominator-thema's en een publiek dat van uptempo en hardcore houdt.</p>

<h2>Harmony of Hardcore</h2>
<p>Een buitenfestival in Brabant voor de liefhebbers van hardcore en hardstyle, met een sfeer die meer clubavond dan massafestival is.</p>

<h2>Qlimax</h2>
<p>Het grootste binnenevenement van de hardstyle, elk najaar in een stadion. Eén avond, één verhaal en een show die je moet zien.</p>

<h2>Reverze</h2>
<p>Net over de grens in Antwerpen, en het eerste grote hardstyle-evenement van het jaar. Ook hier rijden we je groep graag naartoe.</p>

<h2>Met de groep naar een hardstylefestival</h2>
<ul>
<li><strong>Koop je tickets op tijd.</strong> De grote festivals zijn vaak in een paar uur uitverkocht.</li>
<li><strong>Spreek een vaste plek af</strong> waar je elkaar terugvindt, want het bereik op een vol terrein is vaak slecht.</li>
<li><strong>Neem oordoppen mee</strong> en drink genoeg water. Lees ook onze <a href="{$paklijst}">festivalpaklijst</a>.</li>
<li><strong>Regel het vervoer samen.</strong> Met de <a href="{$landing}">festivalbus</a> rijdt de hele groep tegelijk, zet de bus jullie dicht bij de ingang af en staat hij na afloop klaar. Geen rij voor de shuttle, geen lange wandeling naar de parkeerplaats.</li>
</ul>
<p>Festivaldata kunnen veranderen. Check altijd de officiële site van het festival voordat je plannen maakt. Op zoek naar festivals dichter bij huis? Lees dan <a href="{$brabant}">festivals in Brabant</a>.</p>
HTML,
		),

		/* 3. Festivals in Brabant. */
		array(
			'slug'         => 'festivals-brabant',
			'titel'        => 'Festivals in Brabant: van Paaspop tot Decibel Outdoor',
			'seo_titel'    => 'Festivals in Brabant: de leukste op een rij | Lekker Geel Busje',
			'samenvatting' => 'De leukste festivals in Brabant door het jaar heen, van Paaspop en Breda Jazz tot Intents en Decibel Outdoor. Met tips om er met de groep naartoe te gaan.',
			'beeld'        => 'handen-in-de-lucht-in-de-gele-bus',
			'kort'         => "Het Brabantse festivaljaar begint met Paaspop en eindigt met Decibel Outdoor eind augustus.\nVan hardstyle tot jazz: er is voor elke groep een festival.\nWe rijden er vanuit Oisterwijk naartoe, en ook naar alles daarbuiten.",
			'faq_kop'      => 'Vragen over *festivals in Brabant*',
			'offerte_kop'  => 'Festival gekozen? *Wij rijden.*',
			'vragen'       => array(
				array( 'Wat is het grootste festival van Brabant?', 'Paaspop in Schijndel is een van de bekendste en gezelligste festivals van Brabant, elk jaar in het paasweekend. In 2027 viert het festival zijn 50ste editie.' ),
				array( 'Welke festivals zijn er in Brabant in de zomer?', 'Onder meer Intents in Oisterwijk, Awakenings Festival en Decibel Outdoor in Hilvarenbeek en Dominator. Daarnaast zijn er stadsfestivals zoals Breda Jazz en Jazz in Duketown.' ),
				array( 'Hoe kom je met een groep naar een festival in Brabant?', 'Met een eigen bus haal je de hele groep op, ook op meerdere opstapplekken, en word je dicht bij het terrein afgezet. Na afloop staat de bus op een afgesproken plek klaar.' ),
			),
			'blokken'      => array(),
			'tekst'        => <<<HTML
<p>Brabant is een feestprovincie, en dat zie je aan de festivalagenda. Van het eerste festival van het jaar met Pasen tot de hardstyle-afsluiter eind augustus: er is altijd wel iets te doen. Hier vind je de leukste festivals in Brabant op een rij.</p>

<h2>Paaspop</h2>
<p>Het gezelligste festival van Brabant, elk jaar in het paasweekend in Schijndel. Een mix van pop, rock, Nederlandstalig en dance, met een sfeer waar je vrienden voor het leven maakt. In 2027 is de 50ste editie, van 26 tot en met 28 maart.</p>

<h2>Breda Jazz Festival</h2>
<p>Rond Hemelvaart is de binnenstad van Breda een groot jazzfeest, met optredens op pleinen en in kroegen. Ideaal voor een dagje uit met de groep.</p>

<h2>Jazz in Duketown</h2>
<p>In het Pinksterweekend staat de Bossche binnenstad in het teken van jazz, blues en soul, op podia door de hele stad.</p>

<h2>Intents Festival</h2>
<p>Hardstyle en hardcore in Oisterwijk, meestal rond Pinksteren. In 2027 van 4 tot en met 6 juni. Bij ons om de hoek.</p>

<h2>Awakenings Festival</h2>
<p>Een van de grootste technofestivals ter wereld, elke zomer in Hilvarenbeek.</p>

<h2>Dominator</h2>
<p>Het hardcorefestival aan het water, in de zomer in Brabant.</p>

<h2>Decibel Outdoor</h2>
<p>De Loudest City on Earth in Hilvarenbeek sluit eind augustus de festivalzomer af. Drie dagen hardstyle en hardcore.</p>

<h2>GLOW</h2>
<p>Geen muziekfestival, wel een feest: in november verandert Eindhoven een week lang in een lichtkunstroute. Een mooi avondje uit met de groep.</p>

<h2>Met de groep naar een Brabants festival</h2>
<p>Onze bussen staan in Oisterwijk, dus de meeste Brabantse festivals liggen voor ons om de hoek. Met de <a href="{$landing}">festivalbus</a> halen we de hele groep op, zetten we jullie dicht bij het terrein af en brengen we iedereen na afloop thuis. Pak je spullen met onze <a href="{$paklijst}">festivalpaklijst</a>, en houd je van hardere muziek, lees dan ook <a href="{$hard}">hardstylefestivals in Nederland</a>.</p>
<p>Festivaldata kunnen veranderen. Check altijd de officiële site van het festival voordat je plannen maakt.</p>
HTML,
		),

		/* 4. Festivalkleding. */
		array(
			'slug'         => 'festival-kleding',
			'titel'        => 'Festivalkleding: wat trek je aan naar een festival?',
			'seo_titel'    => 'Festivalkleding: wat trek je aan? | Lekker Geel Busje',
			'samenvatting' => 'Wat trek je aan naar een festival? Tips voor festivalkleding bij zon, regen en kou, de juiste schoenen en een groepsoutfit waarmee je elkaar terugvindt.',
			'beeld'        => 'tropisch-themafeest-in-de-sfeerbus',
			'kort'         => "Kleed je in laagjes: warm overdag, fris in de avond.\nKies schoenen waar je de hele dag op kunt staan en die vies mogen worden.\nEen groepsoutfit of dezelfde kleur maakt het makkelijk om elkaar terug te vinden.",
			'faq_kop'      => 'Vragen over *festivalkleding*',
			'offerte_kop'  => 'Outfit klaar? *Wij rijden.*',
			'vragen'       => array(
				array( 'Wat trek je aan naar een festival?', 'Kleding in laagjes waar je je de hele dag prettig in voelt, schoenen die tegen een stootje kunnen en iets tegen de regen. Voeg een opvallend item toe, dan ben je ook op de foto\'s goed te zien.' ),
				array( 'Welke schoenen draag je op een festival?', 'Comfortabele sneakers die vies mogen worden, of laarzen als het nat is. Nieuwe schoenen kun je beter thuislaten: na een dag staan en dansen krijg je anders blaren.' ),
				array( 'Wat trek je aan naar een festival als het regent?', 'Een regenponcho, laarzen of waterdichte schoenen en kleding die snel droogt. Neem droge sokken mee voor de terugweg.' ),
			),
			'blokken'      => array(),
			'tekst'        => <<<HTML
<p>Je ticket is binnen, de groepsapp ontploft, en dan de grote vraag: wat trek je aan? Festivalkleding moet er goed uitzien, maar vooral een hele dag staan, dansen en wisselend weer overleven. Met deze tips zit je goed, wat het weer ook doet.</p>

<h2>Kleed je in laagjes</h2>
<p>Overdag in de zon is het warm, 's avonds kan het flink afkoelen. Begin met een luchtig shirt, neem een vest of overshirt mee en knoop een trui om je middel. Zo trek je aan of uit wat je nodig hebt.</p>

<h2>Schoenen die tegen een stootje kunnen</h2>
<ul>
<li><strong>Comfortabele sneakers</strong> die vies mogen worden.</li>
<li><strong>Laarzen</strong> als er regen voorspeld is of het terrein modderig is.</li>
<li><strong>Geen nieuwe schoenen:</strong> na een dag staan en dansen krijg je blaren.</li>
</ul>

<h2>Bij zon</h2>
<ul>
<li>Een pet of hoedje, een zonnebril en luchtige kleding.</li>
<li>Smeer je in met zonnebrand, ook als het bewolkt is.</li>
</ul>

<h2>Bij regen</h2>
<ul>
<li>Een regenponcho is lichter en handiger dan een jas.</li>
<li>Kleding die snel droogt, en droge sokken voor de terugweg.</li>
</ul>

<h2>Een groepsoutfit</h2>
<p>Met de hele groep in dezelfde kleur, hetzelfde shirt of een opvallend accessoire vind je elkaar sneller terug op een vol terrein. En het ziet er op de groepsfoto goed uit. Denk aan felgekleurde zonnebrillen, dezelfde bandana of een thema voor de hele groep.</p>

<h2>Praktisch</h2>
<ul>
<li><strong>Een heuptas of crossbody</strong> voor je telefoon, pas en oordoppen.</li>
<li><strong>Kleding met zakken die dicht kunnen,</strong> tegen zakkenrollers.</li>
<li><strong>Laat dure sieraden thuis.</strong></li>
</ul>
<p>Wat er verder in je tas moet, lees je in onze <a href="{$paklijst}">festivalpaklijst</a>. En de outfit van de hele groep komt het best tot zijn recht in de <a href="{$landing}">festivalbus</a>, met licht, lasers en jullie eigen playlist op weg naar het terrein.</p>
HTML,
		),
	);
}
