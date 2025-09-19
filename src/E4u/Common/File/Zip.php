<?php
namespace E4u\Common\File;

use E4u\Application\Exception;
use E4u\Common\File;

class Zip extends File
{
    private \ZipArchive $archive;
    
    public function __construct(string $filename, string $publicPath = 'public/')
    {
        parent::__construct($filename, $publicPath);
        $this->createArchive();
    }

    public function addFile(File $file): void
    {
        if ($file->isLocal()) {
            $this->archive->addFile($file->getFullPath(), $file->getBasename());
        } else {
            $fullPath = str_replace(' ', '%20', $file->getFullPath());
            if ($fileContent = @file_get_contents($fullPath)) {
                $this->archive->addFromString($file->getBasename(), $fileContent);
            }
        }
    }
    
    public function close(): void
    {
        $this->archive->close();
    }

    private function createArchive(): void
    {
        $this->archive = new \ZipArchive;
        $result = $this->archive->open($this->getFullPath(), \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        if ($result !== true) {
            throw new Exception("Cannot create zip archive. Code: $result");
        }
    }

    public function addEmptyDir(string $dirName): void
    {
        $this->archive->addEmptyDir($dirName);
    }
}