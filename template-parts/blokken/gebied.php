<?php
/**
 * Blok: gebied in de stad met foto, tekst en plekken met een link naar hun website.
 */
$b       = $args['blok'];
$plekken = array_values( array_filter( (array) ( $b['plekken'] ?? array() ), fn( $p ) => ! empty( $p['naam'] ) ) );
echo thema_sectie_open( $b, 'gebied', ! empty( $b['spiegel'] ) ? 'gebied-spiegel' : '' );
?>
	<div class="gebied-raster">
		<?php if ( ! empty( $b['beeld'] ) ) : ?>
			<div class="gebied-beeld" data-animeer><?php echo thema_foto_met_credit( $b['beeld'], 'breed', ! empty( $b['tijdelijk'] ) ); ?></div>
		<?php endif; ?>
		<div class="gebied-tekst">
			<?php echo thema_eyebrow( $b['eyebrow'] ?? '' ); ?>
			<?php echo thema_kop( $b['kop'] ?? '', 'h2', 'kop-middel' ); ?>
			<?php echo thema_alineas( $b['tekst'] ?? '' ); ?>
			<?php if ( $plekken ) : ?>
				<ul class="gebied-plekken">
					<?php foreach ( $plekken as $p ) : ?>
						<li class="gebied-plek" data-animeer>
							<div class="gebied-plek-kop">
								<h3 class="gebied-plek-naam"><?php echo esc_html( $p['naam'] ); ?></h3>
								<?php if ( ! empty( $p['soort'] ) ) : ?><span class="gebied-plek-soort"><?php echo esc_html( $p['soort'] ); ?></span><?php endif; ?>
							</div>
							<?php if ( ! empty( $p['tekst'] ) ) : ?><p class="gebied-plek-tekst"><?php echo esc_html( $p['tekst'] ); ?></p><?php endif; ?>
							<?php if ( ! empty( $p['adres'] ) || ! empty( $p['link'] ) ) : ?>
								<p class="gebied-plek-voet">
									<?php if ( ! empty( $p['adres'] ) ) : ?><span class="gebied-plek-adres"><?php echo esc_html( $p['adres'] ); ?></span><?php endif; ?>
									<?php if ( ! empty( $p['link'] ) ) : ?><a class="gebied-plek-link" href="<?php echo esc_url( $p['link'] ); ?>" target="_blank" rel="noopener">Website<span class="screen-reader-text"> van <?php echo esc_html( $p['naam'] ); ?></span> <?php echo thema_icoon( 'extern', 14, 2.4 ); ?></a><?php endif; ?>
								</p>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</div>
</section>
