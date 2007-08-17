-- ============================================================================
-- Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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


CREATE INDEX idx_contratdet_fk_contrat ON llx_contratdet(fk_contrat);
CREATE INDEX idx_contratdet_fk_product ON llx_contratdet(fk_product);
CREATE INDEX idx_contratdet_date_ouverture_prevue ON llx_contratdet(date_ouverture_prevue);
CREATE INDEX idx_contratdet_date_ouverture ON llx_contratdet(date_ouverture);
CREATE INDEX idx_contratdet_date_fin_validite ON llx_contratdet(date_fin_validite);

ALTER TABLE llx_contratdet ADD CONSTRAINT fk_contratdet_fk_contrat FOREIGN KEY (fk_contrat) REFERENCES llx_contrat (rowid);
ALTER TABLE llx_contratdet ADD CONSTRAINT fk_contratdet_fk_product FOREIGN KEY (fk_product) REFERENCES llx_product (rowid);
