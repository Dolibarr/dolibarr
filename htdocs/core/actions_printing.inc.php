<?php
/* Copyright (C) 2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014 Frederic France      <frederic.france@free.fr>
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
 * or see http://www.gnu.org/
 */

/**
 *  \file           htdocs/core/actions_printing.inc.php
 *  \brief          Code for actions print_file to print file with calling trigger
 */


// $action must be defined
// $db, $user, $conf, $langs must be defined
// Filename to print must be provided into 'file' parameter

// Print file
if ($action == 'print_file' and $user->rights->printing->read) 
{
    $langs->load("printing");
    //require_once DOL_DOCUMENT_ROOT . '/core/class/dolprintipp.class.php';
    //$printer = new dolPrintIPP($db, $conf->global->PRINTIPP_HOST, $conf->global->PRINTIPP_PORT, $user->login, $conf->global->PRINTIPP_USER, $conf->global->PRINTIPP_PASSWORD);
    //$result = $printer->print_file(GETPOST('file', 'alpha'), GETPOST('printer', 'alpha'));
    // Call trigger to Print Doc
    //$actiontypecode='AC_PRINT';
    include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
    $interface=new Interfaces($db);
    $trigger_name='PRINT_DOCPDF';
    $printing->file = GETPOST('file', 'alpha');
    $printing->printer = GETPOST('printer', 'alpha');
    //print print_r($printing, true);

    $result=$interface->run_triggers($trigger_name,$printing,$user,$langs,$conf);
    if ($result < 0) {
        setEventMessage($interface->errors, 'errors');
    }
    if ($result == 0) {
        setEventMessage($langs->trans("NoModuleFound"));
    }

    if ($result>0) {
        setEventMessage($langs->trans("FileWasSentToPrinter", basename(GETPOST('file'))));
    }
    $action = '';
}
