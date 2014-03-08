/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

CKEDITOR.plugins.setLang( 'a11yhelp', 'zh', {
	title: '輔助工具指南',
	contents: '說明內容。若要關閉此對話框請按「ESC」。',
	legend: [
		{
		name: '一般',
		items: [
			{
			name: '編輯器工具列',
			legend: '請按「${toolbarFocus}」以瀏覽工具列。\r\n利用「TAB」或「SHIFT+TAB」以便移動到下一個或前一個工具列群組。\r\n利用「→」或「←」以便移動到下一個或前一個工具列按鈕。\r\n請按下「空白鍵」或「ENTER」鍵啟動工具列按鈕。'
		},

			{
			name: '編輯器對話方塊',
			legend: '在對話框中，請按 TAB 鍵以便移動到下個欄位，請按 SHIFT + TAB 以便移動到前個欄位；請按 ENTER 以提交對話框資料，或按下 ESC 取消對話框。\r\n若是有多個頁框的對話框，請按 ALT + F10 以移動到頁框列表，並以 TAB 或是 → 方向鍵移動到下個頁框。以 SHIFT + TAB 或是 ← 方向鍵移動到前個頁框。按下 空白鍵 或是 ENTER 以選取頁框。'
		},

			{
			name: '編輯器內容功能表',
			legend: '請按下「${contextMenu}」或是「應用程式鍵」以開啟內容選單。以「TAB」或是「↓」鍵移動到下一個選單選項。以「SHIFT + TAB」或是「↑」鍵移動到上一個選單選項。按下「空白鍵」或是「ENTER」鍵以選取選單選項。以「空白鍵」或「ENTER」或「→」開啟目前選項之子選單。以「ESC」或「←」回到父選單。以「ESC」鍵關閉內容選單」。'
		},

			{
			name: '編輯器清單方塊',
			legend: '在列表中，請利用 TAB  或  ↓ 方向鍵以移動到下一個項目；或利用 SHIFT + TAB 或 ↑ 方向鍵移動到前一個項目。請按下 空白鍵 或是 ENTER 以選取項目。請按 ESC 關閉列表。'
		},

			{
			name: '編輯器元件路徑工具列',
			legend: '請按「${elementsPathFocus}」以瀏覽元素路徑工具列。\r\n利用「TAB」或「→」以便移動到下一個元素按鈕。\r\n利用「SHIFT+TAB」或「←」以便移動到前一個元素按鈕。\r\n請按下「空白鍵」或「ENTER」鍵選擇編輯器中的元素。'
		}
		]
	},
		{
		name: '命令',
		items: [
			{
			name: '復原命令',
			legend: '請按下「${undo}」'
		},
			{
			name: '重複命令',
			legend: '請按下「 ${redo}」'
		},
			{
			name: '粗體命令',
			legend: '請按下「${bold}」'
		},
			{
			name: '斜體',
			legend: '請按下「${italic}」'
		},
			{
			name: '底線命令',
			legend: '請按下「${underline}」'
		},
			{
			name: '連結',
			legend: '請按下「${link}」'
		},
			{
			name: '隱藏工具列',
			legend: '請按下「${toolbarCollapse}」'
		},
			{
			name: '存取前一個焦點空間命令',
			legend: '請按下 ${accessPreviousSpace} 以存取最近但無法靠近之插字符號前的焦點空間。舉例：二個相鄰的 HR 元素。\r\n重複按鍵以存取較遠的焦點空間。'
		},
			{
			name: '存取下一個焦點空間命令',
			legend: '請按下 ${accessNextSpace} 以存取最近但無法靠近之插字符號後的焦點空間。舉例：二個相鄰的 HR 元素。\r\n重複按鍵以存取較遠的焦點空間。'
		},
			{
			name: '協助工具說明',
			legend: '請按下「${a11yHelp}」'
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
