-- Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
-- Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
-- Copyright (C) 2004      Guillaume Delecourt  <guillaume.delecourt@opensides.be>
-- Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
-- Copyright (C) 2007 	   Patrick Raguin       <patrick.raguin@gmail.com>
-- Copyright (C) 2010-2016 Juanjo Menent        <jmenent@2byte.es>
-- Copyright (C) 2012 	   Sebastian Neuwert    <sebastian.neuwert@modula71.de>
-- Copyright (C) 2012 	   Tommaso Basilici     <t.basilici@19.coop>
-- Copyright (C) 2012	   Ricardo Schluter     <info@ripasch.nl>
-- Copyright (C) 2013	   Cedric GROSS		    <c.gross@kreiz-it.fr>
-- Copyright (C) 2020-2021 Udo Tamm       	    <dev@dolibit.de>
-- Copyright (C) 2022      Miro Sertić       	<miro.sertic0606@gmail.com>
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

-- WARNING ----------------------------------------------------------------
--
-- EN:
-- Do not put a comment at the end of the line, this file is parsed during
-- install and all '--' symbols are removed.
--
-- FR:
-- Ne pas placer de commentaire en fin de ligne, ce fichier est parsé lors
-- de l'install et tous les sigles '--' sont supprimés.
--

-- CONTENT ----------------------------------------------------------------
--
-- Legal Formes (en) / Formes juridiques (fr)
--
-- fk_pays = country_id
--


delete from llx_c_forme_juridique;

insert into llx_c_forme_juridique (fk_pays, code, libelle) values (0, '0','-');


-- Argentina
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (23, '2301', 'Monotributista', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (23, '2302', 'Sociedad Civil', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (23, '2303', 'Sociedades Comerciales', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (23, '2304', 'Sociedades de Hecho', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (23, '2305', 'Sociedades Irregulares', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (23, '2306', 'Sociedad Colectiva', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (23, '2307', 'Sociedad en Comandita Simple', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (23, '2308', 'Sociedad de Capital e Industria', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (23, '2309', 'Sociedad Accidental o en participación', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (23, '2310', 'Sociedad de Responsabilidad Limitada', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (23, '2311', 'Sociedad Anónima', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (23, '2312', 'Sociedad Anónima con Participación Estatal Mayoritaria', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (23, '2313', 'Sociedad en Comandita por Acciones (arts. 315 a 324, LSC)', 1);


-- Austria
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (41, '4100', 'GmbH - Gesellschaft mit beschränkter Haftung', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (41, '4101', 'GesmbH - Gesellschaft mit beschränkter Haftung', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (41, '4102', 'AG - Aktiengesellschaft', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (41, '4103', 'EWIV - Europäische wirtschaftliche Interessenvereinigung', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (41, '4104', 'KEG - Kommanditerwerbsgesellschaft', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (41, '4105', 'OEG - Offene Erwerbsgesellschaft', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (41, '4106', 'OHG - Offene Handelsgesellschaft', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (41, '4107', 'AG & Co KG - Kommanditgesellschaft', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (41, '4108', 'GmbH & Co KG - Kommanditgesellschaft', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (41, '4109', 'KG - Kommanditgesellschaft', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (41, '4110', 'OG - Offene Gesellschaft', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (41, '4111', 'GbR - Gesellschaft nach bürgerlichem Recht', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (41, '4112', 'GesbR - Gesellschaft nach bürgerlichem Recht', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (41, '4113', 'GesnbR - Gesellschaft nach bürgerlichem Recht', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (41, '4114', 'e.U. - eingetragener Einzelunternehmer', 1);


-- Belgium
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2, '200', 'Indépendant');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2, '201', 'SRL - Société à responsabilité limitée');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2, '202', 'SA   - Société Anonyme');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2, '203', 'SCRL - Société coopérative à responsabilité limitée');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2, '204', 'ASBL - Association sans but Lucratif');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2, '205', 'SCRI - Société coopérative à responsabilité illimitée');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2, '206', 'SCS  - Société en commandite simple');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2, '207', 'SCA  - Société en commandite par action');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2, '208', 'SNC  - Société en nom collectif');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2, '209', 'GIE  - Groupement d intérêt économique');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2, '210', 'GEIE - Groupement européen d intérêt économique');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2, '220', 'Eenmanszaak');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2, '221', 'BVBA - Besloten vennootschap met beperkte aansprakelijkheid');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2, '222', 'NV   - Naamloze Vennootschap');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2, '223', 'CVBA - Coöperatieve vennootschap met beperkte aansprakelijkheid');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2, '224', 'VZW  - Vereniging zonder winstoogmerk');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2, '225', 'CVOA - Coöperatieve vennootschap met onbeperkte aansprakelijkheid ');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2, '226', 'GCV  - Gewone commanditaire vennootschap');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2, '227', 'Comm.VA - Commanditaire vennootschap op aandelen');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2, '228', 'VOF  - Vennootschap onder firma');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2, '229', 'VS0  - Vennootschap met sociaal oogmerk');


-- France: Catégories niveau II - Extrait de https://www.insee.fr/fr/information/2028129 - Dernière mise à jour Septembre 2022
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'09','Organisme de placement collectif en valeurs mobilières sans personnalité morale');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'10','Entrepreneur individuel');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'21','Indivision');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'22','Société créée de fait');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'23','Société en participation');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'24','Fiducie');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'27','Paroisse hors zone concordataire');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'28','Assujetti unique à la TVA');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'29','Autre groupement de droit privé non doté de la personnalité morale');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'31','Personne morale de droit étranger, immatriculée au RCS');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'32','Personne morale de droit étranger, non immatriculée au RCS');

insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'41','Etablissement public ou régie à caractère industriel ou commercial');

insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'51','Société coopérative commerciale particulière');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'52','Société en nom collectif');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'53','Société en commandite');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'54','Société à responsabilité limitée (SARL)');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'55','Société anonyme à conseil d''administration');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'56','Société anonyme à directoire');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'57','Société par actions simplifiée (SAS)');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'58','Société européenne');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'61','Caisse d''épargne et de prévoyance');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'62','Groupement d''intérêt économique (GIE)');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'63','Société coopérative agricole');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'64','Société d''assurance mutuelle');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'65','Société civile');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'66','Société publiques locales');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'69','Autre personne morale de droit privé inscrite au RCS');

insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'71','Administration de l état');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'72','Collectivité territoriale');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'73','Etablissement public administratif');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'74','Personne morale de droit public administratif');

insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'81','Organisme gérant régime de protection social à adhésion obligatoire');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'82','Organisme mutualiste');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'83','Comité d entreprise');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'84','Organisme professionnel');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'85','Organisme de retraite à adhésion non obligatoire');

insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'91','Syndicat de propriétaires');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'92','Association loi 1901 ou assimilé');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'93','Fondation');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'99','Autre personne morale de droit privé');


-- Germany
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (5, '500', 'GmbH - Gesellschaft mit beschränkter Haftung');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (5, '501', 'AG - Aktiengesellschaft ');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (5, '502', 'GmbH&Co. KG - Gesellschaft mit beschränkter Haftung & Compagnie Kommanditgesellschaft');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (5, '503', 'Gewerbe - Personengesellschaft');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (5, '504', 'UG - Unternehmergesellschaft -haftungsbeschränkt-');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (5, '505', 'GbR - Gesellschaft des bürgerlichen Rechts');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (5, '506', 'KG - Kommanditgesellschaft');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (5, '507', 'Ltd. - Limited Company');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (5, '508', 'OHG - Offene Handelsgesellschaft');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (5, '509', 'eG - eingetragene Genossenschaft');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (5, '510', 'e.V. - eingetragener Verein');

-- Denmark
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (80, '8001', 'Aktieselvskab A/S');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (80, '8002', 'Anparts Selvskab ApS');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (80, '8003', 'Personlig ejet selvskab');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (80, '8004', 'Iværksætterselvskab IVS');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (80, '8005', 'Interessentskab I/S');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (80, '8006', 'Holdingselskab');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (80, '8007', 'Selskab Med Begrænset Hæftelse SMBA');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (80, '8008', 'Kommanditselskab K/S');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (80, '8009', 'SPE-selskab');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (80, '8010', 'Forening med begrænset ansvar (f.m.b.a.)');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (80, '8011', 'Frivillig forening');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (80, '8012', 'Almindelig forening');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (80, '8013', 'Andelsboligforening');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (80, '8014', 'Særlig forening');


