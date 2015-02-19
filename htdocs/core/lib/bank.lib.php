<?php

/* Copyright (C) 2006-2007	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Regis Houssin		<regis.houssin@capnetworks.com>
 * Copytight (C) 2015		Alexandre Spangaro	<alexandre.spangaro@gmail.com>
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
 * \file       htdocs/core/lib/bank.lib.php
 * \brief      Ensemble de fonctions de base pour le module banque
 * \ingroup    banque
 */

/**
 * Prepare array with list of tabs
 *
 * @param   Account	$object		Object related to tabs
 * @return  array				Array of tabs to show
 */
function bank_prepare_head(Account $object)
{
    global $langs, $conf, $user;
    $h = 0;
    $head = array();

    $head[$h][0] = DOL_URL_ROOT . '/compta/bank/card.php?id=' . $object->id;
    $head[$h][1] = $langs->trans("AccountCard");
    $head[$h][2] = 'bankname';
    $h++;

    $head[$h][0] = DOL_URL_ROOT . "/compta/bank/account.php?id=" . $object->id;
    $head[$h][1] = $langs->trans("Transactions");
    $head[$h][2] = 'journal';
    $h++;

//    if ($conf->global->MAIN_FEATURES_LEVEL >= 1)
//	{
    $head[$h][0] = DOL_URL_ROOT . "/compta/bank/treso.php?account=" . $object->id;
    $head[$h][1] = $langs->trans("PlannedTransactions");
    $head[$h][2] = 'cash';
    $h++;
//	}

    $head[$h][0] = DOL_URL_ROOT . "/compta/bank/annuel.php?account=" . $object->id;
    $head[$h][1] = $langs->trans("IOMonthlyReporting");
    $head[$h][2] = 'annual';
    $h++;

    $head[$h][0] = DOL_URL_ROOT . "/compta/bank/graph.php?account=" . $object->id;
    $head[$h][1] = $langs->trans("Graph");
    $head[$h][2] = 'graph';
    $h++;

    if ($object->courant != 2)
    {
    	$head[$h][0] = DOL_URL_ROOT."/compta/bank/releve.php?account=".$object->id;
	    $head[$h][1] = $langs->trans("AccountStatements");
	    $head[$h][2] = 'statement';
	    $h++;
	}

	// Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname);   												to remove a tab
    complete_head_from_modules($conf, $langs, $object, $head, $h, 'bank');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'bank', 'remove');

    return $head;
}
/**
 * Prepare array with list of tabs
 * 
 * @param   Object	$object		Object related to tabs
 * @return  array				Array of tabs to shoc
 */
function bank_admin_prepare_head($object)
{
	global $langs, $conf, $user;
	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT . '/admin/bank.php';
	$head[$h][1] = $langs->trans("Miscellaneous");
	$head[$h][2] = 'general';
	$h++;

	
	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'bank_admin');
	
	$head[$h][0] = DOL_URL_ROOT.'/admin/bank_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFields");
	$head[$h][2] = 'attributes';
	$h++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'bank_admin', 'remove');

	return $head;
}

/**
 *      Check SWIFT informations for a bank account
 *
 *      @param  Account     $account    A bank account
 *      @return int                     True if informations are valid, false otherwise
 */
function checkSwiftForAccount($account)
{
    $swift = $account->bic;
    if (eregi("^([a-zA-Z]){4}([a-zA-Z]){2}([0-9a-zA-Z]){2}([0-9a-zA-Z]{3})?$", $swift)) {
        return true;
    } else {
        return false;
    }
}

/**
 *      Check IBAN number informations for a bank account
 *
 *      @param  Account     $account    A bank account
 *      @return int                     True if informations are valid, false otherwise
 */
function checkIbanForAccount($account)
{
    require_once DOL_DOCUMENT_ROOT.'/includes/php-iban/oophp-iban.php';
    $iban = new Iban($account->iban);
    $check = $iban->Verify();
    if ($check) {
        return true;
    } else {
        return false;
    }
}