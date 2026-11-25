<?php
/**
 * Inzichten: hoe presteren de formulieren, de WhatsApp-chat en de externe links?
 *
 * - Telt per dag hoe vaak iets gebeurt (geen losse bezoekers, geen cookies, geen IP-adressen, geen ingevulde waarden).
 * - De browser stuurt metingen in één bundel (assets/js/inzichten.js, navigator.sendBeacon).
 *   Versturen, validatiefouten en stappen van Gravity Forms en de stoelverkoop telt de server zelf.
 * - Ingelogde gebruikers tellen niet mee, zodat testen de cijfers niet vertroebelt.
 * - Het overzicht staat in WordPress onder Inzichten. Cijfers ouder dan 13 maanden worden opgeruimd.
 */

defined( 'ABSPATH' ) || exit;

const THEMA_INZICHT_DB = '1';

function thema_inzicht_tabel() {
	global $wpdb;
	return $wpdb->prefix . 'lgb_inzichten';
}

/* --------------------------------------------------------------------------
 * Tabel
 * ----------------------------------------------------------------------- */

add_action( 'init', function () {
	if ( THEMA_INZICHT_DB === get_option( 'thema_inzicht_db' ) ) {
		return;
	}
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$tabel = thema_inzicht_tabel();
	dbDelta( "CREATE TABLE {$tabel} (
		dag date NOT NULL,
		soort varchar(20) NOT NULL,
		naam varchar(120) NOT NULL,
		detail varchar(120) NOT NULL DEFAULT '',
		pagina varchar(120) NOT NULL DEFAULT '',
		aantal int unsigned NOT NULL DEFAULT 0,
		PRIMARY KEY  (dag,soort,naam,detail,pagina),
		KEY soort_dag (soort,dag)
	) {$wpdb->get_charset_collate()};" );
	update_option( 'thema_inzicht_db', THEMA_INZICHT_DB, false );
} );

/**
 * Eén telling erbij.
 */
function thema_inzicht_tel( $soort, $naam, $detail = '', $pagina = '' ) {
	global $wpdb;
	$schoon = function ( $tekst ) {
		return mb_substr( trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( (string) $tekst ) ) ), 0, 120 );
	};
	$naam = $schoon( $naam );
	if ( '' === $naam ) {
		return;
	}
	$wpdb->query( $wpdb->prepare(
		'INSERT INTO ' . thema_inzicht_tabel() . ' (dag, soort, naam, detail, pagina, aantal) VALUES (%s, %s, %s, %s, %s, 1) ON DUPLICATE KEY UPDATE aantal = aantal + 1',
		wp_date( 'Y-m-d' ),
		$schoon( $soort ),
		$naam,
		$schoon( $detail ),
		thema_inzicht_pad( $pagina )
	) );
}

/**
 * Pad van een pagina, zonder domein, zoekparameters en anker. Leeg = de verwijzende pagina.
 */
function thema_inzicht_pad( $url = '' ) {
	if ( '' === $url ) {
		$url = wp_get_referer() ?: ( $_SERVER['HTTP_REFERER'] ?? '' ); // phpcs:ignore
	}
	$pad = (string) wp_parse_url( (string) $url, PHP_URL_PATH );
	return mb_substr( '/' . trim( $pad, '/' ) . ( '' !== trim( $pad, '/' ) ? '/' : '' ), 0, 120 );
}

/**
 * Tellen we dit verzoek mee? Niet voor ingelogde gebruikers en niet voor bots die zich melden.
 */
function thema_inzicht_meetellen() {
	if ( is_user_logged_in() && ! thema_inzicht_testmodus() ) {
		return false;
	}
	$agent = strtolower( (string) ( $_SERVER['HTTP_USER_AGENT'] ?? '' ) ); // phpcs:ignore
	return '' !== $agent && ! preg_match( '/bot|crawl|spider|slurp|lighthouse|headless|preview/', $agent );
}

/**
 * Testmodus: twee uur lang tellen ook ingelogde gebruikers mee, om alles zelf na te lopen.
 */
function thema_inzicht_testmodus() {
	return (int) get_transient( 'thema_inzicht_test' ) > time();
}

/* --------------------------------------------------------------------------
 * Ontvangen uit de browser
 * ----------------------------------------------------------------------- */

add_action( 'rest_api_init', function () {
	register_rest_route( 'lgb/v1', '/inzicht', array(
		'methods'             => 'POST',
		'permission_callback' => '__return_true',
		'callback'            => function ( WP_REST_Request $verzoek ) {
			$thuis = wp_parse_url( home_url(), PHP_URL_HOST );
			$bron  = wp_parse_url( (string) ( $verzoek->get_header( 'origin' ) ?: $verzoek->get_header( 'referer' ) ), PHP_URL_HOST );
			if ( $bron !== $thuis || ! thema_inzicht_meetellen() ) {
				return new WP_REST_Response( null, 204 );
			}
			$data = json_decode( (string) $verzoek->get_body(), true );
			if ( ! is_array( $data ) || empty( $data['e'] ) || ! is_array( $data['e'] ) ) {
				return new WP_REST_Response( null, 204 );
			}
			$pad = thema_inzicht_pad( (string) ( $data['p'] ?? '' ) );
			foreach ( array_slice( $data['e'], 0, 25 ) as $meting ) {
				if ( ! is_array( $meting ) || ! in_array( $meting[0] ?? '', array( 'formulier', 'veld', 'stap', 'chat', 'link', 'contact' ), true ) ) {
					continue;
				}
				thema_inzicht_tel( $meting[0], (string) ( $meting[1] ?? '' ), (string) ( $meting[2] ?? '' ), $pad );
			}
			return new WP_REST_Response( null, 204 );
		},
	) );
} );

add_action( 'wp_enqueue_scripts', function () {
	if ( is_user_logged_in() && ! thema_inzicht_testmodus() ) {
		return;
	}
	wp_enqueue_script( 'thema-inzichten', THEMA_URL . '/assets/js/inzichten.js', array(), THEMA_VERSIE, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	wp_add_inline_script( 'thema-inzichten', 'window.lgbInzichtUrl=' . wp_json_encode( rest_url( 'lgb/v1/inzicht' ) ) . ';', 'before' );
} );

/* --------------------------------------------------------------------------
 * Tellen op de server
 * ----------------------------------------------------------------------- */

/* Gravity Forms: stap verder, verstuurd en welke velden een foutmelding gaven. */
add_action( 'gform_post_paging', function ( $form, $van, $naar ) {
	if ( thema_inzicht_meetellen() && (int) $naar > (int) $van ) {
		thema_inzicht_tel( 'formulier', 'gf:' . $form['id'], 'pagina:' . (int) $naar );
	}
}, 10, 3 );

add_action( 'gform_after_submission', function ( $entry, $form ) {
	if ( thema_inzicht_meetellen() ) {
		thema_inzicht_tel( 'formulier', 'gf:' . $form['id'], 'verstuurd', (string) ( $entry['source_url'] ?? '' ) );
		thema_inzicht_tel( 'formulier', 'gf:' . $form['id'], 'apparaat:verstuurd:' . thema_inzicht_apparaat(), (string) ( $entry['source_url'] ?? '' ) );
	}
}, 10, 2 );

add_filter( 'gform_validation', function ( $uitkomst ) {
	if ( ! empty( $uitkomst['is_valid'] ) || ! thema_inzicht_meetellen() ) {
		return $uitkomst;
	}
	foreach ( (array) ( $uitkomst['form']['fields'] ?? array() ) as $veld ) {
		if ( ! empty( $veld->failed_validation ) ) {
			thema_inzicht_tel( 'formulier', 'gf:' . $uitkomst['form']['id'], 'fout:' . wp_strip_all_tags( (string) $veld->label ) );
			thema_inzicht_tel( 'veld', 'gf:' . $uitkomst['form']['id'], max( 1, (int) $veld->pageNumber ) . '|' . thema_inzicht_veldlabel( $veld->label ) . '|fout' );
		}
	}
	return $uitkomst;
}, 99 );

/* Stoelverkoop: een nieuwe boeking, en welke velden fout gingen. */
add_action( 'added_post_meta', function ( $meta_id, $post_id, $sleutel ) {
	if ( '_stoel_aantal' === $sleutel && thema_inzicht_meetellen() ) {
		thema_inzicht_tel( 'formulier', 'stoelen', 'verstuurd' );
		thema_inzicht_tel( 'formulier', 'stoelen', 'apparaat:verstuurd:' . thema_inzicht_apparaat() );
	}
}, 10, 3 );

add_action( 'setted_transient', function ( $naam, $waarde ) {
	if ( 0 !== strpos( (string) $naam, 'thema_stoelen_' ) || empty( $waarde['fouten'] ) || ! thema_inzicht_meetellen() ) {
		return;
	}
	foreach ( array_keys( (array) $waarde['fouten'] ) as $veld ) {
		thema_inzicht_tel( 'formulier', 'stoelen', 'fout:' . $veld );
		$namen = array( 'rit' => 'Kies je avond', 'opstap' => 'Waar stap je in?', 'aantal' => 'Hoeveel stoelen?', 'naam' => 'Je naam', 'telefoon' => 'Mobiel nummer', 'email' => 'E-mailadres', 'akkoord' => 'Akkoord met de huisregels' );
		thema_inzicht_tel( 'veld', 'stoelen', '1|' . ( $namen[ $veld ] ?? $veld ) . '|fout' );
	}
}, 10, 2 );

/**
 * Mobiel of desktop, voor tellingen op de server.
 */
function thema_inzicht_apparaat() {
	return wp_is_mobile() ? 'mobiel' : 'desktop';
}

/**
 * Label van een veld zoals het in de browser wordt gemeten: zonder opmaak, zonder | en maximaal 70 tekens.
 */
function thema_inzicht_veldlabel( $label ) {
	return mb_substr( trim( preg_replace( '/\s+/u', ' ', str_replace( '|', '/', wp_strip_all_tags( (string) $label ) ) ) ), 0, 70 );
}

/* Opruimen: één keer per dag, alles ouder dan 13 maanden. */
add_action( 'init', function () {
	if ( ! wp_next_scheduled( 'thema_inzicht_opruimen' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'thema_inzicht_opruimen' );
	}
} );
add_action( 'thema_inzicht_opruimen', function () {
	global $wpdb;
	$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . thema_inzicht_tabel() . ' WHERE dag < %s', wp_date( 'Y-m-d', strtotime( '-13 months' ) ) ) );
} );

