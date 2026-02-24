document.addEventListener('Dolibarr:Init', function () {

	'use strict';

	Dolibarr.defineTool('uiDialog', async function(selector, param) {

		// --- Load language for messages ---
		await Dolibarr.tools.langs.load('uxdocumentation');

		// --- Default parameters ---
		const defaultParams = {
			dialogClass: 'dol-dialog',
			dialogId: 'dol-dialog-id',
			title: 'UiDialogTitle',
			align: 'center',	// Box alignment
			width: 0,			// Override CSS
			height: 0,		 	// Box height - Overrride CSS - Do not work on align right
			closedby: 'any', 	// 'any' | 'closerequest' | 'none'
			url: null,       	// Ajax url
			animation: true, 	// Enable open/close animations
		};

		// --- Merge params ---
		param = {...defaultParams, ...param};

		// --- Attach click listener on the trigger element ---
		const trigger = document.querySelector(selector);
		if (!trigger) return;

		// --- Open Modal on click
		trigger.addEventListener('click', function () {

			// --- Collect data attributes from trigger element ---
			const triggerData = {...trigger.dataset};

			let dialogClass = defaultParams.dialogClass;
			if (param.align) {
				dialogClass += ' dol-dialog__' + param.align;
			}
			if (!param.animation) {
				dialogClass += ' no-animation';
			}

			let style = '';
			if (param.width) {
				style += 'width:' + (Number.isInteger(param.width) ? param.width + 'px' : param.width) + ';';
			}
			if (param.height && param.align !== 'right') {
				style += 'height:' + (Number.isInteger(param.height) ? param.height + 'px' : param.height) + ';';
			}
			if (style) {
				style = ' style="' + style + '"';
			}

			let innerHTML = '<dialog id="' + param.dialogId + '" class="' + dialogClass + '"' + style + '>';
			if (param.title) {
				innerHTML += '<h2 class="dol-dialog-title">' + param.title + '<button class="dol-dialog-close">&times;</button></h2>';
			}
			let dialogContent = '';
			if (Object.keys(triggerData).length > 0) {
				dialogContent += '<ul>';
				for (const [key, value] of Object.entries(triggerData)) {
					dialogContent += '<li><strong>' + key + '</strong> : ' + value + '</li>';
				}
				dialogContent += '</ul>';
			}
			innerHTML += '<div class="dol-dialog-content">' + dialogContent + '</div>';
			innerHTML += '</dialog>';

			// --- Insert HTML ---
			document.body.insertAdjacentHTML('beforeend', innerHTML);
			const dialogEl = document.getElementById(param.dialogId);

			// --- Load AJAX content if url is provided ---
			if (param.url) {
				const contentEl = dialogEl.querySelector('.dol-dialog-content');
				contentEl.innerHTML = '<span class="dol-dialog-loading">Chargement...</span>';

				const fetchUrl = new URL(param.url, window.location.origin);
				Object.entries(triggerData).forEach(function ([key, value]) {
					fetchUrl.searchParams.set(key, value);
				});

				fetch(fetchUrl.toString())
					.then(function (response) { return response.text(); })
					.then(function (html) { contentEl.innerHTML = html; })
					.catch(function () { contentEl.innerHTML = '<span class="dol-dialog-error">Erreur lors du chargement.</span>'; });
			}

			// --- Show modal ---
			dialogEl.showModal();

			// --- Close with or without animation ---
			function closeDialog() {
				if (!param.animation) {
					dialogEl.remove();
					return;
				}
				dialogEl.classList.add('is-closing');
				dialogEl.addEventListener('animationend', function () { dialogEl.remove(); }, { once: true });
			}

			// Close button
			dialogEl.querySelector('.dol-dialog-close').addEventListener('click', closeDialog);

			// Escape key
			dialogEl.addEventListener('cancel', function (e) {
				e.preventDefault();
				closeDialog();
			});

			// Backdrop click
			dialogEl.addEventListener('click', function (e) {
				if (e.target === dialogEl) closeDialog();
			});
		});
	});
}); // end event listener
