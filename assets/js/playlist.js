/**
 * Playlist-blok: de Spotify-speler pas laden na een klik op play.
 */
(function () {
	'use strict';
	document.querySelectorAll('[data-playlist]').forEach(function (speler) {
		var knop = speler.querySelector('[data-playlist-start]');
		if (!knop) { return; }
		knop.addEventListener('click', function () {
			var frame = document.createElement('iframe');
			frame.src = speler.getAttribute('data-playlist') + '&autoplay=1';
			frame.title = 'Spotify-playlist';
			frame.loading = 'lazy';
			frame.allow = 'autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture';
			frame.className = 'playlist-frame';
			speler.classList.add('is-geladen');
			speler.replaceChildren(frame);
		});
	});
}());
