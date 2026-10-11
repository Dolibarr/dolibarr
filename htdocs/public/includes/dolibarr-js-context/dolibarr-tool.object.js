document.addEventListener('Dolibarr:Init', function(e) {
	/**
	 * Dolibarr.tools.object
	 * ---------------------
	 * Controller of an object (CommonObject) displayed on a card, to update it in ajax without reloading the page :
	 * - setField() saves one field with core/ajax/objectfield.php (only fields editable from UI, see CommonObject::isFieldEditableFromUi())
	 * - reload() reloads sections and document lines of the card, the elements are replaced by their new version with Dolibarr.tools.replaceContent() :
	 *   lines are loaded alone with core/ajax/objectline.php, sections with the card loaded without its lines (nolines=1)
	 * - postCard() posts data to the card like a form of the card (ex: action=updateline), see Dolibarr.tools.lineEdit
	 * - refreshCardSections() refreshes the sections of the card that change with the object (banner with the status, action buttons,
	 *   generated documents), see Dolibarr.tools.object.cardSections. scheduleCardSectionsRefresh() does it when a section is visible (lazy load).
	 * - refreshFields() refreshes the values of fields displayed in containers [data-dol-field] (ex: totals), with core/ajax/objectfield.php.
	 *   When lines are reloaded, the fields displayed are refreshed too, so the totals follow the changes of the lines.
	 *
	 * Containers of fields are [data-dol-field][data-dol-element][data-dol-id] (see Form::editfieldval()), or [data-dol-field]
	 * without element for the object of the card (ex: totals of the cards).
	 *
	 * Hooks : object:BeforeFieldUpdate (await hook, can change the value or cancel), object:FieldUpdated, object:FieldUpdateError,
	 * object:FieldsRefreshed, object:SectionReloaded, object:LineReloaded, object:LineRemoved, and reloadDocumentLine for each line reloaded.
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
	 * Sections of the cards that change with the object, refreshed by refreshCardSections():
	 * banner (ref, status...), action buttons and generated documents. Modules can add the selectors of their sections.
	 * @type {string[]}
	 */
	const cardSections = ['div.arearef', 'div.tabsAction', '#builddoc_form'];

	/**
	 * Lazy refresh of the sections of the cards, by object (key element:id, shared by all the controllers of an object):
	 * the card is loaded only when a section out of date is visible, once for several changes in a row
	 * @type {Map<string, {observer: IntersectionObserver|null, loading: boolean, pending: boolean, controller: DolibarrObjectController}>}
	 */
	const lazySections = new Map();

	/**
	 * Check if an element is visible in the window
	 * @param {HTMLElement} el
	 * @returns {boolean}
	 */
	function isInViewport(el) {
		const rect = el.getBoundingClientRect();

		return rect.bottom > 0 && rect.top < window.innerHeight && rect.width > 0;
	}

	/**
	 * Check if the anti CSRF token of the session is renewed on each call (MAIN_SECURITY_CSRF_TOKEN_RENEWAL_ON_EACH_CALL):
	 * the current token and the next one printed by the page are then different
	 * @returns {boolean}
	 */
	function isTokenRenewedOnEachCall() {
		const current = document.querySelector('meta[name="anti-csrf-currenttoken"]');
		const next = document.querySelector('meta[name="anti-csrf-newtoken"]');

		return !!(current && next && current.getAttribute('content') !== next.getAttribute('content'));
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
		 * @param {string} [options.url] Url of the card used to reload sections, default is the current page without action parameters
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
		 * Reload sections and document lines of the card, the lines and the sections are loaded in parallel :
		 * - lines with core/ajax/objectline.php, only the lines asked are rendered.
		 *   A line not in the page yet is inserted at its place, a line deleted is removed from the page.
		 * - sections with the card loaded without its lines (nolines=1), only if sections are asked
		 * - fields with core/ajax/objectfield.php, by default the fields displayed in the page when lines are reloaded (totals...)
		 *
		 * @param {Object} what
		 * @param {string[]} [what.sections] CSS selectors of the sections, ex: ['#builddoc']
		 * @param {number[]} [what.lines] Ids of the lines (rows tr#row-{id})
		 * @param {string[]|null} [what.fields=null] Fields to refresh, see refreshFields(). Default null: the fields displayed in the page
		 *                                           if lines are reloaded, none otherwise. [] to refresh no field.
		 * @param {Object} [options]
		 * @param {boolean} [options.showErrors=true] Display errors with Dolibarr.tools.setEventMessage()
		 * @param {AbortSignal} [options.signal]
		 * @returns {Promise<{sections: Object<string, HTMLElement|null>, lines: Object<number, HTMLElement|null>, fields: Object, eventMessages: Object, lineids: number[]|null, tms: number|null, document: Document|null}>}
		 *          New elements (null if not found, or line deleted), fields refreshed {field: {value, html}},
		 *          event messages kept in session by postCard() {mesgs, warnings, errors} (when lines are asked), ids of all the lines of the object
		 *          in the order of the card and date of modification of the object (null if no line nor field asked),
		 *          and the card loaded to use other parts of it (null if no section asked)
		 */
		async reload(what = {}, options = {}) {
			const { sections = [], lines = [], fields = null } = what;
			const { showErrors = true, signal = undefined } = options;

			const lineIds = lines.map(lineId => parseInt(lineId, 10)).filter(lineId => lineId > 0);
			const fieldNames = fields === null ? (lineIds.length > 0 ? this.getDisplayedFields() : []) : fields;

			const [linesData, doc, fieldsData] = await Promise.all([
				lineIds.length > 0 ? this.fetchLines(lineIds, { showErrors, signal }) : null,
				sections.length > 0 ? this.fetchCardWithoutLines({ showErrors, signal }) : null,
				fieldNames.length > 0 ? this.fetchFields(fieldNames, { showErrors, signal }) : null,
			]);

			const tms = (linesData && linesData.tms) || (fieldsData && fieldsData.tms);
			const result = {
				sections: {},
				lines: {},
				fields: fieldsData ? fieldsData.fields : {},
				eventMessages: linesData ? linesData.eventMessages : {},
				lineids: linesData ? linesData.lineids.map(lineId => parseInt(lineId, 10)) : null,
				tms: tms ? parseInt(tms, 10) : null,
				document: doc,
			};

			if (fieldsData) {
				this.updateFields(fieldsData.fields, { tms: result.tms });
			}

			sections.forEach(selector => {
				const node = this.replaceFrom(doc, selector);
				result.sections[selector] = node;
				if (node) {
					Dolibarr.executeHook('object:SectionReloaded', this.hookData({ selector, node }));
				}
			});

			lineIds.forEach(lineId => {
				const html = linesData.lines[lineId];
				result.lines[lineId] = (typeof html === 'string') ? this.replaceLine(lineId, html, result.lineids) : this.removeLine(lineId);
			});

			return result;
		}

		/**
		 * Refresh the values of fields displayed in the page, in containers [data-dol-field] of the object.
		 * Only fields readable from UI are returned by core/ajax/objectfield.php (see CommonObject::isFieldReadableFromUi()).
		 * The values displayed are then the ones of the object saved: the date of modification of the controller is updated.
		 *
		 * @param {string[]|null} [fields=null] Field names, null for all the fields displayed in the page
		 * @param {Object} [options] See reload()
		 * @returns {Promise<Object>} Fields refreshed {field: {value, html}}
		 */
		async refreshFields(fields = null, options = {}) {
			const fieldNames = fields === null ? this.getDisplayedFields() : fields;
			if (fieldNames.length === 0) return {};

			const data = await this.fetchFields(fieldNames, options);
			this.updateFields(data.fields, { tms: data.tms ? parseInt(data.tms, 10) : null });

			return data.fields;
		}

		/**
		 * Load the values of fields with core/ajax/objectfield.php
		 * @param {string[]} fields
		 * @param {Object} [options] See reload()
		 * @returns {Promise<{element: string, id: number, fields: Object, tms: number|null}>}
		 */
		fetchFields(fields, options = {}) {
			return Dolibarr.tools.fetch.get(Dolibarr.getContextVar('DOL_URL_ROOT') + '/core/ajax/objectfield.php', {
				element: this.element,
				id: this.id,
				fields: fields.join(','),
			}, { responseType: 'json', unwrap: true, ...options });
		}

		/**
		 * Containers [data-dol-field] of the fields of this object in the page, the ones without element are the object of the card
		 * @param {string} [field] Field name, all fields if not set
		 * @returns {HTMLElement[]}
		 */
		getFieldContainers(field) {
			const selector = field ? `[data-dol-field="${CSS.escape(field)}"]` : '[data-dol-field]';

			return Array.from(document.querySelectorAll(selector)).filter(el => !el.dataset.dolElement
				|| (el.dataset.dolElement === this.element && parseInt(el.dataset.dolId, 10) === this.id));
		}

		/**
		 * Names of the fields of this object displayed in the page
		 * @returns {string[]}
		 */
		getDisplayedFields() {
			return [...new Set(this.getFieldContainers().map(el => el.dataset.dolField))];
		}

		/**
		 * Replace the values displayed of fields by their new html, containers in edition (data-dol-editing) are not changed
		 * @param {Object} fields {field: {value, html}} returned by core/ajax/objectfield.php
		 * @param {Object} [options]
		 * @param {string[]} [options.except] Fields not to replace (ex: a field already refreshed)
		 * @param {number|null} [options.tms] Date of modification of the object, the values displayed are the ones of this date
		 */
		updateFields(fields, options = {}) {
			const { except = [], tms = null } = options;

			Object.entries(fields || {}).forEach(([field, data]) => {
				if (except.includes(field) || !data || typeof data.html !== 'string') return;
				this.getFieldContainers(field).forEach(el => {
					if (el.dataset.dolEditing) return;
					Dolibarr.tools.replaceContent(el, data.html, { mode: 'inner' });
				});
			});

			if (tms) this.tms = tms;
			Dolibarr.executeHook('object:FieldsRefreshed', this.hookData({ fields, tms: this.tms }));
		}

		/**
		 * Load the html of document lines with core/ajax/objectline.php.
		 * The event messages kept in session by postCard() are returned and displayed (unless option showEventMessages is false).
		 * @param {number[]} lineIds
		 * @param {Object} [options] See reload()
		 * @param {string} [options.action] 'editline' for the edit form of one line (rows of objectline_edit.tpl.php)
		 * @returns {Promise<{lines: Object<number, string|null>, lineids: number[], tms: number|null, eventMessages: Object}>}
		 */
		async fetchLines(lineIds, options = {}) {
			const { action = '', ...fetchOptions } = options;
			const params = {
				element: this.element,
				id: this.id,
				lineids: lineIds.join(','),
				page: new URL(this.url, window.location.href).pathname, // Links of the lines are the ones of the card
			};
			if (action) params.action = action;

			const response = await Dolibarr.tools.fetch.get(Dolibarr.getContextVar('DOL_URL_ROOT') + '/core/ajax/objectline.php', params, {
				responseType: 'json', ...fetchOptions, unwrap: false,
			});

			return { ...response.data, eventMessages: response.eventMessages || {} };
		}

		/**
		 * Post data to the card, like a form of the card submitted (ex: action=updateline), without reloading the page.
		 * The card answers without its lines (nolines=1), and keeps the event messages of the action in session
		 * (noflusheventmessages=1): they are returned by the next call of fetchLines() or reload() with lines.
		 * The anti CSRF tokens of the page are updated with the ones of the card.
		 * When the card redirects after the action (most cards after adding or deleting a line), the redirect is not followed:
		 * the messages of the action stay in session.
		 *
		 * @param {FormData|Object} data Data posted, the anti CSRF token is added if missing
		 * @param {Object} [options] See reload()
		 * @returns {Promise<Document|null>} Card loaded after the action, null if the card redirected
		 */
		async postCard(data, options = {}) {
			const url = new URL(this.url, window.location.href);
			url.searchParams.set('nolines', '1');
			url.searchParams.set('noflusheventmessages', '1');

			const response = await Dolibarr.tools.fetch.post(url.toString(), data, { ...options, responseType: 'response', redirect: 'manual' });
			if (response.type === 'opaqueredirect') {
				// The token of the session was renewed by the card and is unknown: it is read from the card loaded without lines
				if (isTokenRenewedOnEachCall()) await this.fetchCardWithoutLines(options);
				return null;
			}

			const doc = new DOMParser().parseFromString(await response.text(), 'text/html');
			syncCsrfToken(doc);

			return doc;
		}

		/**
		 * Load the card without its lines, faster than the whole card on documents with many lines.
		 * The event messages waiting in session are not displayed by the card loaded in background, they stay in session (noflusheventmessages=1).
		 * @param {Object} [options] See reload()
		 * @returns {Promise<Document>}
		 */
		async fetchCardWithoutLines(options = {}) {
			const url = new URL(this.url, window.location.href);
			url.searchParams.set('nolines', '1');
			url.searchParams.set('noflusheventmessages', '1');

			const html = await Dolibarr.tools.fetch.get(url.toString(), null, { responseType: 'text', ...options });
			const doc = new DOMParser().parseFromString(html, 'text/html');
			syncCsrfToken(doc);

			return doc;
		}

		/**
		 * Replace a document line of the page by its new html, or insert it after the previous line of the card
		 * @param {number} lineId
		 * @param {string} html Html of the line, only the row tr#row-{id} is used
		 * @param {number[]} allLineIds Ids of all the lines of the object in the order of the card
		 * @returns {HTMLElement|null}
		 */
		replaceLine(lineId, html, allLineIds) {
			// Parsed in a table like in the card: html printed by hooks outside of the rows does not prevent to parse them
			const template = document.createElement('template');
			template.innerHTML = '<table><tbody>' + html + '</tbody></table>';
			const newRow = template.content.getElementById('row-' + lineId);
			if (!newRow) {
				Dolibarr.log(`tools: object: #row-${lineId} not found in the line loaded`);
				return null;
			}

			let target = document.getElementById('row-' + lineId);
			if (!target) {
				// New line: an empty row is inserted next to the previous (or next) line of the card, then replaced
				const index = allLineIds.indexOf(lineId);
				const previousRow = allLineIds.slice(0, index).reverse().map(id => document.getElementById('row-' + id)).find(row => row);
				const nextRow = previousRow ? null : allLineIds.slice(index + 1).map(id => document.getElementById('row-' + id)).find(row => row);
				if (!previousRow && !nextRow) {
					Dolibarr.log(`tools: object: no line in the page to insert #row-${lineId}`);
					return null;
				}
				target = document.createElement('tr');
				if (previousRow) {
					previousRow.after(target);
				} else {
					nextRow.before(target);
				}
			}

			const node = Dolibarr.tools.replaceContent(target, newRow);
			if (node) {
				// Same data as when triggered by external modules, listened by ajaxrow.tpl.php for drag and drop of lines
				Dolibarr.executeHook('reloadDocumentLine', { lineId, lineElement: node.dataset.element || '' });
				Dolibarr.executeHook('object:LineReloaded', this.hookData({ lineId, node }));
			}

			return node;
		}

		/**
		 * Remove a document line deleted from the page
		 * @param {number} lineId
		 * @returns {null}
		 */
		removeLine(lineId) {
			const row = document.getElementById('row-' + lineId);
			if (row) {
				row.remove();
				Dolibarr.executeHook('object:LineRemoved', this.hookData({ lineId }));
			}

			return null;
		}

		/**
		 * Refresh the sections of the card that change with the object (Dolibarr.tools.object.cardSections): banner with the status,
		 * action buttons, generated documents. Sections not in the page, or with an edition in progress, are not changed.
		 *
		 * @param {Document|Promise<Document|null>|null} [doc] Card loaded after the action (see postCard()), the card without lines is loaded if not given
		 * @param {Object} [options] See reload()
		 * @returns {Promise<Object<string, HTMLElement|null>>} New sections
		 */
		async refreshCardSections(doc = null, options = {}) {
			const selectors = cardSections.filter(selector => {
				const el = document.querySelector(selector);
				return el && !el.matches('[data-dol-editing]') && !el.querySelector('[data-dol-editing]');
			});
			if (selectors.length === 0) return {};

			const loaded = (await doc) || await this.fetchCardWithoutLines(options);

			// Sections up to date: a lazy refresh scheduled before is not needed anymore
			const state = lazySections.get(this.element + ':' + this.id);
			if (state && !state.loading) {
				if (state.observer) state.observer.disconnect();
				lazySections.delete(this.element + ':' + this.id);
			}
			const result = {};
			selectors.forEach(selector => {
				const node = this.replaceFrom(loaded, selector);
				result[selector] = node;
				if (node) {
					Dolibarr.executeHook('object:SectionReloaded', this.hookData({ selector, node }));
				}
			});

			return result;
		}

		/**
		 * Refresh the sections of the card that change with the object later, when one of them is visible (lazy load).
		 * Use it when the card must be loaded only for these sections (ex: after an update of a field): several changes in a row
		 * load the card once, and not at all while the sections are out of the window.
		 */
		scheduleCardSectionsRefresh() {
			const key = this.element + ':' + this.id;
			const state = lazySections.get(key) || { observer: null, loading: false, pending: false, controller: this };
			state.controller = this;
			lazySections.set(key, state);

			if (state.loading) {
				state.pending = true; // Changed during the loading: loaded again after
				return;
			}

			const elements = cardSections.map(selector => document.querySelector(selector)).filter(el => el);
			if (elements.length === 0) return;
			if (elements.some(el => isInViewport(el)) || typeof IntersectionObserver === 'undefined') {
				this.flushCardSections();
				return;
			}

			if (!state.observer) {
				state.observer = new IntersectionObserver(entries => {
					if (entries.some(entry => entry.isIntersecting)) state.controller.flushCardSections();
				});
			}
			elements.forEach(el => state.observer.observe(el));
		}

		/**
		 * Refresh now the sections of the card scheduled by scheduleCardSectionsRefresh()
		 * @returns {Promise<void>}
		 */
		async flushCardSections() {
			const key = this.element + ':' + this.id;
			const state = lazySections.get(key);
			if (!state || state.loading) return;

			if (state.observer) {
				state.observer.disconnect();
				state.observer = null;
			}
			state.loading = true;
			try {
				await this.refreshCardSections(null, { showErrors: false });
			} catch (error) {
				// Sections stay as they are, the page can be reloaded
			} finally {
				state.loading = false;
				lazySections.delete(key);
				if (state.pending) this.scheduleCardSectionsRefresh();
			}
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
	object.cardSections = cardSections;

	Dolibarr.defineTool('object', object);
});
