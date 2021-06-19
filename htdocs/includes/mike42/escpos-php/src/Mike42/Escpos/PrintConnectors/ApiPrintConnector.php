<?php

namespace Mike42\Escpos\PrintConnectors;

use Guzzle\Http\Client;
use Guzzle\Http\Message\Request;
use Guzzle\Http\Message\Response;
use Exception;

class ApiPrintConnector implements PrintConnector
{
    /**
     * @var string
     */
    protected $stream;
    /**
     * @var Client
     */
    protected $httpClient;

    /**
     * @var string
     */
    protected $printerId;
    /**
     * @var string
     */
    protected $apiToken;

    /**
    * Construct new connector
    *
    * @param string $host
    * @param string $printerId
    * @param string $apiToken
    */
    public function __construct($host, $printerId, $apiToken)
    {
        $this->httpClient = new Client(['base_uri' => $host]);
        $this->printerId = $printerId;
        $this->apiToken = $apiToken;

        $this->stream = '';
    }

    /**
     * Print connectors should cause a NOTICE if they are deconstructed
     * when they have not been finalized.
     */
    public function __destruct()
    {
        if (! empty($this->stream)) {
            trigger_error("Print connector was not finalized. Did you forget to close the printer?", E_USER_NOTICE);
        }
    }

    /**
     * Finish using this print connector (close file, socket, send
     * accumulated output, etc).
     */
    public function finalize()
    {
        /** @var Request $request */
        $request = $this->httpClient->post(
            'printers/'.$this->printerId.'/print?api_token='.$this->apiToken,
            null,
            $this->stream
        );

        /** @var Response $response */
        $response = $request->send();

        if (! $response->isSuccessful()) {
            throw new Exception(
                sprintf('Failed to print. API returned "%s: %s"', $response->getStatusCode(), $response->getReasonPhrase())
            );
        }

        $this->stream = '';
    }

    /**
     * Read data from the printer.
     *
     * @param string $len Length of data to read.
     * @return string Data read from the printer.
     */
    public function read($len)
    {
        return $this->stream;
    }

    /**
     * Write data to the print connector.
     *
     * @param string $data The data to write
     */
    public function write($data)
    {
        $this->stream .= $data;
    }
}
