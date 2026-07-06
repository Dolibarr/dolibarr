<?php
/* Copyright (C) 2025 		Open-Dsi         <support@open-dsi.fr>
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
 *    \file        htdocs/core/class/fields/commonselectfield.class.php
 *    \ingroup    core
 *    \brief      File of class to common select field
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/fields/commonfield.class.php';


/**
 *    Class to common select field
 */
class CommonSelectField extends CommonField
{
	/**
	 * @var array<string,array<string,array{label:string,parent:string}>>	Options cached
	 */
	public static $options = array();

	/**
	 * Get list of options
	 *
	 * @param   FieldInfos    										$fieldInfos     Array of properties for field to show
	 * @param	string												$key			Key of field
	 * @param	bool												$addEmptyValue	Add also empty value if needed
	 * @param 	bool												$reload			Force reload options
	 * @return  array<string,array{label:string,parent:string}>
	 */
	public function getOptions($fieldInfos, $key, $addEmptyValue = false, $reload = false)
	{
		global $langs;

		if (!isset(self::$options[$key]) || $reload) {
			$options = array();
			if (!empty($fieldInfos->options) && is_array($fieldInfos->options)) {
				foreach ($fieldInfos->options as $optionKey => $optionLabel) {
					$optionKey = (string) $optionKey;
					$optionLabel = (string) $optionLabel;
					if ($optionKey == '') {
						continue;
					}

					// Manage dependency list
					$fieldValueParent = '';
					if (strpos($optionLabel, "|") !== false) {
						list($optionLabel, $valueParent) = explode('|', $optionLabel);
						$fieldValueParent = trim($fieldValueParent);
					}

					if (empty($optionLabel)) {
						$optionLabel = '(not defined)';
					} else {
						$optionLabel = $langs->trans($optionLabel);
					}

					$options[$optionKey] = array(
						'id' => $optionKey,
						'label' => $optionLabel,
						'parent' => $fieldValueParent,
					);
				}
			}
			if ($addEmptyValue && (!$fieldInfos->required || count($options) > 1)) {
				// For preserve the numeric key indexes
				$options = array(
					'' => array(
						'id' => '',
						'label' => '&nbsp;',
						'parent' => '',
					)
				) + $options;
			}

			self::$options[$key] = $options;
		}

		return self::$options[$key];
	}
}
