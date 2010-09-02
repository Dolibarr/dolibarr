<?php
/* Copyright (C) 2010 Regis Houssin  <regis@dolibarr.fr>
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
 *	\file       htdocs/societe/canvas/default/thirdparty.default.class.php
 *	\ingroup    thirparty
 *	\brief      Fichier de la classe des tiers par defaut
 *	\version    $Id$
 */

/**
 *	\class      ThirdPartyDefault
 *	\brief      Classe permettant la gestion des tiers par defaut, cette classe surcharge la classe societe
 */
class ThirdPartyDefault extends Societe
{
	//! Numero d'erreur Plage 1280-1535
	var $errno = 0;
	//! Template container
	var $tpl = array();

	/**
	 *    Constructeur de la classe
	 *    @param	DB		Handler acces base de donnees
	 */
	function ThirdPartyDefault($DB)
	{
		$this->db 				= $DB;
		
		$this->smarty			= 0;
		$this->module 			= "societe";
		$this->canvas 			= "default";
		$this->name 			= "default";
		$this->definition 		= "Canvas des tiers (défaut)";
		$this->fieldListName    = "thirdparty_default";
	}

	function getTitle($action)
	{
		global $langs;

		$out='';

		if ($action == 'view') 		$out.= $langs->trans("ThirdParty");
		if ($action == 'edit') 		$out.= $langs->trans("EditCompany");
		if ($action == 'create')	$out.= $langs->trans("NewCompany");
		
		return $out;
	}

	/**
	 *    Lecture des donnees dans la base
	 *    @param      id          Product id
	 */
	function fetch($id='', $action='')
	{
		$result = parent::fetch($id);

		return $result;
	}

	/**
	 *    Assign custom values for canvas
	 *    @param      action     Type of action
	 */
	function assign_values($action='')
	{
		global $conf, $langs, $user, $mysoc;
		global $form, $formadmin, $formcompany;
			
		parent::assign_values($action);
		
		$this->tpl['profid1'] 	= $this->siren;
		$this->tpl['profid2'] 	= $this->siret;
		$this->tpl['profid3'] 	= $this->ape;
		$this->tpl['profid4'] 	= $this->idprof4;
		
		if ($action == 'create' || $action == 'edit')
		{
			for ($i=1; $i<=4; $i++)
			{
				$this->tpl['langprofid'.$i]		= $langs->transcountry('ProfId'.$i,$this->pays_code);
				$this->tpl['showprofid'.$i]		= $this->get_input_id_prof($i,'idprof'.$i,$this->tpl['profid'.$i]);
			}
			
			// Type
			$this->tpl['select_companytype']	= $form->selectarray("typent_id",$formcompany->typent_array(0), $this->typent_id);
			
			// Juridical Status
			$this->tpl['select_juridicalstatus'] = $formcompany->select_juridicalstatus($this->forme_juridique_code,$this->pays_code);
			
			// Workforce
			$this->tpl['select_workforce'] = $form->selectarray("effectif_id",$formcompany->effectif_array(0), $this->effectif_id);
			
			// VAT intra
			$s ='<input type="text" class="flat" name="tva_intra" size="12" maxlength="20" value="'.$this->tva_intra.'">';
			$s.=' ';
			if ($conf->use_javascript_ajax)
			{
				$s.='<a href="#" onclick="javascript: CheckVAT(document.formsoc.tva_intra.value);">'.$langs->trans("VATIntraCheck").'</a>';
				$this->tpl['tva_intra'] =  $form->textwithpicto($s,$langs->trans("VATIntraCheckDesc",$langs->trans("VATIntraCheck")),1);
			}
			else
			{
				$this->tpl['tva_intra'] =  $s.'<a href="'.$langs->transcountry("VATIntraCheckURL",$this->id_pays).'" target="_blank">'.img_picto($langs->trans("VATIntraCheckableOnEUSite"),'help').'</a>';
			}
			
		}
		
		if ($action == 'view')
		{
			// Confirm delete third party
			if ($_GET["action"] == 'delete')
			{
				$this->tpl['action_delete'] = $form->formconfirm($_SERVER["PHP_SELF"]."?socid=".$this->id,$langs->trans("DeleteACompany"),$langs->trans("ConfirmDeleteCompany"),"confirm_delete",'',0,2);
			}
			
			for ($i=1; $i<=4; $i++)
			{
				$this->tpl['langprofid'.$i]		= $langs->transcountry('ProfId'.$i,$this->pays_code);
				$this->tpl['checkprofid'.$i]	= $this->id_prof_check($i,$this);
				$this->tpl['urlprofid'.$i]		= $this->id_prof_url($i,$this);
			}
			
			// TVA intra
			if ($this->tva_intra)
			{
				$s='';
				$s.=$this->tva_intra;
				$s.='<input type="hidden" name="tva_intra" size="12" maxlength="20" value="'.$this->tva_intra.'">';
				$s.=' &nbsp; ';
				if ($conf->use_javascript_ajax)
				{
					$s.='<a href="#" onclick="javascript: CheckVAT(document.formsoc.tva_intra.value);">'.$langs->trans("VATIntraCheck").'</a>';
					$this->tpl['tva_intra'] = $form->textwithpicto($s,$langs->trans("VATIntraCheckDesc",$langs->trans("VATIntraCheck")),1);
				}
				else
				{
					$this->tpl['tva_intra'] = $s.'<a href="'.$langs->transcountry("VATIntraCheckURL",$this->id_pays).'" target="_blank">'.img_picto($langs->trans("VATIntraCheckableOnEUSite"),'help').'</a>';
				}
			}
			else
			{
				$this->tpl['tva_intra'] = '&nbsp;';
			}
			
			// Parent company
			if ($this->parent)
			{
				$socm = new Societe($this->db);
				$socm->fetch($this->parent);
				$this->tpl['parent_company'] = $socm->getNomUrl(1).' '.($socm->code_client?"(".$socm->code_client.")":"");
				$this->tpl['parent_company'].= $socm->ville?' - '.$socm->ville:'';
			}
			else
			{
				$this->tpl['parent_company'] = $langs->trans("NoParentCompany");
			}
		}
	}

	/**
	 * 	Fetch datas list
	 */
	function LoadListDatas($limit, $offset, $sortfield, $sortorder)
	{
		global $conf, $langs;

		$this->list_datas = array();
	}

}

?>