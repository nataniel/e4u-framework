<?php
namespace E4uTest\Common\File;

use E4u\Common\File\Image;
use PHPUnit\Framework\TestCase;

class ImageTest extends TestCase
{
    /**
     * @var \E4u\Common\File\Image
     */
    protected $file;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->file = new Image('files/test.jpg', 'tests');
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }

    /**
     * @covers Image::getWidth
     */
    public function testGetWidth()
    {
        $this->assertEquals(283, $this->file->getWidth());
    }

    /**
     * @covers Image::getHeight
     */
    public function testGetHeight()
    {
        $this->assertEquals(349, $this->file->getHeight());
    }

    /**
     * @covers Image::getHTMLSize
     */
    public function testGetHTMLSize()
    {
        $this->assertEquals('width="283" height="349"', $this->file->getHTMLSize());
    }

    /**
     * @covers Image::getMime
     */
    public function testGetMime()
    {
        $this->assertEquals('image/jpeg', $this->file->getMime());
    }

    /**
     * @covers Image::resizeTo
     */
    public function testResizeTo()
    {
        $this->assertEquals([100, 123], $this->file->resizeTo(100));
        $this->assertEquals([ 81, 100], $this->file->resizeTo(null, 100));
        $this->assertEquals([162, 200], $this->file->resizeTo(200, 200));
    }

    /**
     * @covers Image::getThumbnail
     */
    public function testGetThumbnail()
    {
        $thumbnail = $this->file->getThumbnail(200, 200, 'cccccc', true);
        
        $this->assertFileExists('tests/files/test-162x200-cccccc.jpg');
        $this->assertInstanceOf(Image::class, $thumbnail);
        $this->assertEquals(162, $thumbnail->getWidth());
        $this->assertEquals(200, $thumbnail->getHeight());
        
        unlink('tests/files/test-162x200-cccccc.jpg');
    }
}
