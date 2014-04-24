<?php

namespace Jasny\ConsoleKit;

use ConsoleKit\Command, ConsoleKit\TextFormater, ConsoleKit\Utils, ConsoleKit\Colors;

/**
 * Displays list of commands and help for command.
 * 
 * @usage $0 help [command_name]
 * @arg command_name The command name
 * 
 * @example `$0 help`       Show a list of all commands
 * @example `$0 help help`  Displays help for the help command
 */
class HelpCommand extends Command
{
    /**
     * Execute command
     * 
     * @param array $args
     * @param array $options
     */
    public function execute(array $args, array $options = array())
    {
        if (empty($args)) {
            $this->showUsage();
            $this->showCommands();
        } else {
            $this->showHelp($args[0], Utils::get($args, 1));
        }
    }

    /**
     * Show usage
     */
    protected function showUsage()
    {
        $scriptName = basename($_SERVER['SCRIPT_FILENAME']);
        
        $this->writeln('Usage:', Colors::YELLOW);
        $this->writeln("  $scriptName command [arguments]\n");
    }
    
    /**
     * Show available options and commands
     */
    protected function showCommands()
    {
        $formater = new TextFormater(array('indent' => 2));
        $this->writeln('Available commands:', Colors::YELLOW);

        $rows = [];
        foreach ($this->console->getCommands() as $name => $fqdn) {
            $help = Help::fromFQDN($fqdn);
            $rows[] = array(Colors::colorize($name, Colors::GREEN), $help->getShortDescription());
        }
        $table = new Widgets\Table(null, $rows, array('border'=>false, 'frame'=>false));
        $this->writeln($formater->format($table->render()));
    }
    
    /**
     * Show help for a specific command
     * 
     * @param string $command
     * @param string $subCommand
     */
    public function showHelp($command, $subCommand = null)
    {
        $commandFQDN = $this->console->getCommand($command);
        $help = Help::fromFQDN($commandFQDN, $subCommand);
        $this->writeln($help . "\n");
    }
}
