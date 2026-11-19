<?php

namespace ReportUri\Passkeys;
use ReportUri\Passkeys\Binary\ByteBuffer;
require_once 'WebAuthnException.php';
require_once 'Binary/ByteBuffer.php';
require_once 'Attestation/AttestationObject.php';
require_once 'Attestation/AuthenticatorData.php';
require_once 'CBOR/CborDecoder.php';

/**
 * WebAuthn
 * @author Lukas Buchs
 * @license https://github.com/report-uri/passkeys-php/blob/master/LICENSE MIT
 */
class WebAuthn {
    // relying party
    private $_rpName;
    private $_rpId;
    private $_rpIdHash;
    private $_challenge;
    private $_signatureCounter;

    /**
     * Initialize a new WebAuthn server
     * @param string $rpName the relying party name
     * @param string $rpId the relying party ID = the domain name
     * @param bool $useBase64UrlEncoding true to use base64 url encoding for binary data in json objects. Default is a RFC 1342-Like serialized string.
     * @throws WebAuthnException
     */
    public function __construct($rpName, $rpId, $useBase64UrlEncoding=false) {
        $this->_rpName = $rpName;
        $this->_rpId = $rpId;
        $this->_rpIdHash = \hash('sha256', $rpId, true);
        ByteBuffer::$useBase64UrlEncoding = !!$useBase64UrlEncoding;

        if (!\function_exists('\openssl_open')) {
            throw new WebAuthnException('OpenSSL-Module not installed');
        }

        if (!\in_array('SHA256', \array_map('\strtoupper', \openssl_get_md_methods()))) {
            throw new WebAuthnException('SHA256 not supported by this openssl installation.');
        }
    }

    /**
     * Returns the generated challenge to save for later validation
     * @return ByteBuffer
     */
    public function getChallenge() {
        return $this->_challenge;
    }

