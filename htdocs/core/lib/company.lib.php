<?php
/* Copyright (C) 2006-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2007      Patrick Raguin       <patrick.raguin@gmail.com>
 * Copyright (C) 2010      Regis Houssin        <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 * @param 	Object	$object		Object company shown
 * @return 	array				Array of tabs
 */
function societe_prepare_head($object)
{
    global $langs, $conf, $user;
    $h = 0;
    $head = array();

    $head[$h][0] = DOL_URL_ROOT.'/societe/soc.php?socid='.$object->id;
    $head[$h][1] = $langs->trans("Card");
    $head[$h][2] = 'card';
    $h++;

    // TODO Remove tests on object->object. Functions must be called with a company object directly
    if (($object->client==2 || $object->client==3
    || (isset($object->object) && $object->object->client==2) || (isset($object->object) && $object->object->client==3)) && empty($conf->global->SOCIETE_DISABLE_PROSPECTS))
    {
        $head[$h][0] = DOL_URL_ROOT.'/comm/prospect/fiche.php?socid='.$object->id;
        $head[$h][1] = $langs->trans("Prospect");
        $head[$h][2] = 'prospect';
        $h++;
    }
    if ($object->client==1 || $object->client==3 || (is_object($object->object) && $object->object->client==1) || (is_object($object->object) && $object->object->client==3))
    {
        $head[$h][0] = DOL_URL_ROOT.'/comm/fiche.php?socid='.$object->id;
        $head[$h][1] = $langs->trans("Customer");
        $head[$h][2] = 'customer';
        $h++;
    }
    if (! empty($conf->fournisseur->enabled) && ($object->fournisseur || (isset($object->object) && $object->object->fournisseur)) && ! empty($user->rights->fournisseur->lire))
    {
        $head[$h][0] = DOL_URL_ROOT.'/fourn/fiche.php?socid='.$object->id;
        $head[$h][1] = $langs->trans("Supplier");
        $head[$h][2] = 'supplier';
        $h++;
    }
    if (! empty($conf->agenda->enabled))
    {
    	$head[$h][0] = DOL_URL_ROOT.'/societe/agenda.php?socid='.$object->id;
    	$head[$h][1] = $langs->trans("Agenda");
    	$head[$h][2] = 'agenda';
    	$h++;
    }
    //show categorie tab
    if (! empty($conf->categorie->enabled))
    {
        $type = 2;
        if ($object->fournisseur) $type = 1;
        $head[$h][0] = DOL_URL_ROOT.'/categories/categorie.php?socid='.$object->id."&type=".$type;
        $head[$h][1] = $langs->trans('Categories');
        $head[$h][2] = 'category';
        $h++;
    }

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
    complete_head_from_modules($conf,$langs,$object,$head,$h,'thirdparty');

    if ($user->societe_id == 0)
    {
    	// Notes
        $head[$h][0] = DOL_URL_ROOT.'/societe/socnote.php?socid='.$object->id;
        $head[$h][1] = $langs->trans("Note");
        $head[$h][2] = 'note';
        $h++;

        // Attached files
        $head[$h][0] = DOL_URL_ROOT.'/societe/document.php?socid='.$object->id;
        $head[$h][1] = $langs->trans("Documents");
        $head[$h][2] = 'document';
        $h++;

        // Notifications
        if (! empty($conf->notification->enabled))
        {
        	$head[$h][0] = DOL_URL_ROOT.'/societe/notify/fiche.php?socid='.$object->id;
        	$head[$h][1] = $langs->trans("Notifications");
        	$head[$h][2] = 'notify';
        	$h++;
        }
    }

    // Log
    $head[$h][0] = DOL_URL_ROOT.'/societe/info.php?socid='.$object->id;
    $head[$h][1] = $langs->trans("Info");
    $head[$h][2] = 'info';
    $h++;

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

    if (empty($conf->global->SOCIETE_DISABLE_BANKACCOUNT))
    {
	    $head[$h][0] = DOL_URL_ROOT .'/societe/rib.php?socid='.$object->id;
	    $head[$h][1] = $langs->trans("BankAccount")." $account->number";
	    $head[$h][2] = 'rib';
	    $h++;
    }

    if (empty($conf->global->SOCIETE_DISABLE_PARENTCOMPANY))
    {
	    $head[$h][0] = 'lien.php?socid='.$object->id;
	    $head[$h][1] = $langs->trans("ParentCompany");
	    $head[$h][2] = 'links';
	    $h++;
    }

    $head[$h][0] = 'commerciaux.php?socid='.$object->id;
    $head[$h][1] = $langs->trans("SalesRepresentative");
    $head[$h][2] = 'salesrepresentative';
    $h++;

    return $head;
}



