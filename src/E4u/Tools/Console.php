<?php
namespace E4u\Tools;

use Zend\Console\Getopt,
    Zend\Config\Config,
    E4u\Tools\Console\Command;

class Console
{
    protected $commands = [];

    /**
     * @var Command
     */
    protected $currentCommand;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @todo  Use config to setup additional commands
     * @param Config $config
     */
    public function __construct($config)
    {
        if (!$config instanceof Config) {
            $config = new Config((array)$config);
        }

        $this->config = $config;
        $this->addDefaultCommands();
    }

    /**
     * @todo Obsługa --help|-h
     * @return Console  Current instance
     */
    public function run()
    {
        // default Getopt rules
        $rules = [
            'help|h' => 'Help on command.',
            'dump-sql' => 'Show all SQL queries.',
            'environment|env=s' => 'Set environment for the command.',
            'test' => 'Set TEST environment for the command.',
        ];

        $getopt = new Getopt($rules, null, [ 'freeformFlags' => true ]);
        $arguments = array_slice($getopt->getRemainingArgs(), 1);

        // setup ENVIRONMENT for the application
        $environment = $getopt->getOption('environment');
        if ($getopt->getOption('test')) {
            $environment = 'test';
        }

        if (!empty($environment)) {
            $envConfig = \E4u\Loader::load("environment/$environment");
            $this->config->merge(new Config($envConfig));
            $this->config->environment = $environment;
        }

        // setup simple SQL logger
        if ($getopt->getOption('dump-sql')) {
            $ormConfig = \E4u\Loader::getConnection()->getConfiguration();
            $ormConfig->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger());
        }

        // run command
        $command = $this->getCurrentCommand();
        $command->configure($arguments, $getopt);

        $title = sprintf("E4u command line tool - version %s (%s).", \E4u\Version::VERSION, \E4u\Loader::getEnvironment());
        cli_set_process_title($title);
        echo $title."\n\n";

        return $command->execute();
    }

    /**
     * @param  Command|string $command
     * @param  string $name
     * @return Console  Current instance
     */
    public function addCommand($command, $name)
    {
        if (is_string($command)) {
            $command = new $command;
        }

        if (!$command instanceof Command) {
            throw new \E4u\Exception\LogicException(
                sprintf("Command is expected to be E4u\Tools\Console\Command, %s given.",
                \E4u\Common\Variable::getType($command)));
        }

        $command->setConsole($this);
        $this->commands[$name] = $command;
        return $this;
    }

    /**
     * @param string|Command $command
     */
    public function showHelp($command)
    {
        if ($command instanceof Command) {
            $command = array_search($command, $this->commands);
        }

        $help = $this->commands[$command]->help();
        if (null === $help) {
            return;
        }

        if (!is_array($help)) {
            $help = [ $help ];
        }

        foreach ($help as $key => $message) {
            $cmd = $command;
            if (is_string($key)) {
                $cmd .= ' '.$key;
            }

            echo sprintf("  %-25s %s\n", $cmd, $message);
        }
    }

    /**
     *
     * @return array of Command instances
     */
    public function getCommands()
    {
        return $this->commands;
    }

    /**
     * @return Console  Current instance
     */
    protected function addDefaultCommands()
    {
        $commands = $this->config->get('console');
        $defaultCommands = array_merge(
            $commands ? $commands->toArray() : [], [
            'help'              => Console\Help::class,
            'version'           => Console\Version::class,
            'start'             => Console\Start::class,
            'fixtures:load'     => Console\Fixtures\Load::class,
            'tests:generate'    => Console\Tests\Generate::class,
            'tests:run'         => Console\Tests\Run::class,
            'tests:run:all'     => Console\Tests\RunAll::class,
            'routes:test'       => Console\Routes\Test::class,
        ]);

        foreach ($defaultCommands as $name => $command) {
            $this->addCommand($command, $name);
        }

        return $this;
    }

    /**
     * @param  bool $force
     * @return Command
     */
    public function getCurrentCommand($force = false)
    {
        if ((null === $this->currentCommand) || $force) {
            $command = $this->serverCommand();
            if (empty($command)) {
                $command = 'help';
            }
            elseif (!isset($this->commands[$command])) {
                $command = 'help';
            }

            $this->currentCommand = $this->commands[$command];
        }

        return $this->currentCommand;
    }

    /**
     * @return string
     */
    protected function serverCommand()
    {
        if (!isset($_SERVER['argv'])) {
            $errorDescription = (ini_get('register_argc_argv') == false)
                ? "argv is not available, because ini option 'register_argc_argv' is set Off"
                : '$_SERVER["argv"] is not set, but E4u\Tools\Console cannot work without this information.';
            throw new \InvalidArgumentException($errorDescription);
        }

        $argv = $_SERVER['argv'];
        $script = array_shift($argv);
        foreach ($argv as $arg) {
            if (strpos($arg, '-') !== 0) {
                return $arg;
            }
        }

        return null;
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }
}