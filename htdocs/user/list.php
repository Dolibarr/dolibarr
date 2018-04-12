<?php
/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2017 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2015      Alexandre Spangaro   <aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2016      Marcos García        <marcosgdf@gmail.com>
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
 *      \file       htdocs/user/list.php
 * 		\ingroup	core
 *      \brief      Page of users
 */

require '../main.inc.php';

if (! $user->rights->user->user->lire && ! $user->admin)
	accessforbidden();

$langs->load("users");
$langs->load("companies");
$langs->load('hrm');

$contextpage=GETPOST('contextpage','aZ')?GETPOST('contextpage','aZ'):'userlist';   // To manage different context of search

// Security check (for external users)
$socid=0;
if ($user->societe_id > 0)
	$socid = $user->societe_id;

// Load mode employee
$mode = GETPOST("mode", 'alpha');

// Load variable for pagination
$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
$page = GETPOST('page','int');
if (empty($page) || $page == -1) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield="u.login";
if (! $sortorder) $sortorder="ASC";

// Define value to know what current user can do on users
$canadduser=(! empty($user->admin) || $user->rights->user->user->creer);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$object = new User($db);
$hookmanager->initHooks(array('userlist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('user');
$search_array_options=$extrafields->getOptionalsFromPost($extralabels,'','search_');

$userstatic=new User($db);
$companystatic = new Societe($db);
$form = new Form($db);

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	'u.login'=>"Login",
	'u.lastname'=>"Lastname",
	'u.firstname'=>"Firstname",
	'u.accountancy_code'=>"AccountancyCode",
	'u.email'=>"EMail",
	'u.note'=>"Note"
);

// Definition of fields for list
$arrayfields=array(
	'u.login'=>array('label'=>$langs->trans("Login"), 'checked'=>1),
	'u.lastname'=>array('label'=>$langs->trans("Lastname"), 'checked'=>1),
	'u.firstname'=>array('label'=>$langs->trans("Firstname"), 'checked'=>1),
	'u.gender'=>array('label'=>$langs->trans("Gender"), 'checked'=>0),
	'u.employee'=>array('label'=>$langs->trans("Employee"), 'checked'=>($mode=='employee'?1:0)),
	'u.accountancy_code'=>array('label'=>$langs->trans("AccountancyCode"), 'checked'=>0),
	'u.email'=>array('label'=>$langs->trans("EMail"), 'checked'=>1),
	'u.fk_soc'=>array('label'=>$langs->trans("Company"), 'checked'=>1),
	'u.entity'=>array('label'=>$langs->trans("Entity"), 'checked'=>1, 'enabled'=>(! empty($conf->multicompany->enabled) && empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE))),
	'u.fk_user'=>array('label'=>$langs->trans("HierarchicalResponsible"), 'checked'=>1),
	'u.datelastlogin'=>array('label'=>$langs->trans("LastConnexion"), 'checked'=>1, 'position'=>100),
	'u.datepreviouslogin'=>array('label'=>$langs->trans("PreviousConnexion"), 'checked'=>0, 'position'=>110),
	'u.datec'=>array('label'=>$langs->trans("DateCreation"), 'checked'=>0, 'position'=>500),
	'u.tms'=>array('label'=>$langs->trans("DateModificationShort"), 'checked'=>0, 'position'=>500),
	'u.statut'=>array('label'=>$langs->trans("Status"), 'checked'=>1, 'position'=>1000),
);
// Extra fields
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
{
   foreach($extrafields->attribute_label as $key => $val)
   {
		if (! empty($extrafields->attribute_list[$key])) $arrayfields["ef.".$key]=array('label'=>$extrafields->attribute_label[$key], 'checked'=>(($extrafields->attribute_list[$key]<0)?0:1), 'position'=>$extrafields->attribute_pos[$key], 'enabled'=>(abs($extrafields->attribute_list[$key])!=3 && $extrafields->attribute_perms[$key]));
   }
}

