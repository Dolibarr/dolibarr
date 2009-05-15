<?php
/* Copyright (C) 2008-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2008-2009 Regis Houssin        <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *	\file       htdocs/ecm/index.php
 *	\ingroup    ecm
 *	\brief      Main page for ECM section area
 *	\version    $Id$
 *	\author		Laurent Destailleur
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/ecm.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/treeview.lib.php");
require_once(DOL_DOCUMENT_ROOT."/ecm/ecmdirectory.class.php");

// Load traductions files
$langs->load("ecm");
$langs->load("companies");
$langs->load("other");
$langs->load("users");
$langs->load("orders");
$langs->load("propal");
$langs->load("bills");
$langs->load("contracts");

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'ecm','');

// Load permissions
$user->getrights('ecm');

// Get parameters
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
$action = isset($_GET["action"])?$_GET["action"]:$_POST['action'];
$section=isset($_GET["section"])?$_GET["section"]:$_POST['section'];
if (! $section) $section=0;

$upload_dir = $conf->ecm->dir_output.'/'.$section;

$page=$_GET["page"];
$sortorder=$_GET["sortorder"];
$sortfield=$_GET["sortfield"];

$limit = $conf->liste_limit;
$offset = $limit * $page ;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="label";

$ecmdir = new ECMDirectory($db);
if (! empty($_REQUEST["section"]))
{
	$result=$ecmdir->fetch($_REQUEST["section"]);
	if (! $result > 0)
	{
		dol_print_error($db,$ecmdir->error);
		exit;
	}
}


/*******************************************************************
 * ACTIONS
 *
 * Put here all code to do according to value of "action" parameter
 ********************************************************************/





/*******************************************************************
 * PAGE
 *
 * Put here all code to do according to value of "action" parameter
 ********************************************************************/

llxHeader();

$form=new Form($db);
$ecmdirstatic = new ECMDirectory($db);
$userstatic = new User($db);


// Ajout rubriques automatiques
$rowspan=0;
$sectionauto=array();
if ($conf->produit->enabled)     { $rowspan++; $sectionauto[]=array('level'=>1, 'module'=>'product', 'test'=>$conf->produit->enabled, 'label'=>$langs->trans("ProductsAndServices"),     'desc'=>$langs->trans("ECMDocsByProducts")); }
if ($conf->societe->enabled)     { $rowspan++; $sectionauto[]=array('level'=>1, 'module'=>'company', 'test'=>$conf->societe->enabled, 'label'=>$langs->trans("ThirdParties"), 'desc'=>$langs->trans("ECMDocsByThirdParties")); }
if ($conf->propal->enabled)      { $rowspan++; $sectionauto[]=array('level'=>1, 'module'=>'propal',  'test'=>$conf->propal->enabled,  'label'=>$langs->trans("Prop"),    'desc'=>$langs->trans("ECMDocsByProposals")); }
if ($conf->contrat->enabled)     { $rowspan++; $sectionauto[]=array('level'=>1, 'module'=>'contract','test'=>$conf->contrat->enabled, 'label'=>$langs->trans("Contracts"),    'desc'=>$langs->trans("ECMDocsByContracts")); }
if ($conf->commande->enabled)    { $rowspan++; $sectionauto[]=array('level'=>1, 'module'=>'order',   'test'=>$conf->commande->enabled,'label'=>$langs->trans("CustomersOrders"),       'desc'=>$langs->trans("ECMDocsByOrders")); }
if ($conf->fournisseur->enabled) { $rowspan++; $sectionauto[]=array('level'=>1, 'module'=>'supplier_order', 'test'=>$conf->fournisseur->enabled, 'label'=>$langs->trans("SuppliersInvoices"),     'desc'=>$langs->trans("ECMDocsByOrders")); }
if ($conf->facture->enabled)     { $rowspan++; $sectionauto[]=array('level'=>1, 'module'=>'invoice', 'test'=>$conf->facture->enabled, 'label'=>$langs->trans("CustomersInvoices"),     'desc'=>$langs->trans("ECMDocsByInvoices")); }
if ($conf->fournisseur->enabled) { $rowspan++; $sectionauto[]=array('level'=>1, 'module'=>'supplier_invoice', 'test'=>$conf->fournisseur->enabled, 'label'=>$langs->trans("SuppliersOrders"),     'desc'=>$langs->trans("ECMDocsByOrders")); }