-- Greece
INSERT INTO llx_c_forme_juridique (code,fk_pays,libelle,isvatexempted,active) VALUES ('10201',102,'Ατομική επιχείρηση',0,1);
INSERT INTO llx_c_forme_juridique (code,fk_pays,libelle,isvatexempted,active) VALUES ('10202',102,'Εταιρική  επιχείρηση',0,1);
INSERT INTO llx_c_forme_juridique (code,fk_pays,libelle,isvatexempted,active) VALUES ('10203',102,'Ομόρρυθμη Εταιρεία Ο.Ε',0,1);
INSERT INTO llx_c_forme_juridique (code,fk_pays,libelle,isvatexempted,active) VALUES ('10204',102,'Ετερόρρυθμη Εταιρεία Ε.Ε',0,1);
INSERT INTO llx_c_forme_juridique (code,fk_pays,libelle,isvatexempted,active) VALUES ('10205',102,'Εταιρεία Περιορισμένης Ευθύνης Ε.Π.Ε',0,1);
INSERT INTO llx_c_forme_juridique (code,fk_pays,libelle,isvatexempted,active) VALUES ('10206',102,'Ανώνυμη Εταιρεία Α.Ε',0,1);
INSERT INTO llx_c_forme_juridique (code,fk_pays,libelle,isvatexempted,active) VALUES ('10207',102,'Ανώνυμη ναυτιλιακή εταιρεία Α.Ν.Ε',0,1);
INSERT INTO llx_c_forme_juridique (code,fk_pays,libelle,isvatexempted,active) VALUES ('10208',102,'Συνεταιρισμός',0,1);
INSERT INTO llx_c_forme_juridique (code,fk_pays,libelle,isvatexempted,active) VALUES ('10209',102,'Συμπλοιοκτησία',0,1);

-- Italy
INSERT INTO llx_c_forme_juridique (code,fk_pays,libelle,isvatexempted,active) VALUES ('301',3,'Società semplice',0,1);
INSERT INTO llx_c_forme_juridique (code,fk_pays,libelle,isvatexempted,active) VALUES ('302',3,'Società in nome collettivo s.n.c.',0,1);
INSERT INTO llx_c_forme_juridique (code,fk_pays,libelle,isvatexempted,active) VALUES ('303',3,'Società in accomandita semplice s.a.s.',0,1);
INSERT INTO llx_c_forme_juridique (code,fk_pays,libelle,isvatexempted,active) VALUES ('304',3,'Società per azioni s.p.a.',0,1);
INSERT INTO llx_c_forme_juridique (code,fk_pays,libelle,isvatexempted,active) VALUES ('305',3,'Società a responsabilità limitata s.r.l.',0,1);
INSERT INTO llx_c_forme_juridique (code,fk_pays,libelle,isvatexempted,active) VALUES ('306',3,'Società in accomandita per azioni s.a.p.a.',0,1);
INSERT INTO llx_c_forme_juridique (code,fk_pays,libelle,isvatexempted,active) VALUES ('307',3,'Società cooperativa a r.l.',0,1);
INSERT INTO llx_c_forme_juridique (code,fk_pays,libelle,isvatexempted,active) VALUES ('308',3,'Società consortile',0,1);
INSERT INTO llx_c_forme_juridique (code,fk_pays,libelle,isvatexempted,active) VALUES ('309',3,'Società europea',0,1);
INSERT INTO llx_c_forme_juridique (code,fk_pays,libelle,isvatexempted,active) VALUES ('310',3,'Società cooperativa europea',0,1);
INSERT INTO llx_c_forme_juridique (code,fk_pays,libelle,isvatexempted,active) VALUES ('311',3,'Società unipersonale',0,1);
INSERT INTO llx_c_forme_juridique (code,fk_pays,libelle,isvatexempted,active) VALUES ('312',3,'Società di professionisti',0,1);
INSERT INTO llx_c_forme_juridique (code,fk_pays,libelle,isvatexempted,active) VALUES ('313',3,'Società di fatto',0,1);
--INSERT INTO llx_c_forme_juridique (code,fk_pays,libelle,isvatexempted,active) VALUES ('314',3,'Società occulta',0,1);
INSERT INTO llx_c_forme_juridique (code,fk_pays,libelle,isvatexempted,active) VALUES ('315',3,'Società apparente',0,1);
INSERT INTO llx_c_forme_juridique (code,fk_pays,libelle,isvatexempted,active) VALUES ('316',3,'Impresa individuale ',0,1);
INSERT INTO llx_c_forme_juridique (code,fk_pays,libelle,isvatexempted,active) VALUES ('317',3,'Impresa coniugale',0,1);
INSERT INTO llx_c_forme_juridique (code,fk_pays,libelle,isvatexempted,active) VALUES ('318',3,'Impresa familiare',0,1);
INSERT INTO llx_c_forme_juridique (code,fk_pays,libelle,isvatexempted,active) VALUES ('319',3,'Consorzio cooperativo',0,1);
INSERT INTO llx_c_forme_juridique (code,fk_pays,libelle,isvatexempted,active) VALUES ('320',3,'Società cooperativa sociale',0,1);
INSERT INTO llx_c_forme_juridique (code,fk_pays,libelle,isvatexempted,active) VALUES ('321',3,'Società cooperativa di consumo',0,1);
INSERT INTO llx_c_forme_juridique (code,fk_pays,libelle,isvatexempted,active) VALUES ('322',3,'Società cooperativa agricola',0,1);
INSERT INTO llx_c_forme_juridique (code,fk_pays,libelle,isvatexempted,active) VALUES ('323',3,'A.T.I. Associazione temporanea di imprese',0,1);
INSERT INTO llx_c_forme_juridique (code,fk_pays,libelle,isvatexempted,active) VALUES ('324',3,'R.T.I. Raggruppamento temporaneo di imprese',0,1);
INSERT INTO llx_c_forme_juridique (code,fk_pays,libelle,isvatexempted,active) VALUES ('325',3,'Studio associato',0,1);

