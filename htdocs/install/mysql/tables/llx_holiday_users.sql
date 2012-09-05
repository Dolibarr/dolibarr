CREATE TABLE llx_holiday_users 
(
fk_user     INT( 11 ) NOT NULL PRIMARY KEY,
nb_holiday   FLOAT( 5 ) NOT NULL DEFAULT '0'
) 
ENGINE=innodb;