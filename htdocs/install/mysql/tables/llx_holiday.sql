CREATE TABLE llx_holiday 
(
rowid          integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
fk_user        integer NOT NULL,
date_create    DATETIME NOT NULL,
description    VARCHAR( 255 ) NOT NULL,
date_debut     DATE NOT NULL,
date_fin       DATE NOT NULL,
statut         integer NOT NULL DEFAULT '1',
fk_validator   integer NOT NULL,
date_valid     DATETIME DEFAULT NULL,
fk_user_valid  integer DEFAULT NULL,
date_refuse    DATETIME DEFAULT NULL,
fk_user_refuse integer DEFAULT NULL,
date_cancel    DATETIME DEFAULT NULL,
fk_user_cancel integer DEFAULT NULL,
detail_refuse  varchar( 250 ) DEFAULT NULL
) 
ENGINE=innodb;