<?php
namespace E4u\Common\File;

use E4u\Common\File;

class Csv extends File
{
    protected array $header;
    protected array $data;

    protected string $separator = ',';
    protected string $enclosure = '"';
    protected string $escape = '\\';

    public function setSeparator(string $separator): static
    {
        $this->separator = $separator;
        return $this;
    }

    public function toArray(): array
    {
        return $this->getData();
    }

    public function getColumn(string $key): array
    {
        return array_column($this->getData(), $key);
    }

    public function getData(): array
    {
        $this->initialize();
        return $this->data;
    }

    public function getHeader(): array
    {
        $this->initialize();
        return $this->header;
    }

    public function hasHeader(string $name): bool
    {
        $this->initialize();
        return in_array($name, $this->header);
    }

    public function countColumns(): int
    {
        return count($this->getHeader());
    }
    
    protected function initialize(): void
    {
        if (isset($this->header)) {
            return;
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
    }

    protected function readLineIntoArray($file, ?int $length = null): array|false
    {
        return fgetcsv($file, $length, $this->separator, $this->enclosure, $this->escape);
    }
}