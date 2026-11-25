<?php
/**
 * Onderhoud na de site-analyse van 25 november 2026 (zie het projectdocument site-analyse-nov-2026).
 *
 * - juridisch-live-v1: Privacyverklaring en Algemene voorwaarden overnemen van de huidige site (lekkergeelbusje.nl).
 * - home-h1-v1:        vaste H1 voor Google op de homepage ("Partybus huren in Brabant"), de wisselende kop blijft zichtbaar.
 * - spelling-25nov-v1: spelfouten uit de analyse, in de inhoud, de Rank Math-velden en de formulieren.
 * - seo-25nov-v1:      titels en omschrijvingen in Google op basis van zoekwoorden (SE Ranking, NL, 25 nov 2026):
 *                      partybus 3.200, bus huren 14.800 (te breed), feestbus huren 480 (moeilijkheid 5), feestbus 320,
 *                      discobus huren 140, partybus huren amsterdam 110, rotterdam 90, breda 70, partybus brabant 70,
 *                      partybus huren prijs 70, partybus huren 50 personen 70, partybus prijs 50, tilburg 40, den bosch 30.
 */

defined( 'ABSPATH' ) || exit;

/* --------------------------------------------------------------------------
 * Juridische pagina's van de huidige site
 * ----------------------------------------------------------------------- */

/**
 * Tekst van een Elementor-pagina op de huidige site als schone HTML (koppen, alinea's, lijsten).
 * De paginatitel (H1) laten we weg: die zet het tekstblok zelf neer.
 */
function thema_juridisch_ophalen( $url ) {
	$antwoord = wp_remote_get( $url, array( 'timeout' => 20, 'user-agent' => 'Mozilla/5.0 (Lekker Geel thema)' ) );
	if ( is_wp_error( $antwoord ) ) {
		return $antwoord;
	}
	$html = wp_remote_retrieve_body( $antwoord );
	if ( 200 !== (int) wp_remote_retrieve_response_code( $antwoord ) || '' === $html ) {
		return new WP_Error( 'juridisch', 'Geen pagina: ' . wp_remote_retrieve_response_code( $antwoord ) );
	}
	$dom = new DOMDocument();
	libxml_use_internal_errors( true );
	$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html );
	libxml_clear_errors();
	$xp   = new DOMXPath( $dom );
	$basis = $xp->query( '//*[@data-elementor-type="wp-page"]' )->item( 0 );
	if ( ! $basis ) {
		$basis = $xp->query( '//main' )->item( 0 );
	}
	if ( ! $basis ) {
		return new WP_Error( 'juridisch', 'Geen inhoud gevonden' );
	}
	$toegestaan = array(
		'a'      => array( 'href' => true ),
		'strong' => array(),
		'b'      => array(),
		'em'     => array(),
		'i'      => array(),
		'br'     => array(),
		'li'     => array(),
	);
	$uit = '';
	foreach ( $xp->query( './/h2|.//h3|.//h4|.//p|.//ul|.//ol', $basis ) as $el ) {
		/* Alleen de buitenste: een alinea in een lijst komt mee met de lijst. */
		for ( $p = $el->parentNode; $p && $p !== $basis; $p = $p->parentNode ) {
			if ( in_array( strtolower( $p->nodeName ), array( 'p', 'ul', 'ol', 'li' ), true ) ) {
				continue 2;
			}
		}
		$tag    = strtolower( $el->nodeName );
		$binnen = '';
		foreach ( $el->childNodes as $kind ) {
			$binnen .= $dom->saveHTML( $kind );
		}
		if ( in_array( $tag, array( 'h2', 'h3', 'h4' ), true ) ) {
			$tekst = trim( preg_replace( '/\s+/u', ' ', $el->textContent ) );
			if ( '' !== $tekst ) {
				$uit .= '<' . $tag . '>' . esc_html( $tekst ) . '</' . $tag . ">\n";
			}
			continue;
		}
		$binnen = trim( wp_kses( $binnen, $toegestaan ) );
		$binnen = trim( preg_replace( '/^(&nbsp;|\s|<br\s*\/?>)+|(&nbsp;|\s|<br\s*\/?>)+$/u', '', $binnen ) );
		if ( '' === $binnen ) {
			continue;
		}
		$uit .= '<' . $tag . '>' . $binnen . '</' . $tag . ">\n";
	}
	return '' === trim( $uit ) ? new WP_Error( 'juridisch', 'Lege inhoud' ) : $uit;
}

/**
 * Uitroltaak juridisch-live-v1. Alleen als de pagina nog de plaatshouder heeft, zodat een eigen tekst blijft staan.
 */
function thema_juridisch_van_live() {
	if ( ! function_exists( 'get_field' ) ) {
		return 'geen ACF';
	}
	$uit = array();
	foreach ( array( 'privacyverklaring', 'algemene-voorwaarden' ) as $slug ) {
		$pagina = get_page_by_path( $slug );
		if ( ! $pagina ) {
			$uit[ $slug ] = 'pagina niet gevonden';
			continue;
		}
		$html = thema_juridisch_ophalen( 'https://lekkergeelbusje.nl/' . $slug . '/' );
		if ( is_wp_error( $html ) ) {
			$uit[ $slug ] = $html->get_error_message();
			continue;
		}
		$blokken = (array) get_field( 'blokken', $pagina->ID, false );
		$gezet   = false;
		foreach ( $blokken as $i => $blok ) {
			if ( 'tekst' !== ( $blok['acf_fc_layout'] ?? '' ) ) {
				continue;
			}
			$sleutel = 'blokken_' . $i . '_tekst';
			$huidig  = (string) get_post_meta( $pagina->ID, $sleutel, true );
			if ( '' === $huidig || false !== strpos( $huidig, '[Tekst voor' ) ) {
				update_post_meta( $pagina->ID, $sleutel, $html );
				$gezet = true;
			}
			break;
		}
		clean_post_cache( $pagina->ID );
		$uit[ $slug ] = $gezet ? strlen( $html ) . ' tekens' : 'niet aangepast (eigen tekst of geen tekstblok)';
	}
	return $uit;
}

