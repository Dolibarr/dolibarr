/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

/**
 * @fileOverview Defines the {@link CKEDITOR.lang} object, for the
 * Ukrainian language.
 */

/**#@+
   @type String
   @example
*/

/**
 * Contains the dictionary of language entries.
 * @namespace
 */
CKEDITOR.lang[ 'uk' ] = {
	// ARIA description.
	editor: 'Текстовий редактор',
	editorPanel: 'Панель текстового редактора',

	// Common messages and labels.
	common: {
		// Screenreader titles. Please note that screenreaders are not always capable
		// of reading non-English words. So be careful while translating it.
		editorHelp: 'натисніть ALT 0 для довідки',

		browseServer: 'Огляд Сервера',
		url: 'URL',
		protocol: 'Протокол',
		upload: 'Надіслати',
		uploadSubmit: 'Надіслати на сервер',
		image: 'Зображення',
		flash: 'Flash',
		form: 'Форма',
		checkbox: 'Галочка',
		radio: 'Кнопка вибору',
		textField: 'Текстове поле',
		textarea: 'Текстова область',
		hiddenField: 'Приховане поле',
		button: 'Кнопка',
		select: 'Список',
		imageButton: 'Кнопка із зображенням',
		notSet: '<не визначено>',
		id: 'Ідентифікатор',
		name: 'Ім\'я',
		langDir: 'Напрямок мови',
		langDirLtr: 'Зліва направо (LTR)',
		langDirRtl: 'Справа наліво (RTL)',
		langCode: 'Код мови',
		longDescr: 'Довгий опис URL',
		cssClass: 'Клас CSS',
		advisoryTitle: 'Заголовок',
		cssStyle: 'Стиль CSS',
		ok: 'ОК',
		cancel: 'Скасувати',
		close: 'Закрити',
		preview: 'Попередній перегляд',
		resize: 'Потягніть для зміни розмірів',
		generalTab: 'Основне',
		advancedTab: 'Додаткове',
		validateNumberFailed: 'Значення не є цілим числом.',
		confirmNewPage: 'Всі незбережені зміни будуть втрачені. Ви впевнені, що хочете завантажити нову сторінку?',
		confirmCancel: 'Деякі опції змінено. Закрити вікно без збереження змін?',
		options: 'Опції',
		target: 'Ціль',
		targetNew: 'Нове вікно (_blank)',
		targetTop: 'Поточне вікно (_top)',
		targetSelf: 'Поточний фрейм/вікно (_self)',
		targetParent: 'Батьківський фрейм/вікно (_parent)',
		langDirLTR: 'Зліва направо (LTR)',
		langDirRTL: 'Справа наліво (RTL)',
		styles: 'Стиль CSS',
		cssClasses: 'Клас CSS',
		width: 'Ширина',
		height: 'Висота',
		align: 'Вирівнювання',
		alignLeft: 'По лівому краю',
		alignRight: 'По правому краю',
		alignCenter: 'По центру',
		alignTop: 'По верхньому краю',
		alignMiddle: 'По середині',
		alignBottom: 'По нижньому краю',
		invalidValue	: 'Невірне значення.',
		invalidHeight: 'Висота повинна бути цілим числом.',
		invalidWidth: 'Ширина повинна бути цілим числом.',
		invalidCssLength: 'Значення, вказане для "%1" в полі повинно бути позитивним числом або без дійсного виміру CSS блоку (px, %, in, cm, mm, em, ex, pt або pc).',
		invalidHtmlLength: 'Значення, вказане для "%1" в полі повинно бути позитивним числом або без дійсного виміру HTML блоку (px або %).',
		invalidInlineStyle: 'Значення, вказане для вбудованого стилю повинне складатися з одного чи кількох кортежів у форматі "ім\'я : значення", розділених крапкою з комою.',
		cssLengthTooltip: 'Введіть номер значення в пікселях або число з дійсною одиниці CSS (px, %, in, cm, mm, em, ex, pt або pc).',

		// Put the voice-only part of the label in the span.
		unavailable: '%1<span class="cke_accessibility">, не доступне</span>'
	}
};
