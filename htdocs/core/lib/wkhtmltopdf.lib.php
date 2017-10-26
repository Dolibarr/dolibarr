<?php
/*  Copyright (C) 2017       Frédéric France     <frederic.france@netlogic.fr>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    core/lib/wkhtmltopdf.lib.php
 * \ingroup wkhtmltopdf
 * \brief   Library files with common functions for WkHtmlToPdf
 */

/**
 * Prepare admin pages header
 *
 * @return array
 */
function wkhtmltopdfAdminPrepareHead()
{
    global $langs, $conf;

    $langs->load("wkhtmltopdf");

    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath("/admin/wkhtmltopdf_setup.php", 1);
    $head[$h][1] = $langs->trans("Settings");
    $head[$h][2] = 'settings';
    $h++;
    $head[$h][0] = dol_buildpath("/admin/wkhtmltopdf_about.php", 1);
    $head[$h][1] = $langs->trans("About");
    $head[$h][2] = 'about';
    $h++;
    $head[$h][0] = dol_buildpath("/admin/wk_htmltopdf_edit.php", 1);
    $head[$h][1] = $langs->trans("TemplateEdit");
    $head[$h][2] = 'edit';
    $h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    //$this->tabs = array(
    // 'entity:+tabname:Title:@wkhtmltopdf:/wkhtmltopdf/mypage.php?id=__ID__'
    //); // to add new tab
    //$this->tabs = array(
    // 'entity:-tabname:Title:@wkhtmltopdf:/wkhtmltopdf/mypage.php?id=__ID__'
    //); // to remove a tab
    complete_head_from_modules($conf, $langs, $object, $head, $h, 'wkhtmltopdf');

    return $head;
}
