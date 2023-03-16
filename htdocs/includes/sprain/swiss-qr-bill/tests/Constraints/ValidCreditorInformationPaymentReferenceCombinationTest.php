<?php declare(strict_types=1);

namespace Sprain\Tests\SwissQrBill\Constraints;

use DG\BypassFinals;
use Sprain\SwissQrBill\Constraint\ValidCreditorInformationPaymentReferenceCombination;
use Sprain\SwissQrBill\Constraint\ValidCreditorInformationPaymentReferenceCombinationValidator;
use Sprain\SwissQrBill\DataGroup\Element\CreditorInformation;
use Sprain\SwissQrBill\DataGroup\Element\PaymentReference;
use Sprain\SwissQrBill\QrBill;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

final class ValidCreditorInformationPaymentReferenceCombinationTest extends ConstraintValidatorTestCase
{
    protected function createValidator()
    {
        return new ValidCreditorInformationPaymentReferenceCombinationValidator();
    }

    public function testNullIsValid()
    {
        $this->validator->validate(null, new ValidCreditorInformationPaymentReferenceCombination());

        $this->assertNoViolation();
    }

    public function testRandomClassIsValid()
    {
        $this->validator->validate(new \stdClass(), new ValidCreditorInformationPaymentReferenceCombination());

        $this->assertNoViolation();
    }

    /**
     * @dataProvider emptyQrBillMocksProvider
     */
    public function testEmptyQrBillValuesAreValid(QrBill $qrBillMock)
    {
        $this->validator->validate($qrBillMock, new ValidCreditorInformationPaymentReferenceCombination());

        $this->assertNoViolation();
    }

    public function emptyQrBillMocksProvider(): array
    {
        BypassFinals::enable();

        return [
            [$this->getQrBillMock()],
            [$this->getQrBillMock(
                $this->getCreditorInformationMock(),
                null
            )],
            [$this->getQrBillMock(
                null,
                $this->getPaymentReferenceMock()
            )]
        ];
    }

    /**
     * @dataProvider validCombinationsQrBillMocksProvider
     */
    public function testValidCombinations(QrBill $qrBillMock)
    {
        $this->validator->validate($qrBillMock, new ValidCreditorInformationPaymentReferenceCombination());

        $this->assertNoViolation();
    }

    public function validCombinationsQrBillMocksProvider(): array
    {
        return [
            [$this->getQrBillMock(
                $this->getCreditorInformationMock('any-iban', true),
                $this->getPaymentReferenceMock(PaymentReference::TYPE_QR)
            )],
            [$this->getQrBillMock(
                $this->getCreditorInformationMock('any-iban', false),
                $this->getPaymentReferenceMock(PaymentReference::TYPE_SCOR)
            )],
            [$this->getQrBillMock(
                $this->getCreditorInformationMock('any-iban', false),
                $this->getPaymentReferenceMock(PaymentReference::TYPE_NON)
            )],
        ];
    }

    /**
     * @dataProvider invalidCombinationsQrBillMocksProvider
     */
    public function testInvalidCombinations(QrBill $qrBillMock)
    {
        $this->validator->validate($qrBillMock, new ValidCreditorInformationPaymentReferenceCombination([
            'message' => 'myMessage',
        ]));

        $this->buildViolation('myMessage')
            ->setParameter('{{ referenceType }}', $qrBillMock->getPaymentReference()->getType())
            ->setParameter('{{ iban }}', $qrBillMock->getCreditorInformation()->getIban())
            ->assertRaised();
    }

    public function invalidCombinationsQrBillMocksProvider(): array
    {
        return [
            [$this->getQrBillMock(
                $this->getCreditorInformationMock('any-iban', false),
                $this->getPaymentReferenceMock(PaymentReference::TYPE_QR)
            )],
            [$this->getQrBillMock(
                $this->getCreditorInformationMock('any-iban', true),
                $this->getPaymentReferenceMock(PaymentReference::TYPE_SCOR)
            )],
            [$this->getQrBillMock(
                $this->getCreditorInformationMock('any-iban', true),
                $this->getPaymentReferenceMock(PaymentReference::TYPE_NON)
            )],
        ];
    }

    public function getQrBillMock(?CreditorInformation $creditorInformation = null, ?PaymentReference $paymentReference = null)
    {
        $qrBill = $this->createMock(QrBill::class);

        $qrBill->method('getCreditorInformation')
            ->willReturn($creditorInformation);

        $qrBill->method('getPaymentReference')
            ->willReturn($paymentReference);

        return $qrBill;
    }

    public function getCreditorInformationMock(string $iban = '', bool $containsQrIban = false)
    {
        $creditorInformation = $this->createMock(CreditorInformation::class);

        $creditorInformation->method('getIban')
            ->willReturn($iban);

        $creditorInformation->method('containsQrIban')
            ->willReturn($containsQrIban);

        return $creditorInformation;
    }

    public function getPaymentReferenceMock(string $paymentReferenceType = '')
    {
        $paymentReference = $this->createMock(PaymentReference::class);

        $paymentReference->method('getType')
            ->willReturn($paymentReferenceType);

        return $paymentReference;
    }
}
