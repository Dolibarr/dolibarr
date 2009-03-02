<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2008 Regis Houssin        <regis@dolibarr.fr>
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
 *      \file       htdocs/projet/element.php
 *      \ingroup    projet facture
 *		\brief      Page des elements par projet
 *		\version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/propal.class.php");
if ($conf->facture->enabled)     require_once(DOL_DOCUMENT_ROOT."/facture.class.php");
if ($conf->facture->enabled)     require_once(DOL_DOCUMENT_ROOT."/compta/facture/facture-rec.class.php");
if ($conf->commande->enabled)    require_once(DOL_DOCUMENT_ROOT."/commande/commande.class.php");
if ($conf->fournisseur->enabled) require_once(DOL_DOCUMENT_ROOT."/fourn/fournisseur.facture.class.php");
if ($conf->fournisseur->enabled) require_once(DOL_DOCUMENT_ROOT."/fourn/fournisseur.commande.class.php");
if ($conf->contrat->enabled)     require_once(DOL_DOCUMENT_ROOT."/contrat/contrat.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/project.lib.php");

$langs->load("projects");
$langs->load("companies");
$langs->load("suppliers");
if ($conf->facture->enabled)  $langs->load("bills");
if ($conf->commande->enabled) $langs->load("orders");
if ($conf->propal->enabled)   $langs->load("propal");

// Security check
$projetid='';
$ref='';
if (isset($_GET["id"]))  { $projetid=$_GET["id"]; }
if (isset($_GET["ref"])) { $ref=$_GET["ref"]; }
if ($projetid == '' && $ref == '') accessforbidden();

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'projet', $projetid);


/*
*	View
*/

llxHeader("",$langs->trans("Referers"));

$form = new Form($db);

$projet = new Project($db);
$projet->fetch($_GET["id"],$_GET["ref"]);
$projet->societe->fetch($projet->societe->id);
if ($projet->user_resp_id > 0)
{
	$result=$projet->fetch_user($projet->user_resp_id);
}

$head=project_prepare_head($projet);
dol_fiche_head($head, 'element', $langs->trans("Project"));


print '<table class="border" width="100%">';

print '<tr><td width="30%">'.$langs->trans("Ref").'</td><td>';
print $form->showrefnav($projet,'ref','',1,'ref','ref');
print '</td></tr>';

print '<tr><td>'.$langs->trans("Label").'</td><td>'.$projet->title.'</td></tr>';

print '<tr><td>'.$langs->trans("Company").'</td><td>';
if (! empty($projet->societe->id)) print $projet->societe->getNomUrl(1);
else print '&nbsp;';
print '</td></tr>';

// Project leader
print '<tr><td>'.$langs->trans("OfficerProject").'</td><td>';
if ($projet->user->id) print $projet->user->getNomUrl(1);
else print $langs->trans('SharedProject');
print '</td></tr>';

print '</table>';

print '</div>';


/*
 * Factures
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
	'test'=>$conf->facture->enabled && $conf->global->FACTURE_ENABLE_RECUR),
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
	'test'=>$conf->contrat->enabled)

);

foreach ($listofreferent as $key => $value)
{
	$title=$value['title'];
	$class=$value['class'];
	$qualified=$value['test'];
	if ($qualified)
	{
		print '<br>';

		print_titre($langs->trans($title));
		print '<table class="noborder" width="100%">';

		print '<tr class="liste_titre">';
		print '<td width="150">'.$langs->trans("Ref").'</td>';
		print '<td>'.$langs->trans("Date").'</td>';
		print '<td align="right">'.$langs->trans("Amount").'</td>';
		print '<td align="right" width="200">'.$langs->trans("Status").'</td>';
		print '</tr>';
		$elementarray = $projet->get_element_list($key);
		if (sizeof($elementarray)>0 && is_array($elementarray))
		{
			$var=true;
			$total = 0;
		    for ($i = 0; $i<sizeof($elementarray);$i++)
		    {
		        $element = new $class($db);
		        $element->fetch($elementarray[$i]);

		        $var=!$var;
		        print "<tr $bc[$var]>";
		        print "<td>";
				print $element->getNomUrl(1);
				print "</td>\n";
		        $date=$element->date;
				if (empty($date)) $date=$element->datep;
				if (empty($date)) $date=$element->date_contrat;
				print '<td>'.dol_print_date($date,'day').'</td>';
		        print '<td align="right">'.(isset($element->total_ht)?price($element->total_ht):'&nbsp;').'</td>';
		        print '<td align="right">'.$element->getLibStatut(5).'</td>';
		        print '</tr>';

		        $total = $total + $element->total_ht;
		    }

		    print '<tr class="liste_total"><td colspan="2">'.$langs->trans("Number").': '.$i.'</td>';
		    print '<td align="right" width="100">'.$langs->trans("TotalHT").' : '.price($total).'</td>';
		    print '<td>&nbsp;</td>';
		    print '</tr>';
		}
	    print "</table>";

		/*
		 * Barre d'action
		 */
		print '<div class="tabsAction">';

		if ($projet->societe->prospect || $projet->societe->client)
		{
			if ($key == 'propal' && $conf->propal->enabled && $user->rights->propale->creer)
			{
			    print '<a class="butAction" href="'.DOL_URL_ROOT.'/comm/addpropal.php?socid='.$projet->societe->id.'&amp;action=create&amp;projetid='.$projet->id.'">'.$langs->trans("AddProp").'</a>';
			}
			if ($key == 'order' && $conf->commande->enabled && $user->rights->commande->creer)
			{
			    print '<a class="butAction" href="'.DOL_URL_ROOT.'/commande/fiche.php?socid='.$projet->societe->id.'&amp;action=create&amp;projetid='.$projet->id.'">'.$langs->trans("AddCustomerOrder").'</a>';
			}
			if ($key == 'invoice' && $conf->facture->enabled && $user->rights->facture->creer)
			{
				print '<a class="butAction" href="'.DOL_URL_ROOT.'/compta/facture.php?socid='.$projet->societe->id.'&amp;action=create&amp;projetid='.$projet->id.'">'.$langs->trans("AddCustomerInvoice").'</a>';
			}
		}
		if ($projet->societe->fournisseur)
		{
			if ($key == 'order_supplier' && $conf->fournisseur->enabled && $user->rights->fournisseur->commande->creer)
			{
				print '<a class="butAction" href="'.DOL_URL_ROOT.'/fourn/facture/fiche.php?socid='.$projet->societe->id.'&amp;action=create&amp;projetid='.$projet->id.'">'.$langs->trans("AddSupplierInvoice").'</a>';
			}
			if ($key == 'invoice_supplier' && $conf->fournisseur->enabled && $user->rights->fournisseur->facture->creer)
			{
				print '<a class="butAction" href="'.DOL_URL_ROOT.'/fourn/commande/fiche.php?socid='.$projet->societe->id.'&amp;action=create&amp;projetid='.$projet->id.'">'.$langs->trans("AddSupplierOrder").'</a>';
			}
		}
		print '</div>';
	}
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