    /**
     * generates the object for a key registration
     * provide this data to navigator.credentials.create
     * @param string $userId
     * @param string $userName
     * @param string $userDisplayName
     * @param int $timeout timeout in seconds
     * @param bool|string $requireResidentKey      'required', if the key should be stored by the authentication device
     *                                             Valid values:
     *                                             true = required
     *                                             false = preferred
     *                                             string 'required' 'preferred' 'discouraged'
     * @param bool|string $requireUserVerification indicates that you require user verification and will fail the operation
     *                                             if the response does not have the UV flag set.
     *                                             Valid values:
     *                                             true = required
     *                                             false = preferred
     *                                             string 'required' 'preferred' 'discouraged'
     * @param bool|null $crossPlatformAttachment   true for cross-platform devices (eg. fido usb),
     *                                             false for platform devices (eg. windows hello, android safetynet),
     *                                             null for both
     * @param array $excludeCredentialIds a array of ids, which are already registered, to prevent re-registration
     * @return \stdClass
     */
    public function getCreateArgs($userId, $userName, $userDisplayName, $timeout=20, $requireResidentKey=false, $requireUserVerification=false, $crossPlatformAttachment=null, $excludeCredentialIds=[]) {

        $args = new \stdClass();
        $args->publicKey = new \stdClass();

        // relying party
        $args->publicKey->rp = new \stdClass();
        $args->publicKey->rp->name = $this->_rpName;
        $args->publicKey->rp->id = $this->_rpId;

        $args->publicKey->authenticatorSelection = new \stdClass();
        $args->publicKey->authenticatorSelection->userVerification = 'preferred';

        // validate User Verification Requirement
        if (\is_bool($requireUserVerification)) {
            $args->publicKey->authenticatorSelection->userVerification = $requireUserVerification ? 'required' : 'preferred';

        } else if (\is_string($requireUserVerification) && \in_array(\strtolower($requireUserVerification), ['required', 'preferred', 'discouraged'])) {
            $args->publicKey->authenticatorSelection->userVerification = \strtolower($requireUserVerification);
        }

        // validate Resident Key Requirement
        if (\is_bool($requireResidentKey) && $requireResidentKey) {
            $args->publicKey->authenticatorSelection->requireResidentKey = true;
            $args->publicKey->authenticatorSelection->residentKey = 'required';

        } else if (\is_string($requireResidentKey) && \in_array(\strtolower($requireResidentKey), ['required', 'preferred', 'discouraged'])) {
            $requireResidentKey = \strtolower($requireResidentKey);
            $args->publicKey->authenticatorSelection->residentKey = $requireResidentKey;
            $args->publicKey->authenticatorSelection->requireResidentKey = $requireResidentKey === 'required';
        }

        // filte authenticators attached with the specified authenticator attachment modality
        if (\is_bool($crossPlatformAttachment)) {
            $args->publicKey->authenticatorSelection->authenticatorAttachment = $crossPlatformAttachment ? 'cross-platform' : 'platform';
        }

        // user
        $args->publicKey->user = new \stdClass();
        $args->publicKey->user->id = new ByteBuffer($userId); // binary
        $args->publicKey->user->name = $userName;
        $args->publicKey->user->displayName = $userDisplayName;

        // supported algorithms
        $args->publicKey->pubKeyCredParams = [];

        if (function_exists('sodium_crypto_sign_verify_detached') || defined('OPENSSL_KEYTYPE_ED25519')) {
            $tmp = new \stdClass();
            $tmp->type = 'public-key';
            $tmp->alg = -8; // EdDSA
            $args->publicKey->pubKeyCredParams[] = $tmp;
            unset ($tmp);
        }

        if (\in_array('prime256v1', \openssl_get_curve_names(), true)) {
            $tmp = new \stdClass();
            $tmp->type = 'public-key';
            $tmp->alg = -7; // ES256
            $args->publicKey->pubKeyCredParams[] = $tmp;
            unset ($tmp);
        }

        $tmp = new \stdClass();
        $tmp->type = 'public-key';
        $tmp->alg = -257; // RS256
        $args->publicKey->pubKeyCredParams[] = $tmp;
        unset ($tmp);

        $args->publicKey->attestation = 'none';
        $args->publicKey->extensions = new \stdClass();
        $args->publicKey->extensions->exts = true;
        $args->publicKey->timeout = $timeout * 1000; // microseconds
        $args->publicKey->challenge = $this->_createChallenge(); // binary

        //prevent re-registration by specifying existing credentials
        $args->publicKey->excludeCredentials = [];

        if (is_array($excludeCredentialIds)) {
            foreach ($excludeCredentialIds as $id) {
                $tmp = new \stdClass();
                $tmp->id = $id instanceof ByteBuffer ? $id : new ByteBuffer($id);  // binary
                $tmp->type = 'public-key';
                $tmp->transports = array('usb', 'nfc', 'ble', 'hybrid', 'internal');
                $args->publicKey->excludeCredentials[] = $tmp;
                unset ($tmp);
            }
        }

        return $args;
    }

