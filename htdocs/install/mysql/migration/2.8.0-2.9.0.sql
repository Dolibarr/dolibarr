--
-- $Id$
--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 2.8.0 or higher. 
--

-- rename llx_product_det
ALTER TABLE llx_product_det RENAME TO llx_product_lang;
ALTER TABLE llx_product_lang ADD UNIQUE INDEX uk_product_lang (fk_product, lang);
ALTER TABLE llx_product_lang ADD CONSTRAINT fk_product_lang_fk_product 	FOREIGN KEY (fk_product) REFERENCES llx_product (rowid);

ALTER TABLE llx_product ADD COLUMN virtual tinyint DEFAULT 0 NOT NULL AFTER tms;
ALTER TABLE llx_product ADD COLUMN fk_parent integer DEFAULT 0 AFTER virtual;

alter table llx_societe add column   default_lang   varchar(6) after price_level;
alter table llx_socpeople add column   default_lang   varchar(6) after note;


alter table llx_mailing add column   joined_file1       varchar(255);
alter table llx_mailing add column   joined_file2       varchar(255);
alter table llx_mailing add column   joined_file3       varchar(255);
alter table llx_mailing add column   joined_file4       varchar(255);

update llx_facture_fourn set fk_statut=2 where fk_statut=1 AND paye=1;

alter table llx_facture_fourn add column close_code          varchar(16) after remise;
alter table llx_facture_fourn add column close_note          varchar(128) after close_code;
