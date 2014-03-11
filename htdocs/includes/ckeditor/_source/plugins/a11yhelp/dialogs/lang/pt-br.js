/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

CKEDITOR.plugins.setLang( 'a11yhelp', 'pt-br', {
	title: 'Instruções de Acessibilidade',
	contents: 'Conteúdo da Ajuda. Para fechar este diálogo pressione ESC.',
	legend: [
		{
		name: 'Geral',
		items: [
			{
			name: 'Barra de Ferramentas do Editor',
			legend: 'Pressione ${toolbarFocus} para navegar para a barra de ferramentas. Mova para o anterior ou próximo grupo de ferramentas com TAB e SHIFT-TAB. Mova para o anterior ou próximo botão com SETA PARA DIREITA or SETA PARA ESQUERDA. Pressione ESPAÇO ou ENTER para ativar o botão da barra de ferramentas.'
		},

			{
			name: 'Diálogo do Editor',
			legend: 'Dentro de um diálogo, pressione TAB para navegar para o próximo campo, pressione SHIFT + TAB para mover para o campo anterior, pressione ENTER para enviar o diálogo, pressione ESC para cancelar o diálogo. Para diálogos que tem múltiplas abas, pressione ALT + F10 para navegar para a lista de abas, então mova para a próxima aba com SHIFT + TAB ou SETA PARA ESQUERDA. Pressione ESPAÇO ou ENTER para selecionar a aba.'
		},

			{
			name: 'Menu de Contexto do Editor',
			legend: 'Pressione ${contextMenu} ou TECLA DE MENU para abrir o menu de contexto, então mova para a próxima opção com TAB ou SETA PARA BAIXO. Mova para a anterior com SHIFT+TAB ou SETA PARA CIMA. Pressione ESPAÇO ou ENTER para selecionar a opção do menu. Abra o submenu da opção atual com ESPAÇO ou ENTER ou SETA PARA DIREITA. Volte para o menu pai com ESC ou SETA PARA ESQUERDA. Feche o menu de contexto com ESC.'
		},

			{
			name: 'Caixa de Lista do Editor',
			legend: 'Dentro de uma caixa de lista, mova para o próximo item com TAB ou SETA PARA BAIXO. Mova para o item anterior com SHIFT + TAB ou SETA PARA CIMA. Pressione ESPAÇO ou ENTER para selecionar uma opção na lista. Pressione ESC para fechar a caixa de lista.'
		},

			{
			name: 'Barra de Caminho do Elementos do Editor',
			legend: 'Pressione ${elementsPathFocus} para a barra de caminho dos elementos. Mova para o próximo botão de elemento com TAB ou SETA PARA DIREITA. Mova para o botão anterior com  SHIFT+TAB ou SETA PARA ESQUERDA. Pressione ESPAÇO ou ENTER para selecionar o elemento no editor.'
		}
		]
	},
		{
		name: 'Comandos',
		items: [
			{
			name: ' Comando Desfazer',
			legend: 'Pressione ${undo}'
		},
			{
			name: ' Comando Refazer',
			legend: 'Pressione ${redo}'
		},
			{
			name: ' Comando Negrito',
			legend: 'Pressione ${bold}'
		},
			{
			name: ' Comando Itálico',
			legend: 'Pressione ${italic}'
		},
			{
			name: ' Comando Sublinhado',
			legend: 'Pressione ${underline}'
		},
			{
			name: ' Comando Link',
			legend: 'Pressione ${link}'
		},
			{
			name: ' Comando Fechar Barra de Ferramentas',
			legend: 'Pressione ${toolbarCollapse}'
		},
			{
			name: 'Acessar o comando anterior de spaço de foco',
			legend: 'Pressione ${accessNextSpace} para acessar o espaço de foco não alcançável mais próximo antes do cursor, por exemplo: dois elementos HR adjacentes. Repita a combinação de teclas para alcançar espaços de foco distantes.'
		},
			{
			name: 'Acessar próximo fomando de spaço de foco',
			legend: 'Pressione ${accessNextSpace} para acessar o espaço de foco não alcançável mais próximo após o cursor, por exemplo: dois elementos HR adjacentes. Repita a combinação de teclas para alcançar espaços de foco distantes.'
		},
			{
			name: ' Ajuda de Acessibilidade',
			legend: 'Pressione ${a11yHelp}'
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
