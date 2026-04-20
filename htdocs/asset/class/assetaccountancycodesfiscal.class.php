<?php
/* Copyright (C) 2021  	Open-Dsi  <support@open-dsi.fr>
 * Copyright (C) 2024	MDW			<mdeweerd@users.noreply.github.com>
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
 * \file        asset/class/assetaccountancycodesfiscal.class.php
 * \ingroup     asset
 * \brief       This file is a class file for AssetAccountancyCodesFiscal
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';


/**
 * Class for AssetAccountancyCodes
 */
class AssetAccountancyCodesFiscal extends CommonObject
{
	// TODO This class and table should not exists and should be properties of llx_asset_asset.

	/**
	 * @var string 	Name of table without prefix where object is stored. This is also the key used for extrafields management (so extrafields know the link to the parent table).
	 */
	public $table_element = 'asset_accountancy_codes_fiscal';

	/**
	 * @var string    Field with ID of parent key if this object has a parent
	 */
	public $fk_element = 'fk_asset';

	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @inheritdoc
	 * Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => 1, 'position' => 1, 'notnull' => 1, 'visible' => 0, 'noteditable' => 1, 'index' => 1, 'css' => 'left', 'comment' => 'Id'),
		//...
	);

	/**
	 * @var int ID
	 */
	public $rowid;
	// END MODULEBUILDER PROPERTIES

	/**
	 * @var int		ID parent asset
	 */
	public $fk_asset;


	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param	int		$id	Id object
	 * @param	string	$ref	Ref
	 * @return	int<-1,max>	Return integer <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null)
	{
		$result = $this->fetchCommon($id, $ref);

		return $result;
	}

	/**
	 * Delete object in database
	 *
	 * @param	User		$user		User that deletes
	 * @param	int<0,1> 	$notrigger	0=launch triggers, 1=disable triggers
	 * @return	int<-1,1>				Return integer <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = 0)
	{
		return $this->deleteCommon($user, $notrigger);
		//return $this->deleteCommon($user, $notrigger, 1);
	}
}
