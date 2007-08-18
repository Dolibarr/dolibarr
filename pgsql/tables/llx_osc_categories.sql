-- Generated from dolibarr_mysql2pgsql
-- (c) 2004, PostgreSQL Inc.
-- (c) 2005, Laurent Destailleur.

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
  rowid SERIAL PRIMARY KEY,
  "dolicatid" int4 NOT NULL default '0',
  "osccatid" int4 NOT NULL default '0',
  UNIQUE(dolicatid),
  UNIQUE(osccatid)
) TYPE=InnoDB COMMENT='Correspondance categorie Dolibarr categorie OSC';

CREATE INDEX idx_llx_osc_categories_rowid ON llx_osc_categories (rowid);
CREATE INDEX dolicatid ON llx_osc_categories (dolicatid);
CREATE INDEX osccatid ON llx_osc_categories (osccatid);
