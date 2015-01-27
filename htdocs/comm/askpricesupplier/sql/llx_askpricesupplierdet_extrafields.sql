CREATE TABLE `llx_askpricesupplierdet_extrafields` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_object` int(11) NOT NULL,
  `import_key` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_askpricesupplierdet_extrafields` (`fk_object`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8