<?php
namespace Mike42\GfxPhp\Util;

abstract class AbstractLzwDictionary
{
    const MAX_SIZE = 4096;
    
    protected $minCodeSize;
    protected $clearCode;
    protected $eodCode;
    protected $size;
    
    /**
     * @return number
     */
    public function getClearCode()
    {
        return $this->clearCode;
    }
    
    /**
     * @return number
     */
    public function getEodCode()
    {
        return $this->eodCode;
    }
    
    public function __construct(int $minCodeSize)
    {
        $this -> minCodeSize = $minCodeSize;
        $this -> clear();
    }
    
    public function getSize()
    {
        return $this -> size;
    }
    
    abstract public function clear();
    
    abstract public function add(string $entry);
}
