<?php
/**
 * Foutmeldingen van Gravity Forms altijd in het Nederlands.
 *
 * Niet elke melding van Gravity Forms (of van een add-on) heeft een Nederlandse vertaling.
 * Dit vangt de Engelse meldingen per veld op en zet er een Nederlandse tekst voor in de plaats.
 * Draait als laatste, zodat eigen meldingen uit het thema (offerteformulier, adreszoeker) blijven staan.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Engelse melding (of een herkenbaar stuk ervan) => Nederlandse melding.
 */
function thema_formulier_meldingen() {
	return apply_filters( 'thema_formulier_meldingen', array(
		'valid email'               => 'Vul een geldig e-mailadres in, bijvoorbeeld naam@voorbeeld.nl.',
		'email address entered is'  => 'Vul een geldig e-mailadres in, bijvoorbeeld naam@voorbeeld.nl.',
		'emails do not match'       => 'De e-mailadressen zijn niet gelijk.',
		'your emails do not match'  => 'De e-mailadressen zijn niet gelijk.',
		'valid phone'               => 'Vul een geldig telefoonnummer in.',
		'phone format'              => 'Vul een geldig telefoonnummer in.',
		'valid url'                 => 'Vul een geldig webadres in, bijvoorbeeld https://voorbeeld.nl.',
		'valid number'              => 'Vul een geldig getal in.',
		'valid date'                => 'Vul een geldige datum in.',
		'this field is required'    => 'Dit veld is verplicht.',
		'exceeds the maximum'       => 'Je tekst is te lang.',
		'only one entry'            => 'Deze waarde is al eerder ingevuld.',
		'already been taken'        => 'Deze waarde is al eerder ingevuld.',
		'please enter a value'      => 'Vul hier iets in.',
		'invalid selection'         => 'Kies een van de opties.',
	) );
}

add_filter( 'gform_field_validation', function ( $resultaat, $waarde, $form, $veld ) {
	if ( ! empty( $resultaat['is_valid'] ) || empty( $resultaat['message'] ) ) {
		return $resultaat;
	}
	$melding = strtolower( wp_strip_all_tags( (string) $resultaat['message'] ) );
	foreach ( thema_formulier_meldingen() as $engels => $nederlands ) {
		if ( false !== strpos( $melding, $engels ) ) {
			$resultaat['message'] = $nederlands;
			break;
		}
	}
	return $resultaat;
}, 999, 4 );

/* Kop boven de lijst met fouten, voor het geval die nog in het Engels staat. */
add_filter( 'gform_validation_message', function ( $html ) {
	if ( false !== stripos( $html, 'There was a problem with your submission' ) ) {
		$html = str_ireplace(
			array( 'There was a problem with your submission.', 'Please review the fields below.', 'Errors have been highlighted below.' ),
			array( 'Er was een probleem met je inzending.', 'Controleer de onderstaande velden.', 'Controleer de gemarkeerde velden.' ),
			$html
		);
	}
	return $html;
}, 999 );
