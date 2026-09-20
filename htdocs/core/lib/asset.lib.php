<?php
/* Copyright (C) 2018-2022	OpenDSI					<support@open-dsi.fr>
 * Copyright (C) 2022-2026  Frédéric France			<frederic.france@free.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2025		Alexandre Spangaro		<alexandre@inovea-conseil.com>
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
 * \file    htdocs/core/lib/asset.lib.php
 * \ingroup asset
 * \brief   Library files with common functions for Assets
 */

/**
 * Prepare admin pages header
 *
 * @return array<array{0:string,1:string,2:string}> head array with tabs
 */
function assetAdminPrepareHead()
{
	global $langs, $conf, $extrafields;

	$extrafields->fetch_name_optionals_label('asset');
	$extrafields->fetch_name_optionals_label('asset_model');

	$langs->load("assets");

	$h = 0;
	$head = array();

	$head[$h][0] = dolBuildUrl(DOL_URL_ROOT.'/asset/admin/setup.php');
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@asset:/asset/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@asset:/asset/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'asset_admin');

	$head[$h][0] = dolBuildUrl(DOL_URL_ROOT.'/admin/extrafields.php', array('elementtype' => 'asset'));
	$head[$h][1] = $langs->trans("ExtraFields");
	$nbExtrafields = $extrafields->attributes['asset']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= ' <span class="badge">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'asset_extrafields';
	$h++;

	$head[$h][0] = dolBuildUrl(DOL_URL_ROOT.'/admin/extrafields.php', array('elementtype' => 'asset_model'));
	$head[$h][1] = $langs->trans("ExtraFieldsAssetModel");
	$nbExtrafields = $extrafields->attributes['asset_model']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'assetmodel_extrafields';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'asset_admin', 'remove');

	return $head;
}

/**
 * Prepare array of tabs for Asset
 *
 * @param	Asset	$object		Asset
 * @return 	array<array{0:string,1:string,2:string}>	Array of tabs
 */
function assetPrepareHead(Asset $object)
{
	global $db, $langs, $conf;

	$langs->loadLangs(array("assets", "admin"));

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT . '/asset/card.php?id=' . $object->id;
	$head[$h][1] = $langs->trans("Asset");
	$head[$h][2] = 'card';
	$h++;

	if (empty($object->not_depreciated)) {
		$head[$h][0] = DOL_URL_ROOT . '/asset/depreciation_options.php?id=' . $object->id;
		$head[$h][1] = $langs->trans("AssetDepreciationOptions");
		$head[$h][2] = 'depreciation_options';
		$h++;
	}

	$head[$h][0] = DOL_URL_ROOT . '/asset/accountancy_codes.php?id=' . $object->id;
	$head[$h][1] = $langs->trans("AssetAccountancyCodes");
	$head[$h][2] = 'accountancy_codes';
	$h++;

	if (empty($object->not_depreciated)) {
		$head[$h][0] = DOL_URL_ROOT . '/asset/depreciation.php?id=' . $object->id;
		$head[$h][1] = $langs->trans("AssetDepreciation");
		$head[$h][2] = 'depreciation';
		$h++;
	}

	if (isset($object->disposal_date) && $object->disposal_date !== "") {
		$head[$h][0] = DOL_URL_ROOT . '/asset/disposal.php?id=' . $object->id;
		$head[$h][1] = $langs->trans("AssetDisposal");
		$head[$h][2] = 'disposal';
		$h++;
	}

	if (isset($object->fields['note_public']) || isset($object->fields['note_private'])) {
		$nbNote = 0;
		if (!empty($object->note_private)) {
			$nbNote++;
		}
		if (!empty($object->note_public)) {
			$nbNote++;
		}
		$head[$h][0] = DOL_URL_ROOT . '/asset/note.php?id=' . $object->id;
		$head[$h][1] = $langs->trans('Notes');
		if ($nbNote > 0) {
			$head[$h][1] .= (!getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER') ? '<span class="badge marginleftonlyshort">' . $nbNote . '</span>' : '');
		}
		$head[$h][2] = 'note';
		$h++;
	}

	require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT . '/core/class/link.class.php';
	$upload_dir = $conf->asset->dir_output . "/asset/" . dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$h][0] = DOL_URL_ROOT . '/asset/document.php?id=' . $object->id;
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles + $nbLinks) > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">' . ($nbFiles + $nbLinks) . '</span>';
	}
	$head[$h][2] = 'document';
	$h++;

	$head[$h][0] = DOL_URL_ROOT . '/asset/agenda.php?id=' . $object->id;
	$head[$h][1] = $langs->trans("Events");
	$head[$h][2] = 'agenda';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@asset:/asset/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@asset:/asset/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'asset');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'asset', 'remove');

	return $head;
}

