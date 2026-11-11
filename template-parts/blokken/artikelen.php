<?php
/**
 * Blok: kaarten van blogartikelen, zelf gekozen of de nieuwste uit een onderwerp.
 */
$b       = $args['blok'];
$aantal  = max( 1, min( 6, (int) ( $b['aantal'] ?? 3 ) ) );
$posts   = thema_blog_artikelen( $b['keuze'] ?? array(), (int) ( $b['onderwerp'] ?? 0 ), $aantal, is_singular( 'post' ) ? get_the_ID() : 0 );
if ( ! $posts ) {
	return;
}
$blog = thema_blog_pagina();
$knop = ! empty( $b['knop']['url'] ) ? $b['knop'] : ( $blog ? array( 'title' => 'Alle artikelen', 'url' => get_permalink( $blog ) ) : array() );
echo thema_sectie_open( $b, 'artikelen' );
?>
	<div class="artikelen-binnen">
		<div class="artikelen-kop">
			<?php echo thema_eyebrow( $b['eyebrow'] ?? '' ); ?>
			<?php echo thema_kop( $b['kop'] ?? '', 'h2', 'kop-middel' ); ?>
			<?php if ( ! empty( $b['intro'] ) ) : ?><p class="tekst-groot" data-animeer><?php echo esc_html( $b['intro'] ); ?></p><?php endif; ?>
			<?php if ( ! empty( $knop['url'] ) ) : ?><div class="knop-houder" data-animeer><?php echo thema_blok_knop( $knop, $b, 'paars' ); ?></div><?php endif; ?>
		</div>
		<div class="artikelen-raster artikelen-<?php echo (int) min( 3, count( $posts ) ); ?>">
			<?php
			foreach ( $posts as $post_kaart ) {
				get_template_part( 'template-parts/kaart-blog', null, array( 'post' => $post_kaart ) );
			}
			?>
		</div>
	</div>
</section>
