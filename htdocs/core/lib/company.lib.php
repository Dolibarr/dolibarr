<?php
/* Copyright (C) 2006-2011  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2006       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2007       Patrick Raguin          <patrick.raguin@gmail.com>
 * Copyright (C) 2010-2012  Regis Houssin           <regis.houssin@capnetworks.com>
 * Copyright (C) 2013-2014  Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2013-2014  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2013       Christophe Battarel     <contact@altairis.fr>
 * Copyright (C) 2013       Alexandre Spangaro      <aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2015       Frederic France         <frederic.france@free.fr>
 * Copyright (C) 2015       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/core/lib/company.lib.php
 *	\brief      Ensemble de fonctions de base pour le module societe
 *	\ingroup    societe
 */

/**
 * Return array of tabs to used on pages for third parties cards.
 *
 * @param 	Societe	$object		Object company shown
 * @return 	array				Array of tabs
 */
function societe_prepare_head(Societe $object)
{
    global $db, $langs, $conf, $user;
    $h = 0;
    $head = array();

    $head[$h][0] = DOL_URL_ROOT.'/societe/soc.php?socid='.$object->id;
    $head[$h][1] = $langs->trans("Card");
    $head[$h][2] = 'card';
    $h++;

    if ($object->client==1 || $object->client==2 || $object->client==3)
    {
        $head[$h][0] = DOL_URL_ROOT.'/comm/card.php?socid='.$object->id;
        $head[$h][1] = '';
        if (empty($conf->global->SOCIETE_DISABLE_PROSPECTS) && ($object->client==2 || $object->client==3)) $head[$h][1] .= $langs->trans("Prospect");
        if (empty($conf->global->SOCIETE_DISABLE_PROSPECTS) && empty($conf->global->SOCIETE_DISABLE_CUSTOMERS) && $object->client==3) $head[$h][1] .= '/';
        if (empty($conf->global->SOCIETE_DISABLE_CUSTOMERS) && ($object->client==1 || $object->client==3)) $head[$h][1] .= $langs->trans("Customer");
        $head[$h][2] = 'customer';
        $h++;

        if (! empty($conf->global->PRODUIT_CUSTOMER_PRICES))
        {
            $langs->load("products");
            // price
            $head[$h][0] = DOL_URL_ROOT.'/societe/price.php?socid='.$object->id;
            $head[$h][1] = $langs->trans("CustomerPrices");
            $head[$h][2] = 'price';
            $h++;
        }
    }
    if (! empty($conf->fournisseur->enabled) && $object->fournisseur && ! empty($user->rights->fournisseur->lire))
    {
        $head[$h][0] = DOL_URL_ROOT.'/fourn/card.php?socid='.$object->id;
        $head[$h][1] = $langs->trans("Supplier");
        $head[$h][2] = 'supplier';
        $h++;
    }

    if (! empty($conf->global->MAIN_SUPPORT_SHARED_CONTACT_BETWEEN_THIRDPARTIES))
    {
        $head[$h][0] = DOL_URL_ROOT.'/societe/societecontact.php?socid='.$object->id;
	    $nbContact = count($object->liste_contact(-1,'internal')) + count($object->liste_contact(-1,'external'));
        $head[$h][1] = $langs->trans("ContactsAddresses");
		if ($nbContact > 0) $head[$h][1].= ' <span class="badge">'.$nbContact.'</span>';
        $head[$h][2] = 'contact';
        $h++;
    }

    if (! empty($conf->projet->enabled) && (!empty($user->rights->projet->lire) ))
    {
    	$head[$h][0] = DOL_URL_ROOT.'/societe/project.php?socid='.$object->id;
    	$head[$h][1] = $langs->trans("Projects");
    	$nbNote = 0;
    	$sql = "SELECT COUNT(n.rowid) as nb";
    	$sql.= " FROM ".MAIN_DB_PREFIX."projet as n";
    	$sql.= " WHERE fk_soc = ".$object->id;
    	$resql=$db->query($sql);
    	if ($resql)
    	{
    		$num = $db->num_rows($resql);
    		$i = 0;
    		while ($i < $num)
    		{
    			$obj = $db->fetch_object($resql);
    			$nbNote=$obj->nb;
    			$i++;
    		}
    	}
    	else {
    		dol_print_error($db);
    	}
		if ($nbNote > 0) $head[$h][1].= ' <span class="badge">'.$nbNote.'</span>';
    	$head[$h][2] = 'project';
    	$h++;
    }

    // Tab to link resources
	if (! empty($conf->resource->enabled) && ! empty($conf->global->RESOURCE_ON_THIRDPARTIES))
	{
		$head[$h][0] = DOL_URL_ROOT.'/resource/element_resource.php?element=societe&element_id='.$object->id;
		$head[$h][1] = $langs->trans("Resources");
		$head[$h][2] = 'resources';
		$h++;
	}

	// Related items
    if (! empty($conf->commande->enabled) || ! empty($conf->propal->enabled) || ! empty($conf->facture->enabled) || ! empty($conf->fichinter->enabled) || ! empty($conf->fournisseur->enabled))
    {
        $head[$h][0] = DOL_URL_ROOT.'/societe/consumption.php?socid='.$object->id;
        $head[$h][1] = $langs->trans("Referers");
        $head[$h][2] = 'consumption';
        $h++;
    }

    // Bank accounrs
    if (empty($conf->global->SOCIETE_DISABLE_BANKACCOUNT))
    {
        $langs->load("banks");

        $nbBankAccount=0;
        $head[$h][0] = DOL_URL_ROOT .'/societe/rib.php?socid='.$object->id;
        $head[$h][1] = $langs->trans("BankAccounts");
        $sql = "SELECT COUNT(n.rowid) as nb";
        $sql.= " FROM ".MAIN_DB_PREFIX."societe_rib as n";
        $sql.= " WHERE fk_soc = ".$object->id;
        $resql=$db->query($sql);
        if ($resql)
        {
            $num = $db->num_rows($resql);
            $i = 0;
            while ($i < $num)
            {
                $obj = $db->fetch_object($resql);
                $nbBankAccount=$obj->nb;
                $i++;
            }
        }
        else {
            dol_print_error($db);
        }
		if ($nbBankAccount > 0) $head[$h][1].= ' <span class="badge">'.$nbBankAccount.'</span>';
        $head[$h][2] = 'rib';
        $h++;
    }
        
	// Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
    complete_head_from_modules($conf,$langs,$object,$head,$h,'thirdparty');

    if ($user->societe_id == 0)
    {
        // Notifications
        if (! empty($conf->notification->enabled))
        {
        	$nbNote = 0;
        	$sql = "SELECT COUNT(n.rowid) as nb";
        	$sql.= " FROM ".MAIN_DB_PREFIX."notify_def as n";
        	$sql.= " WHERE fk_soc = ".$object->id;
        	$resql=$db->query($sql);
        	if ($resql)
        	{
        		$num = $db->num_rows($resql);
        		$i = 0;
        		while ($i < $num)
        		{
        			$obj = $db->fetch_object($resql);
        			$nbNote=$obj->nb;
        			$i++;
        		}
        	}
        	else {
        		dol_print_error($db);
        	}

        	$head[$h][0] = DOL_URL_ROOT.'/societe/notify/card.php?socid='.$object->id;
        	$head[$h][1] = $langs->trans("Notifications");
			if ($nbNote > 0) $head[$h][1].= ' <span class="badge">'.$nbNote.'</span>';
        	$head[$h][2] = 'notify';
        	$h++;
        }

		// Notes
        $nbNote = 0;
        if(!empty($object->note_private)) $nbNote++;
		if(!empty($object->note_public)) $nbNote++;
        $head[$h][0] = DOL_URL_ROOT.'/societe/note.php?id='.$object->id;
        $head[$h][1] = $langs->trans("Notes");
		if ($nbNote > 0) $head[$h][1].= ' <span class="badge">'.$nbNote.'</span>';
        $head[$h][2] = 'note';
        $h++;

        // Attached files
        require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
        require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
        $upload_dir = $conf->societe->multidir_output[$object->entity] . "/" . $object->id ;
        $nbFiles = count(dol_dir_list($upload_dir,'files',0,'','(\.meta|_preview\.png)$'));
        $nbLinks=Link::count($db, $object->element, $object->id);
        
        $head[$h][0] = DOL_URL_ROOT.'/societe/document.php?socid='.$object->id;
        $head[$h][1] = $langs->trans("Documents");
		if (($nbFiles+$nbLinks) > 0) $head[$h][1].= ' <span class="badge">'.($nbFiles+$nbLinks).'</span>';
        $head[$h][2] = 'document';
        $h++;
    }

    $head[$h][0] = DOL_URL_ROOT.'/societe/agenda.php?socid='.$object->id;
    $head[$h][1].= $langs->trans("Events");
    if (! empty($conf->agenda->enabled) && (!empty($user->rights->agenda->myactions->read) || !empty($user->rights->agenda->allactions->read) ))
    {
        $head[$h][1].= '/';
        $head[$h][1].= $langs->trans("Agenda");
    }
    $head[$h][2] = 'agenda';
    $h++;
    
    // Log
    /*$head[$h][0] = DOL_URL_ROOT.'/societe/info.php?socid='.$object->id;
    $head[$h][1] = $langs->trans("Info");
    $head[$h][2] = 'info';
    $h++;*/

    complete_head_from_modules($conf,$langs,$object,$head,$h,'thirdparty','remove');

    return $head;
}


