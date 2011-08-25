-- ===================================================================
-- Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2005-2010 Regis Houssin        <regis@dolibarr.fr>
-- Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
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
-- $Id: llx_propaldet.sql,v 1.10 2011/08/08 01:53:25 eldy Exp $
-- ===================================================================

create table llx_propaldet
(
  rowid             integer AUTO_INCREMENT PRIMARY KEY,
  fk_propal         integer		NOT NULL,
  fk_parent_line	integer		NULL,
  fk_product        integer		NULL,
  description       text,
  fk_remise_except	integer      NULL,               -- Lien vers table des remises fixes
  tva_tx            double(6,3)  DEFAULT 0, 	     -- taux tva
  localtax1_tx      double(6,3)  DEFAULT 0,          -- localtax1 tax
  localtax2_tx      double(6,3)  DEFAULT 0,          -- localtax2 tax 
  qty               real,                            -- quantity
  remise_percent    real         DEFAULT 0,          -- pourcentage de remise
  remise            real         DEFAULT 0,          -- montant de la remise (obsolete)
  price             real,                            -- prix final (obsolete)
  subprice          double(24,8) DEFAULT 0,          -- prix unitaire article
  total_ht          double(24,8) DEFAULT 0,          -- Total HT de la ligne toute quantite et incluant remise ligne et globale
  total_tva         double(24,8) DEFAULT 0,          -- Total TVA de la ligne toute quantite et incluant remise ligne et globale
  total_localtax1   double(24,8) DEFAULT 0,          -- Total localtax1
  total_localtax2   double(24,8) DEFAULT 0,          -- Total localtax2
  total_ttc         double(24,8) DEFAULT 0,          -- Total TTC de la ligne toute quantite et incluant remise ligne et globale
  product_type		integer    DEFAULT 0,
  date_start        datetime   DEFAULT NULL,         -- date debut si service
  date_end          datetime   DEFAULT NULL,         -- date fin si service
  info_bits		    integer      DEFAULT 0,          -- TVA NPR ou non
  
  pa_ht             double(24,8) DEFAULT 0,          -- prix d'achat HT
  marge_tx          double(6,3)  DEFAULT 0,          -- taux de marge (marge sur prix d'achat)
  marque_tx         double(6,3)  DEFAULT 0,          -- taux de marque (marge sur prix de vente)

  special_code      integer      DEFAULT 0,          -- code pour les lignes speciales
  rang              integer      DEFAULT 0           -- ordre affichage sur la propal
)ENGINE=innodb;

-- 
-- Liste des codes pour special_code
--
-- 1 : frais de port
-- 2 : ecotaxe
-- 3 : produit/service propose en option
--