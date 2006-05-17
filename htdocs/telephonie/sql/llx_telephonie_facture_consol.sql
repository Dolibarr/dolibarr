-- ========================================================================
-- Copyright (C) 2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 2 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program; if not, write to the Free Software
-- Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
--
-- $Id$
-- $Source$
--
-- ========================================================================
--
-- Consolidation des factures de téléphonie
--

create table llx_telephonie_facture_consol (
  groupe            varchar(255),
  agence            varchar(255),
  ligne             varchar(255) NOT NULL,
  statut            varchar(255) NOT NULL,
  fixe_m0           real,
  mobi_m0           real,
  paye_m0           enum('oui','non'),
  fixe_m1           real,
  mobi_m1           real,
  paye_m1           enum('oui','non'),
  fixe_m2           real,
  mobi_m2           real,
  paye_m2           enum('oui','non'),
  fixe_m3           real,
  mobi_m3           real,
  paye_m3           enum('oui','non'),
  fixe_m4           real,
  mobi_m4           real,
  paye_m4           enum('oui','non'),
  fixe_m5           real,
  mobi_m5           real,
  paye_m5           enum('oui','non'),
  fixe_m6           real,
  mobi_m6           real,
  paye_m6           enum('oui','non'),
  repre             varchar(255),
  distri            varchar(255),
  repre_ib          varchar(255)
  
)type=innodb;

