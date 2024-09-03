<?php
/* Copyright (C) 2008-2016  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2008-2009  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2019-2024	Frédéric France         <frederic.france@free.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/ftp/index.php
 *  \ingroup    ftp
 *  \brief      Main page for FTP section area
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/treeview.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ftp.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('companies', 'other'));

// Security check
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'ftp', '');

// Get parameters
$action = GETPOST('action', 'aZ09');
$section = GETPOST('section');
$newfolder = GETPOST('newfolder');
if (!$section) {
	$section = '/';
}
$numero_ftp = GETPOST("numero_ftp");
/* if (! $numero_ftp) $numero_ftp=1; */
$file = GETPOST("file");
$confirm = GETPOST('confirm');

$upload_dir = $conf->ftp->dir_temp;
$download_dir = $conf->ftp->dir_temp;

$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortorder) {
	$sortorder = "ASC";
}
if (!$sortfield) {
	$sortfield = "label";
}

$s_ftp_name = 'FTP_NAME_'.$numero_ftp;
$s_ftp_server = 'FTP_SERVER_'.$numero_ftp;
$s_ftp_port = 'FTP_PORT_'.$numero_ftp;
$s_ftp_user = 'FTP_USER_'.$numero_ftp;
$s_ftp_password = 'FTP_PASSWORD_'.$numero_ftp;
$s_ftp_passive = 'FTP_PASSIVE_'.$numero_ftp;
$ftp_name = getDolGlobalString($s_ftp_name);
$ftp_server = getDolGlobalString($s_ftp_server);
$ftp_port = getDolGlobalString($s_ftp_port);
if (empty($ftp_port)) {
	$ftp_port = 21;
}
$ftp_user = getDolGlobalString($s_ftp_user);
$ftp_password = getDolGlobalString($s_ftp_password);
$ftp_passive = getDolGlobalInt($s_ftp_passive);

// For result on connection
$ok = 0;
$conn_id = null; // FTP connection ID
$mesg = '';



/*
 * ACTIONS
 */

if ($action == 'uploadfile') {
	// set up a connection or die
	if (!$conn_id) {
		$newsectioniso = mb_convert_encoding($section, 'ISO-8859-1');
		$resultarray = dol_ftp_connect($ftp_server, $ftp_port, $ftp_user, $ftp_password, $newsectioniso, $ftp_passive);
		$conn_id = $resultarray['conn_id'];
		$ok = $resultarray['ok'];
		$mesg = $resultarray['mesg'];
	}
	if ($conn_id && $ok && !$mesg) {
		$nbfile = count($_FILES['userfile']['name']);
		for ($i = 0; $i < $nbfile; $i++) {
			$newsection = $newsectioniso;
			$fileupload = dol_sanitizeFileName($_FILES['userfile']['name'][$i]);
			$fileuploadpath = dol_sanitizePathName($_FILES['userfile']['tmp_name'][$i]);
			$result = dol_ftp_put($conn_id, $fileupload, $fileuploadpath, $newsection);

			if ($result) {
				setEventMessages($langs->trans("FileWasUpload", $fileupload), null, 'mesgs');
			} else {
				dol_syslog("ftp/index.php ftp_delete", LOG_ERR);
				setEventMessages($langs->trans("FTPFailedToUploadFile", $fileupload), null, 'errors');
			}
		}
		$action = '';
	} else {
		dol_print_error(null, $mesg);
	}
}

if ($action == 'addfolder') {
	// set up a connection or die
	if (!$conn_id) {
		$newsectioniso = mb_convert_encoding($section, 'ISO-8859-1');
		$resultarray = dol_ftp_connect($ftp_server, $ftp_port, $ftp_user, $ftp_password, $newsectioniso, $ftp_passive);
		$conn_id = $resultarray['conn_id'];
		$ok = $resultarray['ok'];
		$mesg = $resultarray['mesg'];
	}
	if ($conn_id && $ok && !$mesg) {
		$result = dol_ftp_mkdir($conn_id, $newfolder, $newsectioniso);

		if ($result) {
			setEventMessages($langs->trans("FileWasCreateFolder", $newfolder), null, 'mesgs');
		} else {
			dol_syslog("ftp/index.php ftp_delete", LOG_ERR);
			setEventMessages($langs->trans("FTPFailedToCreateFolder", $newfolder), null, 'errors');
		}
		$action = '';
	} else {
		dol_print_error(null, $mesg);
	}
}

