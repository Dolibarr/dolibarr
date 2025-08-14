<?php
namespace Mike42\GfxPhp\Util;

class LzwEncodeDictionary extends AbstractLzwDictionary
{
    protected $encodeDict;
    
    public function clear()
    {
        $count = 2 << ($this -> minCodeSize - 1);
        $this -> encodeDict = array_flip(range(chr(0), chr($count - 1)));
        $this -> clearCode = $count;
        $count++;
        $this -> eodCode = $count;
        $count++;
        $this -> size = $count;
    }
    
    public function get(string $code)
    {
        if (!$this -> contains($code)) {
            throw new \Exception("LZW encode error; code sequence not in dictionary.");
        }
        return $this -> encodeDict[$code];
    }
    
    public function contains(string $code)
    {
        return isset($this -> encodeDict[$code]);
    }
    
    public function add(string $entry)
    {
        if ($this -> size == self::MAX_SIZE) {
            throw new \Exception("LZW code table overflow");
        }
        $this -> encodeDict[$entry] = $this -> size;
        $this -> size++;
    }
}