/* --------------------------------------------------------------------------
 * Homepage: vaste H1 voor Google
 * ----------------------------------------------------------------------- */

/**
 * Uitroltaak home-h1-v1: het veld Kop voor Google van de eerste hero op de homepage.
 */
function thema_home_h1_google() {
	$id = (int) get_option( 'page_on_front' );
	if ( ! $id ) {
		return 'geen voorpagina';
	}
	$layouts = (array) get_post_meta( $id, 'blokken', true );
	foreach ( $layouts as $i => $layout ) {
		if ( 'hero' === $layout ) {
			update_post_meta( $id, 'blokken_' . $i . '_kop_google', 'Partybus huren in Brabant' );
			update_post_meta( $id, '_blokken_' . $i . '_kop_google', 'field_hero_kop_google' );
			clean_post_cache( $id );
			return 'blok ' . $i;
		}
	}
	return 'geen hero';
}

/* --------------------------------------------------------------------------
 * Spelling
 * ----------------------------------------------------------------------- */

function thema_spelling_25nov_paren() {
	return array(
		/* Langste eerst, zodat een korter paar een langer niet half vervangt. */
		'Hardstyle en hardcore festivals'                    => 'Hardstyle- en hardcorefestivals',
		'hardstyle en hardcore festivals'                    => 'hardstyle- en hardcorefestivals',
		'bijv. bruiloft, vriendendag of voetbalwedstrijd..'  => 'bijv. bruiloft, vriendendag of jubileum',
		"bijv: 'Wij hebben een jarige bij' of 'kan er"       => "bijv. 'Wij hebben een jarige bij' of 'Kan er",
		'Bij retour plannen we de terugreis direct mee in.' => 'Bij retour plannen we de terugreis direct mee.',
		'Hier ontvang je de offerte op.'                     => 'Hierop ontvang je de offerte.',
		'Kunnen we op factuur op bedrijfsnaam betalen?'      => 'Kunnen we een factuur op bedrijfsnaam krijgen?',
		'Met een groep naar een festival is pas leuk'        => 'Met een groep naar een festival gaan is pas leuk',
		'Afwijkende ophaal locatie?'                         => 'Afwijkende ophaallocatie?',
		'rijden we heel Brabant'                             => 'rijden we door heel Brabant',
		'Grootte van groep'                                  => 'Grootte van de groep',
		'in een keer mee'                                    => 'in één keer mee',
		'Familieuitje'                                       => 'Familie-uitje',
		'familieuitje'                                       => 'familie-uitje',
		'DJ Booth'                                           => 'dj-booth',
		"huis-DJ's"                                          => "huis-dj's",
		'Wij zijn de BOB'                                    => 'Wij zijn de Bob',
		'Geen BOB nodig'                                     => 'Geen Bob nodig',
		'iemand BOB zijn'                                    => 'iemand Bob zijn',
		'Paleis Raadhuis'                                    => 'Paleis-Raadhuis',
		'Decibel outdoor'                                    => 'Decibel Outdoor',
		'Hardstyle festival'                                 => 'Hardstylefestival',
		'hardstyle festival'                                 => 'hardstylefestival',
		'Hardcore festival'                                  => 'Hardcorefestival',
		'hardcore festival'                                  => 'hardcorefestival',
		'Bruiloft ideeën'                                    => 'Bruiloftideeën',
		'bruiloft ideeën'                                    => 'bruiloftideeën',
		'Bedrijfsuitje ideeën'                               => 'Bedrijfsuitje-ideeën',
		'bedrijfsuitje ideeën'                               => 'bedrijfsuitje-ideeën',
		'Vrijgezellenfeest ideeën'                           => 'Vrijgezellenfeestideeën',
		'vrijgezellenfeest ideeën'                           => 'vrijgezellenfeestideeën',
		'Festival kleding'                                   => 'Festivalkleding',
		'festival kleding'                                   => 'festivalkleding',
		'Festival paklijst'                                  => 'Festivalpaklijst',
		'festival paklijst'                                  => 'festivalpaklijst',
	);
}

/**
 * Een waarde (tekst, of array of object met teksten) verbeteren.
 */
function thema_spelling_25nov_tekst( $waarde ) {
	if ( is_array( $waarde ) ) {
		return array_map( 'thema_spelling_25nov_tekst', $waarde );
	}
	if ( ! is_string( $waarde ) || '' === $waarde ) {
		return $waarde;
	}
	if ( 'Over Ons' === $waarde ) {
		return 'Over ons';
	}
	$paren  = thema_spelling_25nov_paren();
	$waarde = str_replace( array_keys( $paren ), array_values( $paren ), $waarde );
	/* Overal dezelfde apostrof: collega’s wordt collega's. */
	return preg_replace( "/(\p{L})\x{2019}s\b/u", "$1's", $waarde );
}

/**
 * De zoekteksten voor de database: alleen rijen die er een bevatten, worden bekeken.
 */
function thema_spelling_25nov_zoek() {
	return array_merge( array_keys( thema_spelling_25nov_paren() ), array( 'Over Ons', '’s' ) );
}

