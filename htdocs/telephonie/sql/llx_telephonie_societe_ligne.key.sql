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
--
-- Statut des lignes
--
-- 0 a commander
-- 1 commandée
-- 2 recue
-- 3 probleme
--

ALTER TABLE llx_telephonie_societe_ligne ADD INDEX (fk_fournisseur);
ALTER TABLE llx_telephonie_societe_ligne ADD INDEX (fk_client_comm);
ALTER TABLE llx_telephonie_societe_ligne ADD INDEX (fk_soc);
ALTER TABLE llx_telephonie_societe_ligne ADD INDEX (fk_soc_facture);
ALTER TABLE llx_telephonie_societe_ligne ADD INDEX (fk_user_creat);
ALTER TABLE llx_telephonie_societe_ligne ADD INDEX (fk_user_commande);
ALTER TABLE llx_telephonie_societe_ligne ADD INDEX (fk_commercial);
ALTER TABLE llx_telephonie_societe_ligne ADD INDEX (fk_concurrent);

ALTER TABLE llx_telephonie_societe_ligne ADD CONSTRAINT llx_telephonie_societe_ligne_fournisseur FOREIGN KEY (fk_fournisseur) REFERENCES llx_telephonie_fournisseur (rowid);
ALTER TABLE llx_telephonie_societe_ligne ADD CONSTRAINT llx_telephonie_societe_ligne_client_comm FOREIGN KEY (fk_client_comm) REFERENCES llx_societe(rowid);
ALTER TABLE llx_telephonie_societe_ligne ADD CONSTRAINT llx_telephonie_societe_ligne_soc         FOREIGN KEY (fk_soc)         REFERENCES llx_societe(rowid);
ALTER TABLE llx_telephonie_societe_ligne ADD CONSTRAINT llx_telephonie_societe_ligne_soc_facture FOREIGN KEY (fk_soc_facture) REFERENCES llx_societe(rowid);
ALTER TABLE llx_telephonie_societe_ligne ADD CONSTRAINT llx_telephonie_societe_ligne_user_creat  FOREIGN KEY (fk_user_creat)  REFERENCES llx_user(rowid);
ALTER TABLE llx_telephonie_societe_ligne ADD CONSTRAINT llx_telephonie_societe_ligne_user_commande FOREIGN KEY (fk_user_commande) REFERENCES llx_user(rowid);
ALTER TABLE llx_telephonie_societe_ligne ADD CONSTRAINT llx_telephonie_societe_ligne_commercial  FOREIGN KEY (fk_commercial)  REFERENCES llx_user(rowid);
ALTER TABLE llx_telephonie_societe_ligne ADD CONSTRAINT llx_telephonie_societe_ligne_concurrent  FOREIGN KEY (fk_concurrent)  REFERENCES llx_telephonie_concurrents (rowid);
