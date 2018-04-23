<?php

define("MOCK_MINIMUM_VERSION", "0.5.0");
define("MOCK_PORT", getenv("STRIPE_MOCK_PORT") ?: 12111);

// Send a request to stripe-mock
$ch = curl_init("http://localhost:" . MOCK_PORT . "/");
curl_setopt($ch, CURLOPT_HEADER, 1);
curl_setopt($ch, CURLOPT_NOBODY, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$resp = curl_exec($ch);

if (curl_errno($ch)) {
    echo "Couldn't reach stripe-mock at `localhost:" . MOCK_PORT . "`. Is " .
         "it running? Please see README for setup instructions.\n";
    exit(1);
}

// Retrieve the Stripe-Mock-Version header
$version = null;
$headers = explode("\n", $resp);
foreach ($headers as $header) {
    $pair = explode(":", $header, 2);
    if ($pair[0] == "Stripe-Mock-Version") {
        $version = trim($pair[1]);
    }
}

if ($version === null) {
    echo "Could not retrieve Stripe-Mock-Version header. Are you sure " .
         "that the server at `localhost:" . MOCK_PORT . "` is a stripe-mock " .
         "instance?";
    exit(1);
}

if (version_compare($version, MOCK_MINIMUM_VERSION) == -1) {
    echo "Your version of stripe-mock (" . $version . ") is too old. The minimum " .
         "version to run this test suite is " . MOCK_MINIMUM_VERSION . ". " .
         "Please see its repository for upgrade instructions.\n";
    exit(1);
}

require_once __DIR__ . '/TestCase.php';
