<?php
/**
 * Bedrijfsuitje (november 2026): de landingspagina /gelegenheden/bedrijfsuitje/ en zes artikelen in het
 * onderwerp Teamuitjes (/blog/onderwerp/teamuitjes/). Geplaatst door de uitroltaken bedrijfsuitje-blog-v1
 * en bedrijfsuitje-v1. Wie de tekst later in WordPress aanpast, houdt die aanpassing.
 *
 * Zoekwoorden (SE Ranking, NL, 17 nov 2026):
 * - Pagina: bedrijfsuitje 4.400 en teamuitje 3.600 (zwaar, moeilijkheid 60+), plus de lange staart rond vervoer.
 * - Onderwerp Teamuitjes: teamuitjes 660 (moeilijkheid 34).
 * - Artikelen: ideeën (bedrijfsuitje ideeen 590, personeelsuitje ideeen 590, originele teamuitjes 480,
 *   bedrijfsuitje top 10 320, origineel bedrijfsuitje 260, bijzonder teamuitje 210), organiseren (bedrijfsfeest
 *   organiseren 480, personeelsfeest organiseren 320, bedrijfsuitje organiseren 210), spellen (teambuilding
 *   opdrachten 170, teambuilding oefeningen 140, pubquiz bedrijfsuitje 90, ijsbreker vragen 50), kerst
 *   (kerstborrel organiseren 170, kerstuitje 140, kerstborrel ideeen 110) en winter (bedrijfsuitje winter 140,
 *   nieuwjaarsborrel organiseren 110, bedrijfsuitje binnen 70). Kerst en winter: moeilijkheid onder de 10.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Adres van een artikel op slug, ook als het nog niet bestaat (voor links tussen de artikelen).
 */
function thema_bedrijfsuitje_link( $slug ) {
	$p = get_page_by_path( $slug, OBJECT, 'post' );
	return $p ? get_permalink( $p ) : home_url( '/blog/' . $slug . '/' );
}

/**
 * Uitroltaak bedrijfsuitje-blog-v1: onderwerp Teamuitjes en de artikelen.
 */
function thema_bedrijfsuitje_blog() {
	$uit  = array();
	$term = get_category_by_slug( 'teamuitjes' );
	if ( $term ) {
		$cat = (int) $term->term_id;
	} else {
		$nieuw = wp_insert_term( 'Teamuitjes', 'category', array(
			'slug'        => 'teamuitjes',
			'description' => 'Ideeën voor een origineel teamuitje of bedrijfsuitje, een stappenplan voor de organisator en spellen voor onderweg. Van de kerstborrel tot het personeelsfeest: zo regel je het uitje met collega\'s zonder gedoe.',
		) );
		$cat = is_wp_error( $nieuw ) ? 0 : (int) $nieuw['term_id'];
	}
	$uit['onderwerp'] = $cat;

	$gel = get_page_by_path( 'bedrijfsuitje', OBJECT, 'gelegenheid' );
	$gel = $gel ? (int) $gel->ID : 0;
	foreach ( thema_bedrijfsuitje_artikelen() as $artikel ) {
		$uit[ $artikel['slug'] ] = thema_blog_artikel_plaatsen( $artikel, $cat, $gel );
	}
	delete_option( 'rewrite_rules' );
	return $uit;
}

/**
 * Uitroltaak bedrijfsuitje-v1: de blokken van de landingspagina.
 */
