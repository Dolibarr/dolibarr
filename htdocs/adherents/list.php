<?php
/* Copyright (C) 2001-2003  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003  Jean-Louis Bergamo      <jlb@j1b.org>
 * Copyright (C) 2004-2016  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2013-2015  Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2014-2016  Juanjo Menent           <jmenent@2byte.es>
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
 *      \file       htdocs/adherents/list.php
 *      \ingroup    member
 *		\brief      Page to list all members of foundation
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';

$langs->loadLangs(array("members","companies"));

$action=GETPOST('action','aZ09');
$massaction=GETPOST('massaction','alpha');
$show_files=GETPOST('show_files','int');
$confirm=GETPOST('confirm','alpha');
$toselect = GETPOST('toselect', 'array');

// Security check
$result=restrictedArea($user,'adherent');

$filter=GETPOST("filter",'alpha');
$statut=GETPOST("statut",'intcomma');
$search=GETPOST("search",'alpha');
$search_ref=GETPOST("search_ref",'alpha');
$search_lastname=GETPOST("search_lastname",'alpha');
$search_firstname=GETPOST("search_firstname",'alpha');
$search_civility=GETPOST("search_civility",'alpha');
$search_login=GETPOST("search_login",'alpha');
$search_address=GETPOST("search_address",'alpha');
$search_zip=GETPOST("search_zip",'alpha');
$search_town=GETPOST("search_town",'alpha');
$search_state=GETPOST("search_state",'alpha');
$search_country=GETPOST("search_country",'alpha');
$search_phone=GETPOST("search_phone",'alpha');
$search_phone_perso=GETPOST("search_phone_perso",'alpha');
$search_phone_mobile=GETPOST("search_phone_mobile",'alpha');
$search_type=GETPOST("search_type",'alpha');
$search_email=GETPOST("search_email",'alpha');
$search_categ = GETPOST("search_categ",'int');
$catid        = GETPOST("catid",'int');
$optioncss = GETPOST('optioncss','alpha');

$sall=trim((GETPOST('search_all', 'alphanohtml')!='')?GETPOST('search_all', 'alphanohtml'):GETPOST('sall', 'alphanohtml'));

if ($statut < -1) $statut = '';

$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) { $sortorder=($filter=='outofdate'?"DESC":"ASC"); }
if (! $sortfield) { $sortfield=($filter=='outofdate'?"d.datefin":"d.lastname"); }

$object = new Adherent($db);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('memberlist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('adherent');
$search_array_options=$extrafields->getOptionalsFromPost($object->table_element,'','search_');

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	'd.rowid'=>'Ref',
	'd.login'=>'Login',
	'd.lastname'=>'Lastname',
	'd.firstname'=>'Firstname',
	'd.login'=>'Login',
	'd.societe'=>"Company",
	'd.email'=>'EMail',
	'd.address'=>'Address',
	'd.zip'=>'Zip',
	'd.town'=>'Town',
	'd.note_public'=>'NotePublic',
	'd.note_private'=>'NotePrivate',
);
if($db->type == 'pgsql') unset($fieldstosearchall['d.rowid']);
$arrayfields=array(
	'd.ref'=>array('label'=>$langs->trans("Ref"), 'checked'=>1),
	'd.civility'=>array('label'=>$langs->trans("Civility"), 'checked'=>0),
	'd.lastname'=>array('label'=>$langs->trans("Lastname"), 'checked'=>1),
	'd.firstname'=>array('label'=>$langs->trans("Firstname"), 'checked'=>1),
	'd.company'=>array('label'=>$langs->trans("Company"), 'checked'=>1),
	'd.login'=>array('label'=>$langs->trans("Login"), 'checked'=>1),
	'd.morphy'=>array('label'=>$langs->trans("MorPhy"), 'checked'=>1),
	't.libelle'=>array('label'=>$langs->trans("Type"), 'checked'=>1),
	'd.email'=>array('label'=>$langs->trans("Email"), 'checked'=>1),
	'd.address'=>array('label'=>$langs->trans("Address"), 'checked'=>0),
	'd.zip'=>array('label'=>$langs->trans("Zip"), 'checked'=>0),
	'd.town'=>array('label'=>$langs->trans("Town"), 'checked'=>0),
	'd.phone'=>array('label'=>$langs->trans("Phone"), 'checked'=>0),
	'd.phone_perso'=>array('label'=>$langs->trans("PhonePerso"), 'checked'=>0),
	'd.phone_mobile'=>array('label'=>$langs->trans("PhoneMobile"), 'checked'=>0),
	'state.nom'=>array('label'=>$langs->trans("State"), 'checked'=>0),
	'country.code_iso'=>array('label'=>$langs->trans("Country"), 'checked'=>0),
	/*'d.note_public'=>array('label'=>$langs->trans("NotePublic"), 'checked'=>0),
	'd.note_private'=>array('label'=>$langs->trans("NotePrivate"), 'checked'=>0),*/
	'd.datefin'=>array('label'=>$langs->trans("EndSubscription"), 'checked'=>1, 'position'=>500),
	'd.datec'=>array('label'=>$langs->trans("DateCreation"), 'checked'=>0, 'position'=>500),
	'd.tms'=>array('label'=>$langs->trans("DateModificationShort"), 'checked'=>0, 'position'=>500),
	'd.statut'=>array('label'=>$langs->trans("Status"), 'checked'=>1, 'position'=>1000)
);
// Extra fields
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
{
	foreach($extrafields->attribute_label as $key => $val)
	{
		if (! empty($extrafields->attribute_list[$key])) $arrayfields["ef.".$key]=array('label'=>$extrafields->attribute_label[$key], 'checked'=>(($extrafields->attribute_list[$key]<0)?0:1), 'position'=>$extrafields->attribute_pos[$key], 'enabled'=>(abs($extrafields->attribute_list[$key])!=3 && $extrafields->attribute_perms[$key]));
	}
}


