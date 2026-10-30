<?php
/**
 * Blok: schermbrede foto die openklapt tijdens scrollen.
 */
$b = $args['blok'];
if ( empty( $b['beeld'] ) ) {
	return;
}
$focus = ! empty( $b['focus'] ) ? $b['focus'] : 'center 40%';
echo thema_sectie_open( $b, 'brede_foto', 'brede-foto-' . sanitize_html_class( $b['hoogte'] ?? 'normaal' ) );
?>
	<div class="brede-foto-vlak" data-wide>
		<div class="brede-foto-zoom" data-zoom>
			<?php echo thema_afbeelding( $b['beeld'], 'breed', '', true, array( 'style' => 'object-position:' . esc_attr( $focus ) ) ); ?>
		</div>
	</div>
</section>
