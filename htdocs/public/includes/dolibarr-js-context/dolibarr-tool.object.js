document.addEventListener('Dolibarr:Init', function(e) {
	/**
	 * Dolibarr.tools.object
	 * ---------------------
	 * Controller of an object (CommonObject) displayed on a card, to update it in ajax without reloading the page :
	 * - setField() saves one field with core/ajax/objectfield.php (only fields editable from UI, see CommonObject::isFieldEditableFromUi())
	 * - reload() reloads sections and document lines of the card : the card is loaded again in background
	 *   and the elements are replaced by their new version with Dolibarr.tools.replaceContent()
	 *
	 * Hooks : object:BeforeFieldUpdate (await hook, can change the value or cancel), object:FieldUpdated, object:FieldUpdateError,
	 * object:SectionReloaded, object:LineReloaded, and reloadDocumentLine for each line reloaded.
	 */

	/**
	 * Parameters of the url of the card that must not be sent again when the card is reloaded
	 * (the card must be displayed, not an action executed again)
	 */
	const ACTION_PARAMS = ['action', 'confirm', 'token', 'massaction'];

	/**
	 * Url of the current card, without action parameters
	 * @returns {string}
	 */
	function getCurrentCardUrl() {
		const url = new URL(window.location.href);
		ACTION_PARAMS.forEach(param => url.searchParams.delete(param));
		url.hash = '';
		return url.toString();
	}

	/**
	 * Update the anti CSRF tokens of the page with the ones of a page loaded in background :
	 * loading a page renews the token of the session, the token of the page would be refused
	 * @param {Document} doc
	 */
	function syncCsrfToken(doc) {
		const metaNew = document.querySelector('meta[name="anti-csrf-newtoken"]');
		const oldToken = metaNew ? metaNew.getAttribute('content') : '';

		['anti-csrf-currenttoken', 'anti-csrf-newtoken'].forEach(name => {
			const meta = document.querySelector(`meta[name="${name}"]`);
			const loadedMeta = doc.querySelector(`meta[name="${name}"]`);
			if (meta && loadedMeta) meta.setAttribute('content', loadedMeta.getAttribute('content') || '');
		});

		// Token of forms and links changed only if it is renewed on each call (MAIN_SECURITY_CSRF_TOKEN_RENEWAL_ON_EACH_CALL)
		const newToken = metaNew ? metaNew.getAttribute('content') : '';
		if (!oldToken || !newToken || oldToken === newToken) return;

		document.querySelectorAll('input[name="token"]').forEach(input => {
			if (input.value === oldToken) input.value = newToken;
		});
		document.querySelectorAll(`a[href*="token=${oldToken}"]`).forEach(link => {
			link.setAttribute('href', link.getAttribute('href').split(`token=${oldToken}`).join(`token=${newToken}`));
		});
	}

	/**
	 * Controller of an object
	 */
	class DolibarrObjectController {
		/**
		 * @param {string} element Element of the object, ex: 'propal', 'commande', 'facture' or 'mymodule_myobject'
		 * @param {number} id Id of the object
		 * @param {Object} [options]
		 * @param {number} [options.tms=0] Date of modification of the object displayed (timestamp), to refuse an update if the object was modified since
		 * @param {string} [options.url] Url of the card used to reload sections and lines, default is the current page without action parameters
		 */
		constructor(element, id, options = {}) {
			this.element = String(element);
			this.id = parseInt(id, 10) || 0;
			this.tms = parseInt(options.tms, 10) || 0;
			this.url = options.url || getCurrentCardUrl();
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
		 * @param {HTMLElement|jQuery|string} [options.target] Element whose content is replaced by the html of the new value
		 * @param {boolean} [options.showErrors=true] Display errors with Dolibarr.tools.setEventMessage()
		 * @param {AbortSignal} [options.signal]
		 * @returns {Promise<Object|null>} Data of the response {element, id, field, value, html, tms}, null if cancelled by a hook
		 * @throws {DolibarrFetchError} status 403 not editable, 409 modified since displayed, 400 invalid value...
		 */
		async setField(field, value, options = {}) {
			const { target = null, showErrors = true, signal = undefined } = options;

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

			if (target && typeof data.html === 'string') {
				Dolibarr.tools.replaceContent(target, data.html, { mode: 'inner' });
			}

			Dolibarr.executeHook('object:FieldUpdated', this.hookData({ field, value: data.value, data }));

			return data;
		}

		/**
		 * Reload sections and document lines of the card, with one request
		 *
		 * @param {Object} what
		 * @param {string[]} [what.sections] CSS selectors of the sections, ex: ['#builddoc', '[data-dol-field="total_ht"]']
		 * @param {number[]} [what.lines] Ids of the lines (rows tr#row-{id})
		 * @param {Object} [options]
		 * @param {boolean} [options.showErrors=true] Display errors with Dolibarr.tools.setEventMessage()
		 * @param {AbortSignal} [options.signal]
		 * @returns {Promise<{sections: Object<string, HTMLElement|null>, lines: Object<number, HTMLElement|null>, document: Document}>}
		 *          New elements (null if not found in the page or in the card loaded), and the card loaded to use other parts of it
		 */
		async reload(what = {}, options = {}) {
			const { sections = [], lines = [] } = what;
			const { showErrors = true, signal = undefined } = options;

			const html = await Dolibarr.tools.fetch.get(this.url, null, { responseType: 'text', showErrors, signal });
			const doc = new DOMParser().parseFromString(html, 'text/html');
			syncCsrfToken(doc);

			const result = { sections: {}, lines: {}, document: doc };

			sections.forEach(selector => {
				const node = this.replaceFrom(doc, selector);
				result.sections[selector] = node;
				if (node) {
					Dolibarr.executeHook('object:SectionReloaded', this.hookData({ selector, node }));
				}
			});

			lines.forEach(lineId => {
				lineId = parseInt(lineId, 10);
				const node = this.replaceFrom(doc, '#row-' + lineId);
				result.lines[lineId] = node;
				if (node) {
					// Same data as when triggered by external modules, listened by ajaxrow.tpl.php for drag and drop of lines
					Dolibarr.executeHook('reloadDocumentLine', { lineId, lineElement: node.dataset.element || '' });
					Dolibarr.executeHook('object:LineReloaded', this.hookData({ lineId, node }));
				}
			});

			return result;
		}

		/**
		 * Reload one section of the card
		 * @param {string} selector
		 * @param {Object} [options] See reload()
		 * @returns {Promise<HTMLElement|null>}
		 */
		async reloadSection(selector, options = {}) {
			const result = await this.reload({ sections: [selector] }, options);
			return result.sections[selector];
		}

		/**
		 * Reload one document line of the card
		 * @param {number} lineId
		 * @param {Object} [options] See reload()
		 * @returns {Promise<HTMLElement|null>}
		 */
		async reloadLine(lineId, options = {}) {
			const result = await this.reload({ lines: [lineId] }, options);
			return result.lines[parseInt(lineId, 10)];
		}

		/**
		 * Replace an element of the page by the same element of a loaded card
		 * @param {Document} doc
		 * @param {string} selector
		 * @returns {HTMLElement|null}
		 */
		replaceFrom(doc, selector) {
			const newEl = doc.querySelector(selector);
			if (!newEl || !document.querySelector(selector)) {
				Dolibarr.log(`tools: object: ${selector} not found in the ${newEl ? 'page' : 'card loaded'}`);
				return null;
			}

			return Dolibarr.tools.replaceContent(selector, newEl);
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
	 * Get the controller of an object
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
