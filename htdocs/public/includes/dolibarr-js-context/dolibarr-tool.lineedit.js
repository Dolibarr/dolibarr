document.addEventListener('Dolibarr:Init', function(e) {
	/**
	 * Dolibarr.tools.lineEdit
	 * -----------------------
	 * Edition of the document lines of a card in ajax, without reloading the page (see CommonObject::isAjaxLineEdit()).
	 * The edit link of a line has the attributes data-dol-edit-line, data-dol-element and data-dol-id, its url is used without javascript.
	 * - A click on the link replaces the line by its edit form, the one of the card (core/ajax/objectline.php with action=editline).
	 *   The form to add a line is removed from the page during the edition: it uses the same names and ids of inputs, like in the card.
	 * - The form is saved by the action updateline of the card, posted with Dolibarr.tools.object().postCard(): all the rules
	 *   of the card are applied (prices, taxes, hooks, triggers, document regenerated...).
	 * - Then the line and the fields displayed (totals...) are refreshed. If the card returns an error, it is displayed and the form stays open.
	 * Lines are also added and deleted in ajax, with the actions addline and confirm_deleteline of the card:
	 * - the button Add of the form to add a line has the attributes data-dol-add-line, data-dol-element and data-dol-id.
	 *   The new lines are inserted at their place, then the form to add a line is emptied.
	 * - the delete link of a line has the attributes data-dol-delete-line, data-dol-element and data-dol-id. The deletion is confirmed first.
	 * The lines next to the lines added or deleted are refreshed too (arrows to move the lines, numbers of the lines).
	 */

	/**
	 * Line in edition, only one at a time like in the card
	 * @type {{controller: Object, lineId: number, row: HTMLElement, form: HTMLFormElement, nodes: Node[], addRows: Array<{row: HTMLElement, placeholder: Comment}>, onSubmit: function, onKeydown: function, saving: boolean, tms: number}|null}
	 */
	let current = null;

	/**
	 * Call a function for the rich text editors of nodes
	 * @param {Node[]} nodes
	 * @param {function} callback Called with each CKEditor instance
	 */
	function forEachEditor(nodes, callback) {
		if (!window.CKEDITOR || !window.CKEDITOR.instances) return;
		Object.values(window.CKEDITOR.instances).forEach(instance => {
			if (instance.element && nodes.some(node => node.contains && node.contains(instance.element.$))) callback(instance);
		});
	}

	/**
	 * Rows of the form to add a line, at the end of the table of the lines
	 * @param {HTMLTableElement} table
	 * @returns {HTMLElement[]}
	 */
	function getAddFormRows(table) {
		const rows = Array.from(table.rows);
		const start = rows.findIndex(row => row.matches('.liste_titre_add_, .liste_titre_create'));

		return start < 0 ? [] : rows.slice(start);
	}

	/**
	 * Remove from the page the rows of the form to add a line, they are put back at the end of the edition
	 * @param {HTMLTableElement} table
	 * @returns {Array<{row: HTMLElement, placeholder: Comment}>}
	 */
	function detachAddForm(table) {
		return getAddFormRows(table).map(row => {
			const placeholder = document.createComment('dol-line-add-form');
			row.replaceWith(placeholder);
			return { row, placeholder };
		});
	}

	/**
	 * Enable or disable the buttons of the edit form
	 * @param {boolean} disabled
	 */
	function setButtonsDisabled(disabled) {
		current.nodes.forEach(node => {
			if (!(node instanceof Element)) return;
			node.querySelectorAll('input[type="submit"], button[type="submit"]').forEach(button => {
				button.disabled = disabled;
			});
		});
	}

	/**
	 * Close the edition: the edit form is removed, the line and the form to add a line are put back
	 */
	function close() {
		if (!current) return;
		const { row, form, nodes, addRows, onSubmit, onKeydown } = current;
		current = null;

		forEachEditor(nodes, instance => instance.destroy(true));
		nodes.forEach(node => node.remove());
		row.style.display = '';
		addRows.forEach(({ row: addRow, placeholder }) => placeholder.replaceWith(addRow));
		form.removeEventListener('submit', onSubmit);
		form.removeEventListener('keydown', onKeydown);
	}

	/**
	 * Save the line with the action updateline of the card, then refresh the line and the fields displayed
	 * @param {HTMLElement|null} submitter Button used to submit the form
	 */
	async function save(submitter) {
		if (!current || current.saving) return;
		const edition = current;
		const { controller, lineId, form, nodes } = edition;

		edition.saving = true;
		setButtonsDisabled(true);

		// Rich text editors write their content into their textarea only when asked
		forEachEditor(nodes, instance => instance.updateElement());

		// Same data as the form of the card submitted with the button Save
		const formData = new FormData(form);
		formData.delete('token'); // Current token of the page added by Dolibarr.tools.fetch
		formData.set('action', 'updateline');
		formData.set('save', submitter && submitter.name === 'save' ? submitter.value : '1');

		try {
			const doc = await controller.postCard(formData);
			// Messages of the action are returned with the line
			const data = await controller.fetchLines([lineId]);
			const errors = (data.eventMessages && data.eventMessages.errors) || [];
			// The line is saved if the object was modified, even with an error after the update (ex: document not generated)
			const saved = !edition.tms || !data.tms || parseInt(data.tms, 10) !== edition.tms;
			if (errors.length > 0 && !saved) {
				// Line not saved, error already displayed, the user can correct the values
				edition.saving = false;
				setButtonsDisabled(false);
				return;
			}

			close();
			await applyLines(controller, data, [lineId], doc);
		} catch (error) {
			// Error already displayed
			if (current === edition) {
				edition.saving = false;
				setButtonsDisabled(false);
			}
		}
	}

	/**
	 * Open the edition of the line of an edit link, in place of the line
	 * @param {HTMLElement} link Edit link of a line (a[data-dol-edit-line])
	 * @returns {Promise<boolean>} False if the edition can not be opened here (the url of the link must then be used)
	 */
	async function open(link) {
		// One line at a time, like in the card
		if (current) return true;

		const lineId = parseInt(link.dataset.dolEditLine, 10);
		const row = document.getElementById('row-' + lineId);
		const form = row ? row.closest('form') : null;
		const table = row ? row.closest('table') : null;
		if (!row || !form || !table) return false;

		const controller = Dolibarr.tools.object(link.dataset.dolElement, parseInt(link.dataset.dolId, 10));

		let data;
		try {
			data = await controller.fetchLines([lineId], { action: 'editline' });
		} catch (error) {
			return true; // Error already displayed
		}

		// The edit form is not returned if the line can not be edited (object not a draft anymore...)
		const html = data.lines ? data.lines[lineId] : null;
		const template = document.createElement('template');
		template.innerHTML = '<table><tbody>' + (typeof html === 'string' ? html : '') + '</tbody></table>';
		if (typeof html !== 'string' || !template.content.querySelector('[name="lineid"]')) return false;
		if (current) return true; // Another line opened during the request

		// The form to add a line uses the same names and ids of inputs: it is removed before the scripts of the edit form are executed
		const addRows = detachAddForm(table);

		const start = document.createComment('dol-line-edit');
		const end = document.createComment('/dol-line-edit');
		const placeholder = document.createElement('tr');
		row.before(start, placeholder, end);
		row.style.display = 'none';
		Dolibarr.tools.replaceContent(placeholder, html);

		const nodes = [start];
		for (let node = start.nextSibling; node && node !== end; node = node.nextSibling) nodes.push(node);
		nodes.push(end);

		const onSubmit = event => {
			event.preventDefault();
			if (event.submitter && event.submitter.name === 'cancel') {
				close();
			} else {
				save(event.submitter || null);
			}
		};
		const onKeydown = event => {
			if (event.key === 'Escape' && nodes.some(node => node.contains && node.contains(event.target))) {
				event.preventDefault();
				close();
			}
		};

		// Date of modification of the object when the edition is opened, to know if the line was saved
		const tms = data.tms ? parseInt(data.tms, 10) : 0;
		current = { controller, lineId, row, form, nodes, addRows, onSubmit, onKeydown, saving: false, tms };
		form.addEventListener('submit', onSubmit);
		form.addEventListener('keydown', onKeydown);

		const firstInput = nodes.map(node => (node instanceof Element) ? node.querySelector('input:not([type="hidden"]):not([type="submit"]), textarea, select') : null).find(input => input);
		if (firstInput) firstInput.focus();

		return true;
	}

	/**
	 * Translate a label, the key itself if the langs tool is not available
	 * @param {string} domain
	 * @param {string} key
	 * @returns {Promise<string>}
	 */
	async function trans(domain, key) {
		if (!Dolibarr.checkToolExist('langs')) return key;
		try {
			await Dolibarr.tools.langs.load(domain);
			return Dolibarr.tools.langs.transNoEntities(key); // Used as text, not html
		} catch (error) {
			return key;
		}
	}

	/**
	 * Ids of the lines displayed in a table, in their order
	 * @param {HTMLTableElement} table
	 * @returns {number[]}
	 */
	function getPageLineIds(table) {
		return Array.from(table.querySelectorAll('tr[id^="row-"]')).map(row => parseInt(row.id.slice(4), 10)).filter(id => id > 0);
	}

	/**
	 * Lines to refresh after lines are added or deleted: the lines changed and the lines next to them (arrows to move the lines),
	 * and all the lines after them if the numbers of the lines are displayed
	 * @param {number[]} changedIds Lines added or deleted
	 * @param {number[]} orderedIds Ids of all the lines in their order (with the lines deleted at their former place)
	 * @param {HTMLTableElement} table
	 * @returns {number[]}
	 */
	function getLinesToRefresh(changedIds, orderedIds, table) {
		const ids = new Set(changedIds);
		const withNumbers = !!table.querySelector('td.linecolnum');
		changedIds.forEach(changedId => {
			const index = orderedIds.indexOf(changedId);
			if (index < 0) return;
			if (index > 0) ids.add(orderedIds[index - 1]);
			(withNumbers ? orderedIds.slice(index + 1) : orderedIds.slice(index + 1, index + 2)).forEach(id => ids.add(id));
		});

		return Array.from(ids);
	}

	/**
	 * Replace or remove the lines of the page with the lines loaded by core/ajax/objectline.php, then refresh the fields displayed (totals...)
	 * and the sections of the card (status, action buttons, documents): with the card returned by the action, or later when a section
	 * is visible if the card redirected after the action (lazy load, see scheduleCardSectionsRefresh())
	 * @param {Object} controller
	 * @param {Object} data Answer of fetchLines()
	 * @param {number[]} lineIds Lines asked
	 * @param {Document|null} doc Card returned by postCard()
	 */
	async function applyLines(controller, data, lineIds, doc) {
		const allLineIds = data.lineids.map(id => parseInt(id, 10));
		lineIds.forEach(lineId => {
			const html = data.lines[lineId];
			if (typeof html === 'string') {
				controller.replaceLine(lineId, html, allLineIds);
			} else {
				controller.removeLine(lineId);
			}
		});
		await controller.refreshFields();
		if (doc) {
			await controller.refreshCardSections(doc);
		} else {
			controller.scheduleCardSectionsRefresh();
		}
	}

	/**
	 * Values of the form to add a line before the user changes them, to empty the form after a line is added like when the card is reloaded
	 * (hidden inputs can not be reset by the browser, their default value changes with their value)
	 * @type {WeakMap<HTMLTableElement, Array<{el: HTMLElement, value: string, checked: boolean}>>}
	 */
	const addFormSnapshots = new WeakMap();

	/**
	 * Save the values of the form to add a line of a table, once
	 * @param {HTMLTableElement} table
	 */
	function snapshotAddForm(table) {
		if (addFormSnapshots.has(table)) return;
		const controls = [];
		getAddFormRows(table).forEach(row => row.querySelectorAll('input, select, textarea').forEach(el => {
			if (['submit', 'button', 'image', 'file'].includes(el.type)) return;
			controls.push({ el, value: el.value, checked: !!el.checked });
		}));
		addFormSnapshots.set(table, controls);
	}

	/**
	 * Put back the values of the form to add a line saved before the user changed them
	 * @param {HTMLTableElement} table
	 */
	function resetAddForm(table) {
		const controls = addFormSnapshots.get(table);
		if (!controls) return;

		controls.forEach(({ el, value, checked }) => {
			if (!el.isConnected) return;
			if (el.type === 'checkbox' || el.type === 'radio') {
				el.checked = checked;
			} else {
				el.value = value;
			}
		});
		// Rich text editors and select boxes (select2...) display the value of their input
		forEachEditor(getAddFormRows(table), instance => instance.setData(instance.element.$.value || ''));
		controls.forEach(({ el }) => {
			if (el.isConnected && (el.tagName === 'SELECT' || el.type === 'radio' || el.type === 'checkbox') && window.jQuery) window.jQuery(el).trigger('change');
		});
	}

	/**
	 * Add a line with the action addline of the card, then insert the new lines and refresh the fields displayed
	 * @param {HTMLInputElement|HTMLButtonElement} button Button Add of the form to add a line
	 * @returns {Promise<boolean>} False if the line can not be added here (the form must then be submitted)
	 */
	async function addLine(button) {
		const form = button.form;
		const table = button.closest('table');
		if (!form || !table || button.dataset.dolAdding) return !!button.dataset.dolAdding;

		const before = getPageLineIds(table);
		// No line yet: the table of the lines is built by the card
		if (before.length === 0) return false;

		const controller = Dolibarr.tools.object(button.dataset.dolElement, parseInt(button.dataset.dolId, 10));
		button.dataset.dolAdding = '1';
		button.disabled = true;

		// Rich text editors write their content into their textarea only when asked
		forEachEditor([form], instance => instance.updateElement());

		// Same data as the form of the card submitted with the button Add
		const formData = new FormData(form);
		formData.delete('token'); // Current token of the page added by Dolibarr.tools.fetch
		formData.set('action', 'addline');
		formData.set(button.name, button.value);

		try {
			const doc = await controller.postCard(formData);
			// Messages of the action and order of the lines, read with the last line of the page
			const result = await controller.fetchLines([before[before.length - 1]]);
			const allLineIds = result.lineids.map(id => parseInt(id, 10));
			const added = allLineIds.filter(id => !before.includes(id));
			// No line added: error already displayed, the user can correct the values.
			// Lines added are inserted even with an error after the action (ex: document not generated).
			if (added.length === 0) return true;

			const lineIds = getLinesToRefresh(added, allLineIds, table);
			await applyLines(controller, await controller.fetchLines(lineIds, { showEventMessages: false }), lineIds, doc);
			resetAddForm(table);
		} catch (error) {
			// Error already displayed
		} finally {
			delete button.dataset.dolAdding;
			button.disabled = false;
		}

		return true;
	}

	/**
	 * Ask the confirmation to delete a line, with a dialog like the confirmations of the cards
	 * @returns {Promise<boolean>}
	 */
	async function confirmDeleteLine() {
		const [title, question, yes, no] = await Promise.all([
			trans('products', 'DeleteProductLine'), trans('products', 'ConfirmDeleteProductLine'), trans('main', 'Yes'), trans('main', 'No'),
		]);

		const $ = window.jQuery;
		if (!$ || !$.fn || !$.fn.dialog) return window.confirm(question);

		return new Promise(resolve => {
			const dialog = $('<div></div>').text(question);
			dialog.dialog({
				title,
				modal: true,
				resizable: false,
				width: 'auto',
				buttons: [
					{ text: yes, click: () => { resolve(true); dialog.dialog('close'); } },
					{ text: no, click: () => dialog.dialog('close') },
				],
				close: () => {
					resolve(false);
					dialog.dialog('destroy').remove();
				},
			});
		});
	}

	/**
	 * Delete a line with the action confirm_deleteline of the card, after a confirmation, then remove it from the page
	 * @param {HTMLElement} link Delete link of a line (a[data-dol-delete-line])
	 * @returns {Promise<boolean>} False if the line can not be deleted here (the url of the link must then be used)
	 */
	async function deleteLine(link) {
		const lineId = parseInt(link.dataset.dolDeleteLine, 10);
		const row = document.getElementById('row-' + lineId);
		const table = row ? row.closest('table') : null;
		if (!row || !table) return false;
		if (row.dataset.dolDeleting) return true;

		if (!(await confirmDeleteLine())) return true;

		const controller = Dolibarr.tools.object(link.dataset.dolElement, parseInt(link.dataset.dolId, 10));
		const lineIds = getLinesToRefresh([lineId], getPageLineIds(table), table);
		row.dataset.dolDeleting = '1';

		try {
			const doc = await controller.postCard({ action: 'confirm_deleteline', confirm: 'yes', lineid: lineId });
			const data = await controller.fetchLines(lineIds);
			// Line still there: not deleted, error already displayed. A line deleted is removed even with an error after the action.
			if (typeof data.lines[lineId] === 'string') {
				delete row.dataset.dolDeleting;
				return true;
			}
			await applyLines(controller, data, lineIds, doc);
		} catch (error) {
			// Error already displayed
			delete row.dataset.dolDeleting;
		}

		return true;
	}

	// Values of the form to add a line saved before the user changes them, to empty it after a line is added
	document.addEventListener('focusin', event => {
		const table = event.target.closest && event.target.closest('table');
		if (table && table.querySelector('[data-dol-add-line]') && getAddFormRows(table).some(row => row.contains(event.target))) snapshotAddForm(table);
	}, true);
	document.addEventListener('mousedown', event => {
		const table = event.target.closest && event.target.closest('table');
		if (table && table.querySelector('[data-dol-add-line]') && getAddFormRows(table).some(row => row.contains(event.target))) snapshotAddForm(table);
	}, true);

	// Button Add of the form to add a line printed by objectline_create.tpl.php when the lines are edited in ajax
	document.addEventListener('submit', event => {
		const button = event.submitter;
		if (!button || !button.matches('[data-dol-add-line]') || current) return;
		if (button.dataset.dolClassicSubmit) {
			delete button.dataset.dolClassicSubmit;
			return;
		}

		event.preventDefault();
		addLine(button).then(added => {
			// Line not added here (no line yet...), classic submit of the form
			if (!added) {
				button.dataset.dolClassicSubmit = '1';
				button.form.requestSubmit(button);
			}
		});
	});

	// Delete links of the lines printed by objectline_view.tpl.php when the lines are edited in ajax
	document.addEventListener('click', event => {
		const link = event.target.closest('a[data-dol-delete-line]');
		if (!link || event.ctrlKey || event.metaKey || event.shiftKey) return;

		event.preventDefault();
		deleteLine(link).then(deleted => {
			if (!deleted) window.location.href = link.href;
		});
	});

	// Edit links of the lines printed by objectline_view.tpl.php when the lines are edited in ajax
	document.addEventListener('click', event => {
		const link = event.target.closest('a[data-dol-edit-line]');
		if (!link || event.ctrlKey || event.metaKey || event.shiftKey) return;

		event.preventDefault();
		open(link).then(opened => {
			// Line not editable here, classic edition by reloading the page
			if (!opened) window.location.href = link.href;
		});
	});

	Dolibarr.defineTool('lineEdit', { open, close, addLine, deleteLine });
});
