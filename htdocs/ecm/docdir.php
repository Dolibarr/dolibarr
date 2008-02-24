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
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="label";

$ecmdir = new ECMDirectory($db);
if (! empty($_GET["section"]))
{
	$result=$ecmdir->fetch($_GET["section"]);
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
	
if ($_GET["action"] == 'create')
{
	//***********************
	// Create
	//***********************
	print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
	print '<input type="hidden" name="action" value="add">';
	
	$title=$langs->trans("ECMNewSection");
	print_fiche_titre($title);
	if ($mesg) { print $mesg."<br>"; }
	
	print '<table class="border" width="100%">';

	// Label
	print '<tr><td>'.$langs->trans("Label").'</td><td><input name="label" size="40" value="'.$ecmdir->label.'"></td></tr>';

	// Description
	print '<tr><td valign="top">'.$langs->trans("Description").'</td><td>';
	print '<textarea name="desc" rows="4" cols="90">';
	print $ecmdir->description;
	print '</textarea>';
	print "</td></tr>";
	
	print '<tr><td colspan="3" align="center"><input type="submit" class="button" value="'.$langs->trans("Create").'"></td></tr>';

	print '</table>';
	print '</form>';
}


if (! $_GET["action"] || $_GET["action"] == 'delete_section')
{
	//***********************
	// List
	//***********************
	print_fiche_titre($langs->trans("ECMSectionOfDocuments"));
	
	$ecmdir->ref=$ecmdir->label;
	print $langs->trans("ECMSection").': ';
	print img_picto('','object_dir').' ';
	print '<a href="'.DOL_URL_ROOT.'/ecm/docdir.php">'.$langs->trans("ECMRoot").'</a>';
	//print ' -> <b>'.$ecmdir->getNomUrl(1).'</b><br>';
	print "<br><br>";

	/*
	* Confirmation de la suppression d'une ligne categorie
	*/
	if ($_GET['action'] == 'delete_section')
	{
		$form->form_confirm($_SERVER["PHP_SELF"].'?section='.urldecode($_GET["section"]), $langs->trans('DeleteSection'), $langs->trans('ConfirmDeleteSection',$ecmdir->label), 'confirm_deletesection');
		print '<br>';
	}

	// Construit liste des répertoires

	if ($mesg) { print $mesg."<br>"; }

	print '<table width="100%" class="noborder">';
	print '<tr class="liste_titre">';
	$param='&amp;socid='.$socid;
	print_liste_field_titre($langs->trans("ECMSection"),$_SERVER["PHP_SELF"],"label","",$param,'align="left"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Type"),$_SERVER["PHP_SELF"],"","",$param,'align="left"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Description"),$_SERVER["PHP_SELF"],"description","",$param,'align="left"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("ECMCreationUser"),$_SERVER["PHP_SELF"],"fk_user_c","",$param,'align="left"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("ECMCreationDate"),$_SERVER["PHP_SELF"],"date_c","",$param,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("ECMNbOfDocs"),$_SERVER["PHP_SELF"],"cachenbofdoc","",$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Action"),$_SERVER["PHP_SELF"],"","",$param,'align="right"',$sortfield,$sortorder);
	print '</tr>';


	$sql ="SELECT ed.rowid, ed.label, ed.description, ed.cachenbofdoc, ed.fk_user_c, ed.fk_user_m,";
	$sql.=" ed.date_c,";
	$sql.=" ed.date_m,";
	$sql.=" ed.fk_parent,";
	$sql.=" u.login";
	$sql.=" FROM ".MAIN_DB_PREFIX."ecm_directories as ed";
	$sql.=" LEFT JOIN ".MAIN_DB_PREFIX."user as u on u.rowid = ed.fk_user_c";
	$sql.=" ORDER BY label";
	$resql=$db->query($sql);
	if ($resql)
	{	
		$var=true;
		$obj = $db->fetch_object($resql);
		while ($obj)
		{
			$var=!$var;
			
			$ecmdirstatic->id=$obj->rowid;
			$ecmdirstatic->ref=$obj->label;
			
			print '<tr '.$bc[$var].'>';
			
			// Section
			print '<td align="left">';
			print $ecmdirstatic->getNomUrl(1);
			print "</td>\n";
			
			// Type
			print '<td align="left">';
			print $langs->trans("ECMTypeManual");
			print "</td>\n";
			
			// Description
			print '<td align="left">'.dolibarr_trunc($obj->description,32).'</td>';
			$userstatic->id=$obj->fk_user_c;
			$userstatic->nom=$obj->login;
			print '<td align="left">'.$userstatic->getNomUrl(1).'</td>';
			print '<td align="center">'.dolibarr_print_date($obj->date_c,"dayhour").'</td>';
			
			// Nb of docs
			//print '<td align="right">'.$obj->cachenbofdoc.'</td>';
			print '<td align="right">?</td>';
			
			print '<td align="right">';
			echo '<a href="'.$_SERVER["PHP_SELF"].'?action=delete_section&section='.urlencode($obj->rowid).'">'.img_delete().'</a>';
			print "</td></tr>\n";
			$obj = $db->fetch_object($resql);
		}
	}
	else
	{
		dolibarr_print_error($db);
	}
	
	// Ajout rubriques automatiques
	$sectionauto=array( 0 => array('test'=>$conf->societe->enabled, 'label'=>$langs->trans("ThirdParties"), 'desc'=>$langs->trans("ECMDocsByThirdParties")),
						1 => array('test'=>$conf->propal->enabled,  'label'=>$langs->trans("Proposals"),    'desc'=>$langs->trans("ECMDocsByProposals")),
						2 => array('test'=>$conf->commande->enabled,'label'=>$langs->trans("Orders"),       'desc'=>$langs->trans("ECMDocsByOrders")),
						3 => array('test'=>$conf->contrat->enabled, 'label'=>$langs->trans("Contracts"),    'desc'=>$langs->trans("ECMDocsByContracts")),
						4 => array('test'=>$conf->facture->enabled, 'label'=>$langs->trans("Invoices"),     'desc'=>$langs->trans("ECMDocsByInvoices"))
						);
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
			
			// Type
			print '<td align="left">';
			print $langs->trans("ECMTypeAuto");
			print "</td>\n";
			
			// Description
			print '<td align="left">'.$val['desc'].'</td>';
			print '<td align="left">&nbsp;</td>';
			print '<td align="center">&nbsp;</td>';
			print '<td align="right">&nbsp;</td>';
		
			print '<td align="right">&nbsp;';
			print "</td></tr>\n";
		}
	}
	
	print "</table>";
	// Fin de zone Ajax


	// Actions buttons
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
}


// End of page
$db->close();

llxFooter('$Date$ - $Revision$');
?>
