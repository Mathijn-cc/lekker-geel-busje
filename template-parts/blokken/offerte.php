<?php
/**
 * Blok: offerte in een zin. Eerst compact, open na een klik.
 */
$b        = $args['blok'];
$zeker    = thema_lijst( $b['zekerheden'] ?? '' );
$open     = ! empty( $b['open'] );
$contact  = get_page_by_path( 'contact' );
$actie    = thema_instellingen( 'offerte_link' ) ?: ( $contact ? get_permalink( $contact ) : home_url( '/' ) );
$uid      = 'offerte-' . (int) ( $args['index'] ?? 0 );
$feest    = is_singular( 'gelegenheid' ) ? get_the_title() : sanitize_text_field( wp_unslash( $_GET['feest'] ?? '' ) ); // phpcs:ignore

echo thema_sectie_open( $b, 'offerte', '', '' );
?>
	<div class="offerte-binnen">
		<div class="offerte-kop" data-animeer>
			<?php if ( ! empty( $b['label'] ) ) : ?>
				<span class="label-pil"><?php echo thema_icoon( 'timer', 16, 2.4 ); ?><?php echo esc_html( $b['label'] ); ?></span>
			<?php endif; ?>
			<?php echo thema_kop( $b['kop'] ?? '', 'h2', 'offerte-titel' ); ?>
		</div>
		<div class="offertevak<?php echo $open ? ' is-open' : ''; ?>" data-offerte data-animeer>
			<form class="offertezin" id="<?php echo esc_attr( $uid ); ?>" action="<?php echo esc_url( $actie ); ?>" method="get" aria-hidden="<?php echo $open ? 'false' : 'true'; ?>">
				<?php if ( $feest ) : ?><input type="hidden" name="feest" value="<?php echo esc_attr( ucfirst( str_replace( '-', ' ', $feest ) ) ); ?>"><?php endif; ?>
				<p class="offertezin-tekst">
					<?php echo esc_html( $b['zin_1'] ?? 'Op' ); ?>
					<label><span class="sr-tekst">Datum</span><input type="date" name="datum" class="zinveld zinveld-datum" min="<?php echo esc_attr( wp_date( 'Y-m-d' ) ); ?>"></label>
					<?php echo esc_html( $b['zin_2'] ?? 'gaan we met' ); ?>
					<label><span class="sr-tekst">Aantal personen</span><input type="number" name="personen" class="zinveld zinveld-personen" min="1" max="999" step="1" inputmode="numeric" placeholder="<?php echo esc_attr( $b['ph_personen'] ?? 'aantal' ); ?>"></label>
					<?php echo esc_html( $b['zin_3'] ?? 'personen van' ); ?>
					<label><span class="sr-tekst">Ophalen in</span><input type="text" name="van" class="zinveld zinveld-van" placeholder="<?php echo esc_attr( $b['ph_van'] ?? 'jullie plaats' ); ?>"></label>
					<?php echo esc_html( $b['zin_4'] ?? 'naar' ); ?>
					<label><span class="sr-tekst">Bestemming</span><input type="text" name="naar" value="<?php echo esc_attr( $b['naar_waarde'] ?? '' ); ?>" class="zinveld zinveld-naar" placeholder="<?php echo esc_attr( $b['ph_naar'] ?? 'de bestemming' ); ?>"></label>.
				</p>
				<div class="offertezin-voet">
					<?php if ( $zeker ) : ?>
						<div class="zekerheden">
							<?php foreach ( $zeker as $z ) : ?>
								<span class="zekerheid"><span class="vinkje" aria-hidden="true">✓</span><?php echo esc_html( $z ); ?></span>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
					<button class="knop-rond knop-rond-paars" type="submit"><span class="knop-tekst"><?php echo esc_html( $b['knop_verder'] ?? 'Ga verder' ); ?></span><span class="knop-rond-pijl" aria-hidden="true"><?php echo thema_icoon( 'chevron' ); ?></span></button>
				</div>
			</form>
			<button type="button" class="offerte-sluit" aria-label="Formulier inklappen" data-offerte-sluit><?php echo thema_icoon( 'sluiten', 16, 2.6 ); ?></button>
			<div class="offerte-laag" data-offerte-laag aria-hidden="<?php echo $open ? 'true' : 'false'; ?>">
				<button type="button" class="knop-rond knop-rond-paars" data-offerte-open aria-controls="<?php echo esc_attr( $uid ); ?>"><span class="knop-tekst"><?php echo esc_html( $b['knop_open'] ?? 'Vraag direct je prijs aan' ); ?></span><span class="knop-rond-pijl" aria-hidden="true"><?php echo thema_icoon( 'chevron' ); ?></span></button>
			</div>
		</div>
	</div>
</section>
