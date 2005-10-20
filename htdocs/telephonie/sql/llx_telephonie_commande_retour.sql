-- ========================================================================
-- Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
create table llx_telephonie_commande_retour (
  rowid             integer AUTO_INCREMENT PRIMARY KEY,
  cli               varchar(255) NOT NULL,
  mode              varchar(255) NOT NULL,
  situation         varchar(255) NOT NULL,
  date_mise_service datetime,
  date_resiliation  datetime,
  motif_resiliation varchar(255) NOT NULL,
  commentaire       text NOT NULL,
  fichier           varchar(255) NOT NULL,
  traite            smallint DEFAULT 0,
  date_traitement   datetime,
  fk_fournisseur    integer NOT NULL

)type=innodb;
