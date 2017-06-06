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
     * @return string[]
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
     * @return string[]
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

    protected function initialize()
    {
        if (!is_null($this->header)) {
            return false;
        }

        $file = fopen($this->getFullPath(), 'r');
        $this->header = $this->readLineToArray($file);

        $row = 0;
        $this->data = [];
        while ($data = $this->readLineToArray($file)) {
            foreach ($data as $i => $value) {
                $key = $this->header[$i];
                $this->data[$row][$key] = $value;
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
    protected function readLineToArray($file, $length = null)
    {
        return fgetcsv($file, $length, $this->delimiter, $this->enclosure, $this->escape);
    }
}