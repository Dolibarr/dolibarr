document.addEventListener('Dolibarr:Init', function(e) {
	/**
	 * Dolibarr.tools.replaceContent
	 * -----------------------------
	 * Replace an element of the page (or its content) by html received in ajax, like if it was in the page from the start :
	 * - inline scripts of the new html are executed (select2, date pickers...), with the nonce of the current page, and external scripts not already loaded by the page
	 * - Dolibarr.initNewContent() is called on the new elements (tooltips...)
	 *
	 * Note : scripts of the new html waiting for Dolibarr:Ready or DOMContentLoaded are never executed, these events are already passed.
	 */

	/**
	 * Get the nonce of the scripts of the current page (empty if Content Security Policy is not used)
	 * @returns {string}
	 */
	function getPageNonce() {
		const script = document.querySelector('script[nonce]');
		return script ? (script.nonce || script.getAttribute('nonce') || '') : '';
	}

	/**
	 * Urls of the external scripts already executed by the page: they are not executed again (classes declared twice...)
	 * @type {Set<string>}
	 */
	const loadedScripts = new Set();

	/**
	 * Add the external scripts of the page to the scripts already executed, before they are removed by a replacement
	 */
	function rememberLoadedScripts() {
		Array.from(document.scripts).forEach(script => {
			if (script.src) loadedScripts.add(script.src);
		});
	}

	/**
	 * Execute the scripts of nodes inserted in the page (scripts inserted with innerHTML are never executed by the browser)
	 * @param {Node[]} nodes
	 */
	function executeScripts(nodes) {
		const nonce = getPageNonce();

		nodes.forEach(node => {
			if (!(node instanceof Element)) return;

			const scripts = node.tagName === 'SCRIPT' ? [node] : Array.from(node.querySelectorAll('script'));
			scripts.forEach(oldScript => {
				if (oldScript.src && loadedScripts.has(oldScript.src)) {
					oldScript.remove();
					return;
				}

				const newScript = document.createElement('script');
				Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
				// The html comes from a page of this Dolibarr, its scripts get the nonce of the current page
				if (nonce) newScript.nonce = nonce;
				// Keep the order of execution of external scripts
				if (newScript.src) {
					newScript.async = false;
					loadedScripts.add(newScript.src);
				}
				newScript.textContent = oldScript.textContent;
				oldScript.replaceWith(newScript);
			});
		});
	}

	/**
	 * Replace an element of the page, or its content, by html
	 *
	 * @param {HTMLElement|jQuery|string} target Element or CSS selector, only the first element found is used
	 * @param {string|HTMLElement} content Html, or element (of another document, ex: parsed with DOMParser)
	 * @param {Object} [options]
	 * @param {string} [options.mode='replace'] 'replace' the element, or 'inner' to replace its content
	 * @param {boolean} [options.initNewContent=true] Call Dolibarr.initNewContent() on the new elements
	 * @returns {HTMLElement|null} The new element ('replace' mode, null if the html has no element) or the target ('inner' mode), null if target not found
	 */
	function replaceContent(target, content, options = {}) {
		const { mode = 'replace', initNewContent = true } = options;

		const targetEl = Dolibarr.normalizeToElements(target)[0];
		if (!targetEl || (mode === 'replace' && !targetEl.parentNode)) {
			Dolibarr.log('tools: replaceContent: target not found');
			return null;
		}

		// A template parses any html, even table rows
		const template = document.createElement('template');
		template.innerHTML = (content instanceof Element) ? content.outerHTML : String(content);
		const newNodes = Array.from(template.content.childNodes);

		rememberLoadedScripts();
		if (mode === 'inner') {
			targetEl.replaceChildren(...newNodes);
		} else {
			targetEl.replaceWith(...newNodes);
		}

		executeScripts(newNodes);

		const newElements = newNodes.filter(node => node instanceof Element && node.tagName !== 'SCRIPT');
		if (initNewContent) {
			if (mode === 'inner') {
				Dolibarr.initNewContent(targetEl);
			} else if (newElements.length > 0) {
				Dolibarr.initNewContent(newElements, false);
			}
		}

		if (mode === 'inner') return targetEl;

		return newElements.length > 0 ? newElements[0] : null;
	}

	Dolibarr.defineTool('replaceContent', replaceContent);
});
