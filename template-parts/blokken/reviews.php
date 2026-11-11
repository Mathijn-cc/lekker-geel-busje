<?php
/**
 * Blok: reviews uit Trustindex, met de rating-knop naast de kop. Carrousel en knop staan in Thema > Reviews.
 */
$b       = $args['blok'];
$compact = 'compact' === ( $b['weergave'] ?? 'groot' );
if ( ! thema_instellingen( 'ti_carrousel' ) ) {
	if ( current_user_can( 'edit_pages' ) ) {
		echo '<div class="melding">Reviewblok: vul de Trustindex-carrousel in bij Thema &gt; Reviews.</div>';
	}
	return;
}
echo thema_sectie_open( $b, 'reviews', $compact ? 'reviews-compact' : '' );
?>
	<div class="reviews-kop">
		<?php echo thema_kop( $b['kop'] ?? '', 'h2', $compact ? 'kop-middel' : 'kop-groot' ); ?>
		<?php if ( thema_instellingen( 'ti_rating' ) ) : ?>
			<div class="reviews-score reviews-score-trustindex" data-animeer><?php echo thema_rating_pil(); ?></div>
		<?php endif; ?>
	</div>
	<div class="reviews-trustindex" data-reviews-omzetten><?php echo thema_trustindex( 'ti_carrousel' ); ?></div>
</section>
