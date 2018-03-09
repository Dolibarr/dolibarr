<?php

namespace Stripe;

class WebhookTest extends TestCase
{
    const EVENT_PAYLOAD = "{
  \"id\": \"evt_test_webhook\",
  \"object\": \"event\"
}";
    const SECRET = "whsec_test_secret";

    private function generateHeader($opts = [])
    {
        $timestamp = array_key_exists('timestamp', $opts) ? $opts['timestamp'] : time();
        $payload = array_key_exists('payload', $opts) ? $opts['payload'] : self::EVENT_PAYLOAD;
        $secret = array_key_exists('secret', $opts) ? $opts['secret'] : self::SECRET;
        $scheme = array_key_exists('scheme', $opts) ? $opts['scheme'] : WebhookSignature::EXPECTED_SCHEME;
        $signature = array_key_exists('signature', $opts) ? $opts['signature'] : null;
        if ($signature === null) {
            $signedPayload = "$timestamp.$payload";
            $signature = hash_hmac("sha256", $signedPayload, $secret);
        }
        return "t=$timestamp,$scheme=$signature";
    }

    public function testValidJsonAndHeader()
    {
        $sigHeader = $this->generateHeader();
        $event = Webhook::constructEvent(self::EVENT_PAYLOAD, $sigHeader, self::SECRET);
        $this->assertEquals("evt_test_webhook", $event->id);
    }

    /**
     * @expectedException \UnexpectedValueException
     */
    public function testInvalidJson()
    {
        $payload = "this is not valid JSON";
        $sigHeader = $this->generateHeader(["payload" => $payload]);
        Webhook::constructEvent($payload, $sigHeader, self::SECRET);
    }

    /**
     * @expectedException \Stripe\Error\SignatureVerification
     */
    public function testValidJsonAndInvalidHeader()
    {
        $sigHeader = "bad_header";
        Webhook::constructEvent(self::EVENT_PAYLOAD, $sigHeader, self::SECRET);
    }

    /**
     * @expectedException \Stripe\Error\SignatureVerification
     * @expectedExceptionMessage Unable to extract timestamp and signatures from header
     */
    public function testMalformedHeader()
    {
        $sigHeader = "i'm not even a real signature header";
        WebhookSignature::verifyHeader(self::EVENT_PAYLOAD, $sigHeader, self::SECRET);
    }

    /**
     * @expectedException \Stripe\Error\SignatureVerification
     * @expectedExceptionMessage No signatures found with expected scheme
     */
    public function testNoSignaturesWithExpectedScheme()
    {
        $sigHeader = $this->generateHeader(["scheme" => "v0"]);
        WebhookSignature::verifyHeader(self::EVENT_PAYLOAD, $sigHeader, self::SECRET);
    }

    /**
     * @expectedException \Stripe\Error\SignatureVerification
     * @expectedExceptionMessage No signatures found matching the expected signature for payload
     */
    public function testNoValidSignatureForPayload()
    {
        $sigHeader = $this->generateHeader(["signature" => "bad_signature"]);
        WebhookSignature::verifyHeader(self::EVENT_PAYLOAD, $sigHeader, self::SECRET);
    }

    /**
     * @expectedException \Stripe\Error\SignatureVerification
     * @expectedExceptionMessage Timestamp outside the tolerance zone
     */
    public function testTimestampOutsideTolerance()
    {
        $sigHeader = $this->generateHeader(["timestamp" => time() - 15]);
        WebhookSignature::verifyHeader(self::EVENT_PAYLOAD, $sigHeader, self::SECRET, 10);
    }

    public function testValidHeaderAndSignature()
    {
        $sigHeader = $this->generateHeader();
        $this->assertTrue(WebhookSignature::verifyHeader(self::EVENT_PAYLOAD, $sigHeader, self::SECRET, 10));
    }

    public function testHeaderContainsValidSignature()
    {
        $sigHeader = $this->generateHeader() . ",v1=bad_signature";
        $this->assertTrue(WebhookSignature::verifyHeader(self::EVENT_PAYLOAD, $sigHeader, self::SECRET, 10));
    }

    public function testTimestampOffButNoTolerance()
    {
        $sigHeader = $this->generateHeader(["timestamp" => 12345]);
        $this->assertTrue(WebhookSignature::verifyHeader(self::EVENT_PAYLOAD, $sigHeader, self::SECRET));
    }
}
