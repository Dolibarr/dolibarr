<?php
/* Copyright (C) 2008-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2008-2009 Regis Houssin        <regis.houssin@capnetworks.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/ftp/index.php
 *	\ingroup    ftp
 *	\brief      Main page for FTP section area
 */

require('../main.inc.php');
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/treeview.lib.php';

// Load traductions files
$langs->load("ftp");
$langs->load("companies");
$langs->load("other");

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'ftp','');

// Get parameters
$action=GETPOST('action');
$section=GETPOST('section');
if (! $section) $section='/';
$numero_ftp = GETPOST("numero_ftp");
/* if (! $numero_ftp) $numero_ftp=1; */
$file=GETPOST("file");
$confirm=GETPOST('confirm');

$upload_dir = $conf->ftp->dir_temp;
$download_dir = $conf->ftp->dir_temp;

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="label";

$s_ftp_name='FTP_NAME_'.$numero_ftp;
$s_ftp_server='FTP_SERVER_'.$numero_ftp;
$s_ftp_port='FTP_PORT_'.$numero_ftp;
$s_ftp_user='FTP_USER_'.$numero_ftp;
$s_ftp_password='FTP_PASSWORD_'.$numero_ftp;
$s_ftp_passive='FTP_PASSIVE_'.$numero_ftp;
$ftp_name=$conf->global->$s_ftp_name;
$ftp_server=$conf->global->$s_ftp_server;
$ftp_port=$conf->global->$s_ftp_port; if (empty($ftp_port)) $ftp_port=21;
$ftp_user=$conf->global->$s_ftp_user;
$ftp_password=$conf->global->$s_ftp_password;
$ftp_passive=$conf->global->$s_ftp_passive;

// For result on connection
$ok=0;
$conn_id=null;	// FTP connection ID
$mesg='';



/*
 * ACTIONS
 */

// Submit file
if (GETPOST("sendit") && ! empty($conf->global->MAIN_UPLOAD_DOC))
{
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

	$result=$ecmdir->fetch($_REQUEST["section"]);
	if (! $result > 0)
	{
		dol_print_error($db,$ecmdir->error);
		exit;
	}
	$relativepath=$ecmdir->getRelativePath();
	$upload_dir = $conf->ecm->dir_output.'/'.$relativepath;

	if (dol_mkdir($upload_dir) >= 0)
	{
		$resupload = dol_move_uploaded_file($_FILES['userfile']['tmp_name'], $upload_dir . "/" . dol_unescapefile($_FILES['userfile']['name']),0);
		if (is_numeric($resupload) && $resupload > 0)
		{
			$result=$ecmdir->changeNbOfFiles('+');
		}
		else {
			$langs->load("errors");
			if ($resupload < 0)	// Unknown error
			{
				setEventMessages($langs->trans("ErrorFileNotUploaded"), null, 'errors');
			}
			else if (preg_match('/ErrorFileIsInfectedWithAVirus/',$resupload))	// Files infected by a virus
			{
				setEventMessages($langs->trans("ErrorFileIsInfectedWithAVirus"), null, 'errors');
			}
			else	// Known error
			{
				setEventMessages($langs->trans($resupload), null, 'errors');
			}
		}
	}
	else
	{
		// Echec transfert (fichier depassant la limite ?)
		$langs->load("errors");
		setEventMessages($langs->trans("ErrorFailToCreateDir",$upload_dir), null, 'errors');
	}
}

// Action ajout d'un rep
if ($action == 'add' && $user->rights->ftp->setup)
{
	$ecmdir->ref                = $_POST["ref"];
	$ecmdir->label              = $_POST["label"];
	$ecmdir->description        = $_POST["desc"];

	$id = $ecmdir->create($user);
	if ($id > 0)
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		setEventMessages($langs->trans("ErrorFailToCreateDir"), null, 'errors');
		$action = "create";
	}
}

