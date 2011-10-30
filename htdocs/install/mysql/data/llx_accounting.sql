-- Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
-- Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
-- Copyright (C) 2004      Guillaume Delecourt  <guillaume.delecourt@opensides.be>
-- Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
-- Copyright (C) 2007 	   Patrick Raguin       <patrick.raguin@gmail.com>
-- Copyright (C) 2011 	   Alexandre Spangaro   <alexandre.spangaro@gmail.com>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 2 of the License, or
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
-- $Id: llx_accounting.sql,v 1.2 2011/08/03 01:25:44 eldy Exp $
--

--
-- Ne pas placer de commentaire en fin de ligne, ce fichier est parsé lors
-- de l'install et tous les sigles '--' sont supprimés.
--

delete from llx_accountingaccount;
delete from llx_accountingsystem;

--
-- Descriptif des plans comptables FR PCG99-ABREGE
--

insert into llx_accountingsystem (pcg_version, fk_pays, label, datec, fk_author, active) VALUES ('PCG99-ABREGE', 1, 'The simple accountancy french plan', CURRENT_DATE, null, 0);

insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (  1,'PCG99-ABREGE','CAPIT', 'CAPITAL', '101', '1', 'Capital');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (  2,'PCG99-ABREGE','CAPIT', 'XXXXXX',  '105', '1', 'Ecarts de réévaluation');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (  3,'PCG99-ABREGE','CAPIT', 'XXXXXX', '1061', '1', 'Réserve légale');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (  4,'PCG99-ABREGE','CAPIT', 'XXXXXX', '1063', '1', 'Réserves statutaires ou contractuelles');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (  5,'PCG99-ABREGE','CAPIT', 'XXXXXX', '1064', '1', 'Réserves réglementées');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (  6,'PCG99-ABREGE','CAPIT', 'XXXXXX', '1068', '1', 'Autres réserves');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (  7,'PCG99-ABREGE','CAPIT', 'XXXXXX',  '108', '1', 'Compte de l''exploitant');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (  8,'PCG99-ABREGE','CAPIT', 'XXXXXX',   '12', '1', 'Résultat de l''exercice');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (  9,'PCG99-ABREGE','CAPIT', 'XXXXXX',  '145', '1', 'Amortissements dérogatoires');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 10,'PCG99-ABREGE','CAPIT', 'XXXXXX',  '146', '1', 'Provision spéciale de réévaluation');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 11,'PCG99-ABREGE','CAPIT', 'XXXXXX',  '147', '1', 'Plus-values réinvesties');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 12,'PCG99-ABREGE','CAPIT', 'XXXXXX',  '148', '1', 'Autres provisions réglementées');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 13,'PCG99-ABREGE','CAPIT', 'XXXXXX',   '15', '1', 'Provisions pour risques et charges');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 14,'PCG99-ABREGE','CAPIT', 'XXXXXX',   '16', '1', 'Emprunts et dettes assimilees');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 15,'PCG99-ABREGE','IMMO',  'XXXXXX',   '20', '2', 'Immobilisations incorporelles');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 16,'PCG99-ABREGE','IMMO',  'XXXXXX',  '201','20', 'Frais d''établissement');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 17,'PCG99-ABREGE','IMMO',  'XXXXXX',  '206','20', 'Droit au bail');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 18,'PCG99-ABREGE','IMMO',  'XXXXXX',  '207','20', 'Fonds commercial');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 19,'PCG99-ABREGE','IMMO',  'XXXXXX',  '208','20', 'Autres immobilisations incorporelles');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 20,'PCG99-ABREGE','IMMO',  'XXXXXX',   '21', '2', 'Immobilisations corporelles');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 21,'PCG99-ABREGE','IMMO',  'XXXXXX',   '23', '2', 'Immobilisations en cours');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 22,'PCG99-ABREGE','IMMO',  'XXXXXX',   '27', '2', 'Autres immobilisations financieres');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 23,'PCG99-ABREGE','IMMO',  'XXXXXX',  '280', '2', 'Amortissements des immobilisations incorporelles');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 24,'PCG99-ABREGE','IMMO',  'XXXXXX',  '281', '2', 'Amortissements des immobilisations corporelles');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 25,'PCG99-ABREGE','IMMO',  'XXXXXX',  '290', '2', 'Provisions pour dépréciation des immobilisations incorporelles');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 26,'PCG99-ABREGE','IMMO',  'XXXXXX',  '291', '2', 'Provisions pour dépréciation des immobilisations corporelles');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 27,'PCG99-ABREGE','IMMO',  'XXXXXX',  '297', '2', 'Provisions pour dépréciation des autres immobilisations financières');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 28,'PCG99-ABREGE','STOCK', 'XXXXXX',   '31', '3', 'Matieres premières');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 29,'PCG99-ABREGE','STOCK', 'XXXXXX',   '32', '3', 'Autres approvisionnements');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 30,'PCG99-ABREGE','STOCK', 'XXXXXX',   '33', '3', 'En-cours de production de biens');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 31,'PCG99-ABREGE','STOCK', 'XXXXXX',   '34', '3', 'En-cours de production de services');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 32,'PCG99-ABREGE','STOCK', 'XXXXXX',   '35', '3', 'Stocks de produits');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 33,'PCG99-ABREGE','STOCK', 'XXXXXX',   '37', '3', 'Stocks de marchandises');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 34,'PCG99-ABREGE','STOCK', 'XXXXXX',  '391', '3', 'Provisions pour dépréciation des matières premières');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 35,'PCG99-ABREGE','STOCK', 'XXXXXX',  '392', '3', 'Provisions pour dépréciation des autres approvisionnements');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 36,'PCG99-ABREGE','STOCK', 'XXXXXX',  '393', '3', 'Provisions pour dépréciation des en-cours de production de biens');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 37,'PCG99-ABREGE','STOCK', 'XXXXXX',  '394', '3', 'Provisions pour dépréciation des en-cours de production de services');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 38,'PCG99-ABREGE','STOCK', 'XXXXXX',  '395', '3', 'Provisions pour dépréciation des stocks de produits');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 39,'PCG99-ABREGE','STOCK', 'XXXXXX',  '397', '3', 'Provisions pour dépréciation des stocks de marchandises');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 40,'PCG99-ABREGE','TIERS', 'SUPPLIER','400', '4', 'Fournisseurs et Comptes rattachés');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 41,'PCG99-ABREGE','TIERS', 'XXXXXX',  '409', '4', 'Fournisseurs débiteurs');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 42,'PCG99-ABREGE','TIERS', 'CUSTOMER','410', '4', 'Clients et Comptes rattachés');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 43,'PCG99-ABREGE','TIERS', 'XXXXXX',  '419', '4', 'Clients créditeurs');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 44,'PCG99-ABREGE','TIERS', 'XXXXXX',  '421', '4', 'Personnel');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 45,'PCG99-ABREGE','TIERS', 'XXXXXX',  '428', '4', 'Personnel');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 46,'PCG99-ABREGE','TIERS', 'XXXXXX',   '43', '4', 'Sécurité sociale et autres organismes sociaux');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 47,'PCG99-ABREGE','TIERS', 'XXXXXX',  '444', '4', 'Etat - impôts sur bénéfice');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 48,'PCG99-ABREGE','TIERS', 'XXXXXX',  '445', '4', 'Etat - Taxes sur chiffre affaire');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 49,'PCG99-ABREGE','TIERS', 'XXXXXX',  '447', '4', 'Autres impôts, taxes et versements assimilés');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 50,'PCG99-ABREGE','TIERS', 'XXXXXX',   '45', '4', 'Groupe et associes');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 51,'PCG99-ABREGE','TIERS', 'XXXXXX',  '455','45', 'Associés');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 52,'PCG99-ABREGE','TIERS', 'XXXXXX',   '46', '4', 'Débiteurs divers et créditeurs divers');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 53,'PCG99-ABREGE','TIERS', 'XXXXXX',   '47', '4', 'Comptes transitoires ou d''attente');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 54,'PCG99-ABREGE','TIERS', 'XXXXXX',  '481', '4', 'Charges à répartir sur plusieurs exercices');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 55,'PCG99-ABREGE','TIERS', 'XXXXXX',  '486', '4', 'Charges constatées d''avance');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 56,'PCG99-ABREGE','TIERS', 'XXXXXX',  '487', '4', 'Produits constatés d''avance');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 57,'PCG99-ABREGE','TIERS', 'XXXXXX',  '491', '4', 'Provisions pour dépréciation des comptes de clients');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 58,'PCG99-ABREGE','TIERS', 'XXXXXX',  '496', '4', 'Provisions pour dépréciation des comptes de débiteurs divers');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 59,'PCG99-ABREGE','FINAN', 'XXXXXX',   '50', '5', 'Valeurs mobilières de placement');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 60,'PCG99-ABREGE','FINAN', 'BANK',     '51', '5', 'Banques, établissements financiers et assimilés');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 61,'PCG99-ABREGE','FINAN', 'CASH',     '53', '5', 'Caisse');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 62,'PCG99-ABREGE','FINAN', 'XXXXXX',   '54', '5', 'Régies d''avance et accréditifs');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 63,'PCG99-ABREGE','FINAN', 'XXXXXX',   '58', '5', 'Virements internes');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 64,'PCG99-ABREGE','FINAN', 'XXXXXX',  '590', '5', 'Provisions pour dépréciation des valeurs mobilières de placement');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 65,'PCG99-ABREGE','CHARGE','PRODUCT',  '60', '6', 'Achats');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 66,'PCG99-ABREGE','CHARGE','XXXXXX',  '603','60', 'Variations des stocks');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 67,'PCG99-ABREGE','CHARGE','SERVICE',  '61', '6', 'Services extérieurs');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 68,'PCG99-ABREGE','CHARGE','XXXXXX',   '62', '6', 'Autres services extérieurs');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 69,'PCG99-ABREGE','CHARGE','XXXXXX',   '63', '6', 'Impôts, taxes et versements assimiles');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 70,'PCG99-ABREGE','CHARGE','XXXXXX',  '641', '6', 'Rémunérations du personnel');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 71,'PCG99-ABREGE','CHARGE','XXXXXX',  '644', '6', 'Rémunération du travail de l''exploitant');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 72,'PCG99-ABREGE','CHARGE','SOCIAL',  '645', '6', 'Charges de sécurité sociale et de prévoyance');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 73,'PCG99-ABREGE','CHARGE','XXXXXX',  '646', '6', 'Cotisations sociales personnelles de l''exploitant');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 74,'PCG99-ABREGE','CHARGE','XXXXXX',   '65', '6', 'Autres charges de gestion courante');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 75,'PCG99-ABREGE','CHARGE','XXXXXX',   '66', '6', 'Charges financières');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 76,'PCG99-ABREGE','CHARGE','XXXXXX',   '67', '6', 'Charges exceptionnelles');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 77,'PCG99-ABREGE','CHARGE','XXXXXX',  '681', '6', 'Dotations aux amortissements et aux provisions');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 78,'PCG99-ABREGE','CHARGE','XXXXXX',  '686', '6', 'Dotations aux amortissements et aux provisions');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 79,'PCG99-ABREGE','CHARGE','XXXXXX',  '687', '6', 'Dotations aux amortissements et aux provisions');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 80,'PCG99-ABREGE','CHARGE','XXXXXX',  '691', '6', 'Participation des salariés aux résultats');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 81,'PCG99-ABREGE','CHARGE','XXXXXX',  '695', '6', 'Impôts sur les bénéfices');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 82,'PCG99-ABREGE','CHARGE','XXXXXX',  '697', '6', 'Imposition forfaitaire annuelle des sociétés');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 83,'PCG99-ABREGE','CHARGE','XXXXXX',  '699', '6', 'Produits');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 84,'PCG99-ABREGE','PROD',  'PRODUCT', '701', '7', 'Ventes de produits finis');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 85,'PCG99-ABREGE','PROD',  'SERVICE', '706', '7', 'Prestations de services');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 86,'PCG99-ABREGE','PROD',  'PRODUCT', '707', '7', 'Ventes de marchandises');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 87,'PCG99-ABREGE','PROD',  'PRODUCT', '708', '7', 'Produits des activités annexes');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 88,'PCG99-ABREGE','PROD',  'XXXXXX',  '709', '7', 'Rabais, remises et ristournes accordés par l''entreprise');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 89,'PCG99-ABREGE','PROD',  'XXXXXX',  '713', '7', 'Variation des stocks');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 90,'PCG99-ABREGE','PROD',  'XXXXXX',   '72', '7', 'Production immobilisée');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 91,'PCG99-ABREGE','PROD',  'XXXXXX',   '73', '7', 'Produits nets partiels sur opérations à long terme');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 92,'PCG99-ABREGE','PROD',  'XXXXXX',   '74', '7', 'Subventions d''exploitation');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 93,'PCG99-ABREGE','PROD',  'XXXXXX',   '75', '7', 'Autres produits de gestion courante');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 94,'PCG99-ABREGE','PROD',  'XXXXXX',  '753','75', 'Jetons de présence et rémunérations d''administrateurs, gérants,...');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 95,'PCG99-ABREGE','PROD',  'XXXXXX',  '754','75', 'Ristournes perçues des coopératives');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 96,'PCG99-ABREGE','PROD',  'XXXXXX',  '755','75', 'Quotes-parts de résultat sur opérations faites en commun');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 97,'PCG99-ABREGE','PROD',  'XXXXXX',   '76', '7', 'Produits financiers');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 98,'PCG99-ABREGE','PROD',  'XXXXXX',   '77', '7', 'Produits exceptionnels');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 99,'PCG99-ABREGE','PROD',  'XXXXXX',  '781', '7', 'Reprises sur amortissements et provisions');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (100,'PCG99-ABREGE','PROD',  'XXXXXX',  '786', '7', 'Reprises sur provisions pour risques');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (101,'PCG99-ABREGE','PROD',  'XXXXXX',  '787', '7', 'Reprises sur provisions');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (102,'PCG99-ABREGE','PROD',  'XXXXXX',   '79', '7', 'Transferts de charges');

