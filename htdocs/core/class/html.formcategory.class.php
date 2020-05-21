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
	 * @param string $type			The categorie type (e.g Categorie::TYPE_WAREHOUSE)
	 * @param Array $preSelected	A list with the elements that should pre-selected
	 * @return string				A HTML filter box (Note: selected results can get with GETPOST("search_category_".$type."_list"))
	 */
	public function getFilterBox($type, $preSelected)
	{
		// phpcs:enable
		global $langs;

		if (empty($preSelected) || !is_array($preSelected))
		{
			$preSelected = array();
		}

		$htmlName = "search_category_".$type."_list";

		$categoryArray = $this->select_all_categories($type, "", "", 64, 0, 1);
		$categoryArray[-2] = "- ".$langs->trans('NotCategorized')." -";

		$filter = '';
		$filter .= '<div class="divsearchfield">';
		$filter .= $langs->trans('Categories').": ";
		$filter .= Form::multiselectarray($htmlName, $categoryArray, $preSelected, 0, 0, "minwidth300");
		$filter .= "</div>";

		return $filter;
	}
}
