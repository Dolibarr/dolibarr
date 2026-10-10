document.addEventListener('Dolibarr:Init', function(e) {
	/**
	 * Dolibarr.tools.fetch
	 * --------------------
	 * Ajax requests to Dolibarr pages with :
	 * - anti CSRF token added automatically (POST, PUT, PATCH, DELETE or option token: true)
	 * - parameters sent as PHP expects them (arrays as key[], objects as key[sub])
	 * - response parsed according to its content type, or as JSON if it looks like JSON (or forced with option responseType)
	 * - errors displayed with Dolibarr.tools.setEventMessage() (unless option showErrors: false)
	 *   and thrown as Dolibarr.tools.fetch.Error with status and response data
	 * - responses built with PHP class JsonResponse {result, msg, newToken, data, debug} recognized :
	 *   result = 0 is an error even with HTTP 200, msg is used as error message, option unwrap returns only data
	 *
	 * Require Dolibarr context vars
	 * DOL_CSRF_TOKEN
	 *
	 * Note : the called page must define NOTOKENRENEWAL, otherwise each request renews the session token
	 * and invalidates the forms of the current page.
	 */

	/**
	 * Error thrown by Dolibarr.tools.fetch
	 */
	class DolibarrFetchError extends Error {
		/**
		 * @param {string} message Error message
		 * @param {number} status HTTP status, 0 for network error or timeout
		 * @param {*} data Parsed response body if any
		 * @param {Response|null} response Fetch response if any
		 */
		constructor(message, status = 0, data = null, response = null) {
			super(message);
			this.name = 'DolibarrFetchError';
			this.status = status;
			this.data = data;
			this.response = response;
		}
	}

	/**
	 * Append values to URLSearchParams or FormData the way PHP reads them
	 * @param {URLSearchParams|FormData} target
	 * @param {Object|null} data
	 * @param {string} prefix
	 */
	function appendParams(target, data, prefix = '') {
		if (data === null || data === undefined) return;

		if (data instanceof URLSearchParams || data instanceof FormData) {
			data.forEach((value, key) => target.append(key, value));
			return;
		}

		Object.entries(data).forEach(([key, value]) => {
			const name = prefix ? `${prefix}[${key}]` : key;
			if (value === null || value === undefined) {
				return;
			}
			if (Array.isArray(value)) {
				value.forEach(item => {
					if (item !== null && typeof item === 'object') {
						appendParams(target, item, `${name}[]`);
					} else {
						target.append(`${name}[]`, item);
					}
				});
			} else if (typeof value === 'object' && !(value instanceof Blob)) {
				appendParams(target, value, name);
			} else if (typeof value === 'boolean') {
				target.append(name, value ? '1' : '0');
			} else {
				target.append(name, value);
			}
		});
	}

	/**
	 * Parse a text body as JSON if it looks like JSON (many Dolibarr ajax pages print JSON without JSON content type)
	 * @param {string} text
	 * @returns {*} Parsed JSON, or the text itself
	 */
	function parseIfJson(text) {
		const trimmed = text.trim();
		if (trimmed.charAt(0) !== '{' && trimmed.charAt(0) !== '[') return text;
		try {
			return JSON.parse(trimmed);
		} catch (err) {
			return text;
		}
	}

	/**
	 * Check if a response body was built with PHP class JsonResponse (core/class/jsonResponse.class.php)
	 * @param {*} data Parsed response body
	 * @returns {boolean}
	 */
	function isJsonResponse(data) {
		return data !== null && typeof data === 'object' && !Array.isArray(data)
			&& 'result' in data && 'msg' in data && 'newToken' in data;
	}

	/**
	 * Find a readable error message in a response body
	 * @param {*} data Parsed response body
	 * @param {Response} response
	 * @returns {string}
	 */
	function getErrorMessage(data, response) {
		if (data && typeof data === 'object') {
			if (Array.isArray(data.errors) && data.errors.length > 0) return data.errors.join(', ');
			if (typeof data.error === 'string' && data.error !== '') return data.error;
			if (data.error && typeof data.error.message === 'string') return data.error.message;
			if (typeof data.msg === 'string' && data.msg !== '') return data.msg;
			if (typeof data.message === 'string' && data.message !== '') return data.message;
		}

		// Plain text error (not an html page)
		if (typeof data === 'string') {
			const text = data.trim();
			if (text !== '' && text.length < 500 && text.charAt(0) !== '<') return text;
		}

		if (response.ok) return 'Request failed';

		return `HTTP ${response.status} ${response.statusText}`.trim();
	}

	/**
	 * Send an ajax request to Dolibarr
	 *
	 * @param {string} url
	 * @param {Object} [options] Options, any other option is passed to native fetch()
	 * @param {string} [options.method='GET']
	 * @param {Object|FormData|URLSearchParams} [options.data] Parameters, sent in query string for GET, in body otherwise
	 * @param {*} [options.json] Body sent as JSON (token is then sent in query string)
	 * @param {Object} [options.headers]
	 * @param {boolean} [options.token] Add the anti CSRF token, default true except for GET and HEAD
	 * @param {string} [options.responseType='auto'] 'auto' (according to content type), 'json', 'text' or 'response' (native Response)
	 * @param {boolean} [options.showErrors=true] Display errors with Dolibarr.tools.setEventMessage()
	 * @param {number} [options.timeout=0] Abort after this delay in ms, 0 to wait without limit
	 * @param {boolean} [options.unwrap=false] For a JsonResponse, return only its data property
	 * @returns {Promise<*>} Parsed response body
	 * @throws {DolibarrFetchError}
	 */
	async function dolFetch(url, options = {}) {
		const {
			method = 'GET',
			data = null,
			json = undefined,
			headers = {},
			token = undefined,
			responseType = 'auto',
			showErrors = true,
			timeout = 0,
			unwrap = false,
			...fetchOptions
		} = options;

		const upperMethod = method.toUpperCase();
		const isReadMethod = upperMethod === 'GET' || upperMethod === 'HEAD';
		const addToken = token === undefined ? !isReadMethod : !!token;
		const csrfToken = Dolibarr.getContextVar('DOL_CSRF_TOKEN', '');
		const finalUrl = new URL(url, window.location.href);
		const init = { credentials: 'same-origin', ...fetchOptions, method: upperMethod, headers: { ...headers } };

		if (addToken && !csrfToken) {
			Dolibarr.log('tools: fetch: DOL_CSRF_TOKEN context var is missing');
		}

		if (isReadMethod) {
			appendParams(finalUrl.searchParams, data);
			if (addToken && csrfToken) finalUrl.searchParams.set('token', csrfToken);
		} else if (json !== undefined) {
			init.headers['Content-Type'] = 'application/json';
			init.body = JSON.stringify(json);
			if (addToken && csrfToken) finalUrl.searchParams.set('token', csrfToken);
		} else {
			const body = data instanceof FormData ? data : new URLSearchParams();
			if (body !== data) appendParams(body, data);
			if (addToken && csrfToken && !body.has('token')) body.set('token', csrfToken);
			init.body = body;
		}

		const fail = (error) => {
			if (showErrors && Dolibarr.checkToolExist('setEventMessage')) {
				Dolibarr.tools.setEventMessage(error.message, 'errors');
			}
			throw error;
		};

		// Timeout, combined with the signal given by caller if any
		let timer = null;
		let timedOut = false;
		if (timeout > 0) {
			const controller = new AbortController();
			if (init.signal) {
				init.signal.addEventListener('abort', () => controller.abort(init.signal.reason));
			}
			timer = setTimeout(() => {
				timedOut = true;
				controller.abort();
			}, timeout);
			init.signal = controller.signal;
		}

		let response;
		try {
			response = await fetch(finalUrl.toString(), init);
		} catch (err) {
			if (timedOut) return fail(new DolibarrFetchError(`Request timeout after ${timeout} ms`, 0));
			// Aborted by caller : no message displayed
			if (err && err.name === 'AbortError') throw new DolibarrFetchError('Request aborted', 0);
			return fail(new DolibarrFetchError(err && err.message ? err.message : 'Network error', 0));
		} finally {
			if (timer) clearTimeout(timer);
		}

		Dolibarr.log(`tools: fetch: ${upperMethod} ${finalUrl.pathname} -> ${response.status}`);

		if (responseType === 'response') {
			if (!response.ok) return fail(new DolibarrFetchError(`HTTP ${response.status} ${response.statusText}`.trim(), response.status, null, response));
			return response;
		}

		const contentType = response.headers.get('Content-Type') || '';
		const isJson = contentType.includes('json');
		let body;
		try {
			if (responseType === 'json' || (responseType === 'auto' && isJson)) {
				body = await response.json();
			} else {
				body = await response.text();
				if (responseType === 'auto') body = parseIfJson(body);
			}
		} catch (err) {
			// json expected but not received, ex: login page returned because the session has expired
			return fail(new DolibarrFetchError('Invalid response from server, your session may have expired', response.status, null, response));
		}

		// JsonResponse with result = 0 is an error, even if HTTP status could not be set (output already sent)
		const jsonResponse = isJsonResponse(body);
		if (!response.ok || (jsonResponse && !body.result)) {
			return fail(new DolibarrFetchError(getErrorMessage(body, response), response.status, body, response));
		}

		return (unwrap && jsonResponse) ? body.data : body;
	}

	dolFetch.Error = DolibarrFetchError;

	/**
	 * Shortcut for a GET request
	 * @param {string} url
	 * @param {Object} [data] Parameters sent in query string
	 * @param {Object} [options] See Dolibarr.tools.fetch
	 * @returns {Promise<*>}
	 */
	dolFetch.get = (url, data = null, options = {}) => dolFetch(url, { ...options, method: 'GET', data });

	/**
	 * Shortcut for a POST request
	 * @param {string} url
	 * @param {Object|FormData} [data] Parameters sent in body
	 * @param {Object} [options] See Dolibarr.tools.fetch
	 * @returns {Promise<*>}
	 */
	dolFetch.post = (url, data = null, options = {}) => dolFetch(url, { ...options, method: 'POST', data });

	Dolibarr.defineTool('fetch', dolFetch);
});
