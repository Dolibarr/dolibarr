<?php

namespace Stripe;

abstract class WebhookSignature
{
    const EXPECTED_SCHEME = "v1";

    /**
     * Verifies the signature header sent by Stripe. Throws a
     * SignatureVerification exception if the verification fails for any
     * reason.
     *
     * @param string $payload the payload sent by Stripe.
     * @param string $header the contents of the signature header sent by
     *  Stripe.
     * @param string $secret secret used to generate the signature.
     * @param int $tolerance maximum difference allowed between the header's
     *  timestamp and the current time
     * @throws \Stripe\Error\SignatureVerification if the verification fails.
     * @return bool
     */
    public static function verifyHeader($payload, $header, $secret, $tolerance = null)
    {
        // Extract timestamp and signatures from header
        $timestamp = self::getTimestamp($header);
        $signatures = self::getSignatures($header, self::EXPECTED_SCHEME);
        if ($timestamp == -1) {
            throw new Error\SignatureVerification(
                "Unable to extract timestamp and signatures from header",
                $header,
                $payload
            );
        }
        if (empty($signatures)) {
            throw new Error\SignatureVerification(
                "No signatures found with expected scheme",
                $header,
                $payload
            );
        }

        // Check if expected signature is found in list of signatures from
        // header
        $signedPayload = "$timestamp.$payload";
        $expectedSignature = self::computeSignature($signedPayload, $secret);
        $signatureFound = false;
        foreach ($signatures as $signature) {
            if (Util\Util::secureCompare($expectedSignature, $signature)) {
                $signatureFound = true;
                break;
            }
        }
        if (!$signatureFound) {
            throw new Error\SignatureVerification(
                "No signatures found matching the expected signature for payload",
                $header,
                $payload
            );
        }

        // Check if timestamp is within tolerance
        if (($tolerance > 0) && ((time() - $timestamp) > $tolerance)) {
            throw new Error\SignatureVerification(
                "Timestamp outside the tolerance zone",
                $header,
                $payload
            );
        }

        return true;
    }

    /**
     * Extracts the timestamp in a signature header.
     *
     * @param string $header the signature header
     * @return int the timestamp contained in the header, or -1 if no valid
     *  timestamp is found
     */
    private static function getTimestamp($header)
    {
        $items = explode(",", $header);

        foreach ($items as $item) {
            $itemParts = explode("=", $item, 2);
            if ($itemParts[0] == "t") {
                if (!is_numeric($itemParts[1])) {
                    return -1;
                }
                return intval($itemParts[1]);
            }
        }

        return -1;
    }

    /**
     * Extracts the signatures matching a given scheme in a signature header.
     *
     * @param string $header the signature header
     * @param string $scheme the signature scheme to look for.
     * @return array the list of signatures matching the provided scheme.
     */
    private static function getSignatures($header, $scheme)
    {
        $signatures = [];
        $items = explode(",", $header);

        foreach ($items as $item) {
            $itemParts = explode("=", $item, 2);
            if ($itemParts[0] == $scheme) {
                array_push($signatures, $itemParts[1]);
            }
        }

        return $signatures;
    }

    /**
     * Computes the signature for a given payload and secret.
     *
     * The current scheme used by Stripe ("v1") is HMAC/SHA-256.
     *
     * @param string $payload the payload to sign.
     * @param string $secret the secret used to generate the signature.
     * @return string the signature as a string.
     */
    private static function computeSignature($payload, $secret)
    {
        return hash_hmac("sha256", $payload, $secret);
    }
}
