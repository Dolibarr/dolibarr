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
 *	\file       htdoc/ecm/index.php
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
		dolibarr_print_error($db,$ecmdir->error);
		exit;
	}
}


/*******************************************************************
 * ACTIONS
 *
 * Put here all code to do according to value of "action" parameter
 ********************************************************************/

// Envoie fichier
if ( $_POST["sendit"] && $conf->upload != 0)
{
	$result=$ecmdir->fetch($_REQUEST["section"]);
	if (! $result > 0)
	{
		dolibarr_print_error($db,$ecmdir->error);
		exit;
	}
	$relativepath=$ecmdir->getRelativePath();
	$upload_dir = $conf->ecm->dir_output.'/'.$relativepath;
	
	if (! is_dir($upload_dir))
	{
		$result=create_exdir($upload_dir);
	}
	 
	if (is_dir($upload_dir))
	{
		$result = dol_move_uploaded_file($_FILES['userfile']['tmp_name'], $upload_dir . "/" . $_FILES['userfile']['name'],0);
		if ($result > 0)
		{
			//$mesg = '<div class="ok">'.$langs->trans("FileTransferComplete").'</div>';
			//print_r($_FILES);
			$result=$ecmdir->changeNbOfFiles('+');
		}
		else if ($result < 0)
		{
			// Echec transfert (fichier depassant la limite ?)
			$langs->load("errors");
			$mesg = '<div class="error">'.$langs->trans("ErrorFileNotUploaded").'</div>';
			// print_r($_FILES);
		}
		else
		{
			// File infected by a virus
			$langs->load("errors");
			$mesg = '<div class="error">'.$langs->trans("ErrorFileIsInfectedWith",$result).'</div>';
		}
	}
	else
	{
		// Echec transfert (fichier depassant la limite ?)
		$langs->load("errors");
		$mesg = '<div class="error">'.$langs->trans("ErrorFailToCreateDir",$upload_dir).'</div>';
	}
}

// Remove file
if ($_POST['action'] == 'confirm_deletefile' && $_POST['confirm'] == 'yes')
{
	$result=$ecmdir->fetch($_REQUEST["section"]);
	if (! $result > 0)
	{
		dolibarr_print_error($db,$ecmdir->error);
		exit;
	}
	$relativepath=$ecmdir->getRelativePath();
	$upload_dir = $conf->ecm->dir_output.'/'.$relativepath;
	$file = $upload_dir . "/" . urldecode($_GET["urlfile"]);

	$result=dol_delete_file($file);

	$mesg = '<div class="ok">'.$langs->trans("FileWasRemoved").'</div>';

	$result=$ecmdir->changeNbOfFiles('-');
	$action='file_manager';
}

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

