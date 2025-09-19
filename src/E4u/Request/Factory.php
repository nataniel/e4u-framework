<?php
namespace E4u\Request;

class Factory
{
    public static function create(): Cli|Xhr|Http
    {
        if (PHP_SAPI === 'cli') {
            return new Cli();
        }
        
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')) {
            return new Xhr();
        }

        return new Http();
    }
}