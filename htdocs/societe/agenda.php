<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005      Brice Davoleau       <brice.davoleau@gmail.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2006-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007      Patrick Raguin  		<patrick.raguin@gmail.com>
 * Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
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
 *  \file       htdocs/societe/agenda.php
 *  \ingroup    societe
 *  \brief      Page of third party events
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

$langs->load("companies");

// Security check
$socid = GETPOST('socid','int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe', $socid, '&societe');

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('agendathirdparty'));


/*
 *	Actions
 */

$parameters=array('id'=>$socid);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
$error=$hookmanager->error; $errors=array_merge($errors, (array) $hookmanager->errors);




/*
 *	View
 */

$contactstatic = new Contact($db);

$form = new Form($db);

/*
 * Fiche categorie de client et/ou fournisseur
 */
if ($socid)
{
	require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

	$langs->load("companies");


	$soc = new Societe($db);
	$result = $soc->fetch($socid);
	llxHeader("",$langs->trans("Agenda"),'');

	if (! empty($conf->notification->enabled)) $langs->load("mails");
	$head = societe_prepare_head($soc);

	dol_fiche_head($head, 'agenda', $langs->trans("ThirdParty"),0,'company');

	print '<table class="border" width="100%">';

	print '<tr><td width="25%">'.$langs->trans("ThirdPartyName").'</td><td colspan="3">';
	print $form->showrefnav($soc,'socid','',($user->societe_id?0:1),'rowid','nom');
	print '</td></tr>';

    if (! empty($conf->global->SOCIETE_USEPREFIX))  // Old not used prefix field
    {
        print '<tr><td>'.$langs->trans('Prefix').'</td><td colspan="3">'.$soc->prefix_comm.'</td></tr>';
    }

	if ($soc->client)
	{
		print '<tr><td>';
		print $langs->trans('CustomerCode').'</td><td colspan="3">';
		print $soc->code_client;
		if ($soc->check_codeclient() <> 0) print ' <font class="error">('.$langs->trans("WrongCustomerCode").')</font>';
		print '</td></tr>';
	}

	if ($soc->fournisseur)
	{
		print '<tr><td>';
		print $langs->trans('SupplierCode').'</td><td colspan="3">';
		print $soc->code_fournisseur;
		if ($soc->check_codefournisseur() <> 0) print ' <font class="error">('.$langs->trans("WrongSupplierCode").')</font>';
		print '</td></tr>';
	}

	if (! empty($conf->barcode->enabled))
	{
		print '<tr><td>'.$langs->trans('Gencod').'</td><td colspan="3">'.$soc->barcode.'</td></tr>';
	}

	print "<tr><td valign=\"top\">".$langs->trans('Address')."</td><td colspan=\"3\">";
	dol_print_address($soc->address, 'gmap', 'thirdparty', $soc->id);
	print "</td></tr>";

	// Zip / Town
	print '<tr><td width="25%">'.$langs->trans('Zip').'</td><td width="25%">'.$soc->zip."</td>";
	print '<td width="25%">'.$langs->trans('Town').'</td><td width="25%">'.$soc->town."</td></tr>";

	// Country
	if ($soc->country) {
		print '<tr><td>'.$langs->trans('Country').'</td><td colspan="3">';
		$img=picto_from_langcode($soc->country_code);
		print ($img?$img.' ':'');
		print $soc->country;
		print '</td></tr>';
	}

	// EMail
	print '<tr><td>'.$langs->trans('EMail').'</td><td colspan="3">';
	print dol_print_email($soc->email,0,$soc->id,'AC_EMAIL');
	print '</td></tr>';

	// Web
	print '<tr><td>'.$langs->trans('Web').'</td><td colspan="3">';
	print dol_print_url($soc->url);
	print '</td></tr>';

	// Phone / Fax
	print '<tr><td>'.$langs->trans('Phone').'</td><td>'.dol_print_phone($soc->tel,$soc->country_code,0,$soc->id,'AC_TEL').'</td>';
	print '<td>'.$langs->trans('Fax').'</td><td>'.dol_print_phone($soc->fax,$soc->country_code,0,$soc->id,'AC_FAX').'</td></tr>';

	print '</table>';

	print '</div>';


    /*
     * Barre d'action
     */

    print '<div class="tabsAction">';

    if (! empty($conf->agenda->enabled))
    {
        print '<a class="butAction" href="'.DOL_URL_ROOT.'/comm/action/fiche.php?action=create&socid='.$socid.'">'.$langs->trans("AddAction").'</a>';
    }

    print '</div>';

    print '<br>';

    print load_fiche_titre($langs->trans("ActionsOnCompany"),'','');

    // List of todo actions
    show_actions_todo($conf,$langs,$db,$soc);

    // List of done actions
    show_actions_done($conf,$langs,$db,$soc);
}


llxFooter();

$db->close();
?>
