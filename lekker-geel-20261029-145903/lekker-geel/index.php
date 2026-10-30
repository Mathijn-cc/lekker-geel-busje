<?php
/**
 * Terugval voor overzichten.
 */
get_header();
?>
<section class="sectie sectie-tekst sectie-paginakop tekst-smal">
	<div class="tekst-binnen">
		<div class="tekst-kop"><?php echo thema_kop( wp_strip_all_tags( get_the_archive_title() ?: get_bloginfo( 'name' ) ), 'h1', 'kop-middel' ); ?></div>
		<div class="opmaak">
			<?php if ( have_posts() ) : ?>
				<ul>
					<?php
					while ( have_posts() ) :
						the_post();
						?>
						<li><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></li>
					<?php endwhile; ?>
				</ul>
			<?php else : ?>
				<p>Er is hier nog niets te zien.</p>
			<?php endif; ?>
		</div>
	</div>
</section>
<?php
get_footer();
