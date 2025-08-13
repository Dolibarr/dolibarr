<?php


namespace Mike42\GfxPhp\Codec\Bmp;

use Exception;
use Mike42\GfxPhp\Codec\Common\DataInputStream;

class BmpFileHeader
{
    const FILE_HEADER_SIZE = 14;

    public $offset;
    public $size;

    public function __construct(string $fileType, int $size, int $offset)
    {
        $this -> fileType = $fileType;
        $this -> size = $size;
        $this -> offset = $offset;
    }

    public static function fromBinary(DataInputStream $data) : BmpFileHeader
    {
        $fileType = $data->read(2);
        if (array_search($fileType, ["BM", "BA", "CI", "CP", "IC", "PT", "OS"]) === false) {
            throw new Exception("Not a bitmap image");
        }
        $fileHeaderData = $data->read(12);
        $fields = unpack("Vsize/vreserved1/vreserved2/Voffset", $fileHeaderData);
        return new BmpFileHeader($fileType, $fields['size'], $fields['offset']);
    }
}
