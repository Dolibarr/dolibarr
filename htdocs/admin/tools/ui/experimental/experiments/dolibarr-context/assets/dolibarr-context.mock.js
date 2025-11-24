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
			 * Load a single locale from cache or fetch
			 * @param {string} domain
			 * @param {string} locale
			 * @returns {Promise<Object>} translation object
			 */
			loadLocale(domain, locale) {},

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
			 * Translate a key using current locale
			 * Supports placeholders like %s, %d, %f (simple sprintf)
			 * @param {string} key
			 * @param  {...any} args
			 * @returns {string}
			 */
			trans(key, ...args) {},
		},

		// You can add more standard Dolibarr tools here for IDE autocompletion.
		// Example:
		// alertUser: function(msg) {},
	},

	/**
	 * Defines a new secure tool.
	 * @param {string} name Name of the tool
	 * @param {*} value Function, class or object
	 * @param {boolean} overwrite Explicitly allow overwriting an existing tool
	 *
	 * See also dolibarr-context.mock.js for defining all standard Dolibarr tools and creating mock implementations to improve code completion and editor support.
	 */
	defineTool(name, value, overwrite = false, triggerHook = true) {},

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
	 * @param {boolean} overwrite Allow overwriting existing value
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
	 * Executes a hook-like JS event with CustomEvent.
	 * @param {string} hookName Hook identifier
	 * @param {object} data Extra information passed to listeners
	 */
	executeHook(hookName, data = {}) {},

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
