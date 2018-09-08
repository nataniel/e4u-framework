<?php
namespace E4u\Common\File;

use E4u\Common\File;
use E4u\Exception\RuntimeException;

class Directory extends File implements \IteratorAggregate, \Countable
{
    /**
     * @var File[]
     */
    protected $entries;

    public function __construct($filename, $publicPath = 'public/')
    {
        if ($filename == '.') {
            $filename = '';
        }

        parent::__construct($filename, $publicPath);
        $this->filename .= '/';

        if (!is_dir($this->getFullPath())) {
            throw new RuntimeException(sprintf('%s is not a directory.', $filename));
        }
    }

    /**
     * @return Directory|null
     */
    public function getParent()
    {
        return $this->filename != '/'
            ? new Directory($this->getDirname(), $this->publicPath)
            : null;
    }

    protected function initialize()
    {
        if (null === $this->entries) {
            $this->files = [];
            $files = scandir($this->getFullPath());
            foreach ($files as $entry) {
                if (($entry != '.') && ($entry != '..')) {
                    $this->entries[] = File::factory($this->filename . $entry, $this->publicPath);
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
        $files = [];
        foreach ($this->entries as $entry) {
            if (!$entry instanceof Directory) {
                $files[] = $entry;
            }
        }

        return $files;
    }

    /**
     * @return File\Directory[]
     */
    public function getDirectories()
    {
        $this->initialize();
        $directories = [];
        foreach ($this->entries as $entry) {
            if ($entry instanceof Directory) {
                $directories[] = $entry;
            }
        }

        return $directories;
    }

    /**
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        $this->initialize();
        return new \ArrayIterator($this->entries);
    }

    /**
     * @return int
     */
    public function count()
    {
        $this->initialize();
        return count($this->entries);
    }
}