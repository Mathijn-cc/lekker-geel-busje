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
	?>
	<section class="sectie sectie-gel-hero" id="gel-top" data-donker>
		<div class="gel-hero">
			<?php the_post_thumbnail( 'breed', array( 'class' => 'gel-hero-foto', 'loading' => 'eager', 'fetchpriority' => 'high', 'style' => 'view-transition-name:gel-' . $id ) ); ?>
			<span class="gel-hero-waas" aria-hidden="true"></span>
			<div class="gel-hero-inhoud" data-gel-in>
				<a class="terugpil" href="<?php echo esc_url( $terug ); ?>"><span aria-hidden="true">←</span> Alle gelegenheden</a>
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

	<?php thema_blokken(); ?>

	<?php
	$andere = thema_gelegenheden( array(), $id );
	if ( $andere ) :
		?>
		<section class="sectie sectie-andere">
			<div class="andere-binnen" data-gel-in>
				<h2 class="andere-kop">Andere <span class="accent">feestjes</span></h2>
				<div class="andere-raster">
					<?php
					foreach ( $andere as $i => $ander ) {
						get_template_part( 'template-parts/kaart-gelegenheid', null, array( 'post' => $ander, 'index' => $i, 'variant' => 'klein' ) );
					}
					?>
				</div>
			</div>
		</section>
		<?php
	endif;
endwhile;

get_footer();
