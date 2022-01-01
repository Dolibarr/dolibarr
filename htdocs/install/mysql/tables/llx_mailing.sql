-- ========================================================================
-- Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
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


-- redaction : 0
-- valide    : 1
-- approuve  : 2
-- envoye    : 3

create table llx_mailing
(
  rowid				integer AUTO_INCREMENT PRIMARY KEY,
  statut			smallint       DEFAULT 0,            --
  titre				varchar(128),                        -- Ref of mailing
  entity			integer DEFAULT 1 NOT NULL,	         -- multi company id
  sujet				varchar(128),                        -- Sujet of mailing
  body				mediumtext,
  bgcolor			varchar(8),                          -- Backgroud color of mailing
  bgimage			varchar(255),                        -- Backgroud image of mailing
  cible				varchar(60),
  nbemail			integer,
  email_from		varchar(160),                        -- Email address of sender
  email_replyto		varchar(160),                        -- Email address for reply
  email_errorsto	varchar(160),                        -- Email addresse for errors
  tag				varchar(128) NULL,
  date_creat		datetime,                            -- creation date
  date_valid		datetime,                            -- 
  date_appro		datetime,                            -- 
  date_envoi		datetime,                            -- date d'envoi
  tms				timestamp,  
  fk_user_creat		integer,                             -- user creator
  fk_user_valid		integer,                             -- user validator
  fk_user_appro		integer,                             -- not used
  extraparams		varchar(255),						 -- for stock other parameters with json format
  joined_file1		varchar(255),
  joined_file2		varchar(255),
  joined_file3		varchar(255),
  joined_file4		varchar(255)
)ENGINE=innodb;
