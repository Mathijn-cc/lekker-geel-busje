<?php
/**
 * Blog opzetten (uitroltaak blog-v1): permalinks onder /blog/, de berichtenpagina, onderwerpen
 * en de eerste twee artikelen over het vrijgezellenfeest. Daarna gewoon in WordPress te beheren.
 */

defined( 'ABSPATH' ) || exit;

function thema_blog_opzetten() {
	global $wp_rewrite;
	$uit = array();

	/* Artikelen onder /blog/, onderwerpen onder /blog/onderwerp/. Pagina's en gelegenheden houden hun adres. */
	$wp_rewrite->set_permalink_structure( '/blog/%postname%/' );
	$wp_rewrite->set_category_base( 'blog/onderwerp' );
	update_option( 'tag_base', 'blog/tag' );

	/* Berichtenpagina. */
	$blog_id = thema_voorbeeld_post( 'page', 'blog', 'Blog', array(
		'post_content' => '<p>Tips, ideeën en checklists voor elk feestje met de groep. Van het vrijgezellenfeest tot het bedrijfsuitje: zo regel je het zonder gedoe, en begint het feest al in de bus.</p>',
		'menu_order'   => 8,
	) );
	if ( 'page' !== get_option( 'show_on_front' ) ) {
		$home = get_page_by_path( 'home' );
		if ( $home ) {
			update_option( 'page_on_front', $home->ID );
			update_option( 'show_on_front', 'page' );
		}
	}
	update_option( 'page_for_posts', $blog_id );
	update_option( 'posts_per_page', 9 );
	if ( function_exists( 'update_field' ) ) {
		update_field( 'field_blogpagina_blog_kop', 'Tips en ideeën voor *jullie feestje*', $blog_id );
		update_field( 'field_seo_seo_titel', 'Blog: tips en ideeën voor je feestje | Lekker Geel Busje', $blog_id );
		update_field( 'field_seo_seo_omschrijving', 'Tips, ideeën en checklists voor je vrijgezellenfeest, bedrijfsuitje of dag uit met de groep. Van de sfeerbus met chauffeur en gastvrouwke.', $blog_id );
		$offerte        = thema_blok_offerte();
		$offerte['kop'] = 'Plannen klaar? *Wij rijden.*';
		update_field( 'field_blokken', array( $offerte ), $blog_id );
	}
	$uit['blog'] = $blog_id;

	/* Onderwerpen: de standaardcategorie wordt Algemeen, plus een onderwerp per gelegenheid waar we over schrijven. */
	$standaard = (int) get_option( 'default_category' );
	if ( $standaard ) {
		wp_update_term( $standaard, 'category', array( 'name' => 'Algemeen', 'slug' => 'algemeen' ) );
	}
	$vrij = get_category_by_slug( 'vrijgezellenfeest' );
	if ( $vrij ) {
		$vrij = (int) $vrij->term_id;
	} else {
		$term = wp_insert_term( 'Vrijgezellenfeest', 'category', array(
			'slug'        => 'vrijgezellenfeest',
			'description' => 'Alles voor de organisator van een vrijgezellenfeest: tips, een checklist en ideeën voor een dag die de vrijgezel nooit vergeet.',
		) );
		$vrij = is_wp_error( $term ) ? 0 : (int) $term['term_id'];
	}

	/* Het voorbeeldbericht van WordPress weg. */
	$hallo = get_page_by_path( 'hello-world', OBJECT, 'post' );
	if ( $hallo ) {
		wp_trash_post( $hallo->ID );
	}

	$gel = get_page_by_path( 'vrijgezellenfeest', OBJECT, 'gelegenheid' );
	$gel = $gel ? (int) $gel->ID : 0;
	foreach ( thema_blog_eerste_artikelen() as $artikel ) {
		$uit[ $artikel['slug'] ] = thema_blog_artikel_plaatsen( $artikel, $vrij, $gel );
	}

	/* Regels weggooien: WordPress bouwt ze bij het volgende bezoek opnieuw op, met de nieuwe adressen. */
	delete_option( 'rewrite_rules' );
	return $uit;
}

function thema_blog_artikel_plaatsen( $a, $categorie, $gelegenheid ) {
	$bestaand = get_page_by_path( $a['slug'], OBJECT, 'post' );
	$data     = array(
		'post_type'     => 'post',
		'post_name'     => $a['slug'],
		'post_title'    => $a['titel'],
		'post_excerpt'  => $a['samenvatting'],
		'post_content'  => $a['tekst'],
		'post_status'   => 'publish',
		'post_category' => array_filter( array( $categorie ) ),
	);
	if ( $bestaand ) {
		$data['ID'] = $bestaand->ID;
	}
	$id = $bestaand ? wp_update_post( $data ) : wp_insert_post( $data );
	if ( ! $id || is_wp_error( $id ) ) {
		return 0;
	}
	$beeld = thema_beeld_op_naam( $a['beeld'] ) ?: thema_beeld_op_naam( $a['beeld_oud'] ?? '' );
	if ( $beeld ) {
		set_post_thumbnail( $id, $beeld );
	}
	if ( function_exists( 'update_field' ) ) {
		update_field( 'field_blog_in_het_kort', $a['kort'], $id );
		update_field( 'field_blog_auteur', 'Team Lekker Geel Busje', $id );
		update_field( 'field_blog_auteur_rol', 'Rijdt groepen door heel Nederland en daarbuiten', $id );
		update_field( 'field_blog_gelegenheid', $gelegenheid, $id );
		update_field( 'field_seo_seo_titel', $a['seo_titel'], $id );
		update_field( 'field_seo_seo_omschrijving', $a['samenvatting'], $id );

		$faq           = thema_blok_faq();
		$faq['kop']    = $a['faq_kop'];
		$faq['tekst']  = 'Staat je vraag er niet tussen? Bel ons gerust, we denken graag met je mee.';
		$faq['vragen'] = array_map( fn( $v ) => array( 'vraag' => $v[0], 'antwoord' => $v[1] ), $a['vragen'] );
		$faq['anker']  = 'vragen';

		$offerte          = thema_blok_offerte();
		$offerte['label'] = 'Binnen 2 werkdagen je prijs';
		$offerte['kop']   = 'Vervoer voor het vrijgezellenfeest *geregeld?*';

		update_field( 'field_blokken', array_merge( $a['blokken'], array( $faq, $offerte ) ), $id );
	}
	return (int) $id;
}

