<?php
/* Copyright (C) 2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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

if (! $_GET["action"] || $_GET["action"] == 'delete_section')
{
	//***********************
	// List
	//***********************
	print_fiche_titre($langs->trans("ECMArea"));

	print $langs->trans("ECMAreaDesc")."<br>";
	print $langs->trans("ECMAreaDesc2")."<br>";
	print "<br>\n";

	print '<table class="notopnoleftnoright" width="100%"><tr><td width="50%" valign="top">';

	//print_fiche_titre($langs->trans("ECMManualOrg"));

	print '<form method="post" action="'.DOL_URL_ROOT.'/ecm/todo.php">';
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

	print '<form method="post" action="'.DOL_URL_ROOT.'/ecm/todo.php">';
	print '<table class="noborder" width="100%">';
	print "<tr class=\"liste_titre\">";
	print '<td colspan="3">'.$langs->trans("ECMSearchByEntity").'</td></tr>';

	$buthtml='<td rowspan="5"><input type="submit" value="'.$langs->trans("Search").'" class="button"></td>';
	$butshown=0;

	if ($conf->societe->enabled)  { print "<tr $bc[0]><td>".$langs->trans("ThirdParty").':</td><td><input type="text" name="sf_ref" class="flat" size="18"></td>'.($butshown?'':$buthtml).'</tr>'; $butshown=1; }
	if ($conf->contrat->enabled)  { print "<tr $bc[0]><td>".$langs->trans("Contrat").':</td><td><input type="text" name="sf_ref" class="flat" size="18"></td>'.($butshown?'':$buthtml).'</tr>'; $butshown=1; }
	if ($conf->propal->enabled)   { print "<tr $bc[0]><td>".$langs->trans("Proposal").':</td><td><input type="text" name="sf_ref" class="flat" size="18"></td>'.($butshown?'':$buthtml).'</tr>'; $butshown=1; }
	if ($conf->commande->enabled) { print "<tr $bc[0]><td>".$langs->trans("Order").':</td><td><input type="text" name="sf_ref" class="flat" size="18"></td>'.($butshown?'':$buthtml).'</tr>'; $butshown=1; }
	if ($conf->facture->enabled)  { print "<tr $bc[0]><td>".$langs->trans("Invoice").':</td><td><input type="text" name="sf_ref" class="flat" size="18"></td>'.($butshown?'':$buthtml).'</tr>'; $butshown=1; }
	print "</table></form><br>";
	//print $langs->trans("ECMAutoOrgDesc");
		
	print '</td></tr>';
	print '</table>';
}


// End of page
$db->close();

llxFooter('$Date$ - $Revision$');
?>
