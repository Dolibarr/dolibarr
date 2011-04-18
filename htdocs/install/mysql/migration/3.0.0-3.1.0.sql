--
-- $Id$
--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 2.8.0 or higher. 
--
-- To add a column:         ALTER TABLE llx_table ADD COLUMN newcol varchar(60) NOT NULL DEFAULT '0' AFTER existingcol;
-- To rename a column:      ALTER TABLE llx_table CHANGE COLUMN oldname newname varchar(60);
-- To change type of field: ALTER TABLE llx_table MODIFY name varchar(60);
--

ALTER TABLE llx_adherent MODIFY login varchar(50);

ALTER TABLE llx_c_actioncomm ADD COLUMN position integer NOT NULL DEFAULT 0;

ALTER TABLE llx_commande_fournisseur MODIFY model_pdf varchar(255);
ALTER TABLE llx_commande MODIFY model_pdf varchar(255);
ALTER TABLE llx_don MODIFY model_pdf varchar(255);
ALTER TABLE llx_expedition MODIFY model_pdf varchar(255);
ALTER TABLE llx_facture_fourn MODIFY model_pdf varchar(255);
ALTER TABLE llx_facture MODIFY model_pdf varchar(255);
ALTER TABLE llx_fichinter MODIFY model_pdf varchar(255);
ALTER TABLE llx_livraison MODIFY model_pdf varchar(255);
ALTER TABLE llx_projet MODIFY model_pdf varchar(255);
ALTER TABLE llx_propal MODIFY model_pdf varchar(255);


-- Delete old constants
DELETE FROM llx_const WHERE __DECRYPT('name')__ = 'MAIN_MENU_BARRETOP';
DELETE FROM llx_const WHERE __DECRYPT('name')__ = 'MAIN_MENUFRONT_BARRETOP';
DELETE FROM llx_const WHERE __DECRYPT('name')__ = 'MAIN_MENU_BARRELEFT';
DELETE FROM llx_const WHERE __DECRYPT('name')__ = 'MAIN_MENUFRONT_BARRELEFT';

ALTER TABLE llx_facture_fourn ADD COLUMN ref_ext varchar(30) AFTER entity;
ALTER TABLE llx_commande_fournisseur ADD COLUMN ref_ext varchar(30) AFTER entity;


ALTER TABLE llx_facturedet DROP INDEX uk_fk_remise_except;
ALTER TABLE llx_facturedet ADD UNIQUE INDEX uk_fk_remise_except (fk_remise_except, fk_facture);

ALTER TABLE llx_societe ADD COLUMN fk_currency integer DEFAULT 0 AFTER fk_forme_juridique;

ALTER TABLE llx_societe_remise MODIFY remise_client double(6,3) DEFAULT 0 NOT NULL;

ALTER TABLE llx_menu ADD COLUMN fk_mainmenu   varchar(16) after fk_menu;
ALTER TABLE llx_menu ADD COLUMN fk_leftmenu   varchar(16) after fk_menu;
ALTER TABLE llx_menu MODIFY leftmenu varchar(100) NULL;


CREATE TABLE llx_c_availability
(
	rowid		integer	 	AUTO_INCREMENT PRIMARY KEY,
	code		varchar(30) NOT NULL,
	label		varchar(60) NOT NULL,
	active		tinyint 	DEFAULT 1  NOT NULL

)ENGINE=innodb;

CREATE TABLE llx_c_source
(
	rowid		integer	 	AUTO_INCREMENT PRIMARY KEY,
	code		varchar(30) NOT NULL,
	label		varchar(60) NOT NULL,
	active		tinyint 	DEFAULT 1  NOT NULL

)ENGINE=innodb;

ALTER TABLE llx_propal CHANGE COLUMN delivery fk_availability integer NULL;
ALTER TABLE llx_propal ADD COLUMN fk_availability integer NULL AFTER fk_adresse_livraison;
ALTER TABLE llx_commande ADD COLUMN fk_availability integer NULL;

