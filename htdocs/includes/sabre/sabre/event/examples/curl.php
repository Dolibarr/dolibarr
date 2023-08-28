#!/usr/bin/env php
<?php declare (strict_types=1);

/**
 * The following example demonstrates doing asynchronous HTTP requests with
 * curl_multi.
 *
 * We use the event loop to occasionally see if there were any updates.
 * This is very rudimentary, but demonstrates the basis of something someone
 * might be able to use to create a better async http client.
 *
 * @copyright Copyright (C) 2007-2015 fruux GmbH. (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
use Sabre\Event\Loop;
use Sabre\Event\Promise;

require __DIR__ . '/../vendor/autoload.php';

// create both cURL resources
$ch1 = curl_init();
$ch2 = curl_init();

// set URL. The httpbin.org test url will wait 5 seconds to respond.
curl_setopt_array($ch1, CURLOPT_URL, "http://httpbin.org/delay/5");
curl_setopt_array($ch2, CURLOPT_URL, "http://httpbin.org/delay/5");

//create the multiple cURL handle
$mh = curl_multi_init();

//add the two handles
curl_multi_add_handle($mh, $ch1);
curl_multi_add_handle($mh, $ch2);



/**
 * This function takes a curl handle as its argument, and returns a Promise.
 *
 * When the request is finished the Promise will resolve. Any curl errors will
 * cause the Promise to reject.
 *
 * @param resource $curlHandle
 * @return Promise
 */
function curl_async_promise($curlHandle) {


}

/**
 * This class is used by curl_async_promise. It should generally only get
 * constructed once.
 *
 * @copyright Copyright (C) 2007-2015 fruux GmbH. (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class CurlScheduler {

    protected $curlMultiHandle;

    function __construct() {

        $this->curlMultiHandle = curl_multi_init();

    }

    function addHandle($curlHandle) {

        curl_multi_add_handle($mh, $curlHandle);

    }

}

$active = null;

function curl_multi_loop_scheduler($mh, callable $done) {

    $mrc = curl_multi_exec($mh, $active);
    switch ($mrc) {

        /**
         * From the curl docs. If CURM_CALL_MULTI_PERFORM is returned, simply
         * call curl_multi_perform immediately again. In PHP this means we
         * actually call curl_multi_exec immediately again.
         *
         * We're doing this in the next tick.
         */
        case CURLM_CALL_MULTI_PERFORM :
            Loop\nextTick(function() use ($mh, $done) {

                curl_multi_loop_scheduler($mh, $done);

            });
            break;
        case CURLM_OK :
            if (!$active) {
                // We're done!
                $done();
                return;
            }
            // Check again after 0.02 seconds
            Loop\setTimeout(function() use ($mh, $done) {

                curl_multi_loop_scheduler($mh, $done);

            }, 0.02);
            break;

        default :
            throw Exception('Curl error: ' . curl_multi_strerror($mrc));

    }

}

curl_multi_loop_scheduler($mh, function() {

    echo "Success!\n";

});

Loop\run();

//close the handles
curl_multi_remove_handle($mh, $ch1);
curl_multi_remove_handle($mh, $ch2);
curl_multi_close($mh);
