<?php
/* Copyright (C) 2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 */

/**
 *    	\file       htdocs/ecm/search.php
 *		\ingroup    ecm
 *		\brief      Page for search results
 *		\version    $Id$
 *		\author		Laurent Destailleur
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/html.formfile.class.php");

// Load traductions files
$langs->load("ecm");
$langs->load("companies");
$langs->load("other");

// Load permissions
$user->getrights('ecm');

// Get parameters
$socid = isset($_GET["socid"])?$_GET["socid"]:'';

// Permissions
if ($user->societe_id > 0)
{
    $action = '';
    $socid = $user->societe_id;
}

$section=$_GET["section"];
if (! $section) $section='misc';
$upload_dir = $conf->ecm->dir_output.'/'.$section;



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

print_fiche_titre($langs->trans("Search"));

//$head = societe_prepare_head($societe);


//dol_fiche_head($head, 'document', $societe->nom);



if ($mesg) { print $mesg."<br>"; }


print $langs->trans("FeatureNotYetAvailable");

// End of page
$db->close();

llxFooter('$Date$ - $Revision$');
?>
