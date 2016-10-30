/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

/**
 * @fileOverview Defines the {@link CKEDITOR.lang} object, for the
 * Serbian (Cyrillic) language.
 */

/**#@+
   @type String
   @example
*/

/**
 * Contains the dictionary of language entries.
 * @namespace
 */
CKEDITOR.lang[ 'sr' ] = {
	// ARIA description.
	editor: 'Rich Text Editor', // MISSING
	editorPanel: 'Rich Text Editor panel', // MISSING

	// Common messages and labels.
	common: {
		// Screenreader titles. Please note that screenreaders are not always capable
		// of reading non-English words. So be careful while translating it.
		editorHelp: 'Press ALT 0 for help', // MISSING

		browseServer: 'Претражи сервер',
		url: 'УРЛ',
		protocol: 'Протокол',
		upload: 'Пошаљи',
		uploadSubmit: 'Пошаљи на сервер',
		image: 'Слика',
		flash: 'Флеш елемент',
		form: 'Форма',
		checkbox: 'Поље за потврду',
		radio: 'Радио-дугме',
		textField: 'Текстуално поље',
		textarea: 'Зона текста',
		hiddenField: 'Скривено поље',
		button: 'Дугме',
		select: 'Изборно поље',
		imageButton: 'Дугме са сликом',
		notSet: '<није постављено>',
		id: 'Ид',
		name: 'Назив',
		langDir: 'Смер језика',
		langDirLtr: 'С лева на десно (LTR)',
		langDirRtl: 'С десна на лево (RTL)',
		langCode: 'Kôд језика',
		longDescr: 'Пун опис УРЛ',
		cssClass: 'Stylesheet класе',
		advisoryTitle: 'Advisory наслов',
		cssStyle: 'Стил',
		ok: 'OK',
		cancel: 'Oткажи',
		close: 'Затвори',
		preview: 'Изглед странице',
		resize: 'Resize', // MISSING
		generalTab: 'Опште',
		advancedTab: 'Напредни тагови',
		validateNumberFailed: 'Ова вредност није цигра.',
		confirmNewPage: 'Any unsaved changes to this content will be lost. Are you sure you want to load new page?', // MISSING
		confirmCancel: 'You have changed some options. Are you sure you want to close the dialog window?', // MISSING
		options: 'Опције',
		target: 'Meтa',
		targetNew: 'New Window (_blank)', // MISSING
		targetTop: 'Topmost Window (_top)', // MISSING
		targetSelf: 'Same Window (_self)', // MISSING
		targetParent: 'Parent Window (_parent)', // MISSING
		langDirLTR: 'С лева на десно (LTR)',
		langDirRTL: 'С десна на лево (RTL)',
		styles: 'Стил',
		cssClasses: 'Stylesheet класе',
		width: 'Ширина',
		height: 'Висина',
		align: 'Равнање',
		alignLeft: 'Лево',
		alignRight: 'Десно',
		alignCenter: 'Средина',
		alignTop: 'Врх',
		alignMiddle: 'Средина',
		alignBottom: 'Доле',
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
