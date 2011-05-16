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

RENAME TABLE llx_c_methode_commande_fournisseur TO llx_c_input_method;

ALTER TABLE llx_adherent MODIFY login varchar(50);

ALTER TABLE llx_c_actioncomm ADD COLUMN position integer NOT NULL DEFAULT 0;
ALTER TABLE llx_propal ADD COLUMN fk_demand_reason integer NULL DEFAULT 0;
ALTER TABLE llx_commande_fournisseur ADD COLUMN fk_cond_reglement integer NULL DEFAULT 0;
ALTER TABLE llx_commande_fournisseur ADD COLUMN fk_mode_reglement integer NULL DEFAULT 0;

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

ALTER TABLE llx_societe MODIFY siren varchar(32);
ALTER TABLE llx_societe MODIFY siret varchar(32);
ALTER TABLE llx_societe MODIFY ape varchar(32);
ALTER TABLE llx_societe MODIFY idprof4 varchar(32);

-- Delete old constants
DELETE FROM llx_const WHERE __DECRYPT('name')__ = 'MAIN_MENU_BARRETOP';
DELETE FROM llx_const WHERE __DECRYPT('name')__ = 'MAIN_MENUFRONT_BARRETOP';
DELETE FROM llx_const WHERE __DECRYPT('name')__ = 'MAIN_MENU_BARRELEFT';
DELETE FROM llx_const WHERE __DECRYPT('name')__ = 'MAIN_MENUFRONT_BARRELEFT';

ALTER TABLE llx_facture_fourn ADD COLUMN ref_ext varchar(30) AFTER entity;
ALTER TABLE llx_commande_fournisseur ADD COLUMN ref_ext varchar(30) AFTER entity;
ALTER TABLE llx_commande ADD COLUMN fk_demand_reason int(11) AFTER fk_availability;


ALTER TABLE llx_facturedet DROP INDEX uk_fk_remise_except;
ALTER TABLE llx_facturedet ADD UNIQUE INDEX uk_fk_remise_except (fk_remise_except, fk_facture);

ALTER TABLE llx_societe ADD COLUMN fk_currency integer DEFAULT 0 AFTER fk_forme_juridique;

ALTER TABLE llx_societe ADD COLUMN status tinyint DEFAULT 1;

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

-- Use table name input_reason to match also input_method
DROP table llx_c_demand_reason;
CREATE TABLE llx_c_input_reason
(
	rowid		integer	 	AUTO_INCREMENT PRIMARY KEY,
	code		varchar(30) NOT NULL,
	label		varchar(60) NOT NULL,
	active		tinyint 	DEFAULT 1  NOT NULL
)ENGINE=innodb;

INSERT INTO llx_c_input_reason (rowid,code,label,active) VALUES (1, 'SRC_INTE',       'Web site', 1);
INSERT INTO llx_c_input_reason (rowid,code,label,active) VALUES (2, 'SRC_CAMP_MAIL',  'Mailing campaign', 1);
INSERT INTO llx_c_input_reason (rowid,code,label,active) VALUES (3, 'SRC_CAMP_PHO',   'Phone campaign', 1);
INSERT INTO llx_c_input_reason (rowid,code,label,active) VALUES (4, 'SRC_CAMP_FAX',   'Fax campaign', 1);
INSERT INTO llx_c_input_reason (rowid,code,label,active) VALUES (5, 'SRC_COMM',       'Commercial contact', 1);
INSERT INTO llx_c_input_reason (rowid,code,label,active) VALUES (6, 'SRC_SHOP',       'Shop contact', 1);
INSERT INTO llx_c_input_reason (rowid,code,label,active) VALUES (7, 'SRC_CAMP_EMAIL', 'EMailing campaign', 1);

ALTER TABLE llx_propal CHANGE COLUMN delivery fk_availability integer NULL;
ALTER TABLE llx_propal ADD COLUMN fk_availability integer NULL AFTER date_livraison;
ALTER TABLE llx_commande ADD COLUMN fk_availability integer NULL AFTER date_livraison;

INSERT INTO llx_c_availability (rowid,code,label,active) VALUES (1, 'AV_NOW', 'Immediate', 1);
INSERT INTO llx_c_availability (rowid,code,label,active) VALUES (2, 'AV_1W',  '1 week', 1);
INSERT INTO llx_c_availability (rowid,code,label,active) VALUES (3, 'AV_2W',  '2 weeks', 1);
INSERT INTO llx_c_availability (rowid,code,label,active) VALUES (4, 'AV_3W',  '3 weeks', 1);

