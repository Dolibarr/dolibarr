# TCPDF

> Legacy PDF engine for PHP. **DEPRECATED**: migrate to [tc-lib-pdf](https://github.com/tecnickcom/tc-lib-pdf).

[![Latest Stable Version](https://poser.pugx.org/tecnickcom/tcpdf/version)](https://packagist.org/packages/tecnickcom/tcpdf)
[![License](https://poser.pugx.org/tecnickcom/tcpdf/license)](https://packagist.org/packages/tecnickcom/tcpdf)
[![Downloads](https://poser.pugx.org/tecnickcom/tcpdf/downloads)](https://packagist.org/packages/tecnickcom/tcpdf)

[![Sponsor on GitHub](https://img.shields.io/badge/sponsor-github-EA4AAA.svg?logo=githubsponsors&logoColor=white)](https://github.com/sponsors/tecnickcom)

> 💖 **`tc-lib-pdf`** is the actively-developed successor to **TCPDF**, which is installed 100M+ times across 500+ PHP packages and is now maintenance-only. If your company depends on it, [become a sponsor](https://github.com/sponsors/tecnickcom) to keep this shared infrastructure secure and maintained. See [Sponsors](#sponsors) for tiers.


---

## Deprecation Notice

TCPDF is **DEPRECATED** and receives no updates of any kind: no new features, no bug fixes, and no security fixes.

All users are invited to migrate to [tc-lib-pdf](https://github.com/tecnickcom/tc-lib-pdf), the modern and modular successor.

Using TCPDF constitutes [CWE-1104: Use of Unmaintained Third Party Components](https://cwe.mitre.org/data/definitions/1104.html). See [SECURITY.md](SECURITY.md).

Instantiating the `TCPDF` class raises an `E_USER_DEPRECATED` notice once per process. The notice is raised with `@` so that PHP never prints it into the document output: it reaches custom error handlers and deprecation collectors, not the generated PDF. Define `TCPDF_SILENCE_DEPRECATION` before loading the library to disable it entirely:

```php
define('TCPDF_SILENCE_DEPRECATION', true);
```

Silencing the notice does not remove the need to migrate.

### Migration Path

- New projects: install `tecnickcom/tc-lib-pdf`. Do not start new work on TCPDF.
- Existing projects: plan the migration now and move in phases. Every release still on TCPDF carries unpatched defects, including security ones.
- Every migration requires regression checks to preserve rendering parity for existing documents.

### Why Migrate to tc-lib-pdf

- Modern architecture: modular libraries and cleaner component boundaries improve maintainability.
- Better extensibility: new features are easier to add without patching a monolithic legacy core.
- Stronger tooling fit: modern package structure works better with static analysis, CI, and automated tests.
- Lower long-term risk: reduces technical debt tied to legacy APIs and supports ongoing PHP ecosystem evolution.
- Improved delivery speed: teams can implement and ship new PDF capabilities with less friction.

---

## Overview

TCPDF is a pure-PHP library for generating PDF documents and barcodes directly in application code.

It has been widely used across many PHP stacks and covers text rendering, page composition, graphics, signatures, forms, and standards-oriented output.

The feature list below documents the frozen code as it stands. Nothing will be added to it, and nothing in it will be fixed. Use [tc-lib-pdf](https://github.com/tecnickcom/tc-lib-pdf) instead.

| | |
|---|---|
| **Package** | `tecnickcom/tcpdf` |
| **Author** | Nicola Asuni <info@tecnick.com> |
| **License** | [GNU LGPL v3](https://www.gnu.org/copyleft/lesser.html) (see [LICENSE.TXT](LICENSE.TXT)) |
| **Website** | <http://www.tcpdf.org> |
| **Source** | <https://github.com/tecnickcom/TCPDF> |

---

## Features

### Text & Fonts
- UTF-8 Unicode and right-to-left (RTL) language support
- TrueTypeUnicode, OpenTypeUnicode v1, TrueType, OpenType v1, Type1, and CID-0 fonts
- Font subsetting
- Text hyphenation, stretching, spacing, and rendering modes (fill/stroke/clipping)
- Automatic line breaks, page breaks, and justification

### Layout & Content
- Standard and custom page formats, margins, and measurement units
- XHTML + CSS rendering, JavaScript, and forms
- Automatic headers and footers
- Multi-column mode and no-write page regions
- Bookmarks, named destinations, and table of contents
- Automatic page numbering, page groups, move/delete pages, and undo transactions

### Images, Graphics & Color
- Native JPEG, PNG, and SVG support
- Geometric drawing primitives and transformations
- Support for GD image formats (`GD`, `GD2`, `GD2PART`, `GIF`, `JPEG`, `PNG`, `BMP`, `XBM`, `XPM`)
- Additional formats via ImageMagick (when available)
- JPEG/PNG ICC profiles, grayscale/RGB/CMYK/spot colors, and transparencies

### Security, Standards & Advanced Output
- Encryption up to 256-bit and digital signature certifications
- PDF annotations (links, text, and file attachments)
- 1D and 2D barcode support (including CODE 128, EAN/UPC, Datamatrix, QR Code, PDF417)
- XObject templates and layers with object visibility controls
- PDF/A-1b support

---

## Requirements

- PHP 7.1 or later
- `ext-curl`

Optional extensions for richer output in some workflows: `gd`, `zlib`, `imagick`.

Compatibility with future PHP versions will not be added.

---

## Third-Party Fonts

This library may include third-party font files released under different licenses.

PHP metadata files under [fonts](fonts) are covered by the TCPDF license (GNU LGPL v3). They contain font metadata and can also be generated using TCPDF font utilities.

Original binary TTF files are renamed for compatibility and compressed with PHP `gzcompress` (the `.z` format).

| Prefix | Source | License |
|---|---|---|
| `free*` | [GNU FreeFont](https://www.gnu.org/software/freefont/) | GNU GPL v3 |
| `pdfa*` | Derived from GNU FreeFont | GNU GPL v3 |
| `dejavu*` | [DejaVu Fonts](http://dejavu-fonts.org) | Bitstream/DejaVu terms |
| `ae*` | [Arabeyes.org](http://projects.arabeyes.org/) | GNU GPL v2 |

For full details, see the bundled notices in the corresponding subdirectories under [fonts](fonts).

---

## ICC Profile

TCPDF includes `sRGB.icc` from the Debian [`icc-profiles-free`](https://packages.debian.org/source/stable/icc-profiles-free) package.
