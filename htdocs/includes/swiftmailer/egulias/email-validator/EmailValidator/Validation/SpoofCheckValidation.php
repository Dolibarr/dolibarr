<?php

namespace Egulias\EmailValidator\Validation;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Exception\InvalidEmail;
use Egulias\EmailValidator\Validation\Error\SpoofEmail;
use \Spoofchecker;

class SpoofCheckValidation implements EmailValidation
{
    /**
     * @var InvalidEmail
     */
    private $error;
<<<<<<< HEAD
    
    public function __construct()
    {
        if (!class_exists(Spoofchecker::class)) {
=======

    public function __construct()
    {
        if (!extension_loaded('intl')) {
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
            throw new \LogicException(sprintf('The %s class requires the Intl extension.', __CLASS__));
        }
    }

    public function isValid($email, EmailLexer $emailLexer)
    {
        $checker = new Spoofchecker();
        $checker->setChecks(Spoofchecker::SINGLE_SCRIPT);

        if ($checker->isSuspicious($email)) {
            $this->error = new SpoofEmail();
        }

        return $this->error === null;
    }

    public function getError()
    {
        return $this->error;
    }

    public function getWarnings()
    {
        return [];
    }
}
