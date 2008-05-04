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
if ($conf->societe->enabled)  { $rowspan++; $sectionauto[]=array('module'=>'company', 'test'=>$conf->societe->enabled, 'label'=>$langs->trans("ThirdParties"), 'desc'=>$langs->trans("ECMDocsByThirdParties")); }
if ($conf->propal->enabled)   { $rowspan++; $sectionauto[]=array('module'=>'propal',  'test'=>$conf->propal->enabled,  'label'=>$langs->trans("Prop"),    'desc'=>$langs->trans("ECMDocsByProposals")); }
if ($conf->commande->enabled) { $rowspan++; $sectionauto[]=array('module'=>'order',   'test'=>$conf->commande->enabled,'label'=>$langs->trans("Orders"),       'desc'=>$langs->trans("ECMDocsByOrders")); }
if ($conf->contrat->enabled)  { $rowspan++; $sectionauto[]=array('module'=>'contract','test'=>$conf->contrat->enabled, 'label'=>$langs->trans("Contracts"),    'desc'=>$langs->trans("ECMDocsByContracts")); }
if ($conf->facture->enabled)  { $rowspan++; $sectionauto[]=array('module'=>'invoice', 'test'=>$conf->facture->enabled, 'label'=>$langs->trans("Invoices"),     'desc'=>$langs->trans("ECMDocsByInvoices")); }
if ($conf->produit->enabled)  { $rowspan++; $sectionauto[]=array('module'=>'product', 'test'=>$conf->produit->enabled, 'label'=>$langs->trans("ProductsAndServices"),     'desc'=>$langs->trans("ECMDocsByProducts")); }


//***********************
// List
//***********************
print_fiche_titre($langs->trans("ECMArea"));

print $langs->trans("ECMAreaDesc")."<br>";
print $langs->trans("ECMAreaDesc2")."<br>";
print "<br>\n";

print '<table class="notopnoleftnoright" width="100%"><tr><td width="50%" valign="top">';

//print_fiche_titre($langs->trans("ECMManualOrg"));

print '<form method="post" action="'.DOL_URL_ROOT.'/ecm/search.php">';
print '<table class="noborder" width="100%">';
print "<tr class=\"liste_titre\">";
print '<td colspan="3">'.$langs->trans("ECMSearchByKeywords").'</td></tr>';
print "<tr $bc[0]><td>".$langs->trans("Title").':</td><td><input type="text" name="sf_ref" class="flat" size="18"></td>';
print '<td rowspan="2"><input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
print "<tr $bc[0]><td>".$langs->trans("Keyword").':</td><td><input type="text" name="sall" class="flat" size="18"></td>';
print '</tr>';
print "</table></form><br>";
//print $langs->trans("ECMManualOrgDesc");
	
print '</td><td width="50%" valign="top">';

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
	if ($butshown % 2 == 0) print '<tr '. $bc[0].'>';
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

print '<tr '. $bc[0].'><td colspan="4" align="center"><input type="submit" class="button" value="'.$langs->trans("Search").'"></td></tr>';
print "</table></form><br>";
//print $langs->trans("ECMAutoOrgDesc");
	
print '</td></tr>';
print '</table>';


//***********************
// Files
//***********************
print_fiche_titre($langs->trans("ECMSectionOfDocuments"));
//print '<br>';

// Confirmation de la suppression d'une ligne categorie
if ($_GET['action'] == 'delete_section')
{
	$form->form_confirm($_SERVER["PHP_SELF"].'?section='.urldecode($_GET["section"]), $langs->trans('DeleteSection'), $langs->trans('ConfirmDeleteSection',$ecmdir->label), 'confirm_deletesection');
	print '<br>';
}

if ($mesg) { print $mesg."<br>"; }


// Construit liste des répertoires
print '<table width="100%" class="noborder">';

