<?php
/**
 * Detailpagina van een gelegenheid, volgens het ontwerp.
 */
get_header();

while ( have_posts() ) :
	the_post();
	$id        = get_the_ID();
	$naam      = get_the_title();
	$sub       = function_exists( 'get_field' ) ? get_field( 'ondertitel' ) : '';
	$tekst     = function_exists( 'get_field' ) ? get_field( 'tekst' ) : '';
	$voordelen = function_exists( 'get_field' ) ? (array) get_field( 'voordelen' ) : array();
	$hub       = get_page_by_path( 'gelegenheden' );
	$terug     = $hub ? get_permalink( $hub ) : home_url( '/#gelegenheden' );
	$blokken   = function_exists( 'get_field' ) ? (array) get_field( 'blokken' ) : array();
	/* Begint de pagina met een eigen hero, dan vervalt de vaste kop: zo staat er maar één H1 op de pagina. */
	$eigen     = 'hero' === ( $blokken[0]['acf_fc_layout'] ?? '' );
	if ( ! $eigen ) :
	?>
	<section class="sectie sectie-gel-hero" id="gel-top" data-donker>
		<div class="gel-hero">
			<?php the_post_thumbnail( 'breed', array( 'class' => 'gel-hero-foto', 'loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => thema_sizes( 'vol' ), 'style' => '--gel-naam:gel-' . $id ) ); ?>
			<span class="gel-hero-waas" aria-hidden="true"></span>
			<div class="gel-hero-inhoud" data-gel-in>
				<h1 class="gel-hero-titel"><?php echo esc_html( $naam ); ?></h1>
				<?php if ( $sub ) : ?><p class="gel-hero-sub"><?php echo esc_html( $sub ); ?></p><?php endif; ?>
			</div>
		</div>
	</section>

	<section class="sectie sectie-gel-inhoud">
		<div class="gel-inhoud" data-gel-in>
			<div class="gel-inhoud-tekst">
				<span class="eyebrow">Partybus voor je <?php echo esc_html( mb_strtolower( $naam ) ); ?></span>
				<?php if ( $tekst ) : ?>
					<div class="opmaak tekst-groot"><?php echo wp_kses_post( $tekst ); ?></div>
				<?php endif; ?>
				<?php echo thema_knop( thema_instellingen( 'menu_knop' ), thema_offerte_url( array( 'uitje' => get_the_title() ) ), 'paars' ); ?>
			</div>
			<?php if ( array_filter( $voordelen ) ) : ?>
				<div class="gel-voordelen">
					<?php foreach ( $voordelen as $v ) : ?>
						<?php
						if ( empty( $v['titel'] ) ) {
							continue;
						}
						?>
						<div class="gel-voordeel">
							<span class="gel-vink" aria-hidden="true">✓</span>
							<span class="gel-voordeel-titel"><?php echo esc_html( $v['titel'] ); ?></span>
							<?php if ( ! empty( $v['tekst'] ) ) : ?><span class="gel-voordeel-tekst"><?php echo esc_html( $v['tekst'] ); ?></span><?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</section>
	<?php endif; ?>

	<?php thema_blokken(); ?>

	<?php
endwhile;

get_footer();
