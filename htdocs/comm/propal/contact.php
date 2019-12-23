<?php
/* Copyright (C) 2005      Patrick Rouillon     <patrick@rouillon.net>
 * Copyright (C) 2005-2016 Destailleur Laurent  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2011-2015 Philippe Grand       <philippe.grand@atoo-net.com>
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
 */

/**
 *       \file       htdocs/comm/propal/contact.php
 *       \ingroup    propal
 *       \brief      Onglet de gestion des contacts de propal
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/propal.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

// Load translation files required by the page
$langs->loadLangs(array('facture', 'orders', 'sendings', 'companies'));

$id=GETPOST('id', 'int');
$ref= GETPOST('ref', 'alpha');
$lineid=GETPOST('lineid', 'int');
$action=GETPOST('action', 'alpha');

// Security check
if ($user->socid) $socid=$user->socid;
$result = restrictedArea($user, 'propal', $id);

$object = new Propal($db);

// Load object
if ($id > 0 || ! empty($ref))
{
	$ret=$object->fetch($id, $ref);
	if ($ret == 0)
	{
		$langs->load("errors");
		setEventMessages($langs->trans('ErrorRecordNotFound'), null, 'errors');
		$error++;
	}
	elseif ($ret < 0)
	{
		setEventMessages($object->error, $object->errors, 'errors');
		$error++;
	}
}
if (!$error)
{
	$object->fetch_thirdparty();
}
else
{
	header('Location: '.DOL_URL_ROOT.'/comm/propal/list.php');
	exit;
}


/*
 * Add a new contact
 */

if ($action == 'addcontact' && $user->rights->propale->creer)
{
    if ($object->id > 0)
    {
    	$contactid = (GETPOST('userid', 'int') ? GETPOST('userid', 'int') : GETPOST('contactid', 'int'));
  		$result = $object->add_contact($contactid, $_POST["type"], $_POST["source"]);
    }

	if ($result >= 0)
	{
		header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
		exit;
	}
	else
	{
		if ($object->error == 'DB_ERROR_RECORD_ALREADY_EXISTS')
		{
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType"), null, 'errors');
		}
		else
		{
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
}

// Toggle the status of a contact
elseif ($action == 'swapstatut' && $user->rights->propale->creer)
{
	if ($object->id > 0)
	{
	    $result = $object->swapContactStatus(GETPOST('ligne'));
	}
}

// Deletes a contact
elseif ($action == 'deletecontact' && $user->rights->propale->creer)
{
	$result = $object->delete_contact($lineid);

	if ($result >= 0)
	{
		header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}
/*
elseif ($action == 'setaddress' && $user->rights->propale->creer)
{
	$result=$object->setDeliveryAddress($_POST['fk_address']);
	if ($result < 0) dol_print_error($db,$object->error);
}*/


/*
 * View
 */

llxHeader('', $langs->trans('Proposal'), 'EN:Commercial_Proposals|FR:Proposition_commerciale|ES:Presupuestos');

$form = new Form($db);
$formcompany = new FormCompany($db);
$formother = new FormOther($db);

if ($object->id > 0)
{
    $head = propal_prepare_head($object);
	dol_fiche_head($head, 'contact', $langs->trans("Proposal"), -1, 'propal');


	// Proposal card

	$linkback = '<a href="' . DOL_URL_ROOT . '/comm/propal/list.php?restore_lastsearch_values=1' . (! empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';


	$morehtmlref='<div class="refidno">';
	// Ref customer
	$morehtmlref.=$form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', 0, 1);
	$morehtmlref.=$form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', null, null, '', 1);
	// Thirdparty
	$morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $object->thirdparty->getNomUrl(1, 'customer');
	// Project
	if (! empty($conf->projet->enabled))
	{
	    $langs->load("projects");
	    $morehtmlref.='<br>'.$langs->trans('Project') . ' ';
	    if ($user->rights->propal->creer)
	    {
            if ($action != 'classify') {
                //$morehtmlref.='<a class="editfielda" href="' . $_SERVER['PHP_SELF'] . '?action=classify&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a>';
                $morehtmlref.=' : ';
            }
            if ($action == 'classify') {
                //$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
                $morehtmlref.='<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
                $morehtmlref.='<input type="hidden" name="action" value="classin">';
                $morehtmlref.='<input type="hidden" name="token" value="'.newToken().'">';
                $morehtmlref.=$formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
                $morehtmlref.='<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
                $morehtmlref.='</form>';
            } else {
                $morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
            }
	    } else {
	        if (! empty($object->fk_project)) {
	            $proj = new Project($db);
	            $proj->fetch($object->fk_project);
	            $morehtmlref.='<a href="'.DOL_URL_ROOT.'/projet/card.php?id=' . $object->fk_project . '" title="' . $langs->trans('ShowProject') . '">';
	            $morehtmlref.=$proj->ref;
	            $morehtmlref.='</a>';
	        } else {
	            $morehtmlref.='';
	        }
	    }
	}
	$morehtmlref.='</div>';

	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref, '', 0, '', '', 1);

	dol_fiche_end();


	// Contacts lines (modules that overwrite templates must declare this into descriptor)
	$dirtpls = array_merge($conf->modules_parts['tpl'], array('/core/tpl'));
	foreach ($dirtpls as $reldir)
	{
		$res = @include dol_buildpath($reldir.'/contacts.tpl.php');
		if ($res) break;
	}
}

// End of page
llxFooter();
$db->close();
