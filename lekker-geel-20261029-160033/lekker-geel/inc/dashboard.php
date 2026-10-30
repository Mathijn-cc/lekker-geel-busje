<?php
/**
 * Dashboard met thema-instellingen.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_init', function () {
	register_setting( 'thema_instellingen_groep', THEMA_OPTIE, array(
		'type'              => 'array',
		'sanitize_callback' => 'thema_opschonen',
		'default'           => thema_standaard(),
	) );
} );

add_action( 'admin_menu', function () {
	add_menu_page(
		'Thema-instellingen',
		'Thema',
		'manage_options',
		'thema-instellingen',
		'thema_dashboard_pagina',
		'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2.5" y="5" width="19" height="11" rx="3"/><path d="M2.5 11h19"/><circle cx="7" cy="17.5" r="1.8"/><circle cx="17" cy="17.5" r="1.8"/></svg>' ),
		59
	);
} );

/**
 * Doorsturen naar het dashboard na activeren.
 */
add_action( 'after_switch_theme', function () {
	if ( ! get_option( THEMA_OPTIE ) ) {
		update_option( THEMA_OPTIE, thema_standaard() );
	}
} );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( 'toplevel_page_thema-instellingen' !== $hook ) {
		return;
	}
	wp_enqueue_media();
	wp_enqueue_style( 'thema-dashboard', THEMA_URL . '/assets/css/admin-dashboard.css', array(), THEMA_VERSIE );
	wp_enqueue_style( 'thema-animaties', THEMA_URL . '/assets/css/animaties.css', array(), THEMA_VERSIE );
	wp_add_inline_style( 'thema-dashboard', thema_font_face( thema_fonts() ) );
	wp_enqueue_script( 'thema-animaties', THEMA_URL . '/assets/js/animaties.js', array(), THEMA_VERSIE, true );
	wp_enqueue_script( 'thema-accordeon', THEMA_URL . '/assets/js/accordeon.js', array(), THEMA_VERSIE, true );
	wp_enqueue_script( 'thema-dashboard', THEMA_URL . '/assets/js/admin-dashboard.js', array( 'jquery', 'thema-animaties', 'thema-accordeon' ), THEMA_VERSIE, true );

	$fonts = array();
	foreach ( thema_fonts() as $sleutel => $font ) {
		$fonts[ $sleutel ] = array(
			'stack'  => thema_font_stack( $sleutel ),
			'stijl'  => $font['stijl'] ?? 'normal',
			'schaal' => $font['schaal'] ?? 1,
		);
	}
	$eyebrows = array();
	foreach ( array( 'oranje', 'allesoranje', 'paars', 'zacht' ) as $e ) {
		$eyebrows[ $e ] = thema_eyebrow_kleuren( $e );
	}
	wp_localize_script( 'thema-dashboard', 'themaDashboard', array(
		'fonts'    => $fonts,
		'eyebrows' => $eyebrows,
	) );
} );

/* --------------------------------------------------------------------------
 * Bouwstenen voor de velden.
 * ----------------------------------------------------------------------- */

function thema_naam( $sleutel ) {
	return THEMA_OPTIE . '[' . $sleutel . ']';
}

function thema_veld_rij( $label, $uitleg, $controle, $extra_class = '' ) {
	printf(
		'<div class="veld %s"><div class="veld-label"><span class="veld-titel">%s</span>%s</div><div class="veld-invoer">%s</div></div>',
		esc_attr( $extra_class ),
		esc_html( $label ),
		$uitleg ? '<span class="veld-uitleg">' . esc_html( $uitleg ) . '</span>' : '',
		$controle
	);
}

function thema_schakelaar( $sleutel, $tekst = '' ) {
	$s = thema_instellingen();
	return sprintf(
		'<label class="schakelaar"><input type="hidden" name="%1$s" value="0"><input type="checkbox" name="%1$s" value="1" data-sleutel="%2$s" %3$s><span class="schakelaar-baan" aria-hidden="true"><span class="schakelaar-knop"></span></span>%4$s</label>',
		esc_attr( thema_naam( $sleutel ) ),
		esc_attr( $sleutel ),
		checked( ! empty( $s[ $sleutel ] ), true, false ),
		$tekst ? '<span class="schakelaar-tekst">' . esc_html( $tekst ) . '</span>' : ''
	);
}

function thema_segmenten( $sleutel, $keuzes ) {
	$s    = thema_instellingen();
	$html = '<div class="segmenten" role="radiogroup">';
	foreach ( $keuzes as $waarde => $label ) {
		$html .= sprintf(
			'<label class="segment"><input type="radio" name="%s" value="%s" data-sleutel="%s" %s><span>%s</span></label>',
			esc_attr( thema_naam( $sleutel ) ),
			esc_attr( $waarde ),
			esc_attr( $sleutel ),
			checked( $s[ $sleutel ], $waarde, false ),
			esc_html( $label )
		);
	}
	return $html . '</div>';
}

function thema_schuif( $sleutel, $eenheid = '', $stap = 1 ) {
	$s   = thema_instellingen();
	$def = thema_schema()[ $sleutel ];
	return sprintf(
		'<div class="schuif"><input type="range" name="%1$s" min="%2$d" max="%3$d" step="%4$s" value="%5$d" data-sleutel="%6$s" data-eenheid="%7$s"><output>%5$d%7$s</output></div>',
		esc_attr( thema_naam( $sleutel ) ),
		$def[2],
		$def[3],
		esc_attr( $stap ),
		(int) $s[ $sleutel ],
		esc_attr( $sleutel ),
		esc_attr( $eenheid )
	);
}

function thema_kleurveld( $sleutel, $label ) {
	$s = thema_instellingen();
	return sprintf(
		'<label class="kleurveld"><span class="kleurveld-staal" style="--staal:%1$s"><input type="color" value="%1$s" data-kleur-voor="%2$s" aria-label="%3$s"></span><span class="kleurveld-info"><span class="kleurveld-naam">%3$s</span><input type="text" class="kleurveld-hex" name="%4$s" value="%1$s" data-sleutel="%2$s" maxlength="7" spellcheck="false"></span></label>',
		esc_attr( $s[ $sleutel ] ),
		esc_attr( $sleutel ),
		esc_html( $label ),
		esc_attr( thema_naam( $sleutel ) )
	);
}

