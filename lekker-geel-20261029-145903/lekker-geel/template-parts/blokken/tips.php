<?php
/**
 * Blok: tips als kaarten met foto, tekst en een link naar de website.
 */
$b     = $args['blok'];
$items = array_values( array_filter( (array) ( $b['items'] ?? array() ), fn( $t ) => ! empty( $t['titel'] ) ) );
if ( ! $items ) {
	return;
}
$kolommen = '2' === (string) ( $b['kolommen'] ?? '3' ) ? 2 : 3;
echo thema_sectie_open( $b, 'tips', 'tips-' . $kolommen );
?>
	<div class="tips-binnen">
		<?php if ( ! empty( $b['eyebrow'] ) || ! empty( $b['kop'] ) || ! empty( $b['intro'] ) ) : ?>
			<div class="tips-kop">
				<?php echo thema_eyebrow( $b['eyebrow'] ?? '' ); ?>
				<?php echo thema_kop( $b['kop'] ?? '', 'h2', 'kop-middel' ); ?>
				<?php if ( ! empty( $b['intro'] ) ) : ?><p class="tekst-groot" data-animeer><?php echo esc_html( $b['intro'] ); ?></p><?php endif; ?>
			</div>
		<?php endif; ?>
		<div class="tips-raster">
			<?php foreach ( $items as $i => $t ) : ?>
				<article class="tip" data-animeer>
					<?php if ( ! empty( $t['beeld'] ) ) : ?>
						<div class="tip-beeld"><?php echo thema_foto_met_credit( $t['beeld'], 'kaart', ! empty( $t['tijdelijk'] ) ); ?></div>
					<?php endif; ?>
					<div class="tip-tekst">
						<?php if ( ! empty( $t['soort'] ) ) : ?><span class="tip-soort"><?php echo esc_html( $t['soort'] ); ?></span><?php endif; ?>
						<h3 class="tip-titel"><?php echo esc_html( $t['titel'] ); ?></h3>
						<?php if ( ! empty( $t['tekst'] ) ) : ?><p class="tip-omschrijving"><?php echo esc_html( $t['tekst'] ); ?></p><?php endif; ?>
						<?php if ( ! empty( $t['info'] ) ) : ?><p class="tip-info"><?php echo esc_html( $t['info'] ); ?></p><?php endif; ?>
						<?php if ( ! empty( $t['link'] ) ) : ?>
							<a class="tip-link" href="<?php echo esc_url( $t['link'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $t['linktekst'] ?: 'Bekijk website' ); ?><span class="screen-reader-text"> van <?php echo esc_html( $t['titel'] ); ?></span> <?php echo thema_icoon( 'extern', 15, 2.4 ); ?></a>
						<?php endif; ?>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
