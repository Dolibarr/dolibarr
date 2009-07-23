-- ============================================================================
-- Copyright (C) 2004 Benoit Mortier <benoit.mortier@opensides.be>
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
--
-- ============================================================================

CREATE OR REPLACE FUNCTION UNIX_TIMESTAMP(TIMESTAMP WITHOUT TIME ZONE)
RETURNS BIGINT
LANGUAGE SQL
IMMUTABLE STRICT
AS 'SELECT EXTRACT(EPOCH FROM $1)::bigint;';

CREATE OR REPLACE FUNCTION UNIX_TIMESTAMP(TIMESTAMP WITH TIME ZONE)
RETURNS BIGINT
LANGUAGE SQL
IMMUTABLE STRICT
AS 'SELECT EXTRACT(EPOCH FROM $1)::bigint;';
 
CREATE OR REPLACE FUNCTION FROM_UNIXTIME(BIGINT, VARCHAR)
RETURNS TIMESTAMP WITH TIME ZONE
LANGUAGE SQL
IMMUTABLE STRICT
AS 'SELECT TIMESTAMP WITH TIME ZONE \'epoch\' + $1 * interval \'1 second\' ;';