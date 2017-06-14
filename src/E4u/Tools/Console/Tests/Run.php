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

        if (!class_exists($srcClass)) {
            echo sprintf("ERROR: Class %s not found.\n", $srcClass);
            return false;
        }

        $rootNamespace = strtok($srcClass, '\\');
        $testClass = $rootNamespace . 'Test' . substr($srcClass, strlen($rootNamespace)) . 'Test';

        $testFile = 'tests' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $testClass) . '.php';
        if (!file_exists($testFile)) {
            echo sprintf("ERROR: File %s not found.\n", $testFile);
            return false;
        }

        $command = "phpunit --bootstrap tests\\bootstrap.php $testFile";
        system($command);

        return $this;
    }
}