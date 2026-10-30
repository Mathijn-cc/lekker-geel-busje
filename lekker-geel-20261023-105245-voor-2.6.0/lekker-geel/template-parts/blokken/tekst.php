<?php
/**
 * Blok: vrije tekst.
 */
$b       = $args['blok'];
$breedte = in_array( $b['breedte'] ?? 'smal', array( 'smal', 'breed', 'twee' ), true ) ? $b['breedte'] : 'smal';
echo thema_sectie_open( $b, 'tekst', 'tekst-' . $breedte );
?>
	<div class="tekst-binnen">
		<?php if ( ! empty( $b['eyebrow'] ) || ! empty( $b['kop'] ) ) : ?>
			<div class="tekst-kop">
				<?php echo thema_eyebrow( $b['eyebrow'] ?? '' ); ?>
				<?php echo thema_kop( $b['kop'] ?? '', 0 === (int) ( $args['index'] ?? 1 ) ? 'h1' : 'h2', 'kop-middel' ); ?>
			</div>
		<?php endif; ?>
		<?php if ( ! empty( $b['tekst'] ) ) : ?>
			<div class="opmaak" data-animeer><?php echo wp_kses_post( $b['tekst'] ); ?></div>
		<?php endif; ?>
	</div>
</section>
