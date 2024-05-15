<?php
// phpcs:disable PEAR.Commenting
namespace Sprain\SwissQrBill\Constraint;

/**
 * @internal
 */
final class ValidCreditorInformationPaymentReferenceCombination extends \Symfony\Component\Validator\Constraint
{
	public string $message = 'The payment reference type "{{ referenceType }}" does not match with the iban type of "{{ iban }}".';
	public function getTargets() : string
	{
	}
}
/**
 * @internal
 */
final class ValidCreditorInformationPaymentReferenceCombinationValidator extends \Symfony\Component\Validator\ConstraintValidator
{
	public function validate(mixed $qrBill, \Symfony\Component\Validator\Constraint $constraint) : void
	{
	}
}
/**
 * @internal
 */
final class ValidCreditorReference extends \Symfony\Component\Validator\Constraint
{
	public string $message = 'The string "{{ string }}" is not a valid Creditor Reference.';
}
/**
 * @internal
 */
final class ValidCreditorReferenceValidator extends \Symfony\Component\Validator\ConstraintValidator
{
	public function validate(mixed $value, \Symfony\Component\Validator\Constraint $constraint) : void
	{
	}
}

namespace Sprain\SwissQrBill\DataGroup;

/**
 * @internal
 */
interface AddressInterface
{
	public function getName() : ?string;
	public function getCountry() : ?string;
	public function getFullAddress(bool $forReceipt = false) : string;
}

namespace Sprain\SwissQrBill\DataGroup\Element\Abstracts;

/**
 * @internal
 */
abstract class Address
{
	protected static function normalizeString(?string $string) : ?string
	{
	}
	/**
	 * @param string[] $lines
	 * @return string[]
	 */
	protected static function clearMultilines(array $lines) : array
	{
	}
}

namespace Sprain\SwissQrBill\DataGroup;

/**
 * @internal
 */
interface QrCodeableInterface
{
	/**
	 * @return list<string|int|null>
	 */
	public function getQrCodeData() : array;
}

namespace Sprain\SwissQrBill\Validator;

/**
 * @internal
 */
interface SelfValidatableInterface
{
	public function getViolations() : \Symfony\Component\Validator\ConstraintViolationListInterface;
	public function isValid() : bool;
	public static function loadValidatorMetadata(\Symfony\Component\Validator\Mapping\ClassMetadata $metadata) : void;
}
/**
 * @internal
 */
trait SelfValidatableTrait
{
	private ?\Symfony\Component\Validator\Validator\ValidatorInterface $validator = null;
	public function getViolations() : \Symfony\Component\Validator\ConstraintViolationListInterface
	{
	}
	public function isValid() : bool
	{
	}
}

namespace Sprain\SwissQrBill\DataGroup\Element;

final class AdditionalInformation implements \Sprain\SwissQrBill\DataGroup\QrCodeableInterface, \Sprain\SwissQrBill\Validator\SelfValidatableInterface
{
	use \Sprain\SwissQrBill\Validator\SelfValidatableTrait;
	public const TRAILER_EPD = 'EPD';
	public static function create(?string $message, ?string $billInformation = null) : self
	{
	}
	public function getMessage() : ?string
	{
	}
	public function getBillInformation() : ?string
	{
	}
	public function getFormattedString() : ?string
	{
	}
	public function getQrCodeData() : array
	{
	}
	public static function loadValidatorMetadata(\Symfony\Component\Validator\Mapping\ClassMetadata $metadata) : void
	{
	}
}
/**
 * For available alternative schemes see link.
 * @link https://www.paymentstandards.ch/en/home/software-partner/alternative-schemes.html
 */
