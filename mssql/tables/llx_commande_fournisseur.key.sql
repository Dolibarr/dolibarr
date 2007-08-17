-- ============================================================================
-- Copyright (C) 2006 Laurent Destailleur  <eldy@users.sourceforge.net>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General [public] License as published by
-- the Free Software Foundation; either version 2 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General [public] License for more details.
--
-- You should have received a copy of the GNU General [public] License
-- along with this program; if not, write to the Free Software
-- Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
--
-- $Id$
-- $Source$
--
-- ============================================================================


-- Supprimme orphelins pour permettre montée de la clé
-- V4 DELETE llx_commande_fournisseur FROM llx_commande_fournisseur LEFT JOIN llx_societe ON llx_commande_fournisseur.fk_soc = llx_societe.idp WHERE llx_societe.idp IS NULL; 

CREATE INDEX idx_commande_fournisseur_fk_soc ON llx_commande_fournisseur(fk_soc);

ALTER TABLE llx_commande_fournisseur ADD CONSTRAINT fk_commande_fournisseur_societe FOREIGN KEY (fk_soc) REFERENCES llx_societe (idp);
