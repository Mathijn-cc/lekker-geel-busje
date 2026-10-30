<?php
/**
 * Homepage, opgebouwd met blokken.
 */
get_header();

while ( have_posts() ) :
	the_post();
	if ( ! thema_blokken() ) :
		?>
		<section class="sectie sectie-tekst sectie-paginakop tekst-smal">
			<div class="tekst-binnen">
				<div class="tekst-kop"><?php echo thema_kop( get_the_title(), 'h1', 'kop-middel' ); ?></div>
				<div class="opmaak"><?php the_content(); ?></div>
			</div>
		</section>
		<?php
	endif;
endwhile;

get_footer();
