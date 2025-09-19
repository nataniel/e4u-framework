<?php
namespace E4u\Tools\Console;

use E4u\Tools\Console;

interface Command
{
    public function configure(array $arguments, Getopt $options): static;
    public function execute(): void;
    public function setConsole(Console $console): static;
    
    public function help(): null|array|string;
    public function showHelp(): void;
}