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

The upstream library had several bugs that made its (already non-default,
opt-in) checkbox/radio support, and its field name matching, silently fail
on real-world AcroForms. All are fixed in this vendored copy; every change
is marked inline with `// @CHANGE DOL` and also documented in
`dev/dolibarr_changes.txt` at the repository root:

1. The `/D` (down-appearance) detector used a 2-character prefix match that
   also matched `/DA` (Default Appearance), present on nearly every field -
   checkbox/radio "on" state names were never extracted.
2. Fields declared with the standard PDF "field root + `/Kids` widgets"
   structure (radio groups, or a checkbox/field shared across several pages)
   were not recognized at all (`field ... not found`).
3. A field name containing an accented character stored as plain 8-bit
   Latin-1/WinAnsi (common in Word-produced PDFs) was corrupted into `?`
   by upstream's own "convert to utf-8" fix and could never be matched.
4. A checkbox/radio widget that only defines `/N` (no optional `/D`) had
   its "on"/"off" state names never extracted at all; and the "on"/"off"
   keys were told apart by position (first vs. second) rather than by
   name, which does not hold for every PDF producer.
5. A checkbox/radio "on" token containing a space or an accented character
   is hex-escaped in the PDF (e.g. `/Accident#20vie#20priv#8ee`); this was
   truncated at the first escape, so two different widgets on the same
   field could silently report the same truncated "on" token.

Also added, not present upstream: a public `ListFields()` method (see
[Usage](#usage) below) and the `/FT` capture it relies on to tell an actual
field root apart from any other named PDF object (adding it surfaced a bug
of its own: the field-root branch from fix 2 above registered 274 phantom
"fields" on the 828-field MDPH form below, from tagged-PDF structure
elements that also carry a `/T`; now gated on `/FT` being present too).

## Usage

```php
require_once DOL_DOCUMENT_ROOT.'/includes/fpdm/fpdm.php';

$pdf = new FPDM($pathToSourcePdf);

// Only needed if the form has checkboxes/radio buttons (off by default
// upstream; the Dolibarr patches above are what make this reliable):
$pdf->useCheckboxParser = true;

// Discover what's actually in the PDF instead of shelling out to
// `pdftk file.pdf dump_data_fields_utf8` - no data is changed.
foreach ($pdf->ListFields() as $name => $info) {
	// $info = ['type' => 'Text'|'Button'|'Signature'|'Choice', 'maxlen' => int, 'options' => string[]]
	// 'options' is only meaningful for 'Button' (its "on"/"off" tokens, "Off" always included).
}

// $isUTF8 = true lets you pass UTF-8 field values (accents, etc).
$pdf->Load(array(
	'TextFieldName'     => 'Some value',
	'CheckboxFieldName' => 'OnStateName',   // the field's own "on" token (from ListFields() above), not just "1"/true
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
  not a runtime dependency - `ListFields()` cannot work around it either,
  since it shares the same underlying line-based parser.
- A checkbox/radio "on" token from `ListFields()` may contain a character
  that doesn't print cleanly (e.g. a byte the source PDF escaped using a
  charset other than Latin-1/WinAnsi) - this is cosmetic only: the value
  is still the correct, unique token to pass back to `Load()` to tick that
  specific widget.