// Init search fields
$sall=trim((GETPOST('search_all', 'alphanohtml')!='')?GETPOST('search_all', 'alphanohtml'):GETPOST('sall', 'alphanohtml'));
$search_user=GETPOST('search_user','alpha');
$search_login=GETPOST('search_login','alpha');
$search_lastname=GETPOST('search_lastname','alpha');
$search_firstname=GETPOST('search_firstname','alpha');
$search_gender=GETPOST('search_gender','alpha');
$search_employee=GETPOST('search_employee','alpha');
$search_accountancy_code=GETPOST('search_accountancy_code','alpha');
$search_email=GETPOST('search_email','alpha');
$search_statut=GETPOST('search_statut','intcomma');
$search_thirdparty=GETPOST('search_thirdparty','alpha');
$search_supervisor=GETPOST('search_supervisor','intcomma');
$search_previousconn=GETPOST('search_previousconn','alpha');
$optioncss = GETPOST('optioncss','alpha');

// Default search
if ($search_statut == '') $search_statut='1';
if ($mode == 'employee') $search_employee=1;



/*
 * Actions
 */

if (GETPOST('cancel','alpha')) { $action='list'; $massaction=''; }
if (! GETPOST('confirmmassaction','alpha') && $massaction != 'presend' && $massaction != 'confirm_presend' && $massaction != 'confirm_createbills') { $massaction=''; }

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Purge search criteria
	if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') ||GETPOST('button_removefilter','alpha')) // All tests are required to be compatible with all browsers
	{
		$search_user="";
		$search_login="";
		$search_lastname="";
		$search_firstname="";
		$search_gender="";
		$search_employee="";
		$search_accountancy_code="";
		$search_email="";
		$search_statut="";
		$search_thirdparty="";
		$search_supervisor="";
		$search_datelastlogin="";
		$search_datepreviouslogin="";
		$search_date_creation="";
		$search_date_update="";
		$search_array_options=array();
	}
}


/*
 * View
 */

$user2=new User($db);

$buttonviewhierarchy='<form action="'.DOL_URL_ROOT.'/user/hierarchy.php'.(($search_statut != '' && $search_statut >= 0) ? '?search_statut='.$search_statut : '').'" method="POST"><input type="submit" class="button" style="width:120px" name="viewcal" value="'.dol_escape_htmltag($langs->trans("HierarchicView")).'"></form>';

$sql = "SELECT DISTINCT u.rowid, u.lastname, u.firstname, u.admin, u.fk_soc, u.login, u.email, u.accountancy_code, u.gender, u.employee, u.photo,";
$sql.= " u.datelastlogin, u.datepreviouslogin,";
$sql.= " u.ldap_sid, u.statut, u.entity,";
$sql.= " u.tms as date_update, u.datec as date_creation,";
$sql.= " u2.rowid as id2, u2.login as login2, u2.firstname as firstname2, u2.lastname as lastname2, u2.admin as admin2, u2.fk_soc as fk_soc2, u2.email as email2, u2.gender as gender2, u2.photo as photo2, u2.entity as entity2,";
$sql.= " s.nom as name, s.canvas";
// Add fields from extrafields
foreach ($extrafields->attribute_label as $key => $val) $sql.=($extrafields->attribute_type[$key] != 'separate' ? ",ef.".$key.' as options_'.$key : '');
// Add fields from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListSelect',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql.= " FROM ".MAIN_DB_PREFIX."user as u";
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."user_extrafields as ef on (u.rowid = ef.fk_object)";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON u.fk_soc = s.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."user as u2 ON u.fk_user = u2.rowid";
// Add fields from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printUserListWhere',$parameters);    // Note that $action and $object may have been modified by hook
if ($reshook > 0) {
	$sql.=$hookmanager->resPrint;
} else {
	$sql.= " WHERE u.entity IN (".getEntity('user').")";
}
if ($socid > 0) $sql.= " AND u.fk_soc = ".$socid;
//if ($search_user != '')       $sql.=natural_search(array('u.login', 'u.lastname', 'u.firstname'), $search_user);
if ($search_supervisor > 0)   $sql.= " AND u.fk_user IN (".$db->escape($search_supervisor).")";
if ($search_thirdparty != '') $sql.= natural_search(array('s.nom'), $search_thirdparty);
if ($search_login != '')      $sql.= natural_search("u.login", $search_login);
if ($search_lastname != '')   $sql.= natural_search("u.lastname", $search_lastname);
if ($search_firstname != '')  $sql.= natural_search("u.firstname", $search_firstname);
if ($search_gender != '' && $search_gender != '-1')     $sql.= " AND u.gender = '".$search_gender."'";
if (is_numeric($search_employee) && $search_employee >= 0)    {
	$sql .= ' AND u.employee = '.(int) $search_employee;
}
if ($search_accountancy_code != '')  $sql.= natural_search("u.accountancy_code", $search_accountancy_code);
if ($search_email != '')             $sql.= natural_search("u.email", $search_email);
if ($search_statut != '' && $search_statut >= 0) $sql.= " AND u.statut IN (".$db->escape($search_statut).")";
if ($sall)                           $sql.= natural_search(array_keys($fieldstosearchall), $sall);
// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListWhere',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql.=$db->order($sortfield,$sortorder);

