<?php

namespace Jasny\ConsoleKit;

use ConsoleKit\TextFormater, ConsoleKit\Utils, ConsoleKit\Colors;

/**
 * Displays help.
 * 
 * @usage $0 help [command_name]
 * @arg command_name The command name
 * 
 * @example `$0 help`         Show a list of all commands
 * @example `$0 help status`  Displays help for the status command
 */
class HelpCommand extends Command
{
    public function execute(array $args, array $options = array())
    {
        if (!empty($options['v']) || !empty($options['version'])) {
            $this->showVersion();
            return;
        }
        
        if (empty($args)) {
            $this->showCommands();
        } else {
            $commandFQDN = $this->console->getCommand($args[0]);
            $help = Help::fromFQDN($commandFQDN, Utils::get($args, 1));
            $this->writeln($help);
        }
    }
    
    /**
     * Display the application version.
     */
    protected function showVersion()
    {
        $this->writeln(\Jasny\DBVC::VERSION);
    }


    /**
     * Show available options and commands
     */
    protected function showCommands()
    {
        $formater = new TextFormater(array('quote' => ' * '));
        $this->writeln('Available commands:', Colors::BLACK | Colors::BOLD);
        foreach ($this->console->getCommands() as $name => $fqdn) {
            if ($fqdn !== __CLASS__) {
                $this->writeln($formater->format($name));
            }
        }
        
        $scriptName = basename($_SERVER['SCRIPT_FILENAME']);
        $this->writeln("Use './$scriptName help command' for more info");
}
}
