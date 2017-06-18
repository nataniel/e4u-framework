# e4u-framework

## Installation
```
composer require nataniel/e4u-framework:dev-master
```

## Usage
```php
<?php
chdir(dirname(__DIR__));

// Your application namespace
define('APPLICATION', 'Main');

// Bootstrap E4u\Application
require_once __DIR__ . '/../vendor/autoload.php';
$app = E4u\Loader::get(APPLICATION);
$app->run()->send();
```

## Tests
```
phpunit --configuration tests/phpunit.xml
```