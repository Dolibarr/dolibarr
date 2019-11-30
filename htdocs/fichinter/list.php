<?php
/* Copyright (C) 2002-2003	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2014	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2012	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2013		Cédric Salvador			<csalvador@gpcsolutions.fr>
 * Copyright (C) 2015       Jean-François Ferry		<jfefe@aternatik.fr>
 * Copyright (C) 2018-2019	Ferran Marcet			<fmarcet@2byte.es>
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
 *	\file       htdocs/fichinter/list.php
 *	\brief      List of all interventions
 *	\ingroup    ficheinter
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
if ($conf->projet->enabled) {
	require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
}
if ($conf->contrat->enabled) {
	require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
}



// Load translation files required by the page
$langs->loadLangs(array('companies', 'bills', 'interventions', 'exports'));

$action=GETPOST('action', 'alpha');
$massaction=GETPOST('massaction', 'alpha');
$show_files=GETPOST('show_files', 'int');
$confirm=GETPOST('confirm', 'alpha');
$toselect = GETPOST('toselect', 'array');
$contextpage=GETPOST('contextpage', 'aZ')?GETPOST('contextpage', 'aZ'):'interventionlist';

//$search_ref=GETPOST('search_ref')?GETPOST('search_ref','alpha'):GETPOST('search_inter','alpha');
//$search_company=GETPOST('search_company','alpha');
//$search_desc=GETPOST('search_desc','alpha');
//$search_status=GETPOST('search_status');
$sall=trim((GETPOST('search_all', 'alphanohtml')!='')?GETPOST('search_all', 'alphanohtml'):GETPOST('sall', 'alphanohtml'));
//$optioncss = GETPOST('optioncss','alpha');
$socid=GETPOST('socid','int');

// Security check
$id = GETPOST('id','int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'ficheinter', $id,'fichinter');

$diroutputmassaction=$conf->ficheinter->dir_output . '/temp/massgeneration/'.$user->id;

$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
$page = GETPOST('page','int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="DESC";
if (! $sortfield)
{
 	if (empty($conf->global->FICHINTER_DISABLE_DETAILS)) $sortfield="fd.date";
 	else $sortfield="f.ref";
}

$sall=trim((GETPOST('search_all', 'alphanohtml')!='')?GETPOST('search_all', 'alphanohtml'):GETPOST('sall', 'alphanohtml'));
$search_ref=GETPOST('search_ref')?GETPOST('search_ref','alpha'):GETPOST('search_inter','alpha');
$search_company=GETPOST('search_company','alpha');
$search_desc=GETPOST('search_desc','alpha');
$search_status=GETPOST('search_status');
$optioncss = GETPOST('optioncss','alpha');
$search_project=GETPOST('search_project','alpha');
$search_contract=GETPOST('search_contract','alpha');

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$object = new Fichinter($db);
$hookmanager->initHooks(array('interventionlist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('fichinter');
$search_array_options=$extrafields->getOptionalsFromPost($extralabels,'','search_');

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	'f.ref'=>'Ref',
	's.nom'=>"ThirdParty",
	'f.description'=>'Description',
	'f.note_public'=>'NotePublic',
);
if (empty($user->socid)) $fieldstosearchall["f.note_private"]="NotePrivate";
if (! empty($conf->global->FICHINTER_DISABLE_DETAILS)) unset($fieldstosearchall['f.description']);

// Definition of fields for list
$arrayfields=array(
	'f.ref'=>array('label'=>$langs->trans("Ref"), 'checked'=>1),
	's.nom'=>array('label'=>$langs->trans("ThirdParty"), 'checked'=>1),
	'f.fk_projet'=>array('label'=>$langs->trans("Project"), 'checked'=>1),
	'f.fk_contrat'=>array('label'=>$langs->trans("Contract"), 'checked'=>1),
	'f.description'=>array('label'=>$langs->trans("Description"), 'checked'=>1),
	'fd.description'=>array('label'=>$langs->trans("LineDescription"), 'checked'=>1, 'enabled'=>empty($conf->global->FICHINTER_DISABLE_DETAILS)?1:0),
	'fd.date'=>array('label'=>$langs->trans("Date"), 'checked'=>1, 'enabled'=>empty($conf->global->FICHINTER_DISABLE_DETAILS)?1:0),
	'fd.duree'=>array('label'=>$langs->trans("Duration"), 'checked'=>1, 'enabled'=>empty($conf->global->FICHINTER_DISABLE_DETAILS)?1:0),
	'f.datec'=>array('label'=>$langs->trans("DateCreation"), 'checked'=>0, 'position'=>500),
	'f.tms'=>array('label'=>$langs->trans("DateModificationShort"), 'checked'=>0, 'position'=>500),
	'f.fk_statut'=>array('label'=>$langs->trans("Status"), 'checked'=>1, 'position'=>1000),
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
		$search_ref="";
		$search_company="";
		$search_project="";
		$search_contract="";
		$search_desc="";
		$search_status="";
		$toselect='';
		$search_array_options=array();
	}

	// Mass actions
	$objectclass='Fichinter';
	$objectlabel='Interventions';
	$permtoread = $user->rights->ficheinter->lire;
	$permtodelete = $user->rights->ficheinter->supprimer;
	$uploaddir = $conf->ficheinter->dir_output;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}



/*
 *	View
 */

