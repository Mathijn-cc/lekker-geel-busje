<?php
/**
 * Vrije foto's (Openverse: Commons, Flickr e.a., alleen licenties voor commercieel gebruik met bewerking)
 * voor de activiteitenkaarten die nog een tijdelijke busfoto hebben.
 */

defined( 'ABSPATH' ) || exit;

function thema_vrije_foto_zoektermen() {
	return array(
		'jeu-de-boules'   => 'petanque boules game',
		'simracen'        => 'racing simulator',
		'bijlwerpen'      => 'axe throwing',
		'bowlen'          => 'bowling alley',
		'escape-room'     => 'escape room',
		'illusie'         => 'optical illusion museum',
		'paintball'       => 'paintball team',
		'karaoke'         => 'karaoke singing friends',
		'cabaret'         => 'cabaret show stage',
		'showdans'        => 'dance show stage lights',
		'darten'          => 'darts bar',
		'biltart'         => 'pool billiards bar',
		'speciaalbier'    => 'craft beer tasting flight',
		'bingo'           => 'bingo night',
		'diner-groep'     => 'friends dinner restaurant table',
		'whisky'          => 'whisky tasting glasses',
		'karten-buiten'   => 'go kart racing track',
		'karten-binnen'   => 'indoor karting',
		'lasergame'       => 'laser tag',
		'cocktails'       => 'cocktail making bartender',
		'vr'              => 'virtual reality headset',
		'concert'         => 'concert crowd stage',
		'bordspellen'     => 'board games friends',
		'grachtenboot'    => 'Amsterdam canal boat',
	);
}

/**
 * Uitroltaak foto-kandidaten-v1: per zoekterm vijf kandidaten als kleine voorbeeldfoto in uploads/kandidaten,
 * met maker en licentie in het log. Plaatst niets op pagina's.
 */
function thema_foto_kandidaten( $termen = null ) {
	$map = wp_upload_dir();
	$dir = trailingslashit( $map['basedir'] ) . 'kandidaten';
	wp_mkdir_p( $dir );
	$uit = array();
	foreach ( ( $termen ?: thema_vrije_foto_zoektermen() ) as $sleutel => $q ) {
		$r = wp_remote_get( add_query_arg( array(
			'q' => $q, 'license_type' => 'commercial,modification', 'page_size' => 20, 'mature' => 'false',
		), 'https://api.openverse.org/v1/images/' ), array( 'timeout' => 25, 'user-agent' => 'LekkerGeelBusje/1.0 (staging; info@lekkergeelbusje.nl)' ) );
		$d = is_wp_error( $r ) ? array() : json_decode( wp_remote_retrieve_body( $r ), true );
		$n     = 0;
		$fouten = array();
		foreach ( (array) ( $d['results'] ?? array() ) as $res ) {
			if ( $n >= 5 ) {
				break;
			}
			if ( ! empty( $res['width'] ) && $res['width'] < 1000 ) {
				$fouten[] = 'klein';
				continue;
			}
			$bestand = $dir . '/' . $sleutel . '-' . $n . '.jpg';
			$ok      = false;
			foreach ( array( $res['thumbnail'] ?? '', $res['url'] ?? '' ) as $bron ) {
				if ( ! $bron ) {
					continue;
				}
				$t = wp_remote_get( $bron, array( 'timeout' => 30, 'user-agent' => 'LekkerGeelBusje/1.0 (staging; info@lekkergeelbusje.nl)' ) );
				if ( is_wp_error( $t ) || 200 !== (int) wp_remote_retrieve_response_code( $t ) ) {
					$fouten[] = is_wp_error( $t ) ? 'fout' : wp_remote_retrieve_response_code( $t );
					continue;
				}
				file_put_contents( $bestand, wp_remote_retrieve_body( $t ) );
				$ed = wp_get_image_editor( $bestand );
				if ( is_wp_error( $ed ) ) {
					$fouten[] = 'geen beeld';
					continue;
				}
				$ed->resize( 600, 600, false );
				$ed->save( $bestand, 'image/jpeg' );
				$ok = true;
				break;
			}
			if ( ! $ok ) {
				continue;
			}
			$uit[ $sleutel ][ $n ] = array( $res['id'], $res['source'] ?? '', $res['license'] . ' ' . $res['license_version'], mb_substr( (string) ( $res['creator'] ?? '' ), 0, 40 ), mb_substr( (string) ( $res['title'] ?? '' ), 0, 60 ), $res['width'] . 'x' . $res['height'] );
			$n++;
		}
		if ( ! $n ) {
			$uit[ $sleutel ] = 'geen: ' . ( is_wp_error( $r ) ? $r->get_error_message() : wp_remote_retrieve_response_code( $r ) ) . ' resultaten ' . count( (array) ( $d['results'] ?? array() ) ) . ' ' . implode( ',', array_slice( $fouten, 0, 6 ) ) . ' ' . substr( wp_remote_retrieve_body( $r ), 0, 120 );
		}
		sleep( 2 );
	}
	return $uit;
}

