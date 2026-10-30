<?php
/**
 * Blok: contact. Kaarten, formulier en gegevens. Telefoon, e-mail, adres,
 * socials en bedrijfsgegevens komen uit de thema-instellingen.
 */
$b  = $args['blok'];
$s  = thema_instellingen();
$id = (int) ( $b['formulier'] ?? 0 );

$waarde = function ( $sleutel, $standaard = '' ) use ( $b ) {
	return isset( $b[ $sleutel ] ) && '' !== $b[ $sleutel ] && null !== $b[ $sleutel ] ? $b[ $sleutel ] : $standaard;
};
$aan = function ( $sleutel ) use ( $b ) {
	return ! array_key_exists( $sleutel, $b ) || null === $b[ $sleutel ] || '' === $b[ $sleutel ] || (bool) $b[ $sleutel ];
};

$kaarten = array();
if ( $s['telefoon'] ) {
	$kaarten[] = array( 'bellen', thema_telefoon_link(), 'telefoon', $waarde( 'bellen_label', 'Bel ons' ), $s['telefoon'], $waarde( 'bellen_uitleg' ), '' );
}
if ( $s['email'] ) {
	$kaarten[] = array( 'mailen', 'mailto:' . $s['email'], 'mail', $waarde( 'mailen_label', 'Mail ons' ), $s['email'], $waarde( 'mailen_uitleg' ), '' );
}
$whatsapp = preg_replace( '/\D+/', '', (string) $s['whatsapp'] );
if ( $whatsapp && $aan( 'toon_whatsapp' ) ) {
	$kaarten[] = array( 'whatsapp', 'https://wa.me/' . $whatsapp, 'whatsapp', $waarde( 'whatsapp_label', 'App ons' ), 'WhatsApp', $waarde( 'whatsapp_uitleg' ), ' target="_blank" rel="noopener"' );
}

$adres   = trim( (string) $s['adres'] );
$socials = array_filter( array(
	'instagram' => $s['instagram'],
	'facebook'  => $s['facebook'],
	'tiktok'    => $s['tiktok'],
	'youtube'   => $s['youtube'],
) );
$bedrijf = array_filter( array(
	$s['kvk'] ? 'KvK ' . $s['kvk'] : '',
	$s['btw'] ? 'Btw ' . $s['btw'] : '',
) );

$blokken = array();
if ( $adres && $aan( 'toon_adres' ) ) {
	$route = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( get_bloginfo( 'name' ) . ' ' . str_replace( "\n", ' ', $adres ) );
	$html  = '<address>' . esc_html( get_bloginfo( 'name' ) ) . '<br>' . nl2br( esc_html( $adres ), false ) . '</address>';
	if ( $aan( 'toon_route' ) ) {
		$html .= '<a class="contactblok-link" href="' . esc_url( $route ) . '" target="_blank" rel="noopener">Plan je route</a>';
	}
	$blokken[] = array( 'Adres', $html );
}
if ( $socials && $aan( 'toon_socials' ) ) {
	$html = '<div class="socials">';
	foreach ( $socials as $naam => $url ) {
		$html .= sprintf( '<a class="social social-%1$s" href="%2$s" target="_blank" rel="noopener" aria-label="%3$s">%4$s</a>', esc_attr( $naam ), esc_url( $url ), esc_attr( ucfirst( $naam ) ), thema_icoon( $naam, 22 ) );
	}
	$blokken[] = array( 'Volg ons', $html . '</div>' );
}
if ( $bedrijf && $aan( 'toon_bedrijf' ) ) {
	$blokken[] = array( 'Bedrijfsgegevens', '<p>' . implode( '<br>', array_map( 'esc_html', $bedrijf ) ) . '</p>' );
}

$met_formulier = $id && function_exists( 'gravity_form' );

echo thema_sectie_open( $b, 'contact' );
?>
	<div class="contact-binnen">
		<div class="contact-kop">
			<?php echo thema_eyebrow( $waarde( 'eyebrow' ) ); ?>
			<?php echo thema_kop( $waarde( 'kop', 'Neem *contact* met ons op' ), 0 === (int) ( $args['index'] ?? 1 ) ? 'h1' : 'h2', 'kop-middel' ); ?>
			<?php if ( $waarde( 'intro' ) ) : ?><p class="tekst-groot contact-intro" data-animeer><?php echo esc_html( $waarde( 'intro' ) ); ?></p><?php endif; ?>
			<?php if ( $aan( 'toon_offerte' ) && $waarde( 'offerte_knop' ) ) : ?>
				<p class="contact-offerte" data-animeer><?php echo esc_html( $waarde( 'offerte_tekst' ) ); ?> <a href="<?php echo esc_url( thema_offerte_url() ); ?>"><?php echo esc_html( $waarde( 'offerte_knop' ) ); ?></a></p>
			<?php endif; ?>
		</div>

		<?php if ( $kaarten ) : ?>
			<div class="contactkaarten contactkaarten-<?php echo count( $kaarten ); ?>" data-animeer>
				<?php foreach ( $kaarten as $k ) : ?>
					<a class="contactkaart contactkaart-<?php echo esc_attr( $k[0] ); ?>" href="<?php echo esc_attr( $k[1] ); ?>"<?php echo $k[6]; // phpcs:ignore ?>>
						<span class="contactkaart-icoon" aria-hidden="true"><?php echo thema_icoon( $k[2], 24 ); ?></span>
						<span class="contactkaart-label"><?php echo esc_html( $k[3] ); ?></span>
						<span class="contactkaart-waarde"><?php echo esc_html( $k[4] ); ?></span>
						<?php if ( $k[5] ) : ?><span class="contactkaart-uitleg"><?php echo esc_html( $k[5] ); ?></span><?php endif; ?>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php if ( $met_formulier || $blokken ) : ?>
			<div class="contactraster<?php echo $met_formulier ? '' : ' contactraster-zonder-formulier'; ?>">
				<?php if ( $met_formulier ) : ?>
					<div class="contactformulier-kop">
						<?php echo thema_kop( $waarde( 'formulier_kop' ), 'h2', 'kop-middel' ); ?>
						<?php if ( $waarde( 'formulier_intro' ) ) : ?><p class="tekst-groot" data-animeer><?php echo esc_html( $waarde( 'formulier_intro' ) ); ?></p><?php endif; ?>
					</div>
					<div class="formulier-vak" data-animeer>
						<?php gravity_form( $id, false, false, false, null, true, 0, true ); ?>
					</div>
				<?php elseif ( $id && current_user_can( 'edit_pages' ) ) : ?>
					<p class="melding">Gravity Forms is niet actief.</p>
				<?php endif; ?>
				<?php if ( $blokken ) : ?>
					<div class="contactgegevens" data-animeer>
						<?php foreach ( $blokken as $blok ) : ?>
							<div class="contactblok">
								<p class="contactblok-titel"><?php echo esc_html( $blok[0] ); ?></p>
								<?php echo $blok[1]; // phpcs:ignore ?>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
