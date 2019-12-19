<?php
/* Copyright (C) 2017		Alexandre Spangaro   <aspangaro@open-dsi.fr>
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
 *
 */

/**
 * \file		htdocs/accountancy/admin/journals_list.php
 * \ingroup		Accountancy (Double entries)
 * \brief		Setup page to configure journals
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingjournal.class.php';

// Load translation files required by the page
$langs->loadLangs(array("admin", "compta", "accountancy"));

$action = GETPOST('action', 'aZ09') ?GETPOST('action', 'aZ09') : 'view';
$confirm = GETPOST('confirm', 'alpha');
$id = 35;
$rowid = GETPOST('rowid', 'alpha');
$code = GETPOST('code', 'alpha');

// Security access
if (empty($user->rights->accounting->chartofaccount))
{
	accessforbidden();
}

$acts[0] = "activate";
$acts[1] = "disable";
$actl[0] = img_picto($langs->trans("Disabled"), 'switch_off');
$actl[1] = img_picto($langs->trans("Activated"), 'switch_on');

$listoffset = GETPOST('listoffset', 'alpha');
$listlimit = GETPOST('listlimit', 'int') > 0 ?GETPOST('listlimit', 'int') : 1000;
$active = 1;

$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOST("page", 'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $listlimit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (empty($sortfield)) $sortfield = 'code';
if (empty($sortorder)) $sortorder = 'ASC';

$error = 0;

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('admin'));

// This page is a generic page to edit dictionaries
// Put here declaration of dictionaries properties

// Sort order to show dictionary (0 is space). All other dictionaries (added by modules) will be at end of this.
$taborder = array(35);

// Name of SQL tables of dictionaries
$tabname = array();
$tabname[35] = MAIN_DB_PREFIX."accounting_journal";

// Dictionary labels
$tablib = array();
$tablib[35] = "DictionaryAccountancyJournal";

// Requests to extract data
$tabsql = array();
$tabsql[35] = "SELECT a.rowid as rowid, a.code as code, a.label, a.nature, a.active FROM ".MAIN_DB_PREFIX."accounting_journal as a";

// Criteria to sort dictionaries
$tabsqlsort = array();
$tabsqlsort[35] = "code ASC";

// Nom des champs en resultat de select pour affichage du dictionnaire
$tabfield = array();
$tabfield[35] = "code,label,nature";

// Nom des champs d'edition pour modification d'un enregistrement
$tabfieldvalue = array();
$tabfieldvalue[35] = "code,label,nature";

// Nom des champs dans la table pour insertion d'un enregistrement
$tabfieldinsert = array();
$tabfieldinsert[35] = "code,label,nature";

// Nom du rowid si le champ n'est pas de type autoincrement
// Example: "" if id field is "rowid" and has autoincrement on
//          "nameoffield" if id field is not "rowid" or has not autoincrement on
$tabrowid = array();
$tabrowid[35] = "";

// Condition to show dictionary in setup page
$tabcond = array();
$tabcond[35] = !empty($conf->accounting->enabled);

// List of help for fields
$tabhelp = array();
$tabhelp[35] = array('code'=>$langs->trans("EnterAnyCode"));

// List of check for fields (NOT USED YET)
$tabfieldcheck = array();
$tabfieldcheck[35] = array();

// Complete all arrays with entries found into modules
complete_dictionary_with_modules($taborder, $tabname, $tablib, $tabsql, $tabsqlsort, $tabfield, $tabfieldvalue, $tabfieldinsert, $tabrowid, $tabcond, $tabhelp, $tabfieldcheck);


// Define elementList and sourceList (used for dictionary type of contacts "llx_c_type_contact")
$elementList = array();
// Must match ids defined into eldy.lib.php
$sourceList = array(
	'1' => $langs->trans('AccountingJournalType1'),
	'2' => $langs->trans('AccountingJournalType2'),
	'3' => $langs->trans('AccountingJournalType3'),
	'4' => $langs->trans('AccountingJournalType4'),
	'5' => $langs->trans('AccountingJournalType5'),
	'8' => $langs->trans('AccountingJournalType8'),
	'9' => $langs->trans('AccountingJournalType9'),
);

/*
 * Actions
 */

