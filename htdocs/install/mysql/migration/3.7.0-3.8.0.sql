--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 3.8.0 or higher.
--
-- To rename a table:       ALTER TABLE llx_table RENAME TO llx_table_new;
-- To add a column:         ALTER TABLE llx_table ADD COLUMN newcol varchar(60) NOT NULL DEFAULT '0' AFTER existingcol;
-- To rename a column:      ALTER TABLE llx_table CHANGE COLUMN oldname newname varchar(60);
-- To drop a column:        ALTER TABLE llx_table DROP COLUMN oldname;
-- To change type of field: ALTER TABLE llx_table MODIFY COLUMN name varchar(60);
-- To drop a foreign key:   ALTER TABLE llx_table DROP FOREIGN KEY fk_name;
-- To restrict request to Mysql version x.y use -- VMYSQLx.y
-- To restrict request to Pgsql version x.y use -- VPGSQLx.y
-- To make pk to be auto increment (mysql):    VMYSQL4.3 ALTER TABLE llx_c_shipment_mode CHANGE COLUMN rowid rowid INTEGER NOT NULL AUTO_INCREMENT;
-- To make pk to be auto increment (postgres): VPGSQL8.2 NOT POSSIBLE. MUST DELETE/CREATE TABLE
-- To set a field as NULL:                     VPGSQL8.2 ALTER TABLE llx_table ALTER COLUMN name DROP NOT NULL;
-- To set a field as default NULL:             VPGSQL8.2 ALTER TABLE llx_table ALTER COLUMN name SET DEFAULT NULL;
-- -- VPGSQL8.2 DELETE FROM llx_usergroup_user      WHERE fk_user      NOT IN (SELECT rowid from llx_user);
-- -- VMYSQL4.1 DELETE FROM llx_usergroup_user      WHERE fk_usergroup NOT IN (SELECT rowid from llx_usergroup);


--create table for price expressions and add column in product supplier
create table llx_c_price_expression
(
  rowid      integer AUTO_INCREMENT PRIMARY KEY,
  title      varchar(20) NOT NULL,
  expression varchar(80) NOT NULL
)ENGINE=innodb;

ALTER TABLE llx_product_fournisseur_price ADD COLUMN fk_supplier_price_expression integer DEFAULT NULL;
ALTER TABLE llx_product ADD COLUMN fk_price_expression integer DEFAULT NULL;
ALTER TABLE llx_product_price ADD COLUMN fk_price_expression integer DEFAULT NULL;


--create table for user conf of printing driver
CREATE TABLE llx_printing 
(
 rowid integer AUTO_INCREMENT PRIMARY KEY,
 tms timestamp,
 datec datetime,
 printer_name text NOT NULL, 
 printer_location text NOT NULL,
 printer_id varchar(255) NOT NULL,
 copy integer NOT NULL DEFAULT '1',
 module varchar(16) NOT NULL,
 driver varchar(16) NOT NULL,
 userid integer
)ENGINE=innodb;

ALTER TABLE llx_product_fournisseur_price ADD COLUMN fk_price_expression integer DEFAULT NULL;

-- Taiwan VAT Rates
insert into llx_c_tva(rowid,fk_pays,taux,recuperableonly,note,active) values ( 2131, 213, '5', '0', 'VAT 5%', 1);

-- Add situation invoices
ALTER TABLE llx_facture ADD COLUMN situation_cycle_ref smallint;
ALTER TABLE llx_facture ADD COLUMN situation_counter smallint;
ALTER TABLE llx_facture ADD COLUMN situation_final smallint;
ALTER TABLE llx_facturedet ADD COLUMN situation_percent real;
ALTER TABLE llx_facturedet ADD COLUMN fk_prev_id integer;

-- Convert SMTP config to main entity, so new entities don't get the old values
UPDATE llx_const SET entity = 1 WHERE entity = 0 AND name = "MAIN_MAIL_SENDMODE";
UPDATE llx_const SET entity = 1 WHERE entity = 0 AND name = "MAIN_MAIL_SMTP_PORT";
UPDATE llx_const SET entity = 1 WHERE entity = 0 AND name = "MAIN_MAIL_SMTP_SERVER";
UPDATE llx_const SET entity = 1 WHERE entity = 0 AND name = "MAIN_MAIL_SMTPS_ID";
UPDATE llx_const SET entity = 1 WHERE entity = 0 AND name = "MAIN_MAIL_SMTPS_PW";
UPDATE llx_const SET entity = 1 WHERE entity = 0 AND name = "MAIN_MAIL_EMAIL_TLS";


create table llx_bank_account_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                          		-- import key
) ENGINE=innodb;


