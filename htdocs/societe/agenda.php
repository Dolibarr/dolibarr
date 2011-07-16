<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005      Brice Davoleau       <brice.davoleau@gmail.com>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2006-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007      Patrick Raguin  		<patrick.raguin@gmail.com>
 * Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2010-2011 Herve Prot           <herve.prot@symeos.com>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *  \file       htdocs/societe/agenda.php
 *  \ingroup    societe
 *  \brief      Page of third party events
 *  \version    $Id$
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
if ($conf->highcharts->enabled) require_once(DOL_DOCUMENT_ROOT."/highCharts/class/highCharts.class.php");

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

        $var=false;
        print '<table width="100%"><tr><td valign="top" width="50%">';
	print '<table class="noborder" width="100%">';

	print '<tr class="liste_titre"><td colspan="4">';
	print $html->showrefnav($soc,'socid','',($user->societe_id?0:1),'rowid','nom');
	print '</td></tr>';

	// Name
	print '<tr '.$bc[$var].'><td id="label" width="20%">'.$langs->trans('Name').'</td>';
	print '<td colspan="1" id="value" width="30%">';
	print $soc->getNomUrl(1);
	print '</td>';

    if (! empty($conf->global->SOCIETE_USEPREFIX))  // Old not used prefix field
    {
        print '<tr><td>'.$langs->trans('Prefix').'</td><td colspan="3">'.$soc->prefix_comm.'</td></tr>';
    }

	print '<td width="20%" id="label">'.$langs->trans('Prefix').'</td><td colspan="1"  id="value">'.$soc->prefix_comm.'</td></tr>';
        $var=!$var;

	if ($soc->client)
	{
		print '<tr '.$bc[$var].'><td  colspan="3" id="label">';
		print $langs->trans('CustomerCode').'</td><td id="value">';
		print $soc->code_client;
		if ($soc->check_codeclient() <> 0) print ' <font class="error">('.$langs->trans("WrongCustomerCode").')</font>';
		print '</td></tr>';
        $var=!$var;
	}

	if ($soc->fournisseur)
	{
		print '<tr '.$bc[$var].'><td colspan="3" id="label">';
		print $langs->trans('SupplierCode').'</td><td  id="value">';
		print $soc->code_fournisseur;
		if ($soc->check_codefournisseur() <> 0) print ' <font class="error">('.$langs->trans("WrongSupplierCode").')</font>';
		print '</td></tr>';
                $var=!$var;
	}

	if ($conf->global->MAIN_MODULE_BARCODE)
	{
		print '<tr '.$bc[$var].'><td id="label">'.$langs->trans('Gencod').'</td><td colspan="3" id="value">'.$soc->gencod.'</td></tr>';
                $var=!$var;
	}

	print "<tr ".$bc[$var]."><td valign=\"top\" id=\"label\">".$langs->trans('Address')."</td><td colspan=\"3\" id=\"value\">".nl2br($soc->address)."</td></tr>";
        $var=!$var;

	// Zip / Town
    print '<tr '.$bc[$var].'><td id="label" width="25%">'.$langs->trans('Zip').' / '.$langs->trans("Town").'</td><td id="value" colspan="3">';
    print $soc->cp.($soc->cp && $soc->ville?" / ":"").$soc->ville;
    print "</td>";
    print '</tr>';
        $var=!$var;

	// Country
    print '<tr '.$bc[$var].'><td id="label">'.$langs->trans("Country").'</td><td id="value" nowrap="nowrap">';
    $img=picto_from_langcode($soc->pays_code);
    if ($soc->isInEEC()) print $html->textwithpicto(($img?$img.' ':'').$soc->pays,$langs->trans("CountryIsInEEC"),1,0);
    else print ($img?$img.' ':'').$soc->pays;
    print '</td>';
        
    // MAP GPS
    if($conf->map->enabled)
        print '<td id="label" colspan="2">GPS '.img_picto(($soc->lat.','.$soc->lng),(($soc->lat && $soc->lng)?"statut4":"statut1")).'</td></tr>';
    else
        print '<td id="label" colspan="2"></td></tr>';
    $var=!$var;


	print '<tr '.$bc[$var].'><td id="label">'.$langs->trans('Phone').'</td><td id="value">'.dol_print_phone($soc->tel,$soc->pays_code,0,$soc->id,'AC_TEL').'</td>';
	print '<td id="label">'.$langs->trans('Fax').'</td><td id="value">'.dol_print_phone($soc->fax,$soc->pays_code,0,$soc->id,'AC_FAX').'</td></tr>';
        $var=!$var;

	// EMail
	print '<tr '.$bc[$var].'><td id="label">'.$langs->trans('EMail').'</td><td id="value">';
	print dol_print_email($soc->email,0,$soc->id,'AC_EMAIL');
	print '</td>';

	// Web
	print '<td id="label">'.$langs->trans('Web').'</td><td id="value">';
	print dol_print_url($soc->url);
	print '</td></tr>';
        $var=!$var;

	print '</table>';
        print '</td>';
        print '<td valign="top" width="50%" class="notopnoright">';
        if($conf->highcharts->enabled && $user->rights->highcharts->read )
                {
                    $langs->load("highcharts@highCharts");
                    
                    $graph = new HighCharts($db);
                    $graph->height = '300px';
                    $graph->socid = $soc->id;
                    $graph->label = $langs->trans("ActivityHistory");
                    if($user->rights->highcharts->all)
                            $graph->mine=0;
                    $graph->graphTaskDone(0,0,1);
                }
        print '</td>';
        print '</tr>';
        print '</table>';

	print '</div>';

	if ($mesg) print($mesg);

    /*
     * Barre d'action
     */

    print '<div class="tabsAction">';

    if ($conf->agenda->enabled)
    {
        print '<a class="butAction" href="'.DOL_URL_ROOT.'/comm/action/fiche.php?action=create&socid='.$socid.'&backtopage='.$_SERVER["PHP_SELF"].'?socid='.$socid.'">'.$langs->trans("AddAction").'</a>';
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

llxFooter('$Date$ - $Revision$');
?>
