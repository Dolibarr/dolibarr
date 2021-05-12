<?php
<<<<<<< HEAD
/* Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
=======
/* Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 * Copyright (C) 2007-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2012      Juanjo Menent        <jmenent@2byte.es>
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
 *       \file       htdocs/fichinter/contact.php
 *       \ingroup    fichinter
 *       \brief      Onglet de gestion des contacts de fiche d'intervention
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/fichinter.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

// Load translation files required by the page
$langs->loadLangs(array('interventions', 'sendings', 'companies'));

<<<<<<< HEAD
$id = GETPOST('id','int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action','alpha');
=======
$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'alpha');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'ficheinter', $id, 'fichinter');

$object = new Fichinter($db);
<<<<<<< HEAD
$result = $object->fetch($id,$ref);
=======
$result = $object->fetch($id, $ref);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
if (! $result)
{
    print 'Record not found';
    exit;
}

/*
 * Adding a new contact
 */

if ($action == 'addcontact' && $user->rights->ficheinter->creer)
{
    if ($result > 0 && $id > 0)
    {
<<<<<<< HEAD
    	$contactid = (GETPOST('userid','int') ? GETPOST('userid','int') : GETPOST('contactid','int'));
  		$result = $object->add_contact($contactid, GETPOST('type','int'), GETPOST('source','alpha'));
=======
    	$contactid = (GETPOST('userid', 'int') ? GETPOST('userid', 'int') : GETPOST('contactid', 'int'));
  		$result = $object->add_contact($contactid, GETPOST('type', 'int'), GETPOST('source', 'alpha'));
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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

		setEventMessages($mesg, null, 'errors');
	}
}

// Toggle the status of a contact
<<<<<<< HEAD
else if ($action == 'swapstatut' && $user->rights->ficheinter->creer)
{
    $result=$object->swapContactStatus(GETPOST('ligne','int'));
}

// Deletes a contact
else if ($action == 'deletecontact' && $user->rights->ficheinter->creer)
{
	$result = $object->delete_contact(GETPOST('lineid','int'));
=======
elseif ($action == 'swapstatut' && $user->rights->ficheinter->creer)
{
    $result=$object->swapContactStatus(GETPOST('ligne', 'int'));
}

// Deletes a contact
elseif ($action == 'deletecontact' && $user->rights->ficheinter->creer)
{
	$result = $object->delete_contact(GETPOST('lineid', 'int'));
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

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
$formproject=new FormProjets($db);

<<<<<<< HEAD
llxHeader('',$langs->trans("Intervention"));
=======
llxHeader('', $langs->trans("Intervention"));
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

// Mode vue et edition

if ($id > 0 || ! empty($ref))
{
	$object->fetch_thirdparty();

	$head = fichinter_prepare_head($object);
	dol_fiche_head($head, 'contact', $langs->trans("InterventionCard"), -1, 'intervention');


	// Intervention card
	$linkback = '<a href="'.DOL_URL_ROOT.'/fichinter/list.php?restore_lastsearch_values=1'.(! empty($socid)?'&socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';


	$morehtmlref='<div class="refidno">';
	// Ref customer
	//$morehtmlref.=$form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', 0, 1);
	//$morehtmlref.=$form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', null, null, '', 1);
	// Thirdparty
	$morehtmlref.=$langs->trans('ThirdParty') . ' : ' . $object->thirdparty->getNomUrl(1);
	// Project
	if (! empty($conf->projet->enabled))
	{
	    $langs->load("projects");
	    $morehtmlref.='<br>'.$langs->trans('Project') . ' ';
	    if ($user->rights->ficheinter->creer)
	    {
	        if ($action != 'classify')
	            //$morehtmlref.='<a href="' . $_SERVER['PHP_SELF'] . '?action=classify&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> : ';
	            $morehtmlref.=' : ';
	        	if ($action == 'classify') {
	                //$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
	                $morehtmlref.='<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
	                $morehtmlref.='<input type="hidden" name="action" value="classin">';
	                $morehtmlref.='<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
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

	print '<br>';

	if (! empty($conf->global->FICHINTER_HIDE_ADD_CONTACT_USER))     $hideaddcontactforuser=1;
	if (! empty($conf->global->FICHINTER_HIDE_ADD_CONTACT_THIPARTY)) $hideaddcontactforthirdparty=1;

	// Contacts lines (modules that overwrite templates must declare this into descriptor)
<<<<<<< HEAD
	$dirtpls=array_merge($conf->modules_parts['tpl'],array('/core/tpl'));
=======
	$dirtpls=array_merge($conf->modules_parts['tpl'], array('/core/tpl'));
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	foreach($dirtpls as $reldir)
	{
	    $res=@include dol_buildpath($reldir.'/contacts.tpl.php');
	    if ($res) break;
	}
<<<<<<< HEAD

=======
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
}


llxFooter();
$db->close();
