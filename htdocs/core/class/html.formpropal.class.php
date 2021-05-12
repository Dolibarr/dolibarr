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
class FormPropal
{
<<<<<<< HEAD
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
=======
    /**
     * @var DoliDB Database handler.
     */
    public $db;

    /**
     * @var string Error code (or message)
     */
    public $error='';


    /**
     * Constructor
     *
     * @param		DoliDB		$db      Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

    /**
     *    Return combo list of differents status of a proposal
     *    Values are id of table c_propalst
     *
     *    @param	string	$selected   	Preselected value
     *    @param	int		$short			Use short labels
     *    @param	int		$excludedraft	0=All status, 1=Exclude draft status
     *    @param	int 	$showempty		1=Add empty line
     *    @param    string  $mode           'customer', 'supplier'
     *    @param    string  $htmlname       Name of select field
     *    @return	void
     */
<<<<<<< HEAD
    function selectProposalStatus($selected='',$short=0, $excludedraft=0, $showempty=1, $mode='customer',$htmlname='propal_statut')
=======
    public function selectProposalStatus($selected = '', $short = 0, $excludedraft = 0, $showempty = 1, $mode = 'customer', $htmlname = 'propal_statut')
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    {
        global $langs;

        $prefix='';
        $listofstatus=array();
<<<<<<< HEAD
        if ($mode == 'supplier') 
        {
            $prefix='SupplierProposalStatus';
            
=======
        if ($mode == 'supplier') {
            $prefix='SupplierProposalStatus';

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
            $langs->load("supplier_proposal");
            $listofstatus=array(
                0=>array('id'=>0, 'code'=>'PR_DRAFT'),
                1=>array('id'=>1, 'code'=>'PR_OPEN'),
                2=>array('id'=>2, 'code'=>'PR_SIGNED'),
                3=>array('id'=>3, 'code'=>'PR_NOTSIGNED'),
                4=>array('id'=>4, 'code'=>'PR_CLOSED')
            );
<<<<<<< HEAD
        }
        else
        {
            $prefix="PropalStatus";
            
=======
        } else {
            $prefix="PropalStatus";

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
            $sql = "SELECT id, code, label, active FROM ".MAIN_DB_PREFIX."c_propalst";
            $sql .= " WHERE active = 1";
            dol_syslog(get_class($this)."::selectProposalStatus", LOG_DEBUG);
            $resql=$this->db->query($sql);
            if ($resql)
            {
                $num = $this->db->num_rows($resql);
                $i = 0;
                if ($num)
                {
                    while ($i < $num)
                    {
                        $obj = $this->db->fetch_object($resql);
                        $listofstatus[$obj->id]=array('id'=>$obj->id,'code'=>$obj->code,'label'=>$obj->label);
                        $i++;
                    }
                }
            }
            else
            {
                dol_print_error($this->db);
            }
        }

        print '<select class="flat" name="'.$htmlname.'">';
        if ($showempty) print '<option value="-1">&nbsp;</option>';

        foreach($listofstatus as $key => $obj)
        {
            if ($excludedraft)
            {
<<<<<<< HEAD
				if ($obj['code'] == 'Draft' || $obj['code'] == 'PR_DRAFT')
				{
					$i++;
					continue;
				}
=======
                if ($obj['code'] == 'Draft' || $obj['code'] == 'PR_DRAFT')
                {
                    $i++;
                    continue;
                }
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
            }
            if ($selected != '' && $selected == $obj['id'])
            {
                print '<option value="'.$obj['id'].'" selected>';
            }
            else
            {
                print '<option value="'.$obj['id'].'">';
            }
            $key=$obj['code'];
            if ($langs->trans($prefix.$key.($short?'Short':'')) != $prefix.$key.($short?'Short':''))
            {
                print $langs->trans($prefix.$key.($short?'Short':''));
            }
            else
<<<<<<< HEAD
			{
=======
            {
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
                $conv_to_new_code=array('PR_DRAFT'=>'Draft','PR_OPEN'=>'Validated','PR_CLOSED'=>'Closed','PR_SIGNED'=>'Signed','PR_NOTSIGNED'=>'NotSigned','PR_FAC'=>'Billed');
                if (! empty($conv_to_new_code[$obj['code']])) $key=$conv_to_new_code[$obj['code']];

                print ($langs->trans($prefix.$key.($short?'Short':''))!=$prefix.$key.($short?'Short':''))?$langs->trans($prefix.$key.($short?'Short':'')):($obj['label']?$obj['label']:$obj['code']);
            }
            print '</option>';
            $i++;
        }
        print '</select>';
    }
}
<<<<<<< HEAD

=======
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
