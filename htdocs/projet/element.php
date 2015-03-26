<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2012	   Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2015      Alexandre Spangaro	<alexandre.spangaro@gmail.com>
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
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
if (! empty($conf->propal->enabled))      require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
if (! empty($conf->facture->enabled))     require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
if (! empty($conf->facture->enabled))     require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture-rec.class.php';
if (! empty($conf->commande->enabled))    require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
if (! empty($conf->fournisseur->enabled)) require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
if (! empty($conf->fournisseur->enabled)) require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
if (! empty($conf->contrat->enabled))     require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
if (! empty($conf->ficheinter->enabled))  require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';
if (! empty($conf->deplacement->enabled)) require_once DOL_DOCUMENT_ROOT.'/compta/deplacement/class/deplacement.class.php';
if (! empty($conf->expensereport->enabled)) require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
if (! empty($conf->agenda->enabled))      require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
if (! empty($conf->don->enabled))         require_once DOL_DOCUMENT_ROOT.'/donations/class/don.class.php';

$langs->load("projects");
$langs->load("companies");
$langs->load("suppliers");
if (! empty($conf->facture->enabled))  	    $langs->load("bills");
if (! empty($conf->commande->enabled)) 	    $langs->load("orders");
if (! empty($conf->propal->enabled))   	    $langs->load("propal");
if (! empty($conf->ficheinter->enabled))	$langs->load("interventions");
if (! empty($conf->deplacement->enabled))	$langs->load("trips");
if (! empty($conf->expensereport->enabled)) $langs->load("trips");
if (! empty($conf->don->enabled))			$langs->load("donations");

$id=GETPOST('id','int');
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
if ($id == '' && $projectid == '' && $ref == '')
{
	dol_print_error('','Bad parameter');
	exit;
}

$mine = $_REQUEST['mode']=='mine' ? 1 : 0;
//if (! $user->rights->projet->all->lire) $mine=1;	// Special for projects

$projectid=$id;	// For backward compatibility

$object = new Project($db);

include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php';  // Must be include, not includ_once

// Security check
$socid=0;
if ($user->societe_id > 0) $socid=$user->societe_id;
$result = restrictedArea($user, 'projet', $projectid);


/*
 *	View
 */

$title=$langs->trans("ProjectReferers").' - '.$object->ref.' '.$object->name;
if (! empty($conf->global->MAIN_HTML_TITLE) && preg_match('/projectnameonly/',$conf->global->MAIN_HTML_TITLE) && $object->name) $title=$object->ref.' '.$object->name.' - '.$langs->trans("ProjectReferers");
$help_url="EN:Module_Projects|FR:Module_Projets|ES:M&oacute;dulo_Proyectos";
llxHeader("",$langs->trans("Referers"),$help_url);

$form = new Form($db);
$formproject=new FormProjets($db);
$formfile = new FormFile($db);

$userstatic=new User($db);

// To verify role of users
$userAccess = $object->restrictedProjectArea($user);

$head=project_prepare_head($object);
dol_fiche_head($head, 'element', $langs->trans("Project"),0,($object->public?'projectpub':'project'));


print '<table class="border" width="100%">';

$linkback = '<a href="'.DOL_URL_ROOT.'/projet/list.php">'.$langs->trans("BackToList").'</a>';

print '<tr><td width="30%">'.$langs->trans("Ref").'</td><td>';
// Define a complementary filter for search of next/prev ref.
if (! $user->rights->projet->all->lire)
{
    $projectsListId = $object->getProjectsAuthorizedForUser($user,$mine,0);
    $object->next_prev_filter=" rowid in (".(count($projectsListId)?join(',',array_keys($projectsListId)):'0').")";
}
print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref');
print '</td></tr>';

print '<tr><td>'.$langs->trans("Label").'</td><td>'.$object->title.'</td></tr>';

print '<tr><td>'.$langs->trans("ThirdParty").'</td><td>';
if (! empty($object->thirdparty->id)) print $object->thirdparty->getNomUrl(1);
else print '&nbsp;';
print '</td></tr>';