// Remove file
if ($action == 'confirm_deletefile' && $_REQUEST['confirm'] == 'yes')
{
	// set up a connection or die
	if (! $conn_id)
	{
		$newsectioniso=utf8_decode($section);
		$resultarray=dol_ftp_connect($ftp_server, $ftp_port, $ftp_user, $ftp_password, $newsectioniso, $ftp_passive);
		$conn_id=$resultarray['conn_id'];
		$ok=$resultarray['ok'];
		$mesg=$resultarray['mesg'];
	}

	if ($conn_id && $ok && ! $mesg)
	{
	    $langs->load("other");

		// Remote file
		$filename=$file;
		$remotefile=$section.(preg_match('@[\\\/]$@',$section)?'':'/').$file;
		$newremotefileiso=utf8_decode($remotefile);

		//print "x".$newremotefileiso;
		dol_syslog("ftp/index.php ftp_delete ".$newremotefileiso);
		$result=@ftp_delete($conn_id, $newremotefileiso);
		if ($result)
		{
			setEventMessages($langs->trans("FileWasRemoved",$file), null, 'mesgs');
		}
		else
		{
			dol_syslog("ftp/index.php ftp_delete", LOG_ERR);
			setEventMessages($langs->trans("FTPFailedToRemoveFile",$file), null, 'errors');
		}

		//ftp_close($conn_id);	Close later

		$action='';
	}
	else
	{
		dol_print_error('',$mesg);
	}
}

// Delete several lines at once
if ($_POST["const"] && $_POST["delete"] && $_POST["delete"] == $langs->trans("Delete"))
{
	// set up a connection or die
	if (! $conn_id)
	{
		$newsectioniso=utf8_decode($section);
		$resultarray=dol_ftp_connect($ftp_server, $ftp_port, $ftp_user, $ftp_password, $newsectioniso, $ftp_passive);
		$conn_id=$resultarray['conn_id'];
		$ok=$resultarray['ok'];
		$mesg=$resultarray['mesg'];
	}

	if ($conn_id && $ok && ! $mesg)
	{
		foreach($_POST["const"] as $const)
		{
			if ($const["check"])	// Is checkbox checked
			{
			    $langs->load("other");

				// Remote file
				$file=$const["file"];
				$section=$const["section"];
				$remotefile=$section.(preg_match('@[\\\/]$@',$section)?'':'/').$file;
				$newremotefileiso=utf8_decode($remotefile);

				//print "x".$newremotefileiso;
				dol_syslog("ftp/index.php ftp_delete ".$newremotefileiso);
				$result=@ftp_delete($conn_id, $newremotefileiso);
				if ($result)
				{
					setEventMessages($langs->trans("FileWasRemoved",$file), null, 'mesgs');
				}
				else
				{
					dol_syslog("ftp/index.php ftp_delete", LOG_ERR);
					setEventMessages($langs->trans("FTPFailedToRemoveFile",$file), null, 'errors');
				}

				//ftp_close($conn_id);	Close later

				$action='';
			}
		}

	}
	else
	{
		dol_print_error('',$mesg);
	}
}

// Remove directory
if ($action == 'confirm_deletesection' && $confirm == 'yes')
{
	// set up a connection or die
	if (! $conn_id)
	{
		$newsectioniso=utf8_decode($section);
		$resultarray=dol_ftp_connect($ftp_server, $ftp_port, $ftp_user, $ftp_password, $newsectioniso, $ftp_passive);
		$conn_id=$resultarray['conn_id'];
		$ok=$resultarray['ok'];
		$mesg=$resultarray['mesg'];
	}

	if ($conn_id && $ok && ! $mesg)
	{
		// Remote file
		$filename=$file;
		$remotefile=$section.(preg_match('@[\\\/]$@',$section)?'':'/').$file;
		$newremotefileiso=utf8_decode($remotefile);

		$result=@ftp_rmdir($conn_id, $newremotefileiso);
		if ($result)
		{
			setEventMessages($langs->trans("DirWasRemoved",$file), null, 'mesgs');
		}
		else
		{
			setEventMessages($langs->trans("FTPFailedToRemoveDir",$file), null, 'errors');
		}

		//ftp_close($conn_id);	Close later

		$action='';
	}
	else
	{
		dol_print_error('',$mesg);
	}
}

