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
 *	Classe permettant la generation de composants html
 *	Only common components are here.
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
		if ($addempty) print '<option value="-1" selected="selected">&nbsp;</option>';

		// TODO Use a table called llx_c_input_reason
		print '<option value="0"'.($selected=='0'?' selected="selected"':'').'>'.$langs->trans('OrderSource0').'</option>';
		print '<option value="1"'.($selected=='1'?' selected="selected"':'').'>'.$langs->trans('OrderSource1').'</option>';
		print '<option value="2"'.($selected=='2'?' selected="selected"':'').'>'.$langs->trans('OrderSource2').'</option>';
		print '<option value="3"'.($selected=='3'?' selected="selected"':'').'>'.$langs->trans('OrderSource3').'</option>';
		print '<option value="4"'.($selected=='4'?' selected="selected"':'').'>'.$langs->trans('OrderSource4').'</option>';
		print '<option value="5"'.($selected=='5'?' selected="selected"':'').'>'.$langs->trans('OrderSource5').'</option>';
		print '<option value="6"'.($selected=='6'?' selected="selected"':'').'>'.$langs->trans('OrderSource6').'</option>';

		print '</select>';
	}


	/**
	 *	Return list of way to order
	 *
	 *	@param	string	$selected		Id of preselected input method
	 *  @param  string	$htmlname 		Name of HTML select list
	 *  @param  int		$addempty		0=liste sans valeur nulle, 1=ajoute valeur inconnue
	 *  @return	array					Tableau des sources de commandes
	 */
	function select_methodes_commande($selected='',$htmlname='source_id',$addempty=0)
	{
		global $conf,$langs;
		$listemethodes=array();

		require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
		$form=new Form($this->db);

		$sql = "SELECT rowid, code, libelle as label";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_input_method";
		$sql.= " WHERE active = 1";

		dol_syslog(get_class($this)."::select_methodes_commande sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$i = 0;
			$num = $this->db->num_rows($resql);
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);
				$listemethodes[$obj->rowid] = $langs->trans($obj->code)!=$obj->code?$langs->trans($obj->code):$obj->label;
				$i++;
			}
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}

		print $form->selectarray($htmlname,$listemethodes,$selected,$addempty);
		return 1;
	}

}

?>