-- Switzerland
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (6, '600', 'Raison Individuelle');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (6, '601', 'Société Simple');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (6, '602', 'Société en nom collectif');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (6, '603', 'Société en commandite');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (6, '604', 'Société anonyme (SA)');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (6, '605', 'Société en commandite par actions');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (6, '606', 'Société à responsabilité limitée (SARL)');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (6, '607', 'Société coopérative');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (6, '608', 'Association');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (6, '609', 'Fondation');

-- United Kingdom
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (7, '700', 'Sole Trader');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (7, '701', 'Partnership');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (7, '702', 'Private Limited Company by shares (LTD)');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (7, '703', 'Public Limited Company');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (7, '704', 'Workers Cooperative');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (7, '705', 'Limited Liability Partnership');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (7, '706', 'Franchise');

-- Tunisia (Formes les plus utilisées)
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (10, '1000','Société à responsabilité limitée (SARL)');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (10, '1001','Société en Nom Collectif (SNC)');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (10, '1002','Société en Commandite Simple (SCS)');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (10, '1003','société en participation');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (10, '1004','Société Anonyme (SA)');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (10, '1005','Société Unipersonnelle à Responsabilité Limitée (SUARL)');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (10, '1006','Groupement d''intérêt économique (GEI)');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (10, '1007','Groupe de sociétés');

-- The Netherlands
INSERT INTO llx_c_forme_juridique (fk_pays,code,libelle,isvatexempted,active,module) VALUES (17,1701,'Eenmanszaak',0,1,NULL);
INSERT INTO llx_c_forme_juridique (fk_pays,code,libelle,isvatexempted,active,module) VALUES (17,1702,'Maatschap',0,1,NULL);
INSERT INTO llx_c_forme_juridique (fk_pays,code,libelle,isvatexempted,active,module) VALUES (17,1703,'Vennootschap onder firma',0,1,NULL);
INSERT INTO llx_c_forme_juridique (fk_pays,code,libelle,isvatexempted,active,module) VALUES (17,1704,'Commanditaire vennootschap',0,1,NULL);
INSERT INTO llx_c_forme_juridique (fk_pays,code,libelle,isvatexempted,active,module) VALUES (17,1705,'Besloten vennootschap (BV)',0,1,NULL);
INSERT INTO llx_c_forme_juridique (fk_pays,code,libelle,isvatexempted,active,module) VALUES (17,1706,'Naamloze Vennootschap (NV)',0,1,NULL);
INSERT INTO llx_c_forme_juridique (fk_pays,code,libelle,isvatexempted,active,module) VALUES (17,1707,'Vereniging',0,1,NULL);
INSERT INTO llx_c_forme_juridique (fk_pays,code,libelle,isvatexempted,active,module) VALUES (17,1708,'Stichting',0,1,NULL);
INSERT INTO llx_c_forme_juridique (fk_pays,code,libelle,isvatexempted,active,module) VALUES (17,1709,'Coöperatie met beperkte aansprakelijkheid (BA)',0,1,NULL);
INSERT INTO llx_c_forme_juridique (fk_pays,code,libelle,isvatexempted,active,module) VALUES (17,1710,'Coöperatie met uitgesloten aansprakelijkheid (UA)',0,1,NULL);
INSERT INTO llx_c_forme_juridique (fk_pays,code,libelle,isvatexempted,active,module) VALUES (17,1711,'Coöperatie met wettelijke aansprakelijkheid (WA)',0,1,NULL);
INSERT INTO llx_c_forme_juridique (fk_pays,code,libelle,isvatexempted,active,module) VALUES (17,1712,'Onderlinge waarborgmaatschappij',0,1,NULL);

