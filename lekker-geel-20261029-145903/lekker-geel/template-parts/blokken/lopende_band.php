<?php
/**
 * Blok: lopende band, paars of subtiel. Een tot drie rijen, vanzelf of met scrollen, eventueel schuin.
 */
$b     = $args['blok'];
$items = thema_lijst( $b['items'] ?? '' );
if ( ! $items ) {
	return;
}
$subtiel = 'subtiel' === ( $b['variant'] ?? 'paars' );
$scroll  = 'scroll' === ( $b['beweging'] ?? 'vanzelf' );
$rijen   = max( 1, min( 3, (int) ( $b['rijen'] ?? 1 ) ) );
$schuin  = max( -8, min( 8, (float) ( $b['schuin'] ?? 0 ) ) );
$ruimte  = round( abs( tan( deg2rad( $schuin ) ) ) * 50, 2 );

$stijl = sprintf( ' style="--schuin:%sdeg;--kantel-ruimte:%svw"', esc_attr( $schuin ), esc_attr( $ruimte ) );
echo thema_sectie_open( $b, 'lopende_band', ( $subtiel ? 'band-subtiel' . ( 'kleurrijk' === ( $b['tekstkleur'] ?? 'subtiel' ) ? ' band-kleurrijk' : '' ) : 'band-paars' ) . ( $schuin ? ' band-schuin' : '' ), ( $subtiel ? '' : ' data-donker' ) . $stijl );
?>
	<div class="band-rijen" aria-label="<?php echo esc_attr( implode( ', ', $items ) ); ?>">
		<?php
		for ( $r = 0; $r < $rijen; $r++ ) :
			/* Elke rij begint bij een ander punt, zodat de rijen niet gelijk lopen. */
			$start = array_merge( array_slice( $items, $r % count( $items ) ), array_slice( $items, 0, $r % count( $items ) ) );
			$reeks = array_merge( $start, $start, $start );
			?>
			<div class="band-rij"<?php echo $r ? ' aria-hidden="true"' : ''; ?>>
				<div class="band" data-marquee data-richting="<?php echo $r % 2 ? '-1' : '1'; ?>"<?php echo $scroll ? ' data-scroll' : ''; ?> data-speed="<?php echo $subtiel ? 50 : 34; ?>">
					<?php for ( $k = 0; $k < 2; $k++ ) : ?>
						<div class="band-groep"<?php echo $k ? ' aria-hidden="true"' : ''; ?>>
							<?php foreach ( $reeks as $i => $item ) : ?>
								<span class="band-item"><span class="<?php echo ( $i + $r ) % 2 ? 'band-accent' : 'band-kop'; ?>"><?php echo esc_html( $item ); ?></span><span class="band-ster" aria-hidden="true">✱</span></span>
							<?php endforeach; ?>
						</div>
					<?php endfor; ?>
				</div>
			</div>
		<?php endfor; ?>
	</div>
</section>
