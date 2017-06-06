<?php
namespace E4u\Common\File\Csv;

use E4u\Common\File\Csv;

class CsvWithoutHeader extends Csv
{

    protected function initialize()
    {
        if (!is_null($this->header)) {
            return false;
        }
        $this->header = [];

        $file = fopen($this->getFullPath(), 'r');

        $this->data = [];
        while ($row = $this->readLineToArray($file)) {
            $this->data[] = $row;
        }

        fclose($file);
        return true;
    }
}