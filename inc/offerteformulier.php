<?php
/**
 * Offerteformulier (Gravity Forms): vooraf invullen vanuit de offertezin,
 * een telefoonveld dat ook buitenlandse nummers accepteert, en datumregels.
 *
 * Formulier- en veld-ID's staan op een plek, in thema_offerte_velden().
 * Wijkt de ID op productie af na de migratie? Pas ze daar aan, of gebruik
 * het filter 'thema_offerte_velden'.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Formulier- en veld-ID's van het offerteformulier.
 */
function thema_offerte_velden() {
	return apply_filters( 'thema_offerte_velden', array(
		'formulier'   => 3,
		'doel'        => 2,  // Radio: wat is het doel van jullie uitje?
		'doel_anders' => 3,  // Tekst: wat voor uitje dan precies?
		'personen'    => 4,  // Getal: met hoeveel personen?
		'datum'       => 6,  // Datum: vertrekdatum
		'plaats'      => 72, // Tekst: vertrekplaats
		'bestemming'  => 63, // Tekst: bestemming
		'retourdatum' => 10, // Datum: retourdatum
		'telefoon'    => 71, // Tekst of telefoon: telefoonnummer
	) );
}

/**
 * Zoek een veld op ID.
 */
function thema_offerte_veld( $form, $id ) {
	foreach ( $form['fields'] as $veld ) {
		if ( (int) $veld->id === (int) $id ) {
			return $veld;
		}
	}
	return null;
}

/**
 * PHP-datumnotatie bij de datumnotatie van een Gravity Forms datumveld.
 */
function thema_offerte_datumnotatie( $veld ) {
	$notaties = array(
		'mdy'       => 'm/d/Y',
		'dmy'       => 'd/m/Y',
		'dmy_dash'  => 'd-m-Y',
		'dmy_dot'   => 'd.m.Y',
		'ymd_slash' => 'Y/m/d',
		'ymd_dash'  => 'Y-m-d',
		'ymd_dot'   => 'Y.m.d',
	);
	$soort = $veld && $veld->dateFormat ? $veld->dateFormat : 'mdy';
	return $notaties[ $soort ] ?? 'd-m-Y';
}

/**
 * Een ingevulde datum uit een datumveld als DateTimeImmutable, of null.
 */
function thema_offerte_lees_datum( $waarde, $veld ) {
	$waarde = trim( (string) $waarde );
	if ( '' === $waarde ) {
		return null;
	}
	$datum = DateTimeImmutable::createFromFormat( '!' . thema_offerte_datumnotatie( $veld ), $waarde, wp_timezone() );
	return $datum ?: null;
}

/**
 * Vandaag, om middernacht, in de tijdzone van de site.
 */
function thema_offerte_vandaag() {
	return new DateTimeImmutable( 'today', wp_timezone() );
}

/* --------------------------------------------------------------------------
 * K1: vooraf invullen met wat de offertezin meestuurt.
 * De zin stuurt datum (jjjj-mm-dd), personen, van, naar en uitje.
 * Let op: niet 'gelegenheid', dat is de queryvariabele van het posttype
 * en geeft een 404.
 * ----------------------------------------------------------------------- */

add_filter( 'gform_pre_render', 'thema_offerte_vooraf_invullen' );

