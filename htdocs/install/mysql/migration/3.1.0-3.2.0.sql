--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 3.1.0 or higher. 
--
-- To rename a table:       ALTER TABLE llx_table RENAME TO llx_table_new;
-- To add a column:         ALTER TABLE llx_table ADD COLUMN newcol varchar(60) NOT NULL DEFAULT '0' AFTER existingcol;
-- To rename a column:      ALTER TABLE llx_table CHANGE COLUMN oldname newname varchar(60);
-- To drop a column:        ALTER TABLE llx_table DROP COLUMN oldname;
-- To change type of field: ALTER TABLE llx_table MODIFY name varchar(60);
-- To restrict request to Mysql version x.y use -- VMYSQLx.y
-- To restrict request to Pgsql version x.y use -- VPGSQLx.y


-- -- VPGSQL8.2 DELETE FROM llx_usergroup_user      WHERE fk_user      NOT IN (SELECT rowid from llx_user);
-- -- VMYSQL4.1 DELETE FROM llx_usergroup_user      WHERE fk_usergroup NOT IN (SELECT rowid from llx_usergroup);

-- Delete old themes setup
DELETE FROM llx_user_param WHERE param = 'MAIN_THEME' and value = 'freelug';


update llx_propal set fk_projet = null where fk_projet not in (select rowid from llx_projet);
update llx_commande set fk_projet = null where fk_projet not in (select rowid from llx_projet);
update llx_facture set fk_projet = null where fk_projet not in (select rowid from llx_projet);
update llx_commande_fournisseur set fk_projet = null where fk_projet not in (select rowid from llx_projet);
update llx_contrat set fk_projet = null where fk_projet not in (select rowid from llx_projet);
update llx_deplacement set fk_projet = null where fk_projet not in (select rowid from llx_projet);
update llx_facture_fourn set fk_projet = null where fk_projet not in (select rowid from llx_projet);
update llx_facture_rec set fk_projet = null where fk_projet not in (select rowid from llx_projet);
update llx_fichinter set fk_projet = null where fk_projet not in (select rowid from llx_projet);
update llx_projet_task set fk_projet = null where fk_projet not in (select rowid from llx_projet);

update llx_propal set fk_user_author = null where fk_user_author not in (select rowid from llx_user);
update llx_propal set fk_user_valid = null where fk_user_valid not in (select rowid from llx_user);
update llx_propal set fk_user_cloture = null where fk_user_cloture not in (select rowid from llx_user);
update llx_commande set fk_user_author = null where fk_user_author not in (select rowid from llx_user);
update llx_commande set fk_user_valid = null where fk_user_valid not in (select rowid from llx_user);


ALTER TABLE llx_extrafields ADD COLUMN type VARCHAR(8);

UPDATE llx_c_paper_format SET active=1 WHERE active=0;

ALTER TABLE llx_actioncomm ADD COLUMN ref_ext varchar(128) after id;

ALTER TABLE llx_product_fournisseur_price ADD COLUMN fk_availability integer AFTER fk_product_fournisseur;

ALTER TABLE llx_element_element MODIFY COLUMN sourcetype varchar(32) NOT NULL;
ALTER TABLE llx_element_element MODIFY COLUMN targettype varchar(32) NOT NULL;

ALTER TABLE llx_user MODIFY ref_ext varchar(50);
ALTER TABLE llx_user ADD COLUMN ref_int varchar(50) AFTER ref_ext;

