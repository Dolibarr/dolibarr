-- ===================================================================
-- Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2008 Regis Houssin        <regis@dolibarr.fr>
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

create table llx_livraisondet
(
  rowid             integer AUTO_INCREMENT PRIMARY KEY,
  fk_livraison      integer,
  fk_origin_line    integer,                         -- Correspondance de la ligne avec le document d'origine (propal, commande)
  fk_product        integer,
  description       text,
  qty               real,                            -- quantité
  subprice          double(24,8) DEFAULT 0,          -- prix unitaire
  total_ht          double(24,8) DEFAULT 0,          -- Total HT de la ligne toute quantité
  rang              integer      DEFAULT 0
)type=innodb;
