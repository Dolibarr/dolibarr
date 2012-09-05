CREATE TABLE llx_holiday_config 
(
rowid    INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY,
name     VARCHAR( 255 ) NOT NULL UNIQUE,
value    TEXT NULL
) 
ENGINE=innodb;