/**
 * De eerste twee artikelen. Geen plaatsnamen of namen van zaken: algemeen bruikbaar voor elke groep.
 */
function thema_blog_eerste_artikelen() {
	$landing = home_url( '/gelegenheden/vrijgezellenfeest/' );
	$tips    = home_url( '/blog/vrijgezellenfeest-organiseren-tips/' );
	$lijst   = home_url( '/blog/checklist-vrijgezellenfeest/' );
	$opdrachten = home_url( '/blog/spelletjes-opdrachten-vrijgezellenfeest-bus/' );

	return array(
		array(
			'slug'         => 'vrijgezellenfeest-organiseren-tips',
			'titel'        => 'Vrijgezellenfeest organiseren: 8 tips voor de organisator',
			'seo_titel'    => 'Vrijgezellenfeest organiseren: 8 tips | Lekker Geel Busje',
			'samenvatting' => 'Organiseer jij het vrijgezellenfeest? Met deze acht tips houd je het leuk voor de groep en overzichtelijk voor jezelf, van datum en budget tot playlist.',
			'beeld'        => 'selfie-in-de-feestbus-met-rood-licht',
			'beeld_oud'    => 'slingers.jpg',
			'kort'         => "Begin twee tot drie maanden van tevoren en leg eerst de datum vast.\nMaak de kosten vooraf duidelijk en laat iedereen vooraf betalen.\nStem het programma af op de vrijgezel en plan liever te weinig dan te veel.",
			'faq_kop'      => 'Vragen over het *organiseren*',
			'vragen'       => array(
				array( 'Wie organiseert het vrijgezellenfeest?', 'Meestal de getuigen of de beste vrienden of vriendinnen van de vrijgezel. Vaak neemt één persoon het voortouw, met hulp van een of twee anderen. Spreek af wie wat regelt, dan blijft het overzichtelijk.' ),
				array( 'Hoe lang van tevoren begin je met organiseren?', 'Twee tot drie maanden van tevoren is een goed moment. Dan heb je tijd om een datum te prikken, iedereen uit te nodigen en de activiteit, het eten en het vervoer vast te leggen.' ),
				array( 'Betaalt de vrijgezel mee?', 'Meestal niet: de groep deelt de kosten en trakteert de vrijgezel. Spreek het vooraf af en neem het mee in de bijdrage per persoon, dan is het voor iedereen duidelijk.' ),
			),
			'blokken'      => array(),
			'tekst'        => <<<HTML
<p>Je bent gevraagd om het vrijgezellenfeest te organiseren. Een eer, en ook een flinke klus. Want hoe zorg je dat de vrijgezel een dag heeft om nooit te vergeten, dat de groep het naar zijn zin heeft en dat jij zelf ook nog geniet? We rijden geregeld vrijgezellengroepen rond en zien wat werkt en wat niet. Dit zijn onze acht tips.</p>

<h2>1. Begin op tijd en leg eerst de datum vast</h2>
<p>Twee tot drie maanden van tevoren is een mooi moment om te beginnen. Overleg de datum met de partner en de getuigen, zodat je zeker weet dat de vrijgezel kan en er niets anders gepland staat. Een paar weken voor de bruiloft werkt vaak goed: dicht genoeg bij de grote dag om in de stemming te komen, ver genoeg weg om bij te komen.</p>
<p>Vrijdagen en zaterdagen zitten snel vol, bij restaurants en activiteiten en ook bij vervoer. Staat de datum? Leg dan meteen vast wat je zeker wilt hebben.</p>

<h2>2. Maak de gastenlijst samen met de partner</h2>
<p>Wie hoort erbij? De partner weet vaak het best wie de vrijgezel er écht bij wil hebben: vrienden van vroeger, familie, collega's. Stuur de uitnodiging op tijd en vraag iedereen om een ja of nee voor een vaste datum. Met een vast aantal kun je reserveren en krijg je een vaste prijs.</p>

<h2>3. Kies een programma dat bij de vrijgezel past</h2>
<p>Het feest draait om de vrijgezel, niet om wat de groep zelf leuk vindt. Houdt hij of zij van actie, van lekker eten, van iets creatiefs of vooral van feesten? Kies daar één hoofdactiviteit bij. Een paar richtingen:</p>
<ul>
<li><strong>Actief:</strong> iets sportiefs of een spel waarbij de groep samen moet werken.</li>
<li><strong>Proeven:</strong> een proeverij of kookworkshop, ideaal als opwarmer voor de avond.</li>
<li><strong>Creatief:</strong> een workshop waarbij iedereen iets maakt om mee naar huis te nemen.</li>
<li><strong>Feest:</strong> een avond uit, een festival of een feestrit met muziek en licht.</li>
</ul>
<p>Een verrassing is leuk, een vrijgezel die zich de hele dag schaamt niet. Houd opdrachten en uitdagingen luchtig en vraag de partner gerust wat wel en niet kan.</p>

<h2>4. Plan liever te weinig dan te veel</h2>
<p>Een middagactiviteit, samen eten en daarna de stad in is genoeg voor een topdag. Elk extra onderdeel kost reistijd, regelwerk en energie. Laat tussen de onderdelen wat lucht, zodat je niet de hele dag op de klok kijkt. En plan het eten niet te laat: een groep die de hele middag proost, heeft om zes uur echt een bord nodig.</p>

<h2>5. Maak het budget vooraf duidelijk</h2>
<p>Tel alles bij elkaar op: activiteit, eten, vervoer, een rondje drankjes en een cadeautje of aandenken voor de vrijgezel. Deel het door het aantal deelnemers, en vergeet niet dat de vrijgezel meestal niet meebetaalt. Stuur iedereen vooraf een betaalverzoek. Dan hoef je na afloop niemand achter de broek te zitten en kun jij gewoon meefeesten.</p>

<h2>6. Regel het vervoer in één keer</h2>
<p>Met een groep van tien, twintig of veertig mensen van de ene naar de andere plek gaan is gedoe. Wie rijdt, wie neemt de trein, waar parkeer je en wie raakt onderweg kwijt? Met een eigen bus blijft de groep bij elkaar en hoeft niemand de Bob te zijn.</p>
<p>Kies je voor een <a href="{$landing}">partybus voor je vrijgezellenfeest</a>, dan wordt de rit zelf onderdeel van het feest. In onze sfeerbus zitten professionele verlichting, lasers, rook en een goede geluidsinstallatie, en het gastvrouwke zorgt voor de drankjes. Je krijgt binnen 2 werkdagen een vaste all-in prijs, zodat je die meteen in het budget kunt zetten.</p>

<h2>7. Zorg voor sfeer: dresscode, playlist en opdrachten</h2>
<p>Kleine dingen maken het verschil. Een dresscode of thema, zoals iedereen in dezelfde kleur of een sjerp voor de vrijgezel, ziet er op elke foto feestelijk uit. Een gezamenlijke playlist met de favorieten van de vrijgezel zet meteen de toon. En met een paar <a href="{$opdrachten}">opdrachten voor in de bus</a> vliegt de tijd tussen twee stops voorbij.</p>

<h2>8. Deel een draaiboek met de groep</h2>
<p>Zet in de groepsapp hoe laat jullie waar moeten zijn, wat iedereen mee moet nemen en wie het aanspreekpunt is. Dan hoef jij niet de hele dag te bellen en te zoeken. Wil je zeker weten dat je niets vergeet? Gebruik onze <a href="{$lijst}">checklist voor het vrijgezellenfeest</a> en vink af wat je al geregeld hebt.</p>

<h2>Veelgemaakte fouten</h2>
<ul>
<li>Te laat beginnen, waardoor de leukste activiteit of het vervoer al vol zit.</li>
<li>Een programma dat zo vol zit dat er geen tijd is om gewoon samen te zijn.</li>
<li>Onduidelijke kosten, waardoor de organisator na afloop geld moet terugvragen.</li>
<li>Geen rekening houden met wat de vrijgezel leuk vindt.</li>
<li>Het eten vergeten na een middag vol proosten.</li>
</ul>
<p>Met deze tips wordt het een dag om nooit te vergeten, voor de vrijgezel en voor jou.</p>
HTML,
		),
		array(
			'slug'         => 'checklist-vrijgezellenfeest',
			'titel'        => 'Checklist vrijgezellenfeest: zo regel je alles op tijd',
			'seo_titel'    => 'Checklist vrijgezellenfeest: alles op tijd | Lekker Geel Busje',
			'samenvatting' => 'De complete checklist voor het vrijgezellenfeest: wat regel je drie maanden, een maand en een week van tevoren? Met een lijst om zelf af te vinken.',
			'beeld'        => 'gasten-lachen-aan-tafel-in-sfeerbus',
			'beeld_oud'    => 'groep-in-bus.jpg',
			'kort'         => "Drie maanden van tevoren: datum, gastenlijst, budget en vervoer.\nEen maand van tevoren: activiteit en eten vastleggen, bijdrage innen.\nDe week ervoor: draaiboek, playlist en opdrachten klaarzetten.",
			'faq_kop'      => 'Vragen over de *voorbereiding*',
			'vragen'       => array(
				array( 'Wat moet je allemaal regelen voor een vrijgezellenfeest?', 'De datum, de gastenlijst, het budget, een activiteit, eten, vervoer en een draaiboek voor de dag. Met een dresscode, een playlist en een paar opdrachten maak je het af.' ),
				array( 'Hoeveel mensen nodig je uit voor een vrijgezellenfeest?', 'Dat bepaal je samen met de partner. Een kleine groep beste vrienden kan, maar ook een grote groep met familie en collega\'s. In onze bussen gaan groepen tot 88 personen mee.' ),
				array( 'Wat neem je mee naar een vrijgezellenfeest?', 'Iets voor de dresscode, comfortabele schoenen, een opgeladen telefoon en eventueel spullen voor de opdrachten. Eigen drank laat je thuis als je met ons meerijdt: aan boord staat een koeling vol koude drank.' ),
			),
			'blokken'      => array(
				array(
					'acf_fc_layout' => 'checklist',
					'eyebrow'       => 'Checklist',
					'kop'           => 'Vink het *zelf af*',
					'intro'         => 'Je vinkjes blijven op dit apparaat bewaard, dus kom gerust later terug om verder te gaan.',
					'items'         => array(
						array( 'wanneer' => 'Drie maanden van tevoren', 'taak' => 'Datum prikken met de partner en de getuigen', 'link' => '' ),
						array( 'wanneer' => 'Drie maanden van tevoren', 'taak' => 'Gastenlijst maken en de uitnodiging versturen', 'link' => '' ),
						array( 'wanneer' => 'Drie maanden van tevoren', 'taak' => 'Budget per persoon afspreken', 'link' => '' ),
						array( 'wanneer' => 'Drie maanden van tevoren', 'taak' => 'Partybus aanvragen met datum, aantal en ophaalplek', 'link' => array( 'title' => 'Vraag je prijs aan', 'url' => '#offerte', 'target' => '' ) ),
						array( 'wanneer' => 'Twee maanden van tevoren', 'taak' => 'Activiteit kiezen en reserveren', 'link' => '' ),
						array( 'wanneer' => 'Een maand van tevoren', 'taak' => 'Restaurant reserveren', 'link' => '' ),
						array( 'wanneer' => 'Een maand van tevoren', 'taak' => 'Bijdrage per persoon innen', 'link' => '' ),
						array( 'wanneer' => 'Twee weken van tevoren', 'taak' => 'Dresscode of thema doorgeven aan de groep', 'link' => '' ),
						array( 'wanneer' => 'Twee weken van tevoren', 'taak' => 'Opdrachten en spelletjes voorbereiden', 'link' => '' ),
						array( 'wanneer' => 'De week ervoor', 'taak' => 'Draaiboek met tijden en adressen delen', 'link' => '' ),
						array( 'wanneer' => 'De week ervoor', 'taak' => 'Playlist maken met de favorieten van de vrijgezel', 'link' => '' ),
						array( 'wanneer' => 'Op de dag zelf', 'taak' => 'Genieten. De rit is geregeld.', 'link' => '' ),
					),
					'knop'          => '',
					'achtergrond'   => 'standaard',
					'anker'         => 'afvinken',
				),
			),
			'tekst'        => <<<HTML
<p>Een vrijgezellenfeest organiseren is vooral een kwestie van de juiste dingen op het juiste moment regelen. Met deze checklist weet je precies wat je wanneer doet. Onderaan de pagina staat dezelfde lijst om zelf af te vinken.</p>

<h2>Drie maanden van tevoren</h2>
<p>Dit is het moment voor de grote beslissingen. Wat je nu vastlegt, bepaalt de rest.</p>
<ul>
<li><strong>Datum prikken</strong> met de partner en de getuigen. Vrijdagen en zaterdagen zitten snel vol, dus wacht niet te lang.</li>
<li><strong>Gastenlijst maken</strong> en de uitnodiging versturen, met een duidelijke datum waarop iedereen ja of nee laat weten.</li>
<li><strong>Budget afspreken:</strong> wat mag het per persoon kosten? Bespreek ook of de groep de vrijgezel trakteert.</li>
<li><strong>Vervoer aanvragen.</strong> Met een vast aantal personen en een ophaalplek kun je meteen een prijs aanvragen. Bij ons krijg je die binnen 2 werkdagen, all-in.</li>
</ul>

<h2>Twee maanden van tevoren</h2>
<p>Nu de datum en de groep vaststaan, vul je het programma in.</p>
<ul>
<li><strong>Activiteit kiezen en reserveren.</strong> Kies iets dat bij de vrijgezel past. Twijfel je? Lees onze <a href="{$tips}">tips voor het organiseren van een vrijgezellenfeest</a>.</li>
<li><strong>Grove planning maken:</strong> hoe laat begint de dag, hoe lang duurt de activiteit, wanneer gaan jullie eten en hoe laat is het einde?</li>
</ul>

<h2>Een maand van tevoren</h2>
<ul>
<li><strong>Restaurant reserveren</strong> voor de hele groep, met een tijd die past bij de rest van het programma.</li>
<li><strong>Bijdrage innen.</strong> Stuur iedereen een betaalverzoek. Zo ligt het geld klaar voordat je de rekeningen betaalt.</li>
<li><strong>Tijden doorgeven</strong> aan de activiteit, het restaurant en het vervoer, zodat alles op elkaar aansluit.</li>
</ul>

<h2>Twee weken van tevoren</h2>
<ul>
<li><strong>Dresscode of thema doorgeven,</strong> zodat iedereen tijd heeft om iets te regelen.</li>
<li><strong>Opdrachten en spelletjes voorbereiden.</strong> Denk aan een quiz over het stel, een bingokaart voor de dag of kaartjes met advies voor het huwelijk. Inspiratie vind je bij onze <a href="{$opdrachten}">opdrachten voor in de bus</a>.</li>
<li><strong>Cadeautje of aandenken</strong> voor de vrijgezel regelen.</li>
</ul>

<h2>De week ervoor</h2>
<ul>
<li><strong>Draaiboek delen</strong> in de groepsapp: hoe laat jullie waar moeten zijn, wat iedereen meeneemt en wie het aanspreekpunt is.</li>
<li><strong>Playlist maken</strong> met de favorieten van de vrijgezel. Vraag iedereen een paar nummers toe te voegen.</li>
<li><strong>Weer checken</strong> en zo nodig een plan B voor een buitenactiviteit.</li>
</ul>

<h2>Op de dag zelf</h2>
<p>Houd je telefoon bij de hand voor de groepsapp, zorg dat de vrijgezel op tijd klaarstaat, en geniet. Met een <a href="{$landing}">partybus voor je vrijgezellenfeest</a> is de rit geregeld: de chauffeur rijdt, het gastvrouwke zorgt voor de drankjes en jij hoeft alleen nog mee te feesten.</p>
HTML,
		),
	);
}

