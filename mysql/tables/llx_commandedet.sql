-- ===================================================================
-- Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2006-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
-- ===================================================================

create table llx_commandedet
(
  rowid              integer AUTO_INCREMENT PRIMARY KEY,
  fk_commande        integer,
  fk_product         integer,
  description        text,
  tva_tx             double(6,3),		                  -- taux tva
  qty                real,                            -- quantité
  remise_percent     real         DEFAULT 0,          -- pourcentage de remise
  remise             real         DEFAULT 0,          -- montant de la remise
  fk_remise_except	 integer      NULL,               -- Lien vers table des remises fixes
  price              real,                            -- prix final
  subprice           double(24,8) DEFAULT 0,          -- prix unitaire
  total_ht           double(24,8) DEFAULT 0,          -- Total HT de la ligne toute quantité et incluant remise ligne et globale
  total_tva          double(24,8) DEFAULT 0,          -- Total TVA de la ligne toute quantité et incluant remise ligne et globale
  total_ttc          double(24,8) DEFAULT 0,          -- Total TTC de la ligne toute quantité et incluant remise ligne et globale
  info_bits		       integer      DEFAULT 0,          -- TVA NPR ou non
  marge_tx           double(6,3)  DEFAULT 0,          -- taux de marge (marge sur prix d'achat)
  marque_tx          double(6,3)  DEFAULT 0,          -- taux de marque (marge sur prix de vente)
  special_code       tinyint(4)   UNSIGNED DEFAULT 0, -- code pour les lignes speciales
  rang               integer      DEFAULT 0
)type=innodb;

-- 
-- Liste des codes pour special_code
--
-- 1 : frais de port
-- 2 : ecotaxe
--