// Confirm remove file
if ($_GET['action'] == 'delete')
{
	$form->form_confirm($_SERVER["PHP_SELF"].'?section='.$_REQUEST["section"].'&amp;urlfile='.urldecode($_GET["urlfile"]), $langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile'), 'confirm_deletefile');
	print '<br>';
}

if ($mesg) { print $mesg."<br>"; }

// Tool bar
$selected='file_manager';
if (eregi('search',$action)) $selected='search_form';
$head = ecm_prepare_head_fm($fac);
dolibarr_fiche_head($head, $selected, '', 1);


print '<table class="border" width="100%"><tr><td width="40%" valign="top">';

// Left area
print '<table class="nobordernopadding" width="100%"><tr><td valign="top">';

if (eregi('search',$action))
{
	//print_fiche_titre($langs->trans("ECMManualOrg"));

	print '<form method="post" action="'.DOL_URL_ROOT.'/ecm/search.php">';
	print '<table class="nobordernopadding" width="100%">';
	print "<tr class=\"liste_titre\">";
	print '<td colspan="2">'.$langs->trans("ECMSearchByKeywords").'</td></tr>';
	print "<tr ".$bc[false]."><td>".$langs->trans("Ref").':</td><td align="right"><input type="text" name="search_ref" class="flat" size="14"></td></tr>';
	print "<tr ".$bc[false]."><td>".$langs->trans("Title").':</td><td align="right"><input type="text" name="search_title" class="flat" size="14"></td></tr>';
	print "<tr ".$bc[false]."><td>".$langs->trans("Keyword").':</td><td align="right"><input type="text" name="search_keyword" class="flat" size="14"></td></tr>';
	print "<tr ".$bc[false].'><td colspan="2" align="center"><input type="submit" class="button" value="'.$langs->trans("Search").'"></td></tr>';
	print "</table></form>";
	//print $langs->trans("ECMManualOrgDesc");

	//print_fiche_titre($langs->trans("ECMAutoOrg"));

	print '<form method="post" action="'.DOL_URL_ROOT.'/ecm/search.php">';
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
	//print $langs->trans("ECMAutoOrgDesc");
}


if (empty($action) || $action == 'file_manager' || eregi('refresh',$action) || $action == 'delete')
{
	$userstatic = new User($db);
	$ecmdirstatic = new ECMDirectory($db);

	// Confirmation de la suppression d'une ligne categorie
	if ($_GET['action'] == 'delete_section')
	{
		$form->form_confirm($_SERVER["PHP_SELF"].'?section='.urldecode($_GET["section"]), $langs->trans('DeleteSection'), $langs->trans('ConfirmDeleteSection',$ecmdir->label), 'confirm_deletesection');
		print '<br>';
	}

	// Construit liste des répertoires
	print '<table width="100%" class="nobordernopadding">';

	print '<tr class="liste_titre">';
	print '<td class="liste_titre" align="left">'.$langs->trans("ECMSections").'</td>';
	print '<td class="liste_titre" colspan="4" align="right">';
	print '<a href="'.$_SERVER["PHP_SELF"].'?action=refreshmanual'.($section?'&amp;section='.$section:'').'">'.$langs->trans("Refresh").' '.img_picto($langs->trans("Refresh"),'refresh').'</a>';
	print '</td>';
	print '</tr>';

	if (sizeof($sectionauto))
	{
		// Automatic sections title line
		print '<tr>';
		print '<td>';
		print '<table class="nobordernopadding"><tr class="nobordernopadding">';
		print '<td align="left" width="24px">';
		print img_picto_common('','treemenu/base.gif');
		print '</td><td align="left">'.$langs->trans("ECMRoot").' ('.$langs->trans("ECMSectionAuto").')';
		print '</td>';
		print '</tr></table>';
		print '</td>';
		print '<td align="right">&nbsp;</td>';
		print '<td align="right">&nbsp;</td>';
		print '<td align="right">&nbsp;</td>';
		print '<td align="center">';
		$htmltooltip=$langs->trans("ECMAreaDesc2");
		print $form->textwithhelp('',$htmltooltip,1,0);
		print '</td>';
		//print '<td align="right">'.$langs->trans("ECMNbOfDocsSmall").' <a href="'.$_SERVER["PHP_SELF"].'?action=refreshauto">'.img_picto($langs->trans("Refresh"),'refresh').'</a></td>';
		print '</tr>';

		$sectionauto=dol_sort_array($sectionauto,'label','ASC',true,false);

		$nbofentries=0;
		$oldvallevel=0;
		foreach ($sectionauto as $key => $val)
		{
			if ($val['test'])
			{
				$var=false;

				print '<tr>';
					
				// Section
				print '<td align="left">';
				print '<table class="nobordernopadding"><tr class="nobordernopadding"><td>';
				tree_showpad($sectionauto,$key);
				print '</td><td valign="top">';
				print img_picto('','object_dir');
				print '</td><td valign="middle">&nbsp;';
				print '<a href="'.DOL_URL_ROOT.'/ecm/docother.php">';
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
	print '<tr><td>';
	print '<table class="nobordernopadding"><tr class="nobordernopadding">';
	print '<td align="left" width="24px">';
	print img_picto_common('','treemenu/base.gif');
	print '</td><td align="left">'.$langs->trans("ECMRoot").' ('.$langs->trans("ECMSectionManual").')';
	print '</td>';
	print '</tr></table></td>';
	print '<td align="right">';
	print '</td>';
	print '<td align="right">&nbsp;</td>';
	//print '<td align="right"><a href="'.DOL_URL_ROOT.'/ecm/docdir.php?action=create">'.img_edit_add().'</a></td>';
	print '<td align="right">&nbsp;</td>';
	print '<td align="center">';
	$htmltooltip=$langs->trans("ECMAreaDesc2");
	print $form->textwithhelp('',$htmltooltip,1,0);
	print '</td>';
	print '</tr>';

	// Load full tree
	$fulltree=$ecmdirstatic->get_full_arbo();

	// Define fullpathselected ( _x_y_z )
	$fullpathselected='';
	foreach($fulltree as $key => $val)
	{
		//print $val['id']."-".$section."<br>";
		if ($val['id'] == $section)
		{
			$fullpathselected=$val['fullpath'];
			break;
		}
	}
	//print "fullpathselected=".$fullpathselected."<br>";

	// Update expandedsectionarray in session
	$expandedsectionarray=split(',',$_SESSION['expandedsectionarray']);
	if ($section && $_GET['sectionexpand'] == 'true')
	{
		// We add all sections that are parent of opened section
		$pathtosection=split('_',$fullpathselected);
		foreach($pathtosection as $idcursor)
		{
			if ($idcursor && ! in_array($idcursor,$expandedsectionarray))	// Not already in array
			{
				$expandedsectionarray[]=$idcursor;
			}
		}
		$_SESSION['expandedsectionarray']=join(',',$expandedsectionarray);
	}
	if ($section && $_GET['sectionexpand'] == 'false')
	{
		// We removed all expanded sections that are child of the closed section
		$oldexpandedsectionarray=$expandedsectionarray;
		$expandedsectionarray=array();
		foreach($oldexpandedsectionarray as $sectioncursor)
		{
			// is_in_subtree(fulltree,sectionparent,sectionchild)
			if ($sectioncursor && ! is_in_subtree($fulltree,$section,$sectioncursor)) $expandedsectionarray[]=$sectioncursor;
		}
		$_SESSION['expandedsectionarray']=join(',',$expandedsectionarray);
	}
	//print $_SESSION['expandedsectionarray'].'<br>';
			
	$nbofentries=0;
	$oldvallevel=0;
	$var=true;
	foreach($fulltree as $key => $val)
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

		//$fullpathparent=eregi_replace('_[^_]+$','',$val['fullpath']);
		
		// Define showline
		$showline=0;
		
		// If directory is son of expanded directory, we show line
		if (in_array($val['id_mere'],$expandedsectionarray)) $showline=4;
		// If directory is brother of selected directory, we show line
		elseif ($val['id'] != $section && $val['id_mere'] == $ecmdirstatic->motherof[$section]) $showline=3;
		// If directory is parent of selected directory or is selected directory, we show line
		elseif (eregi($val['fullpath'].'_',$fullpathselected.'_')) $showline=2;
		// If we are level one we show line
		elseif ($val['level'] < 2) $showline=1;

		if ($showline)
		{
			if (in_array($val['id'],$expandedsectionarray)) $option='indexexpanded';
			else $option='indexnotexpanded'; 
			//print $option;
			
			print '<tr>';

			// Show tree graph pictos
			print '<td align="left">';
			print '<table class="nobordernopadding"><tr class="nobordernopadding"><td>';
			tree_showpad($fulltree,$key);
			print '</td>';
			// Show picto
			print '<td valign="top">';
			//print $val['fullpath']."(".$showline.")";
			if (! in_array($val['id'],$expandedsectionarray)) $ref=img_picto('',DOL_URL_ROOT.'/theme/common/treemenu/plustop2.gif','',1);
			else $ref=img_picto('',DOL_URL_ROOT.'/theme/common/treemenu/minustop2.gif','',1);
			$oldref=$ecmdirstatic->ref;
			$ecmdirstatic->ref=$ref;
			print $ecmdirstatic->getNomUrl(0,$option);
			$ecmdirstatic->ref=$oldref;
			if (! in_array($val['id'],$expandedsectionarray)) print img_picto('',DOL_URL_ROOT.'/theme/common/treemenu/folder.gif','',1);
			else print img_picto('',DOL_URL_ROOT.'/theme/common/treemenu/folder-expanded.gif','',1);
			print '</td>';
			// Show link
			print '<td valign="middle">';
			if ($section == $val['id']) print ' <u>';
			print $ecmdirstatic->getNomUrl(0,'index');
			if ($section == $val['id']) print '</u>';
			print '</td>';
			print '<td>&nbsp;</td>';
			print '</tr></table>';
			print "</td>\n";

			// Nb of docs
			print '<td align="right">'.$val['cachenbofdoc'].'</td>';
				
			// Edit link
			print '<td align="right"><a href="'.DOL_URL_ROOT.'/ecm/docmine.php?section='.$val['id'].'">'.img_edit().'</a></td>';
				
			// Add link
			//print '<td align="right"><a href="'.DOL_URL_ROOT.'/ecm/docdir.php?action=create&amp;catParent='.$val['id'].'">'.img_edit_add().'</a></td>';
			print '<td align="right">&nbsp;</td>';
				
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
		}

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

}

print '</td></tr></table>';

print '</td><td valign="top">';

// Right area
$relativepath=$ecmdir->getRelativePath();
$upload_dir = $conf->ecm->dir_output.'/'.$relativepath;
$filearray=dol_dir_list($upload_dir,"files",0,'','\.meta$',$sortfield,(strtolower($sortorder)=='desc'?SORT_ASC:SORT_DESC),1);

$formfile=new FormFile($db);
$param='&amp;section='.$section;
$textifempty=($section?$langs->trans("NoFileFound"):$langs->trans("ECMSelectASection"));
$formfile->list_of_documents($filearray,'','ecm',$param,1,$relativepath,$user->rights->ecm->create,1,$textifempty);

//	print '<table width="100%" class="border">';

//	print '<tr><td> </td></tr></table>';



print '</td></tr>';


// Actions buttons
print '<tr height="22"><td align="center">';
if (empty($action) || $action == 'file_manager' || eregi('refresh',$action))
{
	if ($user->rights->ecm->setup)
	{
		print '<a class="butAction" href="'.DOL_URL_ROOT.'/ecm/docdir.php?action=create">'.$langs->trans('ECMAddSection').'</a>';
	}
	else
	{
		print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans('ECMAddSection').'</a>';
	}
}
print '</td><td>';
if ($user->rights->ecm->create && ! empty($section))
{
	$formfile->form_attach_new_file(DOL_URL_ROOT.'/ecm/index.php','',0,$section,1);
}
else print '&nbsp;';
print '</td></tr>';

print '</table>';


print '<br>';

// End of page
$db->close();

llxFooter('$Date$ - $Revision$');
?>
