<?php
/* Copyright (C) 2026		Pierre Ardoin				<developpeur@lesmetiersdubatiment.fr>
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
 * \file		htdocs/webportal/class/webportalcontract.class.php
 * \ingroup	webportal
 * \brief		This file is a CRUD class file for WebPortalContract.
 */

require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';

/**
 * Class for WebPortalContract
 */
class WebPortalContract extends Contrat
{
	/**
	 * @var string ID of module.
	 */
	public $module = 'webportal';

	/**
	 * Status list (short label)
	 */
	const ARRAY_STATUS_LABEL = array(
		0 => 'StatusDraftShort',
		1 => 'Validated',
		2 => 'Closed',
	);

	/**
	 *  Definition of fields for list rendering.
	 *
	 * @var array<string,array{type:string,label:string,enabled:int<0,2>|string,position:int,visible:int<-6,6>|string,langfile?:string,notnull?:int<-1,1>,noteditable?:int<0,1>,alwayseditable?:int<0,1>|string,default?:string|int,index?:int<0,1>,foreignkey?:string,searchall?:int<0,1>,isameasure?:int<0,1>,css?:string,cssview?:string,csslist?:string,help?:string,helplist?:string,showoncombobox?:int<0,4>|string,disabled?:int<0,1>|string,arrayofkeyval?:array<int|string,string>,autofocusoncreate?:int<0,1>,comment?:string,copytoclipboard?:int<1,2>,validate?:int<0,1>|string,showonheader?:int<0,1>,searchmulti?:int<0,1>,picto?:string,required?:int<0,1>,placeholder?:string}>  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => 1, 'visible' => 0, 'notnull' => 1, 'position' => 10,),
		'entity' => array('type' => 'integer', 'label' => 'Entity', 'default' => '1', 'enabled' => 1, 'visible' => -2, 'notnull' => 1, 'position' => 20, 'index' => 1,),
		'ref' => array('type' => 'varchar(30)', 'label' => 'Ref', 'enabled' => 1, 'visible' => 2, 'notnull' => 1, 'showoncombobox' => 1, 'position' => 25,),
		'date_contrat' => array('type' => 'date', 'label' => 'DateContract', 'enabled' => 1, 'visible' => 2, 'position' => 60,),
		'denormalized_lower_planned_end_date' => array('type' => 'date', 'label' => 'DateEnd', 'enabled' => 1, 'visible' => 2, 'position' => 70,),
		'total_ht' => array('type' => 'price', 'label' => 'TotalHT', 'enabled' => 1, 'visible' => 2, 'position' => 125, 'isameasure' => 1,),
		'total_tva' => array('type' => 'price', 'label' => 'VAT', 'enabled' => 1, 'visible' => 2, 'position' => 130, 'isameasure' => 1,),
		'total_ttc' => array('type' => 'price', 'label' => 'TotalTTC', 'enabled' => 1, 'visible' => 2, 'position' => 145, 'isameasure' => 1,),
		'statut' => array('type' => 'smallint(6)', 'label' => 'Status', 'enabled' => 1, 'visible' => 2, 'position' => 500, 'arrayofkeyval' => self::ARRAY_STATUS_LABEL,),
	);

	/**
	 * Constructor
	 *
	 * @param	DoliDB	$db		Database handler
	 */
	public function __construct(DoliDB $db)
	{
		parent::__construct($db);
	}
}
