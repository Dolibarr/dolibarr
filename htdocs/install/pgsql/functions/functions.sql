-- ============================================================================
-- Copyright (C) 2004 Benoit Mortier       <benoit.mortier@opensides.be>
-- Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
-- ============================================================================

CREATE LANGUAGE plpgsql;


CREATE OR REPLACE FUNCTION UNIX_TIMESTAMP(TIMESTAMP WITHOUT TIME ZONE) RETURNS BIGINT LANGUAGE SQL IMMUTABLE STRICT AS 'SELECT EXTRACT(EPOCH FROM $1)::bigint;';

CREATE OR REPLACE FUNCTION UNIX_TIMESTAMP(TIMESTAMP WITH TIME ZONE) RETURNS BIGINT LANGUAGE SQL IMMUTABLE STRICT AS 'SELECT EXTRACT(EPOCH FROM $1)::bigint;';
 
CREATE OR REPLACE FUNCTION date_format(timestamp without time zone, text) RETURNS text AS $$ DECLARE i int := 1; temp text := ''; c text; n text; res text; BEGIN WHILE i <= pg_catalog.length($2) LOOP c := SUBSTRING ($2 FROM i FOR 1); IF c = '%' AND i != pg_catalog.length($2) THEN n := SUBSTRING ($2 FROM (i + 1) FOR 1); SELECT INTO res CASE WHEN n = 'a' THEN pg_catalog.to_char($1, 'Dy') WHEN n = 'b' THEN pg_catalog.to_char($1, 'Mon') WHEN n = 'c' THEN pg_catalog.to_char($1, 'FMMM') WHEN n = 'D' THEN pg_catalog.to_char($1, 'FMDDth') WHEN n = 'd' THEN pg_catalog.to_char($1, 'DD') WHEN n = 'e' THEN pg_catalog.to_char($1, 'FMDD') WHEN n = 'f' THEN pg_catalog.to_char($1, 'US') WHEN n = 'H' THEN pg_catalog.to_char($1, 'HH24') WHEN n = 'h' THEN pg_catalog.to_char($1, 'HH12') WHEN n = 'I' THEN pg_catalog.to_char($1, 'HH12') WHEN n = 'i' THEN pg_catalog.to_char($1, 'MI') WHEN n = 'j' THEN pg_catalog.to_char($1, 'DDD') WHEN n = 'k' THEN pg_catalog.to_char($1, 'FMHH24') WHEN n = 'l' THEN pg_catalog.to_char($1, 'FMHH12') WHEN n = 'M' THEN pg_catalog.to_char($1, 'FMMonth') WHEN n = 'm' THEN pg_catalog.to_char($1, 'MM') WHEN n = 'p' THEN pg_catalog.to_char($1, 'AM') WHEN n = 'r' THEN pg_catalog.to_char($1, 'HH12:MI:SS AM') WHEN n = 'S' THEN pg_catalog.to_char($1, 'SS') WHEN n = 's' THEN pg_catalog.to_char($1, 'SS') WHEN n = 'T' THEN pg_catalog.to_char($1, 'HH24:MI:SS') WHEN n = 'U' THEN pg_catalog.to_char($1, '?') WHEN n = 'u' THEN pg_catalog.to_char($1, '?') WHEN n = 'V' THEN pg_catalog.to_char($1, '?') WHEN n = 'v' THEN pg_catalog.to_char($1, '?') WHEN n = 'W' THEN pg_catalog.to_char($1, 'FMDay') WHEN n = 'w' THEN EXTRACT(DOW FROM $1)::text WHEN n = 'X' THEN pg_catalog.to_char($1, '?') WHEN n = 'x' THEN pg_catalog.to_char($1, '?') WHEN n = 'Y' THEN pg_catalog.to_char($1, 'YYYY') WHEN n = 'y' THEN pg_catalog.to_char($1, 'YY') WHEN n = '%' THEN pg_catalog.to_char($1, '%') ELSE NULL END; temp := temp operator(pg_catalog.||) res; i := i + 2; ELSE temp = temp operator(pg_catalog.||) c; i := i + 1; END IF; END LOOP; RETURN temp; END $$ IMMUTABLE STRICT LANGUAGE PLPGSQL;


CREATE OR REPLACE FUNCTION YEAR(TIMESTAMP without TIME ZONE) RETURNS INTEGER AS $$ SELECT EXTRACT(YEAR FROM $1)::INTEGER; $$ LANGUAGE SQL IMMUTABLE;

CREATE OR REPLACE FUNCTION YEAR(TIMESTAMP WITH TIME ZONE) RETURNS INTEGER AS $$ SELECT EXTRACT(YEAR FROM $1)::INTEGER; $$ LANGUAGE SQL STABLE;

CREATE OR REPLACE FUNCTION YEAR(DATE) RETURNS INTEGER AS $$ SELECT EXTRACT(YEAR FROM $1)::INTEGER; $$ LANGUAGE SQL IMMUTABLE;


CREATE OR REPLACE FUNCTION MONTH(TIMESTAMP without TIME ZONE) RETURNS INTEGER AS $$ SELECT EXTRACT(MONTH FROM $1)::INTEGER; $$ LANGUAGE SQL IMMUTABLE;

CREATE OR REPLACE FUNCTION MONTH(TIMESTAMP WITH TIME ZONE) RETURNS INTEGER AS $$ SELECT EXTRACT(MONTH FROM $1)::INTEGER; $$ LANGUAGE SQL STABLE;

CREATE OR REPLACE FUNCTION MONTH(DATE) RETURNS INTEGER AS $$ SELECT EXTRACT(MONTH FROM $1)::INTEGER; $$ LANGUAGE SQL IMMUTABLE;


CREATE OR REPLACE FUNCTION DAY(TIMESTAMP without TIME ZONE) RETURNS INTEGER AS $$ SELECT EXTRACT(DAY FROM $1)::INTEGER; $$ LANGUAGE SQL IMMUTABLE;

CREATE OR REPLACE FUNCTION DAY(TIMESTAMP WITH TIME ZONE) RETURNS INTEGER AS $$ SELECT EXTRACT(DAY FROM $1)::INTEGER; $$ LANGUAGE SQL STABLE;

CREATE OR REPLACE FUNCTION DAY(DATE) RETURNS INTEGER AS $$ SELECT EXTRACT(DAY FROM $1)::INTEGER; $$ LANGUAGE SQL IMMUTABLE;


CREATE OR REPLACE FUNCTION rebuilt_sequences() RETURNS integer as $body$ DECLARE sequencedefs RECORD; c integer ; BEGIN FOR sequencedefs IN SELECT DISTINCT constraint_column_usage.table_name as tablename, constraint_column_usage.table_name as tablename, constraint_column_usage.column_name as columnname, replace(replace(columns.column_default,'''::regclass)',''),'nextval(''','') as sequencename from information_schema.constraint_column_usage, information_schema.columns where constraint_column_usage.table_schema ='public' AND columns.table_schema = 'public' AND columns.table_name=constraint_column_usage.table_name AND constraint_column_usage.column_name IN ('rowid','id') AND constraint_column_usage.column_name = columns.column_name AND columns.column_default is not null LOOP EXECUTE 'select max('||sequencedefs.columnname||') from ' || sequencedefs.tablename INTO c; IF c is null THEN c = 0; END IF; IF c is not null THEN c = c+ 1; END IF; EXECUTE 'alter sequence ' || sequencedefs.sequencename ||' restart  with ' || c; END LOOP; RETURN 1; END; $body$ LANGUAGE PLPGSQL;
