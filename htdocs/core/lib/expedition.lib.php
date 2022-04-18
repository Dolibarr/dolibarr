<?php
/* Copyright (C) 2006-2012	Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007		Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2010-2012	Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2010		Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2015 Claudio Aschieri				<c.aschieri@19.coop>
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
 * @return  array				Array of tabs to show
 */
function expedition_prepare_head(Expedition $object)
{
	global $langs, $conf, $user;
	if (!empty($conf->expedition->enabled)) {
		$langs->load("sendings");
	}
	$langs->load("orders");

	$h = 0;
	$head = array();
	$h = 0;

	$head[$h][0] = DOL_URL_ROOT."/admin/confexped.php";
	$head[$h][1] = $langs->trans("Setup");
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/admin/expedition.php";
	$head[$h][1] = $langs->trans("Shipment");
	$hselected = $h;
	$h++;

	if (!empty($conf->global->MAIN_SUBMODULE_DELIVERY)) {
		$head[$h][0] = DOL_URL_ROOT."/admin/delivery.php";
		$head[$h][1] = $langs->trans("Receivings");
		$h++;
	}

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'order');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'order', 'remove');

	return $head;
}

/**
 *  Return array head with list of tabs to view object informations.
 *
 *  @return	array   	    		    head array with tabs
 */
function expedition_admin_prepare_head()
{
	global $langs, $conf, $user;
	$langs->load("sendings");

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/admin/confexped.php";
	$head[$h][1] = $langs->trans("Setup");
	$head[$h][2] = 'general';
	$h++;


	if (!empty($conf->global->MAIN_SUBMODULE_EXPEDITION)) {
		$head[$h][0] = DOL_URL_ROOT."/admin/expedition.php";
		$head[$h][1] = $langs->trans("Shipment");
		$head[$h][2] = 'shipment';
		$h++;
	}


	if (!empty($conf->global->MAIN_SUBMODULE_EXPEDITION)) {
		$head[$h][0] = DOL_URL_ROOT.'/admin/expedition_extrafields.php';
		$head[$h][1] = $langs->trans("ExtraFields");
		$head[$h][2] = 'attributes_shipment';
		$h++;
	}

	if (!empty($conf->global->MAIN_SUBMODULE_EXPEDITION)) {
		$head[$h][0] = DOL_URL_ROOT.'/admin/expeditiondet_extrafields.php';
		$head[$h][1] = $langs->trans("ExtraFieldsLines");
		$head[$h][2] = 'attributeslines_shipment';
		$h++;
	}

	if (!empty($conf->global->MAIN_SUBMODULE_DELIVERY)) {
		$head[$h][0] = DOL_URL_ROOT."/admin/delivery.php";
		$head[$h][1] = $langs->trans("Receivings");
		$head[$h][2] = 'receivings';
		$h++;
	}

	if (!empty($conf->global->MAIN_SUBMODULE_DELIVERY)) {
		$head[$h][0] = DOL_URL_ROOT.'/admin/delivery_extrafields.php';
		$head[$h][1] = $langs->trans("ExtraFields");
		$head[$h][2] = 'attributes_receivings';
		$h++;
	}

	if (!empty($conf->global->MAIN_SUBMODULE_DELIVERY)) {
		$head[$h][0] = DOL_URL_ROOT.'/admin/deliverydet_extrafields.php';
		$head[$h][1] = $langs->trans("ExtraFieldsLines");
		$head[$h][2] = 'attributeslines_receivings';
		$h++;
	}

	complete_head_from_modules($conf, $langs, null, $head, $h, 'expedition_admin');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'expedition_admin', 'remove');

	return $head;
}
