// CustomEvent doesn’t show up until IE 11 and Safari 10. Fortunately a simple polyfill pushes support back to any IE 9.
(function () {
	if ( typeof window.CustomEvent === "function" ) return false;
	function CustomEvent ( event, params ) {
		params = params || { bubbles: false, cancelable: false, detail: undefined };
		var evt = document.createEvent( 'CustomEvent' );
		evt.initCustomEvent( event, params.bubbles, params.cancelable, params.detail );
		return evt;
	}
	CustomEvent.prototype = window.Event.prototype;
	window.CustomEvent = CustomEvent;
})();
// End old browsers support

/**
 * Dolibarr Global Context (UMD)
 * Provides a secure global object window.Dolibarr
 * with non-replaceable tools, events and debug mode.
 *
 * See also dolibarr-context.mock.js for defining all standard Dolibarr tools and creating mock implementations to improve code completion and editor support.
 *
 */
(function (root, factory) {
	// Support AMD
	if (typeof define === "function" && define.amd) {
		define([], factory);

		// Support CommonJS (Node, bundlers)
	} else if (typeof exports === "object") {
		module.exports = factory();

		// Fallback global (browser)
	} else {
		root.Dolibarr = root.Dolibarr || factory();
	}
})(typeof self !== "undefined" ? self : this, function () {

	// Prevent double initialization if script loaded twice
	if (typeof window !== "undefined" && window.Dolibarr) {
		return window.Dolibarr;
	}

	// Private storage for secure tools (non-replaceable)
	const _tools = {};

	// Private storage for secure context vars or constants (non-replaceable)
	const _contextVars = {};

	// Internal map to track proxies for events
	const _proxies = new Map();

	// Native event dispatcher (standard DOM)
	const _events = new EventTarget();

	const _awaitHooks = {};  // Async hooks storage

	// Debug flag (disabled by default)
	let _debug = false;

	// -------------------------
	// Internal helper functions
	// -------------------------
	function _ensureEvent(name) { if (!_awaitHooks[name]) _awaitHooks[name] = []; }
	function _generateId() { return 'hook_' + Math.random().toString(36).slice(2); }
	function _idExists(name, id) { return _awaitHooks[name].some(h => h.id === id); }

	/**
	 * Insert a new hook entry in the array respecting optional before/after lists
	 */
	function _insertWithOrder(arr, entry, beforeList, afterList) {
		if ((!beforeList || beforeList.length === 0) && (!afterList || afterList.length === 0)) {
			arr.push(entry);
			return arr;
		}

		let ordered = [...arr];
		let index = ordered.length;

		if (beforeList && beforeList.length > 0) {
			for (const target of beforeList) {
				const i = ordered.findIndex(h => h.id === target);
				if (i !== -1 && i < index) index = i;
			}
		}

		if (afterList && afterList.length > 0) {
			for (const target of afterList) {
				const i = ordered.findIndex(h => h.id === target);
				if (i !== -1 && i >= index) index = i + 1;
			}
		}

		if (index > ordered.length) index = ordered.length;
		ordered.splice(index, 0, entry);
		return ordered;
	}

	// -------------------------
	// Dolibarr object
	// -------------------------
	const Dolibarr = {

		/**
		 * Returns a frozen copy of the registered tools.
		 * Tools cannot be modified or replaced from outside.
		 */
		get tools() {
			return Object.freeze({ ..._tools });
		},

		/**
		 * Defines a new secure tool.
		 * @param {string} name Name of the tool
		 * @param {*} value Function, class or object
		 * @param {boolean} overwrite Explicitly allow overwriting an existing tool
		 *
		 * See also dolibarr-context.mock.js for defining all standard Dolibarr tools and creating mock implementations to improve code completion and editor support.
		 */
		defineTool(name, value, overwrite = false, triggerHook = true) {
			// Prevent silent overrides unless "overwrite" is true
			if (!overwrite && this.checkToolExist(name)) {
				throw new Error(`Dolibarr: Tool '${name}' already defined`);
			}

			// Define the tool as read-only and non-configurable
			Object.defineProperty(_tools, name, {
				value,
				writable: false,
				configurable: false,
				enumerable: true,
			});

			this.log(`Tool defined: ${name}, triggerHook: ${triggerHook}, overwrite: ${overwrite} `);
			if(triggerHook) {
				this.executeHook('defineTool', { toolName: name, overwrite });
			}
		},

		/**
		 * Check if tool exists
		 * @param {string} name Tool name
		 * @returns {boolean} true if exists
		 */
		checkToolExist(name) {
			return Object.prototype.hasOwnProperty.call(_tools, name);
		},

		/**
		 * Get read-only snapshot of context variables
		 */
		get ContextVars() {
			return Object.freeze({ ..._contextVars });
		},

		/**
		 * Defines a new context variable.
		 * @param {string} key
		 * @param {string|number|boolean} value
		 * @param {boolean} overwrite Allow overwriting existing value
		 */
		setContextVar(key, value, overwrite = false) {
			// Accept only string, number, or boolean
			const type = typeof value;
			if (type !== 'string' && type !== 'number' && type !== 'boolean') {
				throw new TypeError(`Dolibarr: ContextVar '${key}' must be a string, number, or boolean`);
			}

			if (!overwrite && _contextVars.hasOwnProperty(key)) {
				throw new Error(`Dolibarr: ContextVar '${key}' already defined`);
			}

			Object.defineProperty(_contextVars, key, {
				value,
				writable: false,
				configurable: false,
				enumerable: true
			});

			this.log(`ContextVar set: ${key} = ${value} (overwrite: ${overwrite})`);
			this.executeHook('setContextVar', { key, value, overwrite });
		},


		/**
		 * Set multiple context variables
		 * @param {Object} vars Object of key/value pairs
		 * @param {boolean} overwrite Allow overwriting existing values
		 */
		setContextVars(vars, overwrite = false) {
			if (typeof vars !== 'object' || vars === null) {
				throw new Error('Dolibarr: setContextVars expects an object');
			}

			for (const [key, value] of Object.entries(vars)) {
				this.setContextVar(key, value, overwrite);
			}
		},

		/**
		 * Get a context variable safely
		 * @param {string} key
		 * @param {*} fallback Optional fallback if variable not set
		 * @returns {*}
		 */
		getContextVar(key, fallback = null) {
			return _contextVars.hasOwnProperty(key) ? _contextVars[key] : fallback;
		},

		/**
		 * Enable or disable debug mode
		 * @param {boolean} state
		 */
		debugMode(state) {
			_debug = !!state;
			// save in localStorage
			if (typeof window !== "undefined" && window.localStorage) {
				localStorage.setItem('DolibarrDebugMode', _debug ? '1' : '0');
			}
			this.log(`Debug mode: ${_debug}`);
		},

		/**
		 * Enable or disable debug mode
		 * @returns {int}
		 */
		getDebugMode() {
			return _debug ? 1 : 0
		},

		/**
		 * Internal logger
		 * Only prints when debug mode is enabled
		 * @param {string} msg
		 */
		log(msg) {
			if (_debug) console.log(`Dolibarr: ${msg}`);
		},

		/**
		 * Executes a hook-like JS event with CustomEvent.
		 * @param {string} hookName Hook identifier
		 * @param {object} data Extra information passed to listeners
		 */
		executeHook(hookName, data = {}) {
			this.log(`Hook executed: ${hookName}`);

			const ev = new CustomEvent(hookName, { detail: data });

			// Dispatch on internal EventTarget
			_events.dispatchEvent(ev);

			// Dispatch globally on document for backward compatibility
			if (typeof document !== "undefined") {
				document.dispatchEvent(new CustomEvent('Dolibarr:' + hookName, { detail: data }));
			}

			// Notify Dolibarr.on() listeners with data directly
			const listeners = _events.listeners?.[hookName] || [];
			listeners.forEach(fn => fn(data));
		},

		/**
		 * Registers an event listener.
		 * @param {string} eventName Event to listen to
		 * @param {function} callback Listener function
		 */
		on(eventName, callback) {
			// Create a proxy to extract e.detail
			const proxy = function(e) {
				callback(e.detail);
			};

			// Store the proxy so we can remove it later
			if (!_proxies.has(eventName)) _proxies.set(eventName, new Map());
			_proxies.get(eventName).set(callback, proxy);

			// Attach proxy to the internal EventTarget
			_events.addEventListener(eventName, proxy);
		},

		/**
		 * Unregister an event listener
		 * @param {string} eventName
		 * @param {function} callback
		 */
		off(eventName, callback) {
			const map = _proxies.get(eventName);
			if (!map) return;

			const proxy = map.get(callback);
			if (!proxy) return;

			// Remove proxy from EventTarget
			_events.removeEventListener(eventName, proxy);
			map.delete(callback);

			// Cleanup if no proxies remain for this event
			if (map.size === 0) _proxies.delete(eventName);
		},

		/**
		 * Register an asynchronous hook
		 * @param {string} eventName
		 * @param {function} fn Async function receiving previous result
		 * @param {Object} opts Optional {before, after, id} to control order
		 * @returns {string} The hook ID
		 */
		onAwait(eventName, fn, opts = {}) {
			_ensureEvent(eventName);
			let id = opts.id || _generateId();
			if (_idExists(eventName, id)) throw new Error(`onAwait: ID '${id}' already used for '${eventName}'`);
			const before = Array.isArray(opts.before) ? opts.before : (opts.before ? [opts.before] : []);
			const after  = Array.isArray(opts.after)  ? opts.after  : (opts.after  ? [opts.after]  : []);
			_awaitHooks[eventName] = _insertWithOrder(_awaitHooks[eventName], { id, fn }, before, after);
			return id;
		},

		/**
		 * Execute async hooks sequentially
		 * @param {string} eventName
		 * @param {*} data Input data for first hook
		 * @returns {Promise<*>} Final result after all hooks
		 */
		async executeHookAwait(eventName, data) {
			this.log(`Await Hook executed: ${eventName}`);

			_ensureEvent(eventName);
			let result = data;
			for (const h of _awaitHooks[eventName]) {
				result = await h.fn(result);
			}
			return result;
		}
	};

	// Lock Dolibarr core object
	Object.freeze(Dolibarr);

	// Expose Dolibarr to window in a protected, non-writable way
	if (typeof window !== "undefined") {
		Object.defineProperty(window, "Dolibarr", {
			value: Dolibarr,
			writable: false,
			configurable: false,
			enumerable: true,
		});
	}

	// Restore debug mode from localStorage
	if (typeof window !== "undefined" && window.localStorage) {
		const saved = localStorage.getItem('DolibarrDebugMode');
		if (saved === '1') {
			Dolibarr.debugMode(true);
		}
	}


	// Force initialise hook init and Ready in good execution order
	(function triggerDolibarrHooks() {
		// Fire Init first
		const fireInit = () => {
			Dolibarr.executeHook('Init', { context: Dolibarr });
			Dolibarr.log('Context Init done');

			// Only after Init is done, fire Ready
			fireReady();
		};

		const fireReady = () => {
			Dolibarr.executeHook('Ready', { context: Dolibarr });
			Dolibarr.log('Context Ready done');
		};

		if (document.readyState === 'complete' || document.readyState === 'interactive') {
			// DOM already ready, trigger Init -> Ready in order
			fireInit();
		} else {
			// Wait for DOM ready, then trigger Init -> Ready
			document.addEventListener('DOMContentLoaded', fireInit);
		}
	})();

	/**
	 * Display help in console log
	 */
	Dolibarr.defineTool('showConsoleHelp', () => {

		console.groupCollapsed(
			"%cDolibarr JS Developers HELP",
			"background-color: #95cf04; color: #ffffff; font-weight: bold; padding: 4px;"
		);

		console.log("Show this help : %cDolibarr.tools.showConsoleHelp();","font-weight: bold;");
		console.log(`Documentation for admin only on :  %cModule builder ➜ UX Components Doc`,"font-weight: bold;");

		// DEBUG MODE
		console.groupCollapsed("Dolibarr debug mode");

		console.log(
			"When help was displayed, status was: %c" + (Dolibarr.getDebugMode() ? "ENABLED" : "DISABLED"),
			"font-weight: bold; color:" + (Dolibarr.getDebugMode() ? "green" : "red") + ";"
		);

		console.log(
			"Activate debug mode : %cDolibarr.debugMode(true);",
			"font-weight: bold;"
		);

		console.log(
			"Disable debug mode : %cDolibarr.debugMode(false);",
			"font-weight: bold;"
		);

		console.log("Note : debug mode status is persistent.");
		console.groupEnd();

		// HOOKS
		console.groupCollapsed("Hooks helpers");

		console.log(
			"Run a hook manually : %cDolibarr.executeHook('hookName', {...})",
			"font-weight: bold;"
		);

		console.log(
			"Run await hooks manually : %cawait Dolibarr.executeHookAwait('hookName', {...})",
			"font-weight: bold;"
		);

		console.groupEnd();


		console.groupEnd(); // END MAIN GROUP
	}, false, false);




// Auto-show help when console is opened
	Dolibarr.tools.showConsoleHelp();

	return Dolibarr;
});
