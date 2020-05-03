<?php
/* Copyright (C) 2005       Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
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
 * or see https://www.gnu.org/
 */

/**
 * \file       htdocs/core/modules/mailings/fraise.modules.php
 * \ingroup    mailing
 * \brief      File of class to generate target according to rule Fraise
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/mailings/modules_mailings.php';
include_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';


/**
 *    Class to generate target according to rule Fraise
 */
class mailing_fraise extends MailingTargets
{
    public $name = 'FundationMembers'; // Identifiant du module mailing
	// This label is used if no translation is found for key XXX neither MailingModuleDescXXX where XXX=name is found
    public $desc = 'Foundation members with emails';
    // Set to 1 if selector is available for admin users only
    public $require_admin = 0;

    public $require_module = array('adherent');

    /**
     * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
     */
    public $picto = 'user';

    /**
     * @var DoliDB Database handler.
     */
    public $db;

    /**
     *    Constructor
     *
     *  @param        DoliDB        $db      Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }


    /**
     *    On the main mailing area, there is a box with statistics.
     *    If you want to add a line in this report you must provide an
     *    array of SQL request that returns two field:
     *    One called "label", One called "nb".
     *
     *    @return        string[]        Array with SQL requests
     */
    public function getSqlArrayForStats()
    {
        global $langs;

        $langs->load("members");

        // Array for requests for statistics board
        $statssql = array();

        $statssql[0] = "SELECT '".$this->db->escape($langs->trans("FundationMembers"))."' as label, count(*) as nb";
        $statssql[0] .= " FROM ".MAIN_DB_PREFIX."adherent where statut = 1 and entity IN (".getEntity('member').")";

        return $statssql;
    }


    /**
     *    Return here number of distinct emails returned by your selector.
     *    For example if this selector is used to extract 500 different
     *    emails from a text file, this function must return 500.
     *
     *  @param    string    $sql        Requete sql de comptage
     *    @return        int            Nb of recipients
     */
    public function getNbOfRecipients($sql = '')
    {
        $sql  = "SELECT count(distinct(a.email)) as nb";
        $sql .= " FROM ".MAIN_DB_PREFIX."adherent as a";
        $sql .= " WHERE (a.email IS NOT NULL AND a.email != '') AND a.entity IN (".getEntity('member').")";

        // La requete doit retourner un champ "nb" pour etre comprise
        // par parent::getNbOfRecipients
        return parent::getNbOfRecipients($sql);
    }


    /**
     *   Affiche formulaire de filtre qui apparait dans page de selection des destinataires de mailings
     *
     *   @return     string      Retourne zone select
     */
    public function formFilter()
    {
        global $conf, $langs;

        // Load translation files required by the page
        $langs->loadLangs(array("members", "companies", "categories"));

        $form = new Form($this->db);

        $s = '';

        // Status
        $s .= $langs->trans("Status").': ';
        $s .= '<select name="filter" class="flat">';
        $s .= '<option value="none">&nbsp;</option>';
        $s .= '<option value="-1">'.$langs->trans("MemberStatusDraft").'</option>';
        $s .= '<option value="1a">'.$langs->trans("MemberStatusActiveShort").' ('.$langs->trans("MemberStatusPaidShort").')</option>';
        $s .= '<option value="1b">'.$langs->trans("MemberStatusActiveShort").' ('.$langs->trans("MemberStatusActiveLateShort").')</option>';
        $s .= '<option value="0">'.$langs->trans("MemberStatusResiliatedShort").'</option>';
        $s .= '</select> ';
        $s .= $langs->trans("Type").': ';
        $s .= '<select name="filter_type" class="flat">';
        $sql = "SELECT rowid, libelle as label, statut";
        $sql .= " FROM ".MAIN_DB_PREFIX."adherent_type";
        $sql .= " WHERE entity IN (".getEntity('member_type').")";
        $sql .= " ORDER BY rowid";
        $resql = $this->db->query($sql);
        if ($resql)
        {
            $num = $this->db->num_rows($resql);

            $s .= '<option value="0">&nbsp;</option>';
            if (!$num) $s .= '<option value="0" disabled="disabled">'.$langs->trans("NoCategoriesDefined").'</option>';

            $i = 0;
            while ($i < $num)
            {
                $obj = $this->db->fetch_object($resql);

                $s .= '<option value="'.$obj->rowid.'">'.dol_trunc($obj->label, 38, 'middle');
                $s .= '</option>';
                $i++;
            }
        }
        else
        {
            dol_print_error($this->db);
        }

        $s .= '</select>';

        $s .= ' ';

        $s .= $langs->trans("Category").': ';
        $s .= '<select name="filter_category" class="flat">';

        // Show categories
        $sql = "SELECT rowid, label, type, visible";
        $sql .= " FROM ".MAIN_DB_PREFIX."categorie";
        $sql .= " WHERE type = 3"; // We keep only categories for members
        // $sql.= " AND visible > 0";	// We ignore the property visible because member's categories does not use this property (only products categories use it).
        $sql .= " AND entity = ".$conf->entity;
        $sql .= " ORDER BY label";

        //print $sql;
        $resql = $this->db->query($sql);
        if ($resql)
        {
        	$num = $this->db->num_rows($resql);

        	$s .= '<option value="0">&nbsp;</option>';
        	if (!$num) $s .= '<option value="0" disabled>'.$langs->trans("NoCategoriesDefined").'</option>';

        	$i = 0;
        	while ($i < $num)
        	{
        		$obj = $this->db->fetch_object($resql);

        		$s .= '<option value="'.$obj->rowid.'">'.dol_trunc($obj->label, 38, 'middle');
        		$s .= '</option>';
        		$i++;
        	}
        }
        else
        {
        	dol_print_error($this->db);
        }

        $s .= '</select>';


        $s .= '<br>';
        $s .= $langs->trans("DateEndSubscription").': &nbsp;';
        $s .= $langs->trans("After").' > '.$form->selectDate(-1, 'subscriptionafter', 0, 0, 1, 'fraise', 1, 0, 0);
        $s .= ' &nbsp; ';
        $s .= $langs->trans("Before").' < '.$form->selectDate(-1, 'subscriptionbefore', 0, 0, 1, 'fraise', 1, 0, 0);

        return $s;
    }


