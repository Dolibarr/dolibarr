/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

CKEDITOR.plugins.setLang( 'a11yhelp', 'es', {
	title: 'Instrucciones de accesibilidad',
	contents: 'Ayuda. Para cerrar presione ESC.',
	legend: [
		{
		name: 'General',
		items: [
			{
			name: 'Barra de herramientas del editor',
			legend: 'Presiona ${toolbarFocus} para navegar por la barra de herramientas. Para moverse por los distintos grupos de herramientas usa las teclas TAB y MAY-TAB. Para moverse por las distintas herramientas usa FLECHA DERECHA o FECHA IZQUIERDA. Presiona "espacio" o "intro" para activar la herramienta.'
		},

			{
			name: 'Editor de diálogo',
			legend: 'Dentro de un cuadro de diálogo, presione la tecla TAB para desplazarse al campo siguiente del cuadro de diálogo, pulse SHIFT + TAB para desplazarse al campo anterior, pulse ENTER para presentar cuadro de diálogo, pulse la tecla ESC para cancelar el diálogo. Para los diálogos que tienen varias páginas, presione ALT + F10 para navegar a la pestaña de la lista. Luego pasar a la siguiente pestaña con TAB o FLECHA DERECHA. Para ir a la ficha anterior con SHIFT + TAB o FLECHA IZQUIERDA. Presione ESPACIO o ENTRAR para seleccionar la página de ficha.'
		},

			{
			name: 'Editor del menú contextual',
			legend: 'Presiona ${contextMenu} o TECLA MENÚ para abrir el menú contextual. Entonces muévete a la siguiente opción del menú con TAB o FLECHA ABAJO. Muévete a la opción previa con SHIFT + TAB o FLECHA ARRIBA. Presiona ESPACIO o ENTER para seleccionar la opción del menú. Abre el submenú de la opción actual con ESPACIO o ENTER o FLECHA DERECHA. Regresa al elemento padre del menú con ESC o FLECHA IZQUIERDA. Cierra el menú contextual con ESC.'
		},

			{
			name: 'Lista del Editor',
			legend: 'Dentro de una lista, te mueves al siguiente elemento de la lista con TAB o FLECHA ABAJO. Te mueves al elemento previo de la lista con SHIFT + TAB o FLECHA ARRIBA. Presiona ESPACIO o ENTER para elegir la opción de la lista. Presiona ESC para cerrar la lista.'
		},

			{
			name: 'Barra de Ruta del Elemento en el Editor',
			legend: 'Presiona ${elementsPathFocus} para navegar a los elementos de la barra de ruta. Te mueves al siguiente elemento botón con TAB o FLECHA DERECHA. Te mueves al botón previo con SHIFT + TAB o FLECHA IZQUIERDA. Presiona ESPACIO o ENTER para seleccionar el elemento en el editor.'
		}
		]
	},
		{
		name: 'Comandos',
		items: [
			{
			name: 'Comando deshacer',
			legend: 'Presiona ${undo}'
		},
			{
			name: 'Comando rehacer',
			legend: 'Presiona ${redo}'
		},
			{
			name: 'Comando negrita',
			legend: 'Presiona ${bold}'
		},
			{
			name: 'Comando itálica',
			legend: 'Presiona ${italic}'
		},
			{
			name: 'Comando subrayar',
			legend: 'Presiona ${underline}'
		},
			{
			name: 'Comando liga',
			legend: 'Presiona ${liga}'
		},
			{
			name: 'Comando colapsar barra de herramientas',
			legend: 'Presiona ${toolbarCollapse}'
		},
			{
			name: 'Comando accesar el anterior espacio de foco',
			legend: 'Presiona ${accessPreviousSpace} para accesar el espacio de foco no disponible más cercano anterior al cursor, por ejemplo: dos elementos HR adyacentes. Repite la combinación de teclas para alcanzar espacios de foco distantes.'
		},
			{
			name: 'Comando accesar el siguiente spacio de foco',
			legend: 'Presiona ${accessNextSpace} para accesar el espacio de foco no disponible más cercano después del cursor, por ejemplo: dos elementos HR adyacentes. Repite la combinación de teclas para alcanzar espacios de foco distantes.'
		},
			{
			name: 'Ayuda de Accesibilidad',
			legend: 'Presiona ${a11yHelp}'
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
