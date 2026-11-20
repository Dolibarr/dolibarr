-- Copyright (C) 2026 Dolibarr User Australia <dolibarruseraustralia@gmail.com>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <https://www.gnu.org/licenses/>.
--
-- Generic Australian base chart of accounts (AU-BASE).
-- Template only - review with an accountant before use in a live business.
-- pcg_version: AU-BASE

INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000001, 'AU-BASE', 'ASSET', '1000', '0', 'Business Bank Account', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000002, 'AU-BASE', 'ASSET', '1010', '0', 'Business Savings Account', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000003, 'AU-BASE', 'ASSET', '1020', '0', 'Petty Cash', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000004, 'AU-BASE', 'ASSET', '1030', '0', 'Undeposited Funds', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000005, 'AU-BASE', 'ASSET', '1100', '0', 'Trade Debtors', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000006, 'AU-BASE', 'ASSET', '1200', '0', 'GST Paid (Input Tax Credits)', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000007, 'AU-BASE', 'ASSET', '1210', '0', 'Prepaid Expenses', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000008, 'AU-BASE', 'ASSET', '1230', '0', 'Accrued Income', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000009, 'AU-BASE', 'ASSET', '1300', '0', 'Motor Vehicles - at Cost', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000010, 'AU-BASE', 'ASSET', '1301', '0', 'Motor Vehicles - Accumulated Depreciation', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000011, 'AU-BASE', 'ASSET', '1320', '0', 'Office Equipment - at Cost', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000012, 'AU-BASE', 'ASSET', '1321', '0', 'Office Equipment - Accumulated Depreciation', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000013, 'AU-BASE', 'ASSET', '1330', '0', 'Computer Equipment & Software - at Cost', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000014, 'AU-BASE', 'ASSET', '1331', '0', 'Computer Equipment & Software - Accumulated Depreciation', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000015, 'AU-BASE', 'ASSET', '1400', '0', 'Security Bonds & Deposits Paid', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000016, 'AU-BASE', 'LIABILITY', '2000', '0', 'Trade Creditors', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000017, 'AU-BASE', 'LIABILITY', '2100', '0', 'GST Collected', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000018, 'AU-BASE', 'LIABILITY', '2110', '0', 'PAYG Withholding Payable', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000019, 'AU-BASE', 'LIABILITY', '2120', '0', 'Superannuation Payable', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000020, 'AU-BASE', 'LIABILITY', '2130', '0', 'Wages Payable', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000021, 'AU-BASE', 'LIABILITY', '2140', '0', 'Accrued Expenses', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000022, 'AU-BASE', 'LIABILITY', '2170', '0', 'Credit Card', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000023, 'AU-BASE', 'LIABILITY', '2190', '0', 'Provision for Annual Leave', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000024, 'AU-BASE', 'LIABILITY', '2195', '0', 'Provision for Long Service Leave', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000025, 'AU-BASE', 'LIABILITY', '2200', '0', 'Bank Loan - Non-Current', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000026, 'AU-BASE', 'LIABILITY', '2220', '0', 'Director/Shareholder Loan', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000027, 'AU-BASE', 'EQUITY', '3000', '0', 'Owner''s/Director''s Capital', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000028, 'AU-BASE', 'EQUITY', '3010', '0', 'Owner''s Drawings', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000029, 'AU-BASE', 'EQUITY', '3020', '0', 'Retained Earnings', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000030, 'AU-BASE', 'EQUITY', '3030', '0', 'Current Year Earnings', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000031, 'AU-BASE', 'INCOME', '4000', '0', 'Sales - Taxable (GST)', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000032, 'AU-BASE', 'INCOME', '4010', '0', 'Sales - GST-Free', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000033, 'AU-BASE', 'INCOME', '4900', '0', 'Other Income', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000034, 'AU-BASE', 'INCOME', '8000', '0', 'Interest Income', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000035, 'AU-BASE', 'INCOME', '8010', '0', 'Profit on Sale of Asset', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000036, 'AU-BASE', 'EXPENSE', '6000', '0', 'Advertising & Marketing', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000037, 'AU-BASE', 'EXPENSE', '6010', '0', 'Accounting & Bookkeeping Fees', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000038, 'AU-BASE', 'EXPENSE', '6020', '0', 'Bank Fees & Charges', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000039, 'AU-BASE', 'EXPENSE', '6030', '0', 'Merchant Fees (EFTPOS/Card Surcharges)', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000040, 'AU-BASE', 'EXPENSE', '6060', '0', 'Insurance - Business/Public Liability', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000041, 'AU-BASE', 'EXPENSE', '6070', '0', 'Interest Expense', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000042, 'AU-BASE', 'EXPENSE', '6080', '0', 'Legal Fees', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000043, 'AU-BASE', 'EXPENSE', '6090', '0', 'Motor Vehicle Expenses', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000044, 'AU-BASE', 'EXPENSE', '6100', '0', 'Office Supplies & Stationery', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000045, 'AU-BASE', 'EXPENSE', '6110', '0', 'Postage & Courier', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000046, 'AU-BASE', 'EXPENSE', '6120', '0', 'Rent', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000047, 'AU-BASE', 'EXPENSE', '6130', '0', 'Repairs & Maintenance', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000048, 'AU-BASE', 'EXPENSE', '6140', '0', 'Salaries & Wages', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000049, 'AU-BASE', 'EXPENSE', '6150', '0', 'Superannuation Expense', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000050, 'AU-BASE', 'EXPENSE', '6160', '0', 'Workers Compensation Insurance', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000051, 'AU-BASE', 'EXPENSE', '6170', '0', 'Staff Amenities', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000052, 'AU-BASE', 'EXPENSE', '6180', '0', 'Telephone & Internet', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000053, 'AU-BASE', 'EXPENSE', '6190', '0', 'Electricity & Gas', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000054, 'AU-BASE', 'EXPENSE', '6210', '0', 'Subscriptions & Software (SaaS)', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000055, 'AU-BASE', 'EXPENSE', '6220', '0', 'Depreciation Expense', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000056, 'AU-BASE', 'EXPENSE', '6230', '0', 'Bad Debts Written Off', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000057, 'AU-BASE', 'EXPENSE', '6260', '0', 'Sundry/General Expenses', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000058, 'AU-BASE', 'EXPENSE', '6270', '0', 'Training & Professional Development', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 700000059, 'AU-BASE', 'EXPENSE', '9000', '0', 'Loss on Sale of Asset', 1);
