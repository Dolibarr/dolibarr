<?php
/* Copyright (C) 2005-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
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
class mailing_mailinglist_mymodule_myobject extends MailingTargets
{
	// CHANGE THIS: Put here a name not already used
	var $name='mailinglist_mymodule_myobject';
	// CHANGE THIS: Put here a description of your selector module
	var $desc='My object emailing target selector';
	// CHANGE THIS: Set to 1 if selector is available for admin users only
	var $require_admin=0;

	var $enabled=0;
	var $require_module=array();
	var $picto='mymodule@mymodule';

	/**
     * @var DoliDB Database handler.
     */
    public $db;


	/**
     *	Constructor
     *
     * 	@param	DoliDB	$db		Database handler
     */
	function __construct($db)
	{
		global $conf;

		$this->db=$db;
		if (is_array($conf->modules))
		{
			$this->enabled=in_array('mymodule',$conf->modules)?1:0;
		}
	}


    /**
     *   Affiche formulaire de filtre qui apparait dans page de selection des destinataires de mailings
     *
     *   @return     string      Retourne zone select
     */
    function formFilter()
    {
        global $langs;
        $langs->load("members");

        $form=new Form($this->db);

        $arraystatus=array(1=>'Option 1', 2=>'Option 2');

        $s='';
        $s.=$langs->trans("Status").': ';
        $s.='<select name="filter" class="flat">';
        $s.='<option value="none">&nbsp;</option>';
        foreach($arraystatus as $status)
        {
	        $s.='<option value="'.$status.'">'.$status.'</option>';
        }
        $s.='</select>';
        $s.='<br>';

        return $s;
    }


    /**
	 *  Renvoie url lien vers fiche de la source du destinataire du mailing
	 *
	 *  @param		int			$id		ID
	 *  @return     string      		Url lien
	 */
	function url($id)
	{
		return '<a href="'.dol_buildpath('/mymodule/myobject_card.php',1).'?id='.$id.'">'.img_object('',"generic").'</a>';
	}


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *  This is the main function that returns the array of emails
	 *
	 *  @param	int		$mailing_id    	Id of emailing
	 *  @param	array	$filtersarray   Requete sql de selection des destinataires
	 *  @return int           			<0 if error, number of emails added if ok
	 */
	function add_to_target($mailing_id,$filtersarray=array())
	{
        // phpcs:enable
		$target = array();
		$cibles = array();
		$j = 0;


		$sql = " select rowid as id, email, firstname, lastname, plan, partner";
		$sql.= " from ".MAIN_DB_PREFIX."myobject";
		$sql.= " where email IS NOT NULL AND email != ''";
		if (! empty($_POST['filter']) && $_POST['filter'] != 'none') $sql.= " AND status = '".$this->db->escape($_POST['filter'])."'";
		$sql.= " ORDER BY email";

		// Stocke destinataires dans cibles
		$result=$this->db->query($sql);
		if ($result)
		{
			$num = $this->db->num_rows($result);
			$i = 0;

			dol_syslog("mailinglist_mymodule_myobject.modules.php: mailing ".$num." targets found");

			$old = '';
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($result);
				if ($old <> $obj->email)
				{
					$cibles[$j] = array(
						'email' => $obj->email,
						'name' => $obj->lastname,
						'id' => $obj->id,
						'firstname' => $obj->firstname,
						'other' => $obj->plan.';'.$obj->partner,
						'source_url' => $this->url($obj->id),
						'source_id' => $obj->id,
						'source_type' => 'dolicloud'
					);
					$old = $obj->email;
					$j++;
				}

				$i++;
			}
		}
		else
		{
			dol_syslog($this->db->error());
			$this->error=$this->db->error();
			return -1;
		}

		// You must fill the $target array with record like this
		// $target[0]=array('email'=>'email_0','name'=>'name_0','firstname'=>'firstname_0');
		// ...
		// $target[n]=array('email'=>'email_n','name'=>'name_n','firstname'=>'firstname_n');

		// Example: $target[0]=array('email'=>'myemail@mydomain.com','name'=>'Doe','firstname'=>'John');

		// ----- Your code end here -----

		return parent::add_to_target($mailing_id, $cibles);
	}


	/**
	 *	On the main mailing area, there is a box with statistics.
	 *	If you want to add a line in this report you must provide an
	 *	array of SQL request that returns two field:
	 *	One called "label", One called "nb".
	 *
	 *	@return		array
	 */
	function getSqlArrayForStats()
	{
		// CHANGE THIS: Optionnal

		//var $statssql=array();
		//$this->statssql[0]="SELECT field1 as label, count(distinct(email)) as nb FROM mytable WHERE email IS NOT NULL";

		return array();
	}


	/**
	 *	Return here number of distinct emails returned by your selector.
	 *	For example if this selector is used to extract 500 different
	 *	emails from a text file, this function must return 500.
	 *
	 *	@param	string	$filter		Filter
	 *	@param	string	$option		Options
	 *	@return	int					Nb of recipients or -1 if KO
	 */
	function getNbOfRecipients($filter=1,$option='')
	{
		$a=parent::getNbOfRecipients("select count(distinct(email)) as nb from ".MAIN_DB_PREFIX."myobject as p where email IS NOT NULL AND email != ''");

		if ($a < 0) return -1;
		return $a;
	}
}
