<?php
/**
 * Blok: Gravity Forms formulier.
 */
$b  = $args['blok'];
$id = (int) ( $b['formulier'] ?? 0 );
echo thema_sectie_open( $b, 'formulier' );
?>
	<div class="formulier-binnen">
		<div class="formulier-kop">
			<?php echo thema_kop( $b['kop'] ?? '', 0 === (int) ( $args['index'] ?? 1 ) ? 'h1' : 'h2', 'kop-middel' ); ?>
			<?php if ( ! empty( $b['intro'] ) ) : ?><p class="tekst-groot" data-animeer><?php echo esc_html( $b['intro'] ); ?></p><?php endif; ?>
		</div>
		<div class="formulier-vak" data-animeer>
			<?php
			if ( $id && function_exists( 'gravity_form' ) ) {
				gravity_form( $id, false, false, false, null, true, 0, true );
			} elseif ( current_user_can( 'edit_pages' ) ) {
				echo '<p class="melding">Kies een Gravity Forms formulier in dit blok.</p>';
			}
			?>
		</div>
	</div>
</section>
