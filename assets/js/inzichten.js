/**
 * Inzichten: lichte metingen voor het dashboard Inzichten in WordPress (zie inc/inzichten.php).
 *
 * - Formulieren: gezien, gestart, verzonden, invultijd, mobiel of desktop, en per veld: aangeraakt, ingevuld
 *   en of iemand daar afhaakte (het laatste veld dat hij aanraakte, met de stap erbij).
 * - Contactformulier vraag voor vraag en de WhatsApp-chat: welke stappen bezoekers bereiken.
 * - Klikken op externe links, telefoon, e-mail en WhatsApp.
 *
 * Zuinig: geen cookies, geen persoonsgegevens, nooit ingevulde waarden. Metingen gaan gebundeld met
 * navigator.sendBeacon de deur uit, als de pagina verdwijnt of als er tien klaarstaan. Het script hangt
 * alleen luisteraars op; er draait niets zolang de bezoeker niets doet. Of iets al geteld is in dit bezoek,
 * staat in sessionStorage (verdwijnt bij het sluiten van het tabblad).
 */
(function () {
	'use strict';

	var url = window.lgbInzichtUrl;
	if (!url || !navigator.sendBeacon) { return; }

	var rij = [];
	var pad = window.location.pathname;
	var formulieren = [];
	var mobiel = window.matchMedia && window.matchMedia('(max-width: 899px), (pointer: coarse)').matches;
	var apparaat = mobiel ? 'mobiel' : 'desktop';

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

	/* Eén keer per bezoek (tabblad), ook over meerdere pagina's van hetzelfde formulier heen. */
	function eenKeer(sleutel) {
		try {
			if (window.sessionStorage.getItem('lgb_' + sleutel)) { return false; }
			window.sessionStorage.setItem('lgb_' + sleutel, String(Date.now()));
		} catch (e) {}
		return true;
	}
	function bewaard(sleutel) {
		try { return parseInt(window.sessionStorage.getItem('lgb_' + sleutel), 10) || 0; } catch (e) { return 0; }
	}
	function vergeet(sleutel) {
		try { window.sessionStorage.removeItem('lgb_' + sleutel); } catch (e) {}
	}

	function duurKlasse(ms) {
		var s = ms / 1000;
		if (s < 30) { return '1 minder dan 30 sec'; }
		if (s < 60) { return '2 30 sec tot 1 min'; }
		if (s < 120) { return '3 1 tot 2 min'; }
		if (s < 300) { return '4 2 tot 5 min'; }
		if (s < 600) { return '5 5 tot 10 min'; }
		return '6 meer dan 10 min';
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

	/* Huidige of volgende pagina van een formulier met meerdere stappen (Gravity Forms). */
	function paginaVan(form, soort) {
		var veld = form.querySelector('input[name^="gform_' + soort + '_page_number_"]');
		if (!veld) { return soort === 'source' ? 1 : 0; }
		return parseInt(veld.value, 10) || 0;
	}

	function labelVan(veld) {
		var houder = veld.closest('.gfield, fieldset, .stoelen-veld, label');
		var label = houder && houder.querySelector('.gfield_label, legend, .stoelen-label, .gform-field-label');
		var tekst = label ? label.textContent : (veld.getAttribute('aria-label') || veld.getAttribute('placeholder') || veld.name || '');
		return tekst.replace(/\(Vereist\)/g, '').replace(/^\s*\d+\s*/, '').replace(/[|]/g, '/').replace(/\s+/g, ' ').trim().slice(0, 70);
	}

	function heeftWaarde(veld) {
		if (veld.type === 'checkbox' || veld.type === 'radio') { return veld.checked; }
		return String(veld.value || '').trim() !== '';
	}

	function volg(form) {
		var naam = naamVan(form);
		if (!naam || form.getAttribute('data-inzicht')) { return; }
		form.setAttribute('data-inzicht', '1');
		/* Het contactformulier vraag voor vraag meet zijn eigen stappen; het verborgen formulier niet dubbel tellen. */
		if (form.closest('[data-stappen-formulier]')) { return; }
		var staat = { form: form, naam: naam, stap: Math.max(1, paginaVan(form, 'source')), gestart: false, laatste: '', weg: false, aangeraakt: {}, ingevuld: {} };
		formulieren.push(staat);

		if ('IntersectionObserver' in window) {
			var kijker = new IntersectionObserver(function (items) {
				if (items.some(function (i) { return i.isIntersecting; })) {
					if (eenKeer('gezien_' + naam)) {
						tel('formulier', naam, 'gezien');
						tel('formulier', naam, 'apparaat:gezien:' + apparaat);
					}
					kijker.disconnect();
				}
			}, { threshold: 0.3 });
			kijker.observe(form);
		}

		form.addEventListener('focusin', function (e) {
			var veld = e.target;
			if (!veld.matches('input, select, textarea') || veld.type === 'hidden') { return; }
			if (!staat.gestart && eenKeer('gestart_' + naam)) {
				tel('formulier', naam, 'gestart');
				tel('formulier', naam, 'apparaat:gestart:' + apparaat);
			}
			staat.gestart = true;
			var label = labelVan(veld);
			staat.laatste = label;
			if (label && !staat.aangeraakt[label]) {
				staat.aangeraakt[label] = true;
				tel('veld', naam, staat.stap + '|' + label + '|aangeraakt');
			}
		});

		/* Ingevuld: bij verlaten of wijzigen met een waarde, één keer per veld per pagina. */
		function check(e) {
			var veld = e.target;
			if (!veld.matches || !veld.matches('input, select, textarea') || veld.type === 'hidden' || !heeftWaarde(veld)) { return; }
			var label = labelVan(veld);
			if (label && !staat.ingevuld[label]) {
				staat.ingevuld[label] = true;
				tel('veld', naam, staat.stap + '|' + label + '|ingevuld');
			}
		}
		form.addEventListener('change', check);
		form.addEventListener('focusout', check);

		/* Gravity Forms verstuurt Verder, Vorige en Verstuur vaak via script, zonder submit-gebeurtenis.
		   Daarom ook de klik op die knoppen; de onclick van de knop heeft het doel dan al gezet. */
		form.addEventListener('click', function (e) {
			if (e.target.closest && e.target.closest('.gform_next_button, .gform_previous_button, .gform_button, button[type="submit"], input[type="submit"]')) {
				setTimeout(verzend, 0);
			}
		});
		form.addEventListener('submit', verzend);
		function verzend() {
			if (staat.weg) { return; }
			staat.weg = true;
			var doel = paginaVan(form, 'target');
			var bron = staat.stap;
			/* Gravity Forms: doel 0 is versturen, een lager doel is terug. Andere formulieren versturen meteen. */
			if (!form.querySelector('input[name^="gform_target_page_number_"]') || doel === 0) {
				tel('formulier', naam, 'verzonden');
				var start = bewaard('gestart_' + naam);
				if (start) { tel('formulier', naam, 'duur:' + duurKlasse(Date.now() - start)); }
			} else if (doel > bron) {
				tel('formulier', naam, 'verder:' + bron);
			} else {
				tel('formulier', naam, 'terug:' + bron);
			}
			stuur();
			/* Blijft de pagina staan (fout in een veld bij AJAX), dan telt een vertrek later weer als afhaken. */
			setTimeout(function () { if (document.body.contains(form)) { staat.weg = false; } }, 20000);
		}
	}

	function zoekFormulieren() {
		document.querySelectorAll('form[id^="gform_"], form.stoelen-formulier, form.offertezin').forEach(volg);
	}

	/* ---------- Contact vraag voor vraag ---------- */

	var contact = { gestart: false, laatste: '', klaar: false, stappen: 0, begin: 0 };
	window.lgbInzichtStap = function (id) {
		tel('stap', 'contact', id);
		/* De eerste vraag staat er bij het laden al; pas bij de tweede is iemand echt begonnen. */
		contact.stappen++;
		if (contact.stappen === 2) {
			contact.begin = Date.now();
			tel('stap', 'contact', 'apparaat:gestart:' + apparaat);
		}
		contact.gestart = contact.stappen > 1;
		contact.laatste = id;
		if (id === 'bedankt' || id === 'faq-bedankt') {
			contact.klaar = true;
			if (contact.begin) { tel('stap', 'contact', 'duur:' + duurKlasse(Date.now() - contact.begin)); }
			if (id === 'bedankt') { tel('stap', 'contact', 'apparaat:verstuurd:' + apparaat); }
		}
		if (id === 'bezig') { contact.klaar = true; }
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
				tel('veld', s.naam, s.stap + '|' + (s.laatste || 'onbekend') + '|afgehaakt');
				tel('formulier', s.naam, 'afhaakstap:' + s.stap);
				tel('formulier', s.naam, 'apparaat:afgehaakt:' + apparaat);
			}
		});
		if (contact.gestart && !contact.klaar && !contact.gemeld) {
			contact.gemeld = true;
			tel('stap', 'contact', 'afgehaakt:' + contact.laatste);
			tel('stap', 'contact', 'apparaat:afgehaakt:' + apparaat);
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
