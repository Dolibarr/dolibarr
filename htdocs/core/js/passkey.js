/* Copyright (C) 2026		Guenter Lukas			<gl@gl.co.at>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * Library javascript for the passkey (WebAuthn) authentication mode.
 * - On the login page, an element with id "passkeylogin" enables the login with a passkey
 *   (autofill of the login field and button "Login with a passkey").
 * - On the user credentials page, an element with id "passkeyadd" enables the creation of a passkey.
 * The URL of the ajax component and the messages are provided with data-* attributes of these elements.
 */

(function () {
	'use strict';

	/**
	 * Convert a base64url string into an ArrayBuffer
	 *
	 * @param	{string}		value	Base64url string
	 * @return	{ArrayBuffer}			Binary data
	 */
	function base64UrlToBuffer(value) {
		var base64 = value.replace(/-/g, '+').replace(/_/g, '/');
		while (base64.length % 4) {
			base64 += '=';
		}
		var binary = window.atob(base64);
		var bytes = new Uint8Array(binary.length);
		for (var i = 0; i < binary.length; i++) {
			bytes[i] = binary.charCodeAt(i);
		}
		return bytes.buffer;
	}

	/**
	 * Convert an ArrayBuffer into a base64url string
	 *
	 * @param	{ArrayBuffer}	buffer	Binary data
	 * @return	{string}				Base64url string
	 */
	function bufferToBase64Url(buffer) {
		if (!buffer) {
			return '';
		}
		var bytes = new Uint8Array(buffer);
		var binary = '';
		for (var i = 0; i < bytes.length; i++) {
			binary += String.fromCharCode(bytes[i]);
		}
		return window.btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
	}

	/**
	 * Return the current anti-CSRF token of the page
	 *
	 * @return	{string}	Token
	 */
	function getToken() {
		var meta = document.querySelector('meta[name=anti-csrf-currenttoken]');
		return meta ? meta.getAttribute('content') : '';
	}

	/**
	 * Call the ajax component with POST and return its JSON answer
	 *
	 * @param	{string}	url		URL of the ajax component
	 * @param	{Object}	data	Parameters to post
	 * @return	{Promise<Object>}	JSON answer
	 */
	function post(url, data) {
		var params = new URLSearchParams();
		params.append('token', getToken());
		Object.keys(data).forEach(function (key) {
			params.append(key, data[key]);
		});
		return fetch(url, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {'Accept': 'application/json'},
			body: params
		}).then(function (response) {
			// An error page (for example a refused access) is not JSON: report the HTTP status instead of a JSON parse error
			var contenttype = response.headers.get('Content-Type') || '';
			if (!response.ok || contenttype.indexOf('application/json') === -1) {
				return response.text().then(function (text) {
					console.log('passkey: unexpected answer from ' + url + ': ' + text);
					throw new Error('HTTP ' + response.status + ' ' + text.split('\n')[0].substring(0, 200));
				});
			}
			return response.json();
		});
	}

	/**
	 * Return if the browser supports passkeys
	 *
	 * @return	{boolean}
	 */
	function isSupported() {
		return !!(window.PublicKeyCredential && navigator.credentials && window.fetch && window.isSecureContext);
	}

	/**
	 * Show an error message
	 *
	 * @param	{string}	message		Message
	 * @return	{void}
	 */
	function showError(message) {
		console.log('passkey error: ' + message);
		if (window.jQuery && jQuery.jnotify) {
			jQuery.jnotify(message, 'error', true);
		} else {
			window.alert(message);
		}
	}


	/*
	 * Login page
	 */

	var abortController = null;

	/**
	 * Start a passkey login
	 *
	 * @param	{HTMLElement}	element			Element with the data-* attributes
	 * @param	{boolean}		conditional		true to propose the passkeys into the autofill of the login field, false to open the dialog of the browser
	 * @return	{void}
	 */
	function startLogin(element, conditional) {
		var url = element.getAttribute('data-url');
		var form = document.getElementById('login');

		if (abortController) {
			abortController.abort();
		}
		var controller = new AbortController();
		abortController = controller;

		post(url, {action: 'getoptions'}).then(function (answer) {
			if (!answer.success) {
				var servererror = new Error(answer.error);
				servererror.name = 'PasskeyServerError';
				throw servererror;
			}
			var publicKey = answer.options.publicKey;
			publicKey.challenge = base64UrlToBuffer(publicKey.challenge);
			if (publicKey.allowCredentials) {
				publicKey.allowCredentials.forEach(function (credential) {
					credential.id = base64UrlToBuffer(credential.id);
				});
			}
			var request = {publicKey: publicKey, signal: controller.signal};
			if (conditional) {
				request.mediation = 'conditional';
			}
			return navigator.credentials.get(request);
		}).then(function (credential) {
			if (!credential) {
				return null;
			}
			var entity = form.querySelector('[name=entity]');
			return post(url, {
				action: 'verify',
				id: credential.id,
				clientDataJSON: bufferToBase64Url(credential.response.clientDataJSON),
				authenticatorData: bufferToBase64Url(credential.response.authenticatorData),
				signature: bufferToBase64Url(credential.response.signature),
				userHandle: bufferToBase64Url(credential.response.userHandle),
				entity: entity ? entity.value : ''
			}).then(function (answer) {
				// The form is submitted even if the check failed: the login page then shows the reason
				// and the failed login is recorded like for the other authentication modes.
				var input = document.createElement('input');
				input.type = 'hidden';
				input.name = 'afterpasskeyloginreturn';
				input.value = '1';
				form.appendChild(input);
				form.querySelector('[name=username]').value = (answer.success ? answer.login : '');
				var password = form.querySelector('[name=password]');
				if (password) {
					password.value = '';
				}
				form.submit();
			});
		}).catch(function (error) {
			// AbortError: a new request replaced this one. NotAllowedError: the user canceled the dialog of the browser,
			// or the browser refused the request (for example on a page with a TLS certificate error).
			if (error && (error.name === 'AbortError' || error.name === 'NotAllowedError')) {
				console.log('passkey login stopped: ' + error.name + ' ' + error.message);
				return;
			}
			console.log('passkey login error: ' + (error ? error.name + ' ' + error.message : ''));
			// The autofill of the login field is started without any action of the visitor: nothing is shown.
			// Otherwise only the message of the server is shown, the errors of the browser (for example
			// SecurityError on a wrong domain) are details of the setup that a visitor doesn't have to see.
			if (!conditional) {
				showError(element.getAttribute('data-msg-error') + (error && error.name === 'PasskeyServerError' && error.message ? ' ' + error.message : ''));
			}
		});
	}

	/**
	 * Init the passkey login on the login page
	 *
	 * @return	{void}
	 */
	function initLogin() {
		var element = document.getElementById('passkeylogin');
		if (!element) {
			return;
		}
		if (!isSupported()) {
			element.style.display = 'none';
			return;
		}

		element.addEventListener('click', function (event) {
			event.preventDefault();
			startLogin(element, false);
		});

		// Propose the passkeys into the autofill of the login field when the browser supports it
		if (PublicKeyCredential.isConditionalMediationAvailable) {
			PublicKeyCredential.isConditionalMediationAvailable().then(function (available) {
				if (available) {
					startLogin(element, true);
				}
			});
		}
	}


	/*
	 * Credentials page
	 */

	/**
	 * Init the button to add a passkey on the credentials page
	 *
	 * @return	{void}
	 */
	function initRegister() {
		var element = document.getElementById('passkeyadd');
		if (!element) {
			return;
		}
		if (!isSupported()) {
			element.disabled = true;
			element.title = element.getAttribute('data-msg-notsupported');
			return;
		}

		element.addEventListener('click', function (event) {
			event.preventDefault();
			var url = element.getAttribute('data-url');
			var labelinput = document.getElementById('passkeylabel');
			var rpid = '';
			element.disabled = true;

			post(url, {action: 'getoptions'}).then(function (answer) {
				if (!answer.success) {
					throw new Error(answer.error);
				}
				var publicKey = answer.options.publicKey;
				rpid = publicKey.rp && publicKey.rp.id ? publicKey.rp.id : '';
				publicKey.challenge = base64UrlToBuffer(publicKey.challenge);
				publicKey.user.id = base64UrlToBuffer(publicKey.user.id);
				if (publicKey.excludeCredentials) {
					publicKey.excludeCredentials.forEach(function (credential) {
						credential.id = base64UrlToBuffer(credential.id);
					});
				}
				return navigator.credentials.create({publicKey: publicKey});
			}).then(function (credential) {
				return post(url, {
					action: 'verify',
					clientDataJSON: bufferToBase64Url(credential.response.clientDataJSON),
					attestationObject: bufferToBase64Url(credential.response.attestationObject),
					label: labelinput ? labelinput.value : ''
				});
			}).then(function (answer) {
				if (!answer.success) {
					throw new Error(answer.error);
				}
				window.location.reload();
			}).catch(function (error) {
				element.disabled = false;
				if (error && error.name === 'NotAllowedError') {
					// Canceled by the user, but also refused by the browser (for example Chromium refuses passkeys
					// on a page with a TLS certificate error): the message of the browser gives the reason
					console.log('passkey creation canceled or refused: ' + error.message);
					showError(element.getAttribute('data-msg-notallowed'));
					return;
				}
				if (error && error.name === 'InvalidStateError') {
					// The authenticator already contains a passkey listed into excludeCredentials
					showError(element.getAttribute('data-msg-alreadyregistered'));
					return;
				}
				if (error && error.name === 'SecurityError') {
					console.log('passkey creation refused: ' + error.message);
					var host = window.location.hostname;
					if (rpid !== '' && host !== rpid && host.slice(-(rpid.length + 1)) !== '.' + rpid) {
						// The page is not opened with the URL of the application, so the browser refuses the domain of the passkey
						showError(element.getAttribute('data-msg-error') + ' ' + element.getAttribute('data-msg-baddomain'));
					} else {
						// The domain is right: Firefox (since version 140) refuses passkeys on a page with a TLS certificate error
						showError(element.getAttribute('data-msg-insecure'));
					}
					return;
				}
				showError(element.getAttribute('data-msg-error') + ' ' + (error && error.message ? error.message : ''));
			});
		});
	}


	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () {
			initLogin();
			initRegister();
		});
	} else {
		initLogin();
		initRegister();
	}
})();
