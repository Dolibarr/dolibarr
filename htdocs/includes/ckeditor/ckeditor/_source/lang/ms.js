/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

/**
 * @fileOverview Defines the {@link CKEDITOR.lang} object, for the
 * Malay language.
 */

/**#@+
   @type String
   @example
*/

/**
 * Contains the dictionary of language entries.
 * @namespace
 */
CKEDITOR.lang[ 'ms' ] = {
	// ARIA description.
	editor: 'Rich Text Editor', // MISSING
	editorPanel: 'Rich Text Editor panel', // MISSING

	// Common messages and labels.
	common: {
		// Screenreader titles. Please note that screenreaders are not always capable
		// of reading non-English words. So be careful while translating it.
		editorHelp: 'Press ALT 0 for help', // MISSING

		browseServer: 'Browse Server',
		url: 'URL',
		protocol: 'Protokol',
		upload: 'Muat Naik',
		uploadSubmit: 'Hantar ke Server',
		image: 'Gambar',
		flash: 'Flash', // MISSING
		form: 'Borang',
		checkbox: 'Checkbox',
		radio: 'Butang Radio',
		textField: 'Text Field',
		textarea: 'Textarea',
		hiddenField: 'Field Tersembunyi',
		button: 'Butang',
		select: 'Field Pilihan',
		imageButton: 'Butang Bergambar',
		notSet: '<tidak di set>',
		id: 'Id',
		name: 'Nama',
		langDir: 'Arah Tulisan',
		langDirLtr: 'Kiri ke Kanan (LTR)',
		langDirRtl: 'Kanan ke Kiri (RTL)',
		langCode: 'Kod Bahasa',
		longDescr: 'Butiran Panjang URL',
		cssClass: 'Kelas-kelas Stylesheet',
		advisoryTitle: 'Tajuk Makluman',
		cssStyle: 'Stail',
		ok: 'OK',
		cancel: 'Batal',
		close: 'Close', // MISSING
		preview: 'Prebiu',
		resize: 'Resize', // MISSING
		generalTab: 'General', // MISSING
		advancedTab: 'Advanced',
		validateNumberFailed: 'This value is not a number.', // MISSING
		confirmNewPage: 'Any unsaved changes to this content will be lost. Are you sure you want to load new page?', // MISSING
		confirmCancel: 'You have changed some options. Are you sure you want to close the dialog window?', // MISSING
		options: 'Options', // MISSING
		target: 'Sasaran',
		targetNew: 'New Window (_blank)', // MISSING
		targetTop: 'Topmost Window (_top)', // MISSING
		targetSelf: 'Same Window (_self)', // MISSING
		targetParent: 'Parent Window (_parent)', // MISSING
		langDirLTR: 'Kiri ke Kanan (LTR)',
		langDirRTL: 'Kanan ke Kiri (RTL)',
		styles: 'Stail',
		cssClasses: 'Kelas-kelas Stylesheet',
		width: 'Lebar',
		height: 'Tinggi',
		align: 'Jajaran',
		alignLeft: 'Kiri',
		alignRight: 'Kanan',
		alignCenter: 'Tengah',
		alignTop: 'Atas',
		alignMiddle: 'Pertengahan',
		alignBottom: 'Bawah',
		invalidValue	: 'Invalid value.', // MISSING
		invalidHeight: 'Height must be a number.', // MISSING
		invalidWidth: 'Width must be a number.', // MISSING
		invalidCssLength: 'Value specified for the "%1" field must be a positive number with or without a valid CSS measurement unit (px, %, in, cm, mm, em, ex, pt, or pc).', // MISSING
		invalidHtmlLength: 'Value specified for the "%1" field must be a positive number with or without a valid HTML measurement unit (px or %).', // MISSING
		invalidInlineStyle: 'Value specified for the inline style must consist of one or more tuples with the format of "name : value", separated by semi-colons.', // MISSING
		cssLengthTooltip: 'Enter a number for a value in pixels or a number with a valid CSS unit (px, %, in, cm, mm, em, ex, pt, or pc).', // MISSING

		// Put the voice-only part of the label in the span.
		unavailable: '%1<span class="cke_accessibility">, unavailable</span>' // MISSING
	}
};
