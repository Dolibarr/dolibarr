<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005      Brice Davoleau       <brice.davoleau@gmail.com>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2006-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007      Patrick Raguin  		<patrick.raguin@gmail.com>
 * Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *  \version    $Id: agenda.php,v 1.21 2011/07/31 23:22:57 eldy Exp $
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");

$langs->load("companies");

$mesg=isset($_GET["mesg"])?'<div class="ok">'.$_GET["mesg"].'</div>':'';

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe', $socid);


/*
 *	Actions
 */





/*
 *	View
 */

$contactstatic = new Contact($db);

$html = new Form($db);

/*
 * Fiche categorie de client et/ou fournisseur
 */
if ($_GET["socid"])
{
	require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");
	require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");

	$langs->load("companies");


	$soc = new Societe($db);
	$result = $soc->fetch($_GET["socid"]);
	llxHeader("",$langs->trans("Agenda"),$langs->trans("Category"));

	if ($conf->notification->enabled) $langs->load("mails");
	$head = societe_prepare_head($soc);

	dol_fiche_head($head, 'agenda', $langs->trans("ThirdParty"),0,'company');

	print '<table class="border" width="100%">';

	print '<tr><td width="25%">'.$langs->trans("ThirdPartyName").'</td><td colspan="3">';
	print $html->showrefnav($soc,'socid','',($user->societe_id?0:1),'rowid','nom');
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

	if ($conf->global->MAIN_MODULE_BARCODE)
	{
		print '<tr><td>'.$langs->trans('Gencod').'</td><td colspan="3">'.$soc->gencod.'</td></tr>';
	}

	print "<tr><td valign=\"top\">".$langs->trans('Address')."</td><td colspan=\"3\">";
	dol_print_address($soc->address, 'gmap', 'thirdparty', $soc->id);
	print "</td></tr>";

	// Zip / Town
	print '<tr><td width="25%">'.$langs->trans('Zip').'</td><td width="25%">'.$soc->cp."</td>";
	print '<td width="25%">'.$langs->trans('Town').'</td><td width="25%">'.$soc->ville."</td></tr>";

	// Country
	if ($soc->pays) {
		print '<tr><td>'.$langs->trans('Country').'</td><td colspan="3">';
		$img=picto_from_langcode($soc->pays_code);
		print ($img?$img.' ':'');
		print $soc->pays;
		print '</td></tr>';
	}

	print '<tr><td>'.$langs->trans('Phone').'</td><td>'.dol_print_phone($soc->tel,$soc->pays_code,0,$soc->id,'AC_TEL').'</td>';
	print '<td>'.$langs->trans('Fax').'</td><td>'.dol_print_phone($soc->fax,$soc->pays_code,0,$soc->id,'AC_FAX').'</td></tr>';

	// EMail
	print '<tr><td>'.$langs->trans('EMail').'</td><td>';
	print dol_print_email($soc->email,0,$soc->id,'AC_EMAIL');
	print '</td>';

	// Web
	print '<td>'.$langs->trans('Web').'</td><td>';
	print dol_print_url($soc->url);
	print '</td></tr>';

	print '</table>';

	print '</div>';

	if ($mesg) print($mesg);

    /*
     * Barre d'action
     */

    print '<div class="tabsAction">';

    if ($conf->agenda->enabled)
    {
        print '<a class="butAction" href="'.DOL_URL_ROOT.'/comm/action/fiche.php?action=create&socid='.$socid.'">'.$langs->trans("AddAction").'</a>';
    }

    print '</div>';

    print '<br>';

/*
    if ($conf->global->MAIN_REPEATCONTACTONEACHTAB)
    {
        // List of contacts
        show_contacts($conf,$langs,$db,$societe);
    }

    if ($conf->global->MAIN_REPEATTASKONEACHTAB)
    {
*/
        // List of todo actions
        show_actions_todo($conf,$langs,$db,$soc);

        // List of done actions
        show_actions_done($conf,$langs,$db,$soc);
//    }

}






$db->close();

llxFooter('$Date: 2011/07/31 23:22:57 $ - $Revision: 1.21 $');
?>
