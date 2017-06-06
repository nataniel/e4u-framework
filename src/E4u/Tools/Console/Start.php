<?php
namespace E4u\Tools\Console;

class Start extends Base
{
    public function help()
    {
        return "Start E4u console";
    }
    
    public function execute()
    {
        if (PHP_SAPI != 'cli') {
            throw new \E4u\Exception\RuntimeException(sprintf('The console must be started using cli mode, %s used.', PHP_SAPI));
        }

        $old_error_handler = set_error_handler([ get_class($this), 'errorHandler' ]);
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

    public static function showException(\Exception $e)
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

    public static function errorHandler($errno, $errstr)
    {
        echo "ERROR [$errno] $errstr\n";
        return true;
    }
}