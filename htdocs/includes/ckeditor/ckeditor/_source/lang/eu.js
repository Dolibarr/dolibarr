/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

/**
 * @fileOverview Defines the {@link CKEDITOR.lang} object, for the
 * Basque language.
 */

/**#@+
   @type String
   @example
*/

/**
 * Contains the dictionary of language entries.
 * @namespace
 */
CKEDITOR.lang[ 'eu' ] = {
	// ARIA description.
	editor: 'Testu Aberastuko Editorea',
	editorPanel: 'Rich Text Editor panel', // MISSING

	// Common messages and labels.
	common: {
		// Screenreader titles. Please note that screenreaders are not always capable
		// of reading non-English words. So be careful while translating it.
		editorHelp: 'ALT 0 sakatu laguntza jasotzeko',

		browseServer: 'Zerbitzaria arakatu',
		url: 'URL',
		protocol: 'Protokoloa',
		upload: 'Gora kargatu',
		uploadSubmit: 'Zerbitzarira bidali',
		image: 'Irudia',
		flash: 'Flasha',
		form: 'Formularioa',
		checkbox: 'Kontrol-laukia',
		radio: 'Aukera-botoia',
		textField: 'Testu Eremua',
		textarea: 'Testu-area',
		hiddenField: 'Ezkutuko Eremua',
		button: 'Botoia',
		select: 'Hautespen Eremua',
		imageButton: 'Irudi Botoia',
		notSet: '<Ezarri gabe>',
		id: 'Id',
		name: 'Izena',
		langDir: 'Hizkuntzaren Norabidea',
		langDirLtr: 'Ezkerretik Eskumara(LTR)',
		langDirRtl: 'Eskumatik Ezkerrera (RTL)',
		langCode: 'Hizkuntza Kodea',
		longDescr: 'URL Deskribapen Luzea',
		cssClass: 'Estilo-orriko Klaseak',
		advisoryTitle: 'Izenburua',
		cssStyle: 'Estiloa',
		ok: 'Ados',
		cancel: 'Utzi',
		close: 'Itxi',
		preview: 'Aurrebista',
		resize: 'Arrastatu tamaina aldatzeko',
		generalTab: 'Orokorra',
		advancedTab: 'Aurreratua',
		validateNumberFailed: 'Balio hau ez da zenbaki bat.',
		confirmNewPage: 'Eduki honetan gorde gabe dauden aldaketak galduko dira. Ziur zaude orri berri bat kargatu nahi duzula?',
		confirmCancel: 'Aukera batzuk aldatu egin dira. Ziur zaude elkarrizketa-koadroa itxi nahi duzula?',
		options: 'Aukerak',
		target: 'Target (Helburua)',
		targetNew: 'Leiho Berria (_blank)',
		targetTop: 'Goieneko Leihoan (_top)',
		targetSelf: 'Leiho Berdinean (_self)',
		targetParent: 'Leiho Gurasoan (_parent)',
		langDirLTR: 'Ezkerretik Eskumara(LTR)',
		langDirRTL: 'Eskumatik Ezkerrera (RTL)',
		styles: 'Estiloa',
		cssClasses: 'Estilo-orriko Klaseak',
		width: 'Zabalera',
		height: 'Altuera',
		align: 'Lerrokatu',
		alignLeft: 'Ezkerrera',
		alignRight: 'Eskuman',
		alignCenter: 'Erdian',
		alignTop: 'Goian',
		alignMiddle: 'Erdian',
		alignBottom: 'Behean',
		invalidValue	: 'Balio ezegokia.',
		invalidHeight: 'Altuera zenbaki bat izan behar da.',
		invalidWidth: 'Zabalera zenbaki bat izan behar da.',
		invalidCssLength: '"%1" eremurako zehaztutako balioa zenbaki positibo bat izan behar du, aukeran CSS neurri unitate batekin (px, %, in, cm, mm, em, ex, pt edo pc).',
		invalidHtmlLength: '"%1" eremurako zehaztutako balioa zenbaki positibo bat izan behar du, aukeran HTML neurri unitate batekin (px edo %).',
		invalidInlineStyle: 'Lerroko estiloan zehazten dena tupla "name : value" formatuko eta puntu eta komaz bereiztutako tupla bat edo gehiago izan behar dira.',
		cssLengthTooltip: 'Zenbakia bakarrik zehazten bada pixeletan egongo da. CSS neurri unitatea ere zehaztu ahal da (px, %, in, cm, mm, em, ex, pt, edo pc).',

		// Put the voice-only part of the label in the span.
		unavailable: '%1<span class="cke_accessibility">, erabilezina</span>'
	}
};
