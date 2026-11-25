<?php
/**
 * Inhoud van de plaatspagina's Maastricht, Rotterdam, Amsterdam en Den Bosch (november 2026).
 * Activiteiten aangeleverd door Lekker Geel Busje; links worden bij het plaatsen gecontroleerd.
 * Geplaatst door de uitroltaak locaties-v1. Wie de tekst later in WordPress aanpast, houdt die aanpassing.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Vragen die op elke plaatspagina terugkomen, met de plaatsnaam erin.
 */
function thema_locatie_vaste_vragen( $plaats ) {
	return array(
		array( 'Waar halen jullie ons op?', 'Waar je maar wilt: thuis, bij het werk of op een centraal punt, en als het moet op meerdere opstapplekken. Zet de adressen in je aanvraag, dan nemen we ze mee in de route naar ' . $plaats . '.' ),
		array( 'Wacht de bus op ons?', 'Dat kan. De bus wacht tijdens jullie activiteit of komt op een afgesproken tijd terug. Wachttijd staat gewoon in de offerte, dus je weet vooraf waar je aan toe bent.' ),
		array( 'Wat kost een partybus naar ' . $plaats . '?', 'Dat hangt af van de grootte van je groep, de ophaalplek, de rijtijd, de dag en eventuele wachttijd. Binnen 1 werkdag heb je een vaste all-in prijs, met chauffeur, gastvrouwke of gastheer, brandstof en btw erbij.' ),
		array( 'Met hoeveel personen kunnen we mee?', 'Elke bus heeft minstens 39 zitplaatsen. In onze verlengde bus gaan tot 88 personen mee, met 53 zitplaatsen en 35 staanplaatsen. Zo past de hele groep in één bus en hoeft niemand achter het stuur.' ),
	);
}

