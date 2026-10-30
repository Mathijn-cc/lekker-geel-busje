<?php
/**
 * Blok: gelegenheden, meeschuivend of als raster.
 */
$b        = $args['blok'];
$items    = thema_gelegenheden( (array) ( $b['keuze'] ?? array() ) );
$weergave = ( $b['weergave'] ?? 'schuiven' ) === 'raster' ? 'raster' : 'schuiven';
if ( ! $items ) {
	return;
}
echo thema_sectie_open( $b, 'gelegenheden', 'gelegenheden-' . $weergave, ' data-donker' . ( 'schuiven' === $weergave ? ' data-gel-sectie' : '' ) );
?>
	<div class="gelegenheden-binnen">
		<div class="gelegenheden-kop">
			<?php echo thema_kop( $b['kop'] ?? '', 'h2', 'kop-groot' ); ?>
		</div>
		<div class="gelegenheden-venster">
			<div class="gelegenheden-spoor" data-gel-spoor>
				<?php
				foreach ( $items as $i => $post ) {
					get_template_part( 'template-parts/kaart-gelegenheid', null, array( 'post' => $post, 'index' => $i, 'variant' => $weergave ) );
				}
				?>
			</div>
		</div>
	</div>
</section>