// Download directory
if ($action == 'download')
{
	// set up a connection or die
	if (! $conn_id)
	{
		$newsectioniso=utf8_decode($section);
		$resultarray=dol_ftp_connect($ftp_server, $ftp_port, $ftp_user, $ftp_password, $newsectioniso, $ftp_passive);
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
		$remotefile=$section.(preg_match('@[\\\/]$@',$section)?'':'/').$file;
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
			setEventMessages($langs->transnoentitiesnoconv('FailedToGetFile',$remotefile), null, 'errors');
		}

	}
	else
	{
		dol_print_error('',$mesg);
	}

	//ftp_close($conn_id);	Close later
}




/*
 * View
 */

llxHeader();

// Add logic to shoow/hide buttons
if ($conf->use_javascript_ajax)
{
?>
<script type="text/javascript">
jQuery(document).ready(function() {
	jQuery("#delconst").hide();

	jQuery(".checkboxfordelete").click(function() {
		jQuery("#delconst").show();
	});

	$("#checkall").click(function() {
		$(".checkboxfordelete").prop('checked', true);
		jQuery("#delconst").show();
	});
	$("#checknone").click(function() {
		$(".checkboxfordelete").prop('checked', false);
		jQuery("#delconst").hide();
	});
	
});

</script>

<?php
}

$form=new Form($db);
$formfile=new FormFile($db);
$userstatic = new User($db);


