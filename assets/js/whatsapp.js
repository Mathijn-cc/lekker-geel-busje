/**
 * WhatsApp-chat: knop, ballon en uitklapvenster.
 *
 * De bezoeker typt zijn vraag in het venster. Verstuurt hij, dan opent WhatsApp met de vraag
 * al ingevuld (https://wa.me/<nummer>?text=...). Er gaat niets via de server of naar derden.
 *
 * Zonder JavaScript is de knop een gewone link naar WhatsApp.
 * Voor metingen: een event in de dataLayer bij openen ('whatsapp_venster') en versturen ('whatsapp_chat').
 */
(function () {
	'use strict';

	var chat = document.querySelector('[data-whatsapp-chat]');
	if (!chat) { return; }

	var knop = chat.querySelector('[data-whatsapp-knop]');
	var venster = chat.querySelector('[data-whatsapp-venster]');
	var log = chat.querySelector('[data-whatsapp-log]');
	var formulier = chat.querySelector('[data-whatsapp-formulier]');
	var vraag = chat.querySelector('[data-whatsapp-vraag]');
	var verstuur = chat.querySelector('[data-whatsapp-verstuur]');
	var keuzes = chat.querySelector('[data-whatsapp-keuzes]');
	var ballon = chat.querySelector('[data-whatsapp-ballon]');
	var nummer = chat.dataset.nummer;
	var pagina = chat.dataset.pagina || '';
	var ballonNa = parseInt(chat.dataset.ballonNa, 10) || 0;
	var sleutelBallon = 'whatsapp-ballon-weg';
	var ballonTimer = null;
	var open = false;

	function meet(gebeurtenis, extra) {
		window.dataLayer = window.dataLayer || [];
		var data = { event: gebeurtenis, whatsapp_pagina: pagina || window.location.pathname };
		Object.keys(extra || {}).forEach(function (k) { data[k] = extra[k]; });
		window.dataLayer.push(data);
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
		vraag.style.height = Math.min(vraag.scrollHeight, 140) + 'px';
		verstuur.disabled = vraag.value.trim() === '';
	}

	function naarOnder() {
		log.scrollTop = log.scrollHeight;
	}

	/* ---------- Ballon ---------- */

	function toonBallon() {
		if (!ballon || open || bewaard(sleutelBallon)) { return; }
		ballon.hidden = false;
		requestAnimationFrame(function () { ballon.classList.add('is-zichtbaar'); });
	}

	function verbergBallon(voorgoed) {
		clearTimeout(ballonTimer);
		if (voorgoed) { bewaar(sleutelBallon); }
		if (!ballon || ballon.hidden) { return; }
		ballon.classList.remove('is-zichtbaar');
		setTimeout(function () { if (!ballon.classList.contains('is-zichtbaar')) { ballon.hidden = true; } }, 300);
	}

	if (ballon && !bewaard(sleutelBallon)) {
		ballonTimer = setTimeout(toonBallon, ballonNa * 1000);
		ballon.querySelector('[data-whatsapp-ballon-open]').addEventListener('click', function () { openVenster(); });
		ballon.querySelector('[data-whatsapp-ballon-weg]').addEventListener('click', function () { verbergBallon(true); });
	}

	/* ---------- Venster ---------- */

	function openVenster() {
		if (open) { return; }
		open = true;
		verbergBallon(true);
		venster.hidden = false;
		chat.classList.add('is-open');
		knop.setAttribute('aria-expanded', 'true');
		knop.setAttribute('aria-label', 'Chatvenster sluiten');
		requestAnimationFrame(function () {
			venster.classList.add('is-zichtbaar');
			naarOnder();
			/* Op aanraakschermen niet meteen het toetsenbord openen: dat duwt het venster weg. */
			if (!window.matchMedia('(hover: none)').matches) { vraag.focus(); }
		});
		meet('whatsapp_venster');
	}

	function sluitVenster(focusTerug) {
		if (!open) { return; }
		open = false;
		chat.classList.remove('is-open');
		venster.classList.remove('is-zichtbaar');
		knop.setAttribute('aria-expanded', 'false');
		knop.setAttribute('aria-label', 'Chat met ons via WhatsApp');
		setTimeout(function () { if (!open) { venster.hidden = true; } }, 300);
		if (focusTerug) { knop.focus(); }
	}

	knop.addEventListener('click', function (e) {
		e.preventDefault();
		if (open) { sluitVenster(false); } else { openVenster(); }
	});

	chat.querySelector('[data-whatsapp-sluiten]').addEventListener('click', function () { sluitVenster(true); });

	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape' && open) { sluitVenster(true); }
	});

	/* Klik buiten het venster sluit het, behalve als er al iets getypt is. */
	document.addEventListener('click', function (e) {
		if (open && !chat.contains(e.target) && vraag.value.trim() === '') { sluitVenster(false); }
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
		if (e.key === 'Enter' && !e.shiftKey && !e.isComposing && !window.matchMedia('(hover: none)').matches) {
			e.preventDefault();
			if (typeof formulier.requestSubmit === 'function') { formulier.requestSubmit(); } else { formulier.dispatchEvent(new Event('submit', { cancelable: true })); }
		}
	});

	function bericht(tekst, van) {
		var p = document.createElement('p');
		p.className = 'whatsapp-chat-bericht whatsapp-chat-bericht-' + van;
		p.textContent = tekst;
		log.appendChild(p);
		naarOnder();
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

		bericht(tekst, 'gast');
		var antwoord = bericht('', 'bus');
		antwoord.append('Top! WhatsApp gaat open met je vraag erin. Druk daar nog even op verzenden. ');
		var link = document.createElement('a');
		link.href = url;
		link.target = '_blank';
		link.rel = 'noopener';
		link.textContent = 'Opent er niks? Klik hier.';
		antwoord.appendChild(link);
		if (keuzes) { keuzes.hidden = true; }

		vraag.value = '';
		pasVeldAan();
		meet('whatsapp_chat');
	});

	pasVeldAan();
}());
