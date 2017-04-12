<?php
/* Copyright (C) 2006-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2011      Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2013-2014 Marcos García        <marcosgdf@gmail.com>
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
 *  \file       htdocs/core/triggers/interface_50_modNotification_Notification.class.php
 *  \ingroup    notification
 *  \brief      File of class of triggers for notification module
 */
require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';


/**
 *  Class of triggers for notification module
 */
class InterfaceNotification extends DolibarrTriggers
{
	public $family = 'notification';
	public $description = "Triggers of this module send email notifications according to Notification module setup.";
	public $version = self::VERSION_DOLIBARR;
	public $picto = 'email';

    var $listofmanagedevents=array(
        'BILL_VALIDATE',
        'BILL_PAYED',
    	'ORDER_VALIDATE',
    	'PROPAL_VALIDATE',
        'FICHINTER_VALIDATE',
        'FICHINTER_ADD_CONTACT',
    	'ORDER_SUPPLIER_VALIDATE',
    	'ORDER_SUPPLIER_APPROVE',
    	'ORDER_SUPPLIER_REFUSE',
        'SHIPPING_VALIDATE'
   	);

	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "runTrigger" are triggered if file is inside directory htdocs/core/triggers or htdocs/module/code/triggers (and declared)
	 *
	 * @param string		$action		Event action code
	 * @param Object		$object     Object
	 * @param User		    $user       Object user
	 * @param Translate 	$langs      Object langs
	 * @param conf		    $conf       Object conf
	 * @return int         				<0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		if (empty($conf->notification->enabled)) return 0;     // Module not active, we do nothing

		require_once DOL_DOCUMENT_ROOT .'/core/class/notify.class.php';
		$notify = new Notify($this->db);
		
		if (! in_array($action, $notify->arrayofnotifsupported)) return 0;

		dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

		$notify->send($action, $object);

		return 1;
    }


    /**
     * Return list of events managed by notification module
     *
     * @return      array       Array of events managed by notification module
     */
    function getListOfManagedEvents()
    {
        global $conf;

        $ret=array();

        $sql = "SELECT rowid, code, label, description, elementtype";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_action_trigger";
        $sql.= $this->db->order("rang, elementtype, code");
        dol_syslog("getListOfManagedEvents Get list of notifications", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $num=$this->db->num_rows($resql);
            $i=0;
            while ($i < $num)
            {
                $obj=$this->db->fetch_object($resql);

                $qualified=0;
                // Check is this event is supported by notification module
                if (in_array($obj->code,$this->listofmanagedevents)) $qualified=1;
                // Check if module for this event is active
                if ($qualified)
                {
                    //print 'xx'.$obj->code;
                    $element=$obj->elementtype;

                    // Exclude events if related module is disabled
                    if ($element == 'order_supplier' && empty($conf->fournisseur->enabled)) $qualified=0;
                    elseif ($element == 'invoice_supplier' && empty($conf->fournisseur->enabled)) $qualified=0;
                    elseif ($element == 'withdraw' && empty($conf->prelevement->enabled)) $qualified=0;
                    elseif ($element == 'shipping' && empty($conf->expedition->enabled)) $qualified=0;
                    elseif ($element == 'member' && empty($conf->adherent->enabled)) $qualified=0;
                    elseif (! in_array($element,array('order_supplier','invoice_supplier','withdraw','shipping','member')) && empty($conf->$element->enabled)) $qualified=0;
                }

                if ($qualified)
                {
                    $ret[]=array('rowid'=>$obj->rowid,'code'=>$obj->code,'label'=>$obj->label,'description'=>$obj->description,'elementtype'=>$obj->elementtype);
                }

                $i++;
            }
        }
        else dol_print_error($this->db);

        return $ret;
    }

}
