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

	// Native event dispatcher (standard DOM)
	const _events = new EventTarget();

	// Debug flag (disabled by default)
	let _debug = false;

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

			this.log(`Tool defined: ${name}, triggerHook: ${triggerHook}`);
			if(triggerHook) {
				Dolibarr.executeHook('defineTool', { toolName: name, overwrite: overwrite });
			}
		},

		/**
		 * Checks if a tool already exists.
		 * @param {string} name Tool name
		 * @returns {boolean} true if exists
		 */
		checkToolExist(name) {
			return Object.prototype.hasOwnProperty.call(_tools, name);
		},

		/**
		 * Enables or disables debug mode.
		 * When enabled, Dolibarr.log() writes to the console.
		 */
		debugMode(state) {
			_debug = !!state;
			// Sauvegarde dans localStorage
			if (typeof window !== "undefined" && window.localStorage) {
				localStorage.setItem('DolibarrDebugMode', _debug ? '1' : '0');
			}
			this.log(`Debug mode: ${_debug}`);
		},

		/**
		 * Internal logger (only active when debug mode is enabled).
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

			// Dispatch globally on document so document.addEventListener('DolibarrHook:' + hookName) can catch it
			if (typeof document !== "undefined") {
				document.dispatchEvent(new CustomEvent('Dolibarr:' + hookName, { detail: data }));
			}
		},

		/**
		 * Registers an event listener.
		 * @param {string} eventName Event to listen to
		 * @param {function} callback Listener function
		 */
		on(eventName, callback) {
			_events.addEventListener(eventName, callback);
		},

		/**
		 * Unregisters an event listener.
		 * @param {string} eventName Event name
		 * @param {function} callback Listener previously added
		 */
		off(eventName, callback) {
			_events.removeEventListener(eventName, callback);
		}
	};

	// Lock core object to prevent tampering
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

	// Restaurer debug mode depuis localStorage
	if (typeof window !== "undefined" && window.localStorage) {
		const saved = localStorage.getItem('DolibarrDebugMode');
		if (saved === '1') {
			Dolibarr.debugMode(true);
		}
	}


	/**
	 * Display help in console log
	 */
	Dolibarr.defineTool('showConsoleHelp', () => {
		console.groupCollapsed(
			"%cDolibarr JS Developers HELP",
			"background-color: #95cf04 ; color: #ffffff ; font-weight: bold ; padding: 4px ;"
		);
		console.log( "Show this help : %cDolibarr.tools.showConsoleHelp();","font-weight: bold ;");

		console.groupCollapsed('Dolibarr debug mode');
		console.log( "Activate Dolibarr debug mode : %cDolibarr.debugMode(true);","font-weight: bold ;");
		console.log( "Disable Dolibarr debug mode : %cDolibarr.debugMode(false);","font-weight: bold ;");
		console.log( "Note : debug mode status is persistent");
		console.groupEnd();

		console.groupEnd();
	}, false, false);

	Dolibarr.tools.showConsoleHelp();

	// Trigger DolibarrContext:init as DOM ready
	(function triggerContextInit() {
		const initHook = () => {
			Dolibarr.executeHook('Ready', { context: Dolibarr });
			Dolibarr.log('Context initialized');
		};

		if (document.readyState === 'complete' || document.readyState === 'interactive') {
			// DOM is already ready
			initHook();
		} else {
			// Wait for DOM to be ready
			document.addEventListener('DOMContentLoaded', initHook);
		}
	})();



	return Dolibarr;
});
