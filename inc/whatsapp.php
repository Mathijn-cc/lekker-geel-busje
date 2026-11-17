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
			<span class="whatsapp-chat-knop-icoon whatsapp-chat-knop-app"><svg class="icoon" width="30" height="30" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.17-.17.2-.35.22-.65.07-.3-.15-1.26-.46-2.4-1.48-.89-.79-1.49-1.77-1.66-2.07-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.03-.52-.07-.15-.67-1.61-.92-2.21-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48 0 1.46 1.07 2.88 1.21 3.07.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.69.63.71.23 1.36.2 1.87.12.57-.09 1.76-.72 2.01-1.41.25-.7.25-1.29.17-1.41-.07-.13-.27-.2-.57-.35M12.05 21.79h-.01a9.87 9.87 0 0 1-5.03-1.38l-.36-.21-3.74.98 1-3.65-.24-.37a9.86 9.86 0 0 1-1.51-5.26c0-5.45 4.44-9.88 9.89-9.88 2.64 0 5.12 1.03 6.99 2.9a9.82 9.82 0 0 1 2.89 6.99c0 5.45-4.44 9.88-9.89 9.88m8.41-18.3A11.82 11.82 0 0 0 12.05 0C5.5 0 .16 5.34.16 11.89c0 2.1.55 4.14 1.59 5.95L.06 24l6.31-1.65a11.88 11.88 0 0 0 5.68 1.45h.01c6.55 0 11.89-5.34 11.89-11.89 0-3.18-1.24-6.17-3.48-8.42"/></svg></span>
			<span class="whatsapp-chat-knop-icoon whatsapp-chat-knop-dicht"><?php echo thema_icoon( 'sluiten', 24 ); ?></span>
		</a>
	</div>
	<?php
	return ob_get_clean();
}
