# Subtotals module — free text lines with predefined-text dictionary

Date: 2026-07-30
Module: `subtotals` (core module, `htdocs/subtotals/`, `htdocs/core/modules/modSubtotals.class.php`)
Builds on: `docs/superpowers/specs/2026-07-27-subtotals-predefined-phrases-design.md` (predefined phrases for title lines)

## Problem

The `subtotals` module currently supports two special line kinds on a document
(quote, order, invoice, shipment, ...): "title" lines (section headers,
`qty > 0`, encoding a nesting level) and "subtotal" lines (the closing
running-total row for a title, `qty < 0`). There is no way to insert a free
paragraph of text (a note, delivery instructions, legal wording, ...) as its
own row among the product lines.

## Goal

Add a third kind of subtotals-module line: a free-text line. It behaves like
an independent note — no nesting level, no corresponding closing line, no
effect on any subtotal calculation, insertable anywhere among a document's
lines. Content can span multiple lines/paragraphs. An admin can maintain a
dictionary of predefined texts (Setup > Dictionaries) that users pick from to
prefill the text area when adding or editing a text line; the field stays
freely editable afterward, exactly like the existing predefined-phrases
dropdown for title lines.

Available on the same 9 document types the module already supports: `Facture`,
`FactureRec`, `Propal`, `Commande`, `Expedition`, `SupplierProposal`,
`CommandeFournisseur`, `FactureFournisseur`, `Fichinter`.

## Data representation

A text line reuses the existing `special_code = SUBTOTALS_SPECIAL_CODE` /
`product_type = CommonSubtotal::$PRODUCT_TYPE` (9) marking, with **`qty = 0`**
as the discriminator: title lines use `qty > 0` (the level), subtotal lines
use `qty < 0` (minus the level they close), text lines use exactly `0` (no
level, nothing to close).

This is the lowest-risk representation given how deeply `qty`'s sign is
already relied on throughout the codebase (23 files reference
`SUBTOTALS_SPECIAL_CODE`). Auditing every one of them:

- All PDF generators (`htdocs/core/modules/{facture,commande,propale,expedition,supplier_proposal,supplier_order,supplier_invoice}/doc/*.modules.php`)
  suppress price/qty/vat/discount/unit columns for **any** line with
  `special_code == SUBTOTALS_SPECIAL_CODE`, regardless of `qty`'s sign — a
  text line already renders full-width, no code change needed there.
- The shared renderer `pdf_render_subtotals()` (`htdocs/core/lib/pdf.lib.php`)
  only special-cases `qty < 0` (the "Subtotal of %s:" prefix) — a `qty = 0`
  line falls through to plain description rendering, no change needed.
- `CommonSubtotal::getSubtotalLineAmount()` / `getSubtotalLineMulticurrencyAmount()`
  (the running-total calculators) only special-case `qty > 0` lines as
  level-closing boundaries; a `qty = 0` line falls into the "add
  `total_ht`" branch, and a text line's `total_ht` is always 0 (unit price is
  never set), so it contributes nothing — no change needed.
- `CommonSubtotal::getPossibleTitles()` / `getDisabledShippmentSubtotalLines()`
  only look at `qty > 0` (or `qty <= 0` as "not a title"); a text line is
  already correctly excluded — no change needed.
- The drag-and-drop reorder validation in `htdocs/core/tpl/subtotal_ajaxrow.tpl.php`
  branches on `rowLevel > 0` / `rowLevel < 0`; for `rowLevel === 0` neither
  branch runs and no validation error is raised, so a text line can already be
  dragged anywhere — no change needed.
- The generic `htdocs/core/tpl/objectline_view.tpl.php` /
  `objectline_edit.tpl.php` / `htdocs/core/class/commonobject.class.php`
  delegate rendering of any `special_code == SUBTOTALS_SPECIAL_CODE` line to
  the module's own `htdocs/core/tpl/subtotal_view.tpl.php` /
  `subtotal_edit.tpl.php` — confirming those two files (plus
  `subtotal_create.tpl.php` for creation) are the only real integration
  points that need new code.

The only files that actually need a new branch are the three named above
(`subtotal_view.tpl.php`, `subtotal_create.tpl.php`, `subtotal_edit.tpl.php`),
plus `CommonSubtotal::addSubtotalLine()` / `updateSubtotalLine()` (to accept
`$depth == 0` and skip the level/rang placement rules that only make sense
for title/subtotal pairs), plus the 9 `card.php` files' action-button
dropdowns (add an "Add text line" entry) and action handlers (dispatch to a
new `add_text_line` action).

## Predefined-text dictionary

New table `llx_c_subtotals_texts`:

| column | type | notes |
|---|---|---|
| rowid | integer AUTO_INCREMENT PK | |
| entity | integer DEFAULT 1 NOT NULL | multi-company scoping, same convention as `c_subtotals_phrases` |
| code | varchar(32) | technical code |
| label | varchar(255) | short name shown in the dictionary list and in the picker dropdown |
| content | mediumtext | the multi-line predefined text |
| active | tinyint DEFAULT 1 NOT NULL | |

Unique index on `(entity, code)`. Loaded via the same
`DolibarrModules::_load_tables('/install/mysql/', 'subtotals')` call already
wired into `modSubtotals::init()` (from the predefined-phrases feature) — no
additional module wiring needed, adding the new `-subtotals.` suffixed SQL
files is enough for both fresh installs and the upgrade path (also already
registered in `htdocs/install/upgrade2.php`'s module-reload list from that
same prior work).

Registered as a second entry in `modSubtotals::$dictionaries` (the array
already built for phrases gains a second table), labeled "Subtotals
predefined texts", so it appears as its own tab under Setup > Dictionaries
next to "Subtotals predefined phrases".

