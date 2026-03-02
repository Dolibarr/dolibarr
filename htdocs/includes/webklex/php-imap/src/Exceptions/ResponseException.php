<?php
/*
* File:     ResponseException.php
* Category: Exception
* Author:   M. Goldenbaum
* Created:  19.01.17 22:21
* Updated:  -
*
* Description:
*  -
*/

namespace Webklex\PHPIMAP\Exceptions;

use \Exception;
use Webklex\PHPIMAP\Connection\Protocols\Response;

/**
 * Class ResponseException
 *
 * @package Webklex\PHPIMAP\Exceptions
 */
class ResponseException extends Exception {

    /**
     * Make a new ResponseException instance
     * @param Response $response
     * @param false|boolean $debug
     * @param Exception|null $exception
     *
     * @return ResponseException
     */
    public static function make(Response $response, bool $debug = false, ?Exception $exception = null): ResponseException {
        $message = "Command failed to process:\n";
        $message .= "Causes:\n";

        foreach($response->getErrors() as $error) {
            $message .= "\t- $error\n";
        }

        if(!$response->data()) {
            $message .= "\t- Empty response\n";
        }

        if ($debug) {
            $message .= self::debug_message($response);
        }

        foreach($response->getStack() as $_response) {
            $exception = self::make($_response, $debug, $exception);
        }

        return new self($message."Error occurred", 0, $exception);
    }

    /**
     * Generate a debug message containing all commands send and responses received
     * @param Response $response
     *
     * @return string
     */
    protected static function debug_message(Response $response): string {
        $commands = $response->getCommands();
        $message = "Commands send:\n";
        if ($commands) {
            foreach($commands as $command) {
                $message .= "\t".str_replace("\r\n", "\\r\\n", $command)."\n";
            }
        }else{
            $message .= "\tNo command send!\n";
        }

        $responses = $response->getResponse();
        $message .= "Responses received:\n";
        if ($responses) {
            foreach($responses as $_response) {
                if (is_array($_response)) {
                    foreach($_response as $value) {
                        $message .= "\t".str_replace("\r\n", "\\r\\n", "$value")."\n";
                    }
                }else{
                    $message .= "\t".str_replace("\r\n", "\\r\\n", "$_response")."\n";
                }
            }
        }else{
            $message .= "\tNo responses received!\n";
        }

        return $message;
    }
}
