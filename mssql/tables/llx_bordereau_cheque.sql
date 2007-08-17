-- ===================================================================
-- Copyright (C) 2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
--
-- $Id$
-- $Source$
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
-- ===================================================================
--
-- Bordereaux de remise de cheque
--
create table llx_bordereau_cheque
(
  rowid             int IDENTITY PRIMARY KEY,
  datec             datetime,
  date_bordereau    datetime,
  number            varchar(5),
  amount            float(12),
  nbcheque          smallint DEFAULT 0,
  fk_bank_account   int,
  fk_user_author    int,
  note              text,
  statut            tinyint DEFAULT 0
);

set ANSI_NULLS ON
set QUOTED_IDENTIFIER ON
GO

-- FILLZERO du number

CREATE TRIGGER [dbo].[llx_bordereau_cheque_number]
	ON [dbo].[llx_bordereau_cheque] 
	AFTER INSERT, UPDATE
AS
BEGIN
	-- SET NOCOUNT ON added to prevent extra result sets from
	-- interfering with SELECT statements.
	SET NOCOUNT ON;

	DECLARE @numero as varchar(5)
	SELECT @numero = right('00000' + cast(number AS varchar(5)),5)
	from llx_bordereau_cheque
	where rowid in (select rowid from inserted)

update llx_bordereau_cheque set number = @numero
where rowid in (select rowid from inserted)

END

set ANSI_NULLS ON
set QUOTED_IDENTIFIER ON
GO
