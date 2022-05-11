<?php
/* Copyright (C) 2007-2018  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
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
 *      \file       htdocs/core/ajax/ajaxdirtree.php
 *      \ingroup    ecm
 *      \brief      This script returns content of a directory for filetree
 */


// This script is called with a POST method.
// Directory to scan (full path) is inside POST['dir'] and encode by js escape() if ajax is used or encoded by urlencode if mode=noajax

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1); // Disables token renewal
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}

if (!isset($mode) || $mode != 'noajax') {    // For ajax call
	$res = @include '../../main.inc.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/treeview.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
	include_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmdirectory.class.php';

	$openeddir = GETPOST('openeddir');
	$modulepart = GETPOST('modulepart');
	$selecteddir = jsUnEscape(GETPOST('dir')); // relative path. We must decode using same encoding function used by javascript: escape()

	$preopened = GETPOST('preopened');

	if ($selecteddir != '/') {
		$selecteddir = preg_replace('/\/$/', '', $selecteddir); // We removed last '/' except if it is '/'
	}
} else {
	// For no ajax call

	$openeddir = GETPOST('openeddir');
	$modulepart = GETPOST('modulepart');
	$selecteddir = GETPOST('dir');

	$preopened = GETPOST('preopened');

	if ($selecteddir != '/') {
		$selecteddir = preg_replace('/\/$/', '', $selecteddir); // We removed last '/' except if it is '/'
	}
	if (empty($url)) {
		$url = DOL_URL_ROOT.'/ecm/index.php';
	}
}

$websitekey = GETPOST('websitekey', 'alpha');
$pageid = GETPOST('pageid', 'int');

// Load translation files required by the page
$langs->load("ecm");

// Define fullpathselecteddir.
$fullpathselecteddir = '<none>';
if ($modulepart == 'ecm') {
	$fullpathselecteddir = $conf->ecm->dir_output.'/'.($selecteddir != '/' ? $selecteddir : '');
	$fullpathpreopened = $conf->ecm->dir_output.'/'.($preopened != '/' ? $preopened : '');
} elseif ($modulepart == 'medias') {
	$fullpathselecteddir = $dolibarr_main_data_root.'/medias/'.($selecteddir != '/' ? $selecteddir : '');
	$fullpathpreopened = $dolibarr_main_data_root.'/medias/'.($preopened != '/' ? $preopened : '');
}


// Security:
// On interdit les remontees de repertoire ainsi que les pipe dans les noms de fichiers.
if (preg_match('/\.\./', $fullpathselecteddir) || preg_match('/[<>|]/', $fullpathselecteddir)) {
	dol_syslog("Refused to deliver file ".$original_file);
	// Do no show plain path in shown error message
	dol_print_error(0, $langs->trans("ErrorFileNameInvalid", GETPOST("file")));
	exit;
}

// Check permissions
if ($modulepart == 'ecm') {
	if (!$user->rights->ecm->read) {
		accessforbidden();
	}
} elseif ($modulepart == 'medias') {
	// Always allowed
}


/*
 * View
 */

if (!isset($mode) || $mode != 'noajax') {	// if ajax mode
	top_httphead();
}

//print '<!-- selecteddir (relative dir we click on) = '.$selecteddir.', openeddir = '.$openeddir.', modulepart='.$modulepart.', preopened='.$preopened.' -->'."\n";
$userstatic = new User($db);
$form = new Form($db);
$ecmdirstatic = new EcmDirectory($db);

// Load full manual tree of ECM module from database. We will use it to define nbofsubdir and nboffilesinsubdir
if (empty($sqltree)) {
	$sqltree = $ecmdirstatic->get_full_arbo(0);
}

// Try to find selected dir id into $sqltree and save it into $current_ecmdir_id
$current_ecmdir_id = -1;
foreach ($sqltree as $keycursor => $val) {
	//print $val['fullrelativename']." == ".$selecteddir;
	if ($val['fullrelativename'] == $selecteddir) {
		$current_ecmdir_id = $keycursor;
	}
}

