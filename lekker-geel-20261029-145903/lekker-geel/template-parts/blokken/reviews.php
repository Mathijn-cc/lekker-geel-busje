<?php
/**
 * Blok: reviews als lopende band op geel.
 */
$b       = $args['blok'];
$reviews = array_values( array_filter( (array) ( $b['reviews'] ?? array() ), fn( $r ) => ! empty( $r['tekst'] ) ) );
$ti      = 'trustindex' === ( $b['bron'] ?? 'eigen' ) && thema_instellingen( 'ti_carrousel' );
$compact = 'compact' === ( $b['weergave'] ?? 'groot' );
echo thema_sectie_open( $b, 'reviews', $compact ? 'reviews-compact' : '' );
?>
	<div class="reviews-kop">
		<?php echo thema_kop( $b['kop'] ?? '', 'h2', $compact ? 'kop-middel' : 'kop-groot' ); ?>
		<?php if ( $ti && thema_instellingen( 'ti_rating' ) ) : ?>
			<div class="reviews-score reviews-score-trustindex" data-animeer><?php echo thema_rating_pil(); ?></div>
		<?php elseif ( ! empty( $b['score'] ) ) : ?>
			<div class="reviews-score" data-animeer><span class="reviews-score-getal"><?php echo esc_html( $b['score'] ); ?></span><span class="sterren">★★★★★</span><span class="reviews-score-label"><?php echo esc_html( $b['score_label'] ?? '' ); ?></span></div>
		<?php endif; ?>
	</div>
	<?php if ( $ti ) : ?>
		<div class="reviews-trustindex" data-reviews-omzetten><?php echo thema_trustindex( 'ti_carrousel' ); ?></div>
	<?php elseif ( $reviews ) : ?>
		<div class="reviews-band-houder">
			<div class="reviews-band" data-marquee data-speed="<?php echo $compact ? 60 : 70; ?>">
				<?php for ( $k = 0; $k < 2; $k++ ) : ?>
					<?php foreach ( $reviews as $i => $r ) : ?>
						<?php list( $kantel, $y ) = thema_kaart_kantel( $i + $k * count( $reviews ) ); ?>
						<figure class="review" style="--kantel:<?php echo esc_attr( $kantel ); ?>;--y:<?php echo esc_attr( $y ); ?>"<?php echo $k ? ' aria-hidden="true"' : ''; ?>>
							<div class="review-kop"><span class="review-letter" aria-hidden="true"><?php echo esc_html( mb_strtoupper( mb_substr( $r['naam'] ?: '?', 0, 1 ) ) ); ?></span><span class="sterren" aria-label="5 sterren">★★★★★</span></div>
							<blockquote class="review-tekst"><?php echo esc_html( $r['tekst'] ); ?></blockquote>
							<figcaption class="review-naam"><?php echo esc_html( $r['naam'] ); ?></figcaption>
						</figure>
					<?php endforeach; ?>
				<?php endfor; ?>
			</div>
		</div>
	<?php endif; ?>
</section>
