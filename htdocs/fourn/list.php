<?php
/* Copyright (C) 2001-2006  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@capnetworks.com>
 * Copyright (C) 2011       Philippe Grand          <philippe.grand@atoo-net.com>
 * Copyright (C) 2013       Cédric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2015       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
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
 *       \file       htdocs/fourn/list.php
 *       \ingroup    fournisseur
 *       \brief      Home page of supplier area
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

$langs->load("suppliers");
$langs->load("orders");
$langs->load("companies");

$socname					= GETPOST("socname");
$search_name				= GETPOST("search_name");
$search_zipcode				= GETPOST("search_zipcode");
$search_town				= GETPOST("search_town");
$search_supplier_code		= GETPOST("search_supplier_code");
$search_supplier_accounting = GETPOST("search_supplier_accounting");
$search_datec				= GETPOST("search_datec");
$search_categ				= GETPOST('search_categ','int');
$search_status              = GETPOST("search_status",'int');
$catid						= GETPOST("catid",'int');

// Security check
$socid = GETPOST('socid','int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user,'societe',$socid,'');

$page = GETPOST('page','int');
$sortorder = GETPOST('sortorder','alpha');
$sortfield = GETPOST('sortfield','alpha');
if ($page == -1) { $page = 0 ; }
$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="nom";

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('supplierlist'));
$extrafields = new ExtraFields($db);

if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter")) // Both test are required to be compatible with all browsers
{
	$socname="";
	$search_name="";
	$search_zipcode="";
	$search_town="";
	$search_supplier_code="";
	$search_supplier_accounting="";
	$search_datec="";
	$search_categ="";
    $search_status='';
	$catid="";
}

if ($search_status=='') $search_status=1; // always display activ customer first

$extrafields->fetch_name_optionals_label('thirdparty');


/*
 * 	Actions
 */

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');


/*
 *	View
 */

$form=new Form($db);
$htmlother=new FormOther($db);
$thirdpartystatic=new Societe($db);

$help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('',$langs->trans("ThirdParty"),$help_url);

$sql = "SELECT s.rowid as socid, s.nom as name, s.name_alias, s.zip, s.town, s.datec, st.libelle as stcomm, s.prefix_comm, s.status as status, ";
$sql.= "code_fournisseur, code_compta_fournisseur";
if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user ";
// Add fields for extrafields
foreach ($extrafields->attribute_list as $key => $val) $sql.=",ef.".$key.' as options_'.$key;
// Add fields from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListSelect',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_extrafields as ef ON ef.fk_object = s.rowid";
if (! empty($search_categ) || ! empty($catid)) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_fournisseur as cf ON s.rowid = cf.fk_soc"; // We need this table joined to the select in order to filter by categ
$sql.= ", ".MAIN_DB_PREFIX."c_stcomm as st";
if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= " WHERE s.fk_stcomm = st.id AND s.fournisseur = 1";
$sql.= " AND s.entity IN (".getEntity('societe', 1).")";
if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc";
if ($socid) $sql .= " AND s.rowid = ".$socid;
if ($socname) {
	$sql .= natural_search('s.nom', $socname);
	$sortfield = "s.nom";
	$sortorder = "ASC";
}
if ($search_name) $sql .= natural_search(array('s.nom', 's.name_alias'), $search_name);
if ($search_zipcode) $sql .= " AND s.zip LIKE '".$db->escape($search_zipcode)."%'";
if ($search_town) $sql .= natural_search('s.town', $search_town);
if ($search_supplier_code)   $sql .= " AND s.code_fournisseur LIKE '%".$db->escape($search_supplier_code)."%'";
if ($search_supplier_accounting) $sql .= " AND s.code_compta_fournisseur LIKE '%".$db->escape($search_supplier_accounting)."%'";
if ($search_datec)   $sql .= " AND s.datec LIKE '%".$db->escape($search_datec)."%'";
if ($search_status!='')  $sql.= " AND s.status = ".$db->escape($search_status);
if ($catid > 0)          $sql.= " AND cf.fk_categorie = ".$catid;
if ($catid == -2)        $sql.= " AND cf.fk_categorie IS NULL";
if ($search_categ > 0)   $sql.= " AND cf.fk_categorie = ".$search_categ;
if ($search_categ == -2) $sql.= " AND cf.fk_categorie IS NULL";
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
$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($conf->liste_limit+1, $offset);
//print $sql;

