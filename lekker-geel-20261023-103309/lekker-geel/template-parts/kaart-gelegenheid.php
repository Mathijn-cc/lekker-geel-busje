<?php
/**
 * Kaart van een gelegenheid. Varianten: schuiven, raster, klein.
 */
$post    = $args['post'];
$i       = (int) ( $args['index'] ?? 0 );
$variant = $args['variant'] ?? 'schuiven';
$sub     = function_exists( 'get_field' ) ? get_field( 'ondertitel', $post->ID ) : '';
$stijl   = '';
if ( 'schuiven' === $variant ) {
	list( $kantel, $y ) = thema_kaart_kantel( $i );
	$stijl = ' style="--kantel:' . esc_attr( $kantel ) . ';--y:' . esc_attr( $y ) . '"';
}
?>
<a class="gelegenheid gelegenheid-<?php echo esc_attr( $variant ); ?>" href="<?php echo esc_url( get_permalink( $post ) ); ?>" data-cursor="Klikken!"<?php echo 'schuiven' === $variant ? ' data-inspring' : ''; ?><?php echo $stijl; ?>>
	<span class="gelegenheid-kaart">
		<?php echo get_the_post_thumbnail( $post, 'kaart', array( 'class' => 'gelegenheid-foto', 'loading' => 'schuiven' === $variant ? 'eager' : 'lazy', 'style' => 'view-transition-name:gel-' . (int) $post->ID ) ); ?>
		<span class="gelegenheid-waas" aria-hidden="true"></span>
		<?php if ( 'klein' !== $variant ) : ?>
			<span class="gelegenheid-pijl" aria-hidden="true"><?php echo thema_icoon( 'pijl-op', 20, 2.4 ); ?></span>
		<?php endif; ?>
		<span class="gelegenheid-tekst">
			<span class="gelegenheid-naam"><?php echo esc_html( get_the_title( $post ) ); ?></span>
			<?php if ( $sub && 'klein' !== $variant ) : ?><span class="gelegenheid-sub"><?php echo esc_html( $sub ); ?></span><?php endif; ?>
		</span>
	</span>
</a>
