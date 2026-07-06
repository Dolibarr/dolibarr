<?php
/* Copyright (C) 2012 Regis Houssin	<regis.houssin@inodbox.com>
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
 * or see https://www.gnu.org/
 */

/**
 *	\file			htdocs/install/lib/repair.lib.php
 *  \brief			Library of repair functions
 */

/**
 *  Check if an element exist
 *
 *  @param	int		$id			Element id
 *  @param	string	$table		Table of Element
 *  @return	boolean				True if child exists
 */
function checkElementExist($id, $table)
{
	global $db;

	$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX.$table;
	$sql .= " WHERE rowid = ".((int) $id);
	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		if ($num > 0) {
			return true;
		} else {
			return false;
		}
	} else {
		return true; // for security
	}
}

/**
 * Check linked elements and delete if invalid
 *
 * @param	string	$sourcetype		Source element type
 * @param	string	$targettype		Target element type
 * @return	string
 */
function checkLinkedElements($sourcetype, $targettype)
{
	global $db, $langs;

	$elements = array();
	$deleted = 0;

	$sourcetable = $sourcetype;
	$targettable = $targettype;

	if ($sourcetype == 'shipping') {
		$sourcetable = 'expedition';
	} elseif ($targettype == 'shipping') {
		$targettable = 'expedition';
	}
	if ($sourcetype == 'delivery') {
		$sourcetable = 'livraison';
	} elseif ($targettype == 'delivery') {
		$targettable = 'livraison';
	}
	if ($sourcetype == 'order_supplier') {
		$sourcetable = 'commande_fournisseur';
	} elseif ($targettype == 'order_supplier') {
		$targettable = 'commande_fournisseur';
	}
	if ($sourcetype == 'invoice_supplier') {
		$sourcetable = 'facture_fourn';
	} elseif ($targettype == 'invoice_supplier') {
		$targettable = 'facture_fourn';
	}

	$out = $langs->trans('SourceType').': '.$sourcetype.' => '.$langs->trans('TargetType').': '.$targettype.' ';

	$sql = "SELECT rowid, fk_source, fk_target FROM ".MAIN_DB_PREFIX."element_element";
	$sql .= " WHERE sourcetype = '".$db->escape($sourcetype)."' AND targettype = '".$db->escape($targettype)."'";
	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		if ($num) {
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				$elements[$obj->rowid] = array($sourcetype => $obj->fk_source, $targettype => $obj->fk_target);
				$i++;
			}
		}
	}

	if (!empty($elements)) {
		foreach ($elements as $key => $element) {
			if (!checkElementExist($element[$sourcetype], $sourcetable) || !checkElementExist($element[$targettype], $targettable)) {
				$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'element_element';
				$sql .= " WHERE rowid = ".((int) $key);
				$resql = $db->query($sql);
				$deleted++;
			}
		}
	}

	if ($deleted) {
		$out .= '('.$langs->trans('LinkedElementsInvalidDeleted', $deleted).')<br>';
	} else {
		$out .= '('.$langs->trans('NothingToDelete').')<br>';
	}

	return $out;
}

/**
 * Clean data into ecm_directories table
 *
 * @return	int			Return integer <0 if KO, >0 if OK
 */
function clean_data_ecm_directories()
{
	global $db, $langs;

	// Clean data from ecm_directories
	$sql = "SELECT rowid, label FROM ".MAIN_DB_PREFIX."ecm_directories";
	$resql = $db->query($sql);
	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			$id = $obj->rowid;
			$label = $obj->label;
			$newlabel = dol_sanitizeFileName($label);
			if ($label != $newlabel) {
				$sqlupdate = "UPDATE ".MAIN_DB_PREFIX."ecm_directories set label = '".$db->escape($newlabel)."' WHERE rowid = ".((int) $id);
				print '<tr><td>'.$sqlupdate."</td></tr>\n";
				$resqlupdate = $db->query($sqlupdate);
				if (!$resqlupdate) {
					dol_print_error($db, 'Failed to update');
					return -1;
				}
			}
		}
	} else {
		dol_print_error($db, 'Failed to run request');
		return -1;
	}

	return 1;
}
