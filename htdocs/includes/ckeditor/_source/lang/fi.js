/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

/**
 * @fileOverview Defines the {@link CKEDITOR.lang} object for the
 * Finnish language.
 */

/**#@+
   @type String
   @example
*/

/**
 * Contains the dictionary of language entries.
 * @namespace
 */
CKEDITOR.lang[ 'fi' ] = {
	// ARIA description.
	editor: 'Rikastekstieditori',
	editorPanel: 'Rikastekstieditoripaneeli',

	// Common messages and labels.
	common: {
		// Screenreader titles. Please note that screenreaders are not always capable
		// of reading non-English words. So be careful while translating it.
		editorHelp: 'Paina ALT 0 nähdäksesi ohjeen',

		browseServer: 'Selaa palvelinta',
		url: 'Osoite',
		protocol: 'Protokolla',
		upload: 'Lisää tiedosto',
		uploadSubmit: 'Lähetä palvelimelle',
		image: 'Kuva',
		flash: 'Flash-animaatio',
		form: 'Lomake',
		checkbox: 'Valintaruutu',
		radio: 'Radiopainike',
		textField: 'Tekstikenttä',
		textarea: 'Tekstilaatikko',
		hiddenField: 'Piilokenttä',
		button: 'Painike',
		select: 'Valintakenttä',
		imageButton: 'Kuvapainike',
		notSet: '<ei asetettu>',
		id: 'Tunniste',
		name: 'Nimi',
		langDir: 'Kielen suunta',
		langDirLtr: 'Vasemmalta oikealle (LTR)',
		langDirRtl: 'Oikealta vasemmalle (RTL)',
		langCode: 'Kielikoodi',
		longDescr: 'Pitkän kuvauksen URL',
		cssClass: 'Tyyliluokat',
		advisoryTitle: 'Avustava otsikko',
		cssStyle: 'Tyyli',
		ok: 'OK',
		cancel: 'Peruuta',
		close: 'Sulje',
		preview: 'Esikatselu',
		resize: 'Raahaa muuttaaksesi kokoa',
		generalTab: 'Yleinen',
		advancedTab: 'Lisäominaisuudet',
		validateNumberFailed: 'Arvon pitää olla numero.',
		confirmNewPage: 'Kaikki tallentamattomat muutokset tähän sisältöön menetetään. Oletko varma, että haluat ladata uuden sivun?',
		confirmCancel: 'Jotkut asetuksista on muuttuneet. Oletko varma, että haluat sulkea valintaikkunan?',
		options: 'Asetukset',
		target: 'Kohde',
		targetNew: 'Uusi ikkuna (_blank)',
		targetTop: 'Päällimmäinen ikkuna (_top)',
		targetSelf: 'Sama ikkuna (_self)',
		targetParent: 'Ylemmän tason ikkuna (_parent)',
		langDirLTR: 'Vasemmalta oikealle (LTR)',
		langDirRTL: 'Oikealta vasemmalle (RTL)',
		styles: 'Tyyli',
		cssClasses: 'Tyylitiedoston luokat',
		width: 'Leveys',
		height: 'Korkeus',
		align: 'Kohdistus',
		alignLeft: 'Vasemmalle',
		alignRight: 'Oikealle',
		alignCenter: 'Keskelle',
		alignTop: 'Ylös',
		alignMiddle: 'Keskelle',
		alignBottom: 'Alas',
		invalidValue	: 'Virheellinen arvo.',
		invalidHeight: 'Korkeuden täytyy olla numero.',
		invalidWidth: 'Leveyden täytyy olla numero.',
		invalidCssLength: 'Kentän "%1" arvon täytyy olla positiivinen luku CSS mittayksikön (px, %, in, cm, mm, em, ex, pt tai pc) kanssa tai ilman.',
		invalidHtmlLength: 'Kentän "%1" arvon täytyy olla positiivinen luku HTML mittayksikön (px tai %) kanssa tai ilman.',
		invalidInlineStyle: 'Tyylille annetun arvon täytyy koostua yhdestä tai useammasta "nimi : arvo" parista, jotka ovat eroteltuna toisistaan puolipisteillä.',
		cssLengthTooltip: 'Anna numeroarvo pikseleinä tai numeroarvo CSS mittayksikön kanssa (px, %, in, cm, mm, em, ex, pt, tai pc).',

		// Put the voice-only part of the label in the span.
		unavailable: '%1<span class="cke_accessibility">, ei saatavissa</span>'
	}
};
