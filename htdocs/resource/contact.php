<?php
/* Copyright (C) 2005-2012  Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2007-2009  Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2012       Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2016		    Gilles Poirier		   <glgpoirier@gmail.com>

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
 */

/**
 *       \file       htdocs/resource/contact.php
 *       \ingroup    resource
 *       \brief      Onglet de gestion des contacts des resources
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/resource/class/dolresource.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/resource.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';

// Load translation files required by the page
$langs->loadLangs(array('resource', 'sendings', 'companies'));

$id = GETPOST('id','int');
$ref = GETPOST('ref','alpha');
$action = GETPOST('action','alpha');

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'resource', $id, 'resource');

$object = new DolResource($db);
$result = $object->fetch($id,$ref);


/*
 * Ajout d'un nouveau contact
 */

if ($action == 'addcontact' && $user->rights->resource->write)
{
    if ($result > 0 && $id > 0)
    {
    	$contactid = (GETPOST('userid','int') ? GETPOST('userid','int') : GETPOST('contactid','int'));
  		$result = $object->add_contact($contactid, GETPOST('type','int'), GETPOST('source','alpha'));
    }

	if ($result >= 0)
	{
		header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
		exit;
	}
	else
	{
		if ($object->error == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
			$langs->load("errors");
			$mesg = $langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType");
		} else {
			$mesg = $object->error;
		}

		setEventMessage($mesg, 'errors');
	}
}

// bascule du statut d'un contact
else if ($action == 'swapstatut' && $user->rights->resource->write)
{
    $result=$object->swapContactStatus(GETPOST('ligne','int'));
}

// Efface un contact
else if ($action == 'deletecontact' && $user->rights->resource->write)
{
	$result = $object->delete_contact(GETPOST('lineid','int'));

	if ($result >= 0)
	{
		header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
		exit;
	}
	else {
		dol_print_error($db);
	}
}


/*
 * View
 */

$form = new Form($db);
$formcompany = new FormCompany($db);
$contactstatic=new Contact($db);
$userstatic=new User($db);

llxHeader('',$langs->trans("Resource"));

// Mode vue et edition

if ($id > 0 || ! empty($ref))
{
	$soc = new Societe($db);
	$soc->fetch($object->socid);


	$head = resource_prepare_head($object);
	dol_fiche_head($head, 'contact', $langs->trans("ResourceSingular"), -1, 'resource');


	$linkback = '<a href="' . DOL_URL_ROOT . '/resource/list.php' . (! empty($socid) ? '?id=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';


	$morehtmlref='<div class="refidno">';
	$morehtmlref.='</div>';


	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';


	// Object

	print '<table width="100%" class="border">';

	// Resource type
	print '<tr>';
	print '<td class="titlefield">' . $langs->trans("ResourceType") . '</td>';
	print '<td>';
	print $object->type_label;
	print '</td>';
	print '</tr>';

	print '</table>';
	print '</div>';

	dol_fiche_end();

	print '<br>';

	if (! empty($conf->global->RESOURCE_HIDE_ADD_CONTACT_USER))     $hideaddcontactforuser=1;
	if (! empty($conf->global->RESOURCE_HIDE_ADD_CONTACT_THIPARTY)) $hideaddcontactforthirdparty=1;

	$permission=1;
	// Contacts lines
	include DOL_DOCUMENT_ROOT.'/core/tpl/contacts.tpl.php';
}


llxFooter();
$db->close();
