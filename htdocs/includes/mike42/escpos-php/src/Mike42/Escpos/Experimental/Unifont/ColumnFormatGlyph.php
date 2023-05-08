<?php

namespace Mike42\Escpos\Experimental\Unifont;

class ColumnFormatGlyph
{
    public $width;
    public $data;

    public function segment(int $maxWidth)
    {
        if ($this->width <= $maxWidth) {
            return [$this];
        }
        $dataChunks = str_split($this->data, $maxWidth * 3);
        $ret = [];
        foreach ($dataChunks as $chunk) {
            $g = new ColumnFormatGlyph();
            $g->data = $chunk;
            $g->width = strlen($chunk) / 3;
            $ret[] = $g;
        }
        return $ret;
    }
}