function thema_offerte_vooraf_invullen( $form ) {
	$v = thema_offerte_velden();
	if ( (int) $form['id'] !== (int) $v['formulier'] || rgpost( 'is_submit_' . $form['id'] ) ) {
		return $form;
	}

	// Gelegenheid: kies de passende tegel, anders "Anders" met de naam erbij.
	$gelegenheid = sanitize_text_field( wp_unslash( rgget( 'uitje' ) ) );
	$doel        = thema_offerte_veld( $form, $v['doel'] );
	if ( '' !== $gelegenheid && $doel && is_array( $doel->choices ) ) {
		$gekozen = null;
		$anders  = null;
		foreach ( $doel->choices as $i => $keuze ) {
			$tekst = (string) $keuze['text'];
			if ( 0 === stripos( $tekst, 'Anders' ) || 0 === stripos( (string) $keuze['value'], 'Anders' ) ) {
				$anders = $i;
				continue;
			}
			$eerste = strtok( $tekst, ' /' );
			if ( false !== stripos( $tekst, $gelegenheid ) || ( $eerste && false !== stripos( $gelegenheid, $eerste ) ) ) {
				$gekozen = $i;
				break;
			}
		}
		if ( null === $gekozen && null !== $anders ) {
			$gekozen = $anders;
			$tekstveld = thema_offerte_veld( $form, $v['doel_anders'] );
			if ( $tekstveld ) {
				$tekstveld->defaultValue = mb_substr( $gelegenheid, 0, 100 );
			}
		}
		if ( null !== $gekozen ) {
			$keuzes = $doel->choices;
			foreach ( $keuzes as $i => $keuze ) {
				$keuzes[ $i ]['isSelected'] = ( $i === $gekozen );
			}
			$doel->choices = $keuzes;
		}
	}

	// Aantal personen: alleen een heel getal binnen het bereik van het veld.
	$personen = absint( rgget( 'personen' ) );
	$veld     = thema_offerte_veld( $form, $v['personen'] );
	if ( $personen > 0 && $personen <= 9999 && $veld ) {
		$veld->defaultValue = (string) $personen;
	}

	// Datum: van jjjj-mm-dd naar de notatie van het veld, niet in het verleden.
	// De retourdatum krijgt dezelfde datum als startpunt.
	$datum = DateTimeImmutable::createFromFormat( '!Y-m-d', (string) rgget( 'datum' ), wp_timezone() );
	if ( $datum && $datum >= thema_offerte_vandaag() ) {
		foreach ( array( $v['datum'], $v['retourdatum'] ) as $id ) {
			$veld = thema_offerte_veld( $form, $id );
			if ( $veld ) {
				$veld->defaultValue = $datum->format( thema_offerte_datumnotatie( $veld ) );
			}
		}
	}

	// Plaats en bestemming.
	foreach ( array( 'van' => $v['plaats'], 'naar' => $v['bestemming'] ) as $parameter => $id ) {
		$waarde = sanitize_text_field( wp_unslash( rgget( $parameter ) ) );
		$veld   = thema_offerte_veld( $form, $id );
		if ( '' !== $waarde && $veld ) {
			$veld->defaultValue = mb_substr( $waarde, 0, 100 );
		}
	}

	return $form;
}

/* --------------------------------------------------------------------------
 * K3: telefoonveld zonder masker van precies tien cijfers.
 * Op alle momenten dat Gravity Forms het formulier opbouwt, ook bij validatie,
 * zodat het masker ook op de server niet meer geldt.
 * ----------------------------------------------------------------------- */

foreach ( array( 'gform_pre_render', 'gform_pre_validation', 'gform_pre_submission_filter', 'gform_admin_pre_render' ) as $haak ) {
	add_filter( $haak, 'thema_offerte_telefoonveld' );
}

function thema_offerte_telefoonveld( $form ) {
	$v = thema_offerte_velden();
	if ( (int) $form['id'] !== (int) $v['formulier'] ) {
		return $form;
	}
	$veld = thema_offerte_veld( $form, $v['telefoon'] );
	if ( ! $veld ) {
		return $form;
	}
	$veld->inputMask         = false;
	$veld->inputMaskValue    = '';
	$veld->inputMaskIsCustom = false;
	if ( 'phone' === $veld->type ) {
		$veld->phoneFormat = 'international';
	}
	$veld->placeholder = 'bijv. 06 12345678';
	return $form;
}

// Telefoontoetsenbord op mobiel en invullen door de browser.
add_filter( 'gform_field_content', function ( $html, $veld ) {
	$v = thema_offerte_velden();
	if ( is_admin() || (int) $veld->formId !== (int) $v['formulier'] || (int) $veld->id !== (int) $v['telefoon'] ) {
		return $html;
	}
	$naam = preg_quote( 'input_' . $veld->id, '/' );
	return preg_replace_callback(
		'/<input\b[^>]*\bname=([\'"])' . $naam . '\1[^>]*>/',
		function ( $m ) {
			$tag = preg_replace( '/\btype=([\'"])text\1/', 'type="tel"', $m[0] );
			$tag = preg_replace( '/\s(autocomplete|inputmode)=([\'"])[^\'"]*\2/', '', $tag );
			return preg_replace( '/\s*\/?>$/', ' autocomplete="tel" inputmode="tel" />', $tag );
		},
		$html
	);
}, 10, 2 );

