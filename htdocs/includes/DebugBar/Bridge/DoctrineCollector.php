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

use DebugBar\DataCollector\AssetProvider;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use DebugBar\DebugBarException;
use Doctrine\DBAL\Logging\DebugStack;
use Doctrine\ORM\EntityManager;

/**
 * Collects Doctrine queries
 *
 * http://doctrine-project.org
 *
 * Uses the DebugStack logger to collects data about queries
 *
 * <code>
 * $debugStack = new Doctrine\DBAL\Logging\DebugStack();
 * $entityManager->getConnection()->getConfiguration()->setSQLLogger($debugStack);
 * $debugbar->addCollector(new DoctrineCollector($debugStack));
 * </code>
 */
class DoctrineCollector extends DataCollector implements Renderable, AssetProvider
{
    protected $debugStack;

    public function __construct($debugStackOrEntityManager)
    {
        if ($debugStackOrEntityManager instanceof EntityManager) {
            $debugStackOrEntityManager = $debugStackOrEntityManager->getConnection()->getConfiguration()->getSQLLogger();
        }
        if (!($debugStackOrEntityManager instanceof DebugStack)) {
            throw new DebugBarException("'DoctrineCollector' requires an 'EntityManager' or 'DebugStack' object");
        }
        $this->debugStack = $debugStackOrEntityManager;
    }

    public function collect()
    {
        $queries = array();
        $totalExecTime = 0;
        foreach ($this->debugStack->queries as $q) {
            $queries[] = array(
                'sql' => $q['sql'],
                'params' => (object) $q['params'],
                'duration' => $q['executionMS'],
                'duration_str' => $this->formatDuration($q['executionMS'])
            );
            $totalExecTime += $q['executionMS'];
        }

        return array(
            'nb_statements' => count($queries),
            'accumulated_duration' => $totalExecTime,
            'accumulated_duration_str' => $this->formatDuration($totalExecTime),
            'statements' => $queries
        );
    }

    public function getName()
    {
        return 'doctrine';
    }

    public function getWidgets()
    {
        return array(
            "database" => array(
                "icon" => "arrow-right",
                "widget" => "PhpDebugBar.Widgets.SQLQueriesWidget",
                "map" => "doctrine",
                "default" => "[]"
            ),
            "database:badge" => array(
                "map" => "doctrine.nb_statements",
                "default" => 0
            )
        );
    }

    public function getAssets()
    {
        return array(
            'css' => 'widgets/sqlqueries/widget.css',
            'js' => 'widgets/sqlqueries/widget.js'
        );
    }
}
