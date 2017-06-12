
CREATE TABLE llx_blockedlog 
( 
	rowid integer AUTO_INCREMENT PRIMARY KEY, 
	tms	timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	action varchar(50), 
	amounts real NOT NULL, 
	signature varchar(100) NOT NULL, 
	signature_line varchar(100) NOT NULL, 
	element varchar(50), 
	fk_object integer,
	ref_object varchar(100), 
	date_object	datetime,
	object_data	text,
	fk_user	integer,
	entity integer DEFAULT 1 NOT NULL, 
	certified integer
) ENGINE=innodb;

