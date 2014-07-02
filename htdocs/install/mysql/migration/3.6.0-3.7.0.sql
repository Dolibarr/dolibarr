-- Added missing relations of llx_product
-- fk_country
ALTER TABLE  llx_product CHANGE  fk_country  fk_country INT( 11 ) NULL DEFAULT NULL;
ALTER TABLE  llx_product ADD INDEX (  fk_country );
ALTER TABLE  llx_product ADD FOREIGN KEY (  fk_country ) REFERENCES  llx_c_pays (
rowid
) ON DELETE RESTRICT ON UPDATE RESTRICT ;