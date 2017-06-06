<?php
namespace E4u\Tools\Console;

class Version extends Base
{
    public function help()
    {
        return "Shows current version of E4u Framework";
    }
    
    public function execute()
    {
        return true;
    }
}