final class AlternativeScheme implements \Sprain\SwissQrBill\DataGroup\QrCodeableInterface, \Sprain\SwissQrBill\Validator\SelfValidatableInterface
{
	use \Sprain\SwissQrBill\Validator\SelfValidatableTrait;
	public static function create(string $parameter) : self
	{
	}
	public function getParameter() : string
	{
	}
	public function getQrCodeData() : array
	{
	}
	public static function loadValidatorMetadata(\Symfony\Component\Validator\Mapping\ClassMetadata $metadata) : void
	{
	}
}
class CombinedAddress extends \Sprain\SwissQrBill\DataGroup\Element\Abstracts\Address implements \Sprain\SwissQrBill\DataGroup\AddressInterface, \Sprain\SwissQrBill\Validator\SelfValidatableInterface, \Sprain\SwissQrBill\DataGroup\QrCodeableInterface
{
	use \Sprain\SwissQrBill\Validator\SelfValidatableTrait;
	public const ADDRESS_TYPE = 'K';
	public static function create(string $name, ?string $addressLine1, string $addressLine2, string $country) : self
	{
	}
	public function getName() : string
	{
	}
	public function getAddressLine1() : ?string
	{
	}
	public function getAddressLine2() : string
	{
	}
	public function getCountry() : string
	{
	}
	public function getFullAddress(bool $forReceipt = false) : string
	{
	}
	public function getQrCodeData() : array
	{
	}
	public static function loadValidatorMetadata(\Symfony\Component\Validator\Mapping\ClassMetadata $metadata) : void
	{
	}
}
final class CreditorInformation implements \Sprain\SwissQrBill\DataGroup\QrCodeableInterface, \Sprain\SwissQrBill\Validator\SelfValidatableInterface
{
	use \Sprain\SwissQrBill\Validator\SelfValidatableTrait;
	public static function create(string $iban) : self
	{
	}
	public function getIban() : string
	{
	}
	public function getFormattedIban() : string
	{
	}
	public function containsQrIban() : bool
	{
	}
	public function getQrCodeData() : array
	{
	}
	public static function loadValidatorMetadata(\Symfony\Component\Validator\Mapping\ClassMetadata $metadata) : void
	{
	}
}
final class Header implements \Sprain\SwissQrBill\DataGroup\QrCodeableInterface, \Sprain\SwissQrBill\Validator\SelfValidatableInterface
{
	use \Sprain\SwissQrBill\Validator\SelfValidatableTrait;
	public const QRTYPE_SPC = 'SPC';
	public const VERSION_0200 = '0200';
	public const CODING_LATIN = 1;
	public static function create(string $qrType, string $version, int $coding) : self
	{
	}
	public function getQrType() : string
	{
	}
	public function getVersion() : string
	{
	}
	public function getCoding() : int
	{
	}
	public function getQrCodeData() : array
	{
	}
	public static function loadValidatorMetadata(\Symfony\Component\Validator\Mapping\ClassMetadata $metadata) : void
	{
	}
}
final class PaymentAmountInformation implements \Sprain\SwissQrBill\DataGroup\QrCodeableInterface, \Sprain\SwissQrBill\Validator\SelfValidatableInterface
{
	use \Sprain\SwissQrBill\Validator\SelfValidatableTrait;
	public const CURRENCY_CHF = 'CHF';
	public const CURRENCY_EUR = 'EUR';
	public static function create(string $currency, ?float $amount = null) : self
	{
	}
	public function getAmount() : ?float
	{
	}
	public function getFormattedAmount() : ?string
	{
	}
	public function getCurrency() : string
	{
	}
	public function getQrCodeData() : array
	{
	}
	public static function loadValidatorMetadata(\Symfony\Component\Validator\Mapping\ClassMetadata $metadata) : void
	{
	}
}
final class PaymentReference implements \Symfony\Component\Validator\GroupSequenceProviderInterface, \Sprain\SwissQrBill\DataGroup\QrCodeableInterface, \Sprain\SwissQrBill\Validator\SelfValidatableInterface
{
	use \Sprain\SwissQrBill\Validator\SelfValidatableTrait;
	public const TYPE_QR = 'QRR';
	public const TYPE_SCOR = 'SCOR';
	public const TYPE_NON = 'NON';
	public static function create(string $type, ?string $reference = null) : self
	{
	}
	public function getType() : string
	{
	}
	public function getReference() : ?string
	{
	}
	public function getFormattedReference() : ?string
	{
	}
	public function getQrCodeData() : array
	{
	}
	public static function loadValidatorMetadata(\Symfony\Component\Validator\Mapping\ClassMetadata $metadata) : void
	{
	}
	public function getGroupSequence() : array|\Symfony\Component\Validator\Constraints\GroupSequence
	{
	}
}
final class StructuredAddress extends \Sprain\SwissQrBill\DataGroup\Element\Abstracts\Address implements \Sprain\SwissQrBill\DataGroup\AddressInterface, \Sprain\SwissQrBill\Validator\SelfValidatableInterface, \Sprain\SwissQrBill\DataGroup\QrCodeableInterface
{
	use \Sprain\SwissQrBill\Validator\SelfValidatableTrait;
	public const ADDRESS_TYPE = 'S';
	public static function createWithoutStreet(string $name, string $postalCode, string $city, string $country) : self
	{
	}
	public static function createWithStreet(string $name, string $street, ?string $buildingNumber, string $postalCode, string $city, string $country) : self
	{
	}
	public function getName() : string
	{
	}
	public function getStreet() : ?string
	{
	}
	public function getBuildingNumber() : ?string
	{
	}
	public function getPostalCode() : string
	{
	}
	public function getCity() : string
	{
	}
	public function getCountry() : string
	{
	}
	public function getFullAddress(bool $forReceipt = false) : string
	{
	}
	public function getQrCodeData() : array
	{
	}
	public static function loadValidatorMetadata(\Symfony\Component\Validator\Mapping\ClassMetadata $metadata) : void
	{
	}
}

