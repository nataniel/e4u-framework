<?php
namespace E4u\Common\File\Csv;

use E4u\Common\File\Csv;

class CsvWithoutHeader extends Csv
{
    public function countColumns(): int
    {
        return !empty($this->getData())
            ? count($this->data[0])
            : 0;
    }

    protected function initialize(): void
    {
        if (isset($this->header)) {
            return;
        }
        
        $this->header = [];
        $file = fopen($this->getFullPath(), 'r');
        
        $this->data = [];
        while ($row = $this->readLineIntoArray($file)) {
            $this->data[] = $row;
        }

        fclose($file);
    }
}