    /**
     * generates the object for key validation
     * Provide this data to navigator.credentials.get
     * @param array $credentialIds binary
     * @param int $timeout timeout in seconds
     * @param bool $allowUsb allow removable USB
     * @param bool $allowNfc allow Near Field Communication (NFC)
     * @param bool $allowBle allow Bluetooth
     * @param bool $allowHybrid allow a combination of (often separate) data-transport and proximity mechanisms.
     * @param bool $allowInternal allow client device-specific transport. These authenticators are not removable from the client device.
     * @param bool|string $requireUserVerification indicates that you require user verification and will fail the operation
     *                                             if the response does not have the UV flag set.
     *                                             Valid values:
     *                                             true = required
     *                                             false = preferred
     *                                             string 'required' 'preferred' 'discouraged'
     * @return \stdClass
     */
    public function getGetArgs($credentialIds=[], $timeout=20, $allowUsb=true, $allowNfc=true, $allowBle=true, $allowHybrid=true, $allowInternal=true, $requireUserVerification=false) {

        // validate User Verification Requirement
        if (\is_bool($requireUserVerification)) {
            $requireUserVerification = $requireUserVerification ? 'required' : 'preferred';
        } else if (\is_string($requireUserVerification) && \in_array(\strtolower($requireUserVerification), ['required', 'preferred', 'discouraged'])) {
            $requireUserVerification = \strtolower($requireUserVerification);
        } else {
            $requireUserVerification = 'preferred';
        }

        $args = new \stdClass();
        $args->publicKey = new \stdClass();
        $args->publicKey->timeout = $timeout * 1000; // microseconds
        $args->publicKey->challenge = $this->_createChallenge();  // binary
        $args->publicKey->userVerification = $requireUserVerification;
        $args->publicKey->rpId = $this->_rpId;

        if (\is_array($credentialIds) && \count($credentialIds) > 0) {
            $args->publicKey->allowCredentials = [];

            foreach ($credentialIds as $id) {
                $tmp = new \stdClass();
                $tmp->id = $id instanceof ByteBuffer ? $id : new ByteBuffer($id);  // binary
                $tmp->transports = [];

                if ($allowUsb) {
                    $tmp->transports[] = 'usb';
                }
                if ($allowNfc) {
                    $tmp->transports[] = 'nfc';
                }
                if ($allowBle) {
                    $tmp->transports[] = 'ble';
                }
                if ($allowHybrid) {
                    $tmp->transports[] = 'hybrid';
                }
                if ($allowInternal) {
                    $tmp->transports[] = 'internal';
                }

                $tmp->type = 'public-key';
                $args->publicKey->allowCredentials[] = $tmp;
                unset ($tmp);
            }
        }

        return $args;
    }

    /**
     * returns the new signature counter value.
     * returns null if there is no counter
     * @return ?int
     */
    public function getSignatureCounter() {
        return \is_int($this->_signatureCounter) ? $this->_signatureCounter : null;
    }

