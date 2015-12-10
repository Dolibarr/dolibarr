<?php
/* Copyright (C) 2012 Laurent Destailleur   <eldy@users.sourceforge.net>
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
 *	\file       htdocs/core/class/html.formpropal.class.php
 *  \ingroup    core
 *	\brief      File of class with all html predefined components
 */


/**
 *	Class to manage generation of HTML components for proposal management
 */
class FormSupplierProposal
{
	var $db;
	var $error;


	/**
	 * Constructor
	 *
	 * @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

    /**
     *    Return combo list of differents status of a proposal
     *    Values are id of table c_propalst
     *
     *    @param	string	$selected   Preselected value
     *    @param	int		$short		Use short labels
     *    @return	void
     */
    function selectSupplierProposalStatus($selected='',$short=0)
    {
        global $langs;

        $sql = "SELECT id, code, label, active FROM ".MAIN_DB_PREFIX."c_propalst";
        $sql .= " WHERE active = 1";

        dol_syslog(get_class($this)."::selectSupplierProposalStatus", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            print '<select class="flat" name="supplier_proposal_statut">';
            print '<option value="">&nbsp;</option>';
            $num = $this->db->num_rows($resql);
            $i = 0;
            if ($num)
            {
                while ($i < $num)
                {
                    $obj = $this->db->fetch_object($resql);
                    if ($selected == $obj->id)
                    {
                        print '<option value="'.$obj->id.'" selected>';
                    }
                    else
                    {
                        print '<option value="'.$obj->id.'">';
                    }
                    $key=$obj->code;
                    if ($langs->trans("PropalStatus".$key.($short?'Short':'')) != "PropalStatus".$key.($short?'Short':''))
                    {
                        print $langs->trans("PropalStatus".$key.($short?'Short':''));
                    }
                    else
                    {
                        $conv_to_new_code=array('PR_DRAFT'=>'Draft','PR_OPEN'=>'Opened','PR_CLOSED'=>'Closed','PR_SIGNED'=>'Signed','PR_NOTSIGNED'=>'NotSigned','PR_FAC'=>'Billed');
                        if (! empty($conv_to_new_code[$obj->code])) $key=$conv_to_new_code[$obj->code];
                        print ($langs->trans("PropalStatus".$key.($short?'Short':''))!="PropalStatus".$key.($short?'Short':''))?$langs->trans("PropalStatus".$key.($short?'Short':'')):$obj->label;
                    }
                    print '</option>';
                    $i++;
                }
            }
            print '</select>';
        }
        else
        {
            dol_print_error($this->db);
        }
    }

}

