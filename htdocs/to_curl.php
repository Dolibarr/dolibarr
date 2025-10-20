<?php
/* Copyright (C) 2025		MDW	<mdeweerd@users.noreply.github.com>
 */
/**
 * @param string $string String to be used as bash parameter
 *
 * @return string
 */
function _escapeForBash($string)
{
	$specialChars = [
		"'" => "'\\''",
		"\r" => '\r',
		"\n" => '\n'
	];

	return strtr($string, $specialChars);
}

/**
 * Generate curl command from request
 *
 * @param bool $removeForwardedFor When true, reconstruct original request
 *
 * @return string curl command
 */
function generateCurlCommand($removeForwardedFor = false)
{
	$excludeHeaders = [
		'X-Forwarded-For',
		'X-Forwarded-Host',
		'X-Host',
		'X-Forwarded-Proto',
		'Connection',
		'Host',
		// Add more forwarding-related headers if needed
	];

	$curlCommand = 'curl -X ';
	$method = $_SERVER['REQUEST_METHOD'];

	// Set the request method
	$curlCommand .= strtoupper($method) . ' ';

	// Get URL

	// Handle HTTPS if X-Forwarded-Proto is present
	if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
		$url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	} else {
		$url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	}

	$curlCommand .= "'" . $url . "' ";

	$data = file_get_contents('php://input');
	;
	if (!empty($data)) {
		$curlCommand .= "-d '" . _escapeForBash($data) . "' ";
	}


	// Handle GET requests
	if ($method === 'GET') {
		// Get GET data
		$getData = http_build_query($_GET);
		if (!empty($getData)) {
			$getData = _escapeForBash($getData);
			$curlCommand .= "-G --data-urlencode '" . $getData . "' ";
		}
	}

	// Get headers
	$headers = [];
	$userAgentHeaderAdded = false;

	foreach ($_SERVER as $name => $value) {
		if (substr($name, 0, 5) === 'HTTP_') {
			$headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));

			if (in_array($headerName, $excludeHeaders)) {
				continue;
			}

			$headerValue = _escapeForBash($value);
			$headers[] = $headerName . ': ' . $headerValue;

			if ($headerName === 'User-Agent') {
				$userAgentHeaderAdded = true;
			}
		}
	}

	if (!$userAgentHeaderAdded) {
		// Add an empty User-Agent header
		$headers[] = 'User-Agent:';
	}

	foreach ($headers as $header) {
		$curlCommand .= "-H '" . _escapeForBash($header) . "' ";
	}

	return $curlCommand;
}


/**
 * Log current request as a curl command to the debug log
 *
 * Helps with repeating a request for debugging or logging
 * a request to share it for support.
 *
 * @param int	$level	The dolibarr log level for the report
 * @return void
 */
function dol_logRequestAsCurl($level = LOG_DEBUG)
{
	dol_syslog(generateCurlCommand(), $level);
}


/**
 * Generate Hurl test entry with dynamic {{hostnport}} and real HTTP status
 *
 * @param ?string	$comment	Optional comment for the Hurl entry
 * @param string[]	$asserts	Optional assertions (e.g. ["jsonpath '$.data' exists"])
 * @param ?string	$output		Optional output to include in the response body
 * @return string	Hurl test entry
 */
function generateHurlEntry($comment = null, $asserts = [], $output = null)
{
	$excludeHeaders = [
		'X-Forwarded-For', 'X-Forwarded-Host', 'X-Host',
		'X-Forwarded-Proto', 'Connection', 'Host',
		'User-Agent', 'Dolapikey'
	];
	$hurlEntry = '';

	if ($comment !== null) {
		$hurlEntry .= "# " . $comment . "\n";
	}

	$method = $_SERVER['REQUEST_METHOD'];
	$uri = $_SERVER['REQUEST_URI'];
	$scheme = (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? 'https' : 'http';
	$hurlEntry .= strtoupper($method) . " {$scheme}://{{hostnport}}{$uri}\n";

	$userAgentHeaderAdded = false;
	foreach ($_SERVER as $name => $value) {
		if (substr($name, 0, 5) === 'HTTP_') {
			$headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
			if (in_array($headerName, $excludeHeaders)) {
				continue;
			}
			$hurlEntry .= "{$headerName}: {$value}\n";
		}
	}

	$data = file_get_contents('php://input');
	if (!empty($data) && $method !== 'GET') {
		$hurlEntry .= "\n" . $data . "\n";
	}

	// Expected HTTP result code
	$hurlEntry .= "HTTP " . http_response_code() . "\n";
	if ($output !== null && trim($output) !== '') {
		$hurlEntry .= "\n" . $output . "\n";
	}


	if (!empty($asserts)) {
		$hurlEntry .= "[Asserts]\n";
		foreach ($asserts as $assert) {
			$hurlEntry .= "{$assert}\n";
		}
	}

	return $hurlEntry;
}

/**
 * Log current request as a Hurl test entry
 *
 * @param int		$level   The log level for the report
 * @param ?string	$comment Optional comment
 * @param string[]	$asserts Optional assertions
 * @param ?string	$output  Optional output to include in the response body
 * @return void
 */
function dol_logRequestAsHurl($level = LOG_DEBUG, $comment = null, $asserts = [], $output = null)
{
	dol_syslog(generateHurlEntry($comment, $asserts, $output), $level);
}

/**
 * Register a shutdown function to log the request as Hurl at the end of script execution
 *
 * Example:
 * ```php
 * // Add to the start of the php script to log at the end
 * dol_registerHurlShutdownLog(
 *     LOG_DEBUG,
 *     "GET mass mailings",
 *     [
 *         "jsonpath '$.data' exists",
 *         "jsonpath '$.pagination.total' >= 0",
 *     ],
 *     true // Inclure le buffer de sortie
 * );
 * ```
 *
 * @param int		$level			The dolibarr log level for the report
 * @param ?string	$comment		Optional comment for the Hurl entry
 * @param string[]	$asserts		Optional assertions
 * @param bool		$includeOutput	Whether to include the output buffer in the response body
 * @return void
 */
function dol_registerHurlShutdownLog($level = LOG_DEBUG, $comment = null, $asserts = [], $includeOutput = false)
{
	$shutdownLevel = $level;
	$shutdownComment = $comment;
	$shutdownAsserts = $asserts;
	$shutdownIncludeOutput = $includeOutput;

	if ($shutdownIncludeOutput) {
		ob_start();
	}

	register_shutdown_function(function () use ($shutdownLevel, $shutdownComment, $shutdownAsserts, $shutdownIncludeOutput) {
		$output = null;
		if ($shutdownIncludeOutput && ob_get_level() > 0) {
			$output = ob_get_clean();
		}
		dol_logRequestAsHurl($shutdownLevel, $shutdownComment, $shutdownAsserts, $output);
	});
}
