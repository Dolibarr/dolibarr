<?php
/* Copyright (C) 2002-2003	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2014	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2012	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2013		Cédric Salvador			<csalvador@gpcsolutions.fr>
 * Copyright (C) 2015       Jean-François Ferry		<jfefe@aternatik.fr>
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

$langs->load("companies");
$langs->load("bills");
$langs->load("interventions");

$socid=GETPOST('socid','int');

// Security check
$fichinterid = GETPOST('id','int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'ficheinter', $fichinterid,'fichinter');

$limit = GETPOST('limit')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
$page = GETPOST('page','int');
if ($page == -1) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="DESC";
if (! $sortfield)
{
 	if (empty($conf->global->FICHINTER_DISABLE_DETAILS)) $sortfield="fd.date";
 	else $sortfield="f.ref";
}

$search_ref=GETPOST('search_ref')?GETPOST('search_ref','alpha'):GETPOST('search_inter','alpha');
$search_company=GETPOST('search_company','alpha');
$search_desc=GETPOST('search_desc','alpha');
$search_status=GETPOST('search_status');
$sall=GETPOST('sall');
$optioncss = GETPOST('optioncss','alpha');

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
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
	'f.fk_project'=>array('label'=>$langs->trans("Project"), 'checked'=>1),
    'f.fk_contrat'=>array('label'=>$langs->trans("Contract"), 'checked'=>1),
    'f.description'=>array('label'=>$langs->trans("Description"), 'checked'=>1),
    'fd.description'=>array('label'=>"xx", 'checked'=>1, 'enabled'=>empty($conf->global->FICHINTER_DISABLE_DETAILS)?1:0),
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
       $arrayfields["ef.".$key]=array('label'=>$extrafields->attribute_label[$key], 'checked'=>$extrafields->attribute_list[$key], 'position'=>$extrafields->attribute_pos[$key], 'enabled'=>$extrafields->attribute_perms[$key]);
   }
}


/*
 * Acions
 */

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter.x") || GETPOST("button_removefilter")) // Both test are required to be compatible with all browsers
{
	$search_ref="";
	$search_company="";
	$search_project="";
	$search_contract="";
	$search_desc="";
	$search_status="";
    $search_array_options=array();
}