/**
 * Uitroltaak blog-fotos-v1: de artikelen krijgen een foto uit de fotoselectie.
 */
function thema_blog_fotos() {
	$uit = array();
	foreach ( thema_blog_eerste_artikelen() as $a ) {
		$post  = get_page_by_path( $a['slug'], OBJECT, 'post' );
		$beeld = thema_beeld_op_naam( $a['beeld'] );
		if ( $post && $beeld ) {
			set_post_thumbnail( $post->ID, $beeld );
			$uit[ $a['slug'] ] = $beeld;
		}
	}
	return $uit;
}

/**
 * Uitroltaak blog-tips-en-tricks-v1: het onderwerp heet Tips en tricks, niet Vrijgezellenfeest
 * (dat label herhaalde op de vrijgezellenpagina alleen de paginanaam). De vrijgezellenpagina toont
 * daarom de twee artikelen zelf gekozen, in plaats van alles uit het onderwerp.
 */
function thema_blog_tips_en_tricks() {
	$uit  = array();
	$term = get_category_by_slug( 'vrijgezellenfeest' ) ?: get_category_by_slug( 'tips-en-tricks' );
	if ( $term ) {
		wp_update_term( $term->term_id, 'category', array(
			'name'        => 'Tips en tricks',
			'slug'        => 'tips-en-tricks',
			'description' => 'Tips, checklists en ideeën voor de organisator: zo regel je een vrijgezellenfeest, bedrijfsuitje of dag uit met de groep zonder gedoe.',
		) );
		$uit['onderwerp'] = (int) $term->term_id;
	}
	$ids = array();
	foreach ( array( 'vrijgezellenfeest-organiseren-tips', 'checklist-vrijgezellenfeest' ) as $slug ) {
		$p = get_page_by_path( $slug, OBJECT, 'post' );
		if ( $p ) {
			$ids[] = (string) $p->ID;
		}
	}
	$gel = get_page_by_path( 'vrijgezellenfeest', OBJECT, 'gelegenheid' );
	if ( $gel && $ids ) {
		foreach ( (array) get_post_meta( $gel->ID, 'blokken', true ) as $i => $layout ) {
			if ( 'artikelen' === $layout ) {
				update_post_meta( $gel->ID, "blokken_{$i}_keuze", $ids );
				update_post_meta( $gel->ID, "blokken_{$i}_onderwerp", '' );
				$uit['blok'] = $i;
			}
		}
		clean_post_cache( $gel->ID );
	}
	return $uit;
}

