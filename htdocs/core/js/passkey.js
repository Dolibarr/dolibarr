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
 * - On the page of the passkeys of a user (user/passkeys.php), an element with id "passkeyadd" enables the creation of a passkey.
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
	 * Page of the passkeys of a user
	 */

	/**
	 * Init the button to add a passkey on the page of the passkeys of a user
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
			initRegister();
		});
	} else {
		initRegister();
	}
})();
