<?php
/* Copyright (C) 2004       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
<<<<<<< HEAD
 * Copyright (C) 2004-2015  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2004       Benoit Mortier          <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@capnetworks.com>
 * Copyright (C) 2010-2016  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2011-2018  Philippe Grand          <philippe.grand@atoo-net.com>
 * Copyright (C) 2011       Remy Younes             <ryounes@gmail.com>
 * Copyright (C) 2012-2015  Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2012       Christophe Battarel     <christophe.battarel@ltairis.fr>
 * Copyright (C) 2011-2016  Alexandre Spangaro      <aspangaro@zendsi.com>
=======
 * Copyright (C) 2004-2019  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2004       Benoit Mortier          <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2010-2016  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2011-2019  Philippe Grand          <philippe.grand@atoo-net.com>
 * Copyright (C) 2011       Remy Younes             <ryounes@gmail.com>
 * Copyright (C) 2012-2015  Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2012       Christophe Battarel     <christophe.battarel@ltairis.fr>
 * Copyright (C) 2011-2016  Alexandre Spangaro      <aspangaro@open-dsi.fr>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 * Copyright (C) 2015       Ferran Marcet           <fmarcet@2byte.es>
 * Copyright (C) 2016       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
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
 *	    \file       htdocs/accountancy/admin/accountmodel.php
<<<<<<< HEAD
 *		\ingroup    Advanced accountancy
=======
 *		\ingroup    Accountancy (Double entries)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 *		\brief      Page to administer model of chart of accounts
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
if (! empty($conf->accounting->enabled)) require_once DOL_DOCUMENT_ROOT . '/core/class/html.formaccounting.class.php';

// Load translation files required by the page
$langs->loadLangs(array("errors","admin","companies","resource","holiday","compta","accountancy","hrm"));

<<<<<<< HEAD
$action=GETPOST('action','alpha')?GETPOST('action','alpha'):'view';
$confirm=GETPOST('confirm','alpha');
$id=31;
$rowid=GETPOST('rowid','alpha');
$code=GETPOST('code','alpha');

$acts[0] = "activate";
$acts[1] = "disable";
$actl[0] = img_picto($langs->trans("Disabled"),'switch_off');
$actl[1] = img_picto($langs->trans("Activated"),'switch_on');

$listoffset=GETPOST('listoffset');
$listlimit=GETPOST('listlimit')>0?GETPOST('listlimit'):1000;
$active = 1;

$sortfield = GETPOST("sortfield",'aZ09comma');
$sortorder = GETPOST("sortorder",'aZ09comma');
$page = GETPOST("page",'int');
=======
$action=GETPOST('action', 'aZ09')?GETPOST('action', 'aZ09'):'view';
$confirm=GETPOST('confirm', 'alpha');
$id=31;
$rowid=GETPOST('rowid', 'alpha');
$code=GETPOST('code', 'alpha');

$acts[0] = "activate";
$acts[1] = "disable";
$actl[0] = img_picto($langs->trans("Disabled"), 'switch_off');
$actl[1] = img_picto($langs->trans("Activated"), 'switch_on');

$listoffset=GETPOST('listoffset', 'alpha');
$listlimit=GETPOST('listlimit', 'int')>0?GETPOST('listlimit', 'int'):1000;
$active = 1;

$sortfield = GETPOST("sortfield", 'aZ09comma');
$sortorder = GETPOST("sortorder", 'aZ09comma');
$page = GETPOST("page", 'int');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $listlimit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

<<<<<<< HEAD
$search_country_id = GETPOST('search_country_id','int');
=======
$search_country_id = GETPOST('search_country_id', 'int');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9


// Security check
if ($user->societe_id > 0) accessforbidden();
if (! $user->rights->accounting->chartofaccount) accessforbidden();


// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('admin'));

// This page is a generic page to edit dictionaries
// Put here declaration of dictionaries properties

// Name of SQL tables of dictionaries
$tabname=array();

$tabname[31]= MAIN_DB_PREFIX."accounting_system";

// Dictionary labels
$tablib=array();
$tablib[31]= "Pcg_version";

// Requests to extract data
$tabsql=array();
$tabsql[31]= "SELECT s.rowid as rowid, pcg_version, s.label, s.fk_country as country_id, c.code as country_code, c.label as country, s.active FROM ".MAIN_DB_PREFIX."accounting_system as s, ".MAIN_DB_PREFIX."c_country as c WHERE s.fk_country=c.rowid and c.active=1";

// Criteria to sort dictionaries
$tabsqlsort=array();
$tabsqlsort[31]="pcg_version ASC";

// Nom des champs en resultat de select pour affichage du dictionnaire
$tabfield=array();
$tabfield[31]= "pcg_version,label,country_id,country";

// Nom des champs d'edition pour modification d'un enregistrement
$tabfieldvalue=array();
$tabfieldvalue[31]= "pcg_version,label,country";

// Nom des champs dans la table pour insertion d'un enregistrement
$tabfieldinsert=array();
$tabfieldinsert[31]= "pcg_version,label,fk_country";

// Nom du rowid si le champ n'est pas de type autoincrement
// Example: "" if id field is "rowid" and has autoincrement on
//          "nameoffield" if id field is not "rowid" or has not autoincrement on
$tabrowid=array();
$tabrowid[31]= "";

// Condition to show dictionary in setup page
$tabcond=array();
$tabcond[31]= ! empty($conf->accounting->enabled);

// List of help for fields
$tabhelp=array();
$tabhelp[31] = array('pcg_version'=>$langs->trans("EnterAnyCode"));

// List of check for fields (NOT USED YET)
$tabfieldcheck=array();
$tabfieldcheck[31] = array();


// Define elementList and sourceList (used for dictionary type of contacts "llx_c_type_contact")
$elementList = array();
$sourceList=array();



/*
 * Actions
 */