/*
 * Actions
 */

if (GETPOST('cancel','alpha')) { $action='list'; $massaction=''; }
if (! GETPOST('confirmmassaction','alpha') && $massaction != 'presend' && $massaction != 'confirm_presend' && $massaction != 'confirm_createbills') { $massaction=''; }

$parameters=array('socid'=>$socid);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Purge search criteria
	if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')) // All tests are required to be compatible with all browsers
	{
		$search="";
		$search_ref="";
		$search_lastname="";
		$search_firstname="";
		$search_civility="";
		$search_login="";
		$search_company="";
		$search_type="";
		$search_email="";
		$search_address="";
		$search_zip="";
		$search_town="";
		$search_state="";
		$search_country='';
		$search_phone='';
		$search_phone_perso='';
		$search_phone_mobile='';
		$search_morphy="";
		$search_categ="";
		$catid="";
		$sall="";
		$statut='';
		$toselect='';
		$search_array_options=array();
	}

	// Mass actions
	$objectclass='Adherent';
	$objectlabel='Members';
	$permtoread = $user->rights->adherent->lire;
	$permtodelete = $user->rights->adherent->supprimer;
	$uploaddir = $conf->adherent->dir_output;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}


/*
 * View
 */

$form=new Form($db);
$formother=new FormOther($db);
$membertypestatic=new AdherentType($db);
$memberstatic=new Adherent($db);

$now=dol_now();

