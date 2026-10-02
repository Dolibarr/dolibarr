# FPDM - PDF AcroForm filling

Pure PHP library to fill the fields of an existing PDF form (AcroForm), without
calling any external binary (no `pdftk`, no Ghostscript). Used by Dolibarr to
fill official third-party PDF forms (e.g. French Cerfa tax forms) that embed
real AcroForm fields, as opposed to flat/static PDFs which still need to be
reconstructed as a document model (ODT/HTML/TCPDF) instead.

- Upstream: https://github.com/codeshell/fpdm (packagist `tmw/fpdm`)
- Original author: Olivier Plathey (FPDF), maintained fork by codeshell
- Version: 2.9.2 (2017-05-11 base, `tmw/fpdm` packagist history) + Dolibarr
  patches, see below
- License: MIT (see [LICENSE](LICENSE))

## Dolibarr-specific patches

The upstream library had two bugs that made its (already non-default,
opt-in) checkbox/radio support silently fail on real-world AcroForms. Both
are fixed in this vendored copy; every change is marked inline with
`// @CHANGE DOL` and also documented in `dev/dolibarr_changes.txt` at the
repository root:

1. The `/D` (down-appearance) detector used a 2-character prefix match that
   also matched `/DA` (Default Appearance), present on nearly every field -
   checkbox/radio "on" state names were never extracted.
2. Fields declared with the standard PDF "field root + `/Kids` widgets"
   structure (radio groups, or a checkbox/field shared across several pages)
   were not recognized at all (`field ... not found`).

## Usage

```php
require_once DOL_DOCUMENT_ROOT.'/includes/fpdm/fpdm.php';

$pdf = new FPDM($pathToSourcePdf);

// Only needed if the form has checkboxes/radio buttons (off by default
// upstream; the Dolibarr patches above are what make this reliable):
$pdf->useCheckboxParser = true;

// $isUTF8 = true lets you pass UTF-8 field values (accents, etc).
$pdf->Load(array(
	'TextFieldName'     => 'Some value',
	'CheckboxFieldName' => 'OnStateName',   // the field's own "on" token, not just "1"/true
), true);

$pdf->Merge();
$pdf->Output('F', $pathToDestinationPdf);  // 'F' = save to disk, 'I'/'D' also supported (see FPDF)
```

Notes:

- The source PDF must **not** be a linearized ("Fast Web View") PDF, and must
  not use compressed cross-reference streams/object streams (PDF 1.5+
  xref streams, or an "Optimized" PDF per `pdfinfo`) - FPDM's parser is a
  simple, non-compressed line-based PDF reader. If the PDF you need to fill
  is optimized/linearized, decompress it once when adding it to your module
  (e.g. `pdftk in.pdf output out.pdf uncompress`) and ship the decompressed
  copy as the template asset; this is a one-time step on the template file,
  not a runtime dependency.
- A text field's "on"/"off" tokens for a checkbox or radio button are
  whatever name the PDF itself defines (commonly `1`/`Off`, `Oui`/`Off`,
  `A`/`Off`...) - inspect the target PDF once (e.g.
  `pdftk file.pdf dump_data_fields_utf8`) to know which value to pass.