ALTER TABLE llx_societe MODIFY code_client varchar(24);
ALTER TABLE llx_societe MODIFY code_fournisseur varchar(24);
ALTER TABLE llx_societe MODIFY siren varchar(128);
ALTER TABLE llx_societe MODIFY siret varchar(128);
ALTER TABLE llx_societe MODIFY ape varchar(128);
ALTER TABLE llx_societe MODIFY idprof4 varchar(128);
ALTER TABLE llx_societe ADD COLUMN idprof5 varchar(128);
ALTER TABLE llx_societe MODIFY code_compta varchar(24);
ALTER TABLE llx_societe MODIFY code_compta_fournisseur varchar(24);

  
ALTER TABLE llx_chargesociales ADD COLUMN tms                   timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE llx_chargesociales ADD COLUMN date_creation         datetime; 
ALTER TABLE llx_chargesociales ADD COLUMN date_valid            datetime;

  
-- Europe
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (1,   'EU4A0',       'Format 4A0',                '1682', '2378', 'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (2,   'EU2A0',       'Format 2A0',                '1189', '1682', 'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (3,   'EUA0',        'Format A0',                 '840',  '1189', 'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (4,   'EUA1',        'Format A1',                 '594',  '840',  'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (5,   'EUA2',        'Format A2',                 '420',  '594',  'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (6,   'EUA3',        'Format A3',                 '297',  '420',  'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (7,   'EUA4',        'Format A4',                 '210',  '297',  'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (8,   'EUA5',        'Format A5',                 '148',  '210',  'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (9,   'EUA6',        'Format A6',                 '105',  '148',  'mm', 1);

-- US
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (100, 'USLetter',    'Format Letter (A)',         '216',  '279',  'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (105, 'USLegal',     'Format Legal',              '216',  '356',  'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (110, 'USExecutive', 'Format Executive',          '190',  '254',  'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (115, 'USLedger',    'Format Ledger/Tabloid (B)', '279',  '432',  'mm', 1);

-- Canadian
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (200, 'CAP1',        'Format Canadian P1',        '560',  '860',  'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (205, 'CAP2',        'Format Canadian P2',        '430',  '560',  'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (210, 'CAP3',        'Format Canadian P3',        '280',  '430',  'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (215, 'CAP4',        'Format Canadian P4',        '215',  '280',  'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (220, 'CAP5',        'Format Canadian P5',        '140',  '215',  'mm', 1);
INSERT INTO llx_c_paper_format (rowid, code, label, width, height, unit, active) VALUES (225, 'CAP6',        'Format Canadian P6',        '107',  '140',  'mm', 1);



ALTER TABLE llx_product_fournisseur_price ADD COLUMN fk_product	integer after tms;
ALTER TABLE llx_product_fournisseur_price ADD COLUMN fk_soc integer after fk_product;
ALTER TABLE llx_product_fournisseur_price ADD COLUMN ref_fourn varchar(30) after fk_soc;
ALTER TABLE llx_product_fournisseur_price ADD COLUMN entity integer DEFAULT 1 NOT NULL;

-- VMYSQL4.1 UPDATE llx_product_fournisseur_price as a, llx_product_fournisseur as b SET a.fk_product = b.fk_product, a.fk_soc = b.fk_soc, a.ref_fourn = b.ref_fourn, a.entity = b.entity WHERE a.fk_product_fournisseur = b.rowid AND (a.fk_product IS NULL OR a.fk_soc IS NULL OR a.fk_product = 0 OR a.fk_soc = 0);
-- VPGSQL8.1 UPDATE llx_product_fournisseur_price as a SET fk_product = b.fk_product, fk_soc = b.fk_soc, ref_fourn = b.ref_fourn, entity = b.entity FROM llx_product_fournisseur as b WHERE a.fk_product_fournisseur = b.rowid AND (a.fk_product IS NULL OR a.fk_soc IS NULL OR a.fk_product = 0 OR a.fk_soc = 0);

ALTER TABLE llx_product_fournisseur_price DROP INDEX uk_product_fournisseur_price_ref;
ALTER TABLE llx_product_fournisseur_price ADD UNIQUE INDEX uk_product_fournisseur_price_ref (ref_fourn, fk_soc, quantity, entity);
ALTER TABLE llx_product_fournisseur_price ADD INDEX idx_product_fourn_price_fk_product (fk_product, entity);
ALTER TABLE llx_product_fournisseur_price ADD INDEX idx_product_fourn_price_fk_soc (fk_soc, entity);
ALTER TABLE llx_product_fournisseur_price ADD CONSTRAINT fk_product_fournisseur_price_fk_product FOREIGN KEY (fk_product) REFERENCES llx_product (rowid);

ALTER TABLE llx_product_fournisseur_price DROP FOREIGN KEY fk_product_fournisseur_price_fk_product_fournisseur;

DROP TABLE IF EXISTS llx_pos_tmp;

ALTER TABLE llx_deplacement ADD COLUMN fk_user_modif integer AFTER fk_user_author;

CREATE TABLE llx_localtax
(
	rowid			integer		AUTO_INCREMENT PRIMARY KEY,
	entity			integer			NOT NULL DEFAULT '1',
	tms				timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	datep			date			DEFAULT NULL,
	datev			date			DEFAULT NULL,
	amount			double			NOT NULL DEFAULT '0',
	label			varchar(255)	DEFAULT NULL,
	note			text,
	fk_bank			integer			DEFAULT NULL,
	fk_user_creat	integer			DEFAULT NULL,
	fk_user_modif	integer			DEFAULT NULL
	
) ENGINE=InnoDB;

ALTER TABLE llx_propal MODIFY ref_int varchar(255);
ALTER TABLE llx_propal MODIFY ref_ext varchar(255);
ALTER TABLE llx_propal MODIFY ref_client varchar(255);

ALTER TABLE llx_commande MODIFY ref_int varchar(255);
ALTER TABLE llx_commande MODIFY ref_ext varchar(255);
ALTER TABLE llx_commande MODIFY ref_client varchar(255);

ALTER TABLE llx_facture MODIFY ref_int varchar(255);
ALTER TABLE llx_facture MODIFY ref_ext varchar(255);
ALTER TABLE llx_facture MODIFY ref_client varchar(255);


UPDATE llx_societe SET fk_stcomm = 0 WHERE fk_stcomm IS NULL;
ALTER TABLE llx_societe MODIFY COLUMN fk_stcomm integer NOT NULL;

ALTER TABLE llx_societe CHANGE COLUMN gencod barcode varchar(255);
ALTER TABLE llx_societe ADD COLUMN fk_barcode_type integer DEFAULT 0;

UPDATE llx_menu SET leftmenu = NULL where leftmenu in ('', '0', '1');

ALTER TABLE llx_categorie_societe DROP INDEX fk_categorie;
ALTER TABLE llx_categorie_societe DROP INDEX fk_societe;

ALTER TABLE llx_categorie_fournisseur DROP INDEX fk_categorie;
-- VMYSQL ALTER TABLE llx_categorie_fournisseur DROP PRIMARY KEY;
-- VPGSQL ALTER TABLE llx_categorie_fournisseur DROP CONSTRAINT pk_categorie_fournisseur;
ALTER TABLE llx_categorie_fournisseur ADD PRIMARY KEY pk_categorie_fournisseur (fk_categorie, fk_societe);
ALTER TABLE llx_categorie_fournisseur ADD INDEX idx_categorie_fournisseur_fk_categorie (fk_categorie);
ALTER TABLE llx_categorie_fournisseur ADD INDEX idx_categorie_fournisseur_fk_societe (fk_societe);
DELETE FROM llx_categorie_fournisseur WHERE fk_categorie NOT IN (SELECT rowid FROM llx_categorie);
DELETE FROM llx_categorie_fournisseur WHERE fk_societe NOT IN (SELECT rowid FROM llx_societe);
ALTER TABLE llx_categorie_fournisseur ADD CONSTRAINT fk_categorie_fournisseur_categorie_rowid FOREIGN KEY (fk_categorie) REFERENCES llx_categorie (rowid);
ALTER TABLE llx_categorie_fournisseur ADD CONSTRAINT fk_categorie_fournisseur_fk_soc   FOREIGN KEY (fk_societe) REFERENCES llx_societe (rowid);

-- Regions Venezuela (id country=232)
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (23201,  232, 23201, '', 0, 'Los Andes', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (23202,  232, 23202, '', 0, 'Capital', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (23203,  232, 23203, '', 0, 'Central', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (23204,  232, 23204, '', 0, 'Cento Occidental', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (23205,  232, 23205, '', 0, 'Guayana', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (23206,  232, 23206, '', 0, 'Insular', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (23207,  232, 23207, '', 0, 'Los Llanos', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (23208,  232, 23208, '', 0, 'Nor-Oriental', 1);
INSERT INTO llx_c_regions (rowid, fk_pays, code_region, cheflieu, tncc, nom, active) VALUES (23209,  232, 23209, '', 0, 'Zuliana', 1);

-- Provinces Venezuela (id country=232)
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VE-L', 23201, '', 0, 'VE-L', 'Mérida', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VE-T', 23201, '', 0, 'VE-T', 'Trujillo', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VE-E', 23201, '', 0, 'VE-E', 'Barinas', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VE-M', 23202, '', 0, 'VE-M', 'Miranda', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VE-W', 23202, '', 0, 'VE-W', 'Vargas', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VE-A', 23202, '', 0, 'VE-A', 'Distrito Capital', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VE-D', 23203, '', 0, 'VE-D', 'Aragua', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VE-G', 23203, '', 0, 'VE-G', 'Carabobo', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VE-I', 23204, '', 0, 'VE-I', 'Falcón', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VE-K', 23204, '', 0, 'VE-K', 'Lara', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VE-U', 23204, '', 0, 'VE-U', 'Yaracuy', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VE-F', 23205, '', 0, 'VE-F', 'Bolívar', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VE-X', 23205, '', 0, 'VE-X', 'Amazonas', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VE-Y', 23205, '', 0, 'VE-Y', 'Delta Amacuro', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VE-O', 23206, '', 0, 'VE-O', 'Nueva Esparta', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VE-Z', 23206, '', 0, 'VE-Z', 'Dependencias Federales', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VE-C', 23207, '', 0, 'VE-C', 'Apure', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VE-J', 23207, '', 0, 'VE-J', 'Guárico', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VE-H', 23207, '', 0, 'VE-H', 'Cojedes', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VE-P', 23207, '', 0, 'VE-P', 'Portuguesa', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VE-B', 23208, '', 0, 'VE-B', 'Anzoátegui', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VE-N', 23208, '', 0, 'VE-N', 'Monagas', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VE-R', 23208, '', 0, 'VE-R', 'Sucre', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VE-V', 23209, '', 0, 'VE-V', 'Zulia', 1);
INSERT INTO llx_c_departements ( code_departement, fk_region, cheflieu, tncc, ncc, nom, active) VALUES ('VE-S', 23209, '', 0, 'VE-S', 'Táchira', 1);
-- Currency Venezuela
insert into llx_c_currencies ( code, code_iso, active, label ) VALUES ( 'VE', 'VEF', 1, 'Venezuelan Bolívar');

-- VENEZUELA (id country=232)
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (2321,232,     '0','0','No VAT',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (2322,232,     '12','0','VAT 12%',1);
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values (2323,232,     '8','0','VAT 8%',1);

update llx_cotisation set fk_bank = null where fk_bank not in (select rowid from llx_bank);

insert into llx_c_chargesociales (id, libelle, deductible, active, code, fk_pays) values (12, 'Cotisation foncière des entreprises', 0, 1, 'TAXCFE', '1');
insert into llx_c_chargesociales (id, libelle, deductible, active, code, fk_pays) values (13, 'Cotisation sur la valeur ajoutée des entreprises', 0, 1, 'TAXCVAE', '1');

ALTER TABLE llx_paiement ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER rowid;
ALTER TABLE llx_product_price ADD COLUMN entity integer DEFAULT 1 NOT NULL AFTER rowid;

-- Restore foreign key (on llx_expedition_methode before) on correct table (llx_c_shipment_mode)
UPDATE llx_expedition SET fk_expedition_methode = null WHERE fk_expedition_methode NOT IN (SELECT rowid FROM llx_c_shipment_mode);
ALTER TABLE llx_expedition DROP FOREIGN KEY fk_expedition_fk_expedition_methode;
ALTER TABLE llx_expedition ADD CONSTRAINT fk_expedition_fk_expedition_methode 	FOREIGN KEY (fk_expedition_methode) REFERENCES llx_c_shipment_mode (rowid);


-- VMYSQL4.1 UPDATE llx_chargesociales set tms = date_creation WHERE tms = '0000-00-00 00:00:00';

ALTER TABLE llx_propal MODIFY fk_projet integer DEFAULT NULL;
ALTER TABLE llx_propal ADD COLUMN fk_account integer AFTER total;
ALTER TABLE llx_propal ADD COLUMN fk_currency varchar(2) AFTER fk_account;
ALTER TABLE llx_propal ADD INDEX idx_propal_fk_user_author (fk_user_author);
ALTER TABLE llx_propal ADD INDEX idx_propal_fk_user_valid (fk_user_valid);
ALTER TABLE llx_propal ADD INDEX idx_propal_fk_user_cloture (fk_user_cloture);
ALTER TABLE llx_propal ADD INDEX idx_propal_fk_projet (fk_projet);
ALTER TABLE llx_propal ADD INDEX idx_propal_fk_account(fk_account);
ALTER TABLE llx_propal ADD INDEX idx_propal_fk_currency(fk_currency);
ALTER TABLE llx_propal ADD CONSTRAINT fk_propal_fk_user_author	FOREIGN KEY (fk_user_author) REFERENCES llx_user (rowid);
ALTER TABLE llx_propal ADD CONSTRAINT fk_propal_fk_user_valid	FOREIGN KEY (fk_user_valid)  REFERENCES llx_user (rowid);
ALTER TABLE llx_propal ADD CONSTRAINT fk_propal_fk_user_cloture	FOREIGN KEY (fk_user_cloture) REFERENCES llx_user (rowid);
ALTER TABLE llx_propal ADD CONSTRAINT fk_propal_fk_projet		FOREIGN KEY (fk_projet) REFERENCES llx_projet (rowid);
ALTER TABLE llx_propal DROP FOREIGN KEY fk_propal_fk_account;
ALTER TABLE llx_propal DROP FOREIGN KEY fk_propal_fk_currency;

ALTER TABLE llx_commande MODIFY fk_projet integer DEFAULT NULL;
ALTER TABLE llx_commande ADD COLUMN fk_account integer AFTER facture;
ALTER TABLE llx_commande ADD COLUMN fk_currency varchar(2) AFTER fk_account;
ALTER TABLE llx_commande ADD INDEX idx_commande_fk_user_author (fk_user_author);
ALTER TABLE llx_commande ADD INDEX idx_commande_fk_user_valid (fk_user_valid);
ALTER TABLE llx_commande ADD INDEX idx_commande_fk_user_cloture (fk_user_cloture);
ALTER TABLE llx_commande ADD INDEX idx_commande_fk_projet (fk_projet);
ALTER TABLE llx_commande ADD INDEX idx_commande_fk_account(fk_account);
ALTER TABLE llx_commande ADD INDEX idx_commande_fk_currency(fk_currency);
ALTER TABLE llx_commande ADD CONSTRAINT fk_commande_fk_user_author	FOREIGN KEY (fk_user_author) REFERENCES llx_user (rowid);
ALTER TABLE llx_commande ADD CONSTRAINT fk_commande_fk_user_valid	FOREIGN KEY (fk_user_valid)  REFERENCES llx_user (rowid);
ALTER TABLE llx_commande ADD CONSTRAINT fk_commande_fk_user_cloture	FOREIGN KEY (fk_user_cloture) REFERENCES llx_user (rowid);
ALTER TABLE llx_commande ADD CONSTRAINT fk_commande_fk_projet		FOREIGN KEY (fk_projet) REFERENCES llx_projet (rowid);
ALTER TABLE llx_commande DROP FOREIGN KEY fk_commande_fk_account;
ALTER TABLE llx_commande DROP FOREIGN KEY fk_commande_fk_currency;

ALTER TABLE llx_facture MODIFY fk_projet integer DEFAULT NULL;
ALTER TABLE llx_facture ADD COLUMN fk_account integer AFTER fk_projet;
ALTER TABLE llx_facture ADD COLUMN fk_currency varchar(2) AFTER fk_account;
ALTER TABLE llx_facture ADD INDEX idx_facture_fk_account (fk_account);
ALTER TABLE llx_facture ADD INDEX idx_facture_fk_currency (fk_currency);
ALTER TABLE llx_facture DROP FOREIGN KEY fk_facture_fk_account;
ALTER TABLE llx_facture DROP FOREIGN KEY fk_facture_fk_currency;

ALTER TABLE llx_actioncomm DROP COLUMN propalrowid;
ALTER TABLE llx_actioncomm DROP COLUMN fk_facture;
ALTER TABLE llx_actioncomm DROP COLUMN fk_supplier_order;
ALTER TABLE llx_actioncomm DROP COLUMN fk_supplier_invoice;
ALTER TABLE llx_actioncomm DROP COLUMN fk_commande;
ALTER TABLE llx_product_stock DROP COLUMN location;

ALTER TABLE llx_adherent_extrafields ADD COLUMN import_key varchar(14);
ALTER TABLE llx_product_extrafields  ADD COLUMN import_key varchar(14);
ALTER TABLE llx_societe_extrafields  ADD COLUMN import_key varchar(14);

DROP TABLE llx_c_currencies;
create table llx_c_currencies
(
  code_iso		varchar(3)   PRIMARY KEY,
  label			varchar(64) NOT NULL,
  unicode		varchar(32) DEFAULT NULL,
  active		tinyint DEFAULT 1  NOT NULL
)ENGINE=innodb;

INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'ALL', '[76,101,107]', 1,		'Albania Lek');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'DZD', NULL, 1,					'Algeria Dinar');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'AFN', '[1547]', 1,				'Afghanistan Afghani');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'ARS', '[36]', 1,				'Argentino Peso');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'AWG', '[402]', 1,				'Aruba Guilder');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'AUD', '[36]', 1,				'Australia Dollar');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'AZN', '[1084,1072,1085]', 1,	'Azerbaijan New Manat');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'BSD', '[36]', 1,				'Bahamas Dollar');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'BBD', '[36]', 1,				'Barbados Dollar');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'BYR', '[112,46]', 1,			'Belarus Ruble');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'BZD', '[66,90,36]', 1,			'Belize Dollar');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'BMD', '[36]', 1,				'Bermuda Dollar');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'BOB', '[36,98]', 1,				'Bolivia Boliviano');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'BAM', '[75,77]', 1,				'Bosnia and Herzegovina Convertible Marka');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'BWP', '[80]', 1,				'Botswana Pula');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'BGN', '[1083,1074]', 1,			'Bulgaria Lev');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'BRL', '[82,36]', 1,				'Brazil Real');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'BND', '[36]', 1,				'Brunei Darussalam Dollar');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'KHR', '[6107]', 1,				'Cambodia Riel');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'CAD', '[36]', 1,				'Canada Dollar');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'KYD', '[36]', 1,				'Cayman Islands Dollar');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'CLP', '[36]', 1,				'Chile Peso');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'CNY', '[165]', 1,				'China Yuan Renminbi'); 
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'COP', '[36]', 1,				'Colombia Peso');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'CRC', '[8353]', 1,				'Costa Rica Colon');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'HRK', '[107,110]', 1,			'Croatia Kuna');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'CUP', '[8369]', 1,				'Cuba Peso');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'CZK', '[75,269]', 1,			'Czech Republic Koruna');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'DKK', '[107,114]', 1,			'Denmark Krone');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'DOP', '[82,68,36]', 1,			'Dominican Republic Peso');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'XCD', '[36]', 1,				'East Caribbean Dollar');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'EGP', '[163]', 1,				'Egypt Pound');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'SVC', '[36]', 1,				'El Salvador Colon');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'EEK', '[107,114]', 1,			'Estonia Kroon');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'EUR', '[8364]', 1,				'Euro Member Countries'); 
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'FKP', '[163]', 1,				'Falkland Islands (Malvinas) Pound');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'FJD', '[36]', 1,				'Fiji Dollar');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'GHC', '[162]', 1,				'Ghana Cedis');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'GIP', '[163]', 1,				'Gibraltar Pound');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'GTQ', '[81]', 1,				'Guatemala Quetzal');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'GGP', '[163]', 1,				'Guernsey Pound');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'GYD', '[36]', 1,				'Guyana Dollar');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'HNL', '[76]', 1,				'Honduras Lempira');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'HKD', '[36]', 1,				'Hong Kong Dollar');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'HUF', '[70,116]', 1,			'Hungary Forint');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'ISK', '[107,114]', 1,			'Iceland Krona');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'INR', NULL, 1,					'India Rupee'); 
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'IDR', '[82,112]', 1,			'Indonesia Rupiah');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'IRR', '[65020]', 1,				'Iran Rial');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'IMP', '[163]', 1,				'Isle of Man Pound');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'ILS', '[8362]', 1,				'Israel Shekel');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'JMD', '[74,36]', 1,				'Jamaica Dollar');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'JPY', '[165]', 1,				'Japan Yen');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'JEP', '[163]', 1,				'Jersey Pound');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'KZT', '[1083,1074]', 1,			'Kazakhstan Tenge');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'KPW', '[8361]', 1,				'Korea (North) Won');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'KRW', '[8361]', 1,				'Korea (South) Won');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'KGS', '[1083,1074]', 1,			'Kyrgyzstan Som');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'LAK', '[8365]', 1,				'Laos Kip');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'LVL', '[76,115]', 1,			'Latvia Lat');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'LBP', '[163]', 1,				'Lebanon Pound');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'LRD', '[36]', 1,				'Liberia Dollar');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'LTL', '[76,116]', 1,			'Lithuania Litas');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'MKD', '[1076,1077,1085]', 1,	'Macedonia Denar');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'MYR', '[82,77]', 1,				'Malaysia Ringgit');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'MRO', NULL, 1,					'Mauritania Ouguiya');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'MUR', '[8360]', 1,				'Mauritius Rupee');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'MXN', '[36]', 1,				'Mexico Peso');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'MNT', '[8366]', 1,				'Mongolia Tughrik');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'MAD', NULL, 1,					'Morocco Dirham');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'MZN', '[77,84]', 1,				'Mozambique Metical');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'NAD', '[36]', 1,				'Namibia Dollar');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'NPR', '[8360]', 1,				'Nepal Rupee');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'ANG', '[402]', 1,				'Netherlands Antilles Guilder');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'NZD', '[36]', 1,				'New Zealand Dollar');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'NIO', '[67,36]', 1,				'Nicaragua Cordoba');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'NGN', '[8358]', 1,				'Nigeria Naira');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'NOK', '[107,114]', 1,			'Norway Krone');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'OMR', '[65020]', 1,				'Oman Rial');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'PKR', '[8360]', 1,				'Pakistan Rupee');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'PAB', '[66,47,46]', 1,			'Panama Balboa');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'PYG', '[71,115]', 1,			'Paraguay Guarani');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'PEN', '[83,47,46]', 1,			'Peru Nuevo Sol');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'PHP', '[8369]', 1,				'Philippines Peso');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'PLN', '[122,322]', 1,			'Poland Zloty');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'QAR', '[65020]', 1,				'Qatar Riyal');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'RON', '[108,101,105]', 1,		'Romania New Leu');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'RUB', '[1088,1091,1073]', 1,	'Russia Ruble');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'SHP', '[163]', 1,				'Saint Helena Pound');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'SAR', '[65020]', 1,				'Saudi Arabia Riyal');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'RSD', '[1044,1080,1085,46]', 1,	'Serbia Dinar');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'SCR', '[8360]', 1,				'Seychelles Rupee');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'SGD', '[36]', 1,				'Singapore Dollar');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'SBD', '[36]', 1,				'Solomon Islands Dollar');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'SOS', '[83]', 1,				'Somalia Shilling');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'ZAR', '[82]', 1,				'South Africa Rand');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'LKR', '[8360]', 1,				'Sri Lanka Rupee');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'SEK', '[107,114]', 1,			'Sweden Krona');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'CHF', '[67,72,70]', 1,			'Switzerland Franc');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'SRD', '[36]', 1,				'Suriname Dollar');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'SYP', '[163]', 1,				'Syria Pound');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'TWD', '[78,84,36]', 1,			'Taiwan New Dollar');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'THB', '[3647]', 1,				'Thailand Baht');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'TTD', '[84,84,36]', 1,			'Trinidad and Tobago Dollar');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'TND', NULL, 1,					'Tunisia Dinar');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'TRL', '[84,76]', 1,				'Turkey Lira');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'TRY', '[8356]', 1,				'Turkey Lira');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'TVD', '[36]', 1,				'Tuvalu Dollar');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'UAH', '[8372]', 1,				'Ukraine Hryvna');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'AED', NULL, 1,					'United Arab Emirates Dirham');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'GBP', '[163]', 1,				'United Kingdom Pound');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'USD', '[36]', 1,				'United States Dollar');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'UYU', '[36,85]', 1,				'Uruguay Peso');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'UZS', '[1083,1074]', 1,			'Uzbekistan Som');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'VEF', '[66,115]', 1,			'Venezuela Bolivar Fuerte');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'VND', '[8363]', 1,				'Viet Nam Dong');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'XAF', NULL, 1,					'Communaute Financiere Africaine (BEAC) CFA Franc');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'XOF', NULL, 1,					'Communaute Financiere Africaine (BCEAO) Franc');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'YER', '[65020]', 1,				'Yemen Rial');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'ZWD', '[90,36]', 1,				'Zimbabwe Dollar');