$sql = "SELECT d.rowid, d.login, d.lastname, d.firstname, d.societe as company, d.fk_soc,";
$sql.= " d.civility, d.datefin, d.address, d.zip, d.town, d.state_id, d.country,";
$sql.= " d.email, d.phone, d.phone_perso, d.phone_mobile, d.skype, d.birth, d.public, d.photo,";
$sql.= " d.fk_adherent_type as type_id, d.morphy, d.statut, d.datec as date_creation, d.tms as date_update,";
$sql.= " t.libelle as type, t.subscription,";
$sql.= " state.code_departement as state_code, state.nom as state_name";
// Add fields from extrafields
foreach ($extrafields->attribute_label as $key => $val) $sql.=($extrafields->attribute_type[$key] != 'separate' ? ",ef.".$key.' as options_'.$key : '');
// Add fields from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListSelect',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql.= " FROM ".MAIN_DB_PREFIX."adherent as d";
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."adherent_extrafields as ef on (d.rowid = ef.fk_object)";
if (! empty($search_categ) || ! empty($catid)) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_member as cm ON d.rowid = cm.fk_member"; // We need this table joined to the select in order to filter by categ
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as country on (country.rowid = d.country)";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as state on (state.rowid = d.state_id)";
$sql.= ", ".MAIN_DB_PREFIX."adherent_type as t";
$sql.= " WHERE d.fk_adherent_type = t.rowid ";
if ($catid > 0)    $sql.= " AND cm.fk_categorie = ".$db->escape($catid);
if ($catid == -2)  $sql.= " AND cm.fk_categorie IS NULL";
if ($search_categ > 0)   $sql.= " AND cm.fk_categorie = ".$db->escape($search_categ);
if ($search_categ == -2) $sql.= " AND cm.fk_categorie IS NULL";
$sql.= " AND d.entity IN (".getEntity('adherent').")";
if ($sall) $sql.=natural_search(array_keys($fieldstosearchall), $sall);
if ($search_type > 0) $sql.=" AND t.rowid=".$db->escape($search_type);
if ($statut != '') $sql.=" AND d.statut in (".$db->escape($statut).")";     // Peut valoir un nombre ou liste de nombre separes par virgules
if ($search_ref)
{
	if (is_numeric($search_ref)) $sql.= " AND (d.rowid = ".$db->escape($search_ref).")";
	else $sql.=" AND 1 = 2";    // Always wrong
}
if ($search_civility) $sql.= natural_search("d.civility", $search_civility);
if ($search_firstname) $sql.= natural_search("d.firstname", $search_firstname);
if ($search_lastname) $sql.= natural_search(array("d.firstname", "d.lastname", "d.societe"), $search_lastname);
if ($search_login) $sql.= natural_search("d.login", $search_login);
if ($search_email) $sql.= natural_search("d.email", $search_email);
if ($search_town)     $sql.= natural_search("d.town",$search_town);
if ($search_zip)      $sql.= natural_search("d.zip",$search_zip);
if ($search_state)    $sql.= natural_search("state.nom",$search_state);
if ($search_country) $sql .= " AND d.country IN (".$search_country.')';
if ($filter == 'uptodate') $sql.=" AND datefin >= '".$db->idate($now)."'";
if ($filter == 'outofdate') $sql.=" AND (datefin IS NULL OR datefin < '".$db->idate($now)."')";

// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';

// Add where from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListWhere',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;

$sql.= $db->order($sortfield,$sortorder);

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

dol_syslog("get list", LOG_DEBUG);
$resql = $db->query($sql);
if (! $resql)
{
	dol_print_error($db);
	exit;
}

$num = $db->num_rows($resql);

$arrayofselected=is_array($toselect)?$toselect:array();

if ($num == 1 && ! empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && $sall)
{
	$obj = $db->fetch_object($resql);
	$id = $obj->rowid;
	header("Location: ".DOL_URL_ROOT.'/adherents/card.php?id='.$id);
	exit;
}

llxHeader('',$langs->trans("Member"),'EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros');

$titre=$langs->trans("MembersList");
if (GETPOSTISSET("statut"))
{
	if ($statut == '-1,1') { $titre=$langs->trans("MembersListQualified"); }
	if ($statut == '-1')   { $titre=$langs->trans("MembersListToValid"); }
	if ($statut == '1' && ! $filter)    		{ $titre=$langs->trans("MembersListValid"); }
	if ($statut == '1' && $filter=='uptodate')  { $titre=$langs->trans("MembersListUpToDate"); }
	if ($statut == '1' && $filter=='outofdate')	{ $titre=$langs->trans("MembersListNotUpToDate"); }
	if ($statut == '0')    { $titre=$langs->trans("MembersListResiliated"); }
}
elseif ($action == 'search')
{
	$titre=$langs->trans("MembersListQualified");
}

if ($search_type > 0)
{
	$membertype=new AdherentType($db);
	$result=$membertype->fetch(GETPOST("type",'int'));
	$titre.=" (".$membertype->label.")";
}

