/**
 * Dolibarr JS context : backward compatibility layer
 *
 * COMPAT-JSCONTEXT @deprecated since 25.0, remove in 27.0
 *
 * This file restores old behaviors of the Dolibarr JS context on top of dolibarr-context.umd.js,
 * so the core file only contains the new behaviors.
 * It is loaded by main.inc.php right after dolibarr-context.umd.js, unless MAIN_JS_CONTEXT_DISABLE_COMPAT is set.
 * Each old usage reports a warning with Dolibarr.deprecated(), see Dolibarr.getDeprecations() in browser console.
 *
 * To remove this layer : delete this file, its loading in main.inc.php
 * and every code tagged COMPAT-JSCONTEXT.
 */
(function () {
	if (typeof window === 'undefined' || !window.Dolibarr || !window.Dolibarr._compat) {
		return;
	}

	const Dolibarr = window.Dolibarr;

	/**
	 * COMPAT-JSCONTEXT defineTool(name, value, overwrite, triggerHook)
	 * becomes defineTool(name, value, {overwrite, triggerHook})
	 */
	Dolibarr._compat.addArgsAdapter('defineTool', function (args) {
		if (typeof args[2] !== 'boolean' && typeof args[3] !== 'boolean') {
			return args;
		}

		Dolibarr.deprecated(
			'defineTool.positionalArgs',
			"defineTool(name, value, overwrite, triggerHook) is deprecated, use defineTool(name, value, {overwrite, triggerHook}) instead. Backward compatibility will be removed in Dolibarr 27.0"
		);

		return [args[0], args[1], { overwrite: args[2] === true, triggerHook: args[3] !== false }];
	});
})();
