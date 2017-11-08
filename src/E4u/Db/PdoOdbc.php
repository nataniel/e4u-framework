<?php
namespace E4u\Db;

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
}