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

-- add products variants ability
ALTER TABLE llx_product ADD COLUMN virtual tinyint DEFAULT 0 NOT NULL AFTER tms;
ALTER TABLE llx_product ADD COLUMN fk_parent integer DEFAULT 0 AFTER virtual;

create table llx_product_variant
(
  rowid				integer AUTO_INCREMENT PRIMARY KEY,
  tms				timestamp,
  ref				varchar(64) NOT NULL,
  entity			integer DEFAULT 1 NOT NULL, -- multi company id
  active			tinyint DEFAULT 1 NOT NULL,
  rang				integer DEFAULT 0
)type=innodb;

ALTER TABLE llx_product_variant ADD UNIQUE INDEX uk_product_variant_ref (ref, entity);

create table llx_product_variant_lang
(
  rowid					integer AUTO_INCREMENT PRIMARY KEY,
  fk_product_variant	integer			DEFAULT 0 NOT NULL,
  lang					varchar(5)		NOT NULL,
  label					varchar(64)		NOT NULL
)type=innodb;

ALTER TABLE llx_product_variant_lang ADD UNIQUE INDEX uk_product_variant_lang (fk_product_variant, lang);
ALTER TABLE llx_product_variant_lang ADD CONSTRAINT fk_product_variant_lang_fk_product_variant 	FOREIGN KEY (fk_product_variant) REFERENCES llx_product_variant (rowid);


alter table llx_societe add column   default_lang   varchar(6) after price_level;
alter table llx_socpeople add column   default_lang   varchar(6) after note;
