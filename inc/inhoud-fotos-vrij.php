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
function thema_foto_kandidaten() {
	$map = wp_upload_dir();
	$dir = trailingslashit( $map['basedir'] ) . 'kandidaten';
	wp_mkdir_p( $dir );
	$uit = array();
	foreach ( thema_vrije_foto_zoektermen() as $sleutel => $q ) {
		$r = wp_remote_get( add_query_arg( array(
			'q' => $q, 'license_type' => 'commercial,modification', 'page_size' => 20, 'mature' => 'false', 'aspect_ratio' => 'wide,square',
		), 'https://api.openverse.org/v1/images/' ), array( 'timeout' => 25, 'user-agent' => 'LekkerGeelBusje/1.0 (staging; info@lekkergeelbusje.nl)' ) );
		$d = is_wp_error( $r ) ? array() : json_decode( wp_remote_retrieve_body( $r ), true );
		$n = 0;
		foreach ( (array) ( $d['results'] ?? array() ) as $res ) {
			if ( $n >= 5 || ( $res['width'] ?? 0 ) < 1200 ) {
				continue;
			}
			$thumb = $res['thumbnail'] ?? '';
			$t     = $thumb ? wp_remote_get( $thumb, array( 'timeout' => 25 ) ) : null;
			if ( ! $t || is_wp_error( $t ) || 200 !== (int) wp_remote_retrieve_response_code( $t ) ) {
				continue;
			}
			file_put_contents( $dir . '/' . $sleutel . '-' . $n . '.jpg', wp_remote_retrieve_body( $t ) );
			$uit[ $sleutel ][ $n ] = array( $res['id'], $res['source'] ?? '', $res['license'] . ' ' . $res['license_version'], mb_substr( (string) ( $res['creator'] ?? '' ), 0, 40 ), mb_substr( (string) ( $res['title'] ?? '' ), 0, 60 ), $res['width'] . 'x' . $res['height'] );
			$n++;
		}
		if ( ! $n ) {
			$uit[ $sleutel ] = 'geen: ' . ( is_wp_error( $r ) ? $r->get_error_message() : wp_remote_retrieve_response_code( $r ) );
		}
		sleep( 2 );
	}
	return $uit;
}
