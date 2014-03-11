/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

CKEDITOR.plugins.setLang( 'a11yhelp', 'no', {
	title: 'Instruksjoner for tilgjengelighet',
	contents: 'Innhold for hjelp. Trykk ESC for å lukke denne dialogen.',
	legend: [
		{
		name: 'Generelt',
		items: [
			{
			name: 'Verktøylinje for editor',
			legend: 'Trykk ${toolbarFocus} for å navigere til verktøylinjen. Flytt til neste og forrige verktøylinjegruppe med TAB og SHIFT-TAB. Flytt til neste og forrige verktøylinjeknapp med HØYRE PILTAST og VENSTRE PILTAST. Trykk MELLOMROM eller ENTER for å aktivere verktøylinjeknappen.'
		},

			{
			name: 'Dialog for editor',
			legend: 'Mens du er i en dialog, trykk TAB for å navigere til neste dialogfelt, press SHIFT + TAB for å flytte til forrige felt, trykk ENTER for å akseptere dialogen, trykk ESC for å avbryte dialogen. For dialoger med flere faner, trykk ALT + F10 for å navigere til listen over faner. Gå til neste fane med TAB eller HØYRE PILTAST. Gå til forrige fane med SHIFT + TAB eller VENSTRE PILTAST. Trykk MELLOMROM eller ENTER for å velge fanen.'
		},

			{
			name: 'Kontekstmeny for editor',
			legend: 'Trykk ${contextMenu} eller MENYKNAPP for å åpne kontekstmeny. Gå til neste alternativ i menyen med TAB eller PILTAST NED. Gå til forrige alternativ med SHIFT+TAB eller PILTAST OPP. Trykk MELLOMROM eller ENTER for å velge menyalternativet. Åpne undermenyen på valgt alternativ med MELLOMROM eller ENTER eller HØYRE PILTAST. Gå tilbake til overordnet menyelement med ESC eller VENSTRE PILTAST. Lukk kontekstmenyen med ESC.'
		},

			{
			name: 'Listeboks for editor',
			legend: 'I en listeboks, gå til neste alternativ i listen med TAB eller PILTAST NED. Gå til forrige alternativ i listen med SHIFT + TAB eller PILTAST OPP. Trykk MELLOMROM eller ENTER for å velge alternativet i listen. Trykk ESC for å lukke listeboksen.'
		},

			{
			name: 'Verktøylinje for elementsti',
			legend: 'Trykk ${elementsPathFocus} for å navigere til verktøylinjen som viser elementsti. Gå til neste elementknapp med TAB eller HØYRE PILTAST. Gå til forrige elementknapp med SHIFT+TAB eller VENSTRE PILTAST. Trykk MELLOMROM eller ENTER for å velge elementet i editoren.'
		}
		]
	},
		{
		name: 'Kommandoer',
		items: [
			{
			name: 'Angre',
			legend: 'Trykk ${undo}'
		},
			{
			name: 'Gjør om',
			legend: 'Trykk ${redo}'
		},
			{
			name: 'Fet tekst',
			legend: 'Trykk ${bold}'
		},
			{
			name: 'Kursiv tekst',
			legend: 'Trykk ${italic}'
		},
			{
			name: 'Understreking',
			legend: 'Trykk ${underline}'
		},
			{
			name: 'Link',
			legend: 'Trykk ${link}'
		},
			{
			name: 'Skjul verktøylinje',
			legend: 'Trykk ${toolbarCollapse}'
		},
			{
			name: 'Gå til forrige fokusområde',
			legend: 'Trykk ${accessPreviousSpace} for å komme til nærmeste fokusområde før skrivemarkøren som ikke kan nås på vanlig måte, for eksempel to tilstøtende HR-elementer. Gjenta tastekombinasjonen for å komme til fokusområder lenger unna i dokumentet.'
		},
			{
			name: 'Gå til neste fokusområde',
			legend: 'Trykk ${accessNextSpace} for å komme til nærmeste fokusområde etter skrivemarkøren som ikke kan nås på vanlig måte, for eksempel to tilstøtende HR-elementer. Gjenta tastekombinasjonen for å komme til fokusområder lenger unna i dokumentet.'
		},
			{
			name: 'Hjelp for tilgjengelighet',
			legend: 'Trykk ${a11yHelp}'
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