/**
 * Gekozen vrije foto's per kaart: plaatspagina, titel van de kaart, Openverse-id, bestandsnaam, alt-tekst.
 */
function thema_vrije_foto_keuze() {
	return array(
		array( 'rotterdam', 'Mooie Boules', '040c9814-3cab-45da-b81e-3beab534e9fa', 'jeu-de-boules-ballen', 'Jeu de boules-ballen op het zand' ),
		array( 'breda', 'The Boules Club', 'ba57dd93-940a-4980-80e4-f163902a3296', 'jeu-de-boules-spel', 'Boules en het butje op een baan' ),
		array( 'rotterdam', 'Racesquare', '6ba64e64-7400-4b3a-98ef-37a1c283bf68', 'racesimulator-groep', 'Racesimulators voor een groep' ),
		array( 'rotterdam', 'Real Axe Throwing', 'ebed0c05-4113-41a9-85e8-453df5f805d1', 'bijlwerpen-doel', 'Een bijl vliegt naar het doel' ),
		array( 'amsterdam', 'Bijlwerpen bij The Axe Effect', 'da0224c3-17c6-46e7-abe2-c2e92f7b296a', 'bijlwerpen-uitje', 'Bijlwerpen op een houten doel' ),
		array( 'amsterdam', 'Aloha', '95624177-dda7-4656-95df-de18291e6eac', 'bowlingbaan-neon', 'Bowlingbanen met kleurrijke verlichting' ),
		array( 'breda', 'Vier Hoog', 'c2adf557-c48a-4ee5-afb5-ac7fa37a7bdf', 'bowlen-met-de-groep', 'Bowlen op een baan met blauw licht' ),
		array( 'amsterdam', 'Amsterdam Catacombs', '351a19e5-7f4c-4c3b-874b-48c0c01dc8ce', 'escape-room-donker', 'Spelers zoeken naar aanwijzingen in een donkere escape room' ),
		array( 'amsterdam', 'The Upside Down', '6193a7d5-419d-456c-9d34-929a762c05ed', 'lichtkunst-cirkels', 'Lichtgevende cirkels als installatie' ),
		array( 'amsterdam', 'UP Events', 'de781378-fb87-4284-8d1a-8e6930b1a9bf', 'paintball-teams', 'Teams in overall op een paintballveld' ),
		array( 'amsterdam', 'Karaoke en games bij The All Out', 'ffeb4939-0a50-4e1b-b6fd-7cabbfc2ddfb', 'karaoke-microfoon', 'Een microfoon klaar voor karaoke' ),
		array( 'amsterdam', 'Coqtales', 'bb951d74-bb61-4e2b-9cd6-9fc11d7bce0e', 'show-podium-licht', 'Dansers op een podium met paars licht' ),
		array( 'amsterdam', 'Paleis van de Weemoed', 'e09308bc-8d77-43e1-85aa-136425c5ba4c', 'cabaret-show', 'Een artiest op het podium tijdens een cabaretshow' ),
		array( 'den-bosch', 'The Madhouse', '718f2381-178d-41cd-a0d1-6918b6497822', 'darten-in-de-bar', 'Dartborden in een bar' ),
		array( 'den-bosch', 'Speciaalbier op de Tramkade', '9be1a1c8-123a-40d3-871a-d4703395fb99', 'speciaalbier-proeverij', 'Een plank met proefglazen speciaalbier' ),
		array( 'den-bosch', 'Boozy Bingo', '222adfe1-d1f2-493e-9961-34f98c793a67', 'bingo-avond', 'Bingokaarten op tafel tijdens een bingoavond' ),
		array( 'den-bosch', '\'t Opkikkertje', 'f9f0cc7e-35be-44fb-ab33-9b399cf4e664', 'gedekte-tafel-diner', 'Een gedekte tafel voor het diner' ),
		array( 'den-bosch', 'Whiskyproeverij bij Barrique', '4284cb53-b96a-4b05-a02a-a71a26f66b0a', 'whiskyproeverij-glazen', 'Proefglazen whisky op een rij' ),
		array( 'den-bosch', 'Circuit Park Berghem', 'ea50817d-dea7-4c47-a1d3-b14ea7ad74be', 'kartbaan-buiten', 'Een kartbaan in de buitenlucht' ),
		array( 'breda', 'BattleKart Breda', '6ccdd8d0-5a1f-4640-851a-e2e66494be7c', 'indoor-karten', 'Een verlichte indoor kartbaan' ),
		array( 'breda', 'VR World Breda', '2323c680-738c-4ea9-a0c5-68c369136e0a', 'vr-bril', 'Een virtual reality-bril van dichtbij' ),
		array( 'breda', 'Concert in Mezz', '6dc86b99-fa34-4014-8cea-7ad46e9495d9', 'concert-publiek', 'Publiek met de handen in de lucht bij een concert' ),
		array( 'tilburg', 'The Madhouse', '3e17819e-98ff-493d-803c-fa156674dcaf', 'poolbiljart-bar', 'Een pooltafel met ballen in een bar' ),
		array( 'tilburg', 'De Voltage', 'e3865aaf-2676-43d7-ba41-3d00f6e91450', 'karten-indoor-baan', 'Karts op een indoor baan' ),
		array( 'tilburg', 'NondeJeu', '88fdb24a-62bc-4e41-857c-9e39bdb884fa', 'spelkamer-uitdaging', 'Een sfeervolle spelkamer vol aanwijzingen' ),
		array( 'breda', 'Cocktailworkshop', '15da2aab-1aff-4f48-a2f8-45dbd101db9b', 'cocktail-uit-de-shaker', 'Een cocktail wordt uit de shaker in twee glazen geschonken' ),
	);
}