ALTER TABLE llx_actioncomm ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER id;

ALTER TABLE llx_propaldet ADD INDEX idx_propaldet_fk_product (fk_product);
ALTER TABLE llx_commandedet ADD INDEX idx_commandedet_fk_product (fk_product);
ALTER TABLE llx_facturedet ADD INDEX idx_facturedet_fk_product (fk_product);

ALTER TABLE llx_mailing_cibles ADD COLUMN tag varchar(128) NULL AFTER other;
ALTER TABLE llx_mailing ADD COLUMN tag varchar(128) NULL AFTER email_errorsto;

ALTER TABLE llx_usergroup_user DROP INDEX fk_user;
ALTER TABLE llx_usergroup_user ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER rowid;
ALTER TABLE llx_usergroup_user ADD UNIQUE INDEX uk_usergroup_entity (entity,fk_user,fk_usergroup);
ALTER TABLE llx_usergroup_user ADD CONSTRAINT fk_usergroup_user_fk_user      FOREIGN KEY (fk_user)         REFERENCES llx_user (rowid);
ALTER TABLE llx_usergroup_user ADD CONSTRAINT fk_usergroup_user_fk_usergroup FOREIGN KEY (fk_usergroup)    REFERENCES llx_usergroup (rowid);

--Add Chile data (id pays=67)
-- Regions Chile
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
-- Provinces Chile
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