function thema_spelling_25nov_where( $kolom ) {
	global $wpdb;
	$delen = array();
	foreach ( thema_spelling_25nov_zoek() as $zoek ) {
		$delen[] = $wpdb->prepare( "{$kolom} LIKE %s", '%' . $wpdb->esc_like( $zoek ) . '%' );
	}
	return '( ' . implode( ' OR ', $delen ) . ' )';
}

/**
 * Uitroltaak spelling-25nov-v1.
 */
function thema_spelling_25nov() {
	global $wpdb;
	$uit = array( 'meta' => 0, 'berichten' => 0, 'termen' => 0, 'opties' => 0, 'formulieren' => 0 );

	/* Velden van berichten, ook Rank Math en ACF. Het focus keyword blijft het zoekwoord zoals mensen het typen. */
	$overslaan = array( 'rank_math_focus_keyword', '_wp_old_slug', '_wp_attached_file', '_wp_attachment_metadata' );
	foreach ( $wpdb->get_results( "SELECT meta_id, post_id, meta_key, meta_value FROM {$wpdb->postmeta} WHERE " . thema_spelling_25nov_where( 'meta_value' ) ) as $rij ) {
		if ( in_array( $rij->meta_key, $overslaan, true ) ) {
			continue;
		}
		$oud   = maybe_unserialize( $rij->meta_value );
		$nieuw = thema_spelling_25nov_tekst( $oud );
		if ( $nieuw !== $oud ) {
			$wpdb->update( $wpdb->postmeta, array( 'meta_value' => maybe_serialize( $nieuw ) ), array( 'meta_id' => $rij->meta_id ) );
			clean_post_cache( $rij->post_id );
			$uit['meta']++;
		}
	}

	/* Titels, teksten en samenvattingen. */
	$where = thema_spelling_25nov_where( 'post_title' ) . ' OR ' . thema_spelling_25nov_where( 'post_content' ) . ' OR ' . thema_spelling_25nov_where( 'post_excerpt' );
	foreach ( $wpdb->get_results( "SELECT ID, post_title, post_content, post_excerpt FROM {$wpdb->posts} WHERE post_type <> 'revision' AND ( {$where} )" ) as $rij ) {
		$nieuw = array(
			'post_title'   => thema_spelling_25nov_tekst( $rij->post_title ),
			'post_content' => thema_spelling_25nov_tekst( $rij->post_content ),
			'post_excerpt' => thema_spelling_25nov_tekst( $rij->post_excerpt ),
		);
		if ( $nieuw['post_title'] !== $rij->post_title || $nieuw['post_content'] !== $rij->post_content || $nieuw['post_excerpt'] !== $rij->post_excerpt ) {
			$wpdb->update( $wpdb->posts, $nieuw, array( 'ID' => $rij->ID ) );
			clean_post_cache( $rij->ID );
			$uit['berichten']++;
		}
	}

	/* Onderwerpen: beschrijving en velden. */
	foreach ( $wpdb->get_results( "SELECT term_taxonomy_id, term_id, description FROM {$wpdb->term_taxonomy} WHERE " . thema_spelling_25nov_where( 'description' ) ) as $rij ) {
		$nieuw = thema_spelling_25nov_tekst( $rij->description );
		if ( $nieuw !== $rij->description ) {
			$wpdb->update( $wpdb->term_taxonomy, array( 'description' => $nieuw ), array( 'term_taxonomy_id' => $rij->term_taxonomy_id ) );
			clean_term_cache( (int) $rij->term_id );
			$uit['termen']++;
		}
	}
	foreach ( $wpdb->get_results( "SELECT meta_id, term_id, meta_key, meta_value FROM {$wpdb->termmeta} WHERE " . thema_spelling_25nov_where( 'meta_value' ) ) as $rij ) {
		if ( in_array( $rij->meta_key, $overslaan, true ) ) {
			continue;
		}
		$oud   = maybe_unserialize( $rij->meta_value );
		$nieuw = thema_spelling_25nov_tekst( $oud );
		if ( $nieuw !== $oud ) {
			$wpdb->update( $wpdb->termmeta, array( 'meta_value' => maybe_serialize( $nieuw ) ), array( 'meta_id' => $rij->meta_id ) );
			clean_term_cache( (int) $rij->term_id );
			$uit['termen']++;
		}
	}

	/* Thema-instellingen (ACF-optiepagina). */
	foreach ( $wpdb->get_results( "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE 'options\\_%' AND " . thema_spelling_25nov_where( 'option_value' ) ) as $rij ) {
		$oud   = maybe_unserialize( $rij->option_value );
		$nieuw = thema_spelling_25nov_tekst( $oud );
		if ( $nieuw !== $oud ) {
			update_option( $rij->option_name, $nieuw );
			$uit['opties']++;
		}
	}

	/* Formulieren: labels, uitleg, plaatshouders, meldingen. Datumvelden krijgen dd-mm-jjjj. */
	if ( class_exists( 'GFAPI' ) ) {
		foreach ( GFAPI::get_forms() as $form ) {
			$oud   = json_decode( wp_json_encode( $form ), true );
			$nieuw = thema_spelling_25nov_tekst( $oud );
			foreach ( (array) ( $nieuw['fields'] ?? array() ) as $i => $veld ) {
				if ( 'date' === ( $veld['type'] ?? '' ) && 'datepicker' === ( $veld['dateType'] ?? 'datepicker' ) && '' === (string) ( $veld['placeholder'] ?? '' ) ) {
					$nieuw['fields'][ $i ]['placeholder'] = 'dd-mm-jjjj';
				}
			}
			if ( $nieuw !== $oud ) {
				$resultaat = GFAPI::update_form( $nieuw );
				if ( ! is_wp_error( $resultaat ) ) {
					$uit['formulieren']++;
				}
			}
		}
	}

	wp_cache_flush();
	return $uit;
}

