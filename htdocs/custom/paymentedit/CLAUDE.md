# PaymentEdit Module

## Overview

The **PaymentEdit** module enables editing of "Various Payments" (Payment Various) in Dolibarr. The core Dolibarr code has an `update()` method in the `PaymentVarious` class, but no UI to use it (there's a TODO comment on line 765-766 of the core card.php).

**Version**: 1.0.0
**Module ID**: 510200
**Author**: Florian Hödl <florian@hoedl.co>
**Publisher**: Anexum GmbH
**License**: GPL-3.0+

## Problem Solved

The core Dolibarr `various_payment/card.php` page has:
- A "Clone" button
- A "Delete" button
- A TODO comment: `// Add button modify`

This module adds the missing "Modify" functionality without changing core files.

## Technical Implementation

### Hook-Based Integration

Since we cannot modify core files, we use Dolibarr's hook system:

1. **`formObjectOptions` hook** - Injects JavaScript that adds a "Modify" button to the action bar
2. **Custom `card.php`** - Provides the edit form for various payments

### Hook Registration

The module registers hooks for these contexts:
- `variouscard` - The various payment card page
- `globalcard` - General card hook context

### Button Injection

The `ActionsPaymentEdit::formObjectOptions()` method injects JavaScript that:
1. Finds the `.tabsAction` div (action bar)
2. Creates a "Modify" button with proper Dolibarr styling
3. Inserts it before the first existing button (Clone)

### Edit Page

The custom `card.php`:
1. Loads the `PaymentVarious` object
2. Shows an edit form with editable fields
3. Uses the core `update()` method to save changes
4. Also updates the linked bank line (`llx_bank`) if present

## Module Structure

```
htdocs/custom/paymentedit/
├── core/modules/
│   └── modPaymentEdit.class.php    # Module descriptor
├── class/
│   └── actions_paymentedit.class.php # Hook class (button injection)
├── admin/
│   ├── setup.php                   # Settings page
│   └── about.php                   # About page
├── lib/
│   └── paymentedit.lib.php         # Helper functions
├── langs/
│   ├── en_US/paymentedit.lang      # English translations
│   └── de_DE/paymentedit.lang      # German translations
├── card.php                        # Edit form page
└── CLAUDE.md                       # This documentation
```

## Editable Fields

| Field | Description | Condition |
|-------|-------------|-----------|
| `label` | Payment description | Always editable |
| `datep` | Payment date | Always editable |
| `datev` | Value date | Always editable |
| `amount` | Payment amount | Always editable |
| `num_payment` | Payment reference number | Always editable |
| `fk_project` | Linked project | Always editable |
| `note` | Private note | Always editable |
| `accountancy_code` | Accounting code | Only if not accounted |
| `subledger_account` | Subledger account | Only if not accounted |

## Protected Fields (Read-Only)

| Field | Reason |
|-------|--------|
| `sens` | Direction (debit/credit) - Cannot change after creation |
| `fk_bank` | Bank transaction link - Display only |
| `fk_account` | Bank account - Cannot change, linked to bank line |

## Security Rules

1. **Permission Required**: `banque->modifier`
2. **Reconciliation Protection**: If `rappro=1`, editing is blocked (button shows as disabled)
3. **Accounting Protection**: If already accounted, accounting codes are read-only
4. **Bank Line Sync**: Updates to amount/label/dates are synced to the linked bank line

## User Flow

1. Navigate to: `/compta/bank/various_payment/card.php?id=XXX`
2. Click the "Modify" button in the action bar
3. Redirected to: `/custom/paymentedit/card.php?id=XXX`
4. Make changes in the edit form
5. Click "Save" - changes are saved and user is redirected back to core page

## Dependencies

- **modBanque** - The bank module must be enabled

## Key Files Reference

| File | Purpose |
|------|---------|
| `htdocs/compta/bank/class/paymentvarious.class.php` | Core class with `update()` method |
| `htdocs/compta/bank/various_payment/card.php` | Core card page (hook target) |
| `htdocs/core/lib/bank.lib.php` | `various_payment_prepare_head()` function for tabs |

## Changelog

### 1.0.0
- Initial release
- Hook-based "Modify" button injection
- Edit form for various payments
- Bank line synchronization
- Reconciliation and accounting protection

## Testing

1. Enable the module: Home → Setup → Modules → Financial → PaymentEdit
2. Create a various payment: Financial → Miscellaneous → Various Payments → New
3. View the payment card and verify "Modify" button appears
4. Click Modify and verify edit form loads
5. Change fields and save
6. Verify changes are persisted

### Edge Cases to Test

- **Reconciled payment**: Modify button should be disabled
- **Accounted payment**: Accounting fields should be read-only
- **Bank line sync**: Changes to amount should update the bank line

## Troubleshooting

### Button Not Appearing

1. Verify module is enabled
2. Check user has `banque->modifier` permission
3. Clear browser cache (JavaScript is injected dynamically)

### Cannot Edit Accounting Codes

This is by design - once a payment is accounted (exported to accounting), the accounting codes are locked to maintain audit trail integrity.

### Bank Line Amount Mismatch

The module syncs the bank line when saving. If there's a mismatch:
1. Check the `sens` field (0=debit, 1=credit)
2. Verify the bank line exists (`fk_bank` is set)
