<?php
/* Copyright (C) 2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
    	\file       htdoc/ecm/index.php
		\ingroup    ecm
		\brief      Main page for ECM section area
		\version    $Id$
		\author		Laurent Destailleur
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/ecm.lib.php");
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

// Load permissions
$user->getrights('ecm');

// Get parameters
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
$action = isset($_GET["action"])?$_GET["action"]:$_POST['action'];

$section=$_GET["section"];
if (! $section) $section='misc';
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
		dolibarr_print_error($db,$ecmdir->error);
		exit;
	}
}


/*******************************************************************
* ACTIONS
*
* Put here all code to do according to value of "action" parameter
********************************************************************/

// Action ajout d'un produit ou service
if ($_POST["action"] == 'add' && $user->rights->ecm->setup)
{
	$ecmdir->ref                = $_POST["ref"];
	$ecmdir->label              = $_POST["label"];
	$ecmdir->description        = $_POST["desc"];

	$id = $ecmdir->create($user);
	if ($id > 0)
	{
		Header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		$mesg='<div class="error">Error '.$langs->trans($ecmdir->error).'</div>';
		$_GET["action"] = "create";
	}
}

// Suppression fichier
if ($_POST['action'] == 'confirm_deletesection' && $_POST['confirm'] == 'yes')
{
	$result=$ecmdir->delete($user);
	$mesg = '<div class="ok">'.$langs->trans("ECMSectionWasRemoved", $ecmdir->label).'</div>';
}




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


print '<table class="notopnoleftnoright" width="100%"><tr><td width="50%">';

// Left area


// Tool bar
$selected='file_manager';
if (eregi('search',$action)) $selected='search_form';
$head = ecm_prepare_head_fm($fac);
dolibarr_fiche_head($head, $selected, '', 1);

print '<table class="noborder" width="100%"><tr><td>';

if (eregi('search',$action))
{
	//print_fiche_titre($langs->trans("ECMManualOrg"));
	
	print '<form method="post" action="'.DOL_URL_ROOT.'/ecm/search.php">';
	print '<table class="noborder" width="100%">';
	print "<tr class=\"liste_titre\">";
	print '<td colspan="3">'.$langs->trans("ECMSearchByKeywords").'</td></tr>';
	print "<tr ".$bc[false]."><td>".$langs->trans("Ref").':</td><td><input type="text" name="search_ref" class="flat" size="18"></td>';
	print '<td rowspan="3"><input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
	print "<tr ".$bc[false]."><td>".$langs->trans("Title").':</td><td><input type="text" name="search_title" class="flat" size="18"></td></tr>';
	print "<tr ".$bc[false]."><td>".$langs->trans("Keyword").':</td><td><input type="text" name="search_keyword" class="flat" size="18"></td></tr>';
	print "</table></form>";
	//print $langs->trans("ECMManualOrgDesc");
		
	//print_fiche_titre($langs->trans("ECMAutoOrg"));
	
	print '<form method="post" action="'.DOL_URL_ROOT.'/ecm/search.php">';
	print '<table class="noborder" width="100%">';
	print "<tr class=\"liste_titre\">";
	print '<td colspan="4">'.$langs->trans("ECMSearchByEntity").'</td></tr>';
	
	$buthtml='<td rowspan="'.$rowspan.'"><input type="submit" value="'.$langs->trans("Search").'" class="button"></td>';
	$butshown=0;
	foreach($sectionauto as $section)
	{
		if (! $section['test']) continue;
		if ($butshown % 2 == 0) print '<tr '. $bc[false].'>';
		print "<td>".$section['label'].':</td>';
		print '<td';
		if ($butshown % 2 == 1) print ' align="right"';
		print '>';
		print '<input type="text" name="search_'.$section['module'].'" class="flat" size="6">';
		print '</td>';
		if ($butshown % 2 == 1) print '</tr>';
		$butshown++;
	}
	if ($butshown % 2 == 1) print '<td>&nbsp;</td><td>&nbsp;</td></tr>';
	
	print '<tr '. $bc[false].'><td colspan="4" align="center"><input type="submit" class="button" value="'.$langs->trans("Search").'"></td></tr>';
	print "</table></form>";
	//print $langs->trans("ECMAutoOrgDesc");
}