/* Datumveld van Gravity Forms: de schermlezertekst van de kalenderknop in het Nederlands. */
add_filter( 'gform_field_content', function ( $html, $veld ) {
	if ( is_admin() || 'date' !== $veld->type ) {
		return $html;
	}
	return str_replace( 'Choose date on calendar', 'kies een datum in de kalender', $html );
}, 20, 2 );

/* Telefoonvelden buiten de offerte (zoals het contactformulier): cijfertoetsenbord op mobiel. */
add_filter( 'gform_field_content', function ( $html, $veld ) {
	if ( is_admin() || ! in_array( $veld->type, array( 'text', 'phone' ), true ) ) {
		return $html;
	}
	if ( 'phone' !== $veld->type && false === stripos( (string) $veld->label, 'telefoon' ) ) {
		return $html;
	}
	$naam = preg_quote( 'input_' . $veld->id, '/' );
	return preg_replace_callback(
		'/<input\b[^>]*\bname=([\'"])' . $naam . '\1[^>]*>/',
		function ( $m ) {
			$tag = preg_replace( '/\btype=([\'"])text\1/', 'type="tel"', $m[0] );
			if ( false === stripos( $tag, 'inputmode=' ) ) {
				$tag = preg_replace( '/\s*\/?>$/', ' inputmode="tel" />', $tag );
			}
			return $tag;
		},
		$html
	);
}, 20, 2 );

/* --------------------------------------------------------------------------
 * SEO: titels en omschrijvingen
 * ----------------------------------------------------------------------- */

