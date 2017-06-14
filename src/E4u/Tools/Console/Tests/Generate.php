<?php
namespace E4u\Tools\Console\Tests;

use E4u\Tools\Console\Base;

class Generate extends Base
{
    public function help()
    {
        return [ APPLICATION.'\Class' => sprintf("Generate test case for %s\\Class", APPLICATION) ];
    }

    public function execute()
    {
        $srcClass = $this->getArgument(0);
        if (empty($srcClass)) {
            $this->showHelp();
            return false;
        }

        if (!class_exists($srcClass)) {
            echo sprintf("ERROR: Class %s not found.\n", $srcClass);
            return false;
        }

        $rootNamespace = strtok($srcClass, '\\');
        $testClass = $rootNamespace . 'Test' . substr($srcClass, strlen($rootNamespace)) . 'Test';

        if (class_exists($testClass)) {
            echo sprintf("ERROR: Class %s already exists.\n", $testClass);
            return false;
        }

        $testFile = 'tests' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $testClass) . '.php';
        if (file_exists($testFile) && !$this->getOption('force')) {
            echo sprintf("WARNING: File %s already exists.\n".
                "         Use --force to overwrite it.\n", $testFile);
            return false;
        }

        if (!is_dir(dirname($testFile))) {
            mkdir(dirname($testFile), 0777, true);
        }


        file_put_contents($testFile, $this->testFileContents($srcClass, $testClass));
        echo $testFile. " created.\n";

        return $this;
    }

    private function testFileContents($srcClass, $testClass)
    {
        $pos = strrpos($srcClass, '\\');
        $srcClassPart = substr($srcClass, $pos + 1);

        $pos = strrpos($testClass, '\\');
        $testClassPart = substr($testClass, $pos + 1);
        $testNamespace = substr($testClass, 0, $pos);

        return sprintf('<?php
namespace %4$s;

use PHPUnit\Framework\TestCase;
use %1$s;

/**
 * Class %3$s
 * @package %4$s
 * @covers  %2$s
 */
class %3$s extends TestCase
{
    protected function setUp()
    {
    }

    /**
     * @covers %2$s::someFunction()
     */
    public function testSomeFunction()
    {
        // @todo: implement me
        $this->assertTrue(true);    
    }
}',
            $srcClass, $srcClassPart, $testClassPart, $testNamespace);
    }
}