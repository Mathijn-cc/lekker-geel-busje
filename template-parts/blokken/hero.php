<?php
/**
 * Blok: hero in vier stijlen (foto, collage, bento, klassiek).
 */
$b        = $args['blok'];
$stijl    = thema_hero_stijl( $b );
$s        = thema_instellingen();
$plaatsen = thema_lijst( $b['plaatsen'] ?? '' );
$eerste   = $plaatsen[0] ?? '';
$is_h1    = 0 === (int) ( $args['index'] ?? 0 );
$tag      = $is_h1 ? 'h1' : 'h2';
$fotos    = array_filter( (array) ( $b['fotos'] ?? array() ) );
$extra    = array_values( array_filter( (array) ( $b['extra'] ?? array() ) ) );
$beeld    = (int) ( $b['beeld'] ?? 0 );
$knop     = $b['knop'] ?? array();
if ( isset( $b['knop_tonen'] ) && ! $b['knop_tonen'] ) {
	$knop = array();
} elseif ( empty( $knop['url'] ) ) {
	$knop = array( 'title' => 'Plan je feestje', 'url' => thema_offerte_url() );
}
$sub = trim( esc_html( $b['sub_1'] ?? '' ) . ' <span class="accent">' . esc_html( $b['sub_2'] ?? '' ) . '</span>' );

/**
 * De kop met wisselende plaats. $pil = kleurvariant van het label.
 */
$kop = function ( $regels_apart, $pil = 'paars' ) use ( $b, $plaatsen, $eerste, $tag ) {
	$html = '<' . $tag . ' class="hero-kop" data-plaatsen="' . esc_attr( wp_json_encode( $plaatsen ) ) . '">';
	/* Lege regels worden overgeslagen, zodat er geen gat in de kop valt. */
	foreach ( array( 'kop_1', 'kop_2' ) as $regel ) {
		if ( '' !== trim( (string) ( $b[ $regel ] ?? '' ) ) ) {
			$html .= '<span class="hero-regel"><span data-word>' . esc_html( $b[ $regel ] ) . '</span></span> ';
		}
	}
	$html  = rtrim( $html );
	if ( ! $regels_apart ) {
		$html .= '<br class="hero-breuk">';
	}
	if ( $eerste ) {
		$html .= ' <span class="plaatspil"><span class="plaatslabel plaatslabel-' . esc_attr( $pil ) . '" data-pop-word><span data-plaats>' . esc_html( $eerste ) . '?</span></span></span>';
	}
	return $html . '</' . $tag . '>';
};

$score = ! empty( $b['toon_score'] ) ? thema_score_pil() : '';
$terug = $is_h1 ? thema_terug_link() : '';
$anker = ! empty( $b['anker'] ) ? sanitize_title( $b['anker'] ) : 'top';
$hoogte  = $b['hoogte'] ?? 'auto';
$compact = ( 'compact' === $hoogte || ( 'vol' !== $hoogte && ! is_front_page() ) ) ? ' hero-compact' : '';