/**
 * Uitroltaak blog-auteur-rol-v1: nieuwe regel onder de auteur bij de eerste artikelen.
 */
function thema_blog_auteur_rol() {
	$uit = array();
	foreach ( array( 'vrijgezellenfeest-organiseren-tips', 'checklist-vrijgezellenfeest' ) as $slug ) {
		$p = get_page_by_path( $slug, OBJECT, 'post' );
		if ( $p && function_exists( 'update_field' ) ) {
			update_field( 'field_blog_auteur_rol', 'Rijdt groepen door heel Nederland en daarbuiten', $p->ID );
			$uit[] = $p->ID;
		}
	}
	return $uit;
}

/**
 * Derde artikel: ideeën voor het vrijgezellenfeest (zoekterm "vrijgezellenfeest ideeen", ca. 590 per maand).
 */
function thema_blog_artikel_ideeen() {
	$landing = home_url( '/gelegenheden/vrijgezellenfeest/' );
	$tips    = home_url( '/blog/vrijgezellenfeest-organiseren-tips/' );
	$lijst   = home_url( '/blog/checklist-vrijgezellenfeest/' );
	$opdrachten = home_url( '/blog/spelletjes-opdrachten-vrijgezellenfeest-bus/' );
	return array(
		'slug'         => 'vrijgezellenfeest-ideeen',
		'titel'        => 'Vrijgezellenfeest ideeën: 20 leuke activiteiten voor de groep',
		'seo_titel'    => 'Vrijgezellenfeest ideeën: 20 activiteiten | Lekker Geel Busje',
		'samenvatting' => 'Op zoek naar ideeën voor het vrijgezellenfeest? Twintig activiteiten voor elke vrijgezel, van actief en creatief tot proeven, ontspannen en feesten.',
		'beeld'        => 'groepsselfie-met-lichtstaven-in-partybus',
		'beeld_oud'    => 'selfie.jpg',
		'kort'         => "Kies één hoofdactiviteit die bij de vrijgezel past, niet bij de groep.\nCombineer een activiteit overdag met samen eten en een avond uit.\nLet op groepsgrootte, budget en seizoen voordat je reserveert.",
		'faq_kop'      => 'Vragen over *ideeën*',
		'vragen'       => array(
			array( 'Wat is een leuk idee voor een vrijgezellenfeest?', 'Een activiteit die past bij de vrijgezel, gevolgd door samen eten en een avond uit. Denk aan een proeverij, een workshop of iets actiefs, en sluit af met karaoke of een feestrit. Zo heeft de dag een begin, een midden en een knallend einde.' ),
			array( 'Wat doe je op een vrijgezellenfeest voor vrouwen of mannen?', 'Dat hangt veel meer af van de vrijgezel dan van man of vrouw. Kies wat hij of zij zelf leuk vindt: de een wil een spa-dag, de ander een middag lasergamen. Vraag de partner gerust om tips.' ),
			array( 'Hoe combineer je meerdere activiteiten op één dag?', 'Kies er niet meer dan twee, plus eten. Plan de onderdelen dicht bij elkaar of regel vervoer voor de hele groep, zodat niemand tijd verliest met zoeken of parkeren.' ),
		),
		'blokken'      => array(),
		'tekst'        => <<<HTML
<p>Het vrijgezellenfeest staat op de planning, maar wat ga je eigenlijk doen? Een goed idee past bij de vrijgezel, werkt voor de hele groep en laat genoeg ruimte om gewoon samen te zijn. Hier vind je twintig ideeën om uit te kiezen, in vijf richtingen. Combineer er één of twee met samen eten en een avond uit, en de dag staat.</p>

<h2>Actief: voor de vrijgezel die niet stil kan zitten</h2>
<ul>
<li><strong>Escape room:</strong> samen puzzelen tegen de klok. Werkt het best met groepjes van vier tot zes.</li>
<li><strong>Lasergamen of paintball:</strong> fanatiek, luid en vol lachmomenten, ook voor wie niet sportief is.</li>
<li><strong>Suppen of kanoën:</strong> lekker buiten in de zomer, met grote kans op een nat pak voor de vrijgezel.</li>
<li><strong>Dansworkshop:</strong> leer samen een choreografie en voer die later op voor het bruidspaar.</li>
</ul>

<h2>Proeven en eten: voor de genieter</h2>
<ul>
<li><strong>Bierproeverij:</strong> proeven, vergelijken en ontdekken welk bier de vrijgezel het lekkerst vindt.</li>
<li><strong>Wijnproeverij:</strong> een rustige start van de dag, met veel te bespreken.</li>
<li><strong>Cocktailworkshop:</strong> iedereen shaket zijn eigen drankje, met de vrijgezel als hoofdbarman.</li>
<li><strong>Kookworkshop:</strong> samen koken en daarna samen aan tafel, dan is het diner meteen geregeld.</li>
</ul>

<h2>Creatief: voor wie iets wil maken</h2>
<ul>
<li><strong>Schilderworkshop:</strong> iedereen schildert hetzelfde onderwerp, het resultaat is altijd hilarisch.</li>
<li><strong>Keramiek of mokken beschilderen:</strong> een aandenken dat de vrijgezel echt gebruikt.</li>
<li><strong>Bloemenkransen maken:</strong> leuk om tijdens de rest van de dag te dragen.</li>
<li><strong>Fotoshoot met de groep:</strong> met een fotograaf of gewoon met een lijst foto-opdrachten.</li>
</ul>

<h2>Ontspannen: voor de vrijgezel die vooral wil genieten</h2>
<ul>
<li><strong>Wellness of spa:</strong> een middag bijkomen voordat de avond begint.</li>
<li><strong>Picknick of brunch:</strong> eenvoudig, gezellig en makkelijk voor een grote groep.</li>
<li><strong>Boottocht:</strong> rustig varen met een drankje en een hapje.</li>
</ul>

<h2>Feest: voor wie de avond wil laten knallen</h2>
<ul>
<li><strong>Karaoke:</strong> de vrijgezel opent, de rest volgt. Bereid alvast een duet voor.</li>
<li><strong>Festival of feestavond:</strong> kies een evenement met muziek die de vrijgezel goed vindt.</li>
<li><strong>Themafeest:</strong> iedereen verkleed in hetzelfde thema, van jaren tachtig tot tropisch.</li>
</ul>

<h2>Onderweg: maak van de rit een feest</h2>
<ul>
<li><strong>Een feestrit in de partybus:</strong> licht, lasers, rook en muziek aan boord, met een gastvrouwke dat voor de drankjes zorgt. Zo begint het feest al voordat jullie er zijn. Lees meer over een <a href="{$landing}">partybus voor je vrijgezellenfeest</a>.</li>
<li><strong>Opdrachten en spelletjes:</strong> een quiz over het stel, een bingokaart voor de dag of toosten met een verhaal. Bekijk onze <a href="{$opdrachten}">opdrachten voor in de bus</a>.</li>
</ul>

<h2>Ideeën voor vrouwen en mannen</h2>
<p>Veel lijstjes splitsen ideeën voor een vrijgezellenfeest voor vrouwen en een vrijgezellenfeest voor mannen. In de praktijk zegt dat weinig: de een houdt van een spa-dag, de ander van lasergamen, en dat staat los van man of vrouw. Kijk naar wat de vrijgezel zelf leuk vindt en wat de groep samen kan doen. Twijfel je? Vraag de partner, die weet het meestal precies.</p>

<h2>Zo kies je het juiste idee</h2>
<ul>
<li><strong>Groepsgrootte:</strong> niet elke activiteit werkt met dertig mensen. Vraag naar het maximum voordat je kiest.</li>
<li><strong>Budget:</strong> tel activiteit, eten en vervoer bij elkaar op en bepaal wat het per persoon mag kosten.</li>
<li><strong>Seizoen:</strong> buitenactiviteiten zijn heerlijk in de zomer, maar zorg voor een plan B bij slecht weer.</li>
<li><strong>Afwisseling:</strong> één actief onderdeel, samen eten en een avond uit is genoeg voor een topdag.</li>
</ul>
<p>Heb je een idee gekozen? Lees dan onze <a href="{$tips}">tips voor het organiseren</a> en gebruik de <a href="{$lijst}">checklist voor het vrijgezellenfeest</a>, zodat je niets vergeet.</p>
HTML,
	);
}

