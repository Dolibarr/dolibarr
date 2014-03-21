/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

/**
 * @fileOverview Defines the {@link CKEDITOR.lang} object for the
 * Dutch language.
 */

/**#@+
   @type String
   @example
*/

/**
 * Contains the dictionary of language entries.
 * @namespace
 */
CKEDITOR.lang[ 'nl' ] = {
	// ARIA description.
	editor: 'Tekstverwerker',
	editorPanel: 'Tekstverwerker beheerpaneel',

	// Common messages and labels.
	common: {
		// Screenreader titles. Please note that screenreaders are not always capable
		// of reading non-English words. So be careful while translating it.
		editorHelp: 'Druk ALT 0 voor hulp',

		browseServer: 'Bladeren op server',
		url: 'URL',
		protocol: 'Protocol',
		upload: 'Upload',
		uploadSubmit: 'Naar server verzenden',
		image: 'Afbeelding',
		flash: 'Flash',
		form: 'Formulier',
		checkbox: 'Selectievinkje',
		radio: 'Keuzerondje',
		textField: 'Tekstveld',
		textarea: 'Tekstvak',
		hiddenField: 'Verborgen veld',
		button: 'Knop',
		select: 'Selectieveld',
		imageButton: 'Afbeeldingsknop',
		notSet: '<niet ingevuld>',
		id: 'Id',
		name: 'Naam',
		langDir: 'Schrijfrichting',
		langDirLtr: 'Links naar rechts (LTR)',
		langDirRtl: 'Rechts naar links (RTL)',
		langCode: 'Taalcode',
		longDescr: 'Lange URL-omschrijving',
		cssClass: 'Stylesheet-klassen',
		advisoryTitle: 'Adviserende titel',
		cssStyle: 'Stijl',
		ok: 'OK',
		cancel: 'Annuleren',
		close: 'Sluiten',
		preview: 'Voorbeeld',
		resize: 'Sleep om te herschalen',
		generalTab: 'Algemeen',
		advancedTab: 'Geavanceerd',
		validateNumberFailed: 'Deze waarde is geen geldig getal.',
		confirmNewPage: 'Alle aangebrachte wijzigingen gaan verloren. Weet u zeker dat u een nieuwe pagina wilt openen?',
		confirmCancel: 'Enkele opties zijn gewijzigd. Weet u zeker dat u dit dialoogvenster wilt sluiten?',
		options: 'Opties',
		target: 'Doelvenster',
		targetNew: 'Nieuw venster (_blank)',
		targetTop: 'Hele venster (_top)',
		targetSelf: 'Zelfde venster (_self)',
		targetParent: 'Origineel venster (_parent)',
		langDirLTR: 'Links naar rechts (LTR)',
		langDirRTL: 'Rechts naar links (RTL)',
		styles: 'Stijl',
		cssClasses: 'Stylesheet-klassen',
		width: 'Breedte',
		height: 'Hoogte',
		align: 'Uitlijning',
		alignLeft: 'Links',
		alignRight: 'Rechts',
		alignCenter: 'Centreren',
		alignTop: 'Boven',
		alignMiddle: 'Midden',
		alignBottom: 'Onder',
		invalidValue	: 'Ongeldige waarde.',
		invalidHeight: 'De hoogte moet een getal zijn.',
		invalidWidth: 'De breedte moet een getal zijn.',
		invalidCssLength: 'Waarde in veld "%1" moet een positief nummer zijn, met of zonder een geldige CSS meeteenheid (px, %, in, cm, mm, em, ex, pt of pc).',
		invalidHtmlLength: 'Waarde in veld "%1" moet een positief nummer zijn, met of zonder een geldige HTML meeteenheid (px of %).',
		invalidInlineStyle: 'Waarde voor de online stijl moet bestaan uit een of meerdere tupels met het formaat "naam : waarde", gescheiden door puntkomma\'s.',
		cssLengthTooltip: 'Geef een nummer in voor een waarde in pixels of geef een nummer in met een geldige CSS eenheid (px, %, in, cm, mm, em, ex, pt, of pc).',

		// Put the voice-only part of the label in the span.
		unavailable: '%1<span class="cke_accessibility">, niet beschikbaar</span>'
	}
};
