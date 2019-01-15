CREATE TABLE llx_blockedlog_authority 
( 
	rowid integer AUTO_INCREMENT PRIMARY KEY, 
	blockchain longtext NOT NULL,
	signature varchar(100) NOT NULL,
	tms timestamp
) ENGINE=innodb;
