<?php
/**
 * ACF-velden: flexibele blokken volgens het homepage-ontwerp, en de velden van een gelegenheid.
 */

defined( 'ABSPATH' ) || exit;

function thema_veld( $prefix, $naam, $label, $type, $extra = array() ) {
	return array_merge( array(
		'key'   => 'field_' . $prefix . '_' . $naam,
		'name'  => $naam,
		'label' => $label,
		'type'  => $type,
	), $extra );
}

function thema_tab( $prefix, $label ) {
	return array(
		'key'       => 'field_' . $prefix . '_tab_' . sanitize_key( $label ),
		'label'     => $label,
		'name'      => '',
		'type'      => 'tab',
		'placement' => 'top',
	);
}

function thema_breed( $breedte ) {
	return array( 'wrapper' => array( 'width' => $breedte ) );
}

const THEMA_ACCENT_UITLEG = 'Zet *sterretjes* om woorden voor de accentletter.';

/**
 * Standaard achtergrond per blok. Blokken zonder vermelding krijgen geen keuze.
 */
function thema_blok_achtergronden() {
	return array(
		'offerte'      => 'geel',
		'over_ons'     => 'standaard',
		'aan_boord'    => 'standaard',
		'voordelen'    => 'standaard',
		'gelegenheden' => 'paars',
		'werkgebied'   => 'standaard',
		'prijs'        => 'standaard',
		'reviews'      => 'geel',
		'video'        => 'paars',
		'faq'          => 'standaard',
		'tekst'        => 'standaard',
		'formulier'    => 'standaard',
	);
}

/**
 * Tabs Weergave en Geavanceerd, voor elk blok hetzelfde.
 */
function thema_extra_velden( $p, $standaard = '', $weergave = array() ) {
	$velden        = array();
	$achtergronden = thema_blok_achtergronden();

	$met_knop = in_array( $p, array( 'hero', 'over_ons', 'voordelen', 'prijs', 'faq', 'slot_cta' ), true );

	if ( $weergave || $met_knop || isset( $achtergronden[ $p ] ) ) {
		$velden[] = thema_tab( $p, 'Weergave' );
		$velden   = array_merge( $velden, $weergave );
		if ( $met_knop ) {
			$velden[] = thema_veld( $p, 'knop_vorm', 'Knopvorm', 'button_group', array_merge( thema_breed( 50 ), array(
				'choices'       => array( 'standaard' => 'Zoals ontworpen', 'rond' => 'Rond met pijl', 'schuin' => 'Schuin' ),
				'default_value' => 'standaard',
				'instructions'  => 'Staat in de thema-instellingen alles op rond, dan wordt een schuine knop een pil.',
			) ) );
			$velden[] = thema_veld( $p, 'knop_kleur', 'Knopkleur', 'button_group', array_merge( thema_breed( 50 ), array(
				'choices'       => array( 'geel' => 'Geel (standaard)', 'paars' => 'Paars', 'wit' => 'Wit' ),
				'default_value' => 'geel',
				'wrapper'       => array( 'width' => 50, 'class' => 'kleurkeuze knopkleur' ),
			) ) );
		}
		if ( isset( $achtergronden[ $p ] ) ) {
			$velden[] = thema_veld( $p, 'achtergrond', 'Achtergrond', 'button_group', array(
				'choices'       => array( 'standaard' => 'Standaard', 'geel' => 'Geel', 'paars' => 'Paars' ),
				'default_value' => $achtergronden[ $p ],
				'instructions'  => 'Standaard is de achtergrondkleur uit de thema-instellingen.',
				'wrapper'       => array( 'class' => 'kleurkeuze' ),
			) );
		}
	}

	$velden[] = thema_tab( $p, 'Geavanceerd' );
	if ( 'hero' === $p ) {
		$velden[] = thema_veld( $p, 'ruimte_onder', 'Ruimte onder', 'button_group', array(
			'choices'       => array( 'normaal' => 'Standaard', 'groot' => 'Meer', 'extra' => 'Veel' ),
			'default_value' => 'normaal',
			'instructions'  => 'Extra lucht tussen de hero en het volgende blok.',
		) );
	}
	if ( ! in_array( $p, array( 'hero', 'brede_foto' ), true ) ) {
		$ruimte   = array( 'normaal' => 'Standaard', 'klein' => 'Klein', 'geen' => 'Geen' );
		$velden[] = thema_veld( $p, 'ruimte_boven', 'Ruimte boven', 'button_group', array_merge( thema_breed( 50 ), array( 'choices' => $ruimte, 'default_value' => 'normaal' ) ) );
		$velden[] = thema_veld( $p, 'ruimte_onder', 'Ruimte onder', 'button_group', array_merge( thema_breed( 50 ), array( 'choices' => $ruimte, 'default_value' => 'normaal', 'instructions' => 'Zet blokken dichter op elkaar door hier en bij het volgende blok minder ruimte te kiezen.' ) ) );
	}
	$velden[] = thema_veld( $p, 'kopniveau', 'Kopniveau', 'button_group', array(
		'choices'       => array( 'auto' => 'Automatisch', 'h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3' ),
		'default_value' => 'auto',
		'instructions'  => 'Alleen voor Google en schermlezers, de grootte blijft gelijk. Automatisch: H1 als dit het eerste blok is, anders H2. Gebruik één H1 per pagina.',
	) );
	$velden[] = thema_veld( $p, 'anker', 'Anker-ID', 'text', array(
		'instructions'  => 'Voor links naar dit blok, bijvoorbeeld uit het menu (#' . ( $standaard ?: 'voorbeeld' ) . ').',
		'prepend'       => '#',
		'default_value' => $standaard,
	) );
	return $velden;
}