$nbtotalofrecords=0;
$result=$db->query($sql);
if ($result)
{
	$nbtotalofrecords = $db->num_rows($result);
}

$sql.= $db->plimit($limit+1, $offset);

$result = $db->query($sql);
if (! $result)
{
	dol_print_error($db);
	exit;
}

$num = $db->num_rows($result);

if ($num == 1 && ! empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && $sall)
{
	$obj = $db->fetch_object($resql);
	$id = $obj->rowid;
	header("Location: ".DOL_URL_ROOT.'/user/card.php?id='.$id);
	exit;
}

llxHeader('',$langs->trans("ListOfUsers"));

$param='';
if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.$contextpage;
if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.$limit;
if ($sall != '') $param.='&sall='.urlencode($sall);
if ($search_user != '') $param.="&search_user=".$search_user;
if ($search_login != '') $param.="&search_login=".$search_login;
if ($search_lastname != '') $param.="&search_lastname=".$search_lastname;
if ($search_firstname != '') $param.="&search_firstname=".$search_firstname;
if ($search_gender != '') $param.="&search_gender=".$search_gender;
if ($search_employee != '') $param.="&search_employee=".$search_employee;
if ($search_accountancy_code != '') $param.="&search_accountancy_code=".$search_accountancy_code;
if ($search_email != '') $param.="&search_email=".$search_email;
if ($search_supervisor > 0) $param.="&search_supervisor=".$search_supervisor;
if ($search_statut != '') $param.="&search_statut=".$search_statut;
if ($optioncss != '') $param.='&optioncss='.$optioncss;
if ($mode != '')      $param.='&mode='.$mode;
// Add $param from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

$text = $langs->trans("ListOfUsers");

$newcardbutton='';
if ($canadduser)
{
	$newcardbutton='<a class="butAction" href="'.DOL_URL_ROOT.'/user/card.php?action=create'.($mode == 'employee' ? '&employee=1': '').'&leftmenu=">'.$langs->trans('NewUser').'</a>';
}

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">'."\n";
if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="mode" value="'.$mode.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';



print_barre_liste($text, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, "", $num, $nbtotalofrecords, 'title_generic', 0, $newcardbutton, '', $limit);

if ($sall)
{
	foreach($fieldstosearchall as $key => $val) $fieldstosearchall[$key]=$langs->trans($val);
	print $langs->trans("FilterOnInto", $sall) . join(', ',$fieldstosearchall);
}

$moreforfilter='';

$varpage=empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;
$selectedfields=$form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);	// This also change content of $arrayfields


print '<div class="div-table-responsive">';
print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

