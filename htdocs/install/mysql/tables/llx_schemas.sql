--
CREATE TABLE llx_schemas (
    rowid 			integer AUTO_INCREMENT PRIMARY KEY,
	-- uuid from json file
    uuid 			varchar(64) NOT NULL,
	-- name of object like keyboard, mouse, screen ...
    name 			varchar(64) NOT NULL,
	-- name displayed to user
    label 			varchar(255) NOT NULL,
	-- kind of schema (family like "cerfa")
    schema_kind 	varchar(32) NULL,
	-- long description
    description 	text,
	-- json for meta object composed of other parts
	-- note even if that is a json store is TEXT for "old" version of mariadb
    composed_of 	JSON NULL,
	-- orignal json in case of remote deleted file (for example)
    json_schema 	JSON,
	-- 0 disabled / 1 enabled
    active 			integer DEFAULT 1 NOT NULL,
	-- note : version is in json and parent uuid is in json too
    date_creation 	datetime NOT NULL,
    tms 			timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    fk_user_creat 	integer,
    fk_user_modif 	integer,
    entity 			integer DEFAULT 1 NOT NULL
) ENGINE=innodb;
