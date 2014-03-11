/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

/**
 * @fileOverview Defines the {@link CKEDITOR.lang} object, for the
 * Hindi language.
 */

/**#@+
   @type String
   @example
*/

/**
 * Contains the dictionary of language entries.
 * @namespace
 */
CKEDITOR.lang[ 'hi' ] = {
	// ARIA description.
	editor: 'रिच टेक्स्ट एडिटर',
	editorPanel: 'Rich Text Editor panel', // MISSING

	// Common messages and labels.
	common: {
		// Screenreader titles. Please note that screenreaders are not always capable
		// of reading non-English words. So be careful while translating it.
		editorHelp: 'मदद के लिये ALT 0 दबाए',

		browseServer: 'सर्वर ब्राउज़ करें',
		url: 'URL',
		protocol: 'प्रोटोकॉल',
		upload: 'अपलोड',
		uploadSubmit: 'इसे सर्वर को भेजें',
		image: 'तस्वीर',
		flash: 'फ़्लैश',
		form: 'फ़ॉर्म',
		checkbox: 'चॅक बॉक्स',
		radio: 'रेडिओ बटन',
		textField: 'टेक्स्ट फ़ील्ड',
		textarea: 'टेक्स्ट एरिया',
		hiddenField: 'गुप्त फ़ील्ड',
		button: 'बटन',
		select: 'चुनाव फ़ील्ड',
		imageButton: 'तस्वीर बटन',
		notSet: '<सॅट नहीं>',
		id: 'Id',
		name: 'नाम',
		langDir: 'भाषा लिखने की दिशा',
		langDirLtr: 'बायें से दायें (LTR)',
		langDirRtl: 'दायें से बायें (RTL)',
		langCode: 'भाषा कोड',
		longDescr: 'अधिक विवरण के लिए URL',
		cssClass: 'स्टाइल-शीट क्लास',
		advisoryTitle: 'परामर्श शीर्शक',
		cssStyle: 'स्टाइल',
		ok: 'ठीक है',
		cancel: 'रद्द करें',
		close: 'Close', // MISSING
		preview: 'प्रीव्यू',
		resize: 'Resize', // MISSING
		generalTab: 'सामान्य',
		advancedTab: 'ऍड्वान्स्ड',
		validateNumberFailed: 'This value is not a number.', // MISSING
		confirmNewPage: 'Any unsaved changes to this content will be lost. Are you sure you want to load new page?', // MISSING
		confirmCancel: 'You have changed some options. Are you sure you want to close the dialog window?', // MISSING
		options: 'Options', // MISSING
		target: 'टार्गेट',
		targetNew: 'New Window (_blank)', // MISSING
		targetTop: 'Topmost Window (_top)', // MISSING
		targetSelf: 'Same Window (_self)', // MISSING
		targetParent: 'Parent Window (_parent)', // MISSING
		langDirLTR: 'बायें से दायें (LTR)',
		langDirRTL: 'दायें से बायें (RTL)',
		styles: 'स्टाइल',
		cssClasses: 'स्टाइल-शीट क्लास',
		width: 'चौड़ाई',
		height: 'ऊँचाई',
		align: 'ऍलाइन',
		alignLeft: 'दायें',
		alignRight: 'दायें',
		alignCenter: 'बीच में',
		alignTop: 'ऊपर',
		alignMiddle: 'मध्य',
		alignBottom: 'नीचे',
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