    /**
     * process a create request and returns data to save for future logins
     * @param string $clientDataJSON binary from browser
     * @param string $attestationObject binary from browser
     * @param string|ByteBuffer $challenge binary used challange
     * @param bool $requireUserVerification true, if the device must verify user (e.g. by biometric data or pin)
     * @param bool $requireUserPresent false, if the device must NOT check user presence (e.g. by pressing a button)
     * @return \stdClass
     * @throws WebAuthnException
     */
    public function processCreate($clientDataJSON, $attestationObject, $challenge, $requireUserVerification=false, $requireUserPresent=true) {
        $clientDataHash = \hash('sha256', $clientDataJSON, true);
        $clientData = \json_decode($clientDataJSON);
        $challenge = $challenge instanceof ByteBuffer ? $challenge : new ByteBuffer($challenge);

        // security: https://www.w3.org/TR/webauthn/#registering-a-new-credential

        // 2. Let C, the client data claimed as collected during the credential creation,
        //    be the result of running an implementation-specific JSON parser on JSONtext.
        if (!\is_object($clientData)) {
            throw new WebAuthnException('invalid client data', WebAuthnException::INVALID_DATA);
        }

        // 3. Verify that the value of C.type is webauthn.create.
        if (!\property_exists($clientData, 'type') || $clientData->type !== 'webauthn.create') {
            throw new WebAuthnException('invalid type', WebAuthnException::INVALID_TYPE);
        }

        // 4. Verify that the value of C.challenge matches the challenge that was sent to the authenticator in the create() call.
        if (!\property_exists($clientData, 'challenge') || ByteBuffer::fromBase64Url($clientData->challenge)->getBinaryString() !== $challenge->getBinaryString()) {
            throw new WebAuthnException('invalid challenge', WebAuthnException::INVALID_CHALLENGE);
        }

        // 5. Verify that the value of C.origin matches the Relying Party's origin.
        if (!\property_exists($clientData, 'origin') || !$this->_checkOrigin($clientData->origin)) {
            throw new WebAuthnException('invalid origin', WebAuthnException::INVALID_ORIGIN);
        }

        // Reject cross-origin requests (proposed Level 3 spec §7.1 Step 10).
        if (\property_exists($clientData, 'crossOrigin') && $clientData->crossOrigin === true) {
            throw new WebAuthnException('cross-origin request not allowed', WebAuthnException::INVALID_ORIGIN);
        }

        // 6. Verify tokenBinding status matches the TLS connection. We do not
        //    support Token Binding, so reject status "present" (Level 2 §7.1 Step 6).
        if (\property_exists($clientData, 'tokenBinding') && \is_object($clientData->tokenBinding) && \property_exists($clientData->tokenBinding, 'status') && $clientData->tokenBinding->status === 'present') {
            throw new WebAuthnException('token binding not supported', WebAuthnException::INVALID_DATA);
        }

        // Attestation. The RP requested attestation: 'none', so a compliant
        // client must deliver fmt: 'none' with an empty attStmt — both
        // invariants are enforced by AttestationObject's constructor.
        $attestationObject = new Attestation\AttestationObject($attestationObject);

        // 9. Verify that the RP ID hash in authData is indeed the SHA-256 hash of the RP ID expected by the RP.
        if (!$attestationObject->validateRpIdHash($this->_rpIdHash)) {
            throw new WebAuthnException('invalid rpId hash', WebAuthnException::INVALID_RELYING_PARTY);
        }

        // 10. Verify that the User Present bit of the flags in authData is set.
        $userPresent = $attestationObject->getAuthenticatorData()->getUserPresent();
        if ($requireUserPresent && !$userPresent) {
            throw new WebAuthnException('user not present during authentication', WebAuthnException::USER_PRESENT);
        }

        // 11. If user verification is required for this registration, verify that the User Verified bit of the flags in authData is set.
        $userVerified = $attestationObject->getAuthenticatorData()->getUserVerified();
        if ($requireUserVerification && !$userVerified) {
            throw new WebAuthnException('user not verified during authentication', WebAuthnException::USER_VERIFICATED);
        }

        $signCount = $attestationObject->getAuthenticatorData()->getSignCount();
        if ($signCount > 0) {
            $this->_signatureCounter = $signCount;
        }

        // prepare data to store for future logins
        $data = new \stdClass();
        $data->rpId = $this->_rpId;
        $data->credentialId = $attestationObject->getAuthenticatorData()->getCredentialId();
        $data->credentialPublicKey = $attestationObject->getAuthenticatorData()->getPublicKeyPem();
        $data->signatureCounter = $this->_signatureCounter;
        $data->AAGUID = $attestationObject->getAuthenticatorData()->getAAGUID();
        $data->userPresent = $userPresent;
        $data->userVerified = $userVerified;
        $data->isBackupEligible = $attestationObject->getAuthenticatorData()->getIsBackupEligible();
        $data->isBackedUp = $attestationObject->getAuthenticatorData()->getIsBackup();
        return $data;
    }