-- obsolete
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'ATS', NULL, 0,	'Shiliing autrichiens');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'BEF', NULL, 0,	'Francs belges');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'DEM', NULL, 0,	'Deutsch mark');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'ESP', NULL, 0,	'Pesete');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'FIM', NULL, 0,	'Mark finlandais'); 
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'FRF', NULL, 0,	'Francs francais');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'GRD', NULL, 0,	'Drachme (grece)');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'IEP', NULL, 0,	'Livres irlandaises');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'ITL', NULL, 0,	'Lires');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'LUF', NULL, 0,	'Francs luxembourgeois');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'NLG', NULL, 0,	'Florins');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'PTE', NULL, 0,	'Escudos');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'SKK', NULL, 0,	'Couronnes slovaques');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'SUR', NULL, 0,	'Rouble');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'XEU', NULL, 0,	'Ecus');

-- invalid (for compatibility)
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'ARP', NULL, 0,	'Pesos argentins');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'MXP', NULL, 0,	'Pesos Mexicans');

ALTER TABLE llx_expedition DROP COLUMN billed;

ALTER TABLE llx_product_fournisseur_price DROP FOREIGN KEY fk_product_fournisseur_price_fk_product_fournisseur;
ALTER TABLE llx_product_fournisseur_price DROP INDEX idx_product_fournisseur_price_fk_product_fournisseur;
--We keep column for the moment because we must not loose data if migrate process fails (upgrade2) to allow a second chance fix. We will delete it at next version.
--ALTER TABLE llx_product_fournisseur_price DROP COLUMN fk_product_fournisseur;
ALTER TABLE llx_product_fournisseur_price ADD COLUMN tva_tx	double(6,3) NOT NULL DEFAULT 0 AFTER unitprice;

