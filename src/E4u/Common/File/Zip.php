<?php
namespace E4u\Common\File;

use E4u\Application\Exception;
use E4u\Common\File;

class Zip extends File
{
    /**
     * @var \ZipArchive
     */
    private $archive;
    
    public function __construct($filename, $publicPath = 'public/')
    {
        parent::__construct($filename, $publicPath);
        $this->createArchive();
    }

    public function addFile(File $file)
    {
        $this->archive->addFile($file->getFullPath(), $file->getBasename());
    }
    
    public function close()
    {
        $this->archive->close();
    }

    private function createArchive()
    {
        $this->archive = new \ZipArchive;
        $result = $this->archive->open($this->getFullPath(), \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        if ($result !== true) {
            throw new Exception("Cannot create zip archive. Code: $result");
        }
    }

    public function addEmptyDir($dirName)
    {
        $this->archive->addEmptyDir($dirName);
    }
}