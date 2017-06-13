<?php
chdir(dirname(__DIR__));

// Your application namespace
define('APPLICATION', 'E4u');

// Bootstrap E4u\Application
require_once 'vendor/autoload.php';
E4u\Loader::configureApplication(APPLICATION, 'test');
