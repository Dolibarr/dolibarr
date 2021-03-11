--
--
-- To add a column:         ALTER TABLE llx_table ADD COLUMN newcol varchar(60) NOT NULL DEFAULT '0' AFTER existingcol;
-- To rename a column:      ALTER TABLE llx_table CHANGE COLUMN oldname newname varchar(60);
-- To change type of field: ALTER TABLE llx_table MODIFY name varchar(60);
--

create table llx_entity_thirdparty
(
  rowid				integer AUTO_INCREMENT PRIMARY KEY,
  entity			integer DEFAULT 1 NOT NULL,	-- multi company id
  fk_entity			integer NOT NULL,
  fk_soc			integer NOT NULL
  
) ENGINE=innodb;

ALTER TABLE llx_entity_thirdparty ADD UNIQUE INDEX idx_entity_thirdparty_fk_soc (entity, fk_entity, fk_soc);

ALTER TABLE llx_entity_thirdparty ADD CONSTRAINT fk_entity_thirdparty_fk_entity FOREIGN KEY (fk_entity) REFERENCES llx_entity (rowid);
ALTER TABLE llx_entity_thirdparty ADD CONSTRAINT fk_entity_thirdparty_fk_soc FOREIGN KEY (fk_soc) REFERENCES llx_societe (rowid);