/**
 * Uitroltaak blog-ideeen-v1: het derde artikel plaatsen en op de vrijgezellenpagina tonen.
 */
function thema_blog_ideeen_plaatsen() {
	$term = get_category_by_slug( 'tips-en-tricks' ) ?: get_category_by_slug( 'vrijgezellenfeest' );
	$gel  = get_page_by_path( 'vrijgezellenfeest', OBJECT, 'gelegenheid' );
	$id   = thema_blog_artikel_plaatsen( thema_blog_artikel_ideeen(), $term ? (int) $term->term_id : 0, $gel ? (int) $gel->ID : 0 );
	if ( function_exists( 'update_field' ) ) {
		update_field( 'field_blog_auteur_rol', 'Rijdt groepen door heel Nederland en daarbuiten', $id );
	}
	$ids = array();
	foreach ( array( 'vrijgezellenfeest-ideeen', 'vrijgezellenfeest-organiseren-tips', 'checklist-vrijgezellenfeest' ) as $slug ) {
		$p = get_page_by_path( $slug, OBJECT, 'post' );
		if ( $p ) {
			$ids[] = (string) $p->ID;
		}
	}
	if ( $gel && $ids ) {
		foreach ( (array) get_post_meta( $gel->ID, 'blokken', true ) as $i => $layout ) {
			if ( 'artikelen' === $layout ) {
				update_post_meta( $gel->ID, "blokken_{$i}_keuze", $ids );
				update_post_meta( $gel->ID, "blokken_{$i}_intro", 'Ideeën voor de dag, tips voor een geslaagd feest en een checklist om af te vinken. Zo weet je zeker dat alles op tijd geregeld is.' );
			}
		}
		clean_post_cache( $gel->ID );
	}
	return array( 'artikel' => $id, 'getoond' => $ids );
}

