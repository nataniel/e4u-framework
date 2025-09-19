<?php
namespace E4u\Tools\Console;

use E4u\Exception\RuntimeException;

class Start extends Base
{
    public function help(): string
    {
        return "Start E4u console";
    }
    
    public function execute(): void
    {
        if (PHP_SAPI != 'cli') {
            throw new RuntimeException(sprintf('The console must be started using cli mode, %s used.', PHP_SAPI));
        }

        set_error_handler(function (int $errno, string $errstr) {
            echo "ERROR [$errno] $errstr\n";
            return true;
        });
        
        while (true)
        {
            if (!function_exists('readline')) {
                echo '> ';
                $_line = fgets(STDIN, 1024);
            } else {
                $_line = readline('> ');
                readline_add_history($_line);
            }

            try {
                $_line = trim($_line, ';').';';
                eval($_line);
                echo "\n";
            }
            catch (\Exception $e) {
                static::showException($e);
            }
        }
    }

    public static function showException(\Exception $e): void
    {
        echo sprintf("* %s \"%s\"\n* in %s (%d)\n",
                get_class($e),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine());
        $trace = $e->getTrace();

        $i = 0; $limit = count($trace) - 2;
        while (($i < $limit) && !empty($trace[$i]))
        {
            echo sprintf("* #%0d: %s%s%s() - line %d\n",
                    $i,
                    !empty($trace[$i]['class']) ? $trace[$i]['class'] : '',
                    !empty($trace[$i]['type'])  ? $trace[$i]['type'] : '',
                    $trace[$i]['function'],
                    $trace[$i]['line']);
            $i++;
        }
    }
}