UPDATE llx_c_departements SET ncc='JUJUY', nom = 'Jujuy' WHERE code_departement='2302' and fk_region='2301';

ALTER TABLE llx_propal ADD COLUMN import_key varchar(14) AFTER fk_demand_reason;
ALTER TABLE llx_propal ADD COLUMN extraparams varchar(255) AFTER import_key;
ALTER TABLE llx_commande ADD COLUMN extraparams varchar(255) AFTER import_key;
ALTER TABLE llx_facture ADD COLUMN extraparams varchar(255) AFTER import_key;
ALTER TABLE llx_fichinter ADD COLUMN extraparams varchar(255) AFTER model_pdf;
ALTER TABLE llx_deplacement ADD COLUMN extraparams varchar(255) AFTER note_public;
ALTER TABLE llx_contrat ADD COLUMN import_key varchar(14) AFTER note_public;
ALTER TABLE llx_contrat ADD COLUMN extraparams varchar(255) AFTER import_key;
ALTER TABLE llx_commande_fournisseur ADD COLUMN extraparams varchar(255) AFTER import_key;
ALTER TABLE llx_facture_fourn ADD COLUMN extraparams varchar(255) AFTER import_key;

ALTER TABLE llx_boxes ADD COLUMN maxline integer NULL;

ALTER TABLE llx_product_fournisseur_price MODIFY fk_product_fournisseur integer DEFAULT 0;

UPDATE llx_product SET canvas = NULL where canvas = 'default@product';
UPDATE llx_product SET canvas = NULL where canvas = 'product@product';
UPDATE llx_product SET canvas = NULL where canvas = 'service@product';

DELETE FROM llx_const WHERE __DECRYPT('name')__ = 'SOCIETE_CODECOMPTA_ADDON' AND __DECRYPT('value')__ = 'mod_codecompta_digitaria';

ALTER TABLE llx_c_barcode_type ADD UNIQUE INDEX uk_c_barcode_type(code, entity);

-- To make migration script with version >= 3.3 working correctly
ALTER TABLE llx_c_tva ADD COLUMN localtax1_type varchar(1) default '0' after localtax1;
ALTER TABLE llx_c_tva ADD COLUMN localtax2_type varchar(1) default '0' after localtax2;

