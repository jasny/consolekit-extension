<?php

namespace Jasny\ConsoleKit\Widgets;

use ConsoleKit\Widgets\AbstractWidget, ConsoleKit\TextWriter, ConsoleKit\ConsoleException;

class Table extends AbstractWidget
{
    /** @var array */
    protected $values;

    /** @var boolean */
    protected $headers = false;

    /** @var boolean */
    protected $border = true;

    /** @var boolean */
    protected $frame = true;

    /** @var boolean */
    protected $skipEmpty = false;

    
    /**
     * @param TextWriter $writer
     * @param array      $values
     * @param array      $options
     */
    public function __construct(TextWriter $writer = null, array $values = array(), array $options = array())
    {
        $this->textWriter = $writer;
        $this->values = $values;
        
        foreach ($options as $opt=>$value) {
            $this->$opt = $value;
        }
    }

    /**
     * Set table values
     * 
     * @param array $values  2 dimensional array
     * @return Table
     */
    public function setValues($values)
    {
        $this->values = $values;
        return $this;
    }

    /**
     * Get table values
     * 
     * @return array
     */
    public function getValues()
    {
        return $this->text;
    }

    /**
     * Get/set if first row contains header
     * 
     * @param boolean $enable
     * @return boolean
     */
    public function useHeaders($enable = null)
    {
        if (isset($enable)) $this->headers = $enable;
        return $this->headers;
    }
    
    /**
     * Get/set if border should be drawn
     * 
     * @param boolean $enable
     * @return boolean
     */
    public function useBorder($enable = null)
    {
        if (isset($enable)) $this->border = $enable;
        return $this->border;
    }

    /**
     * Get/set if frame should be drawn
     * 
     * @param boolean $enable
     * @return boolean
     */
    public function useFrame($enable = null)
    {
        if (isset($enable)) $this->frame = $enable;
        return $this->frame;
    }
    

    /**
     * Get/set if frame should skip empty rows
     * 
     * @param boolean $enable
     * @return boolean
     */
    public function skipEmpty($enable = null)
    {
        if (isset($enable)) $this->skipEmpty = $enable;
        return $this->skipEmpty;
    }
    
    
    /**
     * Render the table
     * 
     * @return string
     */
    public function render()
    {
        $widths = array();
        foreach ($this->values as $row) {
            foreach ($row as $i=>$value) {
                if (!isset($widths[$i]) || strlen($value) > $widths[$i]) {
                    $widths[$i] = strlen($value);
                }
            }
        }


        $format = $this->getRowFormat($widths);
        $output = "";
        
        if ($this->border) $output .= $this->renderBorder($widths);
        
        foreach ($this->values as $i=>$row) {
            $output .= vsprintf($format, $row);
            if ($i === 0 && $this->headers) $output .= $this->renderBorder($widths);
        }
        
        if ($this->border) $output .= $this->renderBorder($widths);
        
        return $output;
    }
    
    /**
     * Render horizontal border
     * 
     * @param array $widths
     * @return string
     */
    protected function renderBorder(array $widths)
    {
        if (!$this->frame && !$this->border) return "\n";
        
        $output = "+";
        
        foreach ($widths as $i=>$width) {
            if ($this->skipEmpty && $width === 0) continue;
            
            if ($this->frame) {
                $output .= str_repeat('-', $width + 2) . "+";
            } elseif ($this->border) {
                if ($output === '+') $width += 1;
                $output .= str_repeat('-', $width + 8 - ($width % 8));
            }
        }
        
        if (!$this->frame) $output = substr_replace($output, "+", -1);

        return $output . "\n";
    }
    
    /**
     * Get sprintf format for a row
     * 
     * @param array $widths
     */
    protected function getRowFormat(array $widths)
    {
        $format = ""; 
        
        foreach ($widths as $i=>$width) {
            if ($this->skipEmpty && $width === 0) continue;
            
            if (!$this->frame && $format) $format .= "\t";
            if ($this->frame) $format .= " ";
            $format .= "%" . ($i+1) . "\$-" . $width . "s";
            if ($this->frame) $format .= " |";
        }

        if ($this->border) $format = "|" . ($this->frame ? "" : " ") . $format . ($this->frame ? "" : "\t|");
        
        return $format . "\n";
    }

    
    /**
     * Output table
     * 
     * @return Table
     * @throws ConsoleException
     */
    public function write()
    {
        if ($this->textWriter === null) {
            throw new ConsoleException('No TextWriter object specified');
        }
        $this->textWriter->write($this->render());
        return $this;
    }

    /**
     * Cast widget to string
     * 
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }
}
