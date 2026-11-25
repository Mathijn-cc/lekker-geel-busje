/**
 * Inzichten: lichte metingen voor het dashboard Inzichten in WordPress (zie inc/inzichten.php).
 *
 * - Formulieren: gezien, gestart, verzonden en waar iemand afhaakt (het laatste veld dat hij aanraakte).
 * - Contactformulier vraag voor vraag en de WhatsApp-chat: welke stappen bezoekers bereiken.
 * - Klikken op externe links, telefoon, e-mail en WhatsApp.
 *
 * Zuinig: geen cookies, geen persoonsgegevens, geen ingevulde waarden. Metingen gaan in één bundel met
 * navigator.sendBeacon de deur uit, als de pagina verdwijnt of als er tien klaarstaan. Het script hangt
 * alleen luisteraars op het document; er draait niets zolang de bezoeker niets doet.
 */
(function () {
	'use strict';

	var url = window.lgbInzichtUrl;
	if (!url || !navigator.sendBeacon) { return; }

	var rij = [];
	var pad = window.location.pathname;
	var formulieren = [];

	function stuur() {
		if (!rij.length) { return; }
		var bundel = JSON.stringify({ p: pad, e: rij.splice(0, 25) });
		try { navigator.sendBeacon(url, new Blob([bundel], { type: 'text/plain' })); } catch (e) {}
		if (rij.length) { stuur(); }
	}

	function tel(soort, naam, detail) {
		rij.push([soort, String(naam || '').slice(0, 120), String(detail || '').slice(0, 120)]);
		if (rij.length >= 10) { stuur(); }
	}

	/* Voor andere scripts (WhatsApp-chat, contact vraag voor vraag). */
	window.lgbInzicht = tel;

	/* ---------- Formulieren ---------- */

	function naamVan(form) {
		var gf = (form.id || '').match(/^gform_(\d+)$/);
		if (gf) { return 'gf:' + gf[1]; }
		if (form.classList.contains('stoelen-formulier')) { return 'stoelen'; }
		if (form.classList.contains('offertezin')) { return 'offertezin'; }
		return '';
	}

	/* Huidige pagina van een formulier met meerdere stappen (Gravity Forms), anders 1. */
	function stapVan(form) {
		var bron = form.querySelector('input[name^="gform_source_page_number_"]');
		return bron ? parseInt(bron.value, 10) || 1 : 1;
	}

	function labelVan(veld) {
		var houder = veld.closest('.gfield, fieldset, .stoelen-veld, label');
		var label = houder && houder.querySelector('.gfield_label, legend, .stoelen-label, .gform-field-label');
		var tekst = label ? label.textContent : (veld.getAttribute('aria-label') || veld.name || '');
		return tekst.replace(/\(Vereist\)/g, '').replace(/\s+/g, ' ').trim().slice(0, 80);
	}

	function volg(form) {
		var naam = naamVan(form);
		if (!naam || form.getAttribute('data-inzicht')) { return; }
		form.setAttribute('data-inzicht', '1');
		/* Het contactformulier vraag voor vraag meet zijn eigen stappen; het verborgen formulier niet dubbel tellen. */
		if (form.closest('[data-stappen-formulier]')) { return; }
		var staat = { form: form, naam: naam, stap: stapVan(form), gestart: false, laatste: '', weg: false };
		formulieren.push(staat);

		if (staat.stap === 1 && 'IntersectionObserver' in window) {
			var kijker = new IntersectionObserver(function (items) {
				if (items.some(function (i) { return i.isIntersecting; })) {
					tel('formulier', naam, 'gezien');
					kijker.disconnect();
				}
			}, { threshold: 0.3 });
			kijker.observe(form);
		}
		form.addEventListener('focusin', function (e) {
			var veld = e.target;
			if (!veld.matches('input, select, textarea') || veld.type === 'hidden') { return; }
			if (!staat.gestart && staat.stap === 1) { tel('formulier', naam, 'gestart'); }
			staat.gestart = true;
			staat.laatste = labelVan(veld);
		});
		form.addEventListener('submit', function () {
			staat.weg = true;
			tel('formulier', naam, 'verzonden');
			stuur();
		});
	}

	function zoekFormulieren() {
		document.querySelectorAll('form[id^="gform_"], form.stoelen-formulier, form.offertezin').forEach(volg);
	}

	/* ---------- Contact vraag voor vraag ---------- */

	var contact = { gestart: false, laatste: '', klaar: false, stappen: 0 };
	window.lgbInzichtStap = function (id) {
		tel('stap', 'contact', id);
		/* De eerste vraag staat er bij het laden al; pas bij de tweede is iemand echt begonnen. */
		contact.stappen++;
		contact.gestart = contact.stappen > 1;
		contact.laatste = id;
		if (id === 'bedankt' || id === 'faq-bedankt' || id === 'bezig') { contact.klaar = true; }
	};

	/* ---------- Links ---------- */

	document.addEventListener('click', function (e) {
		var a = e.target.closest && e.target.closest('a[href]');
		if (!a) { return; }
		var href = a.getAttribute('href') || '';
		if (/^tel:/i.test(href)) { tel('contact', 'telefoon', ''); return; }
		if (/^mailto:/i.test(href)) { tel('contact', 'e-mail', ''); return; }
		var doel;
		try { doel = new URL(a.href, window.location.href); } catch (err) { return; }
		if (!/^https?:$/.test(doel.protocol) || doel.host === window.location.host) { return; }
		if (/(^|\.)wa\.me$|whatsapp\.com$/.test(doel.host)) { tel('contact', 'whatsapp', ''); return; }
		tel('link', (doel.host.replace(/^www\./, '') + doel.pathname).replace(/\/$/, ''), (a.textContent || '').replace(/\s+/g, ' ').trim().slice(0, 60));
	}, true);

	/* ---------- Afhaken en versturen bij vertrek ---------- */

	function vertrek() {
		formulieren.forEach(function (s) {
			if (s.gestart && !s.weg && !s.gemeld) {
				s.gemeld = true;
				tel('formulier', s.naam, 'afgehaakt:' + (s.stap > 1 ? 'stap ' + s.stap + ', ' : '') + (s.laatste || 'onbekend'));
			}
		});
		if (contact.gestart && !contact.klaar && !contact.gemeld) {
			contact.gemeld = true;
			tel('stap', 'contact', 'afgehaakt:' + contact.laatste);
		}
		stuur();
	}
	/* Tabblad op de achtergrond: alleen de bundel versturen, afhaken telt pas als de pagina echt weggaat. */
	document.addEventListener('visibilitychange', function () {
		if (document.visibilityState === 'hidden') { stuur(); }
	});
	window.addEventListener('pagehide', vertrek);

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', zoekFormulieren);
	} else {
		zoekFormulieren();
	}
	/* Gravity Forms met AJAX zet het formulier na een stap opnieuw neer. */
	document.addEventListener('gform_post_render', zoekFormulieren);
	if (window.jQuery) { window.jQuery(document).on('gform_post_render', zoekFormulieren); }
})();