function thema_tekstveld( $sleutel, $type = 'text', $placeholder = '' ) {
	$s = thema_instellingen();
	if ( 'textarea' === $type ) {
		return sprintf( '<textarea name="%s" rows="3" class="invoer" placeholder="%s">%s</textarea>', esc_attr( thema_naam( $sleutel ) ), esc_attr( $placeholder ), esc_textarea( $s[ $sleutel ] ) );
	}
	return sprintf( '<input type="%s" name="%s" value="%s" class="invoer" placeholder="%s">', esc_attr( $type ), esc_attr( thema_naam( $sleutel ) ), esc_attr( $s[ $sleutel ] ), esc_attr( $placeholder ) );
}

function thema_mediaveld( $sleutel, $knoptekst ) {
	$s   = thema_instellingen();
	$id  = (int) $s[ $sleutel ];
	$url = $id ? wp_get_attachment_image_url( $id, 'medium' ) : '';
	return sprintf(
		'<div class="mediaveld %5$s" data-media><input type="hidden" name="%1$s" value="%2$d" data-sleutel="%6$s"><div class="mediaveld-voorbeeld">%3$s</div><div class="mediaveld-knoppen"><button type="button" class="knopje" data-media-kies>%4$s</button><button type="button" class="knopje knopje-stil" data-media-weg>Verwijderen</button></div></div>',
		esc_attr( thema_naam( $sleutel ) ),
		$id,
		$url ? '<img src="' . esc_url( $url ) . '" alt="">' : '<span class="mediaveld-leeg">' . thema_icoon( 'vonk' ) . 'Nog niets gekozen</span>',
		esc_html( $knoptekst ),
		$id ? 'heeft-beeld' : '',
		esc_attr( $sleutel )
	);
}

/**
 * Keuzekaarten met een kleine illustratie.
 */
function thema_keuzekaarten( $sleutel, $keuzes, $class = '' ) {
	$s    = thema_instellingen();
	$html = '<div class="keuzekaarten ' . esc_attr( $class ) . '" role="radiogroup">';
	foreach ( $keuzes as $waarde => $keuze ) {
		$html .= sprintf(
			'<label class="keuzekaart"><input type="radio" name="%s" value="%s" data-sleutel="%s" %s><span class="keuzekaart-beeld">%s</span><span class="keuzekaart-naam">%s</span>%s</label>',
			esc_attr( thema_naam( $sleutel ) ),
			esc_attr( $waarde ),
			esc_attr( $sleutel ),
			checked( $s[ $sleutel ], $waarde, false ),
			$keuze['beeld'] ?? '',
			esc_html( $keuze['naam'] ),
			! empty( $keuze['uitleg'] ) ? '<span class="keuzekaart-uitleg">' . esc_html( $keuze['uitleg'] ) . '</span>' : ''
		);
	}
	return $html . '</div>';
}

/**
 * Schetsen van de stijlkeuzes, gelijk aan het stijlpaneel in het ontwerp.
 */
