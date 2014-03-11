/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

CKEDITOR.plugins.setLang( 'a11yhelp', 'de', {
	title: 'Barrierefreiheitinformationen',
	contents: 'Hilfeinhalt. Um den Dialog zu schliessen die Taste \'ESC\' drücken.',
	legend: [
		{
		name: 'Allgemein',
		items: [
			{
			name: 'Editor Symbolleiste',
			legend: 'Drücken Sie ${toolbarFocus} auf der Symbolleiste. Gehen Sie zur nächsten oder vorherigen Symbolleistengruppe mit TAB und SHIFT-TAB. Gehen Sie zur nächsten oder vorherigen Symbolleiste auf die Schaltfläche mit dem RECHTS- oder LINKS-Pfeil. Drücken Sie die Leertaste oder Eingabetaste, um die Schaltfläche in der Symbolleiste aktivieren.'
		},

			{
			name: 'Editor Dialog',
			legend: 'Innerhalb des Dialogs drücken Sie TAB um zum nächsten Dialogfeld zu gelangen, drücken Sie SHIFT-TAG um zum vorherigen Feld zu wechseln, drücken Sie ENTER um den Dialog abzusenden und ESC um den Dialog zu abzubrechen. Um zwischen den Reitern innerhalb eines Dialogs zu wechseln drücken sie ALT-F10. Um zum nächsten Reiter zu gelangen können Sie TAB oder die rechte Pfeiltaste. Zurück gelangt man mit SHIFT-TAB oder der linken Pfeiltaste. Mit der Leertaste oder Enter kann man den Reiter auswählen.'
		},

			{
			name: 'Editor Kontextmenü',
			legend: 'Dürcken Sie ${contextMenu} oder die Anwendungstaste um das Kontextmenü zu öffnen. Man kann die Pfeiltasten zum Wechsel benutzen. Mit der Leertaste oder der Enter-Taste kann man den Menüpunkt aufrufen. Schliessen Sie das Kontextmenü mit der ESC-Taste.'
		},

			{
			name: 'Editor Listen',
			legend: 'Innerhalb einer Listenbox kann man mit der TAB-Taste oder den Pfeilrunter-Taste den nächsten Menüeintrag wählen. Mit der Shift-TAB Tastenkombination oder der Pfeilhoch-Taste gelangt man zum vorherigen Menüpunkt. Mit der Leertaste oder Enter kann man den Menüpunkt auswählen. Drücken Sie ESC zum Verlassen des Menüs.'
		},

			{
			name: 'Editor Elementpfadleiste',
			legend: 'Drücken Sie ${elementsPathFocus} um sich durch die Pfadleiste zu bewegen. Um zum nächsten Element zu gelangen drücken Sie TAB oder die Pfeilrechts-Taste. Zum vorherigen Element gelangen Sie mit der SHIFT-TAB oder der Pfeillinks-Taste. Drücken Sie die Leertaste oder Enter um das Element auszuwählen.'
		}
		]
	},
		{
		name: 'Befehle',
		items: [
			{
			name: 'Wiederholen Befehl',
			legend: 'Drücken Sie ${undo}'
		},
			{
			name: 'Rückgängig Befehl',
			legend: 'Drücken Sie ${redo}'
		},
			{
			name: 'Fettschrift Befehl',
			legend: 'Drücken Sie ${bold}'
		},
			{
			name: 'Italic Befehl',
			legend: 'Drücken Sie ${italic}'
		},
			{
			name: 'Unterstreichung Befehl',
			legend: 'Drücken Sie ${underline}'
		},
			{
			name: 'Link Befehl',
			legend: 'Drücken Sie ${link}'
		},
			{
			name: 'Symbolleiste zuammenklappen Befehl',
			legend: 'Drücken Sie ${toolbarCollapse}'
		},
			{
			name: 'Zugang bisheriger Fokussierung Raumbefehl ',
			legend: 'Drücken Sie ${accessPreviousSpace}  auf den am nächsten nicht erreichbar Fokus-Abstand vor die Einfügemarke zugreifen: zwei benachbarte HR-Elemente. Wiederholen Sie die Tastenkombination um entfernte Fokusräume zu erreichen. '
		},
			{
			name: 'Zugang nächster Schwerpunkt Raumbefehl ',
			legend: 'Drücken Sie $ { accessNextSpace }, um den nächsten unerreichbar Fokus Leerzeichen nach dem Cursor zum Beispiel auf: zwei benachbarten HR Elemente. Wiederholen Sie die Tastenkombination zum fernen Fokus Bereiche zu erreichen. '
		},
			{
			name: 'Eingabehilfen',
			legend: 'Drücken Sie ${a11yHelp}'
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
