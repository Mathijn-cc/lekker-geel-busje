<?php
/**
 * Koppeling tussen het offerteblok (vragen in een zin) en het Gravity Forms offerteformulier.
 *
 * Het blok stuurt datum, personen, van, naar en gelegenheid mee in de url (feest in plaats van gelegenheid, want dat is de naam van het berichttype). Gravity Forms vult
 * velden met "Dynamisch vullen" aan met de parameternaam. Hier zetten we de waarden om naar
 * het formaat dat het formulier verwacht.
 */

/* Datum: het blok geeft jjjj-mm-dd, het formulier verwacht dd-mm-jjjj. */
add_filter( 'gform_field_value_datum', function ( $waarde ) {
	$waarde = sanitize_text_field( wp_unslash( $_GET['datum'] ?? $waarde ) ); // phpcs:ignore
	if ( preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $waarde, $m ) ) {
		return $m[3] . '-' . $m[2] . '-' . $m[1];
	}
	return $waarde;
} );

/* Personen: een getal. Oude links met een bereik (zoals 39 tot 53) geven de bovengrens. */
add_filter( 'gform_field_value_personen', function ( $waarde ) {
	$waarde = sanitize_text_field( wp_unslash( $_GET['personen'] ?? $waarde ) ); // phpcs:ignore
	if ( preg_match( '/meer dan/i', $waarde ) ) {
		return '';
	}
	preg_match_all( '/\d+/', $waarde, $m );
	return $m[0] ? (string) max( array_map( 'intval', $m[0] ) ) : '';
} );

/**
 * Gelegenheid uit het blok vertalen naar een keuze in het formulier.
 */
function thema_offerte_gelegenheid() {
	$tekst = sanitize_text_field( wp_unslash( $_GET['feest'] ?? '' ) ); // phpcs:ignore
	if ( '' === $tekst ) {
		return array( '', '' );
	}
	$kaart = array(
		'vrijgezel' => 'Vrijgezellenfeest',
		'bedrijf'   => 'Bedrijfsuitje / Personeelsfeest',
		'personeel' => 'Bedrijfsuitje / Personeelsfeest',
		'festival'  => 'Festival bezoeken',
	);
	foreach ( $kaart as $zoek => $keuze ) {
		if ( false !== stripos( $tekst, $zoek ) ) {
			return array( $keuze, '' );
		}
	}
	return array( 'Anders', $tekst );
}
add_filter( 'gform_field_value_feest', fn() => thema_offerte_gelegenheid()[0] );
add_filter( 'gform_field_value_uitje', fn() => thema_offerte_gelegenheid()[1] );

/* Grote groep: zet het in de wensen, zodat het niet verloren gaat. */
add_filter( 'gform_field_value_wensen', function ( $waarde ) {
	$personen = sanitize_text_field( wp_unslash( $_GET['personen'] ?? '' ) ); // phpcs:ignore
	return preg_match( '/meer dan/i', $personen ) ? 'We zijn met ' . $personen . ' personen.' : $waarde;
} );
