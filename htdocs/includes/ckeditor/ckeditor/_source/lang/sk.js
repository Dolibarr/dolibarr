/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

/**
 * @fileOverview Defines the {@link CKEDITOR.lang} object, for the
 * Slovak language.
 */

/**#@+
   @type String
   @example
*/

/**
 * Contains the dictionary of language entries.
 * @namespace
 */
CKEDITOR.lang[ 'sk' ] = {
	// ARIA description.
	editor: 'Editor formátovaného textu',
	editorPanel: 'Rich Text Editor panel', // MISSING

	// Common messages and labels.
	common: {
		// Screenreader titles. Please note that screenreaders are not always capable
		// of reading non-English words. So be careful while translating it.
		editorHelp: 'Stlačte ALT 0 pre nápovedu',

		browseServer: 'Prechádzať server',
		url: 'URL',
		protocol: 'Protokol',
		upload: 'Odoslať',
		uploadSubmit: 'Odoslať na server',
		image: 'Obrázok',
		flash: 'Flash',
		form: 'Formulár',
		checkbox: 'Zaškrtávacie políčko',
		radio: 'Prepínač',
		textField: 'Textové pole',
		textarea: 'Textová oblasť',
		hiddenField: 'Skryté pole',
		button: 'Tlačidlo',
		select: 'Rozbaľovací zoznam',
		imageButton: 'Obrázkové tlačidlo',
		notSet: '<nenastavené>',
		id: 'Id',
		name: 'Meno',
		langDir: 'Orientácia jazyka',
		langDirLtr: 'Zľava doprava (LTR)',
		langDirRtl: 'Sprava doľava (RTL)',
		langCode: 'Kód jazyka',
		longDescr: 'Dlhý popis URL',
		cssClass: 'Trieda štýlu',
		advisoryTitle: 'Pomocný titulok',
		cssStyle: 'Štýl',
		ok: 'OK',
		cancel: 'Zrušiť',
		close: 'Zatvorit',
		preview: 'Náhľad',
		resize: 'Zmeniť veľkosť',
		generalTab: 'Hlavné',
		advancedTab: 'Rozšírené',
		validateNumberFailed: 'Hodnota nieje číslo.',
		confirmNewPage: 'Prajete si načítat novú stránku? Všetky neuložené zmeny budú stratené. ',
		confirmCancel: 'Niektore možnosti boli zmenené. Naozaj chcete zavrieť okno?',
		options: 'Možnosti',
		target: 'Cieľ',
		targetNew: 'Nové okno (_blank)',
		targetTop: 'Najvrchnejšie okno (_top)',
		targetSelf: 'To isté okno (_self)',
		targetParent: 'Rodičovské okno (_parent)',
		langDirLTR: 'Zľava doprava (LTR)',
		langDirRTL: 'Sprava doľava (RTL)',
		styles: 'Štýl',
		cssClasses: 'Triedy štýlu',
		width: 'Šírka',
		height: 'Výška',
		align: 'Zarovnanie',
		alignLeft: 'Vľavo',
		alignRight: 'Vpravo',
		alignCenter: 'Na stred',
		alignTop: 'Nahor',
		alignMiddle: 'Na stred',
		alignBottom: 'Dole',
		invalidValue	: 'Neplatná hodnota.',
		invalidHeight: 'Výška musí byť číslo.',
		invalidWidth: 'Šírka musí byť číslo.',
		invalidCssLength: 'Špecifikovaná hodnota pre pole "%1" musí byť kladné číslo s alebo bez platnej CSS mernej jednotky (px, %, in, cm, mm, em, ex, pt alebo pc).',
		invalidHtmlLength: 'Špecifikovaná hodnota pre pole "%1" musí byť kladné číslo s alebo bez platnej HTML mernej jednotky (px alebo %).',
		invalidInlineStyle: 'Zadaná hodnota pre inline štýl musí pozostávať s jedného, alebo viac dvojíc formátu "názov: hodnota", oddelených bodkočiarkou.',
		cssLengthTooltip: 'Vložte číslo pre hodnotu v pixeloch alebo číslo so správnou CSS jednotou (px, %, in, cm, mm, em, ex, pt alebo pc).',

		// Put the voice-only part of the label in the span.
		unavailable: '%1<span class="cke_accessibility">, nedostupný</span>'
	}
};
