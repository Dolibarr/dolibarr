<?php
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       htdocs/adherents/subscription/list.php
 *      \ingroup    member
 *      \brief      list of subscription
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/subscription.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

$langs->loadLangs(array("members","companies"));

$action=GETPOST('action', 'aZ09');
$massaction=GETPOST('massaction', 'alpha');
$confirm=GETPOST('confirm', 'alpha');
$toselect = GETPOST('toselect', 'array');

$filter=GETPOST("filter", "alpha");
$statut=(GETPOSTISSET("statut")?GETPOST("statut", "alpha"):1);
$search_ref=GETPOST('search_ref', 'alpha');
$search_type=GETPOST('search_type', 'alpha');
$search_lastname=GETPOST('search_lastname', 'alpha');
$search_firstname=GETPOST('search_firstname', 'alpha');
$search_login=GETPOST('search_login', 'alpha');
$search_note=GETPOST('search_note', 'alpha');
$search_account=GETPOST('search_account', 'int');
$search_amount=GETPOST('search_amount', 'alpha');
$optioncss = GETPOST('optioncss', 'alpha');

$date_select=GETPOST("date_select", 'alpha');

$limit = GETPOST('limit', 'int')?GETPOST('limit', 'int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOST("page", 'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) {  $sortorder="DESC"; }
if (! $sortfield) {  $sortfield="c.dateadh"; }

$object = new Subscription($db);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('subscriptionlist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('subscription');
$search_array_options=$extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
);
$arrayfields=array(
	'd.ref'=>array('label'=>$langs->trans("Ref"), 'checked'=>1),
	'd.fk_type'=>array('label'=>$langs->trans("Type"), 'checked'=>1),
	'd.lastname'=>array('label'=>$langs->trans("Lastname"), 'checked'=>1),
	'd.firstname'=>array('label'=>$langs->trans("Firstname"), 'checked'=>1),
	'd.login'=>array('label'=>$langs->trans("Login"), 'checked'=>1),
	't.libelle'=>array('label'=>$langs->trans("Label"), 'checked'=>1),
	'd.bank'=>array('label'=>$langs->trans("BankAccount"), 'checked'=>1, 'enabled'=>(! empty($conf->banque->enabled))),
	/*'d.note_public'=>array('label'=>$langs->trans("NotePublic"), 'checked'=>0),
	 'd.note_private'=>array('label'=>$langs->trans("NotePrivate"), 'checked'=>0),*/
	'c.dateadh'=>array('label'=>$langs->trans("DateSubscription"), 'checked'=>1, 'position'=>100),
	'c.datef'=>array('label'=>$langs->trans("EndSubscription"), 'checked'=>1, 'position'=>101),
	'd.amount'=>array('label'=>$langs->trans("Amount"), 'checked'=>1, 'position'=>102),
	'c.datec'=>array('label'=>$langs->trans("DateCreation"), 'checked'=>0, 'position'=>500),
	'c.tms'=>array('label'=>$langs->trans("DateModificationShort"), 'checked'=>0, 'position'=>500),
//	'd.statut'=>array('label'=>$langs->trans("Status"), 'checked'=>1, 'position'=>1000)
);

// Security check
$result=restrictedArea($user, 'adherent', '', '', 'cotisation');


