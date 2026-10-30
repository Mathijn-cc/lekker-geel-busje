<?php
/**
 * Blok: intro boven de voordelen.
 */
$b = $args['blok'];
echo thema_sectie_open( $b, 'aan_boord' );
?>
	<div class="aan-boord-raster">
		<div class="aan-boord-kop">
			<?php echo thema_eyebrow( $b['eyebrow'] ?? '' ); ?>
			<?php echo thema_kop( $b['kop'] ?? '', 'h2', 'kop-groot' ); ?>
		</div>
		<?php if ( ! empty( $b['tekst'] ) ) : ?><p class="tekst-groot" data-animeer><?php echo esc_html( $b['tekst'] ); ?></p><?php endif; ?>
	</div>
</section>
