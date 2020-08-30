# Security Policy

This file contains some policies about the security reports on Dolibarr ERP CRM project, one of the most popular Open Source ERP and CRM in the world.

## Supported Versions for security reports

| Version   | Supported          |
| --------- | ------------------ |
| <= 9.0.*  | :x:                |
| >= 10.0.* | :white_check_mark: |


## Reporting a Vulnerability

To report a vulnerability, please send an email to security@dolibarr.org
In most cases, after fixing the security, we make an answer by email to say the issue has been fixed.


## Hunting vulnerabilities on Dolibarr

We believe that future of software is online SaaS. This means software are more and more critical and no technology is perfect. Working with skilled security researchers is crucial in identifying weaknesses in our technology.

If you believe you've found a security bug in our service, we are happy to work with you to resolve the issue promptly and ensure you are fairly rewarded for your discovery.

Any type of denial of service attacks is strictly forbidden, as well as any interference with network equipment and Dolibarr infrastructure.

We recommand to install Dolibarr ERP CRM on you own server (as most Open Source software, download and use is free: https://www.dolibarr.org/download) to get access on every side of application.

### User Agent

If you try to find bug on Dolibarr, we recommend to append to your user-agent header the following value: '-BugHunting-dolibarr'.

### Account access

You can install the web application yourself on your own platform/server so you get full access to application and sources. Download the zip of the files to put into your own web server virtual host from https://www.dolibarr.org/download


## Eligibility and Responsible Disclosure

We are happy to thank everyone who submits valid reports which help us improve the security of Dolibarr however, only those that meet the following eligibility requirements may receive a monetary reward:

You must be the first reporter of a vulnerability.

The vulnerability must be a qualifying vulnerability (see below)

Any vulnerability found must be reported no later than 24 hours after discovery

You must send a clear textual description of the report along with steps to reproduce the issue, include attachments such as screenshots or proof of concept code as necessary.

You must avoid tests that could cause degradation or interruption of our service (refrain from using automated tools, and limit yourself about requests per second), that's why we recommand to install softwate on your own platform.

You must not leak, manipulate, or destroy any user data.

You must not be a former or current employee of Dolibarr or one of its contractor.

Reports about vulnerabilities are examined by our security analysts.

Our analysis is always based on worst case exploitation of the vulnerability, as is the reward we pay.

No vulnerability disclosure, including partial is allowed for the moment.


## Scope for qualified vulnerabilities

ONLY vulnerabilities discovered when the following setup is used are accepted:

* $dolibarr_main_prod must be 1 into conf.php
* $dolibarr_nocsrfcheck must not be set to 0 (should be 1 by default) into conf.php
* The constant MAIN_SECURITY_CSRF_WITH_TOKEN must be set to 1 into backoffice menu Home - Setup - Other (this value should be switched soon to 1 by default)
* ONLY security reports on "stable" modules are allowed (troubles into experimental and developement modules are not accepted).

Scope is the web application (back office) and the APIs.


## Qualifying vulnerabilities for Bug bounty programs
* Remote code execution (RCE)
* Local files access and manipulation (LFI, RFI, XXE, SSRF, XSPA)
* Code injections (HTML, JS, SQL, PHP, ...)
* Cross-Site Scripting (XSS)
* Cross-Site Requests Forgery (CSRF) with real security impact
* Open redirect
* Broken authentication & session management
* Insecure direct object references
* CORS with real security impact
* Horizontal and vertical privilege escalation
* "HTTP Host Header" XSS


## Non-qualifying vulnerabilities for Bug bounty programs, but qualified for reporting
* "Self" XSS
* Missing cookie flags
* SSL/TLS best practices
* Mixed content warnings
* Denial of Service attacks
* Clickjacking/UI redressing
* Software version disclosure
* Stack traces or path disclosure
* Physical or social engineering attempts
* Recently disclosed 0-day vulnerabilities
* Presence of autocomplete attribute on web forms
* Vulnerabilities affecting outdated browsers or platforms
* Issues that require physical access to a victim’s computer/device
* Logout and other instances of low-severity Cross-Site Request Forgery
* Missing security-related HTTP headers which do not lead directly to a vulnerability
* Reports from automated web vulnerability scanners (Acunetix, Vega, etc.) that have not been validated
* Invalid or missing SPF (Sender Policy Framework) records (Incomplete or missing SPF/DKIM/DMARC)
* Reports on features flagged as experimental

