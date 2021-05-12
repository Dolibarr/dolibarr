<?php
<<<<<<< HEAD
/* Copyright (C) 2014-2015  Frederic France      <frederic.france@free.fr>
 * Copyright (C) 2016       Laurent Destailleur  <eldy@users.sourceforge.net>
=======
/* Copyright (C) 2014-2018  Frederic France         <frederic.france@netlogic.fr>
 * Copyright (C) 2016       Laurent Destailleur     <eldy@users.sourceforge.net>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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

// Load translation files required by the page
$langs->load("printing");


/*
 * Actions
 */

// None


/*
 * View
 */

<<<<<<< HEAD
llxHeader("",$langs->trans("Printing"));

print_barre_liste($langs->trans("Printing"), 0, $_SERVER["PHP_SELF"], '', '', '', '<a class="button" href="'.$_SERVER["PHP_SELF"].'">'.$langs->trans("Refresh").'</a>', 0, 0, 'title_setup.png');
=======
llxHeader("", $langs->trans("Printing"));

print_barre_liste($langs->trans("Printing"), 0, $_SERVER["PHP_SELF"], '', '', '', '<a class="button" href="' . $_SERVER["PHP_SELF"] . '">' . $langs->trans("Refresh") . '</a>', 0, 0, 'title_setup.png');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

print $langs->trans("DirectPrintingJobsDesc").'<br><br>';

// List Jobs from printing modules
$object = new PrintingDriver($db);
$result = $object->listDrivers($db, 10);
<<<<<<< HEAD
foreach ($result as $driver) 
{
=======
foreach ($result as $driver) {
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    require_once DOL_DOCUMENT_ROOT.'/core/modules/printing/'.$driver.'.modules.php';
    $classname = 'printing_'.$driver;
    $langs->load($driver);
    $printer = new $classname($db);
<<<<<<< HEAD
    if ($conf->global->{$printer->active}) 
    {
        //$printer->list_jobs('commande');
        $result = $printer->list_jobs();
        print $printer->resprint;
        
        if ($result > 0) 
        {
=======
    if ($conf->global->{$printer->active}) {
        //$printer->listJobs('commande');
        $result = $printer->listJobs();
        print $printer->resprint;

        if ($result > 0) {
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
            setEventMessages($printer->error, $printer->errors, 'errors');
        }
    }
}

<<<<<<< HEAD
llxFooter();

=======
// End of page
llxFooter();
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
$db->close();
