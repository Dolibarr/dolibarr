<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2012	   Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2015      Alexandre Spangaro	<aspangaro.dolibarr@gmail.com>
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
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
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
if (! empty($conf->don->enabled))         require_once DOL_DOCUMENT_ROOT.'/don/class/don.class.php';

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

include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php';  // Must be include, not include_once

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

// Opportunity status
print '<tr><td>'.$langs->trans("OpportunityStatus").'</td><td>';
$code = dol_getIdFromCode($db, $object->opp_status, 'c_lead_status', 'rowid', 'code');
if ($code) print $langs->trans("OppStatus".$code);
print '</td></tr>';

// Opportunity Amount
print '<tr><td>'.$langs->trans("OpportunityAmount").'</td><td>';
if (strcmp($object->opp_amount,'')) print price($object->opp_amount,'',$langs,0,0,0,$conf->currency);
print '</td></tr>';

// Budget
print '<tr><td>'.$langs->trans("Budget").'</td><td>';
if (strcmp($object->budget_amount, '')) print price($object->budget_amount,'',$langs,0,0,0,$conf->currency);
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
	'margin'=>'add',
	'table'=>'don',
	'datefieldname'=>'datedon',
	'disableamount'=>0,
	'test'=>$conf->don->enabled && $user->rights->don->lire),
'project_task'=>array(
	'name'=>"TaskTimeValorised",
	'title'=>"ListTaskTimeUserProject",
	'class'=>'Task',
	'margin'=>'minus',
	'table'=>'projet_task',
	'datefieldname'=>'task_date',
	'disableamount'=>0,
	'test'=>$conf->projet->enabled && $user->rights->projet->lire && $conf->salaries->enabled),
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

$elementuser = new User($db);



$showdatefilter=0;
// Show the filter on date on top of element list
if (! $showdatefilter)
{
	print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$projectid.'" method="post">';
	print '<input type="hidden" name="tablename" value="'.$tablename.'">';
	print '<input type="hidden" name="action" value="view">';
	print '<table><tr>';
	print '<td>'.$langs->trans("From").' ';
	print $form->select_date($dates,'dates',0,0,1,'',1,0,1);
	print '</td>';
	print '<td>'.$langs->trans("to").' ';
	print $form->select_date($datee,'datee',0,0,1,'',1,0,1);
	print '</td>';
	print '<td>';
	print '<input type="submit" name="refresh" value="'.$langs->trans("Refresh").'" class="button">';
	print '</td>';
	print '</tr></table>';
	print '</form>';

	$showdatefilter++;
}



// Show balance for whole project

$langs->load("suppliers");
$langs->load("bills");
$langs->load("orders");
$langs->load("proposals");
$langs->load("margins");

//print load_fiche_titre($langs->trans("Profit"),'','title_accountancy');
print '<div class="center">'.img_picto("", "title_accountancy").' '.$langs->trans("Profit").'</div><br>';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td align="left" width="200">'.$langs->trans("Element").'</td>';
print '<td align="right" width="100">'.$langs->trans("Number").'</td>';
print '<td align="right" width="100">'.$langs->trans("AmountHT").'</td>';
print '<td align="right" width="100">'.$langs->trans("AmountTTC").'</td>';
print '</tr>';

$var = false;

