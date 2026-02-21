# MassSubscriptionBatch module

Custom Dolibarr module that adds a members-list mass action to:

1. create subscriptions,
2. generate invoices without payment,
3. optionally send confirmation emails with invoice attachment.

## Installation

1. Place the module in `htdocs/custom/masssubscriptionbatch`.
2. Enable module **Mass subscription batch** from *Home > Setup > Modules/Applications*.
3. Grant permission **Run mass subscription + invoice + email action** to required users.
4. Optional: in module setup, enable default email sending.

## Usage

Go to **Members > List**, select members, then run mass action:

- **Create subscription + invoice and send email**.

The action uses member type amount and duration to create period and amount.
