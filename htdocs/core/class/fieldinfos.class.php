<?php
/* Copyright (C) 2002-2003  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003  Jean-Louis Bergamo      <jlb@j1b.org>
 * Copyright (C) 2004       Sebastien Di Cintio     <sdicintio@ressource-toi.org>
 * Copyright (C) 2004       Benoit Mortier          <benoit.mortier@opensides.be>
 * Copyright (C) 2009-2012  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2009-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2013       Florian Henry           <forian.henry@open-concept.pro>
 * Copyright (C) 2015       Charles-Fr BENKE        <charles.fr@benke.fr>
 * Copyright (C) 2016       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2017       Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2018-2022  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2022 		Antonin MARCHAL         <antonin@letempledujeu.fr>
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
 *    \file       htdocs/core/class/fieldinfos.class.php
 *    \ingroup    core
 *    \brief      File of class to stock field infos
 */


/**
 *    Class to stock field infos
 */
class FieldInfos
{
	/**
	 * @var CommonObject|null	Object handler (by reference)
	 */
	public $object = null;

	/**
	 * @var string	Display mode ('create', 'edit', 'view', 'list')
	 */
	public $mode = '';

	/**
	 * @var int		Type origin (object or extra field)
	 */
	public $fieldType = self::FIELD_TYPE_OBJECT;

	/**
	 * @var string	Key name of the field
	 */
	public $key = '';

	/**
	 * @var string	Field origin type
	 * 				'integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter[:Sortfield]]]',
	 *    			'select' (list of values are in 'options'. for integer list of values are in 'arrayofkeyval'),
	 *    			'sellist:TableName:LabelFieldName[:KeyFieldName[:KeyFieldParent[:Filter[:CategoryIdType[:CategoryIdList[:SortField]]]]]]',
	 *    			'chkbxlst:...',
	 *    			'varchar(x)',
	 *    			'text', 'text:none', 'html',
	 *    			'double(24,8)', 'real', 'price', 'stock',
	 *    			'date', 'datetime', 'timestamp', 'duration',
	 *    			'boolean', 'checkbox', 'radio', 'array',
	 *    			'email', 'phone', 'url', 'password', 'ip'
	 *    			Note: Filter must be a Dolibarr Universal Filter syntax string. Example: "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.status:!=:0) or (t.nature:is:NULL)"
	 */
	public $originType = '';

	/**
	 * @var string	Field type (The type name. ex: int, varchar, sellist, boolean, ... ) Used as class name, each type have this class. Ex: IntField, ...
	 */
	public $type = '';

	/**
	 * @var string|null	Name of the field in the class
	 */
	public $nameInClass = null;

	/**
	 * @var string|null	Name of the field in the table
	 */
	public $nameInTable = null;

	/**
	 * @var string	Field label (the translation key)
	 */
	public $label = '';

	/**
	 * @var string	Language file to load
	 */
	public $langFile = '';

	/**
	 * @var string	Field picto (code of a picto to show before value in forms)
	 */
	public $picto = '';

	/**
	 * @var int	Field position
	 */
	public $position = 0;

	/**
	 * @var bool	Field required
	 */
	public $required = false;

	/**
	 * @var bool	Field visible
	 */
	public $visible = true;

	/**
	 * @var bool	Field visible in the header
	 */
	public $showOnHeader = false;

	/**
	 * @var bool	Field editable
	 */
	public $editable = true;

	/**
	 * @var bool|null	Field always editable
	 */
	public $alwaysEditable = null;

	/**
	 * @var string	Field default value (to be converted in function of the type of the field)
	 */
	public $defaultValue = '';

	/**
	 * @var int|null 	Field string min length
	 */
	public $minLength = null;

	/**
	 * @var int|null 	Field string max length
	 */
	public $maxLength = null;

	/**
	 * @var double|null 	Field numeric min value
	 */
	public $minValue = null;

	/**
	 * @var double|null 	Field numeric max value
	 */
	public $maxValue = null;

	/**
	 * @var string 	Field size (Example: 255, '24,8')
	 */
	public $size = '';

	/**
	 * @var array<string,string> 	Field options (for select, sellist, ...)
	 */
	public $options = array();

	/**
	 * @var array<int,mixed> 	List of value deemed as empty (null always deemed as empty)
	 */
	public $emptyValues = array();

	/**
	 * @var string 	Specific check for GETPOST() when get field value (for type 'text' and 'html')
	 */
	public $getPostCheck = '';

	/**
	 * @var string	CSS for the input field
	 */
	public $css = '';

	/**
	 * @var string	CSS for the td
	 */
	public $tdCss = '';

	/**
	 * @var string	CSS for the input field in view mode
	 */
	public $viewCss = '';

	/**
	 * @var string	CSS for the input field in list
	 */
	public $listCss = '';

	/**
	 * @var string	Input placeholder
	 */
	public $inputPlaceholder = '';

	/**
	 * @var string	Field help
	 */
	public $help = '';

	/**
	 * @var string	Field comment. Is not used. You can store here any text of your choice. It is not used by application.
	 */
	public $comment = '';

	/**
	 * @var string	Prompt for AI
	 */
	public $aiPrompt = '';

	/**
	 * @var bool	If value of the field must be visible into the label of the combobox that list record
	 */
	public $showOnComboBox = false;

	/**
	 * @var bool	If displayed in documents
	 */
	public $printable = false;

	/**
	 * @var bool	If field set to null on clone
	 */
	public $emptyOnClone = false;

	/**
	 * @var bool	If value must be unique
	 */
	public $unique = false;

	/**
	 * @var string	If value is computed. (eval the provided string)
	 */
	public $computed = '';

	/**
	 * @var bool	Field must be validated
	 */
	public $validateField = false;

	/**
	 * @var int	Is 1 or 2 to allow to add a picto to copy value into clipboard (1=picto after label, 2=picto after value)
	 */
	public $copyToClipboard = 0;

	/**
	 * @var bool	Disable the input
	 */
	public $inputDisabled = false;

	/**
	 * @var bool	Autofocus on the input
	 */
	public $inputAutofocus = false;

	/**
	 * @var bool	Multi input on text type
	 */
	public $multiInput = false;

	/**
	 * @var string	Field help in list
	 */
	public $listHelp = '';

	/**
	 * @var bool	Add total in list footer
	 */
	public $listTotalizable = false;

	/**
	 * @var bool	Field checked in the list
	 */
	public $listChecked = true;

	/**
	 * @var string|null	Alias table used for sql request (ex 't.')
	 */
	public $sqlAlias = null;

	/**
	 * @var string|null		Dependency value (used for filter list from ajax)
	 */
	public $optionsSqlDependencyValue = null;

	/**
	 * @var int|null		Current page when get sql result for options on 'sellist' and 'chkbxlst' field type
	 */
	public $optionsSqlPage = null;

	/**
	 * @var int|null		Current offset when get sql result for options on 'sellist' and 'chkbxlst' field type
	 */
	public $optionsSqlOffset = null;

	/**
	 * @var int|null		Current limit when get sql result for options on 'sellist' and 'chkbxlst' field type
	 */
	public $optionsSqlLimit = null;

	/**
	 * @var string|null		getNameUrl() parameters 'xxx:xxx:xxx:...'
	 */
	public $getNameUrlParams = null;

	/**
	 * @var array<string,mixed>		Other parameters
	 */
	public $otherParams = array();


	const FIELD_TYPE_OBJECT = 0;
	const FIELD_TYPE_EXTRA_FIELD = 1;
}