/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) { $action='list'; $massaction=''; }
if (! GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend' && $massaction != 'confirm_createbills') { $massaction=''; }

$parameters=array('socid'=>$socid);
$reshook=$hookmanager->executeHooks('doActions', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
    // Selection of new fields
    include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

    // Purge search criteria
    if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
    {
	    $search="";
	    $search_type="";
	    $search_ref="";
	    $search_lastname="";
	    $search_firstname="";
	    $search_login="";
	    $search_note="";
	    $search_amount="";
	    $search_account="";
	    $toselect='';
	    $search_array_options=array();
    }
}


/*
 * View
 */

$form=new Form($db);
$subscription=new Subscription($db);
$adherent=new Adherent($db);
$accountstatic=new Account($db);

$now=dol_now();

// List of subscriptions
$sql = "SELECT d.rowid, d.login, d.firstname, d.lastname, d.societe, d.photo, d.statut, d.fk_adherent_type as type,";
$sql.= " c.rowid as crowid, c.fk_type, c.subscription,";
$sql.= " c.dateadh, c.datef, c.datec as date_creation, c.tms as date_update,";
$sql.= " c.fk_bank as bank, c.note,";
$sql.= " b.fk_account";
$sql.= " FROM ".MAIN_DB_PREFIX."adherent as d, ".MAIN_DB_PREFIX."subscription as c";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank as b ON c.fk_bank=b.rowid";
$sql.= " WHERE d.rowid = c.fk_adherent";
$sql.= " AND d.entity IN (".getEntity('adherent').")";
if (isset($date_select) && $date_select != '')
{
    $sql.= " AND c.dateadh >= '".$date_select."-01-01 00:00:00'";
    $sql.= " AND c.dateadh < '".($date_select+1)."-01-01 00:00:00'";
}
if ($search_ref)
{
	if (is_numeric($search_ref)) $sql.= " AND (c.rowid = ".$db->escape($search_ref).")";
	else $sql.=" AND 1 = 2";    // Always wrong
}
if ($search_type) $sql.= natural_search(array('c.fk_type'), $search_type);
if ($search_lastname) $sql.= natural_search(array('d.lastname','d.societe'), $search_lastname);
if ($search_firstname) $sql.= natural_search(array('d.firstname'), $search_firstname);
if ($search_login) $sql.= natural_search('d.login', $search_login);
if ($search_note)  $sql.= natural_search('c.note', $search_note);
if ($search_account > 0) $sql.= " AND b.fk_account = ".urldecode($search_account);
if ($search_amount) $sql.= natural_search('c.subscription', $search_amount, 1);

// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';

// Add where from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListWhere', $parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;

$sql.= $db->order($sortfield, $sortorder);

// Count total nb of records with no order and no limits
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
    $resql = $db->query($sql);
    if ($resql) $nbtotalofrecords = $db->num_rows($resql);
    else dol_print_error($db);
    if (($page * $limit) > $nbtotalofrecords)	// if total resultset is smaller then paging size (filtering), goto and load page 0
    {
    	$page = 0;
    	$offset = 0;
    }
}
// Add limit
$sql.= $db->plimit($limit+1, $offset);

$result = $db->query($sql);
if (! $result)
{
	dol_print_error($db);
	exit;
}

$num = $db->num_rows($result);

$arrayofselected=is_array($toselect)?$toselect:array();

if ($num == 1 && ! empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && $sall)
{
	$obj = $db->fetch_object($resql);
	$id = $obj->rowid;
	header("Location: ".DOL_URL_ROOT.'/adherents/subscription/card.php?id='.$id);
	exit;
}

llxHeader('', $langs->trans("ListOfSubscriptions"), 'EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros');

$i = 0;

$title=$langs->trans("ListOfSubscriptions");
if (! empty($date_select)) $title.=' ('.$langs->trans("Year").' '.$date_select.')';

$param='';
if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.urlencode($contextpage);
if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.urlencode($limit);
if ($statut != '')    $param.="&statut=".urlencode($statut);
if ($search_type)     $param.="&search_type=".urlencode($search_type);
if ($date_select)     $param.="&date_select=".urlencode($date_select);
if ($search_lastname) $param.="&search_lastname=".urlencode($search_lastname);
if ($search_login)    $param.="&search_login=".urlencode($search_login);
if ($search_account)  $param.="&search_account=".urlencode($search_account);
if ($search_amount)   $param.="&search_amount=".urlencode($search_amount);
if ($optioncss != '') $param.='&optioncss='.urlencode($optioncss);
// Add $param from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

// List of mass actions available
$arrayofmassactions =  array(
	//'presend'=>$langs->trans("SendByMail"),
	//'builddoc'=>$langs->trans("PDFMerge"),
);
//if ($user->rights->adherent->supprimer) $arrayofmassactions['predelete']='<span class="fa fa-trash paddingrightonly"></span>'.$langs->trans("Delete");
if (in_array($massaction, array('presend','predelete'))) $arrayofmassactions=array();
$massactionbutton=$form->selectMassAction('', $arrayofmassactions);

