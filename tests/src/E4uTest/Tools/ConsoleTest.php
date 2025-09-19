<?php
namespace E4uTest\Tools;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use E4u\Tools\Console;
use Laminas\Config\Config;

#[CoversClass(Console::class)]
class ConsoleTest extends TestCase
{
    protected Console $console;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->console = new Console(new Config([]));
    }

    public function testGetCurrentCommand()
    {
        $_SERVER['argv'] = [ 'tools\console', 'xxx' ];
        $this->assertInstanceOf('E4u\Tools\Console\Help', $this->console->getCurrentCommand(true));
        
        $_SERVER['argv'] = [ 'tools\console', 'version' ];
        $this->assertInstanceOf('E4u\Tools\Console\Version', $this->console->getCurrentCommand(true));
    }
    
    public function testRun()
    {
        $_SERVER['argv'] = [ 'tools\console', 'version' ];
        
        ob_start();
            $this->console->run();
            $output = ob_get_contents();
        ob_end_clean();
        $this->assertStringContainsString(\E4u\Version::VERSION, $output);
    }

    public function testAddCommand()
    {
        $this->console->addCommand(Console\Help::class, 'test_1');
        $this->console->addCommand(new Console\Help, 'test_2');
        
        $commands = $this->console->getCommands();
        $this->assertInstanceOf(Console\Help::class, $commands['test_1']);
        $this->assertInstanceOf(Console\Help::class, $commands['test_2']);
    }

    public function testShowHelp()
    {
        ob_start();
            $this->console->showHelp('version');
            $output = ob_get_contents();
        ob_end_clean();
        $this->assertStringContainsString('current version', $output);
    }

    public function testGetCommands()
    {
        $commands = $this->console->getCommands();
        foreach ($commands as $command) {
            $this->assertInstanceOf(Console\Command::class, $command);
        }
    }
}