function thema_locatie_maastricht() {
	return array(
		'plaats' => 'Maastricht', 'slug' => 'maastricht', 'volgorde' => 2,
		'seo_titel' => 'Met de partybus naar Maastricht | Lekker Geel Busje',
		'seo_omschrijving' => 'Met je groep naar Maastricht in de sfeerbus: rooftopbar, sloep varen, grotten en bierproeverijen. Wij rijden, jullie feesten. Binnen 1 werkdag een vaste prijs.',
		'hero_fotos' => array( 'gele-sfeerbus-in-de-avond-op-het-plein', 'feest-met-armen-omhoog-in-de-bus', 'gasten-lachen-aan-tafel-in-sfeerbus' ),
		'hero_wissel' => array( 'voor een bierproeverij', 'voor een dag op de Maas', 'voor een avond cocktails', 'voor een tocht onder de grond' ),
		'intro_kop' => 'Op pad naar *Maastricht*',
		'intro' => "Maastricht voelt als een weekendje buitenland, zonder dat je de grens over hoeft. Smalle straatjes, grote pleinen, de Maas door het centrum en een eeuwenoud gangenstelsel onder de stad. Wij rijden je groep ernaartoe, vanuit heel Nederland. Het feest begint al onderweg in de sfeerbus, met jullie muziek, koude drankjes, de chauffeur en het gastvrouwke.\n\nOmdat de rit naar Limburg wat langer is, wordt de bus vanzelf het eerste uitje van de dag. In Maastricht zetten we je af bij je eerste stop en op het afgesproken tijdstip brengen we iedereen weer veilig thuis.",
		'route' => array(
			array( 'tijd' => '10:30', 'icoon' => 'huis', 'titel' => 'Ophalen', 'tekst' => 'We halen je op in je eigen woonplaats, ook op meerdere opstapplekken. Onderweg naar het zuiden wordt er al geproost.' ),
			array( 'tijd' => '13:00', 'icoon' => 'glas', 'titel' => 'Bier proeven', 'tekst' => 'Rondleiding en proeverij bij Brouwerij Bosch of de Stadsbrouwerij, allebei in Wyck.' ),
			array( 'tijd' => '15:30', 'icoon' => 'bus', 'titel' => 'De Maas op', 'tekst' => 'Met een eigen sloep het water op, of onder de grond de grotten van de Sint-Pietersberg in.' ),
			array( 'tijd' => '18:30', 'icoon' => 'bestek', 'titel' => 'Eten en borrelen', 'tekst' => 'Eerst een drankje met uitzicht bij Bold Rooftopbar, daarna eten in de binnenstad.' ),
			array( 'tijd' => '21:00', 'icoon' => 'muziek', 'titel' => 'Cocktails', 'tekst' => 'Op naar Bar Nola, Somewhere in the Middle of Mr. Smith. De bus komt op een afgesproken tijd terug.' ),
			array( 'tijd' => '00:30', 'icoon' => 'maan', 'titel' => 'Thuisbrengen', 'tekst' => 'Iedereen veilig en tegelijk naar huis, met de laatste meezinger in de bus.' ),
		),
		'tips_intro' => 'Uitjes waar je met een groep goed terecht kunt. Combineer er een of twee met eten en cocktails, dan is je dag compleet.',
		'tips' => array(
			array( 'titel' => 'Bold Rooftopbar', 'soort' => 'Rooftopbar', 'foto' => array( 'commons', 'File:2023 Maastricht, Sphinxkwartier (01).jpg', 'maastricht-sphinxkwartier', 'Het Sphinxkwartier in Maastricht' ), 'tekst' => 'Een drankje met uitzicht over de hele stad, op de achtste verdieping van een oude fabriek in het Sphinxkwartier. Een heerlijke plek om bij te kletsen en samen te proosten, zeker bij mooi weer.', 'info' => 'Sphinxcour 9A', 'link' => array( 'https://www.visitmaastricht.com/nl/locaties/bold-rooftopbar' ), 'linktekst' => 'Bekijk Bold' ),
			array( 'titel' => 'Sloep varen met de groep', 'soort' => 'Op het water', 'foto' => array( 'commons', 'File:Sint-Servaasbrug Maastricht Maas.jpg', 'maastricht-maas-sint-servaasbrug', 'De Maas en de Sint-Servaasbrug in Maastricht' ), 'tekst' => 'Maastricht vanaf het water ontdekken: huur een eigen sloep, neem wat lekkere drankjes mee en geniet van muziek, gezelligheid en elkaars gezelschap. Zelf varen of met een schipper aan boord.', 'info' => 'Sint Pietersluisweg 56', 'link' => array( 'https://www.maastrichtsloep.nl/' ), 'linktekst' => 'Bekijk de sloepen' ),
			array( 'titel' => 'Citygame of kroegentocht', 'soort' => 'Spel in de stad', 'foto' => array( 'commons', 'File:Maastricht-Onze Lieve Vrouweplein (3).jpg', 'maastricht-onze-lieve-vrouweplein', 'Het Onze Lieve Vrouweplein in Maastricht' ), 'tekst' => 'Ontdek Maastricht op een originele manier, met grappige opdrachten en leuke uitdagingen langs de mooiste plekjes. Speel in teams tegen elkaar en eindig natuurlijk op een terras.', 'info' => 'Start wanneer het jullie uitkomt', 'link' => array( 'https://www.citygame.com/nl/maastricht' ), 'linktekst' => 'Bekijk de citygame' ),
			array( 'titel' => 'Bierproeverij of brouwerijtour', 'soort' => 'Proeverij', 'foto' => array( 'commons', 'File:Stadsbrouwerij Maastricht proeflokaal sign.jpg', 'maastricht-stadsbrouwerij-proeflokaal', 'Het proeflokaal van de Stadsbrouwerij Maastricht' ), 'tekst' => 'Ontdek de Limburgse biercultuur tijdens een rondleiding en proef verschillende speciaalbieren. Brouwerij Bosch is de laatste complete stadsbrouwerij van Maastricht, de Stadsbrouwerij ligt aan de Maas. Leuk voor echte bierliefhebbers.', 'info' => 'Wycker Grachtstraat 26 · ca. 2 uur', 'link' => array( 'https://www.brouwerijbosch.nl/' ), 'linktekst' => 'Bekijk Brouwerij Bosch' ),
			array( 'titel' => 'Maastricht Underground', 'soort' => 'Onder de grond', 'foto' => array( 'commons', 'File:Maastricht-Noordelijk Gangenstelsel (6).jpg', 'maastricht-grotten-noord-gangenstelsel', 'Gang in de Grotten Noord onder de Sint-Pietersberg in Maastricht' ), 'tekst' => 'Onder de stad ligt een doolhof van kilometers gangen. Doe er een belevingsspel, ga grotbiken of maak een steptocht door de grotten van de Sint-Pietersberg. Spanning verzekerd.', 'info' => 'Grotten, fort en kazematten', 'link' => array( 'https://www.maastrichtunderground.nl/' ), 'linktekst' => 'Bekijk de tochten' ),
			array( 'titel' => 'Solextour', 'soort' => 'Tour', 'foto' => array( 'commons', 'File:Velosolex motorized bicycle 2012 580.jpg', 'solex-tour-groep', 'Een klassieke Solex' ), 'tekst' => 'Ontdek de heuvelachtige omgeving van Maastricht op een solex. Rustig tuffend langs de mooiste plekjes, met onderweg genoeg tijd voor een stop op een terras.', 'info' => 'In de omgeving van Maastricht', 'link' => array( 'https://www.cyclecenter.nl/' ), 'linktekst' => 'Bekijk de solextour' ),
		),
		'band' => array( 'Vrijthof', 'Wyck', 'Sint-Pietersberg', 'Bold Rooftopbar', 'Brouwerij Bosch', 'Sphinxkwartier', 'de Maas' ),
		'gebieden' => array(
			array(
				'eyebrow' => 'Cocktails en uitgaan', 'kop' => 'Cocktails in *Maastricht*',
				'tekst' => "Maastricht heeft ontzettend veel leuke cocktailbars, van verborgen kelders tot een bar midden op het Vrijthof. Ideaal om een dag met de groep af te sluiten.\n\nReserveer met een grote groep op tijd, want de meeste bars zijn klein en sfeervol. Wij spreken vooraf een vaste ophaalplek af, zodat niemand na afloop hoeft te zoeken.",
				'plekken' => array(
					array( 'naam' => 'Bar Nola', 'soort' => 'Cocktailbar en restaurant', 'tekst' => 'Moulin Rouge meets speakeasy. Een sfeervolle plek voor een gezellige avond met vrienden of vriendinnen, ook voor lunch en diner.', 'adres' => 'Platielstraat 17', 'link' => array( 'https://www.barnola.nl/' ) ),
					array( 'naam' => 'Somewhere in the Middle', 'soort' => 'Cocktailbar', 'tekst' => 'Een verborgen cocktailbar onder het Vrijthof, bekend om bijzondere cocktails met een Limburgse twist.', 'adres' => 'Vrijthof 8', 'link' => array( 'https://www.somewhereinthemiddle.nl/' ) ),
					array( 'naam' => 'Mr. Smith', 'soort' => 'Speakeasy', 'tekst' => 'Een stijlvolle cocktailbar in een kelder in Wyck. Aanbellen bij de deur, en dan intiem, sfeervol en net even anders.', 'adres' => 'Rechtstraat 55', 'link' => array( 'https://www.mrsmithmaastricht.com/' ) ),
				),
			),
		),
		'faq' => array_merge(
			array(
				array( 'Rijden jullie ook helemaal naar Maastricht?', 'Zeker. We rijden door heel Nederland en daarbuiten. De rit naar Limburg is geen verloren tijd: met muziek, drankjes en het gastvrouwke aan boord begint jullie dagje Maastricht al zodra iedereen is ingestapt.' ),
				array( 'Waar zetten jullie ons af in Maastricht?', 'Zo dicht mogelijk bij je eerste stop. De binnenstad rond het Vrijthof en de Markt is grotendeels autoluw, dus daar spreken we vooraf een plek af op een paar minuten lopen. Daar halen we jullie ook weer op.' ),
				array( 'Kunnen we in Maastricht meerdere stops maken?', 'Zeker, daar is een busje voor. Eerst de grotten in, dan een proeverij in Wyck en daarna door naar de rooftopbar? Zet de stops in je aanvraag, dan nemen we ze mee in de route en de offerte.' ),
			),
			thema_locatie_vaste_vragen( 'Maastricht' )
		),
	);
}

