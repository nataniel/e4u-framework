<?php
namespace E4uTest\Common\File;

use E4u\Common\File;
use E4u\Common\File\Directory;
use E4u\Exception\RuntimeException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Class DirectoryTest
 * @package E4uTest\Common\File
 */
#[CoversClass(Directory::class)]
class DirectoryTest extends TestCase
{
    public function testIfConstructorAcceptsPath()
    {
        $dir = new Directory('/files/', 'tests/');
        $this->assertEquals('files/', $dir->getFilename());

        $this->expectException(RuntimeException::class);
        new Directory('/files/directory/test.html', 'tests/');
    }

    public function testGetParent()
    {
        $dir = new Directory('/files', 'tests/');
        $parent = $dir->getParent();

        $this->assertEquals('/', $parent->getFilename());
        $this->assertNull($parent->getParent());
    }

    public function testCountable()
    {
        $dir = new Directory('/files/directory', 'tests/');
        $this->assertCount(1, $dir);
        return $dir;
    }

    public function testIterable()
    {
        $dir = new Directory('/files/directory', 'tests/');
        foreach ($dir as $file) {
            $this->assertInstanceOf(File::class, $file);
        }
    }
}