<?php
namespace E4u\Tools\Console\Tests;

use E4u\Tools\Console\Base;

class Run extends Base
{
    public function help()
    {
        return [ APPLICATION.'\Class' => sprintf("Run test case for %s\\Class", APPLICATION) ];
    }

    public function execute()
    {
        $srcClass = $this->getArgument(0);
        if (empty($srcClass)) {
            $this->showHelp();
            return false;
        }

        $rootNamespace = strtok($srcClass, '\\');
        $testClass = $rootNamespace . 'Test' . substr($srcClass, strlen($rootNamespace)) . 'Test';

        if (!class_exists($srcClass)) {
            echo sprintf("ERROR: Class %s not found.\nMake sure autoload for %s\\ namespace is defined in composer.json.\n", $srcClass, $rootNamespace);
            return false;
        }
        
        if (!class_exists($testClass)) {
            echo sprintf("ERROR: Test class %s not found.\nMake sure autoload for %s\\ namespace is defined in composer.json.\n", $srcClass, $rootNamespace . 'Test');
            return false;
        }

        $reflector = new \ReflectionClass($testClass);
        $testFile = $reflector->getFileName();

        $command = "phpunit --bootstrap tests\\bootstrap.php $testFile";
        system($command);

        return $this;
    }
}