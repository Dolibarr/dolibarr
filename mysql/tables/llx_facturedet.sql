-- ===================================================================
-- Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
-- ===================================================================


create table llx_facturedet
(
  rowid               integer    AUTO_INCREMENT PRIMARY KEY,
  fk_facture          integer    NOT NULL,
  fk_product          integer    NULL,      	       -- Doit pouvoir etre nul pour ligne detail sans produits
  description         text,
  tva_taux            real, 				                 -- Taux tva produit/service (exemple 19.6)
  qty                 real,              	           -- Quantité (exemple 2)
  remise_percent      real       DEFAULT 0,    	     -- % de la remise ligne (exemple 20%)
  remise              real       DEFAULT 0,    	     -- Montant calculé de la remise % sur PU HT (exemple 20)
  fk_remise_except    integer    NULL,    	         -- Lien vers table des remises fixes
  subprice            real,              	           -- P.U. HT (exemple 100)
  price               real,              	           -- P.U. HT apres remise % de ligne
  total_ht            real,	             	           -- Total HT de la ligne toute quantité et incluant remise ligne et globale
  total_tva           real,	             	           -- Total TVA de la ligne toute quantité et incluant remise ligne et globale
  total_ttc           real,	             	           -- Total TTC de la ligne toute quantité et incluant remise ligne et globale
  date_start          datetime,          	           -- date debut si service
  date_end            datetime,                      -- date fin si service
  info_bits		        integer    DEFAULT 0, 	       -- TVA NPR ou non
  fk_code_ventilation integer    DEFAULT 0 NOT NULL,
  fk_export_compta    integer    DEFAULT 0 NOT NULL,
  special_code        tinyint(4) UNSIGNED DEFAULT 0, -- code pour les lignes speciales
  rang                integer    DEFAULT 0           -- ordre d'affichage
)type=innodb;

-- 
-- Liste des codes pour special_code
--
-- 1 : frais de port
-- 2 : ecotaxe
--