// List
print load_fiche_titre($langs->trans("FTPArea"));

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
		if ($action == 'delete')
		{
			print $form->formconfirm($_SERVER["PHP_SELF"].'?numero_ftp='.$numero_ftp.'&section='.urlencode($_REQUEST["section"]).'&file='.urlencode($_GET["file"]), $langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile'), 'confirm_deletefile','','',1);
			
		}

		// Confirmation de la suppression d'une ligne categorie
		if ($action == 'delete_section')
		{
			print $form->formconfirm($_SERVER["PHP_SELF"].'?numero_ftp='.$numero_ftp.'&section='.urlencode($_REQUEST["section"]).'&file='.urlencode($_GET["file"]), $langs->trans('DeleteSection'), $langs->trans('ConfirmDeleteSection',$ecmdir->label), 'confirm_deletesection','','',1);
		}

		print $langs->trans("Server").': <b>'.$ftp_server.'</b><br>';
		print $langs->trans("Port").': <b>'.$ftp_port.'</b> '.($ftp_passive?"(Passive)":"(Active)").'<br>';
		print $langs->trans("User").': <b>'.$ftp_user.'</b><br>';
        print $langs->trans("FTPs (FTP over SSH)").': <b>'.yn($conf->global->FTP_CONNECT_WITH_SSL).'</b><br>';
        print $langs->trans("SFTP (FTP as a subsytem of SSH)").': <b>'.yn($conf->global->FTP_CONNECT_WITH_SFTP).'</b><br>';
        print $langs->trans("Directory").': ';
		$sectionarray=preg_split('|[\/]|',$section);
		// For /
		$newsection='/';
		print '<a href="'.$_SERVER["PHP_SELF"].'?action=refreshmanual&numero_ftp='.$numero_ftp.($newsection?'&section='.urlencode($newsection):'').'">';
		print '/';
		print '</a> ';
		// For other directories
		$i=0;
		foreach($sectionarray as $val)
		{
			if (empty($val)) continue;	// Discard first and last entry that should be empty as section start/end with /
			if ($i > 0)
			{
				print ' / ';
				$newsection.='/';
			}
			$newsection.=$val;
			print '<a href="'.$_SERVER["PHP_SELF"].'?action=refreshmanual&numero_ftp='.$numero_ftp.($newsection?'&section='.urlencode($newsection):'').'">';
			print $val;
			print '</a>';
			$i++;
		}
		print '<br>';
		print "<br>\n";

		print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
        print '<input type="hidden" name="numero_ftp" value="'.$numero_ftp.'">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';


		// Construit liste des repertoires
		print '<table width="100%" class="noborder">'."\n";

		print '<tr class="liste_titre">'."\n";
		print '<td class="liste_titre" align="left">'.$langs->trans("Content").'</td>'."\n";
		print '<td class="liste_titre" align="center">'.$langs->trans("Size").'</td>'."\n";
		print '<td class="liste_titre" align="center">'.$langs->trans("Date").'</td>'."\n";
		print '<td class="liste_titre" align="center">'.$langs->trans("Owner").'</td>'."\n";
		print '<td class="liste_titre" align="center">'.$langs->trans("Group").'</td>'."\n";
		print '<td class="liste_titre" align="center">'.$langs->trans("Permissions").'</td>'."\n";
		print '<td class="liste_titre nowrap" align="right">';
		if ($conf->use_javascript_ajax) print '<a href="#" id="checkall">'.$langs->trans("All").'</a> / <a href="#" id="checknone">'.$langs->trans("None").'</a> ';
		print '<a href="'.$_SERVER["PHP_SELF"].'?action=refreshmanual&numero_ftp='.$numero_ftp.($section?'&section='.urlencode($section):'').'">'.img_picto($langs->trans("Refresh"),'refresh').'</a>&nbsp;';
		print '</td>'."\n";
		print '</tr>'."\n";

		// set up a connection or die
		if (empty($conn_id))
		{
			$resultarray=dol_ftp_connect($ftp_server, $ftp_port, $ftp_user, $ftp_password, $section, $ftp_passive);

			$conn_id=$resultarray['conn_id'];
			$ok=$resultarray['ok'];
			$mesg=$resultarray['mesg'];
		}

		if ($ok)
		{
			//$type = ftp_systype($conn_id);

			$newsection=$section;
		    $newsectioniso=utf8_decode($section);
			//$newsection='/home';
			
			// List content of directory ($newsection = '/', '/home', ...)
			if (! empty($conf->global->FTP_CONNECT_WITH_SFTP))
			{
			    if ($newsection == '/') $newsection='/./';  // workaround for bug https://bugs.php.net/bug.php?id=64169
			    //$dirHandle = opendir("ssh2.sftp://$conn_id".$newsection);
			    //var_dump($dirHandle);
                $contents = scandir('ssh2.sftp://' . $conn_id . $newsection);
                $buff=array();
                foreach($contents as $i => $key)
                {
                    $buff[$i]="---------- - root root 1234 Aug 01 2000 ".$key;
                }
    		}
    		else
    		{
                $buff = ftp_rawlist($conn_id, $newsectioniso);
                $contents = ftp_nlist($conn_id, $newsectioniso);	// Sometimes rawlist fails but never nlist
        		//var_dump($contents);
		        //var_dump($buff);
    		}

			$nboflines=count($contents);
			$var=true;
			$rawlisthasfailed=false;
			$i=0;
			while ($i < $nboflines && $i < 1000)
			{
				$vals=preg_split('@ +@',utf8_encode($buff[$i]),9);
				//$vals=preg_split('@ +@','drwxr-xr-x 2 root root 4096 Aug 30 2008 backup_apollon1',9);
				//var_dump($vals);
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
					if (preg_match('/^d/',$vals[0])) $is_directory=1;
					if (preg_match('/^l/',$vals[0])) $is_link=1;
				}
				else
				{
					// Remote file
					$filename=$file;
					//print "section=".$section.' file='.$file.'X';
					//print preg_match('@[\/]$@','aaa/').'Y';
					//print preg_match('@[\\\/]$@',"aaa\\").'Y';
					$remotefile=$section.(preg_match('@[\\\/]$@',$section)?'':'/').preg_replace('@^[\\\/]@','',$file);
					//print 'A'.$remotefile.'A';
					$newremotefileiso=utf8_decode($remotefile);
					//print 'Z'.$newremotefileiso.'Z';
					$is_directory=ftp_isdir($conn_id, $newremotefileiso);
				}

				$var=!$var;
				print '<tr '.$bc[$var].' height="18">';
				// Name
				print '<td>';
				$newsection=$section.(preg_match('@[\\\/]$@',$section)?'':'/').$file;
				$newsection=preg_replace('@[\\\/][^\\\/]+[\\\/]\.\.$@','/',$newsection);	// Change aaa/xxx/.. to new aaa
				if ($is_directory) print '<a href="'.$_SERVER["PHP_SELF"].'?section='.urlencode($newsection).'&numero_ftp='.$numero_ftp.'">';
				print $file;
				if ($is_directory) print '</a>';
				print '</td>';
				// Size
				print '<td align="center" class="nowrap">';
				if (! $is_directory && ! $is_link) print $vals[4];
				else print '&nbsp;';
				print '</td>';
				// Date
				print '<td align="center" class="nowrap">';
				print $vals[5].' '.$vals[6].' '.$vals[7];
				print '</td>';
				// User
				print '<td align="center" class="nowrap">';
				print $vals[2];
				print '</td>';
				// Group
				print '<td align="center" class="nowrap">';
				print $vals[3];
				print '</td>';
				// Permissions
				print '<td align="center" class="nowrap">';
				print $vals[0];
				print '</td>';
				// Action
				print '<td align="right" width="64" class="nowrap">';
				if ($is_directory)
				{
					if ($file != '..') print '<a href="'.$_SERVER["PHP_SELF"].'?action=delete_section&numero_ftp='.$numero_ftp.'&section='.urlencode($section).'&file='.urlencode($file).'">'.img_delete().'</a>';
					else print '&nbsp;';
				}
				else if ($is_link)
				{
					$newfile=$file;
					$newfile=preg_replace('/ ->.*/','',$newfile);
					print '<a href="'.$_SERVER["PHP_SELF"].'?action=delete&numero_ftp='.$numero_ftp.'&section='.urlencode($section).'&file='.urlencode($newfile).'">'.img_delete().'</a>';
				}
				else
				{
					print '<a href="'.$_SERVER["PHP_SELF"].'?action=download&numero_ftp='.$numero_ftp.'&section='.urlencode($section).'&file='.urlencode($file).'">'.img_picto('','file').'</a>';
					print ' &nbsp; ';
					print '<input type="checkbox" class="flat checkboxfordelete" id="check_'.$i.'" name="const['.$i.'][check]" value="1">';
					print ' &nbsp; ';
					print '<a href="'.$_SERVER["PHP_SELF"].'?action=delete&numero_ftp='.$numero_ftp.'&section='.urlencode($section).'&file='.urlencode($file).'">'.img_delete().'</a>';
					print '<input type="hidden" name="const['.$i.'][section]" value="'.$section.'">';
					print '<input type="hidden" name="const['.$i.'][file]" value="'.$file.'">';
				}
				print '</td>';
				print '</tr>'."\n";
				$i++;
				$nbofentries++;
			}

		}

		print "</table>";

		
		if (! $ok)
		{
		      print $mesg.'<br>'."\n";
		      setEventMessages($mesg, null, 'errors');
		}
		
		
		// Actions
		/*
		if ($user->rights->ftp->write && ! empty($section))
		{
		$formfile->form_attach_new_file(DOL_URL_ROOT.'/ftp/index.php','',0,$section,1);
		}
		else print '&nbsp;';
		*/

		print '<br>';
		print '<div id="delconst" align="right">';
		print '<input type="submit" name="delete" class="button" value="'.$langs->trans("Delete").'">';
		print '</div>';

		print "</form>";
	}
	else
	{
		$foundsetup=false;
		$MAXFTP=20;
		$i=1;
		while ($i <= $MAXFTP)
		{
			$paramkey='FTP_NAME_'.$i;
			//print $paramkey;
			if (! empty($conf->global->$paramkey))
			{
				$foundsetup=true;
				break;
			}
			$i++;
		}		
	    if (! $foundsetup)
	    {
            print $langs->trans("SetupOfFTPClientModuleNotComplete");
	    }
	    else
	    {
	        print $langs->trans("ChooseAFTPEntryIntoMenu");
	    }
	}
}

