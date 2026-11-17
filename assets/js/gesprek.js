/**
 * Contactformulier als gesprek.
 *
 * Leest de velden van het Gravity Forms formulier in het contactblok, stelt ze een voor
 * een als vraag en vult en verstuurt daarna het echte formulier. Meldingen, inzendingen
 * en de bevestiging blijven dus van Gravity Forms.
 *
 * Eerst de keuze: een prijs voor een rit (door naar het offerteformulier) of een snelle vraag.
 * Lijkt de vraag toch op een aanvraag (prijs, datum, aantal personen), dan wijst het gesprek
 * nog een keer naar het offerteformulier.
 *
 * Zonder JavaScript, of bij een veldtype dat het gesprek niet kent (bijv. captcha of een
 * keuzelijst), blijft het gewone formulier staan.
 */
(function () {
	'use strict';

	var rustig = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	var aanvraagPatroon = /(prijs|prijzen|kost|kosten|offerte|tarief|tarieven|wat betaal|beschikba|nog vrij|reserveer|reserveren|boeken|boeking|\d+\s*(personen|pers\b|man\b|mensen|gasten)|\b\d{1,2}\s*(jan|feb|maart|mrt|apr|mei|jun|jul|aug|sep|okt|nov|dec)|\b\d{1,2}[-\/.]\d{1,2}\b)/i;
	var emailPatroon = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

	function wacht(ms) {
		return new Promise(function (klaar) { setTimeout(klaar, rustig ? 0 : ms); });
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

	/* Velden uit het formulier lezen. Geeft null terug als er een veld is dat het gesprek niet kent. */
	function leesVelden(formulier) {
		var velden = [];
		var onbekend = false;

		formulier.querySelectorAll('.gfield').forEach(function (el) {
			var lijst = el.classList;
			if (lijst.contains('gform_validation_container') || lijst.contains('gfield--type-honeypot') || lijst.contains('gfield--type-hidden') || lijst.contains('gfield_visibility_hidden') || lijst.contains('gfield--type-html') || lijst.contains('gfield--type-section')) {
				return;
			}
			var label = labelVan(el);
			var veld = { el: el, label: label, verplicht: lijst.contains('gfield_contains_required'), waarde: '' };

			if (lijst.contains('gfield--type-name')) {
				veld.soort = 'naam';
				veld.voor = el.querySelector('.name_first input');
				veld.achter = el.querySelector('.name_last input');
				veld.los = !veld.voor ? el.querySelector('input[type="text"]') : null;
			} else if (lijst.contains('gfield--type-email')) {
				veld.soort = 'email';
				veld.invoer = Array.prototype.slice.call(el.querySelectorAll('input[type="email"], input[type="text"]'));
			} else if (lijst.contains('gfield--type-phone') || (lijst.contains('gfield--type-text') && /telefoon|mobiel|gsm/i.test(label))) {
				veld.soort = 'telefoon';
				veld.invoer = [el.querySelector('input')];
			} else if (lijst.contains('gfield--type-textarea')) {
				veld.soort = 'vraag';
				veld.invoer = [el.querySelector('textarea')];
			} else if (lijst.contains('gfield--type-text')) {
				veld.soort = 'tekst';
				veld.invoer = [el.querySelector('input')];
				var uitleg = el.querySelector('.gfield_description');
				veld.uitleg = uitleg ? uitleg.textContent.trim() : '';
			} else {
				onbekend = true;
				return;
			}
			velden.push(veld);
		});

		if (onbekend || !velden.length) { return null; }

		/* De vraag zelf eerst: dat voelt als een gesprek, en zo kan het gesprek meteen doorverwijzen als het een aanvraag is. */
		return velden.filter(function (v) { return v.soort === 'vraag'; }).concat(velden.filter(function (v) { return v.soort !== 'vraag'; }));
	}

	function zetWaarde(invoer, waarde) {
		if (!invoer) { return; }
		invoer.value = waarde;
		invoer.dispatchEvent(new Event('input', { bubbles: true }));
		invoer.dispatchEvent(new Event('change', { bubbles: true }));
	}

	function Gesprek(vak) {
		this.vak = vak;
		this.gesprek = vak.querySelector('[data-gesprek]');
		this.formulierVak = vak.querySelector('[data-gesprek-formulier]');
		this.formulier = this.formulierVak.querySelector('form');
		this.log = this.gesprek.querySelector('[data-gesprek-log]');
		this.invoerVak = this.gesprek.querySelector('[data-gesprek-invoer]');
		this.veld = this.gesprek.querySelector('[data-gesprek-veld]');
		this.offerte = this.gesprek.getAttribute('data-offerte-link');
		this.t = {};
		try { this.t = JSON.parse(this.gesprek.getAttribute('data-teksten') || '{}'); } catch (e) {}
		this.velden = this.formulier ? leesVelden(this.formulier) : null;
		this.voornaam = '';
	}

	Gesprek.prototype.kan = function () {
		return !!(this.formulier && this.velden && this.formulier.querySelector('[type="submit"], .gform_button'));
	};

	Gesprek.prototype.aanzetten = function () {
		var zelf = this;
		this.vak.classList.add('gesprek-aan');
		this.gesprek.hidden = false;
		this.formulierVak.setAttribute('inert', '');
		this.formulierVak.setAttribute('aria-hidden', 'true');

		this.gesprek.querySelector('[data-gesprek-klassiek]').addEventListener('click', function () { zelf.klassiek(); });

		this.veld.setAttribute('placeholder', this.t.plaatshouder || '');
		this.veld.addEventListener('input', function () { zelf.groei(); });
		this.veld.addEventListener('keydown', function (e) {
			if (e.key === 'Enter' && !e.shiftKey) {
				e.preventDefault();
				zelf.invoerVak.requestSubmit ? zelf.invoerVak.requestSubmit() : zelf.invoerVak.dispatchEvent(new Event('submit', { cancelable: true }));
			}
		});
		this.invoerVak.addEventListener('submit', function (e) {
			e.preventDefault();
			if (zelf.wachtOpInvoer) { zelf.wachtOpInvoer(zelf.veld.value); }
		});

		/* Pas beginnen als het gesprek in beeld komt, anders mist de bezoeker de eerste berichten. */
		if ('IntersectionObserver' in window) {
			var kijker = new IntersectionObserver(function (items) {
				if (items.some(function (i) { return i.isIntersecting; })) {
					kijker.disconnect();
					zelf.begin();
				}
			}, { threshold: 0.25 });
			kijker.observe(this.gesprek);
		} else {
			this.begin();
		}
	};

	Gesprek.prototype.groei = function () {
		this.veld.style.height = 'auto';
		this.veld.style.height = Math.min(this.veld.scrollHeight, 180) + 'px';
	};

	Gesprek.prototype.naarOnder = function () {
		this.log.scrollTop = this.log.scrollHeight;
	};

	Gesprek.prototype.bericht = function (tekst, van) {
		var b = maak('div', 'gesprek-bericht gesprek-bericht-' + (van || 'bus'), tekst);
		this.log.appendChild(b);
		this.naarOnder();
		return b;
	};

	/* Bus zegt iets, met even "typen" ervoor. */
	Gesprek.prototype.zeg = async function (tekst) {
		var typen = maak('div', 'gesprek-bericht gesprek-bericht-bus gesprek-typen');
		typen.setAttribute('aria-hidden', 'true');
		typen.innerHTML = '<span></span><span></span><span></span>';
		this.log.appendChild(typen);
		this.naarOnder();
		await wacht(Math.min(1100, 350 + tekst.length * 9));
		typen.remove();
		return this.bericht(tekst, 'bus');
	};

	/* Keuzeknoppen. Een optie met href is een link (offerteformulier), de rest geeft de waarde terug. */
	Gesprek.prototype.kies = function (opties) {
		var zelf = this;
		return new Promise(function (klaar) {
			var rij = maak('div', 'gesprek-keuzes');
			opties.forEach(function (o) {
				var knop;
				if (o.href) {
					knop = maak('a', 'gesprek-keuze gesprek-keuze-hoofd', o.tekst);
					knop.href = o.href;
				} else {
					knop = maak('button', 'gesprek-keuze' + (o.hoofd ? ' gesprek-keuze-hoofd' : ''), o.tekst);
					knop.type = 'button';
					knop.addEventListener('click', function () {
						rij.remove();
						zelf.bericht(o.tekst, 'gast');
						klaar(o.waarde);
					});
				}
				rij.appendChild(knop);
			});
			zelf.log.appendChild(rij);
			zelf.naarOnder();
		});
	};

	/* Vrije invoer. opties: verplicht, meerregelig, type, autocomplete, overslaan, controle(waarde) -> foutmelding of leeg. */
	Gesprek.prototype.vraag = function (opties) {
		var zelf = this;
		opties = opties || {};
		return new Promise(function (klaar) {
			var overslaan = null;
			var afronden = function (waarde) {
				zelf.wachtOpInvoer = null;
				zelf.invoerVak.hidden = true;
				if (overslaan) { overslaan.remove(); }
				klaar(waarde);
			};

			if (opties.overslaan) {
				overslaan = maak('div', 'gesprek-keuzes');
				var knop = maak('button', 'gesprek-keuze', opties.overslaan);
				knop.type = 'button';
				knop.addEventListener('click', function () {
					zelf.bericht(opties.overslaan, 'gast');
					afronden('');
				});
				overslaan.appendChild(knop);
				zelf.log.appendChild(overslaan);
				zelf.naarOnder();
			}

			zelf.veld.value = '';
			zelf.veld.setAttribute('inputmode', opties.inputmode || 'text');
			zelf.veld.setAttribute('autocomplete', opties.autocomplete || 'off');
			zelf.veld.setAttribute('enterkeyhint', 'send');
			zelf.invoerVak.classList.toggle('gesprek-invoer-groot', !!opties.meerregelig);
			zelf.invoerVak.hidden = false;
			zelf.groei();
			zelf.veld.focus({ preventScroll: true });

			var ontvang = async function (ruw) {
				var waarde = ruw.trim();
				if (!waarde) {
					if (opties.verplicht) {
						zelf.wachtOpInvoer = null;
						await zelf.zeg(zelf.t.verplicht);
						zelf.wachtOpInvoer = ontvang;
					}
					return;
				}
				zelf.bericht(waarde, 'gast');
				zelf.veld.value = '';
				zelf.groei();
				var fout = opties.controle ? opties.controle(waarde) : '';
				if (fout) {
					zelf.wachtOpInvoer = null;
					await zelf.zeg(fout);
					zelf.wachtOpInvoer = ontvang;
					zelf.veld.focus({ preventScroll: true });
					return;
				}
				afronden(waarde);
			};
			zelf.wachtOpInvoer = ontvang;
		});
	};

	Gesprek.prototype.begin = async function () {
		var t = this.t;
		await this.zeg(t.begroeting);
		await this.zeg(t.keuzevraag);
		var keuze = await this.kies([
			{ waarde: 'prijs', tekst: t.keuze_prijs },
			{ waarde: 'vraag', tekst: t.keuze_vraag }
		]);
		if (keuze === 'prijs') {
			await this.zeg(t.prijs_uitleg);
			await this.kies([
				{ href: this.offerte, tekst: t.prijs_knop },
				{ waarde: 'vraag', tekst: t.toch_vraag }
			]);
		}
		await this.vragen();
	};

	Gesprek.prototype.vragen = async function () {
		var t = this.t;
		for (var i = 0; i < this.velden.length; i++) {
			var v = this.velden[i];

			if (v.soort === 'vraag') {
				await this.zeg(t.vraag);
				v.waarde = await this.vraag({ verplicht: v.verplicht, meerregelig: true });
				if (aanvraagPatroon.test(v.waarde)) {
					await this.zeg(t.aanvraag_check);
					await this.kies([
						{ href: this.offerte, tekst: t.prijs_knop },
						{ waarde: 'door', tekst: t.toch_versturen }
					]);
				}
			} else if (v.soort === 'naam') {
				await this.zeg(t.naam);
				var delen = (await this.vraag({ verplicht: v.verplicht, autocomplete: 'name' })).split(/\s+/);
				v.voornaam = delen.shift();
				v.achternaam = delen.join(' ');
				this.voornaam = v.voornaam;
				if (v.achter && !v.achternaam && v.verplicht) {
					await this.zeg(t.achternaam);
					v.achternaam = await this.vraag({ verplicht: true, autocomplete: 'family-name' });
				}
				v.waarde = (v.voornaam + ' ' + v.achternaam).trim();
			} else if (v.soort === 'email') {
				await this.zeg(this.voornaam ? t.email.replace('{naam}', this.voornaam) : t.email.replace(/^[^!]*!\s*/, ''));
				v.waarde = await this.vraag({
					verplicht: v.verplicht,
					overslaan: v.verplicht ? '' : t.overslaan,
					inputmode: 'email',
					autocomplete: 'email',
					controle: function (w) { return emailPatroon.test(w) ? '' : t.email_fout; }
				});
			} else if (v.soort === 'telefoon') {
				await this.zeg(t.telefoon);
				v.waarde = await this.vraag({ verplicht: v.verplicht, overslaan: v.verplicht ? '' : t.overslaan, inputmode: 'tel', autocomplete: 'tel' });
			} else {
				await this.zeg(v.label + (v.uitleg ? ' ' + v.uitleg : ''));
				v.waarde = await this.vraag({ verplicht: v.verplicht, overslaan: v.verplicht ? '' : t.overslaan });
			}
		}
		await this.afronden();
	};

	Gesprek.prototype.vul = function () {
		this.velden.forEach(function (v) {
			if (v.soort === 'naam') {
				if (v.voor) {
					zetWaarde(v.voor, v.voornaam || '');
					zetWaarde(v.achter, v.achternaam || '');
				} else {
					zetWaarde(v.los, v.waarde);
				}
			} else {
				v.invoer.forEach(function (invoer) { zetWaarde(invoer, v.waarde); });
			}
		});
	};

	Gesprek.prototype.afronden = async function () {
		var t = this.t;
		await this.zeg(t.klaar);

		var overzicht = maak('dl', 'gesprek-overzicht');
		this.velden.forEach(function (v) {
			if (!v.waarde) { return; }
			overzicht.appendChild(maak('dt', '', v.label));
			overzicht.appendChild(maak('dd', '', v.waarde));
		});
		var kaart = maak('div', 'gesprek-bericht gesprek-bericht-bus gesprek-bericht-overzicht');
		kaart.appendChild(overzicht);
		this.log.appendChild(kaart);
		this.naarOnder();

		var keuze = await this.kies([
			{ waarde: 'versturen', tekst: t.versturen, hoofd: true },
			{ waarde: 'aanpassen', tekst: t.aanpassen }
		]);
		this.vul();
		if (keuze === 'aanpassen') {
			this.klassiek();
			return;
		}
		this.verstuur();
	};

	Gesprek.prototype.verstuur = function () {
		var zelf = this;
		var typen = maak('div', 'gesprek-bericht gesprek-bericht-bus gesprek-typen');
		typen.setAttribute('aria-hidden', 'true');
		typen.innerHTML = '<span></span><span></span><span></span>';
		this.log.appendChild(typen);
		this.naarOnder();

		var klaar = false;
		var afhandelen = function (gelukt, tekst) {
			if (klaar) { return; }
			klaar = true;
			kijker.disconnect();
			clearTimeout(vangnet);
			typen.remove();
			if (gelukt) {
				zelf.bericht(tekst || zelf.t.gelukt, 'bus');
				zelf.gesprek.querySelector('[data-gesprek-klassiek]').hidden = true;
			} else {
				zelf.bericht(zelf.t.fout, 'bus');
				zelf.klassiek(true);
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

		var knop = this.formulier.querySelector('[type="submit"], .gform_button');
		knop.click();
	};

	/* Terug naar het gewone formulier, met de antwoorden al ingevuld. */
	Gesprek.prototype.klassiek = function (houdGesprek) {
		if (this.velden) { this.vul(); }
		this.wachtOpInvoer = null;
		this.invoerVak.hidden = true;
		this.vak.classList.remove('gesprek-aan');
		this.formulierVak.removeAttribute('inert');
		this.formulierVak.removeAttribute('aria-hidden');
		if (!houdGesprek) {
			this.gesprek.hidden = true;
		} else {
			this.gesprek.querySelector('[data-gesprek-klassiek]').hidden = true;
		}
		var eerste = this.formulierVak.querySelector('.gfield_error input, .gfield_error textarea, input:not([type="hidden"]), textarea');
		if (eerste) { eerste.focus({ preventScroll: true }); }
	};

	document.querySelectorAll('.formulier-vak').forEach(function (vak) {
		if (!vak.querySelector('[data-gesprek]') || !vak.querySelector('[data-gesprek-formulier]')) { return; }
		var g = new Gesprek(vak);
		if (g.kan()) { g.aanzetten(); }
	});
}());
