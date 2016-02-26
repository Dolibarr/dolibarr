<?php
/* Copyright (C) 2001-2004  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@capnetworks.com>
 * Copyright (C) 2011       Philippe Grand          <philippe.grand@atoo-net.com>
 * Copyright (C) 2013       Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2013       Cédric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2015       Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2015       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2015       Marcos García        <marcosgdf@gmail.com>
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
 *	\file       htdocs/comm/prospect/list.php
 *	\ingroup    prospect
 *	\brief      Page to list prospects
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/client.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

$langs->load("propal");
$langs->load("companies");

// Security check
$socid = GETPOST("socid",'int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe',$socid,'');

$action				= GETPOST('action','alpha');
$socname            = GETPOST("socname",'alpha');
$stcomm             = GETPOST("stcomm",'alpha');	// code
$search_stcomm      = GETPOST("search_stcomm",'int');
$search_nom         = GETPOST("search_nom");
$search_zipcode     = GETPOST("search_zipcode");
$search_town        = GETPOST("search_town");
$search_state       = GETPOST("search_state");
$search_datec       = GETPOST("search_datec");
$search_categ       = GETPOST("search_categ",'int');
$search_status		= GETPOST("search_status",'int');
$catid              = GETPOST("catid",'int');

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page      = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="s.nom";

$search_level_from = GETPOST("search_level_from","alpha");
$search_level_to   = GETPOST("search_level_to","alpha");

// If both parameters are set, search for everything BETWEEN them
if ($search_level_from != '' && $search_level_to != '')
{
	// Ensure that these parameters are numbers
	$search_level_from = (int) $search_level_from;
	$search_level_to = (int) $search_level_to;

	// If from is greater than to, reverse orders
	if ($search_level_from > $search_level_to)
	{
		$tmp = $search_level_to;
		$search_level_to = $search_level_from;
		$search_level_from = $tmp;
	}

	// Generate the SQL request
	$sortwhere = '(sortorder BETWEEN '.$search_level_from.' AND '.$search_level_to.') AS is_in_range';
}
// If only "from" parameter is set, search for everything GREATER THAN it
else if ($search_level_from != '')
{
	// Ensure that this parameter is a number
	$search_level_from = (int) $search_level_from;

	// Generate the SQL request
	$sortwhere = '(sortorder >= '.$search_level_from.') AS is_in_range';
}
// If only "to" parameter is set, search for everything LOWER THAN it
else if ($search_level_to != '')
{
	// Ensure that this parameter is a number
	$search_level_to = (int) $search_level_to;

	// Generate the SQL request
	$sortwhere = '(sortorder <= '.$search_level_to.') AS is_in_range';
}
// If no parameters are set, dont search for anything
else
{
	$sortwhere = '0 as is_in_range';
}

// Select every potentiels, and note each potentiels which fit in search parameters
dol_syslog('prospects::prospects_prospect_level',LOG_DEBUG);
$sql = "SELECT code, label, sortorder, ".$sortwhere;
$sql.= " FROM ".MAIN_DB_PREFIX."c_prospectlevel";
$sql.= " WHERE active > 0";
$sql.= " ORDER BY sortorder";

$resql = $db->query($sql);
if ($resql)
{
	$tab_level = array();
	$search_levels = array();

	while ($obj = $db->fetch_object($resql))
	{
		// Compute level text
		$level=$langs->trans($obj->code);
		if ($level == $obj->code) $level=$langs->trans($obj->label);

		// Put it in the array sorted by sortorder
		$tab_level[$obj->sortorder] = $level;

		// If this potentiel fit in parameters, add its code to the $search_levels array
		if ($obj->is_in_range == 1)
		{
			$search_levels[] = '"'.preg_replace('[^A-Za-z0-9_-]', '', $obj->code).'"';
		}
	}

	// Implode the $search_levels array so that it can be use in a "IN (...)" where clause.
	// If no paramters was set, $search_levels will be empty
	$search_levels = implode(',', $search_levels);
}
else dol_print_error($db);

// Load sale and categ filters
$search_sale = GETPOST('search_sale','int');
$search_categ = GETPOST('search_categ','int');
// If the internal user must only see his prospect, force searching by him
if (!$user->rights->societe->client->voir && !$socid) $search_sale = $user->id;

// List of available states; we'll need that for each lines (quick changing prospect states) and for search bar (filter by prospect state)
$sts = array(-1,0,1,2,3);


// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('prospectlist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('thirdparty');
$search_array_options=$extrafields->getOptionalsFromPost($extralabels,'','search_');

// Do we click on purge search criteria ?
if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter")) // Both test are required to be compatible with all browsers
{
    $socname="";
	$stcomm="";
	$search_stcomm="";
	$search_nom="";
	$search_zipcode="";
	$search_town="";
	$search_state="";
	$search_datec="";
	$search_categ="";
	$search_status="";
	$search_array_options=array();
}

if ($search_status=='') $search_status=1; // always display active customer first



/*
 * Actions
 */

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	if ($action == 'setstcomm')
	{
		$object = new Client($db);
		$result=$object->fetch($socid);
		$object->stcomm_id=dol_getIdFromCode($db, GETPOST('stcomm','alpha'), 'c_stcomm');
		$result=$object->set_commnucation_level($user);
		if ($result < 0) setEventMessages($object->error,$object->errors,'errors');

		$action=''; $socid=0;
	}
}


