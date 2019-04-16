<?php
/* Copyright (C) 2014-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file			htdocs/core/actions_setmoduleoptions.inc.php
 *  \brief			Code for actions on setting notes of object page
 */


// $action must be defined
// $arrayofparameters must be set for action 'update'
// $nomessageinupdate can be set to 1
// $nomessageinsetmoduleoptions can be set to 1
if ($action == 'set_freetext')
{
	$freetextvar = GETPOST("freetextvar", 'alpha');
	$freetext = GETPOST("freetext", 'none');	// No alpha here, we want exact string
	$freetextlang = GETPOST('freetextlang', 'alpha');

	if ( ! empty($conf->global->MAIN_MULTILANGS) && !empty($freetextlang) )
	{
		$res = dolibarr_set_const($db, $freetextvar."_".$freetextlang, $freetext, 'chaine', 0, '', $conf->entity);
	}
	else
	{
		$res = dolibarr_set_const($db, $freetextvar, $freetext, 'chaine', 0, '', $conf->entity);
	}

	if (! $res > 0) $error++;

 	if (! $error)
    {
        setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
    }
    else
    {
        setEventMessages($langs->trans("Error"), null, 'errors');
    }
}