$param='';
if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.$contextpage;
if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.$limit;
if ($sall != "") $param.="&sall=".urlencode($sall);
if ($statut != "") $param.="&statut=".urlencode($statut);
if ($search_ref)   $param.="&search_ref=".urlencode($search_ref);
if ($search_nom)   $param.="&search_nom=".urlencode($search_nom);
if ($search_civility) $param.="&search_civility=".urlencode($search_civility);
if ($search_firstname) $param.="&search_firstname=".urlencode($search_firstname);
if ($search_lastname)  $param.="&search_lastname=".urlencode($search_lastname);
if ($search_login)   $param.="&search_login=".urlencode($search_login);
if ($search_email)   $param.="&search_email=".urlencode($search_email);
if ($search_company) $param.="&search_company=".urlencode($search_company);
if ($search_address != '') $param.= "&search_address=".urlencode($search_address);
if ($search_town != '') $param.= "&search_town=".urlencode($search_town);
if ($search_zip != '') $param.= "&search_zip=".urlencode($search_zip);
if ($search_state != '') $param.= "&search_state=".urlencode($search_state);
if ($search_country != '') $param.= "&search_country=".urlencode($search_country);
if ($search_phone != '') $param.= "&search_phone=".urlencode($search_phone);
if ($search_phone_perso != '') $param.= "&search_phone_perso=".urlencode($search_phone_perso);
if ($search_phone_mobile != '') $param.= "&search_phone_mobile=".urlencode($search_phone_mobile);
if ($filter)         $param.="&filter=".urlencode($filter);
if ($search_type > 0)       $param.="&search_type=".urlencode($search_type);
if ($optioncss != '')       $param.='&optioncss='.urlencode($optioncss);
// Add $param from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

// List of mass actions available
$arrayofmassactions =  array(
	//'presend'=>$langs->trans("SendByMail"),
	//'builddoc'=>$langs->trans("PDFMerge"),
);
if ($user->rights->adherent->supprimer) $arrayofmassactions['predelete']=$langs->trans("Delete");
if (in_array($massaction, array('presend','predelete'))) $arrayofmassactions=array();
$massactionbutton=$form->selectMassAction('', $arrayofmassactions);

$newcardbutton='';
if ($user->rights->adherent->creer)
{
	$newcardbutton='<a class="butActionNew" href="'.DOL_URL_ROOT.'/adherents/card.php?action=create"><span class="valignmiddle">'.$langs->trans('NewMember').'</span>';
	$newcardbutton.= '<span class="fa fa-plus-circle valignmiddle"></span>';
	$newcardbutton.= '</a>';
}

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

print_barre_liste($titre, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'title_generic.png', 0, $newcardbutton, '', $limit);

$topicmail="Information";
$modelmail="member";
$objecttmp=new Adherent($db);
$trackid='mem'.$object->id;
include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

if ($sall)
{
	foreach($fieldstosearchall as $key => $val) $fieldstosearchall[$key]=$langs->trans($val);
	print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $sall) . join(', ',$fieldstosearchall).'</div>';
}

// Filter on categories
$moreforfilter='';
if (! empty($conf->categorie->enabled))
{
	require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
	$moreforfilter.='<div class="divsearchfield">';
	$moreforfilter.=$langs->trans('Categories'). ': ';
	$moreforfilter.=$formother->select_categories(Categorie::TYPE_MEMBER,$search_categ,'search_categ',1);
	$moreforfilter.='</div>';
}
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldPreListTitle',$parameters);    // Note that $action and $object may have been modified by hook
if (empty($reshook)) $moreforfilter .= $hookmanager->resPrint;
else $moreforfilter = $hookmanager->resPrint;
if (! empty($moreforfilter))
{
	print '<div class="liste_titre liste_titre_bydiv centpercent">';
	print $moreforfilter;
	print '</div>';
}

$varpage=empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;
$selectedfields=$form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);	// This also change content of $arrayfields
if ($massactionbutton) $selectedfields.=$form->showCheckAddButtons('checkforselect', 1);

print '<div class="div-table-responsive">';
print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";


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
	print '<td class="liste_titre">';
	print '<input class="flat maxwidth50" type="text" name="search_ref" value="'.dol_escape_htmltag($search_ref).'">';
	print '</td>';
}
if (! empty($arrayfields['d.civility']['checked']))
{
	print '<td class="liste_titre" align="left">';
	print '<input class="flat maxwidth25" type="text" name="search_civility" value="'.dol_escape_htmltag($search_civility).'"></td>';
}
if (! empty($arrayfields['d.firstname']['checked']))
{
	print '<td class="liste_titre" align="left">';
	print '<input class="flat maxwidth50" type="text" name="search_firstname" value="'.dol_escape_htmltag($search_firstname).'"></td>';
}
if (! empty($arrayfields['d.lastname']['checked']))
{
	print '<td class="liste_titre" align="left">';
	print '<input class="flat maxwidth50" type="text" name="search_lastname" value="'.dol_escape_htmltag($search_lastname).'"></td>';
}
if (! empty($arrayfields['d.company']['checked']))
{
	print '<td class="liste_titre" align="left">';
	print '<input class="flat maxwidth50" type="text" name="search_company" value="'.dol_escape_htmltag($search_company).'"></td>';
}
if (! empty($arrayfields['d.login']['checked']))
{
	print '<td class="liste_titre" align="left">';
	print '<input class="flat maxwidth50" type="text" name="search_login" value="'.dol_escape_htmltag($search_login).'"></td>';
}
if (! empty($arrayfields['d.morphy']['checked']))
{
	print '<td class="liste_titre" align="left">';
	print '</td>';
}
if (! empty($arrayfields['t.libelle']['checked']))
{
	print '<td class="liste_titre">';
	$listetype=$membertypestatic->liste_array();
	print $form->selectarray("search_type", $listetype, $type, 1, 0, 0, '', 0, 32);
	print '</td>';
}

