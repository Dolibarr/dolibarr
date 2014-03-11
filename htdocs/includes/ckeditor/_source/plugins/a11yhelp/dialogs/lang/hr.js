/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

CKEDITOR.plugins.setLang( 'a11yhelp', 'hr', {
	title: 'Upute dostupnosti',
	contents: 'Sadržaj pomoći. Za zatvaranje pritisnite ESC.',
	legend: [
		{
		name: 'Općenito',
		items: [
			{
			name: 'Alatna traka',
			legend: 'Pritisni ${toolbarFocus} za navigaciju do alatne trake. Pomicanje do prethodne ili sljedeće alatne grupe vrši se pomoću SHIFT-TAB i TAB. Pomicanje do prethodnog ili sljedećeg gumba u alatnoj traci vrši se pomoću lijeve i desne strelice kursora. Pritisnite SPACE ili ENTER za aktivaciju alatne trake.'
		},

			{
			name: 'Dijalog',
			legend: 'Unutar dijaloga, pritisnite TAB za navigaciju do sljedećeg polja, pritisnite SHIFT + TAB za vraćanje na prethodno polje, pritisnite ENTER za slanje dijaloga ili ESC za zatvaranje dijaloga. Za dijaloge koji imaju višestruke kartice, pritisnite ALT + F10 za na navigaciju i zatim TAB ili lijeva strelica kursora ili SHIFT + TAB i desna strelica kursora. SPACE ili ENTER odabiru karticu.'
		},

			{
			name: 'Kontekstni izbornik',
			legend: 'Pritisnite ${contextMenu} ili APPLICATION tipku za otvaranje kontekstnog izbornika. Pomicanje se vrši TAB ili strelicom kursora prema dolje ili SHIFT+TAB ili strelica kursora prema gore. SPACE ili ENTER odabiru opciju izbornika. Otvorite podizbornik trenutne opcije sa  SPACE, ENTER ili desna strelica kursora. Povratak na prethodni izbornik vrši se sa ESC ili lijevom strelicom kursora. Zatvaranje se vrši pritiskom na tipku ESC.'
		},

			{
			name: 'Lista',
			legend: 'Unutar list-boxa, pomicanje na sljedeću stavku vrši se sa TAB ili strelica kursora prema dolje. Na prethodnu sa SHIFT + TAB ili strelica prema gore. Pritiskom na SPACE ili ENTER odabire se stavka ili ESC za zatvaranje.'
		},

			{
			name: 'Traka putanje elemenata',
			legend: 'Pritisnite ${elementsPathFocus} za navigaciju po putanji elemenata. Pritisnite TAB ili desnu strelicu kursora za pomicanje na sljedeći element ili SHIFT + TAB ili lijeva strelica kursora za pomicanje na prethodni element. Pritiskom na SPACE ili ENTER vrši se odabir elementa.'
		}
		]
	},
		{
		name: 'Naredbe',
		items: [
			{
			name: 'Vrati naredbu',
			legend: 'Pritisni ${undo}'
		},
			{
			name: 'Ponovi naredbu',
			legend: 'Pritisni ${redo}'
		},
			{
			name: 'Bold naredba',
			legend: 'Pritisni ${bold}'
		},
			{
			name: 'Italic naredba',
			legend: 'Pritisni ${italic}'
		},
			{
			name: 'Underline naredba',
			legend: 'Pritisni ${underline}'
		},
			{
			name: 'Link naredba',
			legend: 'Pritisni ${link}'
		},
			{
			name: 'Smanji alatnu traku naredba',
			legend: 'Pritisni ${toolbarCollapse}'
		},
			{
			name: 'Access previous focus space naredba',
			legend: 'Pritisni ${accessPreviousSpace} za pristup najbližem nedostupnom razmaku prije kursora, npr.: dva spojena HR elementa. Ponovnim pritiskom dohvatiti će se sljedeći nedostupni razmak.'
		},
			{
			name: 'Access next focus space naredba',
			legend: 'Pritisni ${accessNextSpace} za pristup najbližem nedostupnom razmaku nakon kursora, npr.: dva spojena HR elementa. Ponovnim pritiskom dohvatiti će se sljedeći nedostupni razmak.'
		},
			{
			name: 'Pomoć za dostupnost',
			legend: 'Pritisni ${a11yHelp}'
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
