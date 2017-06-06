<?php
namespace E4u\Tools\Console;

use E4u\Tools\Console;

interface Command
{
    public function configure($arguments, $options);
    public function execute();
    public function setConsole(Console $console);
    
    public function help();
    public function showHelp();
}