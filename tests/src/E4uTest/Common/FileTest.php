<?php
namespace E4uTest\Common;

use PHPUnit\Framework\TestCase;
use E4u\Common\File;

/**
 * Class FileTest
 * @package E4uTest\Common
 * @covers  File
 */
class FileTest extends TestCase
{
    /**
     * @var File
     */
    protected $file;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->file = new File('files/test.pdf', 'tests');
    }

    /**
     * @covers File::testGetPublicPath
     */
    public function testGetPublicPath()
    {
        $this->assertEquals('tests/', $this->file->getPublicPath());
    }

    /**
     * @covers File::getFilename
     */
    public function testGetFilename()
    {
        $this->assertEquals('files/test.pdf', $this->file->getFilename());
    }

    /**
     * @covers File::getFullPath
     */
    public function testGetFullPath()
    {
        $this->assertEquals('tests/files/test.pdf', $this->file->getFullPath());
    }

    /**
     * @covers File::__toString
     */
    public function test__toString()
    {
        $this->assertEquals('files/test.pdf', "".$this->file);
    }

    /**
     * @covers File::getExtension
     */
    public function testGetExtension()
    {
        $this->assertEquals('pdf', $this->file->getExtension());
    }

    /**
     * @covers File::getPublicPath()
     * @covers File::getFullPath()
     */
    public function testFileWithoutPublicPath()
    {
        $file = new File('tests/files/test.pdf', false);
        $this->assertEquals('tests/files/test.pdf', $file->getFullPath());
    }

    /**
     * @covers File::getPublicPath()
     * @covers File::getFullPath()
     */
    public function testFileWithRootPath()
    {
        $file = new File('/tmp/phpGxfrpN', '/');
        $this->assertEquals('', $file->getPublicPath());
        $this->assertEquals('/tmp/phpGxfrpN', $file->getFullPath());
    }

    public function testFileWindows()
    {
        $file = new File('C:\Windows\Temp\phpDFA0.tmp', '/');
        $this->assertEquals('C:\Windows\Temp\phpDFA0.tmp', $file->getFullPath());
    }

    public function testIsHidden()
    {
        $file = new File('files/.hidden', 'tests/');
        $this->assertTrue($file->isHidden());
    }

    /**
     * @dataProvider filesForFactory
     * @param string $expected
     * @param string $filename
     * @covers File::factory
     */
    public function testFactory($expected, $filename)
    {
        $this->assertInstanceOf($expected, File::factory($filename, 'tests/'));
    }

    public function filesForFactory()
    {
        return [
            [ File\Directory::class, 'files/directory' ],
            [ File\Directory::class, 'files' ],
            [ File\Image::class, 'files/test.jpg' ],
            [ File::class, 'files/test.pdf' ],
            [ File::class, 'http://www.test.pl/file.html' ],
        ];
    }
}