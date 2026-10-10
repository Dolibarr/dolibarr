<?php

/**
 * @var Documentation $documentation
 *
 * @phan-var Documentation $documentation
 */

if (!defined('DOL_VERSION')) {die();}

global $documentation;

if ($documentation === null || !($documentation instanceof Documentation)) { return; }


?>

<div class="documentation-section">
	<h2 id="titlesection-tool-fetch" class="documentation-title">Fetch tool</h2>

	<p>
		<code>Dolibarr.tools.fetch()</code> sends ajax requests to Dolibarr pages and handles what every module has to do otherwise:
	</p>
	<ul>
		<li>The anti CSRF token is added automatically for POST, PUT, PATCH and DELETE requests (option <code>token: true</code> to add it on a GET request).</li>
		<li>Parameters are sent the way PHP reads them: arrays as <code>key[]</code>, objects as <code>key[sub]</code>, booleans as <code>1</code> / <code>0</code>.</li>
		<li>The response is parsed according to its content type (JSON or text), or forced with option <code>responseType</code>. A text response that looks like JSON is parsed as JSON, because many Dolibarr ajax pages print JSON without JSON content type.</li>
		<li>Responses built with the PHP class <code>JsonResponse</code> are recognized, see below.</li>
		<li>Errors (HTTP error, network error, timeout, invalid JSON) are displayed with <code>Dolibarr.tools.setEventMessage()</code> and thrown as <code>Dolibarr.tools.fetch.Error</code> with <code>status</code> and <code>data</code> (parsed response body).</li>
	</ul>
	<p>
		<b>Note:</b> the called page must define <code>NOTOKENRENEWAL</code>, otherwise each request renews the session token and the forms of the current page are refused.
		Also note that when the token is invalid, Dolibarr does not return an HTTP error but ignores the POST parameters: your page should check its input and return an explicit error.
	</p>

	<h3>Options</h3>
	<ul>
		<li><code>method</code>: <code>'GET'</code> by default.</li>
		<li><code>data</code>: object, <code>FormData</code> or <code>URLSearchParams</code>. Sent in query string for GET, in body otherwise.</li>
		<li><code>json</code>: body sent as JSON, the token is then sent in query string.</li>
		<li><code>token</code>: add the anti CSRF token, <code>true</code> by default except for GET and HEAD.</li>
		<li><code>responseType</code>: <code>'auto'</code> (default), <code>'json'</code>, <code>'text'</code> or <code>'response'</code> (native Response).
			Use <code>'json'</code> when you expect JSON: if the session has expired, Dolibarr returns the login page and you get a clear error.</li>
		<li><code>showErrors</code>: display errors with <code>setEventMessage</code>, <code>true</code> by default.</li>
		<li><code>timeout</code>: abort after this delay in ms, <code>0</code> (default) to wait without limit.</li>
		<li><code>unwrap</code>: for a <code>JsonResponse</code>, return only its <code>data</code> property, <code>false</code> by default.</li>
		<li>Any other option (<code>signal</code>, <code>cache</code>...) is passed to the native <code>fetch()</code>.</li>
	</ul>

	<?php
	$lines = array(
		'<script nonce="<?php print getNonce() ?>" >',
		'	document.addEventListener(\'Dolibarr:Ready\', async function(e) {',
		'		const url = Dolibarr.getContextVar(\'DOL_URL_ROOT\') + \'/custom/mymodule/ajax/myobject.php\';',
		'',
		'		// GET request, parameters in query string',
		'		const myObject = await Dolibarr.tools.fetch.get(url, { id: 12 }, { responseType: \'json\' });',
		'',
		'		// POST request, token added automatically',
		'		try {',
		'			const result = await Dolibarr.tools.fetch.post(url, { action: \'setField\', id: 12, field: \'note_public\', value: \'Hello\' });',
		'			Dolibarr.tools.setEventMessage(\'Saved\');',
		'		} catch (err) {',
		'			// Error already displayed, use err.status and err.data for specific cases',
		'			if (err.status === 409) {',
		'				// Object modified by someone else',
		'			}',
		'		}',
		'',
		'		// Full syntax',
		'		await Dolibarr.tools.fetch(url, { method: \'PUT\', json: { note_public: \'Hello\' }, timeout: 10000, showErrors: false });',
		'	});',
		'</script>',
	);
	$documentation->showCode($lines, 'php'); ?>

	<h3>Server side: JsonResponse</h3>
	<p>
		For the pages called with <code>Dolibarr.tools.fetch()</code>, use the class <code>JsonResponse</code> (<code>core/class/jsonResponse.class.php</code>).
		Its response <code>{result, msg, newToken, data, debug}</code> is recognized by the fetch tool:
	</p>
	<ul>
		<li><code>result</code> to <code>0</code> is an error, even if the HTTP status could not be set to 400 because something was already printed.</li>
		<li><code>msg</code> is used as error message.</li>
		<li>On success, the whole response is returned, or only <code>data</code> with option <code>unwrap: true</code>. A success <code>msg</code> is not displayed automatically.</li>
		<li><code>newToken</code> is not used: with <code>NOTOKENRENEWAL</code> the token to use stays the one of the <code>DOL_CSRF_TOKEN</code> context var.</li>
	</ul>
	<?php
	$lines = array(
		'<?php',
		'if (!defined(\'NOTOKENRENEWAL\')) define(\'NOTOKENRENEWAL\', \'1\');',
		'if (!defined(\'NOREQUIREMENU\')) define(\'NOREQUIREMENU\', \'1\');',
		'if (!defined(\'NOREQUIREHTML\')) define(\'NOREQUIREHTML\', \'1\');',
		'if (!defined(\'NOREDIRECTBYMAINTOLOGIN\')) define(\'NOREDIRECTBYMAINTOLOGIN\', \'1\');',
		'',
		'require \'../../main.inc.php\';',
		'require_once DOL_DOCUMENT_ROOT.\'/core/class/jsonResponse.class.php\';',
		'',
		'top_httphead(\'application/json\');',
		'',
		'$jsonResponse = new JsonResponse();',
		'',
		'if (!$user->hasRight(\'mymodule\', \'myobject\', \'write\')) {',
		'	$jsonResponse->msg = $langs->trans(\'NotEnoughPermissions\');',
		'	print $jsonResponse->getResponse(); // result = 0 : HTTP 400',
		'	exit;',
		'}',
		'',
		'// ... do your stuff',
		'$jsonResponse->result = 1;',
		'$jsonResponse->data = [\'id\' => $object->id, \'ref\' => $object->ref];',
		'print $jsonResponse->getResponse();',
	);
	$documentation->showCode($lines, 'php'); ?>

	<?php
	$lines = array(
		'<script nonce="<?php print getNonce() ?>" >',
		'	// Returns {id, ref}, or throws with the msg of the response if result = 0',
		'	const myObject = await Dolibarr.tools.fetch.post(url, { action: \'update\', id: 12 }, { unwrap: true });',
		'</script>',
	);
	$documentation->showCode($lines, 'php'); ?>

	<div class="documentation-example">
		<script nonce="<?php print getNonce() ?>"  >
			document.addEventListener('Dolibarr:Ready', function(e) {
				const root = Dolibarr.getContextVar('DOL_URL_ROOT');

				document.getElementById('fetch-try-success').addEventListener('click', async function(e) {
					const data = await Dolibarr.tools.fetch.get(root + '/public/langs/langs-tool-interface.php', { domain: 'main' }, { responseType: 'json' });
					console.log('Dolibarr.tools.fetch result', data);
					Dolibarr.tools.setEventMessage(Object.keys(data[Object.keys(data)[0]]).length + ' translations loaded, see console');
				});

				document.getElementById('fetch-try-error').addEventListener('click', async function(e) {
					try {
						await Dolibarr.tools.fetch.get(root + '/public/langs/page-that-does-not-exist.php');
					} catch (err) {
						console.log('Dolibarr.tools.fetch error', err.status, err.message);
					}
				});
			});
		</script>
		<button id="fetch-try-success" class="button">Fetch main translations</button>
		<button id="fetch-try-error" class="button">Fetch a page that does not exist</button>
	</div>

</div>