    /**
     * process a get request
     * @param string $clientDataJSON binary from browser
     * @param string $authenticatorData binary from browser
     * @param string $signature binary from browser
     * @param string $credentialPublicKey string PEM-formated public key from used credentialId
     * @param string|ByteBuffer $challenge  binary from used challange
     * @param int $prevSignatureCnt signature count value of the last login
     * @param bool $requireUserVerification true, if the device must verify user (e.g. by biometric data or pin)
     * @param bool $requireUserPresent true, if the device must check user presence (e.g. by pressing a button)
     * @return boolean true if get is successful
     * @throws WebAuthnException
     */
    public function processGet($clientDataJSON, $authenticatorData, $signature, $credentialPublicKey, $challenge, $prevSignatureCnt=null, $requireUserVerification=false, $requireUserPresent=true) {
        $authenticatorObj = new Attestation\AuthenticatorData($authenticatorData);
        $clientDataHash = \hash('sha256', $clientDataJSON, true);
        $clientData = \json_decode($clientDataJSON);
        $challenge = $challenge instanceof ByteBuffer ? $challenge : new ByteBuffer($challenge);

        // https://www.w3.org/TR/webauthn/#verifying-assertion

        // 1. If the allowCredentials option was given when this authentication ceremony was initiated,
        //    verify that credential.id identifies one of the public key credentials that were listed in allowCredentials.
        //    -> TO BE VERIFIED BY IMPLEMENTATION

        // 2. If credential.response.userHandle is present, verify that the user identified
        //    by this value is the owner of the public key credential identified by credential.id.
        //    -> TO BE VERIFIED BY IMPLEMENTATION

        // 3. Using credential’s id attribute (or the corresponding rawId, if base64url encoding is
        //    inappropriate for your use case), look up the corresponding credential public key.
        //    -> TO BE LOOKED UP BY IMPLEMENTATION

        // 5. Let JSONtext be the result of running UTF-8 decode on the value of cData.
        if (!\is_object($clientData)) {
            throw new WebAuthnException('invalid client data', WebAuthnException::INVALID_DATA);
        }

        // 7. Verify that the value of C.type is the string webauthn.get.
        if (!\property_exists($clientData, 'type') || $clientData->type !== 'webauthn.get') {
            throw new WebAuthnException('invalid type', WebAuthnException::INVALID_TYPE);
        }

        // 8. Verify that the value of C.challenge matches the challenge that was sent to the
        //    authenticator in the PublicKeyCredentialRequestOptions passed to the get() call.
        if (!\property_exists($clientData, 'challenge') || ByteBuffer::fromBase64Url($clientData->challenge)->getBinaryString() !== $challenge->getBinaryString()) {
            throw new WebAuthnException('invalid challenge', WebAuthnException::INVALID_CHALLENGE);
        }

        // 9. Verify that the value of C.origin matches the Relying Party's origin.
        if (!\property_exists($clientData, 'origin') || !$this->_checkOrigin($clientData->origin)) {
            throw new WebAuthnException('invalid origin', WebAuthnException::INVALID_ORIGIN);
        }

        // Reject cross-origin requests (proposed Level 3 spec §7.2 Step 13).
        if (\property_exists($clientData, 'crossOrigin') && $clientData->crossOrigin === true) {
            throw new WebAuthnException('cross-origin request not allowed', WebAuthnException::INVALID_ORIGIN);
        }

        // 10. Verify tokenBinding status matches the TLS connection. We do not
        //     support Token Binding, so reject status "present" (Level 2 §7.2 Step 10).
        if (\property_exists($clientData, 'tokenBinding') && \is_object($clientData->tokenBinding) && \property_exists($clientData->tokenBinding, 'status') && $clientData->tokenBinding->status === 'present') {
            throw new WebAuthnException('token binding not supported', WebAuthnException::INVALID_DATA);
        }

        // 11. Verify that the rpIdHash in authData is the SHA-256 hash of the RP ID expected by the Relying Party.
        if ($authenticatorObj->getRpIdHash() !== $this->_rpIdHash) {
            throw new WebAuthnException('invalid rpId hash', WebAuthnException::INVALID_RELYING_PARTY);
        }

        // 12. Verify that the User Present bit of the flags in authData is set
        if ($requireUserPresent && !$authenticatorObj->getUserPresent()) {
            throw new WebAuthnException('user not present during authentication', WebAuthnException::USER_PRESENT);
        }

        // 13. If user verification is required for this assertion, verify that the User Verified bit of the flags in authData is set.
        if ($requireUserVerification && !$authenticatorObj->getUserVerified()) {
            throw new WebAuthnException('user not verificated during authentication', WebAuthnException::USER_VERIFICATED);
        }

        // 14. Verify the values of the client extension outputs
        //     (extensions not implemented)

        // 16. Using the credential public key looked up in step 3, verify that sig is a valid signature
        //     over the binary concatenation of authData and hash.
        $dataToVerify = '';
        $dataToVerify .= $authenticatorData;
        $dataToVerify .= $clientDataHash;

        if (!$this->_verifySignature($dataToVerify, $signature, $credentialPublicKey)) {
            throw new WebAuthnException('invalid signature', WebAuthnException::INVALID_SIGNATURE);
        }

        $signatureCounter = $authenticatorObj->getSignCount();
        if ($signatureCounter !== 0) {
            $this->_signatureCounter = $signatureCounter;
        }

        // 17. If either of the signature counter value authData.signCount or
        //     previous signature count is nonzero, and if authData.signCount
        //     less than or equal to previous signature count, it's a signal
        //     that the authenticator may be cloned
        if ($prevSignatureCnt !== null) {
            if ($signatureCounter !== 0 || $prevSignatureCnt !== 0) {
                if ($prevSignatureCnt >= $signatureCounter) {
                    throw new WebAuthnException('signature counter not valid', WebAuthnException::SIGNATURE_COUNTER);
                }
            }
        }

        return true;
    }

