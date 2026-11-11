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

/* Weergave Bento: de eerste is groot (met foto: tekst over de foto), de rest klein. Achtergrond per tegel te kiezen, tekstkleur volgt vanzelf. */
if ( in_array( $b['weergave'] ?? 'tabs', array( 'bento', 'stickers' ), true ) ) :
	$kleuren = array( 'paars', 'zand', 'geel', 'zand' );
	echo thema_sectie_open( $b, 'voordelen', 'voordelen-bento' );
	?>
	<div class="voordelen-binnen">
		<div class="bentoraster">
			<?php foreach ( $items as $i => $item ) : ?>
				<?php
				$foto  = ! empty( $item['beeld'] );
				$kleur = $item['kleur'] ?? 'auto';
				if ( ! in_array( $kleur, array( 'paars', 'geel', 'zand', 'wit', 'roze' ), true ) ) {
					$kleur = 0 === $i ? 'paars' : $kleuren[ ( $i - 1 ) % count( $kleuren ) ];
				}
				$donker = $foto || 'paars' === $kleur;
				?>
				<article class="bentotegel bentotegel-<?php echo esc_attr( $kleur ); ?><?php echo 0 === $i ? ' bentotegel-groot' : ''; ?><?php echo $foto ? ' bentotegel-foto' : ''; ?><?php echo $donker ? ' bentotegel-donker' : ' bentotegel-licht'; ?>" data-animeer style="--i:<?php echo (int) $i; ?>">
					<?php if ( $foto ) : ?>
						<?php echo thema_afbeelding( $item['beeld'], 0 === $i ? 'breed' : 'kaart', 'bentotegel-beeld', true, array( 'alt' => $item['titel'], 'sizes' => thema_sizes( 0 === $i ? 'helft-hoog' : 'kaart' ) ) ); ?>
						<span class="bentotegel-waas" aria-hidden="true"></span>
					<?php endif; ?>
					<div class="bentotegel-inhoud">
						<span class="bentotegel-nummer" aria-hidden="true"><?php echo esc_html( str_pad( $i + 1, 2, '0', STR_PAD_LEFT ) ); ?></span>
						<div class="bentotegel-tekst">
							<h3 class="bentotegel-titel"><?php echo esc_html( $item['titel'] ); ?></h3>
							<?php if ( ! empty( $item['tekst'] ) ) : ?><p class="bentotegel-omschrijving"><?php echo esc_html( $item['tekst'] ); ?></p><?php endif; ?>
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
							<?php echo thema_afbeelding( $item['beeld'] ?? 0, 'large', 'voordeeltegel-foto', true, array( 'alt' => $item['titel'], 'sizes' => thema_sizes( 'tegel' ) ) ); ?>
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
					<?php echo thema_afbeelding( $item['beeld'] ?? 0, 'breed', 'voordeelfoto' . ( 0 === $i ? ' is-actief' : '' ), true, array( 'data-foto' => $i, 'alt' => $item['titel'], 'sizes' => thema_sizes( 'helft-hoog' ) ) ); ?>
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
