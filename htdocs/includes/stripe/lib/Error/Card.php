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
        $this->stripeCode = $stripeCode;

        // This one is not like the others because it was added later and we're
        // trying to do our best not to change the public interface of this class'
        // constructor. We should consider changing its implementation on the
        // next major version bump of this library.
        $this->declineCode = isset($jsonBody["error"]["decline_code"]) ? $jsonBody["error"]["decline_code"] : null;
    }

    public function getDeclineCode()
    {
        return $this->declineCode;
    }

    public function getStripeCode()
    {
        return $this->stripeCode;
    }

    public function getStripeParam()
    {
        return $this->stripeParam;
    }
}