$newcardbutton='';
if ($user->rights->adherent->cotisation->creer)
{
    $newcardbutton.= dolGetButtonTitle($langs->trans('NewSubscription'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/adherents/list.php?status=-1,1');
}

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="view" value="'.dol_escape_htmltag($view).'">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'title_generic.png', 0, $newcardbutton, '', $limit);

$topicmail="Information";
$modelmail="subscription";
$objecttmp=new Subscription($db);
$trackid='sub'.$object->id;
include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

if ($sall)
{
	foreach($fieldstosearchall as $key => $val) $fieldstosearchall[$key]=$langs->trans($val);
	print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $sall) . join(', ', $fieldstosearchall).'</div>';
}

$moreforfilter = '';

$varpage=empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;
$selectedfields=$form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);	// This also change content of $arrayfields
if ($massactionbutton) $selectedfields.=$form->showCheckAddButtons('checkforselect', 1);

print '<div class="div-table-responsive">';
print '<table class="tagtable nobottomiftotal liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";


// Line for filters fields
print '<tr class="liste_titre_filter">';

// Line numbering
if (! empty($conf->global->MAIN_SHOW_TECHNICAL_ID))
{
	print '<td class="liste_titre">&nbsp;</td>';
}

// Ref
if (! empty($arrayfields['d.ref']['checked']))
{
	print '<td class="liste_titre left">';
	print '<input class="flat maxwidth50" type="text" name="search_ref" value="'.dol_escape_htmltag($search_ref).'"></td>';
}

// Type
if (! empty($arrayfields['d.fk_type']['checked']))
{
	print '<td class="liste_titre left">';
	print '<input class="flat maxwidth50" type="text" name="search_type" value="'.dol_escape_htmltag($search_type).'">';
	print'</td>';
}

if (! empty($arrayfields['d.lastname']['checked']))
{
	print '<td class="liste_titre left">';
	print '<input class="flat maxwidth75" type="text" name="search_lastname" value="'.dol_escape_htmltag($search_lastname).'"></td>';
}

if (! empty($arrayfields['d.firstname']['checked']))
{
	print '<td class="liste_titre left">';
	print '<input class="flat maxwidth75" type="text" name="search_firstname" value="'.dol_escape_htmltag($search_firstname).'"></td>';
}

if (! empty($arrayfields['d.login']['checked']))
{
	print '<td class="liste_titre left">';
	print '<input class="flat maxwidth75" type="text" name="search_login" value="'.dol_escape_htmltag($search_login).'"></td>';
}

if (! empty($arrayfields['t.libelle']['checked']))
{
	print '<td class="liste_titre">';
	print '';
	print '</td>';
}

if (! empty($arrayfields['d.bank']['checked']))
{
	print '<td class="liste_titre">';
	$form->select_comptes($search_account, 'search_account', 0, '', 1);
	print '</td>';
}

if (! empty($arrayfields['c.dateadh']['checked']))
{
	print '<td class="liste_titre">&nbsp;</td>';
}

if (! empty($arrayfields['c.datef']['checked']))
{
	print '<td class="liste_titre">&nbsp;</td>';
}

