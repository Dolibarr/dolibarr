#!/usr/bin/env php
<?php
\chdir(__DIR__);

\set_time_limit(0); // unlimited max execution time

$fp = \fopen(__DIR__ . '/data/ca-certificates.crt', 'w+b');

$options = [
	\CURLOPT_FILE => $fp,
	\CURLOPT_TIMEOUT => 3600,
	\CURLOPT_URL => 'https://curl.haxx.se/ca/cacert.pem',
];

$ch = \curl_init();
\curl_setopt_array($ch, $options);
\curl_exec($ch);
\curl_close($ch);
\fclose($fp);
