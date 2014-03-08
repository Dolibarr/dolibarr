/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

CKEDITOR.plugins.setLang( 'a11yhelp', 'ru', {
	title: 'Горячие клавиши',
	contents: 'Помощь. Для закрытия этого окна нажмите ESC.',
	legend: [
		{
		name: 'Основное',
		items: [
			{
			name: 'Панель инструментов',
			legend: 'Нажмите ${toolbarFocus} для перехода к панели инструментов. Для перемещения между группами панели инструментов используйте TAB и SHIFT-TAB. Для перемещения между кнопками панели иструментов используйте кнопки ВПРАВО или ВЛЕВО. Нажмите ПРОБЕЛ или ENTER для запуска кнопки панели инструментов.'
		},

			{
			name: 'Диалоги',
			legend: 'В диалоговом окне, нажмите клавишу TAB для перехода к следующему диалоговому полю, нажмите клавиши SHIFT + TAB, чтобы перейти к предыдущему полю, нажмите ENTER, чтобы отправить данные, нажмите клавишу ESC, для отмены. Для окон, которые имеют несколько вкладок, нажмите ALT + F10 для перехода к списку вкладок. Переход к следующей вкладке TAB ИЛИ ПРАВУЮ СТРЕЛКУ. Переход к предыдущей вкладке с помощью SHIFT + TAB или ЛЕВАЯ СТРЕЛКА. Нажмите ПРОБЕЛ или ENTER, чтобы выбрать вкладку.'
		},

			{
			name: 'Контекстное меню',
			legend: 'Нажмите ${contextMenu} или клавишу APPLICATION, чтобы открыть контекстное меню. Затем перейдите к следующему пункту меню с помощью TAB или стрелкой "ВНИЗ". Переход к предыдущей опции - SHIFT+TAB или стрелкой "ВВЕРХ". Нажмите SPACE, или ENTER, чтобы задействовать опцию меню. Открыть подменю текущей опции - SPACE или ENTER или стрелкой "ВПРАВО". Возврат к родительскому пункту меню - ESC или стрелкой "ВЛЕВО". Закрытие контекстного меню - ESC.'
		},

			{
			name: 'Редактор списка',
			legend: 'Внутри окна списка, переход к следующему пункту списка - TAB или стрелкой "ВНИЗ". Переход к предыдущему пункту списка - SHIFT + TAB или стрелкой "ВВЕРХ". Нажмите SPACE, или ENTER, чтобы задействовать опцию списка. Нажмите ESC, чтобы закрыть окно списка.'
		},

			{
			name: 'Путь к элементу',
			legend: 'Нажмите ${elementsPathFocus}, чтобы перейти к панели пути элементов. Переход к следующей кнопке элемента - TAB или стрелкой "ВПРАВО". Переход к предыдущей кнопку - SHIFT+TAB или стрелкой "ВЛЕВО". Нажмите SPACE, или ENTER, чтобы выбрать элемент в редакторе.'
		}
		]
	},
		{
		name: 'Команды',
		items: [
			{
			name: 'Отменить',
			legend: 'Нажмите ${undo}'
		},
			{
			name: 'Повторить',
			legend: 'Нажмите ${redo}'
		},
			{
			name: 'Полужирный',
			legend: 'Нажмите ${bold}'
		},
			{
			name: 'Курсив',
			legend: 'Нажмите ${italic}'
		},
			{
			name: 'Подчеркнутый',
			legend: 'Нажмите ${underline}'
		},
			{
			name: 'Гиперссылка',
			legend: 'Нажмите ${link}'
		},
			{
			name: 'Свернуть панель инструментов',
			legend: 'Нажмите ${toolbarCollapse}'
		},
			{
			name: 'Команды доступа к предыдущему фокусному пространству',
			legend: 'Нажмите ${accessPreviousSpace}, чтобы обратиться к ближайшему недостижимому фокусному пространству перед символом "^", например: два смежных HR элемента. Повторите комбинацию клавиш, чтобы достичь отдаленных фокусных пространств.'
		},
			{
			name: 'Команды доступа к следующему фокусному пространству',
			legend: 'Press ${accessNextSpace} to access the closest unreachable focus space after the caret, for example: two adjacent HR elements. Repeat the key combination to reach distant focus spaces.'
		},
			{
			name: 'Справка по горячим клавишам',
			legend: 'Нажмите ${a11yHelp}'
		}
		]
	}
	],
	backspace: 'Backspace', // MISSING
	tab: 'Tab', // MISSING
	enter: 'Enter', // MISSING
	shift: 'Shift', // MISSING
	ctrl: 'Ctrl', // MISSING
	alt: 'Alt', // MISSING
	pause: 'Pause', // MISSING
	capslock: 'Caps Lock', // MISSING
	escape: 'Escape', // MISSING
	pageUp: 'Page Up', // MISSING
	pageDown: 'Page Down', // MISSING
	end: 'End', // MISSING
	home: 'Home', // MISSING
	leftArrow: 'Left Arrow', // MISSING
	upArrow: 'Up Arrow', // MISSING
	rightArrow: 'Right Arrow', // MISSING
	downArrow: 'Down Arrow', // MISSING
	insert: 'Insert', // MISSING
	'delete': 'Delete', // MISSING
	leftWindowKey: 'Left Windows key', // MISSING
	rightWindowKey: 'Right Windows key', // MISSING
	selectKey: 'Select key', // MISSING
	numpad0: 'Numpad 0', // MISSING
	numpad1: 'Numpad 1', // MISSING
	numpad2: 'Numpad 2', // MISSING
	numpad3: 'Numpad 3', // MISSING
	numpad4: 'Numpad 4', // MISSING
	numpad5: 'Numpad 5', // MISSING
	numpad6: 'Numpad 6', // MISSING
	numpad7: 'Numpad 7', // MISSING
	numpad8: 'Numpad 8', // MISSING
	numpad9: 'Numpad 9', // MISSING
	multiply: 'Multiply', // MISSING
	add: 'Add', // MISSING
	subtract: 'Subtract', // MISSING
	decimalPoint: 'Decimal Point', // MISSING
	divide: 'Divide', // MISSING
	f1: 'F1', // MISSING
	f2: 'F2', // MISSING
	f3: 'F3', // MISSING
	f4: 'F4', // MISSING
	f5: 'F5', // MISSING
	f6: 'F6', // MISSING
	f7: 'F7', // MISSING
	f8: 'F8', // MISSING
	f9: 'F9', // MISSING
	f10: 'F10', // MISSING
	f11: 'F11', // MISSING
	f12: 'F12', // MISSING
	numLock: 'Num Lock', // MISSING
	scrollLock: 'Scroll Lock', // MISSING
	semiColon: 'Semicolon', // MISSING
	equalSign: 'Equal Sign', // MISSING
	comma: 'Comma', // MISSING
	dash: 'Dash', // MISSING
	period: 'Period', // MISSING
	forwardSlash: 'Forward Slash', // MISSING
	graveAccent: 'Grave Accent', // MISSING
	openBracket: 'Open Bracket', // MISSING
	backSlash: 'Backslash', // MISSING
	closeBracket: 'Close Bracket', // MISSING
	singleQuote: 'Single Quote' // MISSING
} );
