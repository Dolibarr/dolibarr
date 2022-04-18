<?php

namespace Stripe\Error;

class Card extends Base
{
    public function __construct(
        $message,
        $stripeParam,
        $stripeCode,
        $httpStatus,
        $httpBody,
        $jsonBody,
        $httpHeaders = null
    ) {
        parent::__construct($message, $httpStatus, $httpBody, $jsonBody, $httpHeaders);
        $this->stripeParam = $stripeParam;

        // TODO: once Error\Base accepts the error code as an argument, pass it
        //       in the call to parent::__construct() and stop setting it here.
        $this->stripeCode = $stripeCode;

        // This one is not like the others because it was added later and we're
        // trying to do our best not to change the public interface of this class'
        // constructor.
        // TODO: make this a proper constructor argument in the next major
        //       release.
        $this->declineCode = isset($jsonBody["error"]["decline_code"]) ? $jsonBody["error"]["decline_code"] : null;
    }

    public function getDeclineCode()
    {
        return $this->declineCode;
    }

    public function getStripeParam()
    {
        return $this->stripeParam;
    }
}
