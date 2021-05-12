<?php

namespace Stripe\Error\OAuth;

class OAuthBase extends \Stripe\Error\Base
{
    public function __construct(
        $code,
        $description,
        $httpStatus = null,
        $httpBody = null,
        $jsonBody = null,
        $httpHeaders = null
    ) {
        parent::__construct($description, $httpStatus, $httpBody, $jsonBody, $httpHeaders);
<<<<<<< HEAD
        $this->code = $code;
=======
        $this->errorCode = $code;
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    }

    public function getErrorCode()
    {
<<<<<<< HEAD
        return $this->code;
=======
        return $this->errorCode;
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    }
}