/**
 *  Return array head with list of tabs to view object informations.
 *
 *  @param	Object	$object		Thirdparty
 *  @return	array   	        head array with tabs
 */
function societe_admin_prepare_head($object)
{
    global $langs, $conf, $user;

    $h = 0;
    $head = array();

    $head[$h][0] = DOL_URL_ROOT.'/societe/admin/societe.php';
    $head[$h][1] = $langs->trans("Miscellanous");
    $head[$h][2] = 'general';
    $h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
    complete_head_from_modules($conf,$langs,$object,$head,$h,'company_admin');

    $head[$h][0] = DOL_URL_ROOT.'/societe/admin/societe_extrafields.php';
    $head[$h][1] = $langs->trans("ExtraFields");
    $head[$h][2] = 'attributes';
    $h++;

    complete_head_from_modules($conf,$langs,$object,$head,$h,'company_admin','remove');

    return $head;
}



/**
 *    Return country label, code or id from an id or a code
 *
 *    @param      int		$id            	Id or code of country
 *    @param      int		$withcode   	'0'=Return label,
 *    										'1'=Return code + label,
 *    										'2'=Return code from id,
 *    										'3'=Return id from code,
 * 	   										'all'=Return array('id'=>,'code'=>,'label'=>)
 *    @param      DoliDB	$dbtouse       	Database handler (using in global way may fail because of conflicts with some autoload features)
 *    @param      Translate	$outputlangs	Langs object for output translation
 *    @param      int		$entconv       	0=Return value without entities and not converted to output charset
 *    @return     mixed       				String with country code or translated country name or Array('id','code','label')
 */
function getCountry($id,$withcode='',$dbtouse=0,$outputlangs='',$entconv=1)
{
    global $db,$langs;

    if (! is_object($dbtouse)) $dbtouse=$db;
    if (! is_object($outputlangs)) $outputlangs=$langs;

    $sql = "SELECT rowid, code, libelle FROM ".MAIN_DB_PREFIX."c_pays";
    if (is_numeric($id)) $sql.= " WHERE rowid=".$id;
    else $sql.= " WHERE code='".$id."'";

    dol_syslog("Company.lib::getCountry sql=".$sql);
    $resql=$dbtouse->query($sql);
    if ($resql)
    {
        $obj = $dbtouse->fetch_object($resql);
        if ($obj)
        {
            $label=((! empty($obj->libelle) && $obj->libelle!='-')?$obj->libelle:'');
            if (is_object($outputlangs))
            {
                $outputlangs->load("dict");
                if ($entconv) $label=($obj->code && ($outputlangs->trans("Country".$obj->code)!="Country".$obj->code))?$outputlangs->trans("Country".$obj->code):$label;
                else $label=($obj->code && ($outputlangs->transnoentitiesnoconv("Country".$obj->code)!="Country".$obj->code))?$outputlangs->transnoentitiesnoconv("Country".$obj->code):$label;
            }
            if ($withcode == 1) return $label?"$obj->code - $label":"$obj->code";
            else if ($withcode == 2) return $obj->code;
            else if ($withcode == 3) return $obj->rowid;
            else if ($withcode === 'all') return array('id'=>$obj->rowid,'code'=>$obj->code,'label'=>$label);
            else return $label;
        }
        else
        {
            return "NotDefined";
        }
    }
    else dol_print_error($dbtouse,'');
}

/**
 *    Return state translated from an id
 *
 *    @param	int		$id         id of state (province/departement)
 *    @param    int		$withcode   '0'=Return label,
 *    								'1'=Return string code + label,
 *    						  		'2'=Return code,
 *    						  		'all'=return array('id'=>,'code'=>,'label'=>)
 *    @param	DoliDB	$dbtouse	Database handler (using in global way may fail because of conflicts with some autoload features)
 *    @return   string      		String with state code or translated state name
 */
