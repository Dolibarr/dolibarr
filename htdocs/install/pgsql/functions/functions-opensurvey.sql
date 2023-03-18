-- ============================================================================
-- Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <https://www.gnu.org/licenses/>.
--
-- ============================================================================

CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_opensurvey_comments FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_opensurvey_sondage FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_opensurvey_user_studs FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
