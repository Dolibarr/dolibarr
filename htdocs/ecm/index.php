<?php
/* Copyright (C) 2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 */

/**
    	\file       htdoc/google/index.php
		\ingroup    google
		\brief      Main google area page
		\version    $Id$
		\author		Laurent Destailleur
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

// Envoie fichier
if ( $_POST["sendit"] && $conf->upload != 0)
{
  if (! is_dir($upload_dir)) create_exdir($upload_dir);
  
  if (is_dir($upload_dir))
  {
  	$result = doliMoveFileUpload($_FILES['userfile']['tmp_name'], $upload_dir . "/" . $_FILES['userfile']['name']);
  	if ($result == 1)
    {
    	$mesg = '<div class="ok">'.$langs->trans("FileTransferComplete").'</div>';
    	//print_r($_FILES);
    }
    else if (!$result)
    {
    	// Echec transfert (fichier d?passant la limite ?)
    	$mesg = '<div class="error">'.$langs->trans("ErrorFileNotUploaded").'</div>';
    	// print_r($_FILES);
    }
    else
    {
    	// Fichier infect? par un virus
    	$mesg = '<div class="error">'.$langs->trans("ErrorFileIsInfectedWith",$result).'</div>';
    }
  }
}

// Suppression fichier
if ($_POST['action'] == 'confirm_deletefile' && $_POST['confirm'] == 'yes')
{
  $file = $upload_dir . "/" . urldecode($_GET["urlfile"]);
  dol_delete_file($file);
  $mesg = '<div class="ok">'.$langs->trans("FileWasRemoved").'</div>';
}





/*******************************************************************
* PAGE
*
* Put here all code to do according to value of "action" parameter
********************************************************************/

llxHeader();

$form=new Form($db);

print_fiche_titre($langs->trans("ECMArea"));




// End of page
$db->close();

llxFooter('$Date$ - $Revision$');
?>
