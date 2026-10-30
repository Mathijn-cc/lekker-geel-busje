<?php
/**
 * Blok: over ons met fotostapel.
 */
$b       = $args['blok'];
$beelden = array_values( array_filter( (array) ( $b['beelden'] ?? array() ) ) );
echo thema_sectie_open( $b, 'over_ons' );
?>
	<div class="rij">
		<div class="kolom kolom-plak over-tekst">
			<?php echo thema_eyebrow( $b['eyebrow'] ?? '' ); ?>
			<?php echo thema_kop( $b['kop'] ?? '', 'h2', 'kop-groot' ); ?>
			<?php if ( ! empty( $b['tekst_1'] ) ) : ?><p class="tekst-groot" data-animeer><?php echo esc_html( $b['tekst_1'] ); ?></p><?php endif; ?>
			<?php if ( ! empty( $b['tekst_2'] ) ) : ?><p class="tekst-groot tekst-zacht" data-animeer><?php echo esc_html( $b['tekst_2'] ); ?></p><?php endif; ?>
			<?php if ( ! empty( $b['knop']['url'] ) ) : ?><span data-animeer class="knop-houder"><?php echo thema_blok_knop( $b['knop'], $b, 'geel', true ); ?></span><?php endif; ?>
		</div>
		<?php if ( $beelden ) : ?>
			<div class="kolom fotostapel">
				<div data-parallax="-30">
					<div class="fotostapel-groot" data-schuif="2"><?php echo thema_afbeelding( $beelden[0], 'breed' ); ?></div>
				</div>
				<?php if ( count( $beelden ) > 1 ) : ?>
					<div class="fotostapel-rij">
						<div data-parallax="40">
							<div class="fotostapel-klein fotostapel-links" data-schuif="-5"><?php echo thema_afbeelding( $beelden[1], 'kaart' ); ?></div>
						</div>
						<?php if ( ! empty( $beelden[2] ) ) : ?>
							<div data-parallax="100" class="fotostapel-zakt">
								<div class="fotostapel-klein fotostapel-rechts" data-schuif="6"><?php echo thema_afbeelding( $beelden[2], 'kaart' ); ?></div>
							</div>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