function thema_layout( $naam, $label, $velden ) {
	return array(
		'key'        => 'layout_' . $naam,
		'name'       => $naam,
		'label'      => $label,
		'display'    => 'block',
		'sub_fields' => $velden,
	);
}

function thema_layouts() {
	$l = array();

	// Hero.
	$p   = 'hero';
	$l[] = thema_layout( $p, 'Hero (opening)', array_merge( array(
		thema_tab( $p, 'Inhoud' ),
		thema_veld( $p, 'kop_1', 'Kop regel 1', 'text', array_merge( thema_breed( 50 ), array( 'default_value' => 'Partybus' ) ) ),
		thema_veld( $p, 'kop_2', 'Kop regel 2', 'text', array_merge( thema_breed( 50 ), array( 'default_value' => 'huren in' ) ) ),
		thema_veld( $p, 'plaatsen', 'Wisselende plaatsen', 'textarea', array( 'rows' => 4, 'instructions' => 'Eén per regel. Ze wisselen elkaar af in het gele label. Leeg laten voor een vaste kop.' ) ),
		thema_veld( $p, 'sub_1', 'Subkop', 'text', array_merge( thema_breed( 50 ), array( 'default_value' => 'Jouw feest begint' ) ) ),
		thema_veld( $p, 'sub_2', 'Subkop schuin', 'text', array_merge( thema_breed( 50 ), array( 'default_value' => 'in de bus!' ) ) ),
		thema_veld( $p, 'knop', 'Knop', 'link', thema_breed( 50 ) ),
		thema_veld( $p, 'toon_score', 'Google-score tonen', 'true_false', array_merge( thema_breed( 50 ), array( 'ui' => 1, 'default_value' => 1 ) ) ),
		thema_tab( $p, 'Beelden' ),
		thema_veld( $p, 'stijl', 'Hero-stijl', 'button_group', array(
			'choices'       => array( 'standaard' => 'Uit instellingen', 'foto' => 'Fotobreed', 'collage' => 'Collage', 'bento' => 'Bento', 'klassiek' => 'Klassiek' ),
			'default_value' => 'standaard',
		) ),
		thema_veld( $p, 'fotos', 'Wisselende foto\'s', 'gallery', array( 'return_format' => 'id', 'preview_size' => 'thumbnail', 'instructions' => 'Fotobreed: schuiven achter de kop. Collage: subtiel op de achtergrond. Drie werkt het best.' ) ),
		thema_veld( $p, 'beeld', 'Hoofdbeeld', 'image', array_merge( thema_breed( 50 ), array( 'return_format' => 'id', 'preview_size' => 'medium', 'instructions' => 'Voor Klassiek en Bento.' ) ) ),
		thema_veld( $p, 'extra', 'Extra beelden', 'gallery', array_merge( thema_breed( 50 ), array( 'return_format' => 'id', 'preview_size' => 'thumbnail', 'instructions' => 'Collage: vier zwevende foto\'s. Bento: de kleine fototegel.' ) ) ),
		thema_veld( $p, 'bento_klein', 'Bento: roze tegel klein', 'text', array_merge( thema_breed( 33 ), array( 'default_value' => 'Chauffeur en gastvrouwke' ) ) ),
		thema_veld( $p, 'bento_groot', 'Bento: roze tegel groot', 'text', array_merge( thema_breed( 33 ), array( 'default_value' => 'Tot 88 personen' ) ) ),
		thema_veld( $p, 'bento_link', 'Bento: link roze tegel', 'link', thema_breed( 34 ) ),
	), thema_extra_velden( $p, 'top', array(
		thema_veld( $p, 'vorm', 'Vorm bij Fotobreed', 'button_group', array(
			'choices'       => array( 'auto' => 'Automatisch', 'breed' => 'Rand tot rand', 'kaart' => 'Kaart met ronde hoeken' ),
			'default_value' => 'auto',
			'instructions'  => 'Automatisch: rand tot rand op de homepage, als kaart met ronde hoeken op subpagina\'s (zoals bij een gelegenheid).',
		) ),
	) ) ) );

	// Offerte.
	$p   = 'offerte';
	$l[] = thema_layout( $p, 'Offerte als zin', array_merge( array(
		thema_tab( $p, 'Inhoud' ),
		thema_veld( $p, 'label', 'Label', 'text', array_merge( thema_breed( 40 ), array( 'default_value' => 'Duurt nog geen minuut' ) ) ),
		thema_veld( $p, 'kop', 'Kop', 'text', array_merge( thema_breed( 60 ), array( 'default_value' => 'Waar gaat jullie *feestje* naartoe?', 'instructions' => THEMA_ACCENT_UITLEG ) ) ),
		thema_veld( $p, 'zin_1', 'Zin voor datum', 'text', array_merge( thema_breed( 25 ), array( 'default_value' => 'Op' ) ) ),
		thema_veld( $p, 'zin_2', 'Zin voor aantal', 'text', array_merge( thema_breed( 25 ), array( 'default_value' => 'gaan we met' ) ) ),
		thema_veld( $p, 'zin_3', 'Zin voor plaats', 'text', array_merge( thema_breed( 25 ), array( 'default_value' => 'personen van' ) ) ),
		thema_veld( $p, 'zin_4', 'Zin voor bestemming', 'text', array_merge( thema_breed( 25 ), array( 'default_value' => 'naar' ) ) ),
		thema_veld( $p, 'ph_van', 'Plaatshouder plaats', 'text', array_merge( thema_breed( 50 ), array( 'default_value' => 'jullie plaats' ) ) ),
		thema_veld( $p, 'ph_naar', 'Plaatshouder bestemming', 'text', array_merge( thema_breed( 50 ), array( 'default_value' => 'de bestemming' ) ) ),
		thema_veld( $p, 'ph_personen', 'Plaatshouder aantal personen', 'text', array_merge( thema_breed( 50 ), array( 'default_value' => 'aantal' ) ) ),
		thema_veld( $p, 'zekerheden', 'Zekerheden', 'textarea', array_merge( thema_breed( 50 ), array( 'rows' => 4, 'default_value' => "Vrijblijvend\nBinnen 2 werkdagen een prijs\nAll-in, geen verrassingen" ) ) ),
		thema_veld( $p, 'knop_open', 'Knop op het compacte blok', 'text', array_merge( thema_breed( 50 ), array( 'default_value' => 'Vraag direct je prijs aan' ) ) ),
		thema_veld( $p, 'knop_verder', 'Knop in het formulier', 'text', array_merge( thema_breed( 50 ), array( 'default_value' => 'Ga verder' ) ) ),
		thema_veld( $p, 'open', 'Direct open tonen', 'true_false', array( 'ui' => 1, 'instructions' => 'Uit: eerst een compact blok met knop, zoals in het ontwerp.' ) ),
	), thema_extra_velden( $p, 'offerte' ) ) );

	// Lopende band.
	$p   = 'lopende_band';
	$l[] = thema_layout( $p, 'Lopende band', array_merge( array(
		thema_tab( $p, 'Inhoud' ),
		thema_veld( $p, 'items', 'Punten', 'textarea', array( 'rows' => 4, 'instructions' => 'Eén per regel. Om en om in kop- en accentletter.', 'default_value' => "Offerte in 2 werkdagen\nVolle bak gezelligheid\nAll-in, geen verrassingen\n5,0 op Google" ) ),
	), thema_extra_velden( $p, '', array(
		thema_veld( $p, 'variant', 'Variant', 'button_group', array(
			'choices'       => array( 'paars' => 'Paarse band', 'subtiel' => 'Subtiel, zonder achtergrond' ),
			'default_value' => 'paars',
			'instructions'  => 'Subtiel: grote zachte letters zonder achtergrond, om een pagina rustig op te delen.',
		) ),
		thema_veld( $p, 'tekstkleur', 'Tekstkleur', 'button_group', array(
			'choices'           => array( 'subtiel' => 'Subtiel, zacht paars', 'kleurrijk' => 'Kleurrijk, paars en roze' ),
			'default_value'     => 'subtiel',
			'conditional_logic' => array( array( array( 'field' => 'field_lopende_band_variant', 'operator' => '==', 'value' => 'subtiel' ) ) ),
		) ),
		thema_veld( $p, 'beweging', 'Beweging', 'button_group', array_merge( thema_breed( 50 ), array(
			'choices'       => array( 'vanzelf' => 'Loopt vanzelf', 'scroll' => 'Beweegt mee met scrollen' ),
			'default_value' => 'vanzelf',
		) ) ),
		thema_veld( $p, 'rijen', 'Aantal rijen', 'button_group', array_merge( thema_breed( 50 ), array(
			'choices'       => array( '1' => '1', '2' => '2', '3' => '3' ),
			'default_value' => '1',
			'instructions'  => 'Rijen onder elkaar bewegen om en om de andere kant op.',
		) ) ),
		thema_veld( $p, 'schuin', 'Schuin zetten', 'range', array(
			'min'           => -8,
			'max'           => 8,
			'step'          => .5,
			'default_value' => 0,
			'append'        => 'graden',
			'instructions'  => '0 is recht. Een paar graden geeft al een speels effect.',
		) ),
	) ) ) );

	// Over ons.
	$p   = 'over_ons';
	$l[] = thema_layout( $p, 'Tekst + fotostapel', array_merge( array(
		thema_tab( $p, 'Inhoud' ),
		thema_veld( $p, 'eyebrow', 'Label', 'text', array_merge( thema_breed( 35 ), array( 'default_value' => 'Over ons' ) ) ),
		thema_veld( $p, 'kop', 'Kop', 'text', array_merge( thema_breed( 65 ), array( 'instructions' => THEMA_ACCENT_UITLEG, 'default_value' => 'Busje in, *sfeertje aan!*' ) ) ),
		thema_veld( $p, 'tekst_1', 'Alinea 1', 'textarea', array( 'rows' => 3 ) ),
		thema_veld( $p, 'tekst_2', 'Alinea 2', 'textarea', array( 'rows' => 3 ) ),
		thema_veld( $p, 'knop', 'Knop', 'link' ),
		thema_tab( $p, 'Beelden' ),
		thema_veld( $p, 'beelden', 'Drie foto\'s', 'gallery', array( 'return_format' => 'id', 'preview_size' => 'thumbnail', 'max' => 3, 'instructions' => 'De eerste is groot, de andere twee staan schuin eronder.' ) ),
	), thema_extra_velden( $p, 'overons' ) ) );

	// Brede foto.
	$p   = 'brede_foto';
	$l[] = thema_layout( $p, 'Foto schermbreed', array_merge( array(
		thema_tab( $p, 'Inhoud' ),
		thema_veld( $p, 'beeld', 'Foto', 'image', array_merge( thema_breed( 50 ), array( 'return_format' => 'id', 'preview_size' => 'medium' ) ) ),
		thema_veld( $p, 'hoogte', 'Hoogte', 'button_group', array_merge( thema_breed( 25 ), array( 'choices' => array( 'normaal' => 'Normaal', 'hoog' => 'Hoog' ), 'default_value' => 'normaal' ) ) ),
		thema_veld( $p, 'focus', 'Focuspunt', 'text', array_merge( thema_breed( 25 ), array( 'default_value' => 'center 40%', 'instructions' => 'Bijvoorbeeld center 40%.' ) ) ),
	), thema_extra_velden( $p ) ) );

	// Fotoslider.
	$p   = 'fotoslider';
	$l[] = thema_layout( $p, 'Fotoslider schermbreed', array_merge( array(
		thema_tab( $p, 'Inhoud' ),
		thema_veld( $p, 'fotos', 'Foto\'s', 'gallery', array( 'return_format' => 'id', 'preview_size' => 'thumbnail', 'min' => 2, 'instructions' => 'Minimaal twee. Liggende foto\'s werken het best. Sleep om de volgorde te veranderen.' ) ),
		thema_veld( $p, 'bijschriften', 'Bijschriften tonen', 'true_false', array( 'ui' => 1, 'instructions' => 'Gebruikt het bijschrift uit de mediabibliotheek. Zonder bijschrift blijft de foto leeg.' ) ),
	), thema_extra_velden( $p, '', array(
		thema_veld( $p, 'vorm', 'Vorm', 'button_group', array_merge( thema_breed( 50 ), array(
			'choices'       => array( 'kaart' => 'Ronde hoeken met rand', 'breed' => 'Rand tot rand' ),
			'default_value' => 'kaart',
		) ) ),
		thema_veld( $p, 'hoogte', 'Hoogte', 'button_group', array_merge( thema_breed( 50 ), array(
			'choices'       => array( 'laag' => 'Laag', 'normaal' => 'Normaal', 'hoog' => 'Hoog' ),
			'default_value' => 'normaal',
		) ) ),
		thema_veld( $p, 'automatisch', 'Automatisch doorlopen', 'true_false', array_merge( thema_breed( 50 ), array( 'ui' => 1, 'default_value' => 1, 'instructions' => 'Pauzeert als je met de muis op de slider staat of hem bedient.' ) ) ),
		thema_veld( $p, 'interval', 'Wisselt elke', 'number', array_merge( thema_breed( 50 ), array( 'default_value' => 6, 'min' => 3, 'max' => 20, 'append' => 'seconden' ) ) ),
	) ) ) );

	// Aan boord.
	$p   = 'aan_boord';
	$l[] = thema_layout( $p, 'Intro: kop + tekst', array_merge( array(
		thema_tab( $p, 'Inhoud' ),
		thema_veld( $p, 'eyebrow', 'Label', 'text', array_merge( thema_breed( 35 ), array( 'default_value' => 'Aan boord van onze bussen' ) ) ),
		thema_veld( $p, 'kop', 'Kop', 'text', array_merge( thema_breed( 65 ), array( 'instructions' => THEMA_ACCENT_UITLEG . ' Het accent is hier roze.' ) ) ),
		thema_veld( $p, 'tekst', 'Tekst', 'textarea', array( 'rows' => 3 ) ),
	), thema_extra_velden( $p, 'bussen' ) ) );

	// Voordelen.
	$p   = 'voordelen';
	$l[] = thema_layout( $p, 'Voordelen + foto', array_merge( array(
		thema_tab( $p, 'Inhoud' ),
		thema_veld( $p, 'items', 'Voordelen', 'repeater', array(
			'layout'       => 'block',
			'collapsed'    => 'field_voordelen_item_titel',
			'max'          => 8,
			'button_label' => 'Voordeel toevoegen',
			'sub_fields'   => array(
				thema_veld( 'voordelen_item', 'titel', 'Titel', 'text', thema_breed( 60 ) ),
				thema_veld( 'voordelen_item', 'beeld', 'Foto', 'image', array_merge( thema_breed( 40 ), array( 'return_format' => 'id', 'preview_size' => 'thumbnail' ) ) ),
				thema_veld( 'voordelen_item', 'icoon', 'Icoon', 'select', array( 'choices' => thema_icoon_keuzes(), 'wrapper' => array( 'class' => 'icoonveld' ) ) ),
				thema_veld( 'voordelen_item', 'tekst', 'Tekst', 'textarea', array( 'rows' => 2 ) ),
			),
		) ),
		thema_veld( $p, 'knop', 'Knop eronder', 'link' ),
	), thema_extra_velden( $p, '', array(
		thema_veld( $p, 'weergave', 'Weergave', 'button_group', array(
			'choices'       => array( 'tabs' => 'Tabs met wisselende foto', 'tegels' => 'Tegels met foto en tekst' ),
			'default_value' => 'tabs',
			'instructions'  => 'Tegels: alle voordelen tegelijk in beeld, elk met eigen foto en tekst. Mooi op een pagina die meer uitleg geeft.',
		) ),
	) ) ) );

	// Gelegenheden.
	$p   = 'gelegenheden';
	$l[] = thema_layout( $p, 'Gelegenheden (kaarten)', array_merge( array(
		thema_tab( $p, 'Inhoud' ),
		thema_veld( $p, 'kop', 'Kop', 'textarea', array( 'rows' => 2, 'new_lines' => '', 'instructions' => THEMA_ACCENT_UITLEG . ' Enter = nieuwe regel.', 'default_value' => "Wat is jouw reden\n*voor een feestje?*" ) ),
		thema_veld( $p, 'weergave', 'Weergave', 'button_group', array_merge( thema_breed( 50 ), array( 'choices' => array( 'schuiven' => 'Schuift mee met scrollen', 'raster' => 'Raster' ), 'default_value' => 'schuiven' ) ) ),
		thema_veld( $p, 'keuze', 'Welke gelegenheden', 'relationship', array_merge( thema_breed( 50 ), array( 'post_type' => array( 'gelegenheid' ), 'filters' => array( 'search' ), 'return_format' => 'id', 'instructions' => 'Leeg = alle, in de ingestelde volgorde.' ) ) ),
	), thema_extra_velden( $p, 'gelegenheden' ) ) );

	// Werkgebied.
	$p   = 'werkgebied';
	$l[] = thema_layout( $p, 'Werkgebied + kaart', array_merge( array(
		thema_tab( $p, 'Inhoud' ),
		thema_veld( $p, 'kop', 'Kop', 'text', array_merge( thema_breed( 50 ), array( 'instructions' => THEMA_ACCENT_UITLEG, 'default_value' => 'We komen *overal!*' ) ) ),
		thema_veld( $p, 'legenda', 'Label op de kaart', 'text', array_merge( thema_breed( 50 ), array( 'default_value' => 'Thuisbasis Oisterwijk' ) ) ),
		thema_veld( $p, 'tekst', 'Tekst', 'textarea', array( 'rows' => 3 ) ),
		thema_veld( $p, 'steden', 'Plaatsen', 'textarea', array( 'rows' => 6, 'instructions' => 'Eén per regel, als: Plaats | reistijd. De thuisbasis zet je bovenaan.' ) ),
	), thema_extra_velden( $p ) ) );

	// Prijs.
	$p   = 'prijs';
	$l[] = thema_layout( $p, 'Prijsopbouw', array_merge( array(
		thema_tab( $p, 'Inhoud' ),
		thema_veld( $p, 'kop', 'Kop', 'text', array_merge( thema_breed( 50 ), array( 'instructions' => THEMA_ACCENT_UITLEG, 'default_value' => 'Waar hangt de prijs *vanaf?*' ) ) ),
		thema_veld( $p, 'knop', 'Knop', 'link', thema_breed( 50 ) ),
		thema_veld( $p, 'tekst', 'Tekst', 'textarea', array( 'rows' => 2 ) ),
		thema_veld( $p, 'factoren_label', 'Label factoren', 'text', array_merge( thema_breed( 50 ), array( 'default_value' => 'Je prijs hangt af van' ) ) ),
		thema_veld( $p, 'inbegrepen_label', 'Label inbegrepen', 'text', array_merge( thema_breed( 50 ), array( 'default_value' => 'Inbegrepen' ) ) ),
		thema_veld( $p, 'factoren', 'Prijsfactoren (max 4)', 'textarea', array_merge( thema_breed( 50 ), array( 'rows' => 4 ) ) ),
		thema_veld( $p, 'inbegrepen', 'Altijd inbegrepen', 'textarea', array_merge( thema_breed( 50 ), array( 'rows' => 4 ) ) ),
		thema_veld( $p, 'uitkomst', 'Uitkomst', 'text', array( 'default_value' => 'Eén vaste all-in prijs, binnen 2 werkdagen' ) ),
		thema_veld( $p, 'stijl', 'Stijl', 'button_group', array( 'choices' => array( 'standaard' => 'Uit instellingen', 'kaartje' => 'Prijskaartje', 'lijst' => 'Lijst', 'tegels' => 'Tegels' ), 'default_value' => 'standaard' ) ),
	), thema_extra_velden( $p, 'prijzen' ) ) );

	// Reviews.
	$p   = 'reviews';
	$l[] = thema_layout( $p, 'Reviews', array_merge( array(
		thema_tab( $p, 'Inhoud' ),
		thema_veld( $p, 'kop', 'Kop', 'text', array_merge( thema_breed( 50 ), array( 'instructions' => THEMA_ACCENT_UITLEG, 'default_value' => 'Deze *sfeergangers* gingen je voor' ) ) ),
		thema_veld( $p, 'score', 'Score', 'text', array_merge( thema_breed( 20 ), array( 'default_value' => '5.0' ) ) ),
		thema_veld( $p, 'score_label', 'Score label', 'text', array_merge( thema_breed( 30 ), array( 'default_value' => 'Google reviews' ) ) ),
		thema_veld( $p, 'reviews', 'Reviews', 'repeater', array(
			'layout'       => 'table',
			'button_label' => 'Review toevoegen',
			'sub_fields'   => array(
				thema_veld( 'reviews_item', 'naam', 'Naam', 'text', thema_breed( 25 ) ),
				thema_veld( 'reviews_item', 'tekst', 'Tekst', 'textarea', array_merge( thema_breed( 75 ), array( 'rows' => 2 ) ) ),
			),
		) ),
	), thema_extra_velden( $p, 'reviews', array(
		thema_veld( $p, 'weergave', 'Weergave', 'button_group', array(
			'choices'       => array( 'groot' => 'Groot, schuine kaarten', 'compact' => 'Compact, twee kolommen' ),
			'default_value' => 'groot',
			'instructions'  => 'Compact: kop en score links, de reviews lopen rechts in een lagere band. Neemt ongeveer de helft minder hoogte in.',
		) ),
		thema_veld( $p, 'bron', 'Bron van de reviews', 'button_group', array(
			'choices'       => array( 'eigen' => 'Eigen reviews', 'trustindex' => 'Trustindex' ),
			'default_value' => 'eigen',
			'instructions'  => 'Trustindex: de carrousel en de rating-knop uit Thema > Reviews. De kop van dit blok blijft staan.',
		) ),
	) ) ) );

	// Video.
	$p   = 'video';
	$l[] = thema_layout( $p, 'Video', array_merge( array(
		thema_tab( $p, 'Inhoud' ),
		thema_veld( $p, 'poster', 'Posterbeeld', 'image', array_merge( thema_breed( 34 ), array( 'return_format' => 'id', 'preview_size' => 'medium' ) ) ),
		thema_veld( $p, 'bestand', 'Video (mp4)', 'file', array_merge( thema_breed( 33 ), array( 'return_format' => 'url', 'mime_types' => 'mp4,webm' ) ) ),
		thema_veld( $p, 'embed', 'Of YouTube/Vimeo', 'oembed', thema_breed( 33 ) ),
	), thema_extra_velden( $p, 'video', array(
		thema_veld( $p, 'breedte', 'Breedte', 'button_group', array_merge( thema_breed( 50 ), array(
			'choices'       => array( 'smal' => 'Smal kader', 'kader' => 'Breed kader', 'vol' => 'Rand tot rand' ),
			'default_value' => 'kader',
			'instructions'  => 'Breed kader: bijna schermbreed met ronde hoeken, klapt open tijdens scrollen.',
		) ) ),
		thema_veld( $p, 'hoogte', 'Hoogte', 'button_group', array_merge( thema_breed( 50 ), array(
			'choices'       => array( 'standaard' => 'Standaard', 'laag' => 'Laag', 'hoog' => 'Hoog', 'scherm' => 'Schermvullend' ),
			'default_value' => 'standaard',
			'instructions'  => 'De video vult het vlak altijd helemaal, ook als hij een andere verhouding heeft.',
		) ) ),
		thema_veld( $p, 'autoplay', 'Automatisch afspelen', 'true_false', array_merge( thema_breed( 50 ), array(
			'ui'           => 1,
			'instructions' => 'Speelt zonder geluid en herhaalt, zonder afspeelknop. Werkt het best met een korte mp4.',
		) ) ),
	) ) ) );

	// FAQ.
	$p   = 'faq';
	$l[] = thema_layout( $p, 'Veelgestelde vragen', array_merge( array(
		thema_tab( $p, 'Inhoud' ),
		thema_veld( $p, 'kop', 'Kop', 'text', array_merge( thema_breed( 50 ), array( 'instructions' => THEMA_ACCENT_UITLEG, 'default_value' => 'Vragen voordat je *instapt*' ) ) ),
		thema_veld( $p, 'knop', 'Knop', 'link', array_merge( thema_breed( 50 ), array( 'instructions' => 'Leeg = belknop met het telefoonnummer.' ) ) ),
		thema_veld( $p, 'tekst', 'Tekst', 'textarea', array( 'rows' => 2 ) ),
		thema_veld( $p, 'vragen', 'Vragen', 'repeater', array(
			'layout'       => 'block',
			'collapsed'    => 'field_faq_vraag_vraag',
			'button_label' => 'Vraag toevoegen',
			'sub_fields'   => array(
				thema_veld( 'faq_vraag', 'vraag', 'Vraag', 'text' ),
				thema_veld( 'faq_vraag', 'antwoord', 'Antwoord', 'textarea', array( 'rows' => 3 ) ),
			),
		) ),
		thema_veld( $p, 'schema', 'FAQ-schema voor Google', 'true_false', array( 'ui' => 1, 'default_value' => 1 ) ),
	), thema_extra_velden( $p, 'faq' ) ) );

	// Slot-CTA.
	$p   = 'slot_cta';
	$l[] = thema_layout( $p, 'Afsluiter met knop', array_merge( array(
		thema_tab( $p, 'Inhoud' ),
		thema_veld( $p, 'kop', 'Kop', 'text', array_merge( thema_breed( 50 ), array( 'default_value' => 'Ook een lekker' ) ) ),
		thema_veld( $p, 'pil', 'Kop in paars label', 'text', array_merge( thema_breed( 50 ), array( 'default_value' => 'fijn feestje?' ) ) ),
		thema_veld( $p, 'tekst', 'Tekst', 'text' ),
		thema_veld( $p, 'knop', 'Knop', 'link' ),
	), thema_extra_velden( $p, 'contact' ) ) );

	// Tekst.
	$p   = 'tekst';
	$l[] = thema_layout( $p, 'Tekst (vrij)', array_merge( array(
		thema_tab( $p, 'Inhoud' ),
		thema_veld( $p, 'eyebrow', 'Label', 'text', thema_breed( 35 ) ),
		thema_veld( $p, 'kop', 'Kop', 'text', array_merge( thema_breed( 65 ), array( 'instructions' => THEMA_ACCENT_UITLEG ) ) ),
		thema_veld( $p, 'tekst', 'Tekst', 'wysiwyg', array( 'tabs' => 'all', 'toolbar' => 'full', 'media_upload' => 1 ) ),
		thema_veld( $p, 'breedte', 'Breedte', 'button_group', array( 'choices' => array( 'smal' => 'Smal', 'breed' => 'Breed', 'twee' => 'Kop links, tekst rechts' ), 'default_value' => 'smal' ) ),
	), thema_extra_velden( $p ) ) );

	// Formulier.
	$p   = 'formulier';
	$l[] = thema_layout( $p, 'Formulier', array_merge( array(
		thema_tab( $p, 'Inhoud' ),
		thema_veld( $p, 'kop', 'Kop', 'text', array( 'instructions' => THEMA_ACCENT_UITLEG ) ),
		thema_veld( $p, 'intro', 'Intro', 'textarea', array( 'rows' => 2 ) ),
		thema_veld( $p, 'formulier', 'Gravity Forms formulier', 'select', array( 'choices' => array(), 'allow_null' => 1 ) ),
	), thema_extra_velden( $p, 'formulier' ) ) );

	// Contact.
	$p   = 'contact';
	$l[] = thema_layout( $p, 'Contact (gegevens + formulier)', array_merge( array(
		thema_tab( $p, 'Inhoud' ),
		thema_veld( $p, 'eyebrow', 'Label', 'text', array_merge( thema_breed( 35 ), array( 'default_value' => 'Contact' ) ) ),
		thema_veld( $p, 'kop', 'Kop', 'text', array_merge( thema_breed( 65 ), array( 'default_value' => 'Neem *contact* met ons op', 'instructions' => THEMA_ACCENT_UITLEG ) ) ),
		thema_veld( $p, 'intro', 'Intro', 'textarea', array( 'rows' => 2, 'default_value' => 'Een vraag over een rit, onze bussen of iets heel anders? Bel, mail of stuur een bericht. We reageren binnen 2 werkdagen, meestal sneller.' ) ),
		thema_veld( $p, 'toon_offerte', 'Offerteregel tonen', 'true_false', array_merge( thema_breed( 20 ), array( 'ui' => 1, 'default_value' => 1 ) ) ),
		thema_veld( $p, 'offerte_tekst', 'Offerteregel', 'text', array_merge( thema_breed( 40 ), array( 'default_value' => 'Liever meteen een prijs?' ) ) ),
		thema_veld( $p, 'offerte_knop', 'Linktekst', 'text', array_merge( thema_breed( 40 ), array( 'default_value' => 'Vraag direct een offerte aan', 'instructions' => 'Gaat naar de offertelink uit de thema-instellingen.' ) ) ),
		thema_tab( $p, 'Kaarten' ),
		thema_veld( $p, 'kaarten_uitleg', 'Waar komen de gegevens vandaan?', 'message', array( 'message' => 'Telefoonnummer, e-mailadres en WhatsApp-nummer komen uit Thema > Algemeen > Contact. Pas ze daar aan, dan veranderen ze overal tegelijk.' ) ),
		thema_veld( $p, 'bellen_label', 'Label bellen', 'text', array_merge( thema_breed( 35 ), array( 'default_value' => 'Bel ons' ) ) ),
		thema_veld( $p, 'bellen_uitleg', 'Uitleg bellen', 'text', array_merge( thema_breed( 65 ), array( 'default_value' => 'Ook voor een snelle vraag over je rit' ) ) ),
		thema_veld( $p, 'mailen_label', 'Label mailen', 'text', array_merge( thema_breed( 35 ), array( 'default_value' => 'Mail ons' ) ) ),
		thema_veld( $p, 'mailen_uitleg', 'Uitleg mailen', 'text', array_merge( thema_breed( 65 ), array( 'default_value' => 'Antwoord binnen 2 werkdagen' ) ) ),
		thema_veld( $p, 'toon_whatsapp', 'WhatsApp-kaart tonen', 'true_false', array( 'ui' => 1, 'default_value' => 1, 'instructions' => 'Verschijnt alleen als er een WhatsApp-nummer in de thema-instellingen staat.' ) ),
		thema_veld( $p, 'whatsapp_label', 'Label WhatsApp', 'text', array_merge( thema_breed( 35 ), array( 'default_value' => 'App ons' ) ) ),
		thema_veld( $p, 'whatsapp_uitleg', 'Uitleg WhatsApp', 'text', array_merge( thema_breed( 65 ), array( 'default_value' => 'Snel even schakelen via WhatsApp' ) ) ),
		thema_tab( $p, 'Formulier' ),
		thema_veld( $p, 'formulier', 'Gravity Forms formulier', 'select', array( 'choices' => array(), 'allow_null' => 1, 'instructions' => 'Leeg: de gegevens staan dan naast elkaar over de volle breedte.' ) ),
		thema_veld( $p, 'formulier_kop', 'Kop boven het formulier', 'text', array_merge( thema_breed( 50 ), array( 'default_value' => 'Stuur ons een *bericht*', 'instructions' => THEMA_ACCENT_UITLEG ) ) ),
		thema_veld( $p, 'formulier_intro', 'Intro boven het formulier', 'text', array_merge( thema_breed( 50 ), array( 'default_value' => 'Vul je gegevens en je vraag in. We nemen zo snel mogelijk contact met je op.' ) ) ),
		thema_tab( $p, 'Gegevens' ),
		thema_veld( $p, 'gegevens_uitleg', 'Waar komen de gegevens vandaan?', 'message', array( 'message' => 'Adres, socials, KvK- en btw-nummer komen uit Thema > Algemeen en Thema > Footer. Hier kies je alleen wat je toont.' ) ),
		thema_veld( $p, 'toon_adres', 'Adres tonen', 'true_false', array_merge( thema_breed( 25 ), array( 'ui' => 1, 'default_value' => 1 ) ) ),
		thema_veld( $p, 'toon_route', 'Link Plan je route', 'true_false', array_merge( thema_breed( 25 ), array( 'ui' => 1, 'default_value' => 1 ) ) ),
		thema_veld( $p, 'toon_socials', 'Socials tonen', 'true_false', array_merge( thema_breed( 25 ), array( 'ui' => 1, 'default_value' => 1 ) ) ),
		thema_veld( $p, 'toon_bedrijf', 'Bedrijfsgegevens tonen', 'true_false', array_merge( thema_breed( 25 ), array( 'ui' => 1, 'default_value' => 1 ) ) ),
	), thema_extra_velden( $p, 'contact' ) ) );

	return $l;
}

