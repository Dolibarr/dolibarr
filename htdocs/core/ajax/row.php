<?php
/* Copyright (C) 2010-2021 Regis Houssin       <regis.houssin@inodbox.com>
 * Copyright (C) 2017      Laurent Destailleur <eldy@users.sourceforge.net>
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
 *       \file       htdocs/core/ajax/row.php
 *       \brief      File to return Ajax response on Row move.
 *                   This ajax page is called when doing an up or down drag and drop.
 *                   Parameters:
 *                   roworder (Example: '1,3,2,4'),
 *                   table_element_line (Example: 'commandedet')
 *                   fk_element (Example: 'fk_order')
 *                   element_id (Example: 1)
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1'); // Disable token renewal
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
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
if (!defined('NOREQUIRETRAN')) {
	define('NOREQUIRETRAN', '1');
}

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/genericobject.class.php';

$hookmanager->initHooks(array('rowinterface'));

// Security check
// This is done later into view.


/*
 * View
 */

top_httphead();

print '<!-- Ajax page called with url '.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.dol_escape_htmltag($_SERVER["QUERY_STRING"]).' -->'."\n";

// Registering the location of boxes
if (GETPOST('roworder', 'alpha', 3) && GETPOST('table_element_line', 'aZ09', 3)
	&& GETPOST('fk_element', 'aZ09', 3) && GETPOST('element_id', 'int', 3)) {
	$roworder = GETPOST('roworder', 'alpha', 3);
	$table_element_line = GETPOST('table_element_line', 'aZ09', 3);
	$fk_element = GETPOST('fk_element', 'aZ09', 3);
	$element_id = GETPOST('element_id', 'int', 3);

	dol_syslog("AjaxRow roworder=".$roworder." table_element_line=".$table_element_line." fk_element=".$fk_element." element_id=".$element_id, LOG_DEBUG);

	// Make test on permission
	$perm = 0;
	if ($table_element_line == 'propaldet' && $user->hasRight('propal', 'creer')) {
		$perm = 1;
	} elseif ($table_element_line == 'commandedet' && $user->hasRight('commande', 'creer')) {
		$perm = 1;
	} elseif ($table_element_line == 'facturedet' && $user->hasRight('facture', 'creer')) {
		$perm = 1;
	} elseif ($table_element_line == 'facturedet_rec' && $user->hasRight('facture', 'creer')) {
		$perm = 1;
	} elseif ($table_element_line == 'emailcollector_emailcollectoraction' && $user->admin) {
		$perm = 1;
	} elseif ($table_element_line == 'bom_bomline' && $user->hasRight('bom', 'write')) {
		$perm = 1;
	} elseif ($table_element_line == 'mrp_production' && $user->hasRight('mrp', 'write')) {
		$perm = 1;
	} elseif ($table_element_line == 'supplier_proposaldet' && $user->hasRight('supplier_proposal', 'creer')) {
		$perm = 1;
	} elseif ($table_element_line == 'commande_fournisseurdet' && $user->hasRight('fournisseur', 'commande', 'creer')) {
		$perm = 1;
	} elseif ($table_element_line == 'facture_fourn_det' && $user->hasRight('fournisseur', 'facture', 'creer')) {
		$perm = 1;
	} elseif ($table_element_line == 'facture_fourn_det_rec' && $user->hasRight('fournisseur', 'facture', 'creer')) {
		$perm = 1;
	} elseif ($table_element_line == 'product_attribute_value' && $fk_element == 'fk_product_attribute' && ($user->hasRight('produit', 'lire') || $user->hasRight('service', 'lire'))) {
		$perm = 1;
	} elseif ($table_element_line == 'ecm_files') {		// Used when of page "documents.php"
		if ($user->hasRight('ecm', 'creer')) {
			$perm = 1;
		} elseif ($fk_element == 'fk_product' && ($user->hasRight('produit', 'creer') || $user->hasRight('service', 'creer'))) {
			$perm = 1;
		} elseif ($fk_element == 'fk_ticket' && $user->hasRight('ticket', 'write')) {
			$perm = 1;
		} elseif ($fk_element == 'fk_holiday' && $user->hasRight('holiday', 'write')) {
			$perm = 1;
		} elseif ($fk_element == 'fk_soc' && $user->hasRight('societe', 'creer')) {
			$perm = 1;
		}
	} elseif ($table_element_line == 'product_association' && $fk_element == 'fk_product' && ($user->hasRight('produit', 'creer') || $user->hasRight('service', 'creer'))) {
		$perm = 1;
	} elseif ($table_element_line == 'projet_task' && $fk_element == 'fk_projet' && $user->hasRight('projet', 'creer')) {
		$perm = 1;
	} elseif ($table_element_line == 'contratdet' && $fk_element == 'fk_contrat' && $user->hasRight('contrat', 'creer')) {
		$perm = 1;
	} else {
		$tmparray = explode('_', $table_element_line);
		$tmpmodule = $tmparray[0];
		$tmpobject = preg_replace('/line$/', '', $tmparray[1]);
		if (!empty($tmpmodule) && !empty($tmpobject) && !empty($conf->$tmpmodule->enabled) && $user->hasRight($tmpobject, 'write')) {
			$perm = 1;
		}
	}
	$parameters = array('roworder'=> &$roworder, 'table_element_line' => &$table_element_line, 'fk_element' => &$fk_element, 'element_id' => &$element_id, 'perm' => &$perm);
	$row = new GenericObject($db);
	$row->table_element_line = $table_element_line;
	$row->fk_element = $fk_element;
	$row->id = $element_id;
	$reshook = $hookmanager->executeHooks('checkRowPerms', $parameters, $row, $action);
	if ($reshook > 0) {
		$perm = $hookmanager->resArray['perm'];
	}
	if (! $perm) {
		// We should not be here. If we are not allowed to reorder rows, feature should not be visible on script.
		// If we are here, it is a hack attempt, so we report a warning.
		print 'Bad permission to modify position of lines for object in table '.$table_element_line;
		dol_syslog('Bad permission to modify position of lines for object in table='.$table_element_line.', fk_element='.$fk_element, LOG_WARNING);
		accessforbidden('Bad permission to modify position of lines for object in table '.$table_element_line);
	}

	$rowordertab = explode(',', $roworder);
	$newrowordertab = array();
	foreach ($rowordertab as $value) {
		if (!empty($value)) {
			$newrowordertab[] = $value;
		}
	}



	$row->line_ajaxorder($newrowordertab); // This update field rank or position in table row->table_element_line

	// Reorder line to have position of children lines sharing same counter than parent lines
	// This should be useless because there is no need to have children sharing same counter than parent, but well, it's cleaner into database.
	if (in_array($fk_element, array('fk_facture', 'fk_propal', 'fk_commande','fk_contrat'))) {
		$result = $row->line_order(true);
	}
} else {
	print 'Bad parameters for row.php';
}
