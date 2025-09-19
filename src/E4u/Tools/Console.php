<?php
namespace E4u\Tools;

use E4u\Common\Variable;
use E4u\Exception\LogicException;
use E4u\Loader;
use E4u\Tools\Console\Getopt;
use E4u\Version;
use Laminas\Config\Config,
    E4u\Tools\Console\Command;

class Console
{
    protected array $commands = [];

    protected Command $currentCommand;

    protected Config $config;

    /**
     * @todo  Use config to setup additional commands
     */
    public function __construct(mixed $config)
    {
        if (!$config instanceof Config) {
            $config = new Config((array)$config);
        }

        $this->config = $config;
        $this->addDefaultCommands();
    }

    public function run(): void
    {
        // default Getopt rules
        $rules = [
            'help|h' => 'Help on command.',
            'dump-sql' => 'Show all SQL queries.',
            'environment|env=s' => 'Set environment for the command.',
            'test' => 'Set TEST environment for the command.',
        ];

        $getopt = new Getopt($rules, null, [ Getopt::CONFIG_FREEFORM_FLAGS => true ]);
        $arguments = array_slice($getopt->getRemainingArgs(), 1);

        // setup ENVIRONMENT for the application
        $environment = $getopt->getOption('environment');
        if ($getopt->getOption('test')) {
            $environment = 'test';
        }

        if (!empty($environment)) {
            $envConfig = Loader::load("environment/$environment");
            $this->config->merge(new Config($envConfig));
            $this->config->environment = $environment;
        }

        // run command
        $command = $this->getCurrentCommand();
        $command->configure($arguments, $getopt);

        $title = sprintf("E4u command line tool - version %s (%s).", Version::VERSION, Loader::getEnvironment());
        cli_set_process_title('console ' . $this->serverCommand());
        echo $title."\n\n";

        $command->execute();
    }

    public function addCommand(Command|string $command, string $name): static
    {
        if (is_string($command)) {
            $command = new $command;
        }

        if (!$command instanceof Command) {
            throw new LogicException(
                sprintf("Command is expected to be E4u\Tools\Console\Command, %s given.",
                Variable::getType($command)));
        }

        $command->setConsole($this);
        $this->commands[ $name ] = $command;
        return $this;
    }

    public function showHelp(string|Command $command): void
    {
        if ($command instanceof Command) {
            $command = array_search($command, $this->commands);
        }

        $help = $this->commands[ $command ]->help();
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
     * @return Command[]
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    protected function addDefaultCommands(): static
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

    public function getCurrentCommand(bool $force = false): Command
    {
        if (!isset($this->currentCommand) || $force) {
            $command = $this->serverCommand();
            if (empty($command) || !isset($this->commands[ $command ])) {
                $command = 'help';
            }

            $this->currentCommand = $this->commands[ $command ];
        }

        return $this->currentCommand;
    }

    protected function serverCommand(): ?string
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
            if (!str_starts_with($arg, '-')) {
                return $arg;
            }
        }

        return null;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    public static function countProcesses(string $name): ?int
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return null;
        }

        exec(sprintf("pgrep -f '%s' | grep -v pgrep", $name), $pids);
        return count($pids);
    }
}