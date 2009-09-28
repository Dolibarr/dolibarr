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
 *	\file       htdocs/ftp/index.php
 *	\ingroup    ftp
 *	\brief      Main page for FTP section area
 *	\version    $Id$
 *	\author		Laurent Destailleur
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/treeview.lib.php");

// Load traductions files
$langs->load("ftp");
$langs->load("companies");
$langs->load("other");

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'ftp','');

// Load permissions
$user->getrights('ftp');

// Get parameters
$action = isset($_GET["action"])?$_GET["action"]:$_POST['action'];
$section=isset($_GET["section"])?$_GET["section"]:$_POST['section'];
if (! $section) $section='/';
$numero_ftp = isset($_GET["numero_ftp"])?$_GET["numero_ftp"]:$_POST['numero_ftp'];
if (! $numero_ftp) $numero_ftp=1;
$file=isset($_GET["file"])?$_GET["file"]:$_POST['file'];

$upload_dir = $conf->ftp->dir_temp;
$download_dir = $conf->ftp->dir_temp;

$page=$_GET["page"];
$sortorder=$_GET["sortorder"];
$sortfield=$_GET["sortfield"];

$limit = $conf->liste_limit;
$offset = $limit * $page ;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="label";

$s_ftp_name='FTP_NAME_'.$numero_ftp;
$s_ftp_server='FTP_SERVER_'.$numero_ftp;
$s_ftp_port='FTP_PORT_'.$numero_ftp;
$s_ftp_user='FTP_USER_'.$numero_ftp;
$s_ftp_password='FTP_PASSWORD_'.$numero_ftp;
$ftp_name=$conf->global->$s_ftp_name;
$ftp_server=$conf->global->$s_ftp_server;
$ftp_port=$conf->global->$s_ftp_port;
$ftp_user=$conf->global->$s_ftp_user;
$ftp_password=$conf->global->$s_ftp_password;

$conn_id=0;	// FTP connection ID



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

// Action ajout d'un rep
if ($_POST["action"] == 'add' && $user->rights->ftp->setup)
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

// Remove file
if ($_REQUEST['action'] == 'confirm_deletefile' && $_REQUEST['confirm'] == 'yes')
{
	// set up a connection or die
	if (! $conn_id)
	{
		$newsectioniso=utf8_decode($section);
		$resultarray=dol_ftp_connect($ftp_server, $ftp_port, $ftp_user, $ftp_password, $newsectioniso);
		$conn_id=$resultarray['conn_id'];
		$ok=$resultarray['ok'];
		$mesg=$resultarray['mesg'];
	}

	if ($conn_id && $ok && ! $mesg)
	{
		// Remote file
		$filename=$file;
		$remotefile=$section.(eregi('[\\\/]$',$section)?'':'/').$file;
		$newremotefileiso=utf8_decode($remotefile);

		//print "x".$newremotefileiso;
		$result=ftp_delete($conn_id, $newremotefileiso);
		if ($result)
		{
			$mesg = '<div class="ok">'.$langs->trans("FileWasRemoved",$file).'</div>';
		}
		else
		{
			$mesg = '<div class="error">'.$langs->trans("FTPFailedToRemoveFile",$file).'</div>';
		}

		//ftp_close($conn_id);	Close later

		$action='';
	}
	else
	{
		dol_print_error('',$mesg);
	}
}

// Remove directory
if ($_REQUEST['action'] == 'confirm_deletesection' && $_REQUEST['confirm'] == 'yes')
{
	// set up a connection or die
	if (! $conn_id)
	{
		$newsectioniso=utf8_decode($section);
		$resultarray=dol_ftp_connect($ftp_server, $ftp_port, $ftp_user, $ftp_password, $newsectioniso);
		$conn_id=$resultarray['conn_id'];
		$ok=$resultarray['ok'];
		$mesg=$resultarray['mesg'];
	}

	if ($conn_id && $ok && ! $mesg)
	{
		// Remote file
		$filename=$file;
		$remotefile=$section.(eregi('[\\\/]$',$section)?'':'/').$file;
		$newremotefileiso=utf8_decode($remotefile);

		$result=ftp_rmdir($conn_id, $newremotefileiso);
		if ($result)
		{
			$mesg = '<div class="ok">'.$langs->trans("DirWasRemoved",$file).'</div>';
		}
		else
		{
			$mesg = '<div class="error">'.$langs->trans("FTPFailedToRemoveDir",$file).'</div>';
		}

		//ftp_close($conn_id);	Close later

		$action='';
	}
	else
	{
		dol_print_error('',$mesg);
	}
}

