/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

CKEDITOR.plugins.setLang( 'a11yhelp', 'ro', {
	title: 'Instrucțiuni de accesibilitate',
	contents: 'Cuprins. Pentru a închide acest dialog, apăsați tasta ESC.',
	legend: [
		{
		name: 'General',
		items: [
			{
			name: 'Editează bara instrumente.',
			legend: 'Apasă ${toolbarFocus} pentru a naviga prin bara de instrumente. Pentru a te mișca prin grupurile de instrumente folosește tastele TAB și SHIFT-TAB. Pentru a te mișca intre diverse instrumente folosește tastele SĂGEATĂ DREAPTA sau SĂGEATĂ STÂNGA. Apasă butonul SPAȚIU sau ENTER pentru activarea instrumentului.'
		},

			{
			name: 'Dialog editor',
			legend: 'Într-un dialog, apasă TAB pentru a naviga spre câmpul următor de dialog, apasă SHIFT + TAB pentru a te duce la câmpul anterior, apasă ENTER pentru a trimite dialogul, apasă ESC pentru a anula dialogul. Pentru dialoguri care au mai multe subferestre, apasă ALT + F10 pentr a naviga în lista de subferestre. Treci la subferestrea următoare cu TAB sau SĂGEATĂ DREAPTA. Treci la subfereastra anterioară cu SHIFT + TAB sau SĂGEATĂ STÂNGA. Apasă SPAȚIU sau ENTER pentru a selecta subfereastra.'
		},

			{
			name: 'Editor meniu contextual',
			legend: 'Apasă ${contextMenu} sau TASTA MENIU pentru a deschide meniul contextual. Treci la următoarea opțiune din meniu cu TAB sau SĂGEATĂ JOS. Treci la opțiunea anterioară cu  SHIFT+TAB sau SĂGEATĂ SUS. Apasă SPAȚIU sau ENTER pentru a selecta opțiunea din meniu. Deschide sub-meniul opțiunii curente cu SPAȚIU sau ENTER sau SĂGEATĂ DREAPTA. Revino la elementul din meniul părinte cu ESC sau SĂGEATĂ STÂNGA. Închide meniul de context cu ESC.'
		},

			{
			name: 'Editor Casetă Listă',
			legend: 'În interiorul unei liste, treci la următorull element cu TAB sau SĂGEATĂ JOS. Treci la elementul anterior din listă cu SHIFT + TAB sau SĂGEATĂ SUS. Apasă SPAȚIU sau ENTER pentru a selecta opțiunea din listă. Apasă ESC pentru a închide lista.'
		},

			{
			name: 'Editor Element Path Bar', // MISSING
			legend: 'Press ${elementsPathFocus} to navigate to the elements path bar. Move to next element button with TAB or RIGHT ARROW. Move to previous button with  SHIFT+TAB or LEFT ARROW. Press SPACE or ENTER to select the element in editor.' // MISSING
		}
		]
	},
		{
		name: 'Commands', // MISSING
		items: [
			{
			name: ' Undo command', // MISSING
			legend: 'Press ${undo}' // MISSING
		},
			{
			name: ' Redo command', // MISSING
			legend: 'Press ${redo}' // MISSING
		},
			{
			name: ' Bold command', // MISSING
			legend: 'Press ${bold}' // MISSING
		},
			{
			name: ' Italic command', // MISSING
			legend: 'Press ${italic}' // MISSING
		},
			{
			name: ' Underline command', // MISSING
			legend: 'Press ${underline}' // MISSING
		},
			{
			name: ' Link command', // MISSING
			legend: 'Press ${link}' // MISSING
		},
			{
			name: ' Toolbar Collapse command', // MISSING
			legend: 'Press ${toolbarCollapse}' // MISSING
		},
			{
			name: ' Access previous focus space command', // MISSING
			legend: 'Press ${accessPreviousSpace} to access the closest unreachable focus space before the caret, for example: two adjacent HR elements. Repeat the key combination to reach distant focus spaces.' // MISSING
		},
			{
			name: ' Access next focus space command', // MISSING
			legend: 'Press ${accessNextSpace} to access the closest unreachable focus space after the caret, for example: two adjacent HR elements. Repeat the key combination to reach distant focus spaces.' // MISSING
		},
			{
			name: ' Accessibility Help', // MISSING
			legend: 'Press ${a11yHelp}' // MISSING
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
