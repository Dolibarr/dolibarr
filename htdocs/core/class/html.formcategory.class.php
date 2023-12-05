<?php
/* Copyright (C) 2020		Tobias Sekan	<tobias.sekan@startmail.com>
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
 *	\file		htdocs/core/class/html.formcategory.class.php
 *	\ingroup	core
 *	\brief		File of class to build HTML component for category filtering
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';


/**
 *	Class to manage forms for categories
 */
class FormCategory extends Form
{
	/**
	 * Return a HTML filter box for a list filter view
	 *
	 * @param 	string		$type								The categorie type (e.g Categorie::TYPE_WAREHOUSE)
	 * @param 	array		$preSelected						A list with the elements that should pre-selected
	 * @param	string		$morecss							More CSS
	 * @param	int			$searchCategoryProductOperator		0 or 1 to enable the checkbox to search with a or (0=not preseleted, 1=preselected)
	 * @param	int			$multiselect						0 or 1
	 * @param	int			$nocateg							1=Add an entry '- No Category -'
	 * @param	string		$showempty							1 or 'string' to add an empty entry
	 * @return 	string											A HTML filter box (Note: selected results can get with GETPOST("search_category_".$type."_list"))
	 */
	public function getFilterBox($type, array $preSelected, $morecss = "minwidth300imp widthcentpercentminusx", $searchCategoryProductOperator = -1, $multiselect = 1, $nocateg = 1, $showempty = '')
	{
		global $langs, $db;

		if (empty($preSelected) || !is_array($preSelected)) {
			$preSelected = array();
		}

		if ($showempty && !is_numeric($showempty)) {
			$tmptitle = $showempty;
		} else {
			$tmptitle = $langs->transnoentitiesnoconv("Category");
		}

		$filter = '';
		$filter .= '<div class="divsearchfield">';
		$filter .= img_picto($tmptitle, 'category', 'class="pictofixedwidth"');
		if ($multiselect) {
			$categoryArray = $this->select_all_categories($type, '', '', 64, 0, 2);
			if ($nocateg) {
				$categoryArray[-2] = "- ".$langs->trans('NotCategorized')." -";
			}
			$htmlName = "search_category_".$type."_list";
			$htmlName2 = "search_category_".$type."_operator";

			$filter .= Form::multiselectarray($htmlName, $categoryArray, $preSelected, 0, 0, $morecss, 0, 0, '', '', $tmptitle);
		} else {
			$htmlName = "search_".$type."_category";
			$htmlName2 = "";
			require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
			$formother = new FormOther($db);

			$filter .= $formother->select_categories($type, $preSelected[0], $htmlName, $nocateg, $tmptitle, $morecss);
		}
		if ($searchCategoryProductOperator >= 0) {
			$filter .= ' <input type="checkbox" class="valignmiddle" id="'.$htmlName2.'" name="'.$htmlName2.'" value="1"'.($searchCategoryProductOperator == 1 ? ' checked="checked"' : '').'/><label class="none valignmiddle" for="'.$htmlName2.'">'.$langs->trans('UseOrOperatorForCategories').'</label>';
		}
		$filter .= "</div>";

		return $filter;
	}

	/**
	 *    Prints a select form for products categories
	 *    @param    string	$selected          	Id category pre-selection
	 *    @param    string	$htmlname          	Name of HTML field
	 *    @param    int		$showempty         	Add an empty field
	 *    @return	integer|null
	 */
	public function selectProductCategory($selected = 0, $htmlname = 'product_category_id', $showempty = 0)
	{
		global $conf;

		$sql = "SELECT cp.fk_categorie as cat_index, cat.label";
		$sql .= " FROM ".MAIN_DB_PREFIX."categorie_product as cp";
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX."categorie as cat ON cat.rowid = cp.fk_categorie";
		$sql .= " GROUP BY cp.fk_categorie, cat.label";

		dol_syslog(get_class($this)."::selectProductCategory", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			print '<select class="flat" id="select_'.$htmlname.'" name="'.$htmlname.'">';
			if ($showempty) {
				print '<option value="0">&nbsp;</option>';
			}

			$i = 0;
			$num_rows = $this->db->num_rows($resql);
			while ($i < $num_rows) {
				$category = $this->db->fetch_object($resql);
				if ($selected && $selected == $category->cat_index) {
					print '<option value="'.$category->cat_index.'" selected>'.$category->label.'</option>';
				} else {
					print '<option value="'.$category->cat_index.'">'.$category->label.'</option>';
				}
				$i++;
			}
			print('</select>');

			return $num_rows;
		} else {
			dol_print_error($this->db);
		}
	}
}
