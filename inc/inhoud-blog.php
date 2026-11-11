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
	$wp_rewrite->set_category_base( 'onderwerp' );
	update_option( 'tag_base', 'tag' );

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

	flush_rewrite_rules( false );
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
<p>Kleine dingen maken het verschil. Een dresscode of thema, zoals iedereen in dezelfde kleur of een sjerp voor de vrijgezel, ziet er op elke foto feestelijk uit. Een gezamenlijke playlist met de favorieten van de vrijgezel zet meteen de toon. En met een paar <a href="{$landing}#opdrachten">opdrachten voor in de bus</a> vliegt de tijd tussen twee stops voorbij.</p>

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
<li><strong>Opdrachten en spelletjes voorbereiden.</strong> Denk aan een quiz over het stel, een bingokaart voor de dag of kaartjes met advies voor het huwelijk. Inspiratie vind je bij onze <a href="{$landing}#opdrachten">opdrachten voor in de bus</a>.</li>
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
