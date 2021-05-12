<?php
/* Copyright (C) 2005-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
* This file is an example to follow to add your own email selector inside
* the Dolibarr email tool.
* Follow instructions given in README file to know what to change to build
* your own emailing list selector.
* Code that need to be changed in this file are marked by "CHANGE THIS" tag.
*/

/**
 *	\file       htdocs/core/modules/mailings/thirdparties_services_expired.modules.php
 *	\ingroup    mailing
 *	\brief      File of class to offer a selector of emailing targets with Rule 'services expired'.
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/mailings/modules_mailings.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';


/**
 *	Class to offer a selector of emailing targets with Rule 'services expired'.
 */
class mailing_thirdparties_services_expired extends MailingTargets
{
<<<<<<< HEAD
    var $name='DolibarrContractsLinesExpired';
	// This label is used if no translation is found for key XXX neither MailingModuleDescXXX where XXX=name is found
    var $desc='Third parties with expired contract\'s lines';
    var $require_admin=0;

    var $require_module=array('contrat');
    var $picto='company';
    var $db;
    var $arrayofproducts=array();
=======
    public $name='DolibarrContractsLinesExpired';
	// This label is used if no translation is found for key XXX neither MailingModuleDescXXX where XXX=name is found
    public $desc='Third parties with expired contract\'s lines';
    public $require_admin=0;

    public $require_module=array('contrat');
    public $picto='company';

    /**
     * @var DoliDB Database handler.
     */
    public $db;

    public $arrayofproducts=array();
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9


