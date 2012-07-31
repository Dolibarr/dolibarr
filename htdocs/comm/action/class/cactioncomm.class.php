<?php
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
 *       \file       htdocs/comm/action/class/cactioncomm.class.php
 *       \ingroup    commercial
 *       \brief      File of class to manage type of agenda events
 */


/**
 *      \class      CActionComm
 *	    \brief      Class to manage different types of events
 */
class CActionComm
{
    var $error;
    var $db;

    var $id;

    var $code;
    var $type;
    var $libelle;
    var $active;

    var $type_actions=array();


    /**
     *  Constructor
     *
     *  @param	DoliDB		$db		Database handler
     */
    function __construct($db)
    {
        $this->db = $db;
    }

    /**
     *  Load action type from database
     *
     *  @param	int		$id     id or code of action type to read
     *  @return int 			1=ok, 0=not found, -1=error
     */
    function fetch($id)
    {
        $sql = "SELECT id, code, type, libelle, active";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_actioncomm";
        if (is_numeric($id)) $sql.= " WHERE id=".$id;
        else $sql.= " WHERE code='".$id."'";

        dol_syslog(get_class($this)."::fetch sql=".$sql);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id      = $obj->id;
                $this->code    = $obj->code;
                $this->type    = $obj->type;
                $this->libelle = $obj->libelle;
                $this->active  = $obj->active;

                return 1;
            }
            else
            {
                return 0;
            }

            $this->db->free($resql);
        }
        else
        {
            $this->error=$this->db->error();
            return -1;
        }
    }

    /**
     *    Return list of event types
     *
     *    @param    int			$active     1 or 0 to filter on event state active or not ('' by default = no filter)
     *    @param	string		$idorcode	'id' or 'code'
     *    @return   array       			Array of all event types if OK, <0 if KO
     */
    function liste_array($active='',$idorcode='id')
    {
        global $langs,$conf;
        $langs->load("commercial");

        $repid = array();
        $repcode = array();

        $sql = "SELECT id, code, libelle, module";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_actioncomm";
        if ($active != '')
        {
            $sql.=" WHERE active=".$active;
        }
        $sql.= " ORDER BY module, position";

        dol_syslog(get_class($this)."::liste_array sql=".$sql);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $nump = $this->db->num_rows($resql);
            if ($nump)
            {
                $i = 0;
                while ($i < $nump)
                {
                    $obj = $this->db->fetch_object($resql);
                    $qualified=1;
                    if ($obj->module)
                    {
                        if ($obj->module == 'invoice' && ! $conf->facture->enabled)	 $qualified=0;
                        if ($obj->module == 'order'   && ! $conf->commande->enabled) $qualified=0;
                        if ($obj->module == 'propal'  && ! $conf->propal->enabled)	 $qualified=0;
                        if ($obj->module == 'invoice_supplier' && ! $conf->fournisseur->enabled)   $qualified=0;
                        if ($obj->module == 'order_supplier'   && ! $conf->fournisseur->enabled)   $qualified=0;
                    }
                    if ($qualified)
                    {
                        $transcode=$langs->trans("Action".$obj->code);
                        $repid[$obj->id] = ($transcode!="Action".$obj->code?$transcode:$langs->trans($obj->libelle));
                        $repcode[$obj->code] = ($transcode!="Action".$obj->code?$transcode:$langs->trans($obj->libelle));
                    }
                    $i++;
                }
            }
            if ($idorcode == 'id') $this->liste_array=$repid;
            if ($idorcode == 'code') $this->liste_array=$repcode;
            return $this->liste_array;
        }
        else
        {
            $this->error=$this->db->lasterror();
            return -1;
        }
    }


    /**
     *  Return name of action type as a label translated
     *
     *	@param	int		$withpicto		0=No picto, 1=Include picto into link, 2=Picto only
     *  @return string			      	Label of action type
     */
    function getNomUrl($withpicto=0)
    {
        global $langs;

        // Check if translation available
        $transcode=$langs->trans("Action".$this->code);
        if ($transcode != "Action".$this->code) return $transcode;
    }

}
?>