    // -----------------------------------------------
    // PRIVATE
    // -----------------------------------------------

    /**
     * checks if the origin matchs the RP ID
     * @param string $origin
     * @return boolean
     * @throws WebAuthnException
     */
    private function _checkOrigin($origin) {
        // https://www.w3.org/TR/webauthn/#rp-id

        // The origin's scheme must be https
        if ($this->_rpId !== 'localhost' && \parse_url($origin, PHP_URL_SCHEME) !== 'https') {
            return false;
        }

        // extract host from origin
        $host = \parse_url($origin, PHP_URL_HOST);
        $host = \trim($host, '.');

        // The RP ID must be equal to the origin's effective domain, or the
        // origin's host must be a subdomain of the RP ID (i.e. preceded by a dot).
        if (\strcasecmp($host, $this->_rpId) === 0) {
            return true;
        }
        return \str_ends_with(\strtolower($host), '.' . \strtolower($this->_rpId));
    }

    /**
     * generates a new challange
     * @param int $length
     * @return string
     * @throws WebAuthnException
     */
    private function _createChallenge($length = 32) {
        if (!$this->_challenge) {
            $this->_challenge = ByteBuffer::randomBuffer($length);
        }
        return $this->_challenge;
    }

    /**
     * check if the signature is valid.
     * @param string $dataToVerify
     * @param string $signature
     * @param string $credentialPublicKey PEM format
     * @return bool
     */
    private function _verifySignature($dataToVerify, $signature, $credentialPublicKey) {

        // Use Sodium to verify EdDSA 25519 if it's not supported by openssl
        if (\function_exists('sodium_crypto_sign_verify_detached') && !defined('OPENSSL_KEYTYPE_ED25519')) {
            $pkParts = [];
            if (\preg_match('/BEGIN PUBLIC KEY\-+(?:\s|\n|\r)+([^\-]+)(?:\s|\n|\r)*\-+END PUBLIC KEY/i', $credentialPublicKey, $pkParts)) {
                $rawPk = \base64_decode($pkParts[1]);

                // 30        = der sequence
                // 2a        = length 42 byte
                // 30        = der sequence
                // 05        = length 5 byte
                // 06        = der OID
                // 03        = OID length 3 byte
                // 2b 65 70  = OID 1.3.101.112 curveEd25519 (EdDSA 25519 signature algorithm)
                // 03        = der bit string
                // 21        = length 33 byte
                // 00        = null padding
                // [...]     = 32 byte x-curve
                $okpPrefix = "\x30\x2a\x30\x05\x06\x03\x2b\x65\x70\x03\x21\x00";

                if ($rawPk && \strlen($rawPk) === 44 && \substr($rawPk,0, \strlen($okpPrefix)) === $okpPrefix) {
                    $publicKeyXCurve = \substr($rawPk, \strlen($okpPrefix));

                    return \sodium_crypto_sign_verify_detached($signature, $dataToVerify, $publicKeyXCurve);
                }
            }
        }

        // verify with openSSL
        $publicKey = \openssl_pkey_get_public($credentialPublicKey);
        if ($publicKey === false) {
            throw new WebAuthnException('public key invalid', WebAuthnException::INVALID_PUBLIC_KEY);
        }

        $algo = OPENSSL_ALGO_SHA256;

        if (defined('OPENSSL_KEYTYPE_ED25519')) {
            $publicKeyDetails = \openssl_pkey_get_details($publicKey);

            if ($publicKeyDetails && $publicKeyDetails['type'] === OPENSSL_KEYTYPE_ED25519) {
                // algorithm 0 is used because EdDSA has a built-in hash
                $algo = 0;
            }
        }

        return \openssl_verify($dataToVerify, $signature, $publicKey, $algo) === 1;
    }
}
