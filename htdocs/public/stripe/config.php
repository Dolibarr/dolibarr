<?php
/* Copyright (C) 2017		Alexandre Spangaro		<aspangaro@zendsi.com>
 * Copyright (C) 2017		Saasprov				<saasprov@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
*  \file       stripe/config.php
*  \ingroup    Stripe
*  \brief      Page to move config in api
*/

require '../../main.inc.php';

require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/stripe/lib/stripe.lib.php';
require_once DOL_DOCUMENT_ROOT.'/includes/stripe/init.php';

//use \includes\stripe as stripe;
$stripe = array(
  "secret_key"      => $conf->global->STRIPE_TEST_SECRET_KEY,
  "publishable_key" => $conf->global->STRIPE_TEST_PUBLISHABLE_KEY
);

\includes\stripe::setApiKey($stripe['secret_key']);