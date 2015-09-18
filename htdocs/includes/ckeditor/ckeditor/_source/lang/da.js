/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

/**
 * @fileOverview Defines the {@link CKEDITOR.lang} object, for the
 * Danish language.
 */

/**#@+
   @type String
   @example
*/

/**
 * Contains the dictionary of language entries.
 * @namespace
 */
CKEDITOR.lang[ 'da' ] = {
	// ARIA description.
	editor: 'Rich Text Editor',
	editorPanel: 'Rich Text Editor panel', // MISSING

	// Common messages and labels.
	common: {
		// Screenreader titles. Please note that screenreaders are not always capable
		// of reading non-English words. So be careful while translating it.
		editorHelp: 'Tryk ALT 0 for hjælp',

		browseServer: 'Gennemse...',
		url: 'URL',
		protocol: 'Protokol',
		upload: 'Upload',
		uploadSubmit: 'Upload',
		image: 'Indsæt billede',
		flash: 'Indsæt Flash',
		form: 'Indsæt formular',
		checkbox: 'Indsæt afkrydsningsfelt',
		radio: 'Indsæt alternativknap',
		textField: 'Indsæt tekstfelt',
		textarea: 'Indsæt tekstboks',
		hiddenField: 'Indsæt skjult felt',
		button: 'Indsæt knap',
		select: 'Indsæt liste',
		imageButton: 'Indsæt billedknap',
		notSet: '<intet valgt>',
		id: 'Id',
		name: 'Navn',
		langDir: 'Tekstretning',
		langDirLtr: 'Fra venstre mod højre (LTR)',
		langDirRtl: 'Fra højre mod venstre (RTL)',
		langCode: 'Sprogkode',
		longDescr: 'Udvidet beskrivelse',
		cssClass: 'Typografiark (CSS)',
		advisoryTitle: 'Titel',
		cssStyle: 'Typografi (CSS)',
		ok: 'OK',
		cancel: 'Annullér',
		close: 'Luk',
		preview: 'Forhåndsvisning',
		resize: 'Træk for at skalere',
		generalTab: 'Generelt',
		advancedTab: 'Avanceret',
		validateNumberFailed: 'Værdien er ikke et tal.',
		confirmNewPage: 'Alt indhold, der ikke er blevet gemt, vil gå tabt. Er du sikker på, at du vil indlæse en ny side?',
		confirmCancel: 'Nogle af indstillingerne er blevet ændret. Er du sikker på, at du vil lukke vinduet?',
		options: 'Vis muligheder',
		target: 'Mål',
		targetNew: 'Nyt vindue (_blank)',
		targetTop: 'Øverste vindue (_top)',
		targetSelf: 'Samme vindue (_self)',
		targetParent: 'Samme vindue (_parent)',
		langDirLTR: 'Venstre til højre (LTR)',
		langDirRTL: 'Højre til venstre (RTL)',
		styles: 'Style',
		cssClasses: 'Stylesheetklasser',
		width: 'Bredde',
		height: 'Højde',
		align: 'Justering',
		alignLeft: 'Venstre',
		alignRight: 'Højre',
		alignCenter: 'Centreret',
		alignTop: 'Øverst',
		alignMiddle: 'Centreret',
		alignBottom: 'Nederst',
		invalidValue	: 'Invalid value.', // MISSING
		invalidHeight: 'Højde skal være et tal.',
		invalidWidth: 'Bredde skal være et tal.',
		invalidCssLength: 'Værdien specificeret for "%1" feltet skal være et positivt nummer med eller uden en CSS måleenhed  (px, %, in, cm, mm, em, ex, pt, eller pc).',
		invalidHtmlLength: 'Værdien specificeret for "%1" feltet skal være et positivt nummer med eller uden en CSS måleenhed  (px eller %).',
		invalidInlineStyle: 'Værdien specificeret for inline style skal indeholde en eller flere elementer med et format som "name:value", separeret af semikoloner',
		cssLengthTooltip: 'Indsæt en numerisk værdi i pixel eller nummer med en gyldig CSS værdi (px, %, in, cm, mm, em, ex, pt, eller pc).',

		// Put the voice-only part of the label in the span.
		unavailable: '%1<span class="cke_accessibility">, ikke tilgængelig</span>'
	}
};
