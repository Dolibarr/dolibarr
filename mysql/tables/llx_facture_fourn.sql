-- ===========================================================================
-- Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2007      Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2007-2009 Regis Houssin        <regis@dolibarr.fr>
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
-- ===========================================================================

create table llx_facture_fourn
(
  rowid               integer AUTO_INCREMENT PRIMARY KEY,
  facnumber           varchar(50) NOT NULL,
  entity              integer  DEFAULT 1 NOT NULL,	 -- multi company id
  type		            smallint DEFAULT 0 NOT NULL,
  fk_soc              integer NOT NULL,
  
  datec               datetime,                      -- date de creation de la facture
  datef               date,                          -- date de la facture
  libelle             varchar(255),
  paye                smallint         DEFAULT 0 NOT NULL,
  amount              double(24,8)     DEFAULT 0 NOT NULL,
  remise              double(24,8)     DEFAULT 0,
  tva                 double(24,8)     DEFAULT 0,
  total               double(24,8)     DEFAULT 0,
  total_ht            double(24,8)     DEFAULT 0,
  total_tva           double(24,8)     DEFAULT 0,
  total_ttc           double(24,8)     DEFAULT 0,

  fk_statut           smallint DEFAULT 0 NOT NULL,

  fk_user_author      integer,                       -- createur de la facture
  fk_user_valid       integer,                       -- valideur de la facture

  fk_projet           integer,                       -- projet auquel est associée la facture

  fk_cond_reglement   integer  DEFAULT 1 NOT NULL,   -- condition de reglement (30 jours, fin de mois ...)
  date_lim_reglement  date,                          -- date limite de reglement

  note                text,
  note_public         text,
  import_key          varchar(14)
  
)type=innodb;
