<?php

namespace Egulias\EmailValidator\Validation;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Exception\InvalidEmail;
use Egulias\EmailValidator\Validation\Error\RFCWarnings;

class NoRFCWarningsValidation extends RFCValidation
{
    /**
     * @var InvalidEmail
     */
    private $error;

    /**
     * {@inheritdoc}
     */
    public function isValid($email, EmailLexer $emailLexer)
    {
        if (!parent::isValid($email, $emailLexer)) {
            return false;
        }

<<<<<<< HEAD
        if (empty($this->getWarnings())) {
=======
        $ret = $this->getWarnings();
        if (empty($ret)) {
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
            return true;
        }

        $this->error = new RFCWarnings();

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getError()
    {
        return $this->error ?: parent::getError();
    }
}
