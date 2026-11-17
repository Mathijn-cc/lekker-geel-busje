<?php
/**
 * Zwevende knoppen rechtsonder: knop naar boven en de WhatsApp-chat.
 *
 * De chat is een kort, geleid gesprek. Een prijs aanvragen gaat altijd via het offerteformulier:
 * die keuze is een link. Voor een vraag over je rit of een andere vraag beantwoordt de bezoeker
 * een of twee vragen en stuurt alles met één tik via WhatsApp (wa.me-link met ?text=).
 * Geen scripts of cookies van derden.
 * Zonder JavaScript is de knop een gewone link naar WhatsApp.
 *
 * Alles is instelbaar in Thema > WhatsApp. Het nummer komt uit Thema > Algemeen > Contact.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Het WhatsApp-nummer in internationale vorm (31612345678), of een lege string.
 * Werkt ook als het nummer als 06-nummer, met +31 of met 0031 is ingevuld.
 */
function thema_whatsapp_nummer( $nummer = null ) {
	$cijfers = preg_replace( '/\D+/', '', (string) ( null === $nummer ? thema_instellingen( 'whatsapp' ) : $nummer ) );
	if ( 0 === strpos( $cijfers, '00' ) ) {
		$cijfers = substr( $cijfers, 2 );
	} elseif ( 0 === strpos( $cijfers, '0' ) ) {
		$cijfers = '31' . substr( $cijfers, 1 );
	}
	return $cijfers;
}

/**
 * Of de chat op deze pagina getoond wordt.
 */
