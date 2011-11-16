--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 2.8.0 or higher. 
--
-- To rename a table:       ALTER TABLE llx_table RENAME TO llx_table_new;
-- To add a column:         ALTER TABLE llx_table ADD COLUMN newcol varchar(60) NOT NULL DEFAULT '0' AFTER existingcol;
-- To rename a column:      ALTER TABLE llx_table CHANGE COLUMN oldname newname varchar(60);
-- To change type of field: ALTER TABLE llx_table MODIFY name varchar(60);
--

UPDATE llx_c_paper_format SET active=1 WHERE active=0;

ALTER TABLE llx_actioncomm ADD COLUMN ref_ext varchar(128) after id;

ALTER TABLE llx_product_fournisseur_price ADD COLUMN fk_availability integer AFTER fk_product_fournisseur;

ALTER TABLE llx_element_element MODIFY COLUMN sourcetype varchar(32) NOT NULL;
ALTER TABLE llx_element_element MODIFY COLUMN targettype varchar(32) NOT NULL;

ALTER TABLE llx_user MODIFY ref_ext varchar(50);
ALTER TABLE llx_user ADD COLUMN ref_int varchar(50) AFTER ref_ext;

ALTER TABLE llx_societe MODIFY code_client varchar(24);
ALTER TABLE llx_societe MODIFY code_fournisseur varchar(24);


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

UPDATE llx_product_fournisseur_price as a, llx_product_fournisseur as b SET a.fk_product = b.fk_product, a.fk_soc = b.fk_soc, a.ref_fourn = b.ref_fourn, a.entity = b.entity WHERE a.fk_product_fournisseur = b.rowid AND (a.fk_product IS NULL OR a.fk_soc IS NULL OR a.fk_product = 0 OR a.fk_soc = 0);

ALTER TABLE llx_product_fournisseur_price DROP INDEX uk_product_fournisseur_price_ref;
ALTER TABLE llx_product_fournisseur_price ADD UNIQUE INDEX uk_product_fournisseur_price_ref (ref_fourn, fk_soc, quantity, entity);
ALTER TABLE llx_product_fournisseur_price ADD INDEX idx_product_fourn_price_fk_product (fk_product, entity);
ALTER TABLE llx_product_fournisseur_price ADD INDEX idx_product_fourn_price_fk_soc (fk_soc, entity);
ALTER TABLE llx_product_fournisseur_price ADD CONSTRAINT fk_product_fournisseur_price_fk_product FOREIGN KEY (fk_product) REFERENCES llx_product (rowid);

ALTER TABLE llx_product_fournisseur_price DROP FOREIGN KEY fk_product_fournisseur_price_fk_product_fournisseur;

DROP TABLE IF EXISTS llx_pos_tmp;

ALTER TABLE llx_deplacement ADD COLUMN fk_user_modif integer AFTER fk_user_author;

CREATE TABLE IF NOT EXISTS llx_localtax
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

