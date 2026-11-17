<?php
/**
 * Artikel van de blog: kop met datum en leestijd, foto, in het kort, tekst (H2's met een anker),
 * auteur, kaart naar de gelegenheid, blokken (bijv. checklist, vragen, offerte) en gerelateerde artikelen.
 */
get_header();

while ( have_posts() ) :
	the_post();
	$id      = get_the_ID();
	$blog    = thema_blog_pagina();
	$cat     = thema_blog_categorie( $id );
	$auteur  = thema_blog_auteur( $id );
	$inhoud  = thema_blog_inhoud( $id );
	$kort    = function_exists( 'get_field' ) ? thema_lijst( (string) get_field( 'in_het_kort', $id ) ) : array();
	$gel     = function_exists( 'get_field' ) ? (int) get_field( 'gelegenheid', $id ) : 0;
	$foto    = get_post_thumbnail_id( $id );
	?>
	<article class="artikel">
		<header class="sectie sectie-artikelkop">
			<div class="artikelkop-binnen">
				<a class="terugpil" href="<?php echo esc_url( $blog ? get_permalink( $blog ) : home_url( '/' ) ); ?>"><span aria-hidden="true">←</span> <?php echo esc_html( $blog ? get_the_title( $blog ) : 'Blog' ); ?></a>
				<div class="artikel-titelregel">
					<?php echo thema_kop( get_the_title(), 'h1', 'kop-middel artikel-titel' ); ?>
					<?php if ( $cat && 'algemeen' !== $cat->slug ) : ?>
						<a class="blogkaart-onderwerp" href="<?php echo esc_url( get_category_link( $cat ) ); ?>"><?php echo esc_html( $cat->name ); ?></a>
					<?php endif; ?>
				</div>
				<ul class="artikel-meta" aria-label="Over dit artikel">
					<li class="metapil metapil-auteur"><?php echo thema_icoon( 'bus', 16 ); ?><span>Door <?php echo esc_html( $auteur['naam'] ); ?></span></li>
					<li class="metapil"><?php echo thema_icoon( 'kalender', 16 ); ?><span><?php echo thema_blog_datums( $id ); ?></span></li>
					<li class="metapil"><?php echo thema_icoon( 'klok', 16 ); ?><span><?php echo (int) thema_leestijd( $id ); ?> min lezen</span></li>
				</ul>
			</div>
		</header>

		<?php if ( $foto ) : ?>
			<div class="artikel-foto">
				<?php echo thema_foto_met_credit( $foto, 'breed', false, '', array( 'loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '(min-width: 1200px) 1100px, 100vw' ) ); ?>
			</div>
		<?php endif; ?>

		<div class="sectie sectie-artikel">
			<div class="artikel-raster">
				<div class="artikel-hoofd">
					<?php if ( $kort ) : ?>
						<div class="artikel-kort">
							<span class="artikel-kort-kop">In het kort</span>
							<ul>
								<?php foreach ( $kort as $punt ) : ?>
									<li><?php echo esc_html( $punt ); ?></li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endif; ?>
					<div class="opmaak artikel-tekst">
						<?php echo $inhoud['html']; // phpcs:ignore -- via the_content. ?>
					</div>
					<footer class="artikel-voet">
						<div class="artikel-schrijver">
							<span class="artikel-schrijver-icoon" aria-hidden="true"><?php echo thema_icoon( 'bus', 26 ); ?></span>
							<span class="artikel-schrijver-tekst">
								<span class="artikel-schrijver-naam"><?php echo esc_html( $auteur['naam'] ); ?></span>
								<?php if ( $auteur['rol'] ) : ?><span class="artikel-schrijver-rol"><?php echo esc_html( $auteur['rol'] ); ?></span><?php endif; ?>
							</span>
						</div>
						<?php if ( $gel && get_post( $gel ) ) : ?>
							<a class="artikel-gelegenheid" href="<?php echo esc_url( get_permalink( $gel ) ); ?>">
								<?php echo get_the_post_thumbnail( $gel, 'kaart-s', array( 'class' => 'artikel-gelegenheid-foto', 'loading' => 'lazy' ) ); ?>
								<span class="artikel-gelegenheid-tekst">
									<span class="artikel-gelegenheid-label">Op zoek naar vervoer?</span>
									<span class="artikel-gelegenheid-titel">Partybus voor je <?php echo esc_html( mb_strtolower( get_the_title( $gel ) ) ); ?></span>
								</span>
								<span class="artikel-gelegenheid-pijl" aria-hidden="true"><?php echo thema_icoon( 'pijl-op', 20, 2.4 ); ?></span>
							</a>
						<?php endif; ?>
					</footer>
				</div>
			</div>
		</div>
	</article>

	<?php thema_blokken( $id, false ); ?>

	<?php
	$verder = thema_blog_artikelen( array(), $cat ? $cat->term_id : 0, 3, $id );
	if ( count( $verder ) < 3 ) {
		$al = wp_list_pluck( $verder, 'ID' );
		foreach ( thema_blog_artikelen( array(), 0, 6, $id ) as $extra ) {
			if ( count( $verder ) < 3 && ! in_array( $extra->ID, $al, true ) ) {
				$verder[] = $extra;
			}
		}
	}
	if ( $verder ) :
		$onderwerp = ( $cat && 'algemeen' !== $cat->slug ) ? $cat : null;
		$intro     = $onderwerp ? wp_strip_all_tags( (string) $onderwerp->description ) : '';
		if ( '' === $intro ) {
			$intro = 'Meer tips, ideeën en checklists voor jullie feestje met de groep. Zo regel je het zonder gedoe, en begint het feest al in de bus.';
		}
		$naar  = $onderwerp ? get_category_link( $onderwerp ) : ( $blog ? get_permalink( $blog ) : home_url( '/' ) );
		$label = $onderwerp ? 'Alle artikelen over ' . mb_strtolower( $onderwerp->name ) : 'Alle artikelen';
		$kop   = $onderwerp ? 'Meer over *' . mb_strtolower( $onderwerp->name ) . '*' : 'Lees *ook*';
		?>
		<section class="sectie sectie-artikelen sectie-leesook" aria-labelledby="leesook-kop">
			<div class="artikelen-binnen">
				<div class="leesook-kop">
					<div class="leesook-tekst">
						<?php echo thema_eyebrow( 'Lees ook' ); ?>
						<?php echo thema_kop( $kop, 'h2', 'kop-middel', ' id="leesook-kop"' ); ?>
						<p class="tekst-groot"><?php echo esc_html( $intro ); ?></p>
					</div>
					<div class="leesook-knoppen">
						<?php echo thema_knop( $label, $naar, 'paars' ); ?>
						<?php if ( $onderwerp && $blog ) : ?>
							<a class="leesook-blog" href="<?php echo esc_url( get_permalink( $blog ) ); ?>">Of bekijk alle onderwerpen <span aria-hidden="true">→</span></a>
						<?php endif; ?>
					</div>
				</div>
				<div class="artikelen-raster artikelen-<?php echo (int) min( 3, count( $verder ) ); ?>">
					<?php
					foreach ( array_slice( $verder, 0, 3 ) as $ander ) {
						get_template_part( 'template-parts/kaart-blog', null, array( 'post' => $ander ) );
					}
					?>
				</div>
			</div>
		</section>
		<?php
	endif;
endwhile;

get_footer();
