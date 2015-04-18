<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2012	   Juanjo Menent        <jmenent@2byte.es>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/projet/element.php
 *      \ingroup    projet facture
 *		\brief      Page of project referrers
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
if (! empty($conf->propal->enabled))      require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
if (! empty($conf->facture->enabled))     require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
if (! empty($conf->facture->enabled))     require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture-rec.class.php';
if (! empty($conf->commande->enabled))    require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
if (! empty($conf->fournisseur->enabled)) require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
if (! empty($conf->fournisseur->enabled)) require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
if (! empty($conf->contrat->enabled))     require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
if (! empty($conf->ficheinter->enabled))  require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';
if (! empty($conf->deplacement->enabled)) require_once DOL_DOCUMENT_ROOT.'/compta/deplacement/class/deplacement.class.php';
if (! empty($conf->agenda->enabled))      require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';

$langs->load("projects");
$langs->load("companies");
$langs->load("suppliers");
if (! empty($conf->facture->enabled))  	$langs->load("bills");
if (! empty($conf->commande->enabled)) 	$langs->load("orders");
if (! empty($conf->propal->enabled))   	$langs->load("propal");
if (! empty($conf->ficheinter->enabled))	$langs->load("interventions");

$projectid=GETPOST('id','int');
$ref=GETPOST('ref','alpha');
$action=GETPOST('action','alpha');
$datesrfc=GETPOST('datesrfc');
$dateerfc=GETPOST('dateerfc');
$dates=dol_mktime(0, 0, 0, GETPOST('datesmonth'), GETPOST('datesday'), GETPOST('datesyear'));
$datee=dol_mktime(23, 59, 59, GETPOST('dateemonth'), GETPOST('dateeday'), GETPOST('dateeyear'));
if (empty($dates) && ! empty($datesrfc)) $dates=dol_stringtotime($datesrfc);
if (empty($datee) && ! empty($dateerfc)) $datee=dol_stringtotime($dateerfc);
if (! isset($_POST['datesrfc']) && ! isset($_POST['datesday']) && ! empty($conf->global->PROJECT_LINKED_ELEMENT_DEFAULT_FILTER_YEAR))
{
	$new=dol_now();
	$tmp=dol_getdate($new);
	//$datee=$now
	//$dates=dol_time_plus_duree($datee, -1, 'y');
	$dates=dol_get_first_day($tmp['year'],1);
}
if ($projectid == '' && $ref == '')
{
	dol_print_error('','Bad parameter');
	exit;
}

$mine = $_REQUEST['mode']=='mine' ? 1 : 0;
//if (! $user->rights->projet->all->lire) $mine=1;	// Special for projects

$project = new Project($db);
if ($id > 0 || ! empty($ref))
{
    $project->fetch($id,$ref);
    $project->fetch_thirdparty();
    $projectid=$project->id;
}
else
{
	$project->fetch($projectid);
    $project->fetch_thirdparty();
    $projectid=$project->id;
}

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
$formproject=new FormProjets($db);

$userstatic=new User($db);

// To verify role of users
$userAccess = $project->restrictedProjectArea($user);

$head=project_prepare_head($project);
dol_fiche_head($head, 'element', $langs->trans("Project"),0,($project->public?'projectpub':'project'));


print '<table class="border" width="100%">';

$linkback = '<a href="'.DOL_URL_ROOT.'/projet/list.php">'.$langs->trans("BackToList").'</a>';

print '<tr><td width="30%">'.$langs->trans("Ref").'</td><td>';
// Define a complementary filter for search of next/prev ref.
if (! $user->rights->projet->all->lire)
{
    $projectsListId = $project->getProjectsAuthorizedForUser($user,$mine,0);
    $project->next_prev_filter=" rowid in (".(count($projectsListId)?join(',',array_keys($projectsListId)):'0').")";
}
print $form->showrefnav($project, 'ref', $linkback, 1, 'ref', 'ref');
print '</td></tr>';

print '<tr><td>'.$langs->trans("Label").'</td><td>'.$project->title.'</td></tr>';

