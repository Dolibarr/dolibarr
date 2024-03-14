# Security Policy

This file contains some policies about the security reports on Dolibarr ERP CRM project, one of the most popular Open Source ERP and CRM in the world.

## Supported Versions for security reports

Security report are valid only on current stable version (see https://dolibarr.org web site to get current stable version) or on development version (branch "develop" on https://github.com/Dolibarr/dolibarr).

## Reporting a Vulnerability

To report a vulnerability, for a private report, you can:

- Send your report on [https://app.yogosha.com/cvd/dolibarr/10VxeNx6Ui3rSEhAgX63US](https://app.yogosha.com/cvd/dolibarr/10VxeNx6Ui3rSEhAgX63US) (recommended for everybody)
- Or if you have permissions, use GitHub security advisory at [https://github.com/Dolibarr/dolibarr/security/advisories/new](https://github.com/Dolibarr/dolibarr/security/advisories/new)
- Or send by email to security@dolibarr.org a clear textual description of the report along with steps to reproduce the issue, include attachments such as screenshots or proof of concept code as necessary

## Hunting vulnerabilities on Dolibarr

We believe that the future of software is online SaaS. This means software are more and more critical and no technology is perfect. Working with skilled security researchers is crucial in identifying weaknesses in our technology.

If you believe you've found a security bug in our service, we are happy to work with you to resolve the issue promptly and ensure you are fairly rewarded for your discovery.

Any type of denial of service attacks is strictly forbidden, as well as any interference with network equipment and Dolibarr infrastructure.

We recommand to install Dolibarr ERP CRM on your own server (as most Open Source software, download and use is free: [https://www.dolibarr.org/download](https://www.dolibarr.org/download)) to get access on every side of application.

### User Agent

If you try to find bug on Dolibarr, we recommend to append to your user-agent header the following value: '-securitytest-for-dolibarr'.

### Account access

You can install the web application yourself on your own platform/server so you get full access to application and sources. Download the zip of the files to put into your own web server virtual host from [https://www.dolibarr.org/download](https://www.dolibarr.org/download)

## Eligibility and Responsible Disclosure

We are happy to thank everyone who submits valid reports which help us improve the security of Dolibarr, however only those that meet the following eligibility requirements will be "validated reports" (if not, we may close the report without any answer):

You must be the first reporter of the vulnerability (duplicate reports are closed).

You must avoid tests that could cause degradation or interruption of our service (refrain from using automated tools, and limit yourself about requests per second), that's why we recommand to install software on your own platform.

You must not leak, manipulate, or destroy any user data of third parties to find your vulnerability.

Reports are processed around once a month.

## Scope for qualified vulnerabilities

ONLY vulnerabilities discovered, when the following setup on test platform is used, are "valid":

* The version to analyze must be the last version available into "develop" branch or into last stable "vX.Y" released version.  
* $dolibarr_main_prod must be set to 1 into conf.php
* $dolibarr_nocsrfcheck must be kept to the value 0 into conf.php (this is the default value)
* $dolibarr_main_force_https must be set to something else than 0.
* The constant MAIN_SECURITY_CSRF_WITH_TOKEN must be set to 3 into backoffice menu Home - Setup - Other (this protection should be set to 3 soon by default). CSRF attacks are accepted but
 double check that you have set MAIN_SECURITY_CSRF_WITH_TOKEN to value 3.
* ONLY security reports on modules provided by default and with the "stable" status are valid (troubles into "experimental", "developement" or external modules are not valid vulnerabilities).
* The root of web server must link to htdocs and the documents directory must be outside of the web server root (this is the default when using the default installer but may differs with external installer).
* The web server setup must be done so that only the documents directory is in write mode. The root directory called htdocs must be read-only.
* The modules DebugBar and ModuleBuilder must NOT be enabled. (by default, these modules are not enabled. They are developer tools)
* Ability for a high level user to edit web site pages into the CMS by including HTML or Javascript is an expected feature. Vulnerabilities into the website module are validated only if HTML or Javascript injection can be done by a non allowed user.
* Fail2ban rules for rate limit on the login page,password forgotten page, api calls and all public pages (/public/*) must be installed as recommendend into the section "About - Admin tools - Section Access limits and mitigation".

Scope is the web application (back office) and the APIs.

## Examples of vulnerabilities that are Qualified for reporting.

* Remote code execution (RCE)
* Local files access and manipulation (LFI, RFI, XXE, SSRF, XSPA)
* Code injections (JS, SQL, PHP). HTML are covered only for fields that are not description, notes or comments fields (where rich content is allowed on purpose).
* Cross-Site Scripting (XSS), except from setup page of module "External web site" (allowing any content here, editable by admin user only, is accepted on purpose) and except into module "Web site" when permission to edit website content is allowed (injecting any data in this case is allowed too).
* Cross-Site Requests Forgery (CSRF) with real security impact (when using GET URLs, CSRF are qualified only for creating, updating or deleting data from pages restricted to admin users)
* Open redirect
* Broken authentication & session management
* Insecure direct object references (IDOR)
* Cross-Origin Resource Sharing (CORS) with real security impact
* Horizontal and vertical privilege escalation
* "HTTP Host Header" XSS
* Software version disclosure (for non admin users only)
* Stack traces or path disclosure (for non admin users only)

## Examples of vulnerabilities that are Non-qualified for reporting.

* "Self" XSS
* SSL/TLS best practices
* Denial of Service attacks
* Clickjacking/UI redressing
* Physical or social engineering attempts or issues that require physical access to a victim’s computer/device
* Presence of autocomplete attribute on web forms
* Vulnerabilities affecting outdated browsers or platforms, or vulnerabilities inside browsers themself.
* Logout and other instances of low-severity Cross-Site Request Forgery
* Missing security-related HTTP headers which do not lead directly to a vulnerability
* Reports from automated web vulnerability scanners (Acunetix, Vega, etc.) that have not been validated
* Invalid or missing SPF (Sender Policy Framework) records (Incomplete or missing SPF/DKIM/DMARC)
* Reports on features flagged as "experimental" or "development"
* Software version or private IP disclosure when logged-in user is admin
* Stack traces or path disclosure when logged-in user is admin
* Any vulnerabilities due to a configuration different than the one defined into chapter "Scope for qualified vulnerabilities".
* Brute force attacks on login page, password forgotten page or any public pages (/public/*) are not qualified if the fail2ban recommended fail2ban rules were not installed.  
* Vulnerabilities of type XSS exploited by using javascript into a website page (with permission to edit website pages) or by using php code into a website page
  using the permission to edit php code are not qualified, except if this allow to get higher privileges (being able to set javascript or php code is the expected behaviour).
