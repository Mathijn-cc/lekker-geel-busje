<?php
/**
 * Blok: Spotify-playlist. Spotify laadt pas na een klik (geen cookies van Spotify voor die tijd, en snel).
 * Zonder link toont het blok alleen de nummers; zonder link en zonder nummers niets (beheerders zien een melding).
 */
$b       = $args['blok'];
$link    = trim( (string) ( $b['spotify'] ?? '' ) );
$nummers = thema_lijst( $b['nummers'] ?? '' );
$embed   = '';
if ( preg_match( '~open\.spotify\.com/(?:intl-[a-z]+/)?(playlist|album)/([A-Za-z0-9]+)~', $link, $m ) ) {
	$embed = 'https://open.spotify.com/embed/' . $m[1] . '/' . $m[2] . '?utm_source=generator';
}

if ( ! $embed && ! $nummers ) {
	if ( current_user_can( 'edit_pages' ) ) {
		echo '<p class="melding">Playlist: vul een Spotify-link of een paar nummers in.</p>';
	}
	return;
}
wp_enqueue_script( 'thema-playlist' );

echo thema_sectie_open( $b, 'playlist' );
?>
	<div class="playlist-binnen">
		<div class="playlist-kop">
			<?php echo thema_eyebrow( $b['eyebrow'] ?? '' ); ?>
			<?php echo thema_kop( $b['kop'] ?? '', 'h2', 'kop-middel' ); ?>
			<?php if ( ! empty( $b['tekst'] ) ) : ?><p class="tekst-groot" data-animeer><?php echo esc_html( $b['tekst'] ); ?></p><?php endif; ?>
			<?php if ( $embed ) : ?>
				<a class="playlist-spotify" href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener"><?php echo thema_icoon( 'extern', 16 ); ?><?php echo esc_html( ( $b['knop'] ?? '' ) ?: 'Open in Spotify' ); ?></a>
			<?php endif; ?>
		</div>

		<div class="playlist-vak" data-animeer>
			<?php if ( $embed ) : ?>
				<div class="playlist-speler" data-playlist="<?php echo esc_url( $embed ); ?>">
					<button class="playlist-start" type="button" data-playlist-start>
						<span class="playlist-plaat" aria-hidden="true"><span class="playlist-label"><?php echo thema_icoon( 'bus', 26 ); ?></span></span>
						<span class="playlist-start-tekst">
							<span class="playlist-start-titel"><?php echo esc_html( ( $b['titel'] ?? '' ) ?: 'Druk op play' ); ?></span>
							<span class="playlist-start-uitleg">Spotify laadt pas als je op play drukt.</span>
						</span>
						<span class="playlist-knop" aria-hidden="true"><?php echo thema_icoon( 'speel', 22 ); ?></span>
					</button>
				</div>
			<?php endif; ?>

			<?php if ( $nummers ) : ?>
				<div class="playlist-nummers">
					<p class="playlist-nummers-titel"><?php echo esc_html( $embed ? 'Een greep uit de lijst' : 'Op de playlist' ); ?></p>
					<ol>
						<?php foreach ( $nummers as $nummer ) : ?>
							<?php $delen = array_map( 'trim', explode( '|', $nummer, 2 ) ); ?>
							<li>
								<span class="playlist-titel"><?php echo esc_html( $delen[1] ?? $delen[0] ); ?></span>
								<?php if ( isset( $delen[1] ) ) : ?><span class="playlist-artiest"><?php echo esc_html( $delen[0] ); ?></span><?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ol>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>
