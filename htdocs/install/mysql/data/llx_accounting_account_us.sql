-- Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
-- Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
-- Copyright (C) 2004      Guillaume Delecourt  <guillaume.delecourt@opensides.be>
-- Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
-- Copyright (C) 2007      Patrick Raguin       <patrick.raguin@gmail.com>
-- Copyright (C) 2011-2017 Alexandre Spangaro   <aspangaro@open-dsi.fr>
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
-- Note: INCOME = REVENUE
-- Note: EXPENSE = EXPENSES
-- Note: CAPITAL = EQUITY
--
-- Ne pas placer de commentaire en fin de ligne, ce fichier est parsé lors
-- de l''install et tous les sigles '--' sont supprimés.
-- Note: To replace a string thas is '__, 0' into an increasing number, you can use vi with comment
-- :let @a=1 | %s/__, 0/\='__, '.(@a+setreg('a',@a+1))/g

-- Descriptif des plans comptables USA US-BASE                                            
-- ID 1000 - 9999
-- ADD 1100000 to rowid # Do no remove this comment --

INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1000, 'US-BASE', 'ASSETS',         '1', '0', 'Assets', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 2000, 'US-BASE', 'LIABILITIES',    '2', '0', 'Liabilities', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 3000, 'US-BASE', 'EQUITY',         '3', '0', 'Equity', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 4000, 'US-BASE', 'INCOME',         '4', '0', 'Revenue', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 5000, 'US-BASE', 'COGS',           '5', '0', 'Cost of Goods Sold', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 6000, 'US-BASE', 'EXPENSE',        '6', '0', 'Expenses', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 7000, 'US-BASE', 'OTHER_REVENUE',  '7', '0', 'Other Revenue', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 8000, 'US-BASE', 'OTHER_EXPENSES', '8', '0', 'Other Expenses', 1);

INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1010, 'US-BASE', 'ASSETS', '1010', '1000', 'Cash on Hand', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1011, 'US-BASE', 'ASSETS', '1020', '1000', 'Checking Account', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1012, 'US-BASE', 'ASSETS', '1030', '1000', 'Savings Account', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1013, 'US-BASE', 'ASSETS', '1040', '1000', 'Investments and Securities', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1014, 'US-BASE', 'ASSETS', '1100', '1000', 'Accounts Receivable', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1015, 'US-BASE', 'ASSETS', '1140', '1000', 'Other Receivables', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1016, 'US-BASE', 'ASSETS', '1150', '1000', 'Allowance for Doubtful Accounts', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1017, 'US-BASE', 'ASSETS', '1200', '1000', 'Raw Materials Inventory', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1018, 'US-BASE', 'ASSETS', '1205', '1000', 'Supplies Inventory', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1019, 'US-BASE', 'ASSETS', '1210', '1000', 'Work in Progress Inventory', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1020, 'US-BASE', 'ASSETS', '1215', '1000', 'Finished Goods Inventory', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1021, 'US-BASE', 'ASSETS', '1400', '1000', 'Prepaid Expenses', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1022, 'US-BASE', 'ASSETS', '1410', '1000', 'Employee Advances', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1023, 'US-BASE', 'ASSETS', '1420', '1000', 'Notes Receivable - Current', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1024, 'US-BASE', 'ASSETS', '1430', '1000', 'Prepaid Interest', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1025, 'US-BASE', 'ASSETS', '1470', '1000', 'Other Current Assets', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1026, 'US-BASE', 'ASSETS', '1500', '1000', 'Furniture and Fixtures', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1027, 'US-BASE', 'ASSETS', '1510', '1000', 'Equipment', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1028, 'US-BASE', 'ASSETS', '1520', '1000', 'Vehicles', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1029, 'US-BASE', 'ASSETS', '1530', '1000', 'Other Depreciable Property', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1030, 'US-BASE', 'ASSETS', '1550', '1000', 'Buildings', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1031, 'US-BASE', 'ASSETS', '1560', '1000', 'Building Improvements', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1032, 'US-BASE', 'ASSETS', '1690', '1000', 'Land', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1033, 'US-BASE', 'ASSETS', '1700', '1000', 'Accumulated Depreciation, Furniture and Fixtures', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1034, 'US-BASE', 'ASSETS', '1710', '1000', 'Accumulated Depreciation, Equipment', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1035, 'US-BASE', 'ASSETS', '1720', '1000', 'Accumulated Depreciation, Vehicles', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1036, 'US-BASE', 'ASSETS', '1730', '1000', 'Accumulated Depreciation, Buildings', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1037, 'US-BASE', 'ASSETS', '1740', '1000', 'Accumulated Depreciation, Building Improvements', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1038, 'US-BASE', 'ASSETS', '1750', '1000', 'Accumulated Depreciation, Other', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1039, 'US-BASE', 'ASSETS', '1900', '1000', 'Deposits', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1040, 'US-BASE', 'ASSETS', '1910', '1000', 'Accumulated Amortization', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1041, 'US-BASE', 'ASSETS', '1920', '1000', 'Notes Receivable - Non-current', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1042, 'US-BASE', 'ASSETS', '1990', '1000', 'Other Non-current Assets', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1043, 'US-BASE', 'LIABILITIES', '2100', '2000', 'Accounts Payable', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1044, 'US-BASE', 'LIABILITIES', '2300', '2000', 'Accrued Expenses', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1045, 'US-BASE', 'LIABILITIES', '2310', '2000', 'Sales Tax Payable', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1046, 'US-BASE', 'LIABILITIES', '2320', '2000', 'Wages Payable', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1047, 'US-BASE', 'LIABILITIES', '2330', '2000', '401-K Deductions Payable', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1048, 'US-BASE', 'LIABILITIES', '2335', '2000', 'Health Insurance Payable', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1049, 'US-BASE', 'LIABILITIES', '2340', '2000', 'Federal Payroll Taxes Payable', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1050, 'US-BASE', 'LIABILITIES', '2350', '2000', 'Federal Unemployment Tax Act - Tax Payable', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1051, 'US-BASE', 'LIABILITIES', '2360', '2000', 'State Payroll Taxes Payable', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1052, 'US-BASE', 'LIABILITIES', '2370', '2000', 'State Unemployment Tax Act - Tax Payable', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1053, 'US-BASE', 'LIABILITIES', '2380', '2000', 'Local Payroll Taxes Payable', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1054, 'US-BASE', 'LIABILITIES', '2390', '2000', 'Income Taxes Payable', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1055, 'US-BASE', 'LIABILITIES', '2400', '2000', 'Other Taxes Payable', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1056, 'US-BASE', 'LIABILITIES', '2410', '2000', 'Employee Benefits Payable', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1057, 'US-BASE', 'LIABILITIES', '2420', '2000', 'Current Portion of Long-term Debt', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1058, 'US-BASE', 'LIABILITIES', '2440', '2000', 'Deposits from Customers', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1059, 'US-BASE', 'LIABILITIES', '2480', '2000', 'Other Current Liabilities', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1060, 'US-BASE', 'LIABILITIES', '2700', '2000', 'Notes Payable', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1061, 'US-BASE', 'LIABILITIES', '2702', '2000', 'Land Payable', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1062, 'US-BASE', 'LIABILITIES', '2704', '2000', 'Equipment Payable', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1063, 'US-BASE', 'LIABILITIES', '2706', '2000', 'Vehicles Payable', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1064, 'US-BASE', 'LIABILITIES', '2708', '2000', 'Bank Loans Payable', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1065, 'US-BASE', 'LIABILITIES', '2710', '2000', 'Deferred Revenue', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1066, 'US-BASE', 'LIABILITIES', '2740', '2000', 'Other Long-term Liabilities', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1067, 'US-BASE', 'CAPITAL', '3010', '3000', 'Stated Capital', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1068, 'US-BASE', 'CAPITAL', '3020', '3000', 'Capital Surplus', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1069, 'US-BASE', 'CAPITAL', '3030', '3000', 'Retained Earnings', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1070, 'US-BASE', 'INCOME', '4010', '4000', 'Product Sales', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1071, 'US-BASE', 'INCOME', '4060', '4000', 'Reimbursible Expenses', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1072, 'US-BASE', 'INCOME', '4061', '4000', 'Reimbursible Expenses - Meals and Entertainment ', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1073, 'US-BASE', 'INCOME', '4540', '4000', 'Finance Charge Income', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1074, 'US-BASE', 'INCOME', '4550', '4000', 'Shipping Charges Reimbursed', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1075, 'US-BASE', 'INCOME', '4800', '4000', 'Sales Returns and Allowances', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1076, 'US-BASE', 'INCOME', '4900', '4000', 'Sales Discounts', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1077, 'US-BASE', 'COGS', '5010', '5000', 'Product Cost', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1078, 'US-BASE', 'COGS', '5050', '5000', 'Raw Material Purchases', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1079, 'US-BASE', 'COGS', '5100', '5000', 'Direct Labor Costs', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1080, 'US-BASE', 'COGS', '5150', '5000', 'Indirect Labor Costs', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1081, 'US-BASE', 'COGS', '5200', '5000', 'Heat and Power', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1082, 'US-BASE', 'COGS', '5250', '5000', 'Commissions', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1083, 'US-BASE', 'COGS', '5300', '5000', 'Miscellaneous Factory Costs', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1084, 'US-BASE', 'COGS', '5700', '5000', 'Cost of Goods Sold, Salaries and Wages', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1085, 'US-BASE', 'COGS', '5730', '5000', 'Cost of Goods Sold, Contract Labor', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1086, 'US-BASE', 'COGS', '5750', '5000', 'Cost of Goods Sold, Freight', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1087, 'US-BASE', 'COGS', '5800', '5000', 'Cost of Goods Sold, Other', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1088, 'US-BASE', 'COGS', '5850', '5000', 'Inventory Adjustments', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1089, 'US-BASE', 'COGS', '5900', '5000', 'Purchase Returns and Allowances', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1090, 'US-BASE', 'COGS', '5950', '5000', 'Purchase Discounts', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1091, 'US-BASE', 'EXPENSE', '6010', '6000', 'Default Purchase Expense', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1092, 'US-BASE', 'EXPENSE', '6020', '6000', 'Advertising Expense', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1093, 'US-BASE', 'EXPENSE', '6050', '6000', 'Amortization Expense', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1094, 'US-BASE', 'EXPENSE', '6100', '6000', 'Auto EXPENSE', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1095, 'US-BASE', 'EXPENSE', '6150', '6000', 'Bad Debt Expense', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1096, 'US-BASE', 'EXPENSE', '6200', '6000', 'Bank Fees', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1097, 'US-BASE', 'EXPENSE', '6250', '6000', 'Cash Over and Short', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1098, 'US-BASE', 'EXPENSE', '6300', '6000', 'Charitable Contributions Expense', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1099, 'US-BASE', 'EXPENSE', '6350', '6000', 'Commissions and Fees Expense', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1100, 'US-BASE', 'EXPENSE', '6450', '6000', 'Dues and Subscriptions Expense', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1101, 'US-BASE', 'EXPENSE', '6500', '6000', 'Employee Benefit Expense, Health Insurance', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1102, 'US-BASE', 'EXPENSE', '6510', '6000', 'Employee Benefit Expense, Pension Plans', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1103, 'US-BASE', 'EXPENSE', '6520', '6000', 'Employee Benefit Expense, Profit Sharing Plan', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1104, 'US-BASE', 'EXPENSE', '6530', '6000', 'Employee Benefit Expense, Other', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1105, 'US-BASE', 'EXPENSE', '6550', '6000', 'Freight Expense', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1106, 'US-BASE', 'EXPENSE', '6600', '6000', 'Gifts Expense', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1107, 'US-BASE', 'EXPENSE', '6650', '6000', 'Income Tax Expense, Federal', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1108, 'US-BASE', 'EXPENSE', '6660', '6000', 'Income Tax Expense, State', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1109, 'US-BASE', 'EXPENSE', '6670', '6000', 'Income Tax Expense, Local', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1110, 'US-BASE', 'EXPENSE', '6700', '6000', 'Insurance Expense, Product Liability', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1111, 'US-BASE', 'EXPENSE', '6710', '6000', 'Insurance Expense, Vehicle', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1112, 'US-BASE', 'EXPENSE', '6800', '6000', 'Laundry and Dry Cleaning Expense', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1113, 'US-BASE', 'EXPENSE', '6850', '6000', 'Legal and Professional Expense', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1114, 'US-BASE', 'EXPENSE', '6900', '6000', 'Licenses Expense', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1115, 'US-BASE', 'EXPENSE', '6950', '6000', 'Loss on Non-sufficient Funds Checks', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1116, 'US-BASE', 'OTHER_REVENUE', '7010', '7000', 'Interest Income', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1117, 'US-BASE', 'OTHER_REVENUE', '7030', '7000', 'Other Income', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1118, 'US-BASE', 'OTHER_EXPENSES', '8010', '8000', 'Depreciation Expense', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1119, 'US-BASE', 'OTHER_EXPENSES', '8020', '8000', 'Interest Expense', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1120, 'US-BASE', 'OTHER_EXPENSES', '8030', '8000', 'Maintenance Expense', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1121, 'US-BASE', 'OTHER_EXPENSES', '8050', '8000', 'Meals and Entertainment Expense', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1122, 'US-BASE', 'OTHER_EXPENSES', '8100', '8000', 'Office Expense', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1123, 'US-BASE', 'OTHER_EXPENSES', '8200', '8000', 'Payroll Tax Expense', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1124, 'US-BASE', 'OTHER_EXPENSES', '8250', '8000', 'Penalties and Fines Expense', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1125, 'US-BASE', 'OTHER_EXPENSES', '8300', '8000', 'Other Taxes', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1126, 'US-BASE', 'OTHER_EXPENSES', '8350', '8000', 'Postage Expense', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1127, 'US-BASE', 'OTHER_EXPENSES', '8400', '8000', 'Rent or Lease Expense', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1128, 'US-BASE', 'OTHER_EXPENSES', '8450', '8000', 'Repair and Maintenance Expense, Office', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1129, 'US-BASE', 'OTHER_EXPENSES', '8460', '8000', 'Repair and Maintenance Expense, Vehicle', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1130, 'US-BASE', 'OTHER_EXPENSES', '8550', '8000', 'Supplies Expense, Office', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1131, 'US-BASE', 'OTHER_EXPENSES', '8600', '8000', 'Telephone Expense', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1132, 'US-BASE', 'OTHER_EXPENSES', '8620', '8000', 'Training Expense', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1133, 'US-BASE', 'OTHER_EXPENSES', '8650', '8000', 'Travel Expense', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1134, 'US-BASE', 'OTHER_EXPENSES', '8700', '8000', 'Salaries Expense, Officers', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1135, 'US-BASE', 'OTHER_EXPENSES', '8750', '8000', 'Wages Expense', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1136, 'US-BASE', 'OTHER_EXPENSES', '8800', '8000', 'Utilities Expense', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1137, 'US-BASE', 'OTHER_EXPENSES', '8900', '8000', 'Gain/Loss on Sale of Assets', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1138, 'US-BASE', 'OTHER_EXPENSES', '8950', '8000', 'Other Expense', 1);
