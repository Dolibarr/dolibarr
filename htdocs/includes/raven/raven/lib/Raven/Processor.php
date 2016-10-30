<?php
/**
 * Base class for data processing.
 *
 * @package raven
 */
abstract class Raven_Processor
{
    public function __construct(Raven_Client $client)
    {
        $this->client = $client;
    }

    /**
     * Process and sanitize data, modifying the existing value if necessary.
     *
     * @param array $data   Array of log data
     */
    abstract public function process(&$data);
}