// Search bar
print '<tr class="liste_titre_filter">';
if (! empty($arrayfields['u.login']['checked']))
{
	print '<td class="liste_titre"><input type="text" name="search_login" size="6" value="'.$search_login.'"></td>';
}
if (! empty($arrayfields['u.lastname']['checked']))
{
	print '<td class="liste_titre"><input type="text" name="search_lastname" size="6" value="'.$search_lastname.'"></td>';
}
if (! empty($arrayfields['u.firstname']['checked']))
{
	print '<td class="liste_titre"><input type="text" name="search_firstname" size="6" value="'.$search_firstname.'"></td>';
}
if (! empty($arrayfields['u.gender']['checked']))
{
	print '<td class="liste_titre">';
	$arraygender=array('man'=>$langs->trans("Genderman"),'woman'=>$langs->trans("Genderwoman"));
	print $form->selectarray('search_gender', $arraygender, $search_gender, 1);
	print '</td>';
}
if (! empty($arrayfields['u.employee']['checked']))
{
	print '<td class="liste_titre">';
	print $form->selectyesno('search_employee', $search_employee, 1, false, 1);
	print '</td>';
}
if (! empty($arrayfields['u.accountancy_code']['checked']))
{
	print '<td class="liste_titre"><input type="text" name="search_accountancy_code" size="4" value="'.$search_accountancy_code.'"></td>';
}
if (! empty($arrayfields['u.email']['checked']))
{
	print '<td class="liste_titre"><input type="text" name="search_email" size="6" value="'.$search_email.'"></td>';
}
if (! empty($arrayfields['u.fk_soc']['checked']))
{
	print '<td class="liste_titre"><input type="text" name="search_thirdparty" size="6" value="'.$search_thirdparty.'"></td>';
}
if (! empty($arrayfields['u.entity']['checked']))
{
	print '<td class="liste_titre"></td>';
}
// Supervisor
if (! empty($arrayfields['u.fk_user']['checked']))
{
	print '<td class="liste_titre">';
	print $form->select_dolusers($search_supervisor, 'search_supervisor', 1, array(), 0, '', 0, 0, 0, 0, '', 0, '', 'maxwidth200');
	print '</td>';
}
if (! empty($arrayfields['u.datelastlogin']['checked']))
{
	print '<td class="liste_titre"></td>';
}
if (! empty($arrayfields['u.datepreviouslogin']['checked']))
{
	print '<td class="liste_titre"></td>';
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';
// Fields from hook
$parameters=array('arrayfields'=>$arrayfields);
$reshook=$hookmanager->executeHooks('printFieldListOption',$parameters);    // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
if (! empty($arrayfields['u.datec']['checked']))
{
	// Date creation
	print '<td class="liste_titre">';
	print '</td>';
}
if (! empty($arrayfields['u.tms']['checked']))
{
	// Date modification
	print '<td class="liste_titre">';
	print '</td>';
}
if (! empty($arrayfields['u.statut']['checked']))
{
	// Status
	print '<td class="liste_titre" align="center">';
	print $form->selectarray('search_statut', array('-1'=>'','0'=>$langs->trans('Disabled'),'1'=>$langs->trans('Enabled')),$search_statut);
	print '</td>';
}
// Action column
print '<td class="liste_titre" align="right">';
$searchpicto=$form->showFilterAndCheckAddButtons(0);
print $searchpicto;
print '</td>';

print "</tr>\n";


print '<tr class="liste_titre">';
if (! empty($arrayfields['u.login']['checked']))          print_liste_field_titre("Login",$_SERVER['PHP_SELF'],"u.login",$param,"","",$sortfield,$sortorder);
if (! empty($arrayfields['u.lastname']['checked']))       print_liste_field_titre("Lastname",$_SERVER['PHP_SELF'],"u.lastname",$param,"","",$sortfield,$sortorder);
if (! empty($arrayfields['u.firstname']['checked']))      print_liste_field_titre("FirstName",$_SERVER['PHP_SELF'],"u.firstname",$param,"","",$sortfield,$sortorder);
if (! empty($arrayfields['u.gender']['checked']))         print_liste_field_titre("Gender",$_SERVER['PHP_SELF'],"u.gender",$param,"","",$sortfield,$sortorder);
if (! empty($arrayfields['u.employee']['checked']))       print_liste_field_titre("Employee",$_SERVER['PHP_SELF'],"u.employee",$param,"","",$sortfield,$sortorder);
if (! empty($arrayfields['u.accountancy_code']['checked'])) print_liste_field_titre("AccountancyCode",$_SERVER['PHP_SELF'],"u.accountancy_code",$param,"","",$sortfield,$sortorder);
if (! empty($arrayfields['u.email']['checked']))          print_liste_field_titre("EMail",$_SERVER['PHP_SELF'],"u.email",$param,"","",$sortfield,$sortorder);
if (! empty($arrayfields['u.fk_soc']['checked']))         print_liste_field_titre("Company",$_SERVER['PHP_SELF'],"u.fk_soc",$param,"","",$sortfield,$sortorder);
if (! empty($arrayfields['u.entity']['checked']))         print_liste_field_titre("Entity",$_SERVER['PHP_SELF'],"u.entity",$param,"","",$sortfield,$sortorder);
if (! empty($arrayfields['u.fk_user']['checked']))        print_liste_field_titre("HierarchicalResponsible",$_SERVER['PHP_SELF'],"u.fk_user",$param,"","",$sortfield,$sortorder);
if (! empty($arrayfields['u.datelastlogin']['checked']))  print_liste_field_titre("LastConnexion",$_SERVER['PHP_SELF'],"u.datelastlogin",$param,"",'align="center"',$sortfield,$sortorder);
if (! empty($arrayfields['u.datepreviouslogin']['checked'])) print_liste_field_titre("PreviousConnexion",$_SERVER['PHP_SELF'],"u.datepreviouslogin",$param,"",'align="center"',$sortfield,$sortorder);
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
// Hook fields
$parameters=array('arrayfields'=>$arrayfields,'param'=>$param,'sortfield'=>$sortfield,'sortorder'=>$sortorder);
$reshook=$hookmanager->executeHooks('printFieldListTitle',$parameters);    // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
if (! empty($arrayfields['u.datec']['checked']))  print_liste_field_titre("DateCreationShort",$_SERVER["PHP_SELF"],"u.datec","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
if (! empty($arrayfields['u.tms']['checked']))    print_liste_field_titre("DateModificationShort",$_SERVER["PHP_SELF"],"u.tms","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
if (! empty($arrayfields['u.statut']['checked'])) print_liste_field_titre("Status",$_SERVER["PHP_SELF"],"u.statut","",$param,'align="center"',$sortfield,$sortorder);
print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"],"",'','','align="center"',$sortfield,$sortorder,'maxwidthsearch ');
print "</tr>\n";



$i = 0;
$totalarray=array();
while ($i < min($num,$limit))
{
	$obj = $db->fetch_object($result);

	$userstatic->id=$obj->rowid;
	$userstatic->ref=$obj->label;
	$userstatic->login=$obj->login;
	$userstatic->statut=$obj->statut;
	$userstatic->email=$obj->email;
	$userstatic->gender=$obj->gender;
	$userstatic->societe_id=$obj->fk_soc;
	$userstatic->firstname=$obj->firstname;
	$userstatic->lastname=$obj->lastname;
	$userstatic->employee=$obj->employee;
	$userstatic->photo=$obj->photo;

	$li=$userstatic->getNomUrl(-1,'',0,0,24,1,'login','',1);

	print "<tr>";
	if (! empty($arrayfields['u.login']['checked']))
	{
		print '<td>';
		print $li;
		if (! empty($conf->multicompany->enabled) && $obj->admin && ! $obj->entity)
		{
		  	print img_picto($langs->trans("SuperAdministrator"), 'redstar', 'class="valignmiddle paddingleft"');
		}
		else if ($obj->admin)
		{
			print img_picto($langs->trans("Administrator"), 'star', 'class="valignmiddle paddingleft"');
		}
		print '</td>';
		if (! $i) $totalarray['nbfield']++;
	}
	if (! empty($arrayfields['u.lastname']['checked']))
	{
		  print '<td>'.$obj->lastname.'</td>';
		if (! $i) $totalarray['nbfield']++;
	}
	if (! empty($arrayfields['u.firstname']['checked']))
	{
	  print '<td>'.$obj->firstname.'</td>';
		if (! $i) $totalarray['nbfield']++;
	}
	if (! empty($arrayfields['u.gender']['checked']))
	{
	  print '<td>';
	  if ($obj->gender) print $langs->trans("Gender".$obj->gender);
	  print '</td>';
		if (! $i) $totalarray['nbfield']++;
	}
	if (! empty($arrayfields['u.employee']['checked']))
	{
	  print '<td>'.yn($obj->employee).'</td>';
		if (! $i) $totalarray['nbfield']++;
	}
	if (! empty($arrayfields['u.accountancy_code']['checked']))
	{
	  print '<td>'.$obj->accountancy_code.'</td>';
		if (! $i) $totalarray['nbfield']++;
	}
	if (! empty($arrayfields['u.email']['checked']))
	{
	  print '<td>'.$obj->email.'</td>';
		if (! $i) $totalarray['nbfield']++;
	}
	if (! empty($arrayfields['u.fk_soc']['checked']))
	{
		print "<td>";
		if ($obj->fk_soc)
		{
			$companystatic->id=$obj->fk_soc;
			$companystatic->name=$obj->name;
			$companystatic->canvas=$obj->canvas;
			print $companystatic->getNomUrl(1);
		}
		else if ($obj->ldap_sid)
		{
			print $langs->trans("DomainUser");
		}
		else
	   {
			print $langs->trans("InternalUser");
		}
		print '</td>';
		if (! $i) $totalarray['nbfield']++;
	}
	// Multicompany enabled
	if (! empty($conf->multicompany->enabled) && is_object($mc) && empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE))
	{
		if (! empty($arrayfields['u.entity']['checked']))
		{
			print '<td>';
			if (! $obj->entity)
			{
				print $langs->trans("AllEntities");
			}
			else
			{
				$mc->getInfo($obj->entity);
				print $mc->label;
			}
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}
	}
	// Supervisor
	if (! empty($arrayfields['u.fk_user']['checked']))
	{
		// Resp
		print '<td class="nowrap">';
		if ($obj->login2)
		{
			$user2->id=$obj->id2;
			$user2->login=$obj->login2;
			$user2->lastname=$obj->lastname2;
			$user2->firstname=$obj->firstname2;
			$user2->gender=$obj->gender2;
			$user2->photo=$obj->photo2;
			$user2->admin=$obj->admin2;
			$user2->email=$obj->email2;
			$user2->socid=$obj->fk_soc2;
			print $user2->getNomUrl(-1,'',0,0,24,0,'','',1);
			if (! empty($conf->multicompany->enabled) && $obj->admin2 && ! $obj->entity2)
			{
			  	print img_picto($langs->trans("SuperAdministrator"), 'redstar', 'class="valignmiddle paddingleft"');
			}
			else if ($obj->admin2)
			{
				print img_picto($langs->trans("Administrator"), 'star', 'class="valignmiddle paddingleft"');
			}
		}
		print '</td>';
		if (! $i) $totalarray['nbfield']++;
	}

	// Date last login
	if (! empty($arrayfields['u.datelastlogin']['checked']))
	{
		print '<td class="nowrap" align="center">'.dol_print_date($db->jdate($obj->datelastlogin),"dayhour").'</td>';
		if (! $i) $totalarray['nbfield']++;
	}
	// Date previous login
	if (! empty($arrayfields['u.datepreviouslogin']['checked']))
	{
		print '<td class="nowrap" align="center">'.dol_print_date($db->jdate($obj->datepreviouslogin),"dayhour").'</td>';
		if (! $i) $totalarray['nbfield']++;
	}

	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
	// Fields from hook
	$parameters=array('arrayfields'=>$arrayfields, 'obj'=>$obj);
	$reshook=$hookmanager->executeHooks('printFieldListValue',$parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	// Date creation
	if (! empty($arrayfields['u.datec']['checked']))
	{
		print '<td align="center">';
		print dol_print_date($db->jdate($obj->date_creation), 'dayhour', 'tzuser');
		print '</td>';
		if (! $i) $totalarray['nbfield']++;
	}
	// Date modification
	if (! empty($arrayfields['u.tms']['checked']))
	{
		print '<td align="center">';
		print dol_print_date($db->jdate($obj->date_update), 'dayhour', 'tzuser');
		print '</td>';
		if (! $i) $totalarray['nbfield']++;
	}
	// Status
	if (! empty($arrayfields['u.statut']['checked']))
	{
	   $userstatic->statut=$obj->statut;
	   print '<td align="center">'.$userstatic->getLibStatut(3).'</td>';
	   if (! $i) $totalarray['nbfield']++;
	}
	// Action column
	print '<td></td>';
	if (! $i) $totalarray['nbfield']++;

	print "</tr>\n";

	$i++;
}

$parameters=array('arrayfields'=>$arrayfields, 'sql'=>$sql);
$reshook=$hookmanager->executeHooks('printFieldListFooter',$parameters);    // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print "</table>";
print '</div>';
print "</form>\n";

$db->free($result);

llxFooter();
$db->close();
