<?php
/* Copyright (C) 2005-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This file is an example to follow to add your own email selector inside
 * the Dolibarr email tool.
 * Follow instructions given in README file to know what to change to build
 * your own emailing list selector.
 * Code that need to be changed in this file are marked by "CHANGE THIS" tag.
 */

/**
 *    	\file       htdocs/core/modules/mailings/example.modules.php
 *		\ingroup    mailing
 *		\brief      Example file to provide a list of recipients for mailing module
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/mailings/modules_mailings.php';


// CHANGE THIS: Class name must be called mailing_xxx with xxx=name of your selector

/**
	    \class      mailing_example
		\brief      Class to manage a list of personalised recipients for mailing feature
*/
class mailing_example extends MailingTargets
{
    // CHANGE THIS: Put here a name not already used
    public $name='example';
    // CHANGE THIS: Put here a description of your selector module.
    // This label is used if no translation is found for key MailingModuleDescXXX where XXX=name is found
    public $desc='Put here a description';
    // CHANGE THIS: Set to 1 if selector is available for admin users only
    public $require_admin=0;
    // CHANGE THIS: Add a tooltip language key to add a tooltip help icon after the email target selector
    public $tooltip='MyTooltipLangKey';

    public $require_module=array();

    /**
     * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
     */
    public $picto='';

    /**
     * @var DoliDB Database handler.
     */
    public $db;


    // CHANGE THIS: Constructor name must be called mailing_xxx with xxx=name of your selector
	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
    public function __construct($db)
    {
        $this->db=$db;
    }


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *  This is the main function that returns the array of emails
     *
     *  @param	int		$mailing_id    	Id of mailing. No need to use it.
     *  @return int           			<0 if error, number of emails added if ok
     */
    public function add_to_target($mailing_id)
    {
        // phpcs:enable
        $target = array();

	    // CHANGE THIS
	    // ----- Your code start here -----

	    // You must fill the $target array with record like this
	    // $target[0]=array('email'=>'email_0','name'=>'name_0','firstname'=>'firstname_0', 'other'=>'other_0');
		// ...
	    // $target[n]=array('email'=>'email_n','name'=>'name_n','firstname'=>'firstname_n', 'other'=>'other_n');

		// Example: $target[0]=array('email'=>'myemail@example.com', 'name'=>'Doe', 'firstname'=>'John', 'other'=>'Other information');

		// ----- Your code end here -----

        return parent::addTargetsToDatabase($mailing_id, $target);
    }


    /**
	 *	On the main mailing area, there is a box with statistics.
	 *	If you want to add a line in this report you must provide an
	 *	array of SQL request that returns two field:
	 *	One called "label", One called "nb".
	 *
	 *	@return		array		Array with SQL requests
	 */
    public function getSqlArrayForStats()
	{
	    // CHANGE THIS: Optionnal

		//var $statssql=array();
        //$this->statssql[0]="SELECT field1 as label, count(distinct(email)) as nb FROM mytable WHERE email IS NOT NULL";
		return array();
	}


    /**
     *  Return here number of distinct emails returned by your selector.
     *  For example if this selector is used to extract 500 different
     *  emails from a text file, this function must return 500.
     *
     *  @param		string		$sql		Requete sql de comptage
     *  @return		int|string				Number of recipient or '?'
     */
    public function getNbOfRecipients($sql = '')
    {
        // CHANGE THIS: Optionnal

        // Example: return parent::getNbOfRecipients("SELECT count(*) as nb from dolibarr_table");
        // Example: return 500;
        return '?';
    }

    /**
     *  This is to add a form filter to provide variant of selector
     *  If used, the HTML select must be called "filter"
     *
     *  @return     string      A html select zone
     */
    public function formFilter()
    {
        // CHANGE THIS: Optionnal

        $s='';
        return $s;
    }


    /**
     *  Can include an URL link on each record provided by selector
     *	shown on target page.
     *
     *  @param	int		$id		ID
     *  @return string      	Url link
     */
    public function url($id)
    {
        // CHANGE THIS: Optionnal

        return '';
    }
}
