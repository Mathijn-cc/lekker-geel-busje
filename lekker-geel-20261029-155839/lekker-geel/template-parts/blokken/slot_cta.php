<?php
/**
 * Blok: gele afsluiter die tijdens scrollen openklapt.
 */
$b    = $args['blok'];
$knop = $b['knop'] ?? array();
if ( empty( $knop['url'] ) ) {
	$knop = array( 'title' => thema_instellingen( 'menu_knop' ), 'url' => thema_offerte_url() );
}
echo thema_sectie_open( $b, 'slot_cta' );
?>
	<div class="slot-vlak" data-slot-cta>
		<h2 class="slot-kop">
			<?php if ( ! empty( $b['kop'] ) ) : ?><span class="slot-masker"><span data-slot-woord><?php echo esc_html( $b['kop'] ); ?></span></span><br><?php endif; ?>
			<?php if ( ! empty( $b['pil'] ) ) : ?><span class="slot-pil" data-slot-pil><span><?php echo esc_html( $b['pil'] ); ?></span></span><?php endif; ?>
		</h2>
		<?php if ( ! empty( $b['tekst'] ) ) : ?><p class="slot-tekst" data-slot-in><?php echo esc_html( $b['tekst'] ); ?></p><?php endif; ?>
		<span data-slot-knop class="knop-houder"><?php echo thema_blok_knop( $knop, $b, 'paars' ); ?></span>
	</div>
</section>
