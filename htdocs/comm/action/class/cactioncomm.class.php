<?php
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *       \file       htdocs/comm/action/class/cactioncomm.class.php
 *       \ingroup    agenda
 *       \brief      File of class to manage type of agenda events
 */


/**
 *      Class to manage different types of events
 */
class CActionComm
{
    /**
     * @var string Error code (or message)
     */
    public $error='';

    /**
     * @var DoliDB Database handler.
     */
    public $db;

    /**
     * @var int ID
     */
    public $id;

    public $code;
    public $type;
    public $libelle;       // deprecated

    /**
     * @var string Type of agenda event label
     */
    public $label;

    public $active;
    public $color;

    /**
     * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
     */
    public $picto;

    public $type_actions=array();


    /**
     *  Constructor
     *
     *  @param	DoliDB		$db		Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     *  Load action type from database
     *
     *  @param  int     $id     id or code of action type to read
     *  @return int             1=ok, 0=not found, -1=error
     */
    public function fetch($id)
    {
        $sql = "SELECT id, code, type, libelle as label, color, active, picto";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_actioncomm";
        if (is_numeric($id)) $sql.= " WHERE id=".$id;
        else $sql.= " WHERE code='".$this->db->escape($id)."'";

        dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id      = $obj->id;
                $this->code    = $obj->code;
                $this->type    = $obj->type;
                $this->libelle = $obj->label;   // deprecated
                $this->label   = $obj->label;
                $this->active  = $obj->active;
                $this->color   = $obj->color;

                $this->db->free($resql);
                return 1;
            }
            else
			{
                $this->db->free($resql);
                return 0;
            }
        }
        else
        {
            $this->error=$this->db->error();
            return -1;
        }
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *  Return list of event types: array(id=>label) or array(code=>label)
     *
     *  @param  string|int  $active         1 or 0 to filter on event state active or not ('' by default = no filter)
     *  @param  string      $idorcode       'id' or 'code'
     *  @param  string      $excludetype    Type to exclude ('system' or 'systemauto')
     *  @param  int         $onlyautoornot  1=Group all type AC_XXX into 1 line AC_MANUAL. 0=Keep details of type, -1=Keep details and add a combined line "All manual"
     *  @param  string      $morefilter     Add more SQL filter
     *  @param  int         $shortlabel     1=Get short label instead of long label
     *  @return mixed                       Array of all event types if OK, <0 if KO. Key of array is id or code depending on parameter $idorcode.
     */
    public function liste_array($active = '', $idorcode = 'id', $excludetype = '', $onlyautoornot = 0, $morefilter = '', $shortlabel = 0)
    {
        // phpcs:enable
        global $langs,$conf;
        $langs->load("commercial");

        $repid = array();
        $repcode = array();

        $sql = "SELECT id, code, libelle as label, module, type, color, picto";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_actioncomm";
        $sql.= " WHERE 1=1";
        if ($active != '') $sql.=" AND active=".$active;
        if (! empty($excludetype)) $sql.=" AND type <> '".$excludetype."'";
        if ($morefilter) $sql.=" AND ".$morefilter;
        $sql.= " ORDER BY module, position, type";

        dol_syslog(get_class($this)."::liste_array", LOG_DEBUG);
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

                    // $obj->type can be system, systemauto, module, moduleauto, xxx, xxxauto
                    if ($qualified && $onlyautoornot > 0 && preg_match('/^system/', $obj->type) && ! preg_match('/^AC_OTH/', $obj->code)) $qualified=0;	// We discard detailed system events. We keep only the 2 generic lines (AC_OTH and AC_OTH_AUTO)

                    if ($qualified && $obj->module)
                    {
                        if ($obj->module == 'invoice' && ! $conf->facture->enabled)	 $qualified=0;
                        if ($obj->module == 'order'   && ! $conf->commande->enabled) $qualified=0;
                        if ($obj->module == 'propal'  && ! $conf->propal->enabled)	 $qualified=0;
                        if ($obj->module == 'invoice_supplier' && ! $conf->fournisseur->enabled)   $qualified=0;
                        if ($obj->module == 'order_supplier'   && ! $conf->fournisseur->enabled)   $qualified=0;
                        if ($obj->module == 'shipping'  && ! $conf->expedition->enabled)	 $qualified=0;
                    }

                    if ($qualified)
                    {
                        $keyfortrans='';
                    	$transcode='';
                    	$code=$obj->code;
                    	if ($onlyautoornot > 0 && $code == 'AC_OTH') $code='AC_MANUAL';
                    	if ($onlyautoornot > 0 && $code == 'AC_OTH_AUTO') $code='AC_AUTO';
                    	if ($shortlabel)
                    	{
                    		$keyfortrans="Action".$code.'Short';
                    		$transcode=$langs->trans($keyfortrans);
                    	}
                    	if (empty($keyfortrans) || $keyfortrans == $transcode)
                    	{
                    		$keyfortrans="Action".$code;
                    		$transcode=$langs->trans($keyfortrans);
                    	}
                    	$label = (($transcode!=$keyfortrans) ? $transcode : $langs->trans($obj->label));
                        if ($onlyautoornot == -1 && ! empty($conf->global->AGENDA_USE_EVENT_TYPE) && ! preg_match('/auto/i', $code))
                        {
                            $label='&nbsp; '.$label;
                            $repid[-99]=$langs->trans("ActionAC_MANUAL");
                            $repcode['AC_NON_AUTO']=$langs->trans("ActionAC_MANUAL");
                        }
                    	$repid[$obj->id] = $label;
                    	$repcode[$obj->code] = $label;
                        if ($onlyautoornot > 0 && preg_match('/^module/', $obj->type) && $obj->module) $repcode[$obj->code].=' ('.$langs->trans("Module").': '.$obj->module.')';
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
    public function getNomUrl($withpicto = 0)
    {
        global $langs;

        // Check if translation available
        $transcode=$langs->trans("Action".$this->code);
        if ($transcode != "Action".$this->code) return $transcode;
    }
}
