CREATE TABLE llx_holiday_logs 
(
rowid             INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
date_action       DATETIME NOT NULL ,
fk_user_action    INT( 11 ) NOT NULL ,
fk_user_update    INT( 11 ) NOT NULL ,
type_action       VARCHAR( 255 ) NOT NULL ,
prev_solde        VARCHAR( 255 ) NOT NULL ,
new_solde         VARCHAR( 255 ) NOT NULL
) 
ENGINE=innodb;