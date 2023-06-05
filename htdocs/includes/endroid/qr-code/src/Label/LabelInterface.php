<?php

declare(strict_types=1);

namespace Endroid\QrCode\Label;

use Endroid\QrCode\Color\ColorInterface;
use Endroid\QrCode\Label\Alignment\LabelAlignmentInterface;
use Endroid\QrCode\Label\Font\FontInterface;
use Endroid\QrCode\Label\Margin\MarginInterface;

interface LabelInterface
{
    public function getText(): string;

    public function getFont(): FontInterface;

    public function getAlignment(): LabelAlignmentInterface;

    public function getMargin(): MarginInterface;

    public function getTextColor(): ColorInterface;
}
