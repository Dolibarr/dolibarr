<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	    \file       htdocs/admin/website.php
 *		\ingroup    setup
 *		\brief      Page to administer web sites
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/website.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/website/class/website.class.php';

// Load translation files required by the page
$langs->loadlangs(array('errors', 'admin', 'companies', 'website'));

$action = GETPOST('action', 'alpha') ? GETPOST('action', 'alpha') : 'view';
$confirm = GETPOST('confirm', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

$rowid = GETPOST('rowid', 'alpha');

$id = 1;

$acts[0] = "activate";
$acts[1] = "disable";
$actl[0] = img_picto($langs->trans("Disabled"), 'switch_off', 'class="size15x"');
$actl[1] = img_picto($langs->trans("Activated"), 'switch_on', 'class="size15x"');

// Load variable for pagination
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (empty($sortfield)) {
	$sortfield = 'position, ref';
}
if (empty($sortorder)) {
	$sortorder = 'ASC';
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('website'));

// Name of SQL tables of dictionaries
$tabname = array();
$tabname[1] = MAIN_DB_PREFIX."website";

// Dictionary labels
$tablib = array();
$tablib[1] = "Websites";

// Requests to extract data
$tabsql = array();
$tabsql[1] = "SELECT f.rowid as rowid, f.entity, f.ref, f.description, f.virtualhost, f.position, f.status, f.date_creation, f.lastaccess, f.pageviews_previous_month, f.pageviews_total FROM ".MAIN_DB_PREFIX.'website as f WHERE f.entity IN ('.getEntity('website').')';

// Criteria to sort dictionaries
$tabsqlsort = array();
$tabsqlsort[1] = "ref ASC";

// Nom des champs en resultat de select pour affichage du dictionnaire
$tabfield = array();
$tabfield[1] = "ref,description,virtualhost,position,date_creation,lastaccess,pageviews_previous_month,pageviews_total";

// Nom des champs d'edition pour modification d'un enregistrement
$tabfieldvalue = array();
$tabfieldvalue[1] = "ref,description,virtualhost,position,entity";

// Nom des champs dans la table pour insertion d'un enregistrement
$tabfieldinsert = array();
$tabfieldinsert[1] = "ref,description,virtualhost,position,entity";

// Nom du rowid si le champ n'est pas de type autoincrement
// Example: "" if id field is "rowid" and has autoincrement on
//          "nameoffield" if id field is not "rowid" or has not autoincrement on
$tabrowid = array();
$tabrowid[1] = "";

// Condition to show dictionary in setup page
$tabcond = array();
$tabcond[1] = (isModEnabled('website'));

// List of help for fields
$tabhelp = array();
$tabhelp[1] = array('ref'=>$langs->trans("EnterAnyCode"), 'virtualhost'=>$langs->trans("SetHereVirtualHost", DOL_DATA_ROOT.($conf->entity > 1 ? '/'.$conf->entity : '').'/website/<i>websiteref</i>'));

// List of check for fields (NOT USED YET)
$tabfieldcheck = array();
$tabfieldcheck[1] = array();


// Define elementList and sourceList (used for dictionary type of contacts "llx_c_type_contact")
$elementList = array();
$sourceList = array();

if (!$user->admin) {
	accessforbidden();
}


/*
 * Actions
 */

// Actions add or modify a website
if (GETPOST('actionadd', 'alpha') || GETPOST('actionmodify', 'alpha')) {
	$listfield = explode(',', $tabfield[$id]);
	$listfieldinsert = explode(',', $tabfieldinsert[$id]);
	$listfieldmodify = explode(',', $tabfieldinsert[$id]);
	$listfieldvalue = explode(',', $tabfieldvalue[$id]);

	// Check that all fields are filled
	$ok = 1;
	foreach ($listfield as $f => $value) {
		if ($value == 'ref' && (!GETPOSTISSET($value) || GETPOST($value) == '')) {
			$ok = 0;
			$fieldnamekey = $listfield[$f];
			setEventMessages($langs->transnoentities("ErrorFieldRequired", $langs->transnoentities($fieldnamekey)), null, 'errors');
			break;
		} elseif ($value == 'ref' && !preg_match('/^[a-z0-9_\-\.]+$/i', GETPOST($value))) {
			$ok = 0;
			$fieldnamekey = $listfield[$f];
			setEventMessages($langs->transnoentities("ErrorFieldCanNotContainSpecialCharacters", $langs->transnoentities($fieldnamekey)), null, 'errors');
			break;
		}
	}

	// Clean parameters
	if (GETPOST('ref')) {
		$websitekey = strtolower(GETPOST('ref'));
	}

	// Si verif ok et action add, on ajoute la ligne
	if ($ok && GETPOST('actionadd', 'alpha')) {
		if ($tabrowid[$id]) {
			// Get free id for insert
			$newid = 0;
			$sql = "SELECT MAX(".$tabrowid[$id].") newid from ".$tabname[$id];
			$result = $db->query($sql);
			if ($result) {
				$obj = $db->fetch_object($result);
				$newid = ($obj->newid + 1);
			} else {
				dol_print_error($db);
			}
		}

		/* $website=new Website($db);
		$website->ref=
		$website->description=
		$website->virtualhost=
		$website->create($user); */

		// Add new entry
		$sql = "INSERT INTO ".$tabname[$id]." (";
		// List of fields
		if ($tabrowid[$id] && !in_array($tabrowid[$id], $listfieldinsert)) {
			$sql .= $tabrowid[$id].",";
		}
		$sql .= $tabfieldinsert[$id];
		$sql .= ", status, date_creation)";
		$sql .= " VALUES(";

		// List of values
		if ($tabrowid[$id] && !in_array($tabrowid[$id], $listfieldinsert)) {
			$sql .= $newid.",";
		}
		$i = 0;
		foreach ($listfieldinsert as $f => $value) {
			if ($value == 'entity') {
				$_POST[$listfieldvalue[$i]] = $conf->entity;
			}
			if ($value == 'ref') {
				$_POST[$listfieldvalue[$i]] = strtolower(GETPOST($listfieldvalue[$i]));
			}
			if ($i) {
				$sql .= ",";
			}
			if (GETPOST($listfieldvalue[$i]) == '') {
				$sql .= "null";
			} else {
				$sql .= "'".$db->escape(GETPOST($listfieldvalue[$i]))."'";
			}
			$i++;
		}
		$sql .= ", 1, '".$db->idate(dol_now())."')";

		dol_syslog("actionadd", LOG_DEBUG);
		$result = $db->query($sql);
		if ($result) {	// Add is ok
			setEventMessages($langs->transnoentities("RecordSaved"), null, 'mesgs');
			unset($_POST); // Clean $_POST array, we keep only
		} else {
			if ($db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
				setEventMessages($langs->transnoentities("ErrorRecordAlreadyExists"), null, 'errors');
			} else {
				dol_print_error($db);
			}
		}
	}

	// Si verif ok et action modify, on modifie la ligne
	if ($ok && GETPOST('actionmodify', 'alpha')) {
		if ($tabrowid[$id]) {
			$rowidcol = $tabrowid[$id];
		} else {
			$rowidcol = "rowid";
		}

		$db->begin();

		$website = new Website($db);
		$rowid = GETPOST('rowid', 'int');
		$website->fetch($rowid);

		// Modify entry
		$sql = "UPDATE ".$tabname[$id]." SET ";
		// Modifie valeur des champs
		if ($tabrowid[$id] && !in_array($tabrowid[$id], $listfieldmodify)) {
			$sql .= $tabrowid[$id]."=";
			$sql .= "'".$db->escape($rowid)."', ";
		}
		$i = 0;
		foreach ($listfieldmodify as $field) {
			if ($field == 'entity') {
				$_POST[$listfieldvalue[$i]] = $conf->entity;
			}
			if ($i) {
				$sql .= ",";
			}
			$sql .= $field."=";
			if ($_POST[$listfieldvalue[$i]] == '') {
				$sql .= "null";
			} else {
				$sql .= "'".$db->escape(GETPOST($listfieldvalue[$i]))."'";
			}
			$i++;
		}
		$sql .= " WHERE ".$rowidcol." = ".((int) $rowid);

		dol_syslog("actionmodify", LOG_DEBUG);
		//print $sql;
		$resql = $db->query($sql);
		if ($resql) {
			$newname = dol_sanitizeFileName(GETPOST('ref', 'aZ09'));
			if ($newname != $website->ref) {
				$srcfile = DOL_DATA_ROOT.($conf->entity > 1 ? '/'.$conf->entity : '').'/website/'.$website->ref;
				$destfile = DOL_DATA_ROOT.($conf->entity > 1 ? '/'.$conf->entity : '').'/website/'.$newname;

				if (dol_is_dir($destfile)) {
					$error++;
					setEventMessages($langs->trans('ErrorDirAlreadyExists', $destfile), null, 'errors');
				} else {
					@rename($srcfile, $destfile);

					// We must now rename $website->ref into $newname inside files
					$arrayreplacement = array($website->ref.'/htmlheader.html' => $newname.'/htmlheader.html');
					$listofilestochange = dol_dir_list($destfile, 'files', 0, '\.php$');
					foreach ($listofilestochange as $key => $value) {
						dolReplaceInFile($value['fullname'], $arrayreplacement);
					}
				}
			}
		} else {
			$error++;
			setEventMessages($db->lasterror(), null, 'errors');
		}

		if (!$error) {
			$db->commit();
		} else {
			$db->rollback();
		}
	}
	//$_GET["id"]=GETPOST('id', 'int');       // Force affichage dictionnaire en cours d'edition
}

if (GETPOST('actioncancel', 'alpha')) {
	//$_GET["id"]=GETPOST('id', 'int');       // Force affichage dictionnaire en cours d'edition
}

if ($action == 'confirm_delete' && $confirm == 'yes') {       // delete
	if ($tabrowid[$id]) {
		$rowidcol = $tabrowid[$id];
	} else {
		$rowidcol = "rowid";
	}

	$website = new Website($db);
	$website->fetch($rowid);

	if ($website->id > 0) {
		$sql = "DELETE from ".MAIN_DB_PREFIX."website_account WHERE fk_website = ".((int) $rowid);
		$result = $db->query($sql);

		$sql = "DELETE from ".MAIN_DB_PREFIX."website_page WHERE fk_website = ".((int) $rowid);
		$result = $db->query($sql);

		$sql = "DELETE from ".MAIN_DB_PREFIX."website_extrafields WHERE fk_object = ".((int) $rowid);
		$result = $db->query($sql);

		$sql = "DELETE from ".MAIN_DB_PREFIX."website WHERE rowid = ".((int) $rowid);
		$result = $db->query($sql);
		if (!$result) {
			if ($db->errno() == 'DB_ERROR_CHILD_EXISTS') {
				setEventMessages($langs->transnoentities("ErrorRecordIsUsedByChild"), null, 'errors');
			} else {
				dol_print_error($db);
			}
		}

		if ($website->ref) {
			dol_delete_dir_recursive($conf->website->dir_output.'/'.$website->ref);
		}
	} else {
		dol_print_error($db, 'Failed to load website with id '.$rowid);
	}
}

// activate
if ($action == $acts[0]) {
	if ($tabrowid[$id]) {
		$rowidcol = $tabrowid[$id];
	} else {
		$rowidcol = "rowid";
	}

	if ($rowid) {
		$sql = "UPDATE ".$tabname[$id]." SET status = 1 WHERE rowid = ".((int) $rowid);
	}

	$result = $db->query($sql);
	if (!$result) {
		dol_print_error($db);
	}
}

// disable
if ($action == $acts[1]) {
	if ($tabrowid[$id]) {
		$rowidcol = $tabrowid[$id];
	} else {
		$rowidcol = "rowid";
	}

	if ($rowid) {
		$sql = "UPDATE ".$tabname[$id]." SET status = 0 WHERE rowid = ".((int) $rowid);
	}

	$result = $db->query($sql);
	if (!$result) {
		dol_print_error($db);
	}
}



/*
 * View
 */

$form = new Form($db);
$formadmin = new FormAdmin($db);

llxHeader('', $langs->trans("WebsiteSetup"));

$titre = $langs->trans("WebsiteSetup");
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php').'">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($titre, $linkback, 'title_setup');

// Onglets
$head = array();
$h = 0;

$head[$h][0] = DOL_URL_ROOT."/admin/website.php";
$head[$h][1] = $langs->trans("WebSites");
$head[$h][2] = 'website';
$h++;

$head[$h][0] = DOL_URL_ROOT."/admin/website_options.php";
$head[$h][1] = $langs->trans("Options");
$head[$h][2] = 'options';
$h++;

print dol_get_fiche_head($head, 'website', '', -1);


print '<span class="opacitymedium">'.$langs->trans("WebsiteSetupDesc").'</span><br>';
print "<br>\n";


// Confirmation de la suppression de la ligne
if ($action == 'delete') {
	print $form->formconfirm($_SERVER["PHP_SELF"].'?'.($page ? 'page='.$page.'&' : '').'sortfield='.$sortfield.'&sortorder='.$sortorder.'&rowid='.$rowid, $langs->trans('DeleteWebsite'), $langs->trans('ConfirmDeleteWebsite'), 'confirm_delete', '', 0, 1, 220);
}
//var_dump($elementList);

/*
 * Show website list
 */
if ($id) {
	// Complete requete recherche valeurs avec critere de tri
	$sql = $tabsql[$id];
	$sql .= $db->order($sortfield, $sortorder);
	$sql .= $db->plimit($limit + 1, $offset);
	//print $sql;

	$fieldlist = explode(',', $tabfield[$id]);

	print '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<table class="noborder centpercent">';

	// Form to add a new line
	if ($tabname[$id]) {
		// Line for title
		print '<tr class="liste_titre">';
		foreach ($fieldlist as $field => $value) {
			if (in_array($fieldlist[$field], array('date_creation', 'lastaccess', 'pageviews_previous_month', 'pageviews_month', 'pageviews_total'))) {
				continue;
			}

			// Determine le nom du champ par rapport aux noms possibles
			// dans les dictionnaires de donnees
			$valuetoshow = ucfirst($fieldlist[$field]); // Par defaut
			$valuetoshow = $langs->trans($valuetoshow); // try to translate
			$align = '';
			if ($fieldlist[$field] == 'lang') {
				$valuetoshow = $langs->trans("Language");
			}
			if ($valuetoshow != '') {
				print '<td class="'.$align.'">';
				if (!empty($tabhelp[$id][$value]) && preg_match('/^http(s*):/i', $tabhelp[$id][$value])) {
					print '<a href="'.$tabhelp[$id][$value].'" target="_blank" rel="noopener noreferrer">'.$valuetoshow.' '.img_help(1, $valuetoshow).'</a>';
				} elseif (!empty($tabhelp[$id][$value])) {
					if ($value == 'virtualhost') {
						print $form->textwithpicto($valuetoshow, $tabhelp[$id][$value], 1, 'help', '', 0, 2, 'tooltipvirtual');
					} else {
						print $form->textwithpicto($valuetoshow, $tabhelp[$id][$value]);
					}
				} else {
					print $valuetoshow;
				}
				print '</td>';
			}
		}

		print '<td colspan="4">';
		print '</td>';
		print '</tr>';

		// Line to enter new values
		print '<tr class="oddeven">';

		$obj = new stdClass();
		// If data was already input, we define them in obj to populate input fields.
		if (GETPOST('actionadd', 'alpha')) {
			foreach ($fieldlist as $key => $val) {
				if (GETPOST($val, 'alpha')) {
					$obj->$val = GETPOST($val);
				}
			}
		}
		if (!isset($obj->position)) {
			$obj->position = 1;
		}

		fieldListWebsites($fieldlist, $obj, $tabname[$id], 'add');

		print '<td colspan="3" class="right">';
		if ($action != 'edit') {
			print '<input type="submit" class="button button-add" name="actionadd" value="'.$langs->trans("Add").'">';
		}
		print '</td>';
		print "</tr>";
	}

	print '</table>';
	print '</form>';


	// List of websites in database
	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		if ($num) {
			print '<br>';

			print '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="page" value="'.$page.'">';
			print '<input type="hidden" name="rowid" value="'.$rowid.'">';

			print '<div class="div-table-responsive">';
			print '<table class="noborder centpercent">';

			// Title of lines
			print '<tr class="liste_titre">';
			foreach ($fieldlist as $field => $value) {
				// Determine le nom du champ par rapport aux noms possibles
				// dans les dictionnaires de donnees
				$showfield = 1; // Par defaut
				$align = "left";
				$sortable = 1;
				$valuetoshow = '';
				if (in_array($fieldlist[$field], array('pageviews_total', 'pageviews_previous_month'))) {
					$align = 'right';
				}

				/*
				$tmparray=getLabelOfField($fieldlist[$field]);
				$showfield=$tmp['showfield'];
				$valuetoshow=$tmp['valuetoshow'];
				$align=$tmp['align'];
				$sortable=$tmp['sortable'];
				*/
				$valuetoshow = ucfirst($fieldlist[$field]); // Par defaut
				$valuetoshow = $langs->trans($valuetoshow); // try to translate
				if ($fieldlist[$field] == 'lang') {
					$valuetoshow = $langs->trans("Language");
				}
				if ($fieldlist[$field] == 'type') {
					$valuetoshow = $langs->trans("Type");
				}
				if ($fieldlist[$field] == 'code') {
					$valuetoshow = $langs->trans("Code");
				}
				if ($fieldlist[$field] == 'date_creation') {
					$valuetoshow = $langs->trans("DateCreation");
				}
				if ($fieldlist[$field] == 'lastaccess') {
					$valuetoshow = $langs->trans("LastAccess");
				}
				if ($fieldlist[$field] == 'pageviews_previous_month') {
					$valuetoshow = $langs->trans("PagesViewedPreviousMonth");
				}
				if ($fieldlist[$field] == 'pageviews_total') {
					$valuetoshow = $langs->trans("PagesViewedTotal");
				}

				// Affiche nom du champ
				if ($showfield) {
					print getTitleFieldOfList($valuetoshow, 0, $_SERVER["PHP_SELF"], ($sortable ? $fieldlist[$field] : ''), ($page ? 'page='.$page.'&' : ''), "", '', $sortfield, $sortorder, $align.' ');
				}
			}

			// Status
			print getTitleFieldOfList($langs->trans("Status"), 0, $_SERVER["PHP_SELF"], "status", ($page ? 'page='.$page.'&' : ''), "", '', $sortfield, $sortorder, 'center ');
			print getTitleFieldOfList('');
			print getTitleFieldOfList('');
			print '</tr>';

			// Lines with values
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				//print_r($obj);
				print '<tr class="oddeven" id="rowid-'.$obj->rowid.'">';
				if ($action == 'edit' && ($rowid == (!empty($obj->rowid) ? $obj->rowid : $obj->code))) {
					$tmpaction = 'edit';
					$parameters = array('fieldlist'=>$fieldlist, 'tabname'=>$tabname[$id]);
					$reshook = $hookmanager->executeHooks('editWebsiteFieldlist', $parameters, $obj, $tmpaction); // Note that $action and $object may have been modified by some hooks
					$error = $hookmanager->error;
					$errors = $hookmanager->errors;

					if (empty($reshook)) {
						fieldListWebsites($fieldlist, $obj, $tabname[$id], 'edit');
					}

					print '<td colspan="7" class="right"><a name="'.(!empty($obj->rowid) ? $obj->rowid : $obj->code).'">&nbsp;</a>';
					print '<input type="submit" class="button button-edit small" name="actionmodify" value="'.$langs->trans("Modify").'">';
					print '&nbsp;';
					print '<input type="submit" class="button button-cancel small" name="actioncancel" value="'.$langs->trans("Cancel").'">';
					print '</td>';
				} else {
					$tmpaction = 'view';
					$parameters = array('fieldlist'=>$fieldlist, 'tabname'=>$tabname[$id]);
					$reshook = $hookmanager->executeHooks('viewWebsiteFieldlist', $parameters, $obj, $tmpaction); // Note that $action and $object may have been modified by some hooks

					$error = $hookmanager->error;
					$errors = $hookmanager->errors;

					if (empty($reshook)) {
						foreach ($fieldlist as $field => $value) {
							$showfield = 1;
							$fieldname = $fieldlist[$field];
							$align = "left";
							if (in_array($fieldname, array('pageviews_total', 'pageviews_previous_month'))) {
								$align = 'right';
							}
							$valuetoshow = $obj->$fieldname;

							// Show value for field
							if ($showfield) {
								print '<td class="'.$align.'">'.$valuetoshow.'</td>';
							}
						}
					}

					// Can an entry be erased or disabled ?
					$iserasable = 1;
					$isdisable = 1; // true by default
					if ($obj->status) {
						$iserasable = 0; // We can't delete a website on. Disable it first.
					}

					$url = $_SERVER["PHP_SELF"].'?'.($page ? 'page='.$page.'&' : '').'sortfield='.$sortfield.'&sortorder='.$sortorder.'&rowid='.(!empty($obj->rowid) ? $obj->rowid : (!empty($obj->code) ? $obj->code : '')).'&amp;code='.(!empty($obj->code) ? urlencode($obj->code) : '').'&amp;';


					// Active
					print '<td align="center" class="nowrap">';
					print '<a class="reposition" href="'.$url.'action='.$acts[($obj->status ? 1 : 0)].'&token='.newToken().'">'.$actl[($obj->status ? 1 : 0)].'</a>';
					print "</td>";

					// Modify link
					print '<td align="center"><a class="reposition editfielda" href="'.$url.'action=edit&token='.newToken().'">'.img_edit().'</a></td>';

					// Delete link
					if ($iserasable) {
						print '<td align="center"><a class="reposition" href="'.$url.'action=delete&token='.newToken().'">'.img_delete().'</a></td>';
					} else {
						print '<td class="center">'.img_delete($langs->trans("DisableSiteFirst"), 'class="opacitymedium"').'</td>';
					}

					print "</tr>\n";
				}
				$i++;
			}

			print '</table>';
			print '</div>';

			print '</form>';
		}
	} else {
		dol_print_error($db);
	}
}

