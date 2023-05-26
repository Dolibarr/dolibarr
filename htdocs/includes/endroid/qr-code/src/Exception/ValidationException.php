<?php

declare(strict_types=1);

namespace Endroid\QrCode\Exception;

final class ValidationException extends \Exception
{
    public static function createForUnsupportedWriter(string $writerClass): self
    {
        return new self(sprintf('Unable to validate the result: "%s" does not support validation', $writerClass));
    }

    public static function createForMissingPackage(string $packageName): self
    {
        return new self(sprintf('Please install "%s" or disable image validation', $packageName));
    }

    public static function createForInvalidData(string $expectedData, string $actualData): self
    {
        return new self('The validation reader read "'.$actualData.'" instead of "'.$expectedData.'". Adjust your parameters to increase readability or disable validation.');
    }
}
