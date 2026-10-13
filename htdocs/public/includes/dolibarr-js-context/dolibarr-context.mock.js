/** This file is purely for IDE autocompletion and developer convenience.
 * It is never executed or loaded in Dolibarr itself.
 *
 * MOCK DEFINITION: Dolibarr.tools
 * This mock helps your code editor understand the structure of Dolibarr.tools
 * and provides autocomplete hints, parameter hints, and inline documentation.
 * You can safely edit this file to add all standard Dolibarr tools for autocompletion.
 *
 * @SEE dolibarr-context.umd.js
 *
*/

var Dolibarr = {
	tools: {

		/**
		 * Displays a Dolibarr notification message (success, warning, or error).
		 * This is the JavaScript equivalent of the PHP setEventMessage tool.
		 *
		 * @param {string} msg      The message text to display
		 * @param {string=} type    Optional: 'mesgs' (default), 'warnings', or 'errors'
		 * @param {boolean=} sticky Optional: true if the message should stay until manually closed
		 *
		 * Example usage in your IDE:
		 * Dolibarr.tools.setEventMessage('Operation successful', 'success');
		 */
		setEventMessage: function(msg, type, sticky) {},

		/**
		 * Send an ajax request to Dolibarr : anti CSRF token added for POST/PUT/PATCH/DELETE,
		 * parameters sent as PHP expects them, response parsed, errors displayed and thrown.
		 * Responses of PHP class JsonResponse are recognized : result = 0 is an error, msg is the error message.
		 *
		 * @param {string} url
		 * @param {Object} [options] Any other option is passed to native fetch()
		 * @param {string} [options.method='GET']
		 * @param {Object|FormData|URLSearchParams} [options.data] Sent in query string for GET, in body otherwise
		 * @param {*} [options.json] Body sent as JSON
		 * @param {Object} [options.headers]
		 * @param {boolean} [options.token] Add the anti CSRF token, default true except for GET and HEAD
		 * @param {string} [options.responseType='auto'] 'auto', 'json', 'text' or 'response'
		 * @param {boolean} [options.showErrors=true] Display errors with setEventMessage
		 * @param {number} [options.timeout=0] Abort after this delay in ms
		 * @param {boolean} [options.unwrap=false] For a JsonResponse {result, msg, newToken, data, debug}, return only data
		 * @returns {Promise<*>} Parsed response body, throws Dolibarr.tools.fetch.Error {message, status, data, response}
		 */
		fetch: Object.assign(function(url, options) {}, {
			/**
			 * GET request
			 * @param {string} url
			 * @param {Object} [data] Sent in query string
			 * @param {Object} [options]
			 * @returns {Promise<*>}
			 */
			get(url, data, options) {},

			/**
			 * POST request, token added automatically
			 * @param {string} url
			 * @param {Object|FormData} [data] Sent in body
			 * @param {Object} [options]
			 * @returns {Promise<*>}
			 */
			post(url, data, options) {},

			/** Error thrown by Dolibarr.tools.fetch, with status, data and response properties */
			Error: class extends Error {},
		}),

		/**
		 * Replace an element of the page, or its content, by html received in ajax.
		 * Scripts of the html are executed and Dolibarr.initNewContent() is called on the new elements.
		 * @param {HTMLElement|jQuery|string} target Element or CSS selector
		 * @param {string|HTMLElement} content Html, or element of another document
		 * @param {Object} [options]
		 * @param {string} [options.mode='replace'] 'replace' the element, or 'inner' to replace its content
		 * @param {boolean} [options.initNewContent=true]
		 * @returns {HTMLElement|null}
		 */
		replaceContent(target, content, options) {},

		/**
		 * Get a controller of an object, to read and update it in ajax (data only, the page is not changed)
		 * @param {string} element Element of the object, ex: 'propal'
		 * @param {number} id
		 * @param {Object} [options] {tms}
		 * @returns {{
		 *   element: string, id: number, tms: number,
		 *   setField: function(string, *, Object=): Promise<Object|null>,
		 *   fetchFields: function(string[], Object=): Promise<{element: string, id: number, fields: Object, tms: number|null}>,
		 *   fetchLines: function(number[], Object=): Promise<{lines: Object, lineids: number[], tms: number|null}>,
		 *   on: function(string, function): function
		 * }}
		 */
		object: Object.assign(function(element, id, options) {}, {
			/** Class of the controllers */
			Controller: class {},
		}),

		/**
		 * Get the controller of the card of an object displayed in the page (one controller by card, shared by all the scripts)
		 * @param {string} element Element of the object, ex: 'propal'
		 * @param {number} id
		 * @param {Object} [options] {tms, url}
		 * @returns {{
		 *   element: string, id: number, tms: number, url: string, object: Object,
		 *   setField: function(string, *, Object=): Promise<Object|null>,
		 *   reload: function({sections?: string[], lines?: number[], fields?: string[]|null}, Object=): Promise<{sections: Object, lines: Object, fields: Object, lineids: number[]|null, tms: number|null, document: Document|null}>,
		 *   reloadSection: function(string, Object=): Promise<HTMLElement|null>,
		 *   reloadLine: function(number, Object=): Promise<HTMLElement|null>,
		 *   refreshFields: function(string[]=, Object=): Promise<Object>,
		 *   updateFields: function(Object, Object=): void,
		 *   getFieldContainers: function(string=): HTMLElement[],
		 *   getDisplayedFields: function(): string[],
		 *   fetchLines: function(number[], Object=): Promise<Object>,
		 *   replaceLine: function(number, string, number[]): HTMLElement|null,
		 *   removeLine: function(number): null,
		 *   fetchCardWithoutLines: function(Object=): Promise<Document>,
		 *   replaceFrom: function(Document, string): HTMLElement|null,
		 *   on: function(string, function): function
		 * }}
		 */
		card: Object.assign(function(element, id, options) {}, {
			/** Class of the controllers, to extend with Dolibarr.tools.card.register() */
			Controller: class {},

			/**
			 * Specialize the cards of an element with a class extending Dolibarr.tools.card.Controller
			 * @param {string} element
			 * @param {{Controller: Function}} specialization
			 */
			register(element, specialization) {},

			/**
			 * Define a part of the card tool, available as Dolibarr.tools.card.<name>
			 * @param {string} name
			 * @param {Object} api
			 */
			definePart(name, api) {},
		}),

		/**
		 * TThe langs tool
		 */
		langs: {
			/**
			 * Load translations for a domain (multiple locales)
			 * @param {string} domain
			 * @param {string} locales - comma-separated list
			 * @returns {Promise<Object>}
			 */
			load(domain, locales = currentLocale) {},

			/**
			 * Set the current locale to use for translations
			 * @param {string} locale
			 */
			setLocale(locale) {},

			/**
			 * Clear cached translations in IndexedDB
			 * @param {boolean} clearMemory Also clear translations loaded in memory
			 * @param {boolean} rebuildDatabase Delete the IndexedDB database
			 * @returns {Promise<void>}
			 */
			clearCache(clearMemory = false, rebuildDatabase = false) {},

			/**
			 * Current locale used for translations
			 * @type {string}
			 */
			currentLocale: '',

			/**
			 * Translate a key using current locale
			 * Supports placeholders like %s, %d, %f (simple sprintf)
			 * @param {string} key
			 * @param  {...any} args
			 * @returns {string}
			 */
			trans(key, ...args) {},

			/**
			 * Translate a key using current locale
			 * Supports placeholders like %s, %d, %f (simple sprintf)
			 * @param {string} key
			 * @param  {...any} args
			 * @returns {string}
			 */
			transNoEntities(key, ...args) {},
		},

		// You can add more standard Dolibarr tools here for IDE autocompletion.
		// Example:
		// alertUser: function(msg) {},
	},

	/**
	 * Defines a new tool.
	 * A tool defined without overwrite is protected : it can never be replaced.
	 * A tool defined with overwrite = true can be replaced later by another call with overwrite = true.
	 *
	 * @param {string} name Name of the tool
	 * @param {*} value Function, class or object
	 * @param {Object} [options]
	 * @param {boolean} [options.overwrite=false] Allow this tool to replace an overwritable tool and to be replaced later
	 * @param {boolean} [options.triggerHook=true] Execute the 'defineTool' hook
	 */
	defineTool(name, value, options = {}) {},

	/**
	 * Check if tool exists
	 * @param {string} name Tool name
	 * @returns {boolean} true if exists
	 */
	checkToolExist(name) {},

	/**
	 * Get read-only snapshot of context variables
	 */
	ContextVars() {},

	/**
	 * Defines a new context variable.
	 * @param {string} key
	 * @param {string|number|boolean} value
	 * @param {boolean} overwrite Allow this var to replace an overwritable var and to be replaced later
	 */
	setContextVar(key, value, overwrite = false) {},

	/**
	 * Set multiple context variables
	 * @param {Object} vars Object of key/value pairs
	 * @param {boolean} overwrite Allow overwriting existing values
	 */
	setContextVars(vars, overwrite = false) {},

	/**
	 * Get a context variable safely
	 * @param {string} key
	 * @param {*} fallback Optional fallback if variable not set
	 * @returns {*}
	 */
	getContextVar(key, fallback = null) {},

	/**
	 * Enable or disable debug mode
	 * @param {boolean} state
	 */
	debugMode(state) {},

	/**
	 * Enable or disable debug mode
	 * @returns {int}
	 */
	getDebugMode() {},

	/**
	 * Internal logger
	 * Only prints when debug mode is enabled
	 * @param {string} msg
	 */
	log(msg) {},

	/**
	 * Report a deprecated usage, warning shown once per key
	 * @param {string} key Unique key of the deprecated usage
	 * @param {string} msg Message explaining what to use instead
	 */
	deprecated(key, msg) {},

	/**
	 * List deprecated usages reported since page load
	 * @returns {Array<{key:string, msg:string, count:number}>}
	 */
	getDeprecations() {},

	/**
	 * Executes a hook-like JS event with CustomEvent.
	 * @param {string} hookName Hook identifier
	 * @param {object} data Extra information passed to listeners
	 */
	executeHook(hookName, data = {}) {},

	/**
	 * Trigger a standardized Dolibarr DOM reload hook.
	 * Useful for re-initializing UX components (tooltips, selects, modals, etc.)
	 * on dynamically injected or updated DOM portions.
	 *
	 * @param {HTMLElement|NodeList|Array<HTMLElement>|jQuery|string} targetEl
	 *   - A single DOM element
	 *   - A CSS selector string
	 *   - A NodeList or array of DOM elements
	 *   - A jQuery object (can contain multiple elements)
	 * @param {boolean} applyToChildrenOnly
	 *   - true: include only the children of each target element
	 *   - false: include the target elements themselves
	 */
	initNewContent(targetEl, applyToChildrenOnly = true) {},

	/**
	 * Registers an event listener.
	 * @param {string} eventName Event to listen to
	 * @param {function} callback Listener function
	 */
	on(eventName, callback) {},

	/**
	 * Unregister an event listener
	 * @param {string} eventName
	 * @param {function} callback
	 */
	off(eventName, callback) {},

	/**
	 * Register an asynchronous hook
	 * @param {string} eventName
	 * @param {function} fn Async function receiving previous result
	 * @param {Object} opts Optional {before, after, id} to control order
	 * @returns {string} The hook ID
	 */
	onAwait(eventName, fn, opts = {}) {},

	/**
	 * Execute async hooks sequentially
	 * @param {string} eventName
	 * @param {*} data Input data for first hook
	 * @returns {Promise<*>} Final result after all hooks
	 */
	async executeHookAwait(eventName, data) {},
};