/* ---------------------------------------------------------------- Fotobreed */
if ( 'foto' === $stijl ) :
	if ( ! $fotos && $beeld ) {
		$fotos = array( $beeld );
	}
	$fotos = array_values( $fotos );
	$vorm  = $b['vorm'] ?? 'auto';
	$kaart = 'kaart' === $vorm || ( 'auto' === $vorm && ! is_front_page() );
	?>
	<section id="<?php echo esc_attr( $anker ); ?>" class="sectie sectie-hero hero-foto<?php echo $kaart ? ' hero-kaart' : ''; ?><?php echo esc_attr( $compact ); ?>" data-donker data-hero-slider>
		<div class="hero-foto-beelden" data-zoom>
			<?php foreach ( $fotos as $i => $id ) : ?>
				<?php
					$foto = thema_afbeelding( $id, 'breed', 'hero-foto-beeld' . ( 0 === $i ? ' is-actief' : '' ), 0 !== $i, array( 'data-slide' => $i, 'sizes' => thema_sizes( 'hero' ) ) );
					echo 0 === $i ? $foto : thema_uitgesteld( $foto );
					?>
			<?php endforeach; ?>
		</div>
		<div class="hero-foto-waas" aria-hidden="true"></div>
		<div class="hero-inhoud">
			<div class="hero-inhoud-tekst">
				<?php echo $terug; ?>
				<?php echo $score; ?>
				<?php echo $kop( false, 'geel' ); ?>
				<div class="hero-acties" data-hero-fade>
					<?php echo thema_blok_knop( $knop, $b, 'creme' ); ?>
					<?php if ( $sub ) : ?><span class="hero-sub-accent"><?php echo $sub; ?></span><?php endif; ?>
				</div>
			</div>
		</div>
		<?php if ( count( $fotos ) > 1 ) : ?>
			<div class="pagineervlak">
				<div class="pagineer-binnen">
					<div class="pagineer-rij">
						<span class="pagineerteller" data-teller>01 / <?php echo esc_html( str_pad( count( $fotos ), 2, '0', STR_PAD_LEFT ) ); ?></span>
						<div class="pagineer-stippen">
							<?php foreach ( $fotos as $i => $id ) : ?>
								<button type="button" class="pagineer-stip<?php echo 0 === $i ? ' is-actief' : ''; ?>" aria-label="Foto <?php echo (int) $i + 1; ?>" aria-pressed="<?php echo 0 === $i ? 'true' : 'false'; ?>" data-naar="<?php echo (int) $i; ?>"></button>
							<?php endforeach; ?>
						</div>
					</div>
					<div class="pagineerbalk" aria-hidden="true"><span data-voortgang></span></div>
				</div>
			</div>
		<?php endif; ?>
	</section>
	<?php

/* ---------------------------------------------------------------- Collage */
elseif ( 'collage' === $stijl ) :
	$fotos = array_values( $fotos );
	?>
	<section id="<?php echo esc_attr( $anker ); ?>" class="sectie sectie-hero hero-collage<?php echo esc_attr( $compact ); ?>" data-hero-slider>
		<?php if ( $fotos ) : ?>
			<div class="hero-collage-achtergrond" data-zoom aria-hidden="true">
				<?php foreach ( $fotos as $i => $id ) : ?>
					<?php
						$foto = thema_afbeelding( $id, 'breed', 'hero-foto-beeld' . ( 0 === $i ? ' is-actief' : '' ), true, array( 'alt' => '', 'data-slide' => $i, 'sizes' => thema_sizes( 'hero' ) ) );
						echo 0 === $i ? $foto : thema_uitgesteld( $foto );
						?>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<div class="hero-collage-gloed" aria-hidden="true"></div>
		<?php
		$posities = array( 'links-boven', 'links-onder', 'rechts-boven', 'rechts-onder' );
		if ( $extra ) :
			?>
			<div class="hero-collage-zwevend">
				<?php foreach ( array_slice( $extra, 0, 4 ) as $i => $id ) : ?>
					<div class="collagefoto collagefoto-<?php echo esc_attr( $posities[ $i ] ); ?>" data-float<?php echo 0 === $i ? ' data-cursor="Feest!"' : ''; ?>><?php echo thema_afbeelding( $id, 'kaart', '', true, array( 'sizes' => thema_sizes( 'kaart-klein' ) ) ); ?></div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<div class="hero-collage-tekst">
			<?php echo $terug; ?>
			<?php echo $score; ?>
			<?php echo $kop( true, 'paars' ); ?>
			<?php if ( $sub ) : ?><p class="hero-sub" data-hero-fade><?php echo $sub; ?></p><?php endif; ?>
			<span data-hero-fade><?php echo thema_blok_knop( $knop, $b, 'paars' ); ?></span>
		</div>
		<?php if ( $extra ) : ?>
			<div class="hero-collage-rij">
				<?php foreach ( array_slice( $extra, 0, 3 ) as $id ) : ?>
					<div class="collagerij-foto"><?php echo thema_afbeelding( $id, 'kaart', '', true, array( 'sizes' => thema_sizes( 'kaart-klein' ) ) ); ?></div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</section>
	<?php

