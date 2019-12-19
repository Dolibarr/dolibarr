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

--
-- Formes juridiques
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

-- France: Extrait de http://www.insee.fr/fr/nom_def_met/nomenclatures/cj/cjniveau2.htm
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'11','Artisan Commerçant (EI)');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'12','Commerçant (EI)');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'13','Artisan (EI)');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'14','Officier public ou ministériel');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'15','Profession libérale (EI)');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'16','Exploitant agricole');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'17','Agent commercial');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'18','Associé Gérant de société');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'19','Personne physique');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'21','Indivision');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'22','Société créée de fait');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'23','Société en participation');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'27','Paroisse hors zone concordataire');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'29','Groupement de droit privé non doté de la personnalité morale');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'31','Personne morale de droit étranger, immatriculée au RCS');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'32','Personne morale de droit étranger, non immatriculée au RCS');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'35','Régime auto-entrepreneur');

insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'41','Etablissement public ou régie à caractère industriel ou commercial');

insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'51','Société coopérative commerciale particulière');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'52','Société en nom collectif');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'53','Société en commandite');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'54','Société à responsabilité limitée (SARL)');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'55','Société anonyme à conseil d administration');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'56','Société anonyme à directoire');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'57','Société par actions simplifiée (SAS)');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'58','Entreprise Unipersonnelle à Responsabilité Limitée (EURL)');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'59','Société par actions simplifiée unipersonnelle (SASU)');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'60','Entreprise Individuelle à Responsabilité Limitée (EIRL)');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'61','Caisse d''épargne et de prévoyance');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'62','Groupement d''intérêt économique (GIE)');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'63','Société coopérative agricole');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'64','Société non commerciale d assurances');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'65','Société civile');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'69','Personnes de droit privé inscrites au RCS');

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
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'99','Personne morale de droit privé');

-- Belgium
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2, '200', 'Indépendant');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (2, '201', 'SPRL - Société à responsabilité limitée');
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
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (154, '15401', 'Sociedad en nombre colectivo', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (154, '15402', 'Sociedad en comandita simple', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (154, '15403', 'Sociedad de responsabilidad limitada', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (154, '15404', 'Sociedad anónima', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (154, '15405', 'Sociedad en comandita por acciones', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (154, '15406', 'Sociedad cooperativa', 1);

-- Luxembourg
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (140, '14001', 'Entreprise individuelle', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (140, '14002', 'Société en nom collectif (SENC)', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (140, '14003', 'Société en commandite simple (SECS)', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (140, '14004', 'Société en commandite par actions (SECA)', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (140, '14005', 'Société à responsabilité limitée (SARL)', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (140, '14006', 'Société anonyme (SA)', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (140, '14007', 'Société coopérative (SC)', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (140, '14008', 'Société européenne (SE)', 1);

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