INSERT INTO llx_c_availability (rowid,code,label,active) VALUES (1, 'AV_NOW', 'Immediate', 1);
INSERT INTO llx_c_availability (rowid,code,label,active) VALUES (2, 'AV_1W',  '1 week', 1);
INSERT INTO llx_c_availability (rowid,code,label,active) VALUES (3, 'AV_2W',  '2 weeks', 1);
INSERT INTO llx_c_availability (rowid,code,label,active) VALUES (4, 'AV_3W',  '3 weeks', 1);

INSERT INTO llx_c_source (rowid,code,label,active) VALUES (1, 'SRC_00', 'Proposition commerciale', 1);
INSERT INTO llx_c_source (rowid,code,label,active) VALUES (2, 'SRC_01',  'Internet', 1);
INSERT INTO llx_c_source (rowid,code,label,active) VALUES (3, 'SRC_02',  'Campagne courrier', 1);
INSERT INTO llx_c_source (rowid,code,label,active) VALUES (4, 'SRC_03',  'Campagne téléphone', 1);
INSERT INTO llx_c_source (rowid,code,label,active) VALUES (5, 'SRC_04', 'Campagne fax', 1);
INSERT INTO llx_c_source (rowid,code,label,active) VALUES (6, 'SRC_05', 'Commercial', 1);
INSERT INTO llx_c_source (rowid,code,label,active) VALUES (7, 'SRC_06', 'Magasin', 1);

ALTER TABLE llx_actioncomm ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER id;

