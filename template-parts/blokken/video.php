<?php
/**
 * Blok: video of poster, in een kader of over de volle breedte.
 */
$b        = $args['blok'];
$poster   = (int) ( $b['poster'] ?? 0 );
$mp4      = $b['bestand'] ?? '';
$embed    = $b['embed'] ?? '';
$breedte  = in_array( $b['breedte'] ?? 'kader', array( 'smal', 'kader', 'vol' ), true ) ? $b['breedte'] : 'kader';
$vol      = 'vol' === $breedte;
$hoogte   = in_array( $b['hoogte'] ?? 'standaard', array( 'standaard', 'laag', 'hoog', 'scherm' ), true ) ? $b['hoogte'] : 'standaard';
$autoplay = ! empty( $b['autoplay'] );
if ( ! $poster && ! $mp4 && ! $embed ) {
	return;
}

/* Bij automatisch afspelen gaat de embed direct in de pagina, gedempt. */
if ( $embed && ! $mp4 && $autoplay ) {
	$embed = preg_replace_callback( '/src="([^"]+)"/', function ( $m ) {
		$url = html_entity_decode( $m[1] );
		$url = false !== strpos( $url, 'vimeo' )
			? add_query_arg( array( 'autoplay' => 1, 'muted' => 1, 'loop' => 1, 'background' => 1 ), $url )
			: add_query_arg( array( 'autoplay' => 1, 'mute' => 1, 'controls' => 0, 'playsinline' => 1, 'rel' => 0 ), $url );
		return 'src="' . esc_url( $url ) . '"';
	}, $embed, 1 );
}

/* Verhouding van de embed, zodat hij het vlak vult zoals een foto. */
$maat = '';
if ( $embed && preg_match( '/width="(\d+)"/', $embed, $w ) && preg_match( '/height="(\d+)"/', $embed, $h ) && (int) $h[1] ) {
	$maat = ' style="--breed:' . (int) $w[1] . ';--hoog:' . (int) $h[1] . '"';
}

echo thema_sectie_open( $b, 'video', 'video-' . $breedte . ' video-hoogte-' . $hoogte, ' data-donker' );
?>
	<div class="video-vlak<?php echo $autoplay ? ' is-spelend' : ''; ?>"<?php echo 'kader' === $breedte ? ' data-wide' : ''; ?><?php echo $maat; ?>>
		<?php if ( $mp4 ) : ?>
			<video class="video-bestand" src="<?php echo esc_url( $mp4 ); ?>"<?php echo $poster ? ' poster="' . esc_url( wp_get_attachment_image_url( $poster, 'breed' ) ) . '"' : ''; ?> playsinline<?php echo $autoplay ? ' autoplay muted loop preload="auto"' : ' preload="none"'; ?> data-video></video>
		<?php elseif ( $embed && $autoplay ) : ?>
			<?php echo $embed; // oEmbed-html van WordPress. ?>
		<?php elseif ( $poster ) : ?>
			<?php echo thema_afbeelding( $poster, 'breed', 'video-poster' ); ?>
		<?php endif; ?>
		<?php if ( $embed && ! $mp4 && ! $autoplay ) : ?>
			<template data-video-embed><?php echo $embed; // oEmbed-html van WordPress. ?></template>
		<?php endif; ?>
		<?php if ( ( $mp4 || $embed ) && ! $autoplay ) : ?>
			<button type="button" class="video-speel" data-video-speel aria-label="Video afspelen"><?php echo thema_icoon( 'speel', 30, 2.2 ); ?></button>
		<?php endif; ?>
	</div>
</section>
