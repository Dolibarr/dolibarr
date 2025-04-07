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
