<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015       Jean-François Ferry		<jfefe@aternatik.fr>
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
 *   \file       htdocs/admin/boxes.php
 *   \brief      Page to setup boxes
 */

require '../main.inc.php';
include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/infobox.class.php';
include_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('admin', 'boxes', 'accountancy'));

if (!$user->admin) {
	accessforbidden();
}

$rowid = GETPOST('rowid', 'int');
$action = GETPOST('action', 'aZ09');


// Define possible position of boxes
$arrayofhomepages = InfoBox::getListOfPagesForBoxes();
$boxes = array();


/*
 * Actions
 */

if ($action == 'addconst') {
	dolibarr_set_const($db, "MAIN_BOXES_MAXLINES", GETPOST("MAIN_BOXES_MAXLINES", 'int'), '', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_ACTIVATE_FILECACHE", GETPOST("MAIN_ACTIVATE_FILECACHE", 'alpha'), 'chaine', 0, '', $conf->entity);
}

if ($action == 'add') {
	$error = 0;
	$boxids = GETPOST('boxid', 'array');

	$db->begin();
	if (is_array($boxids)) {
		foreach ($boxids as $boxid) {
			if (is_numeric($boxid['pos']) && $boxid['pos'] >= 0) {	// 0=Home, 1=...
				$pos = $boxid['pos'];

				// Initialize distinct fk_user with all already existing values of fk_user (user that use a personalized view of boxes for page "pos")
				$distinctfkuser = array();
				if (!$error) {
					$sql = "SELECT fk_user";
					$sql .= " FROM ".MAIN_DB_PREFIX."user_param";
					$sql .= " WHERE param = 'MAIN_BOXES_".$db->escape($pos)."' AND value = '1'";
					$sql .= " AND entity = ".$conf->entity;
					dol_syslog("boxes.php search fk_user to activate box for", LOG_DEBUG);
					$resql = $db->query($sql);
					if ($resql) {
						$num = $db->num_rows($resql);
						$i = 0;
						while ($i < $num) {
							$obj = $db->fetch_object($resql);
							$distinctfkuser[$obj->fk_user] = $obj->fk_user;
							$i++;
						}
					} else {
						setEventMessages($db->lasterror(), null, 'errors');
						$error++;
					}
				}

				$distinctfkuser['0'] = '0'; // Add entry for fk_user = 0. We must use string as key and val

				foreach ($distinctfkuser as $fk_user) {
					if (!$error && $fk_user != '') {
						$arrayofexistingboxid = array();
						$nbboxonleft = $nbboxonright = 0;
						$sql = "SELECT box_id, box_order FROM ".MAIN_DB_PREFIX."boxes";
						$sql .= " WHERE position = ".((int) $pos)." AND fk_user = ".((int) $fk_user)." AND entity = ".((int) $conf->entity);
						dol_syslog("boxes.php activate box", LOG_DEBUG);
						$resql = $db->query($sql);
						if ($resql) {
							while ($obj = $db->fetch_object($resql)) {
								$boxorder = $obj->box_order;
								if (preg_match('/A/', $boxorder)) {
									$nbboxonleft++;
								}
								if (preg_match('/B/', $boxorder)) {
									$nbboxonright++;
								}
								$arrayofexistingboxid[$obj->box_id] = 1;
							}
						} else {
							dol_print_error($db);
						}

						if (empty($arrayofexistingboxid[$boxid['value']])) {
							$sql = "INSERT INTO ".MAIN_DB_PREFIX."boxes (";
							$sql .= "box_id, position, box_order, fk_user, entity";
							$sql .= ") VALUES (";
							$sql .= ((int) $boxid['value']).", ".((int) $pos).", '".(($nbboxonleft > $nbboxonright) ? 'B01' : 'A01')."', ".((int) $fk_user).", ".$conf->entity;
							$sql .= ")";

							dol_syslog("boxes.php activate box", LOG_DEBUG);
							$resql = $db->query($sql);
							if (!$resql) {
								setEventMessages($db->lasterror(), null, 'errors');
								$error++;
							}
						} else {
							dol_syslog("boxes.php activate box - already exists in database", LOG_DEBUG);
						}
					}
				}
			}
		}
	}
	if (!$error) {
		$db->commit();
		$action = '';
	} else {
		$db->rollback();
	}
}

if ($action == 'delete') {
	$sql = "SELECT box_id FROM ".MAIN_DB_PREFIX."boxes";
	$sql .= " WHERE rowid=".((int) $rowid);

	$resql = $db->query($sql);
	$obj = $db->fetch_object($resql);
	if (!empty($obj->box_id)) {
		$db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."boxes";
		$sql .= " WHERE entity = ".$conf->entity;
		$sql .= " AND box_id=".((int) $obj->box_id);

		$resql = $db->query($sql);

		$db->commit();
	}
}

if ($action == 'switch') {
	// We switch values of field box_order for the 2 lines of table boxes
	$db->begin();

	$objfrom = new ModeleBoxes($db);
	$objfrom->fetch(GETPOST("switchfrom", 'int'));

	$objto = new ModeleBoxes($db);
	$objto->fetch(GETPOST('switchto', 'int'));

	$resultupdatefrom = 0;
	$resultupdateto = 0;
	if (is_object($objfrom) && is_object($objto)) {
		$newfirst = $objto->box_order;
		$newsecond = $objfrom->box_order;
		if ($newfirst == $newsecond) {
			$newsecondchar = preg_replace('/[0-9]+/', '', $newsecond);
			$newsecondnum = preg_replace('/[a-zA-Z]+/', '', $newsecond);
			$newsecond = sprintf("%s%02d", $newsecondchar ? $newsecondchar : 'A', $newsecondnum + 1);
		}

		$sql = "UPDATE ".MAIN_DB_PREFIX."boxes SET box_order='".$db->escape($newfirst)."' WHERE rowid=".((int) $objfrom->rowid);
		dol_syslog($sql);
		$resultupdatefrom = $db->query($sql);
		if (!$resultupdatefrom) {
			dol_print_error($db);
		}

		$sql = "UPDATE ".MAIN_DB_PREFIX."boxes SET box_order='".$db->escape($newsecond)."' WHERE rowid=".((int) $objto->rowid);
		dol_syslog($sql);
		$resultupdateto = $db->query($sql);
		if (!$resultupdateto) {
			dol_print_error($db);
		}
	}

	if ($resultupdatefrom && $resultupdateto) {
		$db->commit();
	} else {
		$db->rollback();
	}
}


/*
 * View
 */

$form = new Form($db);

llxHeader('', $langs->trans("Boxes"));

print load_fiche_titre($langs->trans("Boxes"), '', 'title_setup');

print '<span class="opacitymedium">'.$langs->trans("BoxesDesc")." ".$langs->trans("OnlyActiveElementsAreShown")."</span><br>\n";

/*
 * Search for the default active boxes for each possible position
 * We store the active boxes by default in $boxes[position][id_boite]=1
 */

$actives = array();

$sql = "SELECT b.rowid, b.box_id, b.position, b.box_order,";
$sql .= " bd.rowid as boxid";
$sql .= " FROM ".MAIN_DB_PREFIX."boxes as b, ".MAIN_DB_PREFIX."boxes_def as bd";
$sql .= " WHERE b.box_id = bd.rowid";
$sql .= " AND b.entity IN (0,".$conf->entity.")";
$sql .= " AND b.fk_user=0";
$sql .= " ORDER by b.position, b.box_order";
//print $sql;

dol_syslog("Search available boxes", LOG_DEBUG);
$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);

	// Check record to know if we must recalculate sort order
	$i = 0;
	$decalage = 0;
	while ($i < $num) {
		$obj = $db->fetch_object($resql);
		$boxes[$obj->position][$obj->box_id] = 1;
		$i++;

		array_push($actives, $obj->box_id);

		if ($obj->box_order == '' || $obj->box_order == '0' || $decalage) {
			$decalage++;
		}
		// We renumber the order of the boxes if one of them is in ''
		// This occurs just after an insert.
		if ($decalage) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."boxes SET box_order='".$db->escape($decalage)."' WHERE rowid=".((int) $obj->rowid);
			$db->query($sql);
		}
	}

	if ($decalage) {
		// If we have renumbered, we correct the field box_order
		// This occurs just after an insert.
		$sql = "SELECT box_order";
		$sql .= " FROM ".MAIN_DB_PREFIX."boxes";
		$sql .= " WHERE entity = ".$conf->entity;
		$sql .= " AND LENGTH(box_order) <= 2";

		dol_syslog("Execute requests to renumber box order", LOG_DEBUG);
		$result = $db->query($sql);
		if ($result) {
			while ($record = $db->fetch_array($result)) {
				if (dol_strlen($record['box_order']) == 1) {
					if (preg_match("/[13579]{1}/", substr($record['box_order'], -1))) {
						$box_order = "A0".$record['box_order'];
						$sql = "UPDATE ".MAIN_DB_PREFIX."boxes SET box_order = '".$db->escape($box_order)."' WHERE entity = ".$conf->entity." AND box_order = '".$db->escape($record['box_order'])."'";
						$resql = $db->query($sql);
					} elseif (preg_match("/[02468]{1}/", substr($record['box_order'], -1))) {
						$box_order = "B0".$record['box_order'];
						$sql = "UPDATE ".MAIN_DB_PREFIX."boxes SET box_order = '".$db->escape($box_order)."' WHERE entity = ".$conf->entity." AND box_order = '".$db->escape($record['box_order'])."'";
						$resql = $db->query($sql);
					}
				} elseif (dol_strlen($record['box_order']) == 2) {
					if (preg_match("/[13579]{1}/", substr($record['box_order'], -1))) {
						$box_order = "A".$record['box_order'];
						$sql = "UPDATE ".MAIN_DB_PREFIX."boxes SET box_order = '".$db->escape($box_order)."' WHERE entity = ".$conf->entity." AND box_order = '".$db->escape($record['box_order'])."'";
						$resql = $db->query($sql);
					} elseif (preg_match("/[02468]{1}/", substr($record['box_order'], -1))) {
						$box_order = "B".$record['box_order'];
						$sql = "UPDATE ".MAIN_DB_PREFIX."boxes SET box_order = '".$db->escape($box_order)."' WHERE entity = ".$conf->entity." AND box_order = '".$db->escape($record['box_order'])."'";
						$resql = $db->query($sql);
					}
				}
			}
		}
	}
	$db->free($resql);
}

