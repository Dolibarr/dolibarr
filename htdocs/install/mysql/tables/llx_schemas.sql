--
CREATE TABLE llx_schemas (
    rowid INT AUTO_INCREMENT PRIMARY KEY,
	-- uuid from json file
    uuid VARCHAR(64) NOT NULL,
	-- name of object like keyboard, mouse, screen ...
    name VARCHAR(64) NOT NULL,
	-- name displayed to user
    label VARCHAR(255) NOT NULL,
	-- type of object (dolibarr element)
    elementtype VARCHAR(64) NOT NULL,
	-- kind of schema (family like "cerfa")
    schema_kind VARCHAR(32) NULL,
	-- long description
    description TEXT COMMENT,
	-- json for meta object composed of other parts
	-- note even if that is a json store is TEXT for "old" version of mariadb
    composed_of TEXT NULL,
	-- orignal json in case of remote deleted file (for example)
    json_schema TEXT COMMENT,
	-- 0 disabled / 1 enabled
    active TINYINT DEFAULT 1 NOT NULL,
    date_creation DATETIME NOT NULL,
    tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    fk_user_creat INT,
    fk_user_modif INT,
    entity INT DEFAULT 1 NOT NULL
) ENGINE=InnoDB;

-- Table with all fields description to be able to request it like dolibarr native extrafields
--
CREATE TABLE llx_schemas_field (
    rowid INT AUTO_INCREMENT PRIMARY KEY,
	-- link to schema
    fk_schema INT NOT NULL,
    entity INT DEFAULT 1 NOT NULL,
    name VARCHAR(64) NOT NULL,
    label VARCHAR(255) NOT NULL,
    type VARCHAR(64) NOT NULL,
    size VARCHAR(8),
    pos INT DEFAULT 0 NOT NULL,
    visible TINYINT DEFAULT 0 NOT NULL,
    fieldunique TINYINT DEFAULT 0 NOT NULL,
    fieldrequired TINYINT DEFAULT 0 NOT NULL,
    fielddefault VARCHAR(255),
    fieldcomputed TEXT,
    perms VARCHAR(255),
    enabled VARCHAR(255) DEFAULT '1',
    list VARCHAR(24) DEFAULT '1',
    printable TINYINT DEFAULT 0,
    totalizable TINYINT DEFAULT 0 NOT NULL,
    alwayseditable TINYINT DEFAULT 0 NOT NULL,
    emptyonclone TINYINT DEFAULT 0 NOT NULL,
    param TEXT,
    help TEXT,
    langs VARCHAR(64),
    css VARCHAR(255),
    cssview VARCHAR(255),
    csslist VARCHAR(255),
    hidden TINYINT DEFAULT 0 COMMENT 'cache'
) ENGINE=InnoDB;

