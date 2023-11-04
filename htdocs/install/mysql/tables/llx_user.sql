-- ============================================================================
-- Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2006-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2007-2013 Regis Houssin        <regis.houssin@inodbox.com>
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
-- ===========================================================================

create table llx_user
(
  rowid             integer AUTO_INCREMENT PRIMARY KEY,
  entity            integer DEFAULT 1 NOT NULL, -- multi company id

  ref_ext			varchar(50),				-- reference into an external system (not used by dolibarr)
  ref_int			varchar(50),				-- reference into an internal system (deprecated)

  admin             smallint DEFAULT 0,			-- user has admin profile

  employee          tinyint        DEFAULT 1,	-- 1 if user is an employee
  fk_establishment  integer        DEFAULT 0,

  datec             datetime,
  tms               timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_user_creat     integer,
  fk_user_modif     integer,
  login             varchar(50) NOT NULL,
  pass_encoding     varchar(24),
  pass              varchar(128),
  pass_crypted      varchar(128),
  pass_temp         varchar(128),				-- temporary password when asked for forget password or 'hashtoallowreset:YYYMMDDHHMMSS' (where date is max date of validity)
  api_key           varchar(128),				-- key to use REST API by this user
  gender            varchar(10),
  civility          varchar(6),
  lastname          varchar(50),
  firstname         varchar(50),
  address           varchar(255),				-- user personal address
  zip               varchar(25),				-- zipcode
  town              varchar(50),				-- town
  fk_state          integer        DEFAULT 0,
  fk_country        integer        DEFAULT 0,
  birth             date,						-- birthday
  job		        varchar(128),
  office_phone      varchar(20),
  office_fax        varchar(20),
  user_mobile       varchar(20),
  personal_mobile   varchar(20),
  email             varchar(255),
  personal_email    varchar(255),
  signature         text DEFAULT NULL,

  socialnetworks    text DEFAULT NULL,       -- json with socialnetworks

  --module_comm       smallint DEFAULT 1,
  --module_compta     smallint DEFAULT 1,
  
  fk_soc			integer,					-- id thirdparty if user linked to a company (external user)
  fk_socpeople      integer,					-- id contact origin if user linked to a contact
  fk_member         integer,					-- if member if suer linked to a member
  fk_user           integer,					-- Supervisor, hierarchic parent
  fk_user_expense_validator           integer,
  fk_user_holiday_validator           integer,
  
  idpers1			varchar(128),
  idpers2			varchar(128),
  idpers3			varchar(128),
  
  note_public		text,
  note              text DEFAULT NULL,
  model_pdf         varchar(255) DEFAULT NULL,
  datelastlogin     datetime,
  datepreviouslogin datetime,
  datelastpassvalidation datetime,				-- last date we change password or we made a disconnect all
  datestartvalidity datetime,
  dateendvalidity   datetime,
  iplastlogin       varchar(250),
  ippreviouslogin   varchar(250),
  egroupware_id     integer,
  ldap_sid          varchar(255) DEFAULT NULL,
  openid            varchar(255),
  statut            tinyint DEFAULT 1,
  photo             varchar(255),				-- filename or url of photo
  lang              varchar(6),					-- default language for communication. Note that language selected by user as interface language is savec into llx_user_param.
  color				varchar(6),
  barcode			varchar(255) DEFAULT NULL,
  fk_barcode_type	integer      DEFAULT 0,
  accountancy_code  varchar(32) NULL,
  nb_holiday		integer      DEFAULT 0,
  thm				double(24,8),
  tjm				double(24,8),

  salary			double(24,8),				-- denormalized value coming from llx_user_employment
  salaryextra		double(24,8),				-- denormalized value coming from llx_user_employment
  dateemployment	date,						-- denormalized value coming from llx_user_employment
  dateemploymentend	date,						-- denormalized value coming from llx_user_employment
  weeklyhours		double(16,8),				-- denormalized value coming from llx_user_employment

  import_key        varchar(14),				-- import key
  default_range     integer,
  default_c_exp_tax_cat     integer,
  fk_warehouse      integer						-- default warehouse os user
)ENGINE=innodb;
