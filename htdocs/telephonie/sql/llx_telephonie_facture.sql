-- ========================================================================
-- Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

create table llx_telephonie_facture (
  rowid             integer AUTO_INCREMENT PRIMARY KEY,
  fk_ligne          integer NOT NULL,
  ligne             varchar(255) NOT NULL,
  date              varchar(255) NOT NULL,
  fourn_montant     real,    -- montant donné par le fournisseur
  cout_achat        real,    -- cout calculé sur le fournisseur
  cout_vente        real,    -- cout de vente
  remise            real,
  cout_vente_remise real,
  gain              real,
  fk_facture        integer

)type=innodb;

--
--
ALTER TABLE llx_telephonie_facture ADD INDEX (fk_facture);
ALTER TABLE llx_telephonie_facture ADD INDEX (fk_ligne);

--
--
ALTER TABLE llx_telephonie_facture ADD FOREIGN KEY (fk_facture) 
REFERENCES llx_facture (rowid);

ALTER TABLE llx_telephonie_facture ADD FOREIGN KEY (fk_ligne) 
REFERENCES llx_telephonie_societe_ligne (rowid);