/**
 * Return array of tabs to used on page
 *
 * @param	Object	$object		Object for tabs
 * @return	array				Array of tabs
 */
function societe_prepare_head2($object)
{
    global $langs, $conf, $user;
    $h = 0;
    $head = array();

    $head[$h][0] = DOL_URL_ROOT.'/societe/soc.php?socid='.$object->id;
    $head[$h][1] = $langs->trans("Card");
    $head[$h][2] = 'company';
    $h++;

    $head[$h][0] = 'commerciaux.php?socid='.$object->id;
    $head[$h][1] = $langs->trans("SalesRepresentative");
    $head[$h][2] = 'salesrepresentative';
    $h++;

    return $head;
}



/**
 *  Return array head with list of tabs to view object informations.
 *
 *  @return	array   	        head array with tabs
 */
function societe_admin_prepare_head()
{
    global $langs, $conf, $user;

    $h = 0;
    $head = array();

    $head[$h][0] = DOL_URL_ROOT.'/societe/admin/societe.php';
    $head[$h][1] = $langs->trans("Miscellaneous");
    $head[$h][2] = 'general';
    $h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
    complete_head_from_modules($conf,$langs,null,$head,$h,'company_admin');

    $head[$h][0] = DOL_URL_ROOT.'/societe/admin/societe_extrafields.php';
    $head[$h][1] = $langs->trans("ExtraFieldsThirdParties");
    $head[$h][2] = 'attributes';
    $h++;

    $head[$h][0] = DOL_URL_ROOT.'/societe/admin/contact_extrafields.php';
    $head[$h][1] = $langs->trans("ExtraFieldsContacts");
    $head[$h][2] = 'attributes_contacts';
    $h++;

    complete_head_from_modules($conf,$langs,null,$head,$h,'company_admin','remove');

    return $head;
}



/**
 *    Return country label, code or id from an id, code or label
 *
 *    @param      int		$searchkey      Id or code of country to search
 *    @param      int		$withcode   	'0'=Return label,
 *    										'1'=Return code + label,
 *    										'2'=Return code from id,
 *    										'3'=Return id from code,
 * 	   										'all'=Return array('id'=>,'code'=>,'label'=>)
 *    @param      DoliDB	$dbtouse       	Database handler (using in global way may fail because of conflicts with some autoload features)
 *    @param      Translate	$outputlangs	Langs object for output translation
 *    @param      int		$entconv       	0=Return value without entities and not converted to output charset, 1=Ready for html output
 *    @param      int		$searchlabel    Label of country to search (warning: searching on label is not reliable)
 *    @return     mixed       				String with country code or translated country name or Array('id','code','label')
 */
function getCountry($searchkey,$withcode='',$dbtouse=0,$outputlangs='',$entconv=1,$searchlabel='')
{
    global $db,$langs;

    $result='';
    
    // Check parameters
    if (empty($searchkey) && empty($searchlabel))
    {
    	if ($withcode === 'all') return array('id'=>'','code'=>'','label'=>'');
    	else return '';
    }
    if (! is_object($dbtouse)) $dbtouse=$db;
    if (! is_object($outputlangs)) $outputlangs=$langs;

    $sql = "SELECT rowid, code, label FROM ".MAIN_DB_PREFIX."c_country";
    if (is_numeric($searchkey)) $sql.= " WHERE rowid=".$searchkey;
    elseif (! empty($searchkey)) $sql.= " WHERE code='".$db->escape($searchkey)."'";
    else $sql.= " WHERE label='".$db->escape($searchlabel)."'";

    $resql=$dbtouse->query($sql);
    if ($resql)
    {
        $obj = $dbtouse->fetch_object($resql);
        if ($obj)
        {
            $label=((! empty($obj->label) && $obj->label!='-')?$obj->label:'');
            if (is_object($outputlangs))
            {
                $outputlangs->load("dict");
                if ($entconv) $label=($obj->code && ($outputlangs->trans("Country".$obj->code)!="Country".$obj->code))?$outputlangs->trans("Country".$obj->code):$label;
                else $label=($obj->code && ($outputlangs->transnoentitiesnoconv("Country".$obj->code)!="Country".$obj->code))?$outputlangs->transnoentitiesnoconv("Country".$obj->code):$label;
            }
            if ($withcode == 1) $result=$label?"$obj->code - $label":"$obj->code";
            else if ($withcode == 2) $result=$obj->code;
            else if ($withcode == 3) $result=$obj->rowid;
            else if ($withcode === 'all') $result=array('id'=>$obj->rowid,'code'=>$obj->code,'label'=>$label);
            else $result=$label;
        }
        else
        {
            $result='NotDefined';
        }
        $dbtouse->free($resql);
        return $result;
    }
    else dol_print_error($dbtouse,'');
    return 'Error';
}