namespace Sprain\SwissQrBill\DataGroup\EmptyElement;

/**
 * @internal
 */
final class EmptyAdditionalInformation implements \Sprain\SwissQrBill\DataGroup\QrCodeableInterface
{
	public const TRAILER_EPD = 'EPD';
	public function getQrCodeData() : array
	{
	}
}
/**
 * @internal
 */
final class EmptyAddress implements \Sprain\SwissQrBill\DataGroup\QrCodeableInterface
{
	public const ADDRESS_TYPE = '';
	public function getQrCodeData() : array
	{
	}
}
/**
 * @internal
 */
final class EmptyLine implements \Sprain\SwissQrBill\DataGroup\QrCodeableInterface
{
	public function getQrCodeData() : array
	{
	}
}

namespace Sprain\SwissQrBill\Exception;

final class InvalidFpdfImageFormat extends \Exception
{
}
final class InvalidQrBillDataException extends \Exception
{
}

namespace Sprain\SwissQrBill\PaymentPart\Output;

interface OutputInterface
{
	public function getQrBill() : ?\Sprain\SwissQrBill\QrBill;
	public function getLanguage() : ?string;
	public function getPaymentPart() : ?string;
	public function setPrintable(bool $printable) : static;
	public function isPrintable() : bool;
	public function setQrCodeImageFormat(string $imageFormat) : static;
	public function getQrCodeImageFormat() : string;
}
abstract class AbstractOutput implements \Sprain\SwissQrBill\PaymentPart\Output\OutputInterface
{
	protected \Sprain\SwissQrBill\QrBill $qrBill;
	protected string $language;
	protected bool $printable;
	protected string $qrCodeImageFormat;
	public function __construct(\Sprain\SwissQrBill\QrBill $qrBill, string $language)
	{
	}
	public function getQrBill() : ?\Sprain\SwissQrBill\QrBill
	{
	}
	public function getLanguage() : ?string
	{
	}
	public function setPrintable(bool $printable) : static
	{
	}
	public function isPrintable() : bool
	{
	}
	public function setQrCodeImageFormat(string $fileExtension) : static
	{
	}
	public function getQrCodeImageFormat() : string
	{
	}
	/**
	 * @return list<Title|Text|Placeholder>
	 */
	protected function getInformationElements() : array
	{
	}
	/**
	 * @return list<Title|Text|Placeholder>
	 */
	protected function getInformationElementsOfReceipt() : array
	{
	}
	/**
	 * @return list<Title|Text>
	 */
	protected function getCurrencyElements() : array
	{
	}
	/**
	 * @return list<Title|Text|Placeholder>
	 */
	protected function getAmountElements() : array
	{
	}
	/**
	 * @return list<Title|Text|Placeholder>
	 */
	protected function getAmountElementsReceipt() : array
	{
	}
	/**
	 * @return list<FurtherInformation>
	 */
	protected function getFurtherInformationElements() : array
	{
	}
	protected function getQrCode() : \Sprain\SwissQrBill\QrCode\QrCode
	{
	}
}