foreach ($listofreferent as $key => $value)
{
	$name=$langs->trans($value['name']);
	$title=$value['title'];
	$classname=$value['class'];
	$tablename=$value['table'];
	$datefieldname=$value['datefieldname'];
	$qualified=$value['test'];
	$margin = $value['margin'];
	if ($qualified && isset($margin))		// If this element must be included into profit calculation ($margin is 'minus' or 'plus')
	{
		$element = new $classname($db);

		$elementarray = $object->get_element_list($key, $tablename, $datefieldname, $dates, $datee);
		if (count($elementarray)>0 && is_array($elementarray))
		{
			$total_ht = 0;
			$total_ttc = 0;

			$num=count($elementarray);
			for ($i = 0; $i < $num; $i++)
			{
				$tmp=explode('_',$elementarray[$i]);
				$idofelement=$tmp[0];
				$idofelementuser=$tmp[1];

				$element->fetch($idofelement);
				if ($idofelementuser) $elementuser->fetch($idofelementuser);

				if ($tablename != 'expensereport_det') $element->fetch_thirdparty();

				if ($tablename == 'don') $total_ht_by_line=$element->amount;
				elseif ($tablename == 'projet_task')
				{
					if ($idofelementuser)
					{
						$tmp = $element->getSumOfAmount($elementuser, $dates, $datee);
						$total_ht_by_line = price2num($tmp['amount'],'MT');
					}
					else
					{
						$tmp = $element->getSumOfAmount('', $dates, $datee);
						$total_ht_by_line = price2num($tmp['amount'],'MT');
					}
				}
				else $total_ht_by_line=$element->total_ht;

				$total_ht = $total_ht + $total_ht_by_line;

				if ($tablename == 'don') $total_ttc_by_line=$element->amount;
				elseif ($tablename == 'projet_task')
				{
					$defaultvat = get_default_tva($mysoc, $mysoc);
					$total_ttc_by_line = price2num($total_ht_by_line * (1 + ($defaultvat / 100)),'MT');
				}
				else $total_ttc_by_line=$element->total_ttc;

				$total_ttc = $total_ttc + $total_ttc_by_line;
			}

			// Calculate margin
			if ($margin=="add")
			{
				$balance_ht+= $total_ht;
				$balance_ttc+= $total_ttc;
			}
			else
			{
				$balance_ht-= $total_ht;
				$balance_ttc-= $total_ttc;
			}

			// Show $total_ht & $total_ttc -- add a minus when necessary
			if ($margin!="add")
			{
				$total_ht = -$total_ht;
				$total_ttc = -$total_ttc;
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

			$var = ! $var;
			print '<tr '.$bc[$var].'>';
			// Module
			print '<td align="left">'.$langs->trans($newclassname).'</td>';
			// Nb
			print '<td align="right">'.$i.'</td>';
			// Amount HT
			print '<td align="right">'.price($total_ht).'</td>';
			// Amount TTC
			print '<td align="right">'.price($total_ttc).'</td>';
			print '</tr>';
		}
	}
}
// and the final balance
print '<tr class="liste_total">';
print '<td align="right" colspan=2 >'.$langs->trans("Profit").'</td>';
print '<td align="right" >'.price($balance_ht).'</td>';
print '<td align="right" >'.price($balance_ttc).'</td>';
print '</tr>';

print "</table>";



print '<br><br>';
print '<br>';



// Detail
foreach ($listofreferent as $key => $value)
{
	$title=$value['title'];
	$classname=$value['class'];
	$tablename=$value['table'];
	$datefieldname=$value['datefieldname'];
	$qualified=$value['test'];

	if ($qualified)
	{
		// If we want the project task array to have details of users
		//if ($key == 'project_task') $key = 'project_task_time';

		$element = new $classname($db);

		$addform='';

		$idtofilterthirdparty=0;
		if (! in_array($tablename, array('facture_fourn', 'commande_fourn'))) $idtofilterthirdparty=$object->thirdparty->id;

		$selectList=$formproject->select_element($tablename, $idtofilterthirdparty, 'minwidth200');
		if (! $selectList || ($selectList<0))
		{
			setEventMessages($formproject->error,$formproject->errors,'errors');
		}
		elseif($selectList)
		{
			// Define form with the combo list of elements to link
			$addform.='<form action="'.$_SERVER["PHP_SELF"].'?id='.$projectid.'" method="post">';
			$addform.='<input type="hidden" name="tablename" value="'.$tablename.'">';
			$addform.='<input type="hidden" name="action" value="addelement">';
			$addform.='<input type="hidden" name="datesrfc" value="'.dol_print_date($dates,'dayhourrfc').'">';
			$addform.='<input type="hidden" name="dateerfc" value="'.dol_print_date($datee,'dayhourrfc').'">';
			$addform.='<table><tr><td>'.$langs->trans("SelectElement").'</td>';
			$addform.='<td>'.$selectList.'</td>';
			$addform.='<td><input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans("AddElement")).'"></td>';
			$addform.='</tr></table>';
			$addform.='</form>';
		}

		print load_fiche_titre($langs->trans($title), $addform, '');

		print '<table class="noborder" width="100%">';

		print '<tr class="liste_titre">';
		// Remove link
		print '<td style="width: 24px"></td>';
		// Ref
		print '<td style="width: 200px">'.$langs->trans("Ref").'</td>';
		// Date
		print '<td width="100" align="center">';
		if (! in_array($tablename, array('projet_task'))) print $langs->trans("Date");
		print '</td>';
		// Thirdparty or user
		print '<td>';
		if (in_array($tablename, array('projet_task')) && $key == 'project_task') print '';		// if $key == 'project_task', we don't want details per user
		elseif (in_array($tablename, array('expensereport_det','don','projet_task'))) print $langs->trans("User");
		else print $langs->trans("ThirdParty");
		print '</td>';
		// Amount HT
		//if (empty($value['disableamount']) && ! in_array($tablename, array('projet_task'))) print '<td align="right" width="120">'.$langs->trans("AmountHT").'</td>';
		//elseif (empty($value['disableamount']) && in_array($tablename, array('projet_task'))) print '<td align="right" width="120">'.$langs->trans("Amount").'</td>';
		if (empty($value['disableamount'])) print '<td align="right" width="120">'.$langs->trans("AmountHT").'</td>';
		else print '<td width="120"></td>';
		// Amount TTC
		//if (empty($value['disableamount']) && ! in_array($tablename, array('projet_task'))) print '<td align="right" width="120">'.$langs->trans("AmountTTC").'</td>';
		if (empty($value['disableamount'])) print '<td align="right" width="120">'.$langs->trans("AmountTTC").'</td>';
		else print '<td width="120"></td>';
		// Status
		if (in_array($tablename, array('projet_task'))) print '<td align="right" width="200">'.$langs->trans("ProgressDeclared").'</td>';
		else print '<td align="right" width="200">'.$langs->trans("Status").'</td>';
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

			if (canApplySubtotalOn($tablename))
			{
			   // Sort
			   $elementarray = sortElementsByClientName($elementarray);
			}

			$num=count($elementarray);
			for ($i = 0; $i < $num; $i++)
			{
				$tmp=explode('_',$elementarray[$i]);
				$idofelement=$tmp[0];
				$idofelementuser=$tmp[1];

				$element->fetch($idofelement);
				if ($idofelementuser) $elementuser->fetch($idofelementuser);

				if ($tablename != 'expensereport_det')
				{
					$element->fetch_thirdparty();
				}
				else
				{
					$expensereport=new ExpenseReport($db);
					$expensereport->fetch($element->fk_expensereport);
				}

				//print 'xxx'.$tablename;
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
					if (! empty($element->close_code) && $element->close_code == 'replaced') $qualifiedfortotal=false;	// Replacement invoice, do not include into total
				}

				$var=!$var;
				print "<tr ".$bc[$var].">";
				// Remove link
				print '<td style="width: 24px">';
				if ($tablename != 'projet_task')
				{
					print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $projectid . '&action=unlink&tablename=' . $tablename . '&elementselect=' . $element->id . '">' . img_picto($langs->trans('Unlink'), 'editdelete') . '</a>';
				}
				print "</td>\n";
				// Ref
				print '<td align="left">';

				if ($tablename == 'expensereport_det')
				{
					print $expensereport->getNomUrl(1);
				}
				else
				{
					if ($element instanceof Task)
					{
						print $element->getNomUrl(1,'withproject','time');
						print ' - '.dol_trunc($element->label, 48);
					}
					else print $element->getNomUrl(1);

					$element_doc = $element->element;
					$filename=dol_sanitizeFileName($element->ref);
					$filedir=$conf->{$element_doc}->dir_output . '/' . dol_sanitizeFileName($element->ref);

					if($element_doc === 'order_supplier') {
						$element_doc='commande_fournisseur';
						$filedir = $conf->fournisseur->commande->dir_output.'/'.dol_sanitizeFileName($element->ref);
					}
					else if($element_doc === 'invoice_supplier') {
						$element_doc='facture_fournisseur';
						$filename = get_exdir($element->id,2,0,0,$this,'product').dol_sanitizeFileName($element->ref);
						$filedir = $conf->fournisseur->facture->dir_output.'/'.get_exdir($element->id,2,0,0,null,'invoice_supplier').dol_sanitizeFileName($element->ref);
					}

					print $formfile->getDocumentsLink($element_doc, $filename, $filedir);
				}

				print "</td>\n";

				// Date
				if ($tablename == 'commande_fournisseur' || $tablename == 'supplier_order') $date=$element->date_commande;
				elseif ($tablename == 'projet_task') $date='';	// We show no date. Showing date of beginning of task make user think it is date of time consumed
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
				else if ($tablename == 'don')
                {
                	if ($element->fk_user_author > 0)
                	{
	                	$tmpuser2=new User($db);
	                	$tmpuser2->fetch($element->fk_user_author);
	                	print $tmpuser2->getNomUrl(1,'',48);
                	}
                }
                else if ($tablename == 'projet_task' && $key == 'project_task_time')	// if $key == 'project_task', we don't want details per user
                {
                	print $elementuser->getNomUrl(1);
                }
				print '</td>';

                // Amount without tax
				$warning='';
				if (empty($value['disableamount']))
				{
					if ($tablename == 'don') $total_ht_by_line=$element->amount;
					elseif ($tablename == 'projet_task')
					{
						$tmp = $element->getSumOfAmount($elementuser, $dates, $datee);	// $element is a task. $elementuser may be empty
						$total_ht_by_line = price2num($tmp['amount'],'MT');
						if ($tmp['nblinesnull'] > 0)
						{
							$langs->load("errors");
							$warning=$langs->trans("WarningSomeLinesWithNullHourlyRate");
						}
					}
					else
					{
						$total_ht_by_line=$element->total_ht;
					}
					print '<td align="right">';
					if (! $qualifiedfortotal) print '<strike>';
					print (isset($total_ht_by_line)?price($total_ht_by_line):'&nbsp;');
					if (! $qualifiedfortotal) print '</strike>';
					if ($warning) print ' '.img_warning($warning);
					print '</td>';
				}
				else print '<td></td>';

                // Amount inc tax
				if (empty($value['disableamount']))
				{
					if ($tablename == 'don') $total_ttc_by_line=$element->amount;
					elseif ($tablename == 'projet_task')
					{
						$defaultvat = get_default_tva($mysoc, $mysoc);
						$total_ttc_by_line = price2num($total_ht_by_line * (1 + ($defaultvat / 100)),'MT');
					}
					else
					{
						$total_ttc_by_line=$element->total_ttc;
					}
					print '<td align="right">';
					if (! $qualifiedfortotal) print '<strike>';
					print (isset($total_ttc_by_line)?price($total_ttc_by_line):'&nbsp;');
					if (! $qualifiedfortotal) print '</strike>';
					if ($warning) print ' '.img_warning($warning);
					print '</td>';
				}
				else print '<td></td>';

				// Status
				print '<td align="right">';
				if ($tablename == 'expensereport_det')
				{
					print $expensereport->getLibStatut(5);
				}
				else if ($element instanceof CommonInvoice)
				{
					//This applies for Facture and FactureFournisseur
					print $element->getLibStatut(5, $element->getSommePaiement());
				}
				else if ($element instanceof Task)
				{
					if ($element->progress != '')
					{
						print $element->progress.' %';
					}
				}
				else
				{
					print $element->getLibStatut(5);
				}
				print '</td>';

				print '</tr>';

				if ($qualifiedfortotal)
				{
					$total_ht = $total_ht + $total_ht_by_line;
					$total_ttc = $total_ttc + $total_ttc_by_line;

					$total_ht_by_third += $total_ht_by_line;
					$total_ttc_by_third += $total_ttc_by_line;
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
			//if (empty($value['disableamount']) && ! in_array($tablename, array('projet_task'))) print '<td align="right" width="100">'.$langs->trans("TotalHT").' : '.price($total_ht).'</td>';
			//elseif (empty($value['disableamount']) && in_array($tablename, array('projet_task'))) print '<td align="right" width="100">'.$langs->trans("Total").' : '.price($total_ht).'</td>';
			if (empty($value['disableamount'])) print '<td align="right" width="100">'.$langs->trans("TotalHT").' : '.price($total_ht).'</td>';
			else print '<td></td>';
			//if (empty($value['disableamount']) && ! in_array($tablename, array('projet_task'))) print '<td align="right" width="100">'.$langs->trans("TotalTTC").' : '.price($total_ttc).'</td>';
			//elseif (empty($value['disableamount']) && in_array($tablename, array('projet_task'))) print '<td align="right" width="100"></td>';
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
		print "<br>\n";
	}
}

// Enhance with select2
$nodatarole='';
if ($conf->use_javascript_ajax)
{
	include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
	$comboenhancement = ajax_combobox('.elementselect');
	$out.=$comboenhancement;
	$nodatarole=($comboenhancement?' data-role="none"':'');

	print $comboenhancement;
}



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