// Visibility
print '<tr><td>'.$langs->trans("Visibility").'</td><td>';
if ($object->public) print $langs->trans('SharedProject');
else print $langs->trans('PrivateProject');
print '</td></tr>';

// Statut
print '<tr><td>'.$langs->trans("Status").'</td><td>'.$object->getLibStatut(4).'</td></tr>';

// Date start
print '<tr><td>'.$langs->trans("DateStart").'</td><td>';
print dol_print_date($object->date_start,'day');
print '</td></tr>';

// Date end
print '<tr><td>'.$langs->trans("DateEnd").'</td><td>';
print dol_print_date($object->date_end,'day');
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
	'title'=>"ListExpenseReportsAssociatedProject",
	'class'=>'Deplacement',
	'table'=>'deplacement',
	'datefieldname'=>'dated',
	'margin'=>'minus',
	'disableamount'=>1,
	'test'=>$conf->deplacement->enabled && $user->rights->deplacement->lire),
'expensereport'=>array(
	'name'=>"ExpenseReports",
	'title'=>"ListExpenseReportsAssociatedProject",
	'class'=>'ExpenseReportLine',
	'table'=>'expensereport_det',
	'datefieldname'=>'date',
	'margin'=>'minus',
	'disableamount'=>0,
	'test'=>$conf->expensereport->enabled && $user->rights->expensereport->lire),
'agenda'=>array(
	'name'=>"Agenda",
	'title'=>"ListActionsAssociatedProject",
	'class'=>'ActionComm',
	'table'=>'actioncomm',
	'datefieldname'=>'datep',
	'disableamount'=>1,
	'test'=>$conf->agenda->enabled && $user->rights->agenda->allactions->lire),
'donation'=>array(
	'name'=>"Donation",
	'title'=>"ListDonationsAssociatedProject",
	'class'=>'Don',
	'table'=>'don',
	'datefieldname'=>'date',
	'disableamount'=>1,
	'test'=>$conf->don->enabled && $user->rights->don->lire),
);

