/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

/**
 * @fileOverview Defines the {@link CKEDITOR.lang} object, for the
 * German language.
 */

/**#@+
   @type String
   @example
*/

/**
 * Contains the dictionary of language entries.
 * @namespace
 */
CKEDITOR.lang[ 'de' ] = {
	// ARIA description.
	editor: 'WYSIWYG-Editor',
	editorPanel: 'WYSIWYG-Editor-Leiste',

	// Common messages and labels.
	common: {
		// Screenreader titles. Please note that screenreaders are not always capable
		// of reading non-English words. So be careful while translating it.
		editorHelp: 'Drücken Sie ALT 0 für Hilfe',

		browseServer: 'Server durchsuchen',
		url: 'URL',
		protocol: 'Protokoll',
		upload: 'Hochladen',
		uploadSubmit: 'Zum Server senden',
		image: 'Bild',
		flash: 'Flash',
		form: 'Formular',
		checkbox: 'Checkbox',
		radio: 'Radiobutton',
		textField: 'Textfeld einzeilig',
		textarea: 'Textfeld mehrzeilig',
		hiddenField: 'Verstecktes Feld',
		button: 'Klickbutton',
		select: 'Auswahlfeld',
		imageButton: 'Bildbutton',
		notSet: '<nichts>',
		id: 'ID',
		name: 'Name',
		langDir: 'Schreibrichtung',
		langDirLtr: 'Links nach Rechts (LTR)',
		langDirRtl: 'Rechts nach Links (RTL)',
		langCode: 'Sprachenkürzel',
		longDescr: 'Langform URL',
		cssClass: 'Stylesheet Klasse',
		advisoryTitle: 'Titel Beschreibung',
		cssStyle: 'Style',
		ok: 'OK',
		cancel: 'Abbrechen',
		close: 'Schließen',
		preview: 'Vorschau',
		resize: 'Zum Vergrößern ziehen',
		generalTab: 'Allgemein',
		advancedTab: 'Erweitert',
		validateNumberFailed: 'Dieser Wert ist keine Nummer.',
		confirmNewPage: 'Alle nicht gespeicherten Änderungen gehen verlohren. Sind Sie sicher die neue Seite zu laden?',
		confirmCancel: 'Einige Optionen wurden geändert. Wollen Sie den Dialog dennoch schließen?',
		options: 'Optionen',
		target: 'Zielseite',
		targetNew: 'Neues Fenster (_blank)',
		targetTop: 'Oberstes Fenster (_top)',
		targetSelf: 'Gleiches Fenster (_self)',
		targetParent: 'Oberes Fenster (_parent)',
		langDirLTR: 'Links nach Rechts (LNR)',
		langDirRTL: 'Rechts nach Links (RNL)',
		styles: 'Style',
		cssClasses: 'Stylesheet Klasse',
		width: 'Breite',
		height: 'Höhe',
		align: 'Ausrichtung',
		alignLeft: 'Links',
		alignRight: 'Rechts',
		alignCenter: 'Zentriert',
		alignTop: 'Oben',
		alignMiddle: 'Mitte',
		alignBottom: 'Unten',
		invalidValue	: 'Ungültiger Wert.',
		invalidHeight: 'Höhe muss eine Zahl sein.',
		invalidWidth: 'Breite muss eine Zahl sein.',
		invalidCssLength: 'Wert spezifiziert für "%1" Feld muss ein positiver numerischer Wert sein mit oder ohne korrekte CSS Messeinheit (px, %, in, cm, mm, em, ex, pt oder pc).',
		invalidHtmlLength: 'Wert spezifiziert für "%1" Feld muss ein positiver numerischer Wert sein mit oder ohne korrekte HTML Messeinheit (px oder %).',
		invalidInlineStyle: 'Wert spezifiziert für inline Stilart muss enthalten ein oder mehr Tupels mit dem Format "Name : Wert" getrennt mit Semikolons.',
		cssLengthTooltip: 'Gebe eine Zahl ein für ein Wert in pixels oder eine Zahl mit einer korrekten CSS Messeinheit (px, %, in, cm, mm, em, ex, pt oder pc).',

		// Put the voice-only part of the label in the span.
		unavailable: '%1<span class="cke_accessibility">, nicht verfügbar</span>'
	}
};