if (! empty($arrayfields['d.address']['checked']))
{
	print '<td class="liste_titre" align="left">';
	print '<input class="flat maxwidth50" type="text" name="search_address" value="'.$search_address.'"></td>';
}

if (! empty($arrayfields['d.zip']['checked']))
{
	print '<td class="liste_titre" align="left">';
	print '<input class="flat maxwidth50" type="text" name="search_zip" value="'.$search_zip.'"></td>';
}
if (! empty($arrayfields['d.town']['checked']))
{
	print '<td class="liste_titre" align="left">';
	print '<input class="flat maxwidth50" type="text" name="search_town" value="'.$search_town.'"></td>';
}
// State
if (! empty($arrayfields['state.nom']['checked']))
{
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth50" type="text" name="search_state" value="'.dol_escape_htmltag($search_state).'">';
	print '</td>';
}
// Country
if (! empty($arrayfields['country.code_iso']['checked']))
{
	print '<td class="liste_titre" align="center">';
	print $form->select_country($search_country,'search_country','',0,'maxwidth100');
	print '</td>';
}
// Phone pro
if (! empty($arrayfields['d.phone']['checked']))
{
	print '<td class="liste_titre" align="left">';
	print '<input class="flat maxwidth50" type="text" name="search_phone" value="'.$search_phone.'"></td>';
}
// Phone perso
if (! empty($arrayfields['d.phone_perso']['checked']))
{
	print '<td class="liste_titre" align="left">';
	print '<input class="flat maxwidth50" type="text" name="search_phone_perso" value="'.$search_phone_perso.'"></td>';
}
// Phone mobile
if (! empty($arrayfields['d.phone_mobile']['checked']))
{
	print '<td class="liste_titre" align="left">';
	print '<input class="flat maxwidth50" type="text" name="search_phone_mobile" value="'.$search_phone_mobile.'"></td>';
}
// Email
if (! empty($arrayfields['d.email']['checked']))
{
	print '<td class="liste_titre" align="left">';
	print '<input class="flat maxwidth50" type="text" name="search_email" value="'.$search_email.'"></td>';
}

