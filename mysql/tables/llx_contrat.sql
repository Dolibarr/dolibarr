-- ============================================================================
-- Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
-- ============================================================================

create table llx_contrat
(
  rowid           integer AUTO_INCREMENT PRIMARY KEY,  
  ref         	  varchar(30),		-- reference de contrat
  tms             timestamp,
  datec           datetime, -- date de creation de l'enregistrement
  date_contrat    datetime,
  statut          smallint DEFAULT 0,
  mise_en_service datetime,
  fin_validite    datetime,
  date_cloture    datetime,
  fk_soc          integer NOT NULL,
  fk_projet       integer,
  fk_commercial_signature integer NOT NULL,  	-- obsolete
  fk_commercial_suivi     integer NOT NULL,	-- obsolete
  fk_user_author          integer NOT NULL default 0,
  fk_user_mise_en_service integer,
  fk_user_cloture integer,
  note                text,
  note_public         text
)type=innodb;

