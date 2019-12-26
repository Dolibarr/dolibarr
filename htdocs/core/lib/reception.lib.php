<?php
/* Copyright (C) 2006-2012	Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007		Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2010-2012	Regis Houssin        <regis.houssin@capnetworks.com>
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
 *  \file       htdocs/core/lib/reception.lib.php
 *  \brief      Function for reception module
 *  \ingroup    reception
 */

/**
 * Prepare array with list of tabs
 *
 * @param   Reception	$object		Object related to tabs
 * @return  array				Array of tabs to show
 */
function reception_prepare_head(Reception $object)
{
	global $db, $langs, $conf, $user;

	$langs->load("sendings");
	$langs->load("deliveries");

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/reception/card.php?id=".$object->id;
	$head[$h][1] = $langs->trans("ReceptionCard");
	$head[$h][2] = 'reception';
	$h++;



	if (empty($conf->global->MAIN_DISABLE_CONTACTS_TAB))
	{
	    $objectsrc = $object;
	    if ($object->origin == 'commande' && $object->origin_id > 0)
	    {
	        $objectsrc = new Commande($db);
	        $objectsrc->fetch($object->origin_id);
	    }
	    $nbContact = count($objectsrc->liste_contact(-1, 'internal')) + count($objectsrc->liste_contact(-1, 'external'));
	    $head[$h][0] = DOL_URL_ROOT."/reception/contact.php?id=".$object->id;
    	$head[$h][1] = $langs->trans("ContactsAddresses");
		if ($nbContact > 0) $head[$h][1].= '<span class="badge marginleftonlyshort">'.$nbContact.'</span>';
    	$head[$h][2] = 'contact';
    	$h++;
	}

    $nbNote = 0;
    if (!empty($object->note_private)) $nbNote++;
    if (!empty($object->note_public)) $nbNote++;
	$head[$h][0] = DOL_URL_ROOT."/reception/note.php?id=".$object->id;
	$head[$h][1] = $langs->trans("Notes");
	if ($nbNote > 0) $head[$h][1].= '<span class="badge marginleftonlyshort">'.$nbNote.'</span>';
	$head[$h][2] = 'note';
	$h++;






    complete_head_from_modules($conf, $langs, $object, $head, $h, 'order', 'remove');

    return $head;
}

/**
 *  Return array head with list of tabs to view object informations.
 *
 *  @return	array   	    		    head array with tabs
 */
function reception_admin_prepare_head()
{
	global $langs, $conf, $user;
	$langs->load("receptions");

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/admin/reception_setup.php";
	$head[$h][1] = $langs->trans("Reception");
	$head[$h][2] = 'reception';
	$h++;


	if (! empty($conf->global->MAIN_SUBMODULE_RECEPTION))
	{
	    $head[$h][0] = DOL_URL_ROOT.'/admin/reception_extrafields.php';
	    $head[$h][1] = $langs->trans("ExtraFields");
	    $head[$h][2] = 'attributes_reception';
	    $h++;
	}

	if (! empty($conf->global->MAIN_SUBMODULE_RECEPTION))
	{
	    $head[$h][0] = DOL_URL_ROOT.'/admin/commande_fournisseur_dispatch_extrafields.php';
	    $head[$h][1] = $langs->trans("ExtraFieldsLines");
	    $head[$h][2] = 'attributeslines_reception';
	    $h++;
	}



	complete_head_from_modules($conf, $langs, null, $head, $h, 'reception_admin', 'remove');

	return $head;
}
