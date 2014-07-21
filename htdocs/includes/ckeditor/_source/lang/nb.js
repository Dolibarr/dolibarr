/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

/**
 * @fileOverview Defines the {@link CKEDITOR.lang} object, for the
 * Norwegian Bokmål language.
 */

/**#@+
   @type String
   @example
*/

/**
 * Contains the dictionary of language entries.
 * @namespace
 */
CKEDITOR.lang[ 'nb' ] = {
	// ARIA description.
	editor: 'Rikteksteditor',
	editorPanel: 'Panel for rikteksteditor',

	// Common messages and labels.
	common: {
		// Screenreader titles. Please note that screenreaders are not always capable
		// of reading non-English words. So be careful while translating it.
		editorHelp: 'Trykk ALT 0 for hjelp',

		browseServer: 'Bla igjennom server',
		url: 'URL',
		protocol: 'Protokoll',
		upload: 'Last opp',
		uploadSubmit: 'Send det til serveren',
		image: 'Bilde',
		flash: 'Flash',
		form: 'Skjema',
		checkbox: 'Avmerkingsboks',
		radio: 'Alternativknapp',
		textField: 'Tekstboks',
		textarea: 'Tekstområde',
		hiddenField: 'Skjult felt',
		button: 'Knapp',
		select: 'Rullegardinliste',
		imageButton: 'Bildeknapp',
		notSet: '<ikke satt>',
		id: 'Id',
		name: 'Navn',
		langDir: 'Språkretning',
		langDirLtr: 'Venstre til høyre (VTH)',
		langDirRtl: 'Høyre til venstre (HTV)',
		langCode: 'Språkkode',
		longDescr: 'Utvidet beskrivelse',
		cssClass: 'Stilarkklasser',
		advisoryTitle: 'Tittel',
		cssStyle: 'Stil',
		ok: 'OK',
		cancel: 'Avbryt',
		close: 'Lukk',
		preview: 'Forhåndsvis',
		resize: 'Dra for å skalere',
		generalTab: 'Generelt',
		advancedTab: 'Avansert',
		validateNumberFailed: 'Denne verdien er ikke et tall.',
		confirmNewPage: 'Alle ulagrede endringer som er gjort i dette innholdet vil bli tapt. Er du sikker på at du vil laste en ny side?',
		confirmCancel: 'Noen av valgene har blitt endret. Er du sikker på at du vil lukke dialogen?',
		options: 'Valg',
		target: 'Mål',
		targetNew: 'Nytt vindu (_blank)',
		targetTop: 'Hele vindu (_top)',
		targetSelf: 'Samme vindu (_self)',
		targetParent: 'Foreldrevindu (_parent)',
		langDirLTR: 'Venstre til høyre (VTH)',
		langDirRTL: 'Høyre til venstre (HTV)',
		styles: 'Stil',
		cssClasses: 'Stilarkklasser',
		width: 'Bredde',
		height: 'Høyde',
		align: 'Juster',
		alignLeft: 'Venstre',
		alignRight: 'Høyre',
		alignCenter: 'Midtjuster',
		alignTop: 'Topp',
		alignMiddle: 'Midten',
		alignBottom: 'Bunn',
		invalidValue	: 'Ugyldig verdi.',
		invalidHeight: 'Høyde må være et tall.',
		invalidWidth: 'Bredde må være et tall.',
		invalidCssLength: 'Den angitte verdien for feltet "%1" må være et positivt tall med eller uten en gyldig CSS-målingsenhet (px, %, in, cm, mm, em, ex, pt, eller pc).',
		invalidHtmlLength: 'Den angitte verdien for feltet "%1" må være et positivt tall med eller uten en gyldig HTML-målingsenhet (px eller %).',
		invalidInlineStyle: 'Verdi angitt for inline stil må bestå av en eller flere sett med formatet "navn : verdi", separert med semikolon',
		cssLengthTooltip: 'Skriv inn et tall for en piksel-verdi eller et tall med en gyldig CSS-enhet (px, %, in, cm, mm, em, ex, pt, eller pc).',

		// Put the voice-only part of the label in the span.
		unavailable: '%1<span class="cke_accessibility">, utilgjenglig</span>'
	}
};
