<?php

declare(strict_types=1);

namespace Endroid\QrCode\Label;

use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Color\ColorInterface;
use Endroid\QrCode\Label\Alignment\LabelAlignmentCenter;
use Endroid\QrCode\Label\Alignment\LabelAlignmentInterface;
use Endroid\QrCode\Label\Font\Font;
use Endroid\QrCode\Label\Font\FontInterface;
use Endroid\QrCode\Label\Margin\Margin;
use Endroid\QrCode\Label\Margin\MarginInterface;

final class Label implements LabelInterface
{
    private FontInterface $font;
    private LabelAlignmentInterface $alignment;
    private MarginInterface $margin;
    private ColorInterface $textColor;

    public function __construct(
        private string $text,
        FontInterface|null $font = null,
        LabelAlignmentInterface|null $alignment = null,
        MarginInterface|null $margin = null,
        ColorInterface|null $textColor = null
    ) {
        $this->font = $font ?? new Font(__DIR__.'/../../assets/noto_sans.otf', 16);
        $this->alignment = $alignment ?? new LabelAlignmentCenter();
        $this->margin = $margin ?? new Margin(0, 10, 10, 10);
        $this->textColor = $textColor ?? new Color(0, 0, 0);
    }

    public static function create(string $text): self
    {
        return new self($text);
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function setText(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function getFont(): FontInterface
    {
        return $this->font;
    }

    public function setFont(FontInterface $font): self
    {
        $this->font = $font;

        return $this;
    }

    public function getAlignment(): LabelAlignmentInterface
    {
        return $this->alignment;
    }

    public function setAlignment(LabelAlignmentInterface $alignment): self
    {
        $this->alignment = $alignment;

        return $this;
    }

    public function getMargin(): MarginInterface
    {
        return $this->margin;
    }

    public function setMargin(MarginInterface $margin): self
    {
        $this->margin = $margin;

        return $this;
    }

    public function getTextColor(): ColorInterface
    {
        return $this->textColor;
    }

    public function setTextColor(ColorInterface $textColor): self
    {
        $this->textColor = $textColor;

        return $this;
    }
}