function thema_chat_tonen() {
	static $tonen = null;
	if ( null !== $tonen ) {
		return $tonen;
	}
	$s     = thema_instellingen();
	$tonen = ! is_admin() && ! is_404() && $s['chat_aan'] && '' !== thema_whatsapp_nummer();

	if ( $tonen && '' !== trim( $s['chat_verbergen'] ) ) {
		$pad = trim( (string) wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/', PHP_URL_PATH ), '/' );
		$pad = strtolower( rawurldecode( $pad ) );
		foreach ( preg_split( '/\R/', $s['chat_verbergen'] ) as $regel ) {
			$regel = trim( $regel );
			if ( '' === $regel ) {
				continue;
			}
			/* Volledige URL of pad: alleen het pad telt. "/" is de homepage. */
			$regel = strtolower( trim( (string) wp_parse_url( $regel, PHP_URL_PATH ), '/' ) );
			/* Een regel met * aan het eind geldt ook voor alle onderliggende pagina's. */
			if ( '*' === substr( $regel, -1 ) ) {
				$basis = rtrim( substr( $regel, 0, -1 ), '/' );
				if ( '' === $basis || $pad === $basis || 0 === strpos( $pad, $basis . '/' ) ) {
					$tonen = false;
					break;
				}
			} elseif ( $pad === $regel ) {
				$tonen = false;
				break;
			}
		}
	}
	return $tonen;
}

/**
 * Eenmalig: standaardteksten uit eerdere versies omzetten naar de nieuwe.
 * Zelf aangepaste teksten blijven staan.
 */
add_action( 'admin_init', function () {
	if ( (int) get_option( 'thema_chat_versie' ) >= 3 ) {
		return;
	}
	update_option( 'thema_chat_versie', 3, false );
	$opgeslagen = get_option( THEMA_OPTIE, array() );
	if ( ! is_array( $opgeslagen ) || ! $opgeslagen ) {
		return;
	}
	$oud = array(
		'chat_ballon'    => array( 'Vraagje? App ons gerust!', 'Hoi! Vraagje over je feestje? App ons gerust.' ),
		'chat_ballon_na' => array( 8, 12 ),
		'chat_welkom'    => array( "Hoi! Waar kunnen we je mee helpen?\nTyp je vraag hieronder, dan zet je hem met één tik in WhatsApp.", 'Hoi! Waar kunnen we je mee helpen?' ),
		'chat_veld'      => array( 'Typ je vraag...', 'Of typ meteen je vraag...' ),
	);
	foreach ( $oud as $sleutel => $paar ) {
		if ( isset( $opgeslagen[ $sleutel ] ) && str_replace( "\r", '', (string) $opgeslagen[ $sleutel ] ) === (string) $paar[0] ) {
			$opgeslagen[ $sleutel ] = $paar[1];
		}
	}
	/* Ontbrekende sleutels aanvullen, anders maakt het opschonen ze leeg of uit. */
	update_option( THEMA_OPTIE, wp_parse_args( $opgeslagen, thema_standaard() ) );
} );

/**
 * Script alleen laden als de chat zichtbaar is.
 */
add_action( 'wp_enqueue_scripts', function () {
	if ( ! thema_chat_tonen() ) {
		return;
	}
	wp_enqueue_script( 'thema-whatsapp', THEMA_URL . '/assets/js/whatsapp.js', array(), THEMA_VERSIE, array( 'in_footer' => true, 'strategy' => 'defer' ) );
}, 21 );

/**
 * Het WhatsApp-logo, gevuld. Herkenbaarder dan het lijnicoon.
 */
function thema_whatsapp_logo( $maat = 26 ) {
	return sprintf( '<svg class="icoon" width="%1$d" height="%1$d" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.17-.17.2-.35.22-.65.07-.3-.15-1.26-.46-2.4-1.48-.89-.79-1.49-1.77-1.66-2.07-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.03-.52-.07-.15-.67-1.61-.92-2.21-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48 0 1.46 1.07 2.88 1.21 3.07.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.69.63.71.23 1.36.2 1.87.12.57-.09 1.76-.72 2.01-1.41.25-.7.25-1.29.17-1.41-.07-.13-.27-.2-.57-.35M12.05 21.79h-.01a9.87 9.87 0 0 1-5.03-1.38l-.36-.21-3.74.98 1-3.65-.24-.37a9.86 9.86 0 0 1-1.51-5.26c0-5.45 4.44-9.88 9.89-9.88 2.64 0 5.12 1.03 6.99 2.9a9.82 9.82 0 0 1 2.89 6.99c0 5.45-4.44 9.88-9.89 9.88m8.41-18.3A11.82 11.82 0 0 0 12.05 0C5.5 0 .16 5.34.16 11.89c0 2.1.55 4.14 1.59 5.95L.06 24l6.31-1.65a11.88 11.88 0 0 0 5.68 1.45h.01c6.55 0 11.89-5.34 11.89-11.89 0-3.18-1.24-6.17-3.48-8.42"/></svg>', (int) $maat );
}

/**
 * Avatar voor kop en berichtje: foto uit de instellingen, anders het busicoon.
 */
function thema_chat_avatar() {
	$id = (int) thema_instellingen( 'chat_foto' );
	if ( $id ) {
		$foto = wp_get_attachment_image( $id, 'thumbnail', false, array( 'alt' => '', 'loading' => 'lazy' ) );
		if ( $foto ) {
			return $foto;
		}
	}
	return thema_icoon( 'bus', 20 );
}

/**
 * Regels uit een tekstvak als lijst.
 */
function thema_chat_regels( $tekst ) {
	return array_values( array_filter( array_map( 'trim', preg_split( '/\R/', (string) $tekst ) ) ) );
}

/**
 * Het gesprek: per keuze de vragen die gesteld worden. Gaat als JSON naar het script.
 */
function thema_chat_gesprek() {
	$s = thema_instellingen();

	$pagina = '';
	if ( $s['chat_pagina'] ) {
		if ( is_front_page() ) {
			$pagina = 'Homepage';
		} elseif ( is_singular() || is_home() ) {
			$pagina = wp_strip_all_tags( get_the_title( get_queried_object_id() ) );
		} elseif ( is_category() ) {
			$pagina = wp_strip_all_tags( single_cat_title( '', false ) );
		}
	}

	return array(
		'nummer'  => thema_whatsapp_nummer(),
		'pagina'  => $pagina,
		'welkom'  => thema_chat_regels( $s['chat_welkom'] ),
		'veld'    => $s['chat_veld'],
		'klaar'   => $s['chat_klaar'],
		'paden'   => array(
			array(
				'id'    => 'prijs',
				'label' => $s['chat_keuze_prijs'],
				'link'  => thema_offerte_url(),
				'pijl'  => thema_icoon( 'pijl-op', 14, 2.4 ),
			),
			array(
				'id'      => 'rit',
				'label'   => $s['chat_keuze_rit'],
				'intro'   => 'Hoi! Ik heb een vraag over mijn rit.',
				'stappen' => array(
					array( 'naam' => 'Datum rit', 'vraag' => $s['chat_v_ritdatum'], 'soort' => 'datum' ),
					array( 'naam' => 'Vraag', 'vraag' => $s['chat_v_ritvraag'], 'veld' => 'Typ je vraag...' ),
				),
			),
			array(
				'id'      => 'anders',
				'label'   => $s['chat_keuze_anders'],
				'intro'   => '',
				'stappen' => array(
					array( 'naam' => '', 'vraag' => $s['chat_v_vraag'], 'veld' => 'Typ je vraag...' ),
				),
			),
		),
	);
}

/**
 * Zwevende knoppen en chat. Wordt in footer.php geplaatst.
 */
function thema_zweefknoppen() {
	$s     = thema_instellingen();
	$chat  = thema_chat_tonen();
	$boven = (bool) $s['naar_boven'];
	if ( ! $chat && ! $boven ) {
		return '';
	}

	$class = array( 'zweefknoppen' );
	if ( '' !== thema_onderbalk() ) {
		$class[] = 'met-onderbalk';
	}
	if ( ! $chat ) {
		$class[] = 'zonder-chat';
	}
	$naam   = $s['chat_naam'] ?: get_bloginfo( 'name' );
	$avatar = $chat ? thema_chat_avatar() : '';

	ob_start();
	?>
	<div class="<?php echo esc_attr( implode( ' ', $class ) ); ?>" data-zweefknoppen>

		<?php if ( $chat && $s['chat_ballon'] ) : ?>
			<div class="whatsapp-melding" data-whatsapp-ballon hidden>
				<button type="button" class="whatsapp-melding-open" data-whatsapp-ballon-open>
					<span class="whatsapp-avatar"><?php echo $avatar; // phpcs:ignore ?></span>
					<span class="whatsapp-melding-inhoud">
						<span class="whatsapp-melding-naam"><?php echo esc_html( $naam ); ?></span>
						<span class="whatsapp-melding-tekst"><?php echo esc_html( $s['chat_ballon'] ); ?></span>
					</span>
				</button>
				<button type="button" class="whatsapp-melding-weg" aria-label="Melding verbergen" data-whatsapp-ballon-weg><?php echo thema_icoon( 'sluiten', 12, 2.4 ); ?></button>
			</div>
		<?php endif; ?>

		<?php if ( $boven ) : ?>
			<a class="naar-boven" href="#top" aria-label="Terug naar boven" data-naar-boven><?php echo thema_icoon( 'omhoog', 21, 2.4 ); ?></a>
		<?php endif; ?>

		<?php if ( $chat ) : ?>
			<a class="whatsapp-knop" href="<?php echo esc_url( 'https://wa.me/' . thema_whatsapp_nummer() ); ?>" target="_blank" rel="noopener" aria-label="Chat met ons via WhatsApp" aria-controls="whatsapp-venster" aria-expanded="false" data-whatsapp-knop>
				<span class="whatsapp-knop-cirkel" aria-hidden="true"><?php echo thema_whatsapp_logo( 26 ); // phpcs:ignore ?></span>
			</a>

			<div class="whatsapp-venster" id="whatsapp-venster" role="dialog" aria-modal="false" aria-labelledby="whatsapp-venster-naam" data-whatsapp-venster data-gesprek="<?php echo esc_attr( wp_json_encode( thema_chat_gesprek() ) ); ?>" data-ballon-na="<?php echo (int) $s['chat_ballon_na']; ?>" data-lenis-prevent hidden>
				<div class="whatsapp-venster-kop">
					<span class="whatsapp-avatar"><?php echo $avatar; // phpcs:ignore ?></span>
					<span class="whatsapp-venster-afzender">
						<span class="whatsapp-venster-naam" id="whatsapp-venster-naam"><?php echo esc_html( $naam ); ?></span>
						<?php if ( $s['chat_status'] ) : ?>
							<span class="whatsapp-venster-status"><?php echo esc_html( $s['chat_status'] ); ?></span>
						<?php endif; ?>
					</span>
					<button type="button" class="whatsapp-venster-knopje whatsapp-venster-opnieuw" aria-label="Opnieuw beginnen" title="Opnieuw beginnen" data-whatsapp-opnieuw hidden><?php echo thema_icoon( 'opnieuw', 16, 2.4 ); ?></button>
					<button type="button" class="whatsapp-venster-knopje" aria-label="Venster sluiten" data-whatsapp-sluiten><?php echo thema_icoon( 'sluiten', 16, 2.4 ); ?></button>
				</div>

				<div class="whatsapp-venster-log" data-whatsapp-log aria-live="polite"></div>

				<form class="whatsapp-venster-formulier" data-whatsapp-formulier novalidate>
					<div class="whatsapp-venster-invoer" data-whatsapp-invoer>
						<label class="sr-tekst" for="whatsapp-antwoord">Je antwoord</label>
						<textarea class="whatsapp-antwoord" id="whatsapp-antwoord" rows="1" maxlength="1000" placeholder="<?php echo esc_attr( $s['chat_veld'] ); ?>" data-whatsapp-tekst></textarea>
						<label class="sr-tekst" for="whatsapp-datum">Datum</label>
						<input class="whatsapp-datum" id="whatsapp-datum" type="date" data-whatsapp-datum hidden>
						<button type="submit" class="whatsapp-verstuur" aria-label="Antwoord versturen" data-whatsapp-verstuur disabled><?php echo thema_icoon( 'verstuur', 18, 2.2 ); ?></button>
					</div>
					<a class="whatsapp-naar-app" href="#" target="_blank" rel="noopener" data-whatsapp-naar-app hidden><?php echo thema_whatsapp_logo( 17 ); // phpcs:ignore ?><span>Verstuur via WhatsApp</span></a>
				</form>
			</div>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}
