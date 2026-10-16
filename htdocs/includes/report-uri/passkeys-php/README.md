[![Licensed under the MIT License](https://img.shields.io/badge/License-MIT-blue.svg)](https://github.com/report-uri/passkeys-php/blob/main/LICENSE)
[![PHP 8.0+](https://img.shields.io/badge/PHP-8.0+-green.svg)](https://php.net)

# passkeys-php
*A security-focused PHP WebAuthn (FIDO2 / Passkeys) server library.*

This is a maintained fork of [lbuchs/WebAuthn](https://github.com/lbuchs/WebAuthn) by [Report URI](https://report-uri.com), forked at upstream `v2.2.0`. Goal: provide a small, lightweight, understandable library to protect logins with passkeys, security keys (Yubico, Solo), platform authenticators (Touch ID, Face ID, Windows Hello), etc. — with security fixes applied.

## Why fork

Upstream is effectively dormant. A pen test of Report URI's passkey integration surfaced several conformance issues; fixes were submitted as PRs to lbuchs/WebAuthn but have not been merged. This fork ships those fixes inline so consumers don't need to maintain patches of their own.

### Security improvements vs lbuchs/WebAuthn v2.2.0

- **Attestation removed entirely** — only `fmt: 'none'` with an empty `attStmt` is accepted. `getCreateArgs()` always requests `attestation: 'none'` from the browser, which is required by spec to strip the attestation statement regardless of which authenticator the user holds. All TPM / Packed / U2F / Android-Key / SafetyNet / Apple format handling has been deleted, along with the FIDO MDS plumbing and root-CA validation. The library is positioned for SaaS-style passkey auth where the RP only needs to know the user controls a credential bound to the RP — not which authenticator they used.
- **Tighter origin check** — the previous regex-based RP-ID match treated the RP ID as a substring suffix (e.g. RP `example.com` would match host `evil-example.com`). Now an exact match or true subdomain (RP ID preceded by a dot).
- **Cross-origin rejection** — `processCreate` / `processGet` now reject ceremonies where `clientDataJSON.crossOrigin === true` (WebAuthn Level 3 §7.1 Step 10, §7.2 Step 13).
- **Backup flag validation** — `AuthenticatorData` now rejects flag bytes where Backup State (BS) is set without Backup Eligible (BE), per spec.
- **Token Binding rejection** — `clientDataJSON.tokenBinding.status === 'present'` is rejected (WebAuthn Level 2 §7.1 Step 6, §7.2 Step 10), since this library does not implement Token Binding.

Each fix is a separate commit on `main` for easy auditing.

### Migrating from a build with attestation

If you previously consumed `lbuchs/WebAuthn` (or an earlier build of this fork) and used attestation:

- The constructor no longer accepts an `$allowedFormats` argument. Drop the third positional argument: `new WebAuthn($rpName, $rpId, $useBase64UrlEncoding = false)`.
- `addRootCertificates()`, `addAndroidKeyHashes()`, and `queryFidoMetaDataService()` have been removed. Delete any calls to them.
- `processCreate()` no longer accepts `$failIfRootMismatch` or `$requireCtsProfileMatch`. Drop those arguments.
- The `processCreate()` result no longer carries `attestationFormat`, `certificate`, `certificateChain`, `certificateIssuer`, `certificateSubject`, or `rootValid`. Remove any code that reads those fields.
- Native Android app origins (`android:apk-key-hash:…`) are no longer recognised; only `https` origins (and `http://localhost` for development) are accepted. This affects only relying parties that ship a **native Android app** which calls the platform FIDO2 / Credential Manager API in-process — those calls produce `android:apk-key-hash:` origins. Browsers on Android (Chrome, Firefox, Edge, …) and any WebView-based flow still produce normal `https://` origins and work unchanged.
- The `WebAuthnException::CERTIFICATE_NOT_TRUSTED` and `WebAuthnException::ANDROID_NOT_TRUSTED` error-code constants have been removed. Both were thrown only from attestation paths that no longer exist. Update any `catch` blocks that branch on `$e->getCode()`.

## Installation

```bash
composer require report-uri/passkeys-php
```

The library autoloads under PSR-4 as `ReportUri\Passkeys\`. The main entry point is `ReportUri\Passkeys\WebAuthn` (the class name is kept aligned with the W3C spec name).

```php
use ReportUri\Passkeys\WebAuthn;

$server = new WebAuthn('My App', 'example.com');
```

## Manual
See [`_test/`](_test/) for a simple working demo. The `server.php` + `client.html` pair exercises registration and login end-to-end.

## Workflow

             JAVASCRIPT            |          SERVER
    ------------------------------------------------------------
                             REGISTRATION


       window.fetch  ----------------->     getCreateArgs
                                                 |
    navigator.credentials.create   <-------------'
            |
            '------------------------->     processCreate
                                                 |
          alert ok or fail      <----------------'


    ------------------------------------------------------------
                          VALIDATION


       window.fetch ------------------>      getGetArgs
                                                 |
    navigator.credentials.get   <----------------'
            |
            '------------------------->      processGet
                                                 |
          alert ok or fail      <----------------'

## Passkeys / Client-side discoverable Credentials
A Client-side discoverable Credential Source is a public-key credential source whose private key is stored in the authenticator, client or client device. This requires a resident-credential-capable authenticator (FIDO2 hardware, not older U2F).

> Passkeys allow sharing credentials stored on one device with other devices. From a server's perspective there is no difference to client-side discoverable credentials — the OS handles cross-device sync transparently.

### How does it work?
In a typical server-side key management flow, the user enters their username (and maybe password). The server validates and returns a list of public-key identifiers for that user; the authenticator picks the first credential it issued and signs.

In a client-side flow, the user does not need to provide a username. The authenticator searches its own memory for a key bound to the relying party (domain). If a key is found, the authentication process proceeds as it would if the server had sent a list of identifiers.

### How can I use it?
#### on registration
When calling `ReportUri\Passkeys\WebAuthn->getCreateArgs`, set `$requireResidentKey` to true so the authenticator saves the registration in its memory.

#### on login
When calling `ReportUri\Passkeys\WebAuthn->getGetArgs`, don't provide any `$credentialIds` — the authenticator will look up the IDs in its own memory and return the user ID as `userHandle`. Set the type of authenticator to `hybrid` (passkey scanned via QR code) and `internal` (passkey stored on the device itself).

#### caveat
The RP ID (domain) is saved on the authenticator. If an authenticator is lost it is theoretically possible to find the services it's used with and log in there.

### device support
Built-in passkeys that automatically sync to all of a user's devices: see [passkeys.dev/device-support](https://passkeys.dev/device-support/).
* Apple iOS 16+ / iPadOS 16+ / macOS Ventura+
* Android 9+
* Microsoft Windows 11 23H2+

## Requirements
* PHP >= 8.0 with [OpenSSL](http://php.net/manual/en/book.openssl.php) and [Multibyte String](https://www.php.net/manual/en/book.mbstring.php)
* Browser with [WebAuthn support](https://caniuse.com/webauthn)
* PHP [Sodium](https://www.php.net/manual/en/book.sodium.php) (or [Sodium Compat](https://github.com/paragonie/sodium_compat)) for [Ed25519](https://en.wikipedia.org/wiki/EdDSA#Ed25519) support, or OpenSSL with native Ed25519 support (PHP ≥ 8.4)

## Credits

The original library was written by [Lukas Buchs](https://github.com/lbuchs) under the MIT license. See [NOTICE.md](NOTICE.md) for full attribution.

## License

[MIT](LICENSE) — same as upstream.

## Further reading
* [W3C WebAuthn spec](https://www.w3.org/TR/webauthn/)
* [MDN: Web Authentication API](https://developer.mozilla.org/en-US/docs/Web/API/Web_Authentication_API)
* [passkeys.dev](https://passkeys.dev/)
* [FIDO Alliance](https://fidoalliance.org)
