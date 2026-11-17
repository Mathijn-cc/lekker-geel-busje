<?php
/**
 * WhatsApp-chat: zwevende knop rechtsonder met een ballonnetje en een uitklapvenster.
 *
 * De bezoeker typt zijn eerste vraag in het venster en gaat daarna verder in WhatsApp,
 * met die vraag al ingevuld (wa.me-link met ?text=). Geen scripts of cookies van derden.
 * Zonder JavaScript is de knop een gewone link naar WhatsApp.
 *
 * Alles is instelbaar in Thema > WhatsApp. Het nummer komt uit Thema > Algemeen > Contact.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Het WhatsApp-nummer als cijfers, of een lege string.
 */
function thema_whatsapp_nummer() {
	return preg_replace( '/\D+/', '', (string) thema_instellingen( 'whatsapp' ) );
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
 * Script alleen laden als de chat zichtbaar is.
 */
add_action( 'wp_enqueue_scripts', function () {
	if ( ! thema_chat_tonen() ) {
		return;
	}
	wp_enqueue_script( 'thema-whatsapp', THEMA_URL . '/assets/js/whatsapp.js', array(), THEMA_VERSIE, array( 'in_footer' => true, 'strategy' => 'defer' ) );
}, 21 );

/**
 * De chat zelf. Wordt in footer.php geplaatst.
 */
function thema_whatsapp_chat() {
	if ( ! thema_chat_tonen() ) {
		return '';
	}
	$s       = thema_instellingen();
	$nummer  = thema_whatsapp_nummer();
	$link    = 'https://wa.me/' . $nummer;
	$naam    = $s['chat_naam'] ?: get_bloginfo( 'name' );
	$foto    = $s['chat_foto'] ? wp_get_attachment_image( (int) $s['chat_foto'], 'thumbnail', false, array( 'class' => 'whatsapp-chat-foto', 'alt' => '', 'loading' => 'lazy' ) ) : '';
	$avatar  = $foto ? $foto : thema_icoon( 'bus', 22 );
	$welkom  = array_filter( array_map( 'trim', preg_split( '/\R/', (string) $s['chat_welkom'] ) ) );
	$keuzes  = array_slice( array_filter( array_map( 'trim', preg_split( '/\R/', (string) $s['chat_suggesties'] ) ) ), 0, 5 );
	$onderin = '' !== thema_onderbalk() ? ' met-onderbalk' : '';

	/* Pagina meesturen: de titel van de pagina, zonder sitenaam. */
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

	ob_start();
	?>
	<div class="whatsapp-chat<?php echo esc_attr( $onderin ); ?>" data-whatsapp-chat data-nummer="<?php echo esc_attr( $nummer ); ?>" data-pagina="<?php echo esc_attr( $pagina ); ?>" data-ballon-na="<?php echo (int) $s['chat_ballon_na']; ?>">
		<div class="whatsapp-chat-venster" id="whatsapp-chat-venster" role="dialog" aria-modal="false" aria-labelledby="whatsapp-chat-naam" data-whatsapp-venster data-lenis-prevent hidden>
			<div class="whatsapp-chat-kop">
				<span class="whatsapp-chat-avatar"><?php echo $avatar; // phpcs:ignore -- wp_get_attachment_image of thema_icoon. ?></span>
				<span class="whatsapp-chat-afzender">
					<span class="whatsapp-chat-naam" id="whatsapp-chat-naam"><?php echo esc_html( $naam ); ?></span>
					<?php if ( $s['chat_status'] ) : ?>
						<span class="whatsapp-chat-status"><?php echo esc_html( $s['chat_status'] ); ?></span>
					<?php endif; ?>
				</span>
				<button type="button" class="whatsapp-chat-sluiten" aria-label="Venster sluiten" data-whatsapp-sluiten><?php echo thema_icoon( 'sluiten', 18 ); ?></button>
			</div>

			<div class="whatsapp-chat-log" data-whatsapp-log aria-live="polite">
				<?php foreach ( $welkom as $regel ) : ?>
					<p class="whatsapp-chat-bericht whatsapp-chat-bericht-bus"><?php echo esc_html( $regel ); ?></p>
				<?php endforeach; ?>
			</div>

			<?php if ( $keuzes ) : ?>
				<div class="whatsapp-chat-keuzes" data-whatsapp-keuzes>
					<?php foreach ( $keuzes as $keuze ) : ?>
						<button type="button" class="whatsapp-chat-keuze" data-whatsapp-keuze><?php echo esc_html( $keuze ); ?></button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<form class="whatsapp-chat-formulier" data-whatsapp-formulier>
				<label class="screen-reader-text" for="whatsapp-chat-vraag">Je vraag</label>
				<textarea class="whatsapp-chat-vraag" id="whatsapp-chat-vraag" rows="1" maxlength="1000" placeholder="<?php echo esc_attr( $s['chat_veld'] ); ?>" data-whatsapp-vraag></textarea>
				<button type="submit" class="whatsapp-chat-verstuur" aria-label="<?php echo esc_attr( $s['chat_knop'] ?: 'Verder in WhatsApp' ); ?>" data-whatsapp-verstuur disabled><?php echo thema_icoon( 'verstuur', 20 ); ?></button>
			</form>

			<div class="whatsapp-chat-voet">
				<span class="whatsapp-chat-uitleg"><?php echo thema_icoon( 'whatsapp', 14 ); ?><?php echo esc_html( $s['chat_knop'] ?: 'Verder in WhatsApp' ); ?></span>
				<?php if ( $s['chat_offerte'] ) : ?>
					<a class="whatsapp-chat-offerte" href="<?php echo esc_url( thema_offerte_url() ); ?>" data-whatsapp-offerte>Liever meteen een prijs?</a>
				<?php endif; ?>
			</div>
		</div>

		<?php if ( $s['chat_ballon'] ) : ?>
			<div class="whatsapp-chat-ballon" data-whatsapp-ballon hidden>
				<button type="button" class="whatsapp-chat-ballon-tekst" data-whatsapp-ballon-open><?php echo esc_html( $s['chat_ballon'] ); ?></button>
				<button type="button" class="whatsapp-chat-ballon-weg" aria-label="Melding verbergen" data-whatsapp-ballon-weg><?php echo thema_icoon( 'sluiten', 12 ); ?></button>
			</div>
		<?php endif; ?>

		<a class="whatsapp-chat-knop" href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener" aria-label="Chat met ons via WhatsApp" aria-controls="whatsapp-chat-venster" aria-expanded="false" data-whatsapp-knop>
			<span class="whatsapp-chat-knop-icoon whatsapp-chat-knop-app"><?php echo thema_icoon( 'whatsapp', 28 ); ?></span>
			<span class="whatsapp-chat-knop-icoon whatsapp-chat-knop-dicht"><?php echo thema_icoon( 'sluiten', 24 ); ?></span>
		</a>
	</div>
	<?php
	return ob_get_clean();
}
