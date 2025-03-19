<?php

namespace Mike42\Escpos\Experimental\Unifont;

interface ColumnFormatGlyphFactory
{
    public function getGlyph($codePoint);
}
