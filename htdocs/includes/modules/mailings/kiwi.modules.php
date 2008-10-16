<?php
/* Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This file is an example to follow to add your own email selector inside
 * the Dolibarr email tool.
 * Follow instructions given in README file to know what to change to build
 * your own emailing list selector.
 * Code that need to be changed in this file are marked by "CHANGE THIS" tag.
 */

/**
       	\file       htdocs/includes/modules/mailings/kiwi.modules.php
		\ingroup    mailing
		\brief      Example file to provide a list of recipients for mailing module
		\version    $Revision$
*/

include_once DOL_DOCUMENT_ROOT.'/includes/modules/mailings/modules_mailings.php';


// CHANGE THIS: Class name must be called mailing_xxx with xxx=name of your selector

/**
	    \class      mailing_kiwi
		\brief      Class to manage a list of personalised recipients for mailing feature
*/
class mailing_kiwi extends MailingTargets
{
    // CHANGE THIS: Put here a name not already used
    var $name='ContactsCategories';
    // CHANGE THIS: Put here a description of your selector module.
    // This label is used if no translation for MailingModuleDescXXX where XXX=name is found
    var $desc="Contacts des tiers (par categorie)";
	// CHANGE THIS: Set to 1 if selector is available for admin users only
    var $require_admin=0;

    var $require_module=array("categorie");
    var $picto='contact';
    var $db;
    

    // CHANGE THIS: Constructor name must be called mailing_xxx with xxx=name of your selector
    function mailing_categories($DB)
    {
        $this->db=$DB;
    }


    /**
     *    \brief      This is the main function that returns the array of emails
     *    \param      mailing_id    Id of mailing. No need to use it.
     *    \param      filterarray   If you used the formFilter function. Empty otherwise.
     *    \return     int           <0 if error, number of emails added if ok
     */
    function add_to_target($mailing_id,$filtersarray=array())
    {
        $cibles = array();

	    // CHANGE THIS
	    // Select the contacts from category
		$sql = "SELECT s.rowid as id, s.email as email, s.nom as name, llx_categorie.label, null as fk_contact, null as firstname
		FROM llx_societe as s 
		LEFT JOIN llx_categorie_societe ON llx_categorie_societe.fk_societe=s.rowid
		LEFT JOIN llx_categorie ON llx_categorie.rowid = llx_categorie_societe.fk_categorie
		WHERE llx_categorie.rowid='".$_POST['filter']."'";

	        // Stocke destinataires dans cibles
        $result=$this->db->query($sql);
        if ($result)
        {
            $num = $this->db->num_rows($result);
            $i = 0;
            $j = 0;

            dolibarr_syslog(get_class($this)."::add_to_target mailing ".$num." targets found");

            $old = '';
            while ($i < $num)
            {
                $obj = $this->db->fetch_object($result);
                if ($old <> $obj->email)
                {
                    $cibles[$j] = array(
                    			'email' => $obj->email,
                    			'fk_contact' => $obj->fk_contact,
                    			'name' => $obj->name,
                    			'firstname' => $obj->firstname,
                    			'url' => $this->url($obj->id)
                    			);
                    $old = $obj->email;
                    $j++;
                }

                $i++;
            }
        }
        else
        {
            dolibarr_syslog($this->db->error());
            $this->error=$this->db->error();
            return -1;
        }

		
        return parent::add_to_target($mailing_id, $cibles);
    }
    
    
    /**
	 *		\brief		On the main mailing area, there is a box with statistics.
	 *					If you want to add a line in this report you must provide an
	 *					array of SQL request that returns two field:
	 *					One called "label", One called "nb".
	 *		\return		array
	 */
	function getSqlArrayForStats()
	{
	    // CHANGE THIS: Optionnal
	    
		//var $statssql=array();
        //$this->statssql[0]="SELECT field1 as label, count(distinct(email)) as nb FROM mytable WHERE email IS NOT NULL";
		return array();
	}


    /*
     *		\brief		Return here number of distinct emails returned by your selector.
     *					For example if this selector is used to extract 500 different
     *					emails from a text file, this function must return 500.
     *		\return		int
     */
    function getNbOfRecipients()
    {
        return '?'; 
    }
    
    /**
     *      \brief      This is to add a form filter to provide variant of selector
     *					If used, the HTML select must be called "filter"
     *      \return     string      A html select zone
     */
    function formFilter()
    {
    	global $langs;
    	
        $s='';
        $s.='<select name="filter" class="flat">';
        
        # Show categories
        $sql = "SELECT rowid, label, type";
        $sql.= " FROM ".MAIN_DB_PREFIX."categorie";
        $sql.= " WHERE visible > 0 AND type > 0";
        $sql.= " ORDER BY label";

        $resql = $this->db->query($sql);
        if ($resql)
        {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num)
            {
                $obj = $this->db->fetch_object($resql);

                $type='';
                if ($obj->type == 1) $type=$langs->trans("Supplier");
                if ($obj->type == 2) $type=$langs->trans("Customer");
                $s.='<option value="'.$obj->rowid.'">'.$obj->label;
                if ($type) $s.=' ('.$type.')';
                $s.='</option>';
				$i++;
			}
		}

        $s.='</select>';
        return $s;

    }
    
    
    /**
     *      \brief      Can include an URL link on each record provided by selector
     *					shown on target page.
     *      \return     string      Url link
     */
    function url($id)
    {
	    // CHANGE THIS: Optionnal

        return '';
    }

}

?>