    /**
     *	Constructor
     *
     *  @param		DoliDB		$db      Database handler
     */
<<<<<<< HEAD
    function __construct($db)
=======
    public function __construct($db)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    {
    	global $conf;

        $this->db=$db;

        $this->arrayofproducts=array();

        // List of services
        $sql = "SELECT ref FROM ".MAIN_DB_PREFIX."product";
        $sql.= " WHERE entity IN (".getEntity('product').")";
        if (empty($conf->global->CONTRACT_SUPPORT_PRODUCTS)) $sql.= " AND fk_product_type = 1";	// By default, only services
        $sql.= " ORDER BY ref";
        $result=$this->db->query($sql);
        if ($result)
        {
            $num = $this->db->num_rows($result);
            dol_syslog("dolibarr_services_expired.modules.php:mailing_dolibarr_services_expired ".$num." services found");

            $i = 0;
            while ($i < $num)
            {
                $obj = $this->db->fetch_object($result);
                $i++;
                $this->arrayofproducts[$i]=$obj->ref;
            }
<<<<<<< HEAD

=======
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        }
        else
        {
            dol_print_error($this->db);
        }
    }


<<<<<<< HEAD
=======
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    /**
     *  This is the main function that returns the array of emails
     *
     *  @param	int		$mailing_id    	Id of mailing. No need to use it.
<<<<<<< HEAD
     *  @param  array	$filtersarray   If you used the formFilter function. Empty otherwise.
     *  @return int           			<0 if error, number of emails added if ok
     */
    function add_to_target($mailing_id,$filtersarray=array())
    {
        $target = array();

        // ----- Your code start here -----
=======
     *  @return int           			<0 if error, number of emails added if ok
     */
    public function add_to_target($mailing_id)
    {
        // phpcs:enable
        $key=GETPOST('filter', 'int');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

        $cibles = array();
        $j = 0;

        $product='';
<<<<<<< HEAD
        foreach($filtersarray as $key)
        {
            if ($key == '0') return "Error: You must choose a filter";
            $product=$this->arrayofproducts[$key];
        }

=======
        if ($key == '0')
        {
        	$this->error = "Error: You must choose a filter";
        	$this->errors[] = $this->error;
        	return $this->error;
        }

        $product=$this->arrayofproducts[$key];

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        $now=dol_now();

        // La requete doit retourner: id, email, name
        $sql = "SELECT s.rowid as id, s.email, s.nom as name, cd.rowid as cdid, cd.date_ouverture, cd.date_fin_validite, cd.fk_contrat";
        $sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."contrat as c";
        $sql.= ", ".MAIN_DB_PREFIX."contratdet as cd, ".MAIN_DB_PREFIX."product as p";
        $sql.= " WHERE s.entity IN (".getEntity('societe').")";
        $sql.= " AND s.email NOT IN (SELECT email FROM ".MAIN_DB_PREFIX."mailing_cibles WHERE fk_mailing=".$mailing_id.")";
        $sql.= " AND s.rowid = c.fk_soc AND cd.fk_contrat = c.rowid AND s.email != ''";
        $sql.= " AND cd.statut= 4 AND cd.fk_product=p.rowid AND p.ref = '".$product."'";
        $sql.= " AND cd.date_fin_validite < '".$this->db->idate($now)."'";
        $sql.= " ORDER BY s.email";

        // Stocke destinataires dans cibles
        $result=$this->db->query($sql);
        if ($result)
        {
            $num = $this->db->num_rows($result);
            $i = 0;

            dol_syslog(get_class($this)."::add_to_target ".$num." targets found");

            $old = '';
            while ($i < $num)
            {
                $obj = $this->db->fetch_object($result);
                if ($old <> $obj->email)
                {
                    $cibles[$j] = array(
					'email' => $obj->email,
					'lastname' => $obj->name,	// For thirdparties, lastname must be name
                    'firstname' => '',			// For thirdparties, firstname is ''
					'other' =>
<<<<<<< HEAD
                    ('DateStart='.dol_print_date($this->db->jdate($obj->date_ouverture),'day')).';'.
                    ('DateEnd='.dol_print_date($this->db->jdate($obj->date_fin_validite),'day')).';'.
=======
                    ('DateStart='.dol_print_date($this->db->jdate($obj->date_ouverture), 'day')).';'.
                    ('DateEnd='.dol_print_date($this->db->jdate($obj->date_fin_validite), 'day')).';'.
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
                    ('Contract='.$obj->fk_contrat).';'.
                    ('ContactLine='.$obj->cdid),
					'source_url' => $this->url($obj->id),
					'source_id' => $obj->id,
					'source_type' => 'thirdparty'
                    );
                    $old = $obj->email;
                    $j++;
                }

                $i++;
            }
        }
        else
        {
            dol_syslog($this->db->lasterror());
            $this->error=$this->db->lasterror();
            return -1;
        }

        // ----- Your code end here -----

        return parent::add_to_target($mailing_id, $cibles);
    }


    /**
     *	On the main mailing area, there is a box with statistics.
     *	If you want to add a line in this report you must provide an
     *	array of SQL request that returns two field:
     *	One called "label", One called "nb".
     *
     *	@return		array		Array with SQL requests
     */
<<<<<<< HEAD
    function getSqlArrayForStats()
=======
    public function getSqlArrayForStats()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    {

        //var $statssql=array();
        //$this->statssql[0]="SELECT field1 as label, count(distinct(email)) as nb FROM mytable WHERE email IS NOT NULL";

        return array();
    }


    /**
     *	Return here number of distinct emails returned by your selector.
     *	For example if this selector is used to extract 500 different
     *	emails from a text file, this function must return 500.
     *
     *	@param	string	$sql		SQL request to use to count
     *	@return	int					Number of recipients
     */
<<<<<<< HEAD
    function getNbOfRecipients($sql='')
=======
    public function getNbOfRecipients($sql = '')
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    {
        $now=dol_now();

        // Example: return parent::getNbOfRecipients("SELECT count(*) as nb from dolibarr_table");
        // Example: return 500;
        $sql = "SELECT count(*) as nb";
        $sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."contrat as c";
        $sql.= ", ".MAIN_DB_PREFIX."contratdet as cd, ".MAIN_DB_PREFIX."product as p";
        $sql.= " WHERE s.entity IN (".getEntity('societe').")";
        $sql.= " AND s.rowid = c.fk_soc AND cd.fk_contrat = c.rowid AND s.email != ''";
        $sql.= " AND cd.statut= 4 AND cd.fk_product=p.rowid";
<<<<<<< HEAD
        $sql.= " AND p.ref IN ('".join("','",$this->arrayofproducts)."')";
=======
        $sql.= " AND p.ref IN ('".join("','", $this->arrayofproducts)."')";
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        $sql.= " AND cd.date_fin_validite < '".$this->db->idate($now)."'";

        $a=parent::getNbOfRecipients($sql);

        return $a;
    }

    /**
     *  This is to add a form filter to provide variant of selector
     *	If used, the HTML select must be called "filter"
     *
     *  @return     string      A html select zone
     */
<<<<<<< HEAD
    function formFilter()
=======
    public function formFilter()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    {
        global $langs;

        $s=$langs->trans("ProductOrService");
        $s.='<select name="filter" class="flat">';
        if (count($this->arrayofproducts)) $s.='<option value="0">&nbsp;</option>';
        else $s.='<option value="0">'.$langs->trans("ContactsAllShort").'</option>';
        foreach($this->arrayofproducts as $key => $val)
        {
            $s.='<option value="'.$key.'">'.$val.'</option>';
        }
        $s.='</select>';
        return $s;
    }


    /**
     *  Can include an URL link on each record provided by selector	shown on target page.
     *
     *  @param	int		$id		ID
     *  @return string      	Url link
     */
<<<<<<< HEAD
    function url($id)
    {
        return '<a href="'.DOL_URL_ROOT.'/societe/card.php?socid='.$id.'">'.img_object('',"company").'</a>';
    }

}

=======
    public function url($id)
    {
        return '<a href="'.DOL_URL_ROOT.'/societe/card.php?socid='.$id.'">'.img_object('', "company").'</a>';
    }
}
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
