<?php
/* Copyright (C) 2008-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/core/class/html.formorder.class.php
 *  \ingroup    core
 *	\brief      File of predefined functions for HTML forms for order module
 */


/**
 *	Class to manage HTML output components for orders
 *	Before adding component here, check they are not into common part Form.class.php
 */
class FormOrder
{
	var $db;
	var $error;



	/**
	 *	Constructor
	 *
	 *	@param	DoliDB	$db		Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
		return 1;
	}


	/**
     *    Return combo list of differents status of a orders
     *
     *    @param	string	$selected   Preselected value
     *    @param	int		$short		Use short labels
     *    @param	string	$hmlname	Name of HTML select element
     *    @return	void
     */
    function selectSupplierOrderStatus($selected='', $short=0, $hmlname='order_status')
    {
        $tmpsupplierorder=new CommandeFournisseur($db);
        
        print '<select class="flat" name="'.$hmlname.'">';
        print '<option value="-1">&nbsp;</option>';
        $statustohow=array('0'=>'0','1'=>'1','2'=>'2','3'=>'3','4'=>'4','5'=>'5','6'=>'6,7','9'=>'9');	// 7 is same label than 6. 8 does not exists (billed is another field)

        foreach($statustohow as $key => $value)
        {
			print '<option value="'.$value.'"'.(($selected == $key || $selected == $value)?' selected':'').'>';
			$tmpsupplierorder->statut=$key;
			print $tmpsupplierorder->getLibStatut($short);
	        print '</option>';
        }
        print '</select>';
    }

	/**
	 *  Return list of way to order
	 *
	 *	@param	string	$selected		Id of preselected order origin
	 *  @param  string	$htmlname 		Name of HTML select list
	 *  @param  int		$addempty		0=liste sans valeur nulle, 1=ajoute valeur inconnue
	 *  @return	array					Tableau des sources de commandes
	 */
	function selectSourcesCommande($selected='',$htmlname='source_id',$addempty=0)
	{
		global $conf,$langs;
		print '<select class="flat" name="'.$htmlname.'">';
		if ($addempty) print '<option value="-1" selected>&nbsp;</option>';

		// TODO Use the table called llx_c_input_reason
		print '<option value="0"'.($selected=='0'?' selected':'').'>'.$langs->trans('OrderSource0').'</option>';
		print '<option value="1"'.($selected=='1'?' selected':'').'>'.$langs->trans('OrderSource1').'</option>';
		print '<option value="2"'.($selected=='2'?' selected':'').'>'.$langs->trans('OrderSource2').'</option>';
		print '<option value="3"'.($selected=='3'?' selected':'').'>'.$langs->trans('OrderSource3').'</option>';
		print '<option value="4"'.($selected=='4'?' selected':'').'>'.$langs->trans('OrderSource4').'</option>';
		print '<option value="5"'.($selected=='5'?' selected':'').'>'.$langs->trans('OrderSource5').'</option>';
		print '<option value="6"'.($selected=='6'?' selected':'').'>'.$langs->trans('OrderSource6').'</option>';

		print '</select>';
	}


	/**
	 *	Return list of input method (mode used to receive order, like order received by email, fax, online)
	 *  List found into table c_input_method.
	 *
	 *	@param	string	$selected		Id of preselected input method
	 *  @param  string	$htmlname 		Name of HTML select list
	 *  @param  int		$addempty		0=list with no empty value, 1=list with empty value
	 *  @return	array					Tableau des sources de commandes
	 */
	function selectInputMethod($selected='',$htmlname='source_id',$addempty=0)
	{
		global $conf,$langs,$form;

        if (! is_object($form)) $form=new Form($this->db);

        $listofmethods=array();

		$sql = "SELECT rowid, code, libelle as label";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_input_method";
		$sql.= " WHERE active = 1";

		dol_syslog(get_class($this)."::selectInputMethod", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$i = 0;
			$num = $this->db->num_rows($resql);
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);
				$listofmethods[$obj->rowid] = $langs->trans($obj->code)!=$obj->code?$langs->trans($obj->code):$obj->label;
				$i++;
			}
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}

		print $form->selectarray($htmlname,$listofmethods,$selected,$addempty);
		return 1;
	}

}