/**
 *    Return state translated from an id. Return value is always utf8 encoded and without entities.
 *
 *    @param	int			$id         	id of state (province/departement)
 *    @param    int			$withcode   	'0'=Return label,
 *    										'1'=Return string code + label,
 *    						  				'2'=Return code,
 *    						  				'all'=return array('id'=>,'code'=>,'label'=>)
 *    @param	DoliDB		$dbtouse		Database handler (using in global way may fail because of conflicts with some autoload features)
 *    @return   string      				String with state code or state name (Return value is always utf8 encoded and without entities)
 */
function getState($id,$withcode='',$dbtouse=0)
{
    global $db,$langs;

    if (! is_object($dbtouse)) $dbtouse=$db;

    $sql = "SELECT rowid, code_departement as code, nom as label FROM ".MAIN_DB_PREFIX."c_departements";
    $sql.= " WHERE rowid=".$id;

    dol_syslog("Company.lib::getState", LOG_DEBUG);
    $resql=$dbtouse->query($sql);
    if ($resql)
    {
        $obj = $dbtouse->fetch_object($resql);
        if ($obj)
        {
            $label=$obj->label;
            if ($withcode == '1') return $label=$obj->code?"$obj->code":"$obj->code - $label";
            else if ($withcode == '2') return $label=$obj->code;
            else if ($withcode == 'all') return array('id'=>$obj->rowid,'code'=>$obj->code,'label'=>$label);
            else return $label;
        }
        else
        {
            return $langs->transnoentitiesnoconv("NotDefined");
        }
    }
    else dol_print_error($dbtouse,'');
}

/**
 *    Retourne le nom traduit ou code+nom d'une devise
 *
 *    @param      string	$code_iso       Code iso de la devise
 *    @param      int		$withcode       '1'=affiche code + nom
 *    @return     string     			    Nom traduit de la devise
 */
function currency_name($code_iso,$withcode='')
{
    global $langs,$db;

    // Si il existe une traduction, on peut renvoyer de suite le libelle
    if ($langs->trans("Currency".$code_iso)!="Currency".$code_iso)
    {
        return $langs->trans("Currency".$code_iso);
    }

    // Si pas de traduction, on consulte le libelle par defaut en table
    $sql = "SELECT label FROM ".MAIN_DB_PREFIX."c_currencies";
    $sql.= " WHERE code_iso='".$code_iso."'";

    $resql=$db->query($sql);
    if ($resql)
    {
        $num = $db->num_rows($resql);

        if ($num)
        {
            $obj = $db->fetch_object($resql);
            $label=($obj->label!='-'?$obj->label:'');
            if ($withcode) return ($label==$code_iso)?"$code_iso":"$code_iso - $label";
            else return $label;
        }
        else
        {
            return $code_iso;
        }

    }
}

/**
 *    Retourne le nom traduit de la forme juridique
 *
 *    @param      string	$code       Code de la forme juridique
 *    @return     string     			Nom traduit du pays
 */
function getFormeJuridiqueLabel($code)
{
    global $db,$langs;

    if (! $code) return '';

    $sql = "SELECT libelle FROM ".MAIN_DB_PREFIX."c_forme_juridique";
    $sql.= " WHERE code='$code'";

    dol_syslog("Company.lib::getFormeJuridiqueLabel", LOG_DEBUG);
    $resql=$db->query($sql);
    if ($resql)
    {
        $num = $db->num_rows($resql);

        if ($num)
        {
            $obj = $db->fetch_object($resql);
            $label=($obj->libelle!='-' ? $obj->libelle : '');
            return $label;
        }
        else
        {
            return $langs->trans("NotDefined");
        }

    }
}



/**
 * 		Show html area for list of projects
 *
 *		@param	Conf		$conf			Object conf
 * 		@param	Translate	$langs			Object langs
 * 		@param	DoliDB		$db				Database handler
 * 		@param	Object		$object			Third party object
 *      @param  string		$backtopage		Url to go once contact is created
 *      @param  int         $nocreatelink   1=Hide create project link
 *      @return	void
 */
function show_projects($conf, $langs, $db, $object, $backtopage='', $nocreatelink=0)
{
    global $user;
    global $bc;

    $i = -1 ;

    if (! empty($conf->projet->enabled) && $user->rights->projet->lire)
    {
        $langs->load("projects");

        $buttoncreate='';
        if (! empty($conf->projet->enabled) && $user->rights->projet->creer && empty($nocreatelink))
        {
            //$buttoncreate='<a class="butAction" href="'.DOL_URL_ROOT.'/projet/card.php?socid='.$object->id.'&action=create&amp;backtopage='.urlencode($backtopage).'">'.$langs->trans("AddProject").'</a>';
			$buttoncreate='<a class="addnewrecord" href="'.DOL_URL_ROOT.'/projet/card.php?socid='.$object->id.'&amp;action=create&amp;backtopage='.urlencode($backtopage).'">'.$langs->trans("AddProject");
			if (empty($conf->dol_optimize_smallscreen)) $buttoncreate.=' '.img_picto($langs->trans("AddProject"),'filenew');
			$buttoncreate.='</a>'."\n";
        }

        print "\n";
        print load_fiche_titre($langs->trans("ProjectsDedicatedToThisThirdParty"),$buttoncreate,'');
        print '<div class="div-table-responsive">';
        print "\n".'<table class="noborder" width=100%>';

        $sql  = "SELECT p.rowid as id, p.title, p.ref, p.public, p.dateo as do, p.datee as de, p.fk_statut as status";
        $sql .= " FROM ".MAIN_DB_PREFIX."projet as p";
        $sql .= " WHERE p.fk_soc = ".$object->id;
        $sql .= " ORDER BY p.dateo DESC";

        $result=$db->query($sql);
        if ($result)
        {
            $num = $db->num_rows($result);

            print '<tr class="liste_titre">';
            print '<td>'.$langs->trans("Ref").'</td><td>'.$langs->trans("Name").'</td><td align="center">'.$langs->trans("DateStart").'</td><td align="center">'.$langs->trans("DateEnd").'</td>';
            print '<td align="right">'.$langs->trans("Status").'</td>';
            print '</tr>';

            if ($num > 0)
            {
                require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

                $projecttmp = new Project($db);

                $i=0;
                $var=true;
                while ($i < $num)
                {
                    $obj = $db->fetch_object($result);
                    $projecttmp->fetch($obj->id);

                    // To verify role of users
                    $userAccess = $projecttmp->restrictedProjectArea($user);

                    if ($user->rights->projet->lire && $userAccess > 0)
                    {
                        $var = !$var;
                        print "<tr ".$bc[$var].">";

                        // Ref
                        print '<td><a href="'.DOL_URL_ROOT.'/projet/card.php?id='.$projecttmp->id.'">'.img_object($langs->trans("ShowProject"),($obj->public?'projectpub':'project'))." ".$obj->ref.'</a></td>';
                        // Label
                        print '<td>'.$obj->title.'</td>';
                        // Date start
                        print '<td align="center">'.dol_print_date($db->jdate($obj->do),"day").'</td>';
                        // Date end
                        print '<td align="center">'.dol_print_date($db->jdate($obj->de),"day").'</td>';
                        // Status
                        print '<td align="right">'.$projecttmp->getLibStatut(5).'</td>';

                        print '</tr>';
                    }
                    $i++;
                }
            }
            else
			{
                $var = false;
            	print '<tr '.$bc[$var].'><td colspan="5" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
            }
            $db->free($result);
        }
        else
        {
            dol_print_error($db);
        }
        print "</table>";
        print '</div>';
        
        print "<br>\n";
    }

    return $i;
}