/*
 * View
 */

$formother=new FormOther($db);
$form=new Form($db);
$prospectstatic=new Client($db);
$prospectstatic->client=2;
$prospectstatic->loadCacheOfProspStatus();

$sql = "SELECT s.rowid as socid, s.nom as name, s.name_alias, s.zip, s.town, s.datec, s.status as status, s.code_client, s.client,";
$sql.= " s.prefix_comm, s.fk_prospectlevel, s.fk_stcomm as stcomm_id,";
$sql.= " st.libelle as stcomm_label,";
$sql.= " d.nom as departement";
if ((!$user->rights->societe->client->voir && !$socid) || $search_sale > 0) $sql .= ", sc.fk_soc, sc.fk_user"; // We need these fields in order to filter by sale (including the case where the user can only see his prospects)
// Add fields for extrafields
if (is_array($extrafields->attribute_list) && count($extrafields->attribute_list)) foreach ($extrafields->attribute_list as $key => $val) $sql.=",ef.".$key.' as options_'.$key;
// Add fields from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListSelect',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql .= " FROM ".MAIN_DB_PREFIX."c_stcomm as st";
$sql.= ", ".MAIN_DB_PREFIX."societe as s";
if (is_array($extrafields->attribute_list) && count($extrafields->attribute_list)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_extrafields as ef on (s.rowid = ef.fk_object)";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as d on (d.rowid = s.fk_departement)";
if (! empty($search_categ) || ! empty($catid)) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_societe as cs ON s.rowid = cs.fk_soc"; // We need this table joined to the select in order to filter by categ
if ((!$user->rights->societe->client->voir && !$socid) || $search_sale > 0) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc"; // We need this table joined to the select in order to filter by sale
$sql.= " WHERE s.fk_stcomm = st.id";
$sql.= " AND s.client IN (2, 3)";
$sql.= ' AND s.entity IN ('.getEntity('societe', 1).')';
if ((!$user->rights->societe->client->voir && !$socid) || $search_sale > 0) $sql.= " AND s.rowid = sc.fk_soc";
if ($socid) $sql.= " AND s.rowid = " .$socid;
if ($search_stcomm != '' && $search_stcomm != -2) $sql.= natural_search("s.fk_stcomm",$search_stcomm,2);
if ($catid > 0)           $sql.= " AND cs.fk_categorie = ".$catid;
if ($catid == -2)         $sql.= " AND cs.fk_categorie IS NULL";
if ($search_categ > 0)    $sql.= " AND cs.fk_categorie = ".$search_categ;
if ($search_categ == -2)  $sql.= " AND cs.fk_categorie IS NULL";
if ($search_nom) $sql .= natural_search(array('s.nom','s.name_alias'), $search_nom);
if ($search_zipcode) $sql .= " AND s.zip LIKE '".$db->escape(strtolower($search_zipcode))."%'";
if ($search_town)    $sql .= natural_search('s.town', $search_town);
if ($search_state)   $sql .= natural_search('d.nom', $search_state);
if ($search_datec)   $sql .= " AND s.datec LIKE '%".$db->escape($search_datec)."%'";
if ($search_status!='') $sql .= " AND s.status = ".$db->escape($search_status);
// Insert levels filters
if ($search_levels)  $sql .= " AND s.fk_prospectlevel IN (".$search_levels.')';
// Insert sale filter
if ($search_sale > 0) $sql .= " AND sc.fk_user = ".$db->escape($search_sale);
if ($socname)
{
	$sql .= natural_search('s.nom', $search_nom);
	$sortfield = "s.nom";
	$sortorder = "ASC";
}
// Extra fields
foreach ($search_array_options as $key => $val)
{
    $crit=$val;
    $tmpkey=preg_replace('/search_options_/','',$key);
    $typ=$extrafields->attribute_type[$tmpkey];
    $mode=0;
    if (in_array($typ, array('int'))) $mode=1;    // Search on a numeric
    if ($val && ( ($crit != '' && ! in_array($typ, array('select'))) || ! empty($crit))) 
    {
        $sql .= natural_search('ef.'.$tmpkey, $crit, $mode);
    }
}
// Add where from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListWhere',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
// Count total nb of records
$nbtotalofrecords = 0;
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
}
$sql.= " ORDER BY $sortfield $sortorder, s.nom ASC";
$sql.= $db->plimit($conf->liste_limit+1, $offset);
//print $sql;

