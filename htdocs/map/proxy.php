<?php
// the id for the Yelp app
$apiUrl = "http://maps.googleapis.com/maps/api/geocode/json?".$_SERVER['QUERY_STRING'];

// setup the cURL call
$c = curl_init();
curl_setopt($c, CURLOPT_URL, $apiUrl);
curl_setopt($c, CURLOPT_HEADER, false);

// make the call
$content = curl_exec($c);
curl_close($c);
?>