dol_syslog('fourn/list.php:', LOG_DEBUG);
$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;

	$param = "&amp;search_name=".$search_name."&amp;search_supplier_code=".$search_supplier_code."&amp;search_zipcode=".$search_zipcode."&amp;search_town=".$search_town;
 	if ($search_categ != '') $param.='&amp;search_categ='.$search_categ;
 	if ($search_status != '') $param.='&amp;search_status='.$search_status;

	print_barre_liste($langs->trans("ListOfSuppliers"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'title_companies');

	print '<form method="GET" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">'."\n";

	// Filter on categories
	$moreforfilter='';
	if (! empty($conf->categorie->enabled))
	{
		require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
		$moreforfilter.=$langs->trans('Categories'). ': ';
		$moreforfilter.=$htmlother->select_categories(Categorie::TYPE_SUPPLIER,$search_categ,'search_categ',1);
		$moreforfilter.=' &nbsp; &nbsp; &nbsp; ';
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
	print_liste_field_titre($langs->trans("Company"),$_SERVER["PHP_SELF"],"s.nom","",$param,'valign="middle"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Zip"),$_SERVER["PHP_SELF"],"s.zip","",$param,'valign="middle"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Town"),$_SERVER["PHP_SELF"],"s.town","",$param,'valign="middle"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("SupplierCode"),$_SERVER["PHP_SELF"],"s.code_fournisseur","",$param,'align="left"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("AccountancyCode"),$_SERVER["PHP_SELF"],"s.code_compta_fournisseur","",$param,'align="left"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("DateCreation"),$_SERVER["PHP_SELF"],"s.datec","",$param,'align="right"',$sortfield,$sortorder);

	$parameters=array();
    $reshook=$hookmanager->executeHooks('printFieldListTitle',$parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

    print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"s.status","",$param,'align="center"',$sortfield,$sortorder);
    print_liste_field_titre('',$_SERVER["PHP_SELF"],"",'','','',$sortfield,$sortorder,'maxwidthsearch ');
	print "</tr>\n";

	print '<tr class="liste_titre">';

	print '<td class="liste_titre"><input type="text" size="10" class="flat" name="search_name" value="'.$search_name.'"></td>';

	print '<td class="liste_titre"><input type="text" size="10" class="flat" name="search_zipcode" value="'.$search_zipcode.'"></td>';

	print '<td class="liste_titre"><input type="text" size="10" class="flat" name="search_town" value="'.$search_town.'"></td>';

	print '<td align="left" class="liste_titre">';
	print '<input class="flat" type="text" size="10" name="search_supplier_code" value="'.$search_supplier_code.'">';
	print '</td>';

	print '<td align="left" class="liste_titre">';
	print '<input class="flat" type="text" size="10" name="search_supplier_accounting" value="'.$search_supplier_accounting.'">';
	print '</td>';

	print '<td align="right" class="liste_titre">';
	print '<input class="flat" type="text" size="10" name="search_datec" value="'.$search_datec.'">';
	print '</td>';

	$parameters=array();
	$reshook=$hookmanager->executeHooks('printFieldListSearch',$parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

    print '<td class="liste_titre" align="center">';
    print $form->selectarray('search_status', array('0'=>$langs->trans('ActivityCeased'),'1'=>$langs->trans('InActivity')),$search_status);
    print '</td>';

	print '<td class="liste_titre" align="right"><input type="image" class="liste_titre" name="button_search" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '<input type="image" class="liste_titre" name="button_removefilter" src="'.img_picto($langs->trans("Search"),'searchclear.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
    print "</td>\n";

	print '</tr>';

	$var=True;

	while ($i < min($num,$conf->liste_limit))
	{
		$obj = $db->fetch_object($resql);
		$var=!$var;

        $thirdpartystatic->id=$obj->socid;
        $thirdpartystatic->name=$obj->name;
        $thirdpartystatic->status=$obj->status;
        $thirdpartystatic->name_alias=$obj->name_alias;

		print "<tr ".$bc[$var].">";
		print '<td>';
        print $thirdpartystatic->getNomUrl(1,'supplier');
		print "</td>\n";
		print '<td>'.$obj->zip.'</td>'."\n";
		print '<td>'.$obj->town.'</td>'."\n";
		print '<td align="left">'.$obj->code_fournisseur.'&nbsp;</td>';
		print '<td align="left">'.$obj->code_compta_fournisseur.'&nbsp;</td>';
		print '<td align="right">'.dol_print_date($db->jdate($obj->datec),'day').'</td>';

		$parameters=array('obj' => $obj);
		$reshook=$hookmanager->executeHooks('printFieldListValue',$parameters);    // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;

		print '<td align="center">'.$thirdpartystatic->getLibStatut(3).'</td>';

		print '<td></td>';

		print "</tr>\n";
		$i++;
	}
	print "</table>\n";
	print "</form>\n";
	$db->free($resql);

	$parameters=array('sql' => $sql);
	$reshook=$hookmanager->executeHooks('printFieldListFooter',$parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
}
else
{
	dol_print_error($db);
}

$db->close();

llxFooter();
