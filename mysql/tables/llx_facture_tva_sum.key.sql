-- ===================================================================
-- Copyright (C) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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


-- Supprimme orhpelins pour permettre montée de la clé
-- V4 DELETE llx_facture_tva_sum FROM llx_facture_tva_sum LEFT JOIN llx_facture ON llx_facture_tva_sum.fk_facture = llx_facture.rowid WHERE llx_facture.rowid IS NULL;


ALTER TABLE llx_facture_tva_sum ADD INDEX idx_facture_tva_sum_fk_facture (fk_facture);
ALTER TABLE llx_facture_tva_sum ADD CONSTRAINT fk_facture_tva_sum_fk_facture FOREIGN KEY (fk_facture) REFERENCES llx_facture (rowid);
