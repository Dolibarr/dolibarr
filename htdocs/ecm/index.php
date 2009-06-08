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

// Envoie fichier
if ( $_POST["sendit"] && ! empty($conf->global->MAIN_UPLOAD_DOC))
{
	$result=$ecmdir->fetch($_REQUEST["section"]);
	if (! $result > 0)
	{
		dol_print_error($db,$ecmdir->error);
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
		dol_print_error($db,$ecmdir->error);
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
if ($conf->produit->enabled || $conf->service->enabled)     { $rowspan++; $sectionauto[]=array('level'=>1, 'module'=>'product', 'test'=>$conf->produit->enabled, 'label'=>$langs->trans("ProductsAndServices"),     'desc'=>$langs->trans("ECMDocsByProducts")); }
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
	$ret=$form->form_confirm($_SERVER["PHP_SELF"].'?section='.$_REQUEST["section"].'&amp;urlfile='.urldecode($_GET["urlfile"]), $langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile'), 'confirm_deletefile');
	if ($ret == 'html') print '<br>';
}

if ($mesg) { print $mesg."<br>"; }

// Tool bar
$head = ecm_prepare_head_fm($fac);
dol_fiche_head($head, 'file_manager', '', 1);


print '<table class="border" width="100%"><tr><td width="40%" valign="top">';

// Left area
print '<table class="nobordernopadding" width="100%"><tr><td valign="top">';


if (empty($action) || $action == 'file_manager' || eregi('refresh',$action) || $action == 'delete')
{
	$userstatic = new User($db);
	$ecmdirstatic = new ECMDirectory($db);

	// Confirmation de la suppression d'une ligne categorie
	if ($_GET['action'] == 'delete_section')
	{
		$ret=$form->form_confirm($_SERVER["PHP_SELF"].'?section='.urldecode($_GET["section"]), $langs->trans('DeleteSection'), $langs->trans('ConfirmDeleteSection',$ecmdir->label), 'confirm_deletesection');
		if ($ret == 'html') print '<br>';
	}

	// Construit liste des repertoires
	print '<table width="100%" class="nobordernopadding">';

	print '<tr class="liste_titre">';
	print '<td class="liste_titre" align="left">'.$langs->trans("ECMSections").'</td>';
	print '<td class="liste_titre" colspan="5" align="right">';
	print '<a href="'.$_SERVER["PHP_SELF"].'?action=refreshmanual'.($section?'&amp;section='.$section:'').'">'.img_picto($langs->trans("Refresh"),'refresh').'</a>&nbsp;';
	print '</td>';
	print '</tr>';

	if (sizeof($sectionauto))
	{
		// Root title line (Automatic section)
		print '<tr>';
		print '<td>';
		print '<table class="nobordernopadding"><tr class="nobordernopadding">';
		print '<td align="left" width="24">';
		print img_picto_common('','treemenu/base.gif');
		print '</td><td align="left">'.$langs->trans("ECMRoot").' ('.$langs->trans("ECMSectionsAuto").')';
		print '</td>';
		print '</tr></table>';
		print '</td>';
		print '<td align="right">&nbsp;</td>';
		print '<td align="right">&nbsp;</td>';
		print '<td align="right">&nbsp;</td>';
		print '<td align="right">&nbsp;</td>';
		print '<td align="center">';
		$htmltooltip=$langs->trans("ECMAreaDesc2");
		print $form->textwithpicto('',$htmltooltip,1,0);
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
				print '</td>';

				print '<td valign="top">';
				if ($val['module'] == $_REQUEST["module"])
				{
					$n=3;
					$ref=img_picto('',DOL_URL_ROOT.'/theme/common/treemenu/minustop'.$n.'.gif','',1);
				}
				else
				{
					$n=3;
					$ref=img_picto('',DOL_URL_ROOT.'/theme/common/treemenu/plustop'.$n.'.gif','',1);
				}
				print '<a href="'.DOL_URL_ROOT.'/ecm/index.php?module='.$val['module'].'">';
				print $ref;
				print '</a>';
				print img_picto('',DOL_URL_ROOT.'/theme/common/treemenu/folder.gif','',1);
				print '</td>';

				print '<td valign="middle">';
				print '<a href="'.DOL_URL_ROOT.'/ecm/index.php?module='.$val['module'].'">';
				print $val['label'];
				print '</a></td></tr></table>';
				print "</td>\n";

				// Nb of doc in dir
				print '<td align="right">&nbsp;</td>';

				// Nb of doc in subdir
				print '<td align="right">&nbsp;</td>';

				// Edit link
				print '<td align="right">&nbsp;</td>';

				// Add link
				print '<td align="right">&nbsp;</td>';

				// Info
				print '<td align="center">';
				$htmltooltip='<b>'.$langs->trans("ECMSection").'</b>: '.$val['label'].'<br>';
				$htmltooltip='<b>'.$langs->trans("Type").'</b>: '.$langs->trans("ECMSectionAuto").'<br>';
				$htmltooltip.='<b>'.$langs->trans("ECMCreationUser").'</b>: '.$langs->trans("ECMTypeAuto").'<br>';
				$htmltooltip.='<b>'.$langs->trans("Description").'</b>: '.$val['desc'];
				print $form->textwithpicto('',$htmltooltip,1,0);
				print '</td>';

				print "</tr>\n";

				// Show sublevel
				if ($val['module'] == $_REQUEST["module"])
				{
					if ($val['module'] == 'xxx')
					{
					}
					else
					{
						print '<tr><td colspan="6">'.$langs->trans("FeatureNotYetAvailable").'</td></tr>';
					}
				}



				$oldvallevel=$val['level'];
				$nbofentries++;
			}
		}
	}

	// Root title line (Manual section)
	print '<tr><td>';
	print '<table class="nobordernopadding"><tr class="nobordernopadding">';
	print '<td align="left" width="24px">';
	print img_picto_common('','treemenu/base.gif');
	print '</td><td align="left">'.$langs->trans("ECMRoot").' ('.$langs->trans("ECMSectionsManual").')';
	print '</td>';
	print '</tr></table></td>';
	print '<td align="right">';
	print '</td>';
	print '<td align="right">&nbsp;</td>';
	//print '<td align="right"><a href="'.DOL_URL_ROOT.'/ecm/docdir.php?action=create">'.img_edit_add().'</a></td>';
	print '<td align="right">&nbsp;</td>';
	print '<td align="right">&nbsp;</td>';
	print '<td align="center">';
	$htmltooltip=$langs->trans("ECMAreaDesc2");
	print $form->textwithpicto('',$htmltooltip,1,0);
	print '</td>';
	print '</tr>';



	// Load full tree
	$fulltree=$ecmdirstatic->get_full_arbo();

	// ----- This section will show a tree from a fulltree array -----
	// $section must also be defined
	// ----------------------------------------------------------------

	// Define fullpathselected ( _x_y_z ) of $section parameter
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
	$expandedsectionarray=array();
	if (isset($_SESSION['dol_ecmexpandedsectionarray'])) $expandedsectionarray=split(',',$_SESSION['dol_ecmexpandedsectionarray']);

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
		$_SESSION['dol_ecmexpandedsectionarray']=join(',',$expandedsectionarray);
	}
	if ($section && $_GET['sectionexpand'] == 'false')
	{
		// We removed all expanded sections that are child of the closed section
		$oldexpandedsectionarray=$expandedsectionarray;
		$expandedsectionarray=array();	// Reset
		foreach($oldexpandedsectionarray as $sectioncursor)
		{
			// is_in_subtree(fulltree,sectionparent,sectionchild)
			if ($sectioncursor && ! is_in_subtree($fulltree,$section,$sectioncursor)) $expandedsectionarray[]=$sectioncursor;
		}
		$_SESSION['dol_ecmexpandedsectionarray']=join(',',$expandedsectionarray);
	}
	//print $_SESSION['dol_ecmexpandedsectionarray'].'<br>';

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
			$resarray=tree_showpad($fulltree,$key);
			$a=$resarray[0];
			$nbofsubdir=$resarray[1];
			$c=$resarray[2];
			$nboffilesinsubdir=$resarray[3];
			print '</td>';

			// Show picto
			print '<td valign="top">';
			//print $val['fullpath']."(".$showline.")";
			$n='2';
			if ($b == 0 || ! in_array($val['id'],$expandedsectionarray)) $n='3';
			if (! in_array($val['id'],$expandedsectionarray)) $ref=img_picto('',DOL_URL_ROOT.'/theme/common/treemenu/plustop'.$n.'.gif','',1);
			else $ref=img_picto('',DOL_URL_ROOT.'/theme/common/treemenu/minustop'.$n.'.gif','',1);
			if ($option == 'indexexpanded') $lien = '<a href="'.$_SERVER["PHP_SELF"].'?section='.$val['id'].'&amp;sectionexpand=false">';
	    	if ($option == 'indexnotexpanded') $lien = '<a href="'.$_SERVER["PHP_SELF"].'?section='.$val['id'].'&amp;sectionexpand=true">';
	    	$newref=eregi_replace('_',' ',$ref);
	    	$lienfin='</a>';
	    	print $lien.$newref.$lienfin;
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
			print '<td align="right">';
			print $val['cachenbofdoc'];
			print '</td>';
			print '<td align="left">';
			if ($nbofsubdir && $nboffilesinsubdir) print '<font color="#AAAAAA">+'.$nboffilesinsubdir.'</font> ';
			print '</td>';

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
			$htmltooltip='<b>'.$langs->trans("Type").'</b>: '.$langs->trans("ECMSectionManual").'<br>';
			$htmltooltip.='<b>'.$langs->trans("ECMCreationUser").'</b>: '.$userstatic->getNomUrl(1).'<br>';
			$htmltooltip.='<b>'.$langs->trans("ECMCreationDate").'</b>: '.dol_print_date($val['date_c'],"dayhour").'<br>';
			$htmltooltip.='<b>'.$langs->trans("Description").'</b>: '.$val['description'].'<br>';
			$htmltooltip.='<b>'.$langs->trans("ECMNbOfFilesInDir").'</b>: '.$val['cachenbofdoc'].'<br>';
			if ($nbofsubdir) $htmltooltip.='<b>'.$langs->trans("ECMNbOfFilesInSubDir").'</b>: '.$nboffilesinsubdir;
			else $htmltooltip.='<b>'.$langs->trans("ECMNbOfSubDir").'</b>: '.$nbofsubdir.'<br>';
			print $form->textwithpicto('',$htmltooltip,1,0);
			print "</td>";

			print "</tr>\n";
		}

		$oldvallevel=$val['level'];
		$nbofentries++;
	}

	// If nothing to show
	if ($nbofentries == 0)
	{
		print '<tr>';
		print '<td class="left"><table class="nobordernopadding"><tr class="nobordernopadding"><td>'.img_picto_common('','treemenu/branchbottom.gif').'</td>';
		print '<td>'.img_picto('',DOL_URL_ROOT.'/theme/common/treemenu/minustop3.gif','',1).'</td>';
		print '<td valign="middle">';
		print $langs->trans("ECMNoDirecotyYet");
		print '</td>';
		print '<td>&nbsp;</td>';
		print '</table></td>';
		print '<td colspan="5">&nbsp;</td>';
		print '</tr>';
	}


	// ----- End of section -----
	// --------------------------


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
$formfile->list_of_documents($filearray,'','ecm',$param,1,$relativepath,$user->rights->ecm->upload,1,$textifempty,40);

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
if ($user->rights->ecm->upload && ! empty($section))
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