/* ---------------------------------------------------------------- Bento */
elseif ( 'bento' === $stijl ) :
	$tegel_link = $b['bento_link'] ?? array();
	$tegel_url  = ! empty( $tegel_link['url'] ) ? $tegel_link['url'] : '#bussen';
	?>
	<section id="<?php echo esc_attr( $anker ); ?>" class="sectie sectie-hero hero-bento<?php echo esc_attr( $compact ); ?>">
		<div class="bento">
			<div class="bento-tegel bento-tekst">
				<?php echo $score ? str_replace( 'score-pil', 'score-pil score-pil-zand', $score ) : '<span></span>'; ?>
				<?php echo $terug; ?>
				<?php echo $kop( true, 'paars' ); ?>
				<div class="hero-acties hero-acties-tussen" data-hero-fade>
					<?php if ( $sub ) : ?><span class="hero-sub-accent"><?php echo $sub; ?></span><?php endif; ?>
					<?php echo thema_blok_knop( $knop, $b, 'paars' ); ?>
				</div>
			</div>
			<div class="bento-tegel bento-foto" data-hero-img data-cursor="Feest!">
				<?php echo thema_afbeelding( $beeld ?: ( $fotos ? reset( $fotos ) : 0 ), 'breed', '', false, array( 'fetchpriority' => 'high', 'sizes' => thema_sizes( 'hero-klassiek' ) ) ); ?>
				<?php if ( $sub ) : ?><span class="schuinlabel"><?php echo wp_strip_all_tags( $sub ); ?></span><?php endif; ?>
			</div>
			<div class="bento-tegel bento-score">
				<span class="sterren">★★★★★</span>
				<div><span class="bento-score-getal"><?php echo esc_html( $s['score'] ); ?></span><span class="bento-score-label"><?php echo esc_html( $s['score_label'] ); ?></span></div>
			</div>
			<div class="bento-tegel bento-klein"><?php echo thema_afbeelding( $extra[0] ?? 0, 'kaart', '', true, array( 'sizes' => thema_sizes( 'kaart-klein' ) ) ); ?></div>
			<a class="bento-tegel bento-roze" href="<?php echo esc_url( $tegel_url ); ?>">
				<span class="bento-roze-tekst"><span class="bento-roze-klein"><?php echo esc_html( $b['bento_klein'] ?? '' ); ?></span><span class="bento-roze-groot"><?php echo esc_html( $b['bento_groot'] ?? '' ); ?></span></span>
				<span class="bento-roze-pijl"><?php echo thema_icoon( 'chevron' ); ?></span>
			</a>
		</div>
	</section>
	<?php

