<?php
/* Copyright (C) 2006-2012	Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007		Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2010-2012	Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2010		Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2015 Claudio Aschieri				<c.aschieri@19.coop>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 *  \file       htdocs/core/lib/expedition.lib.php
 *  \brief      Function for expedition module
 *  \ingroup    expedition
 */

/**
 * Prepare array with list of tabs
 *
 * @param   Expedition	$object		Object related to tabs
 * @return	array<array{0:string,1:string,2:string}>	Array of tabs to show
 */
function expedition_prepare_head(Expedition $object)
{
	global $langs, $conf, $user;
	if (isModEnabled("shipping")) {
		$langs->load("sendings");
	}
	$langs->load("orders");

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/admin/confexped.php";
	$head[$h][1] = $langs->trans("Setup");
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/admin/expedition.php";
	$head[$h][1] = $langs->trans("Shipment");
	$hselected = $h;
	$h++;

	if (getDolGlobalInt('MAIN_SUBMODULE_DELIVERY')) {
		$head[$h][0] = DOL_URL_ROOT."/admin/delivery.php";
		$head[$h][1] = $langs->trans("Receivings");
		$h++;
	}

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'order');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'order', 'remove');

	return $head;
}

/**
 *  Return array head with list of tabs to view object information.
 *
 *  @return	array   	    		    head array with tabs
 */
function expedition_admin_prepare_head()
{
	global $langs, $conf, $user, $db;
	$langs->load("sendings");

	$extrafields = new ExtraFields($db);

	$h = 0;
	$head = array();

	/*
	$head[$h][0] = DOL_URL_ROOT."/admin/confexped.php";
	$head[$h][1] = $langs->trans("Setup");
	$head[$h][2] = 'general';
	$h++;
	*/

	if (getDolGlobalString('MAIN_SUBMODULE_EXPEDITION')) {
		$extrafields->fetch_name_optionals_label('expedition');
		$extrafields->fetch_name_optionals_label('expeditiondet');

		$head[$h][0] = DOL_URL_ROOT."/admin/expedition.php";
		$head[$h][1] = $langs->trans("Shipment");
		$head[$h][2] = 'shipment';
		$h++;

		$head[$h][0] = DOL_URL_ROOT.'/admin/expedition_extrafields.php';
		$head[$h][1] = $langs->trans("ExtraFields");
		$nbExtrafields = $extrafields->attributes['expedition']['count'];
		if ($nbExtrafields > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
		}
		$head[$h][2] = 'attributes_shipment';
		$h++;

		$head[$h][0] = DOL_URL_ROOT.'/admin/expeditiondet_extrafields.php';
		$head[$h][1] = $langs->trans("ExtraFieldsLines");
		$nbExtrafields = $extrafields->attributes['expeditiondet']['count'];
		if ($nbExtrafields > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
		}
		$head[$h][2] = 'attributeslines_shipment';
		$h++;
	}

	$head[$h][0] = DOL_URL_ROOT."/admin/delivery.php";
	$head[$h][1] = $langs->trans("Receivings");
	$head[$h][2] = 'receivings';
	$h++;

	if (getDolGlobalInt('MAIN_SUBMODULE_DELIVERY')) {
		$extrafields->fetch_name_optionals_label('delivery');
		$extrafields->fetch_name_optionals_label('deliverydet');

		$head[$h][0] = DOL_URL_ROOT.'/admin/delivery_extrafields.php';
		$head[$h][1] = $langs->trans("ExtraFields");
		$nbExtrafields = $extrafields->attributes['delivery']['count'];
		if ($nbExtrafields > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
		}
		$head[$h][2] = 'attributes_receivings';
		$h++;

		$head[$h][0] = DOL_URL_ROOT.'/admin/deliverydet_extrafields.php';
		$head[$h][1] = $langs->trans("ExtraFieldsLines");
		$nbExtrafields = $extrafields->attributes['deliverydet']['count'];
		if ($nbExtrafields > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
		}
		$head[$h][2] = 'attributeslines_receivings';
		$h++;
	}

	complete_head_from_modules($conf, $langs, null, $head, $h, 'expedition_admin');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'expedition_admin', 'remove');

	return $head;
}
