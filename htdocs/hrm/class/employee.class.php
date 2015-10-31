<?php
/* Copyright (C) 2015		Alexandre Spangaro	<aspangaro.dolibarr@gmail.com>
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
 *    \file       htdocs/hrm/class/employee.class.php
 *    \ingroup    HRM
 *    \brief      File of class to manage employees
 */

require_once DOL_DOCUMENT_ROOT .'/core/class/commonobject.class.php';

/**
 * Class to manage establishments
 */
class Employee extends CommonObject
{
	public $element='employee';
	public $table_element='user';
	public $table_element_line = '';
	public $fk_element = 'fk_user';
	protected $ismultientitymanaged = 1;	// 0=No test on entity, 1=Test with field entity, 2=Test with link by societe

	var $rowid;

	var $name;
	var $address;
	var $zip;
	var $town;
	var $status;		// 0=open, 1=closed
	var $entity;

	var $statuts=array();
	var $statuts_short=array();

	/**
	 * Constructor
	 *
	 * @param	DoliDB		$db		Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;

		$this->statuts_short = array(0 => 'Opened', 1 => 'Closed');
        $this->statuts = array(0 => 'Opened', 1 => 'Closed');

		return 1;
	}

	/**
	 * Load an object from database
	 *
	 * @param	int		$id		Id of record to load
	 * @return	int				<0 if KO, >0 if OK
	 */
	function fetch($id)
	{
		$sql = "SELECT rowid, firstname, lastname, status, fk_user";
		$sql.= " FROM ".MAIN_DB_PREFIX."user";
		$sql.= " WHERE rowid = ".$id;

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ( $result )
		{
			$obj = $this->db->fetch_object($result);

			$this->id			= $obj->rowid;
			$this->name			= $obj->name;
			$this->address		= $obj->address;
			$this->zip			= $obj->zip;
			$this->town			= $obj->town;
			$this->status	    = $obj->status;

			return 1;
		}
		else
		{
			$this->error=$this->db->lasterror();
			return -1;
		}
	}

	/**
	 *  Return a link to the employee card (with optionaly the picto)
	 * 	Use this->id,this->lastname, this->firstname
	 *
	 *	@param	int		$withpictoimg		Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto, -1=Include photo into link, -2=Only picto photo)
	 *	@param	string	$option				On what the link point to
     *  @param	integer	$notooltip			1=Disable tooltip on picto and name
     *  @param	int		$maxlen				Max length of visible employee name
     *  @param	int		$hidethirdpartylogo	Hide logo of thirdparty
     *  @param  string  $mode               ''=Show firstname and lastname, 'firstname'=Show only firstname, 'login'=Show login
     *  @param  string  $morecss            Add more css on link
	 *	@return	string						String with URL
	 */
	function getNomUrl($withpictoimg=0, $option='', $notooltip=0, $maxlen=24, $hidethirdpartylogo=0, $mode='',$morecss='')
	{
		global $langs, $conf;

		if (! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) && $withpictoimg) $withpictoimg=0;
			
        $result = '';
        $companylink = '';
        $link = '';

        $label = '<u>' . $langs->trans("Employee") . '</u>';
        $label.= '<div width="100%">';
        $label.= '<b>' . $langs->trans('Name') . ':</b> ' . $this->getFullName($langs,'','');
        $label.= '<br><b>' . $langs->trans("EMail").':</b> '.$this->email;
        $label.='</div>';
        if (! empty($this->photo))
        {
        	$label.= '<div class="photointooltip">';
            $label.= Form::showphoto('userphoto', $this, 80, 0, 0, 'photowithmargin photologintooltip', 'small', 0, 1);
        	$label.= '</div><div style="clear: both;"></div>';
        }

        $link.= '<a href="'.DOL_URL_ROOT.'/hrm/employee/card.php?id='.$this->id.'"';
        if (empty($notooltip))
        {
            if (! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) 
            {
                $langs->load("users");
                $label=$langs->trans("ShowUser");
                $link.=' alt="'.dol_escape_htmltag($label, 1).'"'; 
            }
            $link.= ' title="'.dol_escape_htmltag($label, 1).'"';
            $link.= ' class="classfortooltip'.($morecss?' '.$morecss:'').'"';
        }
        $link.= '>';
		$linkend='</a>';

        //if ($withpictoimg == -1) $result.='<div class="nowrap">';
		$result.=$link;
        if ($withpictoimg)
        {
        	$paddafterimage='';
            if (abs($withpictoimg) == 1) $paddafterimage='style="padding-right: 3px;"';
        	if ($withpictoimg > 0) $picto='<div class="inline-block valignmiddle'.($morecss?' userimg'.$morecss:'').'">'.img_object('', 'user', $paddafterimage.' '.($notooltip?'':'class="classfortooltip"')).'</div>';
        	else $picto='<div class="inline-block valignmiddle'.($morecss?' userimg'.$morecss:'').'"'.($paddafterimage?' '.$paddafterimage:'').'>'.Form::showphoto('userphoto', $this, 0, 0, 0, 'loginphoto', 'mini', 0, 1).'</div>';
            $result.=$picto;
		}
		if (abs($withpictoimg) != 2) 
		{
			if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) $result.='<div class="inline-block valignmiddle'.($morecss?' usertext'.$morecss:'').'">';
			if ($mode == 'login') $result.=dol_trunc($this->login, $maxlen);
			else $result.=$this->getFullName($langs,'',($mode == 'firstname' ? 2 : -1),$maxlen);
			if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) $result.='</div>';
		}
		$result.=$linkend;
		//if ($withpictoimg == -1) $result.='</div>';
		$result.=$companylink;
		return $result;
	}

	/**
	 *  Return status label of an employee
	 *
	 *  @param	int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 *  @return	string 			       Label of status
	 */
	function getLibStatut($mode=0)
	{
		return $this->LibStatut($this->statut,$mode);
	}

	/**
	 *  Return label of given status
	 *
	 *  @param	int		$statut        	Id statut
	 *  @param  int		$mode          	0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 *  @return string 			       	Label of status
	 */
	function LibStatut($statut,$mode=0)
	{
		global $langs;
		$langs->load('users');

		if ($mode == 0)
		{
			$prefix='';
			if ($statut == 1) return $langs->trans('Enabled');
			if ($statut == 0) return $langs->trans('Disabled');
		}
		if ($mode == 1)
		{
			if ($statut == 1) return $langs->trans('Enabled');
			if ($statut == 0) return $langs->trans('Disabled');
		}
		if ($mode == 2)
		{
			if ($statut == 1) return img_picto($langs->trans('Enabled'),'statut4').' '.$langs->trans('Enabled');
			if ($statut == 0) return img_picto($langs->trans('Disabled'),'statut5').' '.$langs->trans('Disabled');
		}
		if ($mode == 3)
		{
			if ($statut == 1) return img_picto($langs->trans('Enabled'),'statut4');
			if ($statut == 0) return img_picto($langs->trans('Disabled'),'statut5');
		}
		if ($mode == 4)
		{
			if ($statut == 1) return img_picto($langs->trans('Enabled'),'statut4').' '.$langs->trans('Enabled');
			if ($statut == 0) return img_picto($langs->trans('Disabled'),'statut5').' '.$langs->trans('Disabled');
		}
		if ($mode == 5)
		{
			if ($statut == 1) return $langs->trans('Enabled').' '.img_picto($langs->trans('Enabled'),'statut4');
			if ($statut == 0) return $langs->trans('Disabled').' '.img_picto($langs->trans('Disabled'),'statut5');
		}
	}
}
