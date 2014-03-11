/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

CKEDITOR.plugins.setLang( 'a11yhelp', 'he', {
	title: 'הוראות נגישות',
	contents: 'הוראות נגישות. לסגירה לחץ אסקייפ (ESC).',
	legend: [
		{
		name: 'כללי',
		items: [
			{
			name: 'סרגל הכלים',
			legend: 'לחץ על ${toolbarFocus} כדי לנווט לסרגל הכלים. עבור לכפתור הבא עם מקש הטאב (TAB) או חץ שמאלי. עבור לכפתור הקודם עם מקש השיפט (SHIFT) + טאב (TAB) או חץ ימני. לחץ רווח או אנטר (ENTER) כדי להפעיל את הכפתור הנבחר.'
		},

			{
			name: 'דיאלוגים (חלונות תשאול)',
			legend: 'בתוך דיאלוג, לחץ טאב (TAB) כדי לנווט לשדה הבא, לחץ שיפט (SHIFT) + טאב (TAB) כדי לנווט לשדה הקודם, לחץ אנטר (ENTER) כדי לשלוח את הדיאלוג, לחץ אסקייפ (ESC) כדי לבטל. בתוך דיאלוגים בעלי מספר טאבים (לשוניות), לחץ אלט (ALT) + F10 כדי לנווט לשורת הטאבים. נווט לטאב הבא עם טאב (TAB) או חץ שמאלי. עבור לטאב הקודם עם שיפט (SHIFT) + טאב (TAB) או חץ שמאלי. לחץ רווח או אנטר (ENTER) כדי להיכנס לטאב.'
		},

			{
			name: 'תפריט ההקשר (Context Menu)',
			legend: 'לחץ ${contextMenu} או APPLICATION KEYכדי לפתוח את תפריט ההקשר. עבור לאפשרות הבאה עם טאב (TAB) או חץ למטה. עבור לאפשרות הקודמת עם שיפט (SHIFT) + טאב (TAB) או חץ למעלה. לחץ רווח או אנטר (ENTER) כדי לבחור את האפשרות. פתח את תת התפריט (Sub-menu) של האפשרות הנוכחית עם רווח או אנטר (ENTER) או חץ שמאלי. חזור לתפריט האב עם אסקייפ (ESC) או חץ שמאלי. סגור את תפריט ההקשר עם אסקייפ (ESC).'
		},

			{
			name: 'תפריטים צפים (List boxes)',
			legend: 'בתוך תפריט צף, עבור לפריט הבא עם טאב (TAB) או חץ למטה. עבור לתפריט הקודם עם שיפט (SHIFT) + טאב (TAB) or חץ עליון. Press SPACE or ENTER to select the list option. Press ESC to close the list-box.'
		},

			{
			name: 'עץ אלמנטים (Elements Path)',
			legend: 'לחץ ${elementsPathFocus} כדי לנווט לעץ האלמנטים. עבור לפריט הבא עם טאב (TAB) או חץ ימני. עבור לפריט הקודם עם שיפט (SHIFT) + טאב (TAB) או חץ שמאלי. לחץ רווח או אנטר (ENTER) כדי לבחור את האלמנט בעורך.'
		}
		]
	},
		{
		name: 'פקודות',
		items: [
			{
			name: ' ביטול צעד אחרון',
			legend: 'לחץ ${undo}'
		},
			{
			name: ' חזרה על צעד אחרון',
			legend: 'לחץ ${redo}'
		},
			{
			name: ' הדגשה',
			legend: 'לחץ ${bold}'
		},
			{
			name: ' הטייה',
			legend: 'לחץ ${italic}'
		},
			{
			name: ' הוספת קו תחתון',
			legend: 'לחץ ${underline}'
		},
			{
			name: ' הוספת לינק',
			legend: 'לחץ ${link}'
		},
			{
			name: ' כיווץ סרגל הכלים',
			legend: 'לחץ ${toolbarCollapse}'
		},
			{
			name: 'גישה למיקום המיקוד הקודם',
			legend: 'לחץ ${accessPreviousSpace} כדי לגשת למיקום המיקוד הלא-נגיש הקרוב לפני הסמן, למשל בין שני אלמנטים סמוכים מסוג HR. חזור על צירוף מקשים זה כדי להגיע למקומות מיקוד רחוקים יותר.'
		},
			{
			name: 'גישה למיקום המיקוד הבא',
			legend: 'לחץ ${accessNextSpace} כדי לגשת למיקום המיקוד הלא-נגיש הקרוב אחרי הסמן, למשל בין שני אלמנטים סמוכים מסוג HR. חזור על צירוף מקשים זה כדי להגיע למקומות מיקוד רחוקים יותר.'
		},
			{
			name: ' הוראות נגישות',
			legend: 'לחץ ${a11yHelp}'
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