if (! empty($arrayfields['d.datefin']['checked']))
{
	print '<td class="liste_titre" align="left">';
	print '</td>';
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

// Fields from hook
$parameters=array('arrayfields'=>$arrayfields);
$reshook=$hookmanager->executeHooks('printFieldListOption',$parameters);    // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
// Date creation
if (! empty($arrayfields['d.datec']['checked']))
{
	print '<td class="liste_titre">';
	print '</td>';
}
// Date modification
if (! empty($arrayfields['d.tms']['checked']))
{
	print '<td class="liste_titre">';
	print '</td>';
}
// Status
if (! empty($arrayfields['d.statut']['checked']))
{
	print '<td class="liste_titre maxwidthonsmartphone" align="right">';
	$liststatus=array(
		'-1'=>$langs->trans("Draft"),
		'1'=>$langs->trans("Validated"),
		'0'=>$langs->trans("Resiliated")
	);
	print $form->selectarray('statut', $liststatus, $statut, -2);
	print '</td>';
}
// Action column
print '<td class="liste_titre" align="middle">';
$searchpicto=$form->showFilterButtons();
print $searchpicto;
print '</td>';

print "</tr>\n";

print '<tr class="liste_titre">';
if (! empty($conf->global->MAIN_SHOW_TECHNICAL_ID))       print_liste_field_titre("ID",$_SERVER["PHP_SELF"],'','',$param,'align="center"',$sortfield,$sortorder);
if (! empty($arrayfields['d.ref']['checked']))            print_liste_field_titre($arrayfields['d.ref']['label'],$_SERVER["PHP_SELF"],'d.rowid','',$param,'',$sortfield,$sortorder);
if (! empty($arrayfields['d.civility']['checked']))       print_liste_field_titre($arrayfields['d.civility']['label'],$_SERVER["PHP_SELF"],'d.civility','',$param,'',$sortfield,$sortorder);
if (! empty($arrayfields['d.firstname']['checked']))      print_liste_field_titre($arrayfields['d.firstname']['label'],$_SERVER["PHP_SELF"],'d.firstname','',$param,'',$sortfield,$sortorder);
if (! empty($arrayfields['d.lastname']['checked']))       print_liste_field_titre($arrayfields['d.lastname']['label'],$_SERVER["PHP_SELF"],'d.lastname','',$param,'',$sortfield,$sortorder);
if (! empty($arrayfields['d.company']['checked']))        print_liste_field_titre($arrayfields['d.company']['label'],$_SERVER["PHP_SELF"],'d.societe','',$param,'',$sortfield,$sortorder);
if (! empty($arrayfields['d.login']['checked']))          print_liste_field_titre($arrayfields['d.login']['label'],$_SERVER["PHP_SELF"],'d.login','',$param,'',$sortfield,$sortorder);
if (! empty($arrayfields['d.morphy']['checked']))         print_liste_field_titre($arrayfields['d.morphy']['label'],$_SERVER["PHP_SELF"],'d.morphy','',$param,'',$sortfield,$sortorder);
if (! empty($arrayfields['t.libelle']['checked']))        print_liste_field_titre($arrayfields['t.libelle']['label'],$_SERVER["PHP_SELF"],'t.libelle','',$param,'',$sortfield,$sortorder);
if (! empty($arrayfields['d.address']['checked']))        print_liste_field_titre($arrayfields['d.address']['label'],$_SERVER["PHP_SELF"],'d.address','',$param,'',$sortfield,$sortorder);
if (! empty($arrayfields['d.zip']['checked']))            print_liste_field_titre($arrayfields['d.zip']['label'],$_SERVER["PHP_SELF"],'d.zip','',$param,'',$sortfield,$sortorder);
if (! empty($arrayfields['d.town']['checked']))           print_liste_field_titre($arrayfields['d.town']['label'],$_SERVER["PHP_SELF"],'d.town','',$param,'',$sortfield,$sortorder);
if (! empty($arrayfields['state.nom']['checked']))        print_liste_field_titre($arrayfields['state.nom']['label'],$_SERVER["PHP_SELF"],"state.nom","",$param,'',$sortfield,$sortorder);
if (! empty($arrayfields['country.code_iso']['checked'])) print_liste_field_titre($arrayfields['country.code_iso']['label'],$_SERVER["PHP_SELF"],"country.code_iso","",$param,'align="center"',$sortfield,$sortorder);
if (! empty($arrayfields['d.phone']['checked']))          print_liste_field_titre($arrayfields['d.phone']['label'],$_SERVER["PHP_SELF"],'d.phone','',$param,'',$sortfield,$sortorder);
if (! empty($arrayfields['d.phone_perso']['checked']))    print_liste_field_titre($arrayfields['d.phone_perso']['label'],$_SERVER["PHP_SELF"],'d.phone_perso','',$param,'',$sortfield,$sortorder);
if (! empty($arrayfields['d.phone_mobile']['checked']))   print_liste_field_titre($arrayfields['d.phone_mobile']['label'],$_SERVER["PHP_SELF"],'d.phone_mobile','',$param,'',$sortfield,$sortorder);
if (! empty($arrayfields['d.email']['checked']))          print_liste_field_titre($arrayfields['d.email']['label'],$_SERVER["PHP_SELF"],'d.email','',$param,'',$sortfield,$sortorder);
if (! empty($arrayfields['d.datefin']['checked']))        print_liste_field_titre($arrayfields['d.datefin']['label'],$_SERVER["PHP_SELF"],'d.datefin','',$param,'align="center"',$sortfield,$sortorder);
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';

// Hook fields
$parameters=array('arrayfields'=>$arrayfields,'param'=>$param,'sortfield'=>$sortfield,'sortorder'=>$sortorder);
$reshook=$hookmanager->executeHooks('printFieldListTitle',$parameters);    // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
if (! empty($arrayfields['d.datec']['checked']))     print_liste_field_titre($arrayfields['d.datec']['label'],$_SERVER["PHP_SELF"],"d.datec","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
if (! empty($arrayfields['d.tms']['checked']))       print_liste_field_titre($arrayfields['d.tms']['label'],$_SERVER["PHP_SELF"],"d.tms","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
if (! empty($arrayfields['d.statut']['checked']))    print_liste_field_titre($arrayfields['d.statut']['label'],$_SERVER["PHP_SELF"],"d.statut","",$param,'align="right"',$sortfield,$sortorder);
print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"],"",'','','align="center"',$sortfield,$sortorder,'maxwidthsearch ');
print "</tr>\n";

$i = 0;
$totalarray=array();
while ($i < min($num, $limit))
{
	$obj = $db->fetch_object($resql);

	$datefin=$db->jdate($obj->datefin);
	$memberstatic->id=$obj->rowid;
	$memberstatic->ref=$obj->rowid;
	$memberstatic->civility_id=$obj->civility;
	$memberstatic->lastname=$obj->lastname;
	$memberstatic->firstname=$obj->firstname;
	$memberstatic->statut=$obj->statut;
	$memberstatic->datefin= $datefin;
	$memberstatic->socid = $obj->fk_soc;
	$memberstatic->photo = $obj->photo;

	if (! empty($obj->fk_soc)) {
		$memberstatic->fetch_thirdparty();
		$companyname=$memberstatic->thirdparty->name;
	} else {
		$companyname=$obj->company;
	}
	$memberstatic->societe = $companyname;

	print '<tr class="oddeven">';

	if (! empty($conf->global->MAIN_SHOW_TECHNICAL_ID))
	{
		print '<td align="center">'.$obj->rowid.'</td>';
		if (! $i) $totalarray['nbfield']++;
	}

	// Ref
	if (! empty($arrayfields['d.ref']['checked']))
	{
		print "<td>";
		print $memberstatic->getNomUrl(-1, 0, 'card', 'ref');
		print "</td>\n";
		if (! $i) $totalarray['nbfield']++;
	}
	// Civility
	if (! empty($arrayfields['d.civility']['checked']))
	{
		print "<td>";
		print $obj->civility;
		print "</td>\n";
		if (! $i) $totalarray['nbfield']++;
	}
	// Firstname
	if (! empty($arrayfields['d.firstname']['checked']))
	{
		print "<td>";
		print $obj->firstname;
		print "</td>\n";
		if (! $i) $totalarray['nbfield']++;
	}
	// Lastname
	if (! empty($arrayfields['d.lastname']['checked']))
	{
		print "<td>";
		print $obj->lastname;
		print "</td>\n";
		if (! $i) $totalarray['nbfield']++;
	}
	// Company
	if (! empty($arrayfields['d.company']['checked']))
	{
		print "<td>";
		print $companyname;
		print "</td>\n";
	}
	// Login
	if (! empty($arrayfields['d.login']['checked']))
	{
		print "<td>".$obj->login."</td>\n";
		if (! $i) $totalarray['nbfield']++;
	}
	// Moral/Physique
	if (! empty($arrayfields['d.morphy']['checked']))
	{
		print "<td>".$memberstatic->getmorphylib($obj->morphy)."</td>\n";
		if (! $i) $totalarray['nbfield']++;
	}
	// Type label
	if (! empty($arrayfields['t.libelle']['checked']))
	{
		$membertypestatic->id=$obj->type_id;
		$membertypestatic->label=$obj->type;
		print '<td class="nowrap">';
		print $membertypestatic->getNomUrl(1,32);
		print '</td>';
		if (! $i) $totalarray['nbfield']++;
	}
	// Address
	if (! empty($arrayfields['d.address']['checked']))
	{
		print '<td class="nocellnopadd">';
		print $obj->address;
		print '</td>';
		if (! $i) $totalarray['nbfield']++;
	}
	// Zip
	if (! empty($arrayfields['d.zip']['checked']))
	{
		print '<td class="nocellnopadd">';
		print $obj->zip;
		print '</td>';
		if (! $i) $totalarray['nbfield']++;
	}
	// Town
	if (! empty($arrayfields['d.town']['checked']))
	{
		print '<td class="nocellnopadd">';
		print $obj->town;
		print '</td>';
		if (! $i) $totalarray['nbfield']++;
	}
	// State
	if (! empty($arrayfields['state.nom']['checked']))
	{
		print "<td>".$obj->state_name."</td>\n";
		if (! $i) $totalarray['nbfield']++;
	}
	// Country
	if (! empty($arrayfields['country.code_iso']['checked']))
	{
		print '<td align="center">';
		$tmparray=getCountry($obj->country,'all');
		print $tmparray['label'];
		print '</td>';
		if (! $i) $totalarray['nbfield']++;
	}
	// Phone pro
	if (! empty($arrayfields['d.phone']['checked']))
	{
		print '<td class="nocellnopadd">';
		print $obj->phone;
		print '</td>';
		if (! $i) $totalarray['nbfield']++;
	}
	// Phone perso
	if (! empty($arrayfields['d.phone_perso']['checked']))
	{
		print '<td class="nocellnopadd">';
		print $obj->phone_perso;
		print '</td>';
		if (! $i) $totalarray['nbfield']++;
	}
	// Phone mobile
	if (! empty($arrayfields['d.phone_mobile']['checked']))
	{
		print '<td class="nocellnopadd">';
		print $obj->phone_mobile;
		print '</td>';
		if (! $i) $totalarray['nbfield']++;
	}
	// EMail
	if (! empty($arrayfields['d.email']['checked']))
	{
		print "<td>".dol_print_email($obj->email,0,0,1)."</td>\n";
	}
	// End of subscription date
	$datefin=$db->jdate($obj->datefin);
	if (! empty($arrayfields['d.datefin']['checked']))
	{
		if ($datefin)
		{
			print '<td align="center" class="nowrap">';
			print dol_print_date($datefin,'day');
			if ($memberstatic->hasDelay()) {
				$textlate .= ' ('.$langs->trans("DateReference").' > '.$langs->trans("DateToday").' '.(ceil($conf->adherent->subscription->warning_delay/60/60/24) >= 0 ? '+' : '').ceil($conf->adherent->subscription->warning_delay/60/60/24).' '.$langs->trans("days").')';
				print " ".img_warning($langs->trans("SubscriptionLate").$textlate);
			}
			print '</td>';
		}
		else
		{
			print '<td align="left" class="nowrap">';
			if ($obj->subscription == 'yes')
			{
				print $langs->trans("SubscriptionNotReceived");
				if ($obj->statut > 0) print " ".img_warning();
			}
			else
			{
				print '&nbsp;';
			}
			print '</td>';
		}
	}
	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
	// Fields from hook
	$parameters=array('arrayfields'=>$arrayfields, 'obj'=>$obj);
	$reshook=$hookmanager->executeHooks('printFieldListValue',$parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	// Date creation
	if (! empty($arrayfields['d.datec']['checked']))
	{
		print '<td align="center" class="nowrap">';
		print dol_print_date($db->jdate($obj->date_creation), 'dayhour', 'tzuser');
		print '</td>';
		if (! $i) $totalarray['nbfield']++;
	}
	// Date modification
	if (! empty($arrayfields['d.tms']['checked']))
	{
		print '<td align="center" class="nowrap">';
		print dol_print_date($db->jdate($obj->date_update), 'dayhour', 'tzuser');
		print '</td>';
		if (! $i) $totalarray['nbfield']++;
	}
	// Status
	if (! empty($arrayfields['d.statut']['checked']))
	{
		print '<td align="right" class="nowrap">';
		print $memberstatic->LibStatut($obj->statut,$obj->subscription,$datefin,5);
		print '</td>';
		if (! $i) $totalarray['nbfield']++;
	}
	// Action column
	print '<td align="center">';
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
		if (! empty($totalarray['pos'][$i]))  print '<td align="right">'.price($totalarray['val'][$totalarray['pos'][$i]]).'</td>';
		else
		{
			if ($i == 1)
			{
				if ($num < $limit) print '<td align="left">'.$langs->trans("Total").'</td>';
				else print '<td align="left">'.$langs->trans("Totalforthispage").'</td>';
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
$reshook=$hookmanager->executeHooks('printFieldListFooter',$parameters);    // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print "</table>\n";
print "</div>";
print '</form>';

if ($num > $limit || $page) print_barre_liste('', $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'title_generic.png', 0, '', '', $limit, 1);

// End of page
llxFooter();
$db->close();