--Add Mexique data (id pays=154)
-- Regions Mexique
insert into llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) values (15401,  154, 15401, '', 0, 'Mexique', 1);
-- Provinces Mexique
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('DIF', 15401, '', 0, 'DIF', 'Distrito Federal', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('AGS', 15401, '', 0, 'AGS', 'Aguascalientes', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('BCN', 15401, '', 0, 'BCN', 'Baja California Norte', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('BCS', 15401, '', 0, 'BCS', 'Baja California Sur', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('CAM', 15401, '', 0, 'CAM', 'Campeche', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('CHP', 15401, '', 0, 'CHP', 'Chiapas', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('CHI', 15401, '', 0, 'CHI', 'Chihuahua', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('COA', 15401, '', 0, 'COA', 'Coahuila', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('COL', 15401, '', 0, 'COL', 'Colima', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('DUR', 15401, '', 0, 'DUR', 'Durango', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('GTO', 15401, '', 0, 'GTO', 'Guanajuato', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('GRO', 15401, '', 0, 'GRO', 'Guerrero', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('HGO', 15401, '', 0, 'HGO', 'Hidalgo', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('JAL', 15401, '', 0, 'JAL', 'Jalisco', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('MEX', 15401, '', 0, 'MEX', 'México', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('MIC', 15401, '', 0, 'MIC', 'Michoacán de Ocampo', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('MOR', 15401, '', 0, 'MOR', 'Morelos', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('NAY', 15401, '', 0, 'NAY', 'Nayarit', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('NLE', 15401, '', 0, 'NLE', 'Nuevo León', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('OAX', 15401, '', 0, 'OAX', 'Oaxaca', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('PUE', 15401, '', 0, 'PUE', 'Puebla', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('QRO', 15401, '', 0, 'QRO', 'Querétaro', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('HGO', 15401, '', 0, 'HGO', 'Hidalgo', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('ROO', 15401, '', 0, 'ROO', 'Quintana Roo', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('SLP', 15401, '', 0, 'SLP', 'San Luis Potosí', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('SIN', 15401, '', 0, 'SIN', 'Sinaloa', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('SON', 15401, '', 0, 'SON', 'Sonora', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('TAB', 15401, '', 0, 'TAB', 'Tabasco', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('TAM', 15401, '', 0, 'TAM', 'Tamaulipas', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('TLX', 15401, '', 0, 'TLX', 'Tlaxcala', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VER', 15401, '', 0, 'VER', 'Veracruz', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('YUC', 15401, '', 0, 'YUC', 'Yucatán', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('ZAC', 15401, '', 0, 'ZAC', 'Zacatecas', 1);

--Add Colombie data (id pays=70)
-- Regions Colombie 
insert into llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) values (7001,  70, 7001, '', 0, 'Colombie', 1);
-- Provinces Colombie
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('ANT', 7001, '', 0, 'ANT', 'Antioquia', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('BOL', 7001, '', 0, 'BOL', 'Bolívar', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('BOY', 7001, '', 0, 'BOY', 'Boyacá', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('CAL', 7001, '', 0, 'CAL', 'Caldas', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('CAU', 7001, '', 0, 'CAU', 'Cauca', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('CUN', 7001, '', 0, 'CUN', 'Cundinamarca', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('HUI', 7001, '', 0, 'HUI', 'Huila', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('LAG', 7001, '', 0, 'LAG', 'La Guajira', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('MET', 7001, '', 0, 'MET', 'Meta', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('NAR', 7001, '', 0, 'NAR', 'Nariño', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('NDS', 7001, '', 0, 'NDS', 'Norte de Santander', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('SAN', 7001, '', 0, 'SAN', 'Santander', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('SUC', 7001, '', 0, 'SUC', 'Sucre', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('TOL', 7001, '', 0, 'TOL', 'Tolima', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VAC', 7001, '', 0, 'VAC', 'Valle del Cauca', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('RIS', 7001, '', 0, 'RIS', 'Risalda', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('ATL', 7001, '', 0, 'ATL', 'Atlántico', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('COR', 7001, '', 0, 'COR', 'Córdoba', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('SAP', 7001, '', 0, 'SAP', 'San Andrés, Providencia y Santa Catalina', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('ARA', 7001, '', 0, 'ARA', 'Arauca', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('CAS', 7001, '', 0, 'CAS', 'Casanare', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('AMA', 7001, '', 0, 'AMA', 'Amazonas', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('CAQ', 7001, '', 0, 'CAQ', 'Caquetá', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('CHO', 7001, '', 0, 'CHO', 'Chocó', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('GUA', 7001, '', 0, 'GUA', 'Guainía', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('GUV', 7001, '', 0, 'GUV', 'Guaviare', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('PUT', 7001, '', 0, 'PUT', 'Putumayo', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('QUI', 7001, '', 0, 'QUI', 'Quindío', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VAU', 7001, '', 0, 'VAU', 'Vaupés', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('BOG', 7001, '', 0, 'BOG', 'Bogotá', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VID', 7001, '', 0, 'VID', 'Vichada', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('CES', 7001, '', 0, 'CES', 'Cesar', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('MAG', 7001, '', 0, 'MAG', 'Magdalena', 1);

--Add Honduras data (id pays=114)
-- Regions Honduras 
insert into llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) values (11401,  114, 11401, '', 0, 'Honduras', 1);
-- Provinces Honduras
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('AT', 11401, '', 0, 'AT', 'Atlántida', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('CH', 11401, '', 0, 'CH', 'Choluteca', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('CL', 11401, '', 0, 'CL', 'Colón', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('CM', 11401, '', 0, 'CM', 'Comayagua', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('CO', 11401, '', 0, 'CO', 'Copán', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('CR', 11401, '', 0, 'CR', 'Cortés', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('EP', 11401, '', 0, 'EP', 'El Paraíso', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('FM', 11401, '', 0, 'FM', 'Francisco Morazán', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('GD', 11401, '', 0, 'GD', 'Gracias a Dios', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('IN', 11401, '', 0, 'IN', 'Intibucá', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('IB', 11401, '', 0, 'IB', 'Islas de la Bahía', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('LP', 11401, '', 0, 'LP', 'La Paz', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('LM', 11401, '', 0, 'LM', 'Lempira', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('OC', 11401, '', 0, 'OC', 'Ocotepeque', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('OL', 11401, '', 0, 'OL', 'Olancho', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('SB', 11401, '', 0, 'SB', 'Santa Bárbara', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VL', 11401, '', 0, 'VL', 'Valle', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('YO', 11401, '', 0, 'YO', 'Yoro', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('DC', 11401, '', 0, 'DC', 'Distrito Central', 1);
-- Currency Honduras
insert into llx_c_currencies ( code, code_iso, active, label ) values ( 'LH', 'HNL', 1, 'Lempiras');
-- ISV (VAT) Honduras
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (1141,114,     '0','0','No ISV',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (1142,114,     '12','0','ISV 12%',1);
