/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

CKEDITOR.plugins.setLang( 'a11yhelp', 'cy', {
	title: 'Canllawiau Hygyrchedd',
	contents: 'Cynnwys Cymorth. I gau y deialog hwn, pwyswch ESC.',
	legend: [
		{
		name: 'Cyffredinol',
		items: [
			{
			name: 'Bar Offer y Golygydd',
			legend: 'Pwyswch $ {toolbarFocus} i fynd at y bar offer. Symudwch i\'r grŵp bar offer nesaf a blaenorol gyda TAB a SHIFT-TAB. Symudwch i\'r botwm bar offer nesaf a blaenorol gyda SAETH DDE neu SAETH CHWITH. Pwyswch SPACE neu ENTER i wneud botwm y bar offer yn weithredol.'
		},

			{
			name: 'Deialog y Golygydd',
			legend: 'Tu mewn i\'r deialog, pwyswch TAB i fynd i\'r maes nesaf ar y deialog, pwyswch SHIFT + TAB i symud i faes blaenorol, pwyswch ENTER i gyflwyno\'r deialog, pwyswch ESC i ddiddymu\'r deialog. Ar gyfer deialogau sydd â thudalennau aml-tab, pwyswch ALT + F10 i lywio\'r tab-restr. Yna symudwch i\'r tab nesaf gyda TAB neu SAETH DDE. Symudwch i dab blaenorol gyda SHIFT + TAB neu\'r SAETH CHWITH. Pwyswch SPACE neu ENTER i ddewis y dudalen tab.'
		},

			{
			name: 'Dewislen Cyd-destun y Golygydd',
			legend: 'Pwyswch $ {contextMenu} neu\'r ALLWEDD \'APPLICATION\' i agor y ddewislen cyd-destun. Yna symudwch i\'r opsiwn ddewislen nesaf gyda\'r TAB neu\'r SAETH I LAWR. Symudwch i\'r opsiwn blaenorol gyda SHIFT + TAB neu\'r SAETH I FYNY. Pwyswch SPACE neu ENTER i ddewis yr opsiwn ddewislen. Agorwch is-dewislen yr opsiwn cyfredol gyda SPACE neu ENTER neu SAETH DDE. Ewch yn ôl i\'r eitem ar y ddewislen uwch gydag ESC neu SAETH CHWITH. Ceuwch y ddewislen cyd-destun gydag ESC.'
		},

			{
			name: 'Blwch Rhestr y Golygydd',
			legend: 'Tu mewn y blwch rhestr, ewch i\'r eitem rhestr nesaf gyda TAB neu\'r SAETH I LAWR. Symudwch i restr eitem flaenorol gyda SHIFT + TAB neu SAETH I FYNY. Pwyswch SPACE neu ENTER i ddewis yr opsiwn o\'r rhestr. Pwyswch ESC i gau\'r rhestr.'
		},

			{
			name: 'Bar Llwybr Elfen y Golygydd',
			legend: 'Pwyswch ${elementsPathFocus} i fynd i\'r bar llwybr elfennau. Symudwch i fotwm yr elfen nesaf gyda TAB neu SAETH DDE. Symudwch i fotwm blaenorol gyda SHIFT + TAB neu SAETH CHWITH. Pwyswch SPACE neu ENTER i ddewis yr elfen yn y golygydd.'
		}
		]
	},
		{
		name: 'Gorchmynion',
		items: [
			{
			name: 'Gorchymyn dadwneud',
			legend: 'Pwyswch ${undo}'
		},
			{
			name: 'Gorchymyn ailadrodd',
			legend: 'Pwyswch ${redo}'
		},
			{
			name: 'Gorchymyn Bras',
			legend: 'Pwyswch ${bold}'
		},
			{
			name: 'Gorchymyn italig',
			legend: 'Pwyswch ${italig}'
		},
			{
			name: 'Gorchymyn tanlinellu',
			legend: 'Pwyso ${underline}'
		},
			{
			name: 'Gorchymyn dolen',
			legend: 'Pwyswch ${link}'
		},
			{
			name: 'Gorchymyn Cwympo\'r Dewislen',
			legend: 'Pwyswch ${toolbarCollapse}'
		},
			{
			name: 'Myned i orchymyn bwlch ffocws blaenorol',
			legend: 'Pwyswch ${accessPreviousSpace} i fyned i\'r "blwch ffocws sydd methu ei gyrraedd" cyn y caret, er enghraifft: dwy elfen HR drws nesaf i\'w gilydd. AIladroddwch y cyfuniad allwedd i gyrraedd bylchau ffocws pell.'
		},
			{
			name: 'Ewch i\'r gorchymyn blwch ffocws nesaf',
			legend: 'Pwyswch ${accessNextSpace} i fyned i\'r blwch ffocws agosaf nad oes modd ei gyrraedd ar ôl y caret, er enghraifft: dwy elfen HR drws nesaf i\'w gilydd. Ailadroddwch y cyfuniad allwedd i gyrraedd blychau ffocws pell.'
		},
			{
			name: 'Cymorth Hygyrchedd',
			legend: 'Pwyswch ${a11yHelp}'
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
