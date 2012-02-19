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

ALTER TABLE llx_extrafields ADD COLUMN TYPE VARCHAR(8);

UPDATE llx_c_paper_format SET active=1 WHERE active=0;

ALTER TABLE llx_actioncomm ADD COLUMN ref_ext varchar(128) after id;

ALTER TABLE llx_product_fournisseur_price ADD COLUMN fk_availability integer AFTER fk_product_fournisseur;

ALTER TABLE llx_element_element MODIFY COLUMN sourcetype varchar(32) NOT NULL;
ALTER TABLE llx_element_element MODIFY COLUMN targettype varchar(32) NOT NULL;

ALTER TABLE llx_user MODIFY ref_ext varchar(50);
ALTER TABLE llx_user ADD COLUMN ref_int varchar(50) AFTER ref_ext;

ALTER TABLE llx_societe MODIFY code_client varchar(24);
ALTER TABLE llx_societe MODIFY code_fournisseur varchar(24);

ALTER TABLE llx_chargesociales ADD COLUMN tms                   timestamp;
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
	tms				timestamp,
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
ALTER TABLE llx_categorie_fournisseur DROP PRIMARY KEY;
ALTER TABLE llx_categorie_fournisseur ADD PRIMARY KEY pk_categorie_fournisseur (fk_categorie, fk_societe);
ALTER TABLE llx_categorie_fournisseur ADD INDEX idx_categorie_fournisseur_fk_categorie (fk_categorie);
ALTER TABLE llx_categorie_fournisseur ADD INDEX idx_categorie_fournisseur_fk_societe (fk_societe);
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
ALTER TABLE llx_expedition DROP FOREIGN KEY fk_expedition_fk_expedition_methode;
ALTER TABLE llx_expedition ADD CONSTRAINT fk_expedition_fk_expedition_methode 	FOREIGN KEY (fk_expedition_methode) REFERENCES llx_c_shipment_mode (rowid);

-- VMYSQL4.1 UPDATE llx_chargesociales set tms = date_creation WHERE tms = '0000-00-00 00:00:00';

ALTER TABLE llx_actioncomm DROP COLUMN propalrowid;
ALTER TABLE llx_actioncomm DROP COLUMN fk_facture;
ALTER TABLE llx_actioncomm DROP COLUMN fk_supplier_order;
ALTER TABLE llx_actioncomm DROP COLUMN fk_supplier_invoice;
ALTER TABLE llx_actioncomm DROP COLUMN fk_commande;
ALTER TABLE llx_product_stock DROP COLUMN location;
-- DROP TABLE llx_c_methode_commande_fournisseur;
-- DROP TABLE llx_c_source;
-- DROP TABLE llx_expedition_methode;
-- DROP TABLE llx_product_fournisseur;