namespace Sprain\SwissQrBill\PaymentPart\Output\Element;

interface OutputElementInterface
{
}
/**
 * @internal
 */
final class FurtherInformation implements \Sprain\SwissQrBill\PaymentPart\Output\Element\OutputElementInterface
{
	public static function create(string $furtherInformation) : self
	{
	}
	public function getText() : string
	{
	}
}
/**
 * @internal
 */
final class Placeholder implements \Sprain\SwissQrBill\PaymentPart\Output\Element\OutputElementInterface
{
	public const FILE_TYPE_SVG = 'svg';
	public const FILE_TYPE_PNG = 'png';
	public const PLACEHOLDER_TYPE_PAYABLE_BY = ['type' => 'placeholder_payable_by', 'fileSvg' => __DIR__ . '/../../../../assets/marks_65x25mm.svg', 'filePng' => __DIR__ . '/../../../../assets/marks_65x25mm.png', 'width' => 65, 'height' => 25];
	public const PLACEHOLDER_TYPE_PAYABLE_BY_RECEIPT = ['type' => 'placeholder_payable_by_receipt', 'fileSvg' => __DIR__ . '/../../../../assets/marks_52x20mm.svg', 'filePng' => __DIR__ . '/../../../../assets/marks_52x20mm.png', 'width' => 52, 'height' => 20];
	public const PLACEHOLDER_TYPE_AMOUNT = ['type' => 'placeholder_amount', 'fileSvg' => __DIR__ . '/../../../../assets/marks_40x15mm.svg', 'filePng' => __DIR__ . '/../../../../assets/marks_40x15mm.png', 'width' => 40, 'height' => 15];
	public const PLACEHOLDER_TYPE_AMOUNT_RECEIPT = ['type' => 'placeholder_amount_receipt', 'fileSvg' => __DIR__ . '/../../../../assets/marks_30x10mm.svg', 'filePng' => __DIR__ . '/../../../../assets/marks_30x10mm.png', 'width' => 30, 'height' => 10];
	/**
	 * @param array{type: string, fileSvg: string, filePng: string, width: int, height: int} $type
	 */
	public static function create(array $type) : self
	{
	}
	public function getType() : ?string
	{
	}
	public function getFile(string $type = self::FILE_TYPE_SVG) : string
	{
	}
	public function getWidth() : ?int
	{
	}
	public function getHeight() : ?int
	{
	}
}
/**
 * @internal
 */
final class Text implements \Sprain\SwissQrBill\PaymentPart\Output\Element\OutputElementInterface
{
	public static function create(string $text) : self
	{
	}
	public function getText() : string
	{
	}
}
/**
 * @internal
 */
final class Title implements \Sprain\SwissQrBill\PaymentPart\Output\Element\OutputElementInterface
{
	public static function create(string $title) : self
	{
	}
	public function getTitle() : string
	{
	}
}

namespace Sprain\SwissQrBill\PaymentPart\Output\FpdfOutput;

final class FpdfOutput extends \Sprain\SwissQrBill\PaymentPart\Output\AbstractOutput
{
	public function __construct(\Sprain\SwissQrBill\QrBill $qrBill, string $language, \Fpdf\Fpdf|\setasign\Fpdi\Fpdi $fpdf, float $offsetX = 0, float $offsetY = 0)
	{
	}
	public function getPaymentPart() : ?string
	{
	}
	public function setQrCodeImageFormat(string $fileExtension) : static
	{
	}
}
class UnsupportedEnvironmentException extends \RuntimeException
{
}

namespace Sprain\SwissQrBill\PaymentPart\Output\HtmlOutput;

final class HtmlOutput extends \Sprain\SwissQrBill\PaymentPart\Output\AbstractOutput
{
	public function getPaymentPart() : ?string
	{
	}
}

namespace Sprain\SwissQrBill\PaymentPart\Output\HtmlOutput\Template;

class FurtherInformationElementTemplate
{
	public const TEMPLATE = <<<EOT
<p>{{ text }}</p>
EOT;
}
class PaymentPartTemplate
{
	public const TEMPLATE = <<<EOT
<style>
#qr-bill {
    box-sizing: border-box;
    border-collapse: collapse;
    color: #000 !important;
}

