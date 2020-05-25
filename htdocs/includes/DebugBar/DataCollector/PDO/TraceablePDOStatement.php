<?php

namespace DebugBar\DataCollector\PDO;

use PDO;
use PDOException;
use PDOStatement;

/**
 * A traceable PDO statement to use with Traceablepdo
 */
class TraceablePDOStatement extends PDOStatement
{
    protected $pdo;

    protected $boundParameters = array();

    protected function __construct(TraceablePDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function bindColumn($column, &$param, $type = null, $maxlen = null, $driverdata = null)
    {
        $this->boundParameters[$column] = $param;
        $args = array_merge(array($column, &$param), array_slice(func_get_args(), 2));
        return call_user_func_array(array("parent", 'bindColumn'), $args);
    }

    public function bindParam($param, &$var, $data_type = PDO::PARAM_STR, $length = null, $driver_options = null)
    {
        $this->boundParameters[$param] = $var;
        $args = array_merge(array($param, &$var), array_slice(func_get_args(), 2));
        return call_user_func_array(array("parent", 'bindParam'), $args);
    }

    public function bindValue($param, $value, $data_type = PDO::PARAM_STR)
    {
        $this->boundParameters[$param] = $value;
        return call_user_func_array(array("parent", 'bindValue'), func_get_args());
    }

    public function execute($params = null)
    {
        $preparedId = spl_object_hash($this);
        $boundParameters = $this->boundParameters;
        if (is_array($params)) {
            $boundParameters = array_merge($boundParameters, $params);
        }

        $trace = new TracedStatement($this->queryString, $boundParameters, $preparedId);
        $trace->start();

        $ex = null;
        try {
            $result = parent::execute($params);
        } catch (PDOException $e) {
            $ex = $e;
        }

        if ($this->pdo->getAttribute(PDO::ATTR_ERRMODE) !== PDO::ERRMODE_EXCEPTION && $result === false) {
            $error = $this->errorInfo();
            $ex = new PDOException($error[2], $error[0]);
        }

        $trace->end($ex, $this->rowCount());
        $this->pdo->addExecutedStatement($trace);

        if ($this->pdo->getAttribute(PDO::ATTR_ERRMODE) === PDO::ERRMODE_EXCEPTION && $ex !== null) {
            throw $ex;
        }
        return $result;
    }
}
