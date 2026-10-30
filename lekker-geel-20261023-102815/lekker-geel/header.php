<?php
/**
 * Kop van de site: cursor, overlaymenu en de gekozen menustijl.
 */
$s     = thema_instellingen();
$stijl = $s['menu_stijl'];
$links = thema_hoofdmenu_items();
$tel   = $s['telefoon'];

$menuknop_pil = sprintf(
	'<button type="button" class="menuknop menuknop-pil" aria-label="Menu openen" aria-expanded="false" aria-controls="menulaag" data-menuknop><span class="menuknop-tekst" aria-hidden="true"><span class="menuknop-rol"><span>Menu</span><span>Open</span><span>Sluiten</span></span></span><span class="menuknop-cirkel" aria-hidden="true"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path class="menuknop-lijn-1" d="M4 12h16"/><path class="menuknop-lijn-2 is-kort" d="M4 12h16"/></svg></span></button>'
);

$offerte_knop = function ( $variant, $kort = false ) use ( $s ) {
	$label = $kort ? $s['menu_knop_kort'] : $s['menu_knop'];
	return sprintf(
		'<a class="knop-rond knop-rond-%1$s menu-offerte" href="%2$s"><span class="knoptekst" aria-hidden="true"><span class="knoptekst-schuif"><span>%3$s</span><span>%4$s</span></span></span><span class="sr-tekst">%3$s</span><span class="knop-rond-pijl" aria-hidden="true">%5$s</span></a>',
		esc_attr( $variant ),
		esc_url( thema_offerte_url() ),
		esc_html( $label ),
		esc_html( $s['menu_knop_hover'] ),
		thema_icoon( 'chevron' )
	);
};
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="<?php echo esc_attr( $s['kleur_paars'] ); ?>">
	<?php wp_head(); ?>
</head>
<body <?php body_class( thema_start_donker() ? 'start-donker' : '' ); ?>>
<?php wp_body_open(); ?>
<a class="overslaan" href="#inhoud">Naar de inhoud</a>

<div class="menulaag" id="menulaag" data-menulaag role="dialog" aria-modal="true" aria-label="Menu" aria-hidden="true">
	<div class="menulaag-scroll">
		<div class="menulaag-raster">
			<nav class="menulaag-links" aria-label="Menu">
				<?php foreach ( $links as $link ) : ?>
					<a class="overlaylink" href="<?php echo esc_url( $link['url'] ); ?>"<?php echo $link['doel'] ? ' target="' . esc_attr( $link['doel'] ) . '"' : ''; ?>><?php echo esc_html( $link['titel'] ); ?></a>
				<?php endforeach; ?>
			</nav>
			<div class="menulaag-kaart">
				<span class="menulaag-kaart-zin"><?php echo esc_html( $s['menu_kaart_tekst'] ); ?></span>
				<div class="contactlijst">
					<?php if ( $tel ) : ?>
						<a class="contactlink" href="<?php echo esc_attr( thema_telefoon_link() ); ?>"><span class="contactlink-icoon"><?php echo thema_icoon( 'telefoon', 22 ); ?></span><span class="contactlink-tekst"><span class="contactlink-label">Bel ons</span><span class="contactwaarde"><?php echo esc_html( $tel ); ?></span></span></a>
					<?php endif; ?>
					<?php if ( $s['email'] ) : ?>
						<a class="contactlink" href="mailto:<?php echo esc_attr( $s['email'] ); ?>"><span class="contactlink-icoon"><?php echo thema_icoon( 'mail', 22 ); ?></span><span class="contactlink-tekst"><span class="contactlink-label">Mail ons</span><span class="contactwaarde contactwaarde-mail"><?php echo esc_html( $s['email'] ); ?></span></span></a>
					<?php endif; ?>
				</div>
				<?php echo thema_knop( $s['menu_knop'], thema_offerte_url(), 'paars' ); ?>
			</div>
		</div>
	</div>
</div>

<div class="navhouder" data-navhouder>
	<?php if ( 'zwevend' === $stijl ) : ?>
		<nav class="nav nav-zwevend" aria-label="Hoofdmenu">
			<div class="nav-pil">
				<?php echo thema_logo( 'nav-logo' ); ?>
				<div class="nav-acties">
					<?php if ( $tel ) : ?>
						<a class="navlink nav-tel nav-breed" href="<?php echo esc_attr( thema_telefoon_link() ); ?>"><?php echo esc_html( $tel ); ?></a>
					<?php endif; ?>
					<span class="nav-breed"><?php echo $offerte_knop( 'geel' ); ?></span>
					<?php echo $menuknop_pil; ?>
				</div>
			</div>
		</nav>

	<?php elseif ( 'balk' === $stijl ) : ?>
		<nav class="nav nav-balk" aria-label="Hoofdmenu">
			<div class="nav-binnen">
				<?php echo thema_logo( 'nav-logo' ); ?>
				<div class="nav-links nav-breed">
					<?php foreach ( array_slice( $links, 0, 5 ) as $link ) : ?>
						<a class="navlink<?php echo $link['actief'] ? ' is-actief' : ''; ?>" href="<?php echo esc_url( $link['url'] ); ?>"><?php echo esc_html( $link['titel'] ); ?></a>
					<?php endforeach; ?>
					<?php if ( $tel ) : ?>
						<a class="navlink nav-tel" href="<?php echo esc_attr( thema_telefoon_link() ); ?>"><?php echo esc_html( $tel ); ?></a>
					<?php endif; ?>
				</div>
				<div class="nav-acties">
					<span class="nav-breed"><?php echo $offerte_knop( 'paars' ); ?></span>
					<span class="nav-smal"><?php echo $offerte_knop( 'paars', true ); ?></span>
					<button type="button" class="menuknop menuknop-rond nav-smal" aria-label="Menu openen" aria-expanded="false" aria-controls="menulaag" data-menuknop><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path class="menuknop-lijn-1" d="M4 12h16"/><path class="menuknop-lijn-2" d="M4 12h16"/></svg></button>
				</div>
			</div>
		</nav>

	<?php else : ?>
		<nav class="nav nav-overlay" aria-label="Hoofdmenu">
			<div class="nav-binnen">
				<?php echo thema_logo( 'nav-logo' ); ?>
				<div class="nav-acties">
					<?php if ( $tel ) : ?>
						<a class="navlink nav-tel nav-breed" href="<?php echo esc_attr( thema_telefoon_link() ); ?>"><?php echo esc_html( $tel ); ?></a>
					<?php endif; ?>
					<span class="nav-breed"><?php echo $offerte_knop( 'geel' ); ?></span>
					<?php echo $menuknop_pil; ?>
				</div>
			</div>
		</nav>
	<?php endif; ?>
</div>

<div class="pagina" data-pagina>
<main id="inhoud" class="inhoud">