if (GETPOST('button_removefilter', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter_x', 'alpha'))
{
	$search_country_id = '';
}

// Actions add or modify an entry into a dictionary
if (GETPOST('actionadd', 'alpha') || GETPOST('actionmodify', 'alpha'))
{
	$listfield = explode(',', str_replace(' ', '', $tabfield[$id]));
	$listfieldinsert = explode(',', $tabfieldinsert[$id]);
	$listfieldmodify = explode(',', $tabfieldinsert[$id]);
	$listfieldvalue = explode(',', $tabfieldvalue[$id]);

	// Check that all fields are filled
	$ok = 1;
	foreach ($listfield as $f => $value)
	{
		if ($fieldnamekey == 'libelle' || ($fieldnamekey == 'label'))  $fieldnamekey = 'Label';
		if ($fieldnamekey == 'code') $fieldnamekey = 'Code';
		if ($fieldnamekey == 'nature') $fieldnamekey = 'NatureOfJournal';
	}
	// Other checks
	if (isset($_POST["code"]))
	{
		if ($_POST["code"] == '0')
		{
			$ok = 0;
			setEventMessages($langs->transnoentities('ErrorCodeCantContainZero'), null, 'errors');
		}
		/*if (!is_numeric($_POST['code']))	// disabled, code may not be in numeric base
    	{
	    	$ok = 0;
	    	$msg .= $langs->transnoentities('ErrorFieldFormat', $langs->transnoentities('Code')).'<br>';
	    }*/
	}
	if (!GETPOST('label', 'alpha'))
	{
		setEventMessages($langs->transnoentities("ErrorFieldRequired", $langs->transnoentitiesnoconv("Label")), null, 'errors');
		$ok = 0;
	}

	// Clean some parameters
	if ($_POST["accountancy_code"] <= 0) $_POST["accountancy_code"] = ''; // If empty, we force to null
	if ($_POST["accountancy_code_sell"] <= 0) $_POST["accountancy_code_sell"] = ''; // If empty, we force to null
	if ($_POST["accountancy_code_buy"] <= 0) $_POST["accountancy_code_buy"] = ''; // If empty, we force to null

	// Si verif ok et action add, on ajoute la ligne
	if ($ok && GETPOST('actionadd', 'alpha'))
	{
		if ($tabrowid[$id])
		{
			// Recupere id libre pour insertion
			$newid = 0;
			$sql = "SELECT max(".$tabrowid[$id].") newid from ".$tabname[$id];
			$result = $db->query($sql);
			if ($result)
			{
				$obj = $db->fetch_object($result);
				$newid = ($obj->newid + 1);
			} else {
				dol_print_error($db);
			}
		}

		// Add new entry
		$sql = "INSERT INTO ".$tabname[$id]." (";
		// List of fields
		if ($tabrowid[$id] && !in_array($tabrowid[$id], $listfieldinsert))
			$sql .= $tabrowid[$id].",";
		$sql .= $tabfieldinsert[$id];
		$sql .= ",active,entity)";
		$sql .= " VALUES(";

		// List of values
		if ($tabrowid[$id] && !in_array($tabrowid[$id], $listfieldinsert))
			$sql .= $newid.",";
		$i = 0;
		foreach ($listfieldinsert as $f => $value)
		{
			if ($value == 'entity') {
				$_POST[$listfieldvalue[$i]] = $conf->entity;
			}
			if ($i) $sql .= ",";
			if ($_POST[$listfieldvalue[$i]] == '') $sql .= "null"; // For vat, we want/accept code = ''
			else $sql .= "'".$db->escape($_POST[$listfieldvalue[$i]])."'";
			$i++;
		}
		$sql .= ",1,".$conf->entity.")";

		dol_syslog("actionadd", LOG_DEBUG);
		$result = $db->query($sql);
		if ($result)	// Add is ok
		{
			setEventMessages($langs->transnoentities("RecordSaved"), null, 'mesgs');
			$_POST = array('id'=>$id); // Clean $_POST array, we keep only
		}
		else
		{
			if ($db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
				setEventMessages($langs->transnoentities("ErrorRecordAlreadyExists"), null, 'errors');
			}
			else {
				dol_print_error($db);
			}
		}
	}

	// Si verif ok et action modify, on modifie la ligne
	if ($ok && GETPOST('actionmodify', 'alpha'))
	{
		if ($tabrowid[$id]) { $rowidcol = $tabrowid[$id]; }
		else { $rowidcol = "rowid"; }

		// Modify entry
		$sql = "UPDATE ".$tabname[$id]." SET ";
		// Modifie valeur des champs
		if ($tabrowid[$id] && !in_array($tabrowid[$id], $listfieldmodify))
		{
			$sql .= $tabrowid[$id]."=";
			$sql .= "'".$db->escape($rowid)."', ";
		}
		$i = 0;
		foreach ($listfieldmodify as $field)
		{
			if ($field == 'price' || preg_match('/^amount/i', $field) || $field == 'taux') {
				$_POST[$listfieldvalue[$i]] = price2num($_POST[$listfieldvalue[$i]], 'MU');
			}
			elseif ($field == 'entity') {
				$_POST[$listfieldvalue[$i]] = $conf->entity;
			}
			if ($i) $sql .= ",";
			$sql .= $field."=";
			if ($_POST[$listfieldvalue[$i]] == '' && !($listfieldvalue[$i] == 'code' && $id == 10)) $sql .= "null"; // For vat, we want/accept code = ''
			else $sql .= "'".$db->escape($_POST[$listfieldvalue[$i]])."'";
			$i++;
		}
		$sql .= " WHERE ".$rowidcol." = '".$rowid."'";
		$sql .= " AND entity = ".$conf->entity;

		dol_syslog("actionmodify", LOG_DEBUG);
		//print $sql;
		$resql = $db->query($sql);
		if (!$resql)
		{
			setEventMessages($db->error(), null, 'errors');
		}
	}
	//$_GET["id"]=GETPOST('id', 'int');       // Force affichage dictionnaire en cours d'edition
}

//if (GETPOST('actioncancel', 'alpha'))
//{
//	$_GET["id"]=GETPOST('id', 'int');       // Force affichage dictionnaire en cours d'edition
//}

if ($action == 'confirm_delete' && $confirm == 'yes')       // delete
{
	if ($tabrowid[$id]) { $rowidcol = $tabrowid[$id]; }
	else { $rowidcol = "rowid"; }

	$sql = "DELETE from ".$tabname[$id]." WHERE ".$rowidcol."='".$rowid."'";
	$sql .= " AND entity = ".$conf->entity;

	dol_syslog("delete", LOG_DEBUG);
	$result = $db->query($sql);
	if (!$result)
	{
		if ($db->errno() == 'DB_ERROR_CHILD_EXISTS')
		{
			setEventMessages($langs->transnoentities("ErrorRecordIsUsedByChild"), null, 'errors');
		}
		else
		{
			dol_print_error($db);
		}
	}
}

// activate
if ($action == $acts[0])
{
	if ($tabrowid[$id]) { $rowidcol = $tabrowid[$id]; }
	else { $rowidcol = "rowid"; }

	if ($rowid) {
		$sql = "UPDATE ".$tabname[$id]." SET active = 1 WHERE ".$rowidcol."='".$rowid."'";
	}
	elseif ($code) {
		$sql = "UPDATE ".$tabname[$id]." SET active = 1 WHERE code='".$code."'";
	}
	$sql .= " AND entity = ".$conf->entity;

	$result = $db->query($sql);
	if (!$result)
	{
		dol_print_error($db);
	}
}

// disable
if ($action == $acts[1])
{
	if ($tabrowid[$id]) { $rowidcol = $tabrowid[$id]; }
	else { $rowidcol = "rowid"; }

	if ($rowid) {
		$sql = "UPDATE ".$tabname[$id]." SET active = 0 WHERE ".$rowidcol."='".$rowid."'";
	}
	elseif ($code) {
		$sql = "UPDATE ".$tabname[$id]." SET active = 0 WHERE code='".$code."'";
	}
	$sql .= " AND entity = ".$conf->entity;

	$result = $db->query($sql);
	if (!$result)
	{
		dol_print_error($db);
	}
}


/*
 * View
 */

$form = new Form($db);
$formadmin = new FormAdmin($db);

llxHeader();

$titre = $langs->trans("DictionarySetup");
$linkback = '';
if ($id)
{
	$titre .= ' - '.$langs->trans($tablib[$id]);
	$titlepicto = 'title_accountancy';
}

print load_fiche_titre($titre, $linkback, $titlepicto);


// Confirmation de la suppression de la ligne
if ($action == 'delete')
{
	print $form->formconfirm($_SERVER["PHP_SELF"].'?'.($page ? 'page='.$page.'&' : '').'sortfield='.$sortfield.'&sortorder='.$sortorder.'&rowid='.$rowid.'&code='.$code.'&id='.$id, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_delete', '', 0, 1);
}
//var_dump($elementList);

/*
 * Show a dictionary
 */
if ($id)
{
	// Complete requete recherche valeurs avec critere de tri
	$sql = $tabsql[$id];
	$sql .= " WHERE a.entity = ".$conf->entity;

	// If sort order is "country", we use country_code instead
	if ($sortfield == 'country') $sortfield = 'country_code';
	$sql .= $db->order($sortfield, $sortorder);
	$sql .= $db->plimit($listlimit + 1, $offset);

	$fieldlist = explode(',', $tabfield[$id]);

	print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$id.'" method="POST">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="from" value="'.dol_escape_htmltag(GETPOST('from', 'alpha')).'">';

	print '<div class="div-table-responsive">';
	print '<table class="noborder centpercent">';

	// Form to add a new line
	if ($tabname[$id])
	{
		$alabelisused = 0;

		$fieldlist = explode(',', $tabfield[$id]);

		// Line for title
		print '<tr class="liste_titre">';
		foreach ($fieldlist as $field => $value)
		{
			// Determine le nom du champ par rapport aux noms possibles
			// dans les dictionnaires de donnees
			$valuetoshow = ucfirst($fieldlist[$field]); // Par defaut
			$valuetoshow = $langs->trans($valuetoshow); // try to translate
			$class = "left";
            if ($fieldlist[$field] == 'code') {
                $valuetoshow = $langs->trans("Code");
            }
			if ($fieldlist[$field] == 'libelle' || $fieldlist[$field] == 'label') {
				$valuetoshow = $langs->trans("Label");
			}
            if ($fieldlist[$field] == 'nature') {
                $valuetoshow = $langs->trans("NatureOfJournal");
            }

			if ($valuetoshow != '') {
				print '<td class="'.$class.'">';
				if (!empty($tabhelp[$id][$value]) && preg_match('/^http(s*):/i', $tabhelp[$id][$value])) print '<a href="'.$tabhelp[$id][$value].'" target="_blank">'.$valuetoshow.' '.img_help(1, $valuetoshow).'</a>';
				elseif (!empty($tabhelp[$id][$value])) print $form->textwithpicto($valuetoshow, $tabhelp[$id][$value]);
				else print $valuetoshow;
				print '</td>';
			}
			if ($fieldlist[$field] == 'libelle' || $fieldlist[$field] == 'label') $alabelisused = 1;
		}

		print '<td>';
		print '<input type="hidden" name="id" value="'.$id.'">';
		print '</td>';
		print '<td style="min-width: 26px;"></td>';
		print '<td style="min-width: 26px;"></td>';
		print '<td style="min-width: 26px;"></td>';
		print '</tr>';

		// Line to enter new values
		print '<tr class="oddeven nodrag nodrap nohover">';

		$obj = new stdClass();
		// If data was already input, we define them in obj to populate input fields.
		if (GETPOST('actionadd', 'alpha'))
		{
			foreach ($fieldlist as $key=>$val)
			{
				if (GETPOST($val) != '')
					$obj->$val = GETPOST($val);
			}
		}

		$tmpaction = 'create';
		$parameters = array('fieldlist'=>$fieldlist, 'tabname'=>$tabname[$id]);
		$reshook = $hookmanager->executeHooks('createDictionaryFieldlist', $parameters, $obj, $tmpaction); // Note that $action and $object may have been modified by some hooks
		$error = $hookmanager->error; $errors = $hookmanager->errors;

		if (empty($reshook))
		{
	   		fieldListJournal($fieldlist, $obj, $tabname[$id], 'add');
		}

		print '<td colspan="4" class="right">';
	   	print '<input type="submit" class="button" name="actionadd" value="'.$langs->trans("Add").'">';
		print '</td>';
		print "</tr>";

		print '<tr><td colspan="7">&nbsp;</td></tr>'; // Keep &nbsp; to have a line with enough height
	}



	// List of available record in database
	dol_syslog("htdocs/admin/dict", LOG_DEBUG);
	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0;

		$param = '&id='.$id;
		if ($search_country_id > 0) $param .= '&search_country_id='.$search_country_id;
		$paramwithsearch = $param;
		if ($sortorder) $paramwithsearch .= '&sortorder='.$sortorder;
		if ($sortfield) $paramwithsearch .= '&sortfield='.$sortfield;
		if (GETPOST('from', 'alpha')) $paramwithsearch .= '&from='.GETPOST('from', 'alpha');

		// There is several pages
		if ($num > $listlimit)
		{
			print '<tr class="none"><td class="right" colspan="'.(3 + count($fieldlist)).'">';
			print_fleche_navigation($page, $_SERVER["PHP_SELF"], $paramwithsearch, ($num > $listlimit), '<li class="pagination"><span>'.$langs->trans("Page").' '.($page + 1).'</span></li>');
			print '</td></tr>';
		}

		// Title line with search boxes
		/*print '<tr class="liste_titre_filter liste_titre_add">';
		print '<td class="liste_titre"></td>';
		print '<td class="liste_titre"></td>';
		print '<td class="liste_titre"></td>';
		print '<td class="liste_titre"></td>';
		print '<td class="liste_titre"></td>';
		print '<td class="liste_titre"></td>';
		print '<td class="liste_titre center">';
		$searchpicto=$form->showFilterButtons();
		print $searchpicto;
		print '</td>';
		print '</tr>';
		*/

		// Title of lines
		print '<tr class="liste_titre liste_titre_add">';
		foreach ($fieldlist as $field => $value)
		{
			// Determine le nom du champ par rapport aux noms possibles
			// dans les dictionnaires de donnees
			$showfield = 1; // By defaut
			$class = "left";
			$sortable = 1;
			$valuetoshow = '';
			/*
            $tmparray=getLabelOfField($fieldlist[$field]);
            $showfield=$tmp['showfield'];
            $valuetoshow=$tmp['valuetoshow'];
            $align=$tmp['align'];
            $sortable=$tmp['sortable'];
			*/
			$valuetoshow = ucfirst($fieldlist[$field]); // By defaut
			$valuetoshow = $langs->trans($valuetoshow); // try to translate
			if ($fieldlist[$field] == 'code') {
                $valuetoshow = $langs->trans("Code");
            }
			if ($fieldlist[$field] == 'libelle' || $fieldlist[$field] == 'label') {
                $valuetoshow = $langs->trans("Label");
            }
			if ($fieldlist[$field] == 'nature') {
                $valuetoshow = $langs->trans("NatureOfJournal");
            }

			// Affiche nom du champ
			if ($showfield) {
				print getTitleFieldOfList($valuetoshow, 0, $_SERVER["PHP_SELF"], ($sortable ? $fieldlist[$field] : ''), ($page ? 'page='.$page.'&' : ''), $param, "", $sortfield, $sortorder, $class.' ');
			}
		}
		print getTitleFieldOfList($langs->trans("Status"), 0, $_SERVER["PHP_SELF"], "active", ($page ? 'page='.$page.'&' : ''), $param, '', $sortfield, $sortorder, 'center ');
		print getTitleFieldOfList('');
		print getTitleFieldOfList('');
		print getTitleFieldOfList('');
		print '</tr>';

		if ($num)
		{
			// Lines with values
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);
				//print_r($obj);
				print '<tr class="oddeven" id="rowid-'.$obj->rowid.'">';
				if ($action == 'edit' && ($rowid == (!empty($obj->rowid) ? $obj->rowid : $obj->code)))
				{
					$tmpaction = 'edit';
					$parameters = array('fieldlist'=>$fieldlist, 'tabname'=>$tabname[$id]);
					$reshook = $hookmanager->executeHooks('editDictionaryFieldlist', $parameters, $obj, $tmpaction); // Note that $action and $object may have been modified by some hooks
					$error = $hookmanager->error; $errors = $hookmanager->errors;

					// Show fields
					if (empty($reshook)) fieldListJournal($fieldlist, $obj, $tabname[$id], 'edit');

					print '<td class="center" colspan="4">';
					print '<input type="hidden" name="page" value="'.$page.'">';
					print '<input type="hidden" name="rowid" value="'.$rowid.'">';
					print '<input type="submit" class="button" name="actionmodify" value="'.$langs->trans("Modify").'">';
					print '<input type="submit" class="button" name="actioncancel" value="'.$langs->trans("Cancel").'">';
					print '<div name="'.(!empty($obj->rowid) ? $obj->rowid : $obj->code).'"></div>';
					print '</td>';
				}
				else
				{
				  	$tmpaction = 'view';
					$parameters = array('var'=>$var, 'fieldlist'=>$fieldlist, 'tabname'=>$tabname[$id]);
					$reshook = $hookmanager->executeHooks('viewDictionaryFieldlist', $parameters, $obj, $tmpaction); // Note that $action and $object may have been modified by some hooks

					$error = $hookmanager->error; $errors = $hookmanager->errors;

					if (empty($reshook))
					{
                        $langs->load("accountancy");
						foreach ($fieldlist as $field => $value)
						{
							$showfield = 1;
							$class = "left";
							$valuetoshow = $obj->{$fieldlist[$field]};
							if ($valuetoshow == 'all') {
								$valuetoshow = $langs->trans('All');
							}
							elseif ($fieldlist[$field] == 'nature' && $tabname[$id] == MAIN_DB_PREFIX.'accounting_journal') {
								$key = $langs->trans("AccountingJournalType".strtoupper($obj->nature));
								$valuetoshow = ($obj->nature && $key != "AccountingJournalType".strtoupper($langs->trans($obj->nature)) ? $key : $obj->{$fieldlist[$field]});
							}
							elseif ($fieldlist[$field] == 'label' && $tabname[$id] == MAIN_DB_PREFIX.'accounting_journal') {
								$valuetoshow = $langs->trans($obj->label);
                            }

							$class = 'tddict';
							// Show value for field
							if ($showfield) print '<!-- '.$fieldlist[$field].' --><td class="'.$class.'">'.$valuetoshow.'</td>';
						}
					}

					// Can an entry be erased or disabled ?
					$iserasable = 1; $canbedisabled = 1; $canbemodified = 1; // true by default
					if (isset($obj->code) && $id != 10) {
						if (($obj->code == '0' || $obj->code == '' || preg_match('/unknown/i', $obj->code))) {
                            $iserasable = 0;
                            $canbedisabled = 0;
                        } elseif ($obj->code == 'RECEP') {
                            $iserasable = 0;
                            $canbedisabled = 0;
                        } elseif ($obj->code == 'EF0') {
                            $iserasable = 0;
                            $canbedisabled = 0;
                        }
					}

					$canbemodified = $iserasable;

					$url = $_SERVER["PHP_SELF"].'?'.($page ? 'page='.$page.'&' : '').'sortfield='.$sortfield.'&sortorder='.$sortorder.'&rowid='.(!empty($obj->rowid) ? $obj->rowid : (!empty($obj->code) ? $obj->code : '')).'&code='.(!empty($obj->code) ?urlencode($obj->code) : '');
					if ($param) $url .= '&'.$param;
					$url .= '&';

					// Active
					print '<td class="nowrap center">';
					if ($canbedisabled) print '<a href="'.$url.'action='.$acts[$obj->active].'">'.$actl[$obj->active].'</a>';
					else print $langs->trans("AlwaysActive");
					print "</td>";

					// Modify link
					if ($canbemodified) print '<td class="center"><a class="reposition" href="'.$url.'action=edit">'.img_edit().'</a></td>';
					else print '<td>&nbsp;</td>';

					// Delete link
					if ($iserasable)
					{
						print '<td class="center">';
						if ($user->admin) print '<a href="'.$url.'action=delete">'.img_delete().'</a>';
						//else print '<a href="#">'.img_delete().'</a>';    // Some dictionary can be edited by other profile than admin
						print '</td>';
					}
					else print '<td>&nbsp;</td>';

					print '<td></td>';

					print '</td>';
				}

				print "</tr>\n";
				$i++;
			}
		}
	}
	else {
		dol_print_error($db);
	}

	print '</table>';
	print '</div>';

	print '</form>';
}