function thema_locatie_rotterdam() {
	return array(
		'plaats' => 'Rotterdam', 'slug' => 'rotterdam', 'volgorde' => 3,
		'seo_titel' => 'Met de partybus naar Rotterdam | Lekker Geel Busje',
		'seo_omschrijving' => 'Met je groep naar Rotterdam in de sfeerbus: karaoke, jeu de boules, abseilen van de Euromast en de Witte de Withstraat. Binnen 1 werkdag een vaste prijs.',
		'hero_fotos' => array( 'sfeerbus-rotterdam-erasmusbrug', 'feest-met-armen-omhoog-in-de-bus', 'meezingen-met-microfoon-in-sfeerbus' ),
		'hero_wissel' => array( 'voor een avond karaoke', 'voor een potje jeu de boules', 'naar de Euromast', 'naar de Witte de Withstraat' ),
		'intro_kop' => 'Op pad naar *Rotterdam*',
		'intro' => "Rotterdam is stoer, groot en altijd in beweging. Je zingt er de longen uit je lijf in een eigen karaokekamer, gooit met bijlen, racet in een F1-simulator of hangt aan een touw langs de Euromast. Wij rijden je groep ernaartoe, vanuit heel Nederland. Het feest begint al onderweg in de sfeerbus, met jullie muziek, koude drankjes, de chauffeur en het gastvrouwke.\n\nIn Rotterdam liggen de leukste plekken flink uit elkaar. Daar is de bus juist handig voor: wij rijden jullie van stop naar stop en brengen iedereen op het afgesproken tijdstip weer veilig thuis.",
		'route' => array(
			array( 'tijd' => '13:00', 'icoon' => 'huis', 'titel' => 'Ophalen', 'tekst' => 'We halen je op in je eigen woonplaats, ook op meerdere opstapplekken. In de bus wordt er met een eerste drankje geproost.' ),
			array( 'tijd' => '14:30', 'icoon' => 'bus', 'titel' => 'Actie', 'tekst' => 'Racen bij Racesquare, bijlwerpen of lasergamen in een scheepsruim aan de Parkhaven.' ),
			array( 'tijd' => '17:00', 'icoon' => 'glas', 'titel' => 'Jeu de boules', 'tekst' => 'Een potje boulen met een borrel bij Mooie Boules, met eten uit de foodhal.' ),
			array( 'tijd' => '20:00', 'icoon' => 'muziek', 'titel' => 'Karaoke', 'tekst' => 'Bij de buren van Dikke Mic, of in een eigen kamer bij Duke of Tokyo of Madam Chen.' ),
			array( 'tijd' => '22:30', 'icoon' => 'glas', 'titel' => 'Witte de Withstraat', 'tekst' => 'Afsluiten in de bekendste uitgaansstraat. De bus komt op een afgesproken tijd terug.' ),
			array( 'tijd' => '01:00', 'icoon' => 'maan', 'titel' => 'Thuisbrengen', 'tekst' => 'Iedereen veilig en tegelijk naar huis, met de laatste meezinger in de bus.' ),
		),
		'tips_intro' => 'Uitjes waar je met een groep goed terecht kunt. Combineer er een of twee met karaoke en een avond op de Witte de Withstraat, dan is je dag compleet.',
		'tips' => array(
			array( 'titel' => 'Mooie Boules', 'soort' => 'Jeu de boules en borrel', 'foto' => array(), 'reserve' => 'gasten-delen-shotjes-uit-in-de-bus', 'tekst' => 'Een potje jeu de boules, lekker eten, drankjes en gezelligheid in een oude metaalfabriek. Tip: combineer het met karaoke bij de buren van Dikke Mic.', 'info' => 'Vijverhofstraat 45', 'link' => array( 'https://www.mooieboules.nl/rotterdam' ), 'linktekst' => 'Bekijk Mooie Boules' ),
			array( 'titel' => 'Splashtour', 'soort' => 'Over de weg en het water', 'foto' => array( 'commons', 'File:Splashtours bus on water, Charlois, Rotterdam-Zuid (2022) 01.jpg', 'rotterdam-splashtours-amfibiebus', 'De amfibiebus van Splashtours op het water in Rotterdam' ), 'tekst' => 'Stap in een amfibiebus en ontdek Rotterdam vanaf de weg én vanaf het water. Hij lijkt erg op onze gele bus, maar dan in zwemvariant.', 'info' => 'Rondrit langs en over de Maas', 'link' => array( 'https://www.splashtours.nl/' ), 'linktekst' => 'Bekijk Splashtours' ),
			array( 'titel' => 'Racesquare', 'soort' => 'Racen', 'foto' => array(), 'reserve' => 'feest-met-blauw-licht-in-de-sfeerbus', 'tekst' => 'Race tegen elkaar in professionele F1-simulators. Met kwalificatie, een echte race en een podiumceremonie voor de winnaar.', 'info' => 'Racing centre', 'link' => array( 'https://www.racesquare.nl/' ), 'linktekst' => 'Bekijk Racesquare' ),
			array( 'titel' => 'Real Axe Throwing', 'soort' => 'Bijlwerpen', 'foto' => array(), 'reserve' => 'discolicht-en-feest-in-de-sfeerbus', 'tekst' => 'Bijlwerpen op houten doelen. Wie gooit het vaakst raak? Een stoere activiteit waarbij je elkaar flink kunt uitdagen.', 'info' => 'Met instructie vooraf', 'link' => array( 'https://www.realaxethrowing.nl/' ), 'linktekst' => 'Bekijk bijlwerpen' ),
			array( 'titel' => 'Abseilen van de Euromast', 'soort' => 'Voor durfals', 'foto' => array( 'commons', 'File:Euromast in 2025.jpg', 'rotterdam-euromast', 'De Euromast in Rotterdam' ), 'tekst' => 'Durf jij het aan? Abseil vanaf 100 meter hoogte naar beneden, met de hele groep als publiek. Daarna samen proosten aan de voet van de toren.', 'info' => 'Parkhaven 20', 'link' => array( 'https://www.euromast.nl/abseilen/' ), 'linktekst' => 'Bekijk abseilen' ),
			array( 'titel' => 'Lasergame Rotterdam', 'soort' => 'Lasergamen', 'foto' => array( 'commons', 'File:1604 Parkhaven, Rotterdam 110.jpg', 'rotterdam-parkhaven', 'De Parkhaven in Rotterdam' ), 'tekst' => 'Lasergamen in een arena in een oud scheepsruim aan de Parkhaven, tegenover de Euromast. Je waant je in een echte onderzeeër.', 'info' => 'Parkhaven 9', 'link' => array( 'https://www.lasergamerotterdam.nl/' ), 'linktekst' => 'Bekijk Lasergame' ),
		),
		'band' => array( 'Euromast', 'Witte de Withstraat', 'Mooie Boules', 'Dikke Mic', 'Splashtours', 'Parkhaven', 'Erasmusbrug' ),
		'gebieden' => array(
			array(
				'eyebrow' => 'Zingen en showtime', 'kop' => 'Karaoke en *shows*',
				'tekst' => "Karaoke kan op verschillende leuke plekken in Rotterdam, van een eigen kleurrijke kamer tot een combinatie met diner. Ideaal voor een groep, want niemand hoeft voor een volle zaal te zingen.\n\nVoor een vrijgezellenfeest voor vrouwen is er ook een avond met een spectaculaire show.",
				'plekken' => array(
					array( 'naam' => 'Dikke Mic', 'soort' => 'Karaoke en arcade', 'tekst' => 'Huur je eigen karaokebooth of daag elkaar uit met arcadespellen. Met Aziatisch streetfood erbij.', 'adres' => 'Vijverhofstraat 47', 'link' => array( 'https://www.dikkemic.nl/' ) ),
					array( 'naam' => 'Duke of Tokyo', 'soort' => 'Karaoke', 'tekst' => 'Een avondje zingen in je eigen kleurrijke karaokekamer, cocktails bestellen en helemaal losgaan met je vrienden.', 'adres' => 'Hofplein 19', 'link' => array( 'https://www.dukeoftokyo.com/rotterdam' ) ),
					array( 'naam' => 'Madam Chen', 'soort' => 'Diner en karaoke', 'tekst' => 'Eerst Asian streetfood eten en daarna met de hele groep de microfoon pakken. Een leuke combi van diner en entertainment.', 'adres' => 'Rijstuin 172', 'link' => array( 'https://www.madamchen.nl/' ) ),
					array( 'naam' => 'BonTon', 'soort' => 'Show', 'tekst' => 'Een avond met mannelijke dansers en een spectaculaire show. Leuk voor een vrijgezellenfeest voor vrouwen.', 'adres' => 'Oostplein', 'link' => array( array( 'https://www.bonton.club/', 'BonTon' ), array( 'https://bonton.amsterdam/rotterdam', 'BonTon' ), array( 'https://www.bontonrotterdam.com/', 'BonTon' ) ) ),
				),
			),
			array(
				'eyebrow' => 'Eten en uitgaan', 'kop' => 'De *Witte de Withstraat*',
				'tekst' => "Een bekende uitgaansstraat vol cafés, restaurants en bars. Ideaal om met de groep verschillende plekken te combineren: eerst eten, dan borrelen en daarna nog een café verder.\n\nWij zetten jullie in de buurt af en spreken vooraf een vaste ophaalplek af, zodat niemand na afloop hoeft te zoeken.",
				'plekken' => array(
					array( 'naam' => 'Botanero', 'soort' => 'Cocktails en shared dining', 'tekst' => 'Cocktails en kleine gerechtjes om te delen, met Zuid-Amerikaanse invloeden. Een leuke plek om te borrelen.', 'adres' => '', 'link' => array( array( 'https://www.botanero.com/', 'Botanero' ), array( 'https://botanerorotterdam.nl/', 'Botanero' ), array( 'https://www.botanero-rotterdam.nl/', 'Botanero' ) ) ),
					array( 'naam' => 'OX Rotterdam', 'soort' => 'Restaurant', 'tekst' => 'Een verborgen restaurant met een speakeasy-uitstraling en verrassende gerechten om te delen. Weer eens wat anders dan een standaard restaurant.', 'adres' => '', 'link' => array( 'https://www.ox-rotterdam.nl/' ) ),
				),
			),
		),
		'faq' => array_merge(
			array(
				array( 'Waar zetten jullie ons af in Rotterdam?', 'Zo dicht mogelijk bij je eerste stop, vaak tot voor de deur. Op drukke plekken zoals de Witte de Withstraat spreken we vooraf een plek af op een paar minuten lopen, en daar halen we jullie ook weer op.' ),
				array( 'Kunnen we in Rotterdam meerdere stops maken?', 'Zeker, en in Rotterdam is dat extra handig. Eerst racen of bijlwerpen, dan boulen bij Mooie Boules en daarna karaoke? Zet de stops in je aanvraag, dan nemen we ze mee in de route en de offerte.' ),
				array( 'Is Rotterdam geschikt voor een vrijgezellenfeest?', 'Heel geschikt. Karaoke in een eigen kamer, abseilen van de Euromast, een show bij BonTon of bijlwerpen: er is voor elke groep wel iets. Wij zorgen dat iedereen er samen komt en samen weer thuiskomt.' ),
			),
			thema_locatie_vaste_vragen( 'Rotterdam' )
		),
	);
}

