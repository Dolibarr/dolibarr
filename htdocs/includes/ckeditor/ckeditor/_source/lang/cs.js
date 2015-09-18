/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

/**
 * @fileOverview Defines the {@link CKEDITOR.lang} object, for the
 * Czech language.
 */

/**#@+
   @type String
   @example
*/

/**
 * Contains the dictionary of language entries.
 * @namespace
 */
CKEDITOR.lang[ 'cs' ] = {
	// ARIA description.
	editor: 'Textový editor',
	editorPanel: 'Panel textového editoru',

	// Common messages and labels.
	common: {
		// Screenreader titles. Please note that screenreaders are not always capable
		// of reading non-English words. So be careful while translating it.
		editorHelp: 'Stiskněte ALT 0 pro nápovědu',

		browseServer: 'Vybrat na serveru',
		url: 'URL',
		protocol: 'Protokol',
		upload: 'Odeslat',
		uploadSubmit: 'Odeslat na server',
		image: 'Obrázek',
		flash: 'Flash',
		form: 'Formulář',
		checkbox: 'Zaškrtávací políčko',
		radio: 'Přepínač',
		textField: 'Textové pole',
		textarea: 'Textová oblast',
		hiddenField: 'Skryté pole',
		button: 'Tlačítko',
		select: 'Seznam',
		imageButton: 'Obrázkové tlačítko',
		notSet: '<nenastaveno>',
		id: 'Id',
		name: 'Jméno',
		langDir: 'Směr jazyka',
		langDirLtr: 'Zleva doprava (LTR)',
		langDirRtl: 'Zprava doleva (RTL)',
		langCode: 'Kód jazyka',
		longDescr: 'Dlouhý popis URL',
		cssClass: 'Třída stylu',
		advisoryTitle: 'Pomocný titulek',
		cssStyle: 'Styl',
		ok: 'OK',
		cancel: 'Zrušit',
		close: 'Zavřít',
		preview: 'Náhled',
		resize: 'Uchopit pro změnu velikosti',
		generalTab: 'Obecné',
		advancedTab: 'Rozšířené',
		validateNumberFailed: 'Zadaná hodnota není číselná.',
		confirmNewPage: 'Jakékoliv neuložené změny obsahu budou ztraceny. Skutečně chcete otevřít novou stránku?',
		confirmCancel: 'Některá z nastavení byla změněna. Skutečně chcete zavřít dialogové okno?',
		options: 'Nastavení',
		target: 'Cíl',
		targetNew: 'Nové okno (_blank)',
		targetTop: 'Okno nejvyšší úrovně (_top)',
		targetSelf: 'Stejné okno (_self)',
		targetParent: 'Rodičovské okno (_parent)',
		langDirLTR: 'Zleva doprava (LTR)',
		langDirRTL: 'Zprava doleva (RTL)',
		styles: 'Styly',
		cssClasses: 'Třídy stylů',
		width: 'Šířka',
		height: 'Výška',
		align: 'Zarovnání',
		alignLeft: 'Vlevo',
		alignRight: 'Vpravo',
		alignCenter: 'Na střed',
		alignTop: 'Nahoru',
		alignMiddle: 'Na střed',
		alignBottom: 'Dolů',
		invalidValue	: 'Neplatná hodnota.',
		invalidHeight: 'Zadaná výška musí být číslo.',
		invalidWidth: 'Šířka musí být číslo.',
		invalidCssLength: 'Hodnota určená pro pole "%1" musí být kladné číslo bez nebo s platnou jednotkou míry CSS (px, %, in, cm, mm, em, ex, pt, nebo pc).',
		invalidHtmlLength: 'Hodnota určená pro pole "%1" musí být kladné číslo bez nebo s platnou jednotkou míry HTML (px nebo %).',
		invalidInlineStyle: 'Hodnota určená pro řádkový styl se musí skládat z jedné nebo více n-tic ve formátu "název : hodnota", oddělené středníky',
		cssLengthTooltip: 'Zadejte číslo jako hodnotu v pixelech nebo číslo s platnou jednotkou CSS (px, %, v cm, mm, em, ex, pt, nebo pc).',

		// Put the voice-only part of the label in the span.
		unavailable: '%1<span class="cke_accessibility">, nedostupné</span>'
	}
};
