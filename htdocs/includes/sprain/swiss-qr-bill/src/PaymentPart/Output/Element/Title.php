<?php declare(strict_types=1);

namespace Sprain\SwissQrBill\PaymentPart\Output\Element;

/**
 * @internal
 */
final class Title implements OutputElementInterface
{
    private string $title;

    public static function create(string $title): self
    {
        $element = new self();
        $element->title = $title;

        return $element;
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}