/* --------------------------------------------------------------------------
 * Overzicht in WordPress
 * ----------------------------------------------------------------------- */

add_action( 'admin_post_thema_inzicht_test', function () {
	check_admin_referer( 'thema_inzicht_test' );
	if ( ! current_user_can( 'edit_pages' ) ) {
		wp_die( 'Dat mag niet.' );
	}
	if ( thema_inzicht_testmodus() ) {
		delete_transient( 'thema_inzicht_test' );
	} else {
		set_transient( 'thema_inzicht_test', time() + 2 * HOUR_IN_SECONDS, 2 * HOUR_IN_SECONDS );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=thema-inzichten' ) );
	exit;
} );

add_action( 'admin_post_thema_inzicht_wissen', function () {
	check_admin_referer( 'thema_inzicht_wissen' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Dat mag niet.' );
	}
	global $wpdb;
	$wpdb->query( 'TRUNCATE TABLE ' . thema_inzicht_tabel() ); // phpcs:ignore
	wp_safe_redirect( admin_url( 'admin.php?page=thema-inzichten&gewist=1' ) );
	exit;
} );

/**
 * Het Gravity Forms-formulier achter het contactformulier vraag voor vraag (blok Formulier op de pagina Contact).
 */
function thema_inzicht_contactformulier() {
	$pagina = get_page_by_path( 'contact' );
	if ( ! $pagina || ! function_exists( 'get_field' ) ) {
		return 0;
	}
	foreach ( (array) get_field( 'blokken', $pagina->ID, false ) as $blok ) {
		foreach ( (array) $blok as $sleutel => $waarde ) {
			if ( preg_match( '/formulier$/', (string) $sleutel ) && is_numeric( $waarde ) ) {
				return (int) $waarde;
			}
		}
	}
	return 0;
}

add_action( 'admin_menu', function () {
	add_menu_page( 'Inzichten', 'Inzichten', 'edit_pages', 'thema-inzichten', 'thema_inzicht_pagina', 'dashicons-chart-bar', 3 );
} );

/**
 * Leesbare naam van een formulier.
 */
function thema_inzicht_formuliernaam( $naam ) {
	if ( function_exists( 'thema_offerte_velden' ) && 'gf:' . thema_offerte_velden()['formulier'] === $naam ) {
		return 'Offerteformulier';
	}
	if ( 0 === strpos( $naam, 'gf:' ) && class_exists( 'GFAPI' ) ) {
		$form = GFAPI::get_form( (int) substr( $naam, 3 ) );
		return $form ? $form['title'] : $naam;
	}
	$namen = array(
		'stoelen'    => 'Stoelen Vrienden van Amstel LIVE',
		'offertezin' => 'Snelle aanvraag (zin op de pagina)',
	);
	return $namen[ $naam ] ?? $naam;
}

function thema_inzicht_cijfers( $soort, $van, $groep = 'naam, detail' ) {
	global $wpdb;
	return $wpdb->get_results( $wpdb->prepare(
		"SELECT {$groep}, SUM(aantal) AS aantal FROM " . thema_inzicht_tabel() . " WHERE soort = %s AND dag BETWEEN %s AND %s GROUP BY {$groep} ORDER BY aantal DESC", // phpcs:ignore
		$soort,
		$van,
		thema_inzicht_periode()['tot']
	) );
}

function thema_inzicht_balk( $waarde, $max ) {
	$breed = $max ? max( 2, round( 100 * $waarde / $max ) ) : 0;
	return '<span class="inzicht-balk"><span style="width:' . (int) $breed . '%"></span></span>';
}

function thema_inzicht_procent( $deel, $geheel ) {
	return $geheel ? round( 100 * $deel / $geheel ) . '%' : 'n.v.t.';
}

function thema_inzicht_pagina() {
	$periode = thema_inzicht_periode();
	$van     = $periode['van'];
	$dagen   = $periode['args'];
	$detail = sanitize_text_field( wp_unslash( $_GET['formulier'] ?? '' ) ); // phpcs:ignore
	if ( preg_match( '/^(gf:\d+|stoelen|offertezin|contact)$/', $detail ) ) {
		thema_inzicht_detail( $detail, $van, $dagen );
		return;
	}

	/* Formulieren per naam. */
	$form = array();
	foreach ( thema_inzicht_cijfers( 'formulier', $van ) as $r ) {
		$form[ $r->naam ][ $r->detail ] = (int) $r->aantal;
	}
	/* Elk formulier altijd tonen, ook zonder metingen. Het contactformulier staat in een eigen kaart. */
	$contact_gf = thema_inzicht_contactformulier();
	$basis      = array();
	if ( class_exists( 'GFAPI' ) ) {
		foreach ( GFAPI::get_forms() as $f ) {
			if ( (int) $f['id'] !== $contact_gf ) {
				$basis[ 'gf:' . $f['id'] ] = array();
			}
		}
	}
	$basis['stoelen'] = array();
	$contact_verstuurd = (int) ( $form[ 'gf:' . $contact_gf ]['verstuurd'] ?? 0 );
	$contact_fouten    = array();
	foreach ( (array) ( $form[ 'gf:' . $contact_gf ] ?? array() ) as $sleutel => $aantal ) {
		if ( 0 === strpos( $sleutel, 'fout:' ) ) {
			$contact_fouten[ substr( $sleutel, 5 ) ] = $aantal;
		}
	}
	unset( $form[ 'gf:' . $contact_gf ] );
	$form = array_merge( $basis, $form );

	$stappen = array();
	foreach ( thema_inzicht_cijfers( 'stap', $van ) as $r ) {
		$stappen[ $r->detail ] = (int) $r->aantal;
	}
	$chat = array();
	foreach ( thema_inzicht_cijfers( 'chat', $van ) as $r ) {
		$chat[ $r->naam ][ $r->detail ] = (int) $r->aantal;
	}
	$contact = thema_inzicht_cijfers( 'contact', $van, 'naam' );
	$links   = thema_inzicht_cijfers( 'link', $van, 'naam, pagina' );
	$per_pag = thema_inzicht_cijfers( 'link', $van, 'pagina' );

	$adres = admin_url( 'admin.php?page=thema-inzichten' );
	?>
	<div class="wrap inzichten">
		<h1>Inzichten</h1>
		<p class="inzicht-uitleg">Hoe vaak mensen formulieren invullen, waar ze afhaken, wat ze in de WhatsApp-chat doen en welke externe links ze aanklikken. Alleen tellingen per dag: geen cookies, geen persoonsgegevens. Ingelogde gebruikers tellen niet mee.</p>
		<?php thema_inzicht_periodekiezer(); ?>
		<?php thema_inzicht_overzicht_grafiek(); ?>

		<?php if ( thema_inzicht_testmodus() ) : ?>
			<div class="notice notice-warning inline"><p><strong>Testmodus staat aan</strong> tot <?php echo esc_html( wp_date( 'H:i', (int) get_transient( 'thema_inzicht_test' ) ) ); ?>: je eigen bezoeken tellen mee. Wis de cijfers na het testen.</p></div>
		<?php endif; ?>
		<?php if ( ! empty( $_GET['gewist'] ) ) : // phpcs:ignore ?>
			<div class="notice notice-success inline"><p>Alle cijfers zijn gewist.</p></div>
		<?php endif; ?>

		<h2>Formulieren</h2>
		<div class="inzicht-raster">
			<?php foreach ( $form as $naam => $d ) : ?>
				<?php
				$stap_rijen = array( 'Gezien' => $d['gezien'] ?? 0, 'Begonnen met invullen' => $d['gestart'] ?? 0 );
				foreach ( $d as $sleutel => $aantal ) {
					if ( 0 === strpos( $sleutel, 'pagina:' ) ) {
						$stap_rijen[ 'Door naar stap ' . substr( $sleutel, 7 ) ] = $aantal;
					}
				}
				$stap_rijen['Verstuurd'] = $d['verstuurd'] ?? ( 'offertezin' === $naam ? ( $d['verzonden'] ?? 0 ) : 0 );
				$max                     = max( 1, max( $stap_rijen ) );
				$fouten                  = array();
				$afhaak                  = array();
				foreach ( $d as $sleutel => $aantal ) {
					if ( 0 === strpos( $sleutel, 'fout:' ) ) {
						$fouten[ substr( $sleutel, 5 ) ] = $aantal;
					} elseif ( 0 === strpos( $sleutel, 'afgehaakt:' ) ) {
						$afhaak[ substr( $sleutel, 10 ) ] = $aantal;
					}
				}
				arsort( $fouten );
				arsort( $afhaak );
				/* Grootste val tussen twee stappen. */
				$val   = '';
				$vorig = null;
				$grootst = 0;
				foreach ( $stap_rijen as $label => $aantal ) {
					if ( null !== $vorig && $vorig[1] > 0 && ( $vorig[1] - $aantal ) / $vorig[1] > $grootst ) {
						$grootst = ( $vorig[1] - $aantal ) / $vorig[1];
						$val     = $vorig[0] . ' → ' . $label;
					}
					$vorig = array( $label, $aantal );
				}
				?>
				<div class="inzicht-kaart">
					<h3><?php echo esc_html( thema_inzicht_formuliernaam( $naam ) ); ?></h3>
					<p class="inzicht-kern">
						<strong><?php echo esc_html( thema_inzicht_procent( $stap_rijen['Verstuurd'], $stap_rijen['Begonnen met invullen'] ) ); ?></strong> van wie begint, verstuurt
						<?php if ( $val && $grootst > 0 ) : ?><br><span>Grootste uitval: <?php echo esc_html( $val ); ?> (<?php echo esc_html( round( $grootst * 100 ) ); ?>%)</span><?php endif; ?>
					</p>
					<table class="widefat striped">
						<tbody>
							<?php foreach ( $stap_rijen as $label => $aantal ) : ?>
								<tr><td><?php echo esc_html( $label ); ?></td><td class="inzicht-getal"><?php echo (int) $aantal; ?></td><td><?php echo thema_inzicht_balk( $aantal, $max ); // phpcs:ignore ?></td></tr>
							<?php endforeach; ?>
						</tbody>
					</table>
					<?php if ( $afhaak ) : ?>
						<h4>Afgehaakt bij (laatst aangeraakte veld)</h4>
						<table class="widefat striped"><tbody>
							<?php foreach ( array_slice( $afhaak, 0, 8, true ) as $label => $aantal ) : ?>
								<tr><td><?php echo esc_html( $label ); ?></td><td class="inzicht-getal"><?php echo (int) $aantal; ?></td></tr>
							<?php endforeach; ?>
						</tbody></table>
					<?php endif; ?>
					<?php if ( $fouten ) : ?>
						<h4>Foutmeldingen per veld</h4>
						<table class="widefat striped"><tbody>
							<?php foreach ( array_slice( $fouten, 0, 8, true ) as $label => $aantal ) : ?>
								<tr><td><?php echo esc_html( $label ); ?></td><td class="inzicht-getal"><?php echo (int) $aantal; ?></td></tr>
							<?php endforeach; ?>
						</tbody></table>
					<?php endif; ?>
					<a class="inzicht-details" href="<?php echo esc_url( add_query_arg( array_merge( $dagen, array( 'formulier' => $naam ) ), $adres ) ); ?>">Bekijk per stap en per vraag &rarr;</a>
				</div>
			<?php endforeach; ?>

			<?php if ( $contact_gf || $stappen ) : ?>
				<?php
				$namen = array(
					'keuze' => 'Eerste vraag geladen', 'vraag' => 'Vraag typen', 'prijs' => 'Koos: prijs (naar offerte)', 'direct' => 'Koos: bellen of appen',
					'naam' => 'Naam', 'email' => 'E-mailadres', 'telefoon' => 'Telefoonnummer', 'bezig' => 'Versturen', 'bedankt' => 'Verstuurd', 'faq-bedankt' => 'Antwoord gevonden in de veelgestelde vragen',
				);
				$gewoon  = array_filter( $stappen, fn( $k ) => false === strpos( $k, ':' ), ARRAY_FILTER_USE_KEY );
				$afhaken = array_filter( $stappen, fn( $k ) => 0 === strpos( $k, 'afgehaakt:' ), ARRAY_FILTER_USE_KEY );
				$max     = max( 1, max( $gewoon ?: array( 1 ) ) );
				?>
				<div class="inzicht-kaart">
					<h3>Contactformulier vraag voor vraag</h3>
					<?php
					$begin = 0;
					foreach ( $gewoon as $id => $aantal ) {
						if ( ! in_array( $id, array( 'keuze', 'bezig', 'bedankt', 'faq-bedankt' ), true ) ) {
							$begin = max( $begin, $aantal );
						}
					}
					?>
					<p class="inzicht-kern"><strong><?php echo esc_html( thema_inzicht_procent( $contact_verstuurd, $begin ) ); ?></strong> van wie begint, verstuurt (<?php echo (int) $contact_verstuurd; ?> verstuurd)</p>
					<?php if ( ! $gewoon ) : ?>
						<p>Nog geen metingen in deze periode.</p>
					<?php endif; ?>
					<table class="widefat striped"><tbody>
						<?php foreach ( $gewoon as $id => $aantal ) : ?>
							<tr><td><?php echo esc_html( $namen[ $id ] ?? $id ); ?></td><td class="inzicht-getal"><?php echo (int) $aantal; ?></td><td><?php echo thema_inzicht_balk( $aantal, $max ); // phpcs:ignore ?></td></tr>
						<?php endforeach; ?>
					</tbody></table>
					<a class="inzicht-details" href="<?php echo esc_url( add_query_arg( array_merge( $dagen, array( 'formulier' => 'contact' ) ), $adres ) ); ?>">Bekijk per stap &rarr;</a>
					<?php if ( $contact_fouten ) : ?>
						<h4>Foutmeldingen per veld</h4>
						<table class="widefat striped"><tbody>
							<?php foreach ( $contact_fouten as $label => $aantal ) : ?>
								<tr><td><?php echo esc_html( $label ); ?></td><td class="inzicht-getal"><?php echo (int) $aantal; ?></td></tr>
							<?php endforeach; ?>
						</tbody></table>
					<?php endif; ?>
					<?php if ( $afhaken ) : ?>
						<h4>Afgehaakt bij</h4>
						<table class="widefat striped"><tbody>
							<?php foreach ( $afhaken as $id => $aantal ) : ?>
								<?php $stap = substr( $id, 10 ); ?>
								<tr><td><?php echo esc_html( $namen[ $stap ] ?? $stap ); ?></td><td class="inzicht-getal"><?php echo (int) $aantal; ?></td></tr>
							<?php endforeach; ?>
						</tbody></table>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>

		<h2>WhatsApp-chat</h2>
		<?php
		$open   = array_sum( $chat['whatsapp_venster'] ?? array() );
		$naar   = array_sum( $chat['whatsapp_chat'] ?? array() );
		$offer  = array_sum( $chat['whatsapp_offerte'] ?? array() );
		$keuzes = $chat['whatsapp_keuze'] ?? array();
		arsort( $keuzes );
		$max = max( 1, $open );
		?>
		<div class="inzicht-raster">
			<div class="inzicht-kaart">
				<h3>Gesprekken</h3>
				<p class="inzicht-kern"><strong><?php echo esc_html( thema_inzicht_procent( $naar + $offer, $open ) ); ?></strong> van de geopende chats eindigt in WhatsApp of het offerteformulier</p>
				<table class="widefat striped"><tbody>
					<tr><td>Chat geopend</td><td class="inzicht-getal"><?php echo (int) $open; ?></td><td><?php echo thema_inzicht_balk( $open, $max ); // phpcs:ignore ?></td></tr>
					<tr><td>Een keuze gemaakt of iets getypt</td><td class="inzicht-getal"><?php echo (int) array_sum( $keuzes ); ?></td><td><?php echo thema_inzicht_balk( array_sum( $keuzes ), $max ); // phpcs:ignore ?></td></tr>
					<tr><td>Doorgestuurd naar WhatsApp</td><td class="inzicht-getal"><?php echo (int) $naar; ?></td><td><?php echo thema_inzicht_balk( $naar, $max ); // phpcs:ignore ?></td></tr>
					<tr><td>Doorgestuurd naar de offerte</td><td class="inzicht-getal"><?php echo (int) $offer; ?></td><td><?php echo thema_inzicht_balk( $offer, $max ); // phpcs:ignore ?></td></tr>
					<tr><td>Gesloten zonder vervolg</td><td class="inzicht-getal"><?php echo (int) max( 0, $open - $naar - $offer ); ?></td><td><?php echo thema_inzicht_balk( max( 0, $open - $naar - $offer ), $max ); // phpcs:ignore ?></td></tr>
				</tbody></table>
			</div>
			<div class="inzicht-kaart">
				<h3>Gekozen onderwerpen</h3>
				<?php if ( $keuzes ) : ?>
					<table class="widefat striped"><tbody>
						<?php foreach ( $keuzes as $keuze => $aantal ) : ?>
							<tr><td><?php echo esc_html( 'direct' === $keuze ? 'Meteen zelf getypt' : $keuze ); ?></td><td class="inzicht-getal"><?php echo (int) $aantal; ?></td></tr>
						<?php endforeach; ?>
					</tbody></table>
				<?php else : ?>
					<p>Nog geen keuzes gemeten.</p>
				<?php endif; ?>
			</div>
			<div class="inzicht-kaart">
				<h3>Direct contact</h3>
				<?php if ( $contact ) : ?>
					<table class="widefat striped"><tbody>
						<?php foreach ( $contact as $r ) : ?>
							<tr><td><?php echo esc_html( ucfirst( $r->naam ) . ' aangeklikt' ); ?></td><td class="inzicht-getal"><?php echo (int) $r->aantal; ?></td></tr>
						<?php endforeach; ?>
					</tbody></table>
				<?php else : ?>
					<p>Nog geen klikken op bellen, mailen of WhatsApp.</p>
				<?php endif; ?>
			</div>
		</div>

		<h2>Externe links</h2>
		<div class="inzicht-raster inzicht-raster-breed">
			<div class="inzicht-kaart">
				<h3>Meest aangeklikt</h3>
				<?php if ( $links ) : ?>
					<?php $max = (int) $links[0]->aantal; ?>
					<table class="widefat striped">
						<thead><tr><th>Link</th><th>Op pagina</th><th class="inzicht-getal">Klikken</th><th></th></tr></thead>
						<tbody>
							<?php foreach ( array_slice( $links, 0, 50 ) as $r ) : ?>
								<tr>
									<td><a href="<?php echo esc_url( 'https://' . $r->naam ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $r->naam ); ?></a></td>
									<td><a href="<?php echo esc_url( home_url( $r->pagina ) ); ?>" target="_blank"><?php echo esc_html( $r->pagina ); ?></a></td>
									<td class="inzicht-getal"><?php echo (int) $r->aantal; ?></td>
									<td><?php echo thema_inzicht_balk( (int) $r->aantal, $max ); // phpcs:ignore ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php else : ?>
					<p>Nog geen klikken op externe links.</p>
				<?php endif; ?>
			</div>
			<?php if ( $per_pag ) : ?>
				<div class="inzicht-kaart">
					<h3>Klikken per pagina</h3>
					<table class="widefat striped"><tbody>
						<?php foreach ( $per_pag as $r ) : ?>
							<tr><td><?php echo esc_html( $r->pagina ); ?></td><td class="inzicht-getal"><?php echo (int) $r->aantal; ?></td></tr>
						<?php endforeach; ?>
					</tbody></table>
				</div>
			<?php endif; ?>
		</div>

		<h2>Testen</h2>
		<p class="inzicht-uitleg">Normaal tellen ingelogde gebruikers niet mee. Zet de testmodus aan om alles zelf na te lopen (twee uur, daarna gaat hij vanzelf uit), en wis de cijfers voordat de site live gaat.</p>
		<p class="inzicht-periode">
			<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=thema_inzicht_test' ), 'thema_inzicht_test' ) ); ?>"><?php echo esc_html( thema_inzicht_testmodus() ? 'Testmodus uitzetten' : 'Testmodus aanzetten (2 uur)' ); ?></a>
			<?php if ( current_user_can( 'manage_options' ) ) : ?>
				<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=thema_inzicht_wissen' ), 'thema_inzicht_wissen' ) ); ?>" onclick="return window.confirm('Alle cijfers wissen? Dit kan niet ongedaan worden.');">Alle cijfers wissen</a>
			<?php endif; ?>
		</p>
	</div>
	<?php
	thema_inzicht_stijl();
}

/* --------------------------------------------------------------------------
 * Verdieping per formulier
 * ----------------------------------------------------------------------- */

/**
 * Tellingen van één formulier (of het contactformulier vraag voor vraag) in de periode.
 */
function thema_inzicht_formulier_data( $soort, $naam, $van ) {
	global $wpdb;
	$tabel = thema_inzicht_tabel();
	$uit   = array( 'detail' => array(), 'veld' => array(), 'dag' => array(), 'pagina' => array() );
	foreach ( $wpdb->get_results( $wpdb->prepare( "SELECT detail, SUM(aantal) AS n FROM {$tabel} WHERE soort = %s AND naam = %s AND dag BETWEEN %s AND %s GROUP BY detail", $soort, $naam, $van, thema_inzicht_periode()['tot'] ) ) as $r ) { // phpcs:ignore
		$uit['detail'][ $r->detail ] = (int) $r->n;
	}
	foreach ( $wpdb->get_results( $wpdb->prepare( "SELECT detail, SUM(aantal) AS n FROM {$tabel} WHERE soort = 'veld' AND naam = %s AND dag BETWEEN %s AND %s GROUP BY detail", $naam, $van, thema_inzicht_periode()['tot'] ) ) as $r ) { // phpcs:ignore
		$delen = explode( '|', $r->detail );
		if ( 3 !== count( $delen ) ) {
			continue;
		}
		list( $stap, $label, $actie ) = $delen;
		$uit['veld'][ (int) $stap ][ $label ][ $actie ] = (int) $r->n;
	}
	foreach ( $wpdb->get_results( $wpdb->prepare( "SELECT dag, detail, SUM(aantal) AS n FROM {$tabel} WHERE soort = %s AND naam = %s AND dag BETWEEN %s AND %s GROUP BY dag, detail", $soort, $naam, $van, thema_inzicht_periode()['tot'] ) ) as $r ) { // phpcs:ignore
		$uit['dag'][ $r->dag ][ $r->detail ] = (int) $r->n;
	}
	foreach ( $wpdb->get_results( $wpdb->prepare( "SELECT pagina, detail, SUM(aantal) AS n FROM {$tabel} WHERE soort = %s AND naam = %s AND dag BETWEEN %s AND %s GROUP BY pagina, detail", $soort, $naam, $van, thema_inzicht_periode()['tot'] ) ) as $r ) { // phpcs:ignore
		$uit['pagina'][ $r->pagina ][ $r->detail ] = (int) $r->n;
	}
	return $uit;
}

/**
 * Velden van een Gravity Forms-formulier in volgorde, per pagina: array( pagina => array( label, ... ) ).
 */
function thema_inzicht_gf_velden( $form_id ) {
	$uit = array();
	if ( ! class_exists( 'GFAPI' ) ) {
		return $uit;
	}
	$form = GFAPI::get_form( (int) $form_id );
	foreach ( (array) ( $form['fields'] ?? array() ) as $veld ) {
		if ( in_array( $veld->type, array( 'hidden', 'html', 'page', 'section', 'captcha', 'honeypot' ), true ) || 'hidden' === ( $veld->visibility ?? '' ) || '' === trim( (string) $veld->label ) ) {
			continue;
		}
		$uit[ max( 1, (int) $veld->pageNumber ) ][] = thema_inzicht_veldlabel( $veld->label );
	}
	return $uit;
}

/**
 * Middelste invultijd uit de tijdklassen ("3 1 tot 2 min" => 12).
 */
function thema_inzicht_mediaan_duur( $detail ) {
	$klassen = array();
	foreach ( $detail as $sleutel => $n ) {
		if ( 0 === strpos( $sleutel, 'duur:' ) ) {
			$klassen[ substr( $sleutel, 5 ) ] = $n;
		}
	}
	if ( ! $klassen ) {
		return array( '', array() );
	}
	ksort( $klassen );
	$helft = array_sum( $klassen ) / 2;
	$teller = 0;
	$mediaan = '';
	foreach ( $klassen as $klasse => $n ) {
		$teller += $n;
		if ( $teller >= $helft ) {
			$mediaan = $klasse;
			break;
		}
	}
	$schoon = array();
	foreach ( $klassen as $klasse => $n ) {
		$schoon[ preg_replace( '/^\d+\s/', '', $klasse ) ] = $n;
	}
	return array( preg_replace( '/^\d+\s/', '', $mediaan ), $schoon );
}

function thema_inzicht_kpi( $label, $waarde, $sub = '' ) {
	return '<div class="inzicht-kpi"><span>' . esc_html( $label ) . '</span><strong>' . esc_html( $waarde ) . '</strong>' . ( $sub ? '<em>' . esc_html( $sub ) . '</em>' : '' ) . '</div>';
}

/**
 * De verdieping. $naam: gf:3, stoelen, offertezin of contact.
 */
function thema_inzicht_detail( $naam, $van, $dagen ) {
	$contact_gf = thema_inzicht_contactformulier();
	$is_contact = 'contact' === $naam;
	$d          = thema_inzicht_formulier_data( $is_contact ? 'stap' : 'formulier', $is_contact ? 'contact' : $naam, $van );
	$x          = $d['detail'];
	$terug      = add_query_arg( $dagen, admin_url( 'admin.php?page=thema-inzichten' ) );

	if ( $is_contact ) {
		$gf    = thema_inzicht_formulier_data( 'formulier', 'gf:' . $contact_gf, $van );
		$titel = 'Contactformulier vraag voor vraag';
		$gezien     = (int) ( $x['keuze'] ?? 0 );
		$gestart    = (int) array_sum( array_filter( $x, fn( $k ) => 0 === strpos( $k, 'apparaat:gestart:' ), ARRAY_FILTER_USE_KEY ) );
		$verstuurd  = (int) ( $gf['detail']['verstuurd'] ?? 0 );
		$afgehaakt  = (int) array_sum( array_filter( $x, fn( $k ) => 0 === strpos( $k, 'afgehaakt:' ), ARRAY_FILTER_USE_KEY ) );
		$fouten     = array_filter( $gf['detail'], fn( $k ) => 0 === strpos( $k, 'fout:' ), ARRAY_FILTER_USE_KEY );
		$apparaat_bron = $x + array_filter( $gf['detail'], fn( $k ) => 0 === strpos( $k, 'apparaat:verstuurd:' ), ARRAY_FILTER_USE_KEY );
	} else {
		$titel      = thema_inzicht_formuliernaam( $naam );
		$gezien     = (int) ( $x['gezien'] ?? 0 );
		$gestart    = (int) ( $x['gestart'] ?? 0 );
		$verstuurd  = (int) ( $x['verstuurd'] ?? ( 'offertezin' === $naam ? ( $x['verzonden'] ?? 0 ) : 0 ) );
		$afgehaakt  = (int) array_sum( array_filter( $x, fn( $k ) => 0 === strpos( $k, 'afhaakstap:' ), ARRAY_FILTER_USE_KEY ) );
		$fouten     = array_filter( $x, fn( $k ) => 0 === strpos( $k, 'fout:' ), ARRAY_FILTER_USE_KEY );
		$apparaat_bron = $x;
	}
	list( $mediaan, $duur ) = thema_inzicht_mediaan_duur( $x );
	?>
	<div class="wrap inzichten">
		<p><a href="<?php echo esc_url( $terug ); ?>">&larr; Terug naar Inzichten</a></p>
		<h1><?php echo esc_html( $titel ); ?></h1>
		<?php thema_inzicht_periodekiezer( $naam ); ?>

		<div class="inzicht-kpis">
			<?php
			echo thema_inzicht_kpi( $is_contact ? 'Eerste vraag geladen' : 'Gezien', (string) $gezien ); // phpcs:ignore
			echo thema_inzicht_kpi( 'Begonnen', (string) $gestart, thema_inzicht_procent( $gestart, $gezien ) . ' van wie het zag' ); // phpcs:ignore
			echo thema_inzicht_kpi( 'Verstuurd', (string) $verstuurd, thema_inzicht_procent( $verstuurd, $gestart ) . ' van wie begon' ); // phpcs:ignore
			echo thema_inzicht_kpi( 'Afgehaakt', (string) $afgehaakt, thema_inzicht_procent( $afgehaakt, $gestart ) . ' van wie begon' ); // phpcs:ignore
			echo thema_inzicht_kpi( 'Foutmeldingen', (string) array_sum( $fouten ), $verstuurd ? round( array_sum( $fouten ) / max( 1, $verstuurd ), 1 ) . ' per verzending' : '' ); // phpcs:ignore
			echo thema_inzicht_kpi( 'Invultijd (mediaan)', $mediaan ?: 'n.v.t.' ); // phpcs:ignore
			?>
		</div>

		<?php
		/* ---------- Grafiek per dag ---------- */
		$reeks = array( 'Gezien' => array(), 'Begonnen' => array(), 'Verstuurd' => array(), 'Afgehaakt' => array(), 'Vragen ingevuld' => array() );
		foreach ( $d['dag'] as $dag => $r ) {
			foreach ( $r as $k => $n ) {
				if ( $is_contact ) {
					$tak = 'keuze' === $k ? 'Gezien' : ( 0 === strpos( $k, 'apparaat:gestart:' ) ? 'Begonnen' : ( 0 === strpos( $k, 'afgehaakt:' ) ? 'Afgehaakt' : null ) );
				} else {
					$tak = 'gezien' === $k ? 'Gezien' : ( 'gestart' === $k ? 'Begonnen' : ( 'verstuurd' === $k ? 'Verstuurd' : ( 0 === strpos( $k, 'afhaakstap:' ) ? 'Afgehaakt' : null ) ) );
				}
				if ( $tak ) {
					$reeks[ $tak ][ $dag ] = ( $reeks[ $tak ][ $dag ] ?? 0 ) + $n;
				}
			}
		}
		if ( $is_contact ) {
			foreach ( $gf['dag'] as $dag => $r ) {
				$reeks['Verstuurd'][ $dag ] = (int) ( $r['verstuurd'] ?? 0 );
			}
			unset( $reeks['Vragen ingevuld'] );
		} else {
			global $wpdb;
			$per = thema_inzicht_periode();
			foreach ( $wpdb->get_results( $wpdb->prepare( 'SELECT dag, SUM(aantal) AS n FROM ' . thema_inzicht_tabel() . " WHERE soort = 'veld' AND naam = %s AND detail LIKE %s AND dag BETWEEN %s AND %s GROUP BY dag", $naam, '%|ingevuld', $per['van'], $per['tot'] ) ) as $r ) { // phpcs:ignore
				$reeks['Vragen ingevuld'][ $r->dag ] = (int) $r->n;
			}
		}
		$kleuren = thema_inzicht_kleuren();
		$lijnen  = array();
		foreach ( $reeks as $label => $data ) {
			$lijnen[ $label ] = array( 'kleur' => $kleuren[ $label ], 'data' => $data );
		}
		$per = thema_inzicht_periode();
		echo thema_inzicht_lijngrafiek( $lijnen, $per['van'], $per['tot'], 'Verloop' ); // phpcs:ignore

		/* ---------- Stappen ---------- */
		if ( $is_contact ) {
			$route = array(
				'keuze' => 'Eerste vraag: waarmee kunnen we helpen?', 'vraag' => 'Vraag typen', 'prijs' => 'Koos: een prijs (naar offerte)', 'direct' => 'Koos: bellen of appen',
				'naam' => 'Naam', 'email' => 'E-mailadres', 'telefoon' => 'Telefoonnummer', 'bezig' => 'Versturen', 'bedankt' => 'Verstuurd', 'faq-bedankt' => 'Antwoord gevonden in de veelgestelde vragen',
			);
			$rijen = array();
			foreach ( $route as $id => $label ) {
				$rijen[] = array( $label, (int) ( $x[ $id ] ?? 0 ), (int) ( $x[ 'afgehaakt:' . $id ] ?? 0 ) );
			}
			foreach ( $x as $id => $n ) {
				if ( false === strpos( $id, ':' ) && ! isset( $route[ $id ] ) ) {
					$rijen[] = array( $id, $n, (int) ( $x[ 'afgehaakt:' . $id ] ?? 0 ) );
				}
			}
			$max = max( 1, max( array_column( $rijen, 1 ) ) );
			?>
			<h2>Per stap</h2>
			<p class="inzicht-uitleg">Hoe vaak elke vraag in beeld kwam, en hoe vaak iemand bij die vraag de pagina verliet zonder te versturen.</p>
			<table class="widefat striped inzicht-tabel">
				<thead><tr><th>Stap</th><th class="inzicht-getal">Bereikt</th><th></th><th class="inzicht-getal">Afgehaakt hier</th><th class="inzicht-getal">% afgehaakt</th></tr></thead>
				<tbody>
					<?php foreach ( $rijen as $r ) : ?>
						<tr class="<?php echo $r[1] && $r[2] / max( 1, $r[1] ) >= .25 ? 'inzicht-let-op' : ''; ?>"><td><?php echo esc_html( $r[0] ); ?></td><td class="inzicht-getal"><?php echo (int) $r[1]; ?></td><td><?php echo thema_inzicht_balk( $r[1], $max ); // phpcs:ignore ?></td><td class="inzicht-getal"><?php echo (int) $r[2]; ?></td><td class="inzicht-getal"><?php echo esc_html( $r[1] ? thema_inzicht_procent( $r[2], $r[1] ) : '' ); ?></td></tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php
		} else {
			$paginas = 0 === strpos( $naam, 'gf:' ) ? thema_inzicht_gf_velden( (int) substr( $naam, 3 ) ) : array();
			$stappen = max( 1, count( $paginas ), ...array_map( 'intval', array_keys( $d['veld'] ?: array( 1 => 1 ) ) ) );
			if ( $stappen > 1 ) :
				$rijen = array();
				for ( $i = 1; $i <= $stappen; $i++ ) {
					$bereikt = 1 === $i ? $gestart : (int) ( $x[ 'pagina:' . $i ] ?? 0 );
					$rijen[] = array( 'Stap ' . $i, $bereikt, (int) ( $x[ 'afhaakstap:' . $i ] ?? 0 ), (int) ( $x[ 'terug:' . $i ] ?? 0 ) );
				}
				$rijen[] = array( 'Verstuurd', $verstuurd, 0, 0 );
				$max     = max( 1, max( array_column( $rijen, 1 ) ) );
				?>
				<h2>Per stap</h2>
				<p class="inzicht-uitleg">Hoeveel mensen elke stap bereikten, hoeveel er in die stap afhaakten en hoe vaak iemand vanuit die stap terugging.</p>
				<table class="widefat striped inzicht-tabel">
					<thead><tr><th>Stap</th><th class="inzicht-getal">Bereikt</th><th></th><th class="inzicht-getal">Van vorige</th><th class="inzicht-getal">Afgehaakt in stap</th><th class="inzicht-getal">Terug geklikt</th></tr></thead>
					<tbody>
						<?php foreach ( $rijen as $i => $r ) : ?>
							<?php $vorig = $i ? $rijen[ $i - 1 ][1] : 0; ?>
							<tr class="<?php echo $vorig && ( $vorig - $r[1] ) / $vorig >= .3 ? 'inzicht-let-op' : ''; ?>"><td><?php echo esc_html( $r[0] ); ?></td><td class="inzicht-getal"><?php echo (int) $r[1]; ?></td><td><?php echo thema_inzicht_balk( $r[1], $max ); // phpcs:ignore ?></td><td class="inzicht-getal"><?php echo esc_html( $i ? thema_inzicht_procent( $r[1], $vorig ) : '' ); ?></td><td class="inzicht-getal"><?php echo (int) $r[2]; ?></td><td class="inzicht-getal"><?php echo (int) $r[3]; ?></td></tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<h2>Per vraag</h2>
			<p class="inzicht-uitleg"><strong>Bereikt</strong>: deel van wie begon dat bij deze vraag kwam. <strong>Ingevuld</strong>: deel van wie de vraag aanraakte dat er iets invulde. <strong>Afgehaakt hier</strong>: de laatste vraag die iemand aanraakte voordat hij de pagina verliet zonder te versturen. Rood gemarkeerd: opvallend veel uitval of foutmeldingen.</p>
			<?php
			/* Volgorde: velden uit het formulier, aangevuld met wat gemeten is maar (nog) niet in het formulier staat. */
			$volgorde = $paginas;
			foreach ( $d['veld'] as $stap => $velden ) {
				foreach ( array_keys( $velden ) as $label ) {
					if ( ! in_array( $label, $volgorde[ $stap ] ?? array(), true ) ) {
						$volgorde[ $stap ][] = $label;
					}
				}
			}
			ksort( $volgorde );
			if ( ! $volgorde ) {
				echo '<p>Nog geen metingen per vraag in deze periode.</p>';
			}
			foreach ( $volgorde as $stap => $labels ) :
				?>
				<?php if ( count( $volgorde ) > 1 ) : ?><h3>Stap <?php echo (int) $stap; ?></h3><?php endif; ?>
				<table class="widefat striped inzicht-tabel">
					<thead><tr><th>Vraag</th><th class="inzicht-getal">Aangeraakt</th><th class="inzicht-getal">Bereikt</th><th></th><th class="inzicht-getal">Ingevuld</th><th class="inzicht-getal">Foutmeldingen</th><th class="inzicht-getal">Afgehaakt hier</th></tr></thead>
					<tbody>
						<?php foreach ( $labels as $label ) : ?>
							<?php
							$v      = $d['veld'][ $stap ][ $label ] ?? array();
							$aan    = (int) ( $v['aangeraakt'] ?? 0 );
							$in     = (int) ( $v['ingevuld'] ?? 0 );
							$fout   = (int) ( $v['fout'] ?? 0 );
							$weg    = (int) ( $v['afgehaakt'] ?? 0 );
							$let_op = ( $aan && $weg / $aan >= .15 ) || ( $verstuurd && $fout / $verstuurd >= .2 );
							?>
							<tr class="<?php echo $let_op ? 'inzicht-let-op' : ''; ?>">
								<td><?php echo esc_html( $label ); ?></td>
								<td class="inzicht-getal"><?php echo (int) $aan; ?></td>
								<td class="inzicht-getal"><?php echo esc_html( thema_inzicht_procent( $aan, $gestart ) ); ?></td>
								<td><?php echo thema_inzicht_balk( $aan, max( 1, $gestart ) ); // phpcs:ignore ?></td>
								<td class="inzicht-getal"><?php echo esc_html( $aan ? thema_inzicht_procent( $in, $aan ) : '' ); ?></td>
								<td class="inzicht-getal"><?php echo (int) $fout; ?></td>
								<td class="inzicht-getal"><?php echo (int) $weg; ?><?php echo $aan && $weg ? ' <span class="inzicht-klein">(' . esc_html( thema_inzicht_procent( $weg, $aan ) ) . ')</span>' : ''; ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endforeach; ?>
			<?php
		}
		?>

		<div class="inzicht-raster">
			<div class="inzicht-kaart">
				<h3>Mobiel en desktop</h3>
				<table class="widefat striped"><thead><tr><th></th><th class="inzicht-getal">Begonnen</th><th class="inzicht-getal">Afgehaakt</th><th class="inzicht-getal">Verstuurd</th><th class="inzicht-getal">Conversie</th></tr></thead><tbody>
					<?php foreach ( array( 'mobiel' => 'Mobiel', 'desktop' => 'Desktop' ) as $a => $label ) : ?>
						<?php
						$b = (int) ( $apparaat_bron[ 'apparaat:gestart:' . $a ] ?? 0 );
						$w = (int) ( $apparaat_bron[ 'apparaat:afgehaakt:' . $a ] ?? 0 );
						$v = (int) ( $apparaat_bron[ 'apparaat:verstuurd:' . $a ] ?? 0 );
						?>
						<tr><td><?php echo esc_html( $label ); ?></td><td class="inzicht-getal"><?php echo $b; ?></td><td class="inzicht-getal"><?php echo $w; ?></td><td class="inzicht-getal"><?php echo $v; ?></td><td class="inzicht-getal"><?php echo esc_html( thema_inzicht_procent( $v, $b ) ); ?></td></tr>
					<?php endforeach; ?>
				</tbody></table>
			</div>
			<div class="inzicht-kaart">
				<h3>Invultijd</h3>
				<?php if ( $duur ) : ?>
					<?php $max = max( $duur ); ?>
					<table class="widefat striped"><tbody>
						<?php foreach ( $duur as $klasse => $n ) : ?>
							<tr><td><?php echo esc_html( $klasse ); ?></td><td class="inzicht-getal"><?php echo (int) $n; ?></td><td><?php echo thema_inzicht_balk( $n, $max ); // phpcs:ignore ?></td></tr>
						<?php endforeach; ?>
					</tbody></table>
				<?php else : ?>
					<p>Nog geen invultijden gemeten.</p>
				<?php endif; ?>
			</div>
			<div class="inzicht-kaart">
				<h3>Foutmeldingen per veld</h3>
				<?php if ( $fouten ) : ?>
					<?php arsort( $fouten ); ?>
					<table class="widefat striped"><tbody>
						<?php foreach ( $fouten as $k => $n ) : ?>
							<tr><td><?php echo esc_html( substr( $k, 5 ) ); ?></td><td class="inzicht-getal"><?php echo (int) $n; ?></td></tr>
						<?php endforeach; ?>
					</tbody></table>
				<?php else : ?>
					<p>Geen foutmeldingen in deze periode.</p>
				<?php endif; ?>
			</div>
		</div>

		<?php
		$pag = $is_contact ? $gf['pagina'] : $d['pagina'];
		if ( ! $is_contact ) :
			$pag = array_filter( $pag, fn( $r ) => ( $r['gezien'] ?? 0 ) || ( $r['gestart'] ?? 0 ) || ( $r['verstuurd'] ?? 0 ) );
			uasort( $pag, fn( $a, $b ) => ( $b['gestart'] ?? 0 ) <=> ( $a['gestart'] ?? 0 ) );
			?>
			<h2>Per pagina</h2>
			<p class="inzicht-uitleg">Waar het formulier stond toen mensen het zagen, begonnen of verstuurden.</p>
			<?php if ( $pag ) : ?>
				<table class="widefat striped inzicht-tabel"><thead><tr><th>Pagina</th><th class="inzicht-getal">Gezien</th><th class="inzicht-getal">Begonnen</th><th class="inzicht-getal">Verstuurd</th><th class="inzicht-getal">Conversie</th></tr></thead><tbody>
					<?php foreach ( $pag as $pad => $r ) : ?>
						<tr><td><a href="<?php echo esc_url( home_url( $pad ) ); ?>" target="_blank"><?php echo esc_html( $pad ); ?></a></td><td class="inzicht-getal"><?php echo (int) ( $r['gezien'] ?? 0 ); ?></td><td class="inzicht-getal"><?php echo (int) ( $r['gestart'] ?? 0 ); ?></td><td class="inzicht-getal"><?php echo (int) ( $r['verstuurd'] ?? 0 ); ?></td><td class="inzicht-getal"><?php echo esc_html( thema_inzicht_procent( (int) ( $r['verstuurd'] ?? 0 ), (int) ( $r['gestart'] ?? 0 ) ) ); ?></td></tr>
					<?php endforeach; ?>
				</tbody></table>
			<?php else : ?>
				<p>Nog geen metingen per pagina.</p>
			<?php endif; ?>
		<?php endif; ?>

		<h2>Per dag</h2>
		<?php
		$dagdata = $d['dag'];
		if ( $is_contact ) {
			foreach ( $gf['dag'] as $dag => $r ) {
				$dagdata[ $dag ]['verstuurd'] = $r['verstuurd'] ?? 0;
			}
		}
		krsort( $dagdata );
		$max = 1;
		foreach ( $dagdata as $r ) {
			$max = max( $max, (int) ( $r['gezien'] ?? $r['keuze'] ?? 0 ), (int) ( $r['gestart'] ?? 0 ) );
		}
		?>
		<?php if ( $dagdata ) : ?>
			<table class="widefat striped inzicht-tabel"><thead><tr><th>Dag</th><th class="inzicht-getal"><?php echo $is_contact ? 'Geladen' : 'Gezien'; ?></th><th class="inzicht-getal">Begonnen</th><th class="inzicht-getal">Verstuurd</th><th></th></tr></thead><tbody>
				<?php foreach ( $dagdata as $dag => $r ) : ?>
					<?php
					$z = (int) ( $is_contact ? ( $r['keuze'] ?? 0 ) : ( $r['gezien'] ?? 0 ) );
					$g = (int) ( $is_contact ? array_sum( array_filter( $r, fn( $k ) => 0 === strpos( $k, 'apparaat:gestart:' ), ARRAY_FILTER_USE_KEY ) ) : ( $r['gestart'] ?? 0 ) );
					$v = (int) ( $r['verstuurd'] ?? 0 );
					?>
					<tr><td><?php echo esc_html( wp_date( 'D j M', strtotime( $dag ) ) ); ?></td><td class="inzicht-getal"><?php echo $z; ?></td><td class="inzicht-getal"><?php echo $g; ?></td><td class="inzicht-getal"><?php echo $v; ?></td><td><?php echo thema_inzicht_balk( $g, $max ); // phpcs:ignore ?></td></tr>
				<?php endforeach; ?>
			</tbody></table>
		<?php else : ?>
			<p>Nog geen metingen in deze periode.</p>
		<?php endif; ?>
	</div>
	<?php
	thema_inzicht_stijl();
}

function thema_inzicht_stijl() {
	?>
	<style>
		.inzichten .inzicht-uitleg { max-width: 760px; font-size: 14px; }
		.inzichten .inzicht-periode { display: flex; gap: 6px; margin: 16px 0 8px; }
		.inzichten h2 { margin-top: 32px; }
		.inzichten .inzicht-raster { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 16px; }
		.inzichten .inzicht-raster-breed { grid-template-columns: minmax(0, 2fr) minmax(280px, 1fr); }
		.inzichten .inzicht-kaart { padding: 18px 20px 20px; border-radius: 14px; background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,.06), inset 0 0 0 1px #e6e0d6; }
		.inzichten .inzicht-kaart h3 { margin: 0 0 6px; }
		.inzichten .inzicht-kaart h4 { margin: 18px 0 6px; }
		.inzichten .inzicht-kern { margin: 0 0 12px; color: #555; }
		.inzichten .inzicht-kern strong { color: #38065e; font-size: 22px; }
		.inzichten .inzicht-getal { width: 60px; text-align: right; font-variant-numeric: tabular-nums; font-weight: 600; }
		.inzichten .inzicht-balk { display: block; width: 120px; height: 8px; border-radius: 4px; background: #f1e9dc; overflow: hidden; }
		.inzichten .inzicht-balk span { display: block; height: 100%; background: #ffaf00; }
		@media (max-width: 1100px) { .inzichten .inzicht-raster-breed { grid-template-columns: minmax(0, 1fr); } }
		.inzichten .inzicht-kpis { display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 12px; margin: 16px 0 8px; }
		.inzichten .inzicht-kpi { display: flex; flex-direction: column; gap: 4px; padding: 14px 16px; border-radius: 12px; background: #fff; box-shadow: inset 0 0 0 1px #e6e0d6; }
		.inzichten .inzicht-kpi span { color: #666; font-size: 12px; text-transform: uppercase; letter-spacing: .04em; }
		.inzichten .inzicht-kpi strong { color: #38065e; font-size: 26px; line-height: 1.1; }
		.inzichten .inzicht-kpi em { color: #666; font-style: normal; font-size: 12px; }
		.inzichten .inzicht-tabel { max-width: 1100px; }
		.inzichten tr.inzicht-let-op td { background: #fdecee; }
		.inzichten tr.inzicht-let-op td:first-child { box-shadow: inset 3px 0 0 #e0479e; font-weight: 600; }
		.inzichten .inzicht-klein { color: #777; font-weight: 400; }
		.inzichten .inzicht-details { display: inline-block; margin-top: 12px; font-weight: 600; }
		.inzichten h3 + .inzicht-tabel { margin-top: 6px; }
		.inzichten .inzicht-periodekiezer { display: flex; flex-wrap: wrap; align-items: center; gap: 10px 18px; margin: 16px 0 6px; }
		.inzichten .inzicht-periodekiezer .inzicht-periode { flex-wrap: wrap; margin: 0; }
		.inzichten .inzicht-eigen { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
		.inzichten .inzicht-eigen label { display: flex; align-items: center; gap: 6px; }
		.inzichten .inzicht-periode-label { flex-basis: 100%; margin: 0; color: #555; }
		.inzichten .inzicht-periodekiezer .inzicht-periode { align-items: center; }
		.inzichten select.inzicht-meer { min-height: 30px; }
		.inzichten select.inzicht-meer.is-gekozen { border-color: #2271b1; box-shadow: 0 0 0 1px #2271b1; font-weight: 600; }
		.inzichten .inzicht-grafiek-kaart { margin: 16px 0 8px; max-width: 1200px; }
		.inzichten .inzicht-legenda { display: flex; flex-wrap: wrap; gap: 6px 18px; margin: 4px 0 10px; font-size: 13px; }
		.inzichten .inzicht-legenda span { display: inline-flex; align-items: center; gap: 6px; }
		.inzichten .inzicht-legenda i { display: inline-block; width: 14px; height: 4px; border-radius: 2px; }
		.inzichten svg.inzicht-grafiek { display: block; width: 100%; height: auto; overflow: visible; }
		.inzichten svg.inzicht-grafiek line.raster { stroke: #eee6da; stroke-width: 1; }
		.inzichten svg.inzicht-grafiek text { fill: #777; font-size: 12px; }
		.inzichten svg.inzicht-grafiek circle { cursor: default; }
	</style>
	<?php
}

/* --------------------------------------------------------------------------
 * Periode en grafieken
 * ----------------------------------------------------------------------- */

/**
 * Gekozen periode uit de adresbalk: een vaste keuze (periode=30, maand, ...) of een eigen van/tot.
 * Geeft van, tot (Y-m-d), de sleutel, een leesbaar label en de parameters voor links.
 */
function thema_inzicht_periode() {
	static $uit = null;
	if ( null !== $uit ) {
		return $uit;
	}
	$vandaag = wp_date( 'Y-m-d' );
	$keuze   = sanitize_key( wp_unslash( $_GET['periode'] ?? ( $_GET['dagen'] ?? '30' ) ) ); // phpcs:ignore
	$van_in  = sanitize_text_field( wp_unslash( $_GET['van'] ?? '' ) ); // phpcs:ignore
	$tot_in  = sanitize_text_field( wp_unslash( $_GET['tot'] ?? '' ) ); // phpcs:ignore
	$geldig  = fn( $d ) => preg_match( '/^\d{4}-\d{2}-\d{2}$/', $d ) && strtotime( $d );

	if ( 'eigen' === $keuze && $geldig( $van_in ) && $geldig( $tot_in ) ) {
		$van = min( $van_in, $tot_in );
		$tot = min( max( $van_in, $tot_in ), $vandaag );
		$uit = array( 'van' => $van, 'tot' => $tot, 'sleutel' => 'eigen', 'label' => wp_date( 'j M Y', strtotime( $van ) ) . ' t/m ' . wp_date( 'j M Y', strtotime( $tot ) ), 'args' => array( 'periode' => 'eigen', 'van' => $van, 'tot' => $tot ) );
		return $uit;
	}
	$vast = thema_inzicht_periodes();
	if ( ! isset( $vast[ $keuze ] ) ) {
		$keuze = '30';
	}
	switch ( $keuze ) {
		case 'vandaag':
			$van = $vandaag;
			$tot = $vandaag;
			break;
		case 'gisteren':
			$van = wp_date( 'Y-m-d', strtotime( '-1 day' ) );
			$tot = $van;
			break;
		case 'maand':
			$van = wp_date( 'Y-m-01' );
			$tot = $vandaag;
			break;
		case 'vorige-maand':
			$van = wp_date( 'Y-m-01', strtotime( 'first day of last month' ) );
			$tot = wp_date( 'Y-m-t', strtotime( 'first day of last month' ) );
			break;
		case 'jaar':
			$van = wp_date( 'Y-01-01' );
			$tot = $vandaag;
			break;
		case 'alles':
			global $wpdb;
			$van = (string) ( $wpdb->get_var( 'SELECT MIN(dag) FROM ' . thema_inzicht_tabel() ) ?: $vandaag ); // phpcs:ignore
			$tot = $vandaag;
			break;
		default:
			$van = wp_date( 'Y-m-d', strtotime( '-' . ( (int) $keuze - 1 ) . ' days' ) );
			$tot = $vandaag;
	}
	$uit = array( 'van' => $van, 'tot' => $tot, 'sleutel' => $keuze, 'label' => $vast[ $keuze ], 'args' => array( 'periode' => $keuze ) );
	return $uit;
}

function thema_inzicht_periodes() {
	return array(
		'vandaag'      => 'Vandaag',
		'gisteren'     => 'Gisteren',
		'7'            => '7 dagen',
		'14'           => '14 dagen',
		'30'           => '30 dagen',
		'90'           => '90 dagen',
		'180'          => '6 maanden',
		'365'          => '12 maanden',
		'maand'        => 'Deze maand',
		'vorige-maand' => 'Vorige maand',
		'jaar'         => 'Dit jaar',
		'alles'        => 'Alles',
	);
}

/**
 * Knoppen voor de vaste periodes en een eigen van/tot.
 */
function thema_inzicht_periodekiezer( $formulier = '' ) {
	$p     = thema_inzicht_periode();
	$basis = admin_url( 'admin.php?page=thema-inzichten' );
	$extra = $formulier ? array( 'formulier' => $formulier ) : array();
	?>
	<div class="inzicht-periodekiezer">
		<div class="inzicht-periode">
			<?php
			/* De drie meest gebruikte periodes als knop, de rest in een keuzelijst. */
			$knoppen = array( '7', '30', '90' );
			foreach ( $knoppen as $sleutel ) :
				?>
				<a class="button<?php echo $sleutel === $p['sleutel'] ? ' button-primary' : ''; ?>" href="<?php echo esc_url( add_query_arg( array_merge( $extra, array( 'periode' => $sleutel ) ), $basis ) ); ?>"><?php echo esc_html( thema_inzicht_periodes()[ $sleutel ] ); ?></a>
			<?php endforeach; ?>
			<?php $in_lijst = ! in_array( $p['sleutel'], $knoppen, true ) && 'eigen' !== $p['sleutel']; ?>
			<select class="inzicht-meer<?php echo $in_lijst ? ' is-gekozen' : ''; ?>" aria-label="Andere periode" onchange="if(this.value){window.location.href=this.value;}">
				<option value=""><?php echo esc_html( $in_lijst ? $p['label'] : 'Andere periode' ); ?></option>
				<?php foreach ( thema_inzicht_periodes() as $sleutel => $label ) : ?>
					<?php if ( in_array( (string) $sleutel, $knoppen, true ) ) { continue; } ?>
					<option value="<?php echo esc_url( add_query_arg( array_merge( $extra, array( 'periode' => $sleutel ) ), $basis ) ); ?>"<?php selected( (string) $sleutel, $p['sleutel'] ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<form class="inzicht-eigen" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
			<input type="hidden" name="page" value="thema-inzichten">
			<input type="hidden" name="periode" value="eigen">
			<?php if ( $formulier ) : ?><input type="hidden" name="formulier" value="<?php echo esc_attr( $formulier ); ?>"><?php endif; ?>
			<label>Van <input type="date" name="van" value="<?php echo esc_attr( $p['van'] ); ?>" max="<?php echo esc_attr( wp_date( 'Y-m-d' ) ); ?>"></label>
			<label>t/m <input type="date" name="tot" value="<?php echo esc_attr( $p['tot'] ); ?>" max="<?php echo esc_attr( wp_date( 'Y-m-d' ) ); ?>"></label>
			<button class="button<?php echo 'eigen' === $p['sleutel'] ? ' button-primary' : ''; ?>" type="submit">Eigen periode</button>
		</form>
		<p class="inzicht-periode-label">Periode: <strong><?php echo esc_html( $p['label'] ); ?></strong> (<?php echo esc_html( wp_date( 'j M Y', strtotime( $p['van'] ) ) . ' t/m ' . wp_date( 'j M Y', strtotime( $p['tot'] ) ) ); ?>)</p>
	</div>
	<?php
}

/**
 * Lijngrafiek als SVG, zonder bibliotheek.
 * $reeksen: array( label => array( 'kleur' => '#hex', 'data' => array( 'Y-m-d' => aantal ) ) ).
 * Bij lange periodes per week (vanaf 92 dagen) of per maand (vanaf 400 dagen).
 */
function thema_inzicht_lijngrafiek( $reeksen, $van, $tot, $titel = '' ) {
	$begin  = new DateTimeImmutable( $van );
	$einde  = new DateTimeImmutable( $tot );
	$aantal = (int) $begin->diff( $einde )->days + 1;
	$stap   = $aantal >= 400 ? 'maand' : ( $aantal >= 92 ? 'week' : 'dag' );

	/* Emmers op de x-as. */
	$emmers = array();
	for ( $d = $begin; $d <= $einde; $d = $d->modify( '+1 day' ) ) {
		$sleutel = 'maand' === $stap ? $d->format( 'Y-m' ) : ( 'week' === $stap ? $d->format( 'o-\WW' ) : $d->format( 'Y-m-d' ) );
		if ( ! isset( $emmers[ $sleutel ] ) ) {
			$emmers[ $sleutel ] = 'maand' === $stap ? wp_date( 'M Y', $d->getTimestamp() ) : ( 'week' === $stap ? 'wk ' . $d->format( 'W' ) : wp_date( 'j M', $d->getTimestamp() ) );
		}
	}
	$lijnen = array();
	$max    = 1;
	foreach ( $reeksen as $label => $reeks ) {
		$waarden = array_fill_keys( array_keys( $emmers ), 0 );
		foreach ( (array) $reeks['data'] as $dag => $n ) {
			$t = strtotime( $dag );
			if ( ! $t ) {
				continue;
			}
			$sleutel = 'maand' === $stap ? gmdate( 'Y-m', $t ) : ( 'week' === $stap ? gmdate( 'o-\WW', $t ) : gmdate( 'Y-m-d', $t ) );
			if ( isset( $waarden[ $sleutel ] ) ) {
				$waarden[ $sleutel ] += (int) $n;
			}
		}
		$max             = max( $max, max( $waarden ?: array( 0 ) ) );
		$lijnen[ $label ] = array( 'kleur' => $reeks['kleur'], 'waarden' => $waarden );
	}

	/* Mooie bovengrens en vier rasterlijnen. */
	$orde  = pow( 10, floor( log10( max( 1, $max ) ) ) );
	$boven = max( 4, ceil( $max / $orde ) * $orde );
	if ( $boven / 4 !== floor( $boven / 4 ) ) {
		$boven = ceil( $boven / 4 ) * 4;
	}
	$b    = 1000;
	$h    = 280;
	$l    = 44;
	$r    = 16;
	$o    = 30;
	$t    = 14;
	$n    = count( $emmers );
	$x    = fn( $i ) => $l + ( $n > 1 ? $i * ( $b - $l - $r ) / ( $n - 1 ) : ( $b - $l - $r ) / 2 );
	$y    = fn( $v ) => $t + ( $h - $t - $o ) * ( 1 - $v / $boven );
	$elke = max( 1, (int) ceil( $n / 10 ) );
	$svg  = '<svg class="inzicht-grafiek" viewBox="0 0 ' . $b . ' ' . $h . '" role="img" aria-label="' . esc_attr( $titel ?: 'Lijngrafiek' ) . '">';
	for ( $i = 0; $i <= 4; $i++ ) {
		$waarde = $boven * $i / 4;
		$svg   .= '<line x1="' . $l . '" x2="' . ( $b - $r ) . '" y1="' . round( $y( $waarde ), 1 ) . '" y2="' . round( $y( $waarde ), 1 ) . '" class="raster"/>';
		$svg   .= '<text x="' . ( $l - 8 ) . '" y="' . round( $y( $waarde ) + 4, 1 ) . '" text-anchor="end">' . esc_html( number_format_i18n( $waarde ) ) . '</text>';
	}
	$i = 0;
	foreach ( $emmers as $label ) {
		if ( 0 === $i % $elke || $i === $n - 1 ) {
			$svg .= '<text x="' . round( $x( $i ), 1 ) . '" y="' . ( $h - 8 ) . '" text-anchor="middle">' . esc_html( $label ) . '</text>';
		}
		$i++;
	}
	foreach ( $lijnen as $naam => $lijn ) {
		$punten = array();
		$i      = 0;
		foreach ( $lijn['waarden'] as $waarde ) {
			$punten[] = round( $x( $i ), 1 ) . ',' . round( $y( $waarde ), 1 );
			$i++;
		}
		$svg .= '<polyline points="' . implode( ' ', $punten ) . '" fill="none" stroke="' . esc_attr( $lijn['kleur'] ) . '" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round"/>';
		$i    = 0;
		$labels = array_values( $emmers );
		foreach ( $lijn['waarden'] as $waarde ) {
			$svg .= '<circle cx="' . round( $x( $i ), 1 ) . '" cy="' . round( $y( $waarde ), 1 ) . '" r="' . ( $n > 60 ? 2 : 3.5 ) . '" fill="' . esc_attr( $lijn['kleur'] ) . '"><title>' . esc_html( $labels[ $i ] . ': ' . $waarde . ' ' . mb_strtolower( $naam ) ) . '</title></circle>';
			$i++;
		}
	}
	$svg .= '</svg>';

	$legenda = '';
	foreach ( $lijnen as $naam => $lijn ) {
		$legenda .= '<span><i style="background:' . esc_attr( $lijn['kleur'] ) . '"></i>' . esc_html( $naam ) . ' <strong>' . (int) array_sum( $lijn['waarden'] ) . '</strong></span>';
	}
	$per = array( 'dag' => 'per dag', 'week' => 'per week', 'maand' => 'per maand' )[ $stap ];
	return '<div class="inzicht-kaart inzicht-grafiek-kaart">' . ( $titel ? '<h3>' . esc_html( $titel ) . ' <span class="inzicht-klein">' . esc_html( $per ) . '</span></h3>' : '' ) . '<div class="inzicht-legenda">' . $legenda . '</div>' . $svg . '</div>';
}

/**
 * Kleuren van de lijnen, gelijk voor elke grafiek.
 */
function thema_inzicht_kleuren() {
	return array( 'Gezien' => '#b9a6cf', 'Begonnen' => '#ffaf00', 'Verstuurd' => '#1d9a5b', 'Afgehaakt' => '#e0479e', 'Vragen ingevuld' => '#38065e' );
}

/**
 * Grafiek bovenaan het overzicht: alle formulieren samen.
 */
function thema_inzicht_overzicht_grafiek() {
	global $wpdb;
	$p     = thema_inzicht_periode();
	$tabel = thema_inzicht_tabel();
	$rijen = $wpdb->get_results( $wpdb->prepare( "SELECT dag, soort, detail, SUM(aantal) AS n FROM {$tabel} WHERE soort IN ('formulier','stap') AND dag BETWEEN %s AND %s GROUP BY dag, soort, detail", $p['van'], $p['tot'] ) ); // phpcs:ignore
	$data  = array( 'Gezien' => array(), 'Begonnen' => array(), 'Verstuurd' => array(), 'Afgehaakt' => array() );
	foreach ( $rijen as $r ) {
		$tak = null;
		if ( 'formulier' === $r->soort ) {
			if ( 'gezien' === $r->detail ) {
				$tak = 'Gezien';
			} elseif ( 'gestart' === $r->detail ) {
				$tak = 'Begonnen';
			} elseif ( 'verstuurd' === $r->detail ) {
				$tak = 'Verstuurd';
			} elseif ( 0 === strpos( $r->detail, 'afhaakstap:' ) ) {
				$tak = 'Afgehaakt';
			}
		} elseif ( 0 === strpos( $r->detail, 'apparaat:gestart:' ) ) {
			$tak = 'Begonnen';
		} elseif ( 0 === strpos( $r->detail, 'afgehaakt:' ) ) {
			$tak = 'Afgehaakt';
		} elseif ( 'keuze' === $r->detail ) {
			$tak = 'Gezien';
		}
		if ( $tak ) {
			$data[ $tak ][ $r->dag ] = ( $data[ $tak ][ $r->dag ] ?? 0 ) + (int) $r->n;
		}
	}
	$kleuren = thema_inzicht_kleuren();
	$reeksen = array();
	foreach ( $data as $label => $reeks ) {
		$reeksen[ $label ] = array( 'kleur' => $kleuren[ $label ], 'data' => $reeks );
	}
	echo thema_inzicht_lijngrafiek( $reeksen, $p['van'], $p['tot'], 'Alle formulieren' ); // phpcs:ignore
}
