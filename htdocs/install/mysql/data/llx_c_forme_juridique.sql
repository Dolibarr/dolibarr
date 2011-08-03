-- Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
-- Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
-- Copyright (C) 2004      Guillaume Delecourt  <guillaume.delecourt@opensides.be>
-- Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
-- Copyright (C) 2007 	   Patrick Raguin       <patrick.raguin@gmail.com>
-- Copyright (C) 2010-2011 Juanjo Menent        <jmenent@2byte.es>
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
-- $Id: llx_c_forme_juridique.sql,v 1.6 2011/08/03 01:25:45 eldy Exp $
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
                                                                     
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'41','Établissement public ou régie à caractère industriel ou commercial');
                                                                     
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'51','Société coopérative commerciale particulière');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'52','Société en nom collectif');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'53','Société en commandite');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'54','Société à responsabilité limitée (SARL)');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'55','Société anonyme à conseil d administration');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'56','Société anonyme à directoire');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'57','Société par actions simplifiée');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'58','Entreprise Unipersonnelle à Responsabilité Limitée (EURL)');
                                                                     
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'61','Caisse d''épargne et de prévoyance');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'62','Groupement d''intérêt économique (GIE)');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'63','Société coopérative agricole');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'64','Société non commerciale d assurances');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'65','Société civile');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'69','Personnes de droit privé inscrites au RCS');
                                                                     
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'71','Administration de l état');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'72','Collectivité territoriale');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (1,'73','Établissement public administratif');
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

-- Germany
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (5, '500', 'Limited liability corporation (GmbH)');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (5, '501', 'Stock corporation (AG)');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (5, '502', 'Partnerships general or limited (GmbH & CO. KG)');
insert into llx_c_forme_juridique (fk_pays, code, libelle) values (5, '503', 'Sole proprietor / Private business');

-- Italy
INSERT INTO llx_c_forme_juridique (code,fk_pays,libelle,isvatexempted,active) VALUES ('301',3,'Società semplice',0,1);
INSERT INTO llx_c_forme_juridique (code,fk_pays,libelle,isvatexempted,active) VALUES ('302',3,'Società in nome collettivo s.n.c.',0,1);
INSERT INTO llx_c_forme_juridique (code,fk_pays,libelle,isvatexempted,active) VALUES ('303',3,'Società in accomandita semplice s.a.s.',0,1);
INSERT INTO llx_c_forme_juridique (code,fk_pays,libelle,isvatexempted,active) VALUES ('304',3,'Società per azioni s.p.a.',0,1);
INSERT INTO llx_c_forme_juridique (code,fk_pays,libelle,isvatexempted,active) VALUES ('305',3,'Società a responsabilità limitata s.r.l.',0,1);
INSERT INTO llx_c_forme_juridique (code,fk_pays,libelle,isvatexempted,active) VALUES ('306',3,'Società in accomandita per azioni s.a.p.a.',0,1);
INSERT INTO llx_c_forme_juridique (code,fk_pays,libelle,isvatexempted,active) VALUES ('307',3,'Società cooperativa',0,1);
INSERT INTO llx_c_forme_juridique (code,fk_pays,libelle,isvatexempted,active) VALUES ('308',3,'Società consortile',0,1);
INSERT INTO llx_c_forme_juridique (code,fk_pays,libelle,isvatexempted,active) VALUES ('309',3,'Società europea',0,1);
INSERT INTO llx_c_forme_juridique (code,fk_pays,libelle,isvatexempted,active) VALUES ('310',3,'Società cooperativa europea',0,1);
INSERT INTO llx_c_forme_juridique (code,fk_pays,libelle,isvatexempted,active) VALUES ('311',3,'Società unipersonale',0,1);
INSERT INTO llx_c_forme_juridique (code,fk_pays,libelle,isvatexempted,active) VALUES ('312',3,'Società di professionisti',0,1);
INSERT INTO llx_c_forme_juridique (code,fk_pays,libelle,isvatexempted,active) VALUES ('313',3,'Società di fatto',0,1);
INSERT INTO llx_c_forme_juridique (code,fk_pays,libelle,isvatexempted,active) VALUES ('314',3,'Società occulta',0,1);
INSERT INTO llx_c_forme_juridique (code,fk_pays,libelle,isvatexempted,active) VALUES ('315',3,'Società apparente',0,1);
INSERT INTO llx_c_forme_juridique (code,fk_pays,libelle,isvatexempted,active) VALUES ('316',3,'Impresa individuale ',0,1);
INSERT INTO llx_c_forme_juridique (code,fk_pays,libelle,isvatexempted,active) VALUES ('317',3,'Impresa coniugale',0,1);
INSERT INTO llx_c_forme_juridique (code,fk_pays,libelle,isvatexempted,active) VALUES ('318',3,'Impresa familiare',0,1);

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

-- Spain
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (4, '401', 'Empresario Individual', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (4, '402', 'Comunidad de Bienes', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (4, '403', 'Sociedad Civil', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (4, '404', 'Sociedad Colectiva', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (4, '405', 'Sociedad Limitada', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (4, '406', 'Sociedad Anónima', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (4, '407', 'Sociedad Comandataria por Acciones', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (4, '408', 'Sociedad Comandataria Simple', 1);
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