// Action ajout d'un rep
if ($action == 'add' && $user->hasRight('ftp', 'setup')) {
	$ecmdir = new EcmDirectory($db);
	$ecmdir->ref                = GETPOST("ref");
	$ecmdir->label              = GETPOST("label");
	$ecmdir->description        = GETPOST("desc");

	$id = $ecmdir->create($user);
	if ($id > 0) {
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	} else {
		setEventMessages($langs->trans("ErrorFailToCreateDir"), null, 'errors');
		$action = "create";
	}
}

// Remove 1 file
if ($action == 'confirm_deletefile' && GETPOST('confirm') == 'yes') {
	// set up a connection or die
	if (!$conn_id) {
		$newsectioniso = mb_convert_encoding($section, 'ISO-8859-1');
		$resultarray = dol_ftp_connect($ftp_server, $ftp_port, $ftp_user, $ftp_password, $newsectioniso, $ftp_passive);
		$conn_id = $resultarray['conn_id'];
		$ok = $resultarray['ok'];
		$mesg = $resultarray['mesg'];
	}

	if ($conn_id && $ok && !$mesg) {
		$newsection = $section;
		$result = dol_ftp_delete($conn_id, $file, $newsection);

		if ($result) {
			setEventMessages($langs->trans("FileWasRemoved", $file), null, 'mesgs');
		} else {
			dol_syslog("ftp/index.php ftp_delete", LOG_ERR);
			setEventMessages($langs->trans("FTPFailedToRemoveFile", $file), null, 'errors');
		}

		$action = '';
	} else {
		dol_print_error(null, $mesg);
	}
}

// Delete several lines at once
if (GETPOST("const", 'array') && GETPOST("delete") && GETPOST("delete") == $langs->trans("Delete")) {
	// set up a connection or die
	if (!$conn_id) {
		$newsectioniso = mb_convert_encoding($section, 'ISO-8859-1');
		$resultarray = dol_ftp_connect($ftp_server, $ftp_port, $ftp_user, $ftp_password, $newsectioniso, $ftp_passive);
		$conn_id = $resultarray['conn_id'];
		$ok = $resultarray['ok'];
		$mesg = $resultarray['mesg'];
	}

	if ($conn_id && $ok && !$mesg) {
		foreach (GETPOST('const', 'array') as $const) {
			if (isset($const["check"])) {	// Is checkbox checked
				$langs->load("other");

				// Remote file
				$file = $const["file"];
				$newsection = $const["section"];

				$result = dol_ftp_delete($conn_id, $file, $newsection);

				if ($result) {
					setEventMessages($langs->trans("FileWasRemoved", $file), null, 'mesgs');
				} else {
					dol_syslog("ftp/index.php ftp_delete n files", LOG_ERR);
					setEventMessages($langs->trans("FTPFailedToRemoveFile", $file), null, 'errors');
				}

				//ftp_close($conn_id);	Close later

				$action = '';
			}
		}
	} else {
		dol_print_error(null, $mesg);
	}
}

// Remove directory
if ($action == 'confirm_deletesection' && $confirm == 'yes') {
	// set up a connection or die
	if (!$conn_id) {
		$newsectioniso = mb_convert_encoding($section, 'ISO-8859-1');
		$resultarray = dol_ftp_connect($ftp_server, $ftp_port, $ftp_user, $ftp_password, $newsectioniso, $ftp_passive);
		$conn_id = $resultarray['conn_id'];
		$ok = $resultarray['ok'];
		$mesg = $resultarray['mesg'];
	}

	if ($conn_id && $ok && !$mesg) {
		$newsection = $section;

		$result = dol_ftp_rmdir($conn_id, $file, $newsection);

		if ($result) {
			setEventMessages($langs->trans("DirWasRemoved", $file), null, 'mesgs');
		} else {
			setEventMessages($langs->trans("FTPFailedToRemoveDir", $file), null, 'errors');
		}

		//ftp_close($conn_id);	Close later

		$action = '';
	} else {
		dol_print_error(null, $mesg);
	}
}

