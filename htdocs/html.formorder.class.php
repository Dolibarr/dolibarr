<?php
/* Copyright (C) 2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *	\file       htdocs/html.formorder.class.php
 *	\brief      File of predefined functions for HTML forms for order module
 *	\version	$Id$
 */


/**
 *	\class      FormOrder
 *	\brief      Classe permettant la generation de composants html
 *	\remarks	Only common components must be here.
 */
class FormOrder
{
	var $db;
	var $error;



	/**
	 *	\brief     Constructeur
	 *	\param     DB      handler d'acces base de donnee
	 */
	function FormOrder($DB)
	{
		$this->db = $DB;

		return 1;
	}


	/**
	 *   	\brief      Renvoie la liste des sources de commandes
	 *		\param      selected		Id de la source pre-selectionnee
	 *    	\param     	htmlname 		Nom de la liste deroulante
	 *      \param     	addempty		0=liste sans valeur nulle, 1=ajoute valeur inconnue
	 *      \return		array			Tableau des sources de commandes
	 */
	function selectSourcesCommande($selected='',$htmlname='source_id',$addempty=0)
	{
		global $conf,$langs;
		print '<select class="flat" name="'.$htmlname.'" '.$htmloption.'>';
		if ($addempty) print '<option value="-1" selected="true">&nbsp;</option>';

		// \TODO Aller chercher les sources dans dictionnaire
		print '<option value="0"'.($selected=='0'?' selected="true"':'').'>'.$langs->trans('OrderSource0').'</option>';
		print '<option value="1"'.($selected=='1'?' selected="true"':'').'>'.$langs->trans('OrderSource1').'</option>';
		print '<option value="2"'.($selected=='2'?' selected="true"':'').'>'.$langs->trans('OrderSource2').'</option>';
		print '<option value="3"'.($selected=='3'?' selected="true"':'').'>'.$langs->trans('OrderSource3').'</option>';
		print '<option value="4"'.($selected=='4'?' selected="true"':'').'>'.$langs->trans('OrderSource4').'</option>';
		print '<option value="5"'.($selected=='5'?' selected="true"':'').'>'.$langs->trans('OrderSource5').'</option>';
		print '<option value="6"'.($selected=='6'?' selected="true"':'').'>'.$langs->trans('OrderSource6').'</option>';

		print '</select>';
	}


	/**
	 *
	 *
	 */
	function select_methodes_commande($selected='',$htmlname='source_id',$addempty=0)
	{
		global $conf,$langs;
		$listemethodes=array();
		
		require_once(DOL_DOCUMENT_ROOT."/html.form.class.php");
		$form=new Form($this->db);
		
		$sql = "SELECT rowid, libelle ";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_methode_commande_fournisseur";
		$sql.= " WHERE active = 1";

		dol_syslog("Form::select_methodes_commande sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$i = 0;
			$num = $this->db->num_rows($resql);
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);
				$listemethodes[$obj->rowid] = $obj->libelle;
				$i++;
			}
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}

		print $form->select_array($htmlname,$listemethodes,$selected,$addempty);
		return 1;
	}

}

?>
