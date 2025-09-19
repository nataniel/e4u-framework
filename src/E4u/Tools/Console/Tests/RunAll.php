<?php
namespace E4u\Tools\Console\Tests;

use E4u\Tools\Console\Base;

class RunAll extends Base
{
    public function help(): string
    {
        return 'Run all tests';
    }

    public function execute(): void
    {
        $command = "phpunit --configuration tests/phpunit.xml";
        system($command);
    }
}