function thema_schets( $soort, $keuze ) {
	$g = 'var(--s-geel)';
	$p = 'var(--s-paars)';
	$c = 'var(--s-creme)';
	$r = 'var(--s-roze)';
	$svg = array(
		'hero'    => array(
			'foto'     => "<rect width='160' height='90' fill='{$p}'/><rect width='160' height='90' fill='url(#s-foto)' opacity='.55'/><rect x='12' y='44' width='80' height='10' rx='2' fill='{$c}'/><rect x='12' y='58' width='44' height='11' rx='3' fill='{$g}' transform='rotate(-3 34 63)'/><path d='M104 90 L116 74 L160 74 L160 90Z' fill='{$g}'/>",
			'collage'  => "<rect width='160' height='90' fill='{$g}'/><rect x='10' y='22' width='26' height='34' rx='4' fill='{$c}' transform='rotate(-8 23 39)'/><rect x='124' y='18' width='26' height='32' rx='4' fill='{$c}' transform='rotate(7 137 34)'/><rect x='48' y='28' width='64' height='10' rx='2' fill='{$p}'/><rect x='58' y='42' width='44' height='10' rx='2' fill='{$p}'/><rect x='62' y='58' width='36' height='10' rx='5' fill='{$p}'/>",
			'bento'    => "<rect width='160' height='90' fill='{$g}'/><rect x='8' y='8' width='70' height='46' rx='6' fill='{$c}'/><rect x='82' y='8' width='70' height='46' rx='6' fill='{$p}'/><rect x='8' y='58' width='33' height='24' rx='6' fill='{$p}'/><rect x='45' y='58' width='33' height='24' rx='6' fill='{$p}' opacity='.6'/><rect x='82' y='58' width='70' height='24' rx='6' fill='{$r}'/><rect x='14' y='22' width='40' height='7' rx='2' fill='{$p}'/><rect x='14' y='32' width='30' height='7' rx='2' fill='{$p}'/>",
			'klassiek' => "<rect width='160' height='90' fill='{$g}'/><rect x='12' y='26' width='54' height='9' rx='2' fill='{$p}'/><rect x='12' y='38' width='42' height='9' rx='2' fill='{$p}'/><rect x='12' y='52' width='32' height='9' rx='2' fill='{$p}' transform='rotate(-3 28 56)'/><rect x='84' y='16' width='66' height='56' rx='8' fill='{$p}'/><circle cx='86' cy='72' r='12' fill='{$c}'/>",
		),
		'menu'    => array(
			'zwevend' => "<rect width='160' height='90' fill='{$c}'/><rect x='10' y='10' width='140' height='18' rx='9' fill='#fff'/><rect x='18' y='15' width='24' height='8' rx='2' fill='{$p}'/><rect x='96' y='13' width='28' height='12' rx='6' fill='{$g}'/><rect x='128' y='13' width='18' height='12' rx='6' fill='{$p}'/>",
			'balk'    => "<rect width='160' height='90' fill='{$c}'/><rect width='160' height='26' fill='#fff'/><rect x='10' y='9' width='24' height='8' rx='2' fill='{$p}'/><rect x='52' y='11' width='12' height='4' rx='2' fill='{$p}' opacity='.5'/><rect x='68' y='11' width='12' height='4' rx='2' fill='{$p}' opacity='.5'/><rect x='84' y='11' width='12' height='4' rx='2' fill='{$p}' opacity='.5'/><rect x='118' y='8' width='32' height='10' rx='5' fill='{$p}'/>",
			'overlay' => "<rect width='160' height='90' fill='{$p}'/><rect x='10' y='9' width='24' height='8' rx='2' fill='{$g}'/><rect x='108' y='7' width='22' height='12' rx='6' fill='{$g}'/><rect x='132' y='7' width='18' height='12' rx='6' fill='{$c}'/><rect x='12' y='34' width='56' height='8' rx='2' fill='{$c}'/><rect x='12' y='48' width='44' height='8' rx='2' fill='{$c}'/><rect x='12' y='62' width='50' height='8' rx='2' fill='{$c}'/><rect x='92' y='34' width='56' height='44' rx='8' fill='{$g}'/>",
		),
		'prijs'   => array(
			'kaartje' => "<rect width='160' height='90' fill='{$c}'/><g transform='rotate(-2 80 45)'><rect x='38' y='14' width='88' height='62' rx='2' fill='{$p}'/><rect x='34' y='10' width='88' height='62' rx='2' fill='#fff' stroke='{$p}' stroke-width='2'/><rect x='42' y='20' width='40' height='5' rx='2' fill='{$p}'/><rect x='42' y='31' width='60' height='3' rx='1.5' fill='{$p}' opacity='.3'/><rect x='42' y='39' width='60' height='3' rx='1.5' fill='{$p}' opacity='.3'/><path d='M35 60 L121 54 L121 71 L35 71Z' fill='{$p}'/></g>",
			'lijst'   => "<rect width='160' height='90' fill='{$c}'/><rect x='24' y='16' width='112' height='2.5' fill='{$p}'/><text x='26' y='34' font-size='10' font-style='italic' fill='{$g}'>01</text><rect x='42' y='27' width='70' height='6' rx='2' fill='{$p}'/><text x='26' y='50' font-size='10' font-style='italic' fill='{$g}'>02</text><rect x='42' y='43' width='56' height='6' rx='2' fill='{$p}'/><text x='26' y='66' font-size='10' font-style='italic' fill='{$g}'>03</text><rect x='42' y='59' width='62' height='6' rx='2' fill='{$p}'/>",
			'tegels'  => "<rect width='160' height='90' fill='{$c}'/><rect x='22' y='10' width='116' height='70' rx='10' fill='#fff'/><rect x='30' y='20' width='48' height='14' rx='5' fill='{$c}'/><rect x='82' y='20' width='48' height='14' rx='5' fill='{$c}'/><rect x='30' y='38' width='48' height='14' rx='5' fill='{$c}'/><rect x='82' y='38' width='48' height='14' rx='5' fill='{$c}'/><circle cx='37' cy='27' r='4' fill='{$g}'/><circle cx='89' cy='27' r='4' fill='{$g}'/><circle cx='37' cy='45' r='4' fill='{$g}'/><circle cx='89' cy='45' r='4' fill='{$g}'/><path d='M22 62 H138 V70 a10 10 0 0 1 -10 10 H32 a10 10 0 0 1 -10 -10Z' fill='{$p}'/>",
		),
		'knoppen' => array(
			'rond'   => "<rect width='160' height='90' fill='{$c}'/><rect x='30' y='30' width='100' height='30' rx='15' fill='{$p}'/><rect x='42' y='42' width='44' height='6' rx='2' fill='{$c}'/><circle cx='115' cy='45' r='11' fill='{$g}'/>",
			'schuin' => "<rect width='160' height='90' fill='{$c}'/><path d='M32 34 L130 28 L128 60 L30 63Z' fill='{$g}'/><rect x='44' y='42' width='50' height='6' rx='2' fill='{$p}'/><path d='M104 40 l6 5 -6 5' stroke='{$p}' stroke-width='2.4' fill='none' stroke-linecap='round'/>",
		),
	);
	$defs = "<defs><pattern id='s-foto' width='20' height='20' patternUnits='userSpaceOnUse'><circle cx='6' cy='6' r='4' fill='{$g}' opacity='.5'/><circle cx='16' cy='14' r='3' fill='{$r}' opacity='.6'/></pattern></defs>";
	return '<svg viewBox="0 0 160 90" class="schets" aria-hidden="true">' . $defs . ( $svg[ $soort ][ $keuze ] ?? '' ) . '</svg>';
}

/**
 * Proef van een animatie in een keuzekaart.
 */
function thema_animatie_proef( $soort, $naam ) {
	if ( 'kop' === $soort ) {
		return '<span class="animatie-proef koppen-' . esc_attr( $naam ) . '"><span class="kop" data-kop>Busje in</span></span>';
	}
	return '<span class="animatie-proef elementen-' . esc_attr( $naam ) . '"><span class="proefblok" data-animeer></span><span class="proefblok" data-animeer></span><span class="proefblok" data-animeer></span></span>';
}

/* --------------------------------------------------------------------------
 * De pagina zelf.
 * ----------------------------------------------------------------------- */

