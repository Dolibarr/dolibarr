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

-- Descriptif des plans comptables Canada CA-ENG-BASE                                            
-- ID 1000 - 9999
-- ADD 1400000 to rowid # Do no remove this comment --

INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1000,'CA-ENG-BASE','ASSETS',   '1',   '0', 'Assets', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 2000,'CA-ENG-BASE','LIABILITIES',   '2',   '0', 'Liabilities', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 3000,'CA-ENG-BASE','CAPITAL',   '3',   '0', 'Equity', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 4000,'CA-ENG-BASE','INCOME',   '4',   '0', 'Revenue', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 5000,'CA-ENG-BASE','COGS',   '5',   '0', 'Cost of Goods Sold', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 6000,'CA-ENG-BASE','EXPENSE',   '6',   '0', 'Expenses', 1);

INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1010, 'CA-ENG-BASE', 'ASSETS', '1010', '1000', 'Cash', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1011, 'CA-ENG-BASE', 'ASSETS', '1030', '1000', 'Investments and Securities', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1012, 'CA-ENG-BASE', 'ASSETS', '1100', '1000', 'Accounts Receivable', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1013, 'CA-ENG-BASE', 'ASSETS', '1120', '1000', 'Other Receivables', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1014, 'CA-ENG-BASE', 'ASSETS', '1140', '1000', 'Allowance for Doubtful Accounts', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1015, 'CA-ENG-BASE', 'ASSETS', '1160', '1000', 'Customers Account Receivable', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1016, 'CA-ENG-BASE', 'ASSETS', '1200', '1000', 'Raw Materials Inventory', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1017, 'CA-ENG-BASE', 'ASSETS', '1220', '1000', 'Supplies Inventory', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1018, 'CA-ENG-BASE', 'ASSETS', '1240', '1000', 'Work in Progress Inventory', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1019, 'CA-ENG-BASE', 'ASSETS', '1260', '1000', 'Finished Goods Inventory', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1020, 'CA-ENG-BASE', 'ASSETS', '1300', '1000', 'Prepaid Expenses', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1021, 'CA-ENG-BASE', 'ASSETS', '1350', '1000', 'Employee Advances', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1022, 'CA-ENG-BASE', 'ASSETS', '1400', '1000', 'Notes Receivable - Current', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1023, 'CA-ENG-BASE', 'ASSETS', '1430', '1000', 'Prepaid Interest', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1024, 'CA-ENG-BASE', 'ASSETS', '1450', '1000', 'Other Current Assets', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1025, 'CA-ENG-BASE', 'ASSETS', '1500', '1000', 'Furniture and Fixtures', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1026, 'CA-ENG-BASE', 'ASSETS', '1520', '1000', 'Equipment', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1027, 'CA-ENG-BASE', 'ASSETS', '1540', '1000', 'Vehicles', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1028, 'CA-ENG-BASE', 'ASSETS', '1560', '1000', 'Other Depreciable Property', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1029, 'CA-ENG-BASE', 'ASSETS', '1580', '1000', 'Buildings', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1030, 'CA-ENG-BASE', 'ASSETS', '1600', '1000', 'Building Improvements', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1031, 'CA-ENG-BASE', 'ASSETS', '1620', '1000', 'Land', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1032, 'CA-ENG-BASE', 'ASSETS', '1640', '1000', 'Accumulated Depreciation, Furniture and Fixtures', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1033, 'CA-ENG-BASE', 'ASSETS', '1660', '1000', 'Accumulated Depreciation, Equipment', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1034, 'CA-ENG-BASE', 'ASSETS', '1680', '1000', 'Accumulated Depreciation, Vehicles', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1035, 'CA-ENG-BASE', 'ASSETS', '1700', '1000', 'Accumulated Depreciation, Buildings', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1036, 'CA-ENG-BASE', 'ASSETS', '1720', '1000', 'Accumulated Depreciation, Building Improvements', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1037, 'CA-ENG-BASE', 'ASSETS', '1740', '1000', 'Accumulated Depreciation, Other', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1038, 'CA-ENG-BASE', 'ASSETS', '1760', '1000', 'Goods and Services Tax (GST) Receivable', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1039, 'CA-ENG-BASE', 'ASSETS', '1800', '1000', 'Harmonized Sales Tax (HST) Receivable', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1040, 'CA-ENG-BASE', 'ASSETS', '1820', '1000', 'Provincial Sales Tax (PST) Receivable', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1041, 'CA-ENG-BASE', 'ASSETS', '1840', '1000', 'Québec Sales Tax (QST) Receivable', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1042, 'CA-ENG-BASE', 'ASSETS', '1860', '1000', 'Deposits', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1043, 'CA-ENG-BASE', 'ASSETS', '1880', '1000', 'Accumulated Amortization', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1044, 'CA-ENG-BASE', 'ASSETS', '1900', '1000', 'Notes Receivable - Non-current', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1045, 'CA-ENG-BASE', 'ASSETS', '1940', '1000', 'Other Non-current Assets', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1046, 'CA-ENG-BASE', 'LIABILITIES', '2100', '2000', 'Accounts Payable', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1047, 'CA-ENG-BASE', 'LIABILITIES', '2200', '2000', 'Accrued Expenses', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1048, 'CA-ENG-BASE', 'LIABILITIES', '2300', '2000', 'Current Financial Debts', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1049, 'CA-ENG-BASE', 'LIABILITIES', '2320', '2000', 'Employment Insurance Payable', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1050, 'CA-ENG-BASE', 'LIABILITIES', '2321', '2000', 'Employment insurance (EI) Employees Contribution', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1051, 'CA-ENG-BASE', 'LIABILITIES', '2322', '2000', 'Employment insurance (EI) Employer Contribution', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1052, 'CA-ENG-BASE', 'LIABILITIES', '2340', '2000', 'Federal Income Tax', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1053, 'CA-ENG-BASE', 'LIABILITIES', '2350', '2000', 'Annuities Payable', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1054, 'CA-ENG-BASE', 'LIABILITIES', '2351', '2000', 'Annuities - Employee Contribution', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1055, 'CA-ENG-BASE', 'LIABILITIES', '2352', '2000', 'Annuities - Employer Contribution', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1056, 'CA-ENG-BASE', 'LIABILITIES', '2360', '2000', 'Health Services Fund Payable', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1057, 'CA-ENG-BASE', 'LIABILITIES', '2370', '2000', 'Labour Health and Safety Payable', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1058, 'CA-ENG-BASE', 'LIABILITIES', '2380', '2000', 'Labour Standards to Pay', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1059, 'CA-ENG-BASE', 'LIABILITIES', '2390', '2000', 'Parental Insurance Plan Payable', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1060, 'CA-ENG-BASE', 'LIABILITIES', '2391', '2000', 'Parental Insurance Plan Payable - Employee Contribution', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1061, 'CA-ENG-BASE', 'LIABILITIES', '2392', '2000', 'Parental Insurance Plan Payable - Employer Contribution', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1062, 'CA-ENG-BASE', 'LIABILITIES', '2400', '2000', 'Provincial Income Tax', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1063, 'CA-ENG-BASE', 'LIABILITIES', '2410', '2000', 'Other Accounts Payable', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1064, 'CA-ENG-BASE', 'LIABILITIES', '2420', '2000', 'Goods and Services Tax (GST) Payable', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1065, 'CA-ENG-BASE', 'LIABILITIES', '2430', '2000', 'Harmonized Sales Tax (HST) Payable', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1066, 'CA-ENG-BASE', 'LIABILITIES', '2440', '2000', 'Provincial Sales Tax (PST) Payable', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1067, 'CA-ENG-BASE', 'LIABILITIES', '2450', '2000', 'Québec Sales Tax (QST) Payable', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1068, 'CA-ENG-BASE', 'LIABILITIES', '2460', '2000', 'Other Taxes Payable', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1069, 'CA-ENG-BASE', 'LIABILITIES', '2470', '2000', 'Employee Benefits Payable', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1070, 'CA-ENG-BASE', 'LIABILITIES', '2480', '2000', 'Deposits from Customers', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1071, 'CA-ENG-BASE', 'LIABILITIES', '2490', '2000', 'Other Current Liabilities', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1072, 'CA-ENG-BASE', 'LIABILITIES', '2500', '2000', 'Notes Payable', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1073, 'CA-ENG-BASE', 'LIABILITIES', '2600', '2000', 'Land Payable', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1074, 'CA-ENG-BASE', 'LIABILITIES', '2630', '2000', 'Equipment Payable', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1075, 'CA-ENG-BASE', 'LIABILITIES', '2660', '2000', 'Vehicles Payable', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1076, 'CA-ENG-BASE', 'LIABILITIES', '2700', '2000', 'Bank Loans Payable', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1077, 'CA-ENG-BASE', 'LIABILITIES', '2730', '2000', 'Non-current Financial Debts', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1078, 'CA-ENG-BASE', 'LIABILITIES', '2760', '2000', 'Other Non-current Liabilities', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1079, 'CA-ENG-BASE', 'LIABILITIES', '2800', '2000', 'Deferred Revenue', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1080, 'CA-ENG-BASE', 'LIABILITIES', '2900', '2000', 'Deferred Fees', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1081, 'CA-ENG-BASE', 'CAPITAL', '3100', '3000', 'Common Shares', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1082, 'CA-ENG-BASE', 'CAPITAL', '3200', '3000', 'Preferred Shares (Voting)', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1083, 'CA-ENG-BASE', 'CAPITAL', '3300', '3000', 'Preferred Shares (Non-voting)', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1084, 'CA-ENG-BASE', 'CAPITAL', '3400', '3000', 'Contributed Surplus', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1085, 'CA-ENG-BASE', 'CAPITAL', '3500', '3000', 'Retained Earnings', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1086, 'CA-ENG-BASE', 'CAPITAL', '3600', '3000', 'Dividends', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1087, 'CA-ENG-BASE', 'INCOME', '4100', '4000', 'Harmonized Provinces Sales', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1088, 'CA-ENG-BASE', 'INCOME', '4200', '4000', 'Non-Harmonized Provinces Sales', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1089, 'CA-ENG-BASE', 'INCOME', '4300', '4000', 'Inside Sales', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1090, 'CA-ENG-BASE', 'INCOME', '4400', '4000', 'International Sales', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1091, 'CA-ENG-BASE', 'INCOME', '4500', '4000', 'Reimbursible Expenses', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1092, 'CA-ENG-BASE', 'INCOME', '4600', '4000', 'Shipping Charges Reimbursed', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1093, 'CA-ENG-BASE', 'INCOME', '4700', '4000', 'Other Operating Revenues', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1094, 'CA-ENG-BASE', 'INCOME', '4800', '4000', 'Interests', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1095, 'CA-ENG-BASE', 'INCOME', '4900', '4000', 'Other Non-operating Revenues', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1096, 'CA-ENG-BASE', 'COGS', '5010', '5000', 'Inside Purchases', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1097, 'CA-ENG-BASE', 'COGS', '5050', '5000', 'International Purchases', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1098, 'CA-ENG-BASE', 'COGS', '5100', '5000', 'Purchases in Harmonized Provinces', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1099, 'CA-ENG-BASE', 'COGS', '5150', '5000', 'Purchases in Non-harmonized Provinces', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1100, 'CA-ENG-BASE', 'COGS', '5200', '5000', 'Direct Labor Costs', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1101, 'CA-ENG-BASE', 'COGS', '5250', '5000', 'Indirect Labor Costs', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1102, 'CA-ENG-BASE', 'COGS', '5270', '5000', 'Heat and Power', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1103, 'CA-ENG-BASE', 'COGS', '5300', '5000', 'Commissions', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1104, 'CA-ENG-BASE', 'COGS', '5350', '5000', 'Miscellaneous Factory Costs', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1105, 'CA-ENG-BASE', 'COGS', '5400', '5000', 'Cost of Goods Sold, Salaries and Wages', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1106, 'CA-ENG-BASE', 'COGS', '5450', '5000', 'Cost of Goods Sold, Contract Labor', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1107, 'CA-ENG-BASE', 'COGS', '5500', '5000', 'Cost of Goods Sold, Freight', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1108, 'CA-ENG-BASE', 'COGS', '5550', '5000', 'Cost of Goods Sold, Other', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1109, 'CA-ENG-BASE', 'COGS', '5600', '5000', 'Inventory Adjustments', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1110, 'CA-ENG-BASE', 'COGS', '5700', '5000', 'Purchase Returns and Allowances', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1111, 'CA-ENG-BASE', 'EXPENSE', '6010', '6000', 'Federal Income Tax', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1112, 'CA-ENG-BASE', 'EXPENSE', '6020', '6000', 'Health Services Fund', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1113, 'CA-ENG-BASE', 'EXPENSE', '6030', '6000', 'Holidays', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1114, 'CA-ENG-BASE', 'EXPENSE', '6040', '6000', 'Labour Health and Safety', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1115, 'CA-ENG-BASE', 'EXPENSE', '6050', '6000', 'Labour Standards', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1116, 'CA-ENG-BASE', 'EXPENSE', '6060', '6000', 'Parental Insurance', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1117, 'CA-ENG-BASE', 'EXPENSE', '6080', '6000', 'Provincial Income Tax', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1118, 'CA-ENG-BASE', 'EXPENSE', '6100', '6000', 'Salaries, wages', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1119, 'CA-ENG-BASE', 'EXPENSE', '6130', '6000', 'Employee Benefit Expense, Pension Plans', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1120, 'CA-ENG-BASE', 'EXPENSE', '6160', '6000', 'Employee Benefit Expense, Profit Sharing Plan', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1121, 'CA-ENG-BASE', 'EXPENSE', '6180', '6000', 'Employee Benefit Expense, Other', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1122, 'CA-ENG-BASE', 'EXPENSE', '6200', '6000', 'Commissions and Fees Expense', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1123, 'CA-ENG-BASE', 'EXPENSE', '6230', '6000', 'Annuities', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1124, 'CA-ENG-BASE', 'EXPENSE', '6250', '6000', 'Employment Insurance', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1125, 'CA-ENG-BASE', 'EXPENSE', '6280', '6000', 'Insurance Expense, Product Liability', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1126, 'CA-ENG-BASE', 'EXPENSE', '6300', '6000', 'Insurance Expense, Vehicle', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1127, 'CA-ENG-BASE', 'EXPENSE', '6340', '6000', 'Payroll Tax Expense', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1128, 'CA-ENG-BASE', 'EXPENSE', '6360', '6000', 'Penalties and Fines Expense', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1129, 'CA-ENG-BASE', 'EXPENSE', '6380', '6000', 'Other Taxes', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1130, 'CA-ENG-BASE', 'EXPENSE', '6400', '6000', 'Advertising Expense', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1131, 'CA-ENG-BASE', 'EXPENSE', '6420', '6000', 'Amortization Expense', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1132, 'CA-ENG-BASE', 'EXPENSE', '6460', '6000', 'Auto Expenses', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1133, 'CA-ENG-BASE', 'EXPENSE', '6480', '6000', 'Legal and Professional Expense', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1134, 'CA-ENG-BASE', 'EXPENSE', '6500', '6000', 'Licenses Expense', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1135, 'CA-ENG-BASE', 'EXPENSE', '6520', '6000', 'Maintenance Expense', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1136, 'CA-ENG-BASE', 'EXPENSE', '6540', '6000', 'Repair and Maintenance Expense, Office', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1137, 'CA-ENG-BASE', 'EXPENSE', '6560', '6000', 'Repair and Maintenance Expense, Vehicle', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1138, 'CA-ENG-BASE', 'EXPENSE', '6580', '6000', 'Office Supplies Expense', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1139, 'CA-ENG-BASE', 'EXPENSE', '6600', '6000', 'Telephone Expense', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1140, 'CA-ENG-BASE', 'EXPENSE', '6610', '6000', 'Training Expense', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1141, 'CA-ENG-BASE', 'EXPENSE', '6630', '6000', 'Travel Expense', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1142, 'CA-ENG-BASE', 'EXPENSE', '6650', '6000', 'Utilities Expense', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1143, 'CA-ENG-BASE', 'EXPENSE', '6670', '6000', 'Postage Expense', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1144, 'CA-ENG-BASE', 'EXPENSE', '6690', '6000', 'Freight Expense', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1145, 'CA-ENG-BASE', 'EXPENSE', '6700', '6000', 'Rent or Lease Expense', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1146, 'CA-ENG-BASE', 'EXPENSE', '6720', '6000', 'Meals and Entertainment Expense', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1147, 'CA-ENG-BASE', 'EXPENSE', '6730', '6000', 'Gain/Loss on Sale of Assets', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1148, 'CA-ENG-BASE', 'EXPENSE', '6740', '6000', 'Depreciation Expense', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1149, 'CA-ENG-BASE', 'EXPENSE', '6750', '6000', 'Bad Debt Expense', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1150, 'CA-ENG-BASE', 'EXPENSE', '6760', '6000', 'Bank Fees', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1151, 'CA-ENG-BASE', 'EXPENSE', '6790', '6000', 'Loss on Non-sufficient Funds Checks', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1152, 'CA-ENG-BASE', 'EXPENSE', '6800', '6000', 'Gifts Expense', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1153, 'CA-ENG-BASE', 'EXPENSE', '6820', '6000', 'Charitable Contributions Expense', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1154, 'CA-ENG-BASE', 'EXPENSE', '6840', '6000', 'Other Operating Expense', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1155, 'CA-ENG-BASE', 'EXPENSE', '6860', '6000', 'Interests Expense', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, account_number, account_parent, label, active) VALUES (__ENTITY__, 1156, 'CA-ENG-BASE', 'EXPENSE', '6900', '6000', 'Other Non-operating Expense', 1);
