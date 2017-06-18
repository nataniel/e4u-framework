<?php
namespace E4u\Common\File;

use E4u\Common\File;
use E4u\Exception\LogicException;

class Image extends File
{
    const JPEG_QUALITY = 95;

    /** @var array */
    protected $size;

    /** @var array */
    protected $_thumbnails;

    /**
     * @return array
     */
    protected function getImageSize()
    {
        if (null === $this->size) {
            if ($this->isLocal && $this->fileExists()) {
                $this->size = getimagesize($this->getFullPath());
            }
            else {
                $this->size = [];
            }
        }

        return $this->size;
    }

    public function isHorizontal()
    {
        return $this->getWidth() > $this->getHeight();
    }

    /**
     * @return int
     */
    public function getWidth()
    {
        $size = $this->getImageSize();
        return !empty($size) ? $size[0] : null;
    }

    /**
     * @return int
     */
    public function getHeight()
    {
        $size = $this->getImageSize();
        return !empty($size) ? $size[1] : null;
    }

    /**
     * @return string
     */
    public function getHTMLSize()
    {
        $size = $this->getImageSize();
        return !empty($size) ? $size[3] : null;
    }

    /**
     * @return string
     */
    public function getMime()
    {
        $size = $this->getImageSize();
        return !empty($size) ? $size['mime'] : null;
    }

    /**
     * @param  int $maxWidth
     * @param  int $maxHeight
     * @return array
     */
    public function resizeTo($maxWidth = 0, $maxHeight = 0)
    {
        $width  = $this->getWidth();
        $height = $this->getHeight();

        if (($maxWidth && ($width > $maxWidth)) || ($maxHeight && ($height > $maxHeight)))
        {
            if ($maxHeight > 0)
            {
                if (($maxWidth > 0) && (($width / $maxWidth) > ($height / $maxHeight)))
                {
                    // resize to maxWidth
                    $height = (int)($height * ($maxWidth / $width));
                    $width = $maxWidth;
                }
                else
                {
                    // resize to maxHeight
                    $width = (int)($width * ($maxHeight / $height));
                    $height = $maxHeight;
                }
            }
            else
            {
                // resize to maxWidth
                $height = (int)($height * ($maxWidth / $width));
                $width = $maxWidth;
            }
        }

        return [ $width, $height ];
    }

    /**
     * @param int $squareSide
     * @return Image
     */
    public function getThumbnailForSquareCrop($squareSide)
    {
        if ($this->isHorizontal()) {
            $maxWidth = 0;
            $maxHeight = $squareSide;
        } else {
            $maxWidth = $squareSide;
            $maxHeight = 0;
        }
        return $this->getThumbnail($maxWidth, $maxHeight);
    }

    /**
     * @param int    $maxWidth
     * @param int    $maxHeight
     * @param string $backgroundColor
     * @return Image
     */
    public function getThumbnail($maxWidth = 0, $maxHeight = 0, $backgroundColor = 'ffffff', $forceOverwrite = false)
    {
        if (!$this->isLocal) {
            return $this;
        }

        if (!$this->fileExists()) {
            return null;
        }

        $_key = $maxWidth.'x'.$maxHeight;
        if (isset($this->_thumbnails[$_key])) {
            return $this->_thumbnails[$_key];
        }

        $size = $this->resizeTo($maxWidth, $maxHeight);
        if (($size[0] == $this->getWidth()) && ($size[1] == $this->getHeight())) {
            // if the thumbnail is the same size as the original file,
            // just return the original file
            $this->_thumbnails[$_key] = $this;
            return $this;
        }

        // thumbnail filename convention:
        // foobar.jpg => foobar-120x450-ffffff.jpg
        // some/dir/foo.jpg => some/dir/foo-120x450-ffffff.jpg
        $extension = '-'.$size[0].'x'.$size[1].'-'.$backgroundColor;
        $base = substr($this->getFilename(), 0, -1 - strlen($this->getExtension()));
        $filename = $base.$extension.'.'.$this->getExtension();

        // create thumbnail file, if it does not exist already
        if ($forceOverwrite || !is_file($this->getPublicPath().$filename))
        {
            switch (strtolower($this->getExtension()))
            {
                case 'jpg':
                case 'jpeg':
                    @$img = imagecreatefromjpeg($this->getFullPath());
                break;
                case 'png':
                    @$img = imagecreatefrompng($this->getFullPath());
                break;
                case 'gif':
                    @$img = imagecreatefromgif($this->getFullPath());
                    break;
                default:
                    throw new LogicException(sprintf('Only JPG, PNG and GIF files are allowed for getThumbnail(), %s given.', $this->getFilename()));
            }

            $red = 255; $green = 255; $blue = 255;
            if (preg_match('/([0-9A-F]{2})([0-9A-F]{2})([0-9A-F]{2})/i', $backgroundColor, $regs))
            {
                $red   = hexdec($regs[1]);
                $green = hexdec($regs[2]);
                $blue  = hexdec($regs[3]);
            }

            // resizing with fixed background color
            $out = imagecreatetruecolor($size[0], $size[1]);
            $white = imagecolorallocate($out, $red, $green, $blue);
            imagefilledrectangle($out, 0, 0, $size[0], $size[1], $white);
            imagecopyresampled($out, $img, 0, 0, 0, 0, $size[0], $size[1], $this->getWidth(), $this->getHeight());

            switch (strtolower($this->getExtension()))
            {
                case 'jpg':
                case 'jpeg':
                    imagejpeg($out, $this->getPublicPath() . $filename, static::JPEG_QUALITY);
                    break;
                case 'png':
                    imagepng($out, $this->getPublicPath() . $filename);
                    break;
                case 'gif':
                    imagegif($out, $this->getPublicPath() . $filename);
                    break;
            }
        }

        $this->_thumbnails[$_key] = new Image($filename, $this->getPublicPath());
        return $this->_thumbnails[$_key];
    }
}