<<<<<<< HEAD
if (GETPOST('button_removefilter') || GETPOST('button_removefilter.x') || GETPOST('button_removefilter_x'))
=======
if (GETPOST('button_removefilter', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter_x', 'alpha'))
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
{
	$search_country_id = '';
}

// Actions add or modify an entry into a dictionary
<<<<<<< HEAD
if (GETPOST('actionadd') || GETPOST('actionmodify'))
{
	$listfield=explode(',', str_replace(' ', '',$tabfield[$id]));
	$listfieldinsert=explode(',',$tabfieldinsert[$id]);
	$listfieldmodify=explode(',',$tabfieldinsert[$id]);
	$listfieldvalue=explode(',',$tabfieldvalue[$id]);
=======
if (GETPOST('actionadd', 'alpha') || GETPOST('actionmodify', 'alpha'))
{
	$listfield=explode(',', str_replace(' ', '', $tabfield[$id]));
	$listfieldinsert=explode(',', $tabfieldinsert[$id]);
	$listfieldmodify=explode(',', $tabfieldinsert[$id]);
	$listfieldvalue=explode(',', $tabfieldvalue[$id]);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	// Check that all fields are filled
	$ok=1;
	foreach ($listfield as $f => $value)
	{
<<<<<<< HEAD
		if ($value == 'country_id' && in_array($tablib[$id],array('DictionaryVAT','DictionaryRegion','DictionaryCompanyType','DictionaryHolidayTypes','DictionaryRevenueStamp','DictionaryAccountancyCategory','Pcg_version'))) continue;		// For some pages, country is not mandatory
		if ($value == 'country' && in_array($tablib[$id],array('DictionaryCanton','DictionaryCompanyType','DictionaryRevenueStamp'))) continue;		// For some pages, country is not mandatory
=======
		if ($value == 'country_id' && in_array($tablib[$id], array('DictionaryVAT','DictionaryRegion','DictionaryCompanyType','DictionaryHolidayTypes','DictionaryRevenueStamp','DictionaryAccountancyCategory','Pcg_version'))) continue;		// For some pages, country is not mandatory
		if ($value == 'country' && in_array($tablib[$id], array('DictionaryCanton','DictionaryCompanyType','DictionaryRevenueStamp'))) continue;		// For some pages, country is not mandatory
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		if ($value == 'localtax1' && empty($_POST['localtax1_type'])) continue;
		if ($value == 'localtax2' && empty($_POST['localtax2_type'])) continue;
		if ($value == 'color' && empty($_POST['color'])) continue;
		if ($value == 'formula' && empty($_POST['formula'])) continue;
		if ((! isset($_POST[$value]) || $_POST[$value]=='')
			&& (! in_array($listfield[$f], array('decalage','module','accountancy_code','accountancy_code_sell','accountancy_code_buy'))  // Fields that are not mandatory
			&& (! ($id == 10 && $listfield[$f] == 'code')) // Code is mandatory fir table 10
			)
		)
		{
			$ok=0;
			$fieldnamekey=$listfield[$f];
			// We take translate key of field

			if ($fieldnamekey == 'pcg_version')  $fieldnamekey='Pcg_version';
			if ($fieldnamekey == 'libelle' || ($fieldnamekey == 'label'))  $fieldnamekey='Label';
			if ($fieldnamekey == 'libelle_facture') $fieldnamekey = 'LabelOnDocuments';
			if ($fieldnamekey == 'nbjour')   $fieldnamekey='NbOfDays';
			if ($fieldnamekey == 'decalage') $fieldnamekey='Offset';
			if ($fieldnamekey == 'module')   $fieldnamekey='Module';
			if ($fieldnamekey == 'code') $fieldnamekey = 'Code';
			if ($fieldnamekey == 'note') $fieldnamekey = 'Note';
			if ($fieldnamekey == 'taux') $fieldnamekey = 'Rate';
			if ($fieldnamekey == 'type') $fieldnamekey = 'Type';
			if ($fieldnamekey == 'position') $fieldnamekey = 'Position';
			if ($fieldnamekey == 'unicode') $fieldnamekey = 'Unicode';
			if ($fieldnamekey == 'deductible') $fieldnamekey = 'Deductible';
			if ($fieldnamekey == 'sortorder') $fieldnamekey = 'SortOrder';
			if ($fieldnamekey == 'category_type') $fieldnamekey = 'Calculated';

			setEventMessages($langs->transnoentities("ErrorFieldRequired", $langs->transnoentities($fieldnamekey)), null, 'errors');
		}
	}
	// Other checks
<<<<<<< HEAD
	if ($tabname[$id] == MAIN_DB_PREFIX."c_actioncomm" && isset($_POST["type"]) && in_array($_POST["type"],array('system','systemauto'))) {
=======
	if ($tabname[$id] == MAIN_DB_PREFIX."c_actioncomm" && isset($_POST["type"]) && in_array($_POST["type"], array('system','systemauto'))) {
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		$ok=0;
		setEventMessages($langs->transnoentities('ErrorReservedTypeSystemSystemAuto'), null, 'errors');
	}
	if (isset($_POST["code"]))
	{
		if ($_POST["code"]=='0')
		{
			$ok=0;
			setEventMessages($langs->transnoentities('ErrorCodeCantContainZero'), null, 'errors');
		}
		/*if (!is_numeric($_POST['code']))	// disabled, code may not be in numeric base
    	{
	    	$ok = 0;
	    	$msg .= $langs->transnoentities('ErrorFieldFormat', $langs->transnoentities('Code')).'<br>';
	    }*/
	}
	if (isset($_POST["country"]) && ($_POST["country"]=='0') && ($id != 2))
	{
<<<<<<< HEAD
		if (in_array($tablib[$id],array('DictionaryCompanyType','DictionaryHolidayTypes')))	// Field country is no mandatory for such dictionaries
=======
		if (in_array($tablib[$id], array('DictionaryCompanyType','DictionaryHolidayTypes')))	// Field country is no mandatory for such dictionaries
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		{
			$_POST["country"]='';
		}
		else
		{
			$ok=0;
<<<<<<< HEAD
			setEventMessages($langs->transnoentities("ErrorFieldRequired",$langs->transnoentities("Country")), null, 'errors');
		}
	}
	if ($id == 3 && ! is_numeric($_POST["code"]))
	{
	   	$ok=0;
	   	setEventMessages($langs->transnoentities("ErrorFieldMustBeANumeric",$langs->transnoentities("Code")), null, 'errors');
=======
			setEventMessages($langs->transnoentities("ErrorFieldRequired", $langs->transnoentities("Country")), null, 'errors');
		}
	}
	if (! is_numeric($_POST["code"]))
	{
	   	$ok=0;
	   	setEventMessages($langs->transnoentities("ErrorFieldMustBeANumeric", $langs->transnoentities("Code")), null, 'errors');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	}

	// Clean some parameters
	if (isset($_POST["localtax1"]) && empty($_POST["localtax1"])) $_POST["localtax1"]='0';	// If empty, we force to 0
	if (isset($_POST["localtax2"]) && empty($_POST["localtax2"])) $_POST["localtax2"]='0';	// If empty, we force to 0
	if ($_POST["accountancy_code"] <= 0) $_POST["accountancy_code"]='';	// If empty, we force to null
	if ($_POST["accountancy_code_sell"] <= 0) $_POST["accountancy_code_sell"]='';	// If empty, we force to null
	if ($_POST["accountancy_code_buy"] <= 0) $_POST["accountancy_code_buy"]='';	// If empty, we force to null

	// Si verif ok et action add, on ajoute la ligne
<<<<<<< HEAD
	if ($ok && GETPOST('actionadd'))
=======
	if ($ok && GETPOST('actionadd', 'alpha'))
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		if ($tabrowid[$id])
		{
			// Recupere id libre pour insertion
			$newid=0;
			$sql = "SELECT max(".$tabrowid[$id].") newid from ".$tabname[$id];
			$result = $db->query($sql);
			if ($result)
			{
				$obj = $db->fetch_object($result);
				$newid=($obj->newid + 1);
<<<<<<< HEAD

=======
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
			} else {
				dol_print_error($db);
			}
		}

		// Add new entry
		$sql = "INSERT INTO ".$tabname[$id]." (";
		// List of fields
<<<<<<< HEAD
		if ($tabrowid[$id] && ! in_array($tabrowid[$id],$listfieldinsert))
=======
		if ($tabrowid[$id] && ! in_array($tabrowid[$id], $listfieldinsert))
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
			$sql.= $tabrowid[$id].",";
		$sql.= $tabfieldinsert[$id];
		$sql.=",active)";
		$sql.= " VALUES(";

		// List of values
<<<<<<< HEAD
		if ($tabrowid[$id] && ! in_array($tabrowid[$id],$listfieldinsert))
=======
		if ($tabrowid[$id] && ! in_array($tabrowid[$id], $listfieldinsert))
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
			$sql.= $newid.",";
		$i=0;
		foreach ($listfieldinsert as $f => $value)
		{
<<<<<<< HEAD
			if ($value == 'price' || preg_match('/^amount/i',$value) || $value == 'taux') {
				$_POST[$listfieldvalue[$i]] = price2num($_POST[$listfieldvalue[$i]],'MU');
			}
			else if ($value == 'entity') {
=======
			if ($value == 'price' || preg_match('/^amount/i', $value) || $value == 'taux') {
				$_POST[$listfieldvalue[$i]] = price2num($_POST[$listfieldvalue[$i]], 'MU');
			}
			elseif ($value == 'entity') {
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
				$_POST[$listfieldvalue[$i]] = $conf->entity;
			}
			if ($i) $sql.=",";
			if ($_POST[$listfieldvalue[$i]] == '') $sql.="null";
			else $sql.="'".$db->escape($_POST[$listfieldvalue[$i]])."'";
			$i++;
		}
		$sql.=",1)";

		dol_syslog("actionadd", LOG_DEBUG);
		$result = $db->query($sql);
		if ($result)	// Add is ok
		{
			setEventMessages($langs->transnoentities("RecordSaved"), null, 'mesgs');
			$_POST=array('id'=>$id);	// Clean $_POST array, we keep only
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
<<<<<<< HEAD
	if ($ok && GETPOST('actionmodify'))
=======
	if ($ok && GETPOST('actionmodify', 'alpha'))
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		if ($tabrowid[$id]) { $rowidcol=$tabrowid[$id]; }
		else { $rowidcol="rowid"; }

		// Modify entry
		$sql = "UPDATE ".$tabname[$id]." SET ";
		// Modifie valeur des champs
<<<<<<< HEAD
		if ($tabrowid[$id] && ! in_array($tabrowid[$id],$listfieldmodify))
=======
		if ($tabrowid[$id] && ! in_array($tabrowid[$id], $listfieldmodify))
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		{
			$sql.= $tabrowid[$id]."=";
			$sql.= "'".$db->escape($rowid)."', ";
		}
		$i = 0;
		foreach ($listfieldmodify as $field)
		{
<<<<<<< HEAD
			if ($field == 'price' || preg_match('/^amount/i',$field) || $field == 'taux') {
				$_POST[$listfieldvalue[$i]] = price2num($_POST[$listfieldvalue[$i]],'MU');
			}
			else if ($field == 'entity') {
=======
			if ($field == 'price' || preg_match('/^amount/i', $field) || $field == 'taux') {
				$_POST[$listfieldvalue[$i]] = price2num($_POST[$listfieldvalue[$i]], 'MU');
			}
			elseif ($field == 'entity') {
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
				$_POST[$listfieldvalue[$i]] = $conf->entity;
			}
			if ($i) $sql.=",";
			$sql.= $field."=";
			if ($_POST[$listfieldvalue[$i]] == '') $sql.="null";
			else $sql.="'".$db->escape($_POST[$listfieldvalue[$i]])."'";
			$i++;
		}
		$sql.= " WHERE ".$rowidcol." = '".$rowid."'";

		dol_syslog("actionmodify", LOG_DEBUG);
		//print $sql;
		$resql = $db->query($sql);
		if (! $resql)
		{
			setEventMessages($db->error(), null, 'errors');
		}
	}
	//$_GET["id"]=GETPOST('id', 'int');       // Force affichage dictionnaire en cours d'edition
}

<<<<<<< HEAD
if (GETPOST('actioncancel'))
=======
if (GETPOST('actioncancel', 'alpha'))
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
{
	//$_GET["id"]=GETPOST('id', 'int');       // Force affichage dictionnaire en cours d'edition
}

if ($action == 'confirm_delete' && $confirm == 'yes')       // delete
{
	if ($tabrowid[$id]) { $rowidcol=$tabrowid[$id]; }
	else { $rowidcol="rowid"; }

	$sql = "DELETE from ".$tabname[$id]." WHERE ".$rowidcol."='".$rowid."'";

	dol_syslog("delete", LOG_DEBUG);
	$result = $db->query($sql);
	if (! $result)
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
	if ($tabrowid[$id]) { $rowidcol=$tabrowid[$id]; }
	else { $rowidcol="rowid"; }

	if ($rowid) {
		$sql = "UPDATE ".$tabname[$id]." SET active = 1 WHERE ".$rowidcol."='".$rowid."'";
	}
	elseif ($code) {
		$sql = "UPDATE ".$tabname[$id]." SET active = 1 WHERE code='".$code."'";
	}

	$result = $db->query($sql);
	if (!$result)
	{
		dol_print_error($db);
	}
}

// disable
if ($action == $acts[1])
{
	if ($tabrowid[$id]) { $rowidcol=$tabrowid[$id]; }
	else { $rowidcol="rowid"; }

	if ($rowid) {
		$sql = "UPDATE ".$tabname[$id]." SET active = 0 WHERE ".$rowidcol."='".$rowid."'";
	}
	elseif ($code) {
		$sql = "UPDATE ".$tabname[$id]." SET active = 0 WHERE code='".$code."'";
	}

	$result = $db->query($sql);
	if (!$result)
	{
		dol_print_error($db);
	}
}

// favorite
if ($action == 'activate_favorite')
{
	if ($tabrowid[$id]) { $rowidcol=$tabrowid[$id]; }
	else { $rowidcol="rowid"; }

	if ($rowid) {
		$sql = "UPDATE ".$tabname[$id]." SET favorite = 1 WHERE ".$rowidcol."='".$rowid."'";
	}
	elseif ($code) {
		$sql = "UPDATE ".$tabname[$id]." SET favorite = 1 WHERE code='".$code."'";
	}

	$result = $db->query($sql);
	if (!$result)
	{
		dol_print_error($db);
	}
}

// disable favorite
if ($action == 'disable_favorite')
{
	if ($tabrowid[$id]) { $rowidcol=$tabrowid[$id]; }
	else { $rowidcol="rowid"; }

	if ($rowid) {
		$sql = "UPDATE ".$tabname[$id]." SET favorite = 0 WHERE ".$rowidcol."='".$rowid."'";
	}
	elseif ($code) {
		$sql = "UPDATE ".$tabname[$id]." SET favorite = 0 WHERE code='".$code."'";
	}

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
$formadmin=new FormAdmin($db);

llxHeader();

$titre=$langs->trans($tablib[$id]);
$linkback='';

<<<<<<< HEAD
print load_fiche_titre($titre,$linkback,'title_accountancy');

if (empty($id))
{
	print $langs->trans("DictionaryDesc");
	print " ".$langs->trans("OnlyActiveElementsAreShown")."<br>\n";
}
print "<br>\n";
=======
print load_fiche_titre($titre, $linkback, 'title_accountancy');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9


// Confirmation de la suppression de la ligne
if ($action == 'delete')
{
<<<<<<< HEAD
	print $form->formconfirm($_SERVER["PHP_SELF"].'?'.($page?'page='.urlencode($page).'&':'').'sortfield='.urlencode($sortfield).'&sortorder='.urlencode($sortorder).'&rowid='.urlencode($rowid).'&code='.urlencode($code).'&id='.urlencode($id), $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_delete','',0,1);
=======
	print $form->formconfirm($_SERVER["PHP_SELF"].'?'.($page?'page='.urlencode($page).'&':'').'sortfield='.urlencode($sortfield).'&sortorder='.urlencode($sortorder).'&rowid='.urlencode($rowid).'&code='.urlencode($code).'&id='.urlencode($id), $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_delete', '', 0, 1);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
}
//var_dump($elementList);

/*
 * Show a dictionary
 */
if ($id)
{
	// Complete requete recherche valeurs avec critere de tri
	$sql=$tabsql[$id];

	if ($search_country_id > 0)
	{
<<<<<<< HEAD
		if (preg_match('/ WHERE /',$sql)) $sql.= " AND ";
=======
		if (preg_match('/ WHERE /', $sql)) $sql.= " AND ";
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		else $sql.=" WHERE ";
		$sql.= " c.rowid = ".$search_country_id;
	}

	// If sort order is "country", we use country_code instead
	if ($sortfield == 'country') $sortfield='country_code';
<<<<<<< HEAD
	$sql.=$db->order($sortfield,$sortorder);
	$sql.=$db->plimit($listlimit+1,$offset);
	//print $sql;

	$fieldlist=explode(',',$tabfield[$id]);
=======
	$sql.=$db->order($sortfield, $sortorder);
	$sql.=$db->plimit($listlimit+1, $offset);
	//print $sql;

	$fieldlist=explode(',', $tabfield[$id]);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$id.'" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

	print '<div class="div-table-responsive">';
	print '<table class="noborder" width="100%">';

	// Form to add a new line
	if ($tabname[$id])
	{
		$alabelisused=0;
		$var=false;

<<<<<<< HEAD
		$fieldlist=explode(',',$tabfield[$id]);
=======
		$fieldlist=explode(',', $tabfield[$id]);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

		// Line for title
		print '<tr class="liste_titre">';
		foreach ($fieldlist as $field => $value)
		{
			// Determine le nom du champ par rapport aux noms possibles
			// dans les dictionnaires de donnees
			$valuetoshow=ucfirst($fieldlist[$field]);   // Par defaut
			$valuetoshow=$langs->trans($valuetoshow);   // try to translate
<<<<<<< HEAD
			$align="left";
			if ($fieldlist[$field]=='source')          { $valuetoshow=$langs->trans("Contact"); }
			if ($fieldlist[$field]=='price')           { $valuetoshow=$langs->trans("PriceUHT"); }
			if ($fieldlist[$field]=='taux')            {
				if ($tabname[$id] != MAIN_DB_PREFIX."c_revenuestamp") $valuetoshow=$langs->trans("Rate");
				else $valuetoshow=$langs->trans("Amount");
				$align='right';
			}
			if ($fieldlist[$field]=='localtax1_type')  { $valuetoshow=$langs->trans("UseLocalTax")." 2"; $align="center"; $sortable=0; }
			if ($fieldlist[$field]=='localtax1')       { $valuetoshow=$langs->trans("Rate")." 2";}
			if ($fieldlist[$field]=='localtax2_type')  { $valuetoshow=$langs->trans("UseLocalTax")." 3"; $align="center"; $sortable=0; }
			if ($fieldlist[$field]=='localtax2')       { $valuetoshow=$langs->trans("Rate")." 3";}
			if ($fieldlist[$field]=='organization')    { $valuetoshow=$langs->trans("Organization"); }
			if ($fieldlist[$field]=='lang')            { $valuetoshow=$langs->trans("Language"); }
			if ($fieldlist[$field]=='type')            {
				if ($tabname[$id] == MAIN_DB_PREFIX."c_paiement") $valuetoshow=$form->textwithtooltip($langs->trans("Type"),$langs->trans("TypePaymentDesc"),2,1,img_help(1,''));
				else $valuetoshow=$langs->trans("Type");
			}
=======
			$class="left";
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
			if ($fieldlist[$field]=='code')            { $valuetoshow=$langs->trans("Code"); }
			if ($fieldlist[$field]=='libelle' || $fieldlist[$field]=='label')
			{
				$valuetoshow=$langs->trans("Label");
<<<<<<< HEAD
				if ($id != 25) $valuetoshow.="*";
			}
			if ($fieldlist[$field]=='libelle_facture') { $valuetoshow=$langs->trans("LabelOnDocuments")."*"; }
			if ($fieldlist[$field]=='country')         {
				if (in_array('region_id',$fieldlist)) { print '<td>&nbsp;</td>'; continue; }		// For region page, we do not show the country input
				$valuetoshow=$langs->trans("Country");
			}
			if ($fieldlist[$field]=='recuperableonly') { $valuetoshow=$langs->trans("NPR"); $align="center"; }
			if ($fieldlist[$field]=='nbjour')          { $valuetoshow=$langs->trans("NbOfDays"); }
			if ($fieldlist[$field]=='type_cdr')        { $valuetoshow=$langs->trans("AtEndOfMonth"); $align="center"; }
			if ($fieldlist[$field]=='decalage')        { $valuetoshow=$langs->trans("Offset"); }
			if ($fieldlist[$field]=='width')           { $valuetoshow=$langs->trans("Width"); }
			if ($fieldlist[$field]=='height')          { $valuetoshow=$langs->trans("Height"); }
			if ($fieldlist[$field]=='unit')            { $valuetoshow=$langs->trans("MeasuringUnit"); }
			if ($fieldlist[$field]=='region_id' || $fieldlist[$field]=='country_id') { $valuetoshow=''; }
			if ($fieldlist[$field]=='accountancy_code'){ $valuetoshow=$langs->trans("AccountancyCode"); }
			if ($fieldlist[$field]=='accountancy_code_sell'){ $valuetoshow=$langs->trans("AccountancyCodeSell"); }
			if ($fieldlist[$field]=='accountancy_code_buy'){ $valuetoshow=$langs->trans("AccountancyCodeBuy"); }
			if ($fieldlist[$field]=='pcg_version' || $fieldlist[$field]=='fk_pcg_version') { $valuetoshow=$langs->trans("Pcg_version"); }
			if ($fieldlist[$field]=='account_parent')  { $valuetoshow=$langs->trans("Accountparent"); }
			if ($fieldlist[$field]=='pcg_type')        { $valuetoshow=$langs->trans("Pcg_type"); }
			if ($fieldlist[$field]=='pcg_subtype')     { $valuetoshow=$langs->trans("Pcg_subtype"); }
			if ($fieldlist[$field]=='sortorder')       { $valuetoshow=$langs->trans("SortOrder"); }
			if ($fieldlist[$field]=='short_label')     { $valuetoshow=$langs->trans("ShortLabel"); }
			if ($fieldlist[$field]=='type_template')   { $valuetoshow=$langs->trans("TypeOfTemplate"); }
			if ($fieldlist[$field]=='range_account')   { $valuetoshow=$langs->trans("Range"); }
			if ($fieldlist[$field]=='sens')            { $valuetoshow=$langs->trans("Sens"); }
			if ($fieldlist[$field]=='category_type')   { $valuetoshow=$langs->trans("Calculated"); }
			if ($fieldlist[$field]=='formula')         { $valuetoshow=$langs->trans("Formula"); }

			if ($id == 2)	// Special cas for state page
			{
				if ($fieldlist[$field]=='region_id') { $valuetoshow='&nbsp;'; $showfield=1; }
				if ($fieldlist[$field]=='region') { $valuetoshow=$langs->trans("Country").'/'.$langs->trans("Region"); $showfield=1; }
			}

			if ($valuetoshow != '')
			{
				print '<td align="'.$align.'">';
				if (! empty($tabhelp[$id][$value]) && preg_match('/^http(s*):/i',$tabhelp[$id][$value])) print '<a href="'.$tabhelp[$id][$value].'" target="_blank">'.$valuetoshow.' '.img_help(1,$valuetoshow).'</a>';
				else if (! empty($tabhelp[$id][$value])) print $form->textwithpicto($valuetoshow,$tabhelp[$id][$value]);
				else print $valuetoshow;
				print '</td>';
			 }
			 if ($fieldlist[$field]=='libelle' || $fieldlist[$field]=='label') $alabelisused=1;
=======
			}
			if ($fieldlist[$field]=='country')         {
				if (in_array('region_id', $fieldlist)) { print '<td>&nbsp;</td>'; continue; }		// For region page, we do not show the country input
				$valuetoshow=$langs->trans("Country");
			}
			if ($fieldlist[$field]=='country_id') { $valuetoshow=''; }
			if ($fieldlist[$field]=='pcg_version' || $fieldlist[$field]=='fk_pcg_version') { $valuetoshow=$langs->trans("Pcg_version"); }

			if ($valuetoshow != '') {
				print '<td class="'.$class.'">';
                if (! empty($tabhelp[$id][$value]) && preg_match('/^http(s*):/i', $tabhelp[$id][$value])) {
                    print '<a href="'.$tabhelp[$id][$value].'" target="_blank">'.$valuetoshow.' '.img_help(1, $valuetoshow).'</a>';
                } elseif (! empty($tabhelp[$id][$value])) {
                    print $form->textwithpicto($valuetoshow, $tabhelp[$id][$value]);
                } else {
                    print $valuetoshow;
                }
				print '</td>';
			}
			if ($fieldlist[$field]=='libelle' || $fieldlist[$field]=='label') $alabelisused=1;
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		}

		print '<td>';
		print '<input type="hidden" name="id" value="'.$id.'">';
		print '</td>';
		print '<td style="min-width: 26px;"></td>';
		print '<td style="min-width: 26px;"></td>';
		print '</tr>';

		// Line to enter new values
		print "<tr ".$bcnd[$var].">";

		$obj = new stdClass();
		// If data was already input, we define them in obj to populate input fields.
<<<<<<< HEAD
		if (GETPOST('actionadd'))
=======
		if (GETPOST('actionadd', 'alpha'))
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		{
			foreach ($fieldlist as $key=>$val)
			{
				if (GETPOST($val))
					$obj->$val=GETPOST($val);
			}
		}

		$tmpaction = 'create';
		$parameters=array('fieldlist'=>$fieldlist, 'tabname'=>$tabname[$id]);
<<<<<<< HEAD
		$reshook=$hookmanager->executeHooks('createDictionaryFieldlist',$parameters, $obj, $tmpaction);    // Note that $action and $object may have been modified by some hooks
=======
		$reshook=$hookmanager->executeHooks('createDictionaryFieldlist', $parameters, $obj, $tmpaction);    // Note that $action and $object may have been modified by some hooks
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		$error=$hookmanager->error; $errors=$hookmanager->errors;

		if (empty($reshook))
		{
<<<<<<< HEAD
			fieldListAccountModel($fieldlist,$obj,$tabname[$id],'add');
		}

		print '<td colspan="3" align="right">';
=======
			fieldListAccountModel($fieldlist, $obj, $tabname[$id], 'add');
		}

		print '<td colspan="3" class="right">';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		print '<input type="submit" class="button" name="actionadd" value="'.$langs->trans("Add").'">';
		print '</td>';
		print "</tr>";

		$colspan=count($fieldlist)+3;

<<<<<<< HEAD
		if (! empty($alabelisused))  // If there is one label among fields, we show legend of *
		{
			print '<tr><td colspan="'.$colspan.'">* '.$langs->trans("LabelUsedByDefault").'.</td></tr>';
		}
=======
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		print '<tr><td colspan="'.$colspan.'">&nbsp;</td></tr>';	// Keep &nbsp; to have a line with enough height
	}



	// List of available values in database
	dol_syslog("htdocs/admin/dict", LOG_DEBUG);
	$resql=$db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0;

		$param = '&id='.$id;
		if ($search_country_id > 0) $param.= '&search_country_id='.$search_country_id;
		$paramwithsearch = $param;
		if ($sortorder) $paramwithsearch.= '&sortorder='.$sortorder;
		if ($sortfield) $paramwithsearch.= '&sortfield='.$sortfield;

		// There is several pages
		if ($num > $listlimit)
		{
<<<<<<< HEAD
			print '<tr class="none"><td align="right" colspan="'.(3+count($fieldlist)).'">';
=======
			print '<tr class="none"><td class="right" colspan="'.(3+count($fieldlist)).'">';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
			print_fleche_navigation($page, $_SERVER["PHP_SELF"], $paramwithsearch, ($num > $listlimit), '<li class="pagination"><span>'.$langs->trans("Page").' '.($page+1).'</span></li>');
			print '</td></tr>';
		}

		// Title line with search boxes
		print '<tr class="liste_titre liste_titre_add">';
		foreach ($fieldlist as $field => $value)
		{
			$showfield=1;							  	// By defaut

			if ($fieldlist[$field]=='region_id' || $fieldlist[$field]=='country_id') { $showfield=0; }

			if ($showfield)
			{
				if ($value == 'country')
				{
					print '<td class="liste_titre">';
					print $form->select_country($search_country_id, 'search_country_id', '', 28, 'maxwidth200 maxwidthonsmartphone');
					print '</td>';
				}
				else
				{
					print '<td class="liste_titre"></td>';
				}
			}
		}
		print '<td class="liste_titre"></td>';
<<<<<<< HEAD
		print '<td class="liste_titre" colspan="2" align="right">';
=======
		print '<td class="liste_titre right" colspan="2">';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		$searchpicto=$form->showFilterAndCheckAddButtons(0);
		print $searchpicto;
		print '</td>';
		print '</tr>';

		// Title of lines
		print '<tr class="liste_titre">';
		foreach ($fieldlist as $field => $value)
		{
			// Determine le nom du champ par rapport aux noms possibles
			// dans les dictionnaires de donnees
			$showfield=1;							  	// By defaut
<<<<<<< HEAD
			$align="left";
=======
			$class="left";
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
			$sortable=1;
			$valuetoshow='';
			/*
            $tmparray=getLabelOfField($fieldlist[$field]);
            $showfield=$tmp['showfield'];
            $valuetoshow=$tmp['valuetoshow'];
            $align=$tmp['align'];
            $sortable=$tmp['sortable'];
			*/
			$valuetoshow=ucfirst($fieldlist[$field]);   // By defaut
			$valuetoshow=$langs->trans($valuetoshow);   // try to translate
<<<<<<< HEAD
			if ($fieldlist[$field]=='source')          { $valuetoshow=$langs->trans("Contact"); }
			if ($fieldlist[$field]=='price')           { $valuetoshow=$langs->trans("PriceUHT"); }
			if ($fieldlist[$field]=='taux')            {
				if ($tabname[$id] != MAIN_DB_PREFIX."c_revenuestamp") $valuetoshow=$langs->trans("Rate");
				else $valuetoshow=$langs->trans("Amount");
				$align='right';
			}
			if ($fieldlist[$field]=='localtax1_type')  { $valuetoshow=$langs->trans("UseLocalTax")." 2"; $align="center"; $sortable=0; }
			if ($fieldlist[$field]=='localtax1')       { $valuetoshow=$langs->trans("Rate")." 2"; $sortable=0; }
			if ($fieldlist[$field]=='localtax2_type')  { $valuetoshow=$langs->trans("UseLocalTax")." 3"; $align="center"; $sortable=0; }
			if ($fieldlist[$field]=='localtax2')       { $valuetoshow=$langs->trans("Rate")." 3"; $sortable=0; }
			if ($fieldlist[$field]=='organization')    { $valuetoshow=$langs->trans("Organization"); }
			if ($fieldlist[$field]=='lang')            { $valuetoshow=$langs->trans("Language"); }
			if ($fieldlist[$field]=='type')            { $valuetoshow=$langs->trans("Type"); }
			if ($fieldlist[$field]=='code')            { $valuetoshow=$langs->trans("Code"); }
			if ($fieldlist[$field]=='libelle' || $fieldlist[$field]=='label')
			{
				$valuetoshow=$langs->trans("Label");
			   	if ($id != 25) $valuetoshow.="*";
			}
			if ($fieldlist[$field]=='libelle_facture') { $valuetoshow=$langs->trans("LabelOnDocuments")."*"; }
			if ($fieldlist[$field]=='country')         { $valuetoshow=$langs->trans("Country"); }
			if ($fieldlist[$field]=='recuperableonly') { $valuetoshow=$langs->trans("NPR"); $align="center"; }
			if ($fieldlist[$field]=='nbjour')          { $valuetoshow=$langs->trans("NbOfDays"); }
			if ($fieldlist[$field]=='type_cdr')        { $valuetoshow=$langs->trans("AtEndOfMonth"); $align="center"; }
			if ($fieldlist[$field]=='decalage')        { $valuetoshow=$langs->trans("Offset"); }
			if ($fieldlist[$field]=='width')           { $valuetoshow=$langs->trans("Width"); }
			if ($fieldlist[$field]=='height')          { $valuetoshow=$langs->trans("Height"); }
			if ($fieldlist[$field]=='unit')            { $valuetoshow=$langs->trans("MeasuringUnit"); }
			if ($fieldlist[$field]=='region_id' || $fieldlist[$field]=='country_id') { $showfield=0; }
			if ($fieldlist[$field]=='accountancy_code'){ $valuetoshow=$langs->trans("AccountancyCode"); }
			if ($fieldlist[$field]=='accountancy_code_sell'){ $valuetoshow=$langs->trans("AccountancyCodeSell"); $sortable=0; }
			if ($fieldlist[$field]=='accountancy_code_buy'){ $valuetoshow=$langs->trans("AccountancyCodeBuy"); $sortable=0; }
			if ($fieldlist[$field]=='fk_pcg_version')  { $valuetoshow=$langs->trans("Pcg_version"); }
			if ($fieldlist[$field]=='account_parent')  { $valuetoshow=$langs->trans("Accountsparent"); }
			if ($fieldlist[$field]=='pcg_type')        { $valuetoshow=$langs->trans("Pcg_type"); }
			if ($fieldlist[$field]=='pcg_subtype')     { $valuetoshow=$langs->trans("Pcg_subtype"); }
			if ($fieldlist[$field]=='sortorder')       { $valuetoshow=$langs->trans("SortOrder"); }
			if ($fieldlist[$field]=='short_label')     { $valuetoshow=$langs->trans("ShortLabel"); }
			if ($fieldlist[$field]=='type_template')   { $valuetoshow=$langs->trans("TypeOfTemplate"); }
			if ($fieldlist[$field]=='range_account')   { $valuetoshow=$langs->trans("Range"); }
			if ($fieldlist[$field]=='sens')            { $valuetoshow=$langs->trans("Sens"); }
			if ($fieldlist[$field]=='category_type')   { $valuetoshow=$langs->trans("Calculated"); }
			if ($fieldlist[$field]=='formula')         { $valuetoshow=$langs->trans("Formula"); }

			// Affiche nom du champ
			if ($showfield)
			{
				print getTitleFieldOfList($valuetoshow, 0, $_SERVER["PHP_SELF"], ($sortable?$fieldlist[$field]:''), ($page?'page='.$page.'&':''), $param, "align=".$align, $sortfield, $sortorder);
			}
		}
		print getTitleFieldOfList($langs->trans("Status"), 0, $_SERVER["PHP_SELF"], "active", ($page?'page='.$page.'&':''), $param, 'align="center"', $sortfield, $sortorder);
=======
			if ($fieldlist[$field]=='code') {
                $valuetoshow=$langs->trans("Code");
            }
            if ($fieldlist[$field]=='libelle' || $fieldlist[$field]=='label') {
                $valuetoshow=$langs->trans("Label");
            }
            if ($fieldlist[$field]=='country') {
                $valuetoshow=$langs->trans("Country");
            }
            if ($fieldlist[$field]=='country_id') {
                $showfield=0;
            }
            if ($fieldlist[$field]=='fk_pcg_version') {
                $valuetoshow=$langs->trans("Pcg_version");
            }

			// Affiche nom du champ
			if ($showfield) {
				print getTitleFieldOfList($valuetoshow, 0, $_SERVER["PHP_SELF"], ($sortable?$fieldlist[$field]:''), ($page?'page='.$page.'&':''), $param, "", $sortfield, $sortorder, $class.' ');
			}
		}
		print getTitleFieldOfList($langs->trans("Status"), 0, $_SERVER["PHP_SELF"], "active", ($page?'page='.$page.'&':''), $param, '', $sortfield, $sortorder, 'center ');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
				if ($action == 'edit' && ($rowid == (! empty($obj->rowid)?$obj->rowid:$obj->code)))
				{
					print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$id.'" method="POST">';
					print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
					print '<input type="hidden" name="page" value="'.$page.'">';
					print '<input type="hidden" name="rowid" value="'.$rowid.'">';

					$tmpaction='edit';
					$parameters=array('fieldlist'=>$fieldlist, 'tabname'=>$tabname[$id]);
<<<<<<< HEAD
					$reshook=$hookmanager->executeHooks('editDictionaryFieldlist',$parameters,$obj, $tmpaction);    // Note that $action and $object may have been modified by some hooks
					$error=$hookmanager->error; $errors=$hookmanager->errors;

					if (empty($reshook)) fieldListAccountModel($fieldlist,$obj,$tabname[$id],'edit');

					print '<td colspan="3" align="right"><a name="'.(! empty($obj->rowid)?$obj->rowid:$obj->code).'">&nbsp;</a><input type="submit" class="button" name="actionmodify" value="'.$langs->trans("Modify").'">';
=======
					$reshook=$hookmanager->executeHooks('editDictionaryFieldlist', $parameters, $obj, $tmpaction);    // Note that $action and $object may have been modified by some hooks
					$error=$hookmanager->error; $errors=$hookmanager->errors;

					if (empty($reshook)) fieldListAccountModel($fieldlist, $obj, $tabname[$id], 'edit');

					print '<td colspan="3" class="right"><a name="'.(! empty($obj->rowid)?$obj->rowid:$obj->code).'">&nbsp;</a><input type="submit" class="button" name="actionmodify" value="'.$langs->trans("Modify").'">';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
					print '&nbsp;<input type="submit" class="button" name="actioncancel" value="'.$langs->trans("Cancel").'"></td>';
				}
				else
				{
				  	$tmpaction = 'view';
					$parameters=array('var'=>$var, 'fieldlist'=>$fieldlist, 'tabname'=>$tabname[$id]);
<<<<<<< HEAD
					$reshook=$hookmanager->executeHooks('viewDictionaryFieldlist',$parameters,$obj, $tmpaction);    // Note that $action and $object may have been modified by some hooks
=======
					$reshook=$hookmanager->executeHooks('viewDictionaryFieldlist', $parameters, $obj, $tmpaction);    // Note that $action and $object may have been modified by some hooks
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

					$error=$hookmanager->error; $errors=$hookmanager->errors;

					if (empty($reshook))
					{
						foreach ($fieldlist as $field => $value)
						{

							$showfield=1;
<<<<<<< HEAD
							$align="left";
=======
							$class="left";
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
							$valuetoshow=$obj->{$fieldlist[$field]};
							if ($value == 'type_template')
							{
								$valuetoshow = isset($elementList[$valuetoshow])?$elementList[$valuetoshow]:$valuetoshow;
							}
							if ($value == 'element')
							{
								$valuetoshow = isset($elementList[$valuetoshow])?$elementList[$valuetoshow]:$valuetoshow;
							}
<<<<<<< HEAD
							else if ($value == 'source')
							{
								$valuetoshow = isset($sourceList[$valuetoshow])?$sourceList[$valuetoshow]:$valuetoshow;
							}
							else if ($valuetoshow=='all') {
								$valuetoshow=$langs->trans('All');
							}
							else if ($fieldlist[$field]=='country') {
=======
							elseif ($value == 'source')
							{
								$valuetoshow = isset($sourceList[$valuetoshow])?$sourceList[$valuetoshow]:$valuetoshow;
							}
							elseif ($valuetoshow=='all') {
								$valuetoshow=$langs->trans('All');
							}
							elseif ($fieldlist[$field]=='country') {
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
								if (empty($obj->country_code))
								{
									$valuetoshow='-';
								}
								else
								{
									$key=$langs->trans("Country".strtoupper($obj->country_code));
									$valuetoshow=($key != "Country".strtoupper($obj->country_code)?$obj->country_code." - ".$key:$obj->country);
								}
							}
<<<<<<< HEAD
							else if ($fieldlist[$field]=='recuperableonly' || $fieldlist[$field]=='type_cdr' || $fieldlist[$field] == 'deductible' || $fieldlist[$field] == 'category_type') {
								if(empty($valuetoshow)) $valuetoshow = $langs->trans('None');
								elseif($valuetoshow == 1) $valuetoshow = $langs->trans('AtEndOfMonth');
								elseif($valuetoshow == 2) $valuetoshow = $langs->trans('CurrentNext');
								$align="center";
							}
							else if ($fieldlist[$field]=='price' || preg_match('/^amount/i',$fieldlist[$field])) {
								$valuetoshow=price($valuetoshow);
							}
							else if ($fieldlist[$field]=='libelle_facture') {
								$langs->loadLangs(array("bills"));
								$key=$langs->trans("PaymentCondition".strtoupper($obj->code));
								$valuetoshow=($obj->code && $key != "PaymentCondition".strtoupper($obj->code)?$key:$obj->{$fieldlist[$field]});
								$valuetoshow=nl2br($valuetoshow);
							}
							else if ($fieldlist[$field]=='label' && $tabname[$id]==MAIN_DB_PREFIX.'c_country') {
								$key=$langs->trans("Country".strtoupper($obj->code));
								$valuetoshow=($obj->code && $key != "Country".strtoupper($obj->code)?$key:$obj->{$fieldlist[$field]});
							}
							else if ($fieldlist[$field]=='label' && $tabname[$id]==MAIN_DB_PREFIX.'c_availability') {
								$langs->loadLangs(array("propal"));
								$key=$langs->trans("AvailabilityType".strtoupper($obj->code));
								$valuetoshow=($obj->code && $key != "AvailabilityType".strtoupper($obj->code)?$key:$obj->{$fieldlist[$field]});
							}
							else if ($fieldlist[$field]=='libelle' && $tabname[$id]==MAIN_DB_PREFIX.'c_actioncomm') {
								$key=$langs->trans("Action".strtoupper($obj->code));
								$valuetoshow=($obj->code && $key != "Action".strtoupper($obj->code)?$key:$obj->{$fieldlist[$field]});
							}
							else if (! empty($obj->code_iso) && $fieldlist[$field]=='label' && $tabname[$id]==MAIN_DB_PREFIX.'c_currencies') {
								$key=$langs->trans("Currency".strtoupper($obj->code_iso));
								$valuetoshow=($obj->code_iso && $key != "Currency".strtoupper($obj->code_iso)?$key:$obj->{$fieldlist[$field]});
							}
							else if ($fieldlist[$field]=='libelle' && $tabname[$id]==MAIN_DB_PREFIX.'c_typent') {
								$key=$langs->trans(strtoupper($obj->code));
								$valuetoshow=($key != strtoupper($obj->code)?$key:$obj->{$fieldlist[$field]});
							}
							else if ($fieldlist[$field]=='libelle' && $tabname[$id]==MAIN_DB_PREFIX.'c_prospectlevel') {
								$key=$langs->trans(strtoupper($obj->code));
								$valuetoshow=($key != strtoupper($obj->code)?$key:$obj->{$fieldlist[$field]});
							}
							else if ($fieldlist[$field]=='label' && $tabname[$id]==MAIN_DB_PREFIX.'c_civility') {
								$key=$langs->trans("Civility".strtoupper($obj->code));
								$valuetoshow=($obj->code && $key != "Civility".strtoupper($obj->code)?$key:$obj->{$fieldlist[$field]});
							}
							else if ($fieldlist[$field]=='libelle' && $tabname[$id]==MAIN_DB_PREFIX.'c_type_contact') {
								$langs->loadLangs(array("agenda"));
								$key=$langs->trans("TypeContact_".$obj->element."_".$obj->source."_".strtoupper($obj->code));
								$valuetoshow=($obj->code && $key != "TypeContact_".$obj->element."_".$obj->source."_".strtoupper($obj->code)?$key:$obj->{$fieldlist[$field]});
							}
							else if ($fieldlist[$field]=='libelle' && $tabname[$id]==MAIN_DB_PREFIX.'c_payment_term') {
								$langs->loadLangs(array("bills"));
								$key=$langs->trans("PaymentConditionShort".strtoupper($obj->code));
								$valuetoshow=($obj->code && $key != "PaymentConditionShort".strtoupper($obj->code)?$key:$obj->{$fieldlist[$field]});
							}
							else if ($fieldlist[$field]=='libelle' && $tabname[$id]==MAIN_DB_PREFIX.'c_paiement') {
								$langs->loadLangs(array("bills"));
								$key=$langs->trans("PaymentType".strtoupper($obj->code));
								$valuetoshow=($obj->code && $key != "PaymentType".strtoupper($obj->code)?$key:$obj->{$fieldlist[$field]});
							}
							else if ($fieldlist[$field]=='label' && $tabname[$id]==MAIN_DB_PREFIX.'c_input_reason') {
								$key=$langs->trans("DemandReasonType".strtoupper($obj->code));
								$valuetoshow=($obj->code && $key != "DemandReasonType".strtoupper($obj->code)?$key:$obj->{$fieldlist[$field]});
							}
							else if ($fieldlist[$field]=='libelle' && $tabname[$id]==MAIN_DB_PREFIX.'c_input_method') {
								$langs->loadLangs(array("orders"));
								$key=$langs->trans($obj->code);
								$valuetoshow=($obj->code && $key != $obj->code)?$key:$obj->{$fieldlist[$field]};
							}
							else if ($fieldlist[$field]=='libelle' && $tabname[$id]==MAIN_DB_PREFIX.'c_shipment_mode') {
								$langs->loadLangs(array("sendings"));
								$key=$langs->trans("SendingMethod".strtoupper($obj->code));
								$valuetoshow=($obj->code && $key != "SendingMethod".strtoupper($obj->code)?$key:$obj->{$fieldlist[$field]});
							}
							else if ($fieldlist[$field] == 'libelle' && $tabname[$id]==MAIN_DB_PREFIX.'c_paper_format')
							{
								$key = $langs->trans('PaperFormat'.strtoupper($obj->code));
								$valuetoshow = ($obj->code && $key != 'PaperFormat'.strtoupper($obj->code) ? $key : $obj->{$fieldlist[$field]});
							}
							else if ($fieldlist[$field] == 'label' && $tabname[$id] == MAIN_DB_PREFIX.'c_type_fees')
							{
								$langs->loadLangs(array("trips"));
								$key = $langs->trans(strtoupper($obj->code));
								$valuetoshow = ($obj->code && $key != strtoupper($obj->code) ? $key : $obj->{$fieldlist[$field]});
							}
							else if ($fieldlist[$field]=='region_id' || $fieldlist[$field]=='country_id') {
								$showfield=0;
							}
							else if ($fieldlist[$field]=='unicode') {
								$valuetoshow = $langs->getCurrencySymbol($obj->code,1);
							}
							else if ($fieldlist[$field]=='label' && $tabname[$_GET["id"]]==MAIN_DB_PREFIX.'c_units') {
								$langs->loadLangs(array("products"));
								$valuetoshow=$langs->trans($obj->{$fieldlist[$field]});
							}
							else if ($fieldlist[$field]=='short_label' && $tabname[$_GET["id"]]==MAIN_DB_PREFIX.'c_units') {
								$langs->loadLangs(array("products"));
								$valuetoshow = $langs->trans($obj->{$fieldlist[$field]});
							}
							else if (($fieldlist[$field] == 'unit') && ($tabname[$id] == MAIN_DB_PREFIX.'c_paper_format'))
							{
								$key = $langs->trans('SizeUnit'.strtolower($obj->unit));
								$valuetoshow = ($obj->code && $key != 'SizeUnit'.strtolower($obj->unit) ? $key : $obj->{$fieldlist[$field]});
							}
							else if ($fieldlist[$field]=='taux') {
								$valuetoshow = price($valuetoshow, 0, $langs, 0, 0);
								$align="right";
							}
							else if (in_array($fieldlist[$field],array('recuperableonly')))
							{
								$align="center";
							}
							else if ($fieldlist[$field]=='accountancy_code' || $fieldlist[$field]=='accountancy_code_sell' || $fieldlist[$field]=='accountancy_code_buy') {
								$valuetoshow = length_accountg($valuetoshow);
							}
=======
							elseif ($fieldlist[$field]=='country_id') {
								$showfield=0;
							}
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

							$class='tddict';
							if ($fieldlist[$field] == 'tracking') $class.=' tdoverflowauto';
							// Show value for field
<<<<<<< HEAD
							if ($showfield) print '<!-- '.$fieldlist[$field].' --><td align="'.$align.'" class="'.$class.'">'.$valuetoshow.'</td>';
=======
							if ($showfield) print '<!-- '.$fieldlist[$field].' --><td class="'.$class.'">'.$valuetoshow.'</td>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
						}
					}

					// Can an entry be erased or disabled ?
					$iserasable=1;$canbedisabled=1;$canbemodified=1;	// true by default
<<<<<<< HEAD
					if (isset($obj->code) && $id != 10)
					{
						if (($obj->code == '0' || $obj->code == '' || preg_match('/unknown/i',$obj->code))) { $iserasable = 0; $canbedisabled = 0; }
						else if ($obj->code == 'RECEP') { $iserasable = 0; $canbedisabled = 0; }
						else if ($obj->code == 'EF0')   { $iserasable = 0; $canbedisabled = 0; }
					}

					if (isset($obj->type) && in_array($obj->type, array('system', 'systemauto'))) { $iserasable=0; }
					if (in_array($obj->code, array('AC_OTH','AC_OTH_AUTO')) || in_array($obj->type, array('systemauto'))) { $canbedisabled=0; $canbedisabled = 0; }
					$canbemodified=$iserasable;
					if ($obj->code == 'RECEP') $canbemodified=1;
=======
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

					$url = $_SERVER["PHP_SELF"].'?'.($page?'page='.$page.'&':'').'sortfield='.$sortfield.'&sortorder='.$sortorder.'&rowid='.(! empty($obj->rowid)?$obj->rowid:(! empty($obj->code)?$obj->code:'')).'&code='.(! empty($obj->code)?urlencode($obj->code):'');
					if ($param) $url .= '&'.$param;
					$url.='&';

					// Active
<<<<<<< HEAD
					print '<td align="center" class="nowrap">';
=======
					print '<td class="center nowrap">';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
					if ($canbedisabled) print '<a href="'.$url.'action='.$acts[$obj->active].'">'.$actl[$obj->active].'</a>';
					else print $langs->trans("AlwaysActive");
					print "</td>";

					// Modify link
<<<<<<< HEAD
					if ($canbemodified) print '<td align="center"><a class="reposition" href="'.$url.'action=edit">'.img_edit().'</a></td>';
					else print '<td>&nbsp;</td>';

					// Delete link
					if ($iserasable) print '<td align="center"><a href="'.$url.'action=delete">'.img_delete().'</a></td>';
=======
					if ($canbemodified) print '<td class="center"><a class="reposition" href="'.$url.'action=edit">'.img_edit().'</a></td>';
					else print '<td>&nbsp;</td>';

					// Delete link
					if ($iserasable) print '<td class="center"><a href="'.$url.'action=delete">'.img_delete().'</a></td>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
					else print '<td>&nbsp;</td>';

					print "</tr>\n";
				}
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

<<<<<<< HEAD

=======
// End of page
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
<<<<<<< HEAD
function fieldListAccountModel($fieldlist, $obj='', $tabname='', $context='')
=======
function fieldListAccountModel($fieldlist, $obj = '', $tabname = '', $context = '')
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
{
	global $conf,$langs,$db;
	global $form;
	global $region_id;
	global $elementList,$sourceList;

	$formadmin = new FormAdmin($db);
	$formcompany = new FormCompany($db);
	$formaccounting = new FormAccounting($db);

	foreach ($fieldlist as $field => $value)
	{
		if ($fieldlist[$field] == 'country')
		{
<<<<<<< HEAD
			if (in_array('region_id',$fieldlist))
=======
			if (in_array('region_id', $fieldlist))
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
			{
				print '<td>';
				//print join(',',$fieldlist);
				print '</td>';
				continue;
			}	// For state page, we do not show the country input (we link to region, not country)
			print '<td>';
			$fieldname='country';
			print $form->select_country((! empty($obj->country_code)?$obj->country_code:(! empty($obj->country)?$obj->country:'')), $fieldname, '', 28, 'maxwidth200 maxwidthonsmartphone');
			print '</td>';
		}
		elseif ($fieldlist[$field] == 'country_id')
		{
<<<<<<< HEAD
			if (! in_array('country',$fieldlist))	// If there is already a field country, we don't show country_id (avoid duplicate)
=======
			if (! in_array('country', $fieldlist))	// If there is already a field country, we don't show country_id (avoid duplicate)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
			{
				$country_id = (! empty($obj->{$fieldlist[$field]}) ? $obj->{$fieldlist[$field]} : 0);
				print '<td>';
				print '<input type="hidden" name="'.$fieldlist[$field].'" value="'.$country_id.'">';
				print '</td>';
			}
		}
<<<<<<< HEAD
		elseif ($fieldlist[$field] == 'region')
		{
			print '<td>';
			$formcompany->select_region($region_id,'region');
			print '</td>';
		}
		elseif ($fieldlist[$field] == 'region_id')
		{
			$region_id = (! empty($obj->{$fieldlist[$field]})?$obj->{$fieldlist[$field]}:0);
			print '<td>';
			print '<input type="hidden" name="'.$fieldlist[$field].'" value="'.$region_id.'">';
			print '</td>';
		}
		elseif ($fieldlist[$field] == 'lang')
		{
			print '<td>';
			print $formadmin->select_language($conf->global->MAIN_LANG_DEFAULT,'lang');
			print '</td>';
		}
		// Le type de template
		elseif ($fieldlist[$field] == 'type_template')
		{
			print '<td>';
			print $form->selectarray('type_template', $elementList,(! empty($obj->{$fieldlist[$field]})?$obj->{$fieldlist[$field]}:''));
			print '</td>';
		}
		// Le type de l'element (pour les type de contact)
		elseif ($fieldlist[$field] == 'element')
		{
			print '<td>';
			print $form->selectarray('element', $elementList,(! empty($obj->{$fieldlist[$field]})?$obj->{$fieldlist[$field]}:''));
			print '</td>';
		}
		// La source de l'element (pour les type de contact)
		elseif ($fieldlist[$field] == 'source')
		{
			print '<td>';
			print $form->selectarray('source', $sourceList,(! empty($obj->{$fieldlist[$field]})?$obj->{$fieldlist[$field]}:''));
			print '</td>';
		}
		elseif ($fieldlist[$field] == 'type' && $tabname == MAIN_DB_PREFIX."c_actioncomm")
		{
			print '<td>';
			print 'user<input type="hidden" name="type" value="user">';
			print '</td>';
		}
		elseif ($fieldlist[$field] == 'recuperableonly' || $fieldlist[$field] == 'type_cdr' || $fieldlist[$field] == 'deductible' || $fieldlist[$field] == 'category_type') {
			if ($fieldlist[$field] == 'type_cdr') print '<td align="center">';
=======
		elseif ($fieldlist[$field] == 'type_cdr') {
			if ($fieldlist[$field] == 'type_cdr') print '<td class="center">';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
			else print '<td>';
			if ($fieldlist[$field] == 'type_cdr') {
				print $form->selectarray($fieldlist[$field], array(0=>$langs->trans('None'), 1=>$langs->trans('AtEndOfMonth'), 2=>$langs->trans('CurrentNext')), (! empty($obj->{$fieldlist[$field]})?$obj->{$fieldlist[$field]}:''));
			} else {
<<<<<<< HEAD
				print $form->selectyesno($fieldlist[$field],(! empty($obj->{$fieldlist[$field]})?$obj->{$fieldlist[$field]}:''),1);
			}
			print '</td>';
		}
		elseif (in_array($fieldlist[$field],array('nbjour','decalage','taux','localtax1','localtax2'))) {
			$align="left";
			if (in_array($fieldlist[$field],array('taux','localtax1','localtax2'))) $align="right";	// Fields aligned on right
			print '<td align="'.$align.'">';
			print '<input type="text" class="flat" value="'.(isset($obj->{$fieldlist[$field]})?$obj->{$fieldlist[$field]}:'').'" size="3" name="'.$fieldlist[$field].'">';
			print '</td>';
		}
		elseif (in_array($fieldlist[$field], array('libelle_facture'))) {
			print '<td><textarea cols="30" rows="'.ROWS_2.'" class="flat" name="'.$fieldlist[$field].'">'.(! empty($obj->{$fieldlist[$field]})?$obj->{$fieldlist[$field]}:'').'</textarea></td>';
		}
		elseif (in_array($fieldlist[$field], array('content')))
		{
			print '<td>';
			if ($context != 'hide')
			{
				//print '<textarea cols="3" rows="'.ROWS_2.'" class="flat" name="'.$fieldlist[$field].'">'.(! empty($obj->{$fieldlist[$field]})?$obj->{$fieldlist[$field]}:'').'</textarea>';
				$okforextended=true;
				$doleditor = new DolEditor($fieldlist[$field], (! empty($obj->{$fieldlist[$field]})?$obj->{$fieldlist[$field]}:''), '', 140, 'dolibarr_mailings', 'In', 0, false, $okforextended, ROWS_5, '90%');
				print $doleditor->Create(1);
			}
			else print '&nbsp;';
			print '</td>';
		}
		elseif ($fieldlist[$field] == 'price' || preg_match('/^amount/i',$fieldlist[$field])) {
			print '<td><input type="text" class="flat" value="'.price((! empty($obj->{$fieldlist[$field]})?$obj->{$fieldlist[$field]}:'')).'" size="8" name="'.$fieldlist[$field].'"></td>';
		}
		elseif ($fieldlist[$field] == 'code' && isset($obj->{$fieldlist[$field]})) {
			print '<td><input type="text" class="flat" value="'.(! empty($obj->{$fieldlist[$field]})?$obj->{$fieldlist[$field]}:'').'" size="10" name="'.$fieldlist[$field].'"></td>';
		}
		elseif ($fieldlist[$field] == 'accountancy_code' || $fieldlist[$field] == 'accountancy_code_sell' || $fieldlist[$field] == 'accountancy_code_buy')
		{
			print '<td>';
			if (! empty($conf->accounting->enabled))
			{
				$accountancy_account = (! empty($obj->$fieldlist[$field]) ? $obj->$fieldlist[$field] : 0);
				print $formaccounting->select_account($accountancy_account, $fieldlist[$field], 1, '', 1, 1, 'maxwidth200 maxwidthonsmartphone');
			}
			else
			{
				print '<input type="text" size="10" class="flat" value="'.(isset($obj->$fieldlist[$field])?$obj->$fieldlist[$field]:'').'" name="'.$fieldlist[$field].'">';
			}
			print '</td>';
		}
=======
				print $form->selectyesno($fieldlist[$field], (! empty($obj->{$fieldlist[$field]})?$obj->{$fieldlist[$field]}:''), 1);
			}
			print '</td>';
		}
		elseif ($fieldlist[$field] == 'code' && isset($obj->{$fieldlist[$field]})) {
			print '<td><input type="text" class="flat" value="'.(! empty($obj->{$fieldlist[$field]})?$obj->{$fieldlist[$field]}:'').'" size="10" name="'.$fieldlist[$field].'"></td>';
		}
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		else
		{
			print '<td>';
			$size=''; $class='';
			if ($fieldlist[$field]=='code') $size='size="8" ';
			if ($fieldlist[$field]=='position') $size='size="4" ';
			if ($fieldlist[$field]=='libelle') $size='centpercent';
<<<<<<< HEAD
			if ($fieldlist[$field]=='tracking') $class='centpercent';
=======
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
			if ($fieldlist[$field]=='sortorder' || $fieldlist[$field]=='sens' || $fieldlist[$field]=='category_type') $size='size="2" ';
			print '<input type="text" '.$size.' class="flat'.($class?' '.$class:'').'" value="'.(isset($obj->{$fieldlist[$field]})?$obj->{$fieldlist[$field]}:'').'" name="'.$fieldlist[$field].'">';
			print '</td>';
		}
	}
}
<<<<<<< HEAD

=======
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