if (sizeof($sectionauto))
{
	// Automatic sections
	print '<tr class="liste_titre">';
	print '<td class="liste_titre" align="left">'.$langs->trans("ECMSectionAuto").'</td>';
	print '<td class="liste_titre" align="left">'.$langs->trans("Description").'</td>';
	print '<td class="liste_titre" align="right">'.$langs->trans("ECMNbOfDocsSmall").'</td>';
	print '<td class="liste_titre">';
	print '&nbsp;';
	print '</td>';
	print '</tr>';
	
	$sectionauto=dol_sort_array($sectionauto,'label',$sortorder,true,false);
	
	$var=true;
	foreach ($sectionauto as $key => $val)
	{
		if ($val['test'])
		{
			$var=! $var;

			print '<tr '.$bc[$var].'>';
				
			// Section
			print '<td align="left">';
			print img_picto('','object_dir').' ';
			print '<a href="'.DOL_URL_ROOT.'/ecm/docother.php">';
			print $val['label'];
			print '</a>';
			print "</td>\n";
				
			// Description
			print '<td align="left">'.$val['desc'].'</td>';
			print '<td align="right">?</td>';
			print '<td align="right">';
			$htmltooltip='<b>'.$langs->trans("ECMSection").'</b>: '.$val['label'].'<br>';
			$htmltooltip='<b>'.$langs->trans("Type").'</b>: '.$langs->trans("ECMAutoOrg").'<br>';
			$htmltooltip.='<b>'.$langs->trans("ECMCreationUser").'</b>: '.$langs->trans("ECMTypeAuto");
			print $form->textwithhelp('',$htmltooltip,1,0);
			print '</td>';
			print "</tr>\n";
		}
	}
}


// Manual sections

print '<tr class="liste_titre">';
$param='&amp;socid='.$socid;

print '<td class="liste_titre" align="left">'.$langs->trans("ECMSectionManual").'</td>';
print '<td class="liste_titre" align="left">'.$langs->trans("Description").'</td>';
print '<td class="liste_titre" align="right">'.$langs->trans("ECMNbOfDocsSmall");
print '<a href="'.$_SERVER["PHP_SELF"].'?action=refreshauto">'.img_picto($langs->trans("Refresh"),'refresh').'</a>';
print '</td>';
print '<td class="liste_titre" align="right">';
if ($user->rights->ecm->setup)
{
	print '<a href="'.DOL_URL_ROOT.'/ecm/docdir?action=create">'.img_picto($langs->trans("ECMNewSection"),'edit_add').'</a>';
}
else
{
	print '&nbsp;';
}
print '</td>';
print '</tr>';

$ecmdirstatic = new ECMDirectory($db);
$rub=$ecmdirstatic->get_full_arbo();

$userstatic = new User($db);

$nbofentries=0;
$var=true;
foreach($rub as $key => $val)
{
	$var=!$var;
		
	$ecmdirstatic->id=$val['id'];
	$ecmdirstatic->ref=$val['label'];

	// Refresh cache
	if ($_GET['action'] == 'refreshauto')
	{
		$result=$ecmdirstatic->fetch($val['id']);
		$ecmdirstatic->ref=$ecmdirstatic->label;

		$result=$ecmdirstatic->refreshcachenboffile();
		$val['cachenbofdoc']=$result;
	}
	

	print '<tr '.$bc[$var].'>';
		
	// Section
	print '<td align="left">';
	print str_repeat(' &nbsp; &nbsp; ',$val['level']-1);
	print $ecmdirstatic->getNomUrl(1);
	print "</td>\n";

	// Description
	print '<td align="left">'.dolibarr_trunc($val['description'],32).'</td>';
		
	// Nb of docs
	//print '<td align="right">'.$obj->cachenbofdoc.'</td>';
	print '<td align="right">'.$val['cachenbofdoc'].'</td>';
	
	print '<td align="right">';
	$userstatic->id=$val['fk_user_c'];
	$userstatic->nom=$val['login_c'];
	$htmltooltip='<b>'.$langs->trans("ECMSection").'</b>: '.$val['label'].'<br>';
	$htmltooltip='<b>'.$langs->trans("Type").'</b>: '.$langs->trans("ECMManualOrg").'<br>';
	$htmltooltip.='<b>'.$langs->trans("ECMCreationUser").'</b>: '.$userstatic->getNomUrl(1).'<br>';
	$htmltooltip.='<b>'.$langs->trans("ECMCreationDate").'</b>: '.dolibarr_print_date($val['date_c'],"dayhour");
	print $form->textwithhelp('',$htmltooltip,1,0);
	print "</td></tr>\n";
	
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
/*
print '<div class="tabsAction">';
if ($user->rights->ecm->setup)
{
	print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=create">'.$langs->trans('ECMAddSection').'</a>';
}
else
{
	print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans('ECMAddSection').'</a>';
}
print '</div>';
*/

print '<br>';

// End of page
$db->close();

llxFooter('$Date$ - $Revision$');
?>
