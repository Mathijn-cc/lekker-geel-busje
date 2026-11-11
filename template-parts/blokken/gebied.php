<?php
/**
 * Blok: gebied in de stad, zonder foto. Kop en intro naast elkaar, daaronder de plekken
 * als genummerde haltes op een route, elk met een link naar de eigen website.
 */
$b       = $args['blok'];
$plekken = array_values( array_filter( (array) ( $b['plekken'] ?? array() ), fn( $p ) => ! empty( $p['naam'] ) ) );

echo thema_sectie_open( $b, 'gebied' );
?>
	<div class="gebied-binnen">
		<div class="gebied-intro">
			<div class="gebied-kop">
				<?php echo thema_eyebrow( $b['eyebrow'] ?? '' ); ?>
				<?php echo thema_kop( $b['kop'] ?? '', 'h2', 'kop-middel' ); ?>
			</div>
			<?php if ( ! empty( $b['tekst'] ) ) : ?><div class="gebied-tekst"><?php echo thema_alineas( $b['tekst'] ); ?></div><?php endif; ?>
		</div>
		<?php if ( $plekken ) : ?>
			<ol class="gebied-plekken">
				<?php foreach ( $plekken as $i => $p ) : ?>
					<li class="gebied-plek" data-animeer>
						<span class="gebied-plek-halte" aria-hidden="true"><?php echo esc_html( str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
						<?php if ( ! empty( $p['soort'] ) ) : ?><span class="gebied-plek-soort"><?php echo esc_html( $p['soort'] ); ?></span><?php endif; ?>
						<h3 class="gebied-plek-naam"><?php echo esc_html( $p['naam'] ); ?></h3>
						<?php if ( ! empty( $p['tekst'] ) ) : ?><p class="gebied-plek-tekst"><?php echo esc_html( $p['tekst'] ); ?></p><?php endif; ?>
						<?php if ( ! empty( $p['adres'] ) || ! empty( $p['link'] ) ) : ?>
							<p class="gebied-plek-voet">
								<?php if ( ! empty( $p['adres'] ) ) : ?><span class="gebied-plek-adres"><?php echo esc_html( $p['adres'] ); ?></span><?php endif; ?>
								<?php if ( ! empty( $p['link'] ) ) : ?><a class="gebied-plek-link" href="<?php echo esc_url( $p['link'] ); ?>" target="_blank" rel="noopener">Website<span class="screen-reader-text"> van <?php echo esc_html( $p['naam'] ); ?></span> <?php echo thema_icoon( 'extern', 14, 2.4 ); ?></a><?php endif; ?>
							</p>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ol>
		<?php endif; ?>
	</div>
</section>