function thema_seo_25nov_lijst() {
	$merk = ' | Lekker Geel Busje';
	return array(
		array( 'voorpagina', '', 'Partybus & feestbus huren in Brabant' . $merk, 'Partybus of feestbus huren met chauffeur en gastvrouwke? Licht, lasers en geluid aan boord, door heel Nederland. Binnen 1 werkdag een vaste all-in prijs.', 'partybus huren,feestbus huren,partybus brabant' ),
		array( 'page', 'onze-bussen', 'Onze partybussen: tot 88 personen per bus' . $merk, 'Eigen gele partybussen met minstens 39 zitplaatsen en tot 88 personen per bus. Licht, lasers, rook en geluid altijd inbegrepen, met chauffeur en gastvrouwke.', 'partybus,discobus huren' ),
		array( 'page', 'over-ons', 'Over ons: de sfeerbus uit Brabant' . $merk, 'Lekker Geel Busje is de sfeerbus uit Oisterwijk: eigen gele bussen met chauffeur, gastvrouwke, licht en geluid. Vrolijk, persoonlijk en respectvol op weg.', 'sfeerbus' ),
		array( 'page', 'contact', 'Contact: bel, app of mail ons' . $merk, 'Een vraag over een rit of onze bussen? Bel 085 006 2061, app of mail ons. We reageren binnen 1 werkdag, meestal sneller. Een prijs vraag je aan via de offerte.', '' ),
		array( 'page', 'offerte-aanvragen', 'Partybus huren: vraag je prijs aan' . $merk, 'Wat kost een partybus? Vertel wanneer, waarheen en met hoeveel je bent en je krijgt binnen 1 werkdag een vaste all-in prijs. Gratis en vrijblijvend.', 'partybus huren prijs,partybus prijs' ),
		array( 'page', 'gelegenheden', 'Partybus voor elk feestje' . $merk, 'Partybus huren voor je vrijgezellenfeest, bedrijfsuitje, bruiloft, festival of uitje met vrienden. Met chauffeur, gastvrouwke, licht en geluid aan boord.', 'partybus' ),
		array( 'page', 'locaties/breda', 'Partybus huren Breda met chauffeur' . $merk, 'Partybus, feestbus of discobus huren naar Breda? Voor je vrijgezellenfeest of bedrijfsuitje: varen over de singels, karten en de Havermarkt. Snel een prijs.', 'partybus huren breda' ),
		array( 'page', 'locaties/tilburg', 'Partybus huren Tilburg met chauffeur' . $merk, 'Partybus, feestbus of discobus huren naar Tilburg? Voor je vrijgezellenfeest of bedrijfsuitje: Schrobbelèr, 013 en de Piushaven. Binnen 1 werkdag een prijs.', 'partybus huren tilburg' ),
		array( 'page', 'locaties/den-bosch', 'Partybus huren Den Bosch met chauffeur' . $merk, 'Partybus huren naar Den Bosch? Wat te doen in Den Bosch met je groep: Binnendieze, Tramkade en de Korte Putstraat. Voor vrijgezellenfeest of bedrijfsuitje.', 'partybus huren den bosch' ),
		array( 'page', 'locaties/rotterdam', 'Partybus huren Rotterdam met chauffeur' . $merk, 'Partybus, feestbus of discobus huren naar Rotterdam? Voor je vrijgezellenfeest of groepsuitje: karaoke, de Euromast en de Witte de Withstraat. Snel een prijs.', 'partybus huren rotterdam' ),
		array( 'page', 'locaties/amsterdam', 'Partybus huren Amsterdam met chauffeur' . $merk, 'Partybus, feestbus of discobus huren naar Amsterdam? Voor je vrijgezellenfeest of groepsuitje: rondvaart, karaoke en een dinnershow. Binnen 1 werkdag een prijs.', 'partybus huren amsterdam' ),
		array( 'page', 'locaties/maastricht', 'Partybus huren Maastricht met chauffeur' . $merk, 'Partybus of feestbus huren naar Maastricht? De leukste uitjes voor je groep: rooftopbar, sloep, grotten en bierproeverij. Binnen 1 werkdag een vaste prijs.', 'partybus huren maastricht' ),
		array( 'page', 'vrienden-van-amstel-live', 'Bus naar Vrienden van Amstel LIVE 2027, vanaf € 25 retour', 'Met de gele bus naar Vrienden van Amstel LIVE in Ahoy. Instappen van Den Bosch tot Breda, heen en terug vanaf € 25. Reserveer je stoel in een minuut.', 'vrienden van amstel live vervoer' ),
		array( 'page', 'privacyverklaring', 'Privacyverklaring' . $merk, 'Zo gaat Lekker Geel Busje om met je persoonsgegevens: welke gegevens we verwerken, waarvoor, hoe lang we ze bewaren en welke rechten je hebt.', '' ),
		array( 'page', 'algemene-voorwaarden', 'Algemene voorwaarden' . $merk, 'De algemene voorwaarden van Lekker Geel Busje voor het huren van een sfeerbus met chauffeur: offerte, reservering, betaling, annulering en aansprakelijkheid.', '' ),
		array( 'page', 'huisregels', 'Huisregels in de bus' . $merk, 'De huisregels in de bus van Lekker Geel Busje: niet roken, geen eigen drank en respect voor de bus, het personeel en elkaar. Zo blijft het voor iedereen leuk.', '' ),
		array( 'gelegenheid', 'festival', '', 'Met de groep naar Paaspop, Intents, Defqon.1 of Zwarte Cross? De festivalbus haalt je op en brengt je weer thuis. Binnen 1 werkdag een vaste prijs.', '' ),
		array( 'gelegenheid', 'trip-met-vrienden', '', 'Uitje met vrienden, familie of de club? De partybus haalt iedereen op, rijdt van activiteit naar de stad en brengt je thuis. Binnen 1 werkdag een prijs.', '' ),
		array( 'post', 'bedrijfsuitje-werkkostenregeling', '', 'Valt een bedrijfsuitje of personeelsfeest onder de werkkostenregeling? De vrije ruimte in 2026, werkplek of buiten de deur, en een rekenvoorbeeld.', '' ),
		array( 'post', 'hardstyle-festivals', '', 'De bekendste hardstyle- en hardcorefestivals in Nederland en net over de grens, van Defqon.1 en Intents tot Qlimax. Plus tips om er met de groep te komen.', '' ),
		array( 'post', 'mannenuitje-ideeen', '', 'Op zoek naar een leuk mannenuitje of jongensuitje? Vijftien ideeën voor een dag uit met de jongens, van karten en brouwerijen tot een escape room.', '' ),
		array( 'category', 'teamuitjes', 'Teamuitjes: ideeën, tips en spellen' . $merk, 'Ideeën voor een origineel teamuitje of bedrijfsuitje, een stappenplan voor de organisator en spellen voor onderweg. Van kerstborrel tot personeelsfeest.', 'teamuitjes' ),
		array( 'category', 'groepsuitjes', 'Groepsuitjes: ideeën voor een dag uit' . $merk, 'Ideeën en tips voor een uitje met vrienden, vriendinnen, de jongens of de hele familie. Van kroegentocht tot vriendenweekend, zonder gedoe geregeld.', 'groepsuitjes' ),
		array( 'category', 'trouwen', 'Trouwen: checklist, ideeën en kosten' . $merk, 'Alles voor het bruidspaar, de getuigen en de ceremoniemeester: een checklist voor de trouwdag, ideeën, wat een bruiloft kost en jubilea vieren.', '' ),
		array( 'category', 'festivals', 'Festivals: paklijst, kleding en tips' . $merk, 'Alles voor een festivaldag met de groep: een paklijst, wat je aantrekt en de festivals waar we het vaakst naartoe rijden. Zo begint het feest in de bus.', '' ),
		array( 'category', 'tips-en-tricks', 'Tips en tricks voor je groepsuitje' . $merk, 'Tips, checklists en ideeën voor de organisator: zo regel je een vrijgezellenfeest, bedrijfsuitje of dag uit met de groep zonder gedoe.', '' ),
	);
}

/**
 * Uitroltaak seo-25nov-v1.
 */
function thema_seo_25nov() {
	$uit = array();
	foreach ( thema_seo_25nov_lijst() as $rij ) {
		list( $soort, $pad, $titel, $omschrijving, $focus ) = $rij;
		if ( 'category' === $soort ) {
			$term = get_category_by_slug( $pad );
			if ( ! $term ) {
				$uit[ $pad ] = 'niet gevonden';
				continue;
			}
			if ( $titel ) {
				update_term_meta( $term->term_id, 'rank_math_title', thema_seo_titel_naar_rank_math( $titel ) );
			}
			update_term_meta( $term->term_id, 'rank_math_description', $omschrijving );
			if ( $focus ) {
				update_term_meta( $term->term_id, 'rank_math_focus_keyword', $focus );
			}
			$uit[ $pad ] = 'ok';
			continue;
		}
		if ( 'voorpagina' === $soort ) {
			$id = (int) get_option( 'page_on_front' );
		} else {
			$bericht = get_page_by_path( $pad, OBJECT, $soort );
			$id      = $bericht ? (int) $bericht->ID : 0;
		}
		if ( ! $id ) {
			$uit[ $soort . ':' . $pad ] = 'niet gevonden';
			continue;
		}
		if ( $titel ) {
			thema_seo_titel_zetten( $titel, $id );
		}
		thema_seo_omschrijving_zetten( $omschrijving, $id );
		if ( $focus ) {
			update_post_meta( $id, 'rank_math_focus_keyword', $focus );
		}
		clean_post_cache( $id );
		$uit[ $soort . ':' . ( $pad ?: $id ) ] = 'ok';
	}
	return $uit;
}