// Available boxes to activate
$boxtoadd = InfoBox::listBoxes($db, 'available', -1, null, $actives);
// Activated boxes
$boxactivated = InfoBox::listBoxes($db, 'activated', -1, null);

print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">'."\n";
print '<input type="hidden" name="token" value="'.newToken().'">'."\n";
print '<input type="hidden" name="action" value="add">'."\n";


print '<br>';

print '<div class="div-table-responsive-no-min">';
print '<table class="tagtable liste centpercent">'."\n";

print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Box").'</td>';
print '<td>'.$langs->trans("Note").'/'.$langs->trans("Parameters").'</td>';
print '<td></td>';
print '<td class="center" width="160">'.$langs->trans("ActivatableOn").'</td>';
print '<td class="center" width="60" colspan="2">'.$langs->trans("PositionByDefault").'</td>';
print '<td class="center" width="80">'.$langs->trans("Disable").'</td>';
print '</tr>'."\n";

print "\n\n".'<!-- Boxes Available -->'."\n";
foreach ($boxtoadd as $box) {
	if (preg_match('/^([^@]+)@([^@]+)$/i', $box->boximg)) {
		$logo = $box->boximg;
	} else {
		$logo = preg_replace("/^object_/i", "", $box->boximg);
	}

	print "\n".'<!-- Box '.$box->boxcode.' -->'."\n";
	print '<tr class="oddeven" style="height:3em !important;">'."\n";
	print '<td class="tdoverflowmax300" title="'.dol_escape_htmltag($langs->transnoentitiesnoconv($box->boxlabel)).'">'.img_object("", $logo, 'class="pictofixedwidth" height="14px"').' '.$langs->transnoentitiesnoconv($box->boxlabel);
	if (!empty($box->class) && preg_match('/graph_/', $box->class)) {
		print img_picto('', 'graph', 'class="paddingleft"');
	}
	if (!empty($box->version)) {
		if ($box->version == 'experimental') {
			print ' <span class="opacitymedium">('.$langs->trans("Experimental").')</span>';
		} elseif ($box->version == 'development') {
			print ' <span class="opacitymedium">('.$langs->trans("Development").')</span>';
		}
	}
	print '</td>'."\n";
	print '<td class="tdoverflowmax300" title="'.dol_escape_htmltag($box->note).'">';
	if ($box->note == '(WarningUsingThisBoxSlowDown)') {
		$langs->load("errors");
		print $langs->trans("WarningUsingThisBoxSlowDown");
	} else {
		print ($box->note ? $box->note : '&nbsp;');
	}
	print '</td>'."\n";
	print '<td>';
	print $form->textwithpicto('', $langs->trans("SourceFile").' : '.$box->sourcefile);
	print '</td>'."\n";

	// For each possible position, an activation link is displayed if the box is not already active for that position
	print '<td class="center">';
	print $form->selectarray("boxid[".$box->box_id."][pos]", $arrayofhomepages, -1, 1, 0, 0, '', 1)."\n";
	print '<input type="hidden" name="boxid['.$box->box_id.'][value]" value="'.$box->box_id.'">'."\n";
	print '</td>';

	print '<td>';
	print '</td>';

	print '<td>';
	print '</td>';

	print '<td>';
	print '<input type="submit" class="button small smallpaddingimp" value="'.$langs->trans("Activate").'">';
	print '</td>';

	print '</tr>'."\n";
}
print "\n".'<!-- End Boxes Available -->'."\n";


