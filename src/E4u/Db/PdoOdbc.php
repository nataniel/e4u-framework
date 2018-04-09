<?php
namespace E4u\Db;

use bar\baz\source_with_namespace;

class PdoOdbc
{
    /**
     * @var \PDO
     */
    private $_connection;

    /**
     * @var bool
     */
    protected $dump_sql = false;

    /**
     * @var \PDOStatement
     */
    protected $result;

    public function __construct($options)
    {
        try {
            $this->_connection = new \PDO('odbc:' . $options['dsn'], $options['user'], $options['password']);
        } catch (PDOException  $e) {
            throw new Exception\ConnectionFailed($e->getMessage(), 0, $e);
        }

        if (isset($options['dump_sql'])) {
            $this->dumpSQL($options['dump_sql']);
        }
    }

    /**
     * @param  bool $flag
     * @return $this
     */
    public function dumpSQL($flag = true)
    {
        $this->dump_sql = (bool)$flag;
        return $this;
    }

    /**
     * @param  string $query
     * @return $this
     */
    public function prepare($query)
    {
        $this->result = $this->_connection->prepare($query);
        return $this;
    }

    /**
     * @param  string $query
     * @return $this
     * @throws Exception\QueryFailed
     */
    public function execute($query, $params = [])
    {
        if ($this->dump_sql) {
            var_dump($query);
            var_dump($params);
        }

        $this->prepare($query);
        $this->result->execute($params);
        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->result->fetchAll();
    }

    /**
     * @return array
     */
    public function select($query, $params = [])
    {
        return $this->execute($query, $params)->toArray();
    }

    /**
     * @param  string $query
     * @param  array $params
     * @param  string $column
     * @return array
     */
    public function selectColumn($query, $params = [], $column = null)
    {
        $result = $this->select($query, $params);
        $values = [];
        foreach ($result as $row) {
            $values[] = $column ? $row[ $column ] : array_values($row)[0];
        }

        return $values;
    }

    /**
     * @return array
     */
    public function selectRow($query, $params = [])
    {
        $result = $this->select($query, $params);
        return !empty($result) ? $result[0] : null;
    }

    /**
     * @return mixed
     */
    public function selectValue($query, $params = [])
    {
        $result = $this->selectRow($query, $params);
        return !empty($result) ? reset($result) : null;
    }

    /**
     * PDO::quote() is not supported by all drivers
     *
     * @param  mixed $value
     * @param  int $type
     * @return string
     */
    public function quote($value, $type = \PDO::PARAM_STR)
    {
        switch ($type) {
            case \PDO::PARAM_INT: return (int)$value;
            case \PDO::PARAM_STR: return "'" . str_replace("'", "''", (string)$value) . "'";
            default: return $value;
        }
    }

    /**
     * @return int
     */
    public function affectedRows()
    {
        return $this->result->rowCount();
    }
}