-- phpMyAdmin SQL Dump
-- version 2.6.2-Debian-3sarge1
-- http://www.phpmyadmin.net
-- 
-- Serveur: localhost
-- Généré le : Samedi 05 Août 2006 à 17:25
-- Version du serveur: 4.0.24
-- Version de PHP: 4.3.10-16
-- 

if not exists (select * from sysobjects where name='llx_osc_customer' and xtype='U')
CREATE TABLE llx_osc_customer
(
  osc_custid int PRIMARY KEY NOT NULL default 0,
  osc_lastmodif datetime default NULL,
  doli_socidp int UNIQUE NOT NULL default 0,
);
