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
 *	\file       htdocs/core/modules/mailings/thirdparties_clients.modules.php
 *	\ingroup    mailing
 *	\brief      File of class to offer a selector of emailing targets with Rule 'services expired'.
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/mailings/modules_mailings.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';


/**
 *	Class to offer a selector of emailing targets with Rule 'services expired'.
 */
class mailing_thirdparties_clients extends MailingTargets
{
    var $name='DolibarrClients';
	// This label is used if no translation is found for key XXX neither MailingModuleDescXXX where XXX=name is found
    var $desc='Third parties by client';
    var $require_admin=0;

    var $require_module=array('contrat');
    var $picto='company';
    var $db;
    var $arrayofclient=array();


    /**
     *	Constructor
     *
     *  @param		DoliDB		$db      Database handler
     */
    function __construct($db)
    {
    	global $conf, $langs;

        $this->db=$db;
        $langs->load("companies");

        $this->arrayofclient=array();

        $this->arrayofclient= array(2 => $langs->trans('Prospect'), 1 => $langs->trans('Customer'), 0 => $langs->trans('NorProspectNorCustomer'));
    }


    /**
     *  This is the main function that returns the array of emails
     *
     *  @param	int		$mailing_id    	Id of mailing. No need to use it.
     *  @param  array	$filtersarray   If you used the formFilter function. Empty otherwise.
     *  @return int           			<0 if error, number of emails added if ok
     */
    function add_to_target($mailing_id,$filtersarray=array())
    {
        $target = array();

        // ----- Your code start here -----

        $cibles = array();
        $j = 0;

        foreach($filtersarray as $key)
        {
            $clientstatus=$key;
        }

        $now=dol_now();

        // La requete doit retourner: id, email, name
        $sql = "SELECT s.rowid as id, s.email, s.nom as name";
        $sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
        $sql.= " WHERE s.entity IN (".getEntity('societe', 1).")";
        $sql.= " AND s.email NOT IN (SELECT email FROM ".MAIN_DB_PREFIX."mailing_cibles WHERE fk_mailing=".$mailing_id.")";
        if ($clientstatus != -1)
        {
            $sql.= " AND s.client= '".$clientstatus."'";
        }
        if (isset($_POST["filter_status"]) && $_POST["filter_status"] == '1') $sql.= " AND s.status=1"; 
        if (isset($_POST["filter_status"]) && $_POST["filter_status"] == '0') $sql.= " AND s.status=0"; 
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
    function getSqlArrayForStats()
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
    function getNbOfRecipients($sql='')
    {
        $now=dol_now();

        // Example: return parent::getNbOfRecipients("SELECT count(*) as nb from dolibarr_table");
        // Example: return 500;
        $sql = "SELECT count(*) as nb";
        $sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
        $sql.= " WHERE s.entity IN (".getEntity('societe', 1).")";
        $sql.= " AND s.email != ''";

        $a=parent::getNbOfRecipients($sql);

        return $a;
    }

    /**
     *  This is to add a form filter to provide variant of selector
     *	If used, the HTML select must be called "filter"
     *
     *  @return     string      A html select zone
     */
    function formFilter()
    {
        global $langs;

        $s='';
        $s.='<select name="filter" class="flat">';
        if (count($this->arrayofclient)) $s.='<option value="-1">&nbsp;</option>';
        else $s.='<option value="-1">'.$langs->trans("AllThirdparties").'</option>';
        foreach($this->arrayofclient as $key => $val)
        {
            $s.='<option value="'.$key.'">'.$val.'</option>';
        }
        $s.='</select> ';
        $s.=$langs->trans("Status");
        $s.=': <select name="filter_status" class="flat">';
        $s.='<option value="1">'.$langs->trans("Enabled").'</option>';
        $s.='<option value="0">'.$langs->trans("Disabled").'</option>';
        $s.='<option value="-1">Alle</option>';
        $s.='</select>';
        return $s;
    }


    /**
     *  Can include an URL link on each record provided by selector	shown on target page.
     *
     *  @param	int		$id		ID
     *  @return string      	Url link
     */
    function url($id)
    {
        return '<a href="'.DOL_URL_ROOT.'/societe/card.php?socid='.$id.'">'.img_object('',"company").'</a>';
    }

}

