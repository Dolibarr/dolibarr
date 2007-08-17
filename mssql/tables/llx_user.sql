-- ============================================================================
-- Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2006-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2007      Regis Houssin        <regis.houssin@cap-networks.com>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General [public] License as published by
-- the Free Software Foundation; either version 2 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General [public] License for more details.
--
-- You should have received a copy of the GNU General [public] License
-- along with this program; if not, write to the Free Software
-- Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
--
-- $Id$
-- $Source$
--
-- ===========================================================================

create table llx_user
(
  rowid             int IDENTITY PRIMARY KEY,
  datec             datetime,
  tms               timestamp,
  login             varchar(24) NOT NULL,
  pass              varchar(32),
  pass_crypted      varchar(128),
  name              varchar(50),
  firstname         varchar(50),
  office_phone      varchar(20),
  office_fax        varchar(20),
  user_mobile       varchar(20),
  email             varchar(255),
  admin             smallint DEFAULT 0,
  webcal_login      varchar(25),
  module_comm       smallint DEFAULT 1,
  module_compta     smallint DEFAULT 1,
  fk_societe        int,
  fk_socpeople      int,
  fk_member         int,
  note              text DEFAULT NULL,
  datelastlogin     datetime,
  datepreviouslogin datetime,
  egroupware_id     int,
  ldap_sid          varchar(255) DEFAULT NULL,
  statut			tinyint DEFAULT 1,
  lang              varchar(6)
);

set ANSI_NULLS ON
set QUOTED_IDENTIFIER ON
GO

CREATE FUNCTION [dbo].[unix_timestamp]
(
	@Date datetime
)
RETURNS datetime
AS
BEGIN
	DECLARE @Result int

	--SET @Result = NULL
	--IF @Date IS NOT NULL SET @Result = DATEDIFF(s, '19700101', @Date)

	RETURN @Date
END

set ANSI_NULLS ON
set QUOTED_IDENTIFIER ON
GO

CREATE FUNCTION [dbo].[from_unixtime]
(
	@Epoch int
)
RETURNS datetime
AS
BEGIN
	RETURN DATEADD(s, @Epoch, '19700101')

END

set ANSI_NULLS ON
set QUOTED_IDENTIFIER ON
GO
