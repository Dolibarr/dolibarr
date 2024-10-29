<?php
/* Copyright (C) 2015-2024  Alexandre Spangaro	<alexandre@inovea-conseil.com>
 * Copyright (C) 2022  		Lionel Vessiller    <lvessiller@open-dsi.fr>
 * Copyright (C) 2016       Charlie Benke		<charlie@patas-monkey.com>
 * Copyright (C) 2022  		Progiseize         	<a.bisotti@progiseize.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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

// $formatexportset must be defined
// $downloadMode 	=0 for direct download or =1 to download after writing files or =-1 not to download files

'
@phan-var-force string $formatexportset
@phan-var-force string $type_export
@phan-var-force string $filename
@phan-var-force int<-1,1> $downloadMode
';

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit(1);
}

$code = getDolGlobalString('MAIN_INFO_ACCOUNTANT_CODE');
$prefix = getDolGlobalString('ACCOUNTING_EXPORT_PREFIX_SPEC');
$format = getDolGlobalString('ACCOUNTING_EXPORT_FORMAT');
$nodateexport = getDolGlobalInt('ACCOUNTING_EXPORT_NO_DATE_IN_FILENAME');
$siren = getDolGlobalString('MAIN_INFO_SIREN');

$date_export = "_".dol_print_date(dol_now(), '%Y%m%d%H%M%S');
$endaccountingperiod = dol_print_date(dol_now(), '%Y%m%d');


if (empty($downloadMode)) {
	header('Content-Type: text/csv');
}

include_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountancyexport.class.php';
$accountancyexport = new AccountancyExport($db);

// Specific filename for FEC model export into the general ledger
if ((substr($accountancyexport->getFormatCode($formatexportset), 0, 3) == 'fec') && $type_export == "general_ledger") {
	// FEC format is defined here: https://www.legifrance.gouv.fr/affichCodeArticle.do?idArticle=LEGIARTI000027804775&cidTexte=LEGITEXT000006069583&dateTexte=20130802&oldAction=rechCodeArticle
	if (empty($search_date_end)) {
		// TODO Get the max date into bookkeeping table
		$search_date_end = dol_now();
	}
	$datetouseforfilename = $search_date_end;
	$tmparray = dol_getdate($datetouseforfilename);
	$fiscalmonth = getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1);
	// Define end of month to use
	if ($tmparray['mon'] < $fiscalmonth || $fiscalmonth == 1) {
		$tmparray['mon'] = $fiscalmonth == 1 ? 12 : $fiscalmonth - 1;
	} else {
		$tmparray['mon'] = $fiscalmonth - 1;
		$tmparray['year']++;
	}

	$endaccountingperiod = dol_print_date(dol_get_last_day($tmparray['year'], $tmparray['mon']), 'dayxcard');
	$siren = str_replace(" ", "", $siren);
	$completefilename = $siren."FEC".$endaccountingperiod.".txt";
} elseif ($accountancyexport->getFormatCode($formatexportset) == 'ciel' && $type_export == "general_ledger" && getDolGlobalString('ACCOUNTING_EXPORT_XIMPORT_FORCE_FILENAME')) {
	$completefilename = "XIMPORT.TXT";
} else {
	$completefilename = ($code ? $code."_" : "").($prefix ? $prefix."_" : "").$filename.($nodateexport ? "" : $date_export).".".$format;
}

if (empty($downloadMode)) {
	header('Content-Disposition: attachment;filename=' . $completefilename);
}
