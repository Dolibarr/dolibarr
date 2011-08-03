-- ===========================================================================
-- Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2006 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2009 Regis Houssin        <regis@dolibarr.fr>
-- Copyright (C) 2010 Juanjo Menent        <jmenent@2byte.es>
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
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
-- $Id: llx_facture_rec.sql,v 1.9 2011/08/03 01:25:27 eldy Exp $
-- ===========================================================================

create table llx_facture_rec
(
  rowid              integer AUTO_INCREMENT PRIMARY KEY,
  titre              varchar(50) NOT NULL,
  entity             integer DEFAULT 1 NOT NULL,	 -- multi company id
  fk_soc             integer NOT NULL,
  datec              datetime,  -- date de creation

  amount             double(24,8)     DEFAULT 0 NOT NULL,
  remise             real     DEFAULT 0,
  remise_percent     real     DEFAULT 0,
  remise_absolue     real     DEFAULT 0,
  tva                double(24,8)     DEFAULT 0,
  localtax1			 double(24,8)     DEFAULT 0,           -- amount localtax1
  localtax2          double(24,8)     DEFAULT 0,           -- amount localtax2
  total              double(24,8)     DEFAULT 0,
  total_ttc          double(24,8)     DEFAULT 0,

  fk_user_author     integer,             -- createur
  fk_projet          integer,             -- projet auquel est associe la facture
  fk_cond_reglement  integer DEFAULT 0,   -- condition de reglement
  fk_mode_reglement  integer DEFAULT 0,  -- mode de reglement (Virement, Prelevement)
  date_lim_reglement date,               -- date limite de reglement

  note               text,
  note_public        text,

  frequency          integer,
  unit_frequency     varchar(2) DEFAULT 'd',
  date_when          datetime DEFAULT NULL,
  date_last_gen      datetime DEFAULT NULL,
  nb_gen_done        integer DEFAULT NULL,
  nb_gen_max         integer DEFAULT NULL
)ENGINE=innodb;
