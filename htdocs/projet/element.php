<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis@dolibarr.fr>
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
 *      \file       htdocs/projet/element.php
 *      \ingroup    projet facture
 *		\brief      Page of project referrers
 *		\version    $Id: element.php,v 1.49 2011/07/31 23:23:39 eldy Exp $
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/project.lib.php");
if ($conf->propal->enabled)      require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
if ($conf->facture->enabled)     require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
if ($conf->facture->enabled)     require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture-rec.class.php");
if ($conf->commande->enabled)    require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
if ($conf->fournisseur->enabled) require_once(DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.facture.class.php");
if ($conf->fournisseur->enabled) require_once(DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.commande.class.php");
if ($conf->contrat->enabled)     require_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");
if ($conf->ficheinter->enabled)  require_once(DOL_DOCUMENT_ROOT."/fichinter/class/fichinter.class.php");
if ($conf->deplacement->enabled) require_once(DOL_DOCUMENT_ROOT."/compta/deplacement/class/deplacement.class.php");
if ($conf->agenda->enabled)      require_once(DOL_DOCUMENT_ROOT."/comm/action/class/actioncomm.class.php");

$langs->load("projects");
$langs->load("companies");
$langs->load("suppliers");
if ($conf->facture->enabled)  $langs->load("bills");
if ($conf->commande->enabled) $langs->load("orders");
if ($conf->propal->enabled)   $langs->load("propal");

$projectid='';
$ref='';
if (isset($_GET["id"]))  { $projectid=$_GET["id"]; }
if (isset($_GET["ref"])) { $ref=$_GET["ref"]; }
if ($projectid == '' && $ref == '')
{
	dol_print_error('','Bad parameter');
	exit;
}

$mine = $_REQUEST['mode']=='mine' ? 1 : 0;
//if (! $user->rights->projet->all->lire) $mine=1;	// Special for projects

// Security check
$socid=0;
if ($user->societe_id > 0) $socid=$user->societe_id;
$result = restrictedArea($user, 'projet', $projectid);


/*
 *	View
 */

$help_url="EN:Module_Projects|FR:Module_Projets|ES:M&oacute;dulo_Proyectos";
llxHeader("",$langs->trans("Referers"),$help_url);

$form = new Form($db);

$userstatic=new User($db);

$project = new Project($db);
$project->fetch($_GET["id"],$_GET["ref"]);
$project->societe->fetch($project->societe->id);

// To verify role of users
$userAccess = $project->restrictedProjectArea($user);

$head=project_prepare_head($project);
dol_fiche_head($head, 'element', $langs->trans("Project"),0,($project->public?'projectpub':'project'));


print '<table class="border" width="100%">';

print '<tr><td width="30%">'.$langs->trans("Ref").'</td><td>';
// Define a complementary filter for search of next/prev ref.
$projectsListId = $project->getProjectsAuthorizedForUser($user,$mine,1);
$project->next_prev_filter=" rowid in (".$projectsListId.")";
print $form->showrefnav($project,'ref','',1,'ref','ref');
print '</td></tr>';

print '<tr><td>'.$langs->trans("Label").'</td><td>'.$project->title.'</td></tr>';

print '<tr><td>'.$langs->trans("Company").'</td><td>';
if (! empty($project->societe->id)) print $project->societe->getNomUrl(1);
else print '&nbsp;';
print '</td></tr>';

// Visibility
print '<tr><td>'.$langs->trans("Visibility").'</td><td>';
if ($project->public) print $langs->trans('SharedProject');
else print $langs->trans('PrivateProject');
print '</td></tr>';

// Statut
print '<tr><td>'.$langs->trans("Status").'</td><td>'.$project->getLibStatut(4).'</td></tr>';

print '</table>';

print '</div>';


/*
 * Referers types
 */

$listofreferent=array(
'propal'=>array(
	'title'=>"ListProposalsAssociatedProject",
	'class'=>'Propal',
	'test'=>$conf->propal->enabled),
'order'=>array(
	'title'=>"ListOrdersAssociatedProject",
	'class'=>'Commande',
	'test'=>$conf->commande->enabled),
'invoice'=>array(
	'title'=>"ListInvoicesAssociatedProject",
	'class'=>'Facture',
	'test'=>$conf->facture->enabled),
'invoice_predefined'=>array(
	'title'=>"ListPredefinedInvoicesAssociatedProject",
	'class'=>'FactureRec',
	'test'=>$conf->facture->enabled),
'order_supplier'=>array(
	'title'=>"ListSupplierOrdersAssociatedProject",
	'class'=>'CommandeFournisseur',
	'test'=>$conf->fournisseur->enabled),
'invoice_supplier'=>array(
	'title'=>"ListSupplierInvoicesAssociatedProject",
	'class'=>'FactureFournisseur',
	'test'=>$conf->fournisseur->enabled),
'contract'=>array(
	'title'=>"ListContractAssociatedProject",
	'class'=>'Contrat',
	'test'=>$conf->contrat->enabled),
'intervention'=>array(
	'title'=>"ListFichinterAssociatedProject",
	'class'=>'Fichinter',
	'disableamount'=>1,
	'test'=>$conf->ficheinter->enabled),
'trip'=>array(
	'title'=>"ListTripAssociatedProject",
	'class'=>'Deplacement',
	'disableamount'=>1,
	'test'=>$conf->deplacement->enabled),
'agenda'=>array(
	'title'=>"ListActionsAssociatedProject",
	'class'=>'ActionComm',
    'disableamount'=>1,
	'test'=>$conf->agenda->enabled)
);

foreach ($listofreferent as $key => $value)
{
	$title=$value['title'];
	$classname=$value['class'];
	$qualified=$value['test'];
	if ($qualified)
	{
		print '<br>';

		print_titre($langs->trans($title));
		print '<table class="noborder" width="100%">';

		print '<tr class="liste_titre">';
		print '<td width="100">'.$langs->trans("Ref").'</td>';
		print '<td width="100" align="center">'.$langs->trans("Date").'</td>';
		print '<td>'.$langs->trans("ThirdParty").'</td>';
		if (empty($value['disableamount'])) print '<td align="right" width="120">'.$langs->trans("AmountHT").'</td>';
		if (empty($value['disableamount'])) print '<td align="right" width="120">'.$langs->trans("AmountTTC").'</td>';
		print '<td align="right" width="200">'.$langs->trans("Status").'</td>';
		print '</tr>';
		$elementarray = $project->get_element_list($key);
		if (sizeof($elementarray)>0 && is_array($elementarray))
		{
			$var=true;
			$total_ht = 0;
			$total_ttc = 0;
			for ($i = 0; $i<sizeof($elementarray);$i++)
			{
				$element = new $classname($db);
				$element->fetch($elementarray[$i]);
				$element->fetch_thirdparty();
				//print $classname;

				$var=!$var;
				print "<tr $bc[$var]>";

				// Ref
				print '<td align="left">';
				print $element->getNomUrl(1);
				print "</td>\n";

				// Date
				$date=$element->date;
				if (empty($date)) $date=$element->datep;
				if (empty($date)) $date=$element->date_contrat;
				print '<td align="center">'.dol_print_date($date,'day').'</td>';

				// Third party
                print '<td align="left">';
                if (is_object($element->client)) print $element->client->getNomUrl(1,'',48);
				print '</td>';

                // Amount
				if (empty($value['disableamount'])) print '<td align="right">'.(isset($element->total_ht)?price($element->total_ht):'&nbsp;').'</td>';

                // Amount
				if (empty($value['disableamount'])) print '<td align="right">'.(isset($element->total_ttc)?price($element->total_ttc):'&nbsp;').'</td>';
				
				// Status
				print '<td align="right">'.$element->getLibStatut(5).'</td>';

				print '</tr>';

				$total_ht = $total_ht + $element->total_ht;
				$total_ttc = $total_ttc + $element->total_ttc;
			}

			print '<tr class="liste_total"><td colspan="3">'.$langs->trans("Number").': '.$i.'</td>';
			if (empty($value['disableamount'])) print '<td align="right" width="100">'.$langs->trans("TotalHT").' : '.price($total_ht).'</td>';
			if (empty($value['disableamount'])) print '<td align="right" width="100">'.$langs->trans("TotalTTC").' : '.price($total_ttc).'</td>';
			print '<td>&nbsp;</td>';
			print '</tr>';
		}
		print "</table>";


		/*
		 * Barre d'action
		 */
		print '<div class="tabsAction">';

		if ($project->statut > 0)
		{
			if ($project->societe->prospect || $project->societe->client)
			{
				if ($key == 'propal' && $conf->propal->enabled && $user->rights->propale->creer)
				{
					print '<a class="butAction" href="'.DOL_URL_ROOT.'/comm/addpropal.php?socid='.$project->societe->id.'&amp;action=create&amp;origin='.$project->element.'&amp;originid='.$project->id.'">'.$langs->trans("AddProp").'</a>';
				}
				if ($key == 'order' && $conf->commande->enabled && $user->rights->commande->creer)
				{
					print '<a class="butAction" href="'.DOL_URL_ROOT.'/commande/fiche.php?socid='.$project->societe->id.'&amp;action=create&amp;origin='.$project->element.'&amp;originid='.$project->id.'">'.$langs->trans("AddCustomerOrder").'</a>';
				}
				if ($key == 'invoice' && $conf->facture->enabled && $user->rights->facture->creer)
				{
					print '<a class="butAction" href="'.DOL_URL_ROOT.'/compta/facture.php?socid='.$project->societe->id.'&amp;action=create&amp;origin='.$project->element.'&amp;originid='.$project->id.'">'.$langs->trans("AddCustomerInvoice").'</a>';
				}
			}
			if ($project->societe->fournisseur)
			{
				if ($key == 'order_supplier' && $conf->fournisseur->enabled && $user->rights->fournisseur->commande->creer)
				{
					print '<a class="butAction" href="'.DOL_URL_ROOT.'/fourn/facture/fiche.php?socid='.$project->societe->id.'&amp;action=create&amp;origin='.$project->element.'&amp;originid='.$project->id.'">'.$langs->trans("AddSupplierInvoice").'</a>';
				}
				if ($key == 'invoice_supplier' && $conf->fournisseur->enabled && $user->rights->fournisseur->facture->creer)
				{
					print '<a class="butAction" href="'.DOL_URL_ROOT.'/fourn/commande/fiche.php?socid='.$project->societe->id.'&amp;action=create&amp;origin='.$project->element.'&amp;originid='.$project->id.'">'.$langs->trans("AddSupplierOrder").'</a>';
				}
			}
		}

		print '</div>';
	}
}

$db->close();

llxFooter('$Date: 2011/07/31 23:23:39 $ - $Revision: 1.49 $');
?>