if (empty($action) || $action == 'file_manager' || eregi('refresh',$action))
{
	// Confirmation de la suppression d'une ligne categorie
	if ($_GET['action'] == 'delete_section')
	{
		$form->form_confirm($_SERVER["PHP_SELF"].'?section='.urldecode($_GET["section"]), $langs->trans('DeleteSection'), $langs->trans('ConfirmDeleteSection',$ecmdir->label), 'confirm_deletesection');
		print '<br>';
	}
	
	if ($mesg) { print $mesg."<br>"; }
	
	
	// Construit liste des répertoires
	print '<table width="100%" class="nobordernopadding">';
	
	print '<tr class="liste_titre">';
	print '<td class="liste_titre" colspan="5" align="right">';
	print '<a href="'.$_SERVER["PHP_SELF"].'?action=refreshmanual">'.$langs->trans("Refresh").' '.img_picto($langs->trans("Refresh"),'refresh').'</a>';
	print '</td>';
	print '</tr>';
	
	if (sizeof($sectionauto))
	{
		// Automatic sections title line
		print '<tr '.$bc[false].'><td>';
		print '<table class="nobordernopadding"><tr class="nobordernopadding">';
		print '<td align="left" width="24px">';
		print img_picto_common('','treemenu/base.gif');
		print '</td><td align="left">'.$langs->trans("ECMRoot").' ('.$langs->trans("ECMSectionAuto").')';
		print '</td>';
		print '</tr></table></td>';
		print '<td align="right">&nbsp;</td>';
		print '<td align="right">&nbsp;</td>';
		print '<td align="right">&nbsp;</td>';
		print '<td align="center">';
		$htmltooltip=$langs->trans("ECMAreaDesc2");
		print $form->textwithhelp('',$htmltooltip,1,0);
		print '</td>';
		//print '<td align="right">'.$langs->trans("ECMNbOfDocsSmall").' <a href="'.$_SERVER["PHP_SELF"].'?action=refreshauto">'.img_picto($langs->trans("Refresh"),'refresh').'</a></td>';
		print '</tr>';
		
		$sectionauto=dol_sort_array($sectionauto,'label',$sortorder,true,false);
		
		$nbofentries=0;
		$oldvallevel=0;
		foreach ($sectionauto as $key => $val)
		{
			if ($val['test'])
			{
				$var=false;
	
				print '<tr '.$bc[$var].'>';
					
				// Section
				print '<td align="left">';
				print '<table class="nobordernopadding"><tr class="nobordernopadding"><td>';
				print tree_showpad($sectionauto,$key);
				print '</td><td valign="top">';
				print img_picto('','object_dir');
				print '</td><td>';
				print '&nbsp; <a href="'.DOL_URL_ROOT.'/ecm/docother.php">';
				print $val['label'];
				print '</a></td></tr></table>';
				print "</td>\n";
					
				// Nb of doc
				print '<td align="right">&nbsp;</td>';

				// Edit link
				print '<td align="right">&nbsp;</td>';
				
				// Add link
				print '<td align="right">&nbsp;</td>';
				
				// Info
				print '<td align="center">';
				$htmltooltip='<b>'.$langs->trans("ECMSection").'</b>: '.$val['label'].'<br>';
				$htmltooltip='<b>'.$langs->trans("Type").'</b>: '.$langs->trans("ECMAutoOrg").'<br>';
				$htmltooltip.='<b>'.$langs->trans("ECMCreationUser").'</b>: '.$langs->trans("ECMTypeAuto").'<br>';
				$htmltooltip.='<b>'.$langs->trans("Description").'</b>: '.$val['desc'];
				print $form->textwithhelp('',$htmltooltip,1,0);
				print '</td>';
				
				print "</tr>\n";
				
				$oldvallevel=$val['level'];
				$nbofentries++;				
			}
		}
	}
	
	// Manual sections title line
	print '<tr '.$bc[false].'><td>';
	print '<table class="nobordernopadding"><tr class="nobordernopadding">';
	print '<td align="left" width="24px">';
	print img_picto_common('','treemenu/base.gif');
	print '</td><td align="left">'.$langs->trans("ECMRoot").' ('.$langs->trans("ECMSectionManual").')';
	print '</td>';
	print '</tr></table></td>';
	print '<td align="right">';
	print '</td>';
	print '<td align="right">&nbsp;</td>';
	print '<td align="right"><a href="'.DOL_URL_ROOT.'/ecm/docdir.php?action=create">'.img_edit_add().'</a></td>';
	print '<td align="center">';
	$htmltooltip=$langs->trans("ECMAreaDesc2");
	print $form->textwithhelp('',$htmltooltip,1,0);
	print '</td>';
	print '</tr>';
	
	$ecmdirstatic = new ECMDirectory($db);
	$rub=$ecmdirstatic->get_full_arbo();
	
	$userstatic = new User($db);
	
	$nbofentries=0;
	$oldvallevel=0;
	$var=true;
	foreach($rub as $key => $val)
	{
		$var=false;
			
		$ecmdirstatic->id=$val['id'];
		$ecmdirstatic->ref=$val['label'];
	
		// Refresh cache
		if (eregi('refresh',$_GET['action']))
		{
			$result=$ecmdirstatic->fetch($val['id']);
			$ecmdirstatic->ref=$ecmdirstatic->label;
	
			$result=$ecmdirstatic->refreshcachenboffile();
			$val['cachenbofdoc']=$result;
		}
		
	
		print '<tr '.$bc[$var].'>';
			
		// Section
		print '<td align="left">';
		print '<table class="nobordernopadding"><tr class="nobordernopadding"><td>';
		print tree_showpad($rub,$key);
		print '</td><td valign="top">';
		print $ecmdirstatic->getNomUrl(1,'index');
		print '</td><td>';
		print '&nbsp;</td></tr></table>';
		print "</td>\n";
	
		// Nb of docs
		print '<td align="right">'.$val['cachenbofdoc'].'</td>';
		
		// Edit link
		print '<td align="right"><a href="'.DOL_URL_ROOT.'/ecm/docmine.php?section='.$val['id'].'">'.img_edit().'</a></td>';
		
		// Add link
		print '<td align="right"><a href="'.DOL_URL_ROOT.'/ecm/docdir.php?action=create&amp;catParent='.$val['id'].'">'.img_edit_add().'</a></td>';
		
		// Info
		print '<td align="center">';
		$userstatic->id=$val['fk_user_c'];
		$userstatic->nom=$val['login_c'];
		$htmltooltip='<b>'.$langs->trans("ECMSection").'</b>: '.$val['label'].'<br>';
		$htmltooltip='<b>'.$langs->trans("Type").'</b>: '.$langs->trans("ECMManualOrg").'<br>';
		$htmltooltip.='<b>'.$langs->trans("ECMCreationUser").'</b>: '.$userstatic->getNomUrl(1).'<br>';
		$htmltooltip.='<b>'.$langs->trans("ECMCreationDate").'</b>: '.dolibarr_print_date($val['date_c'],"dayhour").'<br>';
		$htmltooltip.='<b>'.$langs->trans("Description").'</b>: '.$val['description'];
		print $form->textwithhelp('',$htmltooltip,1,0);
		print "</td>";
		
		print "</tr>\n";
		
		$oldvallevel=$val['level'];
		$nbofentries++;
	}
	
	// If nothing to show	
	if ($nbofentries == 0)
	{
		print '<tr '.$bc[false].'><td colspan="6">'.$langs->trans("ECMNoDirecotyYet").'</td></tr>';
	}
	
	print "</table>";
	// Fin de zone Ajax

// Actions buttons
print '<div class="tabsAction">';
if ($user->rights->ecm->setup)
{
	print '<a class="butAction" href="'.DOL_URL_ROOT.'/ecm/docdir.php?action=create">'.$langs->trans('ECMAddSection').'</a>';
}
else
{
	print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans('ECMAddSection').'</a>';
}
print '</div>';
	

}

print '</td></tr></table>';

print '</td><td>';

// Right area





print '</td></tr></table>';


print '<br>';

// End of page
$db->close();

llxFooter('$Date$ - $Revision$');
?>