$box_order = 1;
$foundrupture = 1;
foreach ($boxactivated as $key => $box) {
	if (preg_match('/^([^@]+)@([^@]+)$/i', $box->boximg)) {
		$logo = $box->boximg;
	} else {
		$logo = preg_replace("/^object_/i", "", $box->boximg);
	}

	print "\n".'<!-- Box '.$box->boxcode.' -->'."\n";
	print '<tr class="oddeven" style="height:3em !important;">';
	print '<td>'.img_object("", $logo, 'class="pictofixedwidth" height="14px"').' '.$langs->transnoentitiesnoconv($box->boxlabel);
	if (!empty($box->class) && preg_match('/graph_/', $box->class)) {
		print img_picto('', 'graph', 'class="paddingleft"');
	}
	if (!empty($box->version)) {
		if ($box->version == 'experimental') {
			print ' <span class="opacitymedium">('.$langs->trans("Experimental").')</span>';
		} elseif ($box->version == 'development') {
			print ' <span class="opacitymedium">('.$langs->trans("Development").')</span>';
		}
	}
	print '</td>';
	$langs->load("errors");
	print '<td class="tdoverflowmax300" title="'.dol_escape_htmltag($box->note == '(WarningUsingThisBoxSlowDown)' ? $langs->trans("WarningUsingThisBoxSlowDown") : $box->note).'">';
	if ($box->note == '(WarningUsingThisBoxSlowDown)') {
		print img_warning('', 0).' '.$langs->trans("WarningUsingThisBoxSlowDown");
	} else {
		print ($box->note ? $box->note : '&nbsp;');
	}
	print '</td>';
	print '<td>';
	print $form->textwithpicto('', $langs->trans("SourceFile").' : '.$box->sourcefile);
	print '</td>'."\n";
	print '<td class="center">'.(empty($arrayofhomepages[$box->position]) ? '' : $langs->trans($arrayofhomepages[$box->position])).'</td>';
	$hasnext = ($key < (count($boxactivated) - 1));
	$hasprevious = ($key != 0);
	print '<td class="center">'.($key + 1).'</td>';
	print '<td class="center nowraponall">';
	print ($hasnext ? '<a class="reposition" href="boxes.php?action=switch&token='.newToken().'&switchfrom='.$box->rowid.'&switchto='.$boxactivated[$key + 1]->rowid.'">'.img_down().'</a>&nbsp;' : '');
	print ($hasprevious ? '<a class="reposition" href="boxes.php?action=switch&token='.newToken().'&switchfrom='.$box->rowid.'&switchto='.$boxactivated[$key - 1]->rowid.'">'.img_up().'</a>' : '');
	print '</td>';
	print '<td class="center">';
	print '<a class="reposition" href="boxes.php?rowid='.$box->rowid.'&action=delete&token='.newToken().'">'.img_delete().'</a>';
	print '</td>';

	print '</tr>'."\n";
}

