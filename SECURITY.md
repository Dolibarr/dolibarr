# Security Policy

This file contains policies about security reports for the Dolibarr ERP CRM project, a popular Open Source ERP and CRM used by millions of users.


## Supported Versions for security reports

Security reports are valid only on any current stable version for the last 2 major stable versions (see https://dolibarr.org web site to get current stable version) or on development version (branch "develop" on https://github.com/Dolibarr/dolibarr), and ONLY if the vulnerability is also confirmed in the "develop" branch (meaning the vulnerability was already reported).


## Reporting a Vulnerability

To report a vulnerability, for a private report, you can:

- Send your report as an issue on https://github.com/Dolibarr/dolibarr/issues or, if you have an allowed account, on GitHub Vulnerability Disclosure Program tool (VDP): https://github.com/Dolibarr/dolibarr/security/advisories (recommended). Submit only 1 report per vulnerability. Reports combining several vulnerabilities, as well as reports generated using AI, will be rejected. 

<!--
- Send your report on Vulnerability Disclosure Program (VDP) [https://app.yogosha.com/cvd/dolibarr/10VxeNx6Ui3rSEhAgX63US](https://app.yogosha.com/cvd/dolibarr/10VxeNx6Ui3rSEhAgX63US) (recommended for everybody)
- Or if you have permissions, use GitHub security advisory at [https://github.com/Dolibarr/dolibarr/security/advisories/new](https://github.com/Dolibarr/dolibarr/security/advisories/new)
-->

- Or send an email to security@dolibarr.org with clear textual description of the report along with steps to reproduce the issue, include attachments such as screenshots or proof of concept code as necessary (in such a case, the issue may be created by the developer that will fix the vulnerability or the Release Manager).

NOTE: Both processes are private vulnerability report processes: Advisories are sent to application end users by our own live channel (RSS at https://cti.dolibarr.org/index-security.rss, you can subscribe to it with any RSS reader). We do not publish CVE reports ourselves (we have no CNA number), but you are free to do it yourself.

Also, note that we are a project developed by volunteers and have no funds for bounties.


## Hunting vulnerabilities on Dolibarr

We believe that the future of software is online SaaS. This means software is more and more critical and no technology is perfect. Working with skilled security researchers is crucial in identifying weaknesses in our technology.

If you believe you've found a security bug in our service, we are happy to work with you to resolve the issue promptly.
We plan to re-open our bug bounty program (closed at the end of 2024) in the future, but this is not yet available.

Any type of denial-of-service attack is strictly forbidden, as well as any interference with network equipment and Dolibarr infrastructure.

We recommend installing Dolibarr ERP CRM on your own server (like most Open Source software, download and use is free: [https://www.dolibarr.org/download](https://www.dolibarr.org/download)) to get access to every side of the application.


### User Agent

If you try to find bugs on a dedicated hosted instance of Dolibarr, we recommend appending to your user-agent header the following value: '-securitytest-for-dolibarr'.


### Account access

You can install the web application yourself on your own platform/server to get full access to the application and sources. Download the zip file to install on your own web server virtual host from [https://www.dolibarr.org/download](https://www.dolibarr.org/download)


## Eligibility and Responsible Disclosure

We are happy to thank everyone who submits valid reports that help us improve the security of Dolibarr; however, only those that meet the following eligibility requirements will be validated as reports (if not, we may close the report without any answer):

You must be the first reporter of the vulnerability (duplicate reports are closed).

You must avoid tests that could cause degradation or interruption of our service (refrain from using automated tools, and limit yourself to requests per second); that's why we recommend installing the software on your own platform.

You must not leak, manipulate, or destroy any user data of third parties to find your vulnerability.

Reports are processed around once a month.


## Scope for qualified vulnerabilities

ONLY vulnerabilities discovered, when the following setup on test platform is used, are "valid":

* The version to analyze must be the last version available in the 'develop' branch. Also, reports on vulnerabilities already fixed (so already reported) in the 'develop' branch will not be validated.
* Installation must be done properly for a production usage. This includes:
** creation of the install.lock in the last step of installation process.
** $dolibarr_main_prod must be set to 1 in conf.php
** $dolibarr_nocsrfcheck must be kept to the value 0 in conf.php (this is the default value)
** $dolibarr_main_force_https must be set to something else than 0.
** The root of web server must link to htdocs and the documents directory must be outside of the web server root (this is the default when using the default installer but may differ with external installers).
** The web server setup must be done so that only the documents directory is in write mode and directory listing is not allowed. The directory path htdocs/ must be read-only.
** The modules DebugBar and ModuleBuilder must NOT be enabled. (by default, these modules are not enabled. They are developer tools)
** Fail2ban rules for rate limiting on the login page, forgotten password page, API calls and all public pages (/public/*) must be installed as recommended in the section "About - Admin tools - Section Access limits and mitigation".
* Some constants must be set in the backoffice menu Home - Setup - Other
  - MAIN_SECURITY_CSRF_WITH_TOKEN must be set to 3 
  - MAIN_RESTRICTHTML_ONLY_VALID_HTML = 1
  - MAIN_RESTRICTHTML_ONLY_VALID_HTML_TIDY = 1
  - MAIN_RESTRICTHTML_REMOVE_ALSO_BAD_ATTRIBUTES = 1 
  - MAIN_DISALLOW_URL_INTO_DESCRIPTIONS = 1 (only relative links are allowed in descriptions/notes), or 2 (no links are allowed in descriptions/notes)
  CSRF attacks and HTML injections are accepted, but double-check this setup, which is an experimental setup that already fixes a lot of cases and will soon be enabled by default.
* ONLY security reports on modules provided by default and with the "stable" status are valid (issues in "experimental", "development" or external modules are not valid vulnerabilities).


Scope is the web application (backoffice) and the APIs.


## Examples of vulnerabilities that are Qualified for reporting.

* Remote code execution (RCE)
* Local files access and manipulation (LFI, RFI, XXE, SSRF, XSPA)
* Code injections (JS, SQL, PHP). HTML is covered only for fields that are not description, notes or comments fields (where rich content is allowed on purpose).
* Cross-Site Scripting (XSS), except for the setup page of module "External web site" (allowing any content here, editable by admin user only, is accepted on purpose) and except 
  in the module "Web site" when permission to edit website content is allowed (injecting any data in this case is also allowed).
* Cross-Site Requests Forgery (CSRF) with real security impact (when using GET URLs, CSRF is qualified only for creating, updating or deleting data from pages restricted to admin users)
* Open redirect
* Broken authentication & session management
* Insecure direct object references (IDOR)
* Cross-Origin Resource Sharing (CORS) with real security impact
* Horizontal and vertical privilege escalation
* "HTTP Host Header" XSS
* Software version disclosure (for non-admin users only)
* Stack traces or path disclosure (for non-admin users only)
* The ability for a high-level user to edit web site pages in the CMS by including HTML or JavaScript is an expected feature. Vulnerabilities in the website module are only validated 
  if HTML or JavaScript injection can be done by a non-allowed user.


## Examples of vulnerabilities that are Non-qualified for reporting.

* Any vulnerabilities due to a configuration different than the one defined in chapter "Scope for qualified vulnerabilities".
* Directory Listing (this is a bad setup of the web server, not a problem in the application)
* "Self" XSS
* Clickjacking/UI redressing
* Presence of autocomplete attribute on web forms
* Logout and other instances of low-severity Cross-Site Request Forgery
* Reports from automated web vulnerability scanners (Acunetix, Vega, etc.) that have not been validated manually
* Reports about features in modules flagged as "deprecated", "experimental" or "development" if the module needs to be enabled for this (this is not the case on production).
* Software or libraries versions, private IP disclosure, Stack traces or path disclosure when the logged-in user is an admin.
* Vulnerabilities affecting outdated browsers or platforms, or vulnerabilities inside browsers themselves.
* Brute force attacks on login pages, password forgotten pages or any public pages (/public/*) are not qualified if the recommended fail2ban rules were not installed.
* SSL/TLS practices (cipher enabled or not)
* Invalid or missing SPF (Sender Policy Framework) records (Incomplete or missing SPF/DKIM/DMARC)
* Physical or social engineering attempts or issues that require physical access to a victim’s computer/device
* Vulnerabilities of type XSS exploited by using JavaScript in a website page of the website module are not vulnerabilities when the user has the permission "Edit page" (being able to set JavaScript in the CMS is the expected behavior in the website module).
* Vulnerabilities that allow running PHP code on the server in a website page are not vulnerabilities when the user has the superpermission "Edit PHP content in website page" (being able to run PHP code is the expected behavior in the website module), except if the command is a RCE command (and $dolibarr_website_allow_custom_php remains at 0 or 1).


## Be informed of a new vulnerability

You can get more information on how to be informed on a new vulnerability on the page https://wiki.dolibarr.org/index.php/Security_information
