<?php
namespace E4u\Common;

use E4u\Application\Helper\Url;

class File
{
    protected $publicPath;
    protected $filename;
    protected $errors = [];
    protected $mime;
    protected $isLocal;

    /**
     * @param string $filename
     * @param string $publicPath
     */
    public function __construct($filename, $publicPath = 'public/')
    {
        if ($publicPath == '/') {
            $this->filename = $filename;
            $this->publicPath = '';
        }
        else {
            $this->filename = trim($filename, '/');
            $this->publicPath = $publicPath ? trim($publicPath, '/') . '/' : '';
        }

        $this->isLocal = !$this->isExternalUrl($filename);
    }

    /**
     * @param  string $target
     * @return bool
     */
    private function isExternalUrl($target)
    {
        return Url::isExternalUrl($target);
    }

    /**
     * @return bool
     */
    public function isLocal()
    {
        return $this->isLocal;
    }

    /**
     * Returns public/ directory.
     * @return string
     */
    public function getPublicPath()
    {
        return $this->isLocal ? $this->publicPath : '';
    }

    /**
     * Returns the file name, without public/ part.
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Returns public/filename.ext
     * @return string
     */
    public function getFullPath()
    {
        return $this->getPublicPath() . $this->getFilename();
    }

    /**
     * @return string
     */
    public function getBasename()
    {
        return pathinfo($this->getFilename(), PATHINFO_BASENAME);
    }

    /**
     * @return float|null
     */
    public function getFilesize()
    {
        if (!is_file($this->getFullPath())) {
            return null;
        }

        return round(filesize($this->getFullPath()) / 1024, 2);
    }

    /**
     * @return string
     */
    public function getMimeType()
    {
        if (null === $this->mime) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $this->mime = finfo_file($finfo, $this->getFullPath());
            finfo_close($finfo);
        }

        return $this->mime;
    }

    /**
     * @return bool
     */
    public function fileExists()
    {
        return !$this->isLocal || is_file($this->getFullPath());
    }

    /**
     * http://www.php.net/manual/en/language.oop5.magic.php#object.tostring
     */
    public function __toString()
    {
        return $this->getFilename();
    }

    /**
     * @return string
     */
    public function toUrl()
    {
        return $this->getFilename();
    }

    /**
     * http://www.php.net/manual/en/splfileinfo.getextension.php
     *
     * @return string
     */
    public function getExtension()
    {
        return pathinfo($this->getFilename(), PATHINFO_EXTENSION);
    }

    /**
     *
     * @param  string $filename
     * @param  string $publicPath
     * @return File
     */
    public static function factory($filename, $publicPath = 'public/')
    {
        $file = trim($publicPath, '/') . '/' . trim($filename, '/');

        return exif_imagetype($file)
            ? new File\Image($filename, $publicPath)
            : new File($filename, $publicPath);
    }
}