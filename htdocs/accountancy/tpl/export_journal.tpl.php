<?php
/* Copyright (C) 2015  Alexandre Spangaro	<aspangaro@zendsi.com>
 * Copyright (C) 2016  Charlie Benke		<charlie@patas-monkey.com>
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

// Protection to avoid direct call of template
if (empty($conf) || ! is_object($conf))
{
	print "Error, template page can't be called as URL";
	exit;
}

$code = $conf->global->MAIN_INFO_ACCOUNTANT_CODE;
$prefix = $conf->global->ACCOUNTING_EXPORT_PREFIX_SPEC;
$format = $conf->global->ACCOUNTING_EXPORT_FORMAT;
$nodateexport = $conf->global->ACCOUNTING_EXPORT_NO_DATE_IN_FILENAME;

$date_export = "_" . dol_print_date($now, '%Y%m%d%H%M%S');

header('Content-Type: text/csv');

$completefilename = ($code?$code . "_":"") . ($prefix?$prefix . "_":"") . $filename . ($nodateexport?"":$date_export) . "." . $format;

header('Content-Disposition: attachment;filename=' . $completefilename);
