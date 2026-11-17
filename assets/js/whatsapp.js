/**
 * WhatsApp-chat: knop, berichtje en uitklapvenster.
 *
 * De bezoeker typt zijn vraag in het venster. Verstuurt hij, dan opent WhatsApp met de vraag
 * al ingevuld (https://wa.me/<nummer>?text=...). Er gaat niets via de server of naar derden.
 *
 * Zonder JavaScript is de knop een gewone link naar WhatsApp.
 * Voor metingen: een event in de dataLayer bij openen ('whatsapp_venster'), versturen
 * ('whatsapp_chat') en klikken op het offerteknopje ('whatsapp_offerte').
 */
(function () {
	'use strict';

	var houder = document.querySelector('[data-zweefknoppen]');
	var chat = document.querySelector('[data-whatsapp-chat]');
	var knop = document.querySelector('[data-whatsapp-knop]');
	if (!houder || !chat || !knop) { return; }

	var venster = chat.querySelector('[data-whatsapp-venster]');
	var log = chat.querySelector('[data-whatsapp-log]');
	var formulier = chat.querySelector('[data-whatsapp-formulier]');
	var vraag = chat.querySelector('[data-whatsapp-vraag]');
	var verstuur = chat.querySelector('[data-whatsapp-verstuur]');
	var keuzes = chat.querySelector('[data-whatsapp-keuzes]');
	var melding = chat.querySelector('[data-whatsapp-ballon]');
	var nummer = chat.dataset.nummer;
	var pagina = chat.dataset.pagina || '';
	var meldingNa = parseInt(chat.dataset.ballonNa, 10) || 0;
	var aanraak = window.matchMedia('(hover: none)').matches;
	var sleutelMelding = 'whatsapp-melding-gezien';
	var open = false;

	function meet(gebeurtenis) {
		window.dataLayer = window.dataLayer || [];
		window.dataLayer.push({ event: gebeurtenis, whatsapp_pagina: pagina || window.location.pathname });
	}

	function bewaar(sleutel) {
		try { window.sessionStorage.setItem(sleutel, '1'); } catch (e) {}
	}

	function bewaard(sleutel) {
		try { return window.sessionStorage.getItem(sleutel) === '1'; } catch (e) { return false; }
	}

	/* Tekstveld groeit mee tot een maximum, en de knop is pas actief als er iets staat. */
	function pasVeldAan() {
		vraag.style.height = 'auto';
		vraag.style.height = Math.min(vraag.scrollHeight, 132) + 'px';
		verstuur.disabled = vraag.value.trim() === '';
	}

	function naarOnder() {
		log.scrollTop = log.scrollHeight;
	}

	/* ---------- Berichtje ---------- */

	var meldingTimer = null;
	var meldingWeg = null;

	function toonMelding() {
		if (!melding || open || bewaard(sleutelMelding)) { return; }
		clearTimeout(meldingTimer);
		window.removeEventListener('scroll', opScroll);
		bewaar(sleutelMelding);
		melding.hidden = false;
		requestAnimationFrame(function () { melding.classList.add('is-zichtbaar'); });
		/* Niet blijven hangen: na een tijdje vanzelf weg. */
		meldingWeg = setTimeout(verbergMelding, 15000);
	}

	function verbergMelding() {
		clearTimeout(meldingTimer);
		clearTimeout(meldingWeg);
		window.removeEventListener('scroll', opScroll);
		if (!melding || melding.hidden) { return; }
		melding.classList.remove('is-zichtbaar');
		setTimeout(function () { if (!melding.classList.contains('is-zichtbaar')) { melding.hidden = true; } }, 350);
	}

	/* Halverwege de pagina is een goed moment: de bezoeker is geïnteresseerd. */
	function opScroll() {
		var hoogte = document.documentElement.scrollHeight - window.innerHeight;
		if (hoogte > 0 && window.scrollY / hoogte > 0.5) { toonMelding(); }
	}

	if (melding && !bewaard(sleutelMelding)) {
		meldingTimer = setTimeout(toonMelding, Math.max(meldingNa, 3) * 1000);
		window.addEventListener('scroll', opScroll, { passive: true });
		melding.querySelector('[data-whatsapp-ballon-open]').addEventListener('click', function () { openVenster(); });
		melding.querySelector('[data-whatsapp-ballon-weg]').addEventListener('click', verbergMelding);
	}

	/* ---------- Venster ---------- */

	function openVenster() {
		if (open) { return; }
		open = true;
		verbergMelding();
		bewaar(sleutelMelding);
		venster.hidden = false;
		houder.classList.add('is-open');
		knop.setAttribute('aria-expanded', 'true');
		knop.setAttribute('aria-label', 'Chatvenster sluiten');
		requestAnimationFrame(function () {
			venster.classList.add('is-zichtbaar');
			naarOnder();
			/* Op aanraakschermen niet meteen het toetsenbord openen: dat duwt het venster weg. */
			if (!aanraak) { vraag.focus(); }
		});
		meet('whatsapp_venster');
	}

	function sluitVenster(focusTerug) {
		if (!open) { return; }
		open = false;
		houder.classList.remove('is-open');
		venster.classList.remove('is-zichtbaar');
		knop.setAttribute('aria-expanded', 'false');
		knop.setAttribute('aria-label', 'Chat met ons via WhatsApp');
		setTimeout(function () { if (!open) { venster.hidden = true; } }, 350);
		if (focusTerug) { knop.focus(); }
	}

	knop.addEventListener('click', function (e) {
		e.preventDefault();
		if (open) { sluitVenster(false); } else { openVenster(); }
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

	chat.querySelector('[data-whatsapp-sluiten]').addEventListener('click', function () { sluitVenster(true); });

	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape' && open) { sluitVenster(true); }
	});

	/* Klik buiten het venster sluit het, behalve als er al iets getypt is. */
	document.addEventListener('click', function (e) {
		if (open && !houder.contains(e.target) && vraag.value.trim() === '') { sluitVenster(false); }
	});

	/* ---------- Snelle vragen ---------- */

	if (keuzes) {
		keuzes.addEventListener('click', function (e) {
			var keuze = e.target.closest('[data-whatsapp-keuze]');
			if (!keuze) { return; }
			vraag.value = keuze.textContent.trim();
			pasVeldAan();
			vraag.focus();
			/* Cursor aan het eind, zodat de bezoeker meteen kan aanvullen. */
			vraag.setSelectionRange(vraag.value.length, vraag.value.length);
		});
	}

	var offerte = chat.querySelector('[data-whatsapp-offerte]');
	if (offerte) {
		offerte.addEventListener('click', function () { sluitVenster(false); meet('whatsapp_offerte'); });
	}

	/* ---------- Versturen ---------- */

	vraag.addEventListener('input', pasVeldAan);

	/* Enter verstuurt, Shift+Enter is een nieuwe regel. Op aanraakschermen is Enter altijd een nieuwe regel. */
	vraag.addEventListener('keydown', function (e) {
		if (e.key === 'Enter' && !e.shiftKey && !e.isComposing && !aanraak) {
			e.preventDefault();
			if (typeof formulier.requestSubmit === 'function') { formulier.requestSubmit(); } else { formulier.dispatchEvent(new Event('submit', { cancelable: true })); }
		}
	});

	function bericht(van) {
		var p = document.createElement('p');
		p.className = 'whatsapp-bericht whatsapp-bericht-' + van;
		log.appendChild(p);
		return p;
	}

	formulier.addEventListener('submit', function (e) {
		e.preventDefault();
		var tekst = vraag.value.trim();
		if (!tekst) { return; }

		var volledig = tekst + (pagina ? '\n\n(Pagina: ' + pagina + ')' : '');
		var url = 'https://wa.me/' + nummer + '?text=' + encodeURIComponent(volledig);

		/* Openen in de klik zelf, anders houdt een pop-upblokker het tegen. */
		window.open(url, '_blank', 'noopener');

		if (keuzes) { keuzes.hidden = true; }
		bericht('gast').textContent = tekst;
		var antwoord = bericht('bus');
		antwoord.append('Top! WhatsApp gaat open met je vraag erin. Druk daar nog even op verzenden. ');
		var link = document.createElement('a');
		link.href = url;
		link.target = '_blank';
		link.rel = 'noopener';
		link.textContent = 'Opent er niks? Klik hier.';
		antwoord.appendChild(link);
		naarOnder();

		vraag.value = '';
		pasVeldAan();
		meet('whatsapp_chat');
	});

	pasVeldAan();
}());
