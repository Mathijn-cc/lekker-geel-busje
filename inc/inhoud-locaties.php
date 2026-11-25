<?php
/**
 * Plaatspagina's onder /locaties/ (Maastricht, Rotterdam, Amsterdam, Den Bosch), in de opbouw van Tilburg (pagina 192).
 */

defined( 'ABSPATH' ) || exit;

/**
 * Uitroltaak tilburg-uitlezen-v1: de blokken van Tilburg als voorbeeld in het uitrollog (leest alleen).
 */
function thema_tilburg_uitlezen() {
	$p = get_page_by_path( 'locaties/tilburg' );
	if ( ! $p || ! function_exists( 'get_field' ) ) {
		return 'geen tilburg';
	}
	$kort = function ( $w ) use ( &$kort ) {
		if ( is_array( $w ) ) {
			if ( isset( $w['ID'], $w['url'], $w['mime_type'] ) ) {
				return 'beeld:' . $w['ID'] . ':' . basename( $w['url'] );
			}
			return array_map( $kort, $w );
		}
		return $w;
	};
	return array(
		'id'      => $p->ID,
		'ouder'   => $p->post_parent,
		'sjabloon'=> get_page_template_slug( $p->ID ),
		'titel'   => $p->post_title,
		'seo'     => array( get_post_meta( $p->ID, 'rank_math_title', true ), get_post_meta( $p->ID, 'rank_math_description', true ) ),
		'blokken' => $kort( get_field( 'blokken', $p->ID ) ),
	);
}

/**
 * Uitroltaak locaties-controle-v1: kandidaat-links nalopen (status en paginatitel) en per onderwerp
 * foto's op Wikimedia Commons zoeken. Leest alleen, de uitkomst staat in het uitrollog.
 */
