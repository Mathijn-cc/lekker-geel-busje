<?php
/**
 * Blok: feiten op een rij. Vaste gegevens van het bedrijf als kaartjes met icoon, in een definitielijst.
 */
$b     = $args['blok'];
$rijen = array_values( array_filter( (array) ( $b['rijen'] ?? array() ), fn( $r ) => ! empty( $r['label'] ) && ! empty( $r['waarde'] ) ) );
if ( ! $rijen ) {
	return;
}
$iconen = array( 'bus', 'groep', 'schild', 'speaker', 'glas', 'route', 'hart', 'vonk' );
echo thema_sectie_open( $b, 'feiten' );
?>
	<div class="feiten-raster">
		<div class="feiten-kop">
			<?php echo thema_eyebrow( $b['eyebrow'] ?? '' ); ?>
			<?php echo thema_kop( $b['kop'] ?? '', 'h2', 'kop-middel' ); ?>
			<?php if ( ! empty( $b['tekst'] ) ) : ?><p class="tekst-groot" data-animeer><?php echo esc_html( $b['tekst'] ); ?></p><?php endif; ?>
		</div>
		<dl class="feiten-lijst">
			<?php foreach ( $rijen as $i => $r ) : ?>
				<div class="feit<?php echo 0 === $i ? ' feit-uitgelicht' : ''; ?>" data-animeer style="--i:<?php echo (int) $i; ?>">
					<span class="feit-badge" aria-hidden="true"><?php echo thema_icoon( ! empty( $r['icoon'] ) ? $r['icoon'] : $iconen[ $i % count( $iconen ) ], 24, 2 ); ?></span>
					<dt class="feit-label"><?php echo esc_html( $r['label'] ); ?></dt>
					<dd class="feit-waarde"><?php echo esc_html( $r['waarde'] ); ?></dd>
				</div>
			<?php endforeach; ?>
		</dl>
	</div>
</section>