// Download directory
if ($action == 'download') {
	// set up a connection or die
	if (!$conn_id) {
		$newsectioniso = mb_convert_encoding($section, 'ISO-8859-1');
		$resultarray = dol_ftp_connect($ftp_server, $ftp_port, $ftp_user, $ftp_password, $newsectioniso, $ftp_passive);
		$conn_id = $resultarray['conn_id'];
		$ok = $resultarray['ok'];
		$mesg = $resultarray['mesg'];
	}

	if ($conn_id && $ok && !$mesg) {
		// Local file
		$localfile = tempnam($download_dir, 'dol_');

		$newsection = $section;

		$result = dol_ftp_get($conn_id, $localfile, $file, $newsection);


		if ($result) {
			dolChmod($localfile);

			// Define mime type
			$type = 'application/octet-stream';
			if (GETPOSTISSET("type")) {
				$type = GETPOST("type");
			} else {
				$type = dol_mimetype($file);
			}

			// Define attachment (attachment=true to force choice popup 'open'/'save as')
			$attachment = true;

			//if ($encoding)   header('Content-Encoding: '.$encoding);
			if ($type) {
				header('Content-Type: '.$type);
			}
			if ($attachment) {
				header('Content-Disposition: attachment; filename="'.$file.'"');
			} else {
				header('Content-Disposition: inline; filename="'.$file.'"');
			}

			// Ajout directives pour resoudre bug IE
			header('Cache-Control: Public, must-revalidate');
			header('Pragma: public');

			readfile($localfile);

			exit;
		} else {
			setEventMessages($langs->transnoentitiesnoconv('FailedToGetFile', $file), null, 'errors');
		}
	} else {
		dol_print_error(null, $mesg);
	}

	//ftp_close($conn_id);	Close later
}




/*
 * View
 */

llxHeader();

