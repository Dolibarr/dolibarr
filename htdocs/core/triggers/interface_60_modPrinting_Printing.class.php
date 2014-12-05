<?php
/* Copyright (C) 2005-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2014 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2014      Marcos García        <marcosgdf@gmail.com>
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
 *  \file       htdocs/core/triggers/interface_60_modPrinting_Printing.class.php
 *  \ingroup    printing
 *  \brief      Trigger call by printing to print with PrintIPP
 *  \remarks
 */
require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';


/**
 *  Class of triggers for printing
 */
class InterfacePrinting extends DolibarrTriggers
{

    public $family = 'printing';
    public $picto = 'technic';
    public $description = "Triggers of this module is used for printing via PrintIPP.";
    public $version = self::VERSION_DOLIBARR;

    /**
     * Function called when a Dolibarrr business event is done.
     * All functions "runTrigger" are triggered if file is inside directory htdocs/core/triggers or htdocs/module/code/triggers (and declared)
     *
     * @param string        $action     Event action code
     * @param Object        $object     Object
     * @param User          $user       Object user
     * @param Translate     $langs      Object langs
     * @param conf          $conf       Object conf
     * @return int                      <0 if KO, 0 if no triggered ran, >0 if OK
     */
    public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
    {
        global $db;
        // Put here code you want to execute when a Dolibarr business events occurs.
        // Data and type of action are stored into $object and $action

        // Actions

        if ($action=='PRINT_DOCPDF') {
            require_once DOL_DOCUMENT_ROOT . '/core/class/dolprintipp.class.php';
            $printer = new dolPrintIPP($db, $conf->global->PRINTIPP_HOST, $conf->global->PRINTIPP_PORT, $user->login, $conf->global->PRINTIPP_USER, $conf->global->PRINTIPP_PASSWORD);
            $result = $printer->print_file($object->file, $object->printer);
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            //$this->errors[]='test';
            //return -1;
            return 1;
        }
        return 0;
    }

}
