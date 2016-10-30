/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

/**
 * @fileOverview Defines the {@link CKEDITOR.lang} object, for the
 * Estonian language.
 */

/**#@+
   @type String
   @example
*/

/**
 * Contains the dictionary of language entries.
 * @namespace
 */
CKEDITOR.lang[ 'et' ] = {
	// ARIA description.
	editor: 'Rikkalik tekstiredaktor',
	editorPanel: 'Rikkaliku tekstiredaktori paneel',

	// Common messages and labels.
	common: {
		// Screenreader titles. Please note that screenreaders are not always capable
		// of reading non-English words. So be careful while translating it.
		editorHelp: 'Abi saamiseks vajuta ALT 0',

		browseServer: 'Serveri sirvimine',
		url: 'URL',
		protocol: 'Protokoll',
		upload: 'Laadi üles',
		uploadSubmit: 'Saada serverisse',
		image: 'Pilt',
		flash: 'Flash',
		form: 'Vorm',
		checkbox: 'Märkeruut',
		radio: 'Raadionupp',
		textField: 'Tekstilahter',
		textarea: 'Tekstiala',
		hiddenField: 'Varjatud lahter',
		button: 'Nupp',
		select: 'Valiklahter',
		imageButton: 'Piltnupp',
		notSet: '<määramata>',
		id: 'ID',
		name: 'Nimi',
		langDir: 'Keele suund',
		langDirLtr: 'Vasakult paremale (LTR)',
		langDirRtl: 'Paremalt vasakule (RTL)',
		langCode: 'Keele kood',
		longDescr: 'Pikk kirjeldus URL',
		cssClass: 'Stiilistiku klassid',
		advisoryTitle: 'Soovituslik pealkiri',
		cssStyle: 'Laad',
		ok: 'Olgu',
		cancel: 'Loobu',
		close: 'Sulge',
		preview: 'Eelvaade',
		resize: 'Suuruse muutmiseks lohista',
		generalTab: 'Üldine',
		advancedTab: 'Täpsemalt',
		validateNumberFailed: 'See väärtus pole number.',
		confirmNewPage: 'Kõik salvestamata muudatused lähevad kaotsi. Kas oled kindel, et tahad laadida uue lehe?',
		confirmCancel: 'Mõned valikud on muudetud. Kas oled kindel, et tahad dialoogi sulgeda?',
		options: 'Valikud',
		target: 'Sihtkoht',
		targetNew: 'Uus aken (_blank)',
		targetTop: 'Kõige ülemine aken (_top)',
		targetSelf: 'Sama aken (_self)',
		targetParent: 'Vanemaken (_parent)',
		langDirLTR: 'Vasakult paremale (LTR)',
		langDirRTL: 'Paremalt vasakule (RTL)',
		styles: 'Stiili',
		cssClasses: 'Stiililehe klassid',
		width: 'Laius',
		height: 'Kõrgus',
		align: 'Joondus',
		alignLeft: 'Vasak',
		alignRight: 'Paremale',
		alignCenter: 'Kesk',
		alignTop: 'Üles',
		alignMiddle: 'Keskele',
		alignBottom: 'Alla',
		invalidValue	: 'Vigane väärtus.',
		invalidHeight: 'Kõrgus peab olema number.',
		invalidWidth: 'Laius peab olema number.',
		invalidCssLength: '"%1" välja jaoks määratud väärtus peab olema positiivne täisarv CSS ühikuga (px, %, in, cm, mm, em, ex, pt või pc) või ilma.',
		invalidHtmlLength: '"%1" välja jaoks määratud väärtus peab olema positiivne täisarv HTML ühikuga (px või %) või ilma.',
		invalidInlineStyle: 'Reasisese stiili määrangud peavad koosnema paarisväärtustest (tuples), mis on semikoolonitega eraldatult järgnevas vormingus: "nimi : väärtus".',
		cssLengthTooltip: 'Sisesta väärtus pikslites või number koos sobiva CSS-i ühikuga (px, %, in, cm, mm, em, ex, pt või pc).',

		// Put the voice-only part of the label in the span.
		unavailable: '%1<span class="cke_accessibility">, pole saadaval</span>'
	}
};
