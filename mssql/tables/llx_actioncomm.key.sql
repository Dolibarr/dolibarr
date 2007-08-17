-- ============================================================================
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
-- ===========================================================================


CREATE INDEX idx_actioncomm_datea ON llx_actioncomm(datea);
CREATE INDEX idx_actioncomm_fk_soc ON llx_actioncomm(fk_soc);
CREATE INDEX idx_actioncomm_fk_contact ON llx_actioncomm(fk_contact);
CREATE INDEX idx_actioncomm_fk_facture ON llx_actioncomm(fk_facture);
