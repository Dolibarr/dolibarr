<?php
/* Copyright (C) 2005		Patrick Rouillon	<patrick@rouillon.net>
 * Copyright (C) 2005-2009	Destailleur Laurent	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin		<regis.houssin@capnetworks.com>
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
 *      \file       htdocs/contrat/contact.php
 *      \ingroup    contrat
 *      \brief      Onglet de gestion des contacts des contrats
 */

require ("../main.inc.php");
require_once DOL_DOCUMENT_ROOT.'/core/lib/contract.lib.php';
require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';


$langs->load("contracts");
$langs->load("companies");

$action=GETPOST('action','alpha');
$confirm=GETPOST('confirm','alpha');
$socid = GETPOST('socid','int');
$id = GETPOST('id','int');
$ref=GETPOST('ref','alpha');

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'contrat',$id);

$object = new Contrat($db);


/*
 * Ajout d'un nouveau contact
 */

if ($action == 'addcontact' && $user->rights->contrat->creer)
{
	$result = $object->fetch($id);

    if ($result > 0 && $id > 0)
    {
    	$contactid = (GETPOST('userid') ? GETPOST('userid') : GETPOST('contactid'));
  		$result = $object->add_contact($contactid, $_POST["type"], $_POST["source"]);
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
			$msg = $langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType");
		} else {
			$mesg = $object->error;
		}

		setEventMessages($mesg, null, 'errors');
	}
}

// bascule du statut d'un contact
if ($action == 'swapstatut' && $user->rights->contrat->creer)
{
	if ($object->fetch($id))
	{
	    $result=$object->swapContactStatus(GETPOST('ligne'));
	}
	else
	{
		dol_print_error($db,$object->error);
	}
}

// Efface un contact
if ($action == 'deletecontact' && $user->rights->contrat->creer)
{
	$object->fetch($id);
	$result = $object->delete_contact($_GET["lineid"]);

	if ($result >= 0)
	{
		header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
		exit;
	}
}


/*
 * View
 */

llxHeader('', $langs->trans("ContractCard"), "Contrat");

$form = new Form($db);
$formcompany= new FormCompany($db);
$contactstatic=new Contact($db);
$userstatic=new User($db);

/* *************************************************************************** */
/*                                                                             */
/* Mode vue et edition                                                         */
/*                                                                             */
/* *************************************************************************** */

if ($id > 0 || ! empty($ref))
{
	if ($object->fetch($id, $ref) > 0)
	{
		$object->fetch_thirdparty();

	    $head = contract_prepare_head($object);

		$hselected=1;

		dol_fiche_head($head, $hselected, $langs->trans("Contract"), 0, 'contract');

		/*
		 *   Contrat
		 */
		print '<table class="border" width="100%">';

		$linkback = '<a href="'.DOL_URL_ROOT.'/contrat/list.php'.(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';

		// Reference du contrat
		print '<tr><td width="25%">'.$langs->trans("Ref").'</td><td colspan="3">';
		print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref', '');
		print "</td></tr>";

		// Customer
		print "<tr><td>".$langs->trans("Customer")."</td>";
        print '<td colspan="3">'.$object->thirdparty->getNomUrl(1).'</td></tr>';

		// Ligne info remises tiers
	    print '<tr><td>'.$langs->trans('Discount').'</td><td>';
		if ($object->thirdparty->remise_percent) print $langs->trans("CompanyHasRelativeDiscount",$object->thirdparty->remise_percent);
		else print $langs->trans("CompanyHasNoRelativeDiscount");
		$absolute_discount=$object->thirdparty->getAvailableDiscounts();
		print '. ';
		if ($absolute_discount) print $langs->trans("CompanyHasAbsoluteDiscount",$absolute_discount,$langs->trans("Currency".$conf->currency));
		else print $langs->trans("CompanyHasNoAbsoluteDiscount");
		print '.';
		print '</td></tr>';

		print "</table>";

		print '</div>';

		print '<br>';

		// Contacts lines
		include DOL_DOCUMENT_ROOT.'/core/tpl/contacts.tpl.php';

	}
	else
	{
		print "ErrorRecordNotFound";
	}
}


llxFooter();
$db->close();
