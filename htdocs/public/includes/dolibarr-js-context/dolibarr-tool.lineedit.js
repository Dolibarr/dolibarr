document.addEventListener('Dolibarr:Init', function(e) {
	/**
	 * Dolibarr.tools.card.lines
	 * -------------------------
	 * Edition of the document lines of a card in ajax, without reloading the page (experimental, see CommonObject::isAjaxLineEdit()).
	 * It uses the controller of the card (Dolibarr.tools.card), shared with the edition of the fields: same date of modification.
	 * The edit link of a line has the attributes data-dol-edit-line, data-dol-element and data-dol-id, its url is used without javascript.
	 * - A click on the link replaces the line by its edit form, the one of the card (core/ajax/objectline.php with action=editline).
	 *   The form to add a line is removed from the page during the edition: it uses the same names and ids of inputs, like in the card.
	 * - The form is saved by the action updateline of the card, posted with Dolibarr.tools.card().postCard(): all the rules
	 *   of the card are applied (prices, taxes, hooks, triggers, document regenerated...).
	 * - Then the line and the fields displayed (totals...) are refreshed. If the card returns an error, it is displayed and the form stays open.
	 * Lines are also added and deleted in ajax, with the actions addline and confirm_deleteline of the card:
	 * - the button Add of the form to add a line has the attributes data-dol-add-line, data-dol-element and data-dol-id.
	 *   The new lines are inserted at their place, then the form to add a line is emptied.
	 * - the delete link of a line has the attributes data-dol-delete-line, data-dol-element and data-dol-id. The deletion is confirmed first.
	 * The lines next to the lines added or deleted are refreshed too (arrows to move the lines, numbers of the lines).
	 * A value of a line (cells td[data-dol-line-field]) is edited in its cell with a field popover, see editCell().
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

		const controller = Dolibarr.tools.card(link.dataset.dolElement, parseInt(link.dataset.dolId, 10));

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
	 * is visible if the card redirected after the action (lazy load, see scheduleSectionsRefresh())
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
			await controller.refreshSections(doc);
		} else {
			controller.scheduleSectionsRefresh();
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

		const controller = Dolibarr.tools.card(button.dataset.dolElement, parseInt(button.dataset.dolId, 10));
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

		const controller = Dolibarr.tools.card(link.dataset.dolElement, parseInt(link.dataset.dolId, 10));
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

	/**
	 * Edition of the values of the lines in their cell
	 * ------------------------------------------------
	 * A click on a value of a line (span.dol-line-value of the cells td[data-dol-line-field], see objectline_view.tpl.php) opens a compact field popover exactly over it
	 * (Dolibarr.tools.card.fields.openPopover() with autoSave) with the input of the edit form of the line, the one of the card: same lists and formats.
	 * The value is saved when it is changed: choice in a list, Enter, Tab, or leaving the input (click outside). Escape cancels.
	 * The value is saved with the action updateline of the card, posted with all the values of the edit form of the line, like the
	 * button Save: all the rules of the card are applied. The document is generated again later, once after the last change
	 * (Dolibarr.tools.card scheduleDocumentBuild()). Tab saves and opens the next value (Shift + Tab the previous one).
	 */

	/** Cells of the values edited in their cell */
	const CELL_SELECTOR = 'td[data-dol-line-field]';

	/** Value of a cell, the zone that opens its edition (it gets the look of a field on hover) */
	const CELL_VALUE_SELECTOR = CELL_SELECTOR + ' .dol-line-value';

	/**
	 * Edit forms of the lines loaded for the edition of their values (not in the page), by id of line.
	 * Emptied when the object changes (lines or fields refreshed).
	 * @type {Map<number, Promise<{form: HTMLFormElement, tms: number}|null>>}
	 */
	const cellForms = new Map();

	/**
	 * Line, controller and field of a cell
	 * @param {HTMLElement} cell
	 * @returns {{row: HTMLElement, link: HTMLElement, lineId: number, field: string, controller: Object}|null}
	 */
	function getCellContext(cell) {
		const row = cell.closest('tr[id^="row-"]');
		const link = row ? row.querySelector('a[data-dol-edit-line]') : null;
		if (!link) return null;

		return {
			row,
			link,
			lineId: parseInt(row.id.slice(4), 10),
			field: cell.dataset.dolLineField,
			controller: Dolibarr.tools.card(link.dataset.dolElement, parseInt(link.dataset.dolId, 10)),
		};
	}

	/**
	 * Load the edit form of a line, kept out of the page: its scripts are not executed
	 * @param {Object} controller
	 * @param {number} lineId
	 * @param {Object} [options] Options of fetchLines()
	 * @returns {Promise<{form: HTMLFormElement, tms: number}|null>} Null if the line can not be edited
	 */
	function loadCellForm(controller, lineId, options = {}) {
		if (!cellForms.has(lineId)) {
			const promise = controller.fetchLines([lineId], { action: 'editline', ...options }).then(data => {
				const html = data.lines ? data.lines[lineId] : null;
				if (typeof html !== 'string') return null;
				const template = document.createElement('template');
				template.innerHTML = '<table><tbody>' + html + '</tbody></table>';
				template.content.querySelectorAll('script').forEach(script => script.remove());
				const form = document.createElement('form');
				form.append(template.content);
				if (!form.querySelector('[name="lineid"]')) return null;

				return { form, tms: data.tms ? parseInt(data.tms, 10) : 0 };
			});
			promise.catch(() => cellForms.delete(lineId));
			cellForms.set(lineId, promise);
		}

		return cellForms.get(lineId);
	}

	/**
	 * Data posted to save a value: the hidden inputs of the form of the card (outside the table of the lines) and the values of the
	 * edit form of the line, with the new value. Like the scripts of the edit form, a price entered empties the other prices.
	 * @param {HTMLElement} row Line in the page
	 * @param {HTMLFormElement} editForm Edit form of the line
	 * @param {string} field
	 * @param {string} value
	 * @returns {FormData}
	 */
	function getCellFormData(row, editForm, field, value) {
		const data = new FormData();
		const pageForm = row.closest('form');
		const table = row.closest('table');
		if (pageForm) {
			pageForm.querySelectorAll('input[type="hidden"][name]').forEach(input => {
				if (!table.contains(input)) data.append(input.name, input.value);
			});
		}
		new FormData(editForm).forEach((fieldValue, name) => data.append(name, fieldValue));

		data.set(field, value);
		const otherPrices = { price_ht: ['price_ttc', 'multicurrency_subprice'], price_ttc: ['price_ht', 'multicurrency_subprice'] };
		(otherPrices[field] || []).forEach(name => {
			if (data.has(name)) data.set(name, '');
		});
		// Margin rates are computed again from the new values
		['np_marginRate', 'np_markRate'].forEach(name => {
			if (data.has(name)) data.set(name, '');
		});

		data.delete('token'); // Current token of the page added by Dolibarr.tools.fetch
		data.set('action', 'updateline');
		data.set('save', '1');
		data.set('nodocumentbuild', '1'); // Generated once after the last change, see scheduleDocumentBuild()

		return data;
	}

	/**
	 * Texts seen of the cells of a line and of the fields displayed (not the text of their scripts), to show which values changed after a save
	 * @param {Object} controller
	 * @param {HTMLElement} row
	 * @returns {{cells: string[], fields: Map<HTMLElement, string>}}
	 */
	function getDisplayedTexts(controller, row) {
		return {
			cells: Array.from(row.cells).map(cell => cell.innerText.trim()),
			fields: new Map(controller.getFieldContainers().map(el => [el, el.innerText.trim()])),
		};
	}

	/**
	 * Show the feedback of the value saved, and the lighter one on the values changed by the save (total of the line, totals of the card)
	 * @param {Object} controller
	 * @param {number} lineId
	 * @param {string} field
	 * @param {{cells: string[], fields: Map<HTMLElement, string>}} before Texts before the save, see getDisplayedTexts()
	 */
	function showCellFeedback(controller, lineId, field, before) {
		const row = document.getElementById('row-' + lineId);
		if (row) {
			Array.from(row.cells).forEach((cell, index) => {
				if (cell.dataset.dolLineField === field) {
					Dolibarr.tools.card.showUpdatedFeedback(cell);
				} else if (cell.innerText.trim() !== '' && before.cells[index] !== undefined && cell.innerText.trim() !== before.cells[index]) {
					Dolibarr.tools.card.showUpdatedFeedback(cell, { soft: true });
				}
			});
		}
		before.fields.forEach((text, el) => {
			if (el.isConnected && el.innerText.trim() !== text) Dolibarr.tools.card.showUpdatedFeedback(el, { soft: true });
		});
	}

	/**
	 * Mode of edition of a value: 'instant' (saved when changed, no buttons) for a value entered in one input,
	 * 'confirm' (buttons save and cancel) for a value entered in several parts, saved only when confirmed:
	 * several inputs, date (the card posts its parts: day, month, year...), text on several lines, list with several choices.
	 * The mode given by the server on the cell (data-dol-edit-mode) is used first.
	 * @param {HTMLElement} cell
	 * @param {HTMLElement} content Inputs of the value
	 * @param {HTMLFormElement} editForm Edit form of the line
	 * @param {string} field
	 * @returns {string} 'instant' or 'confirm'
	 */
	function getCellEditMode(cell, content, editForm, field) {
		if (['instant', 'confirm'].includes(cell.dataset.dolEditMode)) return cell.dataset.dolEditMode;

		const inputs = content.querySelectorAll('input:not([type="hidden"]), select, textarea');
		const input = inputs[0];
		const severalParts = inputs.length !== 1 || input.tagName === 'TEXTAREA' || input.multiple
			|| ['date', 'datetime-local', 'time'].includes(input.type) || !!editForm.querySelector(`[name="${CSS.escape(field + 'day')}"]`);

		return severalParts ? 'confirm' : 'instant';
	}

	/**
	 * Cell of a value of a line in the page
	 * @param {number} lineId
	 * @param {string} field
	 * @returns {HTMLElement|null}
	 */
	function findCell(lineId, field) {
		const row = document.getElementById('row-' + lineId);

		return row ? row.querySelector(`${CELL_SELECTOR}[data-dol-line-field="${CSS.escape(field)}"]`) : null;
	}

	/**
	 * Value next to a cell, in the order of the page (next line after the last value of a line)
	 * @param {HTMLElement} cell
	 * @param {number} step 1 for the next value, -1 for the previous one
	 * @returns {{lineId: number, field: string}|null}
	 */
	function getSiblingCell(cell, step) {
		const cells = Array.from(document.querySelectorAll(CELL_SELECTOR));
		const sibling = cells[cells.indexOf(cell) + step];
		const context = sibling ? getCellContext(sibling) : null;

		return context ? { lineId: context.lineId, field: context.field } : null;
	}

	/**
	 * Save a value of a line with the action updateline of the card, then refresh the line, the fields displayed and the sections of the card
	 * @param {Object} controller
	 * @param {HTMLElement} row Line in the page
	 * @param {number} lineId
	 * @param {{form: HTMLFormElement, tms: number}} loaded Edit form of the line, and date of modification of the object when it was loaded
	 * @param {string} field
	 * @param {string} value
	 * @returns {Promise<void>} Rejected if the line was not saved (error already displayed)
	 */
	async function saveCellValue(controller, row, lineId, loaded, field, value) {
		const doc = await controller.postCard(getCellFormData(row, loaded.form, field, value));
		// Messages of the action are returned with the line
		const data = await controller.fetchLines([lineId]);
		const errors = (data.eventMessages && data.eventMessages.errors) || [];
		// The line is saved if the object was modified, even with an error after the update
		const changed = !loaded.tms || !data.tms || parseInt(data.tms, 10) !== loaded.tms;
		if (errors.length > 0 && !changed) throw new Error('Line not saved');

		await applyLines(controller, data, [lineId], doc);
		if (data.builddoc) controller.scheduleDocumentBuild();
	}

	/**
	 * Label of a value of a line for the undo message: number of the line and title of the column
	 * @param {HTMLElement} cell
	 * @param {HTMLElement} row
	 * @returns {{number: string, column: string}}
	 */
	function getCellLabel(cell, row) {
		const table = row.closest('table');
		const column = Array.from(cell.classList).find(className => className.startsWith('linecol'));
		const title = column && table ? table.querySelector(`tr.liste_titre .${CSS.escape(column)}`) : null;
		const numCell = row.querySelector('td.linecolnum');
		const number = numCell && numCell.innerText.trim() ? numCell.innerText.trim() : String(getPageLineIds(table).indexOf(parseInt(row.id.slice(4), 10)) + 1);
		const columnLabel = title && title.innerText.trim() ? title.innerText.replace(/\s+/g, ' ').trim() : cell.dataset.dolLineField;

		return { number, column: columnLabel };
	}

	/**
	 * The change of a value of a line can be undone (Dolibarr.tools.card.setUndo()): the value before is saved again with the edit form
	 * loaded before the change, so the other values of the line are the ones before too
	 * @param {Object} controller
	 * @param {number} lineId
	 * @param {string} field
	 * @param {{number: string, column: string}} label See getCellLabel()
	 * @param {string} from Value displayed before
	 * @param {HTMLFormElement} editForm Edit form of the line loaded before the change
	 * @param {string} oldValue Value of the input before the change
	 */
	async function setCellUndo(controller, lineId, field, label, from, editForm, oldValue) {
		const cell = findCell(lineId, field);
		if (!cell) return;
		const lineLabel = await trans('main', 'Line');
		Dolibarr.tools.card.setUndo({
			controller,
			label: lineLabel + ' ' + label.number + ' \u00b7 ' + label.column,
			from,
			to: (cell.querySelector('.dol-line-value') || cell).innerText.trim(),
			undo: async() => {
				const row = document.getElementById('row-' + lineId);
				if (!row) throw new Error('Line not found');
				const before = getDisplayedTexts(controller, row);
				await saveCellValue(controller, row, lineId, { form: editForm, tms: controller.tms }, field, oldValue);
				showCellFeedback(controller, lineId, field, before);
			},
		});
	}

	/**
	 * Open the edition of a value of a line in its cell
	 * @param {HTMLElement} cell td[data-dol-line-field]
	 * @returns {Promise<boolean>} True if opened, or if the edition is not possible now (a line is being edited, error displayed)
	 */
	async function editCell(cell) {
		// One edition of a line at a time, like in the card
		if (current || cell.dataset.dolEditing || cell.querySelector('[data-dol-editing]') || !Dolibarr.tools.card.fields) return true;
		const context = getCellContext(cell);
		if (!context) return false;
		const { row, link, lineId, field, controller } = context;

		cell.dataset.dolEditing = '1';
		let loaded;
		try {
			loaded = await loadCellForm(controller, lineId);
		} catch (error) {
			return true; // Error already displayed
		} finally {
			delete cell.dataset.dolEditing;
		}
		if (current || !cell.isConnected) return true;

		// Input of the value in the edit form of the line, the whole edit form is opened when the value is not editable alone
		const source = loaded ? loaded.form.querySelector(`[name="${CSS.escape(field)}"]`) : null;
		if (!source || source.type === 'hidden' || source.readOnly || source.disabled) {
			// Classic edition by reloading the page when the line can not be edited here
			if (!(await open(link))) window.location.href = link.href;
			return true;
		}

		const input = source.cloneNode(true);
		input.removeAttribute('id'); // Same ids as the form to add a line
		const content = document.createElement('div');
		content.append(input);

		input.dataset.dolInitialValue = input.value; // Value before the change, to undo it
		const before = getDisplayedTexts(controller, row);
		const label = getCellLabel(cell, row);
		const valueText = (cell.querySelector('.dol-line-value') || cell).innerText.trim();
		let saved = false;
		let next = null;

		// Placed over the value (span.dol-line-value), saved when changed like a cell of a spreadsheet, unless the value has several parts
		await Dolibarr.tools.card.fields.openPopover(cell.querySelector('.dol-line-value') || cell, {
			content,
			autoSave: getCellEditMode(cell, content, loaded.form, field) === 'instant',
			className: '--line-value',
			getValue: () => input.value,
			save: async value => {
				await saveCellValue(controller, row, lineId, loaded, field, value);
				saved = true;
			},
			onKeydown: (event, popover) => {
				if (event.key !== 'Tab' || popover.saving) return;
				event.preventDefault();
				next = getSiblingCell(cell, event.shiftKey ? -1 : 1);
				if (popover.isChanged()) {
					popover.save();
				} else {
					popover.close();
				}
			},
			onClose: () => {
				if (saved) {
					showCellFeedback(controller, lineId, field, before);
					setCellUndo(controller, lineId, field, label, valueText, loaded.form, input.dataset.dolInitialValue);
				}
				const nextCell = next ? findCell(next.lineId, next.field) : null;
				next = null;
				if (nextCell) setTimeout(() => editCell(nextCell), 0);
			},
		});

		return true;
	}

	// The edit forms loaded are not up to date anymore when the object changes
	['card:FieldsRefreshed', 'card:LineReloaded', 'card:LineRemoved'].forEach(hookName => Dolibarr.on(hookName, () => cellForms.clear()));

	// Values of the lines printed by objectline_view.tpl.php when the lines are edited in ajax
	// Only the value opens the edition, not the rest of the cell
	document.addEventListener('click', event => {
		const value = event.target.closest(CELL_VALUE_SELECTOR);
		const cell = value ? value.closest(CELL_SELECTOR) : null;
		if (!cell || event.ctrlKey || event.metaKey || event.shiftKey || event.target.closest('a, button, input, select, textarea')) return;
		// Text selected by the user
		if (String(window.getSelection()) !== '') return;

		editCell(cell);
	});

	// The edit form of a line is loaded when the mouse stays on one of its values, so the edition opens at once
	let preloadTimer = null;
	document.addEventListener('pointerover', event => {
		const value = event.target.closest && event.target.closest(CELL_VALUE_SELECTOR);
		const cell = value ? value.closest(CELL_SELECTOR) : null;
		clearTimeout(preloadTimer);
		if (!cell || current) return;
		preloadTimer = setTimeout(() => {
			const context = getCellContext(cell);
			if (context && !cellForms.has(context.lineId)) loadCellForm(context.controller, context.lineId, { showErrors: false }).catch(() => {});
		}, 150);
	});

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

	Dolibarr.tools.card.definePart('lines', { open, close, editCell, addLine, deleteLine });
});
