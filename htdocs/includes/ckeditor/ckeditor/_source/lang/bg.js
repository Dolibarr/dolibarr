/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

/**
 * @fileOverview Defines the {@link CKEDITOR.lang} object, for the
 * Bulgarian language.
 */

/**#@+
   @type String
   @example
*/

/**
 * Contains the dictionary of language entries.
 * @namespace
 */
CKEDITOR.lang[ 'bg' ] = {
	// ARIA description.
	editor: 'Текстов редактор за форматиран текст',
	editorPanel: 'Rich Text Editor panel', // MISSING

	// Common messages and labels.
	common: {
		// Screenreader titles. Please note that screenreaders are not always capable
		// of reading non-English words. So be careful while translating it.
		editorHelp: 'натиснете ALT 0 за помощ',

		browseServer: 'Избор от сървъра',
		url: 'URL',
		protocol: 'Протокол',
		upload: 'Качване',
		uploadSubmit: 'Изпращане към сървъра',
		image: 'Снимка',
		flash: 'Флаш',
		form: 'Форма',
		checkbox: 'Поле за избор',
		radio: 'Радио бутон',
		textField: 'Текстово поле',
		textarea: 'Текстова зона',
		hiddenField: 'Скрито поле',
		button: 'Бутон',
		select: 'Поле за избор',
		imageButton: 'Бутон за снимка',
		notSet: '<не е избрано>',
		id: 'ID',
		name: 'Име',
		langDir: 'Посока на езика',
		langDirLtr: 'Ляво на дясно (ЛнД)',
		langDirRtl: 'Дясно на ляво (ДнЛ)',
		langCode: 'Код на езика',
		longDescr: 'Уеб адрес за дълго описание',
		cssClass: 'Класове за CSS',
		advisoryTitle: 'Препоръчително заглавие',
		cssStyle: 'Стил',
		ok: 'ОК',
		cancel: 'Отказ',
		close: 'Затвори',
		preview: 'Преглед',
		resize: 'Влачете за да оразмерите',
		generalTab: 'Общи',
		advancedTab: 'Разширено',
		validateNumberFailed: 'Тази стойност не е число',
		confirmNewPage: 'Всички незапазени промени ще бъдат изгубени. Сигурни ли сте, че желаете да заредите нова страница?',
		confirmCancel: 'Някои от опциите са променени. Сигурни ли сте, че желаете да затворите прозореца?',
		options: 'Опции',
		target: 'Цел',
		targetNew: 'Нов прозорец (_blank)',
		targetTop: 'Горна позиция (_top)',
		targetSelf: 'Текущия прозорец (_self)',
		targetParent: 'Основен прозорец (_parent)',
		langDirLTR: 'Ляво на дясно (ЛнД)',
		langDirRTL: 'Дясно на ляво (ДнЛ)',
		styles: 'Стил',
		cssClasses: 'Класове за CSS',
		width: 'Ширина',
		height: 'Височина',
		align: 'Подравняване',
		alignLeft: 'Ляво',
		alignRight: 'Дясно',
		alignCenter: 'Център',
		alignTop: 'Горе',
		alignMiddle: 'По средата',
		alignBottom: 'Долу',
		invalidValue	: 'Невалидна стойност.',
		invalidHeight: 'Височината трябва да е число.',
		invalidWidth: 'Ширина требе да е число.',
		invalidCssLength: 'Стойността на полето "%1" трябва да бъде положително число с или без валидна CSS измервателна единица (px, %, in, cm, mm, em, ex, pt, или pc).',
		invalidHtmlLength: 'Стойността на полето "%1" трябва да бъде положително число с или без валидна HTML измервателна единица (px или %).',
		invalidInlineStyle: 'Стойността на стилa трябва да съдържат една или повече двойки във формат "name : value", разделени с двоеточие.',
		cssLengthTooltip: 'Въведете числена стойност в пиксели или друга валидна CSS единица (px, %, in, cm, mm, em, ex, pt, или pc).',

		// Put the voice-only part of the label in the span.
		unavailable: '%1<span class="cke_accessibility">, недостъпно</span>'
	}
};
