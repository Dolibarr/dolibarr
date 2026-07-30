-- ========================================================================
-- Copyright (C) 2026  Frédéric France         <frederic.france@free.fr>
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
-- ========================================================================

INSERT INTO llx_c_subtotals_texts (entity, code, label, content, active) VALUES (__ENTITY__, 'DELIVERY_INSTRUCTIONS', 'Delivery instructions', 'Please deliver during business hours (8am-6pm).\nA signature will be required on receipt.', 1);
INSERT INTO llx_c_subtotals_texts (entity, code, label, content, active) VALUES (__ENTITY__, 'PAYMENT_TERMS_NOTE', 'Payment terms note', 'Payment is due within 30 days of the invoice date.\nLate payments may be subject to interest charges.', 1);
