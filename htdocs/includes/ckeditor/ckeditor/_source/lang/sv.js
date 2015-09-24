/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

/**
* @fileOverview
*/

/**#@+
   @type String
   @example
*/

/**
 * Contains the dictionary of language entries.
 * @namespace
 */
CKEDITOR.lang[ 'sv' ] = {
	// ARIA description.
	editor: 'Rich Text Editor',
	editorPanel: 'Rich Text Editor panel',

	// Common messages and labels.
	common: {
		// Screenreader titles. Please note that screenreaders are not always capable
		// of reading non-English words. So be careful while translating it.
		editorHelp: 'Tryck ALT 0 för hjälp',

		browseServer: 'Bläddra på server',
		url: 'URL',
		protocol: 'Protokoll',
		upload: 'Ladda upp',
		uploadSubmit: 'Skicka till server',
		image: 'Bild',
		flash: 'Flash',
		form: 'Formulär',
		checkbox: 'Kryssruta',
		radio: 'Alternativknapp',
		textField: 'Textfält',
		textarea: 'Textruta',
		hiddenField: 'Dolt fält',
		button: 'Knapp',
		select: 'Flervalslista',
		imageButton: 'Bildknapp',
		notSet: '<ej angivet>',
		id: 'Id',
		name: 'Namn',
		langDir: 'Språkriktning',
		langDirLtr: 'Vänster till Höger (VTH)',
		langDirRtl: 'Höger till Vänster (HTV)',
		langCode: 'Språkkod',
		longDescr: 'URL-beskrivning',
		cssClass: 'Stilmall',
		advisoryTitle: 'Titel',
		cssStyle: 'Stilmall',
		ok: 'OK',
		cancel: 'Avbryt',
		close: 'Stäng',
		preview: 'Förhandsgranska',
		resize: 'Dra för att ändra storlek',
		generalTab: 'Allmänt',
		advancedTab: 'Avancerad',
		validateNumberFailed: 'Värdet är inte ett nummer.',
		confirmNewPage: 'Alla ändringar i innehållet kommer att förloras. Är du säker på att du vill ladda en ny sida?',
		confirmCancel: 'Några av alternativen har ändrats. Är du säker på att du vill stänga dialogrutan?',
		options: 'Alternativ',
		target: 'Mål',
		targetNew: 'Nytt fönster (_blank)',
		targetTop: 'Översta fönstret (_top)',
		targetSelf: 'Samma fönster (_self)',
		targetParent: 'Föregående fönster (_parent)',
		langDirLTR: 'Vänster till höger (LTR)',
		langDirRTL: 'Höger till vänster (RTL)',
		styles: 'Stil',
		cssClasses: 'Stilmallar',
		width: 'Bredd',
		height: 'Höjd',
		align: 'Justering',
		alignLeft: 'Vänster',
		alignRight: 'Höger',
		alignCenter: 'Centrerad',
		alignTop: 'Överkant',
		alignMiddle: 'Mitten',
		alignBottom: 'Nederkant',
		invalidValue	: 'Felaktigt värde.',
		invalidHeight: 'Höjd måste vara ett nummer.',
		invalidWidth: 'Bredd måste vara ett nummer.',
		invalidCssLength: 'Värdet för fältet "%1" måste vara ett positivt nummer med eller utan CSS-mätenheter (px, %, in, cm, mm, em, ex, pt, eller pc).',
		invalidHtmlLength: 'Värdet för fältet "%1" måste vara ett positivt nummer med eller utan godkända HTML-mätenheter (px eller %).',
		invalidInlineStyle: 'Det angivna värdet för style måste innehålla en eller flera tupler separerade med semikolon i följande format: "name : value"',
		cssLengthTooltip: 'Ange ett nummer i pixlar eller ett nummer men godkänd CSS-mätenhet (px, %, in, cm, mm, em, ex, pt, eller pc).',

		// Put the voice-only part of the label in the span.
		unavailable: '%1<span class="cke_accessibility">, Ej tillgänglig</span>'
	}
};
