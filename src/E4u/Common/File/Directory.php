<?php
namespace E4u\Common\File;

use E4u\Common\File;
use E4u\Exception\RuntimeException;

class Directory extends File implements \IteratorAggregate, \Countable
{
    /**
     * @var File[]
     */
    protected $files;

    public function __construct($filename, $publicPath = 'public/')
    {
        if ($filename == '.') {
            $filename = '';
        }

        parent::__construct($filename, $publicPath);
        if (!is_dir($this->getFullPath())) {
            throw new RuntimeException(sprintf('%s is not a directory.', $filename));
        }
    }

    /**
     * @return Directory|null
     */
    public function getParent()
    {
        return $this->filename != ''
            ? new Directory($this->getDirname(), $this->publicPath)
            : null;
    }

    protected function initialize()
    {
        if (null === $this->files) {
            $this->files = [];
            $files = scandir($this->getFullPath());
            foreach ($files as $entry) {
                if (($entry != '.') && ($entry != '..')) {
                    $this->files[] = File::factory($this->filename . '/' . $entry, $this->publicPath);
                }
            }
        }

        return $this;
    }

    /**
     * @return File[]
     */
    public function getFiles()
    {
        $this->initialize();
        return $this->files;
    }

    /**
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        $this->initialize();
        return new \ArrayIterator($this->files);
    }

    /**
     * @return int
     */
    public function count()
    {
        $this->initialize();
        return count($this->files);
    }
}