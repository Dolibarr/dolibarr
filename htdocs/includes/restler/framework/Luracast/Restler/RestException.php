<?php
namespace Luracast\Restler;

use Exception;

/**
 * Special Exception for raising API errors
 * that can be used in API methods
 *
 * @category   Framework
 * @package    Restler
 * @subpackage exception
 * @author     R.Arul Kumaran <arul@luracast.com>
 * @copyright  2010 Luracast
 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link       http://luracast.com/products/restler/
 * @version    3.0.0rc6
 */

class RestException extends Exception
{
    /**
     * HTTP status codes
     *
     * @var array
     */
    public static $codes = array(
        100 => 'Continue',
        101 => 'Switching Protocols',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => '(Unused)',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        429 => 'Too Many Requests', //still in draft but used for rate limiting
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported'
    );
    private $details;
    private $stage;

    /**
     * @param string      $httpStatusCode http status code
     * @param string|null $errorMessage   error message
     * @param array       $details        any extra detail about the exception
     * @param Exception   $previous       previous exception if any
     */
    public function __construct($httpStatusCode, $errorMessage = null, array $details = array(), Exception $previous = null)
    {
        $events = Scope::get('Restler')->getEvents();
        if(count($events)<= 1){
            $this->stage = 'setup';
        } else {
            $this->stage = $previous ? $events[count($events)-2] : end($events);
        }
        $this->details = $details;
        parent::__construct($errorMessage, $httpStatusCode, $previous);
    }

    /**
     * Get extra details about the exception
     *
     * @return array details array
     */
    public function getDetails()
    {
        return $this->details;
    }

    public function getStage()
    {
        return $this->stage;
    }

    public function getStages()
    {
        $e = Scope::get('Restler')->getEvents();
        $i = array_search($this->stage, $e);
        return array(
            'success' => array_slice($e, 0, $i),
            'failure' => array_slice($e, $i),
        );
    }

    public function getErrorMessage()
    {
        $statusCode = $this->getCode();
        $message = $this->getMessage();
        if (isset(RestException::$codes[$statusCode])) {
            $message = RestException::$codes[$statusCode] .
                (empty($message) ? '' : ': ' . $message);
        }
        return $message;
    }

    public function getSource()
    {
        $e = $this;
        while ($e->getPrevious()) {
            $e = $e->getPrevious();
        }
        return basename($e->getFile()) . ':'
        . $e->getLine() . ' at '
        . $this->getStage() . ' stage';
    }
}

