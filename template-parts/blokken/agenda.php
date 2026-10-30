<?php
/**
 * Blok: korte agenda met evenementen, los van de kaarten.
 * Kop en tekst links (plakt mee), evenementen rechts op datum.
 */
$b       = $args['blok'];
$zone    = wp_timezone();
$vandaag = ( new DateTimeImmutable( 'today', $zone ) )->format( 'Ymd' );

$lees_datum = function ( $waarde ) use ( $zone ) {
	$waarde = preg_replace( '/\D/', '', (string) $waarde );
	if ( 8 !== strlen( $waarde ) ) {
		return null;
	}
	$datum = DateTimeImmutable::createFromFormat( '!Ymd', $waarde, $zone );
	return $datum ?: null;
};

$items = array();
foreach ( (array) ( $b['items'] ?? array() ) as $volgorde => $item ) {
	if ( empty( $item['titel'] ) ) {
		continue;
	}
	$start = $lees_datum( $item['start'] ?? '' );
	$eind  = $lees_datum( $item['eind'] ?? '' );
	if ( $start && $eind && $eind < $start ) {
		$eind = null;
	}
	$laatste = $eind ?: $start;
	if ( ! empty( $b['verlopen_verbergen'] ) && $laatste && $laatste->format( 'Ymd' ) < $vandaag ) {
		continue;
	}
	$items[] = array_merge( $item, array( '_start' => $start, '_eind' => $eind, '_volgorde' => $volgorde ) );
}

if ( ! $items ) {
	return;
}

// Op datum, evenementen zonder datum houden hun volgorde en komen achteraan.
usort( $items, function ( $a, $c ) {
	if ( $a['_start'] && $c['_start'] ) {
		return $a['_start'] <=> $c['_start'] ?: $a['_volgorde'] <=> $c['_volgorde'];
	}
	if ( $a['_start'] xor $c['_start'] ) {
		return $a['_start'] ? -1 : 1;
	}
	return $a['_volgorde'] <=> $c['_volgorde'];
} );

$datum_regel = function ( $item ) {
	if ( ! empty( $item['datum_tekst'] ) ) {
		return $item['datum_tekst'];
	}
	$start = $item['_start'];
	$eind  = $item['_eind'];
	if ( ! $start ) {
		return '';
	}
	if ( ! $eind || $eind->format( 'Ymd' ) === $start->format( 'Ymd' ) ) {
		return wp_date( 'l j F Y', $start->getTimestamp(), $start->getTimezone() );
	}
	$zelfde_jaar  = $start->format( 'Y' ) === $eind->format( 'Y' );
	$zelfde_maand = $zelfde_jaar && $start->format( 'm' ) === $eind->format( 'm' );
	$begin        = wp_date( $zelfde_maand ? 'j' : ( $zelfde_jaar ? 'j F' : 'j F Y' ), $start->getTimestamp(), $start->getTimezone() );
	return $begin . ' tot en met ' . wp_date( 'j F Y', $eind->getTimestamp(), $eind->getTimezone() );
};

$eigen_host = wp_parse_url( home_url(), PHP_URL_HOST );
echo thema_sectie_open( $b, 'agenda' );
?>
	<div class="rij rij-een-twee">
		<div class="kolom kolom-plak agenda-tekst">
			<?php echo thema_eyebrow( $b['eyebrow'] ?? '' ); ?>
			<?php echo thema_kop( $b['kop'] ?? '', 'h2', 'kop-middel' ); ?>
			<?php if ( ! empty( $b['tekst'] ) ) : ?><p class="tekst-groot" data-animeer><?php echo esc_html( $b['tekst'] ); ?></p><?php endif; ?>
			<?php if ( ! empty( $b['knop']['url'] ) ) : ?>
				<div class="knop-houder" data-animeer><?php echo thema_blok_knop( $b['knop'], $b, 'paars' ); ?></div>
			<?php endif; ?>
		</div>
		<div class="kolom kolom-breed">
			<ol class="agenda-lijst">
				<?php foreach ( $items as $item ) : ?>
					<?php
					$start  = $item['_start'];
					$regel  = $datum_regel( $item );
					$link   = (array) ( $item['link'] ?? array() );
					$url    = $link['url'] ?? '';
					$extern = $url && wp_parse_url( $url, PHP_URL_HOST ) && wp_parse_url( $url, PHP_URL_HOST ) !== $eigen_host;
					$nieuw  = $extern || '_blank' === ( $link['target'] ?? '' );
					?>
					<li class="agenda-item" data-animeer>
						<?php if ( $start ) : ?>
							<span class="agenda-datum" aria-hidden="true">
								<span class="agenda-maand"><?php echo esc_html( wp_date( 'M', $start->getTimestamp(), $start->getTimezone() ) ); ?></span>
								<span class="agenda-dag"><?php echo esc_html( $start->format( 'j' ) ); ?></span>
							</span>
						<?php else : ?>
							<span class="agenda-datum agenda-datum-icoon" aria-hidden="true"><?php echo thema_icoon( 'kalender', 26 ); ?></span>
						<?php endif; ?>
						<div class="agenda-info">
							<h3 class="agenda-titel"><?php echo esc_html( $item['titel'] ); ?></h3>
							<?php if ( $regel || ! empty( $item['plek'] ) ) : ?>
								<p class="agenda-meta">
									<?php if ( $regel ) : ?><span class="agenda-wanneer"><?php echo esc_html( $regel ); ?></span><?php endif; ?>
									<?php if ( ! empty( $item['plek'] ) ) : ?><span class="agenda-plek"><?php echo esc_html( $item['plek'] ); ?></span><?php endif; ?>
								</p>
							<?php endif; ?>
							<?php if ( ! empty( $item['tekst'] ) ) : ?><p class="agenda-omschrijving"><?php echo esc_html( $item['tekst'] ); ?></p><?php endif; ?>
						</div>
						<?php if ( $url ) : ?>
							<a class="agenda-link" href="<?php echo esc_url( $url ); ?>"<?php echo $nieuw ? ' target="_blank" rel="noopener"' : ''; ?>>
								<span><?php echo esc_html( $link['title'] ?: 'Bekijk' ); ?></span>
								<?php echo thema_icoon( 'pijl-op', 16 ); ?>
								<?php if ( $nieuw ) : ?><span class="sr-tekst"> (opent in een nieuw tabblad)</span><?php endif; ?>
							</a>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>
	</div>
</section>