// Remove directory
if ($_REQUEST['action'] == 'download')
{
	// set up a connection or die
	if (! $conn_id)
	{
		$newsectioniso=utf8_decode($section);
		$resultarray=dol_ftp_connect($ftp_server, $ftp_port, $ftp_user, $ftp_password, $newsectioniso);
		$conn_id=$resultarray['conn_id'];
		$ok=$resultarray['ok'];
		$mesg=$resultarray['mesg'];
	}

	if ($conn_id && $ok && ! $mesg)
	{
		// Local file
		$localfile=tempnam($download_dir,'dol_');

		// Remote file
		$filename=$file;
		$remotefile=$section.(eregi('[\\\/]$',$section)?'':'/').$file;
		$newremotefileiso=utf8_decode($remotefile);

		$result=ftp_get($conn_id,$localfile,$newremotefileiso,FTP_BINARY);
		if ($result)
		{
			if (! empty($conf->global->MAIN_UMASK))
				@chmod($localfile, octdec($conf->global->MAIN_UMASK));

			// Define mime type
			$type = 'application/octet-stream';
			if (! empty($_GET["type"])) $type=$_GET["type"];
			else $type=dol_mimetype($original_file);

			// Define attachment (attachment=true to force choice popup 'open'/'save as')
			$attachment = true;

			if ($encoding)   header('Content-Encoding: '.$encoding);
			if ($type)       header('Content-Type: '.$type);
			if ($attachment) header('Content-Disposition: attachment; filename="'.$filename.'"');
			else header('Content-Disposition: inline; filename="'.$filename.'"');

			// Ajout directives pour resoudre bug IE
			header('Cache-Control: Public, must-revalidate');
			header('Pragma: public');

			readfile($localfile);

			ftp_close($conn_id);

			exit;
		}
		else
		{
			$mesg='<div class="error">Failed to get file '.$remotefile.'</div>';
		}

	}
	else
	{
		dol_print_error('',$mesg);
	}

	//ftp_close($conn_id);	Close later
}




/*******************************************************************
 * PAGE
 *
 * Put here all code to do according to value of "action" parameter
 ********************************************************************/

llxHeader();

$form=new Form($db);
$formfile=new FormFile($db);
$userstatic = new User($db);



//***********************
// List
//***********************
print_fiche_titre($langs->trans("FTPArea"));

print $langs->trans("FTPAreaDesc")."<br>";

