-- ========================================================================
-- Copyright (C) 2000-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@capnetworks.com>
-- Copyright (C) 2010      Juanjo Menent        <dolibarr@2byte.es>
-- Copyright (C) 2014      Teddy Andreotti      <125155@supinfo.com>
-- Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
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
-- ========================================================================

create table llx_societe
(
  rowid                    integer AUTO_INCREMENT PRIMARY KEY,
  nom                      varchar(128),                                -- company reference name (should be same length than adherent.societe)
  name_alias               varchar(128) NULL,
  entity                   integer DEFAULT 1 NOT NULL,                  -- multi company id

  ref_ext                  varchar(255),                                -- reference into an external system (not used by dolibarr)
  ref_int                  varchar(255),                                -- reference into an internal system (deprecated)

  statut                   tinyint        DEFAULT 0,            		-- statut
  parent                   integer,
  tms                      timestamp,
  datec	                   datetime,                            		-- creation date

  status            	   tinyint 		  DEFAULT 1,			        -- cessation d'activité ( 1 -- en activité, 0 -- cessation d'activité)						

  code_client              varchar(24),                         		-- code client
  code_fournisseur         varchar(24),                         		-- code founisseur
  code_compta              varchar(24),                         		-- code compta client
  code_compta_fournisseur  varchar(24),                         		-- code compta founisseur
  address                  varchar(255),                        		-- company address
  zip                      varchar(25),                         		-- zipcode
  town                     varchar(50),                         		-- town
  fk_departement           integer        DEFAULT 0,            		--
  fk_pays                  integer        DEFAULT 0,            		--
  fk_account               integer        DEFAULT 0,            		--
  phone                    varchar(20),                         		-- phone number
  fax                      varchar(20),                         		-- fax number
  url                      varchar(255),                        		--
  email                    varchar(128),                        		--
  skype                    varchar(255),                        		--
  fk_effectif              integer        DEFAULT 0,            		--
  fk_typent                integer        DEFAULT 0,            		--
  fk_forme_juridique       integer        DEFAULT 0,            		-- juridical status
  fk_currency			   varchar(3),									-- default currency
  siren	                   varchar(128),                         		-- IDProf1: siren or RCS for france
  siret                    varchar(128),                         		-- IDProf2: siret for france
  ape                      varchar(128),                         		-- IDProf3: code ape for france
  idprof4                  varchar(128),                         		-- IDProf4: nu for france
  idprof5                  varchar(128),                         		-- IDProf5: nu for france
  idprof6                  varchar(128),                         		-- IDProf6: nu for france
  tva_intra                varchar(20),                         		-- tva
  capital                  double(24,8),                       		-- capital de la societe
  fk_stcomm                integer        DEFAULT 0 NOT NULL,      	-- commercial statut
  note_private             text,                                		--
  note_public              text,                                        --
  model_pdf				   varchar(255),
  prefix_comm              varchar(5),                          		-- prefix commercial
  client                   tinyint        DEFAULT 0,            		-- client 0/1/2
  fournisseur              tinyint        DEFAULT 0,            		-- fournisseur 0/1
  supplier_account         varchar(32),                         		-- compte client chez un fournisseur
  fk_prospectlevel         varchar(12),                         		-- prospect level (in llx_c_prospectlevel)
  fk_incoterms             integer,										-- for incoterms
  location_incoterms       varchar(255),								-- for incoterms
  customer_bad             tinyint        DEFAULT 0,            		-- mauvais payeur 0/1
  customer_rate            real           DEFAULT 0,            		-- taux fiabilite client (0 a 1)
  supplier_rate            real           DEFAULT 0,            		-- taux fiabilite fournisseur (0 a 1)
  fk_user_creat            integer NULL,                        		-- utilisateur qui a cree l'info
  fk_user_modif            integer,                             		-- utilisateur qui a modifie l'info
  remise_client            real           DEFAULT 0,            		-- remise systematique pour le client
  mode_reglement           tinyint,                             		-- mode de reglement
  cond_reglement           tinyint,                             		-- condition de reglement
  mode_reglement_supplier  tinyint,                             		-- mode de reglement fournisseur
  cond_reglement_supplier  tinyint,                             		-- condition de reglement fournisseur
  fk_shipping_method       integer,                                     -- preferred shipping method id
  tva_assuj                tinyint        DEFAULT 1,	        		-- assujeti ou non a la TVA
  localtax1_assuj          tinyint        DEFAULT 0,	        		-- assujeti ou non a local tax 1
  localtax1_value 		   double(6,3),
  localtax2_assuj          tinyint        DEFAULT 0,	        		-- assujeti ou non a local tax 2
  localtax2_value 		   double(6,3),
  barcode                  varchar(180),                        		-- barcode
  fk_barcode_type          integer NULL   DEFAULT 0,                    -- barcode type
  price_level              integer NULL,                        		-- level of price for multiprices
  outstanding_limit	       double(24,8)   DEFAULT NULL,					-- allowed outstanding limit
  default_lang             varchar(6),									-- default language
  logo                     varchar(255)   DEFAULT NULL,
  canvas				   varchar(32)    DEFAULT NULL,	                -- type of canvas if used (null by default)
  import_key               varchar(14),                          		-- import key
  webservices_url          varchar(255),                            	-- supplier webservice url
  webservices_key          varchar(128),                            	-- supplier webservice key
  
  fk_multicurrency			integer,
  multicurrency_code		varchar(255)
)ENGINE=innodb;