function thema_dashboard_pagina() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$s = thema_instellingen();

	$tabs = array(
		'algemeen'    => array( 'Algemeen', 'vonk', 'Logo, contact en knoppen' ),
		'stijl'       => array( 'Stijl', 'feest', 'Hero, menu, prijs en achtergrond' ),
		'kleuren'     => array( 'Kleuren', 'hart', 'Het palet van de site' ),
		'typografie'  => array( 'Typografie', 'mail', 'Kop, accent en tekst' ),
		'animaties'   => array( 'Animaties', 'muziek', 'Scroll, uitklappen en cursor' ),
		'reviews'     => array( 'Reviews', 'hart', 'Trustindex en de Google-score' ),
		'footer'      => array( 'Footer', 'route', 'Oproep, teksten en social' ),
		'gereedschap' => array( 'Gereedschap', 'schild', 'Voorbeeldinhoud en back-up' ),
	);

	$melding = isset( $_GET['melding'] ) ? sanitize_key( wp_unslash( $_GET['melding'] ) ) : '';
	$teksten = array(
		'voorbeeld' => 'De voorbeeldinhoud staat klaar. Bekijk de homepage en de gelegenheden.',
		'reset'     => 'Alle instellingen staan weer op de standaardwaarden.',
		'import'    => 'De instellingen zijn geïmporteerd.',
		'fout'      => 'Dat lukte niet. Controleer de invoer en probeer het opnieuw.',
	);

	$achtergronden = array(
		'#faf5ee' => 'Crème',
		'#ffffff' => 'Wit',
		'#fff4dc' => 'Lichtgeel',
		'#f3eef8' => 'Lichtlila',
	);
	?>
	<div class="wrap dashboard" data-dashboard>
		<h1 class="screen-reader-text">Thema-instellingen</h1>

		<header class="dashboard-kop">
			<div class="dashboard-merk">
				<span class="dashboard-merk-icoon"><?php echo thema_icoon( 'bus', 22 ); ?></span>
				<div>
					<p class="dashboard-merk-titel">Thema-instellingen</p>
					<p class="dashboard-merk-sub">Lekker Geel Busje &middot; versie <?php echo esc_html( THEMA_VERSIE ); ?></p>
				</div>
			</div>
			<div class="dashboard-acties">
				<a class="knopje knopje-stil" href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener"><?php echo thema_icoon( 'pijl-op' ); ?>Bekijk website</a>
				<button type="submit" form="thema-formulier" class="knopje knopje-hoofd" data-opslaan>Opslaan</button>
			</div>
		</header>

		<?php if ( isset( $_GET['settings-updated'] ) ) : ?>
			<div class="dashboard-melding" data-melding><?php echo thema_icoon( 'vonk' ); ?>Opgeslagen. De wijzigingen staan live.</div>
		<?php elseif ( $melding && isset( $teksten[ $melding ] ) ) : ?>
			<div class="dashboard-melding <?php echo 'fout' === $melding ? 'is-fout' : ''; ?>" data-melding><?php echo thema_icoon( 'fout' === $melding ? 'sluiten' : 'vonk' ); ?><?php echo esc_html( $teksten[ $melding ] ); ?></div>
		<?php endif; ?>

		<div class="dashboard-raster">
			<nav class="dashboard-nav" aria-label="Onderdelen">
				<?php foreach ( $tabs as $id => $tab ) : ?>
					<a class="dashboard-tab" href="#<?php echo esc_attr( $id ); ?>" data-tab="<?php echo esc_attr( $id ); ?>">
						<span class="dashboard-tab-icoon"><?php echo thema_icoon( $tab[1], 18 ); ?></span>
						<span class="dashboard-tab-tekst"><span class="dashboard-tab-naam"><?php echo esc_html( $tab[0] ); ?></span><span class="dashboard-tab-sub"><?php echo esc_html( $tab[2] ); ?></span></span>
					</a>
				<?php endforeach; ?>
			</nav>

			<div class="dashboard-inhoud">
				<form id="thema-formulier" method="post" action="options.php" data-formulier>
					<?php settings_fields( 'thema_instellingen_groep' ); ?>

					<?php /* ---------------- Algemeen ---------------- */ ?>
					<section class="paneel" data-paneel="algemeen">
						<div class="paneel-kop">
							<h2>Algemeen</h2>
							<p>Logo, contactgegevens en de teksten op de vaste knoppen in het menu.</p>
						</div>

						<div class="kaartje">
							<h3 class="kaartje-titel">Logo</h3>
							<?php
							thema_veld_rij( 'Logo', 'SVG of PNG met transparante achtergrond. Leeg toont het meegeleverde logo.', thema_mediaveld( 'logo', 'Logo kiezen' ) );
							thema_veld_rij( 'Hoogte in het menu', 'Op mobiel wordt het logo vanzelf iets kleiner.', thema_schuif( 'logo_hoogte', 'px' ) );
							?>
						</div>

						<div class="kaartje">
							<h3 class="kaartje-titel">Contact</h3>
							<div class="veldenraster">
								<?php
								thema_veld_rij( 'Telefoon', '', thema_tekstveld( 'telefoon', 'text', '085 006 2061' ) );
								thema_veld_rij( 'E-mail', '', thema_tekstveld( 'email', 'email', 'info@lekkergeelbusje.nl' ) );
								thema_veld_rij( 'WhatsApp', 'Met landcode, bijvoorbeeld 31612345678.', thema_tekstveld( 'whatsapp', 'text', '31612345678' ) );
								thema_veld_rij( 'Adres', 'Straat en huisnummer op de eerste regel, postcode en plaats op de tweede.', thema_tekstveld( 'adres', 'textarea' ) );
								thema_veld_rij( 'KvK-nummer', 'Staat in het contactblok bij Bedrijfsgegevens.', thema_tekstveld( 'kvk', 'text', '12345678' ) );
								thema_veld_rij( 'Btw-nummer', 'Leeg laten om niet te tonen.', thema_tekstveld( 'btw', 'text', 'NL000000000B01' ) );
								?>
							</div>
						</div>

						<div class="kaartje">
							<h3 class="kaartje-titel">Menu en offerte</h3>
							<div class="veldenraster">
								<?php
								thema_veld_rij( 'Offertelink', 'Leeg = het offerteblok op de homepage (#offerte).', thema_tekstveld( 'offerte_link', 'url', home_url( '/#offerte' ) ) );
								thema_veld_rij( 'Offerteknop', '', thema_tekstveld( 'menu_knop' ) );
								thema_veld_rij( 'Offerteknop kort', 'Voor smalle schermen.', thema_tekstveld( 'menu_knop_kort' ) );
								thema_veld_rij( 'Tekst bij hover', 'Rolt in beeld als je over de knop beweegt.', thema_tekstveld( 'menu_knop_hover' ) );
								thema_veld_rij( 'Zin in het menu', 'Grote zin op de gele kaart in het open menu.', thema_tekstveld( 'menu_kaart_tekst' ) );
								?>
							</div>
						</div>

					</section>

					<?php /* ---------------- Reviews ---------------- */ ?>
					<section class="paneel" data-paneel="reviews">
						<div class="paneel-kop">
							<h2>Reviews</h2>
							<p>Laad de echte reviews in via Trustindex. Plak de embedcode of de shortcode van een widget, of alleen het widget-ID.</p>
						</div>

						<div class="kaartje">
							<h3 class="kaartje-titel">Trustindex-widgets</h3>
							<?php
							thema_veld_rij( 'Reviewcarrousel', 'Komt in het blok Reviews als je daar de bron Trustindex kiest.', thema_tekstveld( 'ti_carrousel', 'textarea', "<script defer async src='https://cdn.trustindex.io/loader.js?...'></script>" ) );
							thema_veld_rij( 'Rating-knop', 'Kleine knop met de score, voor in de hero en naast de kop van het blok Reviews.', thema_tekstveld( 'ti_rating', 'textarea', '[trustindex data-widget-id=...]' ) );
							?>
						</div>

						<div class="kaartje">
							<h3 class="kaartje-titel">Score in de hero</h3>
							<?php
							thema_veld_rij( 'Bron', 'Trustindex toont de rating-knop. Is die leeg, dan blijft de vaste score staan.', thema_segmenten( 'score_bron', array( 'vast' => 'Vaste score', 'trustindex' => 'Trustindex rating-knop' ) ) );
							?>
							<div class="veldenraster">
								<?php
								thema_veld_rij( 'Vaste score', 'Leeg = de score nergens tonen.', thema_tekstveld( 'score', 'text', '5,0' ) );
								thema_veld_rij( 'Label', '', thema_tekstveld( 'score_label', 'text', 'op Google' ) );
								?>
							</div>
						</div>
					</section>

					<?php /* ---------------- Stijl ---------------- */ ?>
					<section class="paneel" data-paneel="stijl">
						<div class="paneel-kop">
							<h2>Stijl</h2>
							<p>Dezelfde keuzes als het stijlpaneel in het ontwerp. Per blok kun je een afwijkende hero- of prijsstijl kiezen.</p>
						</div>

						<div class="kaartje">
							<h3 class="kaartje-titel">Hero</h3>
							<?php
							echo thema_keuzekaarten( 'hero_stijl', array(
								'foto'     => array( 'naam' => 'Fotobreed', 'uitleg' => 'Wisselende foto\'s achter de kop', 'beeld' => thema_schets( 'hero', 'foto' ) ),
								'collage'  => array( 'naam' => 'Collage', 'uitleg' => 'Gele hero met zwevende foto\'s', 'beeld' => thema_schets( 'hero', 'collage' ) ),
								'bento'    => array( 'naam' => 'Bento', 'uitleg' => 'Tegels met foto, score en voordeel', 'beeld' => thema_schets( 'hero', 'bento' ) ),
								'klassiek' => array( 'naam' => 'Klassiek', 'uitleg' => 'Kop links, foto rechts', 'beeld' => thema_schets( 'hero', 'klassiek' ) ),
							), 'vier stijlschetsen' );
							?>
						</div>

						<div class="kaartje">
							<h3 class="kaartje-titel">Menu</h3>
							<?php
							echo thema_keuzekaarten( 'menu_stijl', array(
								'zwevend' => array( 'naam' => 'Zwevend', 'uitleg' => 'Witte pil die over de pagina zweeft', 'beeld' => thema_schets( 'menu', 'zwevend' ) ),
								'balk'    => array( 'naam' => 'Balk', 'uitleg' => 'Links in beeld, wit bij scrollen', 'beeld' => thema_schets( 'menu', 'balk' ) ),
								'overlay' => array( 'naam' => 'Overlay', 'uitleg' => 'Alleen knoppen, menu schermvullend', 'beeld' => thema_schets( 'menu', 'overlay' ) ),
							), 'drie stijlschetsen' );
							?>
							<p class="kaartje-tekst">De links beheer je onder <a href="<?php echo esc_url( admin_url( 'nav-menus.php' ) ); ?>">Weergave &rsaquo; Menu's</a>.</p>
						</div>

						<div class="kaartje">
							<h3 class="kaartje-titel">Prijsblok</h3>
							<?php
							echo thema_keuzekaarten( 'prijs_stijl', array(
								'kaartje' => array( 'naam' => 'Prijskaartje', 'uitleg' => 'Schuin kaartje met harde schaduw', 'beeld' => thema_schets( 'prijs', 'kaartje' ) ),
								'lijst'   => array( 'naam' => 'Lijst', 'uitleg' => 'Grote genummerde regels', 'beeld' => thema_schets( 'prijs', 'lijst' ) ),
								'tegels'  => array( 'naam' => 'Tegels', 'uitleg' => 'Witte kaart met iconen', 'beeld' => thema_schets( 'prijs', 'tegels' ) ),
							), 'drie stijlschetsen' );
							?>
						</div>

						<div class="paneel-twee">
							<div class="kaartje">
								<h3 class="kaartje-titel">Knoppen</h3>
								<?php
								echo thema_keuzekaarten( 'knoppen', array(
									'rond'   => array( 'naam' => 'Rond', 'beeld' => thema_schets( 'knoppen', 'rond' ) ),
									'schuin' => array( 'naam' => 'Schuin', 'beeld' => thema_schets( 'knoppen', 'schuin' ) ),
								), 'twee stijlschetsen' );
								?>
							</div>
							<div class="kaartje">
								<h3 class="kaartje-titel">Eyebrow</h3>
								<p class="kaartje-tekst">Het kleine label boven koppen, met de hoekjes uit het logo.</p>
								<?php
								$eyebrows = array();
								foreach ( array( 'oranje' => 'Oranje tekst', 'allesoranje' => 'Alles oranje', 'paars' => 'Alles paars', 'zacht' => 'Paars zacht' ) as $e => $naam ) {
									$k               = thema_eyebrow_kleuren( $e );
									$eyebrows[ $e ] = array( 'naam' => $naam, 'beeld' => '<span class="eyebrow-proef" style="--e-tekst:' . esc_attr( $k[0] ) . ';--e-hoek:' . esc_attr( $k[1] ) . '">Over ons</span>' );
								}
								echo thema_keuzekaarten( 'eyebrow', $eyebrows, 'twee klein' );
								?>
							</div>
						</div>

						<div class="kaartje">
							<h3 class="kaartje-titel">Achtergrond</h3>
							<p class="kaartje-tekst">De kleur achter alle blokken zonder eigen achtergrond.</p>
							<div class="achtergrondkeuze" role="radiogroup" aria-label="Achtergrond">
								<?php foreach ( $achtergronden as $hex => $naam ) : ?>
									<button type="button" class="achtergrondstaal<?php echo strtolower( $s['achtergrond'] ) === $hex ? ' is-actief' : ''; ?>" data-achtergrond="<?php echo esc_attr( $hex ); ?>" aria-pressed="<?php echo strtolower( $s['achtergrond'] ) === $hex ? 'true' : 'false'; ?>">
										<span class="achtergrondstaal-kleur" style="background:<?php echo esc_attr( $hex ); ?>"></span><span><?php echo esc_html( $naam ); ?></span>
									</button>
								<?php endforeach; ?>
								<?php echo thema_kleurveld( 'achtergrond', 'Eigen kleur' ); ?>
							</div>
						</div>
					</section>

					<?php /* ---------------- Kleuren ---------------- */ ?>
					<section class="paneel" data-paneel="kleuren">
						<div class="paneel-kop">
							<h2>Kleuren</h2>
							<p>Geel en paars dragen de site, roze is het accent. Alle blokken lezen deze kleuren uit.</p>
						</div>

						<div class="paneel-twee">
							<div class="kaartje">
								<h3 class="kaartje-titel">Palet</h3>
								<div class="kleurenraster">
									<?php
									echo thema_kleurveld( 'kleur_geel', 'Geel' );
									echo thema_kleurveld( 'kleur_paars', 'Paars' );
									echo thema_kleurveld( 'kleur_diep', 'Diep paars' );
									echo thema_kleurveld( 'kleur_roze', 'Roze' );
									echo thema_kleurveld( 'kleur_creme', 'Crème' );
									echo thema_kleurveld( 'kleur_zand', 'Zand' );
									?>
								</div>
							</div>

							<aside class="voorbeeld" data-voorbeeld="kleuren">
								<p class="voorbeeld-label">Voorbeeld</p>
								<div class="proefsite">
									<div class="proefsite-geel">
										<span class="proefsite-kop">Partybus huren in <span class="proefsite-pil">Tilburg?</span></span>
										<span class="proefsite-knop">Plan je feestje <span class="proefsite-knop-pijl"></span></span>
									</div>
									<div class="proefsite-band">Offerte in 2 werkdagen <em>Volle bak gezelligheid</em></div>
									<div class="proefsite-licht">
										<span class="eyebrow-proef">Over ons</span>
										<span class="proefsite-kop proefsite-kop-klein">Busje in, <em>sfeertje aan!</em></span>
										<span class="proefsite-tegels"><span></span><span class="is-roze"></span><span class="is-paars"></span></span>
									</div>
								</div>
							</aside>
						</div>
					</section>

					<?php /* ---------------- Typografie ---------------- */ ?>
					<section class="paneel" data-paneel="typografie">
						<div class="paneel-kop">
							<h2>Typografie</h2>
							<p>Alle lettertypes staan op de eigen server. Er gaat geen verzoek naar Google.</p>
						</div>

						<div class="paneel-twee">
							<div>
								<?php
								$rollen = array(
									'font_kop'    => array( 'kop', 'Koppen', 'Grote, opvallende titels.' ),
									'font_accent' => array( 'accent', 'Accent', 'Woorden tussen *sterretjes*, labels en cijfers.' ),
									'font_tekst'  => array( 'tekst', 'Lopende tekst', 'Alinea\'s, knoppen en menu.' ),
								);
								foreach ( $rollen as $sleutel_rol => $info ) :
									?>
									<div class="kaartje">
										<h3 class="kaartje-titel"><?php echo esc_html( $info[1] ); ?> <span class="kaartje-sub"><?php echo esc_html( $info[2] ); ?></span></h3>
										<div class="fontkeuze" role="radiogroup">
											<?php foreach ( thema_fonts_voor( $info[0] ) as $sleutel => $font ) : ?>
												<label class="fontkaart">
													<input type="radio" name="<?php echo esc_attr( thema_naam( $sleutel_rol ) ); ?>" value="<?php echo esc_attr( $sleutel ); ?>" data-sleutel="<?php echo esc_attr( $sleutel_rol ); ?>" <?php checked( $s[ $sleutel_rol ], $sleutel ); ?>>
													<span class="fontkaart-proef" style="font-family:<?php echo esc_attr( thema_font_stack( $sleutel ) ); ?>;font-style:<?php echo esc_attr( 'accent' === $info[0] ? ( $font['stijl'] ?? 'normal' ) : 'normal' ); ?>">Aa</span>
													<span class="fontkaart-naam"><?php echo esc_html( $font['naam'] ); ?></span>
												</label>
											<?php endforeach; ?>
										</div>
									</div>
								<?php endforeach; ?>

								<div class="kaartje">
									<h3 class="kaartje-titel">Koppen afstellen</h3>
									<?php
									thema_veld_rij( 'Grootte', 'Ten opzichte van het ontwerp. Lettertypes worden al optisch gelijk getrokken.', thema_schuif( 'kop_grootte', '%' ) );
									thema_veld_rij( 'Dikte', 'Truculenta heeft één dikte.', thema_schuif( 'kop_gewicht', '', 100 ) );
									?>
								</div>
							</div>

							<aside class="voorbeeld" data-voorbeeld="typografie">
								<p class="voorbeeld-label">Voorbeeld</p>
								<div class="voorbeeld-vlak typeproef">
									<p class="typeproef-kop">Waar gaat jullie <em>feestje</em> naartoe?</p>
									<p class="typeproef-kop typeproef-kop-klein">Busje in, <em>sfeertje aan!</em></p>
									<p class="typeproef-tekst">Bij Lekker Geel Busje begint je feestje zodra je instapt. Met ruime zitplaatsen, een goed geluidssysteem en een gezellige gastheer of gastvrouw aan boord.</p>
									<p class="typeproef-tekst typeproef-meta">0123456789 &middot; é ë ï &middot; &euro; &amp; ?!</p>
								</div>
							</aside>
						</div>
					</section>

					<?php /* ---------------- Animaties ---------------- */ ?>
					<section class="paneel" data-paneel="animaties">
						<div class="paneel-kop">
							<h2>Animaties</h2>
							<p>Hoe koppen en elementen in beeld komen, hoe blokken uitklappen en hoe de cursor beweegt. Bezoekers die minder beweging willen, krijgen automatisch een rustige site.</p>
						</div>

						<div class="paneel-twee">
							<div>
								<div class="kaartje">
									<h3 class="kaartje-titel">Algemeen</h3>
									<?php
									thema_veld_rij( 'Animaties', 'Uit = een volledig rustige site.', thema_schakelaar( 'animaties', 'Animaties aan' ) );
									thema_veld_rij( 'Scrolleffecten', 'Foto\'s die openklappen en inzoomen, meeschuivende gelegenheden en de gele afsluiter.', thema_schakelaar( 'scroll_effecten', 'Scrolleffecten aan' ) );
									thema_veld_rij( 'Vloeiend scrollen', 'Zachter scrollgedrag met Lenis, lokaal geladen.', thema_schakelaar( 'vloeiend_scrollen', 'Vloeiend scrollen' ) );
									?>
								</div>

								<div class="kaartje">
									<h3 class="kaartje-titel">Koppen</h3>
									<?php
									$opties = array();
									foreach ( array( 'geen' => 'Geen', 'masker' => 'Masker', 'woorden' => 'Per woord', 'letters' => 'Per letter', 'omhoog' => 'Omhoog', 'vervaging' => 'Scherpstellen' ) as $k => $naam ) {
										$opties[ $k ] = array( 'naam' => $naam, 'beeld' => thema_animatie_proef( 'kop', $k ) );
									}
									echo thema_keuzekaarten( 'kop_animatie', $opties, 'drie animatiekaarten' );
									?>
								</div>

								<div class="kaartje">
									<h3 class="kaartje-titel">Elementen</h3>
									<?php
									$opties = array();
									foreach ( array( 'geen' => 'Geen', 'vervagen' => 'Vervagen', 'omhoog' => 'Omhoog', 'schalen' => 'Schalen', 'schuiven' => 'Van opzij', 'kantelen' => 'Kantelen' ) as $k => $naam ) {
										$opties[ $k ] = array( 'naam' => $naam, 'beeld' => thema_animatie_proef( 'element', $k ) );
									}
									echo thema_keuzekaarten( 'element_animatie', $opties, 'drie animatiekaarten' );
									?>
								</div>

								<div class="kaartje">
									<h3 class="kaartje-titel">Timing</h3>
									<?php
									thema_veld_rij( 'Duur', 'Hoe lang een kop of element erover doet.', thema_schuif( 'animatie_duur', 'ms', 50 ) );
									thema_veld_rij( 'Na elkaar', 'Vertraging tussen woorden, letters en elementen.', thema_schuif( 'animatie_vertraging', 'ms', 10 ) );
									thema_veld_rij( 'Beweging', '', thema_segmenten( 'animatie_easing', array( 'zacht' => 'Zacht', 'veer' => 'Verend', 'strak' => 'Strak', 'lineair' => 'Lineair' ) ) );
									?>
								</div>

								<div class="kaartje">
									<h3 class="kaartje-titel">Uitklappen</h3>
									<?php
									thema_veld_rij( 'Voordelen', 'Hoe de tekst van een voordeel openklapt.', thema_segmenten( 'voordelen_uitklap', array( 'soepel' => 'Soepel', 'veer' => 'Veer' ) ) );
									thema_veld_rij( 'Vragen', '', thema_segmenten( 'faq_uitklap', array( 'direct' => 'Direct', 'soepel' => 'Soepel', 'veer' => 'Veer' ) ) );
									thema_veld_rij( 'Eén tegelijk', 'Andere vragen klappen dicht als je er een opent. De eerste staat open.', thema_schakelaar( 'faq_een_open', 'Eén vraag tegelijk open' ) );
									?>
								</div>

								<div class="kaartje">
									<h3 class="kaartje-titel">Lopende banden</h3>
									<?php
									thema_veld_rij( 'Snelheid lopende band', 'Het blok Lopende band en de plaatsen bij Werkgebied. 100% is de standaard, hoger is sneller.', thema_schuif( 'band_snelheid', '%', 5 ) );
									thema_veld_rij( 'Snelheid reviews', 'De lopende reviewkaarten, groot en compact. 100% is de standaard, hoger is sneller.', thema_schuif( 'reviews_snelheid', '%', 5 ) );
									?>
								</div>

								<div class="kaartje">
									<h3 class="kaartje-titel">Hero en cursor</h3>
									<?php
									thema_veld_rij( 'Plaats wisselt elke', 'De plaatsnaam in het label van de hero.', thema_schuif( 'plaats_interval', 'ms', 100 ) );
									thema_veld_rij( 'Foto wisselt elke', 'Bij de hero Fotobreed en Collage.', thema_schuif( 'hero_interval', 'ms', 500 ) );
									thema_veld_rij( 'Cursor', 'Alleen met een muis. Op telefoons blijft de gewone aanraking.', thema_segmenten( 'cursor', array( 'ring' => 'Subtiel', 'ster' => 'Ster', 'uit' => 'Standaard' ) ) );
									?>
								</div>
							</div>

							<aside class="voorbeeld" data-voorbeeld="animaties">
								<div class="voorbeeld-balk">
									<p class="voorbeeld-label">Voorbeeld</p>
									<button type="button" class="knopje knopje-klein" data-opnieuw><?php echo thema_icoon( 'speel', 14 ); ?>Opnieuw</button>
								</div>
								<div class="voorbeeld-vlak voorbeeld-podium" data-podium>
									<p class="kop voorbeeld-kop" data-kop>Jouw feest begint <span class="accent">in de bus!</span></p>
									<div class="voorbeeld-kaartjes">
										<span class="voorbeeld-kaartje" data-animeer><span class="voorbeeld-kaartje-beeld"></span>Vrijgezellen</span>
										<span class="voorbeeld-kaartje" data-animeer><span class="voorbeeld-kaartje-beeld"></span>Bedrijfsuitje</span>
										<span class="voorbeeld-kaartje" data-animeer><span class="voorbeeld-kaartje-beeld"></span>Bruiloft</span>
									</div>
									<div class="faq-lijst" data-accordeon data-een-open>
										<div class="faq-item is-open">
											<button type="button" class="faq-vraag" aria-expanded="true"><span>Mogen we zelf drinken meenemen?</span><span class="faq-plus" aria-hidden="true">+</span></button>
											<div class="faq-antwoord"><div class="faq-antwoord-binnen"><p>Nee, de gastheer of gastvrouw verzorgt de drankjes aan boord.</p></div></div>
										</div>
										<div class="faq-item">
											<button type="button" class="faq-vraag" aria-expanded="false"><span>Rijden jullie ook buiten Brabant?</span><span class="faq-plus" aria-hidden="true">+</span></button>
											<div class="faq-antwoord"><div class="faq-antwoord-binnen"><p>Zeker. Door heel Nederland en zelfs tot in Oostenrijk.</p></div></div>
										</div>
									</div>
								</div>
							</aside>
						</div>
					</section>

					<?php /* ---------------- Footer ---------------- */ ?>
					<section class="paneel" data-paneel="footer">
						<div class="paneel-kop">
							<h2>Footer</h2>
							<p>De kolommen vul je met de menu's <em>Footer kolom 1 tot 3</em> en <em>Juridisch</em>. De menunaam is de kop van de kolom.</p>
						</div>

						<div class="kaartje">
							<h3 class="kaartje-titel">Oproep bovenin de footer</h3>
							<?php
							thema_veld_rij( 'Kop', 'Zet woorden tussen *sterretjes* voor het gele accent.', thema_tekstveld( 'footer_cta_kop' ) );
							thema_veld_rij( 'Tekst', '', thema_tekstveld( 'footer_cta_tekst', 'textarea' ) );
							?>
						</div>

						<div class="kaartje">
							<h3 class="kaartje-titel">Teksten</h3>
							<div class="veldenraster">
								<?php
								thema_veld_rij( 'Onder het logo', '', thema_tekstveld( 'footer_tekst', 'textarea' ) );
								thema_veld_rij( 'Copyright', '{jaar} wordt het huidige jaar.', thema_tekstveld( 'footer_copy' ) );
								thema_veld_rij( 'Maker', '', thema_schakelaar( 'footer_maker', 'Vermelding Creators Connect' ) );
								?>
							</div>
						</div>

						<div class="kaartje">
							<h3 class="kaartje-titel">Social</h3>
							<div class="veldenraster">
								<?php
								thema_veld_rij( 'Instagram', '', thema_tekstveld( 'instagram', 'url', 'https://www.instagram.com/...' ) );
								thema_veld_rij( 'Facebook', '', thema_tekstveld( 'facebook', 'url', 'https://www.facebook.com/...' ) );
								thema_veld_rij( 'TikTok', '', thema_tekstveld( 'tiktok', 'url', 'https://www.tiktok.com/@...' ) );
								thema_veld_rij( 'YouTube', '', thema_tekstveld( 'youtube', 'url', 'https://www.youtube.com/@...' ) );
								?>
							</div>
						</div>
					</section>
				</form>

				<?php /* ---------------- Gereedschap (eigen formulieren) ---------------- */ ?>
				<section class="paneel" data-paneel="gereedschap">
					<div class="paneel-kop">
						<h2>Gereedschap</h2>
						<p>Handig bij het opzetten en verhuizen van de site.</p>
					</div>

					<div class="kaartje">
						<h3 class="kaartje-titel">Inhoud uit het ontwerp</h3>
						<p class="kaartje-tekst">Vult de homepage, de pagina Gelegenheden, vijf gelegenheden, contact, de juridische pagina's en de menu's met de teksten en foto's uit het ontwerp. Bestaande pagina's met dezelfde naam krijgen de nieuwe blokken.</p>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-bevestig="De blokken van deze pagina's worden vervangen. Doorgaan?">
							<input type="hidden" name="action" value="thema_voorbeeld">
							<?php wp_nonce_field( 'thema_voorbeeld' ); ?>
							<button type="submit" class="knopje knopje-hoofd"><?php echo thema_icoon( 'vonk' ); ?>Inhoud plaatsen</button>
						</form>
					</div>

					<div class="kaartje">
						<h3 class="kaartje-titel">Back-up</h3>
						<p class="kaartje-tekst">Kopieer de instellingen naar een andere omgeving, bijvoorbeeld van staging naar live.</p>
						<textarea class="invoer code" rows="5" readonly onclick="this.select()"><?php echo esc_textarea( wp_json_encode( $s ) ); ?></textarea>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="importformulier">
							<input type="hidden" name="action" value="thema_import">
							<?php wp_nonce_field( 'thema_import' ); ?>
							<textarea class="invoer code" name="instellingen" rows="3" placeholder="Plak hier instellingen om te importeren"></textarea>
							<button type="submit" class="knopje">Importeren</button>
						</form>
					</div>

					<div class="kaartje kaartje-gevaar">
						<h3 class="kaartje-titel">Standaardwaarden</h3>
						<p class="kaartje-tekst">Zet alle thema-instellingen terug naar het ontwerp. Pagina's en blokken blijven staan.</p>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-bevestig="Alle instellingen terugzetten?">
							<input type="hidden" name="action" value="thema_reset">
							<?php wp_nonce_field( 'thema_reset' ); ?>
							<button type="submit" class="knopje knopje-gevaar">Terugzetten</button>
						</form>
					</div>
				</section>
			</div>
		</div>

		<div class="opslaanbalk" data-opslaanbalk>
			<span><?php echo thema_icoon( 'vonk' ); ?>Je hebt wijzigingen die nog niet zijn opgeslagen.</span>
			<button type="submit" form="thema-formulier" class="knopje knopje-hoofd">Opslaan</button>
		</div>
	</div>
	<?php
}

