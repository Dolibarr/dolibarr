# Security Policy

`passkeys-php` is a security-focused library. We take vulnerability
reports seriously and want to make it easy for you to report safely.

## Reporting a vulnerability

**Please do not open a public GitHub issue for security reports.**

Use GitHub's private vulnerability reporting:

1. Go to the [Security tab](https://github.com/report-uri/passkeys-php/security)
   of this repository.
2. Click **Report a vulnerability**.
3. Fill out the form with a description, reproduction steps, and any
   suggested fix.

If you cannot use GitHub for any reason, email
[security@report-uri.com](mailto:security@report-uri.com) instead.

## What to include

- A clear description of the issue and the security impact.
- Steps to reproduce or a proof-of-concept.
- The affected version(s) — ideally the commit SHA you tested against.
- Your assessment of severity, if you have one.

## What to expect

- We aim to acknowledge new reports within **3 working days**.
- We will work with you to verify the issue, agree on a fix, and
  coordinate disclosure.
- We prefer **coordinated disclosure**: please give us a reasonable
  window to ship a fix before publishing details. Standard is up to
  90 days, but we will move faster for high-impact issues and will
  discuss extensions with you for complex ones.
- Once a fix ships, we credit reporters in the GitHub Security
  Advisory unless you ask to remain anonymous.

## Scope

**In scope:** the library code under `src/`.

**Out of scope:**

- The demo under `_test/` — it is for local development only and is
  not intended to be deployed.
- Applications that *use* this library. Please report those to the
  application's own maintainers.
- Vulnerabilities that require already-compromised server-side state
  (e.g. an attacker who already controls the relying party's database).

## Supported versions

We provide security fixes for the latest minor release line only.
Older major versions are not maintained — please upgrade.

| Version | Supported |
|---------|-----------|
| `2.x`   | Yes       |
| `1.x`   | No (please upgrade after v2.0.0 ships) |
