<?php
/**
 * Blok: persoonlijke quote naast een foto.
 */
$b    = $args['blok'];
$kant = 'rechts' === ( $b['foto_kant'] ?? '' ) ? 'rechts' : 'links';
echo thema_sectie_open( $b, 'citaat' );
?>
	<div class="citaat-raster foto-<?php echo esc_attr( $kant ); ?>">
		<?php if ( ! empty( $b['foto'] ) ) : ?>
			<div class="citaat-foto-houder" data-parallax="-20">
				<div class="citaat-foto" data-schuif="-2"><?php echo thema_afbeelding( $b['foto'], 'breed', '', true, array( 'sizes' => thema_sizes( 'helft' ) ) ); ?></div>
				<?php if ( ! empty( $b['naam'] ) ) : ?>
					<span class="citaat-kaartje" data-animeer>
						<strong><?php echo esc_html( $b['naam'] ); ?></strong>
						<?php if ( ! empty( $b['rol'] ) ) : ?><span><?php echo esc_html( $b['rol'] ); ?></span><?php endif; ?>
					</span>
				<?php endif; ?>
			</div>
		<?php endif; ?>
		<div class="citaat-tekst">
			<?php echo thema_eyebrow( $b['eyebrow'] ?? '' ); ?>
			<span class="citaat-teken" aria-hidden="true">&ldquo;</span>
			<figure class="citaat-figuur">
				<blockquote class="citaat-quote" data-animeer><p><?php echo thema_accent( $b['citaat'] ?? '' ); ?></p></blockquote>
				<?php if ( ! empty( $b['naam'] ) ) : ?>
					<figcaption class="citaat-naam" data-animeer><?php echo esc_html( $b['naam'] ); ?><?php if ( ! empty( $b['rol'] ) ) : ?>, <span><?php echo esc_html( $b['rol'] ); ?></span><?php endif; ?></figcaption>
				<?php endif; ?>
			</figure>
		</div>
	</div>
</section>