ALTER TABLE llx_stock_mouvement MODIFY COLUMN label varchar(255);
ALTER TABLE llx_stock_mouvement ADD COLUMN inventorycode varchar(128);

ALTER TABLE llx_product_association ADD COLUMN incdec integer DEFAULT 1;



ALTER TABLE llx_bank_account_extrafields ADD INDEX idx_bank_account_extrafields (fk_object);


create table llx_contratdet_extrafields
(
  rowid            integer AUTO_INCREMENT PRIMARY KEY,
  tms              timestamp,
  fk_object        integer NOT NULL,    -- object id
  import_key       varchar(14)      	-- import key
)ENGINE=innodb;

ALTER TABLE llx_contratdet_extrafields ADD INDEX idx_contratdet_extrafields (fk_object);


--
-- Module incoterm 
--
ALTER TABLE llx_societe ADD COLUMN (
	fk_incoterms integer,
	location_incoterms varchar(255)
);

ALTER TABLE llx_propal ADD COLUMN (
	fk_incoterms integer,
	location_incoterms varchar(255)
);

ALTER TABLE llx_commande ADD COLUMN (
	fk_incoterms integer,
	location_incoterms varchar(255)
);

ALTER TABLE llx_commande_fournisseur ADD COLUMN (
	fk_incoterms integer,
	location_incoterms varchar(255)
);

ALTER TABLE llx_facture ADD COLUMN (
	fk_incoterms integer,
	location_incoterms varchar(255)
);

ALTER TABLE llx_facture_fourn ADD COLUMN (
	fk_incoterms integer,
	location_incoterms varchar(255)
);

ALTER TABLE llx_expedition ADD COLUMN (
	fk_incoterms integer,
	location_incoterms varchar(255)
);

ALTER TABLE llx_livraison ADD COLUMN (
	fk_incoterms integer,
	location_incoterms varchar(255)
);

CREATE TABLE IF NOT EXISTS llx_c_incoterms (
  rowid integer NOT NULL AUTO_INCREMENT,
  code varchar(3) NOT NULL,
  libelle varchar(255) NOT NULL,
  active boolean NOT NULL,
  PRIMARY KEY (rowid)
) ENGINE=InnoDB;

--
-- Content of llx_c_incoterms table
--

INSERT INTO llx_c_incoterms (rowid, code, libelle, active) VALUES
(1, 'EXW', 'Ex Works, au départ non chargé, non dédouané sortie d''usine (uniquement adapté aux flux domestiques, nationaux)', 1),
(2, 'FCA', 'Free Carrier, marchandises dédouanées et chargées dans le pays de départ, chez le vendeur ou chez le commissionnaire de transport de l''acheteur', 1),
(3, 'FAS', 'Free Alongside Ship, sur le quai du port de départ', 1),
(4, 'FOB', 'Free On Board, chargé sur le bateau, les frais de chargement dans celui-ci étant fonction du liner term indiqué par la compagnie maritime (à la charge du vendeur)', 1),
(5, 'CFR', 'Cost and Freight, chargé dans le bateau, livraison au port de départ, frais payés jusqu''au port d''arrivée, sans assurance pour le transport, non déchargé du navire à destination (les frais de déchargement sont inclus ou non au port d''arrivée)', 1),
(6, 'CIF', 'Cost, Insurance and Freight, chargé sur le bateau, frais jusqu''au port d''arrivée, avec l''assurance marchandise transportée souscrite par le vendeur pour le compte de l''acheteur', 1),
(7, 'CPT', 'Carriage Paid To, livraison au premier transporteur, frais jusqu''au déchargement du mode de transport, sans assurance pour le transport', 1),
(8, 'CIP', 'Carriage and Insurance Paid to, idem CPT, avec assurance marchandise transportée souscrite par le vendeur pour le compte de l''acheteur', 1),
(9, 'DAT', 'Delivered At Terminal, marchandises (déchargées) livrées sur quai, dans un terminal maritime, fluvial, aérien, routier ou ferroviaire désigné (dédouanement import, et post-acheminement payés par l''acheteur)', 1),
(10, 'DAP', 'Delivered At Place, marchandises (non déchargées) mises à disposition de l''acheteur dans le pays d''importation au lieu précisé dans le contrat (déchargement, dédouanement import payé par l''acheteur)', 1),
(11, 'DDP', 'Delivered Duty Paid, marchandises (non déchargées) livrées à destination finale, dédouanement import et taxes à la charge du vendeur ; l''acheteur prend en charge uniquement le déchargement (si exclusion des taxes type TVA, le préciser clairement)', 1);
