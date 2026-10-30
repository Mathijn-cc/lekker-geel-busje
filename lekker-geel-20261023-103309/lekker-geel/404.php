<?php
/**
 * Pagina niet gevonden.
 */
get_header();
?>
<section class="sectie sectie-tekst sectie-paginakop sectie-404 tekst-smal">
	<div class="tekst-binnen">
		<div class="tekst-kop">
			<span class="eyebrow">404</span>
			<?php echo thema_kop( 'Deze bus rijdt hier *niet*', 'h1', 'kop-middel' ); ?>
		</div>
		<div class="opmaak"><p>De pagina die je zoekt bestaat niet (meer). Stap in bij de homepage, daar begint het feestje.</p></div>
		<?php echo thema_knop( 'Naar de homepage', home_url( '/' ), 'paars' ); ?>
	</div>
</section>
<?php
get_footer();
