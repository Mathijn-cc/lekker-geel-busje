<?php
/**
 * Blok: feiten op een rij. Vaste gegevens van het bedrijf, als definitielijst.
 */
$b     = $args['blok'];
$rijen = array_values( array_filter( (array) ( $b['rijen'] ?? array() ), fn( $r ) => ! empty( $r['label'] ) && ! empty( $r['waarde'] ) ) );
if ( ! $rijen ) {
	return;
}
echo thema_sectie_open( $b, 'feiten' );
?>
	<div class="feiten-raster">
		<div class="feiten-kop">
			<?php echo thema_eyebrow( $b['eyebrow'] ?? '' ); ?>
			<?php echo thema_kop( $b['kop'] ?? '', 'h2', 'kop-middel' ); ?>
			<?php if ( ! empty( $b['tekst'] ) ) : ?><p class="tekst-groot" data-animeer><?php echo esc_html( $b['tekst'] ); ?></p><?php endif; ?>
		</div>
		<dl class="feiten-lijst" data-animeer>
			<?php foreach ( $rijen as $r ) : ?>
				<div class="feiten-rij">
					<dt class="feiten-label"><?php echo esc_html( $r['label'] ); ?></dt>
					<dd class="feiten-waarde"><?php echo esc_html( $r['waarde'] ); ?></dd>
				</div>
			<?php endforeach; ?>
		</dl>
	</div>
</section>