function getState($id,$withcode='',$dbtouse=0)
{
    global $db,$langs;

    if (! is_object($dbtouse)) $dbtouse=$db;

    $sql = "SELECT rowid, code_departement as code, nom as label FROM ".MAIN_DB_PREFIX."c_departements";
    $sql.= " WHERE rowid=".$id;

    dol_syslog("Company.lib::getState sql=".$sql);
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
            return $langs->trans("NotDefined");
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

    dol_syslog("Company.lib::getFormeJuridiqueLabel sql=".$sql);
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
 *      @return	void
 */
function show_projects($conf,$langs,$db,$object,$backtopage='')
{
    global $user;

    $i = -1 ;

    if ($conf->projet->enabled && $user->rights->projet->lire)
    {
        $langs->load("projects");

        $buttoncreate='';
        if ($conf->projet->enabled && $user->rights->projet->creer)
        {
            //$buttoncreate='<a class="butAction" href="'.DOL_URL_ROOT.'/projet/fiche.php?socid='.$object->id.'&action=create&amp;backtopage='.urlencode($backtopage).'">'.$langs->trans("AddProject").'</a>';
			$buttoncreate='<a class="addnewrecord" href="'.DOL_URL_ROOT.'/projet/fiche.php?socid='.$object->id.'&amp;action=create&amp;backtopage='.urlencode($backtopage).'">'.$langs->trans("AddProject").' '.img_picto($langs->trans("AddProject"),'filenew').'</a>'."\n";
        }

        print "\n";
        print_fiche_titre($langs->trans("ProjectsDedicatedToThisThirdParty"),$buttoncreate,'');
        print "\n".'<table class="noborder" width=100%>';

        $sql  = "SELECT p.rowid,p.title,p.ref,p.public, p.dateo as do, p.datee as de";
        $sql .= " FROM ".MAIN_DB_PREFIX."projet as p";
        $sql .= " WHERE p.fk_soc = ".$object->id;
        $sql .= " ORDER BY p.dateo DESC";

        $result=$db->query($sql);
        if ($result)
        {
            $num = $db->num_rows($result);

            print '<tr class="liste_titre">';
            print '<td>'.$langs->trans("Ref").'</td><td>'.$langs->trans("Name").'</td><td align="center">'.$langs->trans("DateStart").'</td><td align="center">'.$langs->trans("DateEnd").'</td>';
            print '</tr>';

            if ($num > 0)
            {
                require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");

                $projectstatic = new Project($db);

                $i=0;
                $var=true;
                while ($i < $num)
                {
                    $obj = $db->fetch_object($result);
                    $projectstatic->fetch($obj->rowid);

                    // To verify role of users
                    $userAccess = $projectstatic->restrictedProjectArea($user);

                    if ($user->rights->projet->lire && $userAccess > 0)
                    {
                        $var = !$var;
                        print "<tr $bc[$var]>";

                        // Ref
                        print '<td><a href="'.DOL_URL_ROOT.'/projet/fiche.php?id='.$obj->rowid.'">'.img_object($langs->trans("ShowProject"),($obj->public?'projectpub':'project'))." ".$obj->ref.'</a></td>';
                        // Label
                        print '<td>'.$obj->title.'</td>';
                        // Date start
                        print '<td align="center">'.dol_print_date($db->jdate($obj->do),"day").'</td>';
                        // Date end
                        print '<td align="center">'.dol_print_date($db->jdate($obj->de),"day").'</td>';

                        print '</tr>';
                    }
                    $i++;
                }
            }
            else
            {
                print '<tr><td colspan="3">'.$langs->trans("None").'</td></tr>';
            }
            $db->free($result);
        }
        else
        {
            dol_print_error($db);
        }
        print "</table>";

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
 * 		@param	Object		$object		Third party object
 *      @param  string		$backtopage	Url to go once contact is created
 *      @return	void
 */
function show_contacts($conf,$langs,$db,$object,$backtopage='')
{
    global $user;
    global $bc;

    $i=-1;

    $contactstatic = new Contact($db);

    if ($conf->clicktodial->enabled)
    {
        $user->fetch_clicktodial(); // lecture des infos de clicktodial
    }

    $buttoncreate='';
    if ($user->rights->societe->contact->creer)
    {
    	$addcontact = (! empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT) ? $langs->trans("AddContact") : $langs->trans("AddContactAddress"));
		$buttoncreate='<a class="addnewrecord" href="'.DOL_URL_ROOT.'/contact/fiche.php?socid='.$object->id.'&amp;action=create&amp;backtopage='.urlencode($backtopage).'">'.$addcontact.' '.img_picto($addcontact,'filenew').'</a>'."\n";
    }

    print "\n";
    
    $title = (! empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT) ? $langs->trans("ContactsForCompany") : $langs->trans("ContactsAddressesForCompany"));
    print_fiche_titre($title,$buttoncreate,'');

    print "\n".'<table class="noborder" width="100%">'."\n";

    print '<tr class="liste_titre"><td>'.$langs->trans("Name").'</td>';
    print '<td>'.$langs->trans("Poste").'</td><td>'.$langs->trans("Tel").'</td>';
    print '<td>'.$langs->trans("Fax").'</td><td>'.$langs->trans("EMail").'</td>';
    print "<td>&nbsp;</td>";
    if ($conf->agenda->enabled && $user->rights->agenda->myactions->create)
    {
        print '<td>&nbsp;</td>';
    }
    print "</tr>";

    $sql = "SELECT p.rowid, p.name, p.firstname, p.poste, p.phone, p.fax, p.email, p.note ";
    $sql .= " FROM ".MAIN_DB_PREFIX."socpeople as p";
    $sql .= " WHERE p.fk_soc = ".$object->id;
    $sql .= " ORDER by p.datec";

    $result = $db->query($sql);
    $num = $db->num_rows($result);

    if ($num)
    {
        $i=0;
        $var=true;

        while ($i < $num)
        {
            $obj = $db->fetch_object($result);
            $var = !$var;

            print "<tr ".$bc[$var].">";

            print '<td>';
            $contactstatic->id = $obj->rowid;
            $contactstatic->name = $obj->name;
            $contactstatic->firstname = $obj->firstname;
            print $contactstatic->getNomUrl(1);
            print '</td>';

            print '<td>'.$obj->poste.'</td>';

            // Lien click to dial
            print '<td>';
            print dol_print_phone($obj->phone,$obj->pays_code,$obj->rowid,$object->id,'AC_TEL');
            print '</td>';
            print '<td>';
            print dol_print_phone($obj->fax,$obj->pays_code,$obj->rowid,$object->id,'AC_FAX');
            print '</td>';
            print '<td>';
            print dol_print_email($obj->email,$obj->rowid,$object->id,'AC_EMAIL');
            print '</td>';

            if ($conf->agenda->enabled && $user->rights->agenda->myactions->create)
            {
                print '<td align="center"><a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?action=create&actioncode=AC_RDV&contactid='.$obj->rowid.'&socid='.$object->id.'&backtopage='.urlencode($backtopage).'">';
                print img_object($langs->trans("Rendez-Vous"),"action");
                print '</a></td>';
            }

            if ($user->rights->societe->contact->creer)
            {
                print '<td align="right">';
                print '<a href="'.DOL_URL_ROOT.'/contact/fiche.php?action=edit&amp;id='.$obj->rowid.'&amp;backtopage='.urlencode($backtopage).'">';
                print img_edit();
                print '</a></td>';
            }

            print "</tr>\n";
            $i++;
        }
    }
    else
    {
        //print "<tr ".$bc[$var].">";
        //print '<td>'.$langs->trans("NoContactsYetDefined").'</td>';
        //print "</tr>\n";
    }
    print "\n</table>\n";

    print "<br>\n";

    return $i;
}

/**
 * 		Show html area for list of addresses
 *
 *		@param	Conf		$conf		Object conf
 * 		@param	Translate	$langs		Object langs
 * 		@param	DoliDB		$db			Database handler
 * 		@param	Object		$object		Third party object
 *      @param  string		$backtopage	Url to go once address is created
 *      @return	void
 */
function show_addresses($conf,$langs,$db,$object,$backtopage='')
{
	global $user;
	global $bc;
	
	require_once(DOL_DOCUMENT_ROOT."/societe/class/address.class.php");

	$addressstatic = new Address($db);
	$num = $addressstatic->fetch_lines($object->id);

	$buttoncreate='';
	if ($user->rights->societe->creer)
	{
		$buttoncreate='<a class="addnewrecord" href="'.DOL_URL_ROOT.'/comm/address.php?socid='.$object->id.'&amp;action=create&amp;backtopage='.urlencode($backtopage).'">'.$langs->trans("AddAddress").' '.img_picto($langs->trans("AddAddress"),'filenew').'</a>'."\n";
	}

	print "\n";
	print_fiche_titre($langs->trans("AddressesForCompany"),$buttoncreate,'');

	print "\n".'<table class="noborder" width="100%">'."\n";

	print '<tr class="liste_titre"><td>'.$langs->trans("Label").'</td>';
	print '<td>'.$langs->trans("CompanyName").'</td>';
	print '<td>'.$langs->trans("Town").'</td>';
	print '<td>'.$langs->trans("Country").'</td>';
	print '<td>'.$langs->trans("Tel").'</td>';
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
 * 		@param	Conf		$conf		Object conf
 * 		@param	Translate	$langs		Object langs
 * 		@param	DoliDB		$db			Object db
 * 		@param	Object		$object		Object third party or member
 * 		@param	Contact		$objcon		Object contact
 *      @param  int			$noprint	Return string but does not output it
 *      @return	void
 */
function show_actions_todo($conf,$langs,$db,$object,$objcon='',$noprint=0)
{
    global $bc,$user;

    // Check parameters
    if (! is_object($object)) dol_print_error('','BadParameter');

    $now=dol_now();
    $out='';

    if ($conf->agenda->enabled)
    {
        require_once(DOL_DOCUMENT_ROOT."/comm/action/class/actioncomm.class.php");
        $actionstatic=new ActionComm($db);
        $userstatic=new User($db);
        $contactstatic = new Contact($db);

        $out.="\n";
        $out.='<table width="100%" class="noborder">';
        $out.='<tr class="liste_titre">';
        $out.='<td colspan="2">';
        if (get_class($object) == 'Societe') $out.='<a href="'.DOL_URL_ROOT.'/comm/action/listactions.php?socid='.$object->id.'&amp;status=todo">';
        $out.=$langs->trans("ActionsToDoShort");
        if (get_class($object) == 'Societe') $out.='</a>';
        $out.='</td>';
        $out.='<td colspan="5" align="right">';
		$permok=$user->rights->agenda->myactions->create;
        if (($object->id || $objcon->id) && $permok)
		{
            $out.='<a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?action=create';
            if (get_class($object) == 'Societe') $out.='&amp;socid='.$object->id;
            $out.='&amp;contactid='.$objcon->id.'&amp;backtopage=1&amp;percentage=-1">';
    		$out.=$langs->trans("AddAnAction").' ';
    		$out.=img_picto($langs->trans("AddAnAction"),'filenew');
    		$out.="</a>";
		}
        $out.='</td>';
        $out.='</tr>';

        $sql = "SELECT a.id, a.label,";
        $sql.= " a.datep as dp,";
        $sql.= " a.datea as da,";
        $sql.= " a.percent,";
        $sql.= " a.fk_user_author, a.fk_contact,";
        $sql.= " a.fk_element, a.elementtype,";
        $sql.= " c.code as acode, c.libelle,";
        $sql.= " u.login, u.rowid";
        if (get_class($object) == 'Adherent') $sql.= ", m.nom as name, m.prenom as firstname";
        if (get_class($object) == 'Societe')  $sql.= ", sp.name, sp.firstname";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_actioncomm as c, ".MAIN_DB_PREFIX."user as u, ".MAIN_DB_PREFIX."actioncomm as a";
        if (get_class($object) == 'Adherent') $sql.= ", ".MAIN_DB_PREFIX."adherent as m";
        if (get_class($object) == 'Societe')  $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople as sp ON a.fk_contact = sp.rowid";
        $sql.= " WHERE u.rowid = a.fk_user_author";
        $sql.= " AND a.entity IN (".getEntity('actioncomm').")";
        if (get_class($object) == 'Adherent') $sql.= " AND a.fk_element = m.rowid AND a.elementtype = 'member'";
        if (get_class($object) == 'Adherent' && $object->id) $sql.= " AND a.fk_element = ".$object->id;
        if (get_class($object) == 'Societe'  && $object->id) $sql.= " AND a.fk_soc = ".$object->id;
        if (is_object($objcon) && $objcon->id) $sql.= " AND a.fk_contact = ".$objcon->id;
        $sql.= " AND c.id=a.fk_action";
        $sql.= " AND ((a.percent >= 0 AND a.percent < 100) OR (a.percent = -1 AND a.datep > '".$db->idate($now)."'))";
        $sql.= " ORDER BY a.datep DESC, a.id DESC";

        dol_syslog("company.lib::show_actions_todo sql=".$sql);
        $result=$db->query($sql);
        if ($result)
        {
            $i = 0 ;
            $num = $db->num_rows($result);
            $var=true;

            if ($num)
            {
                while ($i < $num)
                {
                    $var = !$var;

                    $obj = $db->fetch_object($result);

                    $datep=$db->jdate($obj->dp);

                    $out.="<tr ".$bc[$var].">";

                    $out.='<td width="120" align="left" nowrap="nowrap">'.dol_print_date($datep,'dayhour')."</td>\n";

                    // Picto warning
                    $out.='<td width="16">';
                    if ($obj->percent >= 0 && $datep && $datep < ($now - ($conf->global->MAIN_DELAY_ACTIONS_TODO *60*60*24)) ) $out.=' '.img_warning($langs->trans("Late"));
                    else $out.='&nbsp;';
                    $out.='</td>';

                    $actionstatic->type_code=$obj->acode;
                    $transcode=$langs->trans("Action".$obj->acode);
                    $libelle=($transcode!="Action".$obj->acode?$transcode:$obj->libelle);
                    //$actionstatic->libelle=$libelle;
                    $actionstatic->libelle=$obj->label;
                    $actionstatic->id=$obj->id;
                    //$out.='<td width="140">'.$actionstatic->getNomUrl(1,16).'</td>';

                    // Title of event
                    //$out.='<td colspan="2">'.dol_trunc($obj->label,40).'</td>';
                    $out.='<td colspan="2">'.$actionstatic->getNomUrl(1,40).'</td>';

                    // Contact pour cette action
                    if (! $objcon->id && $obj->fk_contact > 0)
                    {
                        $contactstatic->name=$obj->name;
                        $contactstatic->firstname=$obj->firstname;
                        $contactstatic->id=$obj->fk_contact;
                        $out.='<td width="120">'.$contactstatic->getNomUrl(1,'',10).'</td>';
                    }
                    else
                    {
                        $out.='<td>&nbsp;</td>';
                    }

                    $out.='<td width="80" nowrap="nowrap">';
                    $userstatic->id=$obj->fk_user_author;
                    $userstatic->login=$obj->login;
                    $out.=$userstatic->getLoginUrl(1);
                    $out.='</td>';

                    // Statut
                    $out.='<td nowrap="nowrap" width="20">'.$actionstatic->LibStatut($obj->percent,3).'</td>';

                    $out.="</tr>\n";
                    $i++;
                }
            }
            else
            {
                // Aucun action a faire

            }
            $db->free($result);
        }
        else
        {
            dol_print_error($db);
        }
        $out.="</table>\n";

        $out.="<br>\n";
    }

    if ($noprint) return $out;
    else print $out;
}

/**
 *    	Show html area with actions done
 *
 * 		@param	Conf		$conf		Object conf
 * 		@param	Translate	$langs		Object langs
 * 		@param	DoliDB		$db			Object db
 * 		@param	Object		$object		Object third party or member
 * 		@param	Contact		$objcon		Object contact
 *      @param  int			$noprint    Return string but does not output it
 *      @return	void
 * TODO change function to be able to list event linked to an object.
 */
function show_actions_done($conf,$langs,$db,$object,$objcon='',$noprint=0)
{
    global $bc,$user;

    // Check parameters
    if (! is_object($object)) dol_print_error('','BadParameter');

    $out='';
    $histo=array();
    $numaction = 0 ;
    $now=dol_now();

    if ($conf->agenda->enabled)
    {
        // Recherche histo sur actioncomm
        $sql = "SELECT a.id, a.label,";
        $sql.= " a.datep as dp,";
        $sql.= " a.datep2 as dp2,";
        $sql.= " a.note, a.percent,";
        $sql.= " a.fk_element, a.elementtype,";
        $sql.= " a.fk_user_author, a.fk_contact,";
        $sql.= " c.code as acode, c.libelle,";
        $sql.= " u.login, u.rowid as user_id";
        if (get_class($object) == 'Adherent') $sql.= ", m.nom as name, m.prenom as firstname";
        if (get_class($object) == 'Societe')  $sql.= ", sp.name, sp.firstname";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_actioncomm as c, ".MAIN_DB_PREFIX."user as u, ".MAIN_DB_PREFIX."actioncomm as a";
        if (get_class($object) == 'Adherent') $sql.= ", ".MAIN_DB_PREFIX."adherent as m";
        if (get_class($object) == 'Societe')  $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople as sp ON a.fk_contact = sp.rowid";
        $sql.= " WHERE u.rowid = a.fk_user_author";
        $sql.= " AND a.entity IN (".getEntity('actioncomm').")";
        if (get_class($object) == 'Adherent') $sql.= " AND a.fk_element = m.rowid AND a.elementtype = 'member'";
        if (get_class($object) == 'Adherent' && $object->id) $sql.= " AND a.fk_element = ".$object->id;
        if (get_class($object) == 'Societe'  && $object->id) $sql.= " AND a.fk_soc = ".$object->id;
        if (is_object($objcon) && $objcon->id) $sql.= " AND a.fk_contact = ".$objcon->id;
        $sql.= " AND c.id=a.fk_action";
        $sql.= " AND (a.percent = 100 OR (a.percent = -1 AND a.datep <= '".$db->idate($now)."'))";
        $sql.= " ORDER BY a.datep DESC, a.id DESC";

        dol_syslog("company.lib::show_actions_done sql=".$sql, LOG_DEBUG);
        $resql=$db->query($sql);
        if ($resql)
        {
            $i = 0 ;
            $num = $db->num_rows($resql);
            $var=true;
            while ($i < $num)
            {
                $obj = $db->fetch_object($resql);
                $histo[$numaction]=array('type'=>'action','id'=>$obj->id,'datestart'=>$db->jdate($obj->dp),'date'=>$db->jdate($obj->dp2),'note'=>$obj->label,'percent'=>$obj->percent,
				'acode'=>$obj->acode,'libelle'=>$obj->libelle,
				'userid'=>$obj->user_id,'login'=>$obj->login,
				'contact_id'=>$obj->fk_contact,'name'=>$obj->name,'firstname'=>$obj->firstname,
				'fk_element'=>$obj->fk_element,'elementtype'=>$obj->elementtype);
                $numaction++;
                $i++;
            }
        }
        else
        {
            dol_print_error($db);
        }
    }

    if ($conf->mailing->enabled && ! empty($objcon->email))
    {
        $langs->load("mails");

        // Recherche histo sur mailing
        $sql = "SELECT m.rowid as id, mc.date_envoi as da, m.titre as note, '100' as percentage,";
        $sql.= " 'AC_EMAILING' as acode,";
        $sql.= " u.rowid as user_id, u.login";	// User that valid action
        $sql.= " FROM ".MAIN_DB_PREFIX."mailing as m, ".MAIN_DB_PREFIX."mailing_cibles as mc, ".MAIN_DB_PREFIX."user as u";
        $sql.= " WHERE mc.email = '".$db->escape($objcon->email)."'";	// Search is done on email.
        $sql.= " AND mc.statut = 1";
        $sql.= " AND u.rowid = m.fk_user_valid";
        $sql.= " AND mc.fk_mailing=m.rowid";
        $sql.= " ORDER BY mc.date_envoi DESC, m.rowid DESC";

        dol_syslog("company.lib::show_actions_done sql=".$sql, LOG_DEBUG);
        $resql=$db->query($sql);
        if ($resql)
        {
            $i = 0 ;
            $num = $db->num_rows($resql);
            $var=true;
            while ($i < $num)
            {
                $obj = $db->fetch_object($resql);
                $histo[$numaction]=array('type'=>'mailing','id'=>$obj->id,'date'=>$db->jdate($obj->da),'note'=>$obj->note,'percent'=>$obj->percentage,
				'acode'=>$obj->acode,'libelle'=>$obj->libelle,
				'userid'=>$obj->user_id,'login'=>$obj->login,
				'contact_id'=>$obj->contact_id);
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


    if ($conf->agenda->enabled || ($conf->mailing->enabled && ! empty($objcon->email)))
    {
        require_once(DOL_DOCUMENT_ROOT."/comm/action/class/actioncomm.class.php");
        require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
        require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
        require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
        $actionstatic=new ActionComm($db);
        $userstatic=new User($db);
        $contactstatic = new Contact($db);

        // TODO uniformize
        $propalstatic=new Propal($db);
        $orderstatic=new Commande($db);
        $facturestatic=new Facture($db);

        $out.="\n";
        $out.='<table class="noborder" width="100%">';
        $out.='<tr class="liste_titre">';
        $out.='<td colspan="2">';
        if (get_class($object) == 'Societe') $out.='<a href="'.DOL_URL_ROOT.'/comm/action/listactions.php?socid='.$object->id.'&amp;status=done">';
        $out.=$langs->trans("ActionsDoneShort");
        if (get_class($object) == 'Societe') $out.='</a>';
        $out.='</td>';
        $out.='<td colspan="5" align="right">';
		$permok=$user->rights->agenda->myactions->create;
        if (($object->id || $objcon->id) && $permok)
		{
            $out.='<a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?action=create';
            if (get_class($object) == 'Societe') $out.='&amp;socid='.$object->id;
            $out.='&amp;contactid='.$objcon->id.'&amp;backtopage=1&amp;percentage=-1">';
    		$out.=$langs->trans("AddAnAction").' ';
    		$out.=img_picto($langs->trans("AddAnAction"),'filenew');
    		$out.="</a>";
		}
        $out.='</td>';
        $out.='</tr>';

        foreach ($histo as $key=>$value)
        {
            $var=!$var;
            $out.="<tr ".$bc[$var].">";

            // Champ date
            $out.='<td width="120" nowrap="nowrap">';
            if ($histo[$key]['date']) $out.=dol_print_date($histo[$key]['date'],'dayhour');
            else if ($histo[$key]['datestart']) $out.=dol_print_date($histo[$key]['datestart'],'dayhour');
            $out.="</td>\n";

            // Picto
            $out.='<td width="16">&nbsp;</td>';

            // Action
            $out.='<td>';
            if ($histo[$key]['type']=='action')
            {
                $actionstatic->type_code=$histo[$key]['acode'];
                $transcode=$langs->trans("Action".$histo[$key]['acode']);
                $libelle=($transcode!="Action".$histo[$key]['acode']?$transcode:$histo[$key]['libelle']);
                //$actionstatic->libelle=$libelle;
                $actionstatic->libelle=$histo[$key]['note'];
                $actionstatic->id=$histo[$key]['id'];
                $out.=$actionstatic->getNomUrl(1,40);
            }
            if ($histo[$key]['type']=='mailing')
            {
                $out.='<a href="'.DOL_URL_ROOT.'/comm/mailing/fiche.php?id='.$histo[$key]['id'].'">'.img_object($langs->trans("ShowEMailing"),"email").' ';
                $transcode=$langs->trans("Action".$histo[$key]['acode']);
                $libelle=($transcode!="Action".$histo[$key]['acode']?$transcode:'Send mass mailing');
                $out.=dol_trunc($libelle,40);
            }
            $out.='</td>';

            // Title of event
            //$out.='<td>'.dol_trunc($histo[$key]['note'], 40).'</td>';

            // Objet lie
            // TODO uniformize
            $out.='<td>';
            if ($histo[$key]['elementtype'] == 'propal' && $conf->propal->enabled)
            {
                $propalstatic->ref=$langs->trans("ProposalShort");
                $propalstatic->id=$histo[$key]['fk_element'];
                $out.=$propalstatic->getNomUrl(1);
            }
            elseif ($histo[$key]['elementtype'] == 'commande' && $conf->commande->enabled)
            {
                $orderstatic->ref=$langs->trans("Order");
                $orderstatic->id=$histo[$key]['fk_element'];
                $out.=$orderstatic->getNomUrl(1);
            }
            elseif ($histo[$key]['elementtype'] == 'facture' && $conf->facture->enabled)
            {
                $facturestatic->ref=$langs->trans("Invoice");
                $facturestatic->id=$histo[$key]['fk_element'];
                $facturestatic->type=$histo[$key]['ftype'];
                $out.=$facturestatic->getNomUrl(1,'compta');
            }
            else $out.='&nbsp;';
            $out.='</td>';

            // Contact pour cette action
            if (! $objcon->id && $histo[$key]['contact_id'] > 0)
            {
                $contactstatic->name=$histo[$key]['name'];
                $contactstatic->firstname=$histo[$key]['firstname'];
                $contactstatic->id=$histo[$key]['contact_id'];
                $out.='<td width="120">'.$contactstatic->getNomUrl(1,'',10).'</td>';
            }
            else
            {
                $out.='<td>&nbsp;</td>';
            }

            // Auteur
            $out.='<td nowrap="nowrap" width="80">';
            $userstatic->id=$histo[$key]['userid'];
            $userstatic->login=$histo[$key]['login'];
            $out.=$userstatic->getLoginUrl(1);
            $out.='</td>';

            // Statut
            $out.='<td nowrap="nowrap" width="20">'.$actionstatic->LibStatut($histo[$key]['percent'],3).'</td>';

            $out.="</tr>\n";
            $i++;
        }
        $out.="</table>\n";
        $out.="<br>\n";
    }

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

	$sql = "SELECT s.rowid, s.nom as name, s.address, s.cp as zip, s.ville as town, s.code_client, s.canvas";
	$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
	$sql.= " WHERE s.parent = ".$object->id;
	$sql.= " AND s.entity IN (".getEntity('societe', 1).")";
	$sql.= " ORDER BY s.nom";

	$result = $db->query($sql);
	$num = $db->num_rows($result);

	if ($num)
	{
		$socstatic = new Societe($db);

		print_titre($langs->trans("Subsidiaries"));
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

?>