-- Spain
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (4, '401', 'Empresario Individual', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (4, '402', 'Comunidad de Bienes', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (4, '403', 'Sociedad Civil', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (4, '404', 'Sociedad Colectiva', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (4, '405', 'Sociedad Limitada', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (4, '406', 'Sociedad Anónima', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (4, '407', 'Sociedad Comanditaria por Acciones', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (4, '408', 'Sociedad Comanditaria Simple', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (4, '409', 'Sociedad Laboral', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (4, '410', 'Sociedad Cooperativa', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (4, '411', 'Sociedad de Garantía Recíproca', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (4, '412', 'Entidad de Capital-Riesgo', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (4, '413', 'Agrupación de Interés Económico', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (4, '414', 'Sociedad de Inversión Mobiliaria', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (4, '415', 'Agrupación sin Ánimo de Lucro', 1);

-- Mauritius
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (152, '15201', 'Mauritius Private Company Limited By Shares', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (152, '15202', 'Mauritius Company Limited By Guarantee', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (152, '15203', 'Mauritius Public Company Limited By Shares', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (152, '15204', 'Mauritius Foreign Company', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (152, '15205', 'Mauritius GBC1 (Offshore Company)', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (152, '15206', 'Mauritius GBC2 (International Company)', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (152, '15207', 'Mauritius General Partnership', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (152, '15208', 'Mauritius Limited Partnership', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (152, '15209', 'Mauritius Sole Proprietorship', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (152, '15210', 'Mauritius Trusts', 1);

-- Mexique
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (154, '15401', '601 - General de Ley Personas Morales', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (154, '15402', '603 - Personas Morales con Fines no Lucrativos', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (154, '15403', '605 - Sueldos y Salarios e Ingresos Asimilados a Salarios', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (154, '15404', '606 - Arrendamiento', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (154, '15405', '607 - Régimen de Enajenación o Adquisición de Bienes', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (154, '15406', '608 - Demás ingresos', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (154, '15407', '610 - Residentes en el Extranjero sin Establecimiento Permanente en México', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (154, '15408', '611 - Ingresos por Dividendos (socios y accionistas)', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (154, '15409', '612 - Personas Físicas con Actividades Empresariales y Profesionales', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (154, '15410', '614 - Ingresos por intereses', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (154, '15411', '615 - Régimen de los ingresos por obtención de premios', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (154, '15412', '616 - Sin obligaciones fiscales', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (154, '15413', '620 - Sociedades Cooperativas de Producción que optan por diferir sus ingresos', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (154, '15414', '621 - Incorporación Fiscal', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (154, '15415', '622 - Actividades Agrícolas, Ganaderas, Silvícolas y Pesqueras', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (154, '15416', '623 - Opcional para Grupos de Sociedades', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (154, '15417', '624 - Coordinados', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (154, '15418', '625 - Régimen de las Actividades Empresariales con ingresos a través de Plataformas Tecnológicas', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (154, '15419', '626 - Régimen Simplificado de Confianza', 1);

-- Luxembourg
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (140, '14001', 'Entreprise individuelle', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (140, '14002', 'Société en nom collectif (SENC)', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (140, '14003', 'Société en commandite simple (SECS)', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (140, '14004', 'Société en commandite par actions (SECA)', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (140, '14005', 'Société à responsabilité limitée (SARL)', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (140, '14006', 'Société anonyme (SA)', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (140, '14007', 'Société coopérative (SC)', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (140, '14008', 'Société européenne (SE)', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (140, '14009', 'Société à responsabilité limitée simplifiée (SARL-S)', 1);

-- Romania
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (188, '18801', 'AFJ - Alte forme juridice', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (188, '18802', 'ASF - Asociatie familialã', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (188, '18803', 'CON - Concesiune', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (188, '18804', 'CRL - Soc civilã profesionala cu pers. juridica si rãspundere limitata (SPRL)', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (188, '18805', 'INC - Închiriere', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (188, '18806', 'LOC - Locaţie de gestiune', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (188, '18807', 'OC1 - Organizaţie cooperatistã meşteşugãreascã', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (188, '18808', 'OC2 - Organizaţie cooperatistã de consum', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (188, '18809', 'OC3 - Organizaţie cooperatistã de credit', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (188, '18810', 'PFA - Persoanã fizicã independentã', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (188, '18811', 'RA - Regie autonomã', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (188, '18812', 'SA - Societate comercialã pe acţiuni', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (188, '18813', 'SCS - Societate comercialã în comanditã simplã', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (188, '18814', 'SNC - Societate comercialã în nume colectiv', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (188, '18815', 'SPI - Societate profesionala practicieni in insolventa (SPPI)', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (188, '18816', 'SRL - Societate comercialã cu rãspundere limitatã', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (188, '18817', 'URL - Intreprindere profesionala unipersonala cu rãspundere limitata (IPURL)', 1);

-- Panama
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (178, '17801', 'Empresa individual', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (178, '17802', 'Asociación General', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (178, '17803', 'Sociedad de Responsabilidad Limitada', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (178, '17804', 'Sociedad Civil', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (178, '17805', 'Sociedad Anónima', 1);

-- Algeria (id country=13)
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (13, '1300','Personne physique');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (13, '1301','Société à responsabilité limitée (SARL)');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (13, '1302','Entreprise unipersonnelle à responsabilité limitée (EURL)');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (13, '1303','Société en Nom Collectif (SNC)');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (13, '1304','société par actions (SPA)');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (13, '1305','Société en Commandite Simple (SCS)');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (13, '1306','Société en commandite par actions (SCA)');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (13, '1307','Société en participation');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (13, '1308','Groupe de sociétés');

-- Sweden (id country=20)
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (20, '2001', 'Aktiebolag');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (20, '2002', 'Publikt aktiebolag (AB publ)');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (20, '2003', 'Ekonomisk förening (ek. för.)');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (20, '2004', 'Bostadsrättsförening (BRF)');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (20, '2005', 'Hyresrättsförening (HRF)');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (20, '2006', 'Kooperativ');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (20, '2007', 'Enskild firma (EF)');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (20, '2008', 'Handelsbolag (HB)');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (20, '2009', 'Kommanditbolag (KB)');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (20, '2010', 'Enkelt bolag');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (20, '2011', 'Ideell förening');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (20, '2012', 'Stiftelse');

-- Burundi (id contry=61)
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (61,'6100','Indépendant - Personne physique');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (61,'6101','Société Unipersonnelle');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (61,'6102','Société de personne à responsabilité limité (SPRL)');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (61,'6103','Société anonyme (SA)');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (61,'6104','Société coopérative');

-- Croatia (id country=76)
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (76, '7601', 'Društvo s ograničenom odgovornošću (d.o.o.)');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (76, '7602', 'Jednostavno društvo s ograničenom odgovornošću (j.d.o.o.)');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (76, '7603', 'Dioničko društvo (d.d.)');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (76, '7604', 'Obrt');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (76, '7605', 'Javno trgovačko društvo (j.t.d.)');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (76, '7606', 'Komanditno društvo (k.d.)');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (76, '7607', 'Gospodarsko interesno udruženje (GIU)');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (76, '7608', 'Predstavništvo');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (76, '7609', 'Državno tijelo');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (76, '7610', 'Kućna radinost');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (76, '7611', 'Sporedno zanimanje');

-- Japan (id country=123)
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (123, '12301', '株式会社');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (123, '12302', '有限会社');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (123, '12303', '合資会社');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (123, '12304', '合名会社');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (123, '12305', '相互会社');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (123, '12306', '医療法人');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (123, '12307', '財団法人');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (123, '12308', '社団法人');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (123, '12309', '社会福祉法人');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (123, '12310', '学校法人');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (123, '12311', '特定非営利活動法人');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (123, '12312', 'ＮＰＯ法人');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (123, '12313', '商工組合');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (123, '12314', '林業組合');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (123, '12315', '同業組合');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (123, '12316', '農業協同組合');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (123, '12317', '漁業協同組合');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (123, '12318', '農事組合法人');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (123, '12319', '生活互助会');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (123, '12320', '協業組合');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (123, '12321', '協同組合');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (123, '12322', '生活協同組合');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (123, '12323', '連合会');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (123, '12324', '組合連合会');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (123, '12325', '協同組合連合会');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (123, '12329', '一般社団法人');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (123, '12330', '公益社団法人');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (123, '12331', '一般財団法人');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (123, '12332', '公益財団法人');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (123, '12333', '合同会社');
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle) VALUES (123, '12399', '個人又はその他の法人');
