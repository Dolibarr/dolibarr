/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

/**
 * @fileOverview Defines the {@link CKEDITOR.lang} object, for the
 * Afrikaans language.
 */

/**#@+
   @type String
   @example
*/

/**
 * Contains the dictionary of language entries.
 * @namespace
 */
CKEDITOR.lang[ 'af' ] = {
	// ARIA description.
	editor: 'Teksverwerker',
	editorPanel: 'Rich Text Editor panel', // MISSING

	// Common messages and labels.
	common: {
		// Screenreader titles. Please note that screenreaders are not always capable
		// of reading non-English words. So be careful while translating it.
		editorHelp: 'Druk op ALT 0 vir hulp',

		browseServer: 'Blaai op bediener',
		url: 'URL',
		protocol: 'Protokol',
		upload: 'Oplaai',
		uploadSubmit: 'Stuur na bediener',
		image: 'Afbeelding',
		flash: 'Flash',
		form: 'Vorm',
		checkbox: 'Merkhokkie',
		radio: 'Radioknoppie',
		textField: 'Teksveld',
		textarea: 'Teks-area',
		hiddenField: 'Blinde veld',
		button: 'Knop',
		select: 'Keuseveld',
		imageButton: 'Afbeeldingsknop',
		notSet: '<geen instelling>',
		id: 'Id',
		name: 'Naam',
		langDir: 'Skryfrigting',
		langDirLtr: 'Links na regs (LTR)',
		langDirRtl: 'Regs na links (RTL)',
		langCode: 'Taalkode',
		longDescr: 'Lang beskrywing URL',
		cssClass: 'CSS klasse',
		advisoryTitle: 'Aanbevole titel',
		cssStyle: 'Styl',
		ok: 'OK',
		cancel: 'Kanselleer',
		close: 'Sluit',
		preview: 'Voorbeeld',
		resize: 'Sleep om te herskaal',
		generalTab: 'Algemeen',
		advancedTab: 'Gevorderd',
		validateNumberFailed: 'Hierdie waarde is nie \'n getal nie.',
		confirmNewPage: 'Alle wysiginge sal verlore gaan. Is u seker dat u \'n nuwe bladsy wil laai?',
		confirmCancel: 'Sommige opsies is gewysig. Is u seker dat u hierdie dialoogvenster wil sluit?',
		options: 'Opsies',
		target: 'Doel',
		targetNew: 'Nuwe venster (_blank)',
		targetTop: 'Boonste venster (_top)',
		targetSelf: 'Selfde venster (_self)',
		targetParent: 'Oorspronklike venster (_parent)',
		langDirLTR: 'Links na Regs (LTR)',
		langDirRTL: 'Regs na Links (RTL)',
		styles: 'Styl',
		cssClasses: 'CSS klasse',
		width: 'Breedte',
		height: 'Hoogte',
		align: 'Oplyn',
		alignLeft: 'Links',
		alignRight: 'Regs',
		alignCenter: 'Sentreer',
		alignTop: 'Bo',
		alignMiddle: 'Middel',
		alignBottom: 'Onder',
		invalidValue	: 'Invalid value.', // MISSING
		invalidHeight: 'Hoogte moet \'n getal wees',
		invalidWidth: 'Breedte moet \'n getal wees.',
		invalidCssLength: 'Value specified for the "%1" field must be a positive number with or without a valid CSS measurement unit (px, %, in, cm, mm, em, ex, pt, or pc).', // MISSING
		invalidHtmlLength: 'Value specified for the "%1" field must be a positive number with or without a valid HTML measurement unit (px or %).', // MISSING
		invalidInlineStyle: 'Value specified for the inline style must consist of one or more tuples with the format of "name : value", separated by semi-colons.', // MISSING
		cssLengthTooltip: 'Enter a number for a value in pixels or a number with a valid CSS unit (px, %, in, cm, mm, em, ex, pt, or pc).', // MISSING

		// Put the voice-only part of the label in the span.
		unavailable: '%1<span class="cke_accessibility">, nie beskikbaar nie</span>'
	}
};
