<?php

namespace Jasny\ConsoleKit;

use ReflectionClass, ReflectionFunction;
use ConsoleKit\Utils, ConsoleKit\TextFormater, ConsoleKit\Colors, ConsoleKit\ConsoleException;

/**
 * Generates help messages based on information in doc comments.
 * Renders to symfony/console style output.
 * 
 * @ignore
 * @internal This is a general improvement to ConsoleKit and doesn't necessary belong to this project.
 */
class Help extends \ConsoleKit\Help
{
    /** @var string */
    protected $more;
    
    /** @var array */
    protected $examples;

    /**
     * Creates an Help object from a FQDN
     *
     * @param string $fqdn
     * @param string $subCommand
     * @return Help
     */
    public static function fromFQDN($fqdn, $subCommand = null)
    {
        if (function_exists($fqdn)) {
            return static::fromFunction($fqdn);
        }
        if (class_exists($fqdn) && is_subclass_of($fqdn, 'ConsoleKit\Command')) {
            return static::fromCommandClass($fqdn, $subCommand);
        }
        throw new ConsoleException("'$fqdn' is not a valid ConsoleKit FQDN");
    }
    
    /**
     * Creates an Help object from a function
     *
     * @param string $name
     * @return Help
     */
    public static function fromFunction($name)
    {
        $func = new ReflectionFunction($name);
        return new static($func->getDocComment());
    }
  
    /**
     * Creates an Help object from a class subclassing Command
     *
     * @param string $name
     * @param string $subCommand
     * @return Help
     */
    public static function fromCommandClass($name, $subCommand = null)
    {
        $prefix = 'execute';
        $class = new ReflectionClass($name);

        if ($subCommand) {
            $method = $prefix . ucfirst(Utils::camelize($subCommand));
            if (!$class->hasMethod($method)) {
                throw new ConsoleException("Sub command '$subCommand' of '$name' does not exist");
            }
            return new static($class->getMethod($method)->getDocComment());
        }

        $help = new static($class->getDocComment());
        foreach ($class->getMethods() as $method) {
            if (
                strlen($method->getName()) > strlen($prefix) && 
                substr($method->getName(), 0, strlen($prefix)) === $prefix
            ) {
                $name = Utils::dashized(substr($method->getName(), strlen($prefix)));
                $subhelp = new static($method->getDocComment());
                $help->subCommands[$name] = $subhelp->getShortDescription();
            }
        }
        return $help;
    }
    
    /**
     * Parse doc comments
     */
    protected function parse()
    {
        $scriptName = basename($_SERVER['SCRIPT_FILENAME']);
        
        $this->usage = '';
        $this->args = array();
        $this->options = array();
        $this->examples = array();

        $lines = explode("\n", substr(trim($this->text), 2, -2));
        $lines = array_map(function($v) { return ltrim(trim($v), '* '); }, $lines);

        $desc = array();
        foreach ($lines as $line) {
            if (preg_match('/@usage (.+)$/', $line, $matches)) {
                $this->usage = str_replace('$0', $scriptName, $matches[1]);
            } elseif (preg_match('/@arg ([^\s]+)(?:\s+(.*))?$/', $line, $matches)) {
                $this->args[$matches[1]] = isset($matches[2]) ? $matches[2] : '';
            } elseif (preg_match('/@opt (?:(?:--)?([a-zA-Z\-_0-9=]+))?(?:\s+-(\w))?(?:\s+(.*))?$/', $line, $matches)) {
                $matches += array(null, null, null, '');
                $this->options[] = array('opt'=>$matches[1], 'flag'=>$matches[2], 'desc'=>$matches[3]);
            } elseif (preg_match('/@flag -?([a-zA-Z0-9])(?:\s+(.*))?$/', $line, $matches)) {
                $matches += array(null, null, '');
                $this->options[] = array('opt'=>null, 'flag'=>$matches[1], 'desc'=>$matches[2]);
            } elseif (preg_match('/@example `([^`]+)`(?:\s+(.*))?$/', $line, $matches)) {
                $code = str_replace('$0', $scriptName, $matches[1]);
                $this->examples[] = array('code'=>$code, 'desc'=>isset($matches[2]) ? $matches[2] : '');
            } elseif (!preg_match('/^@([a-zA-Z\-_0-9]+)(.*)$/', $line)) {
                $desc[] = $line;
            }
        }
        
        $description = trim(implode("\n", $desc), "\n ");
        list($this->description, $this->more) = explode("\n\n", $description, 2) + array(null, null);
    }
    
    /**
     * @return string
     */
    public function getShortDescription()
    {
        return strtok($this->description, "\n");
    }
    
    /**
     * @return string
     */
    public function getMore()
    {
        return $this->more;
    }
    
    
    /**
     * @return string
     */
    public function render()
    {
        $output = "$this->description\n\n"
            . $this->renderUsage()
            . $this->renderArgs()
            . $this->renderOptions()
            . $this->renderSubcommands()
            . ($this->more ? "$this->more\n\n" : '')
            . $this->renderExamples();
        
        return trim($output, "\n ");
    }

    /**
     * Render usage
     * 
     * @return string
     */
    protected function renderUsage()
    {
        if (empty($this->usage)) return null;
        return Colors::colorize("Usage: \n", Colors::YELLOW) . "  {$this->usage}\n\n";
    }

    /**
     * Render arguments
     * 
     * @return string
     */
    protected function renderArgs()
    {
        if (empty($this->args)) return null;
        
        $rows = [];
        foreach ($this->args as $name => $desc) {
            $rows[] = array(Colors::colorize($name, Colors::GREEN), $desc);
        }
        $table = new Widgets\Table(null, $rows, array('border'=>false, 'frame'=>false));

        $formater = new TextFormater(array('indent' => 2));
        return Colors::colorize("Arguments:\n", Colors::YELLOW) . $formater->format($table->render()) . "\n";
    }
    
    /**
     * Render options
     * 
     * @return string
     */
    protected function renderOptions()
    {
        if (empty($this->options)) return null;
        
        $rows = array();
        foreach ($this->options as $option) {
            $rows[] = array(
                Colors::colorize($option['opt'] ? '--' . $option['opt'] : null, Colors::GREEN),
                Colors::colorize($option['flag'] ? '-' . $option['flag'] : null, Colors::GREEN),
                $option['desc']
            );
        }
        $table = new Widgets\Table(null, $rows, array('border'=>false, 'frame'=>false, 'skipEmpty'=>true));
        
        $formater = new TextFormater(array('indent' => 2));
        return Colors::colorize("Options:\n", Colors::YELLOW) . $formater->format($table->render()) . "\n";
    }

    /**
     * Render subcommands
     * 
     * @return string
     */
    protected function renderSubcommands()
    {
        if (empty($this->subCommands)) return null;
        
        $rows = array();
        foreach ($this->subCommands as $name => $desc) {
            $rows[] = array(Colors::colorize($name, Colors::GREEN), $desc);
        }
        $table = new Widgets\Table(null, $rows, array('border'=>false, 'frame'=>false));
        
        $formater = new TextFormater(array('indent' => 2));
        return Colors::colorize("Sub commands:\n", Colors::YELLOW) . $formater->format($table->render()) . "\n";
    }

    /**
     * Render examples
     * 
     * @return string
     */
    protected function renderExamples()
    {
        if (empty($this->examples)) return null;
        
        $output = Colors::colorize("Examples:\n", Colors::YELLOW);
        foreach ($this->examples as $example) {
            $output .= "  {$example['desc']}:\n" . Colors::colorize("    {$example['code']}", Colors::GREEN) . "\n\n";
        }
        
        return $output;
    }
}
