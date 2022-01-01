<?php
/*
 * This file is part of the DebugBar package.
 *
 * (c) 2013 Maxime Bouroumeau-Fuseau
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DebugBar\Storage;

use PDO;

/**
 * Stores collected data into a database using PDO
 */
class PdoStorage implements StorageInterface
{
    protected $pdo;

    protected $tableName;

    protected $sqlQueries = array(
        'save' => "INSERT INTO %tablename% (id, data, meta_utime, meta_datetime, meta_uri, meta_ip, meta_method) VALUES (?, ?, ?, ?, ?, ?, ?)",
        'get' => "SELECT data FROM %tablename% WHERE id = ?",
        'find' => "SELECT data FROM %tablename% %where% LIMIT %limit% OFFSET %offset%",
        'clear' => "DELETE FROM %tablename%"
    );

    /**
     * @param string $dirname Directories where to store files
     * @param array $sqlQueries
     */
    public function __construct(PDO $pdo, $tableName = 'phpdebugbar', array $sqlQueries = array())
    {
        $this->pdo = $pdo;
        $this->tableName = $tableName;
        $this->setSqlQueries($sqlQueries);
    }

    /**
     * Sets the sql queries to be used
     *
     * @param array $queries
     */
    public function setSqlQueries(array $queries)
    {
        $this->sqlQueries = array_merge($this->sqlQueries, $queries);
    }

    public function save($id, $data)
    {
        $sql = $this->getSqlQuery('save');
        $stmt = $this->pdo->prepare($sql);
        $meta = $data['__meta'];
        $stmt->execute(array($id, serialize($data), $meta['utime'], $meta['datetime'], $meta['uri'], $meta['ip'], $meta['method']));
    }

    public function get($id)
    {
        $sql = $this->getSqlQuery('get');
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array($id));
        if (($data = $stmt->fetchColumn(0)) !== false) {
            return unserialize($data);
        }
        return null;
    }

    public function find(array $filters = array(), $max = 20, $offset = 0)
    {
        $where = array();
        $params = array();
        foreach ($filters as $key => $value) {
            $where[] = "meta_$key = ?";
            $params[] = $value;
        }
        if (count($where)) {
            $where = " WHERE " . implode(' AND ', $where);
        } else {
            $where = '';
        }

        $sql = $this->getSqlQuery('find', array(
            'where' => $where,
            'offset' => $offset,
            'limit' => $max
        ));

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $results = array();
        foreach ($stmt->fetchAll() as $row) {
            $data = unserialize($row['data']);
            $results[] = $data['__meta'];
            unset($data);
        }
        return $results;
    }

    public function clear()
    {
        $this->pdo->exec($this->getSqlQuery('clear'));
    }

    protected function getSqlQuery($name, array $vars = array())
    {
        $sql = $this->sqlQueries[$name];
        $vars = array_merge(array('tablename' => $this->tableName), $vars);
        foreach ($vars as $k => $v) {
            $sql = str_replace("%$k%", $v, $sql);
        }
        return $sql;
    }
}
