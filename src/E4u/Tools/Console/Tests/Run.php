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
        $className = $this->getArgument(0);
        if (empty($className)) {
            $this->showHelp();
            return false;
        }

        if (!class_exists($className)) {
            echo sprintf("ERROR: Class %s not found.\n", $className);
            return false;
        }

        $rootNamespace = strtok($className, '\\');
        $testName = strtok('');

        switch ($rootNamespace) {
            case APPLICATION:
                $directory = 'application';
                break;
            case 'E4u':
                $directory = 'framework';
                break;
            default:
                echo sprintf('You can only generate tests for application (%s\\) or framework (%s\\) classes.',
                             APPLICATION, 'E4u');
                return false;
        }

        $testFile = 'tests' . DIRECTORY_SEPARATOR . $directory . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $testName) . 'Test.php';
        if (!file_exists($testFile)) {
            echo sprintf("ERROR: File %s not found.\n", $testFile);
            return false;
        }

        $command = "phpunit --bootstrap tests\\bootstrap.php $testFile";
        system($command);

        return $this;
    }
}