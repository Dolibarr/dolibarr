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

-- Table with all fields description to be able to request it like dolibarr native extrafields
--
CREATE TABLE llx_schemas_field (
    rowid 			integer AUTO_INCREMENT PRIMARY KEY,
    name 			varchar(64) NOT NULL,
    entity 			integer DEFAULT 1 NOT NULL,
    label 			varchar(255) NOT NULL,
	-- there is no element type because there is only one def of schema
	-- and link will be on element_element
	-- like elementtype note some new type will become like note_audio ...
    type 			varchar(64),
    size 			varchar(8) DEFAULT NULL,
    fieldcomputed 	text,
    fielddefault 	text,
    fieldunique 	integer DEFAULT 0,
    fieldrequired 	integer DEFAULT,
    perms 			varchar(255),
    enabled 		varchar(255),
    pos 			integer DEFAULT 0,
    alwayseditable 	integer DEFAULT 0,
    emptyonclone 	integer DEFAULT 0,
    param 			text,
    list 			varchar(255) DEFAULT '1',
	printegerable 	integer DEFAULT 0,
	-- is the extrafield output on tooltip
	showintooltip	integer DEFAULT 0,
    totalizable 	boolean default false,
    langs 			varchar(64),
    help 			text,
	-- a prompt to autofill the value with AI
	aiprompt		text,
    css 			varchar(255),
    cssview 		varchar(255),
    csslist 		varchar(255),
	-- 1 if field contains personal data (GDPR/nLPD/LGPD)
    personal_data	integer DEFAULT 0,
	-- user making creation
	fk_user_author	integer,
	-- user making last change
	fk_user_modif	integer,
	-- link to schema
    fk_schema 		integer NOT NULL,
	-- date of creation
	datec			datetime,
	-- last modification date
	tms             timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=innodb;

