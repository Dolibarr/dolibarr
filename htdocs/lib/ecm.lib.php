<?php
/* Copyright (C) 2008-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *  \file       htdocs/lib/ecm.lib.php
 *  \brief      Ensemble de fonctions de base pour le module ecm
 *  \ingroup    ecm
 *  \version    $Id: ecm.lib.php,v 1.5 2011/07/31 23:25:21 eldy Exp $
 */


function ecm_prepare_head($obj)
{
	global $langs, $conf, $user;
	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/ecm/docmine.php?section='.$obj->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'card';
	$h++;

	return $head;
}

function ecm_file_prepare_head($obj)
{
    global $langs, $conf, $user;
    $h = 0;
    $head = array();

    $head[$h][0] = DOL_URL_ROOT.'/ecm/docfile.php?section='.$obj->section_id.'&urlfile='.urlencode($obj->label);
    $head[$h][1] = $langs->trans("Card");
    $head[$h][2] = 'card';
    $h++;

    return $head;
}

function ecm_prepare_head_fm($fac)
{
	global $langs, $conf;
	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/ecm/index.php?action=file_manager';
	$head[$h][1] = $langs->trans('ECMFileManager');
	$head[$h][2] = 'file_manager';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/ecm/search.php';
	$head[$h][1] = $langs->trans('Search');
	$head[$h][2] = 'search_form';
	$h++;

	return $head;
}

?>
