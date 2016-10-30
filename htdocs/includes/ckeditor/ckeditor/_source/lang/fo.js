/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

/**
 * @fileOverview Defines the {@link CKEDITOR.lang} object, for the
 * Faroese language.
 */

/**#@+
   @type String
   @example
*/

/**
 * Contains the dictionary of language entries.
 * @namespace
 */
CKEDITOR.lang[ 'fo' ] = {
	// ARIA description.
	editor: 'Rich Text Editor',
	editorPanel: 'Rich Text Editor panel', // MISSING

	// Common messages and labels.
	common: {
		// Screenreader titles. Please note that screenreaders are not always capable
		// of reading non-English words. So be careful while translating it.
		editorHelp: 'Trýst ALT og 0 fyri vegleiðing',

		browseServer: 'Ambætarakagi',
		url: 'URL',
		protocol: 'Protokoll',
		upload: 'Send til ambætaran',
		uploadSubmit: 'Send til ambætaran',
		image: 'Myndir',
		flash: 'Flash',
		form: 'Formur',
		checkbox: 'Flugubein',
		radio: 'Radioknøttur',
		textField: 'Tekstteigur',
		textarea: 'Tekstumráði',
		hiddenField: 'Fjaldur teigur',
		button: 'Knøttur',
		select: 'Valskrá',
		imageButton: 'Myndaknøttur',
		notSet: '<ikki sett>',
		id: 'Id',
		name: 'Navn',
		langDir: 'Tekstkós',
		langDirLtr: 'Frá vinstru til høgru (LTR)',
		langDirRtl: 'Frá høgru til vinstru (RTL)',
		langCode: 'Málkoda',
		longDescr: 'Víðkað URL frágreiðing',
		cssClass: 'Typografi klassar',
		advisoryTitle: 'Vegleiðandi heiti',
		cssStyle: 'Typografi',
		ok: 'Góðkent',
		cancel: 'Avlýst',
		close: 'Lat aftur',
		preview: 'Frumsýn',
		resize: 'Drag fyri at broyta stødd',
		generalTab: 'Generelt',
		advancedTab: 'Fjølbroytt',
		validateNumberFailed: 'Hetta er ikki eitt tal.',
		confirmNewPage: 'Allar ikki goymdar broytingar í hesum innihaldið hvørva. Skal nýggj síða lesast kortini?',
		confirmCancel: 'Nakrir valmøguleikar eru broyttir. Ert tú vísur í, at dialogurin skal latast aftur?',
		options: 'Options',
		target: 'Target',
		targetNew: 'Nýtt vindeyga (_blank)',
		targetTop: 'Vindeyga ovast (_top)',
		targetSelf: 'Sama vindeyga (_self)',
		targetParent: 'Upphavligt vindeyga (_parent)',
		langDirLTR: 'Frá vinstru til høgru (LTR)',
		langDirRTL: 'Frá høgru til vinstru (RTL)',
		styles: 'Style',
		cssClasses: 'Stylesheet Classes',
		width: 'Breidd',
		height: 'Hædd',
		align: 'Justering',
		alignLeft: 'Vinstra',
		alignRight: 'Høgra',
		alignCenter: 'Miðsett',
		alignTop: 'Ovast',
		alignMiddle: 'Miðja',
		alignBottom: 'Botnur',
		invalidValue	: 'Invalid value.', // MISSING
		invalidHeight: 'Hædd má vera eitt tal.',
		invalidWidth: 'Breidd má vera eitt tal.',
		invalidCssLength: 'Virðið sett í "%1" feltið má vera eitt positivt tal, við ella uttan gyldugum CSS mátieind (px, %, in, cm, mm, em, ex, pt, ella pc).',
		invalidHtmlLength: 'Virðið sett í "%1" feltiðield má vera eitt positivt tal, við ella uttan gyldugum CSS mátieind (px ella %).',
		invalidInlineStyle: 'Virði specifiserað fyri inline style má hava eitt ella fleiri pør (tuples) skrivað sum "name : value", hvørt parið sundurskilt við semi-colon.',
		cssLengthTooltip: 'Skriva eitt tal fyri eitt virði í pixels ella eitt tal við gyldigum CSS eind (px, %, in, cm, mm, em, ex, pt, ella pc).',

		// Put the voice-only part of the label in the span.
		unavailable: '%1<span class="cke_accessibility">, ikki tøkt</span>'
	}
};
