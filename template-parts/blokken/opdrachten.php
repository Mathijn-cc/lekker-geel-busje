<?php
/**
 * Blok: opdrachten als genummerde kaarten, bijvoorbeeld spelletjes voor in de bus.
 */
$b     = $args['blok'];
$items = array_values( array_filter( (array) ( $b['items'] ?? array() ), fn( $o ) => ! empty( $o['titel'] ) ) );
if ( ! $items ) {
	return;
}
echo thema_sectie_open( $b, 'opdrachten' );
?>
	<div class="opdrachten-binnen">
		<?php if ( ! empty( $b['eyebrow'] ) || ! empty( $b['kop'] ) || ! empty( $b['intro'] ) ) : ?>
			<div class="opdrachten-kop">
				<?php echo thema_eyebrow( $b['eyebrow'] ?? '' ); ?>
				<?php echo thema_kop( $b['kop'] ?? '', 'h2', 'kop-middel' ); ?>
				<?php if ( ! empty( $b['intro'] ) ) : ?><p class="tekst-groot" data-animeer><?php echo esc_html( $b['intro'] ); ?></p><?php endif; ?>
			</div>
		<?php endif; ?>
		<ol class="opdrachten-raster">
			<?php foreach ( $items as $i => $o ) : ?>
				<li class="opdracht" data-animeer style="--i:<?php echo (int) $i; ?>">
					<span class="opdracht-nummer" aria-hidden="true"><?php echo (int) $i + 1; ?></span>
					<?php if ( ! empty( $o['soort'] ) ) : ?><span class="opdracht-soort"><?php echo esc_html( $o['soort'] ); ?></span><?php endif; ?>
					<h3 class="opdracht-titel"><?php echo esc_html( $o['titel'] ); ?></h3>
					<?php if ( ! empty( $o['tekst'] ) ) : ?><p class="opdracht-tekst"><?php echo esc_html( $o['tekst'] ); ?></p><?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>
		<?php if ( ! empty( $b['noot'] ) ) : ?>
			<p class="opdrachten-noot" data-animeer><?php echo thema_icoon( 'schild', 20 ); ?><span><?php echo esc_html( $b['noot'] ); ?></span></p>
		<?php endif; ?>
	</div>
</section>
