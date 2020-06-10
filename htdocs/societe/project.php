<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005      Brice Davoleau       <brice.davoleau@gmail.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2006-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007      Patrick Raguin  		<patrick.raguin@gmail.com>
 * Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
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
 *  \file       htdocs/societe/project.php
 *  \ingroup    societe
 *  \brief      Page of third party projects
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

$langs->loadLangs(array("companies", "projects"));

// Security check
$socid = GETPOST('socid', 'int');
if ($user->socid) $socid=$user->socid;
$result = restrictedArea($user, 'societe', $socid, '&societe');

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('projectthirdparty'));


/*
 *	Actions
 */

$parameters=array('id'=>$socid);
$reshook=$hookmanager->executeHooks('doActions', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');



/*
 *	View
 */

$contactstatic = new Contact($db);

$form = new Form($db);

if ($socid)
{
	require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

	$langs->load("companies");


	$object = new Societe($db);
	$result = $object->fetch($socid);

	$title=$langs->trans("Projects");
	if (! empty($conf->global->MAIN_HTML_TITLE) && preg_match('/thirdpartynameonly/', $conf->global->MAIN_HTML_TITLE) && $object->name) $title=$object->name." - ".$title;
	llxHeader('', $title);

	if (! empty($conf->notification->enabled)) $langs->load("mails");
	$head = societe_prepare_head($object);

	dol_fiche_head($head, 'project', $langs->trans("ThirdParty"), -1, 'company');

    $linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

    dol_banner_tab($object, 'socid', $linkback, ($user->socid?0:1), 'rowid', 'nom');

    print '<div class="fichecenter">';

    print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">';

    if (! empty($conf->global->SOCIETE_USEPREFIX))  // Old not used prefix field
    {
        print '<tr><td>'.$langs->trans('Prefix').'</td><td colspan="3">'.$object->prefix_comm.'</td></tr>';
    }

	if ($object->client)
	{
		print '<tr><td class="titlefield">';
		print $langs->trans('CustomerCode').'</td><td colspan="3">';
		print $object->code_client;
		if ($object->check_codeclient() <> 0) print ' <font class="error">('.$langs->trans("WrongCustomerCode").')</font>';
		print '</td></tr>';
	}

	if ($object->fournisseur)
	{
		print '<tr><td class="titlefield">';
		print $langs->trans('SupplierCode').'</td><td colspan="3">';
		print $object->code_fournisseur;
		if ($object->check_codefournisseur() <> 0) print ' <font class="error">('.$langs->trans("WrongSupplierCode").')</font>';
		print '</td></tr>';
	}

	print '</table>';

	print '</div>';

	dol_fiche_end();

	$params = '';

	$newcardbutton .= dolGetButtonTitle($langs->trans("NewProject"), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/projet/card.php?action=create&socid='.$object->id.'&amp;backtopage='.urlencode($backtopage), '', 1, $params);

    print '<br>';


	// Projects list
	$result=show_projects($conf, $langs, $db, $object, $_SERVER["PHP_SELF"].'?socid='.$object->id, 1, $addbutton);
	print '<br><br><br>';
	if(!empty($user->rights->projet->lire)) {
	$draft =	"Brouillon <img src=\"/htdocs/theme/eldy/img/statut0.png\" alt=\"\" title=\"Brouillon\" class=\"inline-block\">";
	$current ="Ouvert <img src=\"/htdocs/theme/eldy/img/statut4.png\" alt=\"\" title=\"Ouvert\" class=\"inline-block\"></td>";
	$closed ="Clôturé <img src=\"/htdocs/theme/eldy/img/statut6.png\" alt=\"\" title=\"Clôturé\" class=\"inline-block\"></td>";
	$arr= $object->GetOnContactProjects();
		print "<table summary=\"\" class=\"centpercent notopnoleftnoright\" style=\"margin-bottom: 2px;\">
    <tbody>
        <tr>
            <td class=\"nobordernopadding\" valign=\"middle\">
                <div class=\"titre\">
                En contact sur les projets des 12 derniers mois :
                </div>
            </td>
        </tr>
    </tbody>
	</table><br>
	<div class=\"div-table-responsive\">
	<table class=\"noborder\" width=\"100%\">
		<tbody>
			<tr class=\"liste_titre\">
				<td>Réf. et date du Projet</td>
				<td class=\"left\">Nom du Projet</td>
				<td class=\"left\">Contact</td>
				<td class=\"center\">Type du contact</td>
				<td class=\"center\">État</td>
			</tr>";
	foreach ($arr as $proj) {
		print "<tr class=\"oddeven\">
			<td><a href=\"/htdocs/projet/card.php?id=".$proj->prowid."\"><img src=\"/htdocs/theme/eldy/img/object_projectpub.png\" alt=\"\" title=\"Afficher chantier\" class=\"inline-block\">".$proj->ref."</a> créé le ".date('d/m/Y',strtotime($proj->pdate))."</td>
			<td class=\"left\">".$proj->title."</td>
			<td class=\"left\"><a href=\"/htdocs/contact/card.php?id=".$proj->crowid."\"><img height=\"50%\" src=\"/htdocs/public/theme/common/user_man.png\" alt=\"\" title=\"Afficher contact\" class=\"inline-block\">".$proj->cname."</a></td>
			<td class=\"center\">".$proj->ctype."</td>";
			switch ($proj->pstatut) {
				case 0:
					print "<td class=\"center\">".$draft."</td>";
					break;
				case 1:
					print "<td class=\"center\">".$current."</td>";
					break;
				case 2:
				print "<td class=\"center\">".$closed."</td>";
					break;
			}
		print	"</tr>
			";
	
}
print "</tbody>
</table>
</div>";
}
}

// End of page
llxFooter();
$db->close();
