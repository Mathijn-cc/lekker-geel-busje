<?php
/**
 * Blok: uitgelicht. Een opvallende banner voor een actie, met foto, punten en een sticker.
 */
$b   = $args['blok'];
$tot = preg_replace( '/\D/', '', (string) ( $b['tonen_tot'] ?? '' ) );
if ( 8 === strlen( $tot ) && $tot < wp_date( 'Ymd' ) ) {
	return;
}
$knop    = $b['knop'] ?? array();
$punten  = thema_lijst( $b['punten'] ?? '' );
$beeld   = (int) ( $b['beeld'] ?? 0 );
$sticker = trim( (string) ( $b['sticker_groot'] ?? '' ) );
echo thema_sectie_open( $b, 'uitgelicht' );
?>
	<div class="rij">
		<div class="uitgelicht-kaart<?php echo $beeld ? ' met-beeld' : ''; ?>" data-animeer>
			<div class="uitgelicht-tekst">
				<?php echo thema_eyebrow( $b['eyebrow'] ?? '' ); ?>
				<?php echo thema_kop( $b['kop'] ?? '', 'h2', 'kop-middel' ); ?>
				<?php if ( ! empty( $b['tekst'] ) ) : ?>
					<p class="uitgelicht-intro"><?php echo esc_html( $b['tekst'] ); ?></p>
				<?php endif; ?>
				<?php if ( $punten ) : ?>
					<ul class="uitgelicht-punten">
						<?php foreach ( $punten as $punt ) : ?>
							<li><span class="uitgelicht-vink" aria-hidden="true"><?php echo thema_icoon( 'vink', 12, 3 ); ?></span><?php echo esc_html( $punt ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
				<?php if ( ! empty( $knop['url'] ) ) : ?>
					<div class="knop-houder"><?php echo thema_blok_knop( $knop, $b, 'geel' ); ?></div>
				<?php endif; ?>
			</div>
			<?php if ( $beeld ) : ?>
				<div class="uitgelicht-beeld">
					<?php echo thema_afbeelding( $beeld, 'large', '', true, array( 'sizes' => thema_sizes( 'helft' ) ) ); ?>
					<?php if ( '' !== $sticker ) : ?>
						<span class="uitgelicht-sticker" aria-hidden="true">
							<?php if ( ! empty( $b['sticker_klein'] ) ) : ?><span><?php echo esc_html( $b['sticker_klein'] ); ?></span><?php endif; ?>
							<strong><?php echo esc_html( $sticker ); ?></strong>
						</span>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>