/**
 * Prepare array of tabs for AssetModel
 *
 * @param	AssetModel	$object		AssetModel
 * @return	array<array{0:string,1:string,2:string}>	Array of tabs to show
 */
function assetModelPrepareHead($object)
{
	global $langs, $conf;

	$langs->loadLangs(array("assets", "admin"));

	$h = 0;
	$head = array();

	$head[$h][0] = dolBuildUrl(DOL_URL_ROOT . '/asset/model/card.php', ['id' => $object->id]);
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'card';
	$h++;

	if (isset($object->fields['note_public']) || isset($object->fields['note_private'])) {
		$nbNote = 0;
		if (!empty($object->note_private)) {
			$nbNote++;
		}
		if (!empty($object->note_public)) {
			$nbNote++;
		}
		$head[$h][0] = dolBuildUrl(DOL_URL_ROOT . '/asset/model/note.php', ['id' => $object->id]);
		$head[$h][1] = $langs->trans('Notes');
		if ($nbNote > 0) {
			$head[$h][1] .= (!getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER') ? '<span class="badge marginleftonlyshort">' . $nbNote . '</span>' : '');
		}
		$head[$h][2] = 'note';
		$h++;
	}

	$head[$h][0] = dolBuildUrl(DOL_URL_ROOT . '/asset/model/agenda.php', ['id' => $object->id]);
	$head[$h][1] = $langs->trans("Events");
	$head[$h][2] = 'agenda';
	$h++;


	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@asset:/asset/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@asset:/asset/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'assetmodel');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'assetmodel', 'remove');

	return $head;
}

/**
 * Return the list of the day count conventions available to compute the prorata temporis of a
 * depreciation, with their translation key.
 *
 * A convention defines *both* how the days of a partial period are counted and by how many days a
 * full year is divided. Mixing the two (counting real calendar days then dividing by 360) is not a
 * convention, it is a calculation error: it overestimates every partial period by about 1.39%.
 *
 * @return	array<string,string>	Array of convention code => translation key
 */
function getAssetDepreciationDayCountConventions()
{
	return array(
		'THIRTY_360' => 'AssetDayCountConventionThirty360',	// Months of 30 days, year of 360 days (usual in France)
		'ACT_365' => 'AssetDayCountConventionAct365',		// Real calendar days, year of 365 days
		'ACT_ACT' => 'AssetDayCountConventionActAct',		// Real calendar days, each year divided by its own length (365 or 366)
	);
}

/**
 * Return the day count convention to use to compute the prorata temporis of a depreciation.
 *
 * When the new setup ASSET_DEPRECIATION_DAY_COUNT_CONVENTION is not set, the convention is deduced
 * from the deprecated setup ASSET_DEPRECIATION_DURATION_PER_YEAR so that existing installations keep
 * the divisor they were configured with (360 => THIRTY_360, 365 or more => ACT_365).
 *
 * @return	string		'THIRTY_360', 'ACT_365' or 'ACT_ACT'
 */
function getAssetDepreciationDayCountConvention()
{
	global $mysoc;

	$convention = getDolGlobalString('ASSET_DEPRECIATION_DAY_COUNT_CONVENTION');
	if (array_key_exists($convention, getAssetDepreciationDayCountConventions())) {
		return $convention;
	}

	// Backward compatibility with the deprecated ASSET_DEPRECIATION_DURATION_PER_YEAR
	$nbdaysperyear = getDolGlobalInt('ASSET_DEPRECIATION_DURATION_PER_YEAR');
	if ($nbdaysperyear == 360) {
		return 'THIRTY_360';
	} elseif ($nbdaysperyear >= 365) {
		return 'ACT_365';
	}

	// Nothing set up at all: France computes depreciations on a 30/360 basis, other countries usually
	// count real calendar days.
	return (is_object($mysoc) && !empty($mysoc->country_code) && $mysoc->country_code == 'FR') ? 'THIRTY_360' : 'ACT_365';
}

/**
 * Return the fraction of a year (the prorata temporis) between two dates, for a given day count
 * convention. Both bounds are included, so a period covering a whole standard year returns a value
 * very close to 1 (exactly 1 with THIRTY_360).
 *
 * WARNING: this function uses the PHP server timezone by default because THIRTY_360 and ACT_ACT work
 * on the calendar representation of the dates. Force $forcetimezone to 'gmt' for UTC timestamps.
 *
 * @param	int		$timestampStart		Timestamp of the first day of the period
 * @param	int		$timestampEnd		Timestamp of the last day of the period
 * @param	string	$convention			Day count convention ('' to read the current setup)
 * @param	string	$forcetimezone		'' to use the PHP server timezone, or 'gmt', 'Europe/Paris', ...
 * @return	float						Fraction of year
 */