print '<tr><td>'.$langs->trans("ThirdParty").'</td><td>';
if (! empty($project->thirdparty->id)) print $project->thirdparty->getNomUrl(1);
else print '&nbsp;';
print '</td></tr>';

// Visibility
print '<tr><td>'.$langs->trans("Visibility").'</td><td>';
if ($project->public) print $langs->trans('SharedProject');
else print $langs->trans('PrivateProject');
print '</td></tr>';

// Statut
print '<tr><td>'.$langs->trans("Status").'</td><td>'.$project->getLibStatut(4).'</td></tr>';

// Date start
print '<tr><td>'.$langs->trans("DateStart").'</td><td>';
print dol_print_date($project->date_start,'day');
print '</td></tr>';

// Date end
print '<tr><td>'.$langs->trans("DateEnd").'</td><td>';
print dol_print_date($project->date_end,'day');
print '</td></tr>';

print '</table>';

dol_fiche_end();


/*
 * Referers types
 */

$listofreferent=array(
'propal'=>array(
	'name'=>"Proposals",
	'title'=>"ListProposalsAssociatedProject",
	'class'=>'Propal',
	'table'=>'propal',
    'datefieldname'=>'datep',
	'test'=>$conf->propal->enabled && $user->rights->propale->lire),
'order'=>array(
	'name'=>"CustomersOrders",
	'title'=>"ListOrdersAssociatedProject",
	'class'=>'Commande',
	'table'=>'commande',
	'datefieldname'=>'date_commande',
	'test'=>$conf->commande->enabled && $user->rights->commande->lire),
'invoice'=>array(
	'name'=>"CustomersInvoices",
	'title'=>"ListInvoicesAssociatedProject",
	'class'=>'Facture',
	'margin'=>'add',
	'table'=>'facture',
	'datefieldname'=>'datef',
	'test'=>$conf->facture->enabled && $user->rights->facture->lire),
'invoice_predefined'=>array(
	'name'=>"PredefinedInvoices",
	'title'=>"ListPredefinedInvoicesAssociatedProject",
	'class'=>'FactureRec',
	'table'=>'facture_rec',
	'datefieldname'=>'datec',
	'test'=>$conf->facture->enabled && $user->rights->facture->lire),
'order_supplier'=>array(
	'name'=>"SuppliersOrders",
	'title'=>"ListSupplierOrdersAssociatedProject",
	'class'=>'CommandeFournisseur',
	'table'=>'commande_fournisseur',
	'datefieldname'=>'date_commande',
	'test'=>$conf->fournisseur->enabled && $user->rights->fournisseur->commande->lire),
'invoice_supplier'=>array(
	'name'=>"BillsSuppliers",
	'title'=>"ListSupplierInvoicesAssociatedProject",
	'class'=>'FactureFournisseur',
	'margin'=>'minus',
	'table'=>'facture_fourn',
	'datefieldname'=>'datef',
	'test'=>$conf->fournisseur->enabled && $user->rights->fournisseur->facture->lire),
'contract'=>array(
	'name'=>"Contracts",
	'title'=>"ListContractAssociatedProject",
	'class'=>'Contrat',
	'table'=>'contrat',
	'datefieldname'=>'date_contrat',
	'test'=>$conf->contrat->enabled && $user->rights->contrat->lire),
'intervention'=>array(
	'name'=>"Interventions",
	'title'=>"ListFichinterAssociatedProject",
	'class'=>'Fichinter',
	'table'=>'fichinter',
	'datefieldname'=>'date_valid',
	'disableamount'=>1,
	'test'=>$conf->ficheinter->enabled && $user->rights->ficheinter->lire),
'trip'=>array(
	'name'=>"TripsAndExpenses",
	'title'=>"ListTripAssociatedProject",
	'class'=>'Deplacement',
	'table'=>'deplacement',
	'datefieldname'=>'dated',
	'margin'=>'minus',
	'disableamount'=>1,
	'test'=>$conf->deplacement->enabled && $user->rights->deplacement->lire),
'agenda'=>array(
	'name'=>"Agenda",
	'title'=>"ListActionsAssociatedProject",
	'class'=>'ActionComm',
	'table'=>'actioncomm',
	'datefieldname'=>'datep',
	'disableamount'=>1,
	'test'=>$conf->agenda->enabled && $user->rights->agenda->allactions->lire)
);