/* --------------------------------------------------------------------------
 * Stoelverkoop: stand per avond actueel houden
 * De pagina staat in de cache van SiteGround. Na een nieuwe boeking of een andere status legen we de cache
 * van die pagina, zodat bezoekers meteen zien of de bus rijdt en hoeveel stoelen er nog nodig zijn.
 * ----------------------------------------------------------------------- */

function thema_stoelen_cache_onthouden( $boeking ) {
	static $wachtrij = null;
	if ( null === $wachtrij ) {
		$wachtrij = array();
		add_action( 'shutdown', function () use ( &$wachtrij ) {
			$paginas = array();
			foreach ( array_keys( $wachtrij ) as $id ) {
				$pagina = (int) get_post_meta( $id, '_stoel_pagina', true );
				if ( $pagina ) {
					$paginas[ $pagina ] = true;
				}
			}
			foreach ( array_keys( $paginas ) as $pagina ) {
				if ( function_exists( 'sg_cachepress_purge_cache' ) ) {
					sg_cachepress_purge_cache( get_permalink( $pagina ) );
				}
				clean_post_cache( $pagina );
			}
		} );
	}
	$wachtrij[ (int) $boeking ] = true;
}

foreach ( array( 'added_post_meta', 'updated_post_meta', 'deleted_post_meta' ) as $haak ) {
	add_action( $haak, function ( $meta_id, $post_id, $sleutel ) {
		if ( defined( 'THEMA_STOEL_TYPE' ) && in_array( $sleutel, array( '_stoel_aantal', '_stoel_status', '_stoel_rit' ), true ) && THEMA_STOEL_TYPE === get_post_type( $post_id ) ) {
			thema_stoelen_cache_onthouden( $post_id );
		}
	}, 10, 3 );
}
add_action( 'trashed_post', function ( $post_id ) {
	if ( defined( 'THEMA_STOEL_TYPE' ) && THEMA_STOEL_TYPE === get_post_type( $post_id ) ) {
		thema_stoelen_cache_onthouden( $post_id );
	}
} );

/* --------------------------------------------------------------------------
 * Muziek aan boord: bluetooth, microfoons en eigen draaitafels (25 november 2026)
 * Lekker Geel Busje heeft zelf geen draaitafels; "DJ Booth? Regelen we" klopte dus niet.
 * ----------------------------------------------------------------------- */

function thema_muziek_25nov_paren() {
	return array(
		'Een top geluidssysteem, feestverlichting en een koeling met koude drank aan boord. DJ Booth nodig? Regelen we!' => 'Een top geluidssysteem met bluetooth, microfoons, feestverlichting en een koeling met koude drank aan boord. Eigen draaitafels meenemen? Geen probleem.',
		'Natuurlijk. Sluit je eigen playlist aan op het geluidssysteem en de bus is jullie dansvloer. Liever een DJ Booth? Regelen we.' => 'Natuurlijk. Via bluetooth sluit je je eigen playlist aan op het geluidssysteem en de bus is jullie dansvloer. Microfoons liggen klaar. Eigen draaitafels meenemen? Geen probleem.',
		'Natuurlijk. Sluit de playlist van de vrijgezel aan op het geluidssysteem, of juist die van de vrienden. Liever een DJ Booth? Regelen we.' => 'Natuurlijk. Via bluetooth sluit je de playlist van de vrijgezel aan, of juist die van de vrienden. Microfoons liggen klaar en eigen draaitafels meenemen is geen probleem.',
		'Ja, sluit je eigen muziek aan op het geluidssysteem. DJ Booth nodig? Regelen we!' => 'Ja, via bluetooth sluit je je eigen muziek aan op het geluidssysteem. Microfoons liggen klaar. Eigen draaitafels meenemen? Geen probleem.',
		'DJ Booth nodig? Regelen we!'        => 'Eigen draaitafels meenemen? Geen probleem.',
		'Liever een DJ Booth? Regelen we.'   => 'Eigen draaitafels meenemen? Geen probleem.',
		'Ja. Sluit jullie eigen playlist aan, zet het licht vol aan' => 'Ja. Sluit via bluetooth jullie eigen playlist aan, zet het licht vol aan',
		'Sluit je eigen playlist aan op het geluidssysteem.' => 'Via bluetooth sluit je je eigen playlist aan op het geluidssysteem.',
		'Altijd. Geluidssysteem, feestverlichting, lasers en rookmachine gaan standaard mee.' => 'Altijd. Geluidssysteem met bluetooth, microfoons, feestverlichting, lasers en rookmachine gaan standaard mee.',
		'Professionele verlichting, lasers, rookmachine en een stevige geluidsinstallatie zitten altijd in de prijs.' => 'Professionele verlichting, lasers, rookmachine, microfoons en een stevige geluidsinstallatie met bluetooth zitten altijd in de prijs.',
		'lasers, rook en een geluidsinstallatie die er staat' => 'lasers, rook, microfoons en een geluidsinstallatie die er staat',
		'lasers, rook en een goede geluidsinstallatie'       => 'lasers, rook, microfoons en een goede geluidsinstallatie',
		'Jullie muziek, onze lasers en rook.'                => 'Jullie muziek via bluetooth, onze microfoons, lasers en rook.',
	);
}