function getAssetDepreciationPeriodFraction($timestampStart, $timestampEnd, $convention = '', $forcetimezone = '')
{
	if ($convention === '' || !array_key_exists($convention, getAssetDepreciationDayCountConventions())) {
		// An unknown convention must not be computed as if it were one of the known ones: fall back on
		// the convention of the installation, the same value the caller would have got with ''.
		$convention = getAssetDepreciationDayCountConvention();
	}
	if ($timestampStart > $timestampEnd) {
		return 0.0;
	}

	if ($convention == 'THIRTY_360') {
		return num_between_day_30_360($timestampStart, $timestampEnd, 1, $forcetimezone) / 360;
	}

	if ($convention == 'ACT_ACT') {
		// The period is split per calendar year, each part being divided by the real length of its own
		// year (365 or 366), which is the only way to get an exact result on a period spanning a leap year.
		$start = dol_getdate((int) $timestampStart, false, $forcetimezone);
		$end = dol_getdate((int) $timestampEnd, false, $forcetimezone);

		$daystart = $start['yday'] + 1;		// 'yday' is 0 based
		$dayend = $end['yday'] + 1;

		if ($start['year'] == $end['year']) {
			return ($dayend - $daystart + 1) / num_days_in_year($start['year']);
		}

		$fraction = (num_days_in_year($start['year']) - $daystart + 1) / num_days_in_year($start['year']);
		for ($year = $start['year'] + 1; $year < $end['year']; $year++) {
			$fraction += 1;
		}
		$fraction += $dayend / num_days_in_year($end['year']);

		return $fraction;
	}

	// ACT_365
	return num_between_day($timestampStart, $timestampEnd, 1) / 365;
}

/**
 * Return the fraction of a month covered by a period, for the given day count convention.
 *
 * The monthly depreciation used to count the real calendar days of the period then divide them by
 * ASSET_DEPRECIATION_DURATION_PER_MONTH, a conventional month of 30 days. Numerator and denominator
 * came from two different systems, so the depreciation of a partial month depended on the length of
 * that month: placed in service on the 3rd, a 31 day month gave 29/30 of the monthly amount while a
 * 30 day month gave 28/30.
 *
 * @param	int		$timestampStart		Start of the period
 * @param	int		$timestampEnd		End of the period
 * @param	string	$convention			'THIRTY_360', 'ACT_365' or 'ACT_ACT'. Empty to use the setup.
 * @param	string	$forcetimezone		'' to use the server timezone, 'gmt' to read the dates in GMT
 * @return	float						Fraction of a month, between 0 and 1
 */
function getAssetDepreciationMonthFraction($timestampStart, $timestampEnd, $convention = '', $forcetimezone = '')
{
	if ($convention === '' || !array_key_exists($convention, getAssetDepreciationDayCountConventions())) {
		$convention = getAssetDepreciationDayCountConvention();
	}
	if ($timestampStart > $timestampEnd) {
		return 0.0;
	}

	$start = dol_getdate((int) $timestampStart, false, $forcetimezone);
	$end = dol_getdate((int) $timestampEnd, false, $forcetimezone);

	if ($convention == 'THIRTY_360') {
		// Every month is 30 days long, so the last day of a month counts as its 30th. Without that
		// rule a full February would be worth 28/30 of a monthly amount instead of a whole one.
		$lastdayofendmonth = dol_getdate(dol_get_last_day($end['year'], $end['mon'], true), false, $forcetimezone);
		$dayend = ($end['mday'] >= $lastdayofendmonth['mday'] ? 30 : min($end['mday'], 30));
		$daystart = min($start['mday'], 30);

		return max(0.0, min(1.0, ($dayend - $daystart + 1) / 30));
	}

	// ACT_365 and ACT_ACT: real days divided by the real length of the month, so that both sides of
	// the ratio belong to the same system.
	$lastdayofstartmonth = dol_getdate(dol_get_last_day($start['year'], $start['mon'], true), false, $forcetimezone);
	$nbdaysinmonth = (int) $lastdayofstartmonth['mday'];
	if ($nbdaysinmonth <= 0) {
		return 0.0;
	}

	return min(1.0, num_between_day($timestampStart, $timestampEnd, 1) / $nbdaysinmonth);
}
