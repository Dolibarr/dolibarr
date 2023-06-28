<?php

declare(strict_types=1);

namespace Endroid\QrCode\Writer\Result;

use Endroid\QrCode\Matrix\MatrixInterface;

final class SvgResult extends AbstractResult
{
    public function __construct(
        MatrixInterface $matrix,
        private \SimpleXMLElement $xml,
        private bool $excludeXmlDeclaration = false
    ) {
        parent::__construct($matrix);
    }

    public function getXml(): \SimpleXMLElement
    {
        return $this->xml;
    }

    public function getString(): string
    {
        $string = $this->xml->asXML();

        if (!is_string($string)) {
            throw new \Exception('Could not save SVG XML to string');
        }

        if ($this->excludeXmlDeclaration) {
            $string = str_replace("<?xml version=\"1.0\"?>\n", '', $string);
        }

        return $string;
    }

    public function getMimeType(): string
    {
        return 'image/svg+xml';
    }
}
