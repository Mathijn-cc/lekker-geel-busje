<?php
/**
 * Voet van de site, volgens het ontwerp.
 */
$s       = thema_instellingen();
$socials = array_filter( array(
	'instagram' => $s['instagram'],
	'facebook'  => $s['facebook'],
	'tiktok'    => $s['tiktok'],
	'youtube'   => $s['youtube'],
) );
$juridisch = thema_menu_items( 'juridisch' );
if ( ! $juridisch && get_privacy_policy_url() ) {
	$juridisch = array( array( 'titel' => 'Privacyverklaring', 'url' => get_privacy_policy_url(), 'doel' => '' ) );
}
?>
</main>

<footer class="voet" data-donker>
	<div class="voet-vlak">
		<div class="voet-binnen">

			<div class="voet-cta" data-animeer>
				<div class="voet-cta-tekst">
					<h2 class="voet-cta-kop"><?php echo thema_accent( $s['footer_cta_kop'] ); ?></h2>
					<?php if ( $s['footer_cta_tekst'] ) : ?>
						<p class="voet-cta-intro"><?php echo esc_html( $s['footer_cta_tekst'] ); ?></p>
					<?php endif; ?>
					<?php echo thema_knop( $s['menu_knop'], thema_offerte_url(), 'geel' ); ?>
				</div>
				<div class="contactlijst contactlijst-licht">
					<?php if ( $s['telefoon'] ) : ?>
						<a class="contactlink" href="<?php echo esc_attr( thema_telefoon_link() ); ?>"><span class="contactlink-icoon"><?php echo thema_icoon( 'telefoon', 22 ); ?></span><span class="contactlink-tekst"><span class="contactlink-label">Bel ons</span><span class="contactwaarde"><?php echo esc_html( $s['telefoon'] ); ?></span></span></a>
					<?php endif; ?>
					<?php if ( $s['email'] ) : ?>
						<a class="contactlink" href="mailto:<?php echo esc_attr( $s['email'] ); ?>"><span class="contactlink-icoon"><?php echo thema_icoon( 'mail', 22 ); ?></span><span class="contactlink-tekst"><span class="contactlink-label">Mail ons</span><span class="contactwaarde contactwaarde-mail"><?php echo esc_html( $s['email'] ); ?></span></span></a>
					<?php endif; ?>
				</div>
			</div>

			<div class="voetraster">
				<div class="voetmerk">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="voet-logo"><img src="<?php echo esc_url( thema_logo_url() ); ?>" alt="<?php bloginfo( 'name' ); ?>" width="200" height="55" loading="lazy"></a>
					<?php if ( $s['footer_tekst'] ) : ?>
						<span class="voet-tekst"><?php echo esc_html( $s['footer_tekst'] ); ?></span>
					<?php endif; ?>
					<?php if ( $socials ) : ?>
						<div class="voet-socials">
							<?php foreach ( $socials as $net => $url ) : ?>
								<a class="voetsociaal" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( ucfirst( $net ) ); ?>"><?php echo thema_icoon( $net, 20 ); ?></a>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
				<?php foreach ( array( 'footer_1', 'footer_2', 'footer_3' ) as $locatie ) : ?>
					<?php
					$items = thema_menu_items( $locatie );
					if ( ! $items ) {
						continue;
					}
					?>
					<div class="voetkolom">
						<span class="voetkolom-kop"><?php echo esc_html( thema_menu_naam( $locatie ) ); ?></span>
						<?php foreach ( $items as $item ) : ?>
							<a class="voetlink" href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['titel'] ); ?></a>
						<?php endforeach; ?>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="voet-onder">
				<div class="voet-onder-links">
					<span><?php echo esc_html( thema_vervang( $s['footer_copy'] ) ); ?></span>
					<?php foreach ( $juridisch as $item ) : ?>
						<a class="voetlink" href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['titel'] ); ?></a>
					<?php endforeach; ?>
					<?php if ( $s['footer_maker'] ) : ?>
						<span class="voet-maker">Website: <a class="voetlink" href="https://www.creatorsconnect.nl" target="_blank" rel="noopener">Creators Connect</a></span>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</footer>
</div>

<?php if ( in_array( 'met-cursor', thema_body_classes(), true ) ) : ?>
	<div class="cursor-ring" data-cursor-ring aria-hidden="true"><span data-cursor-label></span></div>
	<div class="cursor-stip" data-cursor-stip aria-hidden="true"></div>
	<div class="cursor-ster" data-cursor-ster aria-hidden="true">
		<div class="cursor-ster-vorm" data-cursor-ster-vorm><svg viewBox="0 0 40 40" width="100%" height="100%"><path d="M20 2 23.6 12.9 34.6 8.6 28.4 18.6 38.4 22.8 27 25.1 28.6 36.8 20 28.8 11.4 36.8 13 25.1 1.6 22.8 11.6 18.6 5.4 8.6 16.4 12.9Z" fill="var(--kleur-geel)" stroke="var(--kleur-paars)" stroke-width="2.4" stroke-linejoin="round"/></svg></div>
		<span class="cursor-ster-label" data-cursor-ster-label></span>
	</div>
<?php endif; ?>

<?php wp_footer(); ?>
</body>
</html>
