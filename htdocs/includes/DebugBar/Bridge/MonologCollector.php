<?php
/*
 * This file is part of the DebugBar package.
 *
 * (c) 2013 Maxime Bouroumeau-Fuseau
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DebugBar\Bridge;

use DebugBar\DataCollector\DataCollectorInterface;
use DebugBar\DataCollector\MessagesAggregateInterface;
use DebugBar\DataCollector\Renderable;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

/**
 * A monolog handler as well as a data collector
 *
 * https://github.com/Seldaek/monolog
 *
 * <code>
 * $debugbar->addCollector(new MonologCollector($logger));
 * </code>
 */
class MonologCollector extends AbstractProcessingHandler implements DataCollectorInterface, Renderable, MessagesAggregateInterface
{
    protected $name;

    protected $records = array();

    /**
     * @param Logger $logger
     * @param int $level
     * @param boolean $bubble
     * @param string $name
     */
    public function __construct(Logger $logger = null, $level = Logger::DEBUG, $bubble = true, $name = 'monolog')
    {
        parent::__construct($level, $bubble);
        $this->name = $name;
        if ($logger !== null) {
            $this->addLogger($logger);
        }
    }

    /**
     * Adds logger which messages you want to log
     *
     * @param Logger $logger
     */
    public function addLogger(Logger $logger)
    {
        $logger->pushHandler($this);
    }

    protected function write(array $record)
    {
        $this->records[] = array(
            'message' => $record['formatted'],
            'is_string' => true,
            'label' => strtolower($record['level_name']),
            'time' => $record['datetime']->format('U')
        );
    }

    public function getMessages()
    {
        return $this->records;
    }

    public function collect()
    {
        return array(
            'count' => count($this->records),
            'records' => $this->records
        );
    }

    public function getName()
    {
        return $this->name;
    }

    public function getWidgets()
    {
        $name = $this->getName();
        return array(
            $name => array(
                "icon" => "suitcase",
                "widget" => "PhpDebugBar.Widgets.MessagesWidget",
                "map" => "$name.records",
                "default" => "[]"
            ),
            "$name:badge" => array(
                "map" => "$name.count",
                "default" => "null"
            )
        );
    }
}
