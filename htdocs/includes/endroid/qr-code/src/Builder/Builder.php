<?php

declare(strict_types=1);

namespace Endroid\QrCode\Builder;

use Endroid\QrCode\Color\ColorInterface;
use Endroid\QrCode\Encoding\EncodingInterface;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelInterface;
use Endroid\QrCode\Exception\ValidationException;
use Endroid\QrCode\Label\Alignment\LabelAlignmentInterface;
use Endroid\QrCode\Label\Font\FontInterface;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Label\LabelInterface;
use Endroid\QrCode\Label\Margin\MarginInterface;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\Logo\LogoInterface;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeInterface;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\Result\ResultInterface;
use Endroid\QrCode\Writer\ValidatingWriterInterface;
use Endroid\QrCode\Writer\WriterInterface;

class Builder implements BuilderInterface
{
    /**
     * @var array<string, mixed>{
     *     data: string,
     *     writer: WriterInterface,
     *     writerOptions: array,
     *     qrCodeClass: class-string,
     *     logoClass: class-string,
     *     labelClass: class-string,
     *     validateResult: bool,
     *     size?: int,
     *     encoding?: EncodingInterface,
     *     errorCorrectionLevel?: ErrorCorrectionLevelInterface,
     *     roundBlockSizeMode?: RoundBlockSizeModeInterface,
     *     margin?: int,
     *     backgroundColor?: ColorInterface,
     *     foregroundColor?: ColorInterface,
     *     labelText?: string,
     *     labelFont?: FontInterface,
     *     labelAlignment?: LabelAlignmentInterface,
     *     labelMargin?: MarginInterface,
     *     labelTextColor?: ColorInterface,
     *     logoPath?: string,
     *     logoResizeToWidth?: int,
     *     logoResizeToHeight?: int,
     *     logoPunchoutBackground?: bool
     * }
     */
    private array $options;

    public function __construct()
    {
        $this->options = [
            'data' => '',
            'writer' => new PngWriter(),
            'writerOptions' => [],
            'qrCodeClass' => QrCode::class,
            'logoClass' => Logo::class,
            'labelClass' => Label::class,
            'validateResult' => false,
        ];
    }

    public static function create(): BuilderInterface
    {
        return new self();
    }

    public function writer(WriterInterface $writer): BuilderInterface
    {
        $this->options['writer'] = $writer;

        return $this;
    }

    /** @param array<string, mixed> $writerOptions */
    public function writerOptions(array $writerOptions): BuilderInterface
    {
        $this->options['writerOptions'] = $writerOptions;

        return $this;
    }

    public function data(string $data): BuilderInterface
    {
        $this->options['data'] = $data;

        return $this;
    }

    public function encoding(EncodingInterface $encoding): BuilderInterface
    {
        $this->options['encoding'] = $encoding;

        return $this;
    }

    public function errorCorrectionLevel(ErrorCorrectionLevelInterface $errorCorrectionLevel): BuilderInterface
    {
        $this->options['errorCorrectionLevel'] = $errorCorrectionLevel;

        return $this;
    }

    public function size(int $size): BuilderInterface
    {
        $this->options['size'] = $size;

        return $this;
    }

    public function margin(int $margin): BuilderInterface
    {
        $this->options['margin'] = $margin;

        return $this;
    }

    public function roundBlockSizeMode(RoundBlockSizeModeInterface $roundBlockSizeMode): BuilderInterface
    {
        $this->options['roundBlockSizeMode'] = $roundBlockSizeMode;

        return $this;
    }

    public function foregroundColor(ColorInterface $foregroundColor): BuilderInterface
    {
        $this->options['foregroundColor'] = $foregroundColor;

        return $this;
    }

    public function backgroundColor(ColorInterface $backgroundColor): BuilderInterface
    {
        $this->options['backgroundColor'] = $backgroundColor;

        return $this;
    }

    public function logoPath(string $logoPath): BuilderInterface
    {
        $this->options['logoPath'] = $logoPath;

        return $this;
    }

    public function logoResizeToWidth(int $logoResizeToWidth): BuilderInterface
    {
        $this->options['logoResizeToWidth'] = $logoResizeToWidth;

        return $this;
    }

    public function logoResizeToHeight(int $logoResizeToHeight): BuilderInterface
    {
        $this->options['logoResizeToHeight'] = $logoResizeToHeight;

        return $this;
    }

    public function logoPunchoutBackground(bool $logoPunchoutBackground): BuilderInterface
    {
        $this->options['logoPunchoutBackground'] = $logoPunchoutBackground;

        return $this;
    }

    public function labelText(string $labelText): BuilderInterface
    {
        $this->options['labelText'] = $labelText;

        return $this;
    }

    public function labelFont(FontInterface $labelFont): BuilderInterface
    {
        $this->options['labelFont'] = $labelFont;

        return $this;
    }

    public function labelAlignment(LabelAlignmentInterface $labelAlignment): BuilderInterface
    {
        $this->options['labelAlignment'] = $labelAlignment;

        return $this;
    }

    public function labelMargin(MarginInterface $labelMargin): BuilderInterface
    {
        $this->options['labelMargin'] = $labelMargin;

        return $this;
    }

    public function labelTextColor(ColorInterface $labelTextColor): BuilderInterface
    {
        $this->options['labelTextColor'] = $labelTextColor;

        return $this;
    }

    public function validateResult(bool $validateResult): BuilderInterface
    {
        $this->options['validateResult'] = $validateResult;

        return $this;
    }

    public function build(): ResultInterface
    {
        $writer = $this->options['writer'];

        if ($this->options['validateResult'] && !$writer instanceof ValidatingWriterInterface) {
            throw ValidationException::createForUnsupportedWriter(strval(get_class($writer)));
        }

        /** @var QrCode $qrCode */
        $qrCode = $this->buildObject($this->options['qrCodeClass']);

        /** @var LogoInterface|null $logo */
        $logo = $this->buildObject($this->options['logoClass'], 'logo');

        /** @var LabelInterface|null $label */
        $label = $this->buildObject($this->options['labelClass'], 'label');

        $result = $writer->write($qrCode, $logo, $label, $this->options['writerOptions']);

        if ($this->options['validateResult'] && $writer instanceof ValidatingWriterInterface) {
            $writer->validateResult($result, $qrCode->getData());
        }

        return $result;
    }

    /**
     * @param class-string $class
     *
     * @return mixed
     */
    private function buildObject(string $class, string|null $optionsPrefix = null)
    {
        /** @var \ReflectionClass<object> $reflectionClass */
        $reflectionClass = new \ReflectionClass($class);

        $arguments = [];
        $hasBuilderOptions = false;
        $missingRequiredArguments = [];
        /** @var \ReflectionMethod $constructor */
        $constructor = $reflectionClass->getConstructor();
        $constructorParameters = $constructor->getParameters();
        foreach ($constructorParameters as $parameter) {
            $optionName = null === $optionsPrefix ? $parameter->getName() : $optionsPrefix.ucfirst($parameter->getName());
            if (isset($this->options[$optionName])) {
                $hasBuilderOptions = true;
                $arguments[] = $this->options[$optionName];
            } elseif ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
            } else {
                $missingRequiredArguments[] = $optionName;
            }
        }

        if (!$hasBuilderOptions) {
            return null;
        }

        if (count($missingRequiredArguments) > 0) {
            throw new \Exception(sprintf('Missing required arguments: %s', implode(', ', $missingRequiredArguments)));
        }

        return $reflectionClass->newInstanceArgs($arguments);
    }
}
