document.addEventListener('Dolibarr:Init', function(e) {
	/**
	 * Dolibarr.tools.card.fields
	 * --------------------------
	 * Edition of the fields of a card in ajax, without reloading the page (experimental, see Form::isAjaxFieldEdit()).
	 * Form::editfieldkey() and Form::editfieldval() print the markup when a field is edited in ajax :
	 * - the edit link has the attributes data-dol-edit-field, data-dol-element, data-dol-id and data-dol-tms, its url is used without javascript
	 * - the value is in a container [data-dol-field][data-dol-element][data-dol-id]
	 * A click on the link replaces the value by the input of the field (core/ajax/objectfield.php, CommonObject::ajaxInputField()).
	 * The value is saved with setField() of the controller of the card (Dolibarr.tools.card), which refreshes the other fields changed
	 * by the update (containers [data-dol-field] of the page). The sections of the card (status, action buttons, documents) are refreshed
	 * when they are visible. The controller of the card is shared with the edition of the lines: same date of modification.
	 */

	/**
	 * Translate a label of the main domain, the key itself if the langs tool is not available
	 * @param {string} key
	 * @returns {Promise<string>}
	 */
	async function transMain(key) {
		if (!Dolibarr.checkToolExist('langs')) return key;
		try {
			await Dolibarr.tools.langs.load('main');
			return Dolibarr.tools.langs.transNoEntities(key); // Used as text, not html
		} catch (error) {
			return key;
		}
	}

	/**
	 * Container of the value of the field of an edit link
	 * @param {HTMLElement} link
	 * @returns {HTMLElement|null}
	 */
	function findValueContainer(link) {
		const { dolEditField: field, dolElement: element, dolId: id } = link.dataset;

		return document.querySelector(`[data-dol-field="${CSS.escape(field)}"][data-dol-element="${CSS.escape(element)}"][data-dol-id="${CSS.escape(id)}"]`);
	}

	/**
	 * Values of the inputs of a field, named like the inputs of showInputField() with the key prefix 'ajaxfield_'
	 * @param {HTMLElement} editor
	 * @param {string} field
	 * @returns {*} Value, or object {suffix: value} for a field with several inputs (see setField())
	 */
	function getEditorValue(editor, field) {
		// Rich text editors write their content into their textarea only when asked
		if (window.CKEDITOR && window.CKEDITOR.instances) {
			Object.values(window.CKEDITOR.instances).forEach(instance => {
				if (instance.element && editor.contains(instance.element.$)) instance.updateElement();
			});
		}

		const prefix = 'ajaxfield_' + field;
		const values = {};
		editor.querySelectorAll('[name^="' + prefix + '"]').forEach(input => {
			if ((input.type === 'checkbox' || input.type === 'radio') && !input.checked) return;
			values[input.name.slice(prefix.length)] = input.value;
		});

		const suffixes = Object.keys(values);

		return (suffixes.length === 1 && suffixes[0] === '') ? values[''] : values;
	}

	/**
	 * Destroy the rich text editors of an element before it is removed from the page
	 * @param {HTMLElement} el
	 */
	function destroyEditors(el) {
		if (!window.CKEDITOR || !window.CKEDITOR.instances) return;
		Object.values(window.CKEDITOR.instances).forEach(instance => {
			if (instance.element && el.contains(instance.element.$)) instance.destroy(true);
		});
	}

	/**
	 * Open the edition of the field of an edit link, in place of its value
	 * @param {HTMLElement} link Edit link printed by Form::editfieldkey()
	 * @returns {Promise<boolean>} False if the edition can not be opened here (the url of the link must then be used)
	 */
	async function open(link) {
		const container = findValueContainer(link);
		if (!container) return false;
		if (container.dataset.dolEditing) return true;

		const field = link.dataset.dolEditField;
		const element = link.dataset.dolElement;
		const id = parseInt(link.dataset.dolId, 10);
		const controller = Dolibarr.tools.card(element, id, { tms: parseInt(link.dataset.dolTms, 10) || 0 });

		container.dataset.dolEditing = '1';
		let data;
		try {
			data = await controller.object.fetchFields([field], { input: true });
		} catch (error) {
			delete container.dataset.dolEditing;
			return true; // Error already displayed
		}

		const input = data.fields && data.fields[field] ? data.fields[field].input : null;
		if (typeof input !== 'string') {
			delete container.dataset.dolEditing;
			return false;
		}

		const [saveLabel, cancelLabel] = await Promise.all([transMain('Save'), transMain('Cancel')]);

		const valueHtml = container.innerHTML;
		link.style.display = 'none';

		const editor = document.createElement('span');
		editor.className = 'dol-field-editor';
		const inputs = document.createElement('span');
		const saveButton = document.createElement('input');
		saveButton.type = 'button';
		saveButton.className = 'button smallpaddingimp nomargingtop nomarginbottom';
		saveButton.value = saveLabel;
		const cancelButton = document.createElement('input');
		cancelButton.type = 'button';
		cancelButton.className = 'button button-cancel smallpaddingimp nomargingtop nomarginbottom';
		cancelButton.value = cancelLabel;
		editor.append(inputs, ' ', saveButton, ' ', cancelButton);
		container.replaceChildren(editor);
		Dolibarr.tools.replaceContent(inputs, input, { mode: 'inner' });
		// A multiline editor takes the whole width, its buttons go below it
		if (inputs.querySelector('textarea')) {
			editor.style.display = 'block';
			inputs.style.display = 'block';
		}

		const firstInput = inputs.querySelector('input:not([type="hidden"]), textarea, select');
		if (firstInput) firstInput.focus();

		const close = html => {
			destroyEditors(editor);
			delete container.dataset.dolEditing;
			Dolibarr.tools.replaceContent(container, html, { mode: 'inner' });
			link.style.display = '';
		};

		const save = async() => {
			saveButton.disabled = true;
			cancelButton.disabled = true;
			let result;
			try {
				result = await controller.setField(field, getEditorValue(editor, field));
			} catch (error) {
				// Error already displayed, the user can correct the value
				saveButton.disabled = false;
				cancelButton.disabled = false;
				return;
			}
			if (result === null) {
				close(valueHtml); // Cancelled by a hook
				return;
			}

			close(result.html);
			// Status, action buttons and documents may depend on the field, refreshed when visible
			controller.scheduleSectionsRefresh();
		};

		saveButton.addEventListener('click', save);
		cancelButton.addEventListener('click', () => close(valueHtml));
		editor.addEventListener('keydown', event => {
			if (event.key === 'Escape') {
				event.preventDefault();
				close(valueHtml);
			} else if (event.key === 'Enter' && event.target.tagName === 'INPUT' && event.target.type === 'text') {
				event.preventDefault();
				save();
			}
		});

		return true;
	}

	// Edit links printed by Form::editfieldkey() for fields edited in ajax
	document.addEventListener('click', event => {
		const link = event.target.closest('a[data-dol-edit-field]');
		if (!link || event.ctrlKey || event.metaKey || event.shiftKey) return;

		event.preventDefault();
		open(link).then(opened => {
			// Field not editable here (ex: rights changed since the page was displayed), classic edition by reloading the page
			if (!opened) window.location.href = link.href;
		});
	});

	Dolibarr.tools.card.definePart('fields', { open });
});
