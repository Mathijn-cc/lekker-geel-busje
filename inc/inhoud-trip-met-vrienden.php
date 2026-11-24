<?php
/**
 * Trip met vrienden (november 2026): de landingspagina /gelegenheden/trip-met-vrienden/ en zes artikelen in het
 * onderwerp Groepsuitjes (/blog/onderwerp/groepsuitjes/). Uitroltaken trip-blog-v1, trip-volgorde-v1 en trip-v1.
 *
 * Zoekwoorden (SE Ranking, NL, 17 nov 2026):
 * - Pagina: uitje met vrienden 320 (6), groepsuitje 440 (12), vriendengroep uitje 170 (13). "Trip met vrienden": geen volume.
 * - Onderwerp Groepsuitjes: groepsuitjes 440 (22).
 * - Artikelen: uitje met vrienden 320 + uitjes met vrienden 320 + leuke dingen om te doen met vrienden 390 + wat te doen
 *   met vrienden 320 (6 tot 22), kroegentocht 590 (8) + pubcrawl 320 (23), vriendinnenuitje 320 (5) + dagje uit
 *   vriendinnen 320 (13) + meiden uitje 260 (12), mannenuitje 210 (6), vriendenweekend 390 (5) + weekendje weg met
 *   vrienden 260 (9), familiedag 880 (21) + familieuitje 320 (15) + familiedag ideeen 320 (28).
 */

defined( 'ABSPATH' ) || exit;

function thema_trip_link( $slug ) {
	$p = get_page_by_path( $slug, OBJECT, 'post' );
	return $p ? get_permalink( $p ) : home_url( '/blog/' . $slug . '/' );
}

/**
 * Uitroltaak trip-blog-v1: onderwerp Groepsuitjes en de zes artikelen.
 */
function thema_trip_blog() {
	$uit  = array();
	$term = get_category_by_slug( 'groepsuitjes' );
	if ( $term ) {
		$cat = (int) $term->term_id;
	} else {
		$nieuw = wp_insert_term( 'Groepsuitjes', 'category', array(
			'slug'        => 'groepsuitjes',
			'description' => 'Ideeën en tips voor een uitje met vrienden, vriendinnen, de jongens of de hele familie. Van een kroegentocht tot een vriendenweekend: zo regel je het groepsuitje zonder gedoe.',
		) );
		$cat = is_wp_error( $nieuw ) ? 0 : (int) $nieuw['term_id'];
	}
	$uit['onderwerp'] = $cat;
	$gel = get_page_by_path( 'trip-met-vrienden', OBJECT, 'gelegenheid' );
	$gel = $gel ? (int) $gel->ID : 0;
	foreach ( thema_trip_artikelen() as $artikel ) {
		$uit[ $artikel['slug'] ] = thema_blog_artikel_plaatsen( $artikel, $cat, $gel );
	}
	delete_option( 'rewrite_rules' );
	return $uit;
}

/**
 * Uitroltaak trip-volgorde-v1: datums ruim in het verleden (de site rekent in UTC), de eerste drie staan op de landingspagina.
 */
