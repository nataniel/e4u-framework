<?php
namespace E4u\Common\File;

use E4u\Common\File;
use E4u\Exception\RuntimeException;

class Directory extends File implements \IteratorAggregate, \Countable
{
    /**
     * @var File[]
     */
    protected array $entries;

    public function __construct(string $filename, string $publicPath = 'public/')
    {
        if ($filename === '.') {
            $filename = '';
        }

        parent::__construct($filename, $publicPath);
        $this->filename .= '/';

        if (!is_dir($this->getFullPath())) {
            throw new RuntimeException(sprintf('%s is not a directory.', $filename));
        }
    }

    public function getParent(): ?Directory
    {
        return $this->filename != '/'
            ? new Directory($this->getDirname(), $this->publicPath)
            : null;
    }

    protected function initialize(): void
    {
        if (!isset($this->entries)) {

            $files = scandir($this->getFullPath());
            $this->entries = [];

            foreach ($files as $entry) {
                if (($entry != '.') && ($entry != '..')) {
                    $this->entries[] = File::factory($this->filename . $entry, $this->publicPath);
                }
            }
        }
    }

    /**
     * @return File[]
     */
    public function getFiles(): array
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
    public function getDirectories(): array
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

    public function getIterator(): \ArrayIterator
    {
        $this->initialize();
        return new \ArrayIterator($this->entries);
    }

    public function count(): int
    {
        $this->initialize();
        return count($this->entries);
    }
}