function thema_muziek_25nov_tekst( $waarde ) {
	if ( is_array( $waarde ) ) {
		return array_map( 'thema_muziek_25nov_tekst', $waarde );
	}
	if ( ! is_string( $waarde ) || '' === $waarde ) {
		return $waarde;
	}
	if ( 'Professionele verlichting, lasers, rook en geluidsinstallatie' === $waarde ) {
		return 'Professionele verlichting, lasers, rook, microfoons en geluidsinstallatie met bluetooth';
	}
	$paren = thema_muziek_25nov_paren();
	return str_replace( array_keys( $paren ), array_values( $paren ), $waarde );
}

/**
 * Uitroltaak muziek-25nov-v1: de teksten in de database, plus twee nieuwe vragen op Onze bussen.
 */
function thema_muziek_25nov() {
	global $wpdb;
	$uit   = array( 'meta' => 0, 'berichten' => 0 );
	$zoek  = array_merge( array_keys( thema_muziek_25nov_paren() ), array( 'Professionele verlichting, lasers, rook en geluidsinstallatie' ) );
	$delen = array();
	foreach ( $zoek as $z ) {
		$delen[] = $wpdb->prepare( 'meta_value LIKE %s', '%' . $wpdb->esc_like( $z ) . '%' );
	}
	foreach ( $wpdb->get_results( "SELECT meta_id, post_id, meta_value FROM {$wpdb->postmeta} WHERE " . implode( ' OR ', $delen ) ) as $rij ) {
		$oud   = maybe_unserialize( $rij->meta_value );
		$nieuw = thema_muziek_25nov_tekst( $oud );
		if ( $nieuw !== $oud ) {
			$wpdb->update( $wpdb->postmeta, array( 'meta_value' => maybe_serialize( $nieuw ) ), array( 'meta_id' => $rij->meta_id ) );
			clean_post_cache( $rij->post_id );
			$uit['meta']++;
		}
	}
	foreach ( $wpdb->get_results( "SELECT ID, post_content FROM {$wpdb->posts} WHERE post_type <> 'revision' AND post_content LIKE '%DJ Booth%'" ) as $rij ) {
		$wpdb->update( $wpdb->posts, array( 'post_content' => thema_muziek_25nov_tekst( $rij->post_content ) ), array( 'ID' => $rij->ID ) );
		clean_post_cache( $rij->ID );
		$uit['berichten']++;
	}

	/* Onze bussen: vragen over de microfoon en eigen draaitafels erbij, als ze er nog niet staan. */
	$bussen = get_page_by_path( 'onze-bussen' );
	if ( $bussen && function_exists( 'get_field' ) ) {
		foreach ( array_values( (array) get_field( 'blokken', $bussen->ID, false ) ) as $i => $blok ) {
			if ( 'faq' !== ( $blok['acf_fc_layout'] ?? '' ) ) {
				continue;
			}
			$n      = (int) get_post_meta( $bussen->ID, 'blokken_' . $i . '_vragen', true );
			$vragen = array();
			for ( $j = 0; $j < $n; $j++ ) {
				$vragen[] = (string) get_post_meta( $bussen->ID, 'blokken_' . $i . '_vragen_' . $j . '_vraag', true );
			}
			$sleutel_vraag    = (string) get_post_meta( $bussen->ID, '_blokken_' . $i . '_vragen_0_vraag', true );
			$sleutel_antwoord = (string) get_post_meta( $bussen->ID, '_blokken_' . $i . '_vragen_0_antwoord', true );
			$nieuw            = array(
				array( 'Is er een microfoon in de bus?', 'Ja. In elke bus liggen microfoons klaar, voor meezingen, een speech of een quiz onderweg. Ze zitten gewoon in de prijs.' ),
				array( 'Mogen we eigen draaitafels meenemen?', 'Geen probleem. Zelf hebben we geen draaitafels aan boord, maar je eigen set sluit je zo aan op het geluidssysteem. Zet het even in je aanvraag, dan houden we er rekening mee.' ),
			);
			foreach ( $nieuw as $paar ) {
				if ( in_array( $paar[0], $vragen, true ) ) {
					continue;
				}
				update_post_meta( $bussen->ID, 'blokken_' . $i . '_vragen_' . $n . '_vraag', $paar[0] );
				update_post_meta( $bussen->ID, 'blokken_' . $i . '_vragen_' . $n . '_antwoord', $paar[1] );
				if ( $sleutel_vraag ) {
					update_post_meta( $bussen->ID, '_blokken_' . $i . '_vragen_' . $n . '_vraag', $sleutel_vraag );
					update_post_meta( $bussen->ID, '_blokken_' . $i . '_vragen_' . $n . '_antwoord', $sleutel_antwoord );
				}
				$n++;
			}
			update_post_meta( $bussen->ID, 'blokken_' . $i . '_vragen', $n );
			clean_post_cache( $bussen->ID );
			$uit['bussen_vragen'] = $n;
		}
	}
	wp_cache_flush();
	return $uit;
}

/* --------------------------------------------------------------------------
 * Losse reparaties uit de analyse
 * ----------------------------------------------------------------------- */

/**
 * Uitroltaak faq-stops-v1: op /gelegenheden/ stond bij "meerdere stops" het antwoord over drinken.
 */
function thema_faq_stops_herstellen() {
	global $wpdb;
	$goed  = 'Zeker, daar is een busje voor. Zet de stops in je aanvraag, dan nemen we ze mee in de route en de offerte.';
	$uit   = 0;
	$rijen = $wpdb->get_results( "SELECT post_id, meta_key FROM {$wpdb->postmeta} WHERE meta_key REGEXP '^blokken_[0-9]+_vragen_[0-9]+_vraag$' AND meta_value LIKE '%meerdere stops%'" );
	foreach ( $rijen as $r ) {
		$sleutel = substr( $r->meta_key, 0, -strlen( '_vraag' ) ) . '_antwoord';
		$oud     = (string) get_post_meta( (int) $r->post_id, $sleutel, true );
		if ( false !== stripos( $oud, 'drinken in de bus' ) || false !== stripos( $oud, 'eigen drank' ) ) {
			update_post_meta( (int) $r->post_id, $sleutel, $goed );
			clean_post_cache( (int) $r->post_id );
			$uit++;
		}
	}
	return $uit;
}