function thema_locatie_amsterdam() {
	return array(
		'plaats' => 'Amsterdam', 'slug' => 'amsterdam', 'volgorde' => 4,
		'seo_titel' => 'Met de partybus naar Amsterdam | Lekker Geel Busje',
		'seo_omschrijving' => 'Met je groep naar Amsterdam in de sfeerbus: rondvaart, karaoke in een ballenbak, bijlwerpen en een dinnershow. Wij rijden, jullie feesten. Binnen 1 werkdag een prijs.',
		'hero_fotos' => array( 'verlichte-sfeerbus-van-buiten', 'vrouw-danst-en-proost-in-sfeerbus', 'gasten-lachen-aan-tafel-in-sfeerbus' ),
		'hero_wissel' => array( 'voor een rondvaart', 'voor karaoke in een ballenbak', 'voor een dinnershow', 'naar het Leidseplein' ),
		'intro_kop' => 'Op pad naar *Amsterdam*',
		'intro' => "In Amsterdam is altijd wat te doen. Je vaart met je eigen boot door de grachten, zingt in een ballenbak, gooit met bijlen of eet tijdens een show vol dans en cabaret. Wij rijden je groep ernaartoe, vanuit heel Nederland. Het feest begint al onderweg in de sfeerbus, met jullie muziek, koude drankjes, de chauffeur en het gastvrouwke.\n\nGeen gedoe met parkeren of treinen in de drukke stad: wij zetten jullie af bij je eerste stop en op het afgesproken tijdstip brengen we iedereen weer veilig thuis.",
		'route' => array(
			array( 'tijd' => '12:00', 'icoon' => 'huis', 'titel' => 'Ophalen', 'tekst' => 'We halen je op in je eigen woonplaats, ook op meerdere opstapplekken. In de bus wordt er met een eerste drankje geproost.' ),
			array( 'tijd' => '14:00', 'icoon' => 'bus', 'titel' => 'De grachten op', 'tekst' => 'Een eigen borrelboot met muziek en drankjes, bijvoorbeeld bij Flagship of Mokumboot.' ),
			array( 'tijd' => '16:30', 'icoon' => 'glas', 'titel' => 'Games', 'tekst' => 'Karaoke, darts en shuffleboard bij The All Out, of bijlwerpen bij The Axe Effect.' ),
			array( 'tijd' => '19:00', 'icoon' => 'bestek', 'titel' => 'Eten', 'tekst' => 'Taco\'s en margarita\'s, of een diner met show bij het Paleis van de Weemoed.' ),
			array( 'tijd' => '22:00', 'icoon' => 'muziek', 'titel' => 'Uitgaan', 'tekst' => 'Door naar het Leidseplein of het Rembrandtplein. De bus komt op een afgesproken tijd terug.' ),
			array( 'tijd' => '01:00', 'icoon' => 'maan', 'titel' => 'Thuisbrengen', 'tekst' => 'Iedereen veilig en tegelijk naar huis, met de laatste meezinger in de bus.' ),
		),
		'tips_intro' => 'Uitjes waar je met een groep goed terecht kunt. Combineer er een of twee met eten en uitgaan, dan is je dag compleet.',
		'tips' => array(
			array( 'titel' => 'Rondvaart met je groep', 'soort' => 'Op het water', 'foto' => array( 'commons', 'File:Canal boats in Amsterdam 01.jpg', 'amsterdam-grachten-boten', 'Boten op de grachten van Amsterdam' ), 'tekst' => 'Huur een boot en ontdek de Amsterdamse grachten met je groep. Muziek, drankjes en gezelligheid aan boord: maak er een echte borrelboot van. Kijk bijvoorbeeld bij Flagship Amsterdam of Mokumboot.', 'info' => 'Met schipper aan boord', 'link' => array( 'https://www.flagshipamsterdam.com/' ), 'linktekst' => 'Bekijk Flagship' ),
			array( 'titel' => 'Aloha', 'soort' => 'Indoor fun', 'foto' => array(), 'reserve' => 'gasten-delen-shotjes-uit-in-de-bus', 'tekst' => 'Bowlen, lasergamen, minigolf, karaoke en VR onder één dak. Je kunt hier zelfs een VIP-ruimte huren met eigen muziek en drankjes.', 'info' => 'Indoor fun en food', 'link' => array( 'https://www.aloha.nl/' ), 'linktekst' => 'Bekijk Aloha' ),
			array( 'titel' => 'Amsterdam Catacombs', 'soort' => 'Escape room', 'foto' => array(), 'reserve' => 'feest-met-blauw-licht-in-de-sfeerbus', 'tekst' => 'Geen gewone escape room, maar een spannende ervaring met acteurs, donkere ruimtes en verrassende effecten.', 'info' => 'Met acteurs', 'link' => array( 'https://www.amsterdamcatacombs.com/' ), 'linktekst' => 'Bekijk de Catacombs' ),
			array( 'titel' => 'The Upside Down', 'soort' => 'Interactief museum', 'foto' => array(), 'reserve' => 'tropisch-themafeest-in-de-sfeerbus', 'tekst' => 'Een interactief museum vol bijzondere kamers, optische illusies en fotomomenten. Hier ben je zelf het kunstwerk.', 'info' => 'Europaboulevard 5', 'link' => array( array( 'https://www.the-upsidedown.com/', 'Upside' ), array( 'https://the-upsidedown.com/', 'Upside' ) ), 'linktekst' => 'Bekijk The Upside Down' ),
			array( 'titel' => 'UP Events', 'soort' => 'Outdoor', 'foto' => array(), 'reserve' => 'discolicht-en-feest-in-de-sfeerbus', 'tekst' => 'Trek een overall aan, vorm teams en ga de strijd met elkaar aan. Er zijn nog veel meer outdoor activiteiten voor groepen.', 'info' => 'Voor kleine en grote groepen', 'link' => array( 'https://www.upevents.nl/' ), 'linktekst' => 'Bekijk UP Events' ),
			array( 'titel' => 'Karaoke en games bij The All Out', 'soort' => 'Karaoke en games', 'foto' => array(), 'reserve' => 'meezingen-met-microfoon-in-sfeerbus', 'tekst' => 'Echt een aanrader: privé karaokebooths, interactieve darts, shuffleboards, sjoelen en zelfs karaoke in een ballenbak.', 'info' => 'Bij de RAI', 'link' => array( 'https://www.theallout.com/' ), 'linktekst' => 'Bekijk The All Out' ),
			array( 'titel' => 'Bijlwerpen bij The Axe Effect', 'soort' => 'Bijlwerpen', 'foto' => array(), 'reserve' => 'drankjes-halen-aan-de-bar-in-de-bus', 'tekst' => 'Wie gooit het vaakst raak? Een stoere activiteit waarbij je elkaar flink kunt uitdagen.', 'info' => 'Met instructie vooraf', 'link' => array( 'https://www.theaxeeffect.nl/' ), 'linktekst' => 'Bekijk The Axe Effect' ),
			array( 'titel' => 'Coqtales', 'soort' => 'Show', 'foto' => array(), 'reserve' => 'vrouw-danst-en-proost-in-sfeerbus', 'tekst' => 'Een avond vol mannelijke dansers, entertainment en een flinke dosis humor. Een leuke verrassing voor de aanstaande bruid.', 'info' => 'Voor vrijgezellenfeesten', 'link' => array( 'https://www.coqtales.com/' ), 'linktekst' => 'Bekijk Coqtales' ),
			array( 'titel' => 'Paleis van de Weemoed', 'soort' => 'Dinnershow', 'foto' => array(), 'reserve' => 'vriendengroep-aan-tafel-in-sfeerbus', 'tekst' => 'Combineer je diner met een spectaculaire show vol dans, zang, cabaret en burlesque. Een bijzondere avond uit.', 'info' => 'Oudezijds Voorburgwal 15', 'link' => array( array( 'https://www.paleis-van-de-weemoed.nl/', 'Weemoed' ), array( 'https://paleis-van-de-weemoed.nl/', 'Weemoed' ) ), 'linktekst' => 'Bekijk het Paleis' ),
		),
		'band' => array( 'Grachten', 'Leidseplein', 'Rembrandtplein', 'De Pijp', 'The All Out', 'Aloha', 'Paleis van de Weemoed' ),
		'gebieden' => array(
			array(
				'eyebrow' => 'Eten en borrelen', 'kop' => 'Taco\'s en *cocktails*',
				'tekst' => "Na een middag vol activiteiten is het tijd voor eten. Deze twee plekken zijn levendig en gezellig met een groep, en de avond gaat er zomaar over in een feestje.",
				'plekken' => array(
					array( 'naam' => 'Tacos & Tequila', 'soort' => 'Mexicaans', 'tekst' => 'Een gezellige Mexicaanse hotspot met taco\'s, loaded nacho\'s en margarita\'s.', 'adres' => '', 'link' => array( 'https://www.tacosentequila.nl/' ) ),
					array( 'naam' => 'Calle Ocho', 'soort' => 'Latin American bar', 'tekst' => 'Nacho\'s en cocktails in een levendige bar in De Pijp. De avond kan hier zomaar overgaan in een feestje.', 'adres' => 'De Pijp', 'link' => array( 'https://www.calleocho.nl/' ) ),
				),
			),
			array(
				'eyebrow' => 'Uitgaan', 'kop' => 'Leidseplein en *Rembrandtplein*',
				'tekst' => "Twee bruisende uitgaansgebieden met cafés, bars, clubs en muziek. Op het Leidseplein en het Rembrandtplein loop je met je groep zo van de ene kroeg naar de andere.\n\nHet centrum van Amsterdam is druk en grotendeels autoluw. Wij spreken vooraf een vaste plek af om jullie af te zetten en weer op te halen, zodat niemand na afloop hoeft te zoeken.",
				'plekken' => array(),
			),
		),
		'faq' => array_merge(
			array(
				array( 'Rijden jullie ook helemaal naar Amsterdam?', 'Zeker. We rijden door heel Nederland en daarbuiten. Met muziek, drankjes en het gastvrouwke aan boord begint jullie dagje Amsterdam al zodra iedereen is ingestapt.' ),
				array( 'Waar zetten jullie ons af in Amsterdam?', 'Zo dicht mogelijk bij je eerste stop. In de binnenstad kan een bus niet overal komen, dus daar spreken we vooraf een plek af op een paar minuten lopen. Daar halen we jullie ook weer op.' ),
				array( 'Kunnen we in Amsterdam meerdere stops maken?', 'Zeker, daar is een busje voor. Eerst een rondvaart, dan karaoke en daarna een dinnershow? Zet de stops in je aanvraag, dan nemen we ze mee in de route en de offerte.' ),
			),
			thema_locatie_vaste_vragen( 'Amsterdam' )
		),
	);
}

