<?php
/* Copyright (C) 2008-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * 	\file       	htdocs/ecm/class/htmlecm.form.class.php
 * 	\brief      	File of class to manage HTML component for ECM and generic filemanager
 */
require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmdirectory.class.php';


/**
 * Class to manage HTML component for ECM and generic filemanager
 */
class FormEcm
{
    /**
     * @var DoliDB Database handler.
     */
    public $db;

    /**
     * @var string Error code (or message)
     */
    public $error='';


	/**
	 * 	Constructor
	 *
	 * 	@param	DoliDB	$db		Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 *	Return list of sections
	 *
	 *  @param	int		$selected    		Id of preselected section
	 *  @param  string	$select_name		Name of HTML select component
	 *  @param	string	$module				Module ('ecm', 'medias', ...)
	 *  @return	string						String with HTML select
	 */
	public function selectAllSections($selected = 0, $select_name = '', $module = 'ecm')
	{
		global $conf, $langs;
		$langs->load("ecm");

		if ($select_name=='') $select_name="catParent";

		$cate_arbo=null;
		if ($module == 'ecm')
		{
			$cat = new EcmDirectory($this->db);
			$cate_arbo = $cat->get_full_arbo();
		}
		elseif ($module == 'medias')
		{
			include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
			$path = $conf->medias->multidir_output[$conf->entity];
			$cate_arbo = dol_dir_list($path, 'directories', 1, '', array('(\.meta|_preview.*\.png)$','^\.'), 'relativename', SORT_ASC);
		}

		$output = '<select class="flat minwidth100 maxwidth500" id="'.$select_name.'" name="'.$select_name.'">';
		if (is_array($cate_arbo))
		{
			if (! count($cate_arbo)) $output.= '<option value="-1" disabled>'.$langs->trans("NoDirectoriesFound").'</option>';
			else
			{
				$output.= '<option value="-1">&nbsp;</option>';
				foreach($cate_arbo as $key => $value)
				{
					$valueforoption = empty($cate_arbo[$key]['id']) ? $cate_arbo[$key]['relativename'] : $cate_arbo[$key]['id'];
					if ($selected && $valueforoption == $selected)
					{
						$add = 'selected ';
					}
					else
					{
						$add = '';
					}
					$output.= '<option '.$add.'value="'.dol_escape_htmltag($valueforoption).'">'.(empty($cate_arbo[$key]['fulllabel']) ? $cate_arbo[$key]['relativename'] : $cate_arbo[$key]['fulllabel']).'</option>';
				}
			}
		}
		$output.= '</select>';
		$output.=ajax_combobox($select_name);
		$output.= "\n";
		return $output;
	}
}
