<?php
/* Copyright (C) 2017-2019 Regis Houssin  <regis.houssin@inodbox.com>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *       \file       /multicompany/core/ajax/list.php
 *       \brief      File to return datables output of entities list
 */

if (! defined('NOTOKENRENEWAL'))	define('NOTOKENRENEWAL','1'); // Disables token renewal
if (! defined('NOCSRFCHECK'))		define('NOCSRFCHECK','1');
if (! defined('NOREQUIREMENU'))		define('NOREQUIREMENU','1');
if (! defined('NOREQUIREHTML'))		define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX'))		define('NOREQUIREAJAX','1');
if (! defined('NOREQUIRESOC'))		define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))	define('NOREQUIRETRAN','1');
if (! defined('NOREQUIREHOOK'))		define('NOREQUIREHOOK',1);

$res=@include("../../../main.inc.php");						// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../../../main.inc.php");		// For "custom" directory

require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
dol_include_once('/multicompany/class/dao_multicompany.class.php', 'DaoMulticompany');
dol_include_once('/multicompany/lib/multicompany.lib.php');
dol_include_once('/multicompany/class/ssp.class.php');

$langs->loadLangs(array('languages','admin','multicompany@multicompany'));

// Defini si peux lire/modifier permisssions
$canreadEntity=! empty($user->admin);
$caneditEntity=! empty($user->admin);
$candeleteEntity=! empty($user->admin);

top_httphead('application/json');

//print '<!-- Ajax page called with url '.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].' -->'."\n";

//print_r($_GET);

if (empty($user->admin) || empty($conf->multicompany->enabled)) {
	echo json_encode(array('status' => 'error'));
	$db->close();
	exit();
}

$object = new DaoMulticompany($db);

$extrafields = new ExtraFields($db);
// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label($object->element);

$start		= array('start' => GETPOST('start', 'int'));
$length		= array('length' => GETPOST('length', 'int'));
$draw		= array('draw' => GETPOST('draw', 'int'));
$order		= array('order' => GETPOST('order', 'array'));
$columns	= array('columns' => GETPOST('columns', 'array'));
$search		= array('search' => GETPOST('search', 'array'));

$request = $start + $length + $draw + $order + $columns + $search;
//print_r($request);

// DB table to use
$table = MAIN_DB_PREFIX . "entity";

// Table's primary key
$primaryKey = 'rowid';

// Array of database columns which should be read and sent back to DataTables.
// The `db` parameter represents the column name in the database, while the `dt`
// parameter represents the DataTables column identifier. In this case simple
// indexes
$columns = array();

