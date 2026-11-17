<?php
/**
 * Blok: stoelen in de bus verkopen voor een vaste rit (zie inc/stoelverkoop.php).
 * Links de keuzes als bij een ticketshop (avond, opstapplek, aantal, gegevens), rechts het ticket met het totaal.
 * Na het aanmelden toont hetzelfde blok de bevestiging.
 * Werkt ook zonder JavaScript; het script voegt de stapper, het lopende totaal en de beschikbaarheid toe.
 */
$b      = $args['blok'];
$pagina = (int) get_queried_object_id();
$config = function_exists( 'thema_stoelen_config' ) ? thema_stoelen_config( $pagina ) : null;
if ( ! $config ) {
	return;
}

// phpcs:disable WordPress.Security.NonceVerification
$code    = isset( $_GET['stoel'] ) ? sanitize_text_field( wp_unslash( $_GET['stoel'] ) ) : '';
$fout    = isset( $_GET['stoelfout'] ) ? sanitize_key( wp_unslash( $_GET['stoelfout'] ) ) : '';
$voorkeur_avond  = isset( $_GET['avond'] ) ? preg_replace( '/\D/', '', (string) wp_unslash( $_GET['avond'] ) ) : '';
$voorkeur_opstap = isset( $_GET['opstap'] ) ? sanitize_title( wp_unslash( $_GET['opstap'] ) ) : '';
// phpcs:enable

$boeking = $code ? thema_stoelen_boeking_op_code( $code, $pagina ) : null;
$terug   = $fout ? get_transient( 'thema_stoelen_' . $fout ) : null;
$waarden = is_array( $terug ) ? (array) $terug['waarden'] : array();
$fouten  = is_array( $terug ) ? (array) $terug['fouten'] : ( 'opslaan' === $fout ? array( 'algemeen' => 'Er ging iets mis bij het opslaan. Probeer het nog eens of bel ons even.' ) : array() );

$prijs   = $config['prijs'];
$ritten  = $config['ritten'];
$opstap  = $config['opstap'];
$open    = array_filter( $ritten, fn( $r ) => 'open' === $r['status'] );
$gekozen = array(
	'rit'    => $waarden['rit'] ?? ( isset( $open[ $voorkeur_avond ] ) ? $voorkeur_avond : ( 1 === count( $open ) ? array_key_first( $open ) : '' ) ),
	'opstap' => $waarden['opstap'] ?? ( isset( $opstap[ $voorkeur_opstap ] ) ? $voorkeur_opstap : '' ),
	'aantal' => max( 1, (int) ( $waarden['aantal'] ?? 2 ) ),
);
$v       = fn( $k ) => esc_attr( (string) ( $waarden[ $k ] ?? '' ) );
$foutje  = function ( $k ) use ( $fouten ) {
	return isset( $fouten[ $k ] ) ? '<p class="stoelen-fout" id="fout-' . esc_attr( $k ) . '">' . esc_html( $fouten[ $k ] ) . '</p>' : '';
};
$kenmerk = fn( $k ) => isset( $fouten[ $k ] ) ? ' aria-invalid="true" aria-describedby="fout-' . esc_attr( $k ) . '"' : '';

$punten = thema_lijst( $b['punten'] ?? '' );

wp_enqueue_script( 'thema-stoelen', THEMA_URL . '/assets/js/stoelen.js', array(), THEMA_VERSIE, array( 'in_footer' => true, 'strategy' => 'defer' ) );

