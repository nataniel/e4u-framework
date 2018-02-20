<?php
namespace E4u\Db;

class Odbc
{
    private $_connection;
    protected $result;
    protected $dump_sql = false;

    public function __construct($options)
    {
        $this->_connection = @odbc_connect($options['dsn'], $options['user'], $options['password']);
        if (false === $this->_connection) {
            throw new Exception\ConnectionFailed(odbc_errormsg());
        }

        if (isset($options['dump_sql'])) {
            $this->dump_sql = (bool)$options['dump_sql'];
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
     * @return string
     */
    public function __toString()
    {
        $description = '';
        $source = @odbc_data_source( $this->_connection, SQL_FETCH_FIRST );
        while ($source)
        {
            $description .= $source['server'] . " - " . $source['description'] . "\n";
            $source = @odbc_data_source( $this->_connection, SQL_FETCH_NEXT );
        }

        return $description;
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

        $this->result = odbc_prepare($this->_connection, $query);
        if (false === $this->result) {
            throw new Exception\QueryFailed(odbc_errormsg());
        }

        if (false === odbc_execute($this->result, $params)) {
            throw new Exception\QueryFailed(odbc_errormsg());
        }

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $array = [];
        while ($row = odbc_fetch_array($this->result)) {
            $array[] = $row;
        }

        odbc_free_result($this->result);
        return $array;
    }

    public function select($query, $params = [])
    {
        return $this->execute($query, $params)->toArray();
    }

    public function selectRow($query, $params = [])
    {
        $result = $this->select($query, $params);
        return !empty($result) ? $result[0] : null;
    }

    public function selectValue($query, $params = [])
    {
        $result = $this->selectRow($query, $params);
        return !empty($result) ? reset($result) : null;
    }
    
    public function quoteValue($value)
    {
        if (is_numeric($value)) {
            return "'".$value."'";
        }
        elseif (is_null($value)) {
            return 'NULL';
        }
        elseif (is_array($value)) {
            $array = array();
            foreach ($value as $k => $v) {
                $array[$k] = $this->quoteValue($v);
            }

            return $array;
        }
        else {
            return "'" . addcslashes($value, "\0\'\\"). "'";
        }
    }
}