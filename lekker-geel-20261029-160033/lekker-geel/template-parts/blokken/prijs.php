<?php
/**
 * Blok: waar hangt de prijs van af. Stijlen: kaartje, lijst, tegels.
 */
$b      = $args['blok'];
$stijl  = ( $b['stijl'] ?? 'standaard' );
$stijl  = in_array( $stijl, array( 'kaartje', 'lijst', 'tegels' ), true ) ? $stijl : thema_instellingen( 'prijs_stijl' );
$fact   = array_slice( thema_lijst( $b['factoren'] ?? '' ), 0, 4 );
$incl   = thema_lijst( $b['inbegrepen'] ?? '' );
$f_lab  = $b['factoren_label'] ?? 'Je prijs hangt af van';
$i_lab  = $b['inbegrepen_label'] ?? 'Inbegrepen';
$uit    = $b['uitkomst'] ?? '';
$iconen = array( 'groep', 'route', 'kalender', 'klok' );
$getal  = array( 'nul', 'een', 'twee', 'drie', 'vier' );

/* Inbegrepen als zin: a, b, c en d. */
$zin = $incl;
if ( count( $zin ) > 1 ) {
	$laatste = array_pop( $zin );
	$zin     = implode( ', ', $zin ) . ' en ' . $laatste;
} else {
	$zin = implode( '', $zin );
}
$zin = $zin ? ucfirst( mb_strtolower( $zin ) ) . '.' : '';

echo thema_sectie_open( $b, 'prijs', 'prijs-' . $stijl );
?>
	<div class="prijs-raster">
		<div class="prijs-tekst">
			<?php echo thema_kop( $b['kop'] ?? '', 'h2', 'kop-groot' ); ?>
			<?php if ( ! empty( $b['tekst'] ) ) : ?><p class="tekst-groot" data-animeer><?php echo esc_html( $b['tekst'] ); ?></p><?php endif; ?>
			<?php if ( ! empty( $b['knop']['url'] ) ) : ?><span data-animeer class="knop-houder"><?php echo thema_blok_knop( $b['knop'], $b, 'geel', true ); ?></span><?php endif; ?>
		</div>
		<div class="prijs-uitleg" data-animeer>

			<?php if ( 'kaartje' === $stijl ) : ?>
				<div class="prijskaart">
					<div class="prijskaart-kop">
						<span class="prijskaart-titel"><?php echo esc_html( $f_lab ); ?></span>
						<span class="prijskaart-aantal"><?php echo esc_html( ( $getal[ count( $fact ) ] ?? count( $fact ) ) . ' dingen' ); ?></span>
					</div>
					<div class="prijskaart-factoren">
						<?php foreach ( $fact as $i => $f ) : ?>
							<div class="prijsfactor"><span class="prijsfactor-nummer"><?php echo esc_html( str_pad( $i + 1, 2, '0', STR_PAD_LEFT ) ); ?></span><span class="prijsfactor-label"><?php echo esc_html( $f ); ?></span></div>
						<?php endforeach; ?>
					</div>
					<?php if ( $zin ) : ?>
						<p class="prijskaart-inbegrepen"><span class="prijskaart-inbegrepen-label"><?php echo esc_html( $i_lab ); ?></span><?php echo esc_html( $zin ); ?></p>
					<?php endif; ?>
					<?php if ( $uit ) : ?>
						<div class="prijskaart-uitkomst"><span class="prijs-is" aria-hidden="true">=</span><span class="prijs-uitkomst-tekst"><?php echo esc_html( $uit ); ?></span></div>
					<?php endif; ?>
				</div>

			<?php elseif ( 'lijst' === $stijl ) : ?>
				<div class="prijslijst">
					<div class="prijslijst-kop">
						<span class="prijskaart-titel"><?php echo esc_html( $f_lab ); ?></span>
						<?php if ( $incl ) : ?><span class="eyebrow"><?php echo esc_html( $i_lab . ': ' . implode( ', ', $incl ) ); ?></span><?php endif; ?>
					</div>
					<?php foreach ( $fact as $i => $f ) : ?>
						<div class="prijsfactor"><span class="prijsfactor-nummer"><?php echo esc_html( str_pad( $i + 1, 2, '0', STR_PAD_LEFT ) ); ?></span><span class="prijsfactor-label"><?php echo esc_html( $f ); ?></span></div>
					<?php endforeach; ?>
					<?php if ( $uit ) : ?>
						<div class="prijslijst-uitkomst"><span class="prijs-is" aria-hidden="true">=</span><span class="prijs-uitkomst-tekst"><?php echo esc_html( $uit ); ?></span></div>
					<?php endif; ?>
				</div>

			<?php else : ?>
				<div class="prijstegels">
					<div class="prijstegels-label"><?php echo esc_html( $f_lab ); ?></div>
					<div class="prijstegels-raster">
						<?php foreach ( $fact as $i => $f ) : ?>
							<div class="prijstegel"><span class="prijstegel-icoon" aria-hidden="true"><?php echo thema_icoon( $iconen[ $i ] ?? 'vonk', 18, 2 ); ?></span><span class="prijstegel-label"><?php echo esc_html( $f ); ?></span></div>
						<?php endforeach; ?>
					</div>
					<?php if ( $incl ) : ?>
						<div class="prijstegels-inbegrepen">
							<span class="prijstegels-label"><?php echo esc_html( $i_lab ); ?></span>
							<div class="prijstegels-pillen">
								<?php foreach ( $incl as $inc ) : ?><span class="inbegrepen-pil"><span class="inbegrepen-vink" aria-hidden="true">✓</span><?php echo esc_html( $inc ); ?></span><?php endforeach; ?>
							</div>
						</div>
					<?php endif; ?>
					<?php if ( $uit ) : ?>
						<div class="prijstegels-uitkomst"><span class="prijs-is" aria-hidden="true">=</span><span class="prijs-uitkomst-tekst"><?php echo esc_html( $uit ); ?></span></div>
					<?php endif; ?>
				</div>
			<?php endif; ?>

		</div>
	</div>
</section>
