-- ========================================================================
-- Copyright (C) 2000-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@inodbox.com>
-- Copyright (C) 2010      Juanjo Menent        <dolibarr@2byte.es>
-- Copyright (C) 2014      Teddy Andreotti      <125155@supinfo.com>
-- Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
-- Copyright (C) 2023      Alexandre Spangaro   <aspangaro@open-dsi.fr>
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
-- ========================================================================

create table llx_societe
(
  rowid                    integer AUTO_INCREMENT PRIMARY KEY,
  nom                      varchar(128),                                -- company reference name (should be same length than adherent.societe)
  name_alias               varchar(128) NULL,
  entity                   integer DEFAULT 1 NOT NULL,                  -- multi company id

  ref_ext                  varchar(255),                                -- reference into an external system (not used by dolibarr)

  statut                   tinyint        DEFAULT 0,            		-- statut (deprecated)
  parent                   integer,

  status            	   tinyint 		  DEFAULT 1,			        -- active or not ( 1 -- active, 0 -- closed or not open)

  code_client              varchar(24),                         		-- code client
  code_fournisseur         varchar(24),                         		-- code fournisseur
  code_compta              varchar(24),                         		-- customer accountancy auxiliary account
  code_compta_fournisseur  varchar(24),                         		-- supplier accountancy auxiliary account
  address                  varchar(255),                        		-- company address
  zip                      varchar(25),                         		-- zipcode
  town                     varchar(50),                         		-- town
  fk_departement           integer        DEFAULT 0,            		-- state
  fk_pays                  integer        DEFAULT 0,            		-- country

  geolat                   double(24,8)   DEFAULT NULL,
  geolong                  double(24,8)   DEFAULT NULL,
  geopoint                 point DEFAULT NULL,
  georesultcode            varchar(16),

  phone                    varchar(20),                         		-- phone number
  phone_mobile             varchar(20),                         		-- mobile phone number
  fax                      varchar(20),                         		-- fax number
  url                      varchar(255),                        		-- web site
  email                    varchar(128),                        		-- main email

  fk_account               integer        DEFAULT 0,                    -- default bank account

  socialnetworks           text DEFAULT NULL,                           -- json with socialnetworks

  fk_effectif              integer        DEFAULT 0,            		--
  fk_typent                integer        DEFAULT NULL,                 -- type ent
  fk_forme_juridique       integer        DEFAULT 0,            		-- juridical status
  fk_currency			   varchar(3),									-- default currency
  siren	                   varchar(128),                         		-- IDProf1: depends on country (example: siren or RCS for france, ...)
  siret                    varchar(128),                         		-- IDProf2: depends on country (example: siret for france, ...)
  ape                      varchar(128),                         		-- IDProf3: depends on country (example: code ape for france, ...)
  idprof4                  varchar(128),                         		-- IDProf4: depends on country (example: nu for france, ...)
  idprof5                  varchar(128),                         		-- IDProf5: depends on country (example: nu for france, ...)
  idprof6                  varchar(128),                         		-- IDProf6: depends on country (example: nu for france, ...
  tva_intra                varchar(20),                         		-- vat numero
  capital                  double(24,8)   DEFAULT NULL,        			-- capital of company
  fk_stcomm                integer        DEFAULT 0 NOT NULL,      		-- commercial status
  note_private             text,                                		--
  note_public              text,                                        --
  model_pdf				         varchar(255),
  last_main_doc			       varchar(255),					-- relative filepath+filename of last main generated document
  prefix_comm              varchar(5),                          		-- prefix commercial (deprecated)
  client                   tinyint        DEFAULT 0,            		-- client 0/1/2
  fournisseur              tinyint        DEFAULT 0,            		-- fournisseur 0/1
  supplier_account         varchar(32),                         		-- Id of our customer account known by the supplier
  fk_prospectlevel         varchar(12),                         		-- prospect level (in llx_c_prospectlevel)
  fk_incoterms             integer,										-- for incoterms
  location_incoterms       varchar(255),								-- for incoterms
  customer_bad             tinyint        DEFAULT 0,            		-- mauvais payeur 0/1
  customer_rate            real           DEFAULT 0,            		-- taux fiabilite client (0 a 1)
  supplier_rate            real           DEFAULT 0,            		-- taux fiabilite fournisseur (0 a 1)
  remise_client            real           DEFAULT 0,            		-- discount by default granted to this customer
  remise_supplier          real           DEFAULT 0,            		-- discount by default granted by this supplier
  mode_reglement           tinyint,                             		-- payment mode customer
  cond_reglement           tinyint,                             		-- payment term customer
  deposit_percent          varchar(63) DEFAULT NULL,                    -- default deposit % if payment term needs it
  transport_mode           tinyint,                             		-- transport mode customer (Intracomm report)
  mode_reglement_supplier  tinyint,                             		-- payment mode supplier
  cond_reglement_supplier  tinyint,                             		-- payment term supplier
  transport_mode_supplier  tinyint,                             		-- transport mode supplier (Intracomm report)
  fk_shipping_method       integer,                                     -- preferred shipping method id
  tva_assuj                tinyint        DEFAULT 1,	        		-- assujetti ou non a la TVA
  vat_reverse_charge       tinyint        DEFAULT 0,	        		-- By default, company not concerned by vat reverse charge
  localtax1_assuj          tinyint        DEFAULT 0,	        		-- assujeti ou non a local tax 1
  localtax1_value 		   double(7,4),
  localtax2_assuj          tinyint        DEFAULT 0,	        		-- assujeti ou non a local tax 2
  localtax2_value 		   double(7,4),
  barcode                  varchar(180),                        		-- barcode
  fk_barcode_type          integer NULL   DEFAULT 0,                    -- barcode type
  price_level              integer NULL,                        		-- level of price for multiprices
  outstanding_limit	       double(24,8)   DEFAULT NULL,					-- allowed outstanding limit
  order_min_amount	       double(24,8)   DEFAULT NULL,					-- min amount for orders
  supplier_order_min_amount	       double(24,8)   DEFAULT NULL,			-- min amount for supplier orders
  default_lang             varchar(6),									-- default language
  logo                     varchar(255)   DEFAULT NULL,
  logo_squarred            varchar(255)   DEFAULT NULL,
  canvas				   varchar(32)    DEFAULT NULL,	                -- type of canvas if used (null by default)
  fk_warehouse			   integer 		  DEFAULT NULL,					-- if we need a link between third party and warehouse
  webservices_url          varchar(255),                            	-- supplier webservice url
  webservices_key          varchar(128),                            	-- supplier webservice key

  accountancy_code_sell         varchar(32),                            -- Selling accountancy code
  accountancy_code_buy          varchar(32),                            -- Buying accountancy code

  tms                      timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,									-- last modification date
  datec	                   datetime,                            		-- creation date
  fk_user_creat            integer NULL,                        		-- utilisateur qui a cree l'info
  fk_user_modif            integer,                             		-- utilisateur qui a modifie l'info

  fk_multicurrency		   integer,
  multicurrency_code	   varchar(3),

  ip                     varchar(250),                              --ip used to create record (for public submission page)

  import_key               varchar(14)                          		-- import key
)ENGINE=innodb;