function thema_locaties_controle() {
	$links = array(
		'bold' => array( 'https://boldrooftopbar.nl/', 'https://www.boldrooftopbar.nl/', 'https://www.thesocialhub.co/nl/maastricht/bold-rooftopbar/', 'https://www.visitmaastricht.com/nl/locaties/bold-rooftopbar' ),
		'msloep' => array( 'https://www.maastrichtsloep.nl/', 'https://maastrichtsloep.nl/', 'https://www.stiphout.nl/sloepverhuur' ),
		'mcitygame' => array( 'https://www.citygame.com/nl/maastricht', 'https://www.citygame.com/', 'https://www.escapetours.com/maastricht', 'https://www.qula.nl/pubtrail/maastricht' ),
		'stadsbrouwerij' => array( 'https://www.stadsbrouwerijmaastricht.nl/', 'https://stadsbrouwerijmaastricht.nl/' ),
		'bosch' => array( 'https://www.brouwerijbosch.nl/', 'https://brouwerijbosch.nl/' ),
		'underground' => array( 'https://www.maastrichtunderground.nl/', 'https://www.exploremaastricht.nl/maastricht-underground', 'https://www.exploremaastricht.nl/' ),
		'solex' => array( 'https://www.cyclecenter.nl/', 'https://www.solextoer.nl/', 'https://www.solexmaastricht.nl/' ),
		'nola' => array( 'https://www.barnola.nl/', 'https://barnola.nl/', 'https://www.barnolamaastricht.nl/', 'https://www.nolamaastricht.nl/' ),
		'somewhere' => array( 'https://www.somewhereinthemiddle.nl/', 'https://somewhereinthemiddle.nl/', 'https://www.somewhereinthemiddle.com/' ),
		'mrsmith' => array( 'https://www.mrsmithmaastricht.com/', 'https://mrsmithmaastricht.com/' ),
		'mooieboules' => array( 'https://www.mooieboules.nl/rotterdam', 'https://mooieboules.nl/rotterdam/', 'https://www.mooieboules.nl/' ),
		'dikkemic' => array( 'https://www.dikkemic.nl/', 'https://dikkemic.nl/' ),
		'duke' => array( 'https://www.dukeoftokyo.com/rotterdam', 'https://dukeoftokyo.com/rotterdam/', 'https://www.dukeoftokyo.com/' ),
		'madamchen' => array( 'https://www.madamchen.nl/', 'https://madamchen.nl/', 'https://www.madamchen.com/' ),
		'splash' => array( 'https://www.splashtours.nl/', 'https://splashtours.nl/' ),
		'bonton' => array( 'https://www.bontonrotterdam.nl/', 'https://www.bonton.club/rotterdam', 'https://www.bontonclub.nl/', 'https://bonton.nl/' ),
		'botanero' => array( 'https://www.botanero.nl/', 'https://botanero.nl/' ),
		'ox' => array( 'https://www.oxrotterdam.nl/', 'https://oxrotterdam.nl/', 'https://www.ox-rotterdam.nl/' ),
		'racesquare' => array( 'https://www.racesquare.nl/', 'https://racesquare.nl/', 'https://www.racesquare.com/' ),
		'axe' => array( 'https://www.realaxethrowing.nl/', 'https://realaxethrowing.com/', 'https://www.realaxethrowing.com/rotterdam' ),
		'euromast' => array( 'https://www.euromast.nl/abseilen/', 'https://euromast.nl/nl/abseilen', 'https://www.euromast.nl/' ),
		'lasergame' => array( 'https://www.lasergamerotterdam.nl/', 'https://lasergamerotterdam.nl/', 'https://www.lasergame-rotterdam.nl/' ),
		'aloha' => array( 'https://www.aloha.nl/', 'https://aloha.nl/', 'https://www.aloha-amsterdam.nl/' ),
		'catacombs' => array( 'https://www.amsterdamcatacombs.com/', 'https://amsterdamcatacombs.com/', 'https://www.amsterdamcatacombs.nl/' ),
		'upsidedown' => array( 'https://www.theupsidedownamsterdam.com/', 'https://upsidedownamsterdam.com/', 'https://www.upsidedown.amsterdam/', 'https://theupsidedown.amsterdam/' ),
		'upevents' => array( 'https://www.upevents.nl/', 'https://upevents.nl/', 'https://www.upevents.nl/amsterdam' ),
		'vr' => array( 'https://www.vrexperience.nl/', 'https://www.vrxp.nl/', 'https://www.thevrcave.nl/', 'https://www.vrbar.nl/' ),
		'coqtales' => array( 'https://www.coqtales.nl/', 'https://coqtales.nl/', 'https://www.coqtales.com/', 'https://www.coqtalesamsterdam.nl/' ),
		'flagship' => array( 'https://www.flagshipamsterdam.com/', 'https://flagshipamsterdam.com/' ),
		'mokumboot' => array( 'https://www.mokumboot.nl/', 'https://mokumboot.nl/' ),
		'axeeffect' => array( 'https://www.theaxeeffect.nl/', 'https://theaxeeffect.nl/', 'https://www.axeeffect.nl/', 'https://www.theaxeeffect.com/' ),
		'allout' => array( 'https://www.theallout.com/', 'https://theallout.com/activities/ballaoke' ),
		'weemoed' => array( 'https://www.paleisvanweemoed.nl/', 'https://paleisvanweemoed.nl/', 'https://www.paleisvanweemoed.com/' ),
		'tacos' => array( 'https://www.tacosandtequila.nl/', 'https://www.tacosentequila.nl/', 'https://tacosandtequila.amsterdam/', 'https://www.tacosandtequila.com/' ),
		'calleocho' => array( 'https://www.calleocho.nl/', 'https://calleocho.nl/', 'https://www.calleochoamsterdam.nl/' ),
		'secretbar' => array( 'https://secretbar.nl/', 'https://www.secretbar.nl/' ),
		'gerrit' => array( 'https://www.zoetelievegerrit.nl/', 'https://zoetelievegerrit.nl/', 'https://www.dezoetelievegerrit.nl/' ),
		'zelfvaren' => array( 'https://www.zelfvaren.nl/', 'https://zelfvaren.nl/', 'https://www.zelfvarendenbosch.nl/' ),
		'madhousedb' => array( 'https://www.themadhousedenbosch.nl/', 'https://www.themadhouse.nl/den-bosch', 'https://www.themadhouse.nl/' ),
		'excape' => array( 'https://www.excapexperience.nl/', 'https://www.excape.nl/', 'https://excapexperience.nl/', 'https://www.escapexperience.nl/' ),
		'tramkade' => array( 'https://www.bierbrouwerijdetramkade.nl/', 'https://www.stadsbrouwerijdenbosch.nl/', 'https://www.werkwarenhuis.nl/', 'https://www.tramkade.nl/', 'https://www.kaaihallen.nl/' ),
		'dewit' => array( 'https://www.jongensvandewit.nl/', 'https://jongensvandewit.nl/' ),
		'boozybingo' => array( 'https://www.boozybingo.nl/', 'https://boozybingo.nl/', 'https://www.boozybingoevents.nl/', 'https://boozybingoevents.nl/' ),
		'brabanthallen' => array( 'https://www.brabanthallen.nl/agenda', 'https://www.brabanthallen.nl/' ),
		'opkikkertje' => array( 'https://www.opkikkertje.nl/', 'https://opkikkertje.nl/', 'https://www.topkikkertje.nl/', 'https://www.cafehetopkikkertje.nl/' ),
		'barrique' => array( 'https://www.barrique.nl/', 'https://www.barriquedenbosch.nl/', 'https://barrique-denbosch.nl/', 'https://www.barrique-wijnen.nl/' ),
		'berghem' => array( 'https://www.circuitparkberghem.nl/', 'https://circuitparkberghem.nl/' ),
	);
	$uit = array( 'links' => array(), 'fotos' => array() );
	foreach ( $links as $sleutel => $lijst ) {
		foreach ( $lijst as $url ) {
			$r = wp_remote_get( $url, array( 'timeout' => 12, 'redirection' => 5, 'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_0) AppleWebKit/537.36 Chrome/124 Safari/537.36' ) );
			if ( is_wp_error( $r ) ) {
				$uit['links'][ $sleutel ][] = $url . ' => fout';
				continue;
			}
			$code  = wp_remote_retrieve_response_code( $r );
			$titel = preg_match( '#<title[^>]*>(.*?)</title>#is', wp_remote_retrieve_body( $r ), $m ) ? trim( html_entity_decode( wp_strip_all_tags( $m[1] ) ) ) : '';
			$uit['links'][ $sleutel ][] = $url . ' => ' . $code . ' ' . mb_substr( $titel, 0, 70 );
			if ( 200 === (int) $code ) {
				break;
			}
		}
	}
	$zoek = array(
		'Vrijthof Maastricht', 'Maas Maastricht Sint Servaasbrug', 'Stadsbrouwerij Maastricht', 'Brouwerij Bosch Maastricht', 'Sint-Pietersberg grotten', 'Solex',
		'Platielstraat Maastricht', 'Onze Lieve Vrouweplein Maastricht', 'Wyck Maastricht Rechtstraat', 'Sphinxkwartier Maastricht', 'Markt Maastricht',
		'Witte de Withstraat Rotterdam', 'Euromast', 'Splashtours Rotterdam', 'Erasmusbrug', 'Markthal Rotterdam', 'Oude Haven Rotterdam', 'Parkhaven Rotterdam', 'Hofplein Rotterdam fontein', 'Jeu de boules',
		'Leidseplein', 'Rembrandtplein', 'Amsterdam canal boat', 'De Pijp Amsterdam', 'Amsterdam grachten nacht', 'Karaoke',
		'Binnendieze', 'Tramkade silo', 'Korte Putstraat', 'Markt s-Hertogenbosch', 'Sint-Janskathedraal s-Hertogenbosch', 'Brabanthallen', 'Uilenburg s-Hertogenbosch', 'Kerkstraat s-Hertogenbosch',
	);
	foreach ( $zoek as $q ) {
		$api = add_query_arg( array(
			'action' => 'query', 'format' => 'json', 'generator' => 'search', 'gsrnamespace' => 6, 'gsrlimit' => 6, 'gsrsearch' => $q . ' filetype:bitmap',
			'prop' => 'imageinfo', 'iiprop' => 'size|extmetadata', 'iiextmetadatafilter' => 'LicenseShortName|Artist',
		), 'https://commons.wikimedia.org/w/api.php' );
		$r = wp_remote_get( $api, array( 'timeout' => 15, 'user-agent' => 'LekkerGeelBusje/1.0 (staging; info@lekkergeelbusje.nl)' ) );
		$d = is_wp_error( $r ) ? array() : json_decode( wp_remote_retrieve_body( $r ), true );
		foreach ( (array) ( $d['query']['pages'] ?? array() ) as $p ) {
			$ii = $p['imageinfo'][0] ?? array();
			$uit['fotos'][ $q ][] = $p['title'] . ' | ' . ( $ii['width'] ?? 0 ) . 'x' . ( $ii['height'] ?? 0 ) . ' | ' . ( $ii['extmetadata']['LicenseShortName']['value'] ?? '' );
		}
	}
	return $uit;
}
