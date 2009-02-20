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
 *	\file      	htdocs/ecm/docfile.php
 *	\ingroup   	ecm
 *	\brief     	Card of a file for ECM module
 *	\version   	$Id$
 *	\author		Laurent Destailleur
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/ecm/htmlecm.form.class.php");
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
$langs->load("categories");

// Load permissions
$user->getrights('ecm');

if (!$user->rights->ecm->setup) accessforbidden();

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

$fileid=$_REQUEST["fileid"];
if (! $fileid)
{
	dol_print_error('',"ErrorParamNotDefined");
	exit;
}
$section=$_REQUEST["section"];
if (! $section)
{
	dol_print_error('',"ErrorSectionParamNotDefined");
	exit;
}



/*
$ecmfile = new ECMFile($db);
if (! empty($_GET["fileid"]))
{
	$result=$ecmfile->fetch($_GET["fileid"]);
	if (! $result > 0)
	{
		dol_print_error($db,$ecmfile->error);
		exit;
	}
}
*/



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
$formecm=new FormEcm($db);



$head = ecm_prepare_head($ecmdir);
dol_fiche_head($head, 'card', $langs->trans("ECMSectionManual"));

if ($_GET["action"] == 'edit')
{
	print '<form name="update" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="fileid" value="'.$fileid.'">';		
	print '<input type="hidden" name="action" value="update">';		
}

print '<table class="border" width="100%">';
print '<tr><td width="30%">'.$langs->trans("Ref").'</td><td>';
$s='';
$tmpecmdir=new ECMDirectory($db);	// Need to create a new one
$tmpecmdir->fetch($ecmdir->id);
$result = 1;
$i=0;
while ($tmpecmdir && $result > 0)
{
	$tmpecmdir->ref=$tmpecmdir->label;
	if ($i == 0 && $_GET["action"] == 'edit')
	{
		$s='<input type="text" name="label" size="32" value="'.$tmpecmdir->label.'">';
	}
	else $s=$tmpecmdir->getNomUrl(1).$s;
	if ($tmpecmdir->fk_parent)
	{
		$s=' -> '.$s;
		$result=$tmpecmdir->fetch($tmpecmdir->fk_parent);
	}
	else
	{
		$tmpecmdir=0;
	}
	$i++;
}

print img_picto('','object_dir').' <a href="'.DOL_URL_ROOT.'/ecm/index.php">'.$langs->trans("ECMRoot").'</a> -> ';
print $s;
print '</td></tr>';
print '<tr><td valign="top">'.$langs->trans("Description").'</td><td>';
if ($_GET["action"] == 'edit')
{
	print '<textarea class="flat" name="description" cols="80">';
	print $ecmdir->description;
	print '</textarea>';
}
else print dol_nl2br($ecmdir->description);
print '</td></tr>';
print '<tr><td>'.$langs->trans("ECMCreationUser").'</td><td>';
$userecm=new User($db,$ecmdir->fk_user_c);
$userecm->fetch();
print $userecm->getNomUrl(1);
print '</td></tr>';
print '<tr><td>'.$langs->trans("ECMCreationDate").'</td><td>';
print dol_print_date($ecmdir->date_c,'dayhour');
print '</td></tr>';
print '<tr><td>'.$langs->trans("ECMDirectoryForFiles").'</td><td>';
//print $conf->ecm->dir_output;
print '/ecm/'.$relativepath;
print '</td></tr>';
print '<tr><td>'.$langs->trans("ECMNbOfDocs").'</td><td>';
print sizeof($filearray);
print '</td></tr>';
print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td>';
print dol_print_size($totalsize);
print '</td></tr>';
if ($_GET["action"] == 'edit')
{
	print '<tr><td colspan="2" align="center">';
	print '<input type="submit" class="button" name="submit" value="'.$langs->trans("Save").'">';
	print ' &nbsp; &nbsp; ';
	print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</td></tr>';
}
print '</table>';
if ($_GET["action"] == 'edit')
{
	print '</form>';
}
print '</div>';



if (! $_GET["action"] || $_GET["action"] == 'delete_section')
{
	// Confirmation de la suppression d'une ligne categorie
	if ($_GET['action'] == 'delete_section')
	{
		$form->form_confirm($_SERVER["PHP_SELF"].'?section='.urldecode($_GET["section"]), $langs->trans('DeleteSection'), $langs->trans('ConfirmDeleteSection',$ecmdir->label), 'confirm_deletesection');
		print '<br>';
	}

	if ($mesg) { print $mesg."<br>"; }

	
	// Construit fiche  rubrique


	// Actions buttons
	print '<div class="tabsAction">';
	if ($user->rights->ecm->setup)
	{
		print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=delete_section">'.$langs->trans('Delete').'</a>';
	}
	else
	{
		print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans('Delete').'</a>';
	}
	print '</div>';
}


// End of page
$db->close();

llxFooter('$Date$ - $Revision$');
?>
