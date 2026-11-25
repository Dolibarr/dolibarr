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

	// Private storage for tools : name => {value, overwritable}
	const _tools = new Map();

	// Private storage for context vars or constants : key => {value, overwritable}
	const _contextVars = new Map();

	// Deprecated usages already reported : key => {key, msg, count}
	const _deprecations = new Map();

	// Arguments adapters registered by the backward compatibility layer (dolibarr-context.compat.js) : method => [fn]
	const _argsAdapters = {};

	// Internal map to track proxies for events
	const _proxies = new Map();

	// Native event dispatcher (standard DOM)
	const _events = new EventTarget();

	const _awaitHooks = {};  // Async hooks storage

	// Data of the last execution of sticky hooks, replayed to late listeners : hookName => data
	const _stickyHooks = new Map();

	// Pending Dolibarr.whenTool() calls : toolName => [resolve]
	const _toolWaiters = new Map();

	// Debug flag (disabled by default)
	let _debug = false;

	// -------------------------
	// Internal helper functions
	// -------------------------
	function _ensureEvent(name) { if (!_awaitHooks[name]) _awaitHooks[name] = []; }
	function _generateId() { return 'hook_' + Math.random().toString(36).slice(2); }
	function _idExists(name, id) { return _awaitHooks[name].some(h => h.id === id); }

	/**
	 * Pass arguments of a public method through the adapters of the backward compatibility layer
	 * @param {string} method Method name
	 * @param {Array} args Arguments as received
	 * @returns {Array} Adapted arguments
	 */
	function _adaptArgs(method, args) {
		(_argsAdapters[method] || []).forEach(fn => { args = fn(args); });
		return args;
	}

	/**
	 * Store an entry in a protected registry (tools or context vars).
	 * An existing entry can only be replaced if it was stored with overwrite = true
	 * and if the new call also asks for overwrite = true.
	 * @param {Map} registry
	 * @param {string} label Used in error messages
	 * @param {string} name
	 * @param {*} value
	 * @param {boolean} overwrite
	 */
	function _storeEntry(registry, label, name, value, overwrite) {
		const existing = registry.get(name);
		if (existing) {
			if (!existing.overwritable) {
				throw new Error(`Dolibarr: ${label} '${name}' already defined and protected`);
			}
			if (!overwrite) {
				throw new Error(`Dolibarr: ${label} '${name}' already defined, set overwrite to true to replace it`);
			}
		}
		registry.set(name, { value, overwritable: !!overwrite });
	}

	/**
	 * Build a frozen plain object from a registry
	 * @param {Map} registry
	 * @returns {Object}
	 */
	function _registryToObject(registry) {
		const obj = {};
		registry.forEach((entry, name) => { obj[name] = entry.value; });
		return Object.freeze(obj);
	}

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
			return _registryToObject(_tools);
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
		 *
		 * See also dolibarr-context.mock.js for defining all standard Dolibarr tools and creating mock implementations to improve code completion and editor support.
		 */
		defineTool(...args) {
			const [name, value, options = {}, ...rest] = _adaptArgs('defineTool', args);
			if (options === null || typeof options !== 'object' || rest.length > 0) {
				throw new TypeError(`Dolibarr: defineTool('${name}') third parameter must be an options object {overwrite, triggerHook}`);
			}

			const { overwrite = false, triggerHook = true } = options;

			_storeEntry(_tools, 'Tool', name, value, overwrite);

			// Resolve pending whenTool() calls, even if the hook is not triggered
			(_toolWaiters.get(name) || []).forEach(resolve => resolve(value));
			_toolWaiters.delete(name);

			this.log(`Tool defined: ${name}, triggerHook: ${triggerHook}, overwrite: ${overwrite} `);
			if (triggerHook) {
				this.executeHook('defineTool', { toolName: name, overwrite });
			}
		},

		/**
		 * Check if tool exists
		 * @param {string} name Tool name
		 * @returns {boolean} true if exists
		 */
		checkToolExist(name) {
			return _tools.has(name);
		},

		/**
		 * Get read-only snapshot of context variables
		 */
		get ContextVars() {
			return _registryToObject(_contextVars);
		},

		/**
		 * Defines a new context variable.
		 * A var defined without overwrite is protected : it can never be replaced.
		 * A var defined with overwrite = true can be replaced later by another call with overwrite = true.
		 *
		 * @param {string} key
		 * @param {string|number|boolean} value
		 * @param {boolean} overwrite Allow this var to replace an overwritable var and to be replaced later
		 */
		setContextVar(key, value, overwrite = false) {
			// Accept only string, number, or boolean
			const type = typeof value;
			if (type !== 'string' && type !== 'number' && type !== 'boolean') {
				throw new TypeError(`Dolibarr: ContextVar '${key}' must be a string, number, or boolean`);
			}

			_storeEntry(_contextVars, 'ContextVar', key, value, overwrite);

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
			return _contextVars.has(key) ? _contextVars.get(key).value : fallback;
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
		 * Outputs logs only when debug mode is enabled.
		 *
		 * Why use Dolibarr.log instead of console.log?
		 * Dolibarr.log avoids console noise caused by the Dolibarr core context
		 * or internal notices that can easily spam the console.
		 * Dolibarr.log is primarily intended to be used by the Dolibarr context itself,
		 * providing a unified logging mechanism across the application.
		 *
		 * This allows module developers to focus on their own logs (with console.log)
		 * without being distracted by unrelated global Dolibarr activity.
		 *
		 * @param {string} msg Log message
		 */
		log(msg) {
			if (_debug) console.log(`Dolibarr: ${msg}`);
		},

		/**
		 * Report a deprecated usage.
		 * The warning is always shown (not only in debug mode) but only once per key,
		 * so module developers can see what they have to migrate.
		 *
		 * @param {string} key Unique key of the deprecated usage
		 * @param {string} msg Message explaining what to use instead
		 */
		deprecated(key, msg) {
			const entry = _deprecations.get(key);
			if (entry) {
				entry.count++;
				return;
			}
			_deprecations.set(key, { key, msg, count: 1 });
			console.warn(`Dolibarr deprecated: ${msg}`);
		},

		/**
		 * List deprecated usages reported since page load
		 * @returns {Array<{key:string, msg:string, count:number}>}
		 */
		getDeprecations() {
			return Array.from(_deprecations.values(), entry => ({ ...entry }));
		},

		/**
		 * Internal API used only by the backward compatibility layer (dolibarr-context.compat.js).
		 * Do not use it in modules.
		 */
		_compat: Object.freeze({
			/**
			 * Register a function receiving the arguments array of a public method and returning adapted arguments
			 * @param {string} method Method name (ex: 'defineTool')
			 * @param {function(Array): Array} fn
			 */
			addArgsAdapter(method, fn) {
				if (!_argsAdapters[method]) _argsAdapters[method] = [];
				_argsAdapters[method].push(fn);
			}
		}),

		/**
		 * Executes a hook-like JS event with CustomEvent.
		 * @param {string} hookName Hook identifier
		 * @param {object} data Extra information passed to listeners
		 * @param {Object} [options]
		 * @param {boolean} [options.sticky=false] Keep data of this execution, listeners added later with Dolibarr.on()
		 *                                         are called immediately with it (used for one time events like Init or Ready)
		 */
		executeHook(hookName, data = {}, options = {}) {
			this.log(`Hook executed: ${hookName}`);

			// Stored before dispatch : listeners added during dispatch are not called by EventTarget, they get the replay instead
			if (options.sticky) {
				_stickyHooks.set(hookName, data);
			}

			const ev = new CustomEvent(hookName, { detail: data });

			// Dispatch on internal EventTarget
			_events.dispatchEvent(ev);

			// Dispatch globally on document for backward compatibility
			if (typeof document !== "undefined") {
				document.dispatchEvent(new CustomEvent('Dolibarr:' + hookName, { detail: data }));
			}
		},

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
		initNewContent(targetEl, applyToChildrenOnly = true) {
			let elements = this.normalizeToElements(targetEl);
			if (elements.length === 0) return;

			// Flatten elements according to applyToChildrenOnly
			const finalElements = [];
			elements.forEach(el => {
				if (applyToChildrenOnly) {
					finalElements.push(...Array.from(el.children));
				} else {
					finalElements.push(el);
				}
			});

			if (finalElements.length === 0) return;

			// Trigger standardized hook with an array of elements
			this.executeHook('initNewContent', {
				targets: finalElements, // final loaded elements
				targetEl: targetEl, // the raw element sent in hook
				applyToChildrenOnly: applyToChildrenOnly
			});
		},

		/**
		 * Normalize a target element input into an array of DOM elements.
		 *
		 * This function accepts various types of inputs:
		 * - jQuery objects
		 * - CSS selectors (string)
		 * - NodeLists
		 * - Arrays of HTMLElements
		 * - Single HTMLElement
		 *
		 * @param {HTMLElement | HTMLElement[] | NodeList | jQuery | string} targetEl - The target element(s) to normalize.
		 * @returns {HTMLElement[]} An array of DOM elements corresponding to the input.
		 *
		 * @example
		 * // Using a CSS selector
		 * const elems = Dolibarr.normalizeToElements('.my-class');
		 *
		 * @example
		 * // Using a single HTMLElement
		 * const elem = Dolibarr.normalizeToElements(document.getElementById('myId'));
		 *
		 * @example
		 * // Using a jQuery object
		 * const elems = Dolibarr.normalizeToElements($('.my-class'));
		 *
		 * @example
		 * // Using an array of elements
		 * const elems = Dolibarr.normalizeToElements([elem1, elem2]);
		 */
		normalizeToElements(targetEl){
			let elements = [];

			// Convert jQuery to array of DOM elements
			if (typeof jQuery !== 'undefined' && targetEl instanceof jQuery) {
				elements = targetEl.toArray();
			}
			// CSS selector
			else if (typeof targetEl === 'string') {
				elements = Array.from(document.querySelectorAll(targetEl));
			}
			// NodeList or array
			else if (NodeList.prototype.isPrototypeOf(targetEl) || Array.isArray(targetEl)) {
				elements = Array.from(targetEl);
			}
			// Single HTMLElement
			else if (targetEl instanceof HTMLElement) {
				elements = [targetEl];
			}

			return elements;
		},

		/**
		 * Registers an event listener.
		 * If the event is sticky and was already executed (ex: Init, Ready), the callback is called immediately with its data.
		 *
		 * @param {string} eventName Event to listen to
		 * @param {function} callback Listener function
		 * @param {Object} [options]
		 * @param {boolean} [options.once=false] Remove the listener after its first call
		 */
		on(eventName, callback, options = {}) {
			const once = !!options.once;

			if (once && _stickyHooks.has(eventName)) {
				callback(_stickyHooks.get(eventName));
				return;
			}

			// Create a proxy to extract e.detail
			const proxy = (e) => {
				if (once) this.off(eventName, callback);
				callback(e.detail);
			};

			// Store the proxy so we can remove it later
			if (!_proxies.has(eventName)) _proxies.set(eventName, new Map());
			_proxies.get(eventName).set(callback, proxy);

			// Attach proxy to the internal EventTarget
			_events.addEventListener(eventName, proxy);

			// Replay sticky event already executed
			if (_stickyHooks.has(eventName)) {
				callback(_stickyHooks.get(eventName));
			}
		},

		/**
		 * Wait for the Dolibarr context to be ready (DOM loaded, Init done, tools defined).
		 * Works even if called after the Ready event.
		 *
		 * @param {function} [callback] Optional function called with the Ready data
		 * @returns {Promise<Object>} Resolved with the Ready data
		 */
		ready(callback) {
			return new Promise(resolve => {
				this.on('Ready', data => {
					if (typeof callback === 'function') callback(data);
					resolve(data);
				}, { once: true });
			});
		},

		/**
		 * Wait for a tool to be defined.
		 * Works even if the tool is defined without triggering the defineTool hook.
		 *
		 * @param {string} name Tool name
		 * @param {Object} [options]
		 * @param {number} [options.timeout=0] Reject after this delay in ms, 0 to wait without limit
		 * @returns {Promise<*>} Resolved with the tool
		 */
		whenTool(name, options = {}) {
			if (_tools.has(name)) {
				return Promise.resolve(_tools.get(name).value);
			}

			return new Promise((resolve, reject) => {
				let timer = null;
				const waiter = value => {
					if (timer) clearTimeout(timer);
					resolve(value);
				};

				if (!_toolWaiters.has(name)) _toolWaiters.set(name, []);
				_toolWaiters.get(name).push(waiter);

				if (options.timeout > 0) {
					timer = setTimeout(() => {
						const waiters = _toolWaiters.get(name) || [];
						waiters.splice(waiters.indexOf(waiter), 1);
						reject(new Error(`Dolibarr: tool '${name}' not defined after ${options.timeout} ms`));
					}, options.timeout);
				}
			});
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
			Dolibarr.executeHook('Init', { context: Dolibarr }, { sticky: true });
			Dolibarr.log('Context Init done');

			// Only after Init is done, fire Ready
			fireReady();
		};

		const fireReady = () => {
			Dolibarr.executeHook('Ready', { context: Dolibarr }, { sticky: true });
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
		console.log("`Documentation for admin only on :  %cModule builder ➜ UX Components Doc","font-weight: bold;");

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

		// DEPRECATIONS
		console.groupCollapsed("Deprecations");

		console.log(
			"List deprecated usages reported on this page : %cDolibarr.getDeprecations();",
			"font-weight: bold;"
		);

		console.groupEnd();


		console.groupEnd(); // END MAIN GROUP
	}, { triggerHook: false });




// Auto-show help when console is opened
	Dolibarr.tools.showConsoleHelp();

	return Dolibarr;
});