// Add logic to shoow/hide buttons
if ($conf->use_javascript_ajax) {
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

$form = new Form($db);
$formfile = new FormFile($db);
$userstatic = new User($db);


// List
print load_fiche_titre($langs->trans("FTPArea"));

print $langs->trans("FTPAreaDesc")."<br>";

if (!function_exists('ftp_connect')) {
	print $langs->trans("FTPFeatureNotSupportedByYourPHP");
} else {
	if (!empty($ftp_server)) {
		// Confirm remove file
		if ($action == 'delete') {
			print $form->formconfirm($_SERVER["PHP_SELF"].'?numero_ftp='.$numero_ftp.'&section='.urlencode(GETPOST('section')).'&file='.urlencode(GETPOST('file')), $langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile', GETPOST('file')), 'confirm_deletefile', '', '', 1);
		}

		// Confirmation de la suppression d'une ligne categorie
		if ($action == 'delete_section') {
			print $form->formconfirm($_SERVER["PHP_SELF"].'?numero_ftp='.$numero_ftp.'&section='.urlencode(GETPOST('section')).'&file='.urlencode(GETPOST('file')), $langs->trans('DeleteSection'), $langs->trans('ConfirmDeleteSection', GETPOST('file')), 'confirm_deletesection', '', '', 1);
		}

		print $langs->trans("Server").': <b>'.$ftp_server.'</b><br>';
		print $langs->trans("Port").': <b>'.$ftp_port.'</b> '.($ftp_passive ? "(Passive)" : "(Active)").'<br>';
		print $langs->trans("User").': <b>'.$ftp_user.'</b><br>';
		print $langs->trans("FTPs (FTP over SSH)").': <b>'.yn(getDolGlobalString('FTP_CONNECT_WITH_SSL')).'</b><br>';
		print $langs->trans("SFTP (FTP as a subsystem of SSH)").': <b>'.yn(getDolGlobalString('FTP_CONNECT_WITH_SFTP')).'</b><br>';
		print $langs->trans("Directory").': ';
		$sectionarray = preg_split('|[\/]|', $section);
		// For /
		$newsection = '/';
		print '<a href="'.$_SERVER["PHP_SELF"].'?action=refreshmanual&numero_ftp='.$numero_ftp.($newsection ? '&section='.urlencode($newsection) : '').'">';
		print '/';
		print '</a> ';
		// For other directories
		$i = 0;
		foreach ($sectionarray as $val) {
			if (empty($val)) {
				continue; // Discard first and last entry that should be empty as section start/end with /
			}
			if ($i > 0) {
				print ' / ';
				$newsection .= '/';
			}
			$newsection .= $val;
			print '<a href="'.$_SERVER["PHP_SELF"].'?action=refreshmanual&numero_ftp='.$numero_ftp.($newsection ? '&section='.urlencode($newsection) : '').'">';
			print $val;
			print '</a>';
			$i++;
		}
		print '<br>';
		print "<br>\n";

		print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		print '<input type="hidden" name="numero_ftp" value="'.$numero_ftp.'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';


		// Construit liste des repertoires
		print '<table width="100%" class="noborder">'."\n";

		print '<tr class="liste_titre">'."\n";
		print '<td class="liste_titre left">'.$langs->trans("Content").'</td>'."\n";
		print '<td class="liste_titre center">'.$langs->trans("Size").'</td>'."\n";
		print '<td class="liste_titre center">'.$langs->trans("Date").'</td>'."\n";
		print '<td class="liste_titre center">'.$langs->trans("Owner").'</td>'."\n";
		print '<td class="liste_titre center">'.$langs->trans("Group").'</td>'."\n";
		print '<td class="liste_titre center">'.$langs->trans("Permissions").'</td>'."\n";
		print '<td class="liste_titre nowrap right">';
		if ($conf->use_javascript_ajax) {
			print '<a href="#" id="checkall">'.$langs->trans("All").'</a> / <a href="#" id="checknone">'.$langs->trans("None").'</a> ';
		}
		print '<a href="'.$_SERVER["PHP_SELF"].'?action=refreshmanual&numero_ftp='.$numero_ftp.($section ? '&section='.urlencode($section) : '').'">'.img_picto($langs->trans("Refresh"), 'refresh').'</a>&nbsp;';
		print '</td>'."\n";
		print '</tr>'."\n";

		// set up a connection or die
		if (empty($conn_id)) {
			$resultarray = dol_ftp_connect($ftp_server, $ftp_port, $ftp_user, $ftp_password, $section, $ftp_passive);

			$conn_id = $resultarray['conn_id'];
			$ok = $resultarray['ok'];
			$mesg = $resultarray['mesg'];
		}

		if ($ok) {
			//$type = ftp_systype($conn_id);

			$newsection = $section;
			$newsectioniso = mb_convert_encoding($section, 'ISO-8859-1');
			//$newsection='/home';

			// List content of directory ($newsection = '/', '/home', ...)
			if (getDolGlobalString('FTP_CONNECT_WITH_SFTP')) {
				if ($newsection == '/') {
					//$newsection = '/./';
					$newsection = ssh2_sftp_realpath($conn_id, ".").'/./'; // workaround for bug https://bugs.php.net/bug.php?id=64169
				}

				//$newsection='/';
				//$dirHandle = opendir("ssh2.sftp://$conn_id".$newsection);
				//$dirHandle = opendir("ssh2.sftp://".intval($conn_id).ssh2_sftp_realpath($conn_id, ".").'/./');

				$contents = scandir('ssh2.sftp://'.intval($conn_id).$newsection);
				$buff = array();
				foreach ($contents as $i => $key) {
					$buff[$i] = "---------- - root root 1234 Aug 01 2000 ".$key;
				}

				//$i = 0;
				//$handle = opendir('ssh2.sftp://'.intval($conn_id).$newsection);
				//$buff=array();
				//while (false !== ($file = readdir($handle))) {
				//	if (substr("$file", 0, 1) != "."){
				//  	if (is_dir($file)) {
				//      	$buff[$i]="d--------- - root root 1234 Aug 01 2000 ".$file;
				//      } else {
				//          $buff[$i]="---------- - root root 1234 Aug 01 2000 ".$file;
				//      }
				//  }
				//  $i++;
				//}
			} else {
				$buff = ftp_rawlist($conn_id, $newsectioniso);
				$contents = ftp_nlist($conn_id, $newsectioniso); // Sometimes rawlist fails but never nlist
			}

			$nboflines = count($contents);
			$rawlisthasfailed = false;
			$i = 0;
			$nbofentries = 0;
			while ($i < $nboflines && $i < 1000) {
				$vals = preg_split('@ +@', mb_convert_encoding($buff[$i], 'UTF-8', 'ISO-8859-1'), 9);
				//$vals=preg_split('@ +@','drwxr-xr-x 2 root root 4096 Aug 30 2008 backup_apollon1',9);
				$file = $vals[8];
				if (empty($file)) {
					$rawlisthasfailed = true;
					$file = mb_convert_encoding($contents[$i], 'UTF-8', 'ISO-8859-1');
				}

				if ($file == '.' || ($file == '..' && $section == '/')) {
					$i++;
					continue;
				}

				// Is it a directory ?
				$is_directory = 0;
				$is_link = 0;
				if ($file == '..') {
					$is_directory = 1;
				} elseif (!$rawlisthasfailed) {
					if (preg_match('/^d/', $vals[0])) {
						$is_directory = 1;
					}
					if (preg_match('/^l/', $vals[0])) {
						$is_link = 1;
					}
				} else {
					// Remote file
					$filename = $file;
					//print "section=".$section.' file='.$file.'X';
					//print preg_match('@[\/]$@','aaa/').'Y';
					//print preg_match('@[\\\/]$@',"aaa\\").'Y';
					$remotefile = $section.(preg_match('@[\\\/]$@', $section) ? '' : '/').preg_replace('@^[\\\/]@', '', $file);
					//print 'A'.$remotefile.'A';
					$newremotefileiso = mb_convert_encoding($remotefile, 'ISO-8859-1');
					//print 'Z'.$newremotefileiso.'Z';
					$is_directory = ftp_isdir($conn_id, $newremotefileiso);
				}


				print '<tr class="oddeven" height="18">';
				// Name
				print '<td>';
				$newsection = $section.(preg_match('@[\\\/]$@', $section) ? '' : '/').$file;
				$newsection = preg_replace('@[\\\/][^\\\/]+[\\\/]\.\.$@', '/', $newsection); // Change aaa/xxx/.. to new aaa
				if ($is_directory) {
					print '<a href="'.$_SERVER["PHP_SELF"].'?section='.urlencode($newsection).'&numero_ftp='.$numero_ftp.'">';
				}
				print dol_escape_htmltag($file);
				if ($is_directory) {
					print '</a>';
				}
				print '</td>';
				// Size
				print '<td class="center nowrap">';
				if (!$is_directory && !$is_link) {
					print $vals[4];
				} else {
					print '&nbsp;';
				}
				print '</td>';
				// Date
				print '<td class="center nowrap">';
				print $vals[5].' '.$vals[6].' '.$vals[7];
				print '</td>';
				// User
				print '<td class="center nowrap">';
				print $vals[2];
				print '</td>';
				// Group
				print '<td class="center nowrap">';
				print $vals[3];
				print '</td>';
				// Permissions
				print '<td class="center nowrap">';
				print $vals[0];
				print '</td>';
				// Action
				print '<td class="right nowrap" width="64">';
				if ($is_directory) {
					if ($file != '..') {
						print '<a href="'.$_SERVER["PHP_SELF"].'?action=delete_section&token='.newToken().'&numero_ftp='.$numero_ftp.'&section='.urlencode($section).'&file='.urlencode($file).'">'.img_delete().'</a>';
					} else {
						print '&nbsp;';
					}
				} elseif ($is_link) {
					$newfile = $file;
					$newfile = preg_replace('/ ->.*/', '', $newfile);
					print '<a href="'.$_SERVER["PHP_SELF"].'?action=delete&token='.newToken().'&numero_ftp='.$numero_ftp.'&section='.urlencode($section).'&file='.urlencode($newfile).'">'.img_delete().'</a>';
				} else {
					print '<a href="'.$_SERVER["PHP_SELF"].'?action=download&token='.newToken().'&numero_ftp='.$numero_ftp.'&section='.urlencode($section).'&file='.urlencode($file).'">'.img_picto('', 'file').'</a>';
					print ' &nbsp; ';
					print '<input type="checkbox" class="flat checkboxfordelete" id="check_'.$i.'" name="const['.$i.'][check]" value="1">';
					print ' &nbsp; ';
					print '<a href="'.$_SERVER["PHP_SELF"].'?action=delete&token='.newToken().'&numero_ftp='.$numero_ftp.'&section='.urlencode($section).'&file='.urlencode($file).'">'.img_delete().'</a>';
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


		if (!$ok) {
			print $mesg.'<br>'."\n";
			setEventMessages($mesg, null, 'errors');
		}


		// Actions

		print '<br>';
		print '<div id="delconst" class="right">';
		print '<input type="submit" name="delete" class="button" value="'.$langs->trans("Delete").'">';
		print '</div>';

		print "</form>";

		if ($user->hasRight('ftp', 'write')) {
			print load_fiche_titre($langs->trans("AttachANewFile"), null, null);
			print '<form enctype="multipart/form-data" action="'.$_SERVER["PHP_SELF"].'" method="post">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="numero_ftp" value="'.$numero_ftp.'">';
			print '<input type="hidden" name="section" value="'.$section.'">';
			print '<input type="hidden" name="action" value="uploadfile">';
			print '<td><input type="file" class="flat"  name="userfile[]" multiple></td>';
			print '<td></td>';
			print '<td align="center"><button type="submit" class="butAction" name="uploadfile" value="'.$langs->trans("Save").'">'.$langs->trans("Upload").'</button></td>';
			print '</form>';

			print '<br><br>';

			print load_fiche_titre($langs->trans("AddFolder"), null, null);
			print '<form enctype="multipart/form-data" action="'.$_SERVER["PHP_SELF"].'" method="post">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="numero_ftp" value="'.$numero_ftp.'">';
			print '<input type="hidden" name="section" value="'.$section.'">';
			print '<input type="hidden" name="action" value="addfolder">';
			print '<td><input type="text" class="flat"  name="newfolder" multiple></td>';
			print '<td></td>';
			print '<td align="center"><button type="submit" class="butAction" name="addfolder" value="'.$langs->trans("Save").'">'.$langs->trans("AddFolder").'</button></td>';
			print '</form>';
		}
	} else {
		$foundsetup = false;
		$MAXFTP = 20;
		$i = 1;
		while ($i <= $MAXFTP) {
			$paramkey = 'FTP_NAME_'.$i;
			//print $paramkey;
			if (getDolGlobalString($paramkey)) {
				$foundsetup = true;
				break;
			}
			$i++;
		}
		if (!$foundsetup) {
			print $langs->trans("SetupOfFTPClientModuleNotComplete");
		} else {
			print $langs->trans("ChooseAFTPEntryIntoMenu");
		}
	}
}

print '<br>';

if (!empty($conn_id)) {
	$disconnect = dol_ftp_close($conn_id);

	if (!$disconnect) {
		setEventMessages($langs->trans("ErrorFTPNodisconnect"), null, 'errors');
	}
}

// End of page
llxFooter();
$db->close();
