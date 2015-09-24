/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

/**
 * @fileOverview Defines the {@link CKEDITOR.lang} object, for the
 * Mongolian language.
 */

/**#@+
   @type String
   @example
*/

/**
 * Contains the dictionary of language entries.
 * @namespace
 */
CKEDITOR.lang[ 'mn' ] = {
	// ARIA description.
	editor: 'Хэлбэрт бичвэр боловсруулагч',
	editorPanel: 'Rich Text Editor panel', // MISSING

	// Common messages and labels.
	common: {
		// Screenreader titles. Please note that screenreaders are not always capable
		// of reading non-English words. So be careful while translating it.
		editorHelp: 'Press ALT 0 for help', // MISSING

		browseServer: 'Үйлчлэгч тооцоолуур (сервэр)-ийг үзэх',
		url: 'цахим хуудасны хаяг (URL)',
		protocol: 'Протокол',
		upload: 'Илгээж ачаалах',
		uploadSubmit: 'Үүнийг үйлчлэгч тооцоолуур (сервер) лүү илгээх',
		image: 'Зураг',
		flash: 'Флаш хөдөлгөөнтэй зураг',
		form: 'Маягт',
		checkbox: 'Тэмдэглээний нүд',
		radio: 'Радио товчлуур',
		textField: 'Бичвэрийн талбар',
		textarea: 'Бичвэрийн зай',
		hiddenField: 'Далд талбар',
		button: 'Товчлуур',
		select: 'Сонголтын талбар',
		imageButton: 'Зургий товчуур',
		notSet: '<тохируулаагүй>',
		id: 'Id (техникийн нэр)',
		name: 'Нэр',
		langDir: 'Хэлний чиглэл',
		langDirLtr: 'Зүүнээс баруун (LTR)',
		langDirRtl: 'Баруунаас зүүн (RTL)',
		langCode: 'Хэлний код',
		longDescr: 'Урт тайлбарын вэб хаяг',
		cssClass: 'Хэлбэрийн хуудасны ангиуд',
		advisoryTitle: 'Зөвлөх гарчиг',
		cssStyle: 'Загвар',
		ok: 'За',
		cancel: 'Болих',
		close: 'Хаах',
		preview: 'Урьдчилан харах',
		resize: 'Resize', // MISSING
		generalTab: 'Ерөнхий',
		advancedTab: 'Гүнзгий',
		validateNumberFailed: 'This value is not a number.', // MISSING
		confirmNewPage: 'Any unsaved changes to this content will be lost. Are you sure you want to load new page?', // MISSING
		confirmCancel: 'You have changed some options. Are you sure you want to close the dialog window?', // MISSING
		options: 'Сонголт',
		target: 'Бай',
		targetNew: 'New Window (_blank)', // MISSING
		targetTop: 'Topmost Window (_top)', // MISSING
		targetSelf: 'Same Window (_self)', // MISSING
		targetParent: 'Parent Window (_parent)', // MISSING
		langDirLTR: 'Зүүн талаас баруун тийшээ (LTR)',
		langDirRTL: 'Баруун талаас зүүн тийшээ (RTL)',
		styles: 'Загвар',
		cssClasses: 'Хэлбэрийн хуудасны ангиуд',
		width: 'Өргөн',
		height: 'Өндөр',
		align: 'Эгнээ',
		alignLeft: 'Зүүн',
		alignRight: 'Баруун',
		alignCenter: 'Төвд',
		alignTop: 'Дээд талд',
		alignMiddle: 'Дунд',
		alignBottom: 'Доод талд',
		invalidValue	: 'Invalid value.', // MISSING
		invalidHeight: 'Өндөр нь тоо байх ёстой.',
		invalidWidth: 'Өргөн нь тоо байх ёстой.',
		invalidCssLength: 'Value specified for the "%1" field must be a positive number with or without a valid CSS measurement unit (px, %, in, cm, mm, em, ex, pt, or pc).', // MISSING
		invalidHtmlLength: 'Value specified for the "%1" field must be a positive number with or without a valid HTML measurement unit (px or %).', // MISSING
		invalidInlineStyle: 'Value specified for the inline style must consist of one or more tuples with the format of "name : value", separated by semi-colons.', // MISSING
		cssLengthTooltip: 'Enter a number for a value in pixels or a number with a valid CSS unit (px, %, in, cm, mm, em, ex, pt, or pc).', // MISSING

		// Put the voice-only part of the label in the span.
		unavailable: '%1<span class="cke_accessibility">, unavailable</span>' // MISSING
	}
};
