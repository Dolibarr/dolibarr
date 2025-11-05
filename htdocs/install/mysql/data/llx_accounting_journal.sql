-- Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
-- Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
-- Copyright (C) 2004      Guillaume Delecourt  <guillaume.delecourt@opensides.be>
-- Copyright (C) 2005-2025 Regis Houssin        <regis.houssin@inodbox.com>
-- Copyright (C) 2007      Patrick Raguin       <patrick.raguin@gmail.com>
-- Copyright (C) 2011-2022 Alexandre Spangaro   <aspangaro@open-dsi.fr>
-- Copyright (C) 2015-2017 Juanjo Menent        <jmenent@2byte.es>
-- Copyright (C) 2018      Abbes bahfir         <dolipar@dolipar.org>
-- Copyright (C) 2020      Udo Tamm             <dev@dolibit.de>
-- Copyright (C) 2023      Nick Fragoulis
-- Copyright (C) 2023      Santiago Payà        <santiagopim@gmail.com>
--
--
--------------------------------------------------------------------------------------
-- License
-- #######
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
--------------------------------------------------------------------------------------

-- Comment
-- #######
-- (EN)
-- Do not place a comment at the end of the line, this file is parsed at the end of the line.
-- from the install and all '--' are removed.
--
-- (FR)
-- Ne pas placer de commentaire en fin de ligne, ce fichier est parsé lors
-- de l'install et tous les sigles '--' sont supprimés.
--
--------------------------------------------------------------------------------------
-- PCG = Plan Comptable Général (FR) - General Accounting Plan (EN)
--------------------------------------------------------------------------------------


-- Accounting Journals

INSERT INTO llx_accounting_journal (code, label, nature, active, entity) VALUES ('VT',  'ACCOUNTING_SELL_JOURNAL',          2, 1, __ENTITY__);
INSERT INTO llx_accounting_journal (code, label, nature, active, entity) VALUES ('AC',  'ACCOUNTING_PURCHASE_JOURNAL',      3, 1, __ENTITY__);
INSERT INTO llx_accounting_journal (code, label, nature, active, entity) VALUES ('BQ',  'FinanceJournal',                   4, 1, __ENTITY__);
INSERT INTO llx_accounting_journal (code, label, nature, active, entity) VALUES ('OD',  'ACCOUNTING_MISCELLANEOUS_JOURNAL', 1, 1, __ENTITY__);
INSERT INTO llx_accounting_journal (code, label, nature, active, entity) VALUES ('AN',  'ACCOUNTING_HAS_NEW_JOURNAL',       9, 1, __ENTITY__);
INSERT INTO llx_accounting_journal (code, label, nature, active, entity) VALUES ('ER',  'ExpenseReportsJournal',            5, 1, __ENTITY__);
INSERT INTO llx_accounting_journal (code, label, nature, active, entity) VALUES ('INV', 'InventoryJournal',                 8, 1, __ENTITY__);
