<?php
/**
 * Kaart van een artikel: foto, onderwerp, titel, korte tekst, datum en leestijd.
 * Varianten: standaard en uitgelicht (groot, op het blogoverzicht). $args['kop'] is het kopniveau van de titel.
 */
$kaart   = $args['post'];
$variant = $args['variant'] ?? 'standaard';
$kop     = in_array( $args['kop'] ?? '', array( 'h2', 'h3' ), true ) ? $args['kop'] : 'h3';
$cat     = thema_blog_categorie( $kaart );
$tekst   = has_excerpt( $kaart ) ? get_the_excerpt( $kaart ) : wp_trim_words( wp_strip_all_tags( $kaart->post_content ), 24, '…' );
?>
<article class="blogkaart blogkaart-<?php echo esc_attr( $variant ); ?>" data-animeer>
	<a class="blogkaart-beeld" href="<?php echo esc_url( get_permalink( $kaart ) ); ?>" tabindex="-1" aria-hidden="true">
		<?php
		if ( has_post_thumbnail( $kaart ) ) {
			echo get_the_post_thumbnail( $kaart, 'uitgelicht' === $variant ? 'breed' : 'kaart-m', array(
				'loading' => 'lazy',
				'sizes'   => 'uitgelicht' === $variant ? '(min-width: 1000px) 760px, 100vw' : thema_sizes( 'tip' ),
			) );
		}
		?>
	</a>
	<div class="blogkaart-tekst">
		<?php if ( $cat && 'algemeen' !== $cat->slug ) : ?>
			<span class="blogkaart-onderwerp"><?php echo esc_html( $cat->name ); ?></span>
		<?php endif; ?>
		<<?php echo $kop; ?> class="blogkaart-titel"><a href="<?php echo esc_url( get_permalink( $kaart ) ); ?>"><?php echo esc_html( get_the_title( $kaart ) ); ?></a></<?php echo $kop; ?>>
		<?php if ( $tekst ) : ?><p class="blogkaart-samenvatting"><?php echo esc_html( $tekst ); ?></p><?php endif; ?>
		<p class="blogkaart-meta"><?php echo thema_blog_datums( $kaart, false ); ?> <span aria-hidden="true">·</span> <?php echo (int) thema_leestijd( $kaart ); ?> min lezen</p>
	</div>
</article>
