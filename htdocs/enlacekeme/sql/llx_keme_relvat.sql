CREATE TABLE IF NOT EXISTS `llx_keme_relvat` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ref` varchar(5) NOT NULL,
  `fk_vat` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fk_vat` (`fk_vat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE `llx_keme_relvat`
  ADD CONSTRAINT `llx_keme_relvat_ibfk_1` FOREIGN KEY (`fk_vat`) REFERENCES `llx_c_tva` (`rowid`);

ALTER TABLE llx_bank_account modify account_number varchar(24);