<?php

namespace Mike42\GfxPhp\Codec\Common;

class DataBlobInputStream implements DataInputStream
{
    public function __construct(string $data)
    {
        $this -> data = $data;
        $this -> offset = 0;
    }

    public function read(int $bytes)
    {
        $chunk = $this -> peek($bytes);
        $this -> advance($bytes);
        return $chunk;
    }

    public function advance(int $bytes)
    {
        $this -> offset += $bytes;
    }

    public function peek(int $bytes)
    {
        $chunk = substr($this -> data, $this -> offset, $bytes);
        if ($chunk === false) {
            throw new \Exception("End of file reached, cannot retrieve more data.");
        }
        $read = strlen($chunk);
        if ($read !== $bytes) {
            throw new \Exception("Unexpected end of file, needed $bytes but read $read");
        }
        return $chunk;
    }

    public function isEof()
    {
        return $this -> offset >= strlen($this -> data);
    }
 
    public static function fromBlob(string $blob)
    {
        return new DataBlobInputStream($blob);
    }
    
    public static function fromFilename(string $filename)
    {
        $blob = file_get_contents($filename);
        if ($blob === false) {
            throw new \Exception($filename);
        }
        return self::fromBlob($blob);
    }
}