function thema_formulier_keuzes( $veld ) {
	$veld['choices'] = array();
	if ( class_exists( 'GFAPI' ) ) {
		foreach ( GFAPI::get_forms() as $form ) {
			$veld['choices'][ $form['id'] ] = $form['title'];
		}
	}
	return $veld;
}
add_filter( 'acf/load_field/key=field_formulier_formulier', 'thema_formulier_keuzes' );
add_filter( 'acf/load_field/key=field_contact_formulier', 'thema_formulier_keuzes' );

add_action( 'acf/include_fields', function () {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group( array(
		'key'             => 'group_blokken',
		'title'           => 'Paginablokken',
		'fields'          => array(
			array(
				'key'          => 'field_blokken',
				'label'        => 'Blokken',
				'name'         => 'blokken',
				'type'         => 'flexible_content',
				'button_label' => 'Blok toevoegen',
				'layouts'      => thema_layouts(),
			),
		),
		'location'        => array(
			array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'page' ) ),
			array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'gelegenheid' ) ),
		),
		'menu_order'      => 10,
		'position'        => 'normal',
		'style'           => 'seamless',
		'label_placement' => 'top',
		'show_in_rest'    => 1,
	) );

	acf_add_local_field_group( array(
		'key'        => 'group_gelegenheid',
		'title'      => 'Gelegenheid',
		'fields'     => array(
			thema_veld( 'gel', 'ondertitel', 'Ondertitel', 'text', array( 'instructions' => 'Korte zin op de kaart en in de kop van de pagina.' ) ),
			thema_veld( 'gel', 'tekst', 'Tekst', 'wysiwyg', array( 'tabs' => 'visual', 'toolbar' => 'basic', 'media_upload' => 0, 'instructions' => 'Wat maakt de rit bijzonder, wat regelen we, waar rijden we vaak heen.' ) ),
			thema_veld( 'gel', 'voordelen', 'Voordelen', 'repeater', array(
				'layout'       => 'table',
				'max'          => 3,
				'button_label' => 'Voordeel toevoegen',
				'sub_fields'   => array(
					thema_veld( 'gel_voordeel', 'titel', 'Titel', 'text', thema_breed( 35 ) ),
					thema_veld( 'gel_voordeel', 'tekst', 'Tekst', 'textarea', array_merge( thema_breed( 65 ), array( 'rows' => 2 ) ) ),
				),
			) ),
		),
		'location'   => array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'gelegenheid' ) ) ),
		'menu_order' => 0,
		'position'   => 'acf_after_title',
		'style'      => 'default',
	) );
} );