echo thema_sectie_open( $b, 'stoelen' );
?>
	<div class="stoelen-binnen">
		<div class="stoelen-kop">
			<?php echo thema_eyebrow( $b['eyebrow'] ?? '' ); ?>
			<?php echo thema_kop( $b['kop'] ?? '', 'h2', 'kop-middel' ); ?>
			<?php if ( ! empty( $b['intro'] ) ) : ?><p class="tekst-groot" data-animeer><?php echo esc_html( $b['intro'] ); ?></p><?php endif; ?>
			<?php if ( $punten ) : ?>
				<ul class="stoelen-punten" data-animeer>
					<?php foreach ( $punten as $punt ) : ?>
						<li><?php echo thema_icoon( 'vink', 16, 2.5 ); ?><span><?php echo esc_html( $punt ); ?></span></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>

		<?php
		/* ------------------------------------------------------------------
		 * Bevestiging na het aanmelden.
		 * --------------------------------------------------------------- */
		if ( $boeking ) :
			$m        = fn( $k ) => get_post_meta( $boeking, '_stoel_' . $k, true );
			$rit      = DateTimeImmutable::createFromFormat( '!Ymd', (string) $m( 'rit' ), wp_timezone() );
			$plek     = $opstap[ $m( 'opstap' ) ] ?? array( 'plaats' => $m( 'opstap_naam' ), 'heen' => '', 'plek' => '', 'terug' => '' );
			$aantal   = (int) $m( 'aantal' );
			$voornaam = strtok( (string) $m( 'naam' ), ' ' );
			$delen    = 'Ik ga met de bus van Lekker Geel Busje naar ' . $config['evenement'] . ( $rit ? ' op ' . thema_stoelen_datum( $rit ) : '' ) . '. Ga je mee? ' . get_permalink( $pagina );
			?>
			<div class="stoelen-bevestiging" tabindex="-1" data-stoelen-bevestiging>
				<div class="stoelen-ticket stoelen-ticket-klaar">
					<div class="stoelen-ticket-boven">
						<span class="stoelen-ticket-label">Gereserveerd</span>
						<p class="stoelen-ticket-titel"><?php echo esc_html( $config['evenement'] ); ?></p>
						<?php if ( $config['bestemming'] ) : ?><p class="stoelen-ticket-sub"><?php echo esc_html( $config['bestemming'] ); ?></p><?php endif; ?>
					</div>
					<dl class="stoelen-ticket-regels">
						<div><dt>Avond</dt><dd><?php echo esc_html( $rit ? ucfirst( thema_stoelen_datum( $rit ) ) : '' ); ?></dd></div>
						<div><dt>Instappen</dt><dd><?php echo esc_html( $plek['plaats'] . ( $plek['heen'] ? ', ' . $plek['heen'] : '' ) ); ?></dd></div>
						<div><dt>Stoelen</dt><dd><?php echo esc_html( $aantal . ' x ' . thema_stoelen_euro( $m( 'prijs' ) ) ); ?></dd></div>
					</dl>
					<div class="stoelen-ticket-onder">
						<div class="stoelen-ticket-totaal"><span>Via Tikkie</span><strong><?php echo esc_html( thema_stoelen_euro( $m( 'bedrag' ) ) ); ?></strong></div>
						<div class="stoelen-ticket-code"><span>Je code</span><strong><?php echo esc_html( $m( 'code' ) ); ?></strong></div>
					</div>
				</div>
				<div class="stoelen-bevestiging-tekst">
					<p class="stoelen-bevestiging-kop"><?php echo esc_html( 'Top, ' . $voornaam . '! Je ' . ( 1 === $aantal ? 'stoel staat' : 'stoelen staan' ) . ' voor je klaar.' ); ?></p>
					<p>We hebben de bevestiging naar <strong><?php echo esc_html( $m( 'email' ) ); ?></strong> gestuurd. Zo gaat het verder:</p>
					<?php if ( $config['stappen'] ) : ?>
						<ol class="stoelen-vervolg">
							<?php foreach ( $config['stappen'] as $stap ) : ?><li><?php echo esc_html( $stap ); ?></li><?php endforeach; ?>
						</ol>
					<?php endif; ?>
					<div class="stoelen-bevestiging-knoppen">
						<a class="knop-rond knop-rond-paars stoelen-deel" href="<?php echo esc_url( 'https://wa.me/?text=' . rawurlencode( $delen ) ); ?>" target="_blank" rel="noopener">
							<span class="knop-tekst">Deel met je vrienden</span><span class="knop-rond-pijl" aria-hidden="true"><?php echo thema_icoon( 'whatsapp', 16 ); ?></span>
							<span class="sr-tekst"> via WhatsApp (opent in een nieuw tabblad)</span>
						</a>
						<a class="stoelen-opnieuw" href="<?php echo esc_url( get_permalink( $pagina ) . '#stoelen' ); ?>">Nog een reservering maken</a>
					</div>
				</div>
			</div>

		<?php
		/* ------------------------------------------------------------------
		 * Geen ritten (meer).
		 * --------------------------------------------------------------- */
		elseif ( ! $ritten || ! $opstap ) :
			?>
			<div class="stoelen-leeg">
				<p class="tekst-groot"><?php echo esc_html( $b['leeg'] ?? 'Er staan op dit moment geen ritten open. Wil je met je eigen groep? Vraag dan een prijs aan voor de hele bus.' ); ?></p>
				<?php echo thema_knop( 'Vraag een prijs aan', thema_offerte_url(), 'paars' ); ?>
			</div>

		<?php
		/* ------------------------------------------------------------------
		 * Het formulier.
		 * --------------------------------------------------------------- */
		else :
			$js_ritten = array();
			foreach ( $ritten as $sleutel => $rit ) {
				$js_ritten[ $sleutel ] = array(
					'tekst' => ucfirst( thema_stoelen_datum( $rit['datum'] ) ),
					'vrij'  => 'open' === $rit['status'] ? $rit['vrij'] : 0,
				);
			}
			$js_opstap = array();
			foreach ( $opstap as $sleutel => $plek ) {
				$js_opstap[ $sleutel ] = trim( $plek['plaats'] . ( $plek['heen'] ? ', ' . $plek['heen'] : '' ) );
			}
			?>
			<form class="stoelen-formulier" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-stoelen
				data-prijs="<?php echo esc_attr( $prijs ); ?>"
				data-max="<?php echo (int) $config['max']; ?>"
				data-ritten="<?php echo esc_attr( wp_json_encode( $js_ritten ) ); ?>"
				data-opstap="<?php echo esc_attr( wp_json_encode( $js_opstap ) ); ?>">
				<input type="hidden" name="action" value="thema_stoelen">
				<input type="hidden" name="pagina" value="<?php echo (int) $pagina; ?>">
				<input type="hidden" name="begin" value="<?php echo (int) time(); ?>" data-stoelen-begin>
				<div class="stoelen-nectar" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

				<?php if ( isset( $fouten['algemeen'] ) || $fouten ) : ?>
					<div class="stoelen-melding" role="alert" tabindex="-1" data-stoelen-melding>
						<?php echo esc_html( $fouten['algemeen'] ?? 'Nog even checken: er klopt iets niet helemaal. Kijk bij de rode tekst.' ); ?>
					</div>
				<?php endif; ?>

				<div class="stoelen-stappen">

					<fieldset class="stoelen-stap">
						<legend class="stoelen-stap-kop"><span class="stoelen-stap-nummer" aria-hidden="true">1</span>Kies je avond</legend>
						<?php echo $foutje( 'rit' ); // phpcs:ignore ?>
						<div class="stoelen-keuzes stoelen-ritten">
							<?php foreach ( $ritten as $sleutel => $rit ) : ?>
								<?php
								$dicht  = 'open' !== $rit['status'];
								$gaat   = $rit['minimum'] && $rit['bezet'] >= $rit['minimum'];
								$krap   = ! $dicht && $config['krap'] && $rit['vrij'] <= $config['krap'];
								if ( 'vol' === $rit['status'] ) {
									$staat = 'Uitverkocht';
								} elseif ( $dicht ) {
									$staat = 'Aanmelden gesloten';
								} elseif ( $krap ) {
									$staat = 1 === $rit['vrij'] ? 'Laatste stoel!' : 'Nog ' . $rit['vrij'] . ' stoelen';
								} else {
									$staat = 'Stoelen vrij';
								}
								$klassen = 'stoelen-keuze stoelen-rit' . ( $dicht ? ' is-dicht' : '' ) . ( $krap ? ' is-krap' : '' );
								?>
								<label class="<?php echo esc_attr( $klassen ); ?>">
									<input type="radio" name="rit" value="<?php echo esc_attr( $sleutel ); ?>" required<?php checked( $gekozen['rit'], $sleutel ); ?><?php disabled( $dicht ); ?><?php echo $kenmerk( 'rit' ); // phpcs:ignore ?>>
									<span class="stoelen-rit-datum" aria-hidden="true">
										<span class="stoelen-rit-dag"><?php echo esc_html( thema_stoelen_datum( $rit['datum'], 'D' ) ); ?></span>
										<span class="stoelen-rit-nummer"><?php echo esc_html( $rit['datum']->format( 'j' ) ); ?></span>
										<span class="stoelen-rit-maand"><?php echo esc_html( thema_stoelen_datum( $rit['datum'], 'M' ) ); ?></span>
									</span>
									<span class="stoelen-keuze-tekst">
										<span class="stoelen-keuze-titel"><?php echo esc_html( ucfirst( thema_stoelen_datum( $rit['datum'], 'l' ) ) ); ?><span class="sr-tekst"><?php echo esc_html( ' ' . thema_stoelen_datum( $rit['datum'], 'j F Y' ) ); ?></span></span>
										<?php if ( $rit['label'] ) : ?><span class="stoelen-keuze-sub"><?php echo esc_html( $rit['label'] ); ?></span><?php endif; ?>
										<span class="stoelen-rit-staat"><?php echo esc_html( $staat ); ?></span>
									</span>
									<?php if ( $gaat && ! $dicht ) : ?>
										<span class="stoelen-rit-zeker"><?php echo thema_icoon( 'vink', 12, 3 ); ?>Rijdt zeker</span>
									<?php endif; ?>
								</label>
							<?php endforeach; ?>
						</div>
					</fieldset>

					<fieldset class="stoelen-stap">
						<legend class="stoelen-stap-kop"><span class="stoelen-stap-nummer" aria-hidden="true">2</span>Waar stap je in?</legend>
						<?php echo $foutje( 'opstap' ); // phpcs:ignore ?>
						<div class="stoelen-keuzes stoelen-opstap">
							<?php foreach ( $opstap as $sleutel => $plek ) : ?>
								<label class="stoelen-keuze stoelen-plek">
									<input type="radio" name="opstap" value="<?php echo esc_attr( $sleutel ); ?>" required<?php checked( $gekozen['opstap'], $sleutel ); ?><?php echo $kenmerk( 'opstap' ); // phpcs:ignore ?>>
									<span class="stoelen-plek-icoon" aria-hidden="true"><?php echo thema_icoon( 'bus', 22 ); ?></span>
									<span class="stoelen-keuze-tekst">
										<span class="stoelen-keuze-titel"><?php echo esc_html( $plek['plaats'] ); ?></span>
										<?php if ( $plek['plek'] ) : ?><span class="stoelen-keuze-sub"><?php echo esc_html( $plek['plek'] ); ?></span><?php endif; ?>
									</span>
									<?php if ( $plek['heen'] || $plek['terug'] ) : ?>
										<span class="stoelen-plek-tijden">
											<?php if ( $plek['heen'] ) : ?><span><span class="sr-tekst">Vertrek </span><?php echo thema_icoon( 'klok', 13 ); ?><?php echo esc_html( $plek['heen'] ); ?></span><?php endif; ?>
											<?php if ( $plek['terug'] ) : ?><span class="stoelen-plek-terug"><?php echo esc_html( 'terug ' . $plek['terug'] ); ?></span><?php endif; ?>
										</span>
									<?php endif; ?>
								</label>
							<?php endforeach; ?>
						</div>
						<?php if ( ! empty( $b['terugreis'] ) ) : ?><p class="stoelen-uitleg"><?php echo esc_html( $b['terugreis'] ); ?></p><?php endif; ?>
					</fieldset>

					<fieldset class="stoelen-stap">
						<legend class="stoelen-stap-kop"><span class="stoelen-stap-nummer" aria-hidden="true">3</span>Hoeveel stoelen?</legend>
						<?php echo $foutje( 'aantal' ); // phpcs:ignore ?>
						<div class="stoelen-aantal">
							<button type="button" class="stoelen-min" data-stoelen-min aria-label="Een stoel minder" hidden>&minus;</button>
							<label class="sr-tekst" for="stoelen-aantal">Aantal stoelen</label>
							<input id="stoelen-aantal" type="number" name="aantal" min="1" max="<?php echo (int) $config['max']; ?>" step="1" inputmode="numeric" value="<?php echo (int) $gekozen['aantal']; ?>" required data-stoelen-aantal<?php echo $kenmerk( 'aantal' ); // phpcs:ignore ?>>
							<button type="button" class="stoelen-plus" data-stoelen-plus aria-label="Een stoel meer" hidden>+</button>
							<span class="stoelen-aantal-prijs"><?php echo esc_html( 'x ' . thema_stoelen_euro( $prijs ) . ' per stoel, heen en terug' ); ?></span>
						</div>
						<p class="stoelen-uitleg" data-stoelen-aantal-uitleg><?php echo esc_html( 'Maximaal ' . $config['max'] . ' stoelen per aanmelding. Met een grotere groep? Huur dan de hele bus.' ); ?></p>
					</fieldset>

					<fieldset class="stoelen-stap">
						<legend class="stoelen-stap-kop"><span class="stoelen-stap-nummer" aria-hidden="true">4</span>Je gegevens</legend>
						<div class="stoelen-velden">
							<div class="stoelen-veld stoelen-veld-vol">
								<label for="stoelen-naam">Je naam</label>
								<input id="stoelen-naam" type="text" name="naam" autocomplete="name" required value="<?php echo $v( 'naam' ); // phpcs:ignore ?>"<?php echo $kenmerk( 'naam' ); // phpcs:ignore ?>>
								<?php echo $foutje( 'naam' ); // phpcs:ignore ?>
							</div>
							<div class="stoelen-veld">
								<label for="stoelen-telefoon">Mobiel nummer</label>
								<input id="stoelen-telefoon" type="tel" name="telefoon" autocomplete="tel" inputmode="tel" placeholder="bijv. 06 12345678" required value="<?php echo $v( 'telefoon' ); // phpcs:ignore ?>"<?php echo $kenmerk( 'telefoon' ); // phpcs:ignore ?>>
								<span class="stoelen-veld-uitleg">Hier sturen we de Tikkie naartoe.</span>
								<?php echo $foutje( 'telefoon' ); // phpcs:ignore ?>
							</div>
							<div class="stoelen-veld">
								<label for="stoelen-email">E-mailadres</label>
								<input id="stoelen-email" type="email" name="email" autocomplete="email" required value="<?php echo $v( 'email' ); // phpcs:ignore ?>"<?php echo $kenmerk( 'email' ); // phpcs:ignore ?>>
								<span class="stoelen-veld-uitleg">Voor je bevestiging.</span>
								<?php echo $foutje( 'email' ); // phpcs:ignore ?>
							</div>
							<div class="stoelen-veld stoelen-veld-vol" data-stoelen-mee>
								<label for="stoelen-mee">Wie gaan er met je mee? <span class="stoelen-optioneel">Mag je ook later doorgeven</span></label>
								<textarea id="stoelen-mee" name="meereizigers" rows="2" placeholder="Eén naam per regel"><?php echo esc_textarea( $waarden['meereizigers'] ?? '' ); ?></textarea>
							</div>
						</div>
						<label class="stoelen-akkoord">
							<input type="checkbox" name="akkoord" value="1" required<?php checked( ! empty( $waarden['akkoord'] ) ); ?><?php echo $kenmerk( 'akkoord' ); // phpcs:ignore ?>>
							<span><?php echo wp_kses( $b['akkoord'] ?? 'Iedereen in mijn groep is 18 jaar of ouder en gaat akkoord met de huisregels.', array( 'a' => array( 'href' => array(), 'target' => array() ) ) ); ?></span>
						</label>
						<?php echo $foutje( 'akkoord' ); // phpcs:ignore ?>
					</fieldset>
				</div>

				<aside class="stoelen-zijkant" aria-label="Je reservering">
					<div class="stoelen-ticket" data-stoelen-ticket>
						<div class="stoelen-ticket-boven">
							<span class="stoelen-ticket-label">Je ticket voor de bus</span>
							<p class="stoelen-ticket-titel"><?php echo esc_html( $config['evenement'] ); ?></p>
							<?php if ( $config['bestemming'] ) : ?><p class="stoelen-ticket-sub"><?php echo esc_html( $config['bestemming'] ); ?></p><?php endif; ?>
						</div>
						<dl class="stoelen-ticket-regels">
							<div><dt>Avond</dt><dd data-stoelen-toon="rit"><?php echo esc_html( $js_ritten[ $gekozen['rit'] ]['tekst'] ?? 'Nog kiezen' ); ?></dd></div>
							<div><dt>Instappen</dt><dd data-stoelen-toon="opstap"><?php echo esc_html( $js_opstap[ $gekozen['opstap'] ] ?? 'Nog kiezen' ); ?></dd></div>
							<div><dt>Stoelen</dt><dd data-stoelen-toon="aantal"><?php echo esc_html( $gekozen['aantal'] . ' x ' . thema_stoelen_euro( $prijs ) ); ?></dd></div>
						</dl>
						<div class="stoelen-ticket-onder">
							<div class="stoelen-ticket-totaal" aria-live="polite"><span>Totaal, heen en terug</span><strong data-stoelen-toon="totaal"><?php echo esc_html( thema_stoelen_euro( $gekozen['aantal'] * $prijs ) ); ?></strong></div>
						</div>
						<button type="submit" class="stoelen-verstuur">
							<span data-stoelen-toon="knop"><?php echo esc_html( 'Reserveer ' . ( 1 === $gekozen['aantal'] ? 'je stoel' : 'je stoelen' ) ); ?></span>
							<span class="stoelen-verstuur-pijl" aria-hidden="true"><?php echo thema_icoon( 'chevron' ); ?></span>
						</button>
						<p class="stoelen-ticket-noot"><?php echo esc_html( $b['betaalnoot'] ?? 'Nu nog niets betalen. Je krijgt binnen 1 werkdag een Tikkie, na betaling staat je stoel vast.' ); ?></p>
					</div>
				</aside>
			</form>
		<?php endif; ?>
	</div>
</section>