--Add Chile data
-- Regions Chile (id pays=67)
INSERT INTO llx_c_regions (rowid, code_region, fk_pays, cheflieu, tncc, nom, active) VALUES (6701, 6701, 67, NULL, NULL, 'Tarapacá', 1);
INSERT INTO llx_c_regions (rowid, code_region, fk_pays, cheflieu, tncc, nom, active) VALUES (6702, 6702, 67, NULL, NULL, 'Antofagasta', 1);
INSERT INTO llx_c_regions (rowid, code_region, fk_pays, cheflieu, tncc, nom, active) VALUES (6703, 6703, 67, NULL, NULL, 'Atacama', 1);
INSERT INTO llx_c_regions (rowid, code_region, fk_pays, cheflieu, tncc, nom, active) VALUES (6704, 6704, 67, NULL, NULL, 'Coquimbo', 1);
INSERT INTO llx_c_regions (rowid, code_region, fk_pays, cheflieu, tncc, nom, active) VALUES (6705, 6705, 67, NULL, NULL, 'Valparaíso', 1);
INSERT INTO llx_c_regions (rowid, code_region, fk_pays, cheflieu, tncc, nom, active) VALUES (6706, 6706, 67, NULL, NULL, 'General Bernardo O Higgins', 1);
INSERT INTO llx_c_regions (rowid, code_region, fk_pays, cheflieu, tncc, nom, active) VALUES (6707, 6707, 67, NULL, NULL, 'Maule', 1);
INSERT INTO llx_c_regions (rowid, code_region, fk_pays, cheflieu, tncc, nom, active) VALUES (6708, 6708, 67, NULL, NULL, 'Biobío', 1);
INSERT INTO llx_c_regions (rowid, code_region, fk_pays, cheflieu, tncc, nom, active) VALUES (6709, 6709, 67, NULL, NULL, 'Raucanía', 1);
INSERT INTO llx_c_regions (rowid, code_region, fk_pays, cheflieu, tncc, nom, active) VALUES (6710, 6710, 67, NULL, NULL, 'Los Lagos', 1);
INSERT INTO llx_c_regions (rowid, code_region, fk_pays, cheflieu, tncc, nom, active) VALUES (6711, 6711, 67, NULL, NULL, 'Aysén General Carlos Ibáñez del Campo', 1);
INSERT INTO llx_c_regions (rowid, code_region, fk_pays, cheflieu, tncc, nom, active) VALUES (6712, 6712, 67, NULL, NULL, 'Magallanes y Antártica Chilena', 1);
INSERT INTO llx_c_regions (rowid, code_region, fk_pays, cheflieu, tncc, nom, active) VALUES (6713, 6713, 67, NULL, NULL, 'Santiago', 1);
INSERT INTO llx_c_regions (rowid, code_region, fk_pays, cheflieu, tncc, nom, active) VALUES (6714, 6714, 67, NULL, NULL, 'Los Ríos', 1);
INSERT INTO llx_c_regions (rowid, code_region, fk_pays, cheflieu, tncc, nom, active) VALUES (6715, 6715, 67, NULL, NULL, 'Arica y Parinacota', 1);
-- Provinces chile (id=67)
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('151', 6715, '', 0, '151', 'Arica', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('152', 6715, '', 0, '152', 'Parinacota', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('011', 6701, '', 0, '011', 'Iquique', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('014', 6701, '', 0, '014', 'Tamarugal', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('021', 6702, '', 0, '021', 'Antofagasa', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('022', 6702, '', 0, '022', 'El Loa', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('023', 6702, '', 0, '023', 'Tocopilla', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('031', 6703, '', 0, '031', 'Copiapó', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('032', 6703, '', 0, '032', 'Chañaral', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('033', 6703, '', 0, '033', 'Huasco', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('041', 6704, '', 0, '041', 'Elqui', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('042', 6704, '', 0, '042', 'Choapa', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('043', 6704, '', 0, '043', 'Limarí', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('051', 6705, '', 0, '051', 'Valparaíso', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('052', 6705, '', 0, '052', 'Isla de Pascua', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('053', 6705, '', 0, '053', 'Los Andes', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('054', 6705, '', 0, '054', 'Petorca', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('055', 6705, '', 0, '055', 'Quillota', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('056', 6705, '', 0, '056', 'San Antonio', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('057', 6705, '', 0, '057', 'San Felipe de Aconcagua', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('058', 6705, '', 0, '058', 'Marga Marga', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('061', 6706, '', 0, '061', 'Cachapoal', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('062', 6706, '', 0, '062', 'Cardenal Caro', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('063', 6706, '', 0, '063', 'Colchagua', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('071', 6707, '', 0, '071', 'Talca', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('072', 6707, '', 0, '072', 'Cauquenes', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('073', 6707, '', 0, '073', 'Curicó', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('074', 6707, '', 0, '074', 'Linares', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('081', 6708, '', 0, '081', 'Concepción', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('082', 6708, '', 0, '082', 'Arauco', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('083', 6708, '', 0, '083', 'Biobío', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('084', 6708, '', 0, '084', 'Ñuble', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('091', 6709, '', 0, '091', 'Cautín', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('092', 6709, '', 0, '092', 'Malleco', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('141', 6714, '', 0, '141', 'Valdivia', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('142', 6714, '', 0, '142', 'Ranco', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('101', 6710, '', 0, '101', 'Llanquihue', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('102', 6710, '', 0, '102', 'Chiloé', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('103', 6710, '', 0, '103', 'Osorno', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('104', 6710, '', 0, '104', 'Palena', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('111', 6711, '', 0, '111', 'Coihaique', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('112', 6711, '', 0, '112', 'Aisén', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('113', 6711, '', 0, '113', 'Capitán Prat', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('114', 6711, '', 0, '114', 'General Carrera', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('121', 6712, '', 0, '121', 'Magallanes', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('122', 6712, '', 0, '122', 'Antártica Chilena', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('123', 6712, '', 0, '123', 'Tierra del Fuego', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('124', 6712, '', 0, '124', 'Última Esperanza', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('131', 6713, '', 0, '131', 'Santiago', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('132', 6713, '', 0, '132', 'Cordillera', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('133', 6713, '', 0, '133', 'Chacabuco', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('134', 6713, '', 0, '134', 'Maipo', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('135', 6713, '', 0, '135', 'Melipilla', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('136', 6713, '', 0, '136', 'Talagante', 1);