if (! empty($arrayfields['d.amount']['checked']))
{
	print '<td class="liste_titre right">';
	print '<input class="flat" type="text" name="search_amount" value="'.dol_escape_htmltag($search_amount).'" size="4">';
	print '</td>';
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

// Fields from hook
$parameters=array('arrayfields'=>$arrayfields);
$reshook=$hookmanager->executeHooks('printFieldListOption', $parameters);    // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
// Date creation
if (! empty($arrayfields['c.datec']['checked']))
{
	print '<td class="liste_titre">';
	print '</td>';
}
// Date modification
if (! empty($arrayfields['c.tms']['checked']))
{
	print '<td class="liste_titre">';
	print '</td>';
}

// Action column
print '<td class="liste_titre right">';
$searchpicto=$form->showFilterButtons();
print $searchpicto;
print '</td>';

print "</tr>\n";


print '<tr class="liste_titre">';
if (! empty($arrayfields['d.ref']['checked']))
{
	print_liste_field_titre("Ref", $_SERVER["PHP_SELF"], "c.rowid", $param, "", "", $sortfield, $sortorder);
}
if (! empty($arrayfields['d.fk_type']['checked']))
{
	print_liste_field_titre("Type", $_SERVER["PHP_SELF"], "c.fk_type", $param, "", "", $sortfield, $sortorder);
}
if (! empty($arrayfields['d.lastname']['checked']))
{
	print_liste_field_titre("LastName", $_SERVER["PHP_SELF"], "d.lastname", $param, "", "", $sortfield, $sortorder);
}
if (! empty($arrayfields['d.firstname']['checked']))
{
	print_liste_field_titre("FirstName", $_SERVER["PHP_SELF"], "d.firstname", $param, "", "", $sortfield, $sortorder);
}
if (! empty($arrayfields['d.login']['checked']))
{
	print_liste_field_titre("Login", $_SERVER["PHP_SELF"], "d.login", $param, "", "", $sortfield, $sortorder);
}
if (! empty($arrayfields['t.libelle']['checked']))
{
	print_liste_field_titre("Label", $_SERVER["PHP_SELF"], "c.note", $param, "", '', $sortfield, $sortorder);
}
if (! empty($arrayfields['d.bank']['checked']))
{
	print_liste_field_titre("Account", $_SERVER["PHP_SELF"], "b.fk_account", $param, "", "", $sortfield, $sortorder);
}
if (! empty($arrayfields['c.dateadh']['checked']))
{
	print_liste_field_titre("DateStart", $_SERVER["PHP_SELF"], "c.dateadh", $param, "", '', $sortfield, $sortorder, 'center nowraponall ');
}
if (! empty($arrayfields['c.datef']['checked']))
{
	print_liste_field_titre("DateEnd", $_SERVER["PHP_SELF"], "c.datef", $param, "", '', $sortfield, $sortorder, 'center nowraponall ');
}
if (! empty($arrayfields['d.amount']['checked']))
{
	print_liste_field_titre("Amount", $_SERVER["PHP_SELF"], "c.subscription", $param, "", '', $sortfield, $sortorder, 'right ');
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';

// Hook fields
$parameters=array('arrayfields'=>$arrayfields,'param'=>$param,'sortfield'=>$sortfield,'sortorder'=>$sortorder);
$reshook=$hookmanager->executeHooks('printFieldListTitle', $parameters);    // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
if (! empty($arrayfields['c.datec']['checked']))     print_liste_field_titre($arrayfields['c.datec']['label'], $_SERVER["PHP_SELF"], "c.datec", "", $param, 'align="center" class="nowrap"', $sortfield, $sortorder);
if (! empty($arrayfields['c.tms']['checked']))       print_liste_field_titre($arrayfields['c.tms']['label'], $_SERVER["PHP_SELF"], "c.tms", "", $param, 'align="center" class="nowrap"', $sortfield, $sortorder);
print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], '', '', '', 'align="center"', $sortfield, $sortorder, 'maxwidthsearch ');
print "</tr>\n";