/**
 * Vierde artikel: spelletjes en opdrachten voor in de bus (zoektermen "vrijgezellenfeest opdrachten" ca. 390
 * en "vrijgezellenfeest spelletjes" ca. 110 per maand). De acht opdrachten staan in het blok Opdrachten.
 */
function thema_blog_artikel_opdrachten() {
	$landing = home_url( '/gelegenheden/vrijgezellenfeest/' );
	$ideeen  = home_url( '/blog/vrijgezellenfeest-ideeen/' );
	$lijst   = home_url( '/blog/checklist-vrijgezellenfeest/' );
	return array(
		'slug'         => 'spelletjes-opdrachten-vrijgezellenfeest-bus',
		'titel'        => 'Spelletjes voor het vrijgezellenfeest: 8 opdrachten voor in de bus',
		'seo_titel'    => 'Vrijgezellenfeest opdrachten: 8 spelletjes | Lekker Geel Busje',
		'samenvatting' => 'Acht spelletjes en opdrachten voor het vrijgezellenfeest die je gewoon op je plek in de bus speelt. Van een quiz over het stel tot vrijgezellenbingo.',
		'beeld'        => 'meezingen-met-microfoon-in-sfeerbus',
		'beeld_oud'    => 'feest-disco.jpg',
		'kort'         => "Alle acht opdrachten speel je op je plek, ook tijdens de rit.\nBereid ze een week van tevoren voor: vragen, kaartjes en een bingokaart.\nHoud het leuk voor de vrijgezel en kies niets wat de chauffeur afleidt.",
		'faq_kop'      => 'Vragen over *spelletjes*',
		'vragen'       => array(
			array( 'Welke spelletjes kun je doen op een vrijgezellenfeest?', 'Een quiz over het stel, vrijgezellenbingo, songteksten raden of een fotojacht. Spelletjes die je in een groep op je plek speelt, werken het best: overal te doen en iedereen doet mee.' ),
			array( 'Wat heb je nodig voor de opdrachten?', 'Weinig: een lijst met vragen, wat kaartjes en pennen, een bingokaart per persoon en een telefoon voor de muziek en de foto\'s. Bereid het een week van tevoren voor en neem het mee in een tas.' ),
			array( 'Hoe zorg je dat de vrijgezel het leuk blijft vinden?', 'Houd het luchtig en persoonlijk. Vraag de partner wat wel en niet kan, en kies opdrachten waar de vrijgezel om kan lachen in plaats van zich te schamen.' ),
		),
		'blokken'      => array(
			array(
				'acf_fc_layout' => 'opdrachten',
				'eyebrow'       => 'Voor onderweg',
				'kop'           => 'Acht opdrachten voor *in de bus*',
				'intro'         => 'Allemaal op je plek te doen. Bereid ze voor, dan vliegt de tijd tussen twee stops voorbij.',
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
				'noot'          => 'Alles op je plek te doen en niets wat de chauffeur afleidt. Zo blijft het leuk voor iedereen.',
				'achtergrond'   => 'paars',
				'anker'         => 'opdrachten',
			),
		),
		'tekst'        => <<<HTML
<p>De rit tussen twee onderdelen van het vrijgezellenfeest is geen dode tijd. Met een paar goede spelletjes wordt het juist een van de leukste momenten van de dag: de groep zit bij elkaar, de muziek staat aan en de vrijgezel staat in het middelpunt. Hieronder vind je acht opdrachten voor in de bus die je allemaal op je plek speelt.</p>

<h2>Waarom spelletjes in de bus zo goed werken</h2>
<p>In de bus is iedereen samen, ook de mensen die elkaar nog niet kennen. Vrienden van vroeger, familie en collega's raken met een quiz of een bingokaart sneller aan de praat dan aan een lange tafel in een restaurant. En omdat een rit een vast begin en einde heeft, past er precies een spelletje of twee in.</p>
<p>In een <a href="{$landing}">partybus voor je vrijgezellenfeest</a> zitten licht, geluid en een gastvrouwke dat voor de drankjes zorgt. Jij hoeft alleen nog de opdrachten mee te nemen.</p>

<h2>Zo bereid je de opdrachten voor</h2>
<ul>
<li><strong>Een week van tevoren:</strong> stel de vragen voor de quiz op met de partner en maak de bingokaarten.</li>
<li><strong>Verdeel de rollen:</strong> wie leest voor, wie houdt de score bij en wie doet de muziek?</li>
<li><strong>Neem een tas mee</strong> met kaartjes, pennen, de bingokaarten en eventueel kleine prijsjes.</li>
<li><strong>Plan per rit één of twee opdrachten,</strong> dan blijft er ook tijd om gewoon te kletsen en te zingen.</li>
</ul>
<p>Zoek je ideeën voor de rest van de dag? Bekijk onze <a href="{$ideeen}">vrijgezellenfeest ideeën</a>, en vink met de <a href="{$lijst}">checklist</a> af wat je al geregeld hebt.</p>
HTML,
	);
}

