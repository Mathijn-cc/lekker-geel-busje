/**
 * Contactformulier vraag voor vraag (zoals Typeform).
 *
 * Leest de velden van het Gravity Forms formulier in het contactblok en stelt ze een voor een,
 * groot in beeld, met een voortgangsbalk. Enter gaat door, Shift + Enter is een nieuwe regel.
 * Bij een keuze kun je ook de letter (A, B, C) indrukken. Aan het eind vult het script het echte
 * formulier en verstuurt het; meldingen, inzendingen en bevestiging blijven van Gravity Forms.
 *
 * Slim, zonder robotgevoel:
 * - Eerst de keuze: een prijs (naar het offerteformulier), een vraag, of liever bellen of appen.
 * - Tijdens het typen van de vraag staat het antwoord eronder als het een veelgestelde vraag is
 *   (data-antwoorden), of een verwijzing naar de offerte als het op een aanvraag lijkt.
 * - Daarna naam, e-mailadres en telefoonnummer. Een e-mailadres of telefoonnummer uit de vraag
 *   staat al ingevuld.
 * - Terug en Opnieuw beginnen kan altijd.
 *
 * Zonder JavaScript, of bij een veldtype dat dit script niet kent, blijft het gewone formulier staan.
 * Bij een fout komt het gewone formulier tevoorschijn, met de antwoorden al ingevuld.
 */
