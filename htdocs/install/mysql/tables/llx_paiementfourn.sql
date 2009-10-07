-- ===================================================================
-- Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
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
-- ===================================================================

create table llx_paiementfourn
(
  rowid             integer AUTO_INCREMENT PRIMARY KEY,
  tms               timestamp,
  datec             datetime,          -- date de creation de l'enregistrement
  fk_facture_fourn  integer,           -- facture
  datep             datetime,          -- date de paiement
  amount            real DEFAULT 0,    -- montant
  fk_user_author    integer,           -- auteur
  fk_paiement       integer NOT NULL,  -- moyen de paiement
  num_paiement      varchar(50),       -- numéro de paiement (cheque)
  note              text,
  fk_bank           integer NOT NULL,
  statut			smallint(6) NOT NULL DEFAULT 0
)type=innodb;