$now=dol_now();

$form = new Form($db);
$formfile = new FormFile($db);
$objectstatic=new Fichinter($db);
$companystatic=new Societe($db);
if ($conf->projet->enabled) {
	$projectstatic = new Project($db);
}
if ($conf->contrat->enabled) {
	$contratstatic = new Contrat($db);
}

$title=$langs->trans("ListOfInterventions");
llxHeader('', $title);


$sql = "SELECT";
$sql.= " f.ref, f.rowid, f.fk_statut, f.description, f.datec as date_creation, f.tms as date_update, f.note_private,";
if (empty($conf->global->FICHINTER_DISABLE_DETAILS)) $sql.= " fd.description as descriptiondetail, fd.date as dp, fd.duree,";
$sql.= " s.nom as name, s.rowid as socid, s.client";
if (! empty($arrayfields['f.fk_projet']['checked'])){
	$sql.= " ,p.rowid as projectid, p.ref as projectref, p.title as projecttitle";
}
if (! empty($arrayfields['f.fk_contrat']['checked'])){
	$sql.= " ,c.rowid as contractid, c.ref as contractref";
}
// Add fields from extrafields
foreach ($extrafields->attribute_label as $key => $val) $sql.=($extrafields->attribute_type[$key] != 'separate' ? ",ef.".$key.' as options_'.$key : '');
// Add fields from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListSelect',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql.= " FROM ".MAIN_DB_PREFIX."fichinter as f";
if (! empty($arrayfields['f.fk_projet']['checked'])){
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON f.fk_projet = p.rowid";
}
if (! empty($arrayfields['f.fk_contrat']['checked'])){
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."contrat as c ON f.fk_contrat = c.rowid";
}
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."fichinter_extrafields as ef on (f.rowid = ef.fk_object)";
if (empty($conf->global->FICHINTER_DISABLE_DETAILS)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."fichinterdet as fd ON fd.fk_fichinter = f.rowid";
if (! $user->rights->societe->client->voir && empty($socid)) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= ", ".MAIN_DB_PREFIX."societe as s";
$sql.= " WHERE f.entity IN (".getEntity('intervention').")";
$sql.= " AND f.fk_soc = s.rowid";
if ($search_ref) {
	$sql .= natural_search('f.ref', $search_ref);
}
if ($search_company) {
	$sql .= natural_search('s.nom', $search_company);
}
if ($search_desc) {
	if (empty($conf->global->FICHINTER_DISABLE_DETAILS)) $sql .= natural_search(array('f.description', 'fd.description'), $search_desc);
	else $sql .= natural_search(array('f.description'), $search_desc);
}
if ($search_status != '' && $search_status >= 0) {
	$sql .= ' AND f.fk_statut = '.$search_status;
}
if (! $user->rights->societe->client->voir && empty($socid))
	$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($socid)
	$sql.= " AND s.rowid = " . $socid;
if ($search_project) {
	$sql .= natural_search('p.ref', $search_project);
}
if ($search_contract) {
	$sql .= natural_search('c.ref', $search_contract);
}
if ($sall) {
	$sql .= natural_search(array_keys($fieldstosearchall), $sall);
}
// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListWhere',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql.= $db->order($sortfield,$sortorder);

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
	if (($page * $limit) > $nbtotalofrecords)	// if total resultset is smaller then paging size (filtering), goto and load page 0
	{
		$page = 0;
		$offset = 0;
	}
}