if ($action=="addelement")
{
	$tablename = GETPOST("tablename");
	$elementselectid = GETPOST("elementselect");
	$result=$object->update_element($tablename, $elementselectid);
	if ($result<0) {
		setEventMessage($object->error,'errors');
	}
}elseif ($action == "unlink") {

	$tablename = GETPOST("tablename");
	$elementselectid = GETPOST("elementselect");

	$result = $object->remove_element($tablename, $elementselectid);
	if ($result < 0) {
		setEventMessage($object->error, 'errors');
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
		$element = new $classname($db);

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

		$selectList=$formproject->select_element($tablename,$object->thirdparty->id);
		if (! $selectList || ($selectList<0))
		{
			setEventMessages($formproject->error,$formproject->errors,'errors');
		}
		else
		{
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
		print '<td style="width: 24px"></td>';
		print '<td style="width: 200px">'.$langs->trans("Ref").'</td>';
		print '<td width="100" align="center">'.$langs->trans("Date").'</td>';
		// Thirdparty or user
		print '<td>';
		if ($tablename == 'expensereport_det') print $langs->trans("User");
		else print $langs->trans("ThirdParty");
		print '</td>';
		if (empty($value['disableamount'])) print '<td align="right" width="120">'.$langs->trans("AmountHT").'</td>';
		else print '<td width="120"></td>';
		if (empty($value['disableamount'])) print '<td align="right" width="120">'.$langs->trans("AmountTTC").'</td>';
		else print '<td width="120"></td>';
		print '<td align="right" width="200">'.$langs->trans("Status").'</td>';
		print '</tr>';
		$elementarray = $object->get_element_list($key, $tablename, $datefieldname, $dates, $datee);
		if (is_array($elementarray) && count($elementarray)>0)
		{
			$var=true;
			$total_ht = 0;
			$total_ttc = 0;

			$total_ht_by_third = 0;
			$total_ttc_by_third = 0;

			$saved_third_id = 0;
			$breakline = '';

			if (canApplySubtotalOn($tablename)) {
			   // Appel du mon code de tri :
			   $elementarray = sortElementsByClientName($elementarray);
			}

			$num=count($elementarray);
			for ($i = 0; $i < $num; $i++)
			{
				$element->fetch($elementarray[$i]);

				if ($tablename != 'expensereport_det')
				{
					$element->fetch_thirdparty();
				}
				else
				{
					$expensereport=new ExpenseReport($db);
					$expensereport->fetch($element->fk_expensereport);
				}

				//print $classname;

				if ($breakline && $saved_third_id != $element->thirdparty->id)
				{
					print $breakline;
					$var = true;

					$saved_third_id = $element->thirdparty->id;
					$breakline = '';

					$total_ht_by_third=0;
					$total_ttc_by_third=0;
				}
				$saved_third_id = $element->thirdparty->id;

				$qualifiedfortotal=true;
				if ($key == 'invoice')
				{
					if ($element->close_code == 'replaced') $qualifiedfortotal=false;	// Replacement invoice
				}

				$var=!$var;
				print "<tr ".$bc[$var].">";
				print '<td style="width: 24px">';
				print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $projectid . '&action=unlink&tablename=' . $tablename . '&elementselect=' . $element->id . '">' . img_picto($langs->trans('Unlink'), 'editdelete') . '</a>';
				print "</td>\n";
				// Ref
				print '<td align="left">';

				if ($tablename == 'expensereport_det')
				{
					print $expensereport->getNomUrl(1);
				}
				else {
					print $element->getNomUrl(1);

					$element_doc = $element->element;
					$filename=dol_sanitizeFileName($element->ref);
					$filedir=$conf->{$element_doc}->dir_output . '/' . dol_sanitizeFileName($element->ref);

					if($element_doc === 'order_supplier') {
						$element_doc='commande_fournisseur';
						$filedir = $conf->fournisseur->commande->dir_output.'/'.dol_sanitizeFileName($element->ref);
					}
					else if($element_doc === 'invoice_supplier') {
						$element_doc='facture_fournisseur';
						$filename = get_exdir($element->id,2).dol_sanitizeFileName($element->ref);
						$filedir = $conf->fournisseur->facture->dir_output.'/'.get_exdir($element->id,2).dol_sanitizeFileName($element->ref);
					}

					print $formfile->getDocumentsLink($element_doc, $filename, $filedir);

				}

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

				// Third party or user
                print '<td align="left">';
                if (is_object($element->thirdparty)) print $element->thirdparty->getNomUrl(1,'',48);
                else if ($tablename == 'expensereport_det')
                {
                	$tmpuser=new User($db);
                	$tmpuser->fetch($expensereport->fk_user_author);
                	print $tmpuser->getNomUrl(1,'',48);
                }
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
				if ($tablename == 'expensereport_det') print $expensereport->getLibStatut(5);
				else print $element->getLibStatut(5);
				print '</td>';

				print '</tr>';

				if ($qualifiedfortotal)
				{
					$total_ht = $total_ht + $element->total_ht;
					$total_ttc = $total_ttc + $element->total_ttc;

					$total_ht_by_third += $element->total_ht;
					$total_ttc_by_third += $element->total_ttc;
				}

				if (canApplySubtotalOn($tablename))
				{
					$breakline='<tr class="liste_total">';
					$breakline.='<td colspan="2">';
					$breakline.='</td>';
					$breakline.='<td>';
					$breakline.='</td>';
					$breakline.='<td class="right">';
					$breakline.=$langs->trans('SubTotal').' : ';
					if (is_object($element->thirdparty)) $breakline.=$element->thirdparty->getNomUrl(0,'',48);
					$breakline.='</td>';
					$breakline.='<td align="right">'.price($total_ht_by_third).'</td>';
					$breakline.='<td align="right">'.price($total_ttc_by_third).'</td>';
					$breakline.='<td></td>';
					$breakline.='</tr>';
				}

				//var_dump($element->thirdparty->name.' - '.$saved_third_id.' - '.$element->thirdparty->id);
			}

			if ($breakline) print $breakline;

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

		if ($object->statut > 0)
		{
			if ($object->thirdparty->prospect || $object->thirdparty->client)
			{
				if ($key == 'propal' && ! empty($conf->propal->enabled) && $user->rights->propale->creer)
				{
					print '<a class="butAction" href="'.DOL_URL_ROOT.'/comm/propal.php?socid='.$object->thirdparty->id.'&amp;action=create&amp;origin='.$object->element.'&amp;originid='.$object->id.'">'.$langs->trans("AddProp").'</a>';
				}
				if ($key == 'order' && ! empty($conf->commande->enabled) && $user->rights->commande->creer)
				{
					print '<a class="butAction" href="'.DOL_URL_ROOT.'/commande/card.php?socid='.$object->thirdparty->id.'&amp;action=create&amp;origin='.$object->element.'&amp;originid='.$object->id.'">'.$langs->trans("AddCustomerOrder").'</a>';
				}
				if ($key == 'invoice' && ! empty($conf->facture->enabled) && $user->rights->facture->creer)
				{
					print '<a class="butAction" href="'.DOL_URL_ROOT.'/compta/facture.php?socid='.$object->thirdparty->id.'&amp;action=create&amp;origin='.$object->element.'&amp;originid='.$object->id.'">'.$langs->trans("AddCustomerInvoice").'</a>';
				}
			}
			if ($object->thirdparty->fournisseur)
			{
				if ($key == 'order_supplier' && ! empty($conf->fournisseur->enabled) && $user->rights->fournisseur->commande->creer)
				{
					print '<a class="butAction" href="'.DOL_URL_ROOT.'/fourn/commande/card.php?socid='.$project->thirdparty->id.'&amp;action=create&amp;origin='.$project->element.'&amp;originid='.$project->id.'">'.$langs->trans("AddSupplierOrder").'</a>';
				}
				if ($key == 'invoice_supplier' && ! empty($conf->fournisseur->enabled) && $user->rights->fournisseur->facture->creer)
				{
					print '<a class="butAction" href="'.DOL_URL_ROOT.'/fourn/facture/card.php?socid='.$project->thirdparty->id.'&amp;action=create&amp;origin='.$project->element.'&amp;originid='.$project->id.'">'.$langs->trans("AddSupplierInvoice").'</a>';
				}
			}
		}

		print '</div>';
	}
}


// Show profit summary for whole project

$langs->load("suppliers");
$langs->load("bills");
$langs->load("orders");
$langs->load("proposals");
$langs->load("margins");
print_fiche_titre($langs->trans("Profit"),'');
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
	if ($qualified && isset($margin))		// If this element must be included into profit calculation ($margin is 'minus' or 'plus')
	{
		$element = new $classname($db);

		$elementarray = $object->get_element_list($key, $tablename);
		if (count($elementarray)>0 && is_array($elementarray))
		{
			$var=true;
			$total_ht = 0;
			$total_ttc = 0;
			$num=count($elementarray);
			for ($i = 0; $i < $num; $i++)
			{
				$element->fetch($elementarray[$i]);
				if ($tablename != 'expensereport_det') $element->fetch_thirdparty();

				$total_ht = $total_ht + $element->total_ht;
				$total_ttc = $total_ttc + $element->total_ttc;
			}

			print '<tr >';
			print '<td align="left" >'.$name.'</td>';
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



/**
 * Return if we should do a group by customer with sub-total
 *
 * @param 	string	$tablename		Name of table
 * @return	boolean					True to tell to make a group by sub-total
 */
function canApplySubtotalOn($tablename)
{
	global $conf;

	if (empty($conf->global->PROJECT_ADD_SUBTOTAL_LINES)) return false;
	return in_array($tablename, array('facture_fourn', 'commande_fournisseur'));
}

/**
 * sortElementsByClientName
 *
 * @param 	array		$elementarray	Element array
 * @return	array						Element array sorted
 */
function sortElementsByClientName($elementarray)
{
	global $db, $classname;

	$element = new $classname($db);

	$clientname = array();
	foreach ($elementarray as $key => $id)	// id = id of object
	{
		if (empty($clientname[$id]))
		{
			$element->fetch($id);
			$element->fetch_thirdparty();

			$clientname[$id] = $element->thirdparty->name;
		}
	}

	//var_dump($clientname);
	asort($clientname);	// sort on name

	$elementarray = array();
	foreach ($clientname as $id => $name)
	{
		$elementarray[] = $id;
	}

	return $elementarray;
}
