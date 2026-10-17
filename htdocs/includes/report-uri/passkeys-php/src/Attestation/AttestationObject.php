<?php

namespace ReportUri\Passkeys\Attestation;
use ReportUri\Passkeys\WebAuthnException;
use ReportUri\Passkeys\CBOR\CborDecoder;
use ReportUri\Passkeys\Binary\ByteBuffer;

/**
 * @author Lukas Buchs
 * @license https://github.com/report-uri/passkeys-php/blob/master/LICENSE MIT
 */
class AttestationObject {
    private $_authenticatorData;

    public function __construct($binary) {
        $enc = CborDecoder::decode($binary);

        if (!\is_array($enc) || !\array_key_exists('fmt', $enc) || !\is_string($enc['fmt'])) {
            throw new WebAuthnException('invalid attestation format', WebAuthnException::INVALID_DATA);
        }

        if (!\array_key_exists('attStmt', $enc) || !\is_array($enc['attStmt'])) {
            throw new WebAuthnException('invalid attestation format (attStmt not available)', WebAuthnException::INVALID_DATA);
        }

        if (!\array_key_exists('authData', $enc) || !\is_object($enc['authData']) || !($enc['authData'] instanceof ByteBuffer)) {
            throw new WebAuthnException('invalid attestation format (authData not available)', WebAuthnException::INVALID_DATA);
        }

        // Only the "none" attestation format is supported. The RP always
        // requests attestation: 'none' in getCreateArgs(), and a spec-compliant
        // client must deliver fmt: 'none' with an empty attStmt regardless of
        // which authenticator the user holds (WebAuthn §5.4.7, §8.7).
        if ($enc['fmt'] !== 'none') {
            throw new WebAuthnException('invalid attestation format: ' . $enc['fmt'], WebAuthnException::INVALID_DATA);
        }

        if (\count($enc['attStmt']) !== 0) {
            throw new WebAuthnException('invalid none attestation: attStmt must be empty', WebAuthnException::INVALID_DATA);
        }

        $this->_authenticatorData = new AuthenticatorData($enc['authData']->getBinaryString());
    }

    /**
     * @return AuthenticatorData
     */
    public function getAuthenticatorData() {
        return $this->_authenticatorData;
    }

    /**
     * checks if the RpId-Hash is valid
     * @param string $rpIdHash
     * @return bool
     */
    public function validateRpIdHash($rpIdHash) {
        return $rpIdHash === $this->_authenticatorData->getRpIdHash();
    }
}
