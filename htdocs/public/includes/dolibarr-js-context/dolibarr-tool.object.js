document.addEventListener('Dolibarr:Init', function(e) {
	/**
	 * Dolibarr.tools.object
	 * ---------------------
	 * Controller of an object (CommonObject), to read and update it in ajax. Generic: it works for any object loaded by fetchObjectByElement(),
	 * and does not change the page (see Dolibarr.tools.card for the card of an object displayed in the page):
	 * - setField() saves one field with core/ajax/objectfield.php (only fields editable from UI, see CommonObject::isFieldEditableFromUi())
	 * - fetchFields() reads the values of fields with core/ajax/objectfield.php (only fields readable from UI, see CommonObject::isFieldReadableFromUi())
	 * - fetchLines() reads the html of document lines with core/ajax/objectline.php
	 *
	 * The controller keeps the date of modification of the object (tms): an update is refused if the object was modified since.
	 *
	 * Hooks : object:BeforeFieldUpdate (await hook, can change the value or cancel), object:FieldUpdated, object:FieldUpdateError.
	 */

	/**
	 * Controller of an object
	 */
	class DolibarrObjectController {
		/**
		 * @param {string} element Element of the object, ex: 'propal', 'commande', 'facture' or 'mymodule_myobject'
		 * @param {number} id Id of the object
		 * @param {Object} [options]
		 * @param {number} [options.tms=0] Date of modification of the object known by the caller (timestamp), to refuse an update if the object was modified since
		 */
		constructor(element, id, options = {}) {
			this.element = String(element);
			this.id = parseInt(id, 10) || 0;
			this.tms = parseInt(options.tms, 10) || 0;
		}

		/**
		 * Data sent to hooks, to identify the object
		 * @param {Object} data
		 * @returns {Object}
		 */
		hookData(data = {}) {
			return { element: this.element, id: this.id, ...data };
		}

		/**
		 * Save the value of one field of the object
		 *
		 * @param {string} field Field name, must be editable from UI ('uieditable' in $fields)
		 * @param {*} value New value. For a field with several inputs (like a date), an object {suffix: value}
		 *                  with the suffixes of the inputs of showInputField() (ex: {day: 15, month: 10, year: 2026})
		 * @param {Object} [options]
		 * @param {boolean} [options.showErrors=true] Display errors with Dolibarr.tools.setEventMessage()
		 * @param {AbortSignal} [options.signal]
		 * @returns {Promise<Object|null>} Data of the response {element, id, field, value, html, fields, tms}, null if cancelled by a hook.
		 *          fields contains the updated field and the other fields changed by the update {field: {value, html}}
		 * @throws {DolibarrFetchError} status 403 not editable, 409 modified since displayed, 400 invalid value...
		 */
		async setField(field, value, options = {}) {
			const { showErrors = true, signal = undefined } = options;

			// Listeners must return the data (value can be changed), or false to cancel the update
			const hookResult = await Dolibarr.executeHookAwait('object:BeforeFieldUpdate', this.hookData({ field, value }));
			if (hookResult === false) {
				Dolibarr.log(`tools: object: update of ${this.element} ${this.id} ${field} cancelled by a hook`);
				return null;
			}
			if (hookResult && typeof hookResult === 'object' && 'value' in hookResult) {
				value = hookResult.value;
			}

			// Inputs are named like the ones of showInputField() with the key prefix 'ajaxfield_'
			// (read by CommonObject::getFieldValueFromAjaxPost()). An empty value must be posted to empty the field.
			const params = { element: this.element, id: this.id, field };
			if (value !== null && typeof value === 'object' && !Array.isArray(value)) {
				Object.entries(value).forEach(([suffix, val]) => {
					params['ajaxfield_' + field + suffix] = val ?? '';
				});
			} else {
				params['ajaxfield_' + field] = value ?? '';
			}
			if (this.tms) params.tms = this.tms;

			let data;
			try {
				data = await Dolibarr.tools.fetch.post(Dolibarr.getContextVar('DOL_URL_ROOT') + '/core/ajax/objectfield.php', params, {
					responseType: 'json',
					unwrap: true,
					showErrors,
					signal,
				});
			} catch (error) {
				Dolibarr.executeHook('object:FieldUpdateError', this.hookData({ field, value, error }));
				throw error;
			}

			// Next updates are checked against the new date of modification
			if (data.tms) this.tms = parseInt(data.tms, 10) || 0;

			Dolibarr.executeHook('object:FieldUpdated', this.hookData({ field, value: data.value, data }));

			return data;
		}

		/**
		 * Read the values of fields with core/ajax/objectfield.php
		 * @param {string[]} fields
		 * @param {Object} [options] Options of Dolibarr.tools.fetch (showErrors, signal...)
		 * @param {boolean} [options.input=false] Also return the input html of the fields editable from UI (CommonObject::ajaxInputField())
		 * @returns {Promise<{element: string, id: number, fields: Object, tms: number|null}>} fields {field: {value, html, input}}
		 */
		fetchFields(fields, options = {}) {
			const { input = false, ...fetchOptions } = options;
			const params = { element: this.element, id: this.id, fields: fields.join(',') };
			if (input) params.input = 1;

			return Dolibarr.tools.fetch.get(Dolibarr.getContextVar('DOL_URL_ROOT') + '/core/ajax/objectfield.php', params, {
				responseType: 'json', unwrap: true, ...fetchOptions,
			});
		}

		/**
		 * Read the html of document lines with core/ajax/objectline.php.
		 * The event messages waiting in session (ex: kept by a card loaded with noflusheventmessages=1) are returned and displayed
		 * (unless option showEventMessages is false).
		 * @param {number[]} lineIds
		 * @param {Object} [options] Options of Dolibarr.tools.fetch (showErrors, signal...)
		 * @param {string} [options.action] 'editline' for the edit form of one line (rows of objectline_edit.tpl.php)
		 * @param {string} [options.page] Path of the card, like /comm/propal/card.php, so the links of the lines are the ones of the card
		 * @returns {Promise<{lines: Object<number, string|null>, lineids: number[], tms: number|null, eventMessages: Object}>}
		 */
		async fetchLines(lineIds, options = {}) {
			const { action = '', page = '', ...fetchOptions } = options;
			const params = { element: this.element, id: this.id, lineids: lineIds.join(',') };
			if (page) params.page = page;
			if (action) params.action = action;

			// A GET with an action needs the anti CSRF token (MAIN_SECURITY_CSRF_WITH_TOKEN = 3 by default)
			const response = await Dolibarr.tools.fetch.get(Dolibarr.getContextVar('DOL_URL_ROOT') + '/core/ajax/objectline.php', params, {
				responseType: 'json', token: !!action, ...fetchOptions, unwrap: false,
			});

			return { ...response.data, eventMessages: response.eventMessages || {} };
		}

		/**
		 * Listen to the object:* hooks of this object only
		 * @param {string} eventName Name without the prefix 'object:', ex: 'FieldUpdated'
		 * @param {function} callback
		 * @returns {function} Function to remove the listener
		 */
		on(eventName, callback) {
			const hookName = 'object:' + eventName;
			const listener = data => {
				if (data && data.element === this.element && data.id === this.id) callback(data);
			};
			Dolibarr.on(hookName, listener);

			return () => Dolibarr.off(hookName, listener);
		}
	}

	/**
	 * Get a controller of an object
	 * @param {string} element
	 * @param {number} id
	 * @param {Object} [options] See DolibarrObjectController
	 * @returns {DolibarrObjectController}
	 */
	function object(element, id, options = {}) {
		return new DolibarrObjectController(element, id, options);
	}

	object.Controller = DolibarrObjectController;

	Dolibarr.defineTool('object', object);
});