/* --------------------------------------------------------------------------
 * Acties uit het gereedschap.
 * ----------------------------------------------------------------------- */

function thema_terug( $melding ) {
	wp_safe_redirect( admin_url( 'admin.php?page=thema-instellingen&melding=' . $melding . '#gereedschap' ) );
	exit;
}

add_action( 'admin_post_thema_reset', function () {
	check_admin_referer( 'thema_reset' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Geen toegang.' );
	}
	update_option( THEMA_OPTIE, thema_standaard() );
	thema_terug( 'reset' );
} );

add_action( 'admin_post_thema_import', function () {
	check_admin_referer( 'thema_import' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Geen toegang.' );
	}
	$data = json_decode( wp_unslash( $_POST['instellingen'] ?? '' ), true );
	if ( ! is_array( $data ) ) {
		thema_terug( 'fout' );
	}
	update_option( THEMA_OPTIE, thema_opschonen( wp_parse_args( $data, thema_standaard() ) ) );
	thema_terug( 'import' );
} );

add_action( 'admin_post_thema_voorbeeld', function () {
	check_admin_referer( 'thema_voorbeeld' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Geen toegang.' );
	}
	$gelukt = thema_plaats_voorbeeldinhoud();
	thema_terug( $gelukt ? 'voorbeeld' : 'fout' );
} );

/**
 * Snelkoppeling in de adminbalk.
 */
add_action( 'admin_bar_menu', function ( $balk ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$balk->add_node( array(
		'id'    => 'thema-instellingen',
		'title' => 'Thema-instellingen',
		'href'  => admin_url( 'admin.php?page=thema-instellingen' ),
	) );
}, 80 );