/* ---------------------------------------------------------------- Fotorol */
elseif ( 'fotorol' === $stijl ) :
	/* Waaier van fotokaarten onder de kop: de wisselende foto's (maximaal vijf), anders het hoofdbeeld. */
	$waaier = array_slice( array_values( array_unique( array_filter( array_map( 'intval', $fotos ? $fotos : array( $beeld ) ) ) ) ), 0, 5 );
	$aantal = count( $waaier );
	$midden = ( $aantal - 1 ) / 2;
	?>
	<section id="<?php echo esc_attr( $anker ); ?>" class="sectie sectie-hero hero-fotorol<?php echo esc_attr( $compact ); ?>">
		<div class="hero-fotorol-kaart">
			<div class="hero-fotorol-tekst">
				<?php echo $terug; ?>
				<?php echo $kop( true, 'paars' ); ?>
				<?php if ( $sub ) : ?><p class="hero-sub" data-hero-fade><?php echo $sub; ?></p><?php endif; ?>
				<span data-hero-fade><?php echo thema_blok_knop( $knop, $b, 'paars' ); ?></span>
			</div>
			<?php if ( $waaier ) : ?>
				<div class="hero-waaier" data-waaier>
					<?php
					foreach ( $waaier as $i => $id ) :
						$afstand = $i - $midden;
						$stijl   = sprintf(
							'--plek:%s;--draai:%sdeg;--zak:%s;z-index:%d',
							esc_attr( $afstand ),
							esc_attr( round( $afstand * 5.5 + ( $i % 2 ? .8 : -.8 ), 2 ) ),
							esc_attr( round( pow( abs( $afstand ), 1.5 ), 2 ) ),
							10 - (int) round( abs( $afstand ) * 2 )
						);
						?>
						<div class="hero-waaierkaart<?php echo abs( $afstand ) > 1 ? ' is-buiten' : ''; ?>" style="<?php echo $stijl; ?>">
							<?php /* Drie lagen: plek (CSS), beweging (GSAP) en hover (CSS), zodat ze elkaar niet overschrijven. */ ?>
							<div class="hero-waaierkaart-beweeg" data-waaierkaart data-plek="<?php echo esc_attr( $afstand ); ?>" data-cursor="Feest!">
								<div class="hero-waaierkaart-binnen"><?php echo thema_afbeelding( $id, 'kaart', '', abs( $afstand ) > 1, array( 'alt' => '', 'sizes' => thema_sizes( 'waaier' ) ) ); ?></div>
							</div>
						</div>
					<?php endforeach; ?>
					<?php if ( $s['score'] && ! empty( $b['toon_score'] ) ) : ?>
						<div class="hero-waaier-score" data-waaier-score>
							<span class="hero-badge-getal"><?php echo esc_html( $s['score'] ); ?></span>
							<span class="sterren">★★★★★</span>
							<span class="hero-badge-label"><?php echo esc_html( $s['score_label'] ); ?></span>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
	</section>
	<?php
/* ---------------------------------------------------------------- Klassiek */
else :
	/* Tekst links, foto rechts. De wisselende foto's vloeien een voor een in elkaar over; anders het hoofdbeeld. */
	$wissel = array_slice( array_values( array_unique( array_filter( array_map( 'intval', $fotos ? $fotos : array( $beeld ) ) ) ) ), 0, 6 );
	?>
	<section id="<?php echo esc_attr( $anker ); ?>" class="sectie sectie-hero hero-klassiek<?php echo esc_attr( $compact ); ?>">
		<div class="hero-klassiek-kaart">
			<div class="hero-klassiek-raster">
				<div class="hero-klassiek-tekst">
					<?php echo $terug; ?>
					<?php echo $kop( true, 'paars' ); ?>
					<?php if ( $sub ) : ?><p class="hero-sub" data-hero-fade><?php echo $sub; ?></p><?php endif; ?>
					<?php if ( $knop ) : ?><span data-hero-fade><?php echo thema_blok_knop( $knop, $b, 'paars' ); ?></span><?php endif; ?>
				</div>
				<div class="hero-klassiek-media">
					<div class="hero-klassiek-beeld" data-hero-img<?php echo count( $wissel ) > 1 ? ' data-wissel' : ''; ?> data-cursor="Feest!">
						<?php foreach ( $wissel as $i => $id ) : ?>
							<?php echo thema_afbeelding( $id, 'breed', 0 === $i ? 'is-actief' : '', 0 !== $i, 0 === $i ? array( 'fetchpriority' => 'high', 'sizes' => thema_sizes( 'hero-klassiek' ) ) : array( 'alt' => '', 'sizes' => thema_sizes( 'hero-klassiek' ) ) ); ?>
						<?php endforeach; ?>
					</div>
					<?php if ( $s['score'] && ! empty( $b['toon_score'] ) ) : ?>
						<div class="hero-badge" data-spin>
							<span class="hero-badge-getal"><?php echo esc_html( $s['score'] ); ?></span>
							<span class="sterren">★★★★★</span>
							<span class="hero-badge-label"><?php echo esc_html( $s['score_label'] ); ?></span>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</section>
	<?php
endif;
