/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

/**
 * @fileOverview Defines the {@link CKEDITOR.lang} object, for the
 * Slovenian language.
 */

/**#@+
   @type String
   @example
*/

/**
 * Contains the dictionary of language entries.
 * @namespace
 */
CKEDITOR.lang[ 'sl' ] = {
	// ARIA description.
	editor: 'Bogat Urejevalnik Besedila',
	editorPanel: 'Rich Text Editor plošča',

	// Common messages and labels.
	common: {
		// Screenreader titles. Please note that screenreaders are not always capable
		// of reading non-English words. So be careful while translating it.
		editorHelp: 'Pritisnite ALT 0 za pomoč',

		browseServer: 'Prebrskaj na strežniku',
		url: 'URL',
		protocol: 'Protokol',
		upload: 'Naloži',
		uploadSubmit: 'Pošlji na strežnik',
		image: 'Slika',
		flash: 'Flash',
		form: 'Obrazec',
		checkbox: 'Potrditveno polje',
		radio: 'Izbirno polje',
		textField: 'Vnosno polje',
		textarea: 'Vnosno območje',
		hiddenField: 'Skrito polje',
		button: 'Gumb',
		select: 'Spustno Polje',
		imageButton: 'Slikovni Gumb',
		notSet: '<ni določen>',
		id: 'Id',
		name: 'Ime',
		langDir: 'Smer jezika',
		langDirLtr: 'Od leve proti desni (LTR)',
		langDirRtl: 'Od desne proti levi (RTL)',
		langCode: 'Koda Jezika',
		longDescr: 'Dolg opis URL-ja',
		cssClass: 'Razred stilne predloge',
		advisoryTitle: 'Predlagani naslov',
		cssStyle: 'Slog',
		ok: 'V redu',
		cancel: 'Prekliči',
		close: 'Zapri',
		preview: 'Predogled',
		resize: 'Potegni za spremembo velikosti',
		generalTab: 'Splošno',
		advancedTab: 'Napredno',
		validateNumberFailed: 'Ta vrednost ni število.',
		confirmNewPage: 'Vse neshranjene spremembe te vsebine bodo izgubljene. Ali res želite naložiti novo stran?',
		confirmCancel: 'Nekaj možnosti je bilo spremenjenih. Ali res želite zapreti okno?',
		options: 'Možnosti',
		target: 'Cilj',
		targetNew: 'Novo Okno (_blank)',
		targetTop: 'Vrhovno Okno (_top)',
		targetSelf: 'Enako Okno (_self)',
		targetParent: 'Matično Okno (_parent)',
		langDirLTR: 'Od leve proti desni (LTR)',
		langDirRTL: 'Od desne proti levi (RTL)',
		styles: 'Slog',
		cssClasses: 'Razred stilne predloge',
		width: 'Širina',
		height: 'Višina',
		align: 'Poravnava',
		alignLeft: 'Levo',
		alignRight: 'Desno',
		alignCenter: 'Sredinsko',
		alignTop: 'Na vrh',
		alignMiddle: 'V sredino',
		alignBottom: 'Na dno',
		invalidValue	: 'Neveljavna vrednost.',
		invalidHeight: 'Višina mora biti število.',
		invalidWidth: 'Širina mora biti število.',
		invalidCssLength: 'Vrednost določena za "%1" polje mora biti pozitivna številka z ali brez veljavne CSS enote za merjenje (px, %, in, cm, mm, em, ex, pt, ali pc).',
		invalidHtmlLength: 'Vrednost določena za "%1" polje mora biti pozitivna številka z ali brez veljavne HTML enote za merjenje (px ali %).',
		invalidInlineStyle: 'Vrednost določena za inline slog mora biti sestavljena iz ene ali več tork (tuples) z obliko "ime : vrednost", ločenih z podpičji.',
		cssLengthTooltip: 'Vnesite številko za vrednost v slikovnih pikah (pixels) ali številko z veljavno CSS enoto (px, %, in, cm, mm, em, ex, pt, ali pc).',

		// Put the voice-only part of the label in the span.
		unavailable: '%1<span class="cke_accessibility">, nedosegljiv</span>'
	}
};
