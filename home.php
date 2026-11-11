<?php
/**
 * Blogoverzicht (/blog/) en onderwerpen (/blog/onderwerp/{slug}/).
 * Kop en intro komen van de berichtenpagina (of de categorie), daaronder de artikelen
 * en tot slot de blokken van de berichtenpagina, zoals de offerte.
 */
get_header();

$blog      = thema_blog_pagina();
$is_cat    = is_category();
$pagina    = max( 1, (int) get_query_var( 'paged' ) );
$titel     = $is_cat ? single_cat_title( '', false ) : ( $blog ? get_the_title( $blog ) : 'Blog' );
$intro     = $is_cat ? category_description() : ( $blog ? apply_filters( 'the_content', $blog->post_content ) : '' ); // phpcs:ignore
$onderwerpen = get_categories( array( 'hide_empty' => true, 'exclude' => array( (int) get_option( 'default_category' ) ) ) );
$blog_url  = $blog ? get_permalink( $blog ) : home_url( '/' );
?>
<section class="sectie sectie-blogkop">
	<div class="blogkop-binnen">
		<?php if ( $is_cat ) : ?>
			<a class="terugpil" href="<?php echo esc_url( $blog_url ); ?>"><span aria-hidden="true">←</span> Alle artikelen</a>
		<?php endif; ?>
		<?php echo thema_eyebrow( $is_cat ? 'Onderwerp' : 'Blog' ); ?>
		<?php
		$kop = $titel;
		if ( ! $is_cat && $blog && function_exists( 'get_field' ) && get_field( 'blog_kop', $blog->ID ) ) {
			$kop = get_field( 'blog_kop', $blog->ID );
		}
		echo thema_kop( $kop, 'h1', 'kop-middel' );
		?>
		<?php if ( $intro && 1 === $pagina ) : ?>
			<div class="opmaak tekst-groot blogkop-intro" data-animeer><?php echo wp_kses_post( $intro ); ?></div>
		<?php endif; ?>
		<?php if ( count( $onderwerpen ) > 0 ) : ?>
			<nav class="blogkop-onderwerpen" aria-label="Onderwerpen" data-animeer>
				<a class="onderwerp-pil<?php echo $is_cat ? '' : ' is-actief'; ?>" href="<?php echo esc_url( $blog_url ); ?>"<?php echo $is_cat ? '' : ' aria-current="page"'; ?>>Alles</a>
				<?php foreach ( $onderwerpen as $o ) : ?>
					<?php $actief = $is_cat && get_queried_object_id() === $o->term_id; ?>
					<a class="onderwerp-pil<?php echo $actief ? ' is-actief' : ''; ?>" href="<?php echo esc_url( get_category_link( $o ) ); ?>"<?php echo $actief ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $o->name ); ?></a>
				<?php endforeach; ?>
			</nav>
		<?php endif; ?>
	</div>
</section>

<section class="sectie sectie-blogoverzicht">
	<div class="blogoverzicht-binnen">
		<?php if ( have_posts() ) : ?>
			<div class="blogoverzicht-raster">
				<?php
				$i = 0;
				while ( have_posts() ) :
					the_post();
					$uitgelicht = 0 === $i && 1 === $pagina && ! $is_cat;
					get_template_part( 'template-parts/kaart-blog', null, array(
						'post'    => get_post(),
						'variant' => $uitgelicht ? 'uitgelicht' : 'standaard',
						'kop'     => 'h2',
					) );
					$i++;
				endwhile;
				?>
			</div>
			<?php
			the_posts_pagination( array(
				'mid_size'           => 1,
				'prev_text'          => '<span aria-hidden="true">←</span> Nieuwer',
				'next_text'          => 'Ouder <span aria-hidden="true">→</span>',
				'screen_reader_text' => 'Meer artikelen',
				'aria_label'         => 'Paginering',
			) );
			?>
		<?php else : ?>
			<p class="tekst-groot">Hier komen binnenkort de eerste artikelen.</p>
		<?php endif; ?>
	</div>
</section>

<?php
if ( $blog && 1 === $pagina ) {
	thema_blokken( $blog->ID, false );
}
get_footer();
