<?php
namespace E4u\Response;

use E4u\Application\Exception\PageNotFound;

class File extends Stream
{
    public function setContent($value, ?string $name = null)
    {
        if (!$this->fileExists($value)) {
            throw new PageNotFound(sprintf('Plik %s nie istnieje.', basename($value)));
        }

        $this->setName($name ?: basename($value));
        $this->content = $value;
        return $this;
    }

    private function fileExists(string $filename): bool
    {
        if (parse_url($filename, PHP_URL_SCHEME) != '') {
            $headers = get_headers($filename);
            return stripos($headers[0], "200 OK") !== false;
        }

        return file_exists($filename);
    }

    public function sendContent(): static
    {
        readfile($this->getContent());
        return $this;
    }
}