$columns[]= array( 'db' => 'rowid', 'dt' => 'entity_id' );
$columns[]= array( 'db' => 'label', 'dt' => 'entity_label' );
$columns[]= array( 'db' => 'description', 'dt' => 'entity_description' );
$columns[]= array(
	'db' => 'rowid',
	'dt' => 'entity_name',
	'formatter' => function($value, $row) {
		if (! empty($value))	{
			global $object;
			$object->fetch($value);
			return $object->name;
		}
	}
);
$columns[]= array(
	'db' => 'rowid',
	'dt' => 'entity_zip',
	'formatter' => function($value, $row) {
		if (! empty($value))	{
			global $object;
			$object->fetch($value);
			return $object->zip;
		}
	}
);
$columns[]= array(
	'db' => 'rowid',
	'dt' => 'entity_town',
	'formatter' => function($value, $row) {
		if (! empty($value))	{
			global $object;
			$object->fetch($value);
			return $object->town;
		}
	}
);
$columns[]= array(
	'db' => 'rowid',
	'dt' => 'entity_country',
	'formatter' => function($value, $row) {
		if (! empty($value))	{
			global $object;
			$object->fetch($value);
			if ($cache = getCache('country_' . $object->country_id)) {
				$country = $cache;
			} else {
				$country = getCountry($object->country_id);
				setCache('country_' . $object->country_id, $country);
			}
			$img=picto_from_langcode($object->country_code, 'class="multicompany-flag-country"');
			return ($img?$img.' ':'') . $country;
		}
	}
);
$columns[]= array(
	'db' => 'rowid',
	'dt' => 'entity_currency',
	'formatter' => function($value, $row) {
		if (! empty($value))	{
			global $langs, $object;
			$object->fetch($value);
			return currency_name($object->currency_code) . ' (' . $langs->getCurrencySymbol($object->currency_code) . ')';
		}
	}
);
$columns[]= array(
	'db' => 'rowid',
	'dt' => 'entity_language',
	'formatter' => function($value, $row) {
		if (! empty($value))	{
			global $langs, $object;
			$object->fetch($value);
			$img=picto_from_langcode($object->language_code, 'class="multicompany-flag-language"');
			return ($img?$img.' ':'') . ($object->language_code=='auto'?$langs->trans("AutoDetectLang"):$langs->trans("Language_".$object->language_code));
		}
	}
);
if (! empty($extralabels)) {
	foreach ($extralabels as $key => $name)
	{
		$columns[]= array(
			'db' => 'rowid',
			'dt' => 'entity_'.$key,
			'formatter' => function($value, $row) use ($key) {
				global $object, $extrafields;
				unset($object->array_options['options_'.$key]); // For avoid duplicate data in next row
				$object->fetch_optionals();
				return $extrafields->showOutputField($key, $object->array_options['options_'.$key]);
			}
		);
	}
}
$columns[]= array(
	'db' => 'visible',
	'dt' => 'entity_visible',
	'formatter' => function($value, $row) {
		global $langs;
		if (! empty($value)) {
			if ($value == 1) {
				return img_picto($langs->trans("Enabled"),'on','id="visible_' . $row['rowid'] . '"',false,0,0,'','multicompany-button-visible-on');
			} else {
				return '<span id="template_' . $row['rowid'] . '" class="fas fa-clone multicompany-button-template" title="'.$langs->transnoentities("TemplateOfEntity").'"></span>';
			}
		} else if (! empty($row['active'])) {
			return img_picto($langs->trans("Disabled"),'off','id="visible_' . $row['rowid'] . '"',false,0,0,'','multicompany-button-visible-off');
		} else {
			return img_picto($langs->trans("Disabled"),'off','id="visible_' . $row['rowid'] . '"',false,0,0,'','multicompany-button-disabled');
		}
	}
);
$columns[]= array(
	'db' => 'active',
	'dt' => 'entity_active',
	'formatter' => function($value, $row) {
		global $conf, $langs;
		if ($row['rowid'] == 1 || $conf->entity == $row['rowid']) {
			return img_picto($langs->trans("Enabled"),'on','id="active_' . $row['rowid'] . '"',false,0,0,'','multicompany-button-disabled');
		} else if (! empty($value)) {
			if ($row['visible'] == 2) {
				return img_picto($langs->trans("Enabled"),'on','id="activetemplate_' . $row['rowid'] . '"',false,0,0,'','multicompany-button-active-on');
			} else {
				return img_picto($langs->trans("Enabled"),'on','id="active_' . $row['rowid'] . '"',false,0,0,'','multicompany-button-active-on');
			}
		} else {
			if ($row['visible'] == 2) {
				return img_picto($langs->trans("Disabled"),'off','id="activetemplate_' . $row['rowid'] . '"',false,0,0,'','multicompany-button-active-off');
			} else {
				return img_picto($langs->trans("Disabled"),'off','id="active_' . $row['rowid'] . '"',false,0,0,'','multicompany-button-active-off');
			}
		}
	}
);
$columns[]= array(
	'db' => 'rowid',
	'dt' => 'entity_tools',
	'formatter' => function($value, $row) {
		global $conf, $langs, $caneditEntity, $candeleteEntity;
		$ret='';
		if ($caneditEntity) {
			$ret.= img_edit($langs->transnoentities("Edit"),0, 'id="edit_' . $value . '" class="multicompany-button-setup"');
		}
		if ($candeleteEntity) {
			if ($value == 1 || $conf->entity == $value) {
				$ret.= img_delete($langs->transnoentities("Delete"), 'id="delete_' . $value . '" class="multicompany-button-disabled"');
			} else {
				$ret.= img_delete($langs->transnoentities("Delete"), 'id="delete_' . $value . '" class="multicompany-button-delete"');
			}
		}
		return $ret;
	}
);

//var_dump($columns);
echo json_encode(
	SSP::simple( $request, $db, $table, $primaryKey, $columns )
);

$db->close();