function thema_plaats_bedrijfsuitje() {
	if ( ! function_exists( 'update_field' ) ) {
		return 0;
	}
	$post = get_page_by_path( 'bedrijfsuitje', OBJECT, 'gelegenheid' );
	if ( ! $post ) {
		return 0;
	}
	$id = (int) $post->ID;
	$b  = array();
	foreach ( array(
		'gasten-stappen-in-lekker-geel-busje',
		'gasten-lachen-aan-tafel-in-sfeerbus',
		'handen-in-de-lucht-in-de-gele-bus',
		'high-five-bij-het-instappen',
		'gastvrouw-schenkt-drankje-aan-de-bar',
		'vriendengroep-aan-tafel-in-sfeerbus',
	) as $naam ) {
		$b[ $naam ] = thema_beeld_op_naam( $naam );
	}
	$locaties = get_page_by_path( 'locaties' );
	$locaties = $locaties ? get_permalink( $locaties ) : home_url( '/locaties/' );
	$regels   = get_page_by_path( 'huisregels' );
	$regels   = $regels ? get_permalink( $regels ) : home_url( '/huisregels/' );
	$bussen   = get_page_by_path( 'onze-bussen' );
	$bussen   = $bussen ? get_permalink( $bussen ) : home_url( '/onze-bussen/' );

	update_field( 'field_gel_ondertitel', 'Samen heen en terug, niemand hoeft te rijden.', $id );
	update_field( 'field_seo_seo_titel', 'Partybus voor je bedrijfsuitje of teamuitje | Lekker Geel Busje', $id );
	update_field( 'field_seo_seo_omschrijving', 'Bedrijfsuitje of teamuitje met de partybus: ophalen bij kantoor, chauffeur en gastvrouwke aan boord en binnen 1 werkdag een vaste all-in prijs.', $id );

	$reviews             = thema_blok_reviews();
	$reviews['kop']      = 'Zij stapten al in, *dit vonden ze ervan*';
	$reviews['weergave'] = 'compact';

	/* Eigen vragen plus de vragen uit inc/faq-copy.php, zonder de dubbele vraag over de vaste prijs. */
	$lijst = function_exists( 'thema_faq_per_pagina' ) ? ( thema_faq_per_pagina()['gelegenheid/bedrijfsuitje'] ?? array() ) : array();
	$lijst = array_values( array_filter( $lijst, fn( $v ) => ! ( is_array( $v ) && false !== strpos( $v[0], 'vaste prijs' ) ) ) );
	$eigen = array(
		array( 'Wat kost een partybus voor een bedrijfsuitje?', 'Dat hangt af van de grootte van de groep, de afstand en rijtijd, de dag en eventuele wachttijd. Binnen 1 werkdag heb je een vaste all-in prijs, met chauffeur, gastvrouwke, licht en geluid, brandstof en btw erin. Handig voor de begroting: je weet vooraf precies waar je aan toe bent.' ),
	);
	$extra = array(
		array( 'Kunnen we de bus ook boeken voor een personeelsfeest of kerstborrel?', 'Zeker. Een teamuitje, personeelsfeest, kerstborrel, jubileum of het afscheid van een collega: de bus rijdt voor elk moment waarop je met collega\'s op pad gaat. Vertel wat jullie plan is, dan denken we mee over de route.' ),
		array( 'Waar rijden jullie naartoe?', 'Overal waar jullie uitje is. We rijden door heel Nederland en daarbuiten, dus kies gerust de activiteit, het restaurant of de stad die bij jullie team past.' ),
		array( 'Kunnen we op factuur op bedrijfsnaam betalen?', 'Ja. Je krijgt de factuur op naam van het bedrijf, met de vaste all-in prijs uit de offerte. Zet de bedrijfsgegevens in je aanvraag, dan regelen we het meteen goed.' ),
		array( 'Vanaf hoeveel personen is de bus geschikt?', 'De bus komt het best tot zijn recht vanaf zo\'n 35 personen. Met minder mensen kan het ook, alleen worden de kosten per persoon dan wat hoger. Elke bus heeft minstens 39 zitplaatsen, in onze verlengde bus gaan tot 88 personen mee en voor grotere groepen rijden er meerdere bussen. Met een kleinere groep? Vraag het gerust, dan denken we mee.' ),
		array( 'Bepalen we zelf de sfeer in de bus?', 'Ja. Sluit jullie eigen playlist aan, zet het licht vol aan of houd het juist rustig op de heenweg. Het gastvrouwke en de chauffeur stemmen het met jullie af.' ),
	);
	$vragen        = function_exists( 'thema_faq_rijen' ) ? thema_faq_rijen( array_merge( $eigen, $lijst, $extra ) ) : array();
	$faq           = thema_blok_faq();
	$faq['kop']    = 'Vragen over een partybus voor je *bedrijfsuitje*';
	$faq['vragen'] = $vragen;

	$offerte          = thema_blok_offerte();
	$offerte['label'] = 'Binnen 1 werkdag je prijs';
	$offerte['kop']   = 'Klaar om met het hele team *in te stappen?*';

	$artikelen = array();
	foreach ( array( 'bedrijfsuitje-ideeen', 'bedrijfsuitje-organiseren', 'teambuilding-spellen-collegas-bus' ) as $slug ) {
		$p = get_page_by_path( $slug, OBJECT, 'post' );
		if ( $p ) {
			$artikelen[] = (string) $p->ID;
		}
	}
	$teamuitjes = get_category_by_slug( 'teamuitjes' );

	$naar_offerte = thema_link( 'Vraag je prijs aan', '#offerte' );

	update_field( 'field_blokken', array(
		array(
			'acf_fc_layout' => 'hero',
			'kop_1'         => 'Partybus voor je',
			'kop_2'         => 'bedrijfsuitje',
			'plaatsen'      => '',
			'sub_1'         => 'Samen heen, samen terug,',
			'sub_2'         => 'en niemand hoeft te rijden.',
			'knop'          => $naar_offerte,
			'knop_tonen'    => 1,
			'toon_score'    => 1,
			'stijl'         => 'foto',
			'fotos'         => array_values( array_filter( array( $b['gasten-stappen-in-lekker-geel-busje'], $b['gasten-lachen-aan-tafel-in-sfeerbus'], $b['handen-in-de-lucht-in-de-gele-bus'] ) ) ),
			'vorm'          => 'auto',
			'hoogte'        => 'compact',
			'anker'         => 'top',
		),
		array(
			'acf_fc_layout' => 'over_ons',
			'eyebrow'       => 'Voor de organisator',
			'kop'           => 'Jij regelt het uitje, *wij de rit.*',
			'tekst_1'       => 'Organiseer jij het bedrijfsuitje, het teamuitje of het personeelsfeest? Dan wil je vooral dat het klopt: iedereen op tijd op de plek, niemand die hoeft te rijden en een prijs die vooraf vaststaat. Met Lekker Geel Busje regel je het vervoer in één keer. We halen collega\'s op bij kantoor of op meerdere opstapplekken, rijden van activiteit naar diner en borrel, en brengen iedereen weer terug.',
			'tekst_2'       => 'En de rit zelf hoort gewoon bij het uitje. Collega\'s die elkaar anders alleen bij de koffieautomaat zien, zitten in de bus bij elkaar. Muziek aan, een drankje van het gastvrouwke en professionele verlichting, lasers en rook zodra het feest mag beginnen. Alles inbegrepen, en binnen 1 werkdag heb je een vaste all-in prijs voor de begroting.',
			'knop'          => thema_link( 'Bekijk onze bussen', $bussen ),
			'beelden'       => array_values( array_filter( array( $b['high-five-bij-het-instappen'], $b['gastvrouw-schenkt-drankje-aan-de-bar'], $b['vriendengroep-aan-tafel-in-sfeerbus'] ) ) ),
			'achtergrond'   => 'standaard',
			'anker'         => 'organisator',
		),
		array(
			'acf_fc_layout' => 'opdrachten',
			'eyebrow'       => 'Voor elk moment met collega\'s',
			'kop'           => 'Van teamuitje tot *kerstborrel*',
			'intro'         => 'Neem je één afdeling mee of het hele bedrijf? Met de bus is iedereen er samen bij, van de eerste toost tot de laatste rit terug.',
			'items'         => array(
				array( 'soort' => 'Afdeling', 'titel' => 'Teamuitje', 'tekst' => 'Een middag eruit met je team. Samen naar de activiteit, samen terug, en onderweg alvast bijpraten zonder laptop.' ),
				array( 'soort' => 'Hele bedrijf', 'titel' => 'Personeelsfeest', 'tekst' => 'Met het hele bedrijf op pad? Dan rijden er meerdere bussen tegelijk. Reserveer op tijd, dan komt iedereen samen aan en samen thuis.' ),
				array( 'soort' => 'December', 'titel' => 'Kerstborrel of kerstuitje', 'tekst' => 'Donker, koud en iedereen wil proosten op het jaar. Wij rijden, jullie hoeven nergens aan te denken.' ),
				array( 'soort' => 'Feest', 'titel' => 'Jubileum', 'tekst' => 'Tien of vijfentwintig jaar? Een feestrit met licht en geluid maakt van het jubileum een avond om lang over na te praten.' ),
				array( 'soort' => 'Collega', 'titel' => 'Afscheid of pensioen', 'tekst' => 'Zwaai een collega uit in stijl: de hele afdeling in de bus, met de vertrekkende collega op de beste plek.' ),
				array( 'soort' => 'Personeelsvereniging', 'titel' => 'PV-uitje', 'tekst' => 'Regel je het jaarlijkse uitje van de personeelsvereniging? Met één vaste all-in prijs is de bijdrage per lid zo uitgerekend.' ),
			),
			'noot'          => '',
			'achtergrond'   => 'paars',
			'anker'         => 'momenten',
		),
		array(
			'acf_fc_layout' => 'route',
			'eyebrow'       => 'Voorbeelddag',
			'kop'           => 'Zo kan jullie *bedrijfsuitje* eruitzien',
			'intro'         => 'Zo ziet een bedrijfsuitje met de bus er bijvoorbeeld uit. Elk team is anders: vertel ons jullie plan, dan rijden wij de route.',
			'stappen'       => array(
				array( 'tijd' => '15:00', 'icoon' => 'bus', 'titel' => 'Ophalen bij kantoor', 'tekst' => 'De gele bus staat voor de deur, of haalt collega\'s op bij meerdere opstapplekken. Laptop dicht, muziek aan.' ),
				array( 'tijd' => '15:30', 'icoon' => 'groep', 'titel' => 'Activiteit met het team', 'tekst' => 'Een workshop, proeverij of spel waarbij de teams het tegen elkaar opnemen. De bus wacht, of komt op een afgesproken tijd terug.' ),
				array( 'tijd' => '18:00', 'icoon' => 'bestek', 'titel' => 'Samen aan tafel', 'tekst' => 'Door naar het diner. Niemand zoekt een parkeerplek en niemand komt als laatste binnen.' ),
				array( 'tijd' => '20:30', 'icoon' => 'muziek', 'titel' => 'Feestrit naar de borrel', 'tekst' => 'Licht, lasers en rook aan. Onderweg een quiz of een toost op het team, en het gastvrouwke zorgt voor de drankjes.' ),
				array( 'tijd' => '21:00', 'icoon' => 'glas', 'titel' => 'Borrel of feest', 'tekst' => 'Afsluiten op de feestlocatie of in de kroeg. Jullie kiezen de plek, wij zetten jullie voor de deur af.' ),
				array( 'tijd' => 'Einde avond', 'icoon' => 'huis', 'titel' => 'Iedereen veilig terug', 'tekst' => 'Op de afgesproken tijd staat de bus klaar en brengen we iedereen terug naar kantoor of naar de opstapplekken.' ),
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
			'eyebrow'       => 'Voor de organisator',
			'kop'           => 'Organiseer jij het *bedrijfsuitje?*',
			'intro'         => 'Ideeën voor een origineel uitje, een stappenplan met checklist en spellen voor onderweg. Zo weet je zeker dat het uitje staat.',
			'keuze'         => array(),
			'onderwerp'     => $teamuitjes ? (int) $teamuitjes->term_id : 0,
			'aantal'        => 3,
			'knop'          => $teamuitjes ? thema_link( 'Alle artikelen over teamuitjes', get_category_link( $teamuitjes ) ) : '',
			'achtergrond'   => 'standaard',
			'anker'         => 'organiseren',
		),
		$faq,
		$offerte,
	), $id );

	$duim = $b['vriendengroep-aan-tafel-in-sfeerbus'] ?? 0;
	if ( $duim && ! has_post_thumbnail( $id ) ) {
		set_post_thumbnail( $id, $duim );
	}
	clean_post_cache( $id );
	return array( 'pagina' => $id, 'artikelen' => $artikelen );
}

/**
 * De artikelen. Algemeen gehouden: geen plaatsnamen of namen van zaken.
 */
function thema_bedrijfsuitje_artikelen() {
	$landing     = home_url( '/gelegenheden/bedrijfsuitje/' );
	$ideeen      = thema_bedrijfsuitje_link( 'bedrijfsuitje-ideeen' );
	$organiseren = thema_bedrijfsuitje_link( 'bedrijfsuitje-organiseren' );
	$spellen     = thema_bedrijfsuitje_link( 'teambuilding-spellen-collegas-bus' );
	$kerst       = thema_bedrijfsuitje_link( 'kerstborrel-kerstuitje-organiseren' );
	$winter      = thema_bedrijfsuitje_link( 'bedrijfsuitje-winter-ideeen' );
	$wkr         = thema_bedrijfsuitje_link( 'bedrijfsuitje-werkkostenregeling' );

	return array(

		/* 1. Ideeën. */
		array(
			'slug'         => 'bedrijfsuitje-ideeen',
			'titel'        => 'Bedrijfsuitje ideeën: 25 originele uitjes voor elk team',
			'seo_titel'    => 'Bedrijfsuitje ideeën: 25 originele uitjes | Lekker Geel Busje',
			'samenvatting' => 'Op zoek naar een origineel bedrijfsuitje of teamuitje? 25 ideeën in vijf richtingen, van actief en culinair tot creatief, feestelijk en net even anders.',
			'beeld'        => 'handen-in-de-lucht-in-de-gele-bus',
			'kort'         => "Kies een uitje waar iedereen aan mee kan doen, niet alleen de sportievelingen.\nCombineer één activiteit met samen eten en een borrel.\nRegel vervoer voor de hele groep, dan begint het uitje al bij vertrek.",
			'faq_kop'      => 'Vragen over *ideeën*',
			'offerte_kop'  => 'Idee gekozen? *Wij rijden.*',
			'vragen'       => array(
				array( 'Wat is een leuk bedrijfsuitje?', 'Een uitje waar iedereen aan mee kan doen en dat past bij jullie team. Een activiteit in de middag, samen eten en een borrel of feest is een vorm die bijna altijd werkt. De activiteit zelf kies je op basis van de groep: actief, culinair, creatief of feestelijk.' ),
				array( 'Wat is een origineel teamuitje?', 'Iets wat het team nog niet eerder deed en waar je samen om kunt lachen. Denk aan een mystery tour waarbij niemand weet waar de bus naartoe rijdt, een dinnershow of een uitje met overnachting.' ),
				array( 'Wat is het verschil tussen een bedrijfsuitje en een teamuitje?', 'Een teamuitje is meestal voor één team of afdeling, een bedrijfsuitje voor het hele bedrijf. Personeelsuitje en PV-uitje zijn andere woorden voor hetzelfde. De ideeën werken voor allemaal, alleen de groepsgrootte verschilt.' ),
			),
			'blokken'      => array(),
			'tekst'        => <<<HTML
<p>Het bedrijfsuitje staat in de agenda, maar wat ga je eigenlijk doen? Een goed uitje past bij het team, werkt voor iedereen in de groep en laat genoeg ruimte om gewoon samen te zijn. We rijden geregeld teams en bedrijven rond en zien wat werkt. Hier vind je 25 ideeën in vijf richtingen. Combineer er één met samen eten en een borrel, en het uitje staat.</p>

<h2>Actief: voor teams die willen bewegen</h2>
<ul>
<li><strong>Escape room:</strong> samen puzzelen tegen de klok. Verdeel de groep in teams en kijk wie als eerste buiten staat.</li>
<li><strong>Zeskamp of spelmiddag:</strong> een reeks gekke spellen in teams, waarbij samenwerken belangrijker is dan conditie.</li>
<li><strong>Lasergamen of boogschieten:</strong> fanatiek en vol lachmomenten, ook voor wie niet sportief is.</li>
<li><strong>Suppen of kanoën:</strong> heerlijk in de zomer, met grote kans op een nat pak voor de leidinggevende.</li>
<li><strong>Klimmen:</strong> elkaar zekeren is letterlijk vertrouwen op je collega's.</li>
</ul>

<h2>Proeven en eten: voor de genieters</h2>
<ul>
<li><strong>Kookworkshop:</strong> samen koken en daarna samen aan tafel, dan is het diner meteen geregeld.</li>
<li><strong>Bierproeverij bij een brouwerij:</strong> proeven, vergelijken en ontdekken wie de echte kenner van het team is.</li>
<li><strong>Wijnproeverij:</strong> een rustige start van de middag, met veel om over te praten.</li>
<li><strong>Cocktailworkshop:</strong> iedereen shaket zijn eigen drankje. Alcoholvrije varianten maken het voor iedereen leuk.</li>
<li><strong>Chocolade- of koffieworkshop:</strong> proeven, leren en zelf maken. Ook leuk voor collega's die geen alcohol drinken.</li>
</ul>

<h2>Creatief en slim: voor de denkers</h2>
<ul>
<li><strong>Pubquiz:</strong> teams per afdeling of juist door elkaar, met een ronde vragen over het eigen bedrijf.</li>
<li><strong>Stadsspel of puzzeltocht:</strong> met opdrachten op de telefoon de stad door, als team tegen team.</li>
<li><strong>Schilder- of graffitiworkshop:</strong> samen een kunstwerk maken dat daarna op kantoor komt te hangen.</li>
<li><strong>Muziekworkshop:</strong> samen een nummer opnemen of leren drummen. Herrie gegarandeerd.</li>
<li><strong>Fotojacht:</strong> een lijst foto-opdrachten voor onderweg, met een prijs voor het beste team.</li>
</ul>

<h2>Feest en sfeer: voor teams die iets te vieren hebben</h2>
<ul>
<li><strong>Themafeest:</strong> iedereen verkleed in hetzelfde thema, van jaren tachtig tot tropisch.</li>
<li><strong>Karaoke:</strong> de directie opent, de rest volgt.</li>
<li><strong>Casino-avond:</strong> met speelgeld aan de tafels, de grootste winnaar krijgt de eer.</li>
<li><strong>Concert of festival:</strong> kies een avond met muziek waar het hele team blij van wordt.</li>
<li><strong>Feestrit met de bus:</strong> licht, lasers, rook en muziek aan boord. Zo begint het feest al voordat jullie er zijn.</li>
</ul>

<h2>Net even anders: voor teams die alles al gedaan hebben</h2>
<ul>
<li><strong>Mystery tour:</strong> iedereen stapt in, niemand weet waar de bus naartoe rijdt. Alleen de organisator en de chauffeur kennen de bestemming.</li>
<li><strong>Dinnershow:</strong> eten, muziek en een show in één avond, zonder dat je zelf iets hoeft te organiseren.</li>
<li><strong>Uitje met overnachting:</strong> twee dagen weg met het team, met een feestavond die niet hoeft te stoppen voor de laatste trein.</li>
<li><strong>Vrijwilligersdag:</strong> een dag samen de handen uit de mouwen voor een goed doel, en daarna samen proosten.</li>
<li><strong>Attractiepark:</strong> een dag in de achtbaan met het hele team. Iedereen is even weer twaalf.</li>
</ul>

<h2>Zo kies je het juiste bedrijfsuitje</h2>
<ul>
<li><strong>Kijk naar de groep:</strong> hoe groot is het team, hoe gemengd is het en kan iedereen meedoen? Een uitje waarbij de helft aan de kant staat, verbindt niemand.</li>
<li><strong>Bepaal het doel:</strong> wil je het team beter laten samenwerken, of vooral bedanken voor een goed jaar? Bij het eerste past een spel in teams, bij het tweede een feest.</li>
<li><strong>Denk aan wie niet drinkt:</strong> zorg dat er bij elke proeverij en borrel ook iets lekkers zonder alcohol is.</li>
<li><strong>Houd rekening met het seizoen:</strong> buiten is heerlijk in de zomer, maar in de winter wil je een plan voor binnen. Lees onze ideeën voor een <a href="{$winter}">bedrijfsuitje in de winter</a>.</li>
<li><strong>Plan niet te vol:</strong> één activiteit, samen eten en een borrel is genoeg voor een dag die iedereen onthoudt.</li>
</ul>

<h2>Teamuitje, bedrijfsuitje of personeelsuitje?</h2>
<p>Een teamuitje is meestal voor één team of afdeling, een bedrijfsuitje of personeelsuitje voor het hele bedrijf. Organiseert de personeelsvereniging het, dan heet het vaak een PV-uitje. De ideeën hierboven werken voor allemaal. Het verschil zit vooral in de groepsgrootte: vraag bij elke activiteit naar het maximum aantal deelnemers voordat je kiest.</p>

<h2>Begin het uitje in de bus</h2>
<p>Hoe leuk de activiteit ook is, met een groep collega's van A naar B gaan is vaak gedoe. Wie rijdt, waar parkeer je en wie komt als laatste binnen? Met een <a href="{$landing}">partybus voor je bedrijfsuitje</a> rijdt iedereen samen, hoeft niemand de Bob te zijn en wordt de rit onderdeel van het uitje. Met een paar <a href="{$spellen}">spellen voor in de bus</a> vliegt de tijd voorbij.</p>
<p>Idee gekozen? Gebruik ons <a href="{$organiseren}">stappenplan voor het organiseren van een bedrijfsuitje</a>, dan weet je zeker dat je niets vergeet.</p>
HTML,
		),

		/* 2. Organiseren, met checklist. */
		array(
			'slug'         => 'bedrijfsuitje-organiseren',
			'titel'        => 'Bedrijfsuitje organiseren: stappenplan en checklist',
			'seo_titel'    => 'Bedrijfsuitje organiseren in 10 stappen | Lekker Geel Busje',
			'samenvatting' => 'Organiseer jij het bedrijfsuitje, teamuitje of personeelsfeest? Met dit stappenplan in tien stappen en een checklist om af te vinken regel je alles op tijd.',
			'beeld'        => 'gastvrouw-verwelkomt-gasten-bij-de-bus',
			'kort'         => "Begin drie maanden van tevoren met het doel, de datum en het budget.\nPeil vooraf wat collega's willen en waar ze rekening mee moeten houden.\nRegel het vervoer in één keer, dan komt iedereen samen aan en samen thuis.",
			'faq_kop'      => 'Vragen over het *organiseren*',
			'offerte_kop'  => 'Vervoer voor het bedrijfsuitje *geregeld?*',
			'vragen'       => array(
				array( 'Hoe ver van tevoren organiseer je een bedrijfsuitje?', 'Begin twee tot drie maanden van tevoren. Voor een personeelsfeest of een uitje in december mag het nog eerder, want dan zitten locaties, restaurants en vervoer snel vol.' ),
				array( 'Valt een bedrijfsuitje onder de werkkostenregeling?', 'Ja. De kosten van een uitje zijn loon, maar de werkgever kan ze onderbrengen in de vrije ruimte van de werkkostenregeling. In 2026 is die 2% van de fiscale loonsom tot € 400.000 en 1,18% daarboven. Boven de vrije ruimte betaalt de werkgever 80% eindheffing. Vraag de boekhouder hoeveel ruimte er nog over is.' ),
				array( 'Hoe zorg je dat iedereen meedoet?', 'Vraag vooraf wat collega\'s leuk vinden, kies een activiteit waaraan iedereen kan meedoen en zorg voor genoeg keuze zonder alcohol. En plan het uitje op een moment dat het grootste deel van het team kan.' ),
			),
			'blokken'      => array(
				array(
					'acf_fc_layout' => 'checklist',
					'eyebrow'       => 'Checklist',
					'kop'           => 'Vink het *zelf af*',
					'intro'         => 'Je vinkjes blijven op dit apparaat bewaard, dus kom gerust later terug om verder te gaan.',
					'items'         => array(
						array( 'wanneer' => 'Drie maanden van tevoren', 'taak' => 'Doel en soort uitje bepalen', 'link' => '' ),
						array( 'wanneer' => 'Drie maanden van tevoren', 'taak' => 'Datum vastleggen en in de agenda\'s zetten', 'link' => '' ),
						array( 'wanneer' => 'Drie maanden van tevoren', 'taak' => 'Budget afstemmen, ook met de werkkostenregeling', 'link' => '' ),
						array( 'wanneer' => 'Twee maanden van tevoren', 'taak' => 'Wensen peilen bij collega\'s', 'link' => '' ),
						array( 'wanneer' => 'Twee maanden van tevoren', 'taak' => 'Activiteit kiezen en reserveren', 'link' => '' ),
						array( 'wanneer' => 'Twee maanden van tevoren', 'taak' => 'Vervoer aanvragen met datum, aantal en ophaalplek', 'link' => array( 'title' => 'Vraag je prijs aan', 'url' => '#offerte', 'target' => '' ) ),
						array( 'wanneer' => 'Een maand van tevoren', 'taak' => 'Restaurant of feestlocatie reserveren', 'link' => '' ),
						array( 'wanneer' => 'Een maand van tevoren', 'taak' => 'Uitnodiging met programma versturen', 'link' => '' ),
						array( 'wanneer' => 'Twee weken van tevoren', 'taak' => 'Aanmeldingen, dieetwensen en opstapplekken verzamelen', 'link' => '' ),
						array( 'wanneer' => 'De week ervoor', 'taak' => 'Draaiboek met tijden en adressen delen', 'link' => '' ),
						array( 'wanneer' => 'De week ervoor', 'taak' => 'Spellen of een quiz voor onderweg voorbereiden', 'link' => '' ),
						array( 'wanneer' => 'Op de dag zelf', 'taak' => 'Genieten. De rit is geregeld.', 'link' => '' ),
					),
					'knop'          => '',
					'achtergrond'   => 'standaard',
					'anker'         => 'afvinken',
				),
			),
			'tekst'        => <<<HTML
<p>Je bent gevraagd om het bedrijfsuitje te organiseren. Leuk, en ook een klus naast je gewone werk. Want hoe zorg je dat iedereen het naar zijn zin heeft, dat het budget klopt en dat je zelf ook nog geniet? Met dit stappenplan in tien stappen regel je het teamuitje, personeelsuitje of bedrijfsfeest zonder gedoe. Onderaan staat een checklist om af te vinken.</p>

<h2>1. Bepaal het doel van het uitje</h2>
<p>Wil je het team beter laten samenwerken, nieuwe collega's laten kennismaken of vooral iedereen bedanken voor een goed jaar? Het doel bepaalt de rest. Bij samenwerken past een spel of workshop in teams, bij bedanken een feest of een goed diner.</p>

<h2>2. Leg de datum en het tijdstip vast</h2>
<p>Kies een datum waarop het grootste deel van het team kan, en zet hem ruim van tevoren in de agenda's. Een uitje dat in de middag begint en in de avond eindigt, werkt goed: het voelt als een cadeau van de werkgever en de avond is vrij om door te gaan. Vrijdagen zitten snel vol, bij locaties en ook bij vervoer. Een donderdag is vaak een slimme keuze.</p>

<h2>3. Spreek het budget af</h2>
<p>Tel alles bij elkaar op: activiteit, eten en drinken, locatie, vervoer en eventueel een aandenken. Deel het door het aantal deelnemers, dan weet je wat het per persoon mag kosten.</p>
<p>Houd daarbij rekening met de werkkostenregeling (WKR). Een bedrijfsuitje telt mee in de vrije ruimte: in 2026 is dat 2% van de fiscale loonsom tot € 400.000 en 1,18% over het deel daarboven. Ga je erover, dan betaalt de werkgever 80% eindheffing over het meerdere. Een uitje buiten de deur telt met de volledige rekening mee, een borrel op de eigen werkplek veel minder. Hoe dat precies werkt, lees je in <a href="{$wkr}">bedrijfsuitje en de werkkostenregeling</a>. Check altijd even met de boekhouder hoeveel vrije ruimte er dit jaar nog over is.</p>

<h2>4. Peil wat collega's willen</h2>
<p>Een korte vragenlijst of een poll in de groepsapp voorkomt een uitje waar de helft geen zin in heeft. Vraag naar voorkeuren en naar dingen waar je rekening mee moet houden, zoals dieetwensen, mobiliteit of collega's die niet drinken. Geef twee of drie opties om uit te kiezen, geen open vraag.</p>

<h2>5. Kies het programma</h2>
<p>Eén activiteit, samen eten en een borrel of feest is genoeg. Elk extra onderdeel kost reistijd en energie. Zoek je inspiratie? Bekijk onze <a href="{$ideeen}">25 ideeën voor een bedrijfsuitje</a>. Laat tussen de onderdelen wat lucht, zodat er ook tijd is om gewoon te kletsen.</p>

<h2>6. Reserveer de locatie en het eten</h2>
<p>Vraag bij elke locatie naar het maximum aantal personen, de mogelijkheden voor dieetwensen en een plan bij slecht weer. Plan het eten niet te laat: een groep die de hele middag actief is geweest, heeft om zes uur echt een bord nodig.</p>

<h2>7. Regel het vervoer in één keer</h2>
<p>Met veertig of tachtig collega's van kantoor naar de activiteit, het restaurant en de borrel is een puzzel. Wie rijdt, wie neemt de trein en wie raakt onderweg kwijt? Met een eigen bus blijft de groep bij elkaar, komt iedereen tegelijk aan en hoeft niemand de Bob te zijn. Gaat het hele bedrijf mee, dan rijden er gewoon meerdere bussen. Reserveer dan wel op tijd.</p>
<p>Met een <a href="{$landing}">partybus voor je bedrijfsuitje</a> halen we collega's op bij kantoor of op meerdere opstapplekken, en brengen we iedereen na afloop terug. Je krijgt binnen 1 werkdag een vaste all-in prijs, met chauffeur, gastvrouwke, licht en geluid erin. Dat zet je zo in de begroting.</p>

<h2>8. Verstuur een duidelijke uitnodiging</h2>
<p>Een goede uitnodiging voor het bedrijfsuitje vertelt wanneer het is, hoe laat het begint en eindigt, wat de dresscode is en of partners welkom zijn. Vraag om een aanmelding voor een vaste datum, en meteen naar dieetwensen en de gewenste opstapplek. Houd het programma gerust een beetje geheim: een verrassing maakt nieuwsgierig.</p>

<h2>9. Maak een draaiboek voor de dag</h2>
<p>Zet op één pagina hoe laat jullie waar moeten zijn, met adressen, telefoonnummers en wie het aanspreekpunt is. Deel het met de collega's die je helpen, en stuur de groep de dag ervoor een korte herinnering. Neem voor onderweg een quiz of een paar <a href="{$spellen}">teambuildingspellen voor in de bus</a> mee.</p>

<h2>10. Sluit goed af</h2>
<p>Deel na afloop de mooiste foto's, bedank de mensen die geholpen hebben en vraag kort wat collega's ervan vonden. Dan weet je meteen wat je volgend jaar weer doet, en wat niet.</p>

<h2>Personeelsfeest of bedrijfsfeest organiseren</h2>
<p>Een personeelsfeest of bedrijfsfeest werkt volgens dezelfde stappen, alleen staat het feest centraal in plaats van een activiteit. Kies een locatie met ruimte om te dansen, denk aan muziek en eten dat de hele avond doorloopt, en regel vooral het vervoer naar huis. Een feest dat eindigt met een rij collega's die nog een taxi zoeken, eindigt minder leuk dan het begon.</p>

<h2>Veelgemaakte fouten</h2>
<ul>
<li>Te laat beginnen, waardoor de leukste locatie of het vervoer al vol zit.</li>
<li>Een activiteit kiezen die alleen de organisator leuk vindt.</li>
<li>Geen rekening houden met collega's die niet drinken of niet mee kunnen doen.</li>
<li>Een programma zo vol dat er geen tijd is om gewoon samen te zijn.</li>
<li>Het vervoer naar huis vergeten na een avond feest.</li>
</ul>
HTML,
		),

		/* 3. Spellen en opdrachten voor in de bus. */
		array(
			'slug'         => 'teambuilding-spellen-collegas-bus',
			'titel'        => 'Teambuilding in de bus: 8 spellen en opdrachten voor collega\'s',
			'seo_titel'    => 'Teambuilding opdrachten voor collega\'s | Lekker Geel Busje',
			'samenvatting' => 'Acht teambuilding spellen en opdrachten die je met collega\'s gewoon samen in de bus speelt. Plus tien ijsbrekervragen om elkaar beter te leren kennen.',
			'beeld'        => 'gasten-lachen-aan-tafel-in-sfeerbus',
			'kort'         => "Alle acht opdrachten speel je gewoon in de bus, ook tijdens de rit.\nMix afdelingen in de teams, dan leren collega's elkaar echt kennen.\nBereid de quiz en de bingokaarten een week van tevoren voor.",
			'faq_kop'      => 'Vragen over *teambuilding*',
			'offerte_kop'  => 'Spellen klaar? *Wij rijden.*',
			'vragen'       => array(
				array( 'Welke teambuilding opdrachten kun je in de bus doen?', 'Spellen die je met de hele groep in de bus speelt: een pubquiz in teams, bedrijfsbingo, twee waarheden en een leugen of een complimentenronde. Ze werken met elke groepsgrootte en iedereen doet mee.' ),
				array( 'Wat zijn goede ijsbrekervragen voor collega\'s?', 'Vragen die makkelijk te beantwoorden zijn en toch iets vertellen, zoals wat je eerste baantje was of welk talent niemand van je kent. Houd ze luchtig en persoonlijk, maar nooit te privé.' ),
				array( 'Hoe houd je het leuk voor collega\'s die niet graag in de schijnwerpers staan?', 'Speel in teams in plaats van alleen, en laat iedereen zelf kiezen of hij iets voorleest of zingt. Een quiz of bingokaart werkt voor iedereen, ook voor de stillere collega\'s.' ),
			),
			'blokken'      => array(
				array(
					'acf_fc_layout' => 'opdrachten',
					'eyebrow'       => 'Voor onderweg',
					'kop'           => 'Acht spellen voor *in de bus*',
					'intro'         => 'Allemaal in de bus te doen. Bereid ze voor, dan vliegt de tijd tussen twee stops voorbij.',
					'items'         => array(
						array( 'soort' => 'Quiz', 'titel' => 'Pubquiz op wielen', 'tekst' => 'Elke rij is een team. Een quizmaster leest de vragen voor, met een ronde over het eigen bedrijf: wanneer is het opgericht, wie werkt er het langst en wat staat er op het bord in de kantine?' ),
						array( 'soort' => 'Kennismaken', 'titel' => 'Van wie is dit feitje?', 'tekst' => 'Iedereen schrijft vooraf een onbekend feitje over zichzelf op. Lees ze voor in de bus en laat de groep raden van wie het is. Ideaal als afdelingen elkaar nog niet goed kennen.' ),
						array( 'soort' => 'IJsbreker', 'titel' => 'Twee waarheden en een leugen', 'tekst' => 'Om de beurt vertel je drie dingen over jezelf, waarvan er één niet klopt. De rest stemt. Wie de groep het vaakst voor de gek houdt, wint.' ),
						array( 'soort' => 'Hele dag', 'titel' => 'Bedrijfsbingo', 'tekst' => 'Maak bingokaarten met dingen die vandaag gaan gebeuren: iemand noemt een afkorting van het werk, de leidinggevende zingt mee, er wordt getoost op het team. Wie heeft als eerste een volle rij?' ),
						array( 'soort' => 'Lachen', 'titel' => 'Raad de babyfoto', 'tekst' => 'Verzamel vooraf een babyfoto van elke collega en deel ze één voor één in de groepsapp. Wie herkent de meeste collega\'s?' ),
						array( 'soort' => 'Muziek', 'titel' => 'Zing de volgende regel', 'tekst' => 'Zet een nummer na een paar seconden stil. Welk team zingt de volgende regel goed? Begin met de favoriet van de afdeling.' ),
						array( 'soort' => 'Toekomst', 'titel' => 'Het bedrijf over tien jaar', 'tekst' => 'Elke rij bedenkt om de beurt één zin over hoe het bedrijf er over tien jaar uitziet. Serieus mag, ludiek mag ook. Lees het verhaal aan het eind van de dag voor.' ),
						array( 'soort' => 'Proosten', 'titel' => 'De complimentenronde', 'tekst' => 'Iedereen trekt een naam uit een pot en geeft die collega bij de volgende toost een compliment. Kort, gemeend, glazen omhoog.' ),
					),
					'noot'          => 'Alles in de bus te doen en niets wat de chauffeur afleidt. Zo blijft het leuk voor iedereen.',
					'achtergrond'   => 'paars',
					'anker'         => 'opdrachten',
				),
			),
			'tekst'        => <<<HTML
<p>De rit naar het bedrijfsuitje is geen dode tijd. Juist in de bus zitten collega's bij elkaar die elkaar anders alleen in een overleg zien. Met een paar goede spellen wordt de rit een van de leukste momenten van de dag, en is de teambuilding al begonnen voordat jullie er zijn. Hieronder vind je acht opdrachten die je allemaal samen in de bus speelt, plus tien ijsbrekervragen.</p>

<h2>Waarom teambuilding in de bus zo goed werkt</h2>
<p>In de bus is iedereen samen, van stagiair tot directeur. Niemand zit achter een laptop en niemand kan ontsnappen naar zijn eigen bureau. Een quiz of bingokaart brengt collega's van verschillende afdelingen sneller aan de praat dan een lange tafel in een restaurant. En omdat een rit een vast begin en einde heeft, past er precies een spel of twee in.</p>
<p>In een <a href="{$landing}">partybus voor je bedrijfsuitje</a> zitten licht, geluid en een gastvrouwke dat voor de drankjes zorgt. Jij hoeft alleen nog de spellen mee te nemen.</p>

<h2>Zo bereid je de spellen voor</h2>
<ul>
<li><strong>Een week van tevoren:</strong> verzamel de feitjes en babyfoto's, stel de quizvragen op en maak de bingokaarten.</li>
<li><strong>Maak gemengde teams:</strong> zet collega's van verschillende afdelingen bij elkaar, dan leren ze elkaar echt kennen.</li>
<li><strong>Verdeel de rollen:</strong> wie is quizmaster, wie houdt de score bij en wie doet de muziek?</li>
<li><strong>Neem een tas mee</strong> met kaartjes, pennen, de bingokaarten en een paar kleine prijsjes.</li>
<li><strong>Plan per rit één of twee spellen,</strong> dan blijft er ook tijd om gewoon bij te praten.</li>
</ul>

<h2>Tien ijsbrekervragen voor collega's</h2>
<p>Kennen niet alle collega's elkaar goed? Begin de rit met een paar ijsbrekervragen. Laat iedereen er één beantwoorden, of stel ze aan de hele bus.</p>
<ul>
<li>Wat was je allereerste baantje?</li>
<li>Welk talent heb je dat niemand op het werk kent?</li>
<li>Wat is het beste advies dat je ooit hebt gekregen?</li>
<li>Als je één dag een andere functie in het bedrijf mocht hebben, welke zou het zijn?</li>
<li>Welk nummer mag op dit uitje niet ontbreken?</li>
<li>Wat is de leukste vakantie die je ooit hebt gehad?</li>
<li>Waar werd je als kind blij van?</li>
<li>Wat is je verborgen guilty pleasure op tv?</li>
<li>Wat zou je doen met een extra vrije dag per week?</li>
<li>Wat is het grappigste dat je ooit op het werk hebt meegemaakt?</li>
</ul>

<h2>Tips voor een geslaagd spel</h2>
<ul>
<li><strong>Houd het luchtig:</strong> het gaat om samen lachen, niet om wie het slimst is.</li>
<li><strong>Speel in teams:</strong> dan doen ook de stillere collega's mee zonder in de schijnwerpers te staan.</li>
<li><strong>Kies niets te privés:</strong> een gênante vraag op een feestje is iets anders dan met collega's.</li>
<li><strong>Zet een prijs op het spel:</strong> een zelfgemaakte wisselbeker of de eer om de volgende borrel te kiezen.</li>
</ul>
<p>Zoek je ideeën voor de rest van de dag? Bekijk onze <a href="{$ideeen}">25 ideeën voor een bedrijfsuitje</a>, en vink in het <a href="{$organiseren}">stappenplan</a> af wat je al geregeld hebt.</p>
HTML,
		),

		/* 4. Kerst. */
		array(
			'slug'         => 'kerstborrel-kerstuitje-organiseren',
			'titel'        => 'Kerstborrel of kerstuitje organiseren: 12 ideeën en een planning',
			'seo_titel'    => 'Kerstborrel of kerstuitje organiseren | Lekker Geel Busje',
			'samenvatting' => 'Kerstborrel of kerstuitje met collega\'s organiseren? Twaalf ideeën voor op kantoor en op pad, een planning en tips om december zonder stress af te sluiten.',
			'beeld'        => 'gele-sfeerbus-in-de-avond-op-het-plein',
			'kort'         => "December zit snel vol: leg de datum het liefst in september of oktober vast.\nKies tussen een borrel op kantoor of een kerstuitje op pad.\nIn het donker en de kou is vervoer voor de hele groep extra fijn.",
			'faq_kop'      => 'Vragen over de *kerstborrel*',
			'offerte_kop'  => 'Kerstuitje gepland? *Wij rijden.*',
			'vragen'       => array(
				array( 'Wanneer houd je de kerstborrel?', 'Meestal in de tweede of derde week van december, voordat collega\'s op vakantie gaan. Een donderdag is populair. Is december al te vol, dan is een nieuwjaarsborrel in januari een goed alternatief.' ),
				array( 'Hoe ver van tevoren regel je het kerstuitje?', 'Het liefst drie maanden van tevoren. In december zijn restaurants, locaties en vervoer snel volgeboekt, vooral op donderdag en vrijdag. Laat je het later? Vraag dan meteen bij alles tegelijk na wat er nog kan.' ),
				array( 'Wat is het verschil tussen een kerstborrel en een kerstuitje?', 'Een kerstborrel is meestal een paar uur samen proosten, vaak op kantoor of in een café. Een kerstuitje is een groter programma met een activiteit, een diner of een feest, vaak op een andere plek.' ),
			),
			'blokken'      => array(),
			'tekst'        => <<<HTML
<p>December is de maand om met het team terug te kijken op het jaar en samen te proosten. Maar of het nou een korte kerstborrel wordt of een compleet kerstuitje: het moet wel geregeld worden, in de drukste maand van het jaar. Hier vind je twaalf ideeën, een planning en tips om het zonder stress te organiseren.</p>

<h2>Kerstborrel of kerstuitje: wat past bij jullie?</h2>
<p>Een <strong>kerstborrel</strong> is kort en laagdrempelig: een paar uur samen proosten, met hapjes en muziek, vaak op kantoor of in een café in de buurt. Een <strong>kerstuitje</strong> is een groter programma, met een activiteit, een kerstdiner of een feest op een andere plek. Wil je vooral even samen zijn, dan is een borrel genoeg. Wil je het team echt bedanken voor het jaar, kies dan voor een uitje.</p>

<h2>Zes ideeën voor een kerstborrel op kantoor</h2>
<ul>
<li><strong>Foute kersttruiendag:</strong> iedereen komt in de lelijkste trui, met een prijs voor de winnaar.</li>
<li><strong>Secret Santa:</strong> iedereen trekt een naam en koopt een klein cadeau met een vast bedrag.</li>
<li><strong>Kerstquiz:</strong> vragen over het afgelopen jaar, over kerst en over elkaar. Speel in gemengde teams.</li>
<li><strong>Glühwein- en chocolademelkbar:</strong> warm, gezellig en ook lekker zonder alcohol.</li>
<li><strong>Jaaroverzicht:</strong> een korte diashow met de mooiste momenten en foto's van het jaar.</li>
<li><strong>Kerstkaraoke:</strong> de klassiekers die iedereen kent, de directie zingt het eerste nummer.</li>
</ul>

<h2>Zes ideeën voor een kerstuitje op pad</h2>
<ul>
<li><strong>Kerstdiner op een bijzondere plek:</strong> samen aan een lange tafel, met een toost op het jaar.</li>
<li><strong>Kerstmarkt bezoeken:</strong> slenteren, proeven en samen een warm drankje.</li>
<li><strong>Schaatsen:</strong> op een ijsbaan, met daarna erwtensoep en warme chocolademelk.</li>
<li><strong>Workshop kerststukjes of kransen maken:</strong> creatief, gezellig en iedereen heeft een aandenken.</li>
<li><strong>Kerstfeest met thema:</strong> van gala tot foute kerst, met een dresscode die het hele team meedoet.</li>
<li><strong>Kerstvoorstelling of kerstconcert:</strong> samen naar een show, met daarna een drankje om na te praten.</li>
</ul>

<h2>De planning voor de kerstborrel</h2>
<ul>
<li><strong>September of oktober:</strong> datum vastleggen, budget afspreken en de locatie en het vervoer reserveren. In december zit alles snel vol.</li>
<li><strong>November:</strong> de uitnodiging versturen, aanmeldingen en dieetwensen verzamelen en het programma vastleggen.</li>
<li><strong>Begin december:</strong> secret santa loten, de quiz voorbereiden en de playlist maken.</li>
<li><strong>De week ervoor:</strong> draaiboek delen en iedereen herinneren aan de dresscode.</li>
</ul>
<p>Ben je laat? Geen paniek. Vraag bij de locatie, het restaurant en het vervoer tegelijk na wat er nog kan, en kies dan de datum die overal past. Een uitgebreid stappenplan vind je in <a href="{$organiseren}">bedrijfsuitje organiseren</a>.</p>

<h2>Nog vrije ruimte over in de WKR?</h2>
<p>De vrije ruimte van de werkkostenregeling geldt per kalenderjaar: wat je in 2026 niet gebruikt, vervalt. Is er nog ruimte over, dan is een kerstuitje een mooie manier om die te benutten. Let wel op het verschil: een borrel op de eigen werkplek telt veel minder mee dan een diner of feest buiten de deur. Alles over de regels lees je in <a href="{$wkr}">bedrijfsuitje en de werkkostenregeling</a>.</p>

<h2>Vervoer in december: waarom het extra fijn is</h2>
<p>In december is het vroeg donker, vaak koud en soms glad. En na een kerstborrel wil niemand nog achter het stuur. Met een <a href="{$landing}">partybus voor je bedrijfsuitje</a> rijden jullie samen heen en samen terug. Het gastvrouwke zorgt voor de drankjes, de chauffeur houdt zijn aandacht bij de weg, en met verlichting, lasers en muziek aan boord voelt de rit zelf al als een kerstfeest.</p>

<h2>Liever na de feestdagen? Kies de nieuwjaarsborrel</h2>
<p>Zit december al vol, of wil je het team juist een goede start van het jaar geven? Een nieuwjaarsborrel in januari is een goed alternatief. Locaties en vervoer zijn dan vaak makkelijker te regelen. Ideeën voor een uitje in januari en februari vind je bij <a href="{$winter}">bedrijfsuitje in de winter</a>.</p>
HTML,
		),

		/* 5. Winter en nieuwjaarsborrel. */
		array(
			'slug'         => 'bedrijfsuitje-winter-ideeen',
			'titel'        => 'Bedrijfsuitje in de winter: 12 ideeën voor binnen en buiten',
			'seo_titel'    => 'Bedrijfsuitje in de winter: 12 ideeën | Lekker Geel Busje',
			'samenvatting' => 'Twaalf ideeën voor een bedrijfsuitje of teamuitje in de winter, voor binnen en buiten. Plus tips voor een nieuwjaarsborrel die het jaar goed laat beginnen.',
			'beeld'        => 'verlichte-sfeerbus-van-buiten',
			'kort'         => "In de winter werkt een uitje binnen met een warme afsluiter het best.\nJanuari en februari zijn rustig: makkelijker om te reserveren.\nEen nieuwjaarsborrel is een mooie start van het jaar met het hele team.",
			'faq_kop'      => 'Vragen over een *winteruitje*',
			'offerte_kop'  => 'Winteruitje gepland? *Wij rijden.*',
			'vragen'       => array(
				array( 'Wat is een leuk bedrijfsuitje in de winter?', 'Een activiteit binnen met een warme afsluiter, zoals een kookworkshop, een escape room of een proeverij met daarna samen eten. Wil je naar buiten, kies dan iets korts zoals schaatsen of een winterwandeling, en zorg voor een warme plek erna.' ),
				array( 'Wanneer houd je een nieuwjaarsborrel?', 'In de eerste twee of drie weken van januari, als iedereen terug is van vakantie. Een borrel aan het eind van de middag werkt goed, dan kunnen collega\'s daarna nog door.' ),
				array( 'Is de winter een goed moment voor een teamuitje?', 'Zeker. Januari en februari zijn rustig, dus locaties en vervoer zijn makkelijker te regelen. En na de drukke decembermaand is een uitje een mooie manier om het team weer bij elkaar te brengen.' ),
			),
			'blokken'      => array(),
			'tekst'        => <<<HTML
<p>De zomer is het seizoen van de bedrijfsuitjes, maar de winter is minstens zo leuk. Na een drukke decembermaand is januari of februari een mooi moment om het team weer bij elkaar te brengen. Locaties zijn rustiger, de agenda's leger en een warme afsluiter voelt extra goed. Hier vind je twaalf ideeën voor binnen en buiten, plus tips voor een nieuwjaarsborrel.</p>

<h2>Zes ideeën voor een bedrijfsuitje binnen</h2>
<ul>
<li><strong>Kookworkshop:</strong> samen koken in een warme keuken en daarna samen aan tafel.</li>
<li><strong>Escape room:</strong> puzzelen in teams, met daarna een borrel om na te praten.</li>
<li><strong>Bowlen of glow golf:</strong> laagdrempelig, en iedereen kan meedoen.</li>
<li><strong>Proeverij:</strong> bier, wijn, whisky of chocolade. Proeven en vergelijken werkt in elk seizoen.</li>
<li><strong>Pubquiz:</strong> in een café of op een eigen locatie, met een ronde over het afgelopen jaar.</li>
<li><strong>Indoor karten of lasergamen:</strong> voor teams die een beetje competitie wel kunnen hebben.</li>
</ul>

<h2>Zes ideeën voor een bedrijfsuitje buiten</h2>
<ul>
<li><strong>Schaatsen:</strong> op een ijsbaan of, als het meezit, op natuurijs. Met daarna erwtensoep.</li>
<li><strong>Winterwandeling:</strong> een frisse wandeling door bos of duinen, met glühwein of warme chocolademelk onderweg.</li>
<li><strong>Indoor skiën of snowboarden:</strong> sneeuw gegarandeerd, ook als het buiten regent.</li>
<li><strong>Vuurkorf en barbecue:</strong> winterbarbecue bij het vuur, met dekens en warme drankjes.</li>
<li><strong>Stadsspel in het donker:</strong> een puzzeltocht met zaklampen door de verlichte stad.</li>
<li><strong>Curling:</strong> op het ijs met bezems en stenen. Ook leuk voor wie niet kan schaatsen.</li>
</ul>

<h2>Zo maak je een winteruitje geslaagd</h2>
<ul>
<li><strong>Houd buitenactiviteiten kort:</strong> een uur buiten is genoeg, zorg daarna voor een warme plek.</li>
<li><strong>Vraag iedereen om warme kleding:</strong> zet het in de uitnodiging, met een reservejas in de bus voor wie het vergeet.</li>
<li><strong>Plan op tijd een plan B:</strong> bij storm of gladheid wil je een alternatief binnen.</li>
<li><strong>Begin wat eerder:</strong> het is vroeg donker, dus een buitenactiviteit hoort in de middag.</li>
<li><strong>Regel het vervoer:</strong> in de kou en het donker is het fijn als niemand hoeft te rijden. Met een <a href="{$landing}">partybus voor je bedrijfsuitje</a> zitten jullie warm, met een drankje en muziek aan boord.</li>
</ul>

<h2>Een nieuwjaarsborrel organiseren</h2>
<p>De nieuwjaarsborrel is hét moment om het team een goede start van het jaar te geven. Houd hem in de eerste weken van januari, als iedereen terug is van vakantie. Een paar ideeën om hem bijzonder te maken:</p>
<ul>
<li><strong>Terugblik en vooruitblik:</strong> een korte toespraak over het afgelopen jaar en de plannen voor het nieuwe.</li>
<li><strong>Goede voornemens van het team:</strong> iedereen schrijft er één op een kaartje, en aan het eind van het jaar lees je ze terug.</li>
<li><strong>Oliebollen en champagne:</strong> een klassieker die altijd werkt, ook met alcoholvrije bubbels.</li>
<li><strong>Nieuwjaarsuitje in plaats van een borrel:</strong> combineer de borrel met een activiteit en een diner, en maak er een middag van.</li>
</ul>
<p>Liever in december? Lees dan onze tips voor een <a href="{$kerst}">kerstborrel of kerstuitje</a>. En zoek je nog meer inspiratie, bekijk dan onze <a href="{$ideeen}">25 ideeën voor een bedrijfsuitje</a>.</p>
HTML,
		),

		/* 6. Werkkostenregeling. Cijfers 2026 gecheckt op 17 nov 2026 bij de Belastingdienst en vakmedia. */
		array(
			'slug'         => 'bedrijfsuitje-werkkostenregeling',
			'titel'        => 'Bedrijfsuitje en de werkkostenregeling: zo zit het in 2026',
			'seo_titel'    => 'Bedrijfsuitje en de WKR in 2026 | Lekker Geel Busje',
			'samenvatting' => 'Valt een bedrijfsuitje of personeelsfeest onder de werkkostenregeling? De vrije ruimte in 2026, het verschil tussen de werkplek en buiten de deur, en een rekenvoorbeeld.',
			'beeld'        => 'drankjes-halen-aan-de-bar-in-de-bus',
			'kort'         => "Een bedrijfsuitje valt in de vrije ruimte: in 2026 is dat 2% van de fiscale loonsom tot € 400.000 en 1,18% daarboven.\nBuiten de deur telt de hele rekening mee, op de eigen werkplek een stuk minder.\nOnbenutte vrije ruimte vervalt aan het eind van het jaar.",
			'faq_kop'      => 'Vragen over de *werkkostenregeling*',
			'offerte_kop'  => 'Weet je je budget? *Wij rijden.*',
			'vragen'       => array(
				array( 'Valt een personeelsfeest onder de werkkostenregeling?', 'Ja. De kosten zijn loon voor de werknemers, maar de werkgever kan ze aanwijzen als eindheffingsloon en onderbrengen in de vrije ruimte. Zolang je binnen die ruimte blijft, betaalt niemand er belasting over.' ),
				array( 'Wat gebeurt er als je over de vrije ruimte heen gaat?', 'Dan betaalt de werkgever 80% eindheffing over het bedrag boven de vrije ruimte. De werknemers merken daar niets van. Houd daarom door het jaar heen bij hoeveel ruimte je al gebruikt.' ),
				array( 'Is een feest op kantoor fiscaal voordeliger?', 'Vaak wel. Op de eigen werkplek tellen hapjes en drankjes niet mee en een maaltijd telt voor een vast normbedrag. Buiten de deur telt de hele rekening mee. Of dat opweegt tegen een uitje dat iedereen onthoudt, is aan jullie.' ),
			),
			'blokken'      => array(),
			'tekst'        => <<<HTML
<p>Een bedrijfsuitje kost geld, en dan wil je weten hoe de Belastingdienst ernaar kijkt. Het antwoord zit in de werkkostenregeling, de WKR. Hieronder lees je hoe het in 2026 werkt voor een teamuitje, personeelsfeest of kerstborrel, met een rekenvoorbeeld en links naar de bronnen. Dit is algemene informatie, geen fiscaal advies: leg je eigen situatie altijd even voor aan je boekhouder.</p>

<h2>Wat is de werkkostenregeling?</h2>
<p>Met de werkkostenregeling mag een werkgever een deel van de fiscale loonsom onbelast besteden aan zijn personeel, bijvoorbeeld aan een kerstpakket, een bedrijfsfitness of een personeelsuitje. Dat deel heet de vrije ruimte. Volgens de <a href="https://www.belastingdienst.nl/wps/wcm/connect/nl/personeel-en-loon/content/werkkostenregeling" target="_blank" rel="noopener">Belastingdienst</a> is de vrije ruimte in 2026:</p>
<ul>
<li><strong>2%</strong> over de fiscale loonsom tot en met € 400.000;</li>
<li><strong>1,18%</strong> over het deel daarboven.</li>
</ul>
<p>Voor 2027 staat een verhoging van de eerste schijf naar 2,16% gepland, onder voorbehoud van het Belastingplan.</p>

<h2>Rekenvoorbeeld</h2>
<ul>
<li>Fiscale loonsom € 400.000: de vrije ruimte is 2% daarvan, dus <strong>€ 8.000</strong>.</li>
<li>Fiscale loonsom € 1.000.000: 2% over € 400.000 is € 8.000, plus 1,18% over € 600.000 is € 7.080. Samen <strong>€ 15.080</strong>.</li>
</ul>
<p>Let op: dat bedrag is voor alles wat je in de vrije ruimte onderbrengt, dus ook voor kerstpakketten, cadeaus en andere vergoedingen. Het uitje is daar één onderdeel van.</p>

<h2>Valt een bedrijfsuitje onder de WKR?</h2>
<p>Ja. De kosten van een uitje of personeelsfeest zijn loon voor je werknemers. Als werkgever wijs je ze aan als eindheffingsloon en breng je ze onder in de vrije ruimte. Blijf je daarbinnen, dan betaalt niemand er belasting over. Ga je eroverheen, dan betaalt de werkgever 80% eindheffing over het meerdere. De werknemers merken daar niets van.</p>

<h2>Op de werkplek of buiten de deur: dat scheelt</h2>
<p>Voor de WKR maakt het veel uit waar het feest is. Als werkplek geldt elke plek waarvoor de werkgever arboverantwoordelijk is, zoals het kantoor, de werkplaats of de kantine.</p>
<ul>
<li><strong>Op de eigen werkplek:</strong> hapjes en drankjes tellen niet mee voor de vrije ruimte. Een maaltijd, zoals een buffet, telt mee voor een vast normbedrag van € 4,05 per persoon in 2026.</li>
<li><strong>Buiten de deur:</strong> in een restaurant, op een feestlocatie of bij een activiteit telt de volledige rekening mee, inclusief btw.</li>
</ul>
<p>Een borrel op kantoor is fiscaal dus voordeliger. Maar een uitje buiten de deur is vaak precies wat het team onthoudt. Weeg het af tegen het doel van het uitje.</p>

<h2>En het vervoer?</h2>
<p>Neem het vervoer gewoon mee in je berekening van het totale uitje. Hoe het precies wordt behandeld, hangt af van de opzet van jullie uitje. Vraag het je boekhouder als je twijfelt. Bij Lekker Geel Busje krijg je binnen 1 werkdag een vaste all-in prijs, op factuur op naam van het bedrijf. Dan weet je vooraf precies welk bedrag je meeneemt. Meer over vervoer voor jullie uitje lees je op de pagina <a href="{$landing}">partybus voor je bedrijfsuitje</a>.</p>

<h2>Gaan partners mee?</h2>
<p>Mogen partners mee naar het personeelsfeest, dan kunnen ook hun kosten in de vrije ruimte worden ondergebracht. Neem ze dus mee in de berekening, zodat je niet aan het eind van het jaar voor een verrassing staat.</p>

<h2>Tips voor de organisator</h2>
<ul>
<li><strong>Vraag vooraf hoeveel ruimte er nog is.</strong> De boekhouder of salarisadministratie weet wat er dit jaar al in de vrije ruimte zit.</li>
<li><strong>Gebruik de ruimte voor het eind van het jaar.</strong> Onbenutte vrije ruimte vervalt en schuift niet door naar volgend jaar. Een <a href="{$kerst}">kerstborrel of kerstuitje</a> is dan een mooie besteding.</li>
<li><strong>Werk met vaste prijzen.</strong> Met een vaste prijs per onderdeel weet je vooraf of je binnen de ruimte blijft.</li>
<li><strong>Bewaar de facturen</strong> en geef ze door aan de administratie, met een korte omschrijving van het uitje.</li>
</ul>
<p>Budget helder? Gebruik dan ons <a href="{$organiseren}">stappenplan voor het organiseren van een bedrijfsuitje</a>.</p>

<h2>Bronnen</h2>
<ul>
<li><a href="https://www.belastingdienst.nl/wps/wcm/connect/nl/personeel-en-loon/content/werkkostenregeling" target="_blank" rel="noopener">Belastingdienst: wat is de werkkostenregeling (WKR)?</a></li>
<li><a href="https://www.dezaak.nl/financien/payroll-en-salarisadministratie/eindejaarsfeest-en-werkkostenregeling-wat-telt-mee-in-2026/" target="_blank" rel="noopener">De Zaak: eindejaarsfeest en werkkostenregeling, wat telt mee in 2026</a></li>
<li><a href="https://bjornlunden.com/nl/kennisbank/blogs/kerstborrel-zo-zit-het-fiscaal/" target="_blank" rel="noopener">Björn Lundén: kerstborrel, zo zit het fiscaal</a></li>
<li><a href="https://www.mkbservicedesk.nl/personeel/beloning-arbeidsvoorwaarden/werkkostenregeling-welke-kosten-vallen-daaronder" target="_blank" rel="noopener">MKB Servicedesk: werkkostenregeling, welke kosten vallen daaronder</a></li>
</ul>
<p><em>Bijgewerkt op 17 november 2026. Regels en percentages kunnen per jaar veranderen.</em></p>
HTML,
		),
	);
}


/**
 * Uitroltaak blog-in-hoofdmenu-v1: Blog in het hoofdmenu, voor Neem contact op (anders achteraan).
 */
function thema_blog_in_hoofdmenu() {
	$blog = thema_blog_pagina();
	$plek = get_nav_menu_locations();
	if ( ! $blog || empty( $plek['hoofdmenu'] ) ) {
		return 'geen blog of hoofdmenu';
	}
	$menu  = (int) $plek['hoofdmenu'];
	$items = (array) wp_get_nav_menu_items( $menu );
	foreach ( $items as $item ) {
		if ( (int) $item->object_id === (int) $blog->ID ) {
			return 'staat er al';
		}
	}
	$positie = count( $items ) + 1;
	foreach ( $items as $item ) {
		if ( 0 === (int) $item->menu_item_parent && false !== stripos( $item->title, 'contact' ) ) {
			$positie = (int) $item->menu_order;
			break;
		}
	}
	foreach ( $items as $item ) {
		if ( (int) $item->menu_order >= $positie ) {
			wp_update_post( array( 'ID' => $item->ID, 'menu_order' => (int) $item->menu_order + 1 ) );
		}
	}
	$id = wp_update_nav_menu_item( $menu, 0, array(
		'menu-item-object-id' => $blog->ID,
		'menu-item-object'    => 'page',
		'menu-item-type'      => 'post_type',
		'menu-item-title'     => 'Blog',
		'menu-item-position'  => $positie,
		'menu-item-status'    => 'publish',
	) );
	return is_wp_error( $id ) ? $id->get_error_message() : array( 'item' => $id, 'positie' => $positie );
}

/**
 * Uitroltaak bedrijfsuitje-volgorde-v1: de landingspagina toont de drie nieuwste artikelen uit Teamuitjes.
 * De publicatiedatums bepalen de volgorde: de WKR eerst, de ideeën als laatste (en dus niet op de landingspagina).
 */
function thema_bedrijfsuitje_volgorde() {
	$uit       = array();
	$volgorde  = array(
		'bedrijfsuitje-werkkostenregeling',
		'bedrijfsuitje-organiseren',
		'teambuilding-spellen-collegas-bus',
		'kerstborrel-kerstuitje-organiseren',
		'bedrijfsuitje-winter-ideeen',
		'bedrijfsuitje-ideeen',
	);
	$basis = strtotime( '2026-11-17 12:00:00' );
	foreach ( $volgorde as $i => $slug ) {
		$p = get_page_by_path( $slug, OBJECT, 'post' );
		if ( ! $p ) {
			continue;
		}
		$lokaal = gmdate( 'Y-m-d H:i:s', $basis - $i * 10 * MINUTE_IN_SECONDS );
		wp_update_post( array(
			'ID'            => $p->ID,
			'post_date'     => $lokaal,
			'post_date_gmt' => get_gmt_from_date( $lokaal ),
			'edit_date'     => true,
		) );
		$uit[ $slug ] = $lokaal;
	}

	$gel  = get_page_by_path( 'bedrijfsuitje', OBJECT, 'gelegenheid' );
	$term = get_category_by_slug( 'teamuitjes' );
	if ( $gel && $term ) {
		foreach ( (array) get_post_meta( $gel->ID, 'blokken', true ) as $i => $layout ) {
			if ( 'artikelen' === $layout ) {
				update_post_meta( $gel->ID, "blokken_{$i}_keuze", '' );
				update_post_meta( $gel->ID, "blokken_{$i}_onderwerp", (string) $term->term_id );
				update_post_meta( $gel->ID, "blokken_{$i}_aantal", 3 );
				$uit['blok'] = $i;
			}
		}
		clean_post_cache( $gel->ID );
	}
	return $uit;
}

/**
 * Uitroltaak drinken-in-de-bus-v1: elke vraag over drinken in de bus krijgt hetzelfde duidelijke antwoord.
 * Drinken mag, eigen drank niet, drankjes bestel je bij het gastvrouwke. Werkt op alle opgeslagen FAQ-blokken.
 */
function thema_drinken_in_de_bus() {
	global $wpdb;
	$mag   = 'Ja, drinken in de bus mag. Je bestelt je drankjes bij ons gastvrouwke, uit een koeling vol koude drank. Eigen drank meenemen is niet toegestaan.';
	$eigen = 'Nee, eigen drank is niet toegestaan. Drinken in de bus mag wel: je bestelt je drankjes bij ons gastvrouwke, uit een koeling vol koude drank.';
	$uit   = array();
	$rijen = $wpdb->get_results( "SELECT post_id, meta_key, meta_value FROM {$wpdb->postmeta} WHERE meta_key REGEXP '^blokken_[0-9]+_vragen_[0-9]+_vraag$'" );
	foreach ( $rijen as $r ) {
		$v = mb_strtolower( wp_strip_all_tags( (string) $r->meta_value ) );
		$antwoord = '';
		if ( false !== strpos( $v, 'eigen drank' ) || ( false !== strpos( $v, 'meenemen' ) && ( false !== strpos( $v, 'drank' ) || false !== strpos( $v, 'drinken' ) ) ) ) {
			$antwoord = $eigen;
		} elseif ( preg_match( '/drink|drank|alcohol/u', $v ) && preg_match( '/\b(mag|mogen|kan|kunnen)\b/u', $v ) ) {
			$antwoord = $mag;
		}
		if ( ! $antwoord ) {
			continue;
		}
		$sleutel = substr( $r->meta_key, 0, -strlen( '_vraag' ) ) . '_antwoord';
		$oud     = (string) get_post_meta( (int) $r->post_id, $sleutel, true );
		if ( $oud !== $antwoord ) {
			$extra = false !== strpos( $oud, 'Huisregels' ) ? ' De andere spelregels lees je op de pagina Huisregels.' : '';
			update_post_meta( (int) $r->post_id, $sleutel, $antwoord . $extra );
			clean_post_cache( (int) $r->post_id );
		}
		$uit[] = get_post_field( 'post_name', (int) $r->post_id ) . ': ' . $r->meta_value;
	}
	/* Vrijgezellenchecklist: antwoord over wat je meeneemt. */
	$wpdb->query( $wpdb->prepare(
		"UPDATE {$wpdb->postmeta} SET meta_value = REPLACE( meta_value, %s, %s ) WHERE meta_value LIKE %s",
		'Eigen drank laat je thuis als je met ons meerijdt: aan boord staat een koeling vol koude drank.',
		'Eigen drank laat je thuis, want dat is niet toegestaan in de bus. Drinken mag wel: je bestelt je drankjes bij ons gastvrouwke.',
		'%Eigen drank laat je thuis als je%'
	) );
	wp_cache_flush();
	return $uit;
}