if (!empty($conf->use_javascript_ajax) && empty($conf->global->MAIN_ECM_DISABLE_JS)) {
	treeOutputForAbsoluteDir($sqltree, $selecteddir, $fullpathselecteddir, $modulepart, $websitekey, $pageid, $preopened, $fullpathpreopened);

	// TODO Find a solution to not output this code for each leaf we open
	// Enable jquery handlers on new generated HTML objects (same code than into lib_footer.js.php)
	// Because the content is reloaded by ajax call, we must also reenable some jquery hooks
	print "\n<!-- JS CODE TO ENABLE Tooltips on all object with class classfortooltip (reload into ajaxdirtree) -->\n";
	print '<script type="text/javascript">
	            	jQuery(document).ready(function () {
	            		jQuery(".classfortooltip").tooltip({
							show: { collision: "flipfit", effect:\'toggle\', delay:50 },
							hide: { delay: 50 }, 	/* If I enable effect:\'toggle\' here, a bug appears: the tooltip is shown when collpasing a new dir if it was shown before */
							tooltipClass: "mytooltip",
							content: function () {
	              				return $(this).prop(\'title\');		/* To force to get title as is */
	          				}
	            		});
	            	});
	            	</script>';

	// This ajax service is called only when a directory $selecteddir is opened but not when closed.
	//print '<script type="text/javascript">';
	//print "loadandshowpreview('".dol_escape_js($selecteddir)."');";
	//print '</script>';
}


if (empty($conf->use_javascript_ajax) || !empty($conf->global->MAIN_ECM_DISABLE_JS)) {
	print '<ul class="ecmjqft">';

	// Load full manual tree from database. We will use it to define nbofsubdir and nboffilesinsubdir
	if (empty($sqltree)) {
		$sqltree = $ecmdirstatic->get_full_arbo(0); // Slow
	}

	// ----- This section will show a tree from a fulltree array -----
	// $section must also be defined
	// ----------------------------------------------------------------

	// Define fullpathselected ( _x_y_z ) of $section parameter (!! not into ajaxdirtree)
	$fullpathselected = '';
	foreach ($sqltree as $key => $val) {
		//print $val['id']."-".$section."<br>";
		if ($val['id'] == $section) {
			$fullpathselected = $val['fullpath'];
			break;
		}
	}
	//print "fullpathselected=".$fullpathselected."<br>";

	// Update expandedsectionarray in session
	$expandedsectionarray = array();
	if (isset($_SESSION['dol_ecmexpandedsectionarray'])) {
		$expandedsectionarray = explode(',', $_SESSION['dol_ecmexpandedsectionarray']);
	}

	if ($section && GETPOST('sectionexpand') == 'true') {
		// We add all sections that are parent of opened section
		$pathtosection = explode('_', $fullpathselected);
		foreach ($pathtosection as $idcursor) {
			if ($idcursor && !in_array($idcursor, $expandedsectionarray)) {	// Not already in array
				$expandedsectionarray[] = $idcursor;
			}
		}
		$_SESSION['dol_ecmexpandedsectionarray'] = join(',', $expandedsectionarray);
	}
	if ($section && GETPOST('sectionexpand') == 'false') {
		// We removed all expanded sections that are child of the closed section
		$oldexpandedsectionarray = $expandedsectionarray;
		$expandedsectionarray = array(); // Reset
		foreach ($oldexpandedsectionarray as $sectioncursor) {
			// TODO is_in_subtree(fulltree,sectionparent,sectionchild) does nox exists. Enable or remove this...
			//if ($sectioncursor && ! is_in_subtree($sqltree,$section,$sectioncursor)) $expandedsectionarray[]=$sectioncursor;
		}
		$_SESSION['dol_ecmexpandedsectionarray'] = join(',', $expandedsectionarray);
	}
	//print $_SESSION['dol_ecmexpandedsectionarray'].'<br>';

	$nbofentries = 0;
	$oldvallevel = 0;
	foreach ($sqltree as $key => $val) {
		$ecmdirstatic->id = $val['id'];
		$ecmdirstatic->ref = $val['label'];

		// Refresh cache
		if (preg_match('/refresh/i', $action)) {
			$result = $ecmdirstatic->fetch($val['id']);
			$ecmdirstatic->ref = $ecmdirstatic->label;

			$result = $ecmdirstatic->refreshcachenboffile(0);
			$val['cachenbofdoc'] = $result;
		}

		//$fullpathparent=preg_replace('/(_[^_]+)$/i','',$val['fullpath']);

		// Define showline
		$showline = 0;

		// If directory is son of expanded directory, we show line
		if (in_array($val['id_mere'], $expandedsectionarray)) {
			$showline = 4;
		} elseif ($val['id'] != $section && $val['id_mere'] == $ecmdirstatic->motherof[$section]) {
			// If directory is brother of selected directory, we show line
			$showline = 3;
		} elseif (preg_match('/'.$val['fullpath'].'_/i', $fullpathselected.'_')) {
			// If directory is parent of selected directory or is selected directory, we show line
			$showline = 2;
		} elseif ($val['level'] < 2) {
			// If we are level one we show line
			$showline = 1;
		}

		if ($showline) {
			if (in_array($val['id'], $expandedsectionarray)) {
				$option = 'indexexpanded';
			} else {
				$option = 'indexnotexpanded';
			}
			//print $option;

			print '<li class="directory collapsed">';

			// Show tree graph pictos
			$cpt = 1;
			while ($cpt < $sqltree[$key]['level']) {
				print ' &nbsp; &nbsp;';
				$cpt++;
			}
			$resarray = tree_showpad($sqltree, $key, 1);
			$a = $resarray[0];
			$nbofsubdir = $resarray[1];
			$nboffilesinsubdir = $resarray[2];

			// Show link
			print $ecmdirstatic->getNomUrl(0, $option, 32, 'class="fmdirlia jqft ecmjqft"');

			print '<div class="ecmjqft">';

			// Nb of docs
			print '<table class="nobordernopadding"><tr><td>';
			print $val['cachenbofdoc'];
			print '</td>';
			print '<td class="left">';
			if ($nbofsubdir && $nboffilesinsubdir) {
				print '<span style="color: #AAAAAA">+'.$nboffilesinsubdir.'</span> ';
			}
			print '</td>';

			// Info
			print '<td class="center">';
			$userstatic->id = $val['fk_user_c'];
			$userstatic->lastname = $val['login_c'];
			$userstatic->statut = $val['statut_c'];
			$htmltooltip = '<b>'.$langs->trans("ECMSection").'</b>: '.$val['label'].'<br>';
			$htmltooltip = '<b>'.$langs->trans("Type").'</b>: '.$langs->trans("ECMSectionManual").'<br>';
			$htmltooltip .= '<b>'.$langs->trans("ECMCreationUser").'</b>: '.$userstatic->getNomUrl(1, '', false, 1).'<br>';
			$htmltooltip .= '<b>'.$langs->trans("ECMCreationDate").'</b>: '.dol_print_date($val['date_c'], "dayhour").'<br>';
			$htmltooltip .= '<b>'.$langs->trans("Description").'</b>: '.$val['description'].'<br>';
			$htmltooltip .= '<b>'.$langs->trans("ECMNbOfFilesInDir").'</b>: '.$val['cachenbofdoc'].'<br>';
			if ($nbofsubdir) {
				$htmltooltip .= '<b>'.$langs->trans("ECMNbOfFilesInSubDir").'</b>: '.$nboffilesinsubdir;
			} else {
				$htmltooltip .= '<b>'.$langs->trans("ECMNbOfSubDir").'</b>: '.$nbofsubdir.'<br>';
			}
			print $form->textwithpicto('', $htmltooltip, 1, 'info');
			print "</td>";

			print '</tr></table>';

			print '</div>';

			print "</li>\n";
		}

		$oldvallevel = $val['level'];
		$nbofentries++;
	}

	// If nothing to show
	if ($nbofentries == 0) {
		print '<li class="directory collapsed">';
		print '<div class="ecmjqft">';
		print $langs->trans("ECMNoDirectoryYet");
		print '</div>';
		print "</li>\n";
	}

	print '</ul>';
}


// Close db if mode is not noajax
if ((!isset($mode) || $mode != 'noajax') && is_object($db)) {
	$db->close();
}



/**
 * treeOutputForAbsoluteDir
 *
 * @param	array	$sqltree				Sqltree
 * @param	string	$selecteddir			Selected dir
 * @param	string	$fullpathselecteddir	Full path of selected dir
 * @param	string	$modulepart				Modulepart
 * @param	string	$websitekey				Website key
 * @param	int		$pageid					Page id
 * @param	string	$preopened				Current open dir
 * @param	string	$fullpathpreopened		Full path of current open dir
 * @param	int		$depth					Depth
 * @return	void
 */
function treeOutputForAbsoluteDir($sqltree, $selecteddir, $fullpathselecteddir, $modulepart, $websitekey, $pageid, $preopened, $fullpathpreopened, $depth = 0)
{
	global $conf, $db, $langs, $form;
	global $dolibarr_main_data_root;

	$ecmdirstatic = new EcmDirectory($db);
	$userstatic = new User($db);

	if (file_exists($fullpathselecteddir)) {
		$files = @scandir($fullpathselecteddir);

		if (!empty($files)) {
			natcasesort($files);
			if (count($files) > 2) {    /* The 2 accounts for . and .. */
				echo '<ul class="ecmjqft" style="display: none;">'."\n";

				// All dirs
				foreach ($files as $file) {    // $file can be '.', '..', or 'My dir' or 'My file'
					if ($file == 'temp') {
						continue;
					}

					$nbofsubdir = 0;
					$nboffilesinsubdir = 0;

					$val = array();

					// Loop on all database entries (sqltree) to find the one matching the subdir found into dir to scan
					foreach ($sqltree as $key => $tmpval) {
						//print "-- key=".$key." - ".$tmpval['fullrelativename']." vs ".(($selecteddir != '/'?$selecteddir.'/':'').$file)."<br>\n";
						if ($tmpval['fullrelativename'] == (($selecteddir != '/' ? $selecteddir.'/' : '').$file)) {		// We found equivalent record into database
							$val = $tmpval;
							$resarray = tree_showpad($sqltree, $key, 1);

							// Refresh cache for this subdir
							if (isset($val['cachenbofdoc']) && $val['cachenbofdoc'] < 0) {	// Cache is not up to date, so we update it for this directory t
								$result = $ecmdirstatic->fetch($val['id']);
								$ecmdirstatic->ref = $ecmdirstatic->label;

								$result = $ecmdirstatic->refreshcachenboffile(0);
								$val['cachenbofdoc'] = $result;
							}

							$a = $resarray[0];
							$nbofsubdir = $resarray[1];
							$nboffilesinsubdir = $resarray[2];
							break;
						}
					}

					//print 'modulepart='.$modulepart.' fullpathselecteddir='.$fullpathselecteddir.' - val[fullrelativename] (in database)='.$val['fullrelativename'].' - val[id]='.$val['id'].' - is_dir='.dol_is_dir($fullpathselecteddir . $file).' - file='.$file."\n";
					if ($file != '.' && $file != '..' && ((!empty($val['fullrelativename']) && $val['id'] >= 0) || dol_is_dir($fullpathselecteddir.(preg_match('/\/$/', $fullpathselecteddir) ? '' : '/').$file))) {
						if (empty($val['fullrelativename'])) {	// If we did not find entry into database, but found a directory (dol_is_dir was ok at previous test)
							$val['fullrelativename'] = (($selecteddir && $selecteddir != '/') ? $selecteddir.'/' : '').$file;
							$val['id'] = 0;
							$val['label'] = $file;
							$val['description'] = '';
							$nboffilesinsubdir = $langs->trans("Unknown");
						}

						$collapsedorexpanded = 'collapsed';
						if (preg_match('/^'.preg_quote($val['fullrelativename'].'/', '/').'/', $preopened)) {
							$collapsedorexpanded = 'expanded';
						}
						print '<li class="directory '.$collapsedorexpanded.'">'; // collapsed is opposite if expanded

						print "<a class=\"fmdirlia jqft ecmjqft\" href=\"";
						print "#";
						print "\" rel=\"".dol_escape_htmltag($val['fullrelativename'].'/')."\" id=\"fmdirlia_id_".$val['id']."\"";
						print " onClick=\"loadandshowpreview('".dol_escape_js($val['fullrelativename'])."',".$val['id'].")";
						print "\">";
						print dol_escape_htmltag($file);
						print "</a>";

						print '<div class="ecmjqft">';

						print '<table class="nobordernopadding"><tr>';

						/*print '<td class="left">';
						 print dol_escape_htmltag($file);
						 print '</td>';*/

						// Nb of docs
						print '<td class="right">';
						print (isset($val['cachenbofdoc']) && $val['cachenbofdoc'] >= 0) ? $val['cachenbofdoc'] : '&nbsp;';
						print '</td>';
						print '<td class="left">';
						if ($nbofsubdir > 0 && $nboffilesinsubdir > 0) {
							print '<span class="opacitymedium">+'.$nboffilesinsubdir.'</span> ';
						}
						print '</td>';

						// Edit link
						print '<td class="right" width="18"><a class="editfielda" href="';
						print DOL_URL_ROOT.'/ecm/dir_card.php?module='.urlencode($modulepart).'&section='.$val['id'].'&relativedir='.urlencode($val['fullrelativename']);
						print '&backtopage='.urlencode($_SERVER["PHP_SELF"].'?file_manager=1&website='.$websitekey.'&pageid='.$pageid);
						print '">'.img_edit($langs->trans("Edit").' - '.$langs->trans("View"), 0, 'class="valignmiddle opacitymedium"').'</a></td>';

						// Add link
						//print '<td class="right"><a href="'.DOL_URL_ROOT.'/ecm/dir_add_card.php?action=create&amp;catParent='.$val['id'].'">'.img_edit_add().'</a></td>';
						//print '<td class="right" width="14">&nbsp;</td>';

						// Info
						if ($modulepart == 'ecm') {
							print '<td class="right" width="18">';
							$userstatic->id = isset($val['fk_user_c']) ? $val['fk_user_c'] : 0;
							$userstatic->lastname = isset($val['login_c']) ? $val['login_c'] : 0;
							$userstatic->statut = isset($val['statut_c']) ? $val['statut_c'] : 0;
							$htmltooltip = '<b>'.$langs->trans("ECMSection").'</b>: '.$val['label'].'<br>';
							$htmltooltip = '<b>'.$langs->trans("Type").'</b>: '.$langs->trans("ECMSectionManual").'<br>';
							$htmltooltip .= '<b>'.$langs->trans("ECMCreationUser").'</b>: '.$userstatic->getNomUrl(1, '', false, 1).'<br>';
							$htmltooltip .= '<b>'.$langs->trans("ECMCreationDate").'</b>: '.(isset($val['date_c']) ?dol_print_date($val['date_c'], "dayhour") : $langs->trans("NeedRefresh")).'<br>';
							$htmltooltip .= '<b>'.$langs->trans("Description").'</b>: '.$val['description'].'<br>';
							$htmltooltip .= '<b>'.$langs->trans("ECMNbOfFilesInDir").'</b>: '.((isset($val['cachenbofdoc']) && $val['cachenbofdoc'] >= 0) ? $val['cachenbofdoc'] : $langs->trans("NeedRefresh")).'<br>';
							if ($nboffilesinsubdir > 0) {
								$htmltooltip .= '<b>'.$langs->trans("ECMNbOfFilesInSubDir").'</b>: '.$nboffilesinsubdir;
							} else {
								$htmltooltip .= '<b>'.$langs->trans("ECMNbOfSubDir").'</b>: '.($nbofsubdir >= 0 ? $nbofsubdir : $langs->trans("NeedRefresh")).'<br>';
							}
							print $form->textwithpicto('', $htmltooltip, 1, "info");
							print "</td>";
						}

						print "</tr></table>\n";
						print '</div>';

						//print 'selecteddir='.$selecteddir.' preopened='.$preopened.' $val[\'fullrelativename\']='.$val['fullrelativename']."<br>\n";
						if (preg_match('/^'.preg_quote($val['fullrelativename'].'/', '/').'/', $preopened)) {
							//print 'modulepart='.$modulepart.' fullpathselecteddir='.$fullpathselecteddir.' - val[fullrelativename] (in database)='.$val['fullrelativename'].' - val[id]='.$val['id'].' - is_dir='.dol_is_dir($fullpathselecteddir . $file).' - file='.$file."\n";
							$newselecteddir = $val['fullrelativename'];
							$newfullpathselecteddir = '';
							if ($modulepart == 'ecm') {
								$newfullpathselecteddir = $conf->ecm->dir_output.'/'.($val['fullrelativename'] != '/' ? $val['fullrelativename'] : '');
							} elseif ($modulepart == 'medias') {
								$newfullpathselecteddir = $dolibarr_main_data_root.'/medias/'.($val['fullrelativename'] != '/' ? $val['fullrelativename'] : '');
							}

							if ($newfullpathselecteddir) {
								treeOutputForAbsoluteDir($sqltree, $newselecteddir, $newfullpathselecteddir, $modulepart, $websitekey, $pageid, $preopened, $fullpathpreopened, $depth + 1);
							}
						}

						print "</li>\n";
					}
				}

				echo "</ul>\n";
			}
		} else {
			print "PermissionDenied";
		}
	}
}
