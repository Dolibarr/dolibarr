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
	 * Layers of inputs displayed outside the popover (dropdown of select2, suggestions of ajax_autocompleter(), date picker,
	 * dialogs of the rich text editor): a click on them is not a click outside the popover
	 */
	const POPOVER_LAYERS = '.select2-container, .select2-dropdown, .ui-autocomplete, .ui-datepicker, .ui-dialog, .ui-widget-overlay, .cke_dialog, .cke_panel, .cke_dialog_background_cover';

	/**
	 * Button of the popover with an icon, its label as tooltip
	 * @param {string} className
	 * @param {string} icon Class of the Font Awesome icon
	 * @param {string} label
	 * @returns {HTMLButtonElement}
	 */
	function createIconButton(className, icon, label) {
		const button = document.createElement('button');
		button.type = 'button';
		button.className = 'dol-field-popover-button ' + className;
		button.title = label;
		button.setAttribute('aria-label', label);
		const picto = document.createElement('span');
		picto.className = 'fa ' + icon;
		picto.setAttribute('aria-hidden', 'true');
		button.append(picto);

		return button;
	}

	/**
	 * Give the focus to the first input of the editor. A list replaced by select2 (ajax_combobox()) gets the focus on its select2 box,
	 * initialized by the script of the input once it is in the page
	 * @param {HTMLElement} inputs
	 */
	function focusFirstInput(inputs) {
		const first = inputs.querySelector('input:not([type="hidden"]), textarea, select');
		if (!first) return;
		if (first.tagName !== 'SELECT') {
			first.focus();
			return;
		}
		setTimeout(() => {
			const select2 = first.nextElementSibling && first.nextElementSibling.matches('.select2-container') ? first.nextElementSibling.querySelector('.select2-selection') : null;
			(select2 || first).focus();
		}, 0);
	}

	/**
	 * Fields of the editor seen by the user, for the feedback of input-feedback.css (a list replaced by select2 is seen as its select2 box)
	 * @param {HTMLElement} inputs
	 * @returns {HTMLElement[]}
	 */
	function getVisibleInputs(inputs) {
		return [...inputs.querySelectorAll('input:not([type="hidden"]), textarea, select, .select2-selection')];
	}

	/**
	 * Show a feedback of input-feedback.css on the fields of the editor once (ex: fail-feedback), removed at the end of its animation
	 * @param {HTMLElement} inputs
	 * @param {string} className
	 */
	function showInputsFeedback(inputs, className) {
		getVisibleInputs(inputs).forEach(el => {
			el.classList.remove(className);
			void el.offsetWidth; // Restarts the animation when it is running
			el.classList.add(className);
			el.addEventListener('animationend', () => el.classList.remove(className), { once: true });
		});
	}

	/**
	 * Place the popover over the value of the field, its inputs at the place of the value, in the width of the page
	 * @param {HTMLElement} popover
	 * @param {HTMLElement} container Container of the value
	 */
	function placePopover(popover, container) {
		const rect = container.getBoundingClientRect();
		const style = getComputedStyle(popover);
		const offsetX = parseFloat(style.paddingLeft) + parseFloat(style.borderLeftWidth);
		const offsetY = parseFloat(style.paddingTop) + parseFloat(style.borderTopWidth);
		const pageWidth = document.documentElement.clientWidth;
		const margin = 8;

		let left = rect.left - offsetX;
		// A multiline editor (notes) takes at least 600px
		const width = Math.max(rect.width + 2 * offsetX, popover.classList.contains('--multiline') ? 600 : 0);
		popover.style.minWidth = Math.min(width, pageWidth - 2 * margin) + 'px';
		popover.style.maxWidth = (pageWidth - 2 * margin) + 'px';
		// Kept in the page when the value is near the right edge
		left = Math.max(margin, Math.min(left, pageWidth - margin - popover.offsetWidth));

		popover.style.left = (left + window.scrollX) + 'px';
		popover.style.top = (rect.top - offsetY + window.scrollY) + 'px';
	}

	/**
	 * Edition opened on the page, only one at a time: opening another field cancels it
	 * @type {{container: HTMLElement, cancel: function, saving: boolean}|null}
	 */
	let current = null;

	/**
	 * Open the edition of the field of an edit link, in place of its value
	 * @param {HTMLElement} link Edit link printed by Form::editfieldkey()
	 * @returns {Promise<boolean>} False if the edition can not be opened here (the url of the link must then be used)
	 */
	async function open(link) {
		const container = findValueContainer(link);
		if (!container) return false;
		if (container.dataset.dolEditing) return true;

		// Cancel the edition of another field, unless it is being saved
		if (current && current.container !== container && !current.saving) current.cancel();

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
		// Hidden but keeps its place, so the row does not move
		link.style.visibility = 'hidden';

		// The editor is a popover placed over the value, so the layout of the card does not change
		const editor = document.createElement('div');
		editor.className = 'dol-field-popover --light-mode';
		const inputs = document.createElement('div');
		inputs.className = 'dol-field-popover-inputs';
		const actions = document.createElement('div');
		actions.className = 'dol-field-popover-actions';
		const saveButton = createIconButton('dol-field-popover-save', 'fa-check', saveLabel);
		const cancelButton = createIconButton('dol-field-popover-cancel', 'fa-times', cancelLabel);
		actions.append(saveButton, cancelButton);
		editor.append(inputs, actions);
		container.classList.add('dol-field-editing');
		document.body.append(editor);
		Dolibarr.tools.replaceContent(inputs, input, { mode: 'inner' });
		// A multiline editor takes more width
		if (inputs.querySelector('textarea')) editor.classList.add('--multiline');

		const place = () => placePopover(editor, container);
		place();
		window.addEventListener('resize', place);

		focusFirstInput(inputs);

		const state = { container, cancel: () => close(valueHtml), saving: false };
		current = state;

		// Value when opened, a click outside the popover closes it only when nothing was changed
		const initialValue = JSON.stringify(getEditorValue(editor, field));
		const onOutsideClick = event => {
			if (editor.contains(event.target) || event.target.closest(POPOVER_LAYERS) || state.saving) return;
			if (JSON.stringify(getEditorValue(editor, field)) === initialValue) close(valueHtml);
		};
		// Not the click that opened the popover
		setTimeout(() => document.addEventListener('mousedown', onOutsideClick), 0);

		const close = html => {
			if (current === state) current = null;
			document.removeEventListener('mousedown', onOutsideClick);
			window.removeEventListener('resize', place);
			destroyEditors(editor);
			editor.remove();
			delete container.dataset.dolEditing;
			container.classList.remove('dol-field-editing');
			Dolibarr.tools.replaceContent(container, html, { mode: 'inner' });
			link.style.visibility = '';
		};

		// While saving: the fields can not be changed (inert), they show the processing feedback, the save button turns into a spinner
		const saveIcon = saveButton.querySelector('.fa');
		const setSaving = saving => {
			state.saving = saving;
			saveButton.disabled = saving;
			cancelButton.disabled = saving;
			inputs.inert = saving;
			editor.setAttribute('aria-busy', saving ? 'true' : 'false');
			saveIcon.classList.toggle('fa-check', !saving);
			saveIcon.classList.toggle('fa-circle-notch', saving);
			saveIcon.classList.toggle('fa-spin', saving);
			getVisibleInputs(inputs).forEach(el => el.classList.toggle('processing-feedback', saving));
		};

		const save = async() => {
			const value = getEditorValue(editor, field);
			setSaving(true);
			let result;
			try {
				result = await controller.setField(field, value);
			} catch (error) {
				// Error already displayed, the user can correct the value
				setSaving(false);
				showInputsFeedback(inputs, 'fail-feedback');
				focusFirstInput(inputs);
				return;
			}
			if (result === null) {
				close(valueHtml); // Cancelled by a hook
				return;
			}

			close(result.html);
			Dolibarr.tools.card.showUpdatedFeedback(container);
			// Status, action buttons and documents may depend on the field, refreshed when visible
			controller.scheduleSectionsRefresh();
		};

		saveButton.addEventListener('click', save);
		cancelButton.addEventListener('click', () => close(valueHtml));
		editor.addEventListener('keydown', event => {
			// Not the keys used by the suggestions of ajax_autocompleter() (default prevented by the autocomplete)
			if (event.defaultPrevented) return;
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
