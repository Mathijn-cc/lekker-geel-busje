<?php
/**
 * Blok: voordelen als tabs met foto, lopen vanzelf door.
 */
$b     = $args['blok'];
$items = array_values( array_filter( (array) ( $b['items'] ?? array() ), fn( $i ) => ! empty( $i['titel'] ) ) );
if ( ! $items ) {
	return;
}
$uid = 'voordelen-' . (int) ( $args['index'] ?? 0 );

/* Weergave Stickers: kaarten in de kleuren van de site, met icoon en groot nummer. De eerste is breed, met foto. */
if ( 'stickers' === ( $b['weergave'] ?? 'tabs' ) ) :
	$kleuren = array( 'zand', 'paars', 'zand', 'geel', 'zand' );
	echo thema_sectie_open( $b, 'voordelen', 'voordelen-stickers' );
	?>
	<div class="voordelen-binnen">
		<div class="stickers">
			<?php foreach ( $items as $i => $item ) : ?>
				<?php $met_foto = 0 === $i && ! empty( $item['beeld'] ); ?>
				<article class="sticker sticker-<?php echo esc_attr( $kleuren[ $i % count( $kleuren ) ] ); ?><?php echo 0 === $i ? ' sticker-breed' : ''; ?><?php echo $met_foto ? ' sticker-met-foto' : ''; ?>" data-animeer style="--i:<?php echo (int) $i; ?>">
					<div class="sticker-binnen">
						<div class="sticker-tekst">
							<div class="sticker-kop">
								<span class="sticker-badge" aria-hidden="true"><?php echo thema_icoon( $item['icoon'] ?: 'vonk', 26, 2 ); ?></span>
								<span class="sticker-nummer" aria-hidden="true"><?php echo esc_html( str_pad( $i + 1, 2, '0', STR_PAD_LEFT ) ); ?></span>
							</div>
							<div class="sticker-inhoud">
								<h3 class="sticker-titel"><?php echo esc_html( $item['titel'] ); ?></h3>
								<?php if ( ! empty( $item['tekst'] ) ) : ?><p class="sticker-omschrijving"><?php echo esc_html( $item['tekst'] ); ?></p><?php endif; ?>
							</div>
						</div>
						<?php if ( $met_foto ) : ?>
							<div class="sticker-beeld"><?php echo thema_afbeelding( $item['beeld'], 'kaart', 'sticker-foto', true, array( 'alt' => $item['titel'] ) ); ?></div>
						<?php endif; ?>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
		<?php if ( ! empty( $b['knop']['url'] ) ) : ?><div class="voordelen-knop"><?php echo thema_blok_knop( $b['knop'], $b, 'geel', true ); ?></div><?php endif; ?>
	</div>
</section>
	<?php
	return;
endif;

/* Weergave Tegels: alle voordelen tegelijk, elk met foto, icoon en tekst. */
if ( 'tegels' === ( $b['weergave'] ?? 'tabs' ) ) :
	echo thema_sectie_open( $b, 'voordelen', 'voordelen-tegels' );
	?>
	<div class="voordelen-binnen">
		<div class="voordeeltegels">
			<?php foreach ( $items as $i => $item ) : ?>
				<article class="voordeeltegel" data-animeer style="--i:<?php echo (int) $i; ?>">
					<div class="voordeeltegel-binnen">
						<div class="voordeeltegel-beeld">
							<?php echo thema_afbeelding( $item['beeld'] ?? 0, 'large', 'voordeeltegel-foto', true, array( 'alt' => $item['titel'] ) ); ?>
							<span class="voordeeltegel-nummer"><?php echo esc_html( str_pad( $i + 1, 2, '0', STR_PAD_LEFT ) ); ?></span>
						</div>
						<div class="voordeeltegel-inhoud">
							<span class="voordeel-badge" aria-hidden="true"><?php echo thema_icoon( $item['icoon'] ?: 'vonk', 26, 2 ); ?></span>
							<h3 class="voordeeltegel-titel"><?php echo esc_html( $item['titel'] ); ?></h3>
							<?php if ( ! empty( $item['tekst'] ) ) : ?><p class="voordeeltegel-tekst"><?php echo esc_html( $item['tekst'] ); ?></p><?php endif; ?>
						</div>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
		<?php if ( ! empty( $b['knop']['url'] ) ) : ?><div class="voordelen-knop"><?php echo thema_blok_knop( $b['knop'], $b, 'geel', true ); ?></div><?php endif; ?>
	</div>
</section>
	<?php
	return;
endif;

echo thema_sectie_open( $b, 'voordelen', 'rechts' === ( $b['foto_kant'] ?? 'links' ) ? 'voordelen-foto-rechts' : '' );
?>
	<div class="voordelen-binnen">
		<div class="voordeelpodium" data-voordelen>
			<div class="voordeelbeeld" data-schuif>
				<?php foreach ( $items as $i => $item ) : ?>
					<?php echo thema_afbeelding( $item['beeld'] ?? 0, 'breed', 'voordeelfoto' . ( 0 === $i ? ' is-actief' : '' ), 0 !== $i, array( 'data-foto' => $i, 'alt' => $item['titel'] ) ); ?>
				<?php endforeach; ?>
				<div class="voordeelbeeld-waas" aria-hidden="true"></div>
				<span class="schuinlabel schuinlabel-paars voordeelbeeld-label" data-voordeel-label><?php echo esc_html( $items[0]['titel'] ); ?></span>
			</div>
			<div class="voordeellijst" role="tablist" aria-label="Voordelen">
				<?php foreach ( $items as $i => $item ) : ?>
					<button class="voordeelknop<?php echo 0 === $i ? ' is-actief' : ''; ?>" style="--i:<?php echo (int) $i; ?>" type="button" role="tab" id="<?php echo esc_attr( $uid . '-' . $i ); ?>" aria-selected="<?php echo 0 === $i ? 'true' : 'false'; ?>" data-stap="<?php echo 0 === $i ? 'actief' : ( 1 === $i ? 'volgende' : ( 2 === $i ? 'derde' : 'komend' ) ); ?>" data-titel="<?php echo esc_attr( $item['titel'] ); ?>">
						<span class="voordeel-badge" aria-hidden="true"><?php echo thema_icoon( $item['icoon'] ?: 'vonk', 26, 2 ); ?></span>
						<span class="voordeel-inhoud">
							<span class="voordeel-kop">
								<span class="voordeel-titel"><?php echo esc_html( $item['titel'] ); ?></span>
								<span class="voordeel-nummer"><?php echo esc_html( str_pad( $i + 1, 2, '0', STR_PAD_LEFT ) ); ?></span>
							</span>
							<span class="voordeel-uitklap"><span class="voordeel-uitklap-binnen"><span class="voordeel-tekst"><?php echo esc_html( $item['tekst'] ?? '' ); ?></span></span></span>
						</span>
						<span class="voordeel-voortgang" aria-hidden="true"></span>
					</button>
				<?php endforeach; ?>
			</div>
		</div>
		<?php if ( ! empty( $b['knop']['url'] ) ) : ?><div class="voordelen-knop"><?php echo thema_blok_knop( $b['knop'], $b, 'geel', true ); ?></div><?php endif; ?>
	</div>
</section>
