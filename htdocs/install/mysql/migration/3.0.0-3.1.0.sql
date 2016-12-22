--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 3.0.0 or higher. 
--
-- To rename a table:       ALTER TABLE llx_table RENAME TO llx_table_new;
-- To add a column:         ALTER TABLE llx_table ADD COLUMN newcol varchar(60) NOT NULL DEFAULT '0' AFTER existingcol;
-- To rename a column:      ALTER TABLE llx_table CHANGE COLUMN oldname newname varchar(60);
-- To change type of field: ALTER TABLE llx_table MODIFY name varchar(60);
--

DROP table llx_prelevement_notifications;

-- Fix corrupted data
update llx_deplacement set dated='2010-01-01' where dated < '2000-01-01';

ALTER TABLE llx_c_methode_commande_fournisseur RENAME TO llx_c_input_method;

ALTER TABLE llx_adherent MODIFY login varchar(50);

ALTER TABLE llx_c_ziptown ADD COLUMN fk_pays integer NOT NULL DEFAULT 0 after fk_county; 
ALTER TABLE llx_c_ziptown MODIFY fk_county integer NULL; 

ALTER TABLE llx_c_actioncomm ADD COLUMN position integer NOT NULL DEFAULT 0;
ALTER TABLE llx_propal ADD COLUMN fk_demand_reason integer NULL DEFAULT 0;
ALTER TABLE llx_commande_fournisseur ADD COLUMN fk_cond_reglement integer NULL DEFAULT 0 after model_pdf;
ALTER TABLE llx_commande_fournisseur ADD COLUMN fk_mode_reglement integer NULL DEFAULT 0 after fk_cond_reglement;
ALTER TABLE llx_commande_fournisseur ADD COLUMN import_key varchar(14);

-- ALTER TABLE llx_c_currencies ADD COLUMN symbole varchar(3) NOT NULL default '';

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

DELETE FROM llx_const WHERE __DECRYPT('name')__ = 'MAIN_POPUP_CALENDAR' and value in ('1','eldy');
DELETE FROM llx_const WHERE __DECRYPT('name')__ = 'MAIN_CONFIRM_AJAX';

ALTER TABLE llx_facture_fourn ADD COLUMN ref_ext varchar(30) AFTER entity;
ALTER TABLE llx_commande_fournisseur ADD COLUMN ref_ext varchar(30) AFTER entity;
ALTER TABLE llx_adherent ADD COLUMN ref_ext varchar(30) after entity;

ALTER TABLE llx_facturedet DROP INDEX uk_fk_remise_except;
ALTER TABLE llx_facturedet ADD UNIQUE INDEX uk_fk_remise_except (fk_remise_except, fk_facture);

ALTER TABLE llx_societe ADD COLUMN fk_currency integer DEFAULT 0 AFTER fk_forme_juridique;
ALTER TABLE llx_societe ADD COLUMN status tinyint DEFAULT 1;
ALTER TABLE llx_societe ADD COLUMN logo varchar(255);

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

ALTER TABLE llx_c_availability ADD UNIQUE INDEX uk_c_availability(code);

-- Use table name input_reason to match also input_method
DROP table llx_c_demand_reason;
CREATE TABLE llx_c_input_reason
(
	rowid		integer	 	AUTO_INCREMENT PRIMARY KEY,
	code		varchar(30) NOT NULL,
	label		varchar(60) NOT NULL,
	active		tinyint 	DEFAULT 1  NOT NULL
)ENGINE=innodb;

ALTER TABLE llx_c_input_reason ADD UNIQUE INDEX uk_c_input_reason(code);

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

ALTER TABLE llx_commande ADD COLUMN fk_demand_reason integer AFTER fk_availability;

ALTER TABLE llx_propaldet ADD INDEX idx_propaldet_fk_product (fk_product);
ALTER TABLE llx_commandedet ADD INDEX idx_commandedet_fk_product (fk_product);
ALTER TABLE llx_facturedet ADD INDEX idx_facturedet_fk_product (fk_product);

