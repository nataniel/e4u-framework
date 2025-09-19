<?php
namespace E4uTest\Common;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use E4u\Common\File;

/**
 * Class FileTest
 * @package E4uTest\Common
 */
#[CoversClass(File::class)]
class FileTest extends TestCase
{
    protected File $file;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->file = new File('files/test.pdf', 'tests');
    }

    public function testGetPublicPath()
    {
        $this->assertEquals('tests/', $this->file->getPublicPath());
    }

    public function testGetFilename()
    {
        $this->assertEquals('files/test.pdf', $this->file->getFilename());
    }

    public function testGetFullPath()
    {
        $this->assertEquals('tests/files/test.pdf', $this->file->getFullPath());
    }

    public function test__toString()
    {
        $this->assertEquals('files/test.pdf', "".$this->file);
    }

    public function testGetExtension()
    {
        $this->assertEquals('pdf', $this->file->getExtension());
    }

    public function testFileWithoutPublicPath()
    {
        $file = new File('tests/files/test.pdf', false);
        $this->assertEquals('tests/files/test.pdf', $file->getFullPath());
    }

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

    #[DataProvider('filesForFactory')]
    public function testFactory(string $expected, string $filename)
    {
        $this->assertInstanceOf($expected, File::factory($filename, 'tests/'));
    }

    public static function filesForFactory(): array
    {
        return [
            [ File\Directory::class, 'files/directory' ],
            [ File\Directory::class, 'files' ],
            [ File\Image::class, 'files/test.jpg' ],
            [ File::class, 'files/test.pdf' ],
            [ File::class, 'https://www.test.pl/file.html' ],
        ];
    }
}