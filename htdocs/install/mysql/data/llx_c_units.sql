-- ========================================================================
-- Copyright (C) 2001-2002,2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2004           Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2019           Florian Henry      <florian.henry@atm-consulitng.fr>
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
-- ========================================================================

INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('T',  '3','WeightUnitton','T', 'weight', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('KG', '0','WeightUnitkg','kg', 'weight', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('G', '-3','WeightUnitg','g', 'weight', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('MG','-6','WeightUnitmg','mg', 'weight', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('OZ','98','WeightUnitounce','Oz', 'weight', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('LB','99','WeightUnitpound','lb', 'weight', 1);

INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('M',  '0','SizeUnitm','m', 'size', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('DM','-1','SizeUnitdm','dm', 'size', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('CM','-2','SizeUnitcm','cm', 'size', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('MM','-3','SizeUnitmm','mm', 'size', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('FT','98','SizeUnitfoot','ft', 'size', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('IN','99','SizeUnitinch','in', 'size', 1);

INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('M2',  '0','SurfaceUnitm2','m2', 'surface', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('DM2','-2','SurfaceUnitdm2','dm2', 'surface', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('CM2','-4','SurfaceUnitcm2','cm2', 'surface', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('MM2','-6','SurfaceUnitmm2','mm2', 'surface', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('FT2','98','SurfaceUnitfoot2','ft2', 'surface', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('IN2','99','SurfaceUnitinch2','in2', 'surface', 1);


INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('M3',  '0','VolumeUnitm3','m3', 'volume', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('DM3','-3','VolumeUnitdm3','dm3', 'volume', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('CM3','-6','VolumeUnitcm3','cm3', 'volume', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('MM3','-9','VolumeUnitmm3','mm3', 'volume', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('FT3','88','VolumeUnitfoot3','ft3', 'volume', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('IN3','89','VolumeUnitinch3','in3', 'volume', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('OZ3','97','VolumeUnitounce','Oz', 'volume', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('L',  '98','VolumeUnitlitre','L', 'volume', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('GAL','99','VolumeUnitgallon','gal', 'volume', 1);

INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('GAL','99','VolumeUnitgallon','gal', 'volume', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('GAL','99','VolumeUnitgallon','gal', 'volume', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('GAL','99','VolumeUnitgallon','gal', 'volume', 1);

INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('P',   '0','Piece','p', 'qty', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('SET', '0','Set','set', 'qty', 1);

INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('S',       '0','second','s', 'time', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('MI',     '60','minute','i', 'time', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('H',    '3600','hour','h', 'time', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('D','12960000','day','d', 'time', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('W',  '604800','week','w', 'time', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('MO','2629800','month','m', 'time', 1);
INSERT INTO llx_c_units (code, scale, label, short_label, unit_type, active) VALUES ('Y','31557600','year','y', 'time', 1);
