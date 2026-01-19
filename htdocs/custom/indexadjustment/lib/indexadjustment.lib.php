<?php
/* Copyright (C) 2025 Florian Hödl <florian@hoedl.co>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file       lib/indexadjustment.lib.php
 * \ingroup    indexadjustment
 * \brief      Library files with common functions for IndexAdjustment
 */

/**
 * Prepare array of tabs for IndexAdjustment
 *
 * @param IndexAdjustment $object IndexAdjustment object
 * @return array Array of tabs
 */
function indexadjustmentPrepareHead($object)
{
	global $db, $langs, $conf, $user;

	$langs->load("indexadjustment@indexadjustment");

	$h = 0;
	$head = array();

	// Card tab
	$head[$h][0] = dol_buildpath("/indexadjustment/card.php", 1) . '?id=' . $object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'card';
	$h++;

	// Lines tab (if executed)
	if ($object->status >= 1) {
		$head[$h][0] = dol_buildpath("/indexadjustment/lines.php", 1) . '?id=' . $object->id;
		$head[$h][1] = $langs->trans("Lines");
		if (!empty($object->total_lines)) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">' . $object->total_lines . '</span>';
		}
		$head[$h][2] = 'lines';
		$h++;
	}

	// Notes tab
	$head[$h][0] = dol_buildpath("/indexadjustment/note.php", 1) . '?id=' . $object->id;
	$head[$h][1] = $langs->trans("Notes");
	$head[$h][2] = 'note';
	$h++;

	// Linked files tab
	require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT . '/core/class/link.class.php';
	$upload_dir = $conf->indexadjustment->dir_output . "/" . dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$h][0] = dol_buildpath("/indexadjustment/document.php", 1) . '?id=' . $object->id;
	$head[$h][1] = $langs->trans("Documents");
	if (($nbFiles + $nbLinks) > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">' . ($nbFiles + $nbLinks) . '</span>';
	}
	$head[$h][2] = 'document';
	$h++;

	return $head;
}

/**
 * Prepare admin pages header
 *
 * @return array Array of tabs
 */
function indexadjustmentAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("indexadjustment@indexadjustment");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/indexadjustment/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;

	$head[$h][0] = dol_buildpath("/indexadjustment/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'indexadjustment@indexadjustment');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'indexadjustment@indexadjustment', 'remove');

	return $head;
}

/**
 * Get status label for IndexAdjustment
 *
 * @param int    $status Status value
 * @param int    $mode   0=long label, 1=short label, 2=picto, 3=picto+short, 4=picto+long, 5=short+picto, 6=long+picto
 * @return string        HTML label
 */
function indexadjustmentGetStatusLabel($status, $mode = 0)
{
	global $langs;

	$langs->load("indexadjustment@indexadjustment");

	$statusType = 'status' . $status;
	$labelStatus = '';
	$labelStatusShort = '';

	switch ($status) {
		case 0:
			$labelStatus = $langs->trans('StatusDraft');
			$labelStatusShort = $langs->trans('StatusDraft');
			$statusType = 'status0';
			break;
		case 1:
			$labelStatus = $langs->trans('StatusValidated');
			$labelStatusShort = $langs->trans('StatusValidated');
			$statusType = 'status1';
			break;
		case 2:
			$labelStatus = $langs->trans('StatusExecuted');
			$labelStatusShort = $langs->trans('StatusExecuted');
			$statusType = 'status4';
			break;
		case 9:
			$labelStatus = $langs->trans('StatusCancelled');
			$labelStatusShort = $langs->trans('StatusCancelled');
			$statusType = 'status9';
			break;
	}

	return dolGetStatus($labelStatus, $labelStatusShort, '', $statusType, $mode);
}

/**
 * Format percent value with sign
 *
 * @param float $percent Percentage value
 * @param bool  $colored Add color classes
 * @return string        Formatted string
 */
function indexadjustmentFormatPercent($percent, $colored = true)
{
	$sign = ($percent >= 0) ? '+' : '';
	$class = '';

	if ($colored) {
		$class = ($percent >= 0) ? 'amountremaintopay' : 'amountpaymentcomplete';
	}

	if ($class) {
		return '<span class="' . $class . '">' . $sign . number_format($percent, 2, ',', '.') . '%</span>';
	}

	return $sign . number_format($percent, 2, ',', '.') . '%';
}

/**
 * Format price difference with sign and color
 *
 * @param float  $diff     Price difference
 * @param string $currency Currency code
 * @return string          Formatted HTML
 */
function indexadjustmentFormatPriceDiff($diff, $currency = 'EUR')
{
	global $langs;

	$sign = ($diff >= 0) ? '+' : '';
	$class = ($diff >= 0) ? 'amountremaintopay' : 'amountpaymentcomplete';

	return '<span class="' . $class . '">' . $sign . price($diff, 0, $langs, 1, -1, 2, $currency) . '</span>';
}