/**
 * Uitroltaak vval-agenda-link-v4: de link in de festivalagenda direct in de velden, de eerdere taak kwam niet aan.
 */
function thema_vval_agenda_link_direct() {
	$festival = get_page_by_path( 'festival', OBJECT, 'gelegenheid' );
	$pagina   = get_page_by_path( 'vrienden-van-amstel-live' );
	if ( ! $festival || ! $pagina ) {
		return 'niet gevonden';
	}
	$uit = 0;
	foreach ( (array) get_post_meta( $festival->ID, 'blokken', true ) as $i => $layout ) {
		if ( 'agenda' !== $layout ) {
			continue;
		}
		$n = (int) get_post_meta( $festival->ID, 'blokken_' . $i . '_items', true );
		for ( $j = 0; $j < $n; $j++ ) {
			$basis = 'blokken_' . $i . '_items_' . $j . '_';
			if ( false === stripos( (string) get_post_meta( $festival->ID, $basis . 'titel', true ), 'Vrienden van Amstel' ) ) {
				continue;
			}
			$ref = (string) get_post_meta( $festival->ID, '_' . $basis . 'titel', true );
			update_post_meta( $festival->ID, $basis . 'link', array( 'title' => 'Stoel in de bus vanaf € 25', 'url' => get_permalink( $pagina ), 'target' => '' ) );
			update_post_meta( $festival->ID, $basis . 'tekst', 'De grootste kroeg van Nederland, met meezingers van begin tot eind. Pak een losse stoel in de bus vanaf Den Bosch, Helvoirt, Haaren, Oisterwijk, Berkel-Enschot, Tilburg of Breda.' );
			if ( $ref ) {
				update_post_meta( $festival->ID, '_' . $basis . 'link', preg_replace( '/titel$/', 'link', $ref ) );
				update_post_meta( $festival->ID, '_' . $basis . 'tekst', preg_replace( '/titel$/', 'tekst', $ref ) );
			}
			$uit++;
		}
	}
	clean_post_cache( $festival->ID );
	return $uit;
}

/* --------------------------------------------------------------------------
 * Homepage: banner voor de bus naar Vrienden van Amstel LIVE
 * ----------------------------------------------------------------------- */

/**
 * Uitroltaak home-vval-banner-v1: blok Uitgelicht direct onder de hero, zichtbaar tot en met de laatste avond.
 */
function thema_home_vval_banner() {
	if ( ! function_exists( 'get_field' ) ) {
		return 'geen ACF';
	}
	$home   = (int) get_option( 'page_on_front' );
	$pagina = get_page_by_path( 'vrienden-van-amstel-live' );
	if ( ! $home || ! $pagina ) {
		return 'niet gevonden';
	}
	$rijen = array_values( (array) get_field( 'blokken', $home, false ) );
	foreach ( $rijen as $rij ) {
		if ( 'uitgelicht' === ( $rij['acf_fc_layout'] ?? '' ) ) {
			return 'staat er al';
		}
	}
	/* Foto: de eerste foto uit de hero van de VVAL-pagina. */
	$beeld = 0;
	foreach ( (array) get_post_meta( $pagina->ID, 'blokken', true ) as $i => $layout ) {
		if ( 'hero' !== $layout ) {
			continue;
		}
		$fotos = array_filter( array_map( 'intval', (array) maybe_unserialize( get_post_meta( $pagina->ID, 'blokken_' . $i . '_fotos', true ) ) ) );
		$beeld = $fotos ? (int) reset( $fotos ) : (int) get_post_meta( $pagina->ID, 'blokken_' . $i . '_beeld', true );
		break;
	}
	if ( ! $beeld ) {
		$beeld = (int) get_post_thumbnail_id( $pagina->ID );
	}
	$blok = array(
		'acf_fc_layout'                  => 'uitgelicht',
		'field_uitgelicht_eyebrow'       => 'Nieuw: losse stoelen',
		'field_uitgelicht_kop'           => 'Met de bus naar *Vrienden van Amstel LIVE*',
		'field_uitgelicht_tekst'         => 'Kaartjes voor de grootste kroeg van Nederland binnen? Pak een stoel in de gele bus. Instappen tussen Den Bosch en Breda, en na afloop rijden we je terug.',
		'field_uitgelicht_punten'        => "Heen en terug vanaf € 25\nZeven opstapplekken, van Den Bosch tot Breda\nPas betalen als je avond rijdt",
		'field_uitgelicht_knop'          => array( 'title' => 'Kies je avond', 'url' => get_permalink( $pagina ), 'target' => '' ),
		'field_uitgelicht_beeld'         => $beeld ? (string) $beeld : '',
		'field_uitgelicht_sticker_klein' => 'vanaf',
		'field_uitgelicht_sticker_groot' => '€ 25',
		'field_uitgelicht_tonen_tot'     => '20270129',
		'field_uitgelicht_anker'         => 'vrienden-van-amstel-live',
	);
	array_splice( $rijen, 1, 0, array( $blok ) );
	update_field( 'field_blokken', $rijen, $home );
	clean_post_cache( $home );
	return array( 'plek' => 1, 'foto' => $beeld );
}