print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();


/**
 *	Show fields in insert/edit mode
 *
 * 	@param		array	$fieldlist		Array of fields
 * 	@param		Object	$obj			If we show a particular record, obj is filled with record fields
 *  @param		string	$tabname		Name of SQL table
 *  @param		string	$context		'add'=Output field for the "add form", 'edit'=Output field for the "edit form", 'hide'=Output field for the "add form" but we dont want it to be rendered
 *	@return		void
 */
function fieldListWebsites($fieldlist, $obj = null, $tabname = '', $context = '')
{
	global $conf, $langs, $db;
	global $form;
	global $region_id;
	global $elementList, $sourceList, $localtax_typeList;
	global $bc;

	$formadmin = new FormAdmin($db);

	foreach ($fieldlist as $field => $value) {
		if (in_array($fieldlist[$field], array('lastaccess', 'pageviews_previous_month', 'pageviews_month', 'pageviews_total'))) {
			continue;
		}

		$fieldname = $fieldlist[$field];

		if ($fieldlist[$field] == 'lang') {
			print '<td>';
			print $formadmin->select_language($conf->global->MAIN_LANG_DEFAULT, 'lang');
			print '</td>';
		} elseif ($fieldlist[$field] == 'code' && isset($obj->$fieldname)) {
			print '<td><input type="text" class="flat" value="'.(!empty($obj->$fieldname) ? $obj->$fieldname : '').'" size="10" name="'.$fieldlist[$field].'"></td>';
		} else {
			if ($fieldlist[$field] == 'date_creation') {
				continue;
			}

			print '<td>';
			$size = '';
			if ($fieldlist[$field] == 'code') {
				$size = 'size="8" ';
			}
			if ($fieldlist[$field] == 'position') {
				$size = 'size="4" ';
			}
			if ($fieldlist[$field] == 'libelle') {
				$size = 'size="32" ';
			}
			if ($fieldlist[$field] == 'tracking') {
				$size = 'size="92" ';
			}
			if ($fieldlist[$field] == 'sortorder') {
				$size = 'size="2" ';
			}
			print '<input type="text" '.$size.' class="flat" value="'.(isset($obj->$fieldname) ? $obj->$fieldname : '').'" name="'.$fieldlist[$field].'">';
			print '</td>';
		}
	}
}