function thema_trip_volgorde() {
	$uit      = array();
	$volgorde = array(
		'uitje-met-vrienden-ideeen',
		'kroegentocht-organiseren',
		'familiedag-organiseren',
		'vriendinnenuitje-ideeen',
		'vriendenweekend-organiseren',
		'mannenuitje-ideeen',
	);
	$basis = strtotime( '2026-11-17 10:30:00' );
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
 * Uitroltaak trip-v1: de blokken van de landingspagina.
 */
function thema_plaats_trip() {
	if ( ! function_exists( 'update_field' ) ) {
		return 0;
	}
	$post = get_page_by_path( 'trip-met-vrienden', OBJECT, 'gelegenheid' );
	if ( ! $post ) {
		return 0;
	}
	$id = (int) $post->ID;
	$b  = array();
	foreach ( array(
		'groepsselfie-met-lichtstaven-in-partybus',
		'handen-in-de-lucht-in-de-gele-bus',
		'vriendengroep-aan-tafel-in-sfeerbus',
		'gasten-stappen-in-lekker-geel-busje',
		'meezingen-met-microfoon-in-sfeerbus',
		'drankjes-halen-aan-de-bar-in-de-bus',
	) as $naam ) {
		$b[ $naam ] = thema_beeld_op_naam( $naam );
	}
	$locaties = get_page_by_path( 'locaties' );
	$locaties = $locaties ? get_permalink( $locaties ) : home_url( '/locaties/' );
	$regels   = get_page_by_path( 'huisregels' );
	$regels   = $regels ? get_permalink( $regels ) : home_url( '/huisregels/' );
	$bussen   = get_page_by_path( 'onze-bussen' );
	$bussen   = $bussen ? get_permalink( $bussen ) : home_url( '/onze-bussen/' );

	update_field( 'field_gel_ondertitel', 'Met de hele vriendengroep op pad, het feest begint in de bus.', $id );
	thema_seo_titel_zetten( 'Partybus voor een uitje met vrienden | Lekker Geel Busje', $id );
	thema_seo_omschrijving_zetten( 'Groepsuitje met vrienden, familie of de club? De partybus haalt iedereen op, rijdt van activiteit naar stad en brengt je thuis. Binnen 1 werkdag een vaste prijs.', $id );

	$reviews                = thema_blok_reviews();
	$reviews['kop']         = 'Zij stapten al in, *dit vonden ze ervan*';
	$reviews['weergave']    = 'compact';
	$reviews['achtergrond'] = 'geel';

	$lijst = function_exists( 'thema_faq_per_pagina' ) ? ( thema_faq_per_pagina()['gelegenheid/trip-met-vrienden'] ?? array() ) : array();
	$eigen = array(
		array( 'Wat kost een partybus voor een uitje met vrienden?', 'Dat hangt af van de grootte van de groep, de afstand en rijtijd, de dag en eventuele wachttijd. Binnen 1 werkdag heb je een vaste all-in prijs, met chauffeur, gastvrouwke, licht en geluid, brandstof en btw erin. Deel je die door de groep, dan weet iedereen vooraf precies wat zijn deel is.' ),
		array( 'Vanaf hoeveel personen is de bus geschikt?', 'De bus komt het best tot zijn recht vanaf zo\'n 35 personen. Met minder mensen kan het ook, alleen worden de kosten per persoon dan wat hoger. Elke bus heeft minstens 39 zitplaatsen en in onze verlengde bus gaan tot 88 personen mee. Met een grotere groep rijden er meerdere bussen.' ),
	);
	$extra = array(
		array( 'Kan de bus de hele dag bij ons blijven?', 'Ja. De bus wacht tijdens jullie activiteit of komt op een afgesproken tijd terug. Zet het programma in je aanvraag, dan maken we er één route van met een vaste prijs.' ),
		array( 'Halen jullie iedereen op verschillende plekken op?', 'Dat kan. Geef de opstapplekken door in je aanvraag, dan halen we de hele groep op en brengen we iedereen na afloop weer terug.' ),
	);
	$vragen        = function_exists( 'thema_faq_rijen' ) ? thema_faq_rijen( array_merge( $eigen, $lijst, $extra ) ) : array();
	$faq           = thema_blok_faq();
	$faq['kop']    = 'Vragen over een partybus voor je *uitje met vrienden*';
	$faq['vragen'] = $vragen;

	$offerte                = thema_blok_offerte();
	$offerte['label']       = 'Binnen 1 werkdag je prijs';
	$offerte['achtergrond'] = 'geel';
	$offerte['kop']         = 'Plan gemaakt? *De bus staat klaar.*';

	$groepsuitjes = get_category_by_slug( 'groepsuitjes' );
	$naar_offerte = thema_link( 'Vraag je prijs aan', '#offerte' );

	update_field( 'field_blokken', array(
		array(
			'acf_fc_layout' => 'hero',
			'kop_1'         => 'Partybus voor je',
			'kop_2'         => 'uitje met vrienden',
			'plaatsen'      => '',
			'sub_1'         => 'Busje in, sfeertje aan,',
			'sub_2'         => 'met de hele vriendengroep.',
			'knop'          => $naar_offerte,
			'knop_tonen'    => 1,
			'toon_score'    => 1,
			'stijl'         => 'foto',
			'fotos'         => array_values( array_filter( array( $b['groepsselfie-met-lichtstaven-in-partybus'], $b['handen-in-de-lucht-in-de-gele-bus'], $b['vriendengroep-aan-tafel-in-sfeerbus'] ) ) ),
			'vorm'          => 'auto',
			'hoogte'        => 'compact',
			'anker'         => 'top',
		),
		array(
			'acf_fc_layout' => 'over_ons',
			'eyebrow'       => 'Voor de regelaar',
			'kop'           => 'Jij hebt het plan, *wij de bus.*',
			'tekst_1'       => 'In elke vriendengroep is er één die het regelt. Ben jij dat? Dan weet je hoe lastig het is om met een grote groep op pad te gaan: wie rijdt er, waar parkeer je, en hoe krijg je iedereen tegelijk op de juiste plek? Met Lekker Geel Busje regel je het vervoer in één keer. We halen de groep op, rijden van activiteit naar restaurant en de stad in, en brengen iedereen na afloop weer thuis.',
			'tekst_2'       => 'En de rit zelf is meteen een feest. In de bus zitten professionele verlichting, lasers, rook en een goede geluidsinstallatie, allemaal inbegrepen. Drankjes bestel je bij het gastvrouwke, de chauffeur houdt zijn aandacht bij de weg. Niemand hoeft de Bob te zijn en de hele groep blijft bij elkaar.',
			'knop'          => thema_link( 'Bekijk onze bussen', $bussen ),
			'beelden'       => array_values( array_filter( array( $b['gasten-stappen-in-lekker-geel-busje'], $b['meezingen-met-microfoon-in-sfeerbus'], $b['drankjes-halen-aan-de-bar-in-de-bus'] ) ) ),
			'achtergrond'   => 'standaard',
			'anker'         => 'regelaar',
		),
		array(
			'acf_fc_layout' => 'opdrachten',
			'eyebrow'       => 'Voor elke groep',
			'kop'           => 'Van vriendenclub tot *familiedag*',
			'intro'         => 'Een groepsuitje kan van alles zijn. Zolang jullie samen op pad gaan, rijden wij.',
			'items'         => array(
				array( 'soort' => 'Vrienden', 'titel' => 'Dagje uit met de groep', 'tekst' => 'Een activiteit, samen eten en daarna de stad in. Iedereen stapt samen in en samen weer uit.' ),
				array( 'soort' => 'Vriendinnen', 'titel' => 'Vriendinnenuitje', 'tekst' => 'Een middag proeven, shoppen of een workshop, met een feestrit als toetje. Het gastvrouwke zorgt voor de bubbels.' ),
				array( 'soort' => 'De jongens', 'titel' => 'Mannenuitje', 'tekst' => 'Karten, een brouwerij of een escape room, en daarna gewoon door. Niemand hoeft te loten wie er rijdt.' ),
				array( 'soort' => 'Avond uit', 'titel' => 'Kroegentocht', 'tekst' => 'We zetten jullie af in het uitgaansgebied en halen jullie op als de laatste kroeg sluit.' ),
				array( 'soort' => 'Jong en oud', 'titel' => 'Familiedag', 'tekst' => 'Opa, oma, neven en nichten in één bus. Zo is iedereen erbij, ook wie liever niet zelf rijdt.' ),
				array( 'soort' => 'Samen sterk', 'titel' => 'Club of vereniging', 'tekst' => 'Het jaarlijkse uitje van het elftal, de koor- of buurtvereniging. Eén vaste prijs, makkelijk te delen.' ),
			),
			'noot'          => '',
			'achtergrond'   => 'paars',
			'anker'         => 'groepen',
		),
		array(
			'acf_fc_layout' => 'route',
			'eyebrow'       => 'Voorbeelddag',
			'kop'           => 'Zo kan jullie *dagje uit* eruitzien',
			'intro'         => 'Zo ziet een dagje uit met de bus er bijvoorbeeld uit. Elke groep is anders: vertel ons jullie plan, dan rijden wij de route.',
			'stappen'       => array(
				array( 'tijd' => '12:00', 'icoon' => 'bus', 'titel' => 'Iedereen instappen', 'tekst' => 'De bus haalt de groep op, op één plek of bij meerdere opstapplekken. Muziek aan, eerste drankje, de dag is begonnen.' ),
				array( 'tijd' => '13:00', 'icoon' => 'groep', 'titel' => 'De activiteit', 'tekst' => 'Een workshop, een spel of iets actiefs met de hele groep. De bus wacht, of komt op een afgesproken tijd terug.' ),
				array( 'tijd' => '16:00', 'icoon' => 'beker', 'titel' => 'Proeven en proosten', 'tekst' => 'Door naar een proeverij of een terras. Proosten mag, want niemand hoeft nog te rijden.' ),
				array( 'tijd' => '18:30', 'icoon' => 'bestek', 'titel' => 'Samen eten', 'tekst' => 'Met de hele groep aan tafel. Geen gezoek naar parkeerplekken, geen groepjes die de weg kwijt zijn.' ),
				array( 'tijd' => '21:00', 'icoon' => 'muziek', 'titel' => 'De stad in', 'tekst' => 'Licht, lasers en rook aan voor de feestrit. De bus zet jullie af midden in het uitgaansleven.' ),
				array( 'tijd' => 'Einde avond', 'icoon' => 'huis', 'titel' => 'Iedereen veilig thuis', 'tekst' => 'Op de afgesproken tijd staat de bus klaar en wordt iedereen netjes afgezet.' ),
			),
			'slot'          => '<p>Nog op zoek naar een bestemming? Bij onze <a href="' . esc_url( $locaties ) . '">bestemmingen</a> vind je ideeën voor een dag uit met de groep. We rijden door heel Nederland en daarbuiten.</p>',
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
			'eyebrow'       => 'Voor de regelaar',
			'kop'           => 'Ideeën voor je *groepsuitje*',
			'intro'         => 'Leuke dingen om te doen met vrienden, tips voor een kroegentocht en alles voor een familiedag. Zo heb je het plan zo rond.',
			'keuze'         => array(),
			'onderwerp'     => $groepsuitjes ? (int) $groepsuitjes->term_id : 0,
			'aantal'        => 3,
			'knop'          => $groepsuitjes ? thema_link( 'Alle artikelen over groepsuitjes', get_category_link( $groepsuitjes ) ) : '',
			'achtergrond'   => 'standaard',
			'anker'         => 'ideeen',
		),
		$faq,
		$offerte,
	), $id );

	$duim = $b['groepsselfie-met-lichtstaven-in-partybus'] ?? 0;
	if ( $duim && ! has_post_thumbnail( $id ) ) {
		set_post_thumbnail( $id, $duim );
	}
	clean_post_cache( $id );
	return array( 'pagina' => $id );
}

/**
 * De zes artikelen. Geen plaatsnamen of namen van zaken.
 */
function thema_trip_artikelen() {
	$landing  = home_url( '/gelegenheden/trip-met-vrienden/' );
	$ideeen   = thema_trip_link( 'uitje-met-vrienden-ideeen' );
	$kroeg    = thema_trip_link( 'kroegentocht-organiseren' );
	$familie  = thema_trip_link( 'familiedag-organiseren' );
	$vriendin = thema_trip_link( 'vriendinnenuitje-ideeen' );
	$weekend  = thema_trip_link( 'vriendenweekend-organiseren' );
	$mannen   = thema_trip_link( 'mannenuitje-ideeen' );
	$locaties = home_url( '/locaties/' );
	$jubileum = home_url( '/blog/huwelijksjubileum-vieren/' );

	return array(

		/* 1. Ideeën uitje met vrienden. */
		array(
			'slug'         => 'uitje-met-vrienden-ideeen',
			'titel'        => 'Uitje met vrienden: 25 leuke ideeën voor de hele groep',
			'seo_titel'    => 'Uitje met vrienden: 25 leuke ideeën | Lekker Geel Busje',
			'samenvatting' => 'Leuke dingen om te doen met vrienden: 25 ideeën voor een groepsuitje, van actief en culinair tot creatief en een avond uit. Met tips om de dag goed te plannen.',
			'beeld'        => 'groepsselfie-met-lichtstaven-in-partybus',
			'kort'         => "Kies één activiteit die de hele groep leuk vindt en vul de dag aan met eten en een avond uit.\nPrik de datum ruim van tevoren en spreek de kosten per persoon af.\nRegel het vervoer voor de hele groep, dan blijft iedereen bij elkaar.",
			'faq_kop'      => 'Vragen over een *uitje met vrienden*',
			'offerte_kop'  => 'Plan gemaakt? *Wij rijden.*',
			'vragen'       => array(
				array( 'Wat zijn leuke dingen om te doen met vrienden?', 'Een escape room, een proeverij, een kookworkshop, karten of een avond karaoke. Combineer één activiteit met samen eten en een avond uit, dan heb je een dag die iedereen onthoudt.' ),
				array( 'Hoe plan je een uitje met een grote vriendengroep?', 'Prik eerst een datum, spreek een budget per persoon af en kies dan de activiteit. Vraag bij elke locatie naar het maximum aantal personen en regel vervoer voor de hele groep.' ),
				array( 'Wat doe je met vrienden als het regent?', 'Kies iets binnen: een escape room, bowlen, een workshop of een proeverij. Met vervoer dat je van deur tot deur brengt, maakt het weer dan weinig uit.' ),
			),
			'blokken'      => array(),
			'tekst'        => <<<HTML
<p>De groepsapp staat vol plannen, maar wat ga je nou eigenlijk doen? Een goed uitje met vrienden past bij de groep, is voor iedereen leuk en laat genoeg tijd over om gewoon samen te zijn. Hier vind je 25 ideeën in vijf richtingen. Kies er één, vul aan met samen eten en een avond uit, en de dag staat.</p>

<h2>Actief: voor de groep die wil bewegen</h2>
<ul>
<li><strong>Escape room:</strong> samen puzzelen tegen de klok, in teams tegen elkaar.</li>
<li><strong>Karten:</strong> fanatiek, snel en altijd discussie over wie er nou echt won.</li>
<li><strong>Lasergamen of paintball:</strong> vol adrenaline, ook voor wie niet sportief is.</li>
<li><strong>Suppen, kanoën of raften:</strong> heerlijk in de zomer, met grote kans op een nat pak.</li>
<li><strong>Klimmen of boulderen:</strong> elkaar aanmoedigen tot de top.</li>
</ul>

<h2>Proeven en eten: voor de genieters</h2>
<ul>
<li><strong>Bierproeverij of brouwerij bezoeken:</strong> proeven, vergelijken en ontdekken wie de kenner is.</li>
<li><strong>Wijnproeverij:</strong> rustig beginnen, met veel om over te praten.</li>
<li><strong>Whiskyproeverij:</strong> voor de groep die van iets sterkers houdt.</li>
<li><strong>Kookworkshop:</strong> samen koken en daarna samen aan tafel.</li>
<li><strong>Foodtour:</strong> een middag van hapje naar hapje.</li>
</ul>

<h2>Creatief en slim: voor de denkers</h2>
<ul>
<li><strong>Pubquiz:</strong> in teams, met een ronde over de vriendengroep zelf.</li>
<li><strong>Workshop:</strong> schilderen, keramiek, graffiti of cocktails maken.</li>
<li><strong>Stadsspel of puzzeltocht:</strong> met opdrachten op de telefoon, team tegen team.</li>
<li><strong>Fotojacht:</strong> een lijst foto-opdrachten voor de hele dag.</li>
<li><strong>Muziekworkshop:</strong> samen een nummer opnemen of leren drummen.</li>
</ul>

<h2>Uitgaan: voor de groep die door wil</h2>
<ul>
<li><strong>Kroegentocht:</strong> van kroeg naar kroeg in het uitgaansgebied. Tips lees je in <a href="{$kroeg}">kroegentocht organiseren</a>.</li>
<li><strong>Karaoke:</strong> een eigen ruimte, een microfoon en een groep die durft.</li>
<li><strong>Festival of concert:</strong> samen naar een avond met muziek die iedereen goed vindt.</li>
<li><strong>Comedy-avond:</strong> lachen met de hele groep, daarna nog wat drinken.</li>
<li><strong>Themafeest:</strong> iedereen verkleed in hetzelfde thema.</li>
</ul>

<h2>Er samen op uit: voor de hele groep</h2>
<ul>
<li><strong>Bowlen of glow golf:</strong> laagdrempelig, en de hele groep kan meedoen.</li>
<li><strong>Strand of meer:</strong> een dag aan het water, met een barbecue erbij.</li>
<li><strong>Pretpark:</strong> een dag in de achtbaan, iedereen is even weer twaalf.</li>
<li><strong>Vriendenweekend:</strong> voor als één dag niet genoeg is, een huisje met de hele groep. Lees <a href="{$weekend}">vriendenweekend organiseren</a>.</li>
</ul>

<h2>Zo plan je het uitje</h2>
<ul>
<li><strong>Prik de datum ruim van tevoren.</strong> Hoe groter de groep, hoe eerder.</li>
<li><strong>Spreek een budget per persoon af</strong> en laat iedereen vooraf betalen.</li>
<li><strong>Kies één hoofdactiviteit,</strong> vul aan met eten en een avond uit.</li>
<li><strong>Regel het vervoer.</strong> Met een <a href="{$landing}">partybus voor je uitje met vrienden</a> blijft de groep bij elkaar en hoeft niemand de Bob te zijn. De bus is het best vanaf zo'n 35 personen. Met minder mensen kan het ook, alleen worden de kosten per persoon dan wat hoger.</li>
</ul>
<p>Op pad met alleen de meiden of de jongens? Bekijk dan de ideeën voor een <a href="{$vriendin}">vriendinnenuitje</a> of een <a href="{$mannen}">mannenuitje</a>.</p>
HTML,
		),

		/* 2. Kroegentocht. */
		array(
			'slug'         => 'kroegentocht-organiseren',
			'titel'        => 'Kroegentocht organiseren: tips voor een avond om nooit te vergeten',
			'seo_titel'    => 'Kroegentocht organiseren: tips | Lekker Geel Busje',
			'samenvatting' => 'Een kroegentocht of pubcrawl organiseren met een grote groep? Zo kies je de route, houd je de groep bij elkaar en kom je na afloop veilig thuis.',
			'beeld'        => 'discolicht-en-feest-in-de-sfeerbus',
			'kort'         => "Kies een uitgaansgebied met veel kroegen op loopafstand van elkaar.\nMaak een route met vier tot zes stops en een vaste tijd per kroeg.\nRegel het vervoer heen en terug, dan hoeft niemand na afloop nog te rijden.",
			'faq_kop'      => 'Vragen over de *kroegentocht*',
			'offerte_kop'  => 'Route klaar? *Wij rijden.*',
			'vragen'       => array(
				array( 'Wat is het verschil tussen een kroegentocht en een pubcrawl?', 'Er is geen echt verschil. Pubcrawl is het Engelse woord voor een kroegentocht: met een groep van kroeg naar kroeg, met in elke kroeg een drankje.' ),
				array( 'Hoeveel kroegen doe je op een kroegentocht?', 'Vier tot zes is genoeg voor een avond. Reken op ongeveer drie kwartier per kroeg, dan blijft het gezellig en heb je tijd om tussendoor iets te eten.' ),
				array( 'Hoe kom je na een kroegentocht thuis?', 'Regel het vervoer vooraf. Met een eigen bus spreek je een tijd en plek af waar de groep wordt opgehaald, zodat niemand na afloop nog hoeft te rijden of een taxi moet zoeken.' ),
			),
			'blokken'      => array(),
			'tekst'        => <<<HTML
<p>Een kroegentocht is een van de leukste manieren om met een groep een avond uit te gaan. Van kroeg naar kroeg, steeds een andere sfeer, en onderweg de beste verhalen. Maar met twintig, veertig of zestig mensen gaat dat niet vanzelf goed. Met deze tips organiseer je een kroegentocht waar iedereen nog lang over praat.</p>

<h2>1. Kies een uitgaansgebied</h2>
<p>Zoek een stad of buurt met veel kroegen op loopafstand van elkaar. Zo verlies je geen tijd onderweg en blijft de groep bij elkaar. Ideeën voor een bestemming vind je bij onze <a href="{$locaties}">bestemmingen</a>.</p>

<h2>2. Maak een route met vaste tijden</h2>
<ul>
<li><strong>Vier tot zes stops</strong> is genoeg voor een avond.</li>
<li><strong>Drie kwartier per kroeg</strong> houdt de vaart erin.</li>
<li><strong>Begin rustig en eindig met de drukste kroeg,</strong> waar je kunt dansen.</li>
<li><strong>Bel bij een grote groep vooraf even</strong> of er plek is.</li>
</ul>

<h2>3. Plan eten in</h2>
<p>Een kroegentocht zonder eten eindigt vroeg. Begin met een diner, of plan halverwege een stop bij een eetcafé of snackbar. Je groep zal je dankbaar zijn.</p>

<h2>4. Maak er een spel van</h2>
<ul>
<li><strong>Kroegenbingo:</strong> een kaart met opdrachten per kroeg, zoals een toost met de barman of een groepsfoto.</li>
<li><strong>Dresscode:</strong> iedereen in dezelfde kleur, dan raakt niemand kwijt.</li>
<li><strong>Stempelkaart:</strong> wie bij elke kroeg een stempel heeft, krijgt een prijs.</li>
</ul>

<h2>5. Houd het leuk voor iedereen</h2>
<p>Niet iedereen drinkt even snel of evenveel. Zorg dat er ook alcoholvrij wordt besteld, drink tussendoor water en laat niemand achter. Spreek één aanspreekpunt af die de route en de tijden bewaakt.</p>

<h2>6. Regel het vervoer heen en terug</h2>
<p>Het belangrijkste: zorg dat niemand na afloop nog hoeft te rijden. Met een <a href="{$landing}">partybus voor je uitje met vrienden</a> rijdt de hele groep samen naar het uitgaansgebied, en staat de bus op een afgesproken tijd en plek klaar om iedereen weer thuis te brengen. De rit zelf is met licht, muziek en een drankje van het gastvrouwke meteen het begin van de avond.</p>
<p>Zoek je meer ideeën voor de dag zelf? Bekijk dan onze <a href="{$ideeen}">25 ideeën voor een uitje met vrienden</a>.</p>
HTML,
		),

		/* 3. Familiedag. */
		array(
			'slug'         => 'familiedag-organiseren',
			'titel'        => 'Familiedag organiseren: ideeën voor jong en oud',
			'seo_titel'    => 'Familiedag organiseren: ideeën | Lekker Geel Busje',
			'samenvatting' => 'Een familiedag of familieuitje organiseren? Ideeën waar opa, oma en de kleinkinderen allemaal plezier aan hebben, plus tips voor de planning en het vervoer.',
			'beeld'        => 'gasten-lachen-aan-tafel-in-sfeerbus',
			'kort'         => "Kies een activiteit die voor alle leeftijden werkt, of plan iets voor elke groep.\nHoud de dag rustig: één activiteit, samen eten en genoeg tijd om bij te praten.\nMet vervoer voor de hele familie kan ook wie niet meer zelf rijdt mee.",
			'faq_kop'      => 'Vragen over de *familiedag*',
			'offerte_kop'  => 'Familie compleet? *Wij rijden.*',
			'vragen'       => array(
				array( 'Wat kun je doen op een familiedag?', 'Iets wat voor alle leeftijden leuk is: een boottocht, een speurtocht, een workshop, een dierenpark of een lange tafel met een goed diner. Plan liever één activiteit en veel tijd om samen te zijn.' ),
				array( 'Hoe organiseer je een familiedag met een grote familie?', 'Kies een datum ruim van tevoren, maak een groepsapp en spreek af wie wat regelt. Kies een locatie die voor iedereen bereikbaar is, of regel vervoer voor de hele familie.' ),
				array( 'Wie betaalt de familiedag?', 'Dat verschilt per familie. Vaak betaalt iedereen een vast bedrag per gezin of per persoon, soms trakteren de grootouders. Spreek het vooraf af, dan is het voor iedereen duidelijk.' ),
			),
			'blokken'      => array(),
			'tekst'        => <<<HTML
<p>Een familiedag brengt iedereen weer eens bij elkaar: opa en oma, ooms en tantes, neven, nichten en de kleinkinderen. Mooi, maar ook een uitdaging. Want hoe vind je iets dat zowel de kleinsten als de oudsten leuk vinden? Hier vind je ideeën en tips om de familiedag goed te organiseren.</p>

<h2>Ideeën voor een familiedag</h2>
<ul>
<li><strong>Boottocht:</strong> rustig varen met een hapje en een drankje, voor alle leeftijden.</li>
<li><strong>Speurtocht of fotojacht:</strong> gemengde teams van jong en oud, met opdrachten onderweg.</li>
<li><strong>Dierenpark of pretpark:</strong> de kinderen hebben het naar hun zin, de rest geniet mee.</li>
<li><strong>Workshop:</strong> samen koken, bakken of iets maken. Kies iets waar iedereen aan mee kan doen.</li>
<li><strong>Familiequiz:</strong> vragen over de familiegeschiedenis, met oude foto's en verhalen.</li>
<li><strong>Lange tafel:</strong> soms is een goed diner met de hele familie het mooiste programma.</li>
<li><strong>Bezoek aan de plek van vroeger:</strong> het dorp waar opa en oma opgroeiden, of de straat van het eerste huis.</li>
</ul>

<h2>Zo organiseer je de familiedag</h2>
<ul>
<li><strong>Begin op tijd.</strong> Prik de datum een paar maanden van tevoren, zodat iedereen kan.</li>
<li><strong>Maak een groepsapp</strong> en verdeel de taken: wie regelt de activiteit, het eten en het vervoer?</li>
<li><strong>Spreek de kosten af,</strong> per gezin of per persoon.</li>
<li><strong>Houd het programma rustig.</strong> Eén activiteit, samen eten en genoeg tijd om bij te praten.</li>
<li><strong>Denk aan iedereen:</strong> een rustige plek voor de oudsten, iets te doen voor de kinderen en eten voor elke smaak.</li>
<li><strong>Maak een groepsfoto.</strong> Over tien jaar is dat de mooiste herinnering.</li>
</ul>

<h2>Iedereen samen op pad</h2>
<p>Bij een grote familie komen gasten vaak van verschillende kanten, en niet iedereen rijdt nog graag zelf. Met een bus haal je de hele familie op, ook op meerdere opstapplekken. Iedereen komt tegelijk aan, en aan het eind van de dag wordt iedereen weer thuisgebracht. Meer daarover lees je op onze pagina over een <a href="{$landing}">partybus voor je uitje</a>.</p>
<p>Viert de familie een huwelijksjubileum? Lees dan ook <a href="{$jubileum}">huwelijksjubileum vieren</a>.</p>
HTML,
		),

		/* 4. Vriendinnenuitje. */
		array(
			'slug'         => 'vriendinnenuitje-ideeen',
			'titel'        => 'Vriendinnenuitje: 20 ideeën voor een dagje uit met de meiden',
			'seo_titel'    => 'Vriendinnenuitje: 20 ideeën | Lekker Geel Busje',
			'samenvatting' => 'Op zoek naar een leuk vriendinnenuitje of meidenuitje? Twintig ideeën voor een dagje uit met vriendinnen, van ontspannen en proeven tot creatief en feesten.',
			'beeld'        => 'vriendinnen-feesten-in-de-bus',
			'kort'         => "Combineer iets ontspannends of creatiefs met lekker eten en een avond uit.\nKies een thema of dresscode, dat maakt het meteen een feest.\nRegel vervoer, dan kan iedereen proosten en blijft de groep bij elkaar.",
			'faq_kop'      => 'Vragen over een *vriendinnenuitje*',
			'offerte_kop'  => 'Meiden compleet? *Wij rijden.*',
			'vragen'       => array(
				array( 'Wat kun je doen met vriendinnen?', 'Een high tea, een wellnessmiddag, een workshop, een proeverij of een avond karaoke. Combineer één activiteit met samen eten en een feestrit, dan heb je een complete dag.' ),
				array( 'Wat is een origineel meidenuitje?', 'Iets wat jullie nog niet eerder deden: een cocktailworkshop, samen bloemstukken maken, een dansles of een fotoshoot met de hele groep.' ),
				array( 'Hoe plan je een uitje met een grote groep vriendinnen?', 'Prik een datum in de groepsapp, spreek een budget af en laat iedereen vooraf betalen. Kies een programma waar iedereen aan mee kan doen.' ),
			),
			'blokken'      => array(),
			'tekst'        => <<<HTML
<p>Een dag met alleen de meiden: geen kinderen, geen werk, gewoon samen lachen. Of het nou om je vriendinnen van school gaat, de meiden van de hockey of je zussen en nichten, een goed vriendinnenuitje is altijd een feest. Hier vind je twintig ideeën om uit te kiezen.</p>

<h2>Ontspannen</h2>
<ul>
<li><strong>Wellness of spa:</strong> een middag bijkomen in de sauna en het bubbelbad.</li>
<li><strong>High tea of high wine:</strong> lekker kletsen bij een etagère vol hapjes.</li>
<li><strong>Yoga- of sup-yogales:</strong> samen ontspannen, met een lach als iemand in het water valt.</li>
<li><strong>Brunch:</strong> een late ochtend met eindeloos bijpraten.</li>
</ul>

<h2>Proeven</h2>
<ul>
<li><strong>Wijnproeverij:</strong> proeven, keuren en een favoriet uitkiezen.</li>
<li><strong>Cocktailworkshop:</strong> iedereen shaket haar eigen cocktail.</li>
<li><strong>Chocoladeworkshop:</strong> proeven, maken en mee naar huis nemen.</li>
<li><strong>Foodtour:</strong> een middag van hapje naar hapje.</li>
</ul>

<h2>Creatief</h2>
<ul>
<li><strong>Bloemstukken of kransen maken:</strong> leuk om de rest van de dag te dragen.</li>
<li><strong>Schilder- of keramiekworkshop:</strong> een aandenken dat je echt gebruikt.</li>
<li><strong>Fotoshoot met de groep:</strong> met een fotograaf of een lijst met foto-opdrachten.</li>
<li><strong>Parfum of kaarsen maken:</strong> je eigen geur om mee naar huis te nemen.</li>
</ul>

<h2>Actief</h2>
<ul>
<li><strong>Dansles:</strong> salsa, Zumba of een choreografie van je favoriete nummer.</li>
<li><strong>Escape room:</strong> samen puzzelen tegen de klok.</li>
<li><strong>Suppen of kanoën:</strong> heerlijk op een zomerse dag.</li>
<li><strong>Shoppen:</strong> een middag door de stad, met tussendoor een terrasje.</li>
</ul>

<h2>Feesten</h2>
<ul>
<li><strong>Karaoke:</strong> de meiden op het podium, de rest zingt mee.</li>
<li><strong>Themafeest:</strong> iedereen in hetzelfde thema, van glitter tot jaren tachtig.</li>
<li><strong>Concert of festival:</strong> samen naar de artiest waar jullie allemaal fan van zijn.</li>
<li><strong>Feestrit door de stad:</strong> met licht, muziek en een drankje van het gastvrouwke. Lees meer over een <a href="{$landing}">partybus voor je uitje met vrienden</a>.</li>
</ul>
<p>Op zoek naar meer ideeën voor de hele vriendengroep? Bekijk onze <a href="{$ideeen}">25 ideeën voor een uitje met vrienden</a>. En gaan jullie een heel weekend weg? Lees dan <a href="{$weekend}">vriendenweekend organiseren</a>.</p>
HTML,
		),

		/* 5. Vriendenweekend. */
		array(
			'slug'         => 'vriendenweekend-organiseren',
			'titel'        => 'Vriendenweekend organiseren: tips en ideeën voor een weekendje weg',
			'seo_titel'    => 'Vriendenweekend organiseren: tips | Lekker Geel Busje',
			'samenvatting' => 'Een weekendje weg met vrienden organiseren? Zo kies je de plek, verdeel je de kosten en plan je een programma waar de hele groep van geniet.',
			'beeld'        => 'vriendengroep-aan-tafel-in-sfeerbus',
			'kort'         => "Begin een paar maanden van tevoren met de datum en het budget.\nKies een groot huis of groepsaccommodatie met genoeg ruimte voor iedereen.\nPlan per dag één activiteit en laat de rest vrij.",
			'faq_kop'      => 'Vragen over het *vriendenweekend*',
			'offerte_kop'  => 'Weekend gepland? *Wij rijden.*',
			'vragen'       => array(
				array( 'Hoe ver van tevoren plan je een vriendenweekend?', 'Begin drie tot zes maanden van tevoren. Grote groepsaccommodaties zijn in de weekenden snel volgeboekt, zeker in de zomer en rond feestdagen.' ),
				array( 'Hoe verdeel je de kosten van een vriendenweekend?', 'Tel huur, eten, drinken, activiteiten en vervoer bij elkaar op en deel het door het aantal personen. Laat iedereen vooraf een deel betalen en houd een gezamenlijke pot bij voor de boodschappen.' ),
				array( 'Wat doe je op een vriendenweekend?', 'Eén activiteit per dag is genoeg: een wandeling, een proeverij, een spel of een avond uit. De rest van de tijd is voor samen koken, spelletjes en bijpraten.' ),
			),
			'blokken'      => array(),
			'tekst'        => <<<HTML
<p>Een heel weekend met je vrienden: samen koken, lange avonden en eindelijk tijd om echt bij te praten. Een vriendenweekend organiseren vraagt wel wat voorbereiding, zeker met een grote groep. Met deze tips wordt het een weekendje weg om nooit te vergeten.</p>

<h2>1. Prik de datum en het budget</h2>
<p>Begin drie tot zes maanden van tevoren. Zet een paar data in de groepsapp en kies de datum waarop de meeste mensen kunnen. Spreek meteen een budget per persoon af, zodat je weet welke accommodatie past.</p>

<h2>2. Kies de plek</h2>
<ul>
<li><strong>Een groot vakantiehuis of groepsaccommodatie,</strong> met genoeg slaapplekken en een grote tafel.</li>
<li><strong>Bos, strand of stad:</strong> wat past bij jullie groep?</li>
<li><strong>Reistijd:</strong> twee tot drie uur rijden is prima, en met de bus is ook verder weg goed te doen.</li>
</ul>

<h2>3. Plan het programma</h2>
<p>Eén activiteit per dag is genoeg. Denk aan een wandeling, een proeverij, een spel of een avond uit in de stad. Laat de rest van de tijd vrij voor samen koken, spelletjes en bijpraten. Ideeën vind je bij onze <a href="{$ideeen}">25 ideeën voor een uitje met vrienden</a>.</p>

<h2>4. Verdeel de taken</h2>
<ul>
<li><strong>Eén persoon voor de accommodatie,</strong> één voor de boodschappen, één voor het programma.</li>
<li><strong>Kookschema:</strong> elke avond een ander groepje achter het fornuis.</li>
<li><strong>Gezamenlijke pot</strong> voor de boodschappen en de drankjes.</li>
</ul>

<h2>5. Regel het vervoer</h2>
<p>Met een grote groep is het vervoer de grootste puzzel. Met een bus reist de hele groep samen, met de bagage mee, en begint het weekend al onderweg. We brengen jullie naar de accommodatie en halen jullie aan het eind van het weekend weer op. Ook een avond uit tijdens het weekend kunnen we rijden, zodat niemand hoeft te rijden. Meer lees je op onze pagina over een <a href="{$landing}">partybus voor je uitje met vrienden</a>.</p>

<h2>6. Vergeet de details niet</h2>
<ul>
<li>Spelletjes, een speaker en een playlist.</li>
<li>Een groepsfoto aan het eind van het weekend.</li>
<li>Een thema-avond of dresscode voor de zaterdag.</li>
</ul>
HTML,
		),

		/* 6. Mannenuitje. */
		array(
			'slug'         => 'mannenuitje-ideeen',
			'titel'        => 'Mannenuitje: 15 ideeën voor een dag uit met de jongens',
			'seo_titel'    => 'Mannenuitje: 15 ideeën voor een dag uit | Lekker Geel Busje',
			'samenvatting' => 'Op zoek naar een leuk mannenuitje of jongensuitje? Vijftien ideeën voor een dag uit met de jongens, van karten en brouwerijen tot een escape room en een avond uit.',
			'beeld'        => 'feest-met-armen-omhoog-in-de-bus',
			'kort'         => "Kies iets met een beetje competitie, dat werkt bijna altijd.\nCombineer een activiteit met goed eten en een drankje.\nRegel vervoer, dan hoeft niemand te loten wie er rijdt.",
			'faq_kop'      => 'Vragen over een *mannenuitje*',
			'offerte_kop'  => 'De jongens compleet? *Wij rijden.*',
			'vragen'       => array(
				array( 'Wat is een leuk mannenuitje?', 'Iets met een beetje competitie en een goede afsluiter: karten, lasergamen of een brouwerijbezoek, gevolgd door samen eten en een avond uit.' ),
				array( 'Wat doe je met een groep jongens als het regent?', 'Kies iets binnen: indoor karten, bowlen, darts, een escape room of een bierproeverij. Met vervoer van deur tot deur maakt het weer niet uit.' ),
				array( 'Hoe regel je het vervoer voor een grote groep?', 'Met een eigen bus rijdt de hele groep samen, blijft niemand achter en hoeft niemand te rijden. Vraag een vaste prijs aan, dan deel je die door de groep.' ),
			),
			'blokken'      => array(),
			'tekst'        => <<<HTML
<p>Een dag uit met de jongens: de vrienden van vroeger, het elftal of de mannen van de straat. Goede gesprekken, een beetje competitie en een avond die later eindigt dan gepland. Hier vind je vijftien ideeën voor een mannenuitje.</p>

<h2>Actief en competitief</h2>
<ul>
<li><strong>Karten:</strong> de klassieker, met een prijs voor de snelste ronde.</li>
<li><strong>Lasergamen of paintball:</strong> teams tegen elkaar, met een revanche na afloop.</li>
<li><strong>Golf of footgolf:</strong> rustig beginnen, fanatiek eindigen.</li>
<li><strong>Bijlwerpen of boogschieten:</strong> iets wat je niet elke dag doet.</li>
<li><strong>Escape room:</strong> wie houdt het hoofd koel?</li>
</ul>

<h2>Proeven en eten</h2>
<ul>
<li><strong>Brouwerij bezoeken:</strong> een rondleiding met een proeverij erachteraan.</li>
<li><strong>Whiskyproeverij:</strong> voor de liefhebbers van iets sterkers.</li>
<li><strong>Barbecueworkshop:</strong> leren grillen en daarna samen eten.</li>
<li><strong>Steakhouse of grillrestaurant:</strong> een goede afsluiter van de dag.</li>
</ul>

<h2>Sport en uitgaan</h2>
<ul>
<li><strong>Samen naar een show:</strong> een concert, musical of comedyavond, met een feestrit heen en terug.</li>
<li><strong>Darts of poolbiljart:</strong> in een eigen ruimte, met een toernooi.</li>
<li><strong>Kroegentocht:</strong> van kroeg naar kroeg. Lees <a href="{$kroeg}">kroegentocht organiseren</a>.</li>
<li><strong>Pubquiz:</strong> wie weet het meeste over sport, muziek en de groep zelf?</li>
<li><strong>Festival of concert:</strong> samen naar een avond met muziek.</li>
<li><strong>Vriendenweekend:</strong> twee dagen weg. Tips staan in <a href="{$weekend}">vriendenweekend organiseren</a>.</li>
</ul>

<h2>Niemand hoeft te rijden</h2>
<p>Bij een mannenuitje is er altijd die ene vraag: wie rijdt er? Met een <a href="{$landing}">partybus voor je uitje met vrienden</a> hoeft niemand te loten. De hele groep rijdt samen, het gastvrouwke zorgt voor de drankjes en de chauffeur brengt iedereen na afloop thuis.</p>
<p>Meer ideeën voor de hele vriendengroep vind je bij onze <a href="{$ideeen}">25 ideeën voor een uitje met vrienden</a>.</p>
HTML,
		),
	);
}
