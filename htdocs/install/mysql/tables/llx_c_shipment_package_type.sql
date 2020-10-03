--Dictionary of package type
create table llx_c_shipment_package_type
(
    rowid        integer  AUTO_INCREMENT PRIMARY KEY,
    label        varchar(50) NOT NULL,  -- Short name
    description	 varchar(255), -- Description
    active       integer DEFAULT 1 NOT NULL, -- Active or not	
    entity       integer DEFAULT 1 NOT NULL -- Multi company id 
)ENGINE=innodb;
