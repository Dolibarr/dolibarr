<?php
/* Copyright (C) 2004-2017	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2018-2024  Frédéric France				<frederic.france@free.fr>
 * Copyright (C) ---Replace with your own copyright and developer email---
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    htdocs/modulebuilder/template/core/boxes/mymodulewidget1.php
 * \ingroup mymodule
 * \brief   Widget provided by MyModule
 *
 * Put detailed description here.
 */

include_once DOL_DOCUMENT_ROOT."/core/boxes/modules_boxes.php";


/**
 * Class to manage the box
 *
 * Warning: for the box to be detected correctly by dolibarr,
 * the filename should be the lowercase classname
 */
class mymodulewidget1 extends ModeleBoxes
{
	/**
	 * @var string Alphanumeric ID. Populated by the constructor.
	 */
	public $boxcode = "mymodulebox";

	/**
	 * @var string Box icon (in configuration page)
	 * Automatically calls the icon named with the corresponding "object_" prefix
	 */
	public $boximg = "mymodule@mymodule";

	/**
	 * @var string Box label (in configuration page)
	 */
	public $boxlabel = 'MyWidget';

	/**
	 * @var string Box language file if it needs a specific language file.
	 */
	public $lang = 'mymodule@mymodule';

	/**
	 * @var string[] Module dependencies
	 */
	public $depends = array('mymodule');

	/**
	 * @var string 	Widget type ('graph' means the widget is a graph widget)
	 */
	public $widgettype = 'graph';


	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 * @param string $param More parameters
	 */
	public function __construct(DoliDB $db, $param = '')
	{
		global $user;

		parent::__construct($db, $param);

		$this->param = $param;

		// Condition when module is enabled or not
		// $this->enabled = getDolGlobalInt('MAIN_FEATURES_LEVEL') > 0;
		// Condition when module is visible by user (test on permission)
		// $this->hidden = !$user->hasRight('mymodule', 'myobject', 'read');
	}

	/**
	 * Load data into info_box_contents array to show array later. Called by Dolibarr before displaying the box.
	 *
	 * @param	int<0,max>	$max	Maximum number of records to load
	 * @return	void
	 */
	public function loadBox($max = 5)
	{
		global $langs;

		// Use configuration value for max lines count
		$this->max = $max;

		//dol_include_once("/mymodule/class/mymodule.class.php");

		// Populate the head at runtime
		$text = $langs->trans("MyModuleBoxDescription", $max);
		$this->info_box_head = array(
			// Title text
			'text' => $text,
			// Add a link
			'sublink' => 'https://example.com',
			// Sublink icon placed after the text
			'subpicto' => 'object_mymodule@mymodule',
			// Sublink icon HTML alt text
			'subtext' => '',
			// Sublink HTML target
			'target' => '',
			// HTML class attached to the picto and link
			'subclass' => 'center',
			// Limit and truncate with "…" the displayed text length, 0 = disabled
			'limit' => 0,
			// Adds translated " (Graph)" to a hidden form value's input (?)
			'graph' => 0
		);

		// Populate the contents at runtime
		$this->info_box_contents = array(
			0 => array( // First line
				0 => array( // First Column
					//  HTML properties of the TR element. Only available on the first column.
					'tr' => 'class="left"',
					// HTML properties of the TD element
					'td' => '',

					// Main text for content of cell
					'text' => 'First cell of first line',
					// Link on 'text' and 'logo' elements
					'url' => 'https://example.com',
					// Link's target HTML property
					'target' => '_blank',
					// Fist line logo (deprecated. Include instead logo html code into text or text2, and set asis property to true to avoid HTML cleaning)
					//'logo' => 'monmodule@monmodule',
					// Unformatted text, added after text. Useful to add/load javascript code
					'textnoformat' => '',

					// Main text for content of cell (other method)
					//'text2' => '<p><strong>Another text</strong></p>',

					// Truncates 'text' element to the specified character length, 0 = disabled
					'maxlength' => 0,
					// Prevents HTML cleaning (and truncation)
					'asis' => 0,
					// Same for 'text2'
					'asis2' => 0
				),
				1 => array( // Another column
					// No TR for n≠0
					'td' => '',
					'text' => 'Second cell',
				)
			),
			1 => array( // Another line
				0 => array( // TR
					'tr' => 'class="left"',
					'text' => 'Another line'
				),
				1 => array( // TR
					'tr' => 'class="left"',
					'text' => ''
				)
			),
			2 => array( // Another line
				0 => array( // TR
					'tr' => 'class="left"',
					'text' => ''
				),
				1 => array( // TR
					'tr' => 'class="left"',
					'text' => ''
				)
			),
		);
	}






	/**
	 *	Method to show box.  Called when the box needs to be displayed.
	 *
	 *	@param	?array<array{text?:string,sublink?:string,subtext?:string,subpicto?:?string,picto?:string,nbcol?:int,limit?:int,subclass?:string,graph?:int<0,1>,target?:string}>   $head       Array with properties of box title
	 *	@param	?array<array{tr?:string,td?:string,target?:string,text?:string,text2?:string,textnoformat?:string,tooltip?:string,logo?:string,url?:string,maxlength?:int,asis?:int<0,1>}>   $contents   Array with properties of box lines
	 *	@param	int<0,1>	$nooutput	No print, only return string
	 *	@return	string
	 */
	public function showBox($head = null, $contents = null, $nooutput = 0)
	{
		// You may make your own code here…
		// … or use the parent's class function using the provided head and contents templates
		return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
	}
}
