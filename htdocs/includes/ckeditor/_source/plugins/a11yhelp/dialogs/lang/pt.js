/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

CKEDITOR.plugins.setLang( 'a11yhelp', 'pt', {
	title: 'Instruções de Acessibilidade',
	contents: 'Conteúdos da Ajuda. Pressione em \'ESC\' para fechar esta janela.',
	legend: [
		{
		name: 'Geral',
		items: [
			{
			name: 'Barra de Ferramentas do Editor',
			legend: 'Clique em ${toolbarFocus} para navegar para a barra de ferramentas. Vá para o grupo da barra de ferramentas anterior e seguinte com TAB e SHIFT+TAB. Vá para o botão da barra de ferramentas anterior com a SETA DIREITA ou ESQUERDA. Pressione ESPAÇO ou ENTER para ativar o botão da barra de ferramentas.'
		},

			{
			name: 'Janela do Editor',
			legend: 'Dentro de uma janela, pressione TAB para navigar para o campo da janela seguinte, pressione SHIFT + TAB para mover para o campo anterior, pressione ENTER para submeter a janela, pressione ESC para cancelar a janela. Para as janelas que têm múltiplos páginas com separadores, pressione ALT + F10 para navegar para a lista do separador. Depois mova para o seguinte separador com TAB ou SETA DIREITA. Mover para o separador anterior com SHIFT + TAB ou SETA ESQUERDA. Pressione ESPAÇO ou ENTER para selecionar o separador da página.'
		},

			{
			name: 'Menu de Contexto do Editor',
			legend: 'Clique em ${contextMenu} ou TECLA APLICAÇÃO para abrir o menu de contexto. Depois vá para a opção do menu seguinte com TAB ou SETA PARA BAIXO. Vá para a opção anterior com  SHIFT+TAB ou SETA PARA CIMA. Pressione ESPAÇO ou ENTER para selecionar a opção do menu.  Abra o submenu da opção atual com ESPAÇO, ENTER ou SETA DIREITA. GVá para o item do menu parente  com ESC ou SETA ESQUERDA. Feche o menu de contexto com ESC.'
		},

			{
			name: 'Caixa Lista Editor',
			legend: 'Dentro da caixa da lista, vá para o itemda lista seguinte com TAB ou SETA PARA BAIXO. Move Vá parao item da lista anterior com SHIFT+TAB ou SETA PARA BAIXO. Pressione ESPAÇO ou ENTER para selecionar a opção da lista. Pressione ESC para fechar a caisa da lista.'
		},

			{
			name: 'Caminho Barra Elemento Editor',
			legend: 'Clique em ${elementsPathFocus} para navegar para a barra do caminho dos elementos. Vá para o botão do elemento seguinte com TAB ou SETA DIREITA. Vá para o botão anterior com   SHIFT+TAB ou SETA ESQUERDA. Pressione ESPAÇO ou ENTER para selecionar o elemento no editor.'
		}
		]
	},
		{
		name: 'Comandos',
		items: [
			{
			name: 'Comando de Anular',
			legend: 'Pressione ${undo}'
		},
			{
			name: 'Comando de Refazer',
			legend: 'Pressione ${redo}'
		},
			{
			name: 'Comando de Negrito',
			legend: 'Pressione ${bold}'
		},
			{
			name: 'Comando de Itálico',
			legend: 'Pressione ${italic}'
		},
			{
			name: 'Comando de Sublinhado',
			legend: 'Pressione ${underline}'
		},
			{
			name: 'Comando de Hiperligação',
			legend: 'Pressione ${link}'
		},
			{
			name: 'Comando de Ocultar Barra de Ferramentas',
			legend: 'Pressione ${toolbarCollapse}'
		},
			{
			name: 'Acesso comando do espaço focus anterior',
			legend: 'Clique em ${accessPreviousSpace} para aceder ao espaço do focos inalcançável mais perto antes do sinal de omissão, por exemplo: dois elementos HR adjacentes. Repetir a combinação da chave para alcançar os espaços dos focos distantes.'
		},
			{
			name: 'Acesso comando do espaço focus seguinte',
			legend: 'Pressione ${accessNextSpace} para aceder ao espaço do focos inalcançável mais perto depois do sinal de omissão, por exemplo: dois elementos HR adjacentes. Repetir a combinação da chave para alcançar os espaços dos focos distantes.'
		},
			{
			name: 'Ajuda de Acessibilidade',
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
