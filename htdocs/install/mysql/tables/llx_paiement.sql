-- ===================================================================
-- Copyright (C) 2001-2004	Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2004		Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2012		Regis Houssin        <regis.houssin@inodbox.com>
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

create table llx_paiement
(
  rowid            integer AUTO_INCREMENT PRIMARY KEY,
  ref              varchar(30) NULL,                    -- payment reference number
  ref_ext          varchar(255) NULL,                    -- payment external reference
  entity           integer   DEFAULT 1 NOT NULL,		-- Multi company id
  datec            datetime,							-- date de creation
  tms              timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  datep            datetime,							-- payment date
  amount           double(24,8) DEFAULT 0,				-- amount paid in Dolibarr currency
  multicurrency_amount double(24,8) DEFAULT 0,			-- amount paid in invoice currency	
  fk_paiement      integer NOT NULL,					-- type of payment in llx_c_paiement
  num_paiement     varchar(50),
  note             text,
  ext_payment_id   varchar(128),						-- external id of payment (for example Stripe charge id)
  ext_payment_site varchar(128),						-- name of external paymentmode (for example 'stripe')
  fk_bank          integer NOT NULL DEFAULT 0,
  fk_user_creat    integer,								-- utilisateur qui a cree l'info
  fk_user_modif    integer,								-- utilisateur qui a modifie l'info
  statut           smallint DEFAULT 0 NOT NULL,			-- Satut, 0 ou 1, 1 n'est plus supprimable
  fk_export_compta integer DEFAULT 0 NOT NULL,			-- fk_export_compta 0 pas exporte
  pos_change       double(24,8) DEFAULT 0  				-- Excess received in TakePOS cash payment
)ENGINE=innodb;
