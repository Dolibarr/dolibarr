<?php


namespace Mike42\GfxPhp\Codec\Gif;

class GifSpecialPurposeBlock
{

    private $applicationExt;
    private $commentExt;

    public function __construct(GifApplicationExt $applicationExt = null, GifCommentExt $commentExt = null)
    {
        $this->applicationExt = $applicationExt;
        $this->commentExt = $commentExt;
    }

    public function getApplicationExt()
    {
        return $this->applicationExt;
    }

    public function getCommentExt()
    {
        return $this->commentExt;
    }
}
