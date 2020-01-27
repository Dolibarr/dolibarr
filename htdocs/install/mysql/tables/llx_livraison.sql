-- ===================================================================
-- Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2008-2010 Regis Houssin        <regis.houssin@inodbox.com>
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
-- ===================================================================

create table llx_livraison
(
  rowid                 integer AUTO_INCREMENT PRIMARY KEY,
  tms                   timestamp,
  ref                   varchar(30)  NOT NULL,			-- delivery number
  entity                integer DEFAULT 1 NOT NULL,		-- multi company id
  fk_soc                integer      NOT NULL,
  
  ref_ext               varchar(255),					-- reference into an external system (not used by dolibarr)
  ref_int				varchar(255),					-- reference into an internal system (used by dolibarr to store extern id like paypal info)
  ref_customer          varchar(255),					-- customer number
  
  date_creation         datetime,						-- date de creation
  fk_user_author        integer,						-- createur du bon de livraison
  date_valid            datetime,						-- date de validation
  fk_user_valid         integer,						-- valideur du bon de livraison
  date_delivery 	    datetime		DEFAULT NULL,	-- delivery date
  fk_address  			integer,						-- delivery address (deprecated)
  fk_statut             smallint     DEFAULT 0,
  total_ht              double(24,8) DEFAULT 0,
  note_private          text,
  note_public           text,
  model_pdf             varchar(255),
  last_main_doc			varchar(255),					-- relative filepath+filename of last main generated document
  fk_incoterms          integer,						-- for incoterms
  location_incoterms    varchar(255),					-- for incoterms

  import_key        varchar(14),
  extraparams		varchar(255)						-- for other parameters with json format
)ENGINE=innodb;
