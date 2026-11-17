/**
 * Blok Stoelen: stapper voor het aantal, lopend totaal op het ticket en het maximum per avond.
 * Zonder dit script werkt het formulier ook: dan rekent de server alles na.
 */
(function () {
	'use strict';

	function euro(bedrag) {
		var heel = Math.round(bedrag * 100) % 100 === 0;
		return '€ ' + bedrag.toLocaleString('nl-NL', { minimumFractionDigits: heel ? 0 : 2, maximumFractionDigits: 2 });
	}

	function lees(el, naam) {
		try {
			return JSON.parse(el.getAttribute(naam) || '{}');
		} catch (e) {
			return {};
		}
	}

	function start(form) {
		var prijs  = parseFloat(form.getAttribute('data-prijs')) || 0;
		var max    = parseInt(form.getAttribute('data-max'), 10) || 10;
		var ritten = lees(form, 'data-ritten');
		var opstap = lees(form, 'data-opstap');
		var veld   = form.querySelector('[data-stoelen-aantal]');
		var min    = form.querySelector('[data-stoelen-min]');
		var plus   = form.querySelector('[data-stoelen-plus]');
		var uitleg = form.querySelector('[data-stoelen-aantal-uitleg]');
		var toon   = function (naam) { return form.querySelector('[data-stoelen-toon="' + naam + '"]'); };
		var basisUitleg = uitleg ? uitleg.textContent : '';

		/* Invultijd telt vanaf het laden in de browser, niet vanaf de (gecachte) pagina. */
		var begin = form.querySelector('[data-stoelen-begin]');
		if (begin) {
			begin.value = Math.floor(Date.now() / 1000);
		}

		min.hidden  = false;
		plus.hidden = false;

		function gekozen(naam) {
			var el = form.querySelector('input[name="' + naam + '"]:checked');
			return el ? el.value : '';
		}

		function grens() {
			var rit = ritten[gekozen('rit')];
			return rit ? Math.max(1, Math.min(max, rit.vrij)) : max;
		}

		function bijwerken() {
			var hoogste = grens();
			var aantal  = parseInt(veld.value, 10);
			if (isNaN(aantal) || aantal < 1) {
				aantal = 1;
			}
			if (aantal > hoogste) {
				aantal = hoogste;
			}
			if (String(aantal) !== veld.value) {
				veld.value = aantal;
			}
			veld.max     = hoogste;
			min.disabled = aantal <= 1;
			plus.disabled = aantal >= hoogste;

			var rit = ritten[gekozen('rit')];
			if (uitleg) {
				uitleg.textContent = rit && rit.vrij < max
					? (rit.vrij === 1 ? 'Op deze avond is nog 1 stoel vrij.' : 'Op deze avond zijn nog ' + rit.vrij + ' stoelen vrij.')
					: basisUitleg;
			}

			toon('rit').textContent    = rit ? rit.tekst : 'Nog kiezen';
			toon('opstap').textContent = opstap[gekozen('opstap')] || 'Nog kiezen';
			toon('aantal').textContent = aantal + ' x ' + euro(prijs);
			toon('totaal').textContent = euro(aantal * prijs);
			toon('knop').textContent   = 'Reserveer ' + (aantal === 1 ? 'je stoel' : aantal + ' stoelen');

			form.classList.toggle('is-compleet', !!(gekozen('rit') && gekozen('opstap')));
		}

		min.addEventListener('click', function () {
			veld.value = (parseInt(veld.value, 10) || 1) - 1;
			bijwerken();
		});
		plus.addEventListener('click', function () {
			veld.value = (parseInt(veld.value, 10) || 0) + 1;
			bijwerken();
		});
		form.addEventListener('change', bijwerken);
		veld.addEventListener('input', function () {
			if (veld.value !== '') {
				bijwerken();
			}
		});

		/* Na een keuze rustig door naar de volgende stap, alleen als die nog niet in beeld is. */
		form.addEventListener('change', function (e) {
			if (e.target.type !== 'radio') {
				return;
			}
			var stap = e.target.closest('.stoelen-stap');
			var volgende = stap && stap.nextElementSibling;
			if (!volgende || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
				return;
			}
			var vak = volgende.getBoundingClientRect();
			if (vak.top > window.innerHeight - 120) {
				volgende.scrollIntoView({ behavior: 'smooth', block: 'start' });
			}
		});

		/* Dubbel versturen voorkomen. */
		form.addEventListener('submit', function () {
			var knop = form.querySelector('.stoelen-verstuur');
			if (knop) {
				knop.disabled = true;
				knop.classList.add('is-bezig');
			}
		});

		window.addEventListener('pageshow', function () {
			var knop = form.querySelector('.stoelen-verstuur');
			if (knop) {
				knop.disabled = false;
				knop.classList.remove('is-bezig');
			}
		});

		var melding = form.querySelector('[data-stoelen-melding]');
		if (melding) {
			melding.focus({ preventScroll: true });
		}

		bijwerken();
	}

	function klaar() {
		document.querySelectorAll('form[data-stoelen]').forEach(start);
		var bevestiging = document.querySelector('[data-stoelen-bevestiging]');
		if (bevestiging) {
			bevestiging.focus({ preventScroll: true });
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', klaar);
	} else {
		klaar();
	}
}());
