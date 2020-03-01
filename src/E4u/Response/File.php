<?php
namespace E4u\Response;

use E4u\Application\Exception\PageNotFound;

class File extends Stream
{
    /**
     * @param  mixed $value
     * @param  string $name
     * @return $this
     */
    public function setContent($value, $name = null)
    {
        if (!$this->fileExists($value)) {
            throw new PageNotFound(sprintf('Plik %s nie istnieje.', basename($value)));
        }

        $this->setName($name ?: basename($value));
        $this->content = $value;
        return $this;
    }

    /**
     * @param  string $filename
     * @return bool
     */
    private function fileExists($filename)
    {
        if (parse_url($filename, PHP_URL_SCHEME) != '') {
            $headers = get_headers($filename);
            return stripos($headers[0], "200 OK") !== false;
        }

        return file_exists($filename);
    }

    /**
     * @return $this
     */
    public function sendContent()
    {
        readfile($this->getContent());
        return $this;
    }
}