//***********************
// List
//***********************
print_fiche_titre($langs->trans("ECMArea"));

print $langs->trans("ECMAreaDesc")."<br>";
print $langs->trans("ECMAreaDesc2")."<br>";
print "<br>\n";

if ($mesg) { print $mesg."<br>"; }

// Tool bar
$head = ecm_prepare_head_fm($fac);
dol_fiche_head($head, 'search_form', '', 1);


print '<table class="border" width="100%"><tr><td width="40%" valign="top">';

// Left area
print '<table class="nobordernopadding" width="100%"><tr><td valign="top">';



//print_fiche_titre($langs->trans("ECMSectionsManual"));

print '<form method="post" action="'.DOL_URL_ROOT.'/ecm/search.php">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<table class="nobordernopadding" width="100%">';
print "<tr class=\"liste_titre\">";
print '<td colspan="2">'.$langs->trans("ECMSearchByKeywords").'</td></tr>';
print "<tr ".$bc[false]."><td>".$langs->trans("Ref").':</td><td align="right"><input type="text" name="search_ref" class="flat" size="14"></td></tr>';
print "<tr ".$bc[false]."><td>".$langs->trans("Title").':</td><td align="right"><input type="text" name="search_title" class="flat" size="14"></td></tr>';
print "<tr ".$bc[false]."><td>".$langs->trans("Keyword").':</td><td align="right"><input type="text" name="search_keyword" class="flat" size="14"></td></tr>';
print "<tr ".$bc[false].'><td colspan="2" align="center"><input type="submit" class="button" value="'.$langs->trans("Search").'"></td></tr>';
print "</table></form>";
//print $langs->trans("ECMSectionManualDesc");

//print_fiche_titre($langs->trans("ECMSectionAuto"));

print '<form method="post" action="'.DOL_URL_ROOT.'/ecm/search.php">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<table class="noborder" width="100%">';
print "<tr class=\"liste_titre\">";
print '<td colspan="4">'.$langs->trans("ECMSearchByEntity").'</td></tr>';

$buthtml='<td rowspan="'.$rowspan.'"><input type="submit" value="'.$langs->trans("Search").'" class="button"></td>';
$butshown=0;
foreach($sectionauto as $sectioncur)
{
	if (! $sectioncur['test']) continue;
	//if ($butshown % 2 == 0)
		print '<tr '. $bc[false].'>';
	print "<td>".$sectioncur['label'].':</td>';
	print '<td';
	//if ($butshown % 2 == 1)
		print ' align="right"';
	print '>';
	print '<input type="text" name="search_'.$sectioncur['module'].'" class="flat" size="14">';
	print '</td>';
	//if ($butshown % 2 == 1)
		print '</tr>';
	$butshown++;
}
//if ($butshown % 2 == 1)
//	print '<td>&nbsp;</td><td>&nbsp;</td></tr>';

print '<tr '. $bc[false].'><td colspan="4" align="center"><input type="submit" class="button" value="'.$langs->trans("Search").'"></td></tr>';
print "</table></form>";
//print $langs->trans("ECMSectionAutoDesc");



print '</td></tr></table>';

print '</td><td valign="top">';

// Right area
$relativepath=$ecmdir->getRelativePath();
$upload_dir = $conf->ecm->dir_output.'/'.$relativepath;
$filearray=dol_dir_list($upload_dir,"files",0,'','\.meta$',$sortfield,(strtolower($sortorder)=='desc'?SORT_ASC:SORT_DESC),1);

$formfile=new FormFile($db);
$param='&amp;section='.$section;
$textifempty=($section?$langs->trans("NoFileFound"):$langs->trans("ECMSelectASection"));
$formfile->list_of_documents($filearray,'','ecm',$param,1,$relativepath,$user->rights->ecm->upload,1,$textifempty);

//	print '<table width="100%" class="border">';

//	print '<tr><td> </td></tr></table>';



print '</td></tr>';

print '</table>';


print '<br>';

// End of page
$db->close();

llxFooter('$Date$ - $Revision$');
?>