`content` is rendered as a `<textarea>` in the dictionary's generic
create/edit forms. `htdocs/admin/dict.php` already special-cases one field
name (`libelle_facture`, used by the payment-terms dictionary) to render as a
textarea instead of the default single-line `<input>`; `content` is added to
that same special-casing (both the "create" form block and the
`dictFieldList()` edit-row function), rather than introducing a new
mechanism.

`CommonSubtotal` gains `getPredefinedTexts()`, mirroring
`getPredefinedPhrases()`: queries `llx_c_subtotals_texts` filtered on
`active = 1` and the current entity, returns rows keyed by `rowid` with
`label` and `content`, sorted by `label`. Unlike the phrases dropdown (whose
option value directly holds the label, since that's exactly what gets
copied), the text picker's `<option>` displays `label` but must copy
`content` (a different, potentially long, multi-line string) into the
textarea — so the create/edit templates key the `<select>` by `rowid` and
carry the `rowid → content` lookup as a small inline JSON map for the
`onchange` handler to read, instead of stuffing the full text into an HTML
attribute.

## UI

**Action buttons**: each of the 9 `card.php` files' "Subtotal" dropdown
button (currently "Add title line" / "Add subtotal line") gains a third
entry, "Add text line", following the same `$usercancreate` /
`SUBTOTAL_TITLE_<ELEMENT>`-style enable-condition pattern as the other two
(gated on its own new constant, e.g. `SUBTOTAL_TEXT_<ELEMENT>`, configurable
like the existing title/subtotal per-document-type toggles in the module's
admin page).

**Creation** (`subtotal_create.tpl.php`, new `$type == 'text'` branch): a
`select` ("Predefined text", optional — only shown if the dictionary has
active entries) followed by a `textarea` for the content. Choosing a
dictionary entry copies its `content` into the textarea via the `onchange` +
JSON-map lookup described above; the textarea remains fully editable
afterward and is what actually gets submitted. No PDF display checkboxes
(title lines have three: show unit price, show total excl. VAT, force page
break — text lines start with none, since they carry no computed total and
aren't the object of a page-break policy; this can be revisited later if a
real need appears).

**Inline edit** (`subtotal_edit.tpl.php`, new branch parallel to the existing
`title`/`subtotal` cases): the current single-line `<input>` is replaced by a
`<textarea>` plus the same predefined-text `<select>`, only for
`$line_type == 'text'` — the existing `title`/`subtotal` branches are
untouched.

**Display** (`subtotal_view.tpl.php`, new `elseif ($line->qty == 0)`
branch alongside the existing `if ($line->qty > 0)` / `elseif ($line->qty <
0)`): the text content prints full width, no background color banding (no
level, so no `getSubtotalColors()` call), with the same edit/delete/move
icons the other two kinds already get from the shared code below the
type-specific branch.

## Per-document-type line creation

**The 8 standard types** (`Facture`, `FactureRec`, `Propal`, `Commande`,
`SupplierProposal`, `CommandeFournisseur`, `FactureFournisseur`,
`Fichinter`): `CommonSubtotal::addSubtotalLine()` / `updateSubtotalLine()`
gain handling for `$depth == 0` — skip the title/subtotal rang-placement
scan entirely (those rules find where to slot a title into existing nesting,
which doesn't apply to a level-less text line) and let the line append
through the same `addline()`/`updateline()` calls already used for title and
subtotal lines, with `$depth` (`0`) passed as `qty`.

**Expedition**: shipment lines are structurally different — the module's
existing shipping branch of `addSubtotalLine()` calls
`$this->addline(0, (int) $parent_line, $depth)`, which requires a source
order line (`$parent_line`) to copy from, since every normal shipment line
represents a shipped quantity of a specific ordered product. A text note has
no such source. Instead, the shipping branch of `addSubtotalLine()` calls
`Expedition::addlinefree()` — an existing core method
(`htdocs/expedition/class/expedition.class.php`) that already inserts a
description-only `ExpeditionLigne` with no product/source-line requirement —
then sets `special_code = SUBTOTALS_SPECIAL_CODE` on the resulting line
(`addlinefree()` doesn't accept `special_code` as a parameter, so this is one
extra property set + save after the insert).

## Language keys

New keys in `langs/en_US/subtotals.lang` and `langs/fr_FR/subtotals.lang`:
`AddTextLine`, `SubtotalsPredefinedTexts` (dictionary tab name),
`PredefinedText` (picker label), plus whatever admin-page label is needed for
the new per-document-type `SUBTOTAL_TEXT_<ELEMENT>` enable toggles
(mirroring the existing title/subtotal ones in `admin/subtotals.php`).

## Testing

No PHPUnit suite exists for this module (same situation as the
predefined-phrases feature) — verified manually:

1. Enable/disable the module (or apply the upgrade path) and confirm
   `llx_c_subtotals_texts` is created.
2. In Setup > Dictionaries, find "Subtotals predefined texts", add/edit a
   multi-line entry via the textarea, confirm it round-trips correctly.
3. On each of the 9 supported document types, add a text line: with and
   without picking a predefined text, confirm the content saves and displays
   correctly, full width, no background color.
4. Edit an existing text line inline; confirm the predefined-text picker and
   textarea both work the same way as on creation.
5. Drag a text line around the line list; confirm it can be moved freely
   without triggering the title/subtotal placement-validation messages.
6. Generate a PDF for a document containing a text line; confirm it renders
   full width with no price/qty columns, and that pre-existing title/subtotal
   rendering is unaffected.
7. Confirm a text line contributes nothing to any subtotal's running total.
8. On a shipment (Expedition), add a text line via `addlinefree()` and
   confirm it displays and behaves the same as on the other document types.