if ($action=="addelement")
{
	$tablename = GETPOST("tablename");
	$elementselectid = GETPOST("elementselect");
	$result=$project->update_element($tablename, $elementselectid);
	if ($result<0) {
		setEventMessage($project->error,'errors');
	}
}elseif ($action == "unlink") {

	$tablename = GETPOST("tablename");
	$elementselectid = GETPOST("elementselect");

	$result = $project->remove_element($tablename, $elementselectid);
	if ($result < 0) {
		setEventMessage($project->error, 'errors');
	}
}

$showdatefilter=0;
foreach ($listofreferent as $key => $value)
{
	$title=$value['title'];
	$classname=$value['class'];
	$tablename=$value['table'];
	$datefieldname=$value['datefieldname'];
	$qualified=$value['test'];

	if ($qualified)
	{
		if (! $showdatefilter)
		{
			print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$projectid.'" method="post">';
			print '<input type="hidden" name="tablename" value="'.$tablename.'">';
			print '<input type="hidden" name="action" value="view">';
			print '<table><tr>';
			//print '<td>'.$langs->trans("Filter").':</td>';
			print '<td>'.$langs->trans("From").' ';
			print $form->select_date($dates,'dates',0,0,1);
			print '</td>';
			print '<td>'.$langs->trans("to").' ';
			print $form->select_date($datee,'datee',0,0,1);
			print '</td>';
			print '<td>';
			print '<input type="submit" name="refresh" value="'.$langs->trans("Refresh").'" class="button">';
			print '</td>';
			print '</tr></table>';
			print '</form><br>';

			$showdatefilter++;
		}

		print '<br>';

		print_titre($langs->trans($title));

		$selectList=$formproject->select_element($tablename,$project->thirdparty->id);

		if (!$selectList || ($selectList<0)) {
			setEventMessage($formproject->error,'errors');
		} else {
			print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$projectid.'" method="post">';
			print '<input type="hidden" name="tablename" value="'.$tablename.'">';
			print '<input type="hidden" name="action" value="addelement">';
			print '<input type="hidden" name="datesrfc" value="'.dol_print_date($dates,'dayhourrfc').'">';
			print '<input type="hidden" name="dateerfc" value="'.dol_print_date($datee,'dayhourrfc').'">';
			print '<table><tr><td>'.$langs->trans("SelectElement").'</td>';
			print '<td>'.$selectList.'</td>';
			print '<td><input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans("AddElement")).'"></td>';
			print '</tr></table>';
			print '</form>';
		}
		print '<table class="noborder" width="100%">';

		print '<tr class="liste_titre">';
		print '<td width="100" colspan="2">'.$langs->trans("Ref").'</td>';
		print '<td width="100" align="center">'.$langs->trans("Date").'</td>';
		print '<td>'.$langs->trans("ThirdParty").'</td>';
		if (empty($value['disableamount'])) print '<td align="right" width="120">'.$langs->trans("AmountHT").'</td>';
		else print '<td width="120"></td>';
		if (empty($value['disableamount'])) print '<td align="right" width="120">'.$langs->trans("AmountTTC").'</td>';
		else print '<td width="120"></td>';
		print '<td align="right" width="200">'.$langs->trans("Status").'</td>';
		print '</tr>';
		$elementarray = $project->get_element_list($key, $tablename, $datefieldname, $dates, $datee);
		if (is_array($elementarray) && count($elementarray)>0)
		{
			$var=true;
			$total_ht = 0;
			$total_ttc = 0;
			$num=count($elementarray);
			for ($i = 0; $i < $num; $i++)
			{
				$element = new $classname($db);
				$element->fetch($elementarray[$i]);
				$element->fetch_thirdparty();
				//print $classname;

				$qualifiedfortotal=true;
				if ($key == 'invoice')
				{
					if ($element->close_code == 'replaced') $qualifiedfortotal=false;	// Replacement invoice
				}

				$var=!$var;
				print "<tr ".$bc[$var].">";
				print '<td width="1%">';
				print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $projectid . '&action=unlink&tablename=' . $tablename . '&elementselect=' . $element->id . '">' . img_picto($langs->trans('Unlink'), 'editdelete') . '</a>';
				print "</td>\n";
				// Ref
				print '<td align="left">';
				print $element->getNomUrl(1);
				print "</td>\n";

				// Date
				if ($tablename == 'commande_fournisseur' || $tablename == 'supplier_order') $date=$element->date_commande;
				else
				{
					$date=$element->date;
					if (empty($date)) $date=$element->datep;
					if (empty($date)) $date=$element->date_contrat;
					if (empty($date)) $date=$element->datev; //Fiche inter
				}
				print '<td align="center">'.dol_print_date($date,'day').'</td>';

				// Third party
                print '<td align="left">';
                if (is_object($element->thirdparty)) print $element->thirdparty->getNomUrl(1,'',48);
				print '</td>';

                // Amount without tax
				if (empty($value['disableamount']))
				{
					print '<td align="right">';
					if (! $qualifiedfortotal) print '<strike>';
					print (isset($element->total_ht)?price($element->total_ht):'&nbsp;');
					if (! $qualifiedfortotal) print '</strike>';
					print '</td>';
				}
				else print '<td></td>';

                // Amount inc tax
				if (empty($value['disableamount']))
				{
					print '<td align="right">';
					if (! $qualifiedfortotal) print '<strike>';
					print (isset($element->total_ttc)?price($element->total_ttc):'&nbsp;');
					if (! $qualifiedfortotal) print '</strike>';
					print '</td>';
				}
				else print '<td></td>';

				// Status
				print '<td align="right">';
				if ($element instanceof CommonInvoice) {
					//This applies for Facture and FactureFournisseur
					print $element->getLibStatut(5, $element->getSommePaiement());
				} else {
					print $element->getLibStatut(5);
				}
				print '</td>';

				print '</tr>';

				if ($qualifiedfortotal)
				{
					$total_ht = $total_ht + $element->total_ht;
					$total_ttc = $total_ttc + $element->total_ttc;
				}
			}

			print '<tr class="liste_total"><td colspan="4">'.$langs->trans("Number").': '.$i.'</td>';
			if (empty($value['disableamount'])) print '<td align="right" width="100">'.$langs->trans("TotalHT").' : '.price($total_ht).'</td>';
			else print '<td></td>';
			if (empty($value['disableamount'])) print '<td align="right" width="100">'.$langs->trans("TotalTTC").' : '.price($total_ttc).'</td>';
			else print '<td></td>';
			print '<td>&nbsp;</td>';
			print '</tr>';
		}
		else // error
		{
			print $elementarray;
		}
		print "</table>";


		/*
		 * Barre d'action
		 */
		print '<div class="tabsAction">';

		if ($project->statut > 0)
		{
			if ($project->thirdparty->prospect || $project->thirdparty->client)
			{
				if ($key == 'propal' && ! empty($conf->propal->enabled) && $user->rights->propale->creer)
				{
					print '<a class="butAction" href="'.DOL_URL_ROOT.'/comm/propal.php?socid='.$project->thirdparty->id.'&amp;action=create&amp;origin='.$project->element.'&amp;originid='.$project->id.'">'.$langs->trans("AddProp").'</a>';
				}
				if ($key == 'order' && ! empty($conf->commande->enabled) && $user->rights->commande->creer)
				{
					print '<a class="butAction" href="'.DOL_URL_ROOT.'/commande/card.php?socid='.$project->thirdparty->id.'&amp;action=create&amp;origin='.$project->element.'&amp;originid='.$project->id.'">'.$langs->trans("AddCustomerOrder").'</a>';
				}
				if ($key == 'invoice' && ! empty($conf->facture->enabled) && $user->rights->facture->creer)
				{
					print '<a class="butAction" href="'.DOL_URL_ROOT.'/compta/facture.php?socid='.$project->thirdparty->id.'&amp;action=create&amp;origin='.$project->element.'&amp;originid='.$project->id.'">'.$langs->trans("AddCustomerInvoice").'</a>';
				}
			}
			if ($project->thirdparty->fournisseur)
			{
				if ($key == 'order_supplier' && ! empty($conf->fournisseur->enabled) && $user->rights->fournisseur->commande->creer)
				{
					print '<a class="butAction" href="'.DOL_URL_ROOT.'/fourn/commande/card.php?socid='.$project->societe->id.'&amp;action=create&amp;origin='.$project->element.'&amp;originid='.$project->id.'">'.$langs->trans("AddSupplierOrder").'</a>';
				}
				if ($key == 'invoice_supplier' && ! empty($conf->fournisseur->enabled) && $user->rights->fournisseur->facture->creer)
				{
					print '<a class="butAction" href="'.DOL_URL_ROOT.'/fourn/facture/card.php?socid='.$project->societe->id.'&amp;action=create&amp;origin='.$project->element.'&amp;originid='.$project->id.'">'.$langs->trans("AddSupplierInvoice").'</a>';
				}
			}
		}

		print '</div>';
	}
}