$total=0;
$totalarray=array();
while ($i < min($num, $limit))
{
	$obj = $db->fetch_object($result);
	$total+=$obj->subscription;

	$subscription->ref=$obj->crowid;
	$subscription->id=$obj->crowid;

	$adherent->lastname=$obj->lastname;
	$adherent->firstname=$obj->firstname;
	$adherent->ref=$obj->rowid;
	$adherent->id=$obj->rowid;
	$adherent->statut=$obj->statut;
	$adherent->login=$obj->login;
	$adherent->photo=$obj->photo;
	$adherent->typeid=$obj->type;

	$typeid = ($obj->fk_type > 0 ? $obj->fk_type : $adherent->typeid);
    $adht = new AdherentType($db);
    $adht->fetch($typeid);

	print '<tr class="oddeven">';

	// Ref
	if (! empty($arrayfields['d.ref']['checked']))
	{
		print '<td>'.$subscription->getNomUrl(1).'</td>';
		if (! $i) $totalarray['nbfield']++;
	}
    // Type
    if (! empty($arrayfields['d.fk_type']['checked']))
	{
        print '<td>';
        if ($typeid > 0)
        {
        	print $adht->getNomUrl(1);
        }
        print '</td>';
        if (! $i) $totalarray['nbfield']++;
	}

	// Lastname
	if (! empty($arrayfields['d.lastname']['checked']))
	{
		$adherent->firstname = '';
		print '<td>'.$adherent->getNomUrl(-1).'</td>';
		$adherent->firstname = $obj->firstname;
		if (! $i) $totalarray['nbfield']++;
	}
	// Firstname
	if (! empty($arrayfields['d.firstname']['checked']))
	{
		print '<td>'.$adherent->firstname.'</td>';
		if (! $i) $totalarray['nbfield']++;
	}

	// Login
	if (! empty($arrayfields['d.login']['checked']))
	{
		print '<td>'.$adherent->login.'</td>';
		if (! $i) $totalarray['nbfield']++;
	}

	// Label
	if (! empty($arrayfields['t.libelle']['checked']))
	{
		print '<td>';
		print dol_trunc($obj->note, 128);
		print '</td>';
		if (! $i) $totalarray['nbfield']++;
	}

	// Banque
	if (! empty($arrayfields['d.bank']['checked']))
	{
		print "<td>";
		if ($obj->fk_account > 0)
		{
			$accountstatic->id=$obj->fk_account;
			$accountstatic->fetch($obj->fk_account);
			//$accountstatic->label=$obj->label;
			print $accountstatic->getNomUrl(1);
		}
		print "</td>\n";
		if (! $i) $totalarray['nbfield']++;
	}

	// Date start
	if (! empty($arrayfields['c.dateadh']['checked']))
	{
		print '<td class="center">'.dol_print_date($db->jdate($obj->dateadh), 'day')."</td>\n";
		if (! $i) $totalarray['nbfield']++;
	}
	// Date end
	if (! empty($arrayfields['c.datef']['checked']))
	{
		print '<td class="center">'.dol_print_date($db->jdate($obj->datef), 'day')."</td>\n";
		if (! $i) $totalarray['nbfield']++;
	}
	// Price
	if (! empty($arrayfields['d.amount']['checked']))
	{
		print '<td class="right">'.price($obj->subscription).'</td>';
		if (! $i) $totalarray['nbfield']++;
		if (! $i) $totalarray['pos'][$totalarray['nbfield']]='d.amount';
		$totalarray['val']['d.amount'] += $obj->subscription;
	}
	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
	// Fields from hook
	$parameters=array('arrayfields'=>$arrayfields, 'obj'=>$obj);
	$reshook=$hookmanager->executeHooks('printFieldListValue', $parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	// Date creation
	if (! empty($arrayfields['c.datec']['checked']))
	{
		print '<td class="nowrap center">';
		print dol_print_date($db->jdate($obj->date_creation), 'dayhour', 'tzuser');
		print '</td>';
		if (! $i) $totalarray['nbfield']++;
	}
	// Date modification
	if (! empty($arrayfields['c.tms']['checked']))
	{
		print '<td class="nowrap center">';
		print dol_print_date($db->jdate($obj->date_update), 'dayhour', 'tzuser');
		print '</td>';
		if (! $i) $totalarray['nbfield']++;
	}
	// Action column
	print '<td class="center">';
	if ($massactionbutton || $massaction)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
	{
		$selected=0;
		if (in_array($obj->rowid, $arrayofselected)) $selected=1;
		print '<input id="cb'.$obj->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected?' checked="checked"':'').'>';
	}
	print '</td>';
	if (! $i) $totalarray['nbfield']++;

	print "</tr>\n";
	$i++;
}

// Show total line
if (isset($totalarray['pos']))
{
	print '<tr class="liste_total">';
	$i=0;
	while ($i < $totalarray['nbfield'])
	{
		$i++;
		if (! empty($totalarray['pos'][$i]))  print '<td class="right">'.price($totalarray['val'][$totalarray['pos'][$i]]).'</td>';
		else
		{
			if ($i == 1)
			{
				if ($num < $limit) print '<td class="left">'.$langs->trans("Total").'</td>';
				else print '<td class="left">'.$langs->trans("Totalforthispage").'</td>';
			}
			else print '<td></td>';
		}
	}
	print '</tr>';
}

// If no record found
if ($num == 0)
{
	$colspan=1;
	foreach($arrayfields as $key => $val) { if (! empty($val['checked'])) $colspan++; }
	print '<tr><td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("NoRecordFound").'</td></tr>';
}

$db->free($resql);

$parameters=array('sql' => $sql);
$reshook=$hookmanager->executeHooks('printFieldListFooter', $parameters);    // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print "</table>";
print '</div>';
print '</form>';


// End of page
llxFooter();
$db->close();
