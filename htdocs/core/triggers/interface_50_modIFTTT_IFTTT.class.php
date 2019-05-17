<?php
/*
 * Copyright (C) 2014-2016  Jean-François Ferry	<hello@librethic.io>
 * 				 2016       Christophe Battarel <christophe@altairis.fr>
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
 *  \file       htdocs/core/triggers/interface_50_modIFTTT_IFTTT.class.php
 *  \ingroup    core
 *  \brief      File of trigger for IFTTT module
 */
require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';


/**
 *  Class of triggers for IFTTT module
 */
class InterfaceIFTTT extends DolibarrTriggers
{
    /**
     * @var DoliDB Database handler.
     */
    public $db;

    /**
     *   Constructor
     *
     *   @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;

        $this->name = preg_replace('/^Interface/i', '', get_class($this));
        $this->family = "ifttt";
        $this->description = "Triggers of the module IFTTT";
        $this->version = 'dolibarr'; // 'development', 'experimental', 'dolibarr' or version
        $this->picto = 'ifttt';
    }

    /**
     *   Return name of trigger file
     *
     *   @return string      Name of trigger file
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     *   Return description of trigger file
     *
     *   @return string      Description of trigger file
     */
    public function getDesc()
    {
        return $this->description;
    }

    /**
     *   Return version of trigger file
     *
     *   @return string      Version of trigger file
     */
    public function getVersion()
    {
        global $langs;
        $langs->load("admin");

        if ($this->version == 'development') {
            return $langs->trans("Development");
        } elseif ($this->version == 'experimental') {
            return $langs->trans("Experimental");
        } elseif ($this->version == 'dolibarr') {
            return DOL_VERSION;
        } elseif ($this->version) {
            return $this->version;
        } else {
            return $langs->trans("Unknown");
        }
    }

    /**
     *      Function called when a Dolibarrr business event is done.
     *      All functions "runTrigger" are triggered if file is inside directory htdocs/core/triggers
     *
     *      @param  string    $action Event action code
     *      @param  Object    $object Object
     *      @param  User      $user   Object user
     *      @param  Translate $langs  Object langs
     *      @param  conf      $conf   Object conf
     *      @return int                     <0 if KO, 0 if no triggered ran, >0 if OK
     */
    public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
    {
		$ok = 0;

		if (empty($conf->ifttt->enabled)) return 0;     // Module not active, we do nothing

    	switch ($action) {
    		case 'THIRDPARTY_CREATED':
	            dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);

	            include_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';

	            // See https://platform.ifttt.com/docs/api_reference#realtime-api

                $arrayofdata=array();
                $arrayofdata['user_id']=$conf->global->IFTTT_USER_ID;
                $arrayofdata['trigger_identity']=$conf->global->IFTTT_TRIGGER_IDENTITY;
                $arrayofdata['name']='testabcdef';
                $arrayofdata['email']='testemailabcdef';

                $url = 'https://realtime.ifttt.com/v1/notifications';

                $addheaders=array(
                    'IFTTT-Service-Key'=>'123',
                    'Accept'=>'application/json',
                    'Accept-Charset'=>'utf-8',
                    'Accept-Encoding'=>'gzip, deflate',
                    'Content-Type'=>'application/json',
                    'X-Request-ID'=>getRandomPassword(true, null)
                );

                $result = getURLContent($url, 'POSTALREADYFORMATED', '', 1, $addheaders);

	            $ok = 1;
	            break;
    	}

        return $ok;
    }
}
