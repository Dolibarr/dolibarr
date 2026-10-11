document.addEventListener('Dolibarr:Init', function(e) {
	/**
	 * Dolibarr.tools.card
	 * -------------------
	 * Controller of the card of an object displayed in the page, to update the page in ajax without reloading it.
	 * Built on Dolibarr.tools.object (the data of the object), it changes the page:
	 * - setField() saves a field and refreshes the fields displayed changed by the update, with a light feedback on them (see showUpdatedFeedback())
	 * - refreshFields() refreshes the values of fields displayed in containers [data-dol-field] (ex: totals)
	 * - reload() reloads sections and document lines of the card, the elements are replaced by their new version with Dolibarr.tools.replaceContent():
	 *   lines are loaded alone with core/ajax/objectline.php, sections with the card loaded without its lines (nolines=1).
	 *   When lines are reloaded, the fields displayed are refreshed too, so the totals follow the changes of the lines.
	 * - postCard() posts data to the card like a form of the card (ex: action=updateline)
	 * - refreshSections() refreshes the sections of the card that change with the object (banner with the status, action buttons,
	 *   generated documents), scheduleSectionsRefresh() does it when a section is visible (lazy load)
	 *
	 * There is one controller by card (element and id), shared by all the scripts of the page: same date of modification, same state.
	 * The edition of the card in place is done by parts of this tool: Dolibarr.tools.card.fields and Dolibarr.tools.card.lines (experimental).
	 *
	 * Containers of fields are [data-dol-field][data-dol-element][data-dol-id] (see Form::editfieldval()), or [data-dol-field]
	 * without element for the object of the card (ex: totals of the cards).
	 *
	 * Hooks : card:FieldsRefreshed, card:SectionReloaded, card:LineReloaded, card:LineRemoved, and reloadDocumentLine for each line reloaded.
	 */

	/**
	 * Parameters of the url of the card that must not be sent again when the card is reloaded
	 * (the card must be displayed, not an action executed again)
	 */
	const ACTION_PARAMS = ['action', 'confirm', 'token', 'massaction'];

	/**
	 * Sections of all the cards that change with the object, refreshed by refreshSections():
	 * banner (ref, status...), action buttons and generated documents. Modules can add the selectors of their sections.
	 * @type {string[]}
	 */
	const sections = ['div.arearef', 'div.tabsAction', '#builddoc_form'];

	/**
	 * Delay before generating again the document of the card after a change (ms): changes in a row generate it once
	 */
	const DOCUMENT_BUILD_DELAY = 3000;

	/**
	 * Specializations of the cards by element, see card.register()
	 * @type {Map<string, {sections: string[], Controller: Function|null}>}
	 */
	const specializations = new Map();

	/**
	 * Controllers of the cards of the page, by element:id
	 * @type {Map<string, DolibarrCardController>}
	 */
	const controllers = new Map();

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
	 * Check if an element is visible in the window
	 * @param {HTMLElement} el
	 * @returns {boolean}
	 */
	function isInViewport(el) {
		const rect = el.getBoundingClientRect();

		return rect.bottom > 0 && rect.top < window.innerHeight && rect.width > 0;
	}

	/**
	 * Show that a displayed value was updated: a reflection sweeping it once (.updated-feedback of input-feedback.css), limited to its text
	 * so it crosses the text at the same speed whatever the width of the element. The class is removed at the end of the animation,
	 * so it can be shown again.
	 * @param {HTMLElement} el
	 * @param {Object} [options]
	 * @param {boolean} [options.soft=false] Lighter, for the values changed as a consequence (computed fields)
	 */
	function showUpdatedFeedback(el, options = {}) {
		const { soft = false } = options;
		el.classList.remove('updated-feedback', '--soft');

		// Box of the text, relative to the padding box of the element (origin of the reflection ::after)
		const range = document.createRange();
		range.selectNodeContents(el);
		const textRect = range.getBoundingClientRect();
		const rect = el.getBoundingClientRect();
		if (textRect.width > 0) {
			el.style.setProperty('--updated-feedback-left', (textRect.left - rect.left - el.clientLeft) + 'px');
			el.style.setProperty('--updated-feedback-width', textRect.width + 'px');
		}

		void el.offsetWidth; // Restarts the animation when it is running
		el.classList.add('updated-feedback');
		if (soft) el.classList.add('--soft');
		el.addEventListener('animationend', () => {
			el.classList.remove('updated-feedback', '--soft');
			el.style.removeProperty('--updated-feedback-left');
			el.style.removeProperty('--updated-feedback-width');
		}, { once: true });
	}

	/**
	 * Delay while the last change can be undone (ms), the undo message stays while the mouse is over it
	 */
	const UNDO_DELAY = 6000;

	/**
	 * Last change of a value of the page that can be undone (one level), see setUndo()
	 * @type {{controller: DolibarrCardController, label: string, from: string, to: string, undo: function(): Promise<void>}|null}
	 */
	let lastChange = null;

	/** Message of the card tool (undo, edition in progress), created when first shown */
	let toast = null;
	let toastTimer = null;
	let toastDelay = 0;

	/**
	 * Translate a label of a domain
	 * @param {string} domain
	 * @param {string} key
	 * @param {string} [fallback] Text used when the key is not translated (new key not yet translated in the language of the user),
	 *                            the key itself if not given
	 * @returns {Promise<string>}
	 */
	async function trans(domain, key, fallback = key) {
		if (!Dolibarr.checkToolExist('langs')) return fallback;
		try {
			await Dolibarr.tools.langs.load(domain);
			const text = Dolibarr.tools.langs.transNoEntities(key); // Used as text, not html

			return text === key ? fallback : text;
		} catch (error) {
			return fallback;
		}
	}

	/**
	 * Hide the message of the card tool, the last change can not be undone anymore
	 */
	function hideMessage() {
		clearTimeout(toastTimer);
		lastChange = null;
		if (toast) toast.hidden = true;
	}

	/**
	 * Show a message of the card tool at the bottom of the page (in contrast with the theme), with buttons, replacing the previous one.
	 * It is hidden after a delay, not while the mouse is over it.
	 * @param {string} text
	 * @param {Array<{label: string, title?: string, primary?: boolean, onClick: function(): void}>} [buttons]
	 * @param {number} [delay] Delay before it is hidden (ms), 0 to keep it until closed
	 */
	async function showMessage(text, buttons = [], delay = 2500) {
		if (!toast) {
			toast = document.createElement('div');
			toast.className = 'dol-card-toast';
			toast.setAttribute('role', 'status');
			toast.addEventListener('mouseenter', () => clearTimeout(toastTimer));
			toast.addEventListener('mouseleave', () => {
				clearTimeout(toastTimer);
				if (toastDelay) toastTimer = setTimeout(hideMessage, 3000);
			});
			document.body.append(toast);
		}

		const label = document.createElement('span');
		label.className = 'dol-card-toast-text';
		label.textContent = text;
		const nodes = [label];
		buttons.forEach(({ label: buttonLabel, title = '', primary = false, onClick }) => {
			const button = document.createElement('button');
			button.type = 'button';
			button.className = 'dol-card-toast-button' + (primary ? ' --primary' : '');
			button.textContent = buttonLabel;
			if (title) button.title = title;
			button.addEventListener('click', onClick);
			nodes.push(button);
		});
		const closeButton = document.createElement('button');
		closeButton.type = 'button';
		closeButton.className = 'dol-card-toast-close';
		closeButton.setAttribute('aria-label', await trans('main', 'CloseWindowShort'));
		const picto = document.createElement('span');
		picto.className = 'fa fa-times';
		picto.setAttribute('aria-hidden', 'true');
		closeButton.append(picto);
		closeButton.addEventListener('click', hideMessage);
		nodes.push(closeButton);
		toast.replaceChildren(...nodes);

		toast.hidden = false;
		clearTimeout(toastTimer);
		toastDelay = delay;
		if (delay) toastTimer = setTimeout(hideMessage, delay);
	}

	/**
	 * Remember the last change of a value of the page, so it can be undone (one level): a message shows the change
	 * with a button Undo (Ctrl + Z while it is visible, outside the inputs). Used by the parts fields and lines of the card tool.
	 * @param {Object} change
	 * @param {DolibarrCardController} change.controller Controller of the card of the object changed
	 * @param {string} change.label Label of the value (field or column, with the line)
	 * @param {string} change.from Value displayed before
	 * @param {string} change.to Value displayed after
	 * @param {function(): Promise<void>} change.undo Save the value before again and refresh the page. An error thrown is already displayed.
	 */
	async function setUndo(change) {
		const undoLabel = await trans('main', 'Undo');
		await showMessage(change.label + ' : ' + change.from + ' → ' + change.to, [
			{ label: undoLabel, title: 'Ctrl + Z', primary: true, onClick: () => undo() },
		], UNDO_DELAY);
		lastChange = change;
	}

	/**
	 * Undo the last change of a value of the page (see setUndo()). Refused if the object was modified since, by another page or user.
	 * @returns {Promise<boolean>} True if undone
	 */
	async function undo() {
		const change = lastChange;
		if (!change) return false;
		hideMessage();

		try {
			const data = await change.controller.object.fetchFields([], { showErrors: false });
			if (data.tms && change.controller.tms && parseInt(data.tms, 10) > change.controller.tms) {
				Dolibarr.tools.setEventMessage(await trans('errors', 'ErrorRecordModifiedSinceDisplay'), 'errors');
				return false;
			}
			await change.undo();
		} catch (error) {
			return false; // Error already displayed
		}
		showMessage(await trans('main', 'ChangeUndone', 'Change undone'));

		return true;
	}

	// Ctrl + Z undoes the last change while its message is visible, not in the inputs (their own undo) nor while a value is edited
	document.addEventListener('keydown', event => {
		if (!lastChange || !toast || toast.hidden || !(event.ctrlKey || event.metaKey) || event.shiftKey || event.key.toLowerCase() !== 'z') return;
		const target = event.target instanceof Element ? event.target : null;
		if ((target && target.closest('input, textarea, select, [contenteditable="true"]')) || document.querySelector('.dol-field-popover')) return;
		event.preventDefault();
		undo();
	});

	/**
	 * Form of a classic edition in progress in the page: the card was loaded to edit a field or a line by reloading the page.
	 * Cards follow the same convention: the edit link has an action edit... (edittags, editconditions, editline...),
	 * the form shown posts an action set... or update... (settags, setconditions, updateline...).
	 * @returns {{form: HTMLFormElement|null}|null} Null if no classic edition is in progress, form null if it is not found
	 */
	function getClassicEdition() {
		const action = new URL(window.location.href).searchParams.get('action') || '';
		if (!/^edit/i.test(action)) return null;

		const form = Array.from(document.querySelectorAll('form')).find(el => {
			const input = el.querySelector('input[type="hidden"][name="action"]');
			return el.id !== 'builddoc_form' && input && /^(set|update)/i.test(input.value);
		});

		return { form: form || null };
	}

	/**
	 * Show the form of the classic edition in progress: scrolled into the window if needed, its inputs blink once (fail-feedback)
	 * @param {HTMLFormElement|null} form
	 */
	function showClassicEdition(form) {
		if (!form) return;
		const inputs = Array.from(form.querySelectorAll('input:not([type="hidden"]):not([type="submit"]), textarea, select, .select2-selection'))
			.filter(el => el.getClientRects().length > 0);
		const first = inputs[0] || form;
		if (!isInViewport(first)) first.scrollIntoView({ block: 'center', behavior: 'smooth' });
		inputs.forEach(el => {
			el.classList.remove('fail-feedback');
			void el.offsetWidth; // Restarts the animation when it is running
			el.classList.add('fail-feedback');
			el.addEventListener('animationend', () => el.classList.remove('fail-feedback'), { once: true });
		});
		const focusable = inputs.find(el => el.matches('input, textarea, select')) || inputs[0];
		if (focusable) focusable.focus({ preventScroll: true });
	}

	/**
	 * Check that no classic edition is in progress before an edition in ajax: one edition at a time, the classic one must be saved or
	 * cancelled first, else the user could forget it. If one is in progress, it is shown, with a message to go to it or to discard it
	 * (the card is loaded again without action, nothing is saved, like its button Cancel).
	 * Used by the parts fields and lines of the card tool before opening an edition.
	 * @returns {boolean} True if an edition in ajax can be opened
	 */
	function checkNoClassicEdition() {
		const edition = getClassicEdition();
		if (!edition) return true;

		showClassicEdition(edition.form);
		Promise.all([
			trans('main', 'AnotherEditionInProgress', 'Another change is in progress, save or cancel it first'),
			trans('main', 'GoToEdition', 'Go to it'),
			trans('main', 'DiscardEdition', 'Discard it'),
		]).then(([text, goLabel, discardLabel]) => {
			const buttons = [{ label: discardLabel, onClick: () => { window.location.href = getCurrentCardUrl(); } }];
			if (edition.form) buttons.unshift({ label: goLabel, primary: true, onClick: () => showClassicEdition(edition.form) });
			showMessage(text, buttons, 0);
		});

		return false;
	}

	/**
	 * Controller of the card of an object displayed in the page
	 */
	class DolibarrCardController {
		/**
		 * @param {string} element Element of the object, ex: 'propal', 'commande', 'facture' or 'mymodule_myobject'
		 * @param {number} id Id of the object
		 * @param {Object} [options]
		 * @param {number} [options.tms=0] Date of modification of the object displayed (timestamp), to refuse an update if the object was modified since
		 * @param {string} [options.url] Url of the card used to reload sections and post actions, default is the current page without action parameters
		 */
		constructor(element, id, options = {}) {
			/** Controller of the data of the object, see Dolibarr.tools.object */
			this.object = Dolibarr.tools.object(element, id, { tms: options.tms });
			this.element = this.object.element;
			this.id = this.object.id;
			this.url = options.url || getCurrentCardUrl();
			/** State of the lazy refresh of the sections, see scheduleSectionsRefresh() */
			this.lazySections = { scheduled: false, observer: null, loading: false, pending: false };
			/** State of the delayed generation of the document, see scheduleDocumentBuild() */
			this.documentBuild = { timer: null, building: false, pending: false, onPageHide: null };
		}

		/**
		 * Date of modification of the object displayed, shared with the controller of the object
		 * @type {number}
		 */
		get tms() {
			return this.object.tms;
		}

		set tms(value) {
			this.object.tms = parseInt(value, 10) || 0;
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
		 * Selectors of the sections of this card that change with the object: the ones of all the cards and the ones of its element
		 * (see Dolibarr.tools.card.sections and Dolibarr.tools.card.register())
		 * @returns {string[]}
		 */
		getSections() {
			const specialization = specializations.get(this.element);

			return [...sections, ...(specialization ? specialization.sections : [])];
		}

		/**
		 * Save the value of one field of the object (Dolibarr.tools.object setField()), then refresh the fields displayed changed by the update
		 * @param {string} field
		 * @param {*} value See Dolibarr.tools.object setField()
		 * @param {Object} [options] See Dolibarr.tools.object setField()
		 * @param {HTMLElement|jQuery|string} [options.target] Element whose content is replaced by the html of the new value
		 * @returns {Promise<Object|null>} Data of the response, null if cancelled by a hook
		 */
		async setField(field, value, options = {}) {
			const { target = null, ...setOptions } = options;
			const data = await this.object.setField(field, value, setOptions);
			if (!data) return null;

			if (target && typeof data.html === 'string') {
				Dolibarr.tools.replaceContent(target, data.html, { mode: 'inner' });
			}
			this.updateFields(data.fields, { except: target ? [field] : [], tms: data.tms ? parseInt(data.tms, 10) : null });

			// The other fields changed by the update (computed fields), the field itself is shown by its editor (see card.fields)
			Object.keys(data.fields || {}).filter(name => name !== field).forEach(name => {
				this.getFieldContainers(name).forEach(el => {
					if (!el.dataset.dolEditing) showUpdatedFeedback(el, { soft: true });
				});
			});

			// The document shows the field, generated again like the cards do after a change (unless MAIN_DISABLE_PDF_AUTOUPDATE)
			if (data.builddoc) this.scheduleDocumentBuild();

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
		 * @param {string[]} [what.sections] CSS selectors of the sections, ex: ['#builddoc_form']
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
			const { sections: sectionSelectors = [], lines = [], fields = null } = what;
			const { showErrors = true, signal = undefined } = options;

			const lineIds = lines.map(lineId => parseInt(lineId, 10)).filter(lineId => lineId > 0);
			const fieldNames = fields === null ? (lineIds.length > 0 ? this.getDisplayedFields() : []) : fields;

			const [linesData, doc, fieldsData] = await Promise.all([
				lineIds.length > 0 ? this.fetchLines(lineIds, { showErrors, signal }) : null,
				sectionSelectors.length > 0 ? this.fetchCardWithoutLines({ showErrors, signal }) : null,
				fieldNames.length > 0 ? this.object.fetchFields(fieldNames, { showErrors, signal }) : null,
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

			sectionSelectors.forEach(selector => {
				const node = this.replaceFrom(doc, selector);
				result.sections[selector] = node;
				if (node) {
					Dolibarr.executeHook('card:SectionReloaded', this.hookData({ selector, node }));
				}
			});

			lineIds.forEach(lineId => {
				const html = linesData.lines[lineId];
				result.lines[lineId] = (typeof html === 'string') ? this.replaceLine(lineId, html, result.lineids) : this.removeLine(lineId);
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

			const data = await this.object.fetchFields(fieldNames, options);
			this.updateFields(data.fields, { tms: data.tms ? parseInt(data.tms, 10) : null });

			return data.fields;
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
			Dolibarr.executeHook('card:FieldsRefreshed', this.hookData({ fields, tms: this.tms }));
		}

		/**
		 * Load the html of document lines with core/ajax/objectline.php, with the links of the card (Dolibarr.tools.object fetchLines()).
		 * The event messages kept in session by postCard() are returned and displayed (unless option showEventMessages is false).
		 * @param {number[]} lineIds
		 * @param {Object} [options] See Dolibarr.tools.object fetchLines()
		 * @returns {Promise<{lines: Object<number, string|null>, lineids: number[], tms: number|null, eventMessages: Object}>}
		 */
		fetchLines(lineIds, options = {}) {
			return this.object.fetchLines(lineIds, { page: new URL(this.url, window.location.href).pathname, ...options });
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
				Dolibarr.log(`tools: card: #row-${lineId} not found in the line loaded`);
				return null;
			}

			let target = document.getElementById('row-' + lineId);
			if (!target) {
				// New line: an empty row is inserted next to the previous (or next) line of the card, then replaced
				const index = allLineIds.indexOf(lineId);
				const previousRow = allLineIds.slice(0, index).reverse().map(id => document.getElementById('row-' + id)).find(row => row);
				const nextRow = previousRow ? null : allLineIds.slice(index + 1).map(id => document.getElementById('row-' + id)).find(row => row);
				if (!previousRow && !nextRow) {
					Dolibarr.log(`tools: card: no line in the page to insert #row-${lineId}`);
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
				Dolibarr.executeHook('card:LineReloaded', this.hookData({ lineId, node }));
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
				Dolibarr.executeHook('card:LineRemoved', this.hookData({ lineId }));
			}

			return null;
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
			const response = await Dolibarr.tools.fetch.post(this.getPostUrl(), data, { ...options, responseType: 'response', redirect: 'manual' });
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
		 * Url used to post data to the card (see postCard()): without its lines, event messages kept in session
		 * @returns {string}
		 */
		getPostUrl() {
			const url = new URL(this.url, window.location.href);
			url.searchParams.set('nolines', '1');
			url.searchParams.set('noflusheventmessages', '1');

			return url.toString();
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
		 * Replace an element of the page by the same element of a loaded card
		 * @param {Document} doc
		 * @param {string} selector
		 * @returns {HTMLElement|null}
		 */
		replaceFrom(doc, selector) {
			const newEl = doc.querySelector(selector);
			if (!newEl || !document.querySelector(selector)) {
				Dolibarr.log(`tools: card: ${selector} not found in the ${newEl ? 'page' : 'card loaded'}`);
				return null;
			}

			return Dolibarr.tools.replaceContent(selector, newEl);
		}

		/**
		 * Refresh the sections of the card that change with the object (see getSections()): banner with the status,
		 * action buttons, generated documents. Sections not in the page, or with an edition in progress, are not changed.
		 *
		 * @param {Document|null} [doc] Card loaded after the action (see postCard()), the card without lines is loaded if not given
		 * @param {Object} [options] See reload()
		 * @param {string[]} [options.sections] Selectors of the sections to refresh, all the sections of the card if not given
		 * @returns {Promise<Object<string, HTMLElement|null>>} New sections
		 */
		async refreshSections(doc = null, options = {}) {
			const { sections: only = null, ...fetchOptions } = options;
			const selectors = (only || this.getSections()).filter(selector => {
				const el = document.querySelector(selector);
				return el && !el.matches('[data-dol-editing]') && !el.querySelector('[data-dol-editing]');
			});
			if (selectors.length === 0) return {};

			const loaded = doc || await this.fetchCardWithoutLines(fetchOptions);

			// Sections up to date: a lazy refresh scheduled before is not needed anymore (unless only some sections are refreshed)
			if (!only && !this.lazySections.loading) {
				this.lazySections.scheduled = false;
				if (this.lazySections.observer) {
					this.lazySections.observer.disconnect();
					this.lazySections.observer = null;
				}
			}

			const result = {};
			selectors.forEach(selector => {
				const node = this.replaceFrom(loaded, selector);
				result[selector] = node;
				if (node) {
					Dolibarr.executeHook('card:SectionReloaded', this.hookData({ selector, node }));
				}
			});

			return result;
		}

		/**
		 * Refresh the sections of the card later, when one of them is visible (lazy load).
		 * Use it when the card must be loaded only for these sections (ex: after an update of a field): several changes in a row
		 * load the card once, and not at all while the sections are out of the window.
		 */
		scheduleSectionsRefresh() {
			const state = this.lazySections;
			if (state.loading) {
				state.pending = true; // Changed during the loading: loaded again after
				return;
			}

			const elements = this.getSections().map(selector => document.querySelector(selector)).filter(el => el);
			if (elements.length === 0) return;
			state.scheduled = true;
			if (elements.some(el => isInViewport(el)) || typeof IntersectionObserver === 'undefined') {
				this.flushSections();
				return;
			}

			if (!state.observer) {
				state.observer = new IntersectionObserver(entries => {
					if (entries.some(entry => entry.isIntersecting)) this.flushSections();
				});
			}
			elements.forEach(el => state.observer.observe(el));
		}

		/**
		 * Refresh now the sections of the card scheduled by scheduleSectionsRefresh()
		 * @returns {Promise<void>}
		 */
		async flushSections() {
			const state = this.lazySections;
			if (state.loading || !state.scheduled) return;

			state.scheduled = false;
			if (state.observer) {
				state.observer.disconnect();
				state.observer = null;
			}
			state.loading = true;
			try {
				await this.refreshSections(null, { showErrors: false });
			} catch (error) {
				// Sections stay as they are, the page can be reloaded
			} finally {
				state.loading = false;
				if (state.pending) {
					state.pending = false;
					this.scheduleSectionsRefresh();
				}
			}
		}

		/**
		 * Generate again the document of the card later, with the form of generation of the page (form#builddoc_form, action builddoc
		 * of the card: same model, language and options as the button Generate). The requests are stacked: the document is generated
		 * DOCUMENT_BUILD_DELAY ms after the last change, once at a time, and once again after if the object changed during the generation.
		 * A generation still waiting when the page is left is sent with navigator.sendBeacon().
		 */
		scheduleDocumentBuild() {
			const state = this.documentBuild;
			if (!document.querySelector('form#builddoc_form')) return;
			if (state.building) {
				state.pending = true; // Changed during the generation: generated again after
				return;
			}

			clearTimeout(state.timer);
			state.timer = setTimeout(() => this.flushDocumentBuild(), DOCUMENT_BUILD_DELAY);

			if (!state.onPageHide) {
				state.onPageHide = () => {
					if (!state.timer) return;
					clearTimeout(state.timer);
					state.timer = null;
					const form = document.querySelector('form#builddoc_form');
					if (form && navigator.sendBeacon) navigator.sendBeacon(this.getPostUrl(), this.getDocumentBuildData(form));
				};
				window.addEventListener('pagehide', state.onPageHide);
			}
		}

		/**
		 * Data of the form of generation, without the message "File generated" of the card: the document is generated again automatically,
		 * like the cards do after a change (errors are still displayed)
		 * @param {HTMLFormElement} form
		 * @returns {FormData}
		 */
		getDocumentBuildData(form) {
			const data = new FormData(form);
			data.set('nosuccessmessage', '1');

			return data;
		}

		/**
		 * Display now the event messages kept in session by the card (see postCard()), ex: the errors of the generation of the document,
		 * instead of on the next page. Any json response of Dolibarr returns them, the fields of the object are not read (fields empty).
		 * @returns {Promise<void>}
		 */
		async showWaitingEventMessages() {
			try {
				await this.object.fetchFields([], { showErrors: false });
			} catch (error) {
				// The messages stay in session, displayed by the next page
			}
		}

		/**
		 * Generate now the document scheduled by scheduleDocumentBuild(), then refresh the generated documents
		 * @returns {Promise<void>}
		 */
		async flushDocumentBuild() {
			const state = this.documentBuild;
			clearTimeout(state.timer);
			state.timer = null;
			if (state.building) {
				state.pending = true;
				return;
			}
			const form = document.querySelector('form#builddoc_form');
			if (!form) return;

			state.building = true;
			try {
				const doc = await this.postCard(this.getDocumentBuildData(form), { showErrors: false });
				await this.showWaitingEventMessages();
				if (doc) {
					// Only the list of the documents: the banner and the buttons were refreshed after the change, refreshing them again would move the page
					await this.refreshSections(doc, { showErrors: false, sections: ['#builddoc_form'] });
				} else {
					this.scheduleSectionsRefresh(); // Card redirected after the generation
				}
			} catch (error) {
				// The document can be generated with the button of the card
			} finally {
				state.building = false;
				if (state.pending) {
					state.pending = false;
					this.scheduleDocumentBuild();
				}
			}
		}

		/**
		 * Listen to the card:* hooks of this card only
		 * @param {string} eventName Name without the prefix 'card:', ex: 'LineReloaded'
		 * @param {function} callback
		 * @returns {function} Function to remove the listener
		 */
		on(eventName, callback) {
			const hookName = 'card:' + eventName;
			const listener = data => {
				if (data && data.element === this.element && data.id === this.id) callback(data);
			};
			Dolibarr.on(hookName, listener);

			return () => Dolibarr.off(hookName, listener);
		}
	}

	/**
	 * Get the controller of the card of an object displayed in the page. There is one controller by card, shared by all the scripts.
	 * @param {string} element
	 * @param {number} id
	 * @param {Object} [options] See DolibarrCardController, used when the controller is created.
	 *                           The date of modification is set if the controller did not know it yet.
	 * @returns {DolibarrCardController}
	 */
	function card(element, id, options = {}) {
		const key = String(element) + ':' + (parseInt(id, 10) || 0);
		let controller = controllers.get(key);
		if (!controller) {
			const specialization = specializations.get(String(element));
			const Controller = (specialization && specialization.Controller) || DolibarrCardController;
			controller = new Controller(element, id, options);
			controllers.set(key, controller);
		} else if (options.tms && !controller.tms) {
			controller.tms = options.tms;
		}

		return controller;
	}

	/**
	 * Specialize the cards of an element, for the needs that the server side can not cover.
	 * To call before a controller of this element is created.
	 * @param {string} element Element of the object, ex: 'facture'
	 * @param {Object} specialization
	 * @param {string[]} [specialization.sections] More sections of the card that change with the object (ex: ['#paymenttable'])
	 * @param {Function} [specialization.Controller] Class extending Dolibarr.tools.card.Controller, used for the cards of this element
	 */
	function register(element, specialization = {}) {
		const current = specializations.get(String(element)) || { sections: [], Controller: null };
		if (Array.isArray(specialization.sections)) current.sections.push(...specialization.sections);
		if (specialization.Controller) {
			if (!(specialization.Controller.prototype instanceof DolibarrCardController)) {
				throw new TypeError('Dolibarr: tools.card.register(): Controller must extend Dolibarr.tools.card.Controller');
			}
			current.Controller = specialization.Controller;
		}
		specializations.set(String(element), current);
	}

	/**
	 * Define a part of the card tool (ex: 'fields', 'lines'), available as Dolibarr.tools.card.<name>. A part can not be replaced.
	 * @param {string} name
	 * @param {Object} api
	 */
	function definePart(name, api) {
		if (Object.prototype.hasOwnProperty.call(card, name)) {
			throw new Error(`Dolibarr: tools.card.${name} already defined`);
		}
		Object.defineProperty(card, name, { value: Object.freeze(api), enumerable: true, writable: false, configurable: false });
		Dolibarr.log(`tools: card: part ${name} defined`);
	}

	card.Controller = DolibarrCardController;
	card.sections = sections;
	card.register = register;
	card.definePart = definePart;
	card.showUpdatedFeedback = showUpdatedFeedback;
	card.setUndo = setUndo;
	card.undo = undo;
	card.showMessage = showMessage;
	card.hideMessage = hideMessage;
	card.checkNoClassicEdition = checkNoClassicEdition;

	Dolibarr.defineTool('card', card);
});