// Profit for all project
$langs->load("suppliers");
$langs->load("bills");
$langs->load("orders");
$langs->load("proposals");
$langs->load("margins");
print_fiche_titre($langs->trans("Profit"),'','');
print '<table class="noborder">';
print '<tr class="liste_titre">';
print '<td align="left" width="200">'.$langs->trans("Element").'</td>';
print '<td align="right" width="100">'.$langs->trans("Number").'</td>';
print '<td align="right" width="100">'.$langs->trans("AmountHT").'</td>';
print '<td align="right" width="100">'.$langs->trans("AmountTTC").'</td>';
print '</tr>';

foreach ($listofreferent as $key => $value)
{
	$name=$langs->trans($value['name']);
	$title=$value['title'];
	$classname=$value['class'];
	$tablename=$value['table'];
	$qualified=$value['test'];
	$margin = $value['margin'];
	if (isset($margin))
	{
		$elementarray = $project->get_element_list($key, $tablename);
		if (count($elementarray)>0 && is_array($elementarray))
		{
			$var=true;
			$total_ht = 0;
			$total_ttc = 0;
			$num=count($elementarray);
			for ($i = 0; $i < $num; $i++)
			{
				$element = new $classname($db);
				$element->fetch($elementarray[$i]);
				$element->fetch_thirdparty();
				//print $classname;
				if ($qualified)
				{
					$total_ht = $total_ht + $element->total_ht;
					$total_ttc = $total_ttc + $element->total_ttc;
				}
			}

			switch ($classname) {
				case 'FactureFournisseur':
					$newclassname = 'SupplierInvoice';
					break;
				case 'Facture':
					$newclassname = 'Bill';
					break;
				case 'Propal':
					$newclassname = 'CommercialProposal';
					break;
				case 'Commande':
					$newclassname = 'Order';
					break;
				case 'Expedition':
					$newclassname = 'Sending';
					break;
				case 'Contrat':
					$newclassname = 'Contract';
					break;
				default:
					$newclassname = $classname;
			}

			print '<tr >';
			print '<td align="left">'.$langs->trans($newclassname).'</td>';
			print '<td align="right">'.$i.'</td>';
			print '<td align="right">'.price($total_ht).'</td>';
			print '<td align="right">'.price($total_ttc).'</td>';
			print '</tr>';
			if ($margin=="add")
			{
				$margin_ht+= $total_ht;
				$margin_ttc+= $total_ttc;
			}
			else
			{
				$margin_ht-= $total_ht;
				$margin_ttc-= $total_ttc;
			}
		}

	}
}
// and the margin amount total
print '<tr class="liste_total">';
print '<td align="right" colspan=2 >'.$langs->trans("Total").'</td>';
print '<td align="right" >'.price($margin_ht).'</td>';
print '<td align="right" >'.price($margin_ttc).'</td>';
print '</tr>';

print "</table>";


llxFooter();

$db->close();
