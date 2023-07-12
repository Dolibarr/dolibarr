<?php declare(strict_types=1);

namespace Sprain\SwissQrBill\Constraint;

use Sprain\SwissQrBill\DataGroup\Element\PaymentReference;
use Sprain\SwissQrBill\QrBill;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * @internal
 */
final class ValidCreditorInformationPaymentReferenceCombinationValidator extends ConstraintValidator
{
    private const QR_IBAN_IS_ALLOWED = [
        PaymentReference::TYPE_QR   => true,
        PaymentReference::TYPE_SCOR => false,
        PaymentReference::TYPE_NON  => false,
    ];

    public function validate($qrBill, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidCreditorInformationPaymentReferenceCombination) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\ValidCreditorInformationPaymentReferenceCombination');
        }

        if (!$qrBill instanceof QrBill) {
            return;
        }

        $creditorInformation = $qrBill->getCreditorInformation();
        $paymentReference = $qrBill->getPaymentReference();

        if (null === $creditorInformation || null === $paymentReference) {
            return;
        }

        if (self::QR_IBAN_IS_ALLOWED[$paymentReference->getType()] !== $creditorInformation->containsQrIban()) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ referenceType }}', $paymentReference->getType())
                ->setParameter('{{ iban }}', $creditorInformation->getIban())
                ->addViolation();
        }
    }
}
