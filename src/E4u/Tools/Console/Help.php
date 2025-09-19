<?php
namespace E4u\Tools\Console;

class Help extends Base
{
    public function help(): string
    {
        return "This help message";
    }
    
    public function execute(): void
    {
        echo sprintf("Usage:\n"
                    ."  %s command [options] [arguments]\n"
                    ."\n"
                    ."Options:\n"
                    ."  --help                    Help message on command\n"
                    ."  --dump-sql                Print all SQL queries\n"
                    ."  --environment=ENV         Set environment to ENV\n"
                    ."\n"
                    ."Available commands:\n",
                    $this->getScript()
                );
        $commands = $this->getConsole()->getCommands();
        foreach (array_keys($commands) as $cmd) {
            $this->getConsole()->showHelp($cmd);
        }
        
        echo "\n"
            ."To extend the functionality in your application, create new\n"
            ."commands (implementations of E4u\Tools\Console\Command) and\n"
            ."add them to the application configuration.\n";
    }
}