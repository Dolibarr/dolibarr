-- Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
-- Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
-- Copyright (C) 2004      Guillaume Delecourt  <guillaume.delecourt@opensides.be>
-- Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
-- Copyright (C) 2007 	   Patrick Raguin       <patrick.raguin@gmail.com>
-- Copyright (C) 2011-2017 Alexandre Spangaro   <aspangaro@zendsi.com>
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

--
-- Ne pas placer de commentaire en fin de ligne, ce fichier est parsé lors
-- de l'install et tous les sigles '--' sont supprimés.
--

-- Description of the accounts in NL-VERKORT
-- ID 0100-9999
-- ADD 7006000 to rowid # Do no remove this comment --
--

INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1000, 'NL_VERKORT', 'BALANS', 'XXXXXX', '0050', '', 'Bedrijfspand en woning', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1001, 'NL-VERKORT', 'BALANS', 'XXXXXX', '0055', '', 'Afschrijving bedrijfspand en woning', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1002, 'NL-VERKORT', 'BALANS', 'XXXXXX', '0100', '', 'Inventaris', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1003, 'NL-VERKORT', 'BALANS', 'XXXXXX', '0105', '', 'Afschrijving inventaris', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1004, 'NL-VERKORT', 'BALANS', 'XXXXXX', '0150', '', 'Kantoor-inventaris', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1005, 'NL-VERKORT', 'BALANS', 'XXXXXX', '0155', '', 'Afschrijving kantoor-inventaris', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1006, 'NL-VERKORT', 'BALANS', 'XXXXXX', '0200', '', 'Transportmiddelen', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1007, 'NL-VERKORT', 'BALANS', 'XXXXXX', '0205', '', 'Afschrijving transportmiddelen', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1008, 'NL-VERKORT', 'BALANS', 'XXXXXX', '1100', '', 'Kas', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1009, 'NL-VERKORT', 'BALANS', 'XXXXXX', '1200', '', 'Bank', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1010, 'NL-VERKORT', 'BALANS', 'XXXXXX', '1300', '', 'Debiteuren', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1011, 'NL-VERKORT', 'BALANS', 'XXXXXX', '1310', '', 'Oninbare debiteuren', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1012, 'NL-VERKORT', 'BALANS', 'XXXXXX', '1500', '', 'Privé', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1013, 'NL-VERKORT', 'BALANS', 'XXXXXX', '1520', '', 'Privé IB/ZORGVERZ', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1014, 'NL-VERKORT', 'BALANS', 'XXXXXX', '1600', '', 'Crediteuren', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1015, 'NL-VERKORT', 'BALANS', 'XXXXXX', '1700', '', 'Ingehouden loonbelasing', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1016, 'NL-VERKORT', 'BALANS', 'XXXXXX', '1710', '', 'Afdracht loonbelasting', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1017, 'NL-VERKORT', 'BALANS', 'XXXXXX', '1720', '', 'Ingehouden pensioenlasten', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1018, 'NL-VERKORT', 'BALANS', 'XXXXXX', '1730', '', 'Reservering vakantiegeld', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1019, 'NL-VERKORT', 'BALANS', 'XXXXXX', '1820', '', 'BTW te betalen hoog', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1020, 'NL-VERKORT', 'BALANS', 'XXXXXX', '1825', '', 'BTW te betalen laag', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1021, 'NL-VERKORT', 'BALANS', 'XXXXXX', '1828', '', 'BTW te betalen auto privé', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1022, 'NL-VERKORT', 'BALANS', 'XXXXXX', '1829', '', 'BTW te betalen telefoon privé', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1023, 'NL-VERKORT', 'BALANS', 'XXXXXX', '1830', '', 'BTW te vorderen hoog', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1024, 'NL-VERKORT', 'BALANS', 'XXXXXX', '1835', '', 'BTW te vorderen laag', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1025, 'NL-VERKORT', 'BALANS', 'XXXXXX', '1890', '', 'Betaalde BTW', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1026, 'NL-VERKORT', 'BALANS', 'XXXXXX', '2200', '', 'Te betalen netto lonen', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1027, 'NL-VERKORT', 'BALANS', 'XXXXXX', '2500', '', 'Kruisposten', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1028, 'NL-VERKORT', 'BALANS', 'XXXXXX', '2900', '', 'Vraagposten', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1029, 'NL-VERKORT', 'BALANS', 'XXXXXX', '3000', '', 'Voorraad', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1030, 'NL-VERKORT', 'EXPENSE', 'XXXXXX', '4000', '', 'Kantoorbenodigdheden', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1031, 'NL-VERKORT', 'EXPENSE', 'XXXXXX', '4010', '', 'Porti', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1032, 'NL-VERKORT', 'EXPENSE', 'XXXXXX', '4020', '', 'Telefoon/Internet', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1033, 'NL-VERKORT', 'EXPENSE', 'XXXXXX', '4030', '', 'Onderhoud gebouwen', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1034, 'NL-VERKORT', 'EXPENSE', 'XXXXXX', '4040', '', 'Overige huisvestingskosten', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1035, 'NL-VERKORT', 'EXPENSE', 'XXXXXX', '4050', '', 'Belastingen en heffingen onroerend goed', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1036, 'NL-VERKORT', 'EXPENSE', 'XXXXXX', '4060', '', 'Onderhoud inventaris e.d.', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1037, 'NL-VERKORT', 'EXPENSE', 'XXXXXX', '4070', '', 'Kleine aanschaffingen < 500', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1038, 'NL-VERKORT', 'EXPENSE', 'XXXXXX', '4080', '', 'Kosten automatisering', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1039, 'NL-VERKORT', 'EXPENSE', 'XXXXXX', '4090', '', 'Abonnementen en contributies', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1040, 'NL-VERKORT', 'EXPENSE', 'XXXXXX', '4100', '', 'Autokosten', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1041, 'NL-VERKORT', 'EXPENSE', 'XXXXXX', '4110', '', 'Boetes', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1042, 'NL-VERKORT', 'EXPENSE', 'XXXXXX', '4120', '', 'Auto brandstof', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1043, 'NL-VERKORT', 'EXPENSE', 'XXXXXX', '4130', '', 'Auto belasting en verzekering', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1044, 'NL-VERKORT', 'EXPENSE', 'XXXXXX', '4140', '', 'BTW privé gebruik', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1045, 'NL-VERKORT', 'EXPENSE', 'XXXXXX', '4150', '', 'Bijtelling auto', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1046, 'NL-VERKORT', 'EXPENSE', 'XXXXXX', '4200', '', 'Reiskosten', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1047, 'NL-VERKORT', 'EXPENSE', 'XXXXXX', '4210', '', 'Reiskosten/eten', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1048, 'NL-VERKORT', 'EXPENSE', 'XXXXXX', '4220', '', 'Representatiekosten', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1049, 'NL-VERKORT', 'EXPENSE', 'XXXXXX', '4230', '', 'Werkkleding', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1050, 'NL-VERKORT', 'EXPENSE', 'XXXXXX', '4300', '', 'Magazijnkosten', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1051, 'NL-VERKORT', 'EXPENSE', 'XXXXXX', '4310', '', 'Stroom, gas en water', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1052, 'NL-VERKORT', 'EXPENSE', 'XXXXXX', '4320', '', 'Huur', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1053, 'NL-VERKORT', 'EXPENSE', 'XXXXXX', '4330', '', 'Reclamekosten', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1054, 'NL-VERKORT', 'EXPENSE', 'XXXXXX', '4340', '', 'Sponsoring', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1055, 'NL-VERKORT', 'EXPENSE', 'XXXXXX', '4350', '', 'Vertegenwoordigers/provisie', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1056, 'NL-VERKORT', 'EXPENSE', 'XXXXXX', '4400', '', 'Bruto lonen', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1057, 'NL-VERKORT', 'EXPENSE', 'XXXXXX', '4410', '', 'Sociale lasten', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1058, 'NL-VERKORT', 'EXPENSE', 'XXXXXX', '4500', '', 'Afschr. kosten gebouwen', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1059, 'NL-VERKORT', 'EXPENSE', 'XXXXXX', '4510', '', 'Afschr. kosten bedrijfsinventaris', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1060, 'NL-VERKORT', 'EXPENSE', 'XXXXXX', '4520', '', 'Afschr. kosten kantoorinventaris', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1061, 'NL-VERKORT', 'EXPENSE', 'XXXXXX', '4530', '', 'Afschr. kosten transportmiddelen', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1062, 'NL-VERKORT', 'EXPENSE', 'XXXXXX', '4600', '', 'Administratiekosten', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1063, 'NL-VERKORT', 'EXPENSE', 'XXXXXX', '4610', '', 'Kantinekosten', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1064, 'NL-VERKORT', 'EXPENSE', 'XXXXXX', '4620', '', 'Provisie ass. tussenpersoon', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1065, 'NL-VERKORT', 'EXPENSE', 'XXXXXX', '4630', '', 'Vraagposten (V&W)', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1066, 'NL-VERKORT', 'EXPENSE', 'XXXXXX', '4640', '', 'Diverse verzekeringen', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1067, 'NL-VERKORT', 'EXPENSE', 'XXXXXX', '4700', '', 'Overige kosten', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1068, 'NL-VERKORT', 'EXPENSE', 'XXXXXX', '4750', '', 'Nog te verdelen posten', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1069, 'NL-VERKORT', 'EXPENSE', 'XXXXXX', '4800', '', 'Rente hypotheek', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1070, 'NL-VERKORT', 'EXPENSE', 'XXXXXX', '4810', '', 'Rente overige leningen', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1071, 'NL-VERKORT', 'EXPENSE', 'XXXXXX', '4820', '', 'Rente en kosten RC', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1072, 'NL-VERKORT', 'EXPENSE', 'XXXXXX', '7000', '', 'Inkoop hoog', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1073, 'NL-VERKORT', 'EXPENSE', 'XXXXXX', '7010', '', 'Inkoop laag', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1074, 'NL-VERKORT', 'EXPENSE', 'XXXXXX', '7020', '', 'Inkoop nul BTW', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1075, 'NL-VERKORT', 'EXPENSE', 'XXXXXX', '7030', '', 'Inkoop binnen EU', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1076, 'NL-VERKORT', 'EXPENSE', 'XXXXXX', '7040', '', 'Inkoop buiten EU', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1077, 'NL-VERKORT', 'INCOME', 'XXXXXX', '8000', '', 'Omzet hoog', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1078, 'NL-VERKORT', 'INCOME', 'XXXXXX', '8010', '', 'Omzet laag', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1079, 'NL-VERKORT', 'INCOME', 'XXXXXX', '8020', '', 'Omzet nul BTW', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1080, 'NL-VERKORT', 'INCOME', 'XXXXXX', '8090', '', 'Onderhanden omzet', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1081, 'NL-VERKORT', 'INCOME', 'XXXXXX', '9000', '', 'Bijzondere baten', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1082, 'NL-VERKORT', 'EXPENSE', 'XXXXXX', '9010', '', 'Bijzondere lasten', 1);
INSERT INTO llx_accounting_account (entity, rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active) VALUES (__ENTITY__, 1083, 'NL-VERKORT', 'BALANS', 'XXXXXX', '9900', '', 'Kapitaal', 1);
