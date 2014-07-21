/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

/**
 * @fileOverview Defines the {@link CKEDITOR.lang} object, for the
 * Romanian language.
 */

/**#@+
   @type String
   @example
*/

/**
 * Contains the dictionary of language entries.
 * @namespace
 */
CKEDITOR.lang[ 'ro' ] = {
	// ARIA description.
	editor: 'Rich Text Editor',
	editorPanel: 'Rich Text Editor panel', // MISSING

	// Common messages and labels.
	common: {
		// Screenreader titles. Please note that screenreaders are not always capable
		// of reading non-English words. So be careful while translating it.
		editorHelp: 'Apasă ALT 0 pentru ajutor',

		browseServer: 'Răsfoieşte server',
		url: 'URL',
		protocol: 'Protocol',
		upload: 'Încarcă',
		uploadSubmit: 'Trimite la server',
		image: 'Imagine',
		flash: 'Flash',
		form: 'Formular (Form)',
		checkbox: 'Bifă (Checkbox)',
		radio: 'Buton radio (RadioButton)',
		textField: 'Câmp text (TextField)',
		textarea: 'Suprafaţă text (Textarea)',
		hiddenField: 'Câmp ascuns (HiddenField)',
		button: 'Buton',
		select: 'Câmp selecţie (SelectionField)',
		imageButton: 'Buton imagine (ImageButton)',
		notSet: '<nesetat>',
		id: 'Id',
		name: 'Nume',
		langDir: 'Direcţia cuvintelor',
		langDirLtr: 'stânga-dreapta (LTR)',
		langDirRtl: 'dreapta-stânga (RTL)',
		langCode: 'Codul limbii',
		longDescr: 'Descrierea lungă URL',
		cssClass: 'Clasele cu stilul paginii (CSS)',
		advisoryTitle: 'Titlul consultativ',
		cssStyle: 'Stil',
		ok: 'OK',
		cancel: 'Anulare',
		close: 'Închide',
		preview: 'Previzualizare',
		resize: 'Trage pentru a redimensiona',
		generalTab: 'General',
		advancedTab: 'Avansat',
		validateNumberFailed: 'Această valoare nu este un număr.',
		confirmNewPage: 'Orice modificări nesalvate ale acestui conținut, vor fi pierdute. Sigur doriți încărcarea unei noi pagini?',
		confirmCancel: 'Câteva opțiuni au fost schimbate. Sigur doriți să închideți dialogul?',
		options: 'Opțiuni',
		target: 'Țintă',
		targetNew: 'Fereastră nouă (_blank)',
		targetTop: 'Topmost Window (_top)',
		targetSelf: 'În aceeași fereastră (_self)',
		targetParent: 'Parent Window (_parent)',
		langDirLTR: 'Stânga spre Dreapta (LTR)',
		langDirRTL: 'Dreapta spre Stânga (RTL)',
		styles: 'Stil',
		cssClasses: 'Stylesheet Classes',
		width: 'Lăţime',
		height: 'Înălţime',
		align: 'Aliniere',
		alignLeft: 'Mărește Bara',
		alignRight: 'Dreapta',
		alignCenter: 'Centru',
		alignTop: 'Sus',
		alignMiddle: 'Mijloc',
		alignBottom: 'Jos',
		invalidValue	: 'Varloare invalida',
		invalidHeight: 'Înălțimea trebuie să fie un număr.',
		invalidWidth: 'Lățimea trebuie să fie un număr.',
		invalidCssLength: 'Value specified for the "%1" field must be a positive number with or without a valid CSS measurement unit (px, %, in, cm, mm, em, ex, pt, or pc).', // MISSING
		invalidHtmlLength: 'Value specified for the "%1" field must be a positive number with or without a valid HTML measurement unit (px or %).', // MISSING
		invalidInlineStyle: 'Value specified for the inline style must consist of one or more tuples with the format of "name : value", separated by semi-colons.', // MISSING
		cssLengthTooltip: 'Enter a number for a value in pixels or a number with a valid CSS unit (px, %, in, cm, mm, em, ex, pt, or pc).', // MISSING

		// Put the voice-only part of the label in the span.
		unavailable: '%1<span class="cke_accessibility">, nu este disponibil</span>'
	}
};
