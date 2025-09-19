<?php
namespace E4u\Common;

use E4u\Application\Helper\Url;
use E4u\Application\View;

class File
{
    const int
        B = 1,
        KB = 1024,
        MB = 1048576;

    protected string $publicPath;
    protected string $filename;
    protected array $errors = [];
    protected string $mime;
    protected bool $isLocal;

    /**
     * @param string $filename
     * @param string $publicPath
     */
    public function __construct(string $filename, string $publicPath = 'public/')
    {
        if ($publicPath == '/') {
            $this->filename = $filename;
            $this->publicPath = '';
        }
        else {
            $this->filename = trim($filename, '/');
            $this->publicPath = $publicPath ? trim($publicPath, '/') . '/' : '';
        }

        $this->isLocal = !View::isExternalUrl($filename);
    }

    public function isLocal(): bool
    {
        return $this->isLocal;
    }

    /**
     * Returns public/ directory.
     */
    public function getPublicPath(): string
    {
        return $this->isLocal ? $this->publicPath : '';
    }

    /**
     * Returns the file name, without public/ part.
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * Returns public/filename.ext
     */
    public function getFullPath(): string
    {
        return $this->getPublicPath() . $this->getFilename();
    }

    public function getBasename(): string
    {
        return pathinfo($this->getFilename(), PATHINFO_BASENAME);
    }

    public function getDirname(): string
    {
        return pathinfo($this->getFilename(), PATHINFO_DIRNAME);
    }

    public function getFilesize(int $precision = self::KB): ?float
    {
        if (!is_file($this->getFullPath())) {
            return null;
        }

        return round(filesize($this->getFullPath()) / $precision, 2);
    }

    /**
     * @return string
     */
    public function getMimeType(): string
    {
        if (!isset($this->mime)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $this->mime = finfo_file($finfo, $this->getFullPath());
            finfo_close($finfo);
        }

        return $this->mime;
    }

    public function isHidden(): bool
    {
        return str_starts_with($this->getBasename(), '.');
    }

    public function fileExists(): bool
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

    public function toUrl(): string
    {
        return $this->getFilename();
    }

    /**
     * http://www.php.net/manual/en/splfileinfo.getextension.php
     *
     * @return string
     */
    public function getExtension(): string
    {
        return pathinfo($this->getFilename(), PATHINFO_EXTENSION);
    }

    public static function factory(string $filename, string $publicPath = 'public/'): File
    {
        $file = trim(trim($publicPath, '/') . '/' . trim($filename, '/'), '/');

        if (View::isExternalUrl($filename) || View::isExternalUrl($file)) {
            return new File($filename, $publicPath);
        }

        if (is_dir($file)) {
            return new File\Directory($filename, $publicPath);
        }

        if (exif_imagetype($file)) {
            return new File\Image($filename, $publicPath);
        }

        return new File($filename, $publicPath);
    }
}