ALTER TABLE llx_mailing_cibles ADD COLUMN tag varchar(128) NULL AFTER other;
ALTER TABLE llx_mailing ADD COLUMN tag varchar(128) NULL AFTER email_errorsto;

ALTER TABLE llx_usergroup_user DROP INDEX fk_user;
ALTER TABLE llx_usergroup_user DROP INDEX uk_user_group_entity;
ALTER TABLE llx_usergroup_user ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER rowid;
ALTER TABLE llx_usergroup_user ADD UNIQUE INDEX uk_usergroup_user (entity,fk_user,fk_usergroup);
-- V4.1 DELETE FROM llx_usergroup_user WHERE fk_user NOT IN (SELECT rowid from llx_user);
ALTER TABLE llx_usergroup_user ADD CONSTRAINT fk_usergroup_user_fk_user      FOREIGN KEY (fk_user)         REFERENCES llx_user (rowid);
-- V4.1 DELETE FROM llx_usergroup_user WHERE fk_usergroup NOT IN (SELECT rowid from llx_usergroup);
ALTER TABLE llx_usergroup_user ADD CONSTRAINT fk_usergroup_user_fk_usergroup FOREIGN KEY (fk_usergroup)    REFERENCES llx_usergroup (rowid);

-- V4.1 DELETE FROM llx_product_fournisseur_price_log where fk_product_fournisseur NOT IN (SELECT pf.rowid from llx_product_fournisseur as pf, llx_product as p WHERE pf.fk_product = p.rowid);
-- V4.1 DELETE FROM llx_product_fournisseur_price where fk_product_fournisseur NOT IN (SELECT pf.rowid from llx_product_fournisseur as pf, llx_product as p WHERE pf.fk_product = p.rowid);
-- V4.1 DELETE FROM llx_product_fournisseur where fk_product NOT IN (SELECT rowid from llx_product);
ALTER TABLE llx_product_fournisseur ADD CONSTRAINT fk_product_fournisseur_fk_product FOREIGN KEY (fk_product) REFERENCES llx_product (rowid);

ALTER TABLE llx_commande ADD COLUMN ref_int	varchar(30) AFTER ref_ext;
ALTER TABLE llx_facture ADD COLUMN ref_int varchar(30) AFTER ref_ext;
ALTER TABLE llx_societe ADD COLUMN ref_int varchar(60) AFTER ref_ext;
ALTER TABLE llx_expedition ADD COLUMN ref_ext varchar(30) AFTER fk_soc;
ALTER TABLE llx_expedition ADD COLUMN ref_int varchar(30) AFTER ref_ext;
ALTER TABLE llx_livraison ADD COLUMN ref_ext varchar(30) AFTER fk_soc;
ALTER TABLE llx_livraison ADD COLUMN ref_int varchar(30) AFTER ref_ext;

INSERT INTO llx_c_shipment_mode (rowid,code,libelle,description,active) VALUES (4,'LETTREMAX','Lettre Max','Courrier Suivi et Lettre Max',0);
INSERT INTO llx_c_actioncomm (id, code, type, libelle, module, position) VALUES ( 10, 'AC_SHIP', 'system', 'Send shipping by email'	,'shipping', 11);

ALTER TABLE llx_actioncomm DROP INDEX idx_actioncomm_fk_facture;
ALTER TABLE llx_actioncomm DROP INDEX idx_actioncomm_fk_supplier_order;
ALTER TABLE llx_actioncomm DROP INDEX idx_actioncomm_fk_supplier_invoice;
ALTER TABLE llx_actioncomm ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER id;
ALTER TABLE llx_actioncomm ADD COLUMN fk_element integer DEFAULT NULL AFTER note;
ALTER TABLE llx_actioncomm ADD COLUMN elementtype varchar(16) DEFAULT NULL AFTER fk_element;


ALTER TABLE llx_c_regions MODIFY COLUMN cheflieu    varchar(50);
ALTER TABLE llx_c_departements MODIFY COLUMN cheflieu    varchar(50);


