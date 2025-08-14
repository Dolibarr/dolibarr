<?php
namespace Mike42\GfxPhp\Codec;

class ImageCodec
{
    protected static $instance = null;

    protected $encoders;

    protected $decoders;

    public function __construct(array $encoders, array $decoders)
    {
        $this -> encoders = $encoders;
        $this -> decoders = $decoders;
    }

    public function identify(string $blob) : string
    {
        foreach ($this -> decoders as $decoder) {
            $identity = $decoder -> identify($blob);
            if ($identity !== '') {
                return $identity;
            }
        }
        return '';
    }

    public function getDecoderForFormat(string $format) : ImageDecoder
    {
        $format = strtolower($format);
        foreach ($this -> decoders as $decoder) {
            if (array_search($format, $decoder -> getDecodeFormats(), true) !== false) {
                return $decoder;
            }
        }
        throw new \Exception("No decoder for format $format");
    }

    public function getEncoderForFormat(string $format) : ImageEncoder
    {
        $format = strtolower($format);
        foreach ($this -> encoders as $encoder) {
            if (array_search($format, $encoder -> getEncodeFormats(), true) !== false) {
                return $encoder;
            }
        }
        throw new \Exception("No encoder for format '$format'");
    }
    
    public static function getInstance() : ImageCodec
    {
        if (self::$instance  === null) {
            $encoders = [
                PnmCodec::getInstance(),
                BmpCodec::getInstance(),
                PngCodec::getInstance(),
                GifCodec::getInstance(),
                WbmpCodec::getInstance()
            ];
            $decoders = [
                PngCodec::getInstance(),
                GifCodec::getInstance(),
                BmpCodec::getInstance(),
                PnmCodec::getInstance(),
                WbmpCodec::getInstance()
            ];
            self::$instance = new ImageCodec($encoders, $decoders);
        }
        return self::$instance;
    }
}
