<?php
/* Copyright (C) 2010-2011 	Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2010		Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2011      	Regis Houssin		<regis.houssin@capnetworks.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/core/lib/prelevement.lib.php
 *	\brief      Ensemble de fonctions de base pour le module prelevement
 *	\ingroup    propal
 */


/**
 * Prepare array with list of tabs
 *
 * @param   BonPrelevement	$object		Object related to tabs
 * @return  array				Array of tabs to show
 */
function prelevement_prepare_head(BonPrelevement $object)
{
	global $langs, $conf, $user;
	$langs->load("withdrawals");

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/card.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'prelevement';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/factures.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Bills");
	$head[$h][2] = 'invoices';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/fiche-rejet.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Rejects");
	$head[$h][2] = 'rejects';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/fiche-stat.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Statistics");
	$head[$h][2] = 'statistics';
	$h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname);   												to remove a tab
    complete_head_from_modules($conf,$langs,$object,$head,$h,'prelevement');

    complete_head_from_modules($conf,$langs,$object,$head,$h,'prelevement','remove');

    return $head;
}

/**
 *	Check need data to create standigns orders receipt file
 *
 *	@return    	int		-1 if ko 0 if ok
 */
function prelevement_check_config()
{
	global $conf;
	if(empty($conf->global->PRELEVEMENT_ID_BANKACCOUNT)) return -1;
	if(empty($conf->global->PRELEVEMENT_ICS)) return -1;
    if(empty($conf->global->PRELEVEMENT_USER)) return -1;
	return 0;
}

