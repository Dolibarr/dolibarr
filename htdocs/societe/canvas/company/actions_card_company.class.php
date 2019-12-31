<?php
/* Copyright (C) 2010-2011	Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2011		Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/societe/canvas/company/actions_card_company.class.php
 *	\ingroup    thirdparty
 *	\brief      File of Thirdparty card controller (default canvas)
 */
include_once DOL_DOCUMENT_ROOT.'/societe/canvas/actions_card_common.class.php';


/**
 *	ActionsCardCompany
 *
 *	Class with controller methods for thirdparty canvas
 */
class ActionsCardCompany extends ActionsCardCommon
{
	/**
	 *    Constructor
	 *
     *    @param	DoliDB	$db				Handler acces base de donnees
     *    @param	string	$dirmodule		Name of directory of module
     *    @param	string	$targetmodule	Name of directory of module where canvas is stored
     *    @param	string	$canvas			Name of canvas
     *    @param	string	$card			Name of tab (sub-canvas)
	 */
    public function __construct($db, $dirmodule, $targetmodule, $canvas, $card)
	{
        $this->db				= $db;
        $this->dirmodule		= $dirmodule;
        $this->targetmodule		= $targetmodule;
        $this->canvas			= $canvas;
        $this->card				= $card;
	}

    /**
     *  Return the title of card
     *
     *  @param	string	$action		Action code
     *  @return	string				Title
     */
    private function getTitle($action)
    {
        global $langs;

        $out='';

        if ($action == 'view')      $out.= $langs->trans("ThirdParty");
        if ($action == 'edit')      $out.= $langs->trans("EditCompany");
        if ($action == 'create')    $out.= $langs->trans("NewCompany");

        return $out;
    }


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    Assign custom values for canvas (for example into this->tpl to be used by templates)
	 *
	 *    @param	string	$action    Type of action
	 *    @param	integer	$id			Id of object
	 *    @param	string	$ref		Ref of object
	 *    @return	void
	 */
    public function assign_values(&$action, $id = 0, $ref = '')
	{
        // phpcs:enable
		global $conf, $langs, $user, $mysoc;
		global $form, $formadmin, $formcompany;

		$ret = $this->getObject($id, $ref);

		parent::assign_values($action);

        $this->tpl['title'] = load_fiche_titre($this->getTitle($action));

        $this->tpl['profid1'] 	= $this->object->idprof1;
		$this->tpl['profid2'] 	= $this->object->idprof2;
		$this->tpl['profid3'] 	= $this->object->idprof3;
		$this->tpl['profid4'] 	= $this->object->idprof4;

		if ($conf->use_javascript_ajax && empty($conf->global->MAIN_DISABLEVATCHECK))
		{
			$js = "\n";
	        $js.= '<script language="JavaScript" type="text/javascript">';
	        $js.= "function CheckVAT(a) {\n";
	        $js.= "newpopup('".DOL_URL_ROOT."/societe/checkvat/checkVatPopup.php?vatNumber='+a,'".dol_escape_js($langs->trans("VATIntraCheckableOnEUSite"))."',500,230);\n";
	        $js.= "}\n";
	        $js.= '</script>';
	        $js.= "\n";
			$this->tpl['js_checkVatPopup'] = $js;
		}

		if ($action == 'create' || $action == 'edit')
		{
			for ($i=1; $i<=4; $i++)
			{
				$this->tpl['langprofid'.$i]		= $langs->transcountry('ProfId'.$i, $this->object->country_code);
				$this->tpl['showprofid'.$i]		= $formcompany->get_input_id_prof($i, 'idprof'.$i, $this->tpl['profid'.$i], $this->object->country_code);
			}

			// Type
			$this->tpl['select_companytype']	= $form->selectarray("typent_id", $formcompany->typent_array(0), $this->object->typent_id);

			// Juridical Status
			$this->tpl['select_juridicalstatus'] = $formcompany->select_juridicalstatus($this->object->forme_juridique_code, $this->object->country_code);

			// Workforce
			$this->tpl['select_workforce'] = $form->selectarray("effectif_id", $formcompany->effectif_array(0), $this->object->effectif_id);

			// VAT intra
			$s='<input type="text" class="flat" name="tva_intra" size="12" maxlength="20" value="'.$this->object->tva_intra.'">';
			if (empty($conf->global->MAIN_DISABLEVATCHECK))
			{
				$s.=' ';

				if ($conf->use_javascript_ajax)
				{
					$s.='<a href="#" onclick="javascript: CheckVAT(document.formsoc.tva_intra.value);">'.$langs->trans("VATIntraCheck").'</a>';
					$this->tpl['tva_intra'] =  $form->textwithpicto($s, $langs->trans("VATIntraCheckDesc", $langs->transnoentitiesnoconv("VATIntraCheck")), 1);
				}
				else
				{
					$this->tpl['tva_intra'] =  $s.'<a href="'.$langs->transcountry("VATIntraCheckURL", $this->object->country_id).'" target="_blank">'.img_picto($langs->trans("VATIntraCheckableOnEUSite"), 'help').'</a>';
				}
			}
			else
			{
				$this->tpl['tva_intra'] = $s;
			}
		}
		else
		{
			// Confirm delete third party
			if ($action == 'delete')
			{
				$this->tpl['action_delete'] = $form->formconfirm($_SERVER["PHP_SELF"]."?socid=".$this->object->id, $langs->trans("DeleteACompany"), $langs->trans("ConfirmDeleteCompany"), "confirm_delete", '', 0, "1,action-delete");
			}

			for ($i=1; $i<=4; $i++)
			{
				$this->tpl['langprofid'.$i]		= $langs->transcountry('ProfId'.$i, $this->object->country_code);
				$this->tpl['checkprofid'.$i]	= $this->object->id_prof_check($i, $this->object);
				$this->tpl['urlprofid'.$i]		= $this->object->id_prof_url($i, $this->object);
			}

			// TVA intra
			if ($this->object->tva_intra)
			{
				$s=$this->object->tva_intra;
				$s.='<input type="hidden" name="tva_intra" size="12" maxlength="20" value="'.$this->object->tva_intra.'">';
				if (empty($conf->global->MAIN_DISABLEVATCHECK))
				{
					$s.=' &nbsp; ';

					if ($conf->use_javascript_ajax)
					{
						$s.='<a href="#" onclick="javascript: CheckVAT(document.formsoc.tva_intra.value);">'.$langs->trans("VATIntraCheck").'</a>';
						$this->tpl['tva_intra'] = $form->textwithpicto($s, $langs->trans("VATIntraCheckDesc", $langs->transnoentitiesnoconv("VATIntraCheck")), 1);
					}
					else
					{
						$this->tpl['tva_intra'] = $s.'<a href="'.$langs->transcountry("VATIntraCheckURL", $this->object->country_id).'" target="_blank">'.img_picto($langs->trans("VATIntraCheckableOnEUSite"), 'help').'</a>';
					}
				}
				else
				{
					$this->tpl['tva_intra'] = $s;
				}
			}
			else
			{
				$this->tpl['tva_intra'] = '&nbsp;';
			}

			// Parent company
			if ($this->object->parent)
			{
				$socm = new Societe($this->db);
				$socm->fetch($this->object->parent);
				$this->tpl['parent_company'] = $socm->getNomUrl(1).' '.($socm->code_client?"(".$socm->code_client.")":"");
				$this->tpl['parent_company'].= ($socm->town ? ' - ' . $socm->town : '');
			}
			else
			{
				$this->tpl['parent_company'] = $langs->trans("NoParentCompany");
			}
		}
    }

	/**
	 * 	Check permissions of a user to show a page and an object. Check read permission
	 * 	If $_REQUEST['action'] defined, we also check write permission.
	 *
	 * 	@param      User	$user      	  	User to check
	 * 	@param      string	$features	    Features to check (in most cases, it's module name)
	 * 	@param      int		$objectid      	Object ID if we want to check permission on a particular record (optional)
	 *  @param      string	$dbtablename    Table name where object is stored. Not used if objectid is null (optional)
	 *  @param      string	$feature2		Feature to check (second level of permission)
	 *  @param      string	$dbt_keyfield   Field name for socid foreign key if not fk_soc. (optional)
	 *  @param      string	$dbt_select		Field name for select if not rowid. (optional)
	 *  @return		int						1
	 */
    public function restrictedArea($user, $features = 'societe', $objectid = 0, $dbtablename = '', $feature2 = '', $dbt_keyfield = 'fk_soc', $dbt_select = 'rowid')
    {
        return restrictedArea($user, $features, $objectid, $dbtablename, $feature2, $dbt_keyfield, $dbt_select);
    }
}