#qr-bill * {
    font-family: Arial, Frutiger, Helvetica, "Liberation Sans"  !important;
}

#qr-bill img.qr-bill-placeholder {
    margin-top: 1pt;
}

#qr-bill-separate-info {
    text-align: center;
    font-size: 8pt !important;
    line-height: 9pt;
    border-bottom: 0.75pt solid black;
    height: 5mm;
    vertical-align: middle;
}

/* h1 / h2 */
#qr-bill h1 {
    font-size: 11pt !important;
    line-height: 13pt !important;
    font-weight: bold !important;
    margin: 0 !important;
    padding: 0 !important;
    height: 7mm !important;
    color: #000 !important;
}

#qr-bill h2 {
    font-weight: bold !important;
    margin: 0 !important;
    padding: 0 !important;
    color: #000 !important;
}

#qr-bill-payment-part h2 {
    font-size: 8pt !important;
    line-height: 11pt !important;
    margin-top: 11pt !important;
    color: #000 !important;
}

#qr-bill-receipt h2 {
    font-size: 6pt !important;
    line-height: 8pt !important;
    margin-top: 8pt !important;
    color: #000 !important;
}

#qr-bill-payment-part h2:first-child,
#qr-bill-receipt h2:first-child {
    margin-top: 0 !important;
    color: #000 !important;
}

/* p */
#qr-bill p {
    font-weight: normal !important;
    margin: 0 !important;
    padding: 0 !important;
    color: #000 !important;
}

#qr-bill-receipt p {
    font-size: 8pt !important;
    line-height: 9pt !important;
    color: #000 !important;
}

#qr-bill-payment-part p {
    font-size: 10pt !important;
    line-height: 11pt !important;
    color: #000 !important;
}

#qr-bill-amount-area-receipt p{
    line-height: 11pt !important;
    color: #000 !important;
}

#qr-bill-amount-area p{
    line-height: 13pt !important;
    color: #000 !important;
}

#qr-bill-payment-further-information p {
    font-size: 7pt !important;
    line-height: 9pt !important;
    color: #000 !important;
}

/* Receipt */
#qr-bill-receipt {
    box-sizing: border-box;
    width: 62mm;
    border-right: 0.2mm solid black;
    padding-left: 5mm;
    padding-top: 5mm;
    vertical-align: top;
}

#qr-bill-information-receipt {
    height: 56mm;
}

#qr-bill-amount-area-receipt {
    height: 14mm;
}

#qr-bill-currency-receipt {
    float: left;
    margin-right: 2mm;
}

#qr-bill-acceptance-point {
    height: 18mm;
    text-align: right;
    margin-right: 5mm;
}

#qr-bill img#placeholder_amount_receipt {
    float: right;
    margin-top: -9pt;
    margin-right: 5mm;
}

/* Main part */
#qr-bill-payment-part {
    box-sizing: border-box;
    width: 148mm;
    padding-left: 5mm;
    padding-top: 5mm;
    padding-right: 5mm;
    vertical-align: top;
}

#qr-bill-payment-part-left {
    float: left;
    box-sizing: border-box;
    width: 51mm;
}

#qr-bill-swiss-qr-image {
    width: 46mm;
    height: 46mm;
    margin: 5mm;
    margin-left: 0;
}

#qr-bill-amount-area {
    height: 22mm;
}

#qr-bill-currency {
    float: left;
    margin-right: 2mm;
}

#qr-bill-payment-further-information {
    clear: both;
}

#qr-bill img#placeholder_amount {
    margin-left: 11mm;
    margin-top: -11pt;
}

{{ printable-content }}
</style>