/*
 *	View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$objectstatic=new Fichinter($db);

llxHeader('', $langs->trans("Intervention"));


$sql = "SELECT";
$sql.= " f.ref, f.rowid, f.fk_statut, f.description, f.datec as date_creation, f.tms as date_update, f.note_private,";
if (empty($conf->global->FICHINTER_DISABLE_DETAILS)) $sql.= " fd.description as descriptiondetail, fd.date as dp, fd.duree,";
$sql.= " s.nom as name, s.rowid as socid, s.client";
// Add fields from extrafields
foreach ($extrafields->attribute_label as $key => $val) $sql.=($extrafields->attribute_type[$key] != 'separate' ? ",ef.".$key.' as options_'.$key : '');
// Add fields from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListSelect',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql.= " FROM ".MAIN_DB_PREFIX."fichinter as f";
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."fichinter_extrafields as ef on (f.rowid = ef.fk_object)";
if (empty($conf->global->FICHINTER_DISABLE_DETAILS)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."fichinterdet as fd ON fd.fk_fichinter = f.rowid";
if (! $user->rights->societe->client->voir && empty($socid)) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= ", ".MAIN_DB_PREFIX."societe as s";
$sql.= " WHERE f.fk_soc = s.rowid ";
$sql.= " AND f.entity = ".$conf->entity;
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
if ($sall) {
	$sql .= natural_search(array_keys($fieldstosearchall), $sall);
}
// Add where from extra fields
foreach ($search_array_options as $key => $val)
{
    $crit=$val;
    $tmpkey=preg_replace('/search_options_/','',$key);
    $typ=$extrafields->attribute_type[$tmpkey];
    $mode=0;
    if (in_array($typ, array('int','double'))) $mode=1;    // Search on a numeric
    if ($val && ( ($crit != '' && ! in_array($typ, array('select'))) || ! empty($crit))) 
    {
        $sql .= natural_search('ef.'.$tmpkey, $crit, $mode);
    }
}
// Add where from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListWhere',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql.= $db->order($sortfield,$sortorder);

$totalnboflines=0;
$result=$db->query($sql);
if ($result)
{
    $totalnboflines = $db->num_rows($result);
}

$sql.= $db->plimit($limit+1, $offset);
//print $sql;

$result=$db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);

	$param='';
    if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.$contextpage;
	if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.$limit;
	if ($sall) $urlparam.="&sall=".$sall;
	if ($socid) $param.="&socid=".$socid;
	if ($search_ref) $param.="&search_ref=".urlencode($search_ref);
	if ($search_company) $param.="&search_company=".urlencode($search_company);
	if ($search_desc) $param.="&search_desc=".urlencode($search_desc);
	if ($search_status != '' && $search_status > -1) $param.="&search_status=".urlencode($search_status);
	if ($optioncss != '') $param.='&optioncss='.$optioncss;
    // Add $param from extra fields
    foreach ($search_array_options as $key => $val)
    {
        $crit=$val;
        $tmpkey=preg_replace('/search_options_/','',$key);
        if ($val != '') $param.='&search_options_'.$tmpkey.'='.urlencode($val);
    } 	
	
	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">'."\n";
    if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
    print '<input type="hidden" name="action" value="list">';
    print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
    print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';

	print_barre_liste($langs->trans("ListOfInterventions"), $page, $_SERVER['PHP_SELF'], $param, $sortfield, $sortorder, '', $num, $totalnboflines, 'title_commercial.png', 0, '', '', $limit);
	
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
	print '<tr class="liste_titre">';
	if (! empty($arrayfields['f.ref']['checked']))          print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"],"f.ref","",$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['s.nom']['checked']))          print_liste_field_titre($langs->trans("ThirdParty"),$_SERVER["PHP_SELF"],"s.nom","",$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['f.description']['checked']))  print_liste_field_titre($langs->trans("Description"),$_SERVER["PHP_SELF"],"f.description","",$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['fd.description']['checked'])) print_liste_field_titre('',$_SERVER["PHP_SELF"],'');
	if (! empty($arrayfields['fd.date']['checked']))        print_liste_field_titre($langs->trans("Date"),$_SERVER["PHP_SELF"],"fd.date","",$param,'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['fd.duree']['checked']))       print_liste_field_titre($langs->trans("Duration"),$_SERVER["PHP_SELF"],"fd.duree","",$param,'align="right"',$sortfield,$sortorder);
	// Extra fields
	if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
	{
	   foreach($extrafields->attribute_label as $key => $val) 
	   {
           if (! empty($arrayfields["ef.".$key]['checked'])) 
           {
				$align=$extrafields->getAlignFlag($key);
				print_liste_field_titre($extralabels[$key],$_SERVER["PHP_SELF"],"ef.".$key,"",$param,($align?'align="'.$align.'"':''),$sortfield,$sortorder);
           }
	   }
	}
    // Hook fields
	$parameters=array('arrayfields'=>$arrayfields);
    $reshook=$hookmanager->executeHooks('printFieldListTitle',$parameters);    // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;
	if (! empty($arrayfields['f.datec']['checked']))     print_liste_field_titre($langs->trans("DateCreationShort"),$_SERVER["PHP_SELF"],"f.datec","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
	if (! empty($arrayfields['f.tms']['checked']))       print_liste_field_titre($langs->trans("DateModificationShort"),$_SERVER["PHP_SELF"],"f.tms","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
	if (! empty($arrayfields['f.fk_statut']['checked'])) print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"f.fk_statut","",$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"],"",'','','align="right"',$sortfield,$sortorder,'maxwidthsearch ');
	print "</tr>\n";

	print '<tr class="liste_titre">';
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
	if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
	{
	   foreach($extrafields->attribute_label as $key => $val) 
	   {
			if (! empty($arrayfields["ef.".$key]['checked'])) 
			{
                $align=$extrafields->getAlignFlag($key);
                $typeofextrafield=$extrafields->attribute_type[$key];
                print '<td class="liste_titre'.($align?' '.$align:'').'">';
    		    if (in_array($typeofextrafield, array('varchar', 'int', 'double', 'select')))
				{
				    $crit=$val;
    				$tmpkey=preg_replace('/search_options_/','',$key);
    				$searchclass='';
    				if (in_array($typeofextrafield, array('varchar', 'select'))) $searchclass='searchstring';
    				if (in_array($typeofextrafield, array('int', 'double'))) $searchclass='searchnum';
    				print '<input class="flat'.($searchclass?' '.$searchclass:'').'" size="4" type="text" name="search_options_'.$tmpkey.'" value="'.dol_escape_htmltag($search_array_options['search_options_'.$tmpkey]).'">';
				}
				print '</td>';
			}
	   }
	}
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
	if (! empty($arrayfields['f.fk_statut']['checked']))
    {
		print '<td class="liste_titre" align="right">';
		$liststatus=$objectstatic->statuts_short;
		if (empty($conf->global->FICHINTER_CLASSIFY_BILLED)) unset($liststatus[2]);   // Option deprecated. In a future, billed must be managed with a dedicated field to 0 or 1
		print $form->selectarray('search_status', $liststatus, $search_status, 1, 0, 0, '', 1);
		print '</td>';
    }
	print '<td class="liste_titre" align="right">';
	$searchpitco=$form->showFilterAndCheckAddButtons(0);
	print $searchpitco;
	print '</td>';
    print "</tr>\n";

	$companystatic=new Societe($db);

	$var=True;
	$total = 0;
	$i = 0;
	$totalarray=array();
	while ($i < min($num, $limit))
	{
		$obj = $db->fetch_object($result);
		
		$objectstatic->id=$obj->rowid;
		$objectstatic->ref=$obj->ref;
		$objectstatic->statut=$obj->fk_statut;
		
		$var=!$var;
		print "<tr ".$bc[$var].">";
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
		if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
		{
		   foreach($extrafields->attribute_label as $key => $val) 
		   {
				if (! empty($arrayfields["ef.".$key]['checked'])) 
				{
					print '<td';
					$align=$extrafields->getAlignFlag($key);
					if ($align) print ' align="'.$align.'"';
					print '>';
					$tmpkey='options_'.$key;
					print $extrafields->showOutputField($key, $obj->$tmpkey, '', 1);
					print '</td>';
        			if (! $i) $totalarray['nbfield']++;
				}
		   }
		}
        // Fields from hook
	    $parameters=array('arrayfields'=>$arrayfields, 'obj'=>$obj);
		$reshook=$hookmanager->executeHooks('printFieldListValue',$parameters);    // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;
    	// Date creation
        if (! empty($arrayfields['f.datec']['checked']))
        {
            print '<td align="center">';
            print dol_print_date($db->jdate($obj->date_creation), 'dayhour');
            print '</td>';
        	if (! $i) $totalarray['nbfield']++;
        }
        // Date modification
        if (! empty($arrayfields['f.tms']['checked']))
        {
            print '<td align="center">';
            print dol_print_date($db->jdate($obj->date_update), 'dayhour');
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
        print '<td></td>';
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
	            if ($num < $limit) print '<td align="left">'.$langs->trans("Total").'</td>';
	            else print '<td align="left">'.$langs->trans("Totalforthispage").'</td>';
	        }
	        elseif ($totalarray['totaldurationfield'] == $i) print '<td align="right">'.convertSecondToTime($totalarray['totalduration'], 'allhourmin').'</td>';
	        else print '<td></td>';
	    }
	    print '</tr>';
	}

	print '</table>';
	print '</div>';
	
	print "</form>\n";
	$db->free($result);
}
else
{
	dol_print_error($db);
}


llxFooter();

$db->close();
