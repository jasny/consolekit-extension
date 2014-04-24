<?php

namespace Jasny\ConsoleKit\Widgets;

use ConsoleKit\TextWriter,
    ConsoleKit\ConsoleException;

class Table extends AbstractWidget
{
    /** @var array */
    protected $values;

    /** @var boolean */
    protected $headers;

    /** @var boolean */
    protected $border;

    /** @var boolean */
    protected $frame;

    /** @var boolean */
    protected $skipEmpty;

    
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
        $output = "";
        
        if ($this->border) {
            if ($this->frame) $output .= "+";
            
            foreach ($widths as $i=>$width) {
                if ($this->border || $i > 0) $width++;
                if ($this->border || $i < count($widths) -1) $width++;
                
                $output .= str_repeat('-', $width);
                if ($this->frame) $output .= "+";
            }
        }

        return $output . "\n";
    }
    
    /**
     * Get sprintf format for a row
     * 
     * @param array $widths
     */
    protected function getRowFormat(array $widths)
    {
        $padding = $this->frame ? " " : "";
        $spacing = $this->frame ? "" : "\t";
        $format = ""; 
        
        foreach ($widths as $i=>$width) {
            if ($this->skipEmpty && $width === 0) continue;
            
            if ($format) $format .= $spacing;
            $format .= "{$padding}%$i\$-{$width}s{$padding}";
        }

        if ($this->border) $format = "|" . ($this->frame ? "" : " ") . $format . ($this->frame ? "" : " ") . "|";
        
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
