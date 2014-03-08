/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

CKEDITOR.plugins.setLang( 'a11yhelp', 'eo', {
	title: 'Uzindikoj pri atingeblo',
	contents: 'Helpilenhavo. Por fermi tiun dialogon, premu la ESKAPAN klavon.',
	legend: [
		{
		name: 'Ĝeneralaĵoj',
		items: [
			{
			name: 'Ilbreto de la redaktilo',
			legend: 'Premu ${toolbarFocus} por atingi la ilbreton. Moviĝu al la sekva aŭ antaŭa grupoj de la ilbreto per la klavoj TABA kaj MAJUSKLIGA-TABA. Moviĝu al la sekva aŭ antaŭa butonoj de la ilbreto per la klavoj SAGO DEKSTREN kaj SAGO MALDEKSTREN. Premu la SPACETklavon aŭ la ENENklavon por aktivigi la ilbretbutonon.'
		},

			{
			name: 'Redaktildialogo',
			legend: 'En dialogo, premu la TABAN klavon por navigi al la sekva dialogkampo, premu la MAJUSKLIGAN + TABAN klavojn por reveni al la antaŭa kampo, premu la ENENklavon por sendi la dialogon, premu la ESKAPAN klavon por nuligi la dialogon. Por dialogoj kun pluraj retpaĝoj sub langetoj, premu ALT + F10 por navigi al la langetlisto. Poste moviĝu al la sekva langeto per la klavo TABA aŭ SAGO DEKSTREN. Moviĝu al la antaŭa langeto per la klavoj MAJUSKLIGA + TABA aŭ  SAGO MALDEKSTREN. Premu la SPACETklavon aŭ la ENENklavon por selekti la langetretpaĝon.'
		},

			{
			name: 'Kunteksta menuo de la redaktilo',
			legend: 'Premu ${contextMenu} aŭ entajpu la KLAVKOMBINAĴON por malfermi la kuntekstan menuon. Poste moviĝu al la sekva opcio de la menuo per la klavoj TABA aŭ SAGO SUBEN. Moviĝu al la antaŭa opcio per la klavoj MAJUSKLGA + TABA aŭ SAGO SUPREN. Premu la SPACETklavon aŭ ENENklavon por selekti la menuopcion. Malfermu la submenuon de la kuranta opcio per la SPACETklavo aŭ la ENENklavo aŭ la SAGO DEKSTREN. Revenu al la elemento de la patra menuo per la klavoj ESKAPA aŭ SAGO MALDEKSTREN. Fermu la kuntekstan menuon per la ESKAPA klavo.'
		},

			{
			name: 'Fallisto de la redaktilo',
			legend: 'En fallisto, moviĝu al la sekva listelemento per la klavoj TABA aŭ SAGO SUBEN. Moviĝu al la antaŭa listelemento per la klavoj MAJUSKLIGA + TABA aŭ SAGO SUPREN. Premu la SPACETklavon aŭ ENENklavon por selekti la opcion en la listo. Premu la ESKAPAN klavon por fermi la falmenuon.'
		},

			{
			name: 'Breto indikanta la vojon al la redaktilelementoj',
			legend: 'Premu ${elementsPathFocus} por navigi al la breto indikanta la vojon al la redaktilelementoj. Moviĝu al la butono de la sekva elemento per la klavoj TABA aŭ SAGO DEKSTREN. Moviĝu al la butono de la antaŭa elemento per la klavoj MAJUSKLIGA + TABA aŭ SAGO MALDEKSTREN. Premu la SPACETklavon aŭ ENENklavon por selekti la elementon en la redaktilo.'
		}
		]
	},
		{
		name: 'Komandoj',
		items: [
			{
			name: 'Komando malfari',
			legend: 'Premu ${undo}'
		},
			{
			name: 'Komando refari',
			legend: 'Premu ${redo}'
		},
			{
			name: 'Komando grasa',
			legend: 'Premu ${bold}'
		},
			{
			name: 'Komando kursiva',
			legend: 'Premu ${italic}'
		},
			{
			name: 'Komando substreki',
			legend: 'Premu ${underline}'
		},
			{
			name: 'Komando ligilo',
			legend: 'Premu ${link}'
		},
			{
			name: 'Komando faldi la ilbreton',
			legend: 'Premu ${toolbarCollapse}'
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
			name: 'Helpilo pri atingeblo',
			legend: 'Premu ${a11yHelp}'
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
