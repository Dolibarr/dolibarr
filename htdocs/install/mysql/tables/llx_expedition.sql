-- ===================================================================
-- Copyright (C) 2003-2010 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2008-2010 Regis Houssin        <regis.houssin@inodbox.com>
-- Copyright (C) 2011-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2012      Juanjo Menent		<jmenent@2byte.es>
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
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
-- ===================================================================

create table llx_expedition
(
  rowid                 integer AUTO_INCREMENT PRIMARY KEY,
  tms                   timestamp,
  ref                   varchar(30)        NOT NULL,
  entity                integer  DEFAULT 1 NOT NULL,	-- multi company id
  fk_soc                integer            NOT NULL,
  fk_projet  		integer  DEFAULT NULL,
  
  ref_ext               varchar(255),					-- reference into an external system (not used by dolibarr)
  ref_int				varchar(255),					-- reference into an internal system (used by dolibarr to store extern id like paypal info)
  ref_customer          varchar(255),					-- customer number
  
  date_creation         datetime,						-- date de creation
  fk_user_author        integer,						-- author of creation
  fk_user_modif         integer,						-- author of last change
  date_valid            datetime,						-- date de validation
  fk_user_valid         integer,						-- valideur
  date_delivery			datetime	DEFAULT NULL,		-- date planned of delivery
  date_expedition       datetime,						-- not used (deprecated)
  fk_address  			integer		DEFAULT NULL, 		-- delivery address (deprecated)
  fk_shipping_method    integer,
  tracking_number       varchar(50),
  fk_statut             smallint	DEFAULT 0,			-- 0 = draft, 1 = validated, 2 = billed or closed depending on WORKFLOW_BILL_ON_SHIPMENT option
  billed                smallint    DEFAULT 0,
  
  height                float,							-- height
  width                 float,							-- with
  size_units            integer,						-- unit of all sizes (height, width, depth)
  size                  float,							-- depth
  weight_units          integer,						-- unit of weight
  weight                float,							-- weight
  note_private          text,
  note_public           text,
  model_pdf             varchar(255),
  last_main_doc			varchar(255),					-- relative filepath+filename of last main generated document

  fk_incoterms          integer,						-- for incoterms
  location_incoterms    varchar(255),					-- for incoterms
  
  import_key			varchar(14),
  extraparams			varchar(255)							-- for other parameters with json format
)ENGINE=innodb;