if (! function_exists('ftp_connect'))
{
	print $langs->trans("FTPFeatureNotSupportedByYourPHP");
}
else
{
	if (! empty($ftp_server))
	{

		// Confirm remove file
		if ($_GET['action'] == 'delete')
		{
			$ret=$form->form_confirm($_SERVER["PHP_SELF"].'?numero_ftp='.$numero_ftp.'&section='.urlencode($_REQUEST["section"]).'&file='.urlencode($_GET["file"]), $langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile'), 'confirm_deletefile','','',1);
			if ($ret == 'html') print '<br>';
		}

		// Confirmation de la suppression d'une ligne categorie
		if ($_GET['action'] == 'delete_section')
		{
			$ret=$form->form_confirm($_SERVER["PHP_SELF"].'?numero_ftp='.$numero_ftp.'&section='.urlencode($_REQUEST["section"]).'&file='.urlencode($_GET["file"]), $langs->trans('DeleteSection'), $langs->trans('ConfirmDeleteSection',$ecmdir->label), 'confirm_deletesection','','',1);
			if ($ret == 'html') print '<br>';
		}

		print $langs->trans("Server").': <b>'.$ftp_server.'</b><br>';
		print $langs->trans("Port").': <b>'.$ftp_port.'</b><br>';
		print $langs->trans("User").': <b>'.$ftp_user.'</b><br>';

		print $langs->trans("Directory").': <b>'.$section.'</b><br>';
		print "<br>\n";

		if ($mesg) { print $mesg."<br>"; }

		// Construit liste des repertoires
		print '<table width="100%" class="nobordernopadding">'."\n";

		print '<tr class="liste_titre">'."\n";
		print '<td class="liste_titre" align="left">'.$langs->trans("Content").'</td>'."\n";
		print '<td class="liste_titre" align="center">'.$langs->trans("Size").'</td>'."\n";
		print '<td class="liste_titre" align="center">'.$langs->trans("Date").'</td>'."\n";
		print '<td class="liste_titre" align="center">'.$langs->trans("Owner").'</td>'."\n";
		print '<td class="liste_titre" align="center">'.$langs->trans("Group").'</td>'."\n";
		print '<td class="liste_titre" align="center">'.$langs->trans("Permissions").'</td>'."\n";
		print '<td class="liste_titre" align="right">';
		print '<a href="'.$_SERVER["PHP_SELF"].'?action=refreshmanual&numero_ftp='.$numero_ftp.($section?'&section='.urlencode($section):'').'">'.img_picto($langs->trans("Refresh"),'refresh').'</a>&nbsp;';
		print '</td>'."\n";
		print '</tr>'."\n";

		// set up a connection or die
		if (! $conn_id)
		{
			$resultarray=dol_ftp_connect($ftp_server, $ftp_port, $ftp_user, $ftp_password, $section);
			$conn_id=$resultarray['conn_id'];
			$ok=$resultarray['ok'];
			$mesg=$resultarray['mesg'];

		}

		if ($ok)
		{
			//$type = ftp_systype($conn_id);

			$newsectioniso=utf8_decode($section);
	        $buff = ftp_rawlist($conn_id, $newsectioniso);
	        $contents = ftp_nlist($conn_id, $newsectioniso);	// Sometimes rawlist fails but never nlist
	        //var_dump($contents);
			//var_dump($buff);

	        $nboflines=sizeof($contents);
	        $var=true;
			$rawlisthasfailed=false;
	        $i=0;
	        while ($i < $nboflines && $i < 1000)
	        {
	        	$vals=split(' +',utf8_encode($buff[$i]),9);

	        	$file=$vals[8];
				if (empty($file))
				{
					$rawlisthasfailed=true;
					$file=utf8_encode($contents[$i]);
				}

	        	if ($file == '.' || ($file == '..' && $section == '/'))
				{
					$i++;
					continue;
				}

				// Is it a directory ?
	        	$is_directory=0;
				if ($file == '..') $is_directory=1;
				else if (! $rawlisthasfailed)
				{
					if (eregi('^d',$vals[0])) $is_directory=1;
					if (eregi('^l',$vals[0])) $is_link=1;
				}
				else
				{
					// Remote file
					$filename=$file;
					$remotefile=$section.(eregi('[\\\/]$',$section)?'':'/').$file;
					$newremotefileiso=utf8_decode($remotefile);
					$is_directory=ftp_isdir($conn_id, $newremotefileiso);
				}

	        	$var=!$var;
				print '<tr '.$bc[$var].'>';
				// Name
				print '<td>';
				$newsection=$section.(eregi('[\\\/]$',$section)?'':'/').$file;
				$newsection=eregi_replace('[\\\/][^\\\/]+[\\\/]\.\.$','/',$newsection);	// Change aaa/xxx/.. to new aaa
				if ($is_directory) print '<a href="'.$_SERVER["PHP_SELF"].'?section='.urlencode($newsection).'">';
				print $file;
				if ($is_directory) print '</a>';
				print '</td>';
				// Size
				print '<td align="center">';
				if (! $is_directory && ! $is_link) print $vals[4];
				else print '&nbsp;';
				print '</td><td align="center">';
				print $vals[5].' '.$vals[6].' '.$vals[7];
				print '</td>';
				// User
				print '<td align="center">';
				print $vals[2];
				print '</td>';
				// Group
				print '<td align="center">';
				print $vals[3];
				print '</td>';
				// Permissions
				print '<td align="center">';
				print $vals[0];
				print '</td>';
				// Action
				print '<td align="right" width="64" nowrap="nowrap">';
				if ($is_directory)
				{
					if ($file != '..') print '<a href="'.$_SERVER["PHP_SELF"].'?action=delete_section&numero_ftp='.$numero_ftp.'&section='.urlencode($section).'&file='.urlencode($file).'">'.img_delete().'</a>';
					else print '&nbsp;';
				}
				else if ($is_link)
				{
					print '&nbsp;';
				}
				else
				{
					print '<a href="'.$_SERVER["PHP_SELF"].'?action=download&numero_ftp='.$numero_ftp.'&section='.urlencode($section).'&file='.urlencode($file).'">'.img_file().'</a>';
					print ' &nbsp; ';
					print '<a href="'.$_SERVER["PHP_SELF"].'?action=delete&numero_ftp='.$numero_ftp.'&section='.urlencode($section).'&file='.urlencode($file).'">'.img_delete().'</a>';
				}
				print '</td>';
				print '</tr>'."\n";
				$i++;
				$nbofentries++;
			}

		}


		print "</table>";

		if (! $ok && $mesg) print $mesg;

		// Actions
		/*
		if ($user->rights->ftp->write && ! empty($section))
		{
			$formfile->form_attach_new_file(DOL_URL_ROOT.'/ftp/index.php','',0,$section,1);
		}
		else print '&nbsp;';
		*/
	}
	else
	{
		print $langs->trans("SetupOfFTPClientModuleNotComplete");
	}
}

print '<br>';

// Close FTP connection
if ($conn_id) ftp_close($conn_id);

// End of page
$db->close();

llxFooter('$Date$ - $Revision$');


/**
 * Enter description here...
 *
 * @param unknown_type $ftp_server
 * @param unknown_type $ftp_port
 * @param unknown_type $ftp_user
 * @param unknown_type $ftp_password
 * @param unknown_type $section
 * @return unknown
 */
function dol_ftp_connect($ftp_server, $ftp_port, $ftp_user, $ftp_password, $section)
{
	global $langs;

	$ok=1;

	if (! is_numeric($ftp_port))
	{
		$mesg=$langs->trans("FailedToConnectToFTPServer",$ftp_server,$ftp_port);
		$ok=0;
	}

	if ($ok)
	{
		$conn_id = ftp_connect($ftp_server, $ftp_port, 20);
		if ($conn_id)
		{
			// turn on passive mode transfers
			//ftp_pasv ($conn_id, true) ;

			if ($ftp_user)
			{
				if (ftp_login($conn_id, $ftp_user, $ftp_password))
			    {
			        // Change the dir
					$newsectioniso=utf8_decode($section);
			        ftp_chdir($conn_id, $newsectioniso);
			    }
			    else
			    {
					$mesg=$langs->trans("FailedToConnectToFTPServerWithCredentials");
					$ok=0;
			    }
			}
		}
		else
		{
			$mesg=$langs->trans("FailedToConnectToFTPServer",$ftp_server,$ftp_port);
			$ok=0;
		}
	}

	$arrayresult=array('conn_id'=>$conn_id, 'ok'=>$ok, 'mesg'=>$mesg);
	return $arrayresult;
}


/**
 * Tell if an entry is a FTP directory
 *
 * @param unknown_type $connect_id
 * @param unknown_type $dir
 * @return unknown
 */
function ftp_isdir($connect_id,$dir)
{
    if(ftp_chdir($connect_id,$dir))
    {
        ftp_cdup($connect_id);
        return 1;

    }
    else
    {
        return 0;
    }
}

?>
