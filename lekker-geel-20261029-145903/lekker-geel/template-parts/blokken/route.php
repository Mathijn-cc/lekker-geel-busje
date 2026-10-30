<?php
/**
 * Blok: route. De invulling van een dag als haltes langs een weg, met een bus die meerijdt.
 */
$b       = $args['blok'];
$stappen = array_values( array_filter( (array) ( $b['stappen'] ?? array() ), fn( $s ) => ! empty( $s['titel'] ) ) );
if ( ! $stappen ) {
	return;
}
$aantal = count( $stappen );
echo thema_sectie_open( $b, 'route', 'route-' . $aantal );
?>
	<div class="route-binnen">
		<?php if ( ! empty( $b['eyebrow'] ) || ! empty( $b['kop'] ) || ! empty( $b['intro'] ) ) : ?>
			<div class="route-kop">
				<?php echo thema_eyebrow( $b['eyebrow'] ?? '' ); ?>
				<?php echo thema_kop( $b['kop'] ?? '', 'h2', 'kop-middel' ); ?>
				<?php if ( ! empty( $b['intro'] ) ) : ?><p class="tekst-groot" data-animeer><?php echo esc_html( $b['intro'] ); ?></p><?php endif; ?>
			</div>
		<?php endif; ?>
		<div class="route-weg" data-route style="--aantal:<?php echo (int) $aantal; ?>">
			<div class="route-baan" aria-hidden="true">
				<span class="route-baan-gereden"></span>
				<span class="route-bus"><?php echo thema_icoon( 'bus', 22 ); ?></span>
			</div>
			<ol class="route-stappen">
				<?php foreach ( $stappen as $i => $s ) : ?>
					<li class="route-stap" style="--i:<?php echo (int) $i; ?>" data-route-stap>
						<div class="route-halte">
							<span class="route-halte-icoon"><?php echo thema_icoon( $s['icoon'] ?: 'bus', 26 ); ?></span>
							<span class="route-halte-nummer"><?php echo (int) $i + 1; ?></span>
						</div>
						<div class="route-kaart">
							<?php if ( ! empty( $s['tijd'] ) ) : ?><span class="route-tijd"><?php echo esc_html( $s['tijd'] ); ?></span><?php endif; ?>
							<h3 class="route-titel"><?php echo esc_html( $s['titel'] ); ?></h3>
							<?php if ( ! empty( $s['tekst'] ) ) : ?><p class="route-tekst"><?php echo esc_html( $s['tekst'] ); ?></p><?php endif; ?>
						</div>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>
		<?php if ( ! empty( $b['slot'] ) || ! empty( $b['knop']['url'] ) ) : ?>
			<div class="route-voet" data-animeer>
				<?php if ( ! empty( $b['slot'] ) ) : ?><div class="opmaak"><?php echo wp_kses_post( $b['slot'] ); ?></div><?php endif; ?>
				<?php if ( ! empty( $b['knop']['url'] ) ) : ?><?php echo thema_blok_knop( $b['knop'], $b ); ?><?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