--
-- Descriptif des plans comptables FR PCG99-BASE
--

insert into llx_accountingsystem (pcg_version, fk_pays, label, datec, fk_author, active) VALUES ('PCG99-BASE', 2, 'The base accountancy french plan', CURRENT_DATE, null, 0);

insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (103,'PCG99-BASE','CAPIT', 'XXXXXX',   '10',  '1', 'Capital  et réserves');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (104,'PCG99-BASE','CAPIT', 'CAPITAL', '101', '10', 'Capital');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (105,'PCG99-BASE','CAPIT', 'XXXXXX',  '104', '10', 'Primes liées au capital social');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (106,'PCG99-BASE','CAPIT', 'XXXXXX',  '105', '10', 'Ecarts de réévaluation');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (107,'PCG99-BASE','CAPIT', 'XXXXXX',  '106', '10', 'Réserves');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (108,'PCG99-BASE','CAPIT', 'XXXXXX',  '107', '10', 'Ecart d''equivalence');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (109,'PCG99-BASE','CAPIT', 'XXXXXX',  '108', '10', 'Compte de l''exploitant');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (110,'PCG99-BASE','CAPIT', 'XXXXXX',  '109', '10', 'Actionnaires : capital souscrit - non appelé');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (111,'PCG99-BASE','CAPIT', 'XXXXXX',   '11',  '1', 'Report à nouveau (solde créditeur ou débiteur)');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (112,'PCG99-BASE','CAPIT', 'XXXXXX',  '110', '11', 'Report à nouveau (solde créditeur)');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (113,'PCG99-BASE','CAPIT', 'XXXXXX',  '119', '11', 'Report à nouveau (solde débiteur)');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (114,'PCG99-BASE','CAPIT', 'XXXXXX',   '12',  '1', 'Résultat de l''exercice (bénéfice ou perte)');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (115,'PCG99-BASE','CAPIT', 'XXXXXX',  '120', '12', 'Résultat de l''exercice (bénéfice)');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (116,'PCG99-BASE','CAPIT', 'XXXXXX',  '129', '12', 'Résultat de l''exercice (perte)');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (117,'PCG99-BASE','CAPIT', 'XXXXXX',   '13',  '1', 'Subventions d''investissement');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (118,'PCG99-BASE','CAPIT', 'XXXXXX',  '131', '13', 'Subventions d''équipement');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (119,'PCG99-BASE','CAPIT', 'XXXXXX',  '138', '13', 'Autres subventions d''investissement');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (120,'PCG99-BASE','CAPIT', 'XXXXXX',  '139', '13', 'Subventions d''investissement inscrites au compte de résultat');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (121,'PCG99-BASE','CAPIT', 'XXXXXX',   '14',  '1', 'Provisions réglementées');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (122,'PCG99-BASE','CAPIT', 'XXXXXX',  '142', '14', 'Provisions réglementées relatives aux immobilisations');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (123,'PCG99-BASE','CAPIT', 'XXXXXX',  '143', '14', 'Provisions réglementées relatives aux stocks');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (124,'PCG99-BASE','CAPIT', 'XXXXXX',  '144', '14', 'Provisions réglementées relatives aux autres éléments de l''actif');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (125,'PCG99-BASE','CAPIT', 'XXXXXX',  '145', '14', 'Amortissements dérogatoires');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (126,'PCG99-BASE','CAPIT', 'XXXXXX',  '146', '14', 'Provision spéciale de réévaluation');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (127,'PCG99-BASE','CAPIT', 'XXXXXX',  '147', '14', 'Plus-values réinvesties');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (128,'PCG99-BASE','CAPIT', 'XXXXXX',  '148', '14', 'Autres provisions réglementées');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (129,'PCG99-BASE','CAPIT', 'XXXXXX',   '15',  '1', 'Provisions pour risques et charges');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (130,'PCG99-BASE','CAPIT', 'XXXXXX',  '151', '15', 'Provisions pour risques');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (131,'PCG99-BASE','CAPIT', 'XXXXXX',  '153', '15', 'Provisions pour pensions et obligations similaires');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (132,'PCG99-BASE','CAPIT', 'XXXXXX',  '154', '15', 'Provisions pour restructurations');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (133,'PCG99-BASE','CAPIT', 'XXXXXX',  '155', '15', 'Provisions pour impôts');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (134,'PCG99-BASE','CAPIT', 'XXXXXX',  '156', '15', 'Provisions pour renouvellement des immobilisations (entreprises concessionnaires)');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (135,'PCG99-BASE','CAPIT', 'XXXXXX',  '157', '15', 'Provisions pour charges à répartir sur plusieurs exercices');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (136,'PCG99-BASE','CAPIT', 'XXXXXX',  '158', '15', 'Autres provisions pour charges');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (137,'PCG99-BASE','CAPIT', 'XXXXXX',   '16',  '1', 'Emprunts et dettes assimilees');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (138,'PCG99-BASE','CAPIT', 'XXXXXX',  '161', '16', 'Emprunts obligataires convertibles');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (139,'PCG99-BASE','CAPIT', 'XXXXXX',  '163', '16', 'Autres emprunts obligataires');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (140,'PCG99-BASE','CAPIT', 'XXXXXX',  '164', '16', 'Emprunts auprès des établissements de crédit');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (141,'PCG99-BASE','CAPIT', 'XXXXXX',  '165', '16', 'Dépôts et cautionnements reçus');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (142,'PCG99-BASE','CAPIT', 'XXXXXX',  '166', '16', 'Participation des salariés aux résultats');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (143,'PCG99-BASE','CAPIT', 'XXXXXX',  '167', '16', 'Emprunts et dettes assortis de conditions particulières');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (144,'PCG99-BASE','CAPIT', 'XXXXXX',  '168', '16', 'Autres emprunts et dettes assimilées');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (145,'PCG99-BASE','CAPIT', 'XXXXXX',  '169', '16', 'Primes de remboursement des obligations');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (146,'PCG99-BASE','CAPIT', 'XXXXXX',   '17',  '1', 'Dettes rattachées à des participations');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (147,'PCG99-BASE','CAPIT', 'XXXXXX',  '171', '17', 'Dettes rattachées à des participations (groupe)');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (148,'PCG99-BASE','CAPIT', 'XXXXXX',  '174', '17', 'Dettes rattachées à des participations (hors groupe)');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (149,'PCG99-BASE','CAPIT', 'XXXXXX',  '178', '17', 'Dettes rattachées à des sociétés en participation');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (150,'PCG99-BASE','CAPIT', 'XXXXXX',   '18',  '1', 'Comptes de liaison des établissements et sociétés en participation');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (151,'PCG99-BASE','CAPIT', 'XXXXXX',  '181', '18', 'Comptes de liaison des établissements');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (152,'PCG99-BASE','CAPIT', 'XXXXXX',  '186', '18', 'Biens et prestations de services échangés entre établissements (charges)');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (153,'PCG99-BASE','CAPIT', 'XXXXXX',  '187', '18', 'Biens et prestations de services échangés entre établissements (produits)');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (154,'PCG99-BASE','CAPIT', 'XXXXXX',  '188', '18', 'Comptes de liaison des sociétés en participation');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (155,'PCG99-BASE','IMMO',  'XXXXXX',   '20',  '2', 'Immobilisations incorporelles');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (156,'PCG99-BASE','IMMO',  'XXXXXX',  '201', '20', 'Frais d''établissement');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (157,'PCG99-BASE','IMMO',  'XXXXXX',  '203', '20', 'Frais de recherche et de développement');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (158,'PCG99-BASE','IMMO',  'XXXXXX',  '205', '20', 'Concessions et droits similaires, brevets, licences, marques, procédés, logiciels, droits et valeurs similaires');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (159,'PCG99-BASE','IMMO',  'XXXXXX',  '206', '20', 'Droit au bail');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (160,'PCG99-BASE','IMMO',  'XXXXXX',  '207', '20', 'Fonds commercial');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (161,'PCG99-BASE','IMMO',  'XXXXXX',  '208', '20', 'Autres immobilisations incorporelles');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (162,'PCG99-BASE','IMMO',  'XXXXXX',   '21',  '2', 'Immobilisations corporelles');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (163,'PCG99-BASE','IMMO',  'XXXXXX',  '211', '21', 'Terrains');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (164,'PCG99-BASE','IMMO',  'XXXXXX',  '212', '21', 'Agencements et aménagements de terrains');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (165,'PCG99-BASE','IMMO',  'XXXXXX',  '213', '21', 'Constructions');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (166,'PCG99-BASE','IMMO',  'XXXXXX',  '214', '21', 'Constructions sur sol d''autrui');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (167,'PCG99-BASE','IMMO',  'XXXXXX',  '215', '21', 'Installations techniques, matériels et outillage industriels');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (168,'PCG99-BASE','IMMO',  'XXXXXX',  '218', '21', 'Autres immobilisations corporelles');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (169,'PCG99-BASE','IMMO',  'XXXXXX',   '22',  '2', 'Immobilisations mises en concession');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (170,'PCG99-BASE','IMMO',  'XXXXXX',   '23',  '2', 'Immobilisations en cours');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (171,'PCG99-BASE','IMMO',  'XXXXXX',  '231', '23', 'Immobilisations corporelles en cours');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (172,'PCG99-BASE','IMMO',  'XXXXXX',  '232', '23', 'Immobilisations incorporelles en cours');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (173,'PCG99-BASE','IMMO',  'XXXXXX',  '237', '23', 'Avances et acomptes versés sur immobilisations incorporelles');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (174,'PCG99-BASE','IMMO',  'XXXXXX',  '238', '23', 'Avances et acomptes versés sur commandes d''immobilisations corporelles');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (175,'PCG99-BASE','IMMO',  'XXXXXX',   '25',  '2', 'Parts dans des entreprises liées et créances sur des entreprises liées');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (176,'PCG99-BASE','IMMO',  'XXXXXX',   '26',  '2', 'Participations et créances rattachées à des participations');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (177,'PCG99-BASE','IMMO',  'XXXXXX',  '261', '26', 'Titres de participation');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (178,'PCG99-BASE','IMMO',  'XXXXXX',  '266', '26', 'Autres formes de participation');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (179,'PCG99-BASE','IMMO',  'XXXXXX',  '267', '26', 'Créances rattachées à des participations');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (180,'PCG99-BASE','IMMO',  'XXXXXX',  '268', '26', 'Créances rattachées à des sociétés en participation');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (181,'PCG99-BASE','IMMO',  'XXXXXX',  '269', '26', 'Versements restant à effectuer sur titres de participation non libérés');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (182,'PCG99-BASE','IMMO',  'XXXXXX',   '27',  '2', 'Autres immobilisations financieres');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (183,'PCG99-BASE','IMMO',  'XXXXXX',  '271', '27', 'Titres immobilisés autres que les titres immobilisés de l''activité de portefeuille (droit de propriété)');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (184,'PCG99-BASE','IMMO',  'XXXXXX',  '272', '27', 'Titres immobilisés (droit de créance)');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (185,'PCG99-BASE','IMMO',  'XXXXXX',  '273', '27', 'Titres immobilisés de l''activité de portefeuille');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (186,'PCG99-BASE','IMMO',  'XXXXXX',  '274', '27', 'Prêts');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (187,'PCG99-BASE','IMMO',  'XXXXXX',  '275', '27', 'Dépôts et cautionnements versés');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (188,'PCG99-BASE','IMMO',  'XXXXXX',  '276', '27', 'Autres créances immobilisées');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (189,'PCG99-BASE','IMMO',  'XXXXXX',  '277', '27', '(Actions propres ou parts propres)');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (190,'PCG99-BASE','IMMO',  'XXXXXX',  '279', '27', 'Versements restant à effectuer sur titres immobilisés non libérés');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (191,'PCG99-BASE','IMMO',  'XXXXXX',   '28',  '2', 'Amortissements des immobilisations');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (192,'PCG99-BASE','IMMO',  'XXXXXX',  '280', '28', 'Amortissements des immobilisations incorporelles');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (193,'PCG99-BASE','IMMO',  'XXXXXX',  '281', '28', 'Amortissements des immobilisations corporelles');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (194,'PCG99-BASE','IMMO',  'XXXXXX',  '282', '28', 'Amortissements des immobilisations mises en concession');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (195,'PCG99-BASE','IMMO',  'XXXXXX',   '29',  '2', 'Dépréciations des immobilisations');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (196,'PCG99-BASE','IMMO',  'XXXXXX',  '290', '29', 'Dépréciations des immobilisations incorporelles');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (197,'PCG99-BASE','IMMO',  'XXXXXX',  '291', '29', 'Dépréciations des immobilisations corporelles');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (198,'PCG99-BASE','IMMO',  'XXXXXX',  '292', '29', 'Dépréciations des immobilisations mises en concession');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (199,'PCG99-BASE','IMMO',  'XXXXXX',  '293', '29', 'Dépréciations des immobilisations en cours');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (200,'PCG99-BASE','IMMO',  'XXXXXX',  '296', '29', 'Provisions pour dépréciation des participations et créances rattachées à des participations');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (201,'PCG99-BASE','IMMO',  'XXXXXX',  '297', '29', 'Provisions pour dépréciation des autres immobilisations financières');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (202,'PCG99-BASE','STOCK', 'XXXXXX',   '31',  '3', 'Matières premières (et fournitures)');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (203,'PCG99-BASE','STOCK', 'XXXXXX',  '311', '31', 'Matières (ou groupe) A');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (204,'PCG99-BASE','STOCK', 'XXXXXX',  '312', '31', 'Matières (ou groupe) B');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (205,'PCG99-BASE','STOCK', 'XXXXXX',  '317', '31', 'Fournitures A, B, C,');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (206,'PCG99-BASE','STOCK', 'XXXXXX',   '32',  '3', 'Autres approvisionnements');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (207,'PCG99-BASE','STOCK', 'XXXXXX',  '321', '32', 'Matières consommables');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (208,'PCG99-BASE','STOCK', 'XXXXXX',  '322', '32', 'Fournitures consommables');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (209,'PCG99-BASE','STOCK', 'XXXXXX',  '326', '32', 'Emballages');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (210,'PCG99-BASE','STOCK', 'XXXXXX',   '33',  '3', 'En-cours de production de biens');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (211,'PCG99-BASE','STOCK', 'XXXXXX',  '331', '33', 'Produits en cours');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (212,'PCG99-BASE','STOCK', 'XXXXXX',  '335', '33', 'Travaux en cours');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (213,'PCG99-BASE','STOCK', 'XXXXXX',   '34',  '3', 'En-cours de production de services');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (214,'PCG99-BASE','STOCK', 'XXXXXX',  '341', '34', 'Etudes en cours');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (215,'PCG99-BASE','STOCK', 'XXXXXX',  '345', '34', 'Prestations de services en cours');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (216,'PCG99-BASE','STOCK', 'XXXXXX',   '35',  '3', 'Stocks de produits');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (217,'PCG99-BASE','STOCK', 'XXXXXX',  '351', '35', 'Produits intermédiaires');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (218,'PCG99-BASE','STOCK', 'XXXXXX',  '355', '35', 'Produits finis');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (219,'PCG99-BASE','STOCK', 'XXXXXX',  '358', '35', 'Produits résiduels (ou matières de récupération)');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (220,'PCG99-BASE','STOCK', 'XXXXXX',   '37',  '3', 'Stocks de marchandises');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (221,'PCG99-BASE','STOCK', 'XXXXXX',  '371', '37', 'Marchandises (ou groupe) A');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (222,'PCG99-BASE','STOCK', 'XXXXXX',  '372', '37', 'Marchandises (ou groupe) B');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (223,'PCG99-BASE','STOCK', 'XXXXXX',   '39',  '3', 'Provisions pour dépréciation des stocks et en-cours');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (224,'PCG99-BASE','STOCK', 'XXXXXX',  '391', '39', 'Provisions pour dépréciation des matières premières');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (225,'PCG99-BASE','STOCK', 'XXXXXX',  '392', '39', 'Provisions pour dépréciation des autres approvisionnements');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (226,'PCG99-BASE','STOCK', 'XXXXXX',  '393', '39', 'Provisions pour dépréciation des en-cours de production de biens');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (227,'PCG99-BASE','STOCK', 'XXXXXX',  '394', '39', 'Provisions pour dépréciation des en-cours de production de services');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (228,'PCG99-BASE','STOCK', 'XXXXXX',  '395', '39', 'Provisions pour dépréciation des stocks de produits');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (229,'PCG99-BASE','STOCK', 'XXXXXX',  '397', '39', 'Provisions pour dépréciation des stocks de marchandises');

insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 40,'PCG99-BASE','TIERS', 'SUPPLIER','400', '4', 'Fournisseurs et Comptes rattachés');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 41,'PCG99-BASE','TIERS', 'XXXXXX',  '409', '4', 'Fournisseurs débiteurs');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 42,'PCG99-BASE','TIERS', 'CUSTOMER','410', '4', 'Clients et Comptes rattachés');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 43,'PCG99-BASE','TIERS', 'XXXXXX',  '419', '4', 'Clients créditeurs');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 44,'PCG99-BASE','TIERS', 'XXXXXX',  '421', '4', 'Personnel');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 45,'PCG99-BASE','TIERS', 'XXXXXX',  '428', '4', 'Personnel');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 46,'PCG99-BASE','TIERS', 'XXXXXX',   '43', '4', 'Sécurité sociale et autres organismes sociaux');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 47,'PCG99-BASE','TIERS', 'XXXXXX',  '444', '4', 'Etat - impôts sur bénéfice');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 48,'PCG99-BASE','TIERS', 'XXXXXX',  '445', '4', 'Etat - Taxes sur chiffre affaire');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 49,'PCG99-BASE','TIERS', 'XXXXXX',  '447', '4', 'Autres impôts, taxes et versements assimilés');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 50,'PCG99-BASE','TIERS', 'XXXXXX',   '45', '4', 'Groupe et associes');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 51,'PCG99-BASE','TIERS', 'XXXXXX',  '455','45', 'Associés');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 52,'PCG99-BASE','TIERS', 'XXXXXX',   '46', '4', 'Débiteurs divers et créditeurs divers');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 53,'PCG99-BASE','TIERS', 'XXXXXX',   '47', '4', 'Comptes transitoires ou d''attente');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 54,'PCG99-BASE','TIERS', 'XXXXXX',  '481', '4', 'Charges à répartir sur plusieurs exercices');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 55,'PCG99-BASE','TIERS', 'XXXXXX',  '486', '4', 'Charges constatées d''avance');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 56,'PCG99-BASE','TIERS', 'XXXXXX',  '487', '4', 'Produits constatés d''avance');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 57,'PCG99-BASE','TIERS', 'XXXXXX',  '491', '4', 'Provisions pour dépréciation des comptes de clients');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 58,'PCG99-BASE','TIERS', 'XXXXXX',  '496', '4', 'Provisions pour dépréciation des comptes de débiteurs divers');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 59,'PCG99-BASE','FINAN', 'XXXXXX',   '50', '5', 'Valeurs mobilières de placement');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 60,'PCG99-BASE','FINAN', 'BANK',     '51', '5', 'Banques, établissements financiers et assimilés');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 61,'PCG99-BASE','FINAN', 'CASH',     '53', '5', 'Caisse');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 62,'PCG99-BASE','FINAN', 'XXXXXX',   '54', '5', 'Régies d''avance et accréditifs');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 63,'PCG99-BASE','FINAN', 'XXXXXX',   '58', '5', 'Virements internes');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 64,'PCG99-BASE','FINAN', 'XXXXXX',  '590', '5', 'Provisions pour dépréciation des valeurs mobilières de placement');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 65,'PCG99-BASE','CHARGE','PRODUCT',  '60', '6', 'Achats');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 66,'PCG99-BASE','CHARGE','XXXXXX',  '603','60', 'Variations des stocks');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 67,'PCG99-BASE','CHARGE','SERVICE',  '61', '6', 'Services extérieurs');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 68,'PCG99-BASE','CHARGE','XXXXXX',   '62', '6', 'Autres services extérieurs');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 69,'PCG99-BASE','CHARGE','XXXXXX',   '63', '6', 'Impôts, taxes et versements assimiles');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 70,'PCG99-BASE','CHARGE','XXXXXX',  '641', '6', 'Rémunérations du personnel');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 71,'PCG99-BASE','CHARGE','XXXXXX',  '644', '6', 'Rémunération du travail de l''exploitant');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 72,'PCG99-BASE','CHARGE','SOCIAL',  '645', '6', 'Charges de sécurité sociale et de prévoyance');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 73,'PCG99-BASE','CHARGE','XXXXXX',  '646', '6', 'Cotisations sociales personnelles de l''exploitant');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 74,'PCG99-BASE','CHARGE','XXXXXX',   '65', '6', 'Autres charges de gestion courante');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 75,'PCG99-BASE','CHARGE','XXXXXX',   '66', '6', 'Charges financières');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 76,'PCG99-BASE','CHARGE','XXXXXX',   '67', '6', 'Charges exceptionnelles');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 77,'PCG99-BASE','CHARGE','XXXXXX',  '681', '6', 'Dotations aux amortissements et aux provisions');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 78,'PCG99-BASE','CHARGE','XXXXXX',  '686', '6', 'Dotations aux amortissements et aux provisions');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 79,'PCG99-BASE','CHARGE','XXXXXX',  '687', '6', 'Dotations aux amortissements et aux provisions');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 80,'PCG99-BASE','CHARGE','XXXXXX',  '691', '6', 'Participation des salariés aux résultats');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 81,'PCG99-BASE','CHARGE','XXXXXX',  '695', '6', 'Impôts sur les bénéfices');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 82,'PCG99-BASE','CHARGE','XXXXXX',  '697', '6', 'Imposition forfaitaire annuelle des sociétés');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 83,'PCG99-BASE','CHARGE','XXXXXX',  '699', '6', 'Produits');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 84,'PCG99-BASE','PROD',  'PRODUCT', '701', '7', 'Ventes de produits finis');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 85,'PCG99-BASE','PROD',  'SERVICE', '706', '7', 'Prestations de services');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 86,'PCG99-BASE','PROD',  'PRODUCT', '707', '7', 'Ventes de marchandises');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 87,'PCG99-BASE','PROD',  'PRODUCT', '708', '7', 'Produits des activités annexes');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 88,'PCG99-BASE','PROD',  'XXXXXX',  '709', '7', 'Rabais, remises et ristournes accordés par l''entreprise');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 89,'PCG99-BASE','PROD',  'XXXXXX',  '713', '7', 'Variation des stocks');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 90,'PCG99-BASE','PROD',  'XXXXXX',   '72', '7', 'Production immobilisée');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 91,'PCG99-BASE','PROD',  'XXXXXX',   '73', '7', 'Produits nets partiels sur opérations à long terme');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 92,'PCG99-BASE','PROD',  'XXXXXX',   '74', '7', 'Subventions d''exploitation');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 93,'PCG99-BASE','PROD',  'XXXXXX',   '75', '7', 'Autres produits de gestion courante');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 94,'PCG99-BASE','PROD',  'XXXXXX',  '753','75', 'Jetons de présence et rémunérations d''administrateurs, gérants,...');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 95,'PCG99-BASE','PROD',  'XXXXXX',  '754','75', 'Ristournes perçues des coopératives');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 96,'PCG99-BASE','PROD',  'XXXXXX',  '755','75', 'Quotes-parts de résultat sur opérations faites en commun');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 97,'PCG99-BASE','PROD',  'XXXXXX',   '76', '7', 'Produits financiers');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 98,'PCG99-BASE','PROD',  'XXXXXX',   '77', '7', 'Produits exceptionnels');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES ( 99,'PCG99-BASE','PROD',  'XXXXXX',  '781', '7', 'Reprises sur amortissements et provisions');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (100,'PCG99-BASE','PROD',  'XXXXXX',  '786', '7', 'Reprises sur provisions pour risques');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (101,'PCG99-BASE','PROD',  'XXXXXX',  '787', '7', 'Reprises sur provisions');
insert into llx_accountingaccount (rowid, fk_pcg_version, pcg_type, pcg_subtype, account_number, account_parent, label) VALUES (102,'PCG99-BASE','PROD',  'XXXXXX',   '79', '7', 'Transferts de charges');
