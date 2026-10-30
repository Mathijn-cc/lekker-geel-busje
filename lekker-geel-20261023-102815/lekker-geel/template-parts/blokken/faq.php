<?php
/**
 * Blok: veelgestelde vragen met uitklappen en FAQ-schema.
 */
$b      = $args['blok'];
$vragen = array_values( array_filter( (array) ( $b['vragen'] ?? array() ), fn( $v ) => ! empty( $v['vraag'] ) ) );
$s      = thema_instellingen();
$uid    = 'faq-' . (int) ( $args['index'] ?? 0 );
echo thema_sectie_open( $b, 'faq' );
?>
	<div class="rij rij-een-twee">
		<div class="kolom kolom-plak faq-tekst">
			<?php echo thema_kop( $b['kop'] ?? '', 'h2', 'kop-middel' ); ?>
			<?php if ( ! empty( $b['tekst'] ) ) : ?><p class="tekst-groot" data-animeer><?php echo esc_html( $b['tekst'] ); ?></p><?php endif; ?>
			<div class="knop-houder" data-animeer>
				<?php
				if ( ! empty( $b['knop']['url'] ) ) {
					echo thema_blok_knop( $b['knop'], $b, 'paars' );
				} elseif ( $s['telefoon'] ) {
					echo thema_blok_knop( array( 'title' => $s['telefoon'], 'url' => thema_telefoon_link() ), $b, 'paars' );
				}
				?>
			</div>
		</div>
		<div class="kolom kolom-breed faq-lijst" data-accordeon<?php echo $s['faq_een_open'] ? ' data-een-open' : ''; ?>>
			<?php foreach ( $vragen as $i => $v ) : ?>
				<?php $open = 0 === $i && $s['faq_een_open']; ?>
				<div class="faq-item<?php echo $open ? ' is-open' : ''; ?>" data-animeer>
					<button type="button" class="faq-vraag" id="<?php echo esc_attr( "{$uid}-v{$i}" ); ?>" aria-expanded="<?php echo $open ? 'true' : 'false'; ?>" aria-controls="<?php echo esc_attr( "{$uid}-a{$i}" ); ?>">
						<span><?php echo esc_html( $v['vraag'] ); ?></span>
						<span class="faq-plus" aria-hidden="true"><?php echo thema_icoon( 'chevron', 18 ); ?></span>
					</button>
					<div class="faq-antwoord" id="<?php echo esc_attr( "{$uid}-a{$i}" ); ?>" role="region" aria-labelledby="<?php echo esc_attr( "{$uid}-v{$i}" ); ?>">
						<div class="faq-antwoord-binnen"><p><?php echo nl2br( esc_html( $v['antwoord'] ?? '' ) ); ?></p></div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php
if ( ! empty( $b['schema'] ) && $vragen ) {
	$schema = array(
		'@context'   => 'https://schema.org',
		'@type'      => 'FAQPage',
		'mainEntity' => array_map( fn( $v ) => array(
			'@type'          => 'Question',
			'name'           => $v['vraag'],
			'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $v['antwoord'] ?? '' ),
		), $vragen ),
	);
	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>';
}
