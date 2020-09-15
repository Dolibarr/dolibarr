<?php
/* Copyright (C) 2006-2010  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2010-2017  Regis Houssin       <regis.houssin@inodbox.com>
 * Copyright (C) 2015       Frederic France     <frederic.france@free.fr>
 * Copyright (C) 2015       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
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
 *	    \file       htdocs/core/lib/contact.lib.php
 *		\brief      Ensemble de fonctions de base pour les contacts
 */

/**
 * Prepare array with list of tabs
 *
 * @param   Contact	$object		Object related to tabs
 * @return  array				Array of tabs to show
 */
function contact_prepare_head(Contact $object)
{
	global $db, $langs, $conf, $user;

	$tab = 0;
	$head = array();

	$head[$tab][0] = DOL_URL_ROOT.'/contact/card.php?id='.$object->id;
	$head[$tab][1] = $langs->trans("Contact");
	$head[$tab][2] = 'card';
	$tab++;

	if ((!empty($conf->ldap->enabled) && !empty($conf->global->LDAP_CONTACT_ACTIVE))
		&& (empty($conf->global->MAIN_DISABLE_LDAP_TAB) || !empty($user->admin)))
	{
		$langs->load("ldap");

		$head[$tab][0] = DOL_URL_ROOT.'/contact/ldap.php?id='.$object->id;
		$head[$tab][1] = $langs->trans("LDAPCard");
		$head[$tab][2] = 'ldap';
		$tab++;
	}

	$head[$tab][0] = DOL_URL_ROOT.'/contact/perso.php?id='.$object->id;
	$head[$tab][1] = $langs->trans("PersonalInformations");
	$head[$tab][2] = 'perso';
	$tab++;

	// Related items
    if (! empty($conf->commande->enabled) || ! empty($conf->propal->enabled) || ! empty($conf->facture->enabled) || ! empty($conf->ficheinter->enabled) || !empty($conf->fournisseur->enabled) && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD) || !empty($conf->supplier_order->enabled) || !empty($conf->supplier_invoice->enabled))
    {
        $head[$tab][0] = DOL_URL_ROOT.'/contact/consumption.php?id='.$object->id;
        $head[$tab][1] = $langs->trans("Referers");
        $head[$tab][2] = 'consumption';
        $tab++;
    }

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname);   												to remove a tab
    complete_head_from_modules($conf, $langs, $object, $head, $tab, 'contact');

    // Notes
    if (empty($conf->global->MAIN_DISABLE_NOTES_TAB)) {
        $nbNote = (empty($object->note_private) ? 0 : 1) + (empty($object->note_public) ? 0 : 1);
        $head[$tab][0] = DOL_URL_ROOT.'/contact/note.php?id='.$object->id;
        $head[$tab][1] = $langs->trans("Note");
        if ($nbNote > 0) $head[$tab][1] .= '<span class="badge marginleftonlyshort">'.$nbNote.'</span>';
        $head[$tab][2] = 'note';
        $tab++;
    }

    require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
    require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
    $upload_dir = $conf->societe->dir_output."/contact/".dol_sanitizeFileName($object->ref);
    $nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
    $nbLinks = Link::count($db, $object->element, $object->id);
    $head[$tab][0] = DOL_URL_ROOT.'/contact/document.php?id='.$object->id;
    $head[$tab][1] = $langs->trans("Documents");
    if (($nbFiles + $nbLinks) > 0) $head[$tab][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
    $head[$tab][2] = 'documents';
    $tab++;

    // Agenda / Events
    $head[$tab][0] = DOL_URL_ROOT.'/contact/agenda.php?id='.$object->id;
    $head[$tab][1] .= $langs->trans("Events");
    if (!empty($conf->agenda->enabled) && (!empty($user->rights->agenda->myactions->read) || !empty($user->rights->agenda->allactions->read)))
    {
        $head[$tab][1] .= '/';
        $head[$tab][1] .= $langs->trans("Agenda");
    }
    $head[$tab][2] = 'agenda';
    $tab++;

    // Log
    /*
    $head[$tab][0] = DOL_URL_ROOT.'/contact/info.php?id='.$object->id;
	$head[$tab][1] = $langs->trans("Info");
	$head[$tab][2] = 'info';
	$tab++;*/

	complete_head_from_modules($conf, $langs, $object, $head, $tab, 'contact', 'remove');

	return $head;
}
