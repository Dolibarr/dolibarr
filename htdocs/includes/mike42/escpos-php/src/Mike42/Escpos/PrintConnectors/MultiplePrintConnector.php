<?php
namespace Mike42\Escpos\PrintConnectors;

/**
 * Wrap multiple connectors up, to print to several printers at the same time.
 */
class MultiplePrintConnector implements PrintConnector
{
    private $connectors;

    public function __construct(PrintConnector ...$connectors)
    {
        $this -> connectors = $connectors;
    }

    public function finalize()
    {
        foreach ($this -> connectors as $connector) {
            $connector -> finalize();
        }
    }

    public function read($len)
    {
        // Cannot write
        return false;
    }

    public function write($data)
    {
        foreach ($this -> connectors as $connector) {
            $connector -> write($data);
        }
    }

    public function __destruct()
    {
        // Do nothing
    }
}
