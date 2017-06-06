<?php
namespace E4u\Tools\Console\Fixtures;

use E4u\Tools\Console\Base,
    E4u\Model\Fixture;

class Load extends Base
{
    const DEFAULT_PATH = 'application/data/fixtures/';
    
    public function help()
    {
        return [
            'filename'   => 'Load fixtures file',
            '*'          => 'Load all fixtures',
            'test/*abc'  => 'Load some fixtures from test',
            'users[0-9]' => 'Load fixtures users1, users2, ...',
        ];
    }
    
    public function showHelp()
    {
        parent::showHelp();
        $path = \E4u\Loader::getConfig()->get('fixtures_path', self::DEFAULT_PATH);
        echo sprintf("\nFixtures dir: %s\n", $path);
        return false;
    }
    
    public function execute()
    {
        $path = \E4u\Loader::getConfig()->get('fixtures_path', self::DEFAULT_PATH);
        
        $file = $this->getArgument(0);
        if (empty($file)) {
            $this->showHelp();
            return false;
        }

        $environment = \E4u\Loader::getEnvironment();
        if (($environment != 'test') && !$this->getOption('force')) {
            echo sprintf("WARNING: Not in TEST environment, your *%s* data\n".
                    "         will be modified by this function!!\n".
                    "         Use --force if you are sure to do it\n".
                    "         or --environment=test to switch to test environment.\n", $environment);
            return false;
        }

        $dir = rtrim($path.dirname($file), '/.').'/';
        $pattern = $dir.'*_'.basename($file).".php";
        $files = glob($pattern);
        if (!$files) {
            echo sprintf("No matching fixtures found (using: %s).\n", $pattern);
            return false;
        }
        
        foreach ($files as $filename) {
            $count = Fixture::load($filename);
            echo sprintf("- %-12s %3d entities loaded.\n", str_replace($path, '', $filename).':', $count);
        }

        return $this;
    }
}