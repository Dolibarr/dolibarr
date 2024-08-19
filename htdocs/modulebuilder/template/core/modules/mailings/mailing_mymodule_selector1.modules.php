<?php
/* Copyright (C) 2005-2012	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) ---Replace with your own copyright and developer email---
 *
 * This file is an example to follow to add your own email selector inside
 * the Dolibarr email tool.
 * Follow instructions given in README file to know what to change to build
 * your own emailing list selector.
 * Code that need to be changed in this file are marked by "CHANGE THIS" tag.
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/mailings/modules_mailings.php';
dol_include_once("/mymodule/class/myobject.class.php");


/**
 * mailing_mailinglist_mymodule
 */
class mailing_mailing_mymodule_selector1 extends MailingTargets
{
	// CHANGE THIS: Put here a name not already used
	public $name = 'mailing_mymodule_selector1';
	// CHANGE THIS: Put here a description of your selector module
	public $desc = 'Emailing target selector1';

	// CHANGE THIS: Set to 1 if selector is available for admin users only
	/**
	 * @var int<0,1>
	 */
	public $require_admin = 0;

	public $enabled = 'isModEnabled("mymodule")';

	/**
	 * @var string[] The modules on which this selector depends (can be "none" to not show the module.
	 */
	public $require_module = array();

	/**
	 * @var string 	String with the name of icon for myobject. Can be an image filename like 'object_myobject.png' of a font awesome code 'fa-...'.
	 */
	public $picto = 'generic';

	/**
	 * @var DoliDB Database handler.
	 */
	public $db;


	/**
	 *	Constructor
	 *
	 *	@param	DoliDB	$db		Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
		//$this->enabled = ...
	}


	/**
	 *	Display the filter form that appears in the mailing recipient selection page
	 *
	 *	@return	string		Return select zone
	 */
	public function formFilter()
	{
		global $langs;
		$langs->load("members");

		$arraystatus = array(1 => 'Option 1', 2 => 'Option 2');

		$s = '';
		$s .= $langs->trans("Status").': ';
		$s .= '<select name="filter" class="flat">';
		$s .= '<option value="none">&nbsp;</option>';
		foreach ($arraystatus as $status) {
			$s .= '<option value="'.$status.'">'.$status.'</option>';
		}
		$s .= '</select>';
		$s .= '<br>';

		return $s;
	}


	/**
	 *	Return URL link to file of the source of the recipient of the mailing
	 *
	 *	@param	int		$id		ID
	 *	@return	string			Url link
	 */
	public function url($id)
	{
		return '<a href="'.dol_buildpath('/mymodule/myobject_card.php', 1).'?id='.$id.'">'.img_object('', "generic").'</a>';
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	This is the main function that returns the array of emails
	 *
	 *	@param	int			$mailing_id     Id of emailing
	 *	@return	int<-1,max>					Return integer <0 if error, number of emails added if ok
	 */
	public function add_to_target($mailing_id)
	{
		// phpcs:enable
		$target = array();
		$j = 0;

		$sql = "SELECT rowid as id, firstname, lastname, email";
		$sql .= " FROM ".MAIN_DB_PREFIX."myobject";
		$sql .= " WHERE email IS NOT NULL AND email <> ''";
		if (GETPOSTISSET('filter') && GETPOST('filter', 'alphanohtml') != 'none') {
			$sql .= " AND status = '".$this->db->escape(GETPOST('filter', 'alphanohtml'))."'";
		}
		$sql .= " ORDER BY email";

		// Store recipients in target
		$result = $this->db->query($sql);
		if ($result) {
			$num = $this->db->num_rows($result);
			$i = 0;

			dol_syslog(__METHOD__.":add_to_target ".$num." targets found");

			$old = '';
			while ($i < $num) {
				$obj = $this->db->fetch_object($result);
				if ($old != $obj->email) {
					$target[$j] = array(
						'email' => $obj->email,
						'id' => $obj->id,
						'firstname' => $obj->firstname,
						'lastname' => $obj->lastname,
						//'other' => $obj->label,
						'source_url' => $this->url($obj->id),
						'source_id' => $obj->id,
						'source_type' => 'myobject@mymodule'
					);
					$old = $obj->email;
					$j++;
				}

				$i++;
			}
		} else {
			dol_syslog($this->db->error());
			$this->error = $this->db->error();
			return -1;
		}

		// You must fill the $target array with record like this
		// $target[0]=array('email'=>'email_0','name'=>'name_0','firstname'=>'firstname_0');
		// ...
		// $target[n]=array('email'=>'email_n','name'=>'name_n','firstname'=>'firstname_n');

		// Example: $target[0]=array('email'=>'myemail@mydomain.com','name'=>'Doe','firstname'=>'John');

		// ----- Your code ends here -----

		return parent::addTargetsToDatabase($mailing_id, $target);
	}


	/**
	 *  On the main mailing area, there is a box with statistics.
	 *  If you want to add a line in this report you must provide an
	 *  array of SQL request that returns two field:
	 *  One called "label", One called "nb".
	 *
	 *	@return		string[]		Array with SQL requests
	 */
	public function getSqlArrayForStats()
	{
		// CHANGE THIS: Optional

		//var $statssql=array();
		//$this->statssql[0]="SELECT field1 as label, count(distinct(email)) as nb FROM mytable WHERE email IS NOT NULL";

		return array();
	}


	/**
	 *	Return the number of distinct emails returned by your selector.
	 *	For example if this selector is used to extract 500 different
	 *	emails from a text file, this function must return 500.
	 *
	 *	@param	string			$sql	Not used here
	 *	@return	int<-1,max>				Nb of recipients or -1 if KO
	 */
	public function getNbOfRecipients($sql = '')
	{
		$sql = "SELECT COUNT(DISTINCT(email)) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."myobject as p";
		$sql .= " WHERE email IS NOT NULL AND email <> ''";

		$a = parent::getNbOfRecipients($sql);

		if ($a < 0) {
			return -1;
		}
		return $a;
	}
}