/**
 * Uitroltaak blog-opdrachten-v1: het artikel met de opdrachten plaatsen, het blok Opdrachten van de
 * vrijgezellenpagina halen en de links en kaarten bijwerken.
 */
function thema_blog_opdrachten_plaatsen() {
	$uit  = array();
	$term = get_category_by_slug( 'tips-en-tricks' ) ?: get_category_by_slug( 'vrijgezellenfeest' );
	$gel  = get_page_by_path( 'vrijgezellenfeest', OBJECT, 'gelegenheid' );
	$id   = thema_blog_artikel_plaatsen( thema_blog_artikel_opdrachten(), $term ? (int) $term->term_id : 0, $gel ? (int) $gel->ID : 0 );
	if ( function_exists( 'update_field' ) ) {
		update_field( 'field_blog_auteur_rol', 'Rijdt groepen door heel Nederland en daarbuiten', $id );
	}
	$uit['artikel'] = $id;

	/* Links in de andere artikelen naar het nieuwe artikel. */
	$oud  = home_url( '/gelegenheden/vrijgezellenfeest/' ) . '#opdrachten';
	$nieuw = get_permalink( $id );
	foreach ( array( 'vrijgezellenfeest-organiseren-tips', 'checklist-vrijgezellenfeest', 'vrijgezellenfeest-ideeen' ) as $slug ) {
		$p = get_page_by_path( $slug, OBJECT, 'post' );
		if ( $p && false !== strpos( $p->post_content, $oud ) ) {
			wp_update_post( array( 'ID' => $p->ID, 'post_content' => str_replace( $oud, $nieuw, $p->post_content ) ) );
			$uit['links'][] = $slug;
		}
	}

	if ( $gel && function_exists( 'delete_row' ) ) {
		$layouts = (array) get_post_meta( $gel->ID, 'blokken', true );
		/* Routetekst die naar het blok verwees. */
		foreach ( $layouts as $i => $layout ) {
			if ( 'route' === $layout ) {
				$n = (int) get_post_meta( $gel->ID, "blokken_{$i}_stappen", true );
				for ( $j = 0; $j < $n; $j++ ) {
					$sleutel = "blokken_{$i}_stappen_{$j}_tekst";
					$tekst   = (string) get_post_meta( $gel->ID, $sleutel, true );
					if ( false !== strpos( $tekst, 'opdrachten verderop op deze pagina' ) ) {
						update_post_meta( $gel->ID, $sleutel, str_replace( 'met de opdrachten verderop op deze pagina.', 'met spelletjes en opdrachten voor de hele groep.', $tekst ) );
					}
				}
			}
		}
		/* Drie artikelen in de kaarten: opdrachten, ideeën en de checklist. */
		$ids = array();
		foreach ( array( 'spelletjes-opdrachten-vrijgezellenfeest-bus', 'vrijgezellenfeest-ideeen', 'checklist-vrijgezellenfeest' ) as $slug ) {
			$p = get_page_by_path( $slug, OBJECT, 'post' );
			if ( $p ) {
				$ids[] = (string) $p->ID;
			}
		}
		foreach ( $layouts as $i => $layout ) {
			if ( 'artikelen' === $layout ) {
				update_post_meta( $gel->ID, "blokken_{$i}_keuze", $ids );
				update_post_meta( $gel->ID, "blokken_{$i}_intro", 'Spelletjes voor in de bus, ideeën voor de dag en een checklist om af te vinken. Zo weet je zeker dat alles op tijd geregeld is.' );
			}
		}
		/* Het blok Opdrachten eruit (van achter naar voren, zodat de nummering klopt). */
		foreach ( array_reverse( $layouts, true ) as $i => $layout ) {
			if ( 'opdrachten' === $layout ) {
				delete_row( 'field_blokken', (int) $i + 1, $gel->ID );
				$uit['blok_weg'] = $i;
			}
		}
		clean_post_cache( $gel->ID );
	}
	return $uit;
}

