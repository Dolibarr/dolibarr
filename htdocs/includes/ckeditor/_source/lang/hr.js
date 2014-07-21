/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

/**
 * @fileOverview Defines the {@link CKEDITOR.lang} object, for the
 * Croatian language.
 */

/**#@+
   @type String
   @example
*/

/**
 * Contains the dictionary of language entries.
 * @namespace
 */
CKEDITOR.lang[ 'hr' ] = {
	// ARIA description.
	editor: 'Bogati uređivač teksta',
	editorPanel: 'Rich Text Editor panel', // MISSING

	// Common messages and labels.
	common: {
		// Screenreader titles. Please note that screenreaders are not always capable
		// of reading non-English words. So be careful while translating it.
		editorHelp: 'Pritisni ALT 0 za pomoć',

		browseServer: 'Pretraži server',
		url: 'URL',
		protocol: 'Protokol',
		upload: 'Pošalji',
		uploadSubmit: 'Pošalji na server',
		image: 'Slika',
		flash: 'Flash',
		form: 'Forma',
		checkbox: 'Checkbox',
		radio: 'Radio Button',
		textField: 'Text Field',
		textarea: 'Textarea',
		hiddenField: 'Hidden Field',
		button: 'Button',
		select: 'Selection Field',
		imageButton: 'Image Button',
		notSet: '<nije postavljeno>',
		id: 'Id',
		name: 'Naziv',
		langDir: 'Smjer jezika',
		langDirLtr: 'S lijeva na desno (LTR)',
		langDirRtl: 'S desna na lijevo (RTL)',
		langCode: 'Kôd jezika',
		longDescr: 'Dugački opis URL',
		cssClass: 'Klase stilova',
		advisoryTitle: 'Advisory naslov',
		cssStyle: 'Stil',
		ok: 'OK',
		cancel: 'Poništi',
		close: 'Zatvori',
		preview: 'Pregledaj',
		resize: 'Povuci za promjenu veličine',
		generalTab: 'Općenito',
		advancedTab: 'Napredno',
		validateNumberFailed: 'Ova vrijednost nije broj.',
		confirmNewPage: 'Sve napravljene promjene će biti izgubljene ukoliko ih niste snimili. Sigurno želite učitati novu stranicu?',
		confirmCancel: 'Neke od opcija su promjenjene. Sigurno želite zatvoriti ovaj prozor?',
		options: 'Opcije',
		target: 'Odredište',
		targetNew: 'Novi prozor (_blank)',
		targetTop: 'Vršni prozor (_top)',
		targetSelf: 'Isti prozor (_self)',
		targetParent: 'Roditeljski prozor (_parent)',
		langDirLTR: 'S lijeva na desno (LTR)',
		langDirRTL: 'S desna na lijevo (RTL)',
		styles: 'Stil',
		cssClasses: 'Klase stilova',
		width: 'Širina',
		height: 'Visina',
		align: 'Poravnanje',
		alignLeft: 'Lijevo',
		alignRight: 'Desno',
		alignCenter: 'Središnje',
		alignTop: 'Vrh',
		alignMiddle: 'Sredina',
		alignBottom: 'Dolje',
		invalidValue	: 'Neispravna vrijednost.',
		invalidHeight: 'Visina mora biti broj.',
		invalidWidth: 'Širina mora biti broj.',
		invalidCssLength: 'Vrijednost određena za "%1" polje mora biti pozitivni broj sa ili bez važećih CSS mjernih jedinica (px, %, in, cm, mm, em, ex, pt ili pc).',
		invalidHtmlLength: 'Vrijednost određena za "%1" polje mora biti pozitivni broj sa ili bez važećih HTML mjernih jedinica (px ili %).',
		invalidInlineStyle: 'Vrijednost za linijski stil mora sadržavati jednu ili više definicija s formatom "naziv:vrijednost", odvojenih točka-zarezom.',
		cssLengthTooltip: 'Unesite broj za vrijednost u pikselima ili broj s važećim CSS mjernim jedinicama (px, %, in, cm, mm, em, ex, pt ili pc).',

		// Put the voice-only part of the label in the span.
		unavailable: '%1<span class="cke_accessibility">, nedostupno</span>'
	}
};
