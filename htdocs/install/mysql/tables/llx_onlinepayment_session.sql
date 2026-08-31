-- ========================================================================
-- Copyright (C) 2026 Dolicraft                <contact@dolicraft.com>
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
-- Data needed by an online payment return page, stored server side instead of
-- in the PHP session, which is lost when the browser drops the cookie on the
-- cross site return from the payment platform.
-- ===================================================================

create table llx_onlinepayment_session
(
  rowid             integer AUTO_INCREMENT PRIMARY KEY,
  ext_payment_site  varchar(64) NOT NULL,          -- 'Stripe', 'StripeTest', 'Stancer', ...
  data              text,                          -- json of the data to propagate to the callback page
  date_creation     datetime NOT NULL,
  tms               timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  entity            integer DEFAULT 1 NOT NULL     -- multicompany ID
)ENGINE=innodb;
