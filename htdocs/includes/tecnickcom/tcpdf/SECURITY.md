# Security Policy (TCPDF 6.x)

This policy covers the TCPDF 6.x series, the code shipped in this package.

## CRITICAL NOTICE

**TCPDF 6.x is DEPRECATED, unmaintained, and receives no security fixes.**

No security patches, advisories, or CVE remediations will be issued for this series. Known and unknown vulnerabilities in this code base will remain unfixed.

Using TCPDF 6.x in production constitutes [CWE-1104: Use of Unmaintained Third Party Components](https://cwe.mitre.org/data/definitions/1104.html).

## Structural Vulnerability Classes

TCPDF 6.x contains multiple recurring vulnerability classes that are inherent to its design. Individual reports in these classes have been patched over the years, and equivalent issues keep reappearing: the exposure comes from the public API contract, not from isolated defects. Removing these classes requires changing documented behaviour that existing applications depend on, so they cannot be permanently fixed within 6.x without breaking backward compatibility.

The list below describes structural exposure. It is not an inventory of specific known defects.

| Class | CWE | Origin in TCPDF 6.x |
|---|---|---|
| Server-side request forgery | [CWE-918](https://cwe.mitre.org/data/definitions/918.html) | Images, fonts, and other resources are fetched from caller-supplied URLs through cURL and stream functions. |
| Path traversal and local file disclosure | [CWE-22](https://cwe.mitre.org/data/definitions/22.html), [CWE-73](https://cwe.mitre.org/data/definitions/73.html) | Filesystem paths supplied by the caller are opened directly for images, fonts, ICC profiles, and templates. |
| Unsafe stream wrappers and object injection | [CWE-502](https://cwe.mitre.org/data/definitions/502.html) | Path arguments reach PHP file APIs, so wrapper schemes such as `phar://` are reachable from caller-controlled strings. |
| XML external entities and entity expansion | [CWE-611](https://cwe.mitre.org/data/definitions/611.html), [CWE-776](https://cwe.mitre.org/data/definitions/776.html) | SVG input is processed by an XML parser. |
| Uncontrolled resource consumption | [CWE-400](https://cwe.mitre.org/data/definitions/400.html), [CWE-674](https://cwe.mitre.org/data/definitions/674.html), [CWE-1333](https://cwe.mitre.org/data/definitions/1333.html) | HTML, CSS, and SVG are parsed with recursive descent and extensive regular expressions over unbounded input. |
| Unsafe parsing of binary input | [CWE-20](https://cwe.mitre.org/data/definitions/20.html) | Font, image, and barcode data are decoded with minimal validation of lengths and offsets. |
| Broken or risky cryptography | [CWE-327](https://cwe.mitre.org/data/definitions/327.html) | The document encryption API still exposes the legacy RC4 and MD5 based PDF security handlers. |
| Insecure temporary files | [CWE-377](https://cwe.mitre.org/data/definitions/377.html) | Intermediate data is written to a shared cache directory configured by a global constant. |
| Injection into generated documents | [CWE-94](https://cwe.mitre.org/data/definitions/94.html), [CWE-116](https://cwe.mitre.org/data/definitions/116.html) | Embedded JavaScript, annotations, links, and form actions are assembled from caller-supplied strings. |
| Insecure defaults | [CWE-1188](https://cwe.mitre.org/data/definitions/1188.html) | Behaviour is driven by global `K_*` constants whose permissive defaults enable remote and filesystem access. |

## Supported Versions

| Version | Supported |
|---|---|
| 6.x | ❌ No |
| < 6.x | ❌ No |

No release in the 6.x series or earlier is supported.

## Reporting a Vulnerability

Vulnerability reports against TCPDF 6.x are not accepted and will not be triaged, patched, or assigned an advisory.

Report vulnerabilities affecting the maintained successor, [tc-lib-pdf](https://github.com/tecnickcom/tc-lib-pdf), through that project's security policy.

## Required Action

Migrate to [tc-lib-pdf](https://github.com/tecnickcom/tc-lib-pdf).

Until migration is complete, treat every TCPDF 6.x deployment as an accepted risk:

- Never pass untrusted input (HTML, images, fonts, URLs, file paths) to TCPDF.
- Run PDF generation in an isolated process with no network access and restricted filesystem permissions.
- Disable remote resource fetching and PHP stream wrappers where possible.
- Record the exposure in the project risk register.