(function () {
	'use strict';

	var rustig = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	var aanraak = window.matchMedia && window.matchMedia('(hover: none)').matches;
	var aanvraagPatroon = /(prijs|prijzen|kost|kosten|offerte|tarief|tarieven|wat betaal|beschikba|nog vrij|reserveer|reserveren|boeken|boeking|\d+\s*(personen|pers\b|man\b|mensen|gasten)|\b\d{1,2}\s*(jan|feb|maart|mrt|apr|mei|jun|jul|aug|sep|okt|nov|dec)|\b\d{1,2}[-\/.]\d{1,2}\b)/i;
	var emailPatroon = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
	var emailInTekst = /[^\s@<>()]+@[^\s@<>()]+\.[a-z]{2,}/i;
	var telefoonInTekst = /(?:\+31|0031|0)[\s-]?(?:\d[\s-]?){9}/;
	var LETTERS = 'ABCDEFGHIJ';

	var domeinFouten = {
		'gmial.com': 'gmail.com', 'gmai.com': 'gmail.com', 'gmal.com': 'gmail.com', 'gamil.com': 'gmail.com', 'gmail.nl': 'gmail.com', 'gmail.co': 'gmail.com', 'gmail.con': 'gmail.com',
		'hotmial.com': 'hotmail.com', 'hotmal.com': 'hotmail.com', 'hotmail.co': 'hotmail.com', 'hotmail.con': 'hotmail.com', 'hotmai.com': 'hotmail.com', 'hotmil.com': 'hotmail.com',
		'hotmial.nl': 'hotmail.nl', 'hotmal.nl': 'hotmail.nl', 'hotmai.nl': 'hotmail.nl',
		'outlok.com': 'outlook.com', 'outloo.com': 'outlook.com', 'outlook.co': 'outlook.com', 'outlook.con': 'outlook.com', 'outlok.nl': 'outlook.nl',
		'live.co': 'live.nl', 'icloud.co': 'icloud.com', 'iclod.com': 'icloud.com', 'kpnmial.nl': 'kpnmail.nl', 'ziggo.n': 'ziggo.nl'
	};

	/* Voorstel bij een bekende tikfout in het domein, anders leeg. */
	function emailVoorstel(waarde) {
		var delen = String(waarde).toLowerCase().split('@');
		if (delen.length !== 2 || !delen[0]) { return ''; }
		var goed = domeinFouten[delen[1]];
		return goed ? delen[0] + '@' + goed : '';
	}

	function telefoonGoed(waarde) {
		var cijfers = String(waarde).replace(/\D+/g, '');
		return cijfers.length >= 10 && cijfers.length <= 13;
	}

	function maak(tag, klasse, tekst) {
		var el = document.createElement(tag);
		if (klasse) { el.className = klasse; }
		if (tekst) { el.textContent = tekst; }
		return el;
	}

	function labelVan(veld) {
		var tekst = veld.querySelector('.gform-field-label__text');
		if (tekst) { return tekst.textContent.trim(); }
		var label = veld.querySelector('.gfield_label');
		if (!label) { return ''; }
		label = label.cloneNode(true);
		label.querySelectorAll('.gfield_required').forEach(function (r) { r.remove(); });
		return label.textContent.trim();
	}

	/* Velden uit het formulier lezen. Geeft null terug als er een veld is dat dit script niet kent. */
	function leesVelden(formulier) {
		var velden = { vraag: null, naam: null, email: null, telefoon: null, extra: [] };
		var onbekend = false;

		formulier.querySelectorAll('.gfield').forEach(function (el) {
			var lijst = el.classList;
			if (lijst.contains('gform_validation_container') || lijst.contains('gfield--type-honeypot') || lijst.contains('gfield--type-hidden') || lijst.contains('gfield_visibility_hidden') || lijst.contains('gfield--type-html') || lijst.contains('gfield--type-section')) {
				return;
			}
			var label = labelVan(el);
			var veld = { el: el, label: label, verplicht: lijst.contains('gfield_contains_required') };

			if (lijst.contains('gfield--type-name')) {
				veld.voor = el.querySelector('.name_first input');
				veld.achter = el.querySelector('.name_last input');
				veld.los = !veld.voor ? el.querySelector('input[type="text"]') : null;
				velden.naam = veld;
			} else if (lijst.contains('gfield--type-email')) {
				veld.invoer = Array.prototype.slice.call(el.querySelectorAll('input[type="email"], input[type="text"]'));
				velden.email = veld;
			} else if (lijst.contains('gfield--type-phone') || (lijst.contains('gfield--type-text') && /telefoon|mobiel|gsm/i.test(label))) {
				veld.invoer = [el.querySelector('input')];
				velden.telefoon = veld;
			} else if (lijst.contains('gfield--type-textarea') && !velden.vraag) {
				veld.invoer = [el.querySelector('textarea')];
				velden.vraag = veld;
			} else if (lijst.contains('gfield--type-text') || lijst.contains('gfield--type-textarea')) {
				veld.invoer = [el.querySelector('input, textarea')];
				var uitleg = el.querySelector('.gfield_description');
				veld.uitleg = uitleg ? uitleg.textContent.trim() : '';
				veld.lang = lijst.contains('gfield--type-textarea');
				velden.extra.push(veld);
			} else {
				onbekend = true;
			}
		});

		if (onbekend || !velden.vraag) { return null; }
		return velden;
	}

	function zetWaarde(invoer, waarde) {
		if (!invoer) { return; }
		invoer.value = waarde;
		invoer.dispatchEvent(new Event('input', { bubbles: true }));
		invoer.dispatchEvent(new Event('change', { bubbles: true }));
	}

	function Stappen(vak) {
		var zelf = this;
		this.vak = vak;
		this.el = vak.querySelector('[data-stappen]');
		this.formulierVak = vak.querySelector('[data-stappen-formulier]');
		this.formulier = this.formulierVak.querySelector('form');
		this.podium = this.el.querySelector('[data-stappen-podium]');
		this.fases = this.el.querySelector('[data-stappen-fases]');
		this.terugKnop = this.el.querySelector('[data-stappen-terug]');
		this.verderKnop = this.el.querySelector('[data-stappen-verder]');
		this.opnieuwKnop = this.el.querySelector('[data-stappen-opnieuw]');
		this.offerte = this.el.getAttribute('data-offerte-link');
		this.pijl = this.el.getAttribute('data-pijl') || '';
		this.vink = this.el.getAttribute('data-vink') || '';
		this.t = {};
		this.direct = [];
		this.antwoorden = [];
		try { this.t = JSON.parse(this.el.getAttribute('data-teksten') || '{}'); } catch (e) {}
		try { this.direct = JSON.parse(this.el.getAttribute('data-direct') || '[]'); } catch (e) {}
		try {
			this.antwoorden = JSON.parse(this.el.getAttribute('data-antwoorden') || '[]').map(function (a) {
				a.regex = new RegExp(a.patroon, 'i');
				return a;
			});
		} catch (e) {}
		this.v = this.formulier ? leesVelden(this.formulier) : null;
		this.inBeeld = false;
		this.leeg();

		this.terugKnop.addEventListener('click', function () { zelf.terug(); });
		this.verderKnop.addEventListener('click', function () { if (zelf.ok) { zelf.ok(); } });
		this.opnieuwKnop.addEventListener('click', function () { zelf.opnieuw(); });
	}

	Stappen.prototype.kan = function () {
		return !!(this.formulier && this.v && this.formulier.querySelector('[type="submit"], .gform_button'));
	};

	Stappen.prototype.leeg = function () {
		this.a = { vraag: '', voornaam: '', achternaam: '', vroegAchternaam: false, email: '', telefoon: '', uitVraag: {}, extra: [] };
		this.geschiedenis = [];
		this.huidig = null;
		this.ok = null;
	};

	/* Tekst met {naam}: met voornaam als die bekend is, anders netjes zonder. */
	Stappen.prototype.met = function (tekst) {
		tekst = tekst || '';
		if (this.a.voornaam) { return tekst.replace('{naam}', this.a.voornaam); }
		return tekst.replace(/,\s*\{naam\}/, '').replace(/\s*\{naam\}/, '');
	};

	/* ---------- Route ---------- */

	/* De vragen die nog komen, op basis van wat er al gekozen is. Bepaalt ook de voortgang. */
	Stappen.prototype.route = function () {
		var v = this.v;
		var r = ['keuze', 'vraag'];
		if (v.naam) {
			r.push('naam');
			if (v.naam.achter && v.naam.verplicht && (this.a.vroegAchternaam || (this.a.voornaam && !this.a.achternaam))) { r.push('achternaam'); }
		}
		if (v.email) { r.push('email'); }
		if (v.telefoon) { r.push('telefoon'); }
		v.extra.forEach(function (x, i) { r.push('extra-' + i); });
		return r;
	};

	Stappen.prototype.volgende = function (id) {
		var r = this.route();
		var i = r.indexOf(id);
		if (i === -1) {
			/* Hoort niet bij de route: ga naar de eerste vraag die nog leeg is, anders versturen. */
			var a = this.a;
			var leeg = r.filter(function (s) {
				return (s === 'email' && !a.email) || (s === 'telefoon' && !a.telefoon) || (s === 'naam' && !a.voornaam);
			});
			return leeg.length ? leeg[0] : 'versturen';
		}
		return i < r.length - 1 ? r[i + 1] : 'versturen';
	};

	/* Bij welke fase hoort een scherm. */
	Stappen.prototype.faseVan = function (id) {
		if (id === 'keuze' || id === 'prijs' || id === 'direct') { return 'onderwerp'; }
		if (id === 'vraag' || id === 'faq-bedankt') { return 'vraag'; }
		if (id === 'naam' || id === 'achternaam') { return 'jij'; }
		if (id === 'email' || id === 'telefoon') { return 'contact'; }
		if (/^extra-/.test(id)) { return 'extra'; }
		return 'klaar';
	};

	/* Stappenbalk: alle fases met naam, de huidige gemarkeerd, de vorige met een vinkje. */
	Stappen.prototype.zetVoortgang = function (id) {
		if (!this.fases) { return; }
		var t = this.t;
		var lijst = [
			{ id: 'onderwerp', naam: t.fase_onderwerp },
			{ id: 'vraag', naam: t.fase_vraag }
		];
		if (this.v.naam) { lijst.push({ id: 'jij', naam: t.fase_jij }); }
		if (this.v.email || this.v.telefoon) { lijst.push({ id: 'contact', naam: t.fase_contact }); }
		if (this.v.extra.length) { lijst.push({ id: 'extra', naam: t.fase_extra }); }
		var nu = this.faseVan(id);
		var index = nu === 'klaar' ? lijst.length : lijst.map(function (f) { return f.id; }).indexOf(nu);
		var zelf = this;
		this.fases.innerHTML = '';
		lijst.forEach(function (f, i) {
			var li = maak('li', 'stappen-fase' + (i < index ? ' is-klaar' : '') + (i === index ? ' is-nu' : ''));
			var nr = maak('span', 'stappen-fase-nr');
			if (i < index) { nr.innerHTML = zelf.vink; } else { nr.textContent = String(i + 1); }
			li.appendChild(nr);
			li.appendChild(maak('span', 'stappen-fase-naam', f.naam));
			if (i === index) { li.setAttribute('aria-current', 'step'); }
			zelf.fases.appendChild(li);
		});
	};

	/* ---------- Tonen ---------- */

	Stappen.prototype.ga = function (id, terugNaar) {
		var zelf = this;
		if (this.huidig && !terugNaar) { this.geschiedenis.push(this.huidig); }
		this.huidig = id;
		this.ok = null;

		/* Direct wisselen, zonder wachten: de nieuwe vraag komt meteen, met alleen een korte fade. */
		var stap = this.bouw(id);
		this.podium.querySelectorAll('.stap').forEach(function (oud) { oud.remove(); });
		this.podium.appendChild(stap);

		this.zetVoortgang(id);
		this.terugKnop.disabled = !this.geschiedenis.length || id === 'bedankt' || id === 'bezig';
		this.verderKnop.disabled = !this.ok;
		this.opnieuwKnop.hidden = !this.geschiedenis.length || id === 'bedankt' || id === 'bezig' || id === 'faq-bedankt';

		/* Focus op het veld, maar alleen als het formulier in beeld is: anders springt de pagina. */
		var veld = stap.querySelector('[data-stap-veld]');
		if (this.inBeeld && !aanraak) {
			(veld || stap).focus({ preventScroll: true });
		}
	};

	Stappen.prototype.terug = function () {
		if (!this.geschiedenis.length) { return; }
		this.ga(this.geschiedenis.pop(), true);
	};

	Stappen.prototype.opnieuw = function () {
		this.leeg();
		this.vul();
		this.ga('keuze', true);
	};

	/* Een scherm: nummer, vraag, uitleg, inhoud, melding en de OK-knop. */
	Stappen.prototype.bouw = function (id) {
		var zelf = this;
		var t = this.t;
		var stap = maak('div', 'stap stap-' + id.replace(/-\d+$/, ''));
		stap.tabIndex = -1;

		var kop = maak('div', 'stap-kop');
		var vraag = maak('h3', 'stap-vraag');
		kop.appendChild(vraag);
		stap.appendChild(kop);
		var uitleg = maak('p', 'stap-uitleg');
		stap.appendChild(uitleg);
		var inhoud = maak('div', 'stap-inhoud');
		stap.appendChild(inhoud);
		var melding = maak('p', 'stap-melding');
		melding.setAttribute('role', 'alert');
		melding.hidden = true;
		stap.appendChild(melding);

		var zet = function (titel, tekst) {
			vraag.textContent = titel;
			uitleg.textContent = tekst || '';
			uitleg.hidden = !tekst;
		};
		var fout = function (tekst) {
			melding.textContent = tekst;
			melding.hidden = !tekst;
		};

		/* OK-knop met de hint voor Enter. Op het laatste scherm wordt het Verstuur. */
		var acties = function (opslaan) {
			var laatste = zelf.volgende(id) === 'versturen';
			var rij = maak('div', 'stap-acties');
			var knop = maak('button', 'stap-ok', laatste ? t.versturen : t.ok);
			knop.type = 'button';
			if (!laatste) { knop.insertAdjacentHTML('beforeend', zelf.vink); }
			rij.appendChild(knop);
			if (!aanraak) { rij.appendChild(maak('span', 'stap-enter', t.enter)); }
			stap.appendChild(rij);
			zelf.ok = function () {
				var probleem = opslaan();
				if (probleem) {
					fout(probleem);
					stap.classList.remove('schudt');
					void stap.offsetWidth;
					stap.classList.add('schudt');
					return;
				}
				fout('');
				var verder = zelf.volgende(id);
				if (verder === 'versturen') { zelf.verstuur(); } else { zelf.ga(verder); }
			};
			knop.addEventListener('click', function () { zelf.ok(); });
		};

		/* Tekstveld; Enter gaat door, bij een lang veld is Shift + Enter een nieuwe regel. */
		var veld = function (opties) {
			var el;
			if (opties.lang) {
				el = maak('textarea', 'stap-veld stap-veld-lang');
				el.rows = 2;
				var groei = function () {
					el.style.height = 'auto';
					el.style.height = Math.min(el.scrollHeight, 220) + 'px';
				};
				el.addEventListener('input', groei);
				requestAnimationFrame(groei);
			} else {
				el = maak('input', 'stap-veld');
				el.type = opties.type || 'text';
			}
			el.setAttribute('data-stap-veld', '');
			el.setAttribute('placeholder', opties.plaatshouder || '');
			el.setAttribute('autocomplete', opties.autocomplete || 'off');
			if (opties.inputmode) { el.setAttribute('inputmode', opties.inputmode); }
			el.setAttribute('aria-label', vraag.textContent);
			el.value = opties.waarde || '';
			el.addEventListener('input', function () { fout(''); });
			el.addEventListener('keydown', function (e) {
				if (e.key === 'Enter' && !e.isComposing && !(opties.lang && e.shiftKey)) {
					e.preventDefault();
					if (zelf.ok) { zelf.ok(); }
				}
			});
			inhoud.appendChild(el);
			if (opties.lang && !aanraak) { inhoud.appendChild(maak('p', 'stap-tip', t.enter_regel)); }
			return el;
		};

		/* Keuzes met een letter, zoals A, B, C. Kiezen gaat meteen door. */
		var opties = function (lijst) {
			var rij = maak('div', 'stap-opties');
			lijst.forEach(function (o, i) {
				var knop = maak(o.href ? 'a' : 'button', 'stap-optie' + (o.gekozen ? ' is-gekozen' : ''));
				if (o.href) {
					knop.href = o.href;
					if (o.nieuw) { knop.target = '_blank'; knop.rel = 'noopener'; }
					if (o.soort === 'whatsapp') { knop.setAttribute('data-whatsapp-open', ''); }
				} else {
					knop.type = 'button';
				}
				knop.setAttribute('data-letter', LETTERS[i]);
				knop.appendChild(maak('span', 'stap-letter', LETTERS[i]));
				if (o.icoon) {
					var ic = maak('span', 'stap-optie-icoon');
					ic.innerHTML = o.icoon;
					knop.appendChild(ic);
				}
				var tekst = maak('span', 'stap-optie-tekst', o.tekst);
				if (o.sub) { tekst.appendChild(maak('small', '', o.sub)); }
				knop.appendChild(tekst);
				knop.addEventListener('click', function () {
					if (!o.kies) { return; }
					rij.querySelectorAll('.stap-optie').forEach(function (k) { k.classList.remove('is-gekozen'); });
					knop.classList.add('is-gekozen');
					o.kies();
				});
				rij.appendChild(knop);
			});
			inhoud.appendChild(rij);
			return rij;
		};

		var tochVraag = function () {
			var link = maak('button', 'stap-link', t.toch_vraag);
			link.type = 'button';
			link.addEventListener('click', function () { zelf.ga('vraag'); });
			stap.appendChild(link);
		};

		if (id === 'keuze') {
			zet(t.keuze, t.keuze_uitleg);
			var keuzes = [
				{ tekst: t.keuze_vraag, kies: function () { zelf.ga('vraag'); } },
				{ tekst: t.keuze_prijs, kies: function () { zelf.ga('prijs'); } }
			];
			if (this.direct.length) { keuzes.push({ tekst: t.keuze_direct, kies: function () { zelf.ga('direct'); } }); }
			opties(keuzes);
		} else if (id === 'prijs') {
			zet(t.prijs, t.prijs_uitleg);
			var naar = maak('a', 'stap-ok stap-ok-groot', t.prijs_knop);
			naar.href = this.offerte;
			naar.insertAdjacentHTML('beforeend', this.pijl);
			inhoud.appendChild(naar);
			tochVraag();
		} else if (id === 'direct') {
			zet(t.direct, t.direct_uitleg);
			opties(this.direct.map(function (d) {
				return { href: d.href, nieuw: d.nieuw, soort: d.soort, icoon: d.icoon, tekst: d.label, sub: d.soort === 'whatsapp' ? '' : d.tekst };
			}));
			tochVraag();
		} else if (id === 'vraag') {
			zet(t.vraag, t.vraag_uitleg);
			var vraagVeld = veld({ lang: true, plaatshouder: t.vraag_veld, waarde: this.a.vraag });
			var hint = maak('div', 'stap-hint');
			hint.hidden = true;
			inhoud.appendChild(hint);
			var wacht = null;
			var kijk = function () {
				var tekst = vraagVeld.value.trim();
				var snel = tekst.length > 8 ? zelf.zoekAntwoord(tekst) : null;
				hint.innerHTML = '';
				if (snel) {
					hint.appendChild(maak('p', 'stap-hint-kop', t.faq_kop));
					hint.appendChild(maak('p', 'stap-hint-tekst', snel.antwoord));
					var rij = maak('div', 'stap-hint-acties');
					var klaar = maak('button', 'stap-hint-knop', t.faq_klaar);
					klaar.type = 'button';
					klaar.addEventListener('click', function () { zelf.ga('faq-bedankt'); });
					rij.appendChild(klaar);
					if (snel.link) {
						var lees = maak('a', 'stap-hint-link', snel.linktekst || snel.link);
						lees.href = snel.link;
						rij.appendChild(lees);
					}
					hint.appendChild(rij);
					hint.hidden = false;
				} else if (tekst.length > 8 && aanvraagPatroon.test(tekst)) {
					hint.appendChild(maak('p', 'stap-hint-kop', t.aanvraag_kop));
					hint.appendChild(maak('p', 'stap-hint-tekst', t.aanvraag_tekst));
					var offerte = maak('a', 'stap-hint-knop', t.prijs_knop);
					offerte.href = zelf.offerte;
					var rij2 = maak('div', 'stap-hint-acties');
					rij2.appendChild(offerte);
					hint.appendChild(rij2);
					hint.hidden = false;
				} else {
					hint.hidden = true;
				}
			};
			vraagVeld.addEventListener('input', function () {
				clearTimeout(wacht);
				wacht = setTimeout(kijk, 350);
			});
			kijk();
			acties(function () {
				var waarde = vraagVeld.value.trim();
				if (!waarde && zelf.v.vraag.verplicht) { return t.verplicht; }
				zelf.a.vraag = waarde;
				/* Gegevens uit de vraag alvast klaarzetten voor de volgende schermen. */
				var mail = waarde.match(emailInTekst);
				var tel = waarde.match(telefoonInTekst);
				if (mail && !zelf.a.email) { zelf.a.email = mail[0]; zelf.a.uitVraag.email = true; }
				if (tel && telefoonGoed(tel[0]) && !zelf.a.telefoon) { zelf.a.telefoon = tel[0].trim(); zelf.a.uitVraag.telefoon = true; }
				return '';
			});
		} else if (id === 'naam') {
			zet(t.naam, '');
			var naamVeld = veld({ plaatshouder: t.naam_veld, autocomplete: 'name', waarde: (this.a.voornaam + ' ' + this.a.achternaam).trim() });
			acties(function () {
				var waarde = naamVeld.value.trim();
				if (!waarde && zelf.v.naam.verplicht) { return t.verplicht; }
				var delen = waarde.split(/\s+/);
				zelf.a.voornaam = delen.shift() || '';
				zelf.a.achternaam = delen.join(' ');
				/* Alleen een voornaam en de achternaam is verplicht: die vragen we apart, en dat blijft zo in de route. */
				zelf.a.vroegAchternaam = !zelf.a.achternaam && !!(zelf.v.naam.achter && zelf.v.naam.verplicht);
				return '';
			});
		} else if (id === 'achternaam') {
			zet(this.met(t.achternaam), '');
			var achterVeld = veld({ autocomplete: 'family-name', waarde: this.a.achternaam });
			acties(function () {
				var waarde = achterVeld.value.trim();
				if (!waarde) { return t.verplicht; }
				zelf.a.achternaam = waarde;
				return '';
			});
		} else if (id === 'email') {
			zet(this.met(t.email), this.a.uitVraag.email ? t.overgenomen : '');
			var mailVeld = veld({ type: 'email', inputmode: 'email', autocomplete: 'email', plaatshouder: t.email_veld, waarde: this.a.email });
			/* Direct controleren: na een korte typpauze en bij het verlaten van het veld.
			   Een bekende tikfout in het domein (gmial.com) krijgt een voorstel met één klik. */
			var mailWacht = null;
			var controleer = function (streng) {
				var waarde = mailVeld.value.trim();
				mailVeld.classList.remove('is-goed', 'is-fout');
				if (!waarde) { fout(''); return; }
				var voorstel = emailVoorstel(waarde);
				if (emailPatroon.test(waarde) && !voorstel) {
					mailVeld.classList.add('is-goed');
					fout('');
				} else if (voorstel) {
					mailVeld.classList.add('is-fout');
					fout('');
					melding.hidden = false;
					melding.textContent = t.email_bedoel + ' ';
					var neem = maak('button', 'stap-melding-knop', voorstel);
					neem.type = 'button';
					/* Niet eerst het veld laten verlaten: dan wordt de melding opnieuw opgebouwd en valt de klik weg. */
					neem.addEventListener('mousedown', function (e) { e.preventDefault(); });
					neem.addEventListener('click', function () {
						mailVeld.value = voorstel;
						controleer(true);
						mailVeld.focus();
					});
					melding.appendChild(neem);
					melding.appendChild(document.createTextNode('?'));
				} else if (streng || /@[^@]*\.[^.]{2,}$/.test(waarde)) {
					mailVeld.classList.add('is-fout');
					fout(t.email_fout);
				}
			};
			mailVeld.addEventListener('input', function () {
				clearTimeout(mailWacht);
				mailVeld.classList.remove('is-goed', 'is-fout');
				mailWacht = setTimeout(function () { controleer(false); }, 500);
			});
			mailVeld.addEventListener('blur', function () { controleer(true); });
			if (mailVeld.value) { controleer(true); }
			acties(function () {
				var waarde = mailVeld.value.trim();
				if (!waarde) { return t.verplicht; }
				if (!emailPatroon.test(waarde)) { mailVeld.classList.add('is-fout'); return t.email_fout; }
				zelf.a.email = waarde;
				return '';
			});
		} else if (id === 'telefoon') {
			zet(t.telefoon, this.a.uitVraag.telefoon ? t.overgenomen : t.telefoon_uitleg);
			var telVeld = veld({ type: 'tel', inputmode: 'tel', autocomplete: 'tel', plaatshouder: t.telefoon_veld, waarde: this.a.telefoon });
			acties(function () {
				var waarde = telVeld.value.trim();
				if (!waarde) { return t.verplicht; }
				if (!telefoonGoed(waarde)) { return t.telefoon_fout; }
				zelf.a.telefoon = waarde;
				return '';
			});
		} else if (/^extra-\d+$/.test(id)) {
			var nr2 = parseInt(id.split('-')[1], 10);
			var x = this.v.extra[nr2];
			zet(x.label, x.uitleg);
			var extraVeld = veld({ lang: x.lang, waarde: this.a.extra[nr2] || '' });
			acties(function () {
				var waarde = extraVeld.value.trim();
				if (!waarde && x.verplicht) { return t.verplicht; }
				zelf.a.extra[nr2] = waarde;
				return '';
			});
		} else if (id === 'bezig') {
			stap.classList.add('stap-midden');
			zet(t.bezig, '');
			inhoud.appendChild(maak('span', 'stap-laden'));
		} else if (id === 'bedankt') {
			stap.classList.add('stap-midden');
			zet(t.gelukt_titel.replace('{naam}', this.a.voornaam ? ', ' + this.a.voornaam : ''), this.bevestiging || t.gelukt);
		} else if (id === 'faq-bedankt') {
			stap.classList.add('stap-midden');
			zet(t.faq_bedankt, t.faq_bedankt_uitleg);
			var nog = maak('button', 'stap-ok', t.opnieuw);
			nog.type = 'button';
			nog.addEventListener('click', function () { zelf.opnieuw(); });
			inhoud.appendChild(nog);
		}

		return stap;
	};

	/* Eerste snelle antwoord dat bij de vraag past, of null. */
	Stappen.prototype.zoekAntwoord = function (tekst) {
		for (var i = 0; i < this.antwoorden.length; i++) {
			if (this.antwoorden[i].regex.test(tekst)) { return this.antwoorden[i]; }
		}
		return null;
	};

	/* ---------- Formulier vullen en versturen ---------- */

	Stappen.prototype.vul = function () {
		var v = this.v;
		var a = this.a;
		var t = this.t;
		v.vraag.invoer.forEach(function (i) { zetWaarde(i, a.vraag); });
		if (v.naam) {
			if (v.naam.voor) {
				zetWaarde(v.naam.voor, a.voornaam);
				zetWaarde(v.naam.achter, a.achternaam);
			} else {
				zetWaarde(v.naam.los, (a.voornaam + ' ' + a.achternaam).trim());
			}
		}
		if (v.email) { v.email.invoer.forEach(function (i) { zetWaarde(i, a.email); }); }
		if (v.telefoon) { v.telefoon.invoer.forEach(function (i) { zetWaarde(i, a.telefoon); }); }
		v.extra.forEach(function (x, n) { x.invoer.forEach(function (i) { zetWaarde(i, a.extra[n] || ''); }); });
	};

	Stappen.prototype.verstuur = function () {
		var zelf = this;
		this.vul();
		this.ga('bezig');

		var klaar = false;
		var afhandelen = function (gelukt, tekst) {
			if (klaar) { return; }
			klaar = true;
			kijker.disconnect();
			clearTimeout(vangnet);
			if (gelukt) {
				zelf.bevestiging = tekst || '';
				zelf.geschiedenis = [];
				zelf.ga('bedankt', true);
			} else {
				zelf.klassiek();
			}
		};

		/* Gravity Forms vervangt na het versturen (AJAX) de inhoud: een bevestiging of het formulier met foutmeldingen. */
		var kijker = new MutationObserver(function () {
			var bevestiging = zelf.formulierVak.querySelector('.gform_confirmation_message');
			if (bevestiging) {
				afhandelen(true, bevestiging.textContent.trim());
				return;
			}
			if (zelf.formulierVak.querySelector('.gform_validation_errors, .gfield_error')) {
				afhandelen(false);
			}
		});
		kijker.observe(this.formulierVak, { childList: true, subtree: true });
		var vangnet = setTimeout(function () { afhandelen(false); }, 20000);

		this.formulier.querySelector('[type="submit"], .gform_button').click();
	};

	/* Terug naar het gewone formulier, met de antwoorden al ingevuld. */
	Stappen.prototype.klassiek = function () {
		this.vul();
		this.vak.classList.remove('stappen-aan');
		this.formulierVak.removeAttribute('inert');
		this.formulierVak.removeAttribute('aria-hidden');
		this.el.hidden = true;
		var melding = maak('p', 'stappen-fout', this.t.fout);
		this.formulierVak.insertBefore(melding, this.formulierVak.firstChild);
		var eerste = this.formulierVak.querySelector('.gfield_error input, .gfield_error textarea, input:not([type="hidden"]), textarea');
		if (eerste) { eerste.focus({ preventScroll: true }); }
	};

	/* ---------- Starten ---------- */

	Stappen.prototype.aanzetten = function () {
		var zelf = this;
		this.vak.classList.add('stappen-aan');
		this.el.hidden = false;
		this.formulierVak.setAttribute('inert', '');
		this.formulierVak.setAttribute('aria-hidden', 'true');

		if ('IntersectionObserver' in window) {
			new IntersectionObserver(function (items) {
				zelf.inBeeld = items.some(function (i) { return i.isIntersecting; });
			}, { threshold: 0.5 }).observe(this.el);
		}

		/* Letters kiezen een optie, zolang je niet in een tekstveld zit en het formulier in beeld is. */
		document.addEventListener('keydown', function (e) {
			if (e.metaKey || e.ctrlKey || e.altKey || !zelf.inBeeld) { return; }
			var actief = document.activeElement;
			if (actief && /^(INPUT|TEXTAREA|SELECT)$/.test(actief.tagName)) { return; }
			if (actief && actief !== document.body && !zelf.el.contains(actief)) { return; }
			var letter = e.key && e.key.length === 1 ? e.key.toUpperCase() : '';
			var optie = letter ? zelf.podium.querySelector('.stap:last-child .stap-optie[data-letter="' + letter + '"]') : null;
			if (optie) {
				e.preventDefault();
				optie.click();
			} else if (e.key === 'Enter' && zelf.ok && zelf.el.contains(actief)) {
				e.preventDefault();
				zelf.ok();
			}
		});

		this.ga('keuze');
	};

	document.querySelectorAll('.formulier-vak').forEach(function (vak) {
		if (!vak.querySelector('[data-stappen]') || !vak.querySelector('[data-stappen-formulier]')) { return; }
		var s = new Stappen(vak);
		if (s.kan()) { s.aanzetten(); }
	});
}());
