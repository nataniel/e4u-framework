<?php
namespace E4u\Common\File;

use E4u\Common\File;

class Csv extends File
{
    protected $header;
    protected $data;

    protected $delimiter = ',';
    protected $enclosure = '"';
    protected $escape = '\\';

    /**
     * @param  string $delimiter
     * @return $this
     */
    public function setDelimiter($delimiter)
    {
        $this->delimiter = $delimiter;
        return $this;
    }

    /**
     * @return string[][]
     */
    public function toArray()
    {
        return $this->getData();
    }

    public function getColumn($key)
    {
        return array_column($this->getData(), $key);
    }

    /**
     * @return string[][]
     */
    public function getData()
    {
        $this->initialize();
        return $this->data;
    }

    /**
     * @return string[]
     */
    public function getHeader()
    {
        $this->initialize();
        return $this->header;
    }

    /**
     * @param  string $name Column name
     * @return bool
     */
    public function hasHeader($name)
    {
        $this->initialize();
        return in_array($name, $this->header);
    }

    /**
     * @return int
     */
    public function countColumns()
    {
        return count($this->getHeader());
    }

    protected function initialize()
    {
        if (!is_null($this->header)) {
            return false;
        }

        $file = fopen($this->getFullPath(), 'r');
        $this->header = $this->readLineIntoArray($file);

        $row = 0;
        $this->data = [];
        while ($data = $this->readLineIntoArray($file)) {
            foreach ($data as $i => $value) {
                $key = $this->header[$i];
                $this->data[ $row ][ $key ] = $value;
            }

            $row++;
        }

        fclose($file);
        return true;
    }

    /**
     * @param  resource $file
     * @param  int $length
     * @return string[]
     */
    protected function readLineIntoArray($file, $length = null)
    {
        return fgetcsv($file, $length, $this->delimiter, $this->enclosure, $this->escape);
    }
}