<?php
namespace E4u\Response;

use E4u\Application\Exception\PageNotFound;

class File extends Http
{
    protected $defaultContentType = 'application/octet-stream';
    protected $headers = [
        'Content-Transfer-Encoding' => 'binary',
    ];
    protected $type = 'attachment';

    public function __construct($filename = null, $name = null)
    {
        parent::__construct($filename);

        if (!is_null($name)) {
            $this->setName($name);
        }
    }

    /**
     * @param  mixed $value
     * @param  string $name
     * @return File
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
     * @param  string $name
     * @return File
     */
    public function setName($name)
    {
        $this->addHeader('Content-Disposition', sprintf('%s; filename=%s', $this->type, $name));
        return $this;
    }

    /**
     * @return Http
     */
    public function sendContent()
    {
        readfile($this->getContent());
        return $this;
    }
}