<table id="qr-bill">
    <tr id="qr-bill-separate-info">
        <td colspan="99"><span id="qr-bill-separate-info-text">{{ text.separate }}</span></td>
    </tr>
    <tr>
        <td id="qr-bill-receipt">
            <h1>{{ text.receipt }}</h1>
            <div id="qr-bill-information-receipt">
                {{ information-content-receipt }}
            </div>
            <div id="qr-bill-amount-area-receipt">
                <div id="qr-bill-currency-receipt">
                    {{ currency-content }}
                </div>
                <div id="qr-bill-amount-receipt">
                    {{ amount-content-receipt }}
                </div>
            </div>
            <div id="qr-bill-acceptance-point">
                <h2>{{ text.acceptancePoint }}</h2>
            </div>
        </td>

        <td id="qr-bill-payment-part">
            <div id="qr-bill-payment-part-left">
                <h1>{{ text.paymentPart }}</h1>
                <img src="{{ swiss-qr-image }}" id="qr-bill-swiss-qr-image">
                <div id="qr-bill-amount-area">
                    <div id="qr-bill-currency">
                        {{ currency-content }}
                    </div>
                    <div id="qr-bill-amount">
                        {{ amount-content }}
                    </div>
                </div>
            </div>
            <div id="qr-bill-payment-part-right">
                <div id="qr-bill-information">
                    {{ information-content }}
                </div>
            </div>
            <div id="qr-bill-payment-further-information">
                {{ further-information-content }}
            </div>
        </td>
    </tr>
</table>
EOT;
}
class PlaceholderElementTemplate
{
	public const TEMPLATE = <<<EOT
<img src="{{ file }}" style="width:{{ width }}mm; height:{{ height }}mm;" class="qr-bill-placeholder" id="{{ id }}">
EOT;
}
class PrintableStylesTemplate
{
	public const TEMPLATE = <<<EOT
#qr-bill-separate-info {
    border-bottom: 0;
}

#qr-bill-separate-info-text {
    display: none;
}

#qr-bill-receipt {
    border-right: 0;
}
EOT;
}
class TextElementTemplate
{
	public const TEMPLATE = <<<EOT
<p>{{ text }}</p>
EOT;
}
class TitleElementTemplate
{
	public const TEMPLATE = <<<EOT
<h2>{{ {{ title }} }}</h2>
EOT;
}

namespace Sprain\SwissQrBill\PaymentPart\Output\TcPdfOutput;

final class TcPdfOutput extends \Sprain\SwissQrBill\PaymentPart\Output\AbstractOutput
{
	public function __construct(\Sprain\SwissQrBill\QrBill $qrBill, string $language, \TCPDF|\setasign\Fpdi\Tcpdf\Fpdi $tcPdf, float $offsetX = 0, float $offsetY = 0)
	{
	}
	public function getPaymentPart() : ?string
	{
	}
}

namespace Sprain\SwissQrBill\PaymentPart\Translation;

final class Translation
{
	/**
	 * @return array<string, string>|null
	 */
	public static function getAllByLanguage(string $language) : ?array
	{
	}
	public static function get(string $key, string $language) : ?string
	{
	}
}

namespace Sprain\SwissQrBill;