dol_syslog('comm/prospect/list.php', LOG_DEBUG);
$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);

	if ($num == 1 && $socname)
	{
		$obj = $db->fetch_object($resql);
		header("Location: card.php?socid=".$obj->socid);
		exit;
	}
	else
	{
        $help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
        llxHeader('',$langs->trans("ThirdParty"),$help_url);
	}

	$param='&search_stcomm='.$search_stcomm.'&search_nom='.urlencode($search_nom).'&search_zipcode='.urlencode($search_zipcode).'&search_town='.urlencode($search_town);
 	// Store the status filter in the URL
 	if (isSet($search_setstcomm))
 	{
 		foreach ($search_setstcomm as $key => $value)
 		{
 			if ($value == 'true')
 				$param.='&search_setstcomm['.((int) $key).']=true';
 			else
 				$param.='&search_setstcomm['.((int) $key).']=false';
 		}
 	}
 	if ($search_level_from != '') $param.='&search_level_from='.$search_level_from;
 	if ($search_level_to != '') $param.='&search_level_to='.$search_level_to;
 	if ($search_categ != '') $param.='&search_categ='.urlencode($search_categ);
 	if ($search_sale > 0) $param.='&search_sale='.$search_sale;
 	if ($search_status != '') $param.='&search_status='.$search_status;
    foreach ($search_array_options as $key => $val)
    {
        $crit=$val;
        $tmpkey=preg_replace('/search_options_/','',$key);
        $param.='&search_options_'.$tmpkey.'='.urlencode($val);
    } 	
 	// $param and $urladd should have the same value
 	$urladd = $param;

	print_barre_liste($langs->trans("ListOfProspects"), $page, $_SERVER["PHP_SELF"], $param, $sortfield,$sortorder,'',$num,$nbtotalofrecords,'title_companies.png');


 	// Print the search-by-sale and search-by-categ filters
 	print '<form method="GET" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';

	// Filter on categories
 	$moreforfilter='';
	if (! empty($conf->categorie->enabled))
	{
		require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
	 	$moreforfilter.=$langs->trans('Categories'). ': ';
		$moreforfilter.=$formother->select_categories(Categorie::TYPE_CUSTOMER,$search_categ,'search_categ',1);
	 	$moreforfilter.=' &nbsp; &nbsp; &nbsp; ';
	}
 	// If the user can view prospects other than his'
 	if ($user->rights->societe->client->voir || $socid)
 	{
	 	$moreforfilter.=$langs->trans('SalesRepresentatives'). ': ';
		$moreforfilter.=$formother->select_salesrepresentatives($search_sale,'search_sale',$user);
 	}
 	if ($moreforfilter)
	{
		print '<div class="liste_titre">';
	    print $moreforfilter;
    	$parameters=array();
    	$reshook=$hookmanager->executeHooks('printFieldPreListTitle',$parameters);    // Note that $action and $object may have been modified by hook
    	print $hookmanager->resPrint;
	    print '</div>';
	}

	print '<table class="liste" width="100%">';

	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Company"),$_SERVER["PHP_SELF"],"s.nom","",$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Zip"),$_SERVER["PHP_SELF"],"s.zip","",$param,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Town"),$_SERVER["PHP_SELF"],"s.town","",$param,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("State"),$_SERVER["PHP_SELF"],"s.fk_departement","",$param,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("DateCreation"),$_SERVER["PHP_SELF"],"s.datec","",$param,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("ProspectLevelShort"),$_SERVER["PHP_SELF"],"s.fk_prospectlevel","",$param,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("StatusProsp"),$_SERVER["PHP_SELF"],"s.fk_stcomm","",$param,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre('');
    
    // Extrafields
	if (is_array($extrafields->attribute_list) && count($extrafields->attribute_list))
	{
	   foreach($extrafields->attribute_list as $key => $val) 
	   {
	       if ($val)
	       {
	           print_liste_field_titre($extralabels[$key],$_SERVER["PHP_SELF"],"ef.".$key,"",$param,"",$sortfield,$sortorder);
	       }
	   }
	}
	// Hook fields
	$parameters=array();
    $reshook=$hookmanager->executeHooks('printFieldListTitle',$parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"s.status","",$param,'align="right"',$sortfield,$sortorder);
    print_liste_field_titre('',$_SERVER["PHP_SELF"],"",'','','',$sortfield,$sortorder,'maxwidthsearch ');
	print "</tr>\n";

	print '<tr class="liste_titre">';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_nom" size="10" value="'.$search_nom.'">';
	print '</td>';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_zipcode" size="6" value="'.$search_zipcode.'">';
	print '</td>';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_town" size="8" value="'.$search_town.'">';
	print '</td>';
 	print '<td class="liste_titre" align="center">';
    print '<input type="text" class="flat" name="search_state" size="8" value="'.$search_state.'">';
    print '</td>';
    print '<td align="center" class="liste_titre">';
	print '<input class="flat" type="text" size="10" name="search_datec" value="'.$search_datec.'">';
    print '</td>';

 	// Prospect level
 	print '<td class="liste_titre" align="center">';
 	$options_from = '<option value="">&nbsp;</option>';	 	// Generate in $options_from the list of each option sorted
 	foreach ($tab_level as $tab_level_sortorder => $tab_level_label)
 	{
 		$options_from .= '<option value="'.$tab_level_sortorder.'"'.($search_level_from == $tab_level_sortorder ? ' selected':'').'>';
 		$options_from .= $langs->trans($tab_level_label);
 		$options_from .= '</option>';
 	}
 	array_reverse($tab_level, true);	// Reverse the list
 	$options_to = '<option value="">&nbsp;</option>';		// Generate in $options_to the list of each option sorted in the reversed order
 	foreach ($tab_level as $tab_level_sortorder => $tab_level_label)
 	{
 		$options_to .= '<option value="'.$tab_level_sortorder.'"'.($search_level_to == $tab_level_sortorder ? ' selected':'').'>';
 		$options_to .= $langs->trans($tab_level_label);
 		$options_to .= '</option>';
 	}

 	// Print these two select
 	print $langs->trans("From").' <select class="flat" name="search_level_from">'.$options_from.'</select>';
 	print ' ';
 	print $langs->trans("to").' <select class="flat" name="search_level_to">'.$options_to.'</select>';

    print '</td>';

    // Prospect status
    print '<td class="liste_titre" align="center">';
    $arraystcomm=array();
    foreach($prospectstatic->cacheprospectstatus as $key => $val)
    {
        $arraystcomm[$val['id']]=($langs->trans("StatusProspect".$val['id']) != "StatusProspect".$val['id'] ? $langs->trans("StatusProspect".$val['id']) : $val['label']);
    }    
    print $form->selectarray('search_stcomm', $arraystcomm, $search_stcomm, -2);
    print '</td>';

    print '<td class="liste_titre" align="center">';
    print '&nbsp;';
    print '</td>';

    // Extrafields
	if (is_array($extrafields->attribute_list) && count($extrafields->attribute_list))
	{
	   foreach($extrafields->attribute_list as $key => $val) 
	   {
	       if ($val)
	       {
	           $crit=$search_array_options['search_options_'.$key];
	           print '<td class="liste_titre">';
               print $extrafields->showInputField($key, $crit, '', '', 'search_', 4);
               print '</td>';
	       }
	   }
	}
    // Hook fields
    $parameters=array();
	$reshook=$hookmanager->executeHooks('printFieldListSearch',$parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	// Status
    print '<td class="liste_titre" align="right">';
    print $form->selectarray('search_status', array('0'=>$langs->trans('ActivityCeased'),'1'=>$langs->trans('InActivity')),$search_status);
    print '</td>';

    // Print the search button
    print '<td class="liste_titre" align="right"><input type="image" class="liste_titre" name="button_search" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '<input type="image" class="liste_titre" name="button_removefilter" src="'.img_picto($langs->trans("Search"),'searchclear.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
    print "</td></tr>\n";

	print "</tr>\n";

	$i = 0;
	$var=true;

	while ($i < min($num,$conf->liste_limit))
	{
		$obj = $db->fetch_object($resql);

		$var=!$var;

		print '<tr '.$bc[$var].'>';
		print '<td>';
		$prospectstatic->id=$obj->socid;
		$prospectstatic->name=$obj->name;
        $prospectstatic->status=$obj->status;
        $prospectstatic->code_client=$obj->code_client;
        $prospectstatic->client=$obj->client;
        $prospectstatic->fk_prospectlevel=$obj->fk_prospectlevel;
        $prospectstatic->name_alias=$obj->name_alias;
		print $prospectstatic->getNomUrl(1,'prospect');
        print '</td>';
        print "<td>".$obj->zip."</td>";
		print "<td>".$obj->town."</td>";
		print '<td align="center">'.$obj->departement.'</td>';
		// Creation date
		print '<td align="center">'.dol_print_date($db->jdate($obj->datec)).'</td>';
		// Level
		print '<td align="center">';
		print $prospectstatic->getLibProspLevel();
		print "</td>";
		// Statut
		print '<td align="center" class="nowrap">';
		print $prospectstatic->LibProspCommStatut($obj->stcomm_id,2,$prospectstatic->cacheprospectstatus[$obj->stcomm_id]['label']);
		print "</td>";

		print '<td align="center" class="nowrap">';
		foreach($prospectstatic->cacheprospectstatus as $key => $val)
		{
			$titlealt='default';
			if (! empty($val['code']) && ! in_array($val['code'], array('ST_NO', 'ST_NEVER', 'ST_TODO', 'ST_PEND', 'ST_DONE'))) $titlealt=$val['label'];
			if ($obj->stcomm_id != $val['id']) print '<a class="pictosubstatus" href="'.$_SERVER["PHP_SELF"].'?socid='.$obj->socid.'&stcomm='.$val['code'].'&action=setstcomm'.$param.($page?'&page='.urlencode($page):'').'">'.img_action($titlealt,$val['code']).'</a>';
		}
		print '</td>';

	    // Extrafields
    	if (is_array($extrafields->attribute_list) && count($extrafields->attribute_list))
    	{
    	   foreach($extrafields->attribute_list as $key => $val) 
    	   {
    	       if ($val)
    	       {
                    print '<td>';
                    $paramkey='options_'.$key;
                    print $extrafields->showOutputField($key, $obj->$paramkey);
                    print '</td>';
    	       }
    	   }
    	}		
		// Hook fields
        $parameters=array('obj' => $obj);
        $reshook=$hookmanager->executeHooks('printFieldListValue',$parameters);    // Note that $action and $object may have been modified by hook
	    print $hookmanager->resPrint;

		print '<td align="right">';
		print $prospectstatic->LibStatut($prospectstatic->status,5);
        print '</td>';

        print '<td></td>';

        print "</tr>\n";
		$i++;
	}

	if ($num > $conf->liste_limit || $page > 0) print_barre_liste('', $page, $_SERVER["PHP_SELF"],$param,$sortfield,$sortorder,'',$num,$nbtotalofrecords);

	print "</table>";

	print "</form>";

	$db->free($resql);

	$parameters=array('sql' => $sql);
	$reshook=$hookmanager->executeHooks('printFieldListFooter',$parameters);    // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;
}
else
{
	dol_print_error($db);
}


llxFooter();
$db->close();
