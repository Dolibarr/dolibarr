CREATE TABLE llx_holiday 
(
rowid          INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY,
fk_user        INT( 11 ) NOT NULL ,
date_create    DATETIME NOT NULL ,
description    VARCHAR( 255 ) NOT NULL ,
date_debut     DATE NOT NULL ,
date_fin       DATE NOT NULL ,
statut         INT( 11 ) NOT NULL DEFAULT '1',
fk_validator   INT( 11 ) NOT NULL ,
date_valid     DATETIME NULL DEFAULT NULL ,
fk_user_valid  INT( 11 ) NULL DEFAULT NULL ,
date_refuse    DATETIME NULL DEFAULT NULL ,
fk_user_refuse INT( 11 ) NULL DEFAULT NULL ,
date_cancel    DATETIME NULL DEFAULT NULL ,
fk_user_cancel INT( 11 ) NULL DEFAULT NULL,
detail_refuse  varchar( 250 ) NULL DEFAULT NULL
) 
ENGINE=innodb;