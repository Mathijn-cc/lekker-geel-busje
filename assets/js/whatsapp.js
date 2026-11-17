/**
 * WhatsApp-chat als kort, geleid gesprek.
 *
 * 1. Welkom en drie keuzes: prijs opvragen, vraag over je rit, andere vraag.
 *    Prijs opvragen is een link naar het offerteformulier: aanvragen lopen niet via WhatsApp.
 *    Wie meteen iets typt, slaat de keuze over.
 * 2. Per keuze een of twee vragen, te beantwoorden met een datum of een kort antwoord.
 * 3. Eén groene knop: WhatsApp opent met alle antwoorden netjes in één bericht.
 *
 * Er gaat niets via de server of naar derden. Zonder JavaScript is de knop een gewone link.
 * Metingen in de dataLayer: whatsapp_venster (open), whatsapp_keuze (pad), whatsapp_offerte (naar de
 * offerte), whatsapp_chat (versturen).
 */
(function () {
	'use strict';

	var houder = document.querySelector('[data-zweefknoppen]');
	var knop = houder && houder.querySelector('[data-whatsapp-knop]');
	var venster = houder && houder.querySelector('[data-whatsapp-venster]');
	if (!knop || !venster) { return; }

	var g;
	try { g = JSON.parse(venster.dataset.gesprek); } catch (e) { return; }

	var log = venster.querySelector('[data-whatsapp-log]');
	var formulier = venster.querySelector('[data-whatsapp-formulier]');
	var invoer = venster.querySelector('[data-whatsapp-invoer]');
	var tekst = venster.querySelector('[data-whatsapp-tekst]');
	var datum = venster.querySelector('[data-whatsapp-datum]');
	var verstuur = venster.querySelector('[data-whatsapp-verstuur]');
	var naarApp = venster.querySelector('[data-whatsapp-naar-app]');
	var opnieuw = venster.querySelector('[data-whatsapp-opnieuw]');
	var melding = houder.querySelector('[data-whatsapp-ballon]');

	var aanraak = window.matchMedia('(hover: none)').matches;
	var rustig = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	var sleutelMelding = 'whatsapp-melding-gezien';

	var open = false;
	var pad = null;
	var stap = -1;
	var antwoorden = [];
	var modus = 'tekst';
	var beurt = 0; /* Verhoogt bij opnieuw beginnen, zodat oude vertraagde berichten wegvallen. */

	/* ---------- Hulpjes ---------- */

	function meet(gebeurtenis, extra) {
		window.dataLayer = window.dataLayer || [];
		var data = { event: gebeurtenis, whatsapp_pagina: g.pagina || window.location.pathname };
		if (extra) { Object.keys(extra).forEach(function (k) { data[k] = extra[k]; }); }
		window.dataLayer.push(data);
	}

	function bewaar(sleutel) {
		try { window.sessionStorage.setItem(sleutel, '1'); } catch (e) {}
	}

	function bewaard(sleutel) {
		try { return window.sessionStorage.getItem(sleutel) === '1'; } catch (e) { return false; }
	}

	function naarOnder() {
		log.scrollTop = log.scrollHeight;
	}

	function wacht(ms) {
		return new Promise(function (klaar) { setTimeout(klaar, rustig ? 0 : ms); });
	}

	function element(tag, klasse, inhoud) {
		var el = document.createElement(tag);
		el.className = klasse;
		if (inhoud) { el.textContent = inhoud; }
		return el;
	}

	/* ---------- Berichten ---------- */

	function gastZegt(waarde) {
		log.appendChild(element('p', 'whatsapp-bericht whatsapp-bericht-gast', waarde));
		naarOnder();
	}

	/* De bus "typt" heel even, dan verschijnt het bericht. Meerdere berichten na elkaar. */
	function busZegt(regels) {
		var mijnBeurt = beurt;
		var keten = Promise.resolve();
		regels.filter(Boolean).forEach(function (regel, i) {
			keten = keten.then(function () {
				if (mijnBeurt !== beurt) { return; }
				var typen = element('p', 'whatsapp-bericht whatsapp-bericht-bus is-typen');
				typen.innerHTML = '<span></span><span></span><span></span>';
				typen.setAttribute('aria-hidden', 'true');
				log.appendChild(typen);
				naarOnder();
				return wacht(i === 0 ? 450 : 650).then(function () {
					typen.remove();
					if (mijnBeurt !== beurt) { return; }
					log.appendChild(element('p', 'whatsapp-bericht whatsapp-bericht-bus', regel));
					naarOnder();
				});
			});
		});
		return keten.then(function () { return mijnBeurt === beurt; });
	}

	/* Knopjes onder de vraag. Een keuze met een link (de prijsaanvraag) gaat direct naar die pagina. */
	function toonKeuzes(lijst, kies) {
		if (!lijst || !lijst.length) { return; }
		var rij = element('div', 'whatsapp-keuzes');
		lijst.forEach(function (item) {
			var label = typeof item === 'string' ? item : item.label;
			var keuze;
			if (item.link) {
				keuze = element('a', 'whatsapp-keuze whatsapp-keuze-link', label);
				keuze.href = item.link;
				if (item.pijl) { keuze.insertAdjacentHTML('beforeend', item.pijl); }
				keuze.addEventListener('click', function () {
					meet('whatsapp_offerte');
					sluitVenster(false);
				});
			} else {
				keuze = element('button', 'whatsapp-keuze', label);
				keuze.type = 'button';
				keuze.addEventListener('click', function () {
					rij.remove();
					kies(label);
				});
			}
			rij.appendChild(keuze);
		});
		log.appendChild(rij);
		naarOnder();
	}

	function weg(selector) {
		log.querySelectorAll(selector).forEach(function (el) { el.remove(); });
	}

	/* ---------- Invoer ---------- */

	function zetInvoer(soort, placeholder) {
		modus = soort;
		invoer.hidden = false;
		naarApp.hidden = true;
		tekst.hidden = soort === 'datum';
		datum.hidden = soort !== 'datum';
		tekst.value = '';
		datum.value = '';
		tekst.placeholder = placeholder || 'Typ je antwoord...';
		if (soort === 'datum') {
			datum.min = new Date().toISOString().slice(0, 10);
		}
		pasAan();
		if (!aanraak && open) {
			(soort === 'datum' ? datum : tekst).focus();
		}
	}

	function pasAan() {
		tekst.style.height = 'auto';
		tekst.style.height = Math.min(tekst.scrollHeight, 120) + 'px';
		verstuur.disabled = modus === 'datum' ? !datum.value : tekst.value.trim() === '';
	}

	function mooieDatum(waarde) {
		var d = new Date(waarde + 'T12:00:00');
		if (isNaN(d)) { return waarde; }
		return d.toLocaleDateString('nl-NL', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
	}

	/* ---------- Het gesprek ---------- */

	function begin() {
		beurt++;
		pad = null;
		stap = -1;
		antwoorden = [];
		log.innerHTML = '';
		opnieuw.hidden = true;
		zetInvoer('tekst', g.veld);
		var mijnBeurt = beurt;
		busZegt(g.welkom).then(function (actueel) {
			if (!actueel || mijnBeurt !== beurt || pad) { return; }
			toonKeuzes(g.paden, function (label) {
				gastZegt(label);
				kiesPad(g.paden.filter(function (p) { return p.label === label; })[0]);
			});
		});
	}

	function kiesPad(gekozen) {
		pad = gekozen;
		stap = 0;
		opnieuw.hidden = false;
		weg('.whatsapp-keuzes');
		meet('whatsapp_keuze', { whatsapp_keuze: pad.id });
		vraag();
	}

	function vraag() {
		var st = pad.stappen[stap];
		invoer.hidden = true;
		busZegt([st.vraag]).then(function (actueel) {
			if (!actueel) { return; }
			zetInvoer(st.soort === 'datum' ? 'datum' : 'tekst', st.veld);
			toonKeuzes(st.keuzes, function (label) {
				gastZegt(label);
				beantwoord(label);
			});
		});
	}

	function beantwoord(waarde) {
		weg('.whatsapp-keuzes');
		antwoorden.push({ naam: pad.stappen[stap].naam, waarde: waarde });
		stap++;
		if (stap < pad.stappen.length) {
			vraag();
		} else {
			klaar();
		}
	}

	function bericht() {
		var regels = [];
		if (pad.intro) {
			regels.push(pad.intro, '');
			antwoorden.forEach(function (a) { regels.push(a.naam ? a.naam + ': ' + a.waarde : a.waarde); });
		} else {
			regels.push(antwoorden.map(function (a) { return a.waarde; }).join('\n'));
		}
		return regels.join('\n');
	}

	function klaar() {
		invoer.hidden = true;
		busZegt([g.klaar]).then(function (actueel) {
			if (!actueel) { return; }
			naarApp.href = 'https://wa.me/' + g.nummer + '?text=' + encodeURIComponent(bericht());
			naarApp.hidden = false;
			naarOnder();
		});
	}

	naarApp.addEventListener('click', function () {
		meet('whatsapp_chat', { whatsapp_keuze: pad ? pad.id : '' });
		setTimeout(function () {
			busZegt(['WhatsApp staat open met je bericht. Druk daar nog even op verzenden, dan zien we het meteen.']);
		}, 400);
	});

	formulier.addEventListener('submit', function (e) {
		e.preventDefault();
		if (verstuur.disabled) { return; }
		var waarde = modus === 'datum' ? mooieDatum(datum.value) : tekst.value.trim();
		gastZegt(waarde);
		invoer.hidden = true;
		if (!pad) {
			/* Meteen getypt zonder keuze: dat is gewoon een vraag. */
			beurt++;
			weg('.whatsapp-keuzes');
			weg('.is-typen');
			pad = g.paden.filter(function (p) { return p.id === 'anders'; })[0];
			opnieuw.hidden = false;
			meet('whatsapp_keuze', { whatsapp_keuze: 'direct' });
			antwoorden = [{ naam: '', waarde: waarde }];
			stap = pad.stappen.length;
			klaar();
			return;
		}
		beantwoord(waarde);
	});

	tekst.addEventListener('input', pasAan);
	datum.addEventListener('input', pasAan);
	datum.addEventListener('change', pasAan);

	/* Enter verstuurt, Shift+Enter is een nieuwe regel. Op aanraakschermen is Enter altijd een nieuwe regel. */
	tekst.addEventListener('keydown', function (e) {
		if (e.key === 'Enter' && !e.shiftKey && !e.isComposing && !aanraak) {
			e.preventDefault();
			if (typeof formulier.requestSubmit === 'function') { formulier.requestSubmit(); } else { formulier.dispatchEvent(new Event('submit', { cancelable: true })); }
		}
	});

	opnieuw.addEventListener('click', begin);

	/* ---------- Openen en sluiten ---------- */

	function openVenster() {
		if (open) { return; }
		open = true;
		verbergMelding();
		bewaar(sleutelMelding);
		venster.hidden = false;
		houder.classList.add('is-open');
		knop.setAttribute('aria-expanded', 'true');
		requestAnimationFrame(function () {
			requestAnimationFrame(function () { venster.classList.add('is-zichtbaar'); });
		});
		if (!log.children.length) {
			begin();
		} else if (!aanraak) {
			setTimeout(function () { (modus === 'datum' ? datum : tekst).focus(); }, 300);
		}
		meet('whatsapp_venster');
	}

	function sluitVenster(focusTerug) {
		if (!open) { return; }
		open = false;
		venster.classList.remove('is-zichtbaar');
		houder.classList.remove('is-open');
		knop.setAttribute('aria-expanded', 'false');
		setTimeout(function () { if (!open) { venster.hidden = true; } }, 600);
		if (focusTerug) { knop.focus(); }
	}

	knop.addEventListener('click', function (e) {
		e.preventDefault();
		openVenster();
	});

	/* Elke link met data-whatsapp-open (bijv. op de contactpagina) opent dit venster in plaats van wa.me.
	   In de capture-fase, zodat de klik-buiten-het-venster hieronder het niet meteen weer sluit. */
	document.addEventListener('click', function (e) {
		var opener = e.target.closest('[data-whatsapp-open]');
		if (!opener) { return; }
		e.preventDefault();
		e.stopPropagation();
		openVenster();
	}, true);

	venster.querySelector('[data-whatsapp-sluiten]').addEventListener('click', function () { sluitVenster(true); });

	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape' && open) { sluitVenster(true); }
	});

	/* Klik buiten het venster sluit het, maar alleen als er nog niets ingevuld is. */
	document.addEventListener('click', function (e) {
		if (open && !houder.contains(e.target) && !pad && tekst.value.trim() === '') { sluitVenster(false); }
	});

	/* ---------- Berichtje ---------- */

	var meldingTimer = null;
	var meldingWeg = null;

	function opScroll() {
		var hoogte = document.documentElement.scrollHeight - window.innerHeight;
		if (hoogte > 0 && window.scrollY / hoogte > 0.5) { toonMelding(); }
	}

	function toonMelding() {
		if (!melding || open || bewaard(sleutelMelding)) { return; }
		clearTimeout(meldingTimer);
		window.removeEventListener('scroll', opScroll);
		bewaar(sleutelMelding);
		melding.hidden = false;
		requestAnimationFrame(function () { melding.classList.add('is-zichtbaar'); });
		meldingWeg = setTimeout(verbergMelding, 5000);
	}

	function verbergMelding() {
		clearTimeout(meldingTimer);
		clearTimeout(meldingWeg);
		window.removeEventListener('scroll', opScroll);
		if (!melding || melding.hidden) { return; }
		melding.classList.remove('is-zichtbaar');
		setTimeout(function () { if (!melding.classList.contains('is-zichtbaar')) { melding.hidden = true; } }, 350);
	}

	if (melding && !bewaard(sleutelMelding)) {
		meldingTimer = setTimeout(toonMelding, Math.max(parseInt(venster.dataset.ballonNa, 10) || 0, 3) * 1000);
		window.addEventListener('scroll', opScroll, { passive: true });
		melding.querySelector('[data-whatsapp-ballon-open]').addEventListener('click', openVenster);
		melding.querySelector('[data-whatsapp-ballon-weg]').addEventListener('click', verbergMelding);
	}
}());