    /**
     *  Renvoie url lien vers fiche de la source du destinataire du mailing
     *
     *  @param    int        $id        ID
     *  @return     string      Url lien
     */
    public function url($id)
    {
        return '<a href="'.DOL_URL_ROOT.'/adherents/card.php?rowid='.$id.'">'.img_object('', "user").'</a>';
    }


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *  Ajoute destinataires dans table des cibles
     *
     *  @param    int        $mailing_id        Id of emailing
     *  @return int                       < 0 si erreur, nb ajout si ok
     */
    public function add_to_target($mailing_id)
    {
        // phpcs:enable
    	global $langs, $_POST;

    	// Load translation files required by the page
        $langs->loadLangs(array("members", "companies"));

        $cibles = array();
        $now = dol_now();

        $dateendsubscriptionafter = dol_mktime($_POST['subscriptionafterhour'], $_POST['subscriptionaftermin'], $_POST['subscriptionaftersec'], $_POST['subscriptionaftermonth'], $_POST['subscriptionafterday'], $_POST['subscriptionafteryear']);
        $dateendsubscriptionbefore = dol_mktime($_POST['subscriptionbeforehour'], $_POST['subscriptionbeforemin'], $_POST['subscriptionbeforesec'], $_POST['subscriptionbeforemonth'], $_POST['subscriptionbeforeday'], $_POST['subscriptionbeforeyear']);

        // La requete doit retourner: id, email, fk_contact, name, firstname
        $sql = "SELECT a.rowid as id, a.email as email, null as fk_contact, ";
        $sql .= " a.lastname, a.firstname,";
        $sql .= " a.datefin, a.civility as civility_id, a.login, a.societe"; // Other fields
        $sql .= " FROM ".MAIN_DB_PREFIX."adherent as a";
        if ($_POST['filter_category'])
        {
        	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."categorie_member as cm ON cm.fk_member = a.rowid";
        	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."categorie as c ON c.rowid = cm.fk_categorie";
        }
        $sql .= " , ".MAIN_DB_PREFIX."adherent_type as ta";
        $sql .= " WHERE a.entity IN (".getEntity('member').") AND a.email <> ''"; // Note that null != '' is false
        $sql .= " AND a.email NOT IN (SELECT email FROM ".MAIN_DB_PREFIX."mailing_cibles WHERE fk_mailing=".$this->db->escape($mailing_id).")";
        // Filter on status
        if (isset($_POST["filter"]) && $_POST["filter"] == '-1') {
            $sql .= " AND a.statut=-1";
        }
        if (isset($_POST["filter"]) && $_POST["filter"] == '1a') $sql .= " AND a.statut=1 AND (a.datefin >= '".$this->db->idate($now)."' OR ta.subscription = 0)";
        if (isset($_POST["filter"]) && $_POST["filter"] == '1b') $sql .= " AND a.statut=1 AND ((a.datefin IS NULL or a.datefin < '".$this->db->idate($now)."') AND ta.subscription = 1)";
        if (isset($_POST["filter"]) && $_POST["filter"] == '0')  $sql .= " AND a.statut=0";
        // Filter on date
        if ($dateendsubscriptionafter > 0)  $sql .= " AND datefin > '".$this->db->idate($dateendsubscriptionafter)."'";
        if ($dateendsubscriptionbefore > 0) $sql .= " AND datefin < '".$this->db->idate($dateendsubscriptionbefore)."'";
        $sql .= " AND a.fk_adherent_type = ta.rowid";
        // Filter on type
        if ($_POST['filter_type']) $sql .= " AND ta.rowid='".$_POST['filter_type']."'";
        // Filter on category
        if ($_POST['filter_category']) $sql .= " AND c.rowid='".$_POST['filter_category']."'";
        $sql .= " ORDER BY a.email";
        //print $sql;

        // Add targets into table
        dol_syslog(get_class($this)."::add_to_target", LOG_DEBUG);
        $result = $this->db->query($sql);
        if ($result)
        {
            $num = $this->db->num_rows($result);
            $i = 0;
            $j = 0;

            dol_syslog(get_class($this)."::add_to_target mailing ".$num." targets found");

            $old = '';
            while ($i < $num)
            {
                $obj = $this->db->fetch_object($result);
                if ($old <> $obj->email)
                {
                    $cibles[$j] = array(
                                'email' => $obj->email,
                                'fk_contact' => $obj->fk_contact,
                                'lastname' => $obj->lastname,
                                'firstname' => $obj->firstname,
                                'other' =>
                                ($langs->transnoentities("Login").'='.$obj->login).';'.
                                ($langs->transnoentities("UserTitle").'='.($obj->civility_id ? $langs->transnoentities("Civility".$obj->civility_id) : '')).';'.
                                ($langs->transnoentities("DateEnd").'='.dol_print_date($this->db->jdate($obj->datefin), 'day')).';'.
                                ($langs->transnoentities("Company").'='.$obj->societe),
                                'source_url' => $this->url($obj->id),
                                'source_id' => $obj->id,
                                'source_type' => 'member'
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
            $this->error = $this->db->error();
            return -1;
        }

        return parent::addTargetsToDatabase($mailing_id, $cibles);
    }
}
