<?php
/*
 * Copyright (C) 2014-2015  Frederic France      <frederic.france@free.fr>
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

/**
 *  \file       htdocs/printing/index.php
 *  \ingroup    printing
 *  \brief      Printing
 */

require '../main.inc.php';
include_once DOL_DOCUMENT_ROOT.'/core/modules/printing/modules_printing.php';

llxHeader("",$langs->trans("Printing"));

print load_fiche_titre($langs->trans("Printing"));

// List Jobs from printing modules
$object = new PrintingDriver($db);
$result = $object->listDrivers($db, 10);
foreach ($result as $driver) {
    require_once DOL_DOCUMENT_ROOT.'/core/modules/printing/'.$driver.'.modules.php';
    $classname = 'printing_'.$driver;
    $langs->load($driver);
    $printer = new $classname($db);
    if ($conf->global->{$printer->active}) {
        //$printer->list_jobs('commande');
        if ($printer->list_jobs()==0) {
            print $printer->resprint;
        } else {
            setEventMessages($printer->error, $printer->errors, 'errors');
        }
    }
}

llxFooter();

$db->close();