-- Table c_action_trigger
DROP table llx_c_action_trigger;
create table llx_c_action_trigger
(
  rowid			integer AUTO_INCREMENT PRIMARY KEY,
  code			varchar(32)				NOT NULL,
  label			varchar(128)			NOT NULL,
  description	varchar(255),
  elementtype	varchar(16) 			NOT NULL,
  rang			integer		DEFAULT 0
  
)ENGINE=innodb;
ALTER TABLE llx_c_action_trigger ADD UNIQUE INDEX uk_action_trigger_code (code);

INSERT INTO llx_c_action_trigger (rowid,code,label,description,elementtype,rang) VALUES (1,'FICHEINTER_VALIDATE','Validation fiche intervention','Executed when a intervention is validated','ficheinter',18);
INSERT INTO llx_c_action_trigger (rowid,code,label,description,elementtype,rang) VALUES (2,'BILL_VALIDATE','Validation facture client','Executed when a customer invoice is approved','facture',6);
INSERT INTO llx_c_action_trigger (rowid,code,label,description,elementtype,rang) VALUES (3,'ORDER_SUPPLIER_APPROVE','Approbation commande fournisseur','Executed when a supplier order is approved','order_supplier',11);
INSERT INTO llx_c_action_trigger (rowid,code,label,description,elementtype,rang) VALUES (4,'ORDER_SUPPLIER_REFUSE','Refus commande fournisseur','Executed when a supplier order is refused','order_supplier',12);
INSERT INTO llx_c_action_trigger (rowid,code,label,description,elementtype,rang) VALUES (5,'ORDER_VALIDATE','Validation commande client','Executed when a customer order is validated','commande',4);
INSERT INTO llx_c_action_trigger (rowid,code,label,description,elementtype,rang) VALUES (6,'PROPAL_VALIDATE','Validation proposition client','Executed when a commercial proposal is validated','propal',2);
INSERT INTO llx_c_action_trigger (rowid,code,label,description,elementtype,rang) VALUES (7,'WITHDRAW_TRANSMIT','Transmission prélèvement','Executed when a withdrawal is transmited','withdraw',25);
INSERT INTO llx_c_action_trigger (rowid,code,label,description,elementtype,rang) VALUES (8,'WITHDRAW_CREDIT','Créditer prélèvement','Executed when a withdrawal is credited','withdraw',26);
INSERT INTO llx_c_action_trigger (rowid,code,label,description,elementtype,rang) VALUES (9,'WITHDRAW_EMIT','Emission prélèvement','Executed when a withdrawal is emited','withdraw',27);
INSERT INTO llx_c_action_trigger (rowid,code,label,description,elementtype,rang) VALUES (10,'COMPANY_CREATE','Third party created','Executed when a third party is created','societe',1);
INSERT INTO llx_c_action_trigger (rowid,code,label,description,elementtype,rang) VALUES (11,'CONTRACT_VALIDATE','Contract validated','Executed when a contract is validated','contrat',17);
INSERT INTO llx_c_action_trigger (rowid,code,label,description,elementtype,rang) VALUES (12,'PROPAL_SENTBYMAIL','Commercial proposal sent by mail','Executed when a commercial proposal is sent by mail','propal',3);
INSERT INTO llx_c_action_trigger (rowid,code,label,description,elementtype,rang) VALUES (13,'ORDER_SENTBYMAIL','Customer order sent by mail','Executed when a customer order is sent by mail ','commande',5);
INSERT INTO llx_c_action_trigger (rowid,code,label,description,elementtype,rang) VALUES (14,'BILL_PAYED','Customer invoice payed','Executed when a customer invoice is payed','facture',7);
INSERT INTO llx_c_action_trigger (rowid,code,label,description,elementtype,rang) VALUES (15,'BILL_CANCEL','Customer invoice canceled','Executed when a customer invoice is conceled','facture',8);
INSERT INTO llx_c_action_trigger (rowid,code,label,description,elementtype,rang) VALUES (16,'BILL_SENTBYMAIL','Customer invoice sent by mail','Executed when a customer invoice is sent by mail','facture',9);
INSERT INTO llx_c_action_trigger (rowid,code,label,description,elementtype,rang) VALUES (17,'ORDER_SUPPLIER_VALIDATE','Supplier order validated','Executed when a supplier order is validated','order_supplier',10);
INSERT INTO llx_c_action_trigger (rowid,code,label,description,elementtype,rang) VALUES (18,'ORDER_SUPPLIER_SENTBYMAIL','Supplier order sent by mail','Executed when a supplier order is sent by mail','order_supplier',13);
INSERT INTO llx_c_action_trigger (rowid,code,label,description,elementtype,rang) VALUES (19,'BILL_SUPPLIER_VALIDATE','Supplier invoice validated','Executed when a supplier invoice is validated','invoice_supplier',14);
INSERT INTO llx_c_action_trigger (rowid,code,label,description,elementtype,rang) VALUES (20,'BILL_SUPPLIER_PAYED','Supplier invoice payed','Executed when a supplier invoice is payed','invoice_supplier',15);
INSERT INTO llx_c_action_trigger (rowid,code,label,description,elementtype,rang) VALUES (21,'BILL_SUPPLIER_SENTBYMAIL','Supplier invoice sent by mail','Executed when a supplier invoice is sent by mail','invoice_supplier',16);
INSERT INTO llx_c_action_trigger (rowid,code,label,description,elementtype,rang) VALUES (22,'SHIPPING_VALIDATE','Shipping validated','Executed when a shipping is validated','shipping',19);
INSERT INTO llx_c_action_trigger (rowid,code,label,description,elementtype,rang) VALUES (23,'SHIPPING_SENTBYMAIL','Shipping sent by mail','Executed when a shipping is sent by mail','shipping',20);
INSERT INTO llx_c_action_trigger (rowid,code,label,description,elementtype,rang) VALUES (24,'MEMBER_VALIDATE','Member validated','Executed when a member is validated','member',21);
INSERT INTO llx_c_action_trigger (rowid,code,label,description,elementtype,rang) VALUES (25,'MEMBER_SUBSCRIPTION','Member subscribed','Executed when a member is subscribed','member',22);
INSERT INTO llx_c_action_trigger (rowid,code,label,description,elementtype,rang) VALUES (26,'MEMBER_RESILIATE','Member resiliated','Executed when a member is resiliated','member',23);
INSERT INTO llx_c_action_trigger (rowid,code,label,description,elementtype,rang) VALUES (27,'MEMBER_DELETE','Member deleted','Executed when a member is deleted','member',24);