function thema_locatie_den_bosch() {
	return array(
		'plaats' => 'Den Bosch', 'slug' => 'den-bosch', 'volgorde' => 5,
		'seo_titel' => 'Met de partybus naar Den Bosch | Lekker Geel Busje',
		'seo_omschrijving' => 'Met je groep naar Den Bosch in de sfeerbus: varen over de Binnendieze, speciaalbier op de Tramkade en borrelen op de Korte Putstraat. Binnen 1 werkdag een prijs.',
		'hero_fotos' => array( 'gele-sfeerbus-met-chauffeur-huren', 'feest-met-armen-omhoog-in-de-bus', 'gasten-lachen-aan-tafel-in-sfeerbus' ),
		'hero_wissel' => array( 'voor een tocht over de Binnendieze', 'voor speciaalbier op de Tramkade', 'voor een avond op de Korte Put', 'voor Boozy Bingo' ),
		'intro_kop' => 'Op pad naar *Den Bosch*',
		'intro' => "Den Bosch is Bourgondisch tot in de kleinste straatjes. Je vaart onder de stad door over de Binnendieze, proeft speciaalbier in een oude silo op de Tramkade en eindigt op de Korte Putstraat of in de Karrenstraat. Wij rijden je groep ernaartoe, vanuit heel Nederland. Het feest begint al onderweg in de sfeerbus, met jullie muziek, koude drankjes, de chauffeur en het gastvrouwke.\n\nIn Den Bosch zetten we je af bij je eerste stop en op het afgesproken tijdstip brengen we iedereen weer veilig thuis.",
		'route' => array(
			array( 'tijd' => '13:00', 'icoon' => 'huis', 'titel' => 'Ophalen', 'tekst' => 'We halen je op in je eigen woonplaats, ook op meerdere opstapplekken. In de bus wordt er met een eerste drankje geproost.' ),
			array( 'tijd' => '14:00', 'icoon' => 'bus', 'titel' => 'Over de Binnendieze', 'tekst' => 'Met een bootje onder de stad door, bij de Zoete Lieve Gerrit of met een eigen loungesloep.' ),
			array( 'tijd' => '16:00', 'icoon' => 'glas', 'titel' => 'Speciaalbier', 'tekst' => 'Een bierproeverij bij Jongens van De Wit op de stoere Tramkade.' ),
			array( 'tijd' => '18:30', 'icoon' => 'bestek', 'titel' => 'Eten op de Korte Put', 'tekst' => 'Dé horecastraat van Den Bosch, met restaurants, wijnbars en terrassen.' ),
			array( 'tijd' => '21:00', 'icoon' => 'muziek', 'titel' => 'Karrenstraat', 'tekst' => 'Door naar de borrelcafés en feestcafés. De bus komt op een afgesproken tijd terug.' ),
			array( 'tijd' => '01:00', 'icoon' => 'maan', 'titel' => 'Thuisbrengen', 'tekst' => 'Iedereen veilig en tegelijk naar huis, met de laatste meezinger in de bus.' ),
		),
		'tips_intro' => 'Uitjes waar je met een groep goed terecht kunt. Combineer er een of twee met eten en een avond in de Karrenstraat, dan is je dag compleet.',
		'tips' => array(
			array( 'titel' => 'Varen over de Binnendieze', 'soort' => 'Op het water', 'foto' => array( 'commons', 'File:Binnendieze in Uilenburg.jpg', 'den-bosch-binnendieze-uilenburg', 'De Binnendieze bij de Uilenburg in Den Bosch' ), 'tekst' => 'Stap in een bootje en vaar door de oude watergangen, deels onder de stad door. Dit kun je ook combineren met lunch of diner aan boord. Of boek zelf een loungesloep bij Zelf Varen.', 'info' => 'Met lunch of diner aan boord', 'link' => array( 'https://www.zoetelievegerrit.nl/' ), 'linktekst' => 'Bekijk de Zoete Lieve Gerrit' ),
			array( 'titel' => 'The Madhouse', 'soort' => 'Games en borrel', 'foto' => array(), 'reserve' => 'feest-met-blauw-licht-in-de-sfeerbus', 'tekst' => 'Meer dan zeven activiteiten onder één dak. Denk aan Formule 1 simracen, interactieve darts, Crazy Pool, shuffleboard, curling en Pixel Play.', 'info' => 'Indoor entertainment', 'link' => array( 'https://www.themadhouse.nl/den-bosch' ), 'linktekst' => 'Bekijk The Madhouse' ),
			array( 'titel' => 'Escape Xperience', 'soort' => 'Spel in de stad', 'foto' => array( 'commons', "File:Markt 's-Hertogenbosch 2.jpg", 'den-bosch-markt', 'De Markt in Den Bosch' ), 'tekst' => 'Het Mysterie van Jeroen Bosch: los samen raadsels op en ontdek ondertussen de bijzondere plekjes van Den Bosch.', 'info' => 'Start wanneer het jullie uitkomt', 'link' => array( array( 'https://www.escape-xperience.nl/', 'Xperience' ), array( 'https://escape-xperience.nl/', 'Xperience' ) ), 'linktekst' => 'Bekijk Escape Xperience' ),
			array( 'titel' => 'Speciaalbier op de Tramkade', 'soort' => 'Bierproeverij', 'foto' => array(), 'reserve' => 'drankjes-halen-aan-de-bar-in-de-bus', 'tekst' => 'Een stoere, industriële plek in een oude silo. Proef lokaal gebrouwen speciaalbier bij Jongens van De Wit en geniet van de bijzondere sfeer van de Tramkade. Leuk voor bierliefhebbers of een gezellige mannenmiddag.', 'info' => 'Tramkade', 'link' => array( 'https://www.jongensvandewit.nl/' ), 'linktekst' => 'Bekijk Jongens van De Wit' ),
			array( 'titel' => 'Boozy Bingo', 'soort' => 'Bingo en cocktails', 'foto' => array(), 'reserve' => 'tropisch-themafeest-in-de-sfeerbus', 'tekst' => 'Bingo, cocktails en chaos. Vergeet de standaard bingo: denk aan dragshows, shared brunches en hilarische prijzen.', 'info' => 'Op wisselende data', 'link' => array( 'https://www.boozybingo.nl/' ), 'linktekst' => 'Bekijk Boozy Bingo' ),
			array( 'titel' => '\'t Opkikkertje', 'soort' => 'Diner en puzzels', 'foto' => array(), 'reserve' => 'vriendengroep-aan-tafel-in-sfeerbus', 'tekst' => 'Een driegangendiner met puzzels die je eerst moet oplossen voordat de volgende gang op tafel komt. Ook voor karaokeavonden, live muziek en een pubquiz.', 'info' => 'Diner met spel', 'link' => array( array( 'https://www.opkikkertje.nl/', 'kikkertje' ) ), 'linktekst' => 'Bekijk \'t Opkikkertje' ),
			array( 'titel' => 'Whiskyproeverij bij Barrique', 'soort' => 'Proeverij', 'foto' => array(), 'reserve' => 'gasten-delen-shotjes-uit-in-de-bus', 'tekst' => 'In de Vughterstraat organiseren ze een whiskyproeverij waarbij je zes verschillende whisky\'s proeft.', 'info' => 'Vughterstraat', 'link' => array( array( 'https://www.barrique.nl/', 'Barrique' ) ), 'linktekst' => 'Bekijk Barrique' ),
			array( 'titel' => 'Brabanthallen', 'soort' => 'Evenementen', 'foto' => array( 'commons', 'File:Brabanthallen hoofdgebouw.jpg', 'den-bosch-brabanthallen', 'Het hoofdgebouw van de Brabanthallen in Den Bosch' ), 'tekst' => 'Er is ieder weekend wel iets te beleven in de Brabanthallen, van beurzen tot grote feesten. Wij zetten je bij de deur af en halen je na afloop weer op.', 'info' => 'Diezekade 2', 'link' => array( 'https://www.brabanthallen.nl/agenda' ), 'linktekst' => 'Bekijk de agenda' ),
			array( 'titel' => 'Circuit Park Berghem', 'soort' => 'Karten', 'foto' => array(), 'reserve' => 'discolicht-en-feest-in-de-sfeerbus', 'tekst' => 'Een outdoor kartcircuit met races en groepsuitjes, ongeveer 19 kilometer van Den Bosch. Goed te combineren met een middag of avond in de stad.', 'info' => 'Berghem', 'link' => array( 'https://www.circuitparkberghem.nl/' ), 'linktekst' => 'Bekijk het circuit' ),
		),
		'band' => array( 'Binnendieze', 'Tramkade', 'Korte Putstraat', 'Karrenstraat', 'The Madhouse', 'Brabanthallen', 'Markt' ),
		'gebieden' => array(
			array(
				'eyebrow' => 'Eten en borrelen', 'kop' => 'De *Korte Putstraat*',
				'tekst' => "Dit is dé horecastraat van Den Bosch. Een straat vol gezellige restaurants, wijnbars en terrassen, waar je met de groep makkelijk een plek vindt om te eten.\n\nIn de Kerkstraat, om de hoek, zit een verborgen cocktailbar die ook een Silent Disco Cocktail Tasting organiseert.",
				'plekken' => array(
					array( 'naam' => 'Secret Bar & Kitchen', 'soort' => 'Cocktailbar', 'tekst' => 'De verborgen cocktailbar in de Kerkstraat. Bij de Silent Disco Cocktail Tasting krijg je een koptelefoon op en ga je tijdens het proeven van drie cocktails op zoek naar de ingrediënten.', 'adres' => 'Kerkstraat 42', 'link' => array( 'https://secretbar.nl/' ) ),
				),
			),
			array(
				'eyebrow' => 'Stappen', 'kop' => 'De *Karrenstraat*',
				'tekst' => "Verschillende gezellige borrelcafés naast elkaar. Hier eindigt een dagje Den Bosch vaak: eerst een borrel, later nog even dansen.\n\nHandig met een groep: alles ligt op loopafstand van elkaar. Wij zetten jullie in de buurt af en spreken vooraf een vaste ophaalplek af.",
				'plekken' => array(
					array( 'naam' => 'Borrelbar', 'soort' => 'Borrelcafé', 'tekst' => 'Een gezellige plek om de avond te beginnen met een drankje.', 'adres' => 'Karrenstraat' ),
					array( 'naam' => 'Borrelcafé Jij & Ik', 'soort' => 'Borrelcafé', 'tekst' => 'Gezellig borrelen met de hele groep.', 'adres' => 'Karrenstraat' ),
					array( 'naam' => 'Tante Ted', 'soort' => 'Feestcafé', 'tekst' => 'Voor wie na de borrel nog wil feesten.', 'adres' => 'Karrenstraat' ),
					array( 'naam' => 'Zomers & Zomers', 'soort' => 'Café', 'tekst' => 'Nog een gezellig café in dezelfde straat, voor de laatste ronde.', 'adres' => 'Karrenstraat' ),
				),
			),
		),
		'faq' => array_merge(
			array(
				array( 'Waar zetten jullie ons af in Den Bosch?', 'Zo dicht mogelijk bij je eerste stop. De binnenstad rond de Markt is grotendeels voetgangersgebied, dus daar spreken we vooraf een plek af op een paar minuten lopen. Daar halen we jullie ook weer op.' ),
				array( 'Kunnen we in Den Bosch meerdere stops maken?', 'Zeker, daar is een busje voor. Eerst varen over de Binnendieze, dan speciaalbier op de Tramkade en daarna door naar de Korte Putstraat? Zet de stops in je aanvraag, dan nemen we ze mee in de route en de offerte.' ),
				array( 'Rijden jullie ook naar een evenement in de Brabanthallen?', 'Ja. We zetten je groep bij de ingang af en spreken een vaste plek en tijd af om jullie na afloop weer op te halen. Bij grote evenementen is het druk, dus vraag ruim op tijd een offerte aan.' ),
			),
			thema_locatie_vaste_vragen( 'Den Bosch' )
		),
	);
}

/**
 * Uitroltaak locaties-v1: de vier plaatspagina's aanmaken of bijwerken.
 */
function thema_plaats_locaties() {
	if ( function_exists( 'set_time_limit' ) ) {
		set_time_limit( 0 );
	}
	$uit = array();
	foreach ( array( 'thema_locatie_maastricht', 'thema_locatie_rotterdam', 'thema_locatie_amsterdam', 'thema_locatie_den_bosch' ) as $f ) {
		$c                  = $f();
		$uit[ $c['slug'] ] = thema_locatie_pagina( $c );
	}
	return $uit;
}
