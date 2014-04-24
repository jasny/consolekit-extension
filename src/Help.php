<?php

namespace Jasny\ConsoleKit;

use ReflectionClass, ReflectionFunction;
use ConsoleKit\Utils, ConsoleKit\TextFormater, ConsoleKit\Colors;

/**
 * Generates help messages based on information in doc comments.
 * Renders to symfony/console style output.
 * 
 * @ignore
 * @internal This is a general improvement to ConsoleKit and doesn't necessary belong to this project.
 */
class Help extends \ConsoleKit\Help
{
    /** @var array */
    protected $examples;

    
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
        $this->usage = '';
        $this->args = array();
        $this->options = array();
        $this->examples = array();

        $lines = explode("\n", substr(trim($this->text), 2, -2));
        $lines = array_map(function($v) { return ltrim(trim($v), '* '); }, $lines);

        $desc = array();
        foreach ($lines as $line) {
            if (preg_match('/@usage (.+)$/', $line, $matches)) {
                $this->usage = str_replace('$0', self::getArgv0(), $matches[1]);
            } elseif (preg_match('/@arg ([^\s]+)(?:\s+(.*))?$/', $line, $matches)) {
                $this->args[$matches[1]] = isset($matches[2]) ? $matches[2] : '';
            } elseif (preg_match('/@opt (?:(?:--)?([a-zA-Z\-_0-9=]+))?(?:\s+-(\w))?(?:\s+(.*))?$/', $line, $matches)) {
                $matches += array(null, null, null, '');
                $this->options[] = array('opt'=>$matches[1], 'flag'=>$matches[2], 'desc'=>$matches[3]);
            } elseif (preg_match('/@flag -?([a-zA-Z0-9])(?:\s+(.*))?$/', $line, $matches)) {
                $matches += array(null, null, '');
                $this->options[] = array('opt'=>null, 'flag'=>$matches[1], 'desc'=>$matches[2]);
            } elseif (preg_match('/@example `([^`]+)`(?:\s+(.*))?$/', $line, $matches)) {
                $code = str_replace('$0', self::getArgv0(), $matches[1]);
                $this->examples[] = array('code'=>$code, 'desc'=>isset($matches[2]) ? $matches[2] : '');
            } elseif (!preg_match('/^@([a-zA-Z\-_0-9]+)(.*)$/', $line)) {
                $desc[] = $line;
            }
        }
        
        $this->description = trim(implode("\n", $desc), "\n ");
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
    public function render()
    {
        $indent = new TextFormater(array('indent'=>1));
            
        $output = "{$this->description}\n\n";
        if (!empty($this->usage)) {
            $output .= Colors::colorize("Usage: \n", Colors::YELLOW) . "{$this->usage}\n\n";
        }
        if (!empty($this->args)) {
            $output .= Colors::colorize("Arguments:\n", Colors::YELLOW);
            $rows = [];
            foreach ($this->args as $name => $desc) {
                $rows[] = array($name, $desc);
            }
            $table = new Widgets\Table(null, $rows, array('border'=>false, 'frame'=>false));
            $output .= $indent->format($table->render()) . "\n";
        }
        if (!empty($this->options)) {
            $output .= Colors::colorize("Options:\n", Colors::YELLOW);
            $rows = array();
            foreach ($this->options as $option) {
                $rows[] = array(
                    $option['opt'] ? '--' . $option['opt'] : null,
                    $option['flag'] ? '-' . $option['flag'] : null,
                    $option['desc']
                );
            }
            $table = new Widgets\Table(null, $rows, array('border'=>false, 'frame'=>false, 'skipEmpty'=>true));
            $output .= $indent->format($table->render()) . "\n";
        }
        if (!empty($this->subCommands)) {
            $output .= Colors::colorize("Sub commands:\n", Colors::YELLOW);
            $rows = array();
            foreach ($this->subCommands as $name=>$desc) {
                $rows[] = array($name, $desc);
            }
            $table = new Widgets\Table(null, $rows, array('border'=>false, 'frame'=>false));
            $output .= $indent->format($table->render()) . "\n";
        }
        if (!empty($this->examples)) {
            $output .= Colors::colorize("Help:\n", Colors::YELLOW);
            foreach ($this->example as $example) {
                if ($example['desc']) $output .= " {$example['desc']}:\n\n";
                if ($example['code']) $output .= Colors::colorize("  {$example['code']}\n\n", Colors::GREEN);
            }
        }
        return trim($output, "\n ");
    }
    
    
    /**
     * Get executed command
     * 
     * @return string
     */
    public static function getArgv0()
    {
        return $_SERVER['argv'][0];
    }
}
