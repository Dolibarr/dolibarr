-- phpMyAdmin SQL Dump
-- version 2.6.2-Debian-3sarge3
-- http://www.phpmyadmin.net
-- 
-- Serveur: localhost
-- Généré le : Mercredi 20 Juin 2007 à 15:13
-- Version du serveur: 4.0.24
-- Version de PHP: 4.3.10-19
-- 
-- Base de données: `dolidev`
-- 

-- --------------------------------------------------------

-- 
-- Structure de la table `llx_osc_categories`
-- 

CREATE TABLE llx_osc_categories (
  rowid int(11) unsigned NOT NULL auto_increment,
  dolicatid int(11) NOT NULL default '0',
  osccatid int(11) NOT NULL default '0',
  PRIMARY KEY  (rowid),
  UNIQUE KEY dolicatid (dolicatid),
  UNIQUE KEY osccatid (osccatid)
) TYPE=InnoDB COMMENT='Correspondance categorie Dolibarr categorie OSC';