// Controle op de server: cijfers met eventueel +, spaties, streepjes of haakjes.
add_filter( 'gform_field_validation', function ( $resultaat, $waarde, $form, $veld ) {
	$v = thema_offerte_velden();
	if ( (int) $form['id'] !== (int) $v['formulier'] || (int) $veld->id !== (int) $v['telefoon'] || ! $resultaat['is_valid'] ) {
		return $resultaat;
	}
	$waarde = trim( (string) $waarde );
	if ( '' === $waarde ) {
		return $resultaat;
	}
	$cijfers = preg_replace( '/\D/', '', $waarde );
	if ( ! preg_match( '/^\+?[0-9\s().\/-]+$/', $waarde ) || strlen( $cijfers ) < 9 || strlen( $cijfers ) > 15 ) {
		$resultaat['is_valid'] = false;
		$resultaat['message']  = 'Dit nummer klopt nog niet helemaal. Gebruik cijfers, eventueel met + en landcode, zoals 06 12345678 of +32 471 12 34 56.';
	}
	return $resultaat;
}, 10, 4 );

/* --------------------------------------------------------------------------
 * K5: geen datum in het verleden en geen terugreis voor de heenreis.
 * ----------------------------------------------------------------------- */

// Hint bij de retourdatum.
foreach ( array( 'gform_pre_render', 'gform_pre_validation' ) as $haak ) {
	add_filter( $haak, function ( $form ) {
		$v = thema_offerte_velden();
		if ( (int) $form['id'] !== (int) $v['formulier'] ) {
			return $form;
		}
		$veld = thema_offerte_veld( $form, $v['retourdatum'] );
		$hint = 'Na middernacht terug? Kies dan de volgende dag.';
		if ( $veld && false === strpos( (string) $veld->description, $hint ) ) {
			$veld->description = trim( $veld->description . ' ' . $hint );
		}
		return $form;
	} );
}

add_filter( 'gform_field_validation', function ( $resultaat, $waarde, $form, $veld ) {
	$v = thema_offerte_velden();
	if ( (int) $form['id'] !== (int) $v['formulier'] || ! $resultaat['is_valid'] ) {
		return $resultaat;
	}
	$id = (int) $veld->id;
	if ( $id !== (int) $v['datum'] && $id !== (int) $v['retourdatum'] ) {
		return $resultaat;
	}
	$datum = thema_offerte_lees_datum( is_array( $waarde ) ? implode( '-', $waarde ) : $waarde, $veld );
	if ( ! $datum ) {
		return $resultaat;
	}
	if ( $datum < thema_offerte_vandaag() ) {
		$resultaat['is_valid'] = false;
		$resultaat['message']  = 'Deze datum ligt in het verleden. Kies een datum vanaf vandaag.';
		return $resultaat;
	}
	if ( $id === (int) $v['retourdatum'] ) {
		$vertrekveld = thema_offerte_veld( $form, $v['datum'] );
		$vertrek     = thema_offerte_lees_datum( rgpost( 'input_' . $v['datum'] ), $vertrekveld );
		if ( $vertrek && $datum < $vertrek ) {
			$resultaat['is_valid'] = false;
			$resultaat['message']  = 'De terugreis kan niet voor de heenreis zijn. Na middernacht terug? Kies dan de volgende dag.';
		}
	}
	return $resultaat;
}, 10, 4 );

// Is de retourdatum nog leeg, dan neemt hij de vertrekdatum over. Werkt los van
// welke datumkiezer Gravity Forms gebruikt: het kopieert alleen de tekstwaarde.
// De regels zelf (niet in het verleden, retour niet voor vertrek) bewaakt de server.
add_action( 'gform_register_init_scripts', function ( $form ) {
	$v = thema_offerte_velden();
	if ( (int) $form['id'] !== (int) $v['formulier'] || ! class_exists( 'GFFormDisplay' ) ) {
		return;
	}
	$vertrek = '#input_' . (int) $form['id'] . '_' . (int) $v['datum'];
	$retour  = '#input_' . (int) $form['id'] . '_' . (int) $v['retourdatum'];
	$script  = '(function($){'
		. 'var v=' . wp_json_encode( $vertrek ) . ',r=' . wp_json_encode( $retour ) . ';'
		. 'function neem(){var w=$(v).val();if(w&&!$(r).val()){$(r).val(w).trigger("input").trigger("change");}}'
		. '$(document).off(".themaDatum").on("change.themaDatum blur.themaDatum",v,neem).on("focus.themaDatum",r,neem);'
		. 'neem();'
		. '})(jQuery);';
	GFFormDisplay::add_init_script( $form['id'], 'thema_offerte_datums', GFFormDisplay::ON_PAGE_RENDER, $script );
} );