print '<br>';

// Close FTP connection
if ($conn_id) 
{
    if (! empty($conf->global->FTP_CONNECT_WITH_SFTP))
    {
        
    }
    else if (! empty($conf->global->FTP_CONNECT_WITH_SSL))
    {
        ftp_close($conn_id);
    }
    else
    {
        ftp_close($conn_id);
    }
}
    

llxFooter();

$db->close();



/**
 * Connect to FTP server
 *
 * @param 	string	$ftp_server		Server name
 * @param 	string	$ftp_port		Server port
 * @param 	string	$ftp_user		FTP user
 * @param 	string	$ftp_password	FTP password
 * @param 	string	$section		Directory
 * @param	integer	$ftp_passive	Use a passive mode
 * @return	int 	<0 if OK, >0 if KO
 */
function dol_ftp_connect($ftp_server, $ftp_port, $ftp_user, $ftp_password, $section, $ftp_passive=0)
{
	global $langs, $conf;

	$ok=1;
    $conn_id=null;
    
	if (! is_numeric($ftp_port))
	{
		$mesg=$langs->transnoentitiesnoconv("FailedToConnectToFTPServer",$ftp_server,$ftp_port);
		$ok=0;
	}

	if ($ok)
	{
		$connecttimeout=(empty($conf->global->FTP_CONNECT_TIMEOUT)?40:$conf->global->FTP_CONNECT_TIMEOUT);
		if (! empty($conf->global->FTP_CONNECT_WITH_SFTP)) 
		{
		    dol_syslog('Try to connect with ssh2_ftp');
		    $tmp_conn_id = ssh2_connect($ftp_server, $ftp_port);
		}
		else if (! empty($conf->global->FTP_CONNECT_WITH_SSL)) 
		{
		    dol_syslog('Try to connect with ftp_ssl_connect');
		    $conn_id = ftp_ssl_connect($ftp_server, $ftp_port, $connecttimeout);
		}
		else 
		{
		    dol_syslog('Try to connect with ftp_connect');
		    $conn_id = ftp_connect($ftp_server, $ftp_port, $connecttimeout);
		}
		if ($conn_id || $tmp_conn_id)
		{
			if ($ftp_user)
			{
				if (! empty($conf->global->FTP_CONNECT_WITH_SFTP))
				{
				    if (ssh2_auth_password($tmp_conn_id, $ftp_user, $ftp_password))
    				{
    					// Turn on passive mode transfers (must be after a successful login
    					//if ($ftp_passive) ftp_pasv($conn_id, true);
    
    					// Change the dir
    					$newsectioniso=utf8_decode($section);
    					//ftp_chdir($conn_id, $newsectioniso);
		                $conn_id = ssh2_sftp($tmp_conn_id);
		                if (! $conn_id)
		                {
        					$mesg=$langs->transnoentitiesnoconv("FailedToConnectToSFTPAfterSSHAuthentication");
    	   				    $ok=0;
        				    $error++;
		                }
    				}
    				else 
    				{
    					$mesg=$langs->transnoentitiesnoconv("FailedToConnectToFTPServerWithCredentials");
	   				    $ok=0;
    				    $error++;
    				}
				}   
				else
				{
				    if (ftp_login($conn_id, $ftp_user, $ftp_password))
    				{
    					// Turn on passive mode transfers (must be after a successful login
    					if ($ftp_passive) ftp_pasv($conn_id, true);
    
    					// Change the dir
    					$newsectioniso=utf8_decode($section);
    					ftp_chdir($conn_id, $newsectioniso);
    				}
    				else 
    				{
    					$mesg=$langs->transnoentitiesnoconv("FailedToConnectToFTPServerWithCredentials");
	   				    $ok=0;
    				    $error++;
    				}
				}
			}
		}
		else
		{
		    dol_syslog('FailedToConnectToFTPServer '.$ftp_server.' '.$ftp_port, LOG_ERR);
			$mesg=$langs->transnoentitiesnoconv("FailedToConnectToFTPServer",$ftp_server,$ftp_port);
			$ok=0;
		}
	}

	$arrayresult=array('conn_id'=>$conn_id, 'ok'=>$ok, 'mesg'=>$mesg, 'curdir'=>$section, 'curdiriso'=>$newsectioniso);
	return $arrayresult;
}


/**
 * Tell if an entry is a FTP directory
 *
 * @param 		resource	$connect_id		Connection handler
 * @param 		string		$dir			Directory
 * @return		int			1=directory, 0=not a directory
 */
function ftp_isdir($connect_id,$dir)
{
	if (@ftp_chdir($connect_id,$dir))
	{
		ftp_cdup($connect_id);
		return 1;

	}
	else
	{
		return 0;
	}
}

