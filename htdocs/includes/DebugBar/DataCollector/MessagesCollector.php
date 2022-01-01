<?php
/*
 * This file is part of the DebugBar package.
 *
 * (c) 2013 Maxime Bouroumeau-Fuseau
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DebugBar\DataCollector;

use Psr\Log\AbstractLogger;

/**
 * Provides a way to log messages
 */
class MessagesCollector extends AbstractLogger implements DataCollectorInterface, MessagesAggregateInterface, Renderable
{
    protected $name;

    protected $messages = array();

    protected $aggregates = array();

    protected $dataFormater;

    /**
     * @param string $name
     */
    public function __construct($name = 'messages')
    {
        $this->name = $name;
    }

    /**
     * Sets the data formater instance used by this collector
     *
     * @param DataFormatterInterface $formater
     */
    public function setDataFormatter(DataFormatterInterface $formater)
    {
        $this->dataFormater = $formater;
        return $this;
    }

    public function getDataFormatter()
    {
        if ($this->dataFormater === null) {
            $this->dataFormater = DataCollector::getDefaultDataFormatter();
        }
        return $this->dataFormater;
    }

    /**
     * Adds a message
     *
     * A message can be anything from an object to a string
     *
     * @param mixed $message
     * @param string $label
     */
    public function addMessage($message, $label = 'info', $isString = true)
    {
        if (!is_string($message)) {
            $message = $this->getDataFormatter()->formatVar($message);
            $isString = false;
        }
        $this->messages[] = array(
            'message' => $message,
            'is_string' => $isString,
            'label' => $label,
            'time' => microtime(true)
        );
    }

    /**
     * Aggregates messages from other collectors
     *
     * @param MessagesAggregateInterface $messages
     */
    public function aggregate(MessagesAggregateInterface $messages)
    {
        $this->aggregates[] = $messages;
    }

    public function getMessages()
    {
        $messages = $this->messages;
        foreach ($this->aggregates as $collector) {
            $msgs = array_map(function ($m) use ($collector) {
                $m['collector'] = $collector->getName();
                return $m;
            }, $collector->getMessages());
            $messages = array_merge($messages, $msgs);
        }

        // sort messages by their timestamp
        usort($messages, function ($a, $b) {
            if ($a['time'] === $b['time']) {
                return 0;
            }
            return $a['time'] < $b['time'] ? -1 : 1;
        });

        return $messages;
    }

    public function log($level, $message, array $context = array())
    {
        $this->addMessage($message, $level);
    }

    /**
     * Deletes all messages
     */
    public function clear()
    {
        $this->messages = array();
    }

    public function collect()
    {
        $messages = $this->getMessages();
        return array(
            'count' => count($messages),
            'messages' => $messages
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
            "$name" => array(
                'icon' => 'list-alt',
                "widget" => "PhpDebugBar.Widgets.MessagesWidget",
                "map" => "$name.messages",
                "default" => "[]"
            ),
            "$name:badge" => array(
                "map" => "$name.count",
                "default" => "null"
            )
        );
    }
}
