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
	 */

	/**
	 * Line in edition, only one at a time like in the card
	 * @type {{controller: Object, lineId: number, row: HTMLElement, form: HTMLFormElement, nodes: Node[], addRows: Array<{row: HTMLElement, placeholder: Comment}>, onSubmit: function, onKeydown: function, saving: boolean}|null}
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
	 * Remove from the page the rows of the form to add a line, they are put back at the end of the edition
	 * @param {HTMLTableElement} table
	 * @returns {Array<{row: HTMLElement, placeholder: Comment}>}
	 */
	function detachAddForm(table) {
		const rows = Array.from(table.rows);
		const start = rows.findIndex(row => row.matches('.liste_titre_add_, .liste_titre_create'));
		if (start < 0) return [];

		return rows.slice(start).map(row => {
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
			await controller.postCard(formData);
			// Messages of the action are returned with the line
			const data = await controller.fetchLines([lineId]);
			const errors = (data.eventMessages && data.eventMessages.errors) || [];
			if (errors.length > 0) {
				// Line not saved, error already displayed, the user can correct the values
				edition.saving = false;
				setButtonsDisabled(false);
				return;
			}

			close();
			const html = data.lines[lineId];
			if (typeof html === 'string') {
				controller.replaceLine(lineId, html, data.lineids.map(id => parseInt(id, 10)));
			} else {
				controller.removeLine(lineId);
			}
			await controller.refreshFields();
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

		current = { controller, lineId, row, form, nodes, addRows, onSubmit, onKeydown, saving: false };
		form.addEventListener('submit', onSubmit);
		form.addEventListener('keydown', onKeydown);

		const firstInput = nodes.map(node => (node instanceof Element) ? node.querySelector('input:not([type="hidden"]):not([type="submit"]), textarea, select') : null).find(input => input);
		if (firstInput) firstInput.focus();

		return true;
	}

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

	Dolibarr.defineTool('lineEdit', { open, close });
});
