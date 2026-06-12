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

