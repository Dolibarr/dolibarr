--
CREATE TABLE llx_schemas (
    rowid 			integer AUTO_INCREMENT PRIMARY KEY,
    uuid 			varchar(64) NOT NULL,				-- uuid from json file
    name 			varchar(64) NOT NULL,				-- name of object like keyboard, mouse, screen ...
    label 			varchar(255) NOT NULL,				-- name displayed to user
    schema_kind 	varchar(32) NULL,					-- kind of schema (family like "cerfa")
    description 	text,								-- long description
    composed_of 	JSON NULL,							-- json for meta object composed of other parts note even if that is a json store is TEXT for "old" version of mariadb
    json_schema 	JSON,								-- orignal json in case of remote deleted file (for example)
    active 			integer DEFAULT 1 NOT NULL,			-- 0 disabled / 1 enabled
    date_creation 	datetime NOT NULL,
    tms 			timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    fk_user_creat 	integer,
    fk_user_modif 	integer,
    entity 			integer DEFAULT 1 NOT NULL
) ENGINE=innodb;