final class QrBill implements \Sprain\SwissQrBill\Validator\SelfValidatableInterface
{
	use \Sprain\SwissQrBill\Validator\SelfValidatableTrait;
	public static function create() : self
	{
	}
	public function getHeader() : \Sprain\SwissQrBill\DataGroup\Element\Header
	{
	}
	public function setHeader(\Sprain\SwissQrBill\DataGroup\Element\Header $header) : self
	{
	}
	public function getCreditorInformation() : ?\Sprain\SwissQrBill\DataGroup\Element\CreditorInformation
	{
	}
	public function setCreditorInformation(\Sprain\SwissQrBill\DataGroup\Element\CreditorInformation $creditorInformation) : self
	{
	}
	public function getCreditor() : ?\Sprain\SwissQrBill\DataGroup\AddressInterface
	{
	}
	public function setCreditor(\Sprain\SwissQrBill\DataGroup\AddressInterface $creditor) : self
	{
	}
	public function getPaymentAmountInformation() : ?\Sprain\SwissQrBill\DataGroup\Element\PaymentAmountInformation
	{
	}
	public function setPaymentAmountInformation(\Sprain\SwissQrBill\DataGroup\Element\PaymentAmountInformation $paymentAmountInformation) : self
	{
	}
	public function getUltimateDebtor() : ?\Sprain\SwissQrBill\DataGroup\AddressInterface
	{
	}
	public function setUltimateDebtor(\Sprain\SwissQrBill\DataGroup\AddressInterface $ultimateDebtor) : self
	{
	}
	public function getPaymentReference() : ?\Sprain\SwissQrBill\DataGroup\Element\PaymentReference
	{
	}
	public function setPaymentReference(\Sprain\SwissQrBill\DataGroup\Element\PaymentReference $paymentReference) : self
	{
	}
	public function getAdditionalInformation() : ?\Sprain\SwissQrBill\DataGroup\Element\AdditionalInformation
	{
	}
	public function setAdditionalInformation(\Sprain\SwissQrBill\DataGroup\Element\AdditionalInformation $additionalInformation) : self
	{
	}
	/**
	 * @return list<AlternativeScheme>
	 */
	public function getAlternativeSchemes() : array
	{
	}
	/**
	 * @param list<AlternativeScheme> $alternativeSchemes
	 */
	public function setAlternativeSchemes(array $alternativeSchemes) : self
	{
	}
	public function addAlternativeScheme(\Sprain\SwissQrBill\DataGroup\Element\AlternativeScheme $alternativeScheme) : self
	{
	}
	/**
	 * @throws InvalidQrBillDataException
	 */
	public function getQrCode(?string $fileFormat = null) : \Sprain\SwissQrBill\QrCode\QrCode
	{
	}
	public static function loadValidatorMetadata(\Symfony\Component\Validator\Mapping\ClassMetadata $metadata) : void
	{
	}
}

namespace Sprain\SwissQrBill\QrCode\Exception;

class UnsupportedFileExtensionException extends \Exception
{
}

namespace Sprain\SwissQrBill\QrCode;

final class QrCode
{
	public const FILE_FORMAT_PNG = 'png';
	public const FILE_FORMAT_SVG = 'svg';
	public static function create(string $data, string $fileFormat = null) : self
	{
	}
	public function writeFile(string $path) : void
	{
	}
	/**
	 * @deprecated Will be removed in v5. Use getDataUri() instead.
	 */
	public function writeDataUri() : string
	{
	}
	public function getDataUri(string $format = self::FILE_FORMAT_SVG) : string
	{
	}
	public function getAsString(string $format = self::FILE_FORMAT_SVG) : string
	{
	}
	public function getText() : string
	{
	}
}

namespace Sprain\SwissQrBill\Reference;

final class QrPaymentReferenceGenerator implements \Sprain\SwissQrBill\Validator\SelfValidatableInterface
{
	use \Sprain\SwissQrBill\Validator\SelfValidatableTrait;
	public static function generate(?string $customerIdentificationNumber, string $referenceNumber) : string
	{
	}
	public function __construct(?string $customerIdentificationNumber, string $referenceNumber)
	{
	}
	public function getCustomerIdentificationNumber() : ?string
	{
	}
	public function getReferenceNumber() : ?string
	{
	}
	public function doGenerate() : string
	{
	}
	public static function loadValidatorMetadata(\Symfony\Component\Validator\Mapping\ClassMetadata $metadata) : void
	{
	}
	public function validateFullReference(\Symfony\Component\Validator\Context\ExecutionContextInterface $context) : void
	{
	}
}
final class RfCreditorReferenceGenerator implements \Sprain\SwissQrBill\Validator\SelfValidatableInterface
{
	use \Sprain\SwissQrBill\Validator\SelfValidatableTrait;
	public static function generate(string $reference) : string
	{
	}
	public function __construct(string $reference)
	{
	}
	public function doGenerate() : string
	{
	}
	public static function loadValidatorMetadata(\Symfony\Component\Validator\Mapping\ClassMetadata $metadata) : void
	{
	}
}

namespace Sprain\SwissQrBill\String;

/**
 * @internal
 */
final class StringModifier
{
	public static function replaceLineBreaksAndTabsWithSpaces(?string $string) : string
	{
	}
	public static function replaceMultipleSpacesWithOne(?string $string) : string
	{
	}
	public static function stripWhitespace(?string $string) : string
	{
	}
}

namespace Sprain\SwissQrBill\Validator\Exception;

class InvalidCreditorReferenceException extends \Exception
{
}
class InvalidQrPaymentReferenceException extends \Exception
{
}
