-- Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
-- Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
-- Copyright (C) 2004      Guillaume Delecourt  <guillaume.delecourt@opensides.be>
-- Copyright (C) 2005-2018 Regis Houssin        <regis.houssin@capnetworks.com>
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
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
--

--
-- Ne pas placer de commentaire en fin de ligne, ce fichier est parsé lors
-- de l'install et tous les sigles '--' sont supprimés.
--

--
-- Description of the accounts in DK-STD
--

INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Indtægter', '', '1000', '', 'Salg af varer/ydelser m. moms', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Indtægter', '', '1010', '', 'Salg af varer EU', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Indtægter', '', '1020', '', 'Salg af ydelser EU', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Indtægter', '', '1030', '', 'Ej momspligtigt salg', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Indtægter', '', '1040', '', 'Regulering igangværende arbejder', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Omkostninger', '', '1100', '', 'Varekøb med moms', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Omkostninger', '', '1110', '', 'Køb af varer EU', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Omkostninger', '', '1120', '', 'Køb af ydelser EU', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Omkostninger', '', '1130', '', 'Varelagerregulering', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Omkostninger', '', '1140', '', 'Eget vareforbrug', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Omkostninger', '', '1300', '', 'Repræsentation', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Omkostninger', '', '1310', '', 'Annoncer/reklame', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Omkostninger', '', '1320', '', 'Rejseudgifter', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Omkostninger', '', '1330', '', 'Aviser og blade', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Omkostninger', '', '1400', '', 'Brændstof', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Omkostninger', '', '1410', '', 'Bilforsikring', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Omkostninger', '', '1420', '', 'Vedligeholdelse', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Omkostninger', '', '1430', '', 'Grøn ejerafgift mv.', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Omkostninger', '', '1440', '', 'Leje og leasing', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Omkostninger', '', '1450', '', 'Bilvask og pleje af bil', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Omkostninger', '', '1460', '', 'Parkeringsudgifter', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Omkostninger', '', '1470', '', 'Biludgifter efter statens takster', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Omkostninger', '', '1480', '', 'Fri bil', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Omkostninger', '', '1490', '', 'Privat andel af biludgifter', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Omkostninger', '', '1600', '', 'Husleje uden forbrug', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Omkostninger', '', '1610', '', 'Forbrugsudgifter (el, vand, gas og varme mv)', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Omkostninger', '', '1620', '', 'Ejendomsskatter og forsikringer', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Omkostninger', '', '1630', '', 'Vedligeholdelse af lokaler', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Omkostninger', '', '1700', '', 'Kontorartikler og tryksager', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Omkostninger', '', '1705', '', 'Telefon og internet', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Omkostninger', '', '1710', '', 'Fri telefon privat andel', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Omkostninger', '', '1715', '', 'Anskaffelse af småaktiver', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Omkostninger', '', '1720', '', 'Arbejdstøj', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Omkostninger', '', '1725', '', 'Rådgiverudgifter', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Omkostninger', '', '1730', '', 'Porto og gebyrer', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Omkostninger', '', '1735', '', 'Forsikringer', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Omkostninger', '', '1740', '', 'Bøger og faglitteratur', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Omkostninger', '', '1745', '', 'Konstaterede tab på debitorer', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Omkostninger', '', '1750', '', 'Kassedifferencer', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Afskrivninger', '', '2000', '', 'Afskrivning driftsmidler og inventar', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Afskrivninger', '', '2010', '', 'Afskrivning blandede driftsmidler', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Afskrivninger', '', '2020', '', 'Afskrivninger privat andel', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Afskrivninger', '', '2030', '', 'Gevinst og tab ved salg af aktiver', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Finansielle poster', '', '2400', '', 'Renteindtægter bank', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Finansielle poster', '', '2410', '', 'Renteindtægter kunder', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Finansielle poster', '', '2500', '', 'Renteudgifter bank og realkreditinstitut', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Finansielle poster', '', '2510', '', 'Renteudgifter leverandører', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Finansielle poster', '', '2520', '', 'Fradragsberettigede låneomkostninger', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Finansielle poster', '', '2530', '', 'Fradragsberettigede renteudgifter til det offentlige', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Balance', '', '3000', '', 'Afskrivningsgrundlag primo', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Balance', '', '3010', '', 'Årets køb', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Balance', '', '3015', '', 'Årets forbedringer', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Balance', '', '3020', '', 'Årest salg', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Balance', '', '3030', '', 'Årets afskrivning', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Balance', '', '3040', '', 'Gevinst og tab', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Balance', '', '3100', '', 'Afskrivningsgrundlag primo', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Balance', '', '3110', '', 'Årets køb', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Balance', '', '3115', '', 'Årets forbedringer', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Balance', '', '3120', '', 'Årets salg', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Balance', '', '3130', '', 'Årets afskrivning', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Balance', '', '3140', '', 'Gevinst og tab', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Balance', '', '3900', '', 'Huslejedepositum', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Balance', '', '4000', '', 'Kasse', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Balance', '', '4010', '', 'Bank', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Balance', '', '4020', '', 'Forudbetalte omkostninger', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Balance', '', '4030', '', 'Debitorer', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Balance', '', '4040', '', 'Varelager', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Balance', '', '4050', '', 'Igangværende arbejder', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Balance', '', '5000', '', 'Egenkapital primo', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Balance', '', '5010', '', 'Årets resultat', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Balance', '', '5020', '', 'Privat hævet', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Balance', '', '5030', '', 'Fri bil', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Balance', '', '5040', '', 'Statens takster', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Balance', '', '5050', '', 'Fri telefon m.v.', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Balance', '', '5060', '', 'Private andele', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Balance', '', '5065', '', 'Private afskrivninger', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Balance', '', '5070', '', 'Driftsudgifter u/fradrag', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Balance', '', '5080', '', 'Privat udlæg', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Balance', '', '5090', '', 'Indskud', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Balance', '', '7000', '', 'Gæld bank og realkreditinstitut', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Balance', '', '7010', '', 'Gæld til leverandører af varer og tjenesteydelser', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Balance', '', '7020', '', 'Periodeafgrænsningsposter', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Balance', '', '7030', '', 'Anden gæld', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Balance', '', '8000', '', 'Salgsmoms', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Balance', '', '8010', '', 'Købsmoms', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Balance', '', '8020', '', 'Konto for afgift af varekøb i EU', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Balance', '', '8030', '', 'Konto for afgift af købte ydelser i EU', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Balance', '', '8040', '', 'El- afgift', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Balance', '', '8050', '', 'Øvrige energiafgifter', 1, __ENTITY__);
INSERT INTO llx_accounting_account (fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label, active, entity) VALUES ('DK-STD', 'Balance', '', '8060', '', 'Betalt moms', 1, __ENTITY__);
