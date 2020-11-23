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

ONLY vulnerabilities discovered, when the following setup on test platform is used, are accepted:

* $dolibarr_main_prod must be set to 1 into conf.php
* $dolibarr_nocsrfcheck must be kept to the value 0 into conf.php (this is the default value)
* $dolibarr_main_force_https must be set to something else than 0.
* The constant MAIN_SECURITY_CSRF_WITH_TOKEN must be set to 1 into backoffice menu Home - Setup - Other (this protection should be enabled soon by default)
* The module DebugBar must NOT be enabled (by default, this module is not enabled. This is a developer tool)
* The module ModuleBuilder must NOT be enabled (by default, this module is not enabled. This is a developer tool)
* ONLY security reports on modules provided by default and with the "stable" status are allowed (troubles into "experimental", "developement" or external modules are not accepted).
* The root of web server must link to htdocs and the documents directory must be outside of the web server root (this is the default when using the default installer but may differs with external installer).
* The web server setup must be done so only the documents directory is in write mode. The root directory called htdocs must be readonly.
* CSRF attacks are accepted for all when using a POST URL, but are accepted only for creating or updating data resctricted to the admin user when using GET URL.
* Ability for a high level user to edit web site pages in the CMS by including javascript is an expected feature.

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
* Software version disclosure (for non admin users only)
* Stack traces or path disclosure (for non admin users only)


## Non-qualifying vulnerabilities for Bug bounty programs, but qualified for reporting
* "Self" XSS
* SSL/TLS best practices
* Denial of Service attacks
* Clickjacking/UI redressing
* Physical or social engineering attempts or issues that require physical access to a victim’s computer/device
* Presence of autocomplete attribute on web forms
* Vulnerabilities affecting outdated browsers or platforms
* Logout and other instances of low-severity Cross-Site Request Forgery
* Missing cookie flags
* Missing security-related HTTP headers which do not lead directly to a vulnerability
* Reports from automated web vulnerability scanners (Acunetix, Vega, etc.) that have not been validated
* Invalid or missing SPF (Sender Policy Framework) records (Incomplete or missing SPF/DKIM/DMARC)
* Reports on features flagged as "experimental" or "development"
* Software version or private IP disclosure when logged user is admin
* Stack traces or path disclosure when logged user is admin
* Any vulnerabilities due to a configuration different than the one defined into chapter "Scope for qualified vulnerabilities".

