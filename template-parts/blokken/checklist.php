<?php
/**
 * Blok: checklist die de bezoeker zelf afvinkt. De vinkjes worden alleen op het eigen apparaat bewaard.
 */
$b     = $args['blok'];
$items = array_values( array_filter( (array) ( $b['items'] ?? array() ), fn( $c ) => ! empty( $c['taak'] ) ) );
if ( ! $items ) {
	return;
}
$sleutel = 'checklist-' . get_the_ID() . '-' . (int) ( $args['index'] ?? 0 );
echo thema_sectie_open( $b, 'checklist' );
?>
	<div class="checklist-raster">
		<div class="checklist-kop">
			<?php echo thema_eyebrow( $b['eyebrow'] ?? '' ); ?>
			<?php echo thema_kop( $b['kop'] ?? '', 'h2', 'kop-middel' ); ?>
			<?php if ( ! empty( $b['intro'] ) ) : ?><p class="tekst-groot" data-animeer><?php echo esc_html( $b['intro'] ); ?></p><?php endif; ?>
			<p class="checklist-voortgang" data-animeer aria-live="polite"><span data-checklist-gedaan>0</span> van <?php echo count( $items ); ?> geregeld</p>
			<?php if ( ! empty( $b['knop']['url'] ) ) : ?>
				<div class="knop-houder" data-animeer><?php echo thema_blok_knop( $b['knop'], $b, 'paars' ); ?></div>
			<?php endif; ?>
		</div>
		<ul class="checklist-lijst" data-checklist="<?php echo esc_attr( $sleutel ); ?>">
			<?php foreach ( $items as $i => $c ) : ?>
				<?php
				$id   = $sleutel . '-' . $i;
				$link = ! empty( $c['link']['url'] ) ? $c['link'] : null;
				?>
				<li class="checklist-punt<?php echo $link ? ' checklist-punt-uitgelicht' : ''; ?>" data-animeer>
					<input type="checkbox" class="checklist-vink" id="<?php echo esc_attr( $id ); ?>" data-checklist-vink>
					<label class="checklist-label" for="<?php echo esc_attr( $id ); ?>">
						<span class="checklist-hokje" aria-hidden="true"><?php echo thema_icoon( 'vink', 16, 3 ); ?></span>
						<span class="checklist-tekst">
							<?php if ( ! empty( $c['wanneer'] ) ) : ?><span class="checklist-wanneer"><?php echo esc_html( $c['wanneer'] ); ?></span><?php endif; ?>
							<span class="checklist-taak"><?php echo esc_html( $c['taak'] ); ?></span>
						</span>
					</label>
					<?php if ( $link ) : ?>
						<a class="checklist-link" href="<?php echo esc_url( $link['url'] ); ?>"<?php echo ! empty( $link['target'] ) ? ' target="_blank" rel="noopener"' : ''; ?>><?php echo esc_html( $link['title'] ?: 'Regel het nu' ); ?> <span aria-hidden="true">→</span></a>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
