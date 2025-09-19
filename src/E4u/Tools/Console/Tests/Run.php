<?php
namespace E4u\Tools\Console\Tests;

use E4u\Tools\Console\Base;

class Run extends Base
{
    public function help(): array
    {
        return [ APPLICATION.'\Class' => sprintf("Run test case for %s\\Class", APPLICATION) ];
    }

    public function execute(): void
    {
        $srcClass = $this->getArgument(0);
        if (empty($srcClass)) {
            $this->showHelp();
            return;
        }

        $rootNamespace = strtok($srcClass, '\\');
        $testClass = $rootNamespace . 'Test' . substr($srcClass, strlen($rootNamespace)) . 'Test';

        if (!class_exists($srcClass)) {
            echo sprintf("ERROR: Class %s not found.\nMake sure autoload for %s\\ namespace is defined in composer.json.\n", $srcClass, $rootNamespace);
            return;
        }
        
        if (!class_exists($testClass)) {
            echo sprintf("ERROR: Test class %s not found.\nMake sure autoload for %s\\ namespace is defined in composer.json.\n", $srcClass, $rootNamespace . 'Test');
            return;
        }

        $reflector = new \ReflectionClass($testClass);
        $testFile = $reflector->getFileName();

        $command = "phpunit --bootstrap tests\\bootstrap.php $testFile";
        system($command);
    }
}