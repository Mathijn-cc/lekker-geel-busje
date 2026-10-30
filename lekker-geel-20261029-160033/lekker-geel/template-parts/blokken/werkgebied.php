<?php
/**
 * Blok: werkgebied met de kaart van Nederland.
 */
$b      = $args['blok'];
$steden = array();
foreach ( thema_lijst( $b['steden'] ?? '' ) as $i => $regel ) {
	$delen    = array_map( 'trim', explode( '|', $regel ) );
	$steden[] = array( 'naam' => $delen[0], 'tijd' => $delen[1] ?? '', 'thuis' => 0 === $i );
}
echo thema_sectie_open( $b, 'werkgebied' );
?>
	<div class="werkgebied-raster">
		<div class="werkgebied-tekst">
			<?php echo thema_kop( $b['kop'] ?? '', 'h2', 'kop-groot' ); ?>
			<?php if ( ! empty( $b['tekst'] ) ) : ?><p class="tekst-groot" data-animeer><?php echo esc_html( $b['tekst'] ); ?></p><?php endif; ?>
			<?php if ( $steden ) : ?>
				<div class="plaatsticker" data-animeer>
					<div class="plaatsticker-band" data-marquee data-speed="40">
						<?php for ( $k = 0; $k < 2; $k++ ) : ?>
							<div class="plaatsticker-groep"<?php echo $k ? ' aria-hidden="true"' : ''; ?>>
								<?php foreach ( $steden as $stad ) : ?>
									<span class="stadpil<?php echo $stad['thuis'] ? ' stadpil-thuis' : ''; ?>"><span><?php echo esc_html( $stad['naam'] ); ?></span><span class="stadpil-tijd"><?php echo esc_html( $stad['tijd'] ); ?></span></span>
								<?php endforeach; ?>
							</div>
						<?php endfor; ?>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<div class="werkgebied-kaart" data-animeer>
			<div class="werkgebied-kaart-binnen"><?php get_template_part( 'template-parts/nl-kaart' ); ?></div>
			<?php if ( ! empty( $b['legenda'] ) ) : ?>
				<div class="kaartlegenda"><span class="kaartlegenda-stip" aria-hidden="true"></span><?php echo esc_html( $b['legenda'] ); ?></div>
			<?php endif; ?>
		</div>
	</div>
</section>