/**
 * Uitroltaak blog-onderwerp-adres-v1: onderwerpen op /blog/onderwerp/{slug}/. De regels worden weggegooid,
 * zodat WordPress ze bij het volgende bezoek opnieuw opbouwt (in wp eval staan de oude taxonomieregels al vast).
 */
function thema_blog_onderwerp_adres() {
	update_option( 'category_base', 'blog/onderwerp' );
	update_option( 'tag_base', 'blog/tag' );
	delete_option( 'rewrite_rules' );
	return 'blog/onderwerp';
}

/**
 * Uitroltaak blog-in-footer-v1: Blog in de eerste footerkolom (Snel naar), voor Contact.
 * Laat de rest van het menu staan zoals het in WordPress is ingericht.
 */
function thema_blog_in_footer() {
	$blog = thema_blog_pagina();
	$plek = get_nav_menu_locations();
	if ( ! $blog || empty( $plek['footer_1'] ) ) {
		return 'geen blog of footermenu';
	}
	$menu  = (int) $plek['footer_1'];
	$items = (array) wp_get_nav_menu_items( $menu );
	foreach ( $items as $item ) {
		if ( (int) $item->object_id === (int) $blog->ID ) {
			return 'staat er al';
		}
	}
	/* Plek: voor Contact, anders achteraan. Alles vanaf die plek schuift een op. */
	$positie = count( $items ) + 1;
	foreach ( $items as $item ) {
		if ( 'contact' === strtolower( trim( $item->title ) ) ) {
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
