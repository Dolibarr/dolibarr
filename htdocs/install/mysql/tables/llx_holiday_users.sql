CREATE TABLE llx_holiday_users 
(
fk_user     integer NOT NULL PRIMARY KEY,
nb_holiday   real NOT NULL DEFAULT '0'
) 
ENGINE=innodb;