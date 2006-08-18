-- phpMyAdmin SQL Dump
-- version 2.6.2-Debian-3sarge1
-- http://www.phpmyadmin.net
-- 
-- Serveur: localhost
-- Généré le : Samedi 05 Août 2006 à 17:25
-- Version du serveur: 4.0.24
-- Version de PHP: 4.3.10-16
-- 

CREATE TABLE IF NOT EXISTS `llx_osc_customer` (
  `osc_custid` int(11) NOT NULL default '0',
  `osc_lastmodif` datetime default NULL,
  `doli_socidp` int(11) NOT NULL default '0',
  PRIMARY KEY  (`osc_custid`),
  UNIQUE KEY `doli_socidp` (`doli_socidp`)
) TYPE=InnoDB COMMENT='Table transition client OSC - societe Dolibarr';
