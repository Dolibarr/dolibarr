/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

/**
 * @fileOverview Defines the {@link CKEDITOR.lang} object, for the
 * Catalan language.
 */

/**#@+
   @type String
   @example
*/

/**
 * Contains the dictionary of language entries.
 * @namespace
 */
CKEDITOR.lang[ 'ca' ] = {
	// ARIA description.
	editor: 'Editor de text enriquit',
	editorPanel: 'Panell de l\'editor de text enriquit',

	// Common messages and labels.
	common: {
		// Screenreader titles. Please note that screenreaders are not always capable
		// of reading non-English words. So be careful while translating it.
		editorHelp: 'Premeu ALT 0 per ajuda',

		browseServer: 'Veure servidor',
		url: 'URL',
		protocol: 'Protocol',
		upload: 'Puja',
		uploadSubmit: 'Envia-la al servidor',
		image: 'Imatge',
		flash: 'Flash',
		form: 'Formulari',
		checkbox: 'Casella de verificació',
		radio: 'Botó d\'opció',
		textField: 'Camp de text',
		textarea: 'Àrea de text',
		hiddenField: 'Camp ocult',
		button: 'Botó',
		select: 'Camp de selecció',
		imageButton: 'Botó d\'imatge',
		notSet: '<no definit>',
		id: 'Id',
		name: 'Nom',
		langDir: 'Direcció de l\'idioma',
		langDirLtr: 'D\'esquerra a dreta (LTR)',
		langDirRtl: 'De dreta a esquerra (RTL)',
		langCode: 'Codi d\'idioma',
		longDescr: 'Descripció llarga de la URL',
		cssClass: 'Classes del full d\'estil',
		advisoryTitle: 'Títol consultiu',
		cssStyle: 'Estil',
		ok: 'D\'acord',
		cancel: 'Cancel·la',
		close: 'Tanca',
		preview: 'Previsualitza',
		resize: 'Arrossegueu per redimensionar',
		generalTab: 'General',
		advancedTab: 'Avançat',
		validateNumberFailed: 'Aquest valor no és un número.',
		confirmNewPage: 'Els canvis en aquest contingut que no es desin es perdran. Esteu segur que voleu carregar una pàgina nova?',
		confirmCancel: 'Algunes opcions s\'han canviat. Esteu segur que voleu tancar el quadre de diàleg?',
		options: 'Opcions',
		target: 'Destí',
		targetNew: 'Nova finestra (_blank)',
		targetTop: 'Finestra superior (_top)',
		targetSelf: 'Mateixa finestra (_self)',
		targetParent: 'Finestra pare (_parent)',
		langDirLTR: 'D\'esquerra a dreta (LTR)',
		langDirRTL: 'De dreta a esquerra (RTL)',
		styles: 'Estil',
		cssClasses: 'Classes del full d\'estil',
		width: 'Amplada',
		height: 'Alçada',
		align: 'Alineació',
		alignLeft: 'Ajusta a l\'esquerra',
		alignRight: 'Ajusta a la dreta',
		alignCenter: 'Centre',
		alignTop: 'Superior',
		alignMiddle: 'Centre',
		alignBottom: 'Inferior',
		invalidValue	: 'Valor no vàlid.',
		invalidHeight: 'L\'alçada ha de ser un número.',
		invalidWidth: 'L\'amplada ha de ser un número.',
		invalidCssLength: 'El valor especificat per als "%1" camps ha de ser un número positiu amb o sense unitat de mesura vàlida de CSS (px, %, in, cm, mm, em, ex, pt o pc).',
		invalidHtmlLength: 'El valor especificat per als "%1" camps ha de ser un número positiu amb o sense unitat de mesura vàlida d\'HTML (px o %).',
		invalidInlineStyle: 'El valor especificat per l\'estil en línia ha de constar d\'una o més tuples amb el format "name: value", separats per punt i coma.',
		cssLengthTooltip: 'Introduïu un número per un valor en píxels o un número amb una unitat vàlida de CSS (px, %, in, cm, mm, em, ex, pt o pc).',

		// Put the voice-only part of the label in the span.
		unavailable: '%1<span class="cke_accessibility">, no disponible</span>'
	}
};
