<?php

declare(strict_types=1);

namespace Endroid\QrCode\Writer;

use Endroid\QrCode\Bacon\MatrixFactory;
use Endroid\QrCode\ImageData\LogoImageData;
use Endroid\QrCode\Label\LabelInterface;
use Endroid\QrCode\Logo\LogoInterface;
use Endroid\QrCode\QrCodeInterface;
use Endroid\QrCode\Writer\Result\ResultInterface;
use Endroid\QrCode\Writer\Result\SvgResult;

final class SvgWriter implements WriterInterface
{
    public const DECIMAL_PRECISION = 10;
    public const WRITER_OPTION_BLOCK_ID = 'block_id';
    public const WRITER_OPTION_EXCLUDE_XML_DECLARATION = 'exclude_xml_declaration';
    public const WRITER_OPTION_EXCLUDE_SVG_WIDTH_AND_HEIGHT = 'exclude_svg_width_and_height';
    public const WRITER_OPTION_FORCE_XLINK_HREF = 'force_xlink_href';

    public function write(QrCodeInterface $qrCode, LogoInterface|null $logo = null, LabelInterface|null $label = null, array $options = []): ResultInterface
    {
        if (!isset($options[self::WRITER_OPTION_BLOCK_ID])) {
            $options[self::WRITER_OPTION_BLOCK_ID] = 'block';
        }

        if (!isset($options[self::WRITER_OPTION_EXCLUDE_XML_DECLARATION])) {
            $options[self::WRITER_OPTION_EXCLUDE_XML_DECLARATION] = false;
        }

        if (!isset($options[self::WRITER_OPTION_EXCLUDE_SVG_WIDTH_AND_HEIGHT])) {
            $options[self::WRITER_OPTION_EXCLUDE_SVG_WIDTH_AND_HEIGHT] = false;
        }

        $matrixFactory = new MatrixFactory();
        $matrix = $matrixFactory->create($qrCode);

        $xml = new \SimpleXMLElement('<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"/>');
        $xml->addAttribute('version', '1.1');
        if (!$options[self::WRITER_OPTION_EXCLUDE_SVG_WIDTH_AND_HEIGHT]) {
            $xml->addAttribute('width', $matrix->getOuterSize().'px');
            $xml->addAttribute('height', $matrix->getOuterSize().'px');
        }
        $xml->addAttribute('viewBox', '0 0 '.$matrix->getOuterSize().' '.$matrix->getOuterSize());
        $xml->addChild('defs');

        $blockDefinition = $xml->defs->addChild('rect');
        $blockDefinition->addAttribute('id', strval($options[self::WRITER_OPTION_BLOCK_ID]));
        $blockDefinition->addAttribute('width', number_format($matrix->getBlockSize(), self::DECIMAL_PRECISION, '.', ''));
        $blockDefinition->addAttribute('height', number_format($matrix->getBlockSize(), self::DECIMAL_PRECISION, '.', ''));
        $blockDefinition->addAttribute('fill', '#'.sprintf('%02x%02x%02x', $qrCode->getForegroundColor()->getRed(), $qrCode->getForegroundColor()->getGreen(), $qrCode->getForegroundColor()->getBlue()));
        $blockDefinition->addAttribute('fill-opacity', strval($qrCode->getForegroundColor()->getOpacity()));

        $background = $xml->addChild('rect');
        $background->addAttribute('x', '0');
        $background->addAttribute('y', '0');
        $background->addAttribute('width', strval($matrix->getOuterSize()));
        $background->addAttribute('height', strval($matrix->getOuterSize()));
        $background->addAttribute('fill', '#'.sprintf('%02x%02x%02x', $qrCode->getBackgroundColor()->getRed(), $qrCode->getBackgroundColor()->getGreen(), $qrCode->getBackgroundColor()->getBlue()));
        $background->addAttribute('fill-opacity', strval($qrCode->getBackgroundColor()->getOpacity()));

        for ($rowIndex = 0; $rowIndex < $matrix->getBlockCount(); ++$rowIndex) {
            for ($columnIndex = 0; $columnIndex < $matrix->getBlockCount(); ++$columnIndex) {
                if (1 === $matrix->getBlockValue($rowIndex, $columnIndex)) {
                    $block = $xml->addChild('use');
                    $block->addAttribute('x', number_format($matrix->getMarginLeft() + $matrix->getBlockSize() * $columnIndex, self::DECIMAL_PRECISION, '.', ''));
                    $block->addAttribute('y', number_format($matrix->getMarginLeft() + $matrix->getBlockSize() * $rowIndex, self::DECIMAL_PRECISION, '.', ''));
                    $block->addAttribute('xlink:href', '#'.$options[self::WRITER_OPTION_BLOCK_ID], 'http://www.w3.org/1999/xlink');
                }
            }
        }

        $result = new SvgResult($matrix, $xml, boolval($options[self::WRITER_OPTION_EXCLUDE_XML_DECLARATION]));

        if ($logo instanceof LogoInterface) {
            $this->addLogo($logo, $result, $options);
        }

        return $result;
    }

    /** @param array<string, mixed> $options */
    private function addLogo(LogoInterface $logo, SvgResult $result, array $options): void
    {
        $logoImageData = LogoImageData::createForLogo($logo);

        if (!isset($options[self::WRITER_OPTION_FORCE_XLINK_HREF])) {
            $options[self::WRITER_OPTION_FORCE_XLINK_HREF] = false;
        }

        $xml = $result->getXml();

        /** @var \SimpleXMLElement $xmlAttributes */
        $xmlAttributes = $xml->attributes();

        $x = intval($xmlAttributes->width) / 2 - $logoImageData->getWidth() / 2;
        $y = intval($xmlAttributes->height) / 2 - $logoImageData->getHeight() / 2;

        $imageDefinition = $xml->addChild('image');
        $imageDefinition->addAttribute('x', strval($x));
        $imageDefinition->addAttribute('y', strval($y));
        $imageDefinition->addAttribute('width', strval($logoImageData->getWidth()));
        $imageDefinition->addAttribute('height', strval($logoImageData->getHeight()));
        $imageDefinition->addAttribute('preserveAspectRatio', 'none');

        if ($options[self::WRITER_OPTION_FORCE_XLINK_HREF]) {
            $imageDefinition->addAttribute('xlink:href', $logoImageData->createDataUri(), 'http://www.w3.org/1999/xlink');
        } else {
            $imageDefinition->addAttribute('href', $logoImageData->createDataUri());
        }
    }
}