/**
 * Een Openverse-foto in de mediabibliotheek, met maker en licentie in het bijschrift. Bestaat hij al, dan die.
 */
function thema_openverse_beeld( $ov_id, $naam, $alt ) {
	$al = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_key' => 'thema_openverse_id', 'meta_value' => $ov_id ) );
	if ( $al ) {
		return (int) $al[0];
	}
	$r = wp_remote_get( 'https://api.openverse.org/v1/images/' . rawurlencode( $ov_id ) . '/', array( 'timeout' => 25, 'user-agent' => 'LekkerGeelBusje/1.0 (staging; info@lekkergeelbusje.nl)' ) );
	$d = is_wp_error( $r ) ? array() : json_decode( wp_remote_retrieve_body( $r ), true );
	if ( empty( $d['url'] ) ) {
		return 0;
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$tmp = download_url( $d['url'], 60 );
	if ( is_wp_error( $tmp ) ) {
		return 0;
	}
	$id = media_handle_sideload( array( 'name' => $naam . '.jpg', 'tmp_name' => $tmp ), 0, $alt );
	if ( is_wp_error( $id ) ) {
		@unlink( $tmp ); // phpcs:ignore
		return 0;
	}
	$bron    = array( 'flickr' => 'Flickr', 'wikimedia' => 'Wikimedia Commons' );
	$maker   = trim( (string) ( $d['creator'] ?? '' ) ) ?: 'Onbekend';
	$licentie = strtoupper( 'cc0' === $d['license'] ? 'CC0' : ( 'pdm' === $d['license'] ? 'Publiek domein' : 'CC ' . $d['license'] . ' ' . $d['license_version'] ) );
	$credit  = 'Foto: <a href="' . esc_url( $d['foreign_landing_url'] ?? '' ) . '" target="_blank" rel="noopener">' . esc_html( $maker ) . '</a> via ' . esc_html( $bron[ $d['source'] ?? '' ] ?? ucfirst( (string) ( $d['source'] ?? 'Openverse' ) ) );
	$credit .= ', ' . ( ! empty( $d['license_url'] ) ? '<a href="' . esc_url( $d['license_url'] ) . '" target="_blank" rel="noopener">' . esc_html( $licentie ) . '</a>' : esc_html( $licentie ) );
	wp_update_post( array( 'ID' => $id, 'post_excerpt' => $credit, 'post_title' => $alt ) );
	update_post_meta( $id, '_wp_attachment_image_alt', $alt );
	update_post_meta( $id, 'thema_openverse_id', $ov_id );
	return (int) $id;
}

/**
 * Uitroltaak vrije-fotos-v1: de gekozen foto's importeren en op de kaarten zetten in plaats van de tijdelijke busfoto.
 * De koppeling kaart en foto staat ook in de optie thema_vrije_fotos, zodat opnieuw plaatsen ze houdt.
 */
function thema_vrije_fotos_plaatsen() {
	if ( function_exists( 'set_time_limit' ) ) {
		set_time_limit( 0 );
	}
	$koppeling = (array) get_option( 'thema_vrije_fotos', array() );
	$uit       = array();
	foreach ( thema_vrije_foto_keuze() as $k ) {
		list( $slug, $titel, $ov, $naam, $alt ) = $k;
		$beeld = thema_openverse_beeld( $ov, $naam, $alt );
		if ( ! $beeld ) {
			$uit[ $slug . ': ' . $titel ] = 'geen foto';
			continue;
		}
		$koppeling[ $slug . '|' . $titel ] = $beeld;
		$p = get_page_by_path( 'locaties/' . $slug );
		if ( ! $p ) {
			continue;
		}
		$blokken = get_field( 'field_blokken', $p->ID, false );
		$gezet   = 0;
		foreach ( (array) $blokken as $i => $b ) {
			if ( 'tips' !== ( $b['acf_fc_layout'] ?? '' ) ) {
				continue;
			}
			foreach ( $b as $veld => $rijen ) {
				if ( ! is_array( $rijen ) ) {
					continue;
				}
				foreach ( $rijen as $j => $rij ) {
					if ( is_array( $rij ) && ( $rij['field_tips_item_titel'] ?? '' ) === $titel ) {
						$blokken[ $i ][ $veld ][ $j ]['field_tips_item_beeld']     = $beeld;
						$blokken[ $i ][ $veld ][ $j ]['field_tips_item_tijdelijk'] = 0;
						$gezet++;
					}
				}
			}
		}
		if ( $gezet ) {
			update_field( 'field_blokken', $blokken, $p->ID );
		}
		$uit[ $slug . ': ' . $titel ] = $gezet ? $beeld : 'kaart niet gevonden';
	}
	update_option( 'thema_vrije_fotos', $koppeling, false );
	return $uit;
}

/**
 * Uitroltaak foto-kandidaten-shaker-v1: kandidaten voor de cocktailworkshop in Breda.
 */
function thema_foto_kandidaten_shaker() {
	return thema_foto_kandidaten( array( 'shaker' => 'cocktail shaker', 'shaker-bar' => 'bartender shaker' ) );
}

/**
 * Uitroltaak foto-kandidaten-sloep-v1: kandidaten voor varen met vrienden op een sloep.
 */
function thema_foto_kandidaten_sloep() {
	return thema_foto_kandidaten( array( 'sloep-vrienden' => 'friends boat canal', 'sloep-feest' => 'boat party friends', 'sloep-elektrisch' => 'electric boat canal people', 'sloep-nl' => 'sloep' ) );
}

/**
 * Uitroltaak foto-kandidaten-sloep-v2: tweede ronde voor varen met vrienden.
 */
function thema_foto_kandidaten_sloep2() {
	return thema_foto_kandidaten( array(
		'boot-vrienden'  => 'friends on a boat drinks',
		'boot-gracht'    => 'Utrecht canal boat people',
		'boot-zomer'     => 'boating friends summer',
		'boot-amsterdam' => 'Amsterdam canal small boat people',
		'boot-groep'     => 'group of people small motor boat river',
	) );
}
