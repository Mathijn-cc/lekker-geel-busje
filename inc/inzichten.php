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
	if ( is_user_logged_in() ) {
		return false;
	}
	$agent = strtolower( (string) ( $_SERVER['HTTP_USER_AGENT'] ?? '' ) ); // phpcs:ignore
	return '' !== $agent && ! preg_match( '/bot|crawl|spider|slurp|lighthouse|headless|preview/', $agent );
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
				if ( ! is_array( $meting ) || ! in_array( $meting[0] ?? '', array( 'formulier', 'stap', 'chat', 'link', 'contact' ), true ) ) {
					continue;
				}
				thema_inzicht_tel( $meting[0], (string) ( $meting[1] ?? '' ), (string) ( $meting[2] ?? '' ), $pad );
			}
			return new WP_REST_Response( null, 204 );
		},
	) );
} );

add_action( 'wp_enqueue_scripts', function () {
	if ( is_user_logged_in() ) {
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
	}
}, 10, 2 );

add_filter( 'gform_validation', function ( $uitkomst ) {
	if ( ! empty( $uitkomst['is_valid'] ) || ! thema_inzicht_meetellen() ) {
		return $uitkomst;
	}
	foreach ( (array) ( $uitkomst['form']['fields'] ?? array() ) as $veld ) {
		if ( ! empty( $veld->failed_validation ) ) {
			thema_inzicht_tel( 'formulier', 'gf:' . $uitkomst['form']['id'], 'fout:' . wp_strip_all_tags( (string) $veld->label ) );
		}
	}
	return $uitkomst;
}, 99 );

/* Stoelverkoop: een nieuwe boeking, en welke velden fout gingen. */
add_action( 'added_post_meta', function ( $meta_id, $post_id, $sleutel ) {
	if ( '_stoel_aantal' === $sleutel && thema_inzicht_meetellen() ) {
		thema_inzicht_tel( 'formulier', 'stoelen', 'verstuurd' );
	}
}, 10, 3 );

add_action( 'setted_transient', function ( $naam, $waarde ) {
	if ( 0 !== strpos( (string) $naam, 'thema_stoelen_' ) || empty( $waarde['fouten'] ) || ! thema_inzicht_meetellen() ) {
		return;
	}
	foreach ( array_keys( (array) $waarde['fouten'] ) as $veld ) {
		thema_inzicht_tel( 'formulier', 'stoelen', 'fout:' . $veld );
	}
}, 10, 2 );

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

add_action( 'admin_menu', function () {
	add_menu_page( 'Inzichten', 'Inzichten', 'edit_pages', 'thema-inzichten', 'thema_inzicht_pagina', 'dashicons-chart-bar', 3 );
} );

/**
 * Leesbare naam van een formulier.
 */
function thema_inzicht_formuliernaam( $naam ) {
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
		"SELECT {$groep}, SUM(aantal) AS aantal FROM " . thema_inzicht_tabel() . " WHERE soort = %s AND dag >= %s GROUP BY {$groep} ORDER BY aantal DESC", // phpcs:ignore
		$soort,
		$van
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
	$dagen = (int) ( $_GET['dagen'] ?? 30 ); // phpcs:ignore
	$dagen = in_array( $dagen, array( 7, 30, 90, 365 ), true ) ? $dagen : 30;
	$van   = wp_date( 'Y-m-d', strtotime( '-' . ( $dagen - 1 ) . ' days' ) );

	/* Formulieren per naam. */
	$form = array();
	foreach ( thema_inzicht_cijfers( 'formulier', $van ) as $r ) {
		$form[ $r->naam ][ $r->detail ] = (int) $r->aantal;
	}
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
		<p class="inzicht-periode">
			<?php foreach ( array( 7 => '7 dagen', 30 => '30 dagen', 90 => '90 dagen', 365 => 'Jaar' ) as $d => $label ) : ?>
				<a class="button<?php echo $d === $dagen ? ' button-primary' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'dagen', $d, $adres ) ); ?>"><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
		</p>

		<h2>Formulieren</h2>
		<?php if ( ! $form ) : ?>
			<p>Nog geen metingen in deze periode.</p>
		<?php endif; ?>
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
				</div>
			<?php endforeach; ?>

			<?php if ( $stappen ) : ?>
				<?php
				$namen = array(
					'keuze' => 'Eerste vraag geladen', 'vraag' => 'Vraag typen', 'prijs' => 'Koos: prijs (naar offerte)', 'direct' => 'Koos: bellen of appen',
					'naam' => 'Naam', 'email' => 'E-mailadres', 'telefoon' => 'Telefoonnummer', 'bezig' => 'Versturen', 'bedankt' => 'Verstuurd', 'faq-bedankt' => 'Antwoord gevonden in de veelgestelde vragen',
				);
				$gewoon  = array_filter( $stappen, fn( $k ) => 0 !== strpos( $k, 'afgehaakt:' ), ARRAY_FILTER_USE_KEY );
				$afhaken = array_filter( $stappen, fn( $k ) => 0 === strpos( $k, 'afgehaakt:' ), ARRAY_FILTER_USE_KEY );
				$max     = max( 1, max( $gewoon ?: array( 1 ) ) );
				?>
				<div class="inzicht-kaart">
					<h3>Contactformulier vraag voor vraag</h3>
					<p class="inzicht-kern">Hoe vaak elke stap is bereikt.</p>
					<table class="widefat striped"><tbody>
						<?php foreach ( $gewoon as $id => $aantal ) : ?>
							<tr><td><?php echo esc_html( $namen[ $id ] ?? $id ); ?></td><td class="inzicht-getal"><?php echo (int) $aantal; ?></td><td><?php echo thema_inzicht_balk( $aantal, $max ); // phpcs:ignore ?></td></tr>
						<?php endforeach; ?>
					</tbody></table>
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
	</div>
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
	</style>
	<?php
}
