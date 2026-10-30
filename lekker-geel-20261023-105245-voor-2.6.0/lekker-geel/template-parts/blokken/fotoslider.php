<?php
/**
 * Blok: schermbrede fotoslider met schuine knoppen onderaan in het midden.
 */
$b      = $args['blok'];
$fotos  = array_values( array_filter( array_map( 'intval', (array) ( $b['fotos'] ?? array() ) ) ) );
if ( ! $fotos ) {
	if ( current_user_can( 'edit_pages' ) ) {
		echo thema_sectie_open( $b, 'fotoslider' ) . '<p class="melding">Kies foto\'s voor de fotoslider.</p></section>';
	}
	return;
}
$hoogte   = in_array( $b['hoogte'] ?? 'normaal', array( 'normaal', 'hoog', 'laag' ), true ) ? $b['hoogte'] : 'normaal';
$vorm     = 'breed' === ( $b['vorm'] ?? 'kaart' ) ? 'breed' : 'kaart';
$auto     = ! empty( $b['automatisch'] ) && count( $fotos ) > 1;
$interval = max( 3, min( 20, (int) ( $b['interval'] ?? 6 ) ) ) * 1000;
$bijschrift = ! empty( $b['bijschriften'] );
$aantal   = count( $fotos );
echo thema_sectie_open( $b, 'fotoslider', 'fotoslider-' . $hoogte . ' fotoslider-' . $vorm . ( $aantal > 1 ? ' fotoslider-met-knoppen' : '' ) );
?>
	<div class="fotoslider" data-fotoslider<?php echo $auto ? ' data-interval="' . esc_attr( $interval ) . '"' : ''; ?> aria-roledescription="carrousel" aria-label="Foto's">
		<div class="fotoslider-vlak">
			<div class="fotoslider-spoor" aria-live="<?php echo $auto ? 'off' : 'polite'; ?>">
				<?php foreach ( $fotos as $i => $id ) : ?>
					<figure class="fotoslider-dia<?php echo 0 === $i ? ' is-actief' : ''; ?>" aria-roledescription="dia" aria-label="<?php echo esc_attr( ( $i + 1 ) . ' van ' . $aantal ); ?>"<?php echo $i ? ' aria-hidden="true"' : ''; ?>>
						<?php echo thema_afbeelding( $id, 'breed', '', $i > 1 ); ?>
						<?php if ( $bijschrift && wp_get_attachment_caption( $id ) ) : ?>
							<figcaption class="fotoslider-bijschrift"><?php echo esc_html( wp_get_attachment_caption( $id ) ); ?></figcaption>
						<?php endif; ?>
					</figure>
				<?php endforeach; ?>
			</div>
		</div>
		<?php if ( $aantal > 1 ) : ?>
			<div class="fotoslider-nav">
				<button type="button" class="fotoslider-knop fotoslider-vorige" data-vorige aria-label="Vorige foto"><span class="fotoslider-knop-vlak"><?php echo thema_icoon( 'terug', 30, 2.6 ); ?></span></button>
				<span class="fotoslider-teller" aria-hidden="true"><span data-huidig>01</span><span class="fotoslider-totaal">/ <?php echo esc_html( str_pad( (string) $aantal, 2, '0', STR_PAD_LEFT ) ); ?></span></span>
				<button type="button" class="fotoslider-knop fotoslider-volgende" data-volgende aria-label="Volgende foto"><span class="fotoslider-knop-vlak"><?php echo thema_icoon( 'chevron', 30, 2.6 ); ?></span></button>
			</div>
		<?php endif; ?>
	</div>
</section>