DROP table llx_action_def;

-- Add Chile data (id pays=67)
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

-- Add Mexique data (id pays=154)
-- Regions Mexique
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (15401,  154, 15401, '', 0, 'Mexique', 1);
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
-- Formes juridiques Mexique
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (154, '15401', 'Sociedad en nombre colectivo', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (154, '15402', 'Sociedad en comandita simple', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (154, '15403', 'Sociedad de responsabilidad limitada', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (154, '15404', 'Sociedad anónima', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (154, '15405', 'Sociedad en comandita por acciones', 1);
INSERT INTO llx_c_forme_juridique (fk_pays, code, libelle, active) VALUES (154, '15406', 'Sociedad cooperativa', 1);

-- Add Colombie data (id pays=70)
-- Regions Colombie 
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (7001,  70, 7001, '', 0, 'Colombie', 1);
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

-- Add Honduras data (id pays=114)
-- Regions Honduras 
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (11401,  114, 11401, '', 0, 'Honduras', 1);
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
INSERT INTO llx_c_currencies ( code, code_iso, active, label ) VALUES ( 'LH', 'HNL', 1, 'Lempiras');
-- ISV (VAT) Honduras
INSERT INTO llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) VALUES (1141,114,     '0','0','No ISV',1);
INSERT INTO llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) VALUES (1142,114,     '12','0','ISV 12%',1);
-- Currency Mexique
INSERT INTO llx_c_currencies ( code, code_iso, active, label ) VALUES ( 'MX', 'MXP', 1, 'Pesos Mexicanos');
-- VAT MEXIQUE
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (1541,154,     '0','0','No VAT',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (1542,154,     '16','0','VAT 16%',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (1543,154,     '10','0','VAT Frontero',1);


-- Add Barbados data (id pays=46)
-- Region Barbados 
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (4601,  46, 4601, 'Bridgetown', 0, 'Barbados', 1);
-- Parish Barbados
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('CC', 4601, 'Oistins', 0, 'CC', 'Christ Church', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('SA', 4601, 'Greenland', 0, 'SA', 'Saint Andrew', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('SG', 4601, 'Bulkeley', 0, 'SG', 'Saint George', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('JA', 4601, 'Holetown', 0, 'JA', 'Saint James', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('SJ', 4601, 'Four Roads', 0, 'SJ', 'Saint John', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('SB', 4601, 'Bathsheba', 0, 'SB', 'Saint Joseph', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('SL', 4601, 'Crab Hill', 0, 'SL', 'Saint Lucy', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('SM', 4601, 'Bridgetown', 0, 'SM', 'Saint Michael', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('SP', 4601, 'Speightstown', 0, 'SP', 'Saint Peter', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('SC', 4601, 'Crane', 0, 'SC', 'Saint Philip', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('ST', 4601, 'Hillaby', 0, 'ST', 'Saint Thomas', 1);
-- Currency Barbados
INSERT INTO llx_c_currencies ( code, code_iso, active, label ) VALUES ( 'BD', 'BBD', 1, 'Barbadian or Bajan Dollar');
-- VAT Barbados
INSERT INTO llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) VALUES (461,46,     '0','0','No VAT',1);
INSERT INTO llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) VALUES (462,46,     '15','0','VAT 15%',1);
INSERT INTO llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) VALUES (463,46,     '7.5','0','VAT 7.5%',1);

update llx_actioncomm set elementtype='invoice' where elementtype='facture';
update llx_actioncomm set elementtype='order' where elementtype='commande';
update llx_actioncomm set elementtype='contract' where elementtype='contrat';


alter table llx_propal add column   tms             timestamp after fk_projet;


create table llx_product_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp,
  fk_object                 integer NOT NULL
) ENGINE=innodb;
create table llx_societe_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp,
  fk_object                 integer NOT NULL
) ENGINE=innodb;

ALTER TABLE llx_product_extrafields ADD INDEX idx_product_extrafields (fk_object);
ALTER TABLE llx_societe_extrafields ADD INDEX idx_societe_extrafields (fk_object);

alter table llx_adherent_options_label drop index uk_adherent_options_label_name;
alter table llx_adherent_options_label rename to llx_extrafields; 
ALTER TABLE llx_extrafields ADD COLUMN elementtype varchar(64) NOT NULL DEFAULT 'member' AFTER entity;
ALTER TABLE llx_extrafields ADD UNIQUE INDEX uk_extrafields_name (name, entity, elementtype);
ALTER TABLE llx_adherent_options rename to llx_adherent_extrafields;
ALTER TABLE llx_adherent_extrafields CHANGE COLUMN fk_member fk_object integer NOT NULL;
alter table llx_extrafields add column type varchar(8);

-- drop tables renamed into llx_advanced_extra_xxx
drop table llx_extra_fields_options;
drop table llx_extra_fields_values;
drop table llx_extra_fields;

ALTER TABLE llx_commande MODIFY ref_int varchar(50);
ALTER TABLE llx_commande MODIFY ref_ext varchar(50);
ALTER TABLE llx_commande MODIFY ref_client varchar(50);
ALTER TABLE llx_facture MODIFY ref_int varchar(50);
ALTER TABLE llx_facture MODIFY ref_ext varchar(50);
ALTER TABLE llx_facture MODIFY ref_client varchar(50);
ALTER TABLE llx_propal MODIFY ref_ext varchar(50);
ALTER TABLE llx_propal MODIFY ref_client varchar(50);
ALTER TABLE llx_propal ADD COLUMN ref_int varchar(50) AFTER ref_ext;

-- Add module field to allow external modules to set their name when they add a new record during init/remove.
ALTER TABLE llx_c_chargesociales  ADD COLUMN module        varchar(32) NULL;
ALTER TABLE llx_c_civilite        ADD COLUMN module        varchar(32) NULL;
ALTER TABLE llx_c_effectif        ADD COLUMN module        varchar(32) NULL;
ALTER TABLE llx_c_forme_juridique ADD COLUMN module        varchar(32) NULL;
ALTER TABLE llx_c_input_method    ADD COLUMN module        varchar(32) NULL;
ALTER TABLE llx_c_input_reason    ADD COLUMN module        varchar(32) NULL;
ALTER TABLE llx_c_paiement        ADD COLUMN module        varchar(32) NULL;
ALTER TABLE llx_c_paper_format    ADD COLUMN module        varchar(32) NULL;
ALTER TABLE llx_c_payment_term    ADD COLUMN module        varchar(32) NULL;
ALTER TABLE llx_c_prospectlevel   ADD COLUMN module        varchar(32) NULL;
ALTER TABLE llx_c_shipment_mode   ADD COLUMN module        varchar(32) NULL;
ALTER TABLE llx_c_type_contact    ADD COLUMN module        varchar(32) NULL;
ALTER TABLE llx_c_type_fees       ADD COLUMN module        varchar(32) NULL;
ALTER TABLE llx_c_typent          ADD COLUMN module        varchar(32) NULL;

ALTER TABLE llx_user ADD ref_ext varchar(30) AFTER entity;
ALTER TABLE llx_user ADD civilite varchar(6) AFTER pass_temp;
ALTER TABLE llx_user ADD signature text DEFAULT NULL AFTER email;

ALTER TABLE llx_don ADD   phone_mobile    varchar(24) after email;
ALTER TABLE llx_don ADD   phone           varchar(24) after email;

ALTER TABLE llx_element_element MODIFY sourcetype varchar(32) NOT NULL;
ALTER TABLE llx_element_element MODIFY targettype varchar(32) NOT NULL;

ALTER TABLE llx_societe_prices MODIFY tms timestamp NULL;
-- ALTER TABLE llx_societe_prices ALTER COLUMN tms DROP NOT NULL;

-- Fix: It seems this is missing for some users
insert into llx_c_actioncomm (id, code, type, libelle, module, position) values ( 1,  'AC_TEL',     'system', 'Phone call'                            ,NULL, 2);
insert into llx_c_actioncomm (id, code, type, libelle, module, position) values ( 2,  'AC_FAX',     'system', 'Send Fax'                            ,NULL, 3);
insert into llx_c_actioncomm (id, code, type, libelle, module, position) values ( 3,  'AC_PROP',    'system', 'Send commercial proposal by email'    ,'propal',  10);
insert into llx_c_actioncomm (id, code, type, libelle, module, position) values ( 4,  'AC_EMAIL',   'system', 'Send Email'                            ,NULL, 4);
insert into llx_c_actioncomm (id, code, type, libelle, module, position) values ( 5,  'AC_RDV',     'system', 'Rendez-vous'                            ,NULL, 1);
insert into llx_c_actioncomm (id, code, type, libelle, module, position) values ( 8,  'AC_COM',     'system', 'Send customer order by email'        ,'order',   8);
insert into llx_c_actioncomm (id, code, type, libelle, module, position) values ( 9,  'AC_FAC',     'system', 'Send customer invoice by email'        ,'invoice', 6);
insert into llx_c_actioncomm (id, code, type, libelle, module, position) values ( 10, 'AC_SHIP',    'system', 'Send shipping by email'                ,'shipping', 11);
insert into llx_c_actioncomm (id, code, type, libelle, module, position) values ( 30, 'AC_SUP_ORD', 'system', 'Send supplier order by email'        ,'order_supplier',    9);
insert into llx_c_actioncomm (id, code, type, libelle, module, position) values  (31, 'AC_SUP_INV', 'system', 'Send supplier invoice by email'        ,'invoice_supplier', 7);
insert into llx_c_actioncomm (id, code, type, libelle, module, position) values ( 50, 'AC_OTH',     'system', 'Other'                                ,NULL, 5);
