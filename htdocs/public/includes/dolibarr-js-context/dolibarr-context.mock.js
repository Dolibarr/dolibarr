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
	 * @param {Object} [options]
	 * @param {boolean} [options.sticky=false] Keep data of this execution, listeners added later with Dolibarr.on() are called immediately with it
	 */
	executeHook(hookName, data = {}, options = {}) {},

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
	 * If the event is sticky and was already executed (ex: Init, Ready), the callback is called immediately with its data.
	 * @param {string} eventName Event to listen to
	 * @param {function} callback Listener function
	 * @param {Object} [options]
	 * @param {boolean} [options.once=false] Remove the listener after its first call
	 */
	on(eventName, callback, options = {}) {},

	/**
	 * Wait for the Dolibarr context to be ready (DOM loaded, Init done, tools defined).
	 * Works even if called after the Ready event.
	 * @param {function} [callback] Optional function called with the Ready data
	 * @returns {Promise<Object>} Resolved with the Ready data
	 */
	ready(callback) {},

	/**
	 * Wait for a tool to be defined.
	 * @param {string} name Tool name
	 * @param {Object} [options]
	 * @param {number} [options.timeout=0] Reject after this delay in ms, 0 to wait without limit
	 * @returns {Promise<*>} Resolved with the tool
	 */
	whenTool(name, options = {}) {},

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