print '</table>';
print '</div>';
print '<br>';

print '</form>';


// Other parameters

print "\n\n".'<!-- Other Const -->'."\n";
print load_fiche_titre($langs->trans("Other"), '', '');
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="addconst">';
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print '<td class="liste_titre">'.$langs->trans("Parameter").'</td>';
print '<td class="liste_titre">'.$langs->trans("Value").'</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>';
print $langs->trans("MaxNbOfLinesForBoxes");
print '</td>'."\n";
print '<td>';
print '<input type="text" class="flat" size="6" name="MAIN_BOXES_MAXLINES" value="'.(!empty($conf->global->MAIN_BOXES_MAXLINES) ? $conf->global->MAIN_BOXES_MAXLINES : '').'">';
print '</td>';
print '</tr>';

// Activate FileCache - Developement
if ($conf->global->MAIN_FEATURES_LEVEL == 2 || !empty($conf->global->MAIN_ACTIVATE_FILECACHE)) {
	print '<tr class="oddeven"><td width="35%">'.$langs->trans("EnableFileCache").'</td><td>';
	print $form->selectyesno('MAIN_ACTIVATE_FILECACHE', (!empty($conf->global->MAIN_ACTIVATE_FILECACHE) ? $conf->global->MAIN_ACTIVATE_FILECACHE : 0), 1);
	print '</td>';
	print '</tr>';
}

print '</table>';
print '</div>';

print $form->buttonsSaveCancel("Save", '');

print '</form>';
print "\n".'<!-- End Other Const -->'."\n";

// End of page
llxFooter();
$db->close();
