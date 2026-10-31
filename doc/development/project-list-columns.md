# Project reference and label columns

Objects directly linked to a project offer adjacent `RefProject` and `ProjectLabel`
columns in their native lists. Existing reference selections and field keys are
preserved. New labels and newly added references are hidden by default. Both values
use `Project::getNomUrl()`; Ajax tooltips follow `MAIN_ENABLE_AJAX_TOOLTIP`.

Joins are restricted with `Project::getProjectsAuthorizedForUser()` after a direct
`hasRight('projet', 'lire')` check. Missing or inaccessible projects leave empty cells
without hiding otherwise accessible business objects. Search and sorting use the
restricted join. Existing object, third-party and entity restrictions are retained.

This inventory covers the complete contribution, delivered as four independent PRs:
sales, purchases/logistics, finance/agenda, and generic/event lists. Each PR changes
at most ten files. Matrix results describe the combined patch; individual branches
only implement their own listed files. The regression runner defaults to the lists
already implemented in the checkout; explicit path arguments select a required list.

## Coverage matrix

Paths are relative to `htdocs/`. Inventory based on develop commit
`11bb761c7f626c5c4576493d44c519809f506094`, inspected on 2026-10-13.

| List | Direct relation | Previous columns | Verification |
| --- | --- | --- | --- |
| `fourn/commande/list.php` | `cf.fk_projet` | Reference / combined column | Isolated regression |
| `fourn/facture/list.php` | `f.fk_projet` | Reference / combined column | Isolated regression |
| `supplier_proposal/list.php` | `sp.fk_projet` | Reference / combined column | Isolated regression |
| `compta/sociales/list.php` | `cs.fk_projet` | Reference / combined column | Isolated regression |
| `comm/propal/list.php` | `p.fk_projet` | Reference and label | Isolated regression |
| `commande/list.php` | `c.fk_projet` | Reference and label | Isolated regression |
| `commande/list_det.php` | `c.fk_projet` | Reference and label | Isolated regression |
| `compta/facture/list.php` | `f.fk_projet` | Reference and label | Isolated regression |
| `product/stock/movement_list.php` | `m.fk_projet` | Reference / combined column | Isolated regression |
| `compta/bank/various_payment/list.php` | `v.fk_projet` | Reference / combined column | Isolated regression |
| `contrat/list.php` | `c.fk_projet` | Absent | Isolated regression |
| `expedition/list.php` | `e.fk_projet` | Absent | Isolated regression |
| `reception/list.php` | `e.fk_projet` | Absent | Isolated regression |
| `fichinter/list.php` | `f.fk_projet` | Absent | Isolated regression |
| `product/list.php` | `p.fk_project` | Absent | Isolated regression |
| `compta/facture/invoicetemplate_list.php` | `f.fk_projet` | Absent | Isolated regression |
| `fourn/facture/list-rec.php` | `f.fk_projet` | Absent | Isolated regression |
| `comm/action/list.php` | `a.fk_project` | Absent | Isolated regression |
| `loan/list.php` | `l.fk_projet` | Absent | Isolated regression |
| `salaries/list.php` | `s.fk_projet` | Absent | Isolated regression |
| `ticket/list.php` | `t.fk_project` | Reference from object metadata | Isolated regression |
| `mrp/mo_list.php` | `t.fk_project` | Reference from object metadata | Isolated regression |
| `product/stock/list.php` | `t.fk_project` | Reference from object metadata | Isolated regression |
| `recruitment/recruitmentjobposition_list.php` | `t.fk_project` | Reference from object metadata | Isolated regression |
| `bookcal/calendar_list.php` | `t.fk_project` | Reference from object metadata | Isolated regression |
| `product/stock/stocktransfer/stocktransfer_list.php` | `t.fk_project` | Reference from object metadata | Isolated regression |
| `eventorganization/conferenceorbooth_list.php` | `t.fk_project` | Reference from object metadata | Isolated regression |
| `eventorganization/conferenceorboothattendee_list.php` | `t.fk_project` | Reference from object metadata | Isolated regression |
| `salaries/payments.php` | `s.fk_projet` | Absent | Isolated regression |
| `accountancy/expensereport/list.php` | `erd.fk_projet` | Absent | Isolated regression |
| `fourn/product/list.php` | `p.fk_project` | Absent | Isolated regression |
| `don/list.php` | `d.fk_projet` | Reference / combined column | Isolated regression |
| `comm/mailing/list.php` | `m.fk_project` | Reference / combined column | Isolated regression |
| `bookcal/booking_list.php` | `ac.fk_project` | Absent | Isolated regression |
| `projet/tasks/list.php` | `t.fk_projet` | Reference and label | Native scope retained; link analyzed |

The project list already exposes its own reference and label. Public ticket lists
intentionally do not expose authenticated project information. Indirect relations
through payments, contracts, applications or other parents are excluded. Booking
availabilities have no direct project relation. Legacy travel expenses and recurring
interventions have no active standalone native list in this revision. Expense report
headers can contain lines belonging to different projects; the accounting list of
expense lines is covered. Dashboard widgets and card line editors are excluded.

## Reproducible checks

With Python 3 and PHP on PATH, from the repository root:

```sh
python test/other/test_project_list_columns.py
```

The test executes changed PHP column fragments and native project methods. Generated
SQL runs against an in-memory SQLite fixture with a non-default table prefix. The
1,462 checks cover private/assigned projects, all-project permissions, shared and
inaccessible entities, external third-party scope, denied permissions, a disabled
Project module, label defaults, reference-only/label-only/hidden selections, missing
projects, special characters, combined filters and an SQL injection input. Native
Ajax markup and non-Ajax tooltips are exercised. No instance data is written.

These are **isolated regression checks**, with fixture users/database/hooks, not
validation of a deployed Dolibarr or the Multicompany implementation. They do not
execute every original list query against MySQL/PostgreSQL or send Ajax requests.

PHP 8.4.22 lint passed on all changed PHP files. PHP_CodeSniffer uses the repository
`dev/tools/php-cs-fixer/.phpcs.xml.dist` rules. PHPStan 2.3.1 uses the unchanged
`phpstan.neon.dist` (level 10). Two existing `totalbilled`/`totalbudget` reports in the
task list reproduce on the unmodified base; no new report is introduced.

On a real instance, verify saved column choices, filters, sorting, empty lists,
pagination/page size, tooltip requests and module on/off behavior. Repeat with a
restricted user, an external account and a project shared from another entity.
No deployed instance was used during this change.

## Visual preview

Fictitious data with the native project cell HTML and Eldy CSS. This illustrates the
optional label after enabling its column; it is not a deployed-instance screenshot.

![Isolated before/after preview](https://raw.githubusercontent.com/mapiolca/dolibarr/105daef3464eebde3b3c5092090ca8571e5e0c7e/doc/images/project-list-columns-preview.png)
