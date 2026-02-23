document.addEventListener('Dolibarr:Init', function () {

	'use strict';

	Dolibarr.defineTool('uiDialog', async function(param) {

		// --- Load language for messages ---
		await Dolibarr.tools.langs.load('uxdocumentation');

		// --- Default parameters ---
		const defaultParams = {
			dialogClass: 'dialog',
			dialogId: 'dialogId',
			title: 'test',     // Min height of the dropzone
			align: 'center', // 0 = no auto submit, 1 = submit after change
			width: '500px',     // Min width of the dropzone
			height: '300px',    // @todo height management - height 100% si right
		};

		// --- Merge params ---
		param = {...defaultParams, ...param};

		let dialogClass = defaultParams.dialogClass;
		if (param.align) {
			dialogClass += ' dialog__' + param.align;
		}

		let style = '';
		if (param.width) {
			style += 'width:' + param.width + ';';
		}
		if (param.height) {
			style += 'height:' + param.height + ';';
		}
		if (style) {
			style = ' style="' + style + '"';
		}

		let innerHTML = '<dialog open id="' + param.dialogId + '" class="' + dialogClass + '"' + style + '>';
		if (param.title) {
			innerHTML += '<h2 class="dialog-title">' + param.title + '</h2>';
		}
		innerHTML += '<div class="dialog-content"></div>';
		innerHTML += '</dialog>';

		document.body.innerHTML = innerHTML;
	});
}); // end event listener