$sql.= $db->plimit($limit+1, $offset);
//print $sql;

$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);

	$arrayofselected=is_array($toselect)?$toselect:array();

	if ($socid > 0)
	{
		$soc = new Societe($db);
		$soc->fetch($socid);
		if (empty($search_company)) $search_company = $soc->name;
	}

	$param='';
	if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.$contextpage;
	if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.$limit;
	if ($sall) $urlparam.="&sall=".$sall;
	if ($socid) $param.="&socid=".$socid;
	if ($search_ref) $param.="&search_ref=".urlencode($search_ref);
	if ($search_company) $param.="&search_company=".urlencode($search_company);
	if ($search_desc) $param.="&search_desc=".urlencode($search_desc);
	if ($search_status != '' && $search_status > -1) $param.="&search_status=".urlencode($search_status);
	if ($search_project) $param.="&search_project=".urlencode($search_project);
	if ($search_contract) $param.="&search_contract=".urlencode($search_contract);
	if ($show_files)            $param.='&show_files=' .$show_files;
	if ($optioncss != '') $param.='&optioncss='.$optioncss;
	// Add $param from extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

	// List of mass actions available
	$arrayofmassactions =  array(
		//'presend'=>$langs->trans("SendByMail"),
		'builddoc'=>$langs->trans("PDFMerge"),
	);
	if ($user->rights->ficheinter->supprimer) $arrayofmassactions['predelete']=$langs->trans("Delete");
	if (in_array($massaction, array('presend','predelete'))) $arrayofmassactions=array();
	$massactionbutton=$form->selectMassAction('', $arrayofmassactions);

	$newcardbutton='';
	if ($user->rights->ficheinter->creer)
	{
		$newcardbutton='<a class="butActionNew" href="'.DOL_URL_ROOT.'/fichinter/card.php?action=create"><span class="valignmiddle">'.$langs->trans('NewIntervention').'</span>';
		$newcardbutton.= '<span class="fa fa-plus-circle valignmiddle"></span>';
		$newcardbutton.= '</a>';
	}

	// Lines of title fields
	print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">'."\n";
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="page" value="'.$page.'">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

	print_barre_liste($title, $page, $_SERVER['PHP_SELF'], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'title_commercial.png', 0, $newcardbutton, '', $limit);

	$topicmail="Information";
	$modelmail="intervention";
	$objecttmp=new Fichinter($db);
	$trackid='int'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

	if ($sall)
	{
		foreach($fieldstosearchall as $key => $val) $fieldstosearchall[$key]=$langs->trans($val);
		print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $sall) . join(', ',$fieldstosearchall).'</div>';
	}

	$moreforfilter='';

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

	print '<tr class="liste_titre_filter">';
	if (! empty($arrayfields['f.ref']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input type="text" class="flat" name="search_ref" value="'.$search_ref.'" size="8">';
		print '</td>';
	}
	if (! empty($arrayfields['s.nom']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input type="text" class="flat" name="search_company" value="'.$search_company.'" size="10">';
		print '</td>';
	}
	if (! empty($arrayfields['f.fk_projet']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input type="text" class="flat" name="search_project" value="'.$search_project.'" size="10">';
		print '</td>';
	}
	if (! empty($arrayfields['f.fk_contrat']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input type="text" class="flat" name="search_contract" value="'.$search_contract.'" size="10">';
		print '</td>';
	}
	if (! empty($arrayfields['f.description']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input type="text" class="flat" name="search_desc" value="'.$search_desc.'" size="12">';
		print '</td>';
	}
	if (! empty($arrayfields['fd.description']['checked']))
	{
		// Desc of line
		print '<td class="liste_titre">&nbsp;</td>';
	}
	if (! empty($arrayfields['fd.date']['checked']))
	{
		print '<td class="liste_titre">&nbsp;</td>';
	}
	if (! empty($arrayfields['fd.duree']['checked']))
	{
		print '<td class="liste_titre">&nbsp;</td>';
	}
	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

	// Fields from hook
	$parameters=array('arrayfields'=>$arrayfields);
	$reshook=$hookmanager->executeHooks('printFieldListOption',$parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	if (! empty($arrayfields['f.datec']['checked']))
	{
		// Date creation
		print '<td class="liste_titre">';
		print '</td>';
	}
	if (! empty($arrayfields['f.tms']['checked']))
	{
		// Date modification
		print '<td class="liste_titre">';
		print '</td>';
	}
	// Status
	if (! empty($arrayfields['f.fk_statut']['checked']))
	{
		print '<td class="liste_titre" align="right">';
		$tmp = $objectstatic->LibStatut(0);		// To load $this->statuts_short
		$liststatus=$objectstatic->statuts_short;
		if (empty($conf->global->FICHINTER_CLASSIFY_BILLED)) unset($liststatus[2]);   // Option deprecated. In a future, billed must be managed with a dedicated field to 0 or 1
		print $form->selectarray('search_status', $liststatus, $search_status, 1, 0, 0, '', 1);
		print '</td>';
	}
	print '<td class="liste_titre" align="right">';
	$searchpicto=$form->showFilterButtons();
	print $searchpicto;
	print '</td>';

	print "</tr>\n";

	print '<tr class="liste_titre">';
	if (! empty($arrayfields['f.ref']['checked']))          print_liste_field_titre($arrayfields['f.ref']['label'],$_SERVER["PHP_SELF"],"f.ref","",$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['s.nom']['checked']))          print_liste_field_titre($arrayfields['s.nom']['label'],$_SERVER["PHP_SELF"],"s.nom","",$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['f.fk_projet']['checked']))   print_liste_field_titre($arrayfields['f.fk_projet']['label'],$_SERVER["PHP_SELF"],"p.ref","",$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['f.fk_contrat']['checked']))   print_liste_field_titre($arrayfields['f.fk_contrat']['label'],$_SERVER["PHP_SELF"],"p.ref","",$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['f.description']['checked']))  print_liste_field_titre($arrayfields['f.description']['label'],$_SERVER["PHP_SELF"],"f.description","",$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['fd.description']['checked'])) print_liste_field_titre($arrayfields['fd.description']['label'],$_SERVER["PHP_SELF"],'');
	if (! empty($arrayfields['fd.date']['checked']))        print_liste_field_titre($arrayfields['fd.date']['label'],$_SERVER["PHP_SELF"],"fd.date","",$param,'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['fd.duree']['checked']))       print_liste_field_titre($arrayfields['fd.duree']['label'],$_SERVER["PHP_SELF"],"fd.duree","",$param,'align="right"',$sortfield,$sortorder);
	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
	// Hook fields
	$parameters=array('arrayfields'=>$arrayfields,'param'=>$param,'sortfield'=>$sortfield,'sortorder'=>$sortorder);
	$reshook=$hookmanager->executeHooks('printFieldListTitle',$parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	if (! empty($arrayfields['f.datec']['checked']))     print_liste_field_titre($arrayfields['f.datec']['label'],$_SERVER["PHP_SELF"],"f.datec","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
	if (! empty($arrayfields['f.tms']['checked']))       print_liste_field_titre($arrayfields['f.tms']['label'],$_SERVER["PHP_SELF"],"f.tms","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
	if (! empty($arrayfields['f.fk_statut']['checked'])) print_liste_field_titre($arrayfields['f.fk_statut']['label'],$_SERVER["PHP_SELF"],"f.fk_statut","",$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"],"",'','','align="center"',$sortfield,$sortorder,'maxwidthsearch ');
	print "</tr>\n";

	$total = 0;
	$i = 0;
	$totalarray=array();
	while ($i < min($num, $limit))
	{
		$obj = $db->fetch_object($resql);

		$objectstatic->id=$obj->rowid;
		$objectstatic->ref=$obj->ref;
		$objectstatic->statut=$obj->fk_statut;

		print '<tr class="oddeven">';

		if (! empty($arrayfields['f.ref']['checked']))
		{
			print "<td>";

			print '<table class="nobordernopadding"><tr class="nocellnopadd">';
			// Picto + Ref
			print '<td class="nobordernopadding nowrap">';
			print $objectstatic->getNomUrl(1);
			print '</td>';
			// Warning
			$warnornote='';
			//if ($obj->fk_statut == 1 && $db->jdate($obj->dfv) < ($now - $conf->fichinter->warning_delay)) $warnornote.=img_warning($langs->trans("Late"));
			if (! empty($obj->note_private))
			{
				$warnornote.=($warnornote?' ':'');
				$warnornote.= '<span class="note">';
				$warnornote.= '<a href="note.php?id='.$obj->rowid.'">'.img_picto($langs->trans("ViewPrivateNote"),'object_generic').'</a>';
				$warnornote.= '</span>';
			}
			if ($warnornote)
			{
				print '<td style="min-width: 20px" class="nobordernopadding nowrap">';
				print $warnornote;
				print '</td>';
			}

			// Other picto tool
			print '<td width="16" align="right" class="nobordernopadding hideonsmartphone">';
			$filename=dol_sanitizeFileName($obj->ref);
			$filedir=$conf->ficheinter->dir_output . '/' . dol_sanitizeFileName($obj->ref);
			$urlsource=$_SERVER['PHP_SELF'].'?id='.$obj->rowid;
			print $formfile->getDocumentsLink($objectstatic->element, $filename, $filedir);
			print '</td></tr></table>';

			print "</td>\n";
			if (! $i) $totalarray['nbfield']++;
		}
		if (! empty($arrayfields['s.nom']['checked']))
		{
			print '<td>';
			$companystatic->name=$obj->name;
			$companystatic->id=$obj->socid;
			$companystatic->client=$obj->client;
			print $companystatic->getNomUrl(1,'',44);
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}
		if (! empty($arrayfields['f.fk_projet']['checked']))
		{
			print '<td>';
			if ($obj->projectid > 0) {
				$projectstatic->ref = $obj->projectref;
				$projectstatic->id = $obj->projectid;
				$projectstatic->title = $obj->projecttitle;
				print $projectstatic->getNomUrl(1, '', 44);
			}
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}
		if (! empty($arrayfields['f.fk_contrat']['checked']))
		{
			print '<td>';
			if ($obj->contractid > 0) {
				$contratstatic->ref = $obj->contractref;
				$contratstatic->id = $obj->contractid;
				print $contratstatic->getNomUrl(1, '', 44);
			}
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}
		if (! empty($arrayfields['f.description']['checked']))
		{
			print '<td>'.dol_trunc(dolGetFirstLineOfText($obj->description),48).'</td>';
			if (! $i) $totalarray['nbfield']++;
		}
		if (! empty($arrayfields['fd.description']['checked']))
		{
			//print '<td>'.dol_trunc(dol_escape_htmltag(dolGetFirstLineOfText($obj->descriptiondetail)),48).'</td>';
			print '<td>'.dolGetFirstLineOfText($obj->descriptiondetail).'</td>';
			if (! $i) $totalarray['nbfield']++;
		}
		if (! empty($arrayfields['fd.date']['checked']))
		{
			print '<td align="center">'.dol_print_date($db->jdate($obj->dp),'dayhour')."</td>\n";
			if (! $i) $totalarray['nbfield']++;
		}
		if (! empty($arrayfields['fd.duree']['checked']))
		{
			print '<td align="right">'.convertSecondToTime($obj->duree, 'allhourmin').'</td>';
			if (! $i) $totalarray['nbfield']++;
			if (! $i) $totalarray['totaldurationfield']=$totalarray['nbfield'];
			$totalarray['totalduration']+=$obj->duree;
		}

		// Extra fields
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
		// Fields from hook
		$parameters=array('arrayfields'=>$arrayfields, 'obj'=>$obj);
		$reshook=$hookmanager->executeHooks('printFieldListValue',$parameters);    // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;
		// Date creation
		if (! empty($arrayfields['f.datec']['checked']))
		{
			print '<td align="center">';
			print dol_print_date($db->jdate($obj->date_creation), 'dayhour', 'tzuser');
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}
		// Date modification
		if (! empty($arrayfields['f.tms']['checked']))
		{
			print '<td align="center">';
			print dol_print_date($db->jdate($obj->date_update), 'dayhour', 'tzuser');
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}
		// Status
		if (! empty($arrayfields['f.fk_statut']['checked']))
		{
			print '<td align="right">'.$objectstatic->LibStatut($obj->fk_statut,5).'</td>';
			if (! $i) $totalarray['nbfield']++;
		}
		// Action column
		print '<td class="nowrap" align="center">';
		if ($massactionbutton || $massaction)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
		{
			$selected=0;
			if (in_array($obj->rowid, $arrayofselected)) $selected=1;
			print '<input id="cb'.$obj->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected?' checked="checked"':'').'>';
		}
		print '</td>';
		if (! $i) $totalarray['nbfield']++;

		print "</tr>\n";

		$total += $obj->duree;
		$i++;
	}

	// Show total line
	if (isset($totalarray['totalduration']))
	{
		print '<tr class="liste_total">';
		$i=0;
		while ($i < $totalarray['nbfield'])
		{
			$i++;
			if ($i == 1)
			{
				if ($num < $limit && empty($offset)) print '<td align="left">'.$langs->trans("Total").'</td>';
				else print '<td align="left">'.$langs->trans("Totalforthispage").'</td>';
			}
			elseif ($totalarray['totaldurationfield'] == $i) print '<td align="right">'.convertSecondToTime($totalarray['totalduration'], 'allhourmin').'</td>';
			else print '<td></td>';
		}
		print '</tr>';
	}

	$db->free($resql);

	$parameters=array('arrayfields'=>$arrayfields, 'sql'=>$sql);
	$reshook=$hookmanager->executeHooks('printFieldListFooter',$parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	print '</table>'."\n";
	print '</div>';

	print "</form>\n";

	$hidegeneratedfilelistifempty=1;
	if ($massaction == 'builddoc' || $action == 'remove_file' || $show_files) $hidegeneratedfilelistifempty=0;

	// Show list of available documents
	$urlsource=$_SERVER['PHP_SELF'].'?sortfield='.$sortfield.'&sortorder='.$sortorder;
	$urlsource.=str_replace('&amp;','&',$param);

	$filedir=$diroutputmassaction;
	$genallowed=$user->rights->ficheinter->lire;
	$delallowed=$user->rights->ficheinter->creer;

	print $formfile->showdocuments('massfilesarea_interventions','',$filedir,$urlsource,0,$delallowed,'',1,1,0,48,1,$param,$title,'','','',null,$hidegeneratedfilelistifempty);
}
else
{
	dol_print_error($db);
}


llxFooter();
$db->close();
