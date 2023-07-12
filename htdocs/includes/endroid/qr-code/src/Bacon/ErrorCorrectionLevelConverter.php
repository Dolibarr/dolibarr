<?php

declare(strict_types=1);

namespace Endroid\QrCode\Bacon;

use BaconQrCode\Common\ErrorCorrectionLevel;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelInterface;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelLow;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelMedium;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelQuartile;

final class ErrorCorrectionLevelConverter
{
    public static function convertToBaconErrorCorrectionLevel(ErrorCorrectionLevelInterface $errorCorrectionLevel): ErrorCorrectionLevel
    {
        if ($errorCorrectionLevel instanceof ErrorCorrectionLevelLow) {
            return ErrorCorrectionLevel::valueOf('L');
        } elseif ($errorCorrectionLevel instanceof ErrorCorrectionLevelMedium) {
            return ErrorCorrectionLevel::valueOf('M');
        } elseif ($errorCorrectionLevel instanceof ErrorCorrectionLevelQuartile) {
            return ErrorCorrectionLevel::valueOf('Q');
        } elseif ($errorCorrectionLevel instanceof ErrorCorrectionLevelHigh) {
            return ErrorCorrectionLevel::valueOf('H');
        }

        throw new \Exception('Error correction level could not be converted');
    }
}