print '<br>';

// End of page
llxFooter();
$db->close();


/**
 *	Show fields in insert/edit mode
 *
 *  @param      array   $fieldlist      Array of fields
 *  @param      Object  $obj            If we show a particular record, obj is filled with record fields
 *  @param      string  $tabname        Name of SQL table
 *  @param      string  $context        'add'=Output field for the "add form", 'edit'=Output field for the "edit form", 'hide'=Output field for the "add form" but we dont want it to be rendered
 *  @return     void
 */
function fieldListJournal($fieldlist, $obj = '', $tabname = '', $context = '')
{
	global $conf, $langs, $db;
	global $form, $mysoc;
	global $region_id;
	global $elementList, $sourceList, $localtax_typeList;
	global $bc;

	$formadmin = new FormAdmin($db);
	$formcompany = new FormCompany($db);

	foreach ($fieldlist as $field => $value)
	{
		if ($fieldlist[$field] == 'nature')
		{
			print '<td>';
			print $form->selectarray('nature', $sourceList, (!empty($obj->{$fieldlist[$field]}) ? $obj->{$fieldlist[$field]}:''));
			print '</td>';
		}
		elseif ($fieldlist[$field] == 'code' && isset($obj->{$fieldlist[$field]})) {
			print '<td><input type="text" class="flat minwidth100" value="'.(!empty($obj->{$fieldlist[$field]}) ? $obj->{$fieldlist[$field]}:'').'" name="'.$fieldlist[$field].'"></td>';
		}
		else
		{
			print '<td>';
			$size = ''; $class = '';
			if ($fieldlist[$field] == 'code')  $class = 'maxwidth100';
			if ($fieldlist[$field] == 'label') $class = 'quatrevingtpercent';
			if ($fieldlist[$field] == 'sortorder' || $fieldlist[$field] == 'sens' || $fieldlist[$field] == 'category_type') $size = 'size="2" ';
			print '<input type="text" '.$size.'class="flat'.($class ? ' '.$class : '').'" value="'.(isset($obj->{$fieldlist[$field]}) ? $obj->{$fieldlist[$field]}:'').'" name="'.$fieldlist[$field].'">';
			print '</td>';
		}
	}
}
