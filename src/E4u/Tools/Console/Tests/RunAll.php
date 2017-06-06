<?php
namespace E4u\Tools\Console\Tests;

use E4u\Tools\Console\Base;

class RunAll extends Base
{
    public function help()
    {
        return 'Run all tests';
    }

    public function execute()
    {
        $command = "phpunit --configuration tests/phpunit.xml";
        system($command);
    }
}