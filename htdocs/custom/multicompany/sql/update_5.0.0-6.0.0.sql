--
--
-- To add a column:         ALTER TABLE llx_table ADD COLUMN newcol varchar(60) NOT NULL DEFAULT '0' AFTER existingcol;
-- To rename a column:      ALTER TABLE llx_table CHANGE COLUMN oldname newname varchar(60);
-- To change type of field: ALTER TABLE llx_table MODIFY name varchar(60);
--

UPDATE llx_const SET name = __ENCRYPT('MULTICOMPANY_THIRDPARTY_SHARING_ENABLED')__ WHERE name = __ENCRYPT('MULTICOMPANY_SOCIETE_SHARING_ENABLED')__;
UPDATE llx_const SET name = __ENCRYPT('MULTICOMPANY_BANKACCOUNT_SHARING_ENABLED')__ WHERE name = __ENCRYPT('MULTICOMPANY_BANK_ACCOUNT_SHARING_ENABLED')__;

ALTER TABLE llx_entity ADD COLUMN rang smallint DEFAULT 0 NOT NULL;

ALTER TABLE llx_entity MODIFY COLUMN visible tinyint DEFAULT 1 NOT NULL;
ALTER TABLE llx_entity MODIFY COLUMN active tinyint DEFAULT 1 NOT NULL;