/**
 * 		Show html area for list of contacts
 *
 *		@param	Conf		$conf		Object conf
 * 		@param	Translate	$langs		Object langs
 * 		@param	DoliDB		$db			Database handler
 * 		@param	Societe		$object		Third party object
 *      @param  string		$backtopage	Url to go once contact is created
 *      @return	void
 */
function show_contacts($conf,$langs,$db,$object,$backtopage='')
{
    global $user,$conf;
    global $bc;

    $form= new Form($db);

    $sortfield = GETPOST("sortfield",'alpha');
    $sortorder = GETPOST("sortorder",'alpha');
    $search_status		= GETPOST("search_status",'int');
    if ($search_status=='') $search_status=1; // always display activ customer first
    $search_name = GETPOST("search_name",'alpha');
    $search_addressphone = GETPOST("search_addressphone",'alpha');

    if (! $sortorder) $sortorder="ASC";
    if (! $sortfield) $sortfield="p.lastname";

    $i=-1;

    $contactstatic = new Contact($db);

    if (! empty($conf->clicktodial->enabled))
    {
        $user->fetch_clicktodial(); // lecture des infos de clicktodial
    }

    $buttoncreate='';
    if ($user->rights->societe->contact->creer)
    {
    	$addcontact = (! empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT) ? $langs->trans("AddContact") : $langs->trans("AddContactAddress"));
		$buttoncreate='<a class="addnewrecord" href="'.DOL_URL_ROOT.'/contact/card.php?socid='.$object->id.'&amp;action=create&amp;backtopage='.urlencode($backtopage).'">'.$addcontact;
		if (empty($conf->dol_optimize_smallscreen)) $buttoncreate.=' '.img_picto($addcontact,'filenew');
		$buttoncreate.='</a>'."\n";
    }

    print "\n";

    $title = (! empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT) ? $langs->trans("ContactsForCompany") : $langs->trans("ContactsAddressesForCompany"));
    print load_fiche_titre($title,$buttoncreate,'');

    print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'" name="formfilter">';
    print '<input type="hidden" name="socid" value="'.$object->id.'">';
    print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
    print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';

    print "\n".'<table class="noborder" width="100%">'."\n";

    $param="socid=".$object->id;
    if ($search_status != '') $param.='&amp;search_status='.$search_status;
    if ($search_name != '') $param.='&amp;search_name='.urlencode($search_name);

    $colspan=9;
    print '<tr class="liste_titre">';
    print_liste_field_titre('');
    print_liste_field_titre($langs->trans("Name"),$_SERVER["PHP_SELF"],"p.lastname","",$param,'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Poste"),$_SERVER["PHP_SELF"],"p.poste","",$param,'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Address").' / '.$langs->trans("Phone").' / '.$langs->trans("Email"),$_SERVER["PHP_SELF"],"","",$param,'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"p.statut","",$param,'',$sortfield,$sortorder);
    // Add to agenda
    if (! empty($conf->agenda->enabled) && ! empty($user->rights->agenda->myactions->create))
    {
    	$colspan++;
        print_liste_field_titre('');
    }
    // Edit
    print_liste_field_titre('');
	print "</tr>\n";


    $sql = "SELECT p.rowid, p.lastname, p.firstname, p.fk_pays as country_id, p.civility, p.poste, p.phone as phone_pro, p.phone_mobile, p.phone_perso, p.fax, p.email, p.skype, p.statut, p.photo,";
    $sql .= " p.civility as civility_id, p.address, p.zip, p.town";
    $sql .= " FROM ".MAIN_DB_PREFIX."socpeople as p";
    $sql .= " WHERE p.fk_soc = ".$object->id;
    if ($search_status!='' && $search_status != '-1') $sql .= " AND p.statut = ".$db->escape($search_status);
    if ($search_name)       $sql .= " AND (p.lastname LIKE '%".$db->escape($search_name)."%' OR p.firstname LIKE '%".$db->escape($search_name)."%')";
    $sql.= " ORDER BY $sortfield $sortorder";

    dol_syslog('core/lib/company.lib.php :: show_contacts', LOG_DEBUG);
    $result = $db->query($sql);
    if (! $result) dol_print_error($db);

    $num = $db->num_rows($result);

	$var=true;
	if ($num || (GETPOST('button_search') || GETPOST('button_search.x') || GETPOST('button_search_x')))
    {
        print '<tr class="liste_titre">';
        
        // Photo
        print '<td class="liste_titre">';
        print '</td>';
        
        // Name - Position
        print '<td class="liste_titre">';
        print '<input type="text" class="flat" name="search_name" size="20" value="'.$search_name.'">';
        print '</td>';
    
        // Address / Phone
        print '<td class="liste_titre">';
        //print '<input type="text" class="flat" name="search_addressphone" size="20" value="'.$search_addressphone.'">';
        print '</td>';
    
        // Email
        print '<td class="liste_titre">&nbsp;</td>';
    
        // Status
        print '<td class="liste_titre maxwidthonsmartphone">';
        print $form->selectarray('search_status', array('-1'=>'','0'=>$contactstatic->LibStatut(0,1),'1'=>$contactstatic->LibStatut(1,1)),$search_status);
        print '</td>';
    
        // Add to agenda
        if (! empty($conf->agenda->enabled) && $user->rights->agenda->myactions->create)
        {
        	$colspan++;
            print '<td class="liste_titre">&nbsp;</td>';
        }
    
    	// Edit
        print '<td class="liste_titre" align="right">';
        print '<input type="image" class="liste_titre" name="button_search" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
        print '</td>';
    
        print "</tr>";
    
        $i=0;

        while ($i < $num)
        {
            $obj = $db->fetch_object($result);
            $var = !$var;
            
            $contactstatic->id = $obj->rowid;
            $contactstatic->ref = $obj->ref;
            $contactstatic->statut = $obj->statut;
            $contactstatic->lastname = $obj->lastname;
            $contactstatic->firstname = $obj->firstname;
            $contactstatic->civility_id = $obj->civility_id;
            $contactstatic->civility_code = $obj->civility_id;
            $contactstatic->poste = $obj->poste;
            $contactstatic->address = $obj->address;
            $contactstatic->zip = $obj->zip;
            $contactstatic->town = $obj->town;
            $contactstatic->phone_pro = $obj->phone_pro;
            $contactstatic->phone_mobile = $obj->phone_mobile;
            $contactstatic->phone_perso = $obj->phone_perso;
            $contactstatic->email = $obj->email;
            $contactstatic->web = $obj->web;
            $contactstatic->skype = $obj->skype;
            $contactstatic->photo = $obj->photo;

            $country_code = getCountry($obj->country_id, 2);
            $contactstatic->country_code = $country_code;

            $contactstatic->setGenderFromCivility();
            
            print "<tr ".$bc[$var].">";

            // Photo
            print '<td width="50px">';
            print $form->showphoto('contact',$contactstatic,0,0,0,'photorefnoborder','small',1,0,1);
			print '</td>';
            
			// Name
			print '<td>';
            print $contactstatic->getNomUrl(0,'',0,'&backtopage='.urlencode($backtopage));
			print '</td>';
			
			// Job position
			print '<td>';
            if ($obj->poste) print $obj->poste;
            print '</td>';

            // Address and phone
            print '<td>';
            print $contactstatic->getBannerAddress('contact', $object);
            print '</td>';

            // Status
			print '<td>'.$contactstatic->getLibStatut(5).'</td>';

            // Add to agenda
            if (! empty($conf->agenda->enabled) && $user->rights->agenda->myactions->create)
            {
                print '<td align="center">';
                print '<a href="'.DOL_URL_ROOT.'/comm/action/card.php?action=create&actioncode=&contactid='.$obj->rowid.'&socid='.$object->id.'&backtopage='.urlencode($backtopage).'">';
                print img_object($langs->trans("Event"),"action");
                print '</a></td>';
            }

            // Edit
            if ($user->rights->societe->contact->creer)
            {
                print '<td align="right">';
                print '<a href="'.DOL_URL_ROOT.'/contact/card.php?action=edit&amp;id='.$obj->rowid.'&amp;backtopage='.urlencode($backtopage).'">';
                print img_edit();
                print '</a></td>';
            }
            else print '<td>&nbsp;</td>';

            print "</tr>\n";
            $i++;
        }
    }
    else
	{
        print "<tr ".$bc[! $var].">";
        print '<td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("None").'</td>';
        print "</tr>\n";
    }
    print "\n</table>\n";

    print '</form>'."\n";

    return $i;
}

/**
 * 		Show html area for list of addresses
 *
 *		@param	Conf		$conf		Object conf
 * 		@param	Translate	$langs		Object langs
 * 		@param	DoliDB		$db			Database handler
 * 		@param	Societe		$object		Third party object
 *      @param  string		$backtopage	Url to go once address is created
 *      @return	void
 */
function show_addresses($conf,$langs,$db,$object,$backtopage='')
{
	global $user;
	global $bc;

	require_once DOL_DOCUMENT_ROOT.'/societe/class/address.class.php';

	$addressstatic = new Address($db);
	$num = $addressstatic->fetch_lines($object->id);

	$buttoncreate='';
	if ($user->rights->societe->creer)
	{
		$buttoncreate='<a class="addnewrecord" href="'.DOL_URL_ROOT.'/comm/address.php?socid='.$object->id.'&amp;action=create&amp;backtopage='.urlencode($backtopage).'">'.$langs->trans("AddAddress").' '.img_picto($langs->trans("AddAddress"),'filenew').'</a>'."\n";
	}

	print "\n";
	print load_fiche_titre($langs->trans("AddressesForCompany"),$buttoncreate,'');

	print "\n".'<table class="noborder" width="100%">'."\n";

	print '<tr class="liste_titre"><td>'.$langs->trans("Label").'</td>';
	print '<td>'.$langs->trans("CompanyName").'</td>';
	print '<td>'.$langs->trans("Town").'</td>';
	print '<td>'.$langs->trans("Country").'</td>';
	print '<td>'.$langs->trans("Phone").'</td>';
	print '<td>'.$langs->trans("Fax").'</td>';
	print "<td>&nbsp;</td>";
	print "</tr>";

	if ($num > 0)
	{
		$var=true;

		foreach ($addressstatic->lines as $address)
		{
			$var = !$var;

			print "<tr ".$bc[$var].">";

			print '<td>';
			$addressstatic->id = $address->id;
			$addressstatic->label = $address->label;
			print $addressstatic->getNomUrl(1);
			print '</td>';

			print '<td>'.$address->name.'</td>';

			print '<td>'.$address->town.'</td>';

			$img=picto_from_langcode($address->country_code);
			print '<td>'.($img?$img.' ':'').$address->country.'</td>';

			// Lien click to dial
			print '<td>';
			print dol_print_phone($address->phone,$address->country_code,$address->id,$object->id,'AC_TEL');
			print '</td>';
			print '<td>';
			print dol_print_phone($address->fax,$address->country_code,$address->id,$object->id,'AC_FAX');
			print '</td>';

			if ($user->rights->societe->creer)
			{
				print '<td align="right">';
				print '<a href="'.DOL_URL_ROOT.'/comm/address.php?action=edit&amp;id='.$address->id.'&amp;socid='.$object->id.'&amp;backtopage='.urlencode($backtopage).'">';
				print img_edit();
				print '</a></td>';
			}

			print "</tr>\n";
		}
	}
	else
	{
		//print "<tr ".$bc[$var].">";
		//print '<td>'.$langs->trans("NoAddressYetDefined").'</td>';
		//print "</tr>\n";
	}
	print "\n</table>\n";

	print "<br>\n";

	return $num;
}

/**
 *    	Show html area with actions to do
 *
 * 		@param	Conf		$conf		       Object conf
 * 		@param	Translate	$langs		       Object langs
 * 		@param	DoliDB		$db			       Object db
 * 		@param	Adherent|Societe    $filterobj    Object third party or member
 * 		@param	Contact		$objcon	           Object contact
 *      @param  int			$noprint	       Return string but does not output it
 *      @param  int			$actioncode 	   Filter on actioncode
 *      @return	mixed						   Return html part or void if noprint is 1
 */
function show_actions_todo($conf,$langs,$db,$filterobj,$objcon='',$noprint=0,$actioncode='')
{
    global $bc,$user,$conf;

    $out = show_actions_done($conf,$langs,$db,$filterobj,$objcon,1,$actioncode, 'todo');

    if ($noprint) return $out;
    else print $out;
}

/**
 *    	Show html area with actions (done or not, ignore the name of function)
 *
 * 		@param	Conf		       $conf		   Object conf
 * 		@param	Translate	       $langs		   Object langs
 * 		@param	DoliDB		       $db			   Object db
 * 		@param	Adherent|Societe|Project   $filterobj	   Object third party or member or project
 * 		@param	Contact		       $objcon		   Object contact
 *      @param  int			       $noprint        Return string but does not output it
 *      @param  string		       $actioncode     Filter on actioncode
 *      @param  string             $donetodo       Filter on event 'done' or 'todo' or ''=nofilter (all).
 *      @param  array              $filters        Filter on other fields
 *      @param  string             $sortfield      Sort field
 *      @param  string             $sortorder      Sort order
 *      @return	mixed					           Return html part or void if noprint is 1
 *      TODO change function to be able to list event linked to an object.
 */
function show_actions_done($conf, $langs, $db, $filterobj, $objcon='', $noprint=0, $actioncode='', $donetodo='done', $filters=array(), $sortfield='a.datep,a.id', $sortorder='DESC')
{
    global $bc,$user,$conf;
    global $form;

    global $param;
    
    // Check parameters
    if (! is_object($filterobj)) dol_print_error('','BadParameter');

    $out='';
    $histo=array();
    $numaction = 0 ;
    $now=dol_now('tzuser');

    if (! empty($conf->agenda->enabled))
    {
        // Recherche histo sur actioncomm
        $sql = "SELECT a.id, a.label,";
        $sql.= " a.datep as dp,";
        $sql.= " a.datep2 as dp2,";
        $sql.= " a.note, a.percent,";
        $sql.= " a.fk_element, a.elementtype,";
        $sql.= " a.fk_user_author, a.fk_contact,";
        $sql.= " c.code as acode, c.libelle as alabel, c.picto as apicto,";
        $sql.= " u.login, u.rowid as user_id";
        if (get_class($filterobj) == 'Societe')  $sql.= ", sp.lastname, sp.firstname";
        if (get_class($filterobj) == 'Adherent') $sql.= ", m.lastname, m.firstname";
        if (get_class($filterobj) == 'CommandeFournisseur') $sql.= ", o.ref";
        $sql.= " FROM ".MAIN_DB_PREFIX."user as u, ".MAIN_DB_PREFIX."actioncomm as a";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_actioncomm as c ON a.fk_action = c.id";
        if (get_class($filterobj) == 'Societe')  $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople as sp ON a.fk_contact = sp.rowid";
        if (get_class($filterobj) == 'Adherent') $sql.= ", ".MAIN_DB_PREFIX."adherent as m";
        if (get_class($filterobj) == 'CommandeFournisseur') $sql.= ", ".MAIN_DB_PREFIX."commande_fournisseur as o";
        $sql.= " WHERE u.rowid = a.fk_user_action";
        $sql.= " AND a.entity IN (".getEntity('agenda', 1).")";
        if (get_class($filterobj) == 'Societe'  && $filterobj->id) $sql.= " AND a.fk_soc = ".$filterobj->id;
        if (get_class($filterobj) == 'Project' && $filterobj->id) $sql.= " AND a.fk_project = ".$filterobj->id;
        if (get_class($filterobj) == 'Adherent') 
        {
            $sql.= " AND a.fk_element = m.rowid AND a.elementtype = 'member'";
            if ($filterobj->id) $sql.= " AND a.fk_element = ".$filterobj->id;
        }
        if (get_class($filterobj) == 'CommandeFournisseur')
        {
            $sql.= " AND a.fk_element = o.rowid AND a.elementtype = 'order_supplier'";
            if ($filterobj->id) $sql.= " AND a.fk_element = ".$filterobj->id;
        }
        if (is_object($objcon) && $objcon->id) $sql.= " AND a.fk_contact = ".$objcon->id;
        // Condition on actioncode
        if (! empty($actioncode))
        {
            if (empty($conf->global->AGENDA_USE_EVENT_TYPE))
            {
                if ($actioncode == 'AC_NON_AUTO') $sql.= " AND c.type != 'systemauto'";
                elseif ($actioncode == 'AC_ALL_AUTO') $sql.= " AND c.type = 'systemauto'";
                else 
                {
                    if ($actioncode == 'AC_OTH') $sql.= " AND c.type != 'systemauto'";
                    if ($actioncode == 'AC_OTH_AUTO') $sql.= " AND c.type = 'systemauto'";
                }
            }
            else
            {
                if ($actioncode == 'AC_NON_AUTO') $sql.= " AND c.type != 'systemauto'";
                elseif ($actioncode == 'AC_ALL_AUTO') $sql.= " AND c.type = 'systemauto'";
                else $sql.= " AND c.code = '".$db->escape($actioncode)."'";
            }
        }
        if ($donetodo == 'todo') $sql.= " AND ((a.percent >= 0 AND a.percent < 100) OR (a.percent = -1 AND a.datep > '".$db->idate($now)."'))";
        if ($donetodo == 'done') $sql.= " AND (a.percent = 100 OR (a.percent = -1 AND a.datep <= '".$db->idate($now)."'))";
        if (is_array($filters) && $filters['search_agenda_label']) $sql.= natural_search('a.label', $filters['search_agenda_label']);
        $sql.= $db->order($sortfield, $sortorder);

        dol_syslog("company.lib::show_actions_done", LOG_DEBUG);
        $resql=$db->query($sql);
        if ($resql)
        {
            $i = 0 ;
            $num = $db->num_rows($resql);
            $var=true;
            while ($i < $num)
            {
                $obj = $db->fetch_object($resql);
                
                //if ($donetodo == 'todo') $sql.= " AND ((a.percent >= 0 AND a.percent < 100) OR (a.percent = -1 AND a.datep > '".$db->idate($now)."'))";
                //if ($donetodo == 'done') $sql.= " AND (a.percent = 100 OR (a.percent = -1 AND a.datep <= '".$db->idate($now)."'))";
                $tododone='';
                if (($obj->percent >= 0 and $obj->percent < 100) || ($obj->percent == -1 && $obj->datep > $now)) $tododone='todo';

                $histo[$numaction]=array(
            		'type'=>'action',
                    'tododone'=>$tododone,
            		'id'=>$obj->id,
            		'datestart'=>$db->jdate($obj->dp),
            		'dateend'=>$db->jdate($obj->dp2),
            		'note'=>$obj->label,
            		'percent'=>$obj->percent,
                    'userid'=>$obj->user_id,
            		'login'=>$obj->login,
            		'contact_id'=>$obj->fk_contact,
            		'lastname'=>$obj->lastname,
            		'firstname'=>$obj->firstname,
            		'fk_element'=>$obj->fk_element,
            		'elementtype'=>$obj->elementtype,
                    // Type of event
                    'acode'=>$obj->acode,
                    'alabel'=>$obj->alabel,
                    'libelle'=>$obj->alabel,    // deprecated
                    'apicto'=>$obj->apicto
                );
                
                $numaction++;
                $i++;
            }
        }
        else
        {
            dol_print_error($db);
        }
    }

    // Add also event from emailings. FIXME This should be replaced by an automatic event
    if (! empty($conf->mailing->enabled) && ! empty($objcon->email))
    {
        $langs->load("mails");

        $sql = "SELECT m.rowid as id, mc.date_envoi as da, m.titre as note, '100' as percentage,";
        $sql.= " 'AC_EMAILING' as acode,";
        $sql.= " u.rowid as user_id, u.login";	// User that valid action
        $sql.= " FROM ".MAIN_DB_PREFIX."mailing as m, ".MAIN_DB_PREFIX."mailing_cibles as mc, ".MAIN_DB_PREFIX."user as u";
        $sql.= " WHERE mc.email = '".$db->escape($objcon->email)."'";	// Search is done on email.
        $sql.= " AND mc.statut = 1";
        $sql.= " AND u.rowid = m.fk_user_valid";
        $sql.= " AND mc.fk_mailing=m.rowid";
        $sql.= " ORDER BY mc.date_envoi DESC, m.rowid DESC";

        dol_syslog("company.lib::show_actions_done", LOG_DEBUG);
        $resql=$db->query($sql);
        if ($resql)
        {
            $i = 0 ;
            $num = $db->num_rows($resql);
            $var=true;
            while ($i < $num)
            {
                $obj = $db->fetch_object($resql);
                $histo[$numaction]=array(
                		'type'=>'mailing',
                        'tododone'=>'done',
                		'id'=>$obj->id,
                		'datestart'=>$db->jdate($obj->da),
                		'dateend'=>$db->jdate($obj->da),
                		'note'=>$obj->note,
                		'percent'=>$obj->percentage,
                		'acode'=>$obj->acode,
                		'userid'=>$obj->user_id,
                		'login'=>$obj->login
				);
                $numaction++;
                $i++;
            }
	        $db->free($resql);
        }
        else
        {
            dol_print_error($db);
        }
    }


    if (! empty($conf->agenda->enabled) || (! empty($conf->mailing->enabled) && ! empty($objcon->email)))
    {
        $delay_warning=$conf->global->MAIN_DELAY_ACTIONS_TODO*24*60*60;
        
        require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
        require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
        require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
        require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
        require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
	    require_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';

	    $formactions=new FormActions($db);
        
	    $actionstatic=new ActionComm($db);
        $userstatic=new User($db);
        $contactstatic = new Contact($db);

        // TODO mutualize/uniformize
        $propalstatic=new Propal($db);
        $orderstatic=new Commande($db);
        $supplierorderstatic=new CommandeFournisseur($db);
        $facturestatic=new Facture($db);

        $out.='<form name="listactionsfilter" class="listactionsfilter" action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
        if ($objcon && get_class($objcon) == 'Contact' && get_class($filterobj) == 'Societe')
        {
            $out.='<input type="hidden" name="id" value="'.$objcon->id.'" />';
        }
        else
        {
            $out.='<input type="hidden" name="id" value="'.$filterobj->id.'" />';
        }
        if (get_class($filterobj) == 'Societe') $out.='<input type="hidden" name="socid" value="'.$filterobj->id.'" />';
        
        $out.="\n";
        
        $out.='<div class="div-table-responsive-no-min">';
        $out.='<table class="noborder" width="100%">';
        $out.='<tr class="liste_titre">';
		if ($donetodo)
		{
            $out.='<td>';
            if (get_class($filterobj) == 'Societe') $out.='<a href="'.DOL_URL_ROOT.'/comm/action/listactions.php?socid='.$filterobj->id.'&amp;status=done">';
            $out.=($donetodo != 'done' ? $langs->trans("ActionsToDoShort") : '');
            $out.=($donetodo != 'done' && $donetodo != 'todo' ? ' / ' : '');
            $out.=($donetodo != 'todo' ? $langs->trans("ActionsDoneShort") : '');
            //$out.=$langs->trans("ActionsToDoShort").' / '.$langs->trans("ActionsDoneShort");
            if (get_class($filterobj) == 'Societe') $out.='</a>';
            $out.='</td>';
		}
        $out.=getTitleFieldOfList($langs->trans("Ref"), 0, $_SERVER["PHP_SELF"], 'a.id', '', $param, '', $sortfield, $sortorder);
        $out.='<td class="maxwidth100onsmartphone">'.$langs->trans("Label").'</td>';
        $out.=getTitleFieldOfList($langs->trans("Date"), 0, $_SERVER["PHP_SELF"], 'a.datep,a.id', '', $param, '', $sortfield, $sortorder);
        $out.='<td>'.$langs->trans("Type").'</td>';
		$out.='<td></td>';
		$out.='<td></td>';
		$out.='<td>'.$langs->trans("Owner").'</td>';
		$out.=getTitleFieldOfList($langs->trans("Status"), 0, $_SERVER["PHP_SELF"], 'a.percent', '', $param, 'align="center"', $sortfield, $sortorder);
		$out.='<td class="maxwidthsearch">';
		//TODO Add selection of fields
		$out.='</td>';
		$out.='</tr>';

		
		$out.='<tr class="liste_titre">';
		if ($donetodo)
		{
            $out.='<td class="liste_titre"></td>';
		}
		$out.='<td class="liste_titre"></td>';
		$out.='<td class="liste_titre maxwidth100onsmartphone"><input type="text" class="maxwidth100onsmartphone" name="search_agenda_label" value="'.$filters['search_agenda_label'].'"></td>';
		$out.='<td class="liste_titre"></td>';
		$out.='<td class="liste_titre">';
	    $out.=$formactions->select_type_actions($actioncode, "actioncode", '', empty($conf->global->AGENDA_USE_EVENT_TYPE)?1:-1, 0, 0, 1);
		$out.='</td>';
		$out.='<td class="liste_titre"></td>';
		$out.='<td class="liste_titre"></td>';
		$out.='<td class="liste_titre"></td>';
		$out.='<td class="liste_titre"></td>';
    	// Action column
    	$out.='<td class="liste_titre" align="middle">';
    	$searchpitco=$form->showFilterAndCheckAddButtons($massactionbutton?1:0, 'checkforselect', 1);
    	$out.=$searchpitco;
    	$out.='</td>';
		$out.='</tr>';
		
        foreach ($histo as $key=>$value)
        {
            $var=!$var;
			$actionstatic->fetch($histo[$key]['id']);    // TODO Do we need this, we already have a lot of data of line into $histo

            $out.="<tr ".$bc[$var].">";
			
            // Done or todo
            if ($donetodo)
            {
                $out.='<td class="nowrap">';
                $out.='</td>';
            }
            
            // Ref
            $out.='<td class="nowrap">';
            $out.=$actionstatic->getNomUrl(1, -1);
            $out.='</td>';
            
            // Title
            $out.='<td>';
            if (isset($histo[$key]['type']) && $histo[$key]['type']=='action')
            {
                $actionstatic->type_code=$histo[$key]['acode'];
                $transcode=$langs->trans("Action".$histo[$key]['acode']);
                $libelle=($transcode!="Action".$histo[$key]['acode']?$transcode:$histo[$key]['alabel']);
                //$actionstatic->libelle=$libelle;
                $libelle=$histo[$key]['note'];
                $actionstatic->id=$histo[$key]['id'];
                $out.=dol_trunc($libelle,120);
            }
            if (isset($histo[$key]['type']) && $histo[$key]['type']=='mailing')
            {
                $out.='<a href="'.DOL_URL_ROOT.'/comm/mailing/card.php?id='.$histo[$key]['id'].'">'.img_object($langs->trans("ShowEMailing"),"email").' ';
                $transcode=$langs->trans("Action".$histo[$key]['acode']);
                $libelle=($transcode!="Action".$histo[$key]['acode']?$transcode:'Send mass mailing');
                $out.=dol_trunc($libelle,120);
            }
            $out.='</td>';
			
            // Date
            $out.='<td class="nowrap">';
            $out.=dol_print_date($histo[$key]['datestart'],'dayhour');
            if ($histo[$key]['dateend'] && $histo[$key]['dateend'] != $histo[$key]['datestart'])
            {
                $tmpa=dol_getdate($histo[$key]['datestart'],true);
                $tmpb=dol_getdate($histo[$key]['dateend'],true);
                if ($tmpa['mday'] == $tmpb['mday'] && $tmpa['mon'] == $tmpb['mon'] && $tmpa['year'] == $tmpb['year']) $out.='-'.dol_print_date($histo[$key]['dateend'],'hour');
                else $out.='-'.dol_print_date($histo[$key]['dateend'],'dayhour');
            }
            $late=0;
            if ($histo[$key]['percent'] == 0 && $histo[$key]['datestart'] && $histo[$key]['datestart'] < ($now - $delay_warning)) $late=1;
            if ($histo[$key]['percent'] == 0 && ! $histo[$key]['datestart'] && $histo[$key]['dateend'] && $histo[$key]['datestart'] < ($now - $delay_warning)) $late=1;
            if ($histo[$key]['percent'] > 0 && $histo[$key]['percent'] < 100 && $histo[$key]['dateend'] && $histo[$key]['dateend'] < ($now - $delay_warning)) $late=1;
            if ($histo[$key]['percent'] > 0 && $histo[$key]['percent'] < 100 && ! $histo[$key]['dateend'] && $histo[$key]['datestart'] && $histo[$key]['datestart'] < ($now - $delay_warning)) $late=1;
            if ($late) $out.=img_warning($langs->trans("Late")).' ';
            $out.="</td>\n";
            
            // Type
			$out.='<td>';
			if (! empty($conf->global->AGENDA_USE_EVENT_TYPE))
			{
    			if ($histo[$key]['apicto']) $out.=img_picto('', $histo[$key]['apicto']);
    			else {
    			    if ($histo[$key]['acode'] == 'AC_TEL')   $out.=img_picto('', 'object_phoning').' ';
    			    if ($histo[$key]['acode'] == 'AC_FAX')   $out.=img_picto('', 'object_phoning_fax').' ';
    			    if ($histo[$key]['acode'] == 'AC_EMAIL') $out.=img_picto('', 'object_email').' ';
    			}
			    $out.=$actionstatic->type;
			}
			else {
			    $typelabel = $actionstatic->type;
			    if ($histo[$key]['acode'] != 'AC_OTH_AUTO') $typelabel = $langs->trans("ActionAC_MANUAL"); 
			    $out.=$typelabel;
			}
			$out.='</td>';

            // Title of event
            //$out.='<td>'.dol_trunc($histo[$key]['note'], 40).'</td>';

            // Objet lie
            // TODO mutualize/uniformize
            $out.='<td>';
            //var_dump($histo[$key]['elementtype']);
            if (isset($histo[$key]['elementtype']))
            {
            	if ($histo[$key]['elementtype'] == 'propal' && ! empty($conf->propal->enabled))
            	{
            		//$propalstatic->ref=$langs->trans("ProposalShort");
            		//$propalstatic->id=$histo[$key]['fk_element'];
                    if ($propalstatic->fetch($histo[$key]['fk_element'])>0) {
                        $propalstatic->type=$histo[$key]['ftype'];
                        $out.=$propalstatic->getNomUrl(1);
                    } else {
                        $out.= $langs->trans("ProposalDeleted");
                    }
             	}
            	elseif (($histo[$key]['elementtype'] == 'order' || $histo[$key]['elementtype'] == 'commande') && ! empty($conf->commande->enabled))
            	{
            		//$orderstatic->ref=$langs->trans("Order");
            		//$orderstatic->id=$histo[$key]['fk_element'];
                    if ($orderstatic->fetch($histo[$key]['fk_element'])>0) {
                        $orderstatic->type=$histo[$key]['ftype'];
                        $out.=$orderstatic->getNomUrl(1);
                    } else {
                        $out.= $langs->trans("OrderDeleted");
                    }
             	}
            	elseif (($histo[$key]['elementtype'] == 'invoice' || $histo[$key]['elementtype'] == 'facture') && ! empty($conf->facture->enabled))
            	{
            		//$facturestatic->ref=$langs->trans("Invoice");
            		//$facturestatic->id=$histo[$key]['fk_element'];
                    if ($facturestatic->fetch($histo[$key]['fk_element'])>0) {
                        $facturestatic->type=$histo[$key]['ftype'];
                        $out.=$facturestatic->getNomUrl(1,'compta');
                    } else {
                        $out.= $langs->trans("InvoiceDeleted");
                    }
            	}
            	else $out.='&nbsp;';
            }
            else $out.='&nbsp;';
            $out.='</td>';

            // Contact pour cette action
            if (! empty($objcon->id) && isset($histo[$key]['contact_id']) && $histo[$key]['contact_id'] > 0)
            {
                $contactstatic->lastname=$histo[$key]['lastname'];
                $contactstatic->firstname=$histo[$key]['firstname'];
                $contactstatic->id=$histo[$key]['contact_id'];
                $out.='<td width="120">'.$contactstatic->getNomUrl(1,'',10).'</td>';
            }
            else
            {
                $out.='<td>&nbsp;</td>';
            }

            // Auteur
            $out.='<td class="nowrap" width="80">';
            //$userstatic->id=$histo[$key]['userid'];
            //$userstatic->login=$histo[$key]['login'];
            //$out.=$userstatic->getLoginUrl(1);
            $userstatic->fetch($histo[$key]['userid']);
            $out.=$userstatic->getNomUrl(1);
            $out.='</td>';

            // Statut
            $out.='<td class="nowrap" align="center">'.$actionstatic->LibStatut($histo[$key]['percent'],3,1,$histo[$key]['datestart']).'</td>';

            // Actions
            $out.='<td></td>';
            
            $out.="</tr>\n";
            $i++;
        }
        $out.="</table>\n";
        $out.="</div>\n";
    }

    $out.='</form>';
    
    if ($noprint) return $out;
    else print $out;
}

/**
 * 		Show html area for list of subsidiaries
 *
 *		@param	Conf		$conf		Object conf
 * 		@param	Translate	$langs		Object langs
 * 		@param	DoliDB		$db			Database handler
 * 		@param	Societe		$object		Third party object
 * 		@return	void
 */
function show_subsidiaries($conf,$langs,$db,$object)
{
	global $user;
	global $bc;

	$i=-1;

	$sql = "SELECT s.rowid, s.nom as name, s.address, s.zip, s.town, s.code_client, s.canvas";
	$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
	$sql.= " WHERE s.parent = ".$object->id;
	$sql.= " AND s.entity IN (".getEntity('societe', 1).")";
	$sql.= " ORDER BY s.nom";

	$result = $db->query($sql);
	$num = $db->num_rows($result);

	if ($num)
	{
		$socstatic = new Societe($db);

		print load_fiche_titre($langs->trans("Subsidiaries"), '', '');
		print "\n".'<table class="noborder" width="100%">'."\n";

		print '<tr class="liste_titre"><td>'.$langs->trans("Company").'</td>';
		print '<td>'.$langs->trans("Address").'</td><td>'.$langs->trans("Zip").'</td>';
		print '<td>'.$langs->trans("Town").'</td><td>'.$langs->trans("CustomerCode").'</td>';
		print "<td>&nbsp;</td>";
		print "</tr>";

		$i=0;
		$var=true;

		while ($i < $num)
		{
			$obj = $db->fetch_object($result);
			$var = !$var;

			print "<tr ".$bc[$var].">";

			print '<td>';
			$socstatic->id = $obj->rowid;
			$socstatic->name = $obj->name;
			$socstatic->canvas = $obj->canvas;
			print $socstatic->getNomUrl(1);
			print '</td>';

			print '<td>'.$obj->address.'</td>';
			print '<td>'.$obj->zip.'</td>';
			print '<td>'.$obj->town.'</td>';
			print '<td>'.$obj->code_client.'</td>';

			print '<td align="center">';
			print '<a href="'.DOL_URL_ROOT.'/societe/soc.php?socid='.$obj->rowid.'&amp;action=edit">';
			print img_edit();
			print '</a></td>';

			print "</tr>\n";
			$i++;
		}
		print "\n</table>\n";
	}

	print "<br>\n";

	return $i;
}

