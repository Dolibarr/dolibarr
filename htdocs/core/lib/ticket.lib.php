<?php
/* Copyright (C) 2013-2018	Jean-François FERRY	<hello@librethic.io>
 * Copyright (C) 2016		Christophe Battarel	<christophe@altairis.fr>
 * Copyright (C) 2019       Frédéric France     <frederic.france@netlogic.fr>
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
 *    \file       core/lib/ticket.lib.php
 *    \ingroup    ticket
 *    \brief        This file is a library for Ticket module
 */

/**
 * Build tabs for admin page
 *
 * @return array
 */
function ticketAdminPrepareHead()
{
    global $langs, $conf;

    $langs->load("ticket");

    $h = 0;
    $head = array();

    $head[$h][0] = DOL_URL_ROOT.'/admin/ticket.php';
    $head[$h][1] = $langs->trans("TicketSettings");
    $head[$h][2] = 'settings';
    $h++;

    $head[$h][0] = DOL_URL_ROOT.'/admin/ticket_extrafields.php';
    $head[$h][1] = $langs->trans("ExtraFieldsTicket");
    $head[$h][2] = 'attributes';
    $h++;

    $head[$h][0] = DOL_URL_ROOT.'/admin/ticket_public.php';
    $head[$h][1] = $langs->trans("PublicInterface");
    $head[$h][2] = 'public';
    $h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    //$this->tabs = array(
    //    'entity:+tabname:Title:@ticket:/ticket/mypage.php?id=__ID__'
    //); // to add new tab
    //$this->tabs = array(
    //    'entity:-tabname:Title:@ticket:/ticket/mypage.php?id=__ID__'
    //); // to remove a tab
    complete_head_from_modules($conf, $langs, null, $head, $h, 'ticketadmin');

    return $head;
}

/**
 *  Build tabs for a Ticket object
 *
 *  @param	Ticket	  $object		Object Ticket
 *  @return array				          Array of tabs
 */
function ticket_prepare_head($object)
{
    global $db, $langs, $conf, $user;

    $h = 0;
    $head = array();
    $head[$h][0] = DOL_URL_ROOT.'/ticket/card.php?action=view&track_id='.$object->track_id;
    $head[$h][1] = $langs->trans("Ticket");
    $head[$h][2] = 'tabTicket';
    $h++;

    if (empty($conf->global->MAIN_DISABLE_CONTACTS_TAB) && empty($user->socid))
    {
    	$nbContact = count($object->liste_contact(-1, 'internal')) + count($object->liste_contact(-1, 'external'));
    	$head[$h][0] = DOL_URL_ROOT.'/ticket/contact.php?track_id='.$object->track_id;
    	$head[$h][1] = $langs->trans('ContactsAddresses');
    	if ($nbContact > 0) $head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbContact.'</span>';
    	$head[$h][2] = 'contact';
    	$h++;
    }

    complete_head_from_modules($conf, $langs, $object, $head, $h, 'ticket');

    // Attached files
    include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
    $upload_dir = $conf->ticket->dir_output."/".$object->ref;
    $nbFiles = count(dol_dir_list($upload_dir, 'files'));
    $head[$h][0] = dol_buildpath('/ticket/document.php', 1).'?id='.$object->id;
    $head[$h][1] = $langs->trans("Documents");
    if ($nbFiles > 0) {
        $head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbFiles.'</span>';
    }

    $head[$h][2] = 'tabTicketDocument';
    $h++;


    // History
	$ticketViewType = "messaging";
	if (empty($_SESSION['ticket-view-type'])) {
		$_SESSION['ticket-view-type'] = $ticketViewType;
	}
	else {
		$ticketViewType = $_SESSION['ticket-view-type'];
	}

	if ($ticketViewType == "messaging") {
		$head[$h][0] = DOL_URL_ROOT.'/ticket/messaging.php?track_id='.$object->track_id;
	}
	else {
		// $ticketViewType == "list"
		$head[$h][0] = DOL_URL_ROOT.'/ticket/agenda.php?track_id='.$object->track_id;
	}
    $head[$h][1] = $langs->trans('Events');
    if (!empty($conf->agenda->enabled) && (!empty($user->rights->agenda->myactions->read) || !empty($user->rights->agenda->allactions->read)))
    {
    	$head[$h][1] .= '/';
    	$head[$h][1] .= $langs->trans("Agenda");
    }
    $head[$h][2] = 'tabTicketLogs';
    $h++;


    complete_head_from_modules($conf, $langs, $object, $head, $h, 'ticket', 'remove');


    return $head;
}

/**
 * Return string with full Url. The file qualified is the one defined by relative path in $object->last_main_doc
 *
 * @param   Object	$object				Object
 * @return	string						Url string
 */
function showDirectPublicLink($object)
{
	global $conf, $langs;

	require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
	$email = CMailFile::getValidAddress($object->origin_email, 2);
	$url = '';
	if ($email)
	{
		$url = dol_buildpath('/public/ticket/view.php', 3).'?track_id='.$object->track_id.'&email='.$email;
	}

	$out = '';
	if (empty($conf->global->TICKET_ENABLE_PUBLIC_INTERFACE))
	{
		$out .= '<span class="opacitymedium">'.$langs->trans("PublicInterfaceNotEnabled").'</span>';
	}
	else
	{
		$out .= img_picto('', 'object_globe.png').' '.$langs->trans("TicketPublicAccess").':<br>';
		if ($url)
		{
			$out .= '<input type="text" id="directpubliclink" class="quatrevingtpercent" value="'.$url.'">';
			$out .= ajax_autoselect("directpubliclink", 0);
		}
		else
		{
			$out .= '<span class="opacitymedium">'.$langs->trans("TicketNotCreatedFromPublicInterface").'</span>';
		}
	}

	return $out;
}

/**
 *  Generate a random id
 *
 *  @param  int $car Length of string to generate key
 *  @return string
 */
function generate_random_id($car = 16)
{
    $string = "";
    $chaine = "abcdefghijklmnopqrstuvwxyz123456789";
    srand((double) microtime() * 1000000);
    for ($i = 0; $i < $car; $i++) {
        $string .= $chaine[rand() % strlen($chaine)];
    }
    return $string;
}

/**
 * Show header for public pages
 *
 * @param  string $title       Title
 * @param  string $head        Head array
 * @param  int    $disablejs   More content into html header
 * @param  int    $disablehead More content into html header
 * @param  array  $arrayofjs   Array of complementary js files
 * @param  array  $arrayofcss  Array of complementary css files
 * @return void
 */
function llxHeaderTicket($title, $head = "", $disablejs = 0, $disablehead = 0, $arrayofjs = '', $arrayofcss = '')
{
    global $user, $conf, $langs, $mysoc;

    top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss); // Show html headers

    print '<body id="mainbody" class="publicnewticketform">';

    // Define urllogo
    $width = 0;
    if (!empty($conf->global->TICKET_SHOW_COMPANY_LOGO) || !empty($conf->global->TICKET_PUBLIC_INTERFACE_TOPIC)) {
        // Print logo
        if (!empty($conf->global->TICKET_SHOW_COMPANY_LOGO))
        {
        	$urllogo = DOL_URL_ROOT.'/theme/common/login_logo.png';

        	if (!empty($mysoc->logo_small) && is_readable($conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_small)) {
        		$urllogo = DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&amp;entity='.$conf->entity.'&amp;file='.urlencode('logos/thumbs/'.$mysoc->logo_small);
        		$width = 150;
        	} elseif (!empty($mysoc->logo) && is_readable($conf->mycompany->dir_output.'/logos/'.$mysoc->logo)) {
        		$urllogo = DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&amp;entity='.$conf->entity.'&amp;file='.urlencode('logos/'.$mysoc->logo);
        		$width = 150;
        	} elseif (is_readable(DOL_DOCUMENT_ROOT.'/theme/dolibarr_logo.svg')) {
        		$urllogo = DOL_URL_ROOT.'/theme/dolibarr_logo.svg';
        	}
        }
    }

    print '<div class="center">';
    // Output html code for logo
    if ($urllogo || !empty($conf->global->TICKET_PUBLIC_INTERFACE_TOPIC))
    {
    	print '<div class="backgreypublicpayment">';
    	print '<div class="logopublicpayment">';
    	if ($urllogo) {
	    	print '<a href="'.($conf->global->TICKET_URL_PUBLIC_INTERFACE ? $conf->global->TICKET_URL_PUBLIC_INTERFACE : dol_buildpath('/public/ticket/index.php', 1)).'">';
	    	print '<img id="dolpaymentlogo" src="'.$urllogo.'"';
	    	if ($width) print ' width="'.$width.'"';
	    	print '>';
	    	print '</a>';
    	}
    	if (!empty($conf->global->TICKET_PUBLIC_INTERFACE_TOPIC)) {
    		print '<div class="clearboth"></div><strong>'.($conf->global->TICKET_PUBLIC_INTERFACE_TOPIC ? $conf->global->TICKET_PUBLIC_INTERFACE_TOPIC : $langs->trans("TicketSystem")).'</strong>';
    	}
    	print '</div>';
    	if (empty($conf->global->MAIN_HIDE_POWERED_BY)) {
    		print '<div class="poweredbypublicpayment opacitymedium right"><a href="https://www.dolibarr.org" target="dolibarr">'.$langs->trans("PoweredBy").'<br><img src="'.DOL_URL_ROOT.'/theme/dolibarr_logo.svg" width="80px"></a></div>';
    	}
    	print '</div>';
    }

    print '</div>';

    print '<div class="ticketlargemargin">';
}



/**
 *    	Show html area with actions for ticket messaging.
 *      Note: Global parameter $param must be defined.
 *
 * 		@param	Conf		       $conf		   Object conf
 * 		@param	Translate	       $langs		   Object langs
 * 		@param	DoliDB		       $db			   Object db
 * 		@param	mixed			   $filterobj	   Filter on object Adherent|Societe|Project|Product|CommandeFournisseur|Dolresource|Ticket|... to list events linked to an object
 * 		@param	Contact		       $objcon		   Filter on object contact to filter events on a contact
 *      @param  int			       $noprint        Return string but does not output it
 *      @param  string		       $actioncode     Filter on actioncode
 *      @param  string             $donetodo       Filter on event 'done' or 'todo' or ''=nofilter (all).
 *      @param  array              $filters        Filter on other fields
 *      @param  string             $sortfield      Sort field
 *      @param  string             $sortorder      Sort order
 *      @return	string|void				           Return html part or void if noprint is 1
 */
function show_ticket_messaging($conf, $langs, $db, $filterobj, $objcon = '', $noprint = 0, $actioncode = '', $donetodo = 'done', $filters = array(), $sortfield = 'a.datep,a.id', $sortorder = 'DESC')
{
    global $user, $conf;
    global $form;

    global $param, $massactionbutton;

    dol_include_once('/comm/action/class/actioncomm.class.php');

    // Check parameters
    if (!is_object($filterobj) && !is_object($objcon)) dol_print_error('', 'BadParameter');

    $out = '';
    $histo = array();
    $numaction = 0;
    $now = dol_now('tzuser');

    // Open DSI -- Fix order by -- Begin
    $sortfield_list = explode(',', $sortfield);
    $sortfield_label_list = array('a.id' => 'id', 'a.datep' => 'dp', 'a.percent' => 'percent');
    $sortfield_new_list = array();
    foreach ($sortfield_list as $sortfield_value) {
        $sortfield_new_list[] = $sortfield_label_list[trim($sortfield_value)];
    }
    $sortfield_new = implode(',', $sortfield_new_list);

    if (!empty($conf->agenda->enabled))
    {
        // Recherche histo sur actioncomm
        if (is_object($objcon) && $objcon->id > 0) {
            $sql = "SELECT DISTINCT a.id, a.label as label,";
        }
        else
        {
            $sql = "SELECT a.id, a.label as label,";
        }
        $sql .= " a.datep as dp,";
        $sql .= " a.note as message,";
        $sql .= " a.datep2 as dp2,";
        $sql .= " a.percent as percent, 'action' as type,";
        $sql .= " a.fk_element, a.elementtype,";
        $sql .= " a.fk_contact,";
        $sql .= " c.code as acode, c.libelle as alabel, c.picto as apicto,";
        $sql .= " u.rowid as user_id, u.login as user_login, u.photo as user_photo, u.firstname as user_firstname, u.lastname as user_lastname";
        if (is_object($filterobj) && get_class($filterobj) == 'Societe')      $sql .= ", sp.lastname, sp.firstname";
        elseif (is_object($filterobj) && get_class($filterobj) == 'Adherent') $sql .= ", m.lastname, m.firstname";
        elseif (is_object($filterobj) && get_class($filterobj) == 'CommandeFournisseur')  $sql .= ", o.ref";
        elseif (is_object($filterobj) && get_class($filterobj) == 'Product')  $sql .= ", o.ref";
        elseif (is_object($filterobj) && get_class($filterobj) == 'Ticket')   $sql .= ", o.ref";
        elseif (is_object($filterobj) && get_class($filterobj) == 'BOM')      $sql .= ", o.ref";
        elseif (is_object($filterobj) && get_class($filterobj) == 'Contrat')  $sql .= ", o.ref";
        $sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as a";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u on u.rowid = a.fk_user_action";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_actioncomm as c ON a.fk_action = c.id";

        $force_filter_contact = false;
        if (is_object($objcon) && $objcon->id > 0) {
            $force_filter_contact = true;
            $sql .= " INNER JOIN ".MAIN_DB_PREFIX."actioncomm_resources as r ON a.id = r.fk_actioncomm";
            $sql .= " AND r.element_type = '".$db->escape($objcon->table_element)."' AND r.fk_element = ".$objcon->id;
        }

        if (is_object($filterobj) && get_class($filterobj) == 'Societe')  $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople as sp ON a.fk_contact = sp.rowid";
        elseif (is_object($filterobj) && get_class($filterobj) == 'Dolresource') {
            $sql .= " INNER JOIN ".MAIN_DB_PREFIX."element_resources as er";
            $sql .= " ON er.resource_type = 'dolresource'";
            $sql .= " AND er.element_id = a.id";
            $sql .= " AND er.resource_id = ".$filterobj->id;
        }
        elseif (is_object($filterobj) && get_class($filterobj) == 'Adherent') $sql .= ", ".MAIN_DB_PREFIX."adherent as m";
        elseif (is_object($filterobj) && get_class($filterobj) == 'CommandeFournisseur') $sql .= ", ".MAIN_DB_PREFIX."commande_fournisseur as o";
        elseif (is_object($filterobj) && get_class($filterobj) == 'Product') $sql .= ", ".MAIN_DB_PREFIX."product as o";
        elseif (is_object($filterobj) && get_class($filterobj) == 'Ticket') $sql .= ", ".MAIN_DB_PREFIX."ticket as o";
        elseif (is_object($filterobj) && get_class($filterobj) == 'BOM') $sql .= ", ".MAIN_DB_PREFIX."bom_bom as o";
        elseif (is_object($filterobj) && get_class($filterobj) == 'Contrat') $sql .= ", ".MAIN_DB_PREFIX."contrat as o";

        $sql .= " WHERE a.entity IN (".getEntity('agenda').")";
        if ($force_filter_contact === false) {
            if (is_object($filterobj) && in_array(get_class($filterobj), array('Societe', 'Client', 'Fournisseur')) && $filterobj->id) $sql .= " AND a.fk_soc = ".$filterobj->id;
            elseif (is_object($filterobj) && get_class($filterobj) == 'Project' && $filterobj->id) $sql .= " AND a.fk_project = ".$filterobj->id;
            elseif (is_object($filterobj) && get_class($filterobj) == 'Adherent')
            {
                $sql .= " AND a.fk_element = m.rowid AND a.elementtype = 'member'";
                if ($filterobj->id) $sql .= " AND a.fk_element = ".$filterobj->id;
            }
            elseif (is_object($filterobj) && get_class($filterobj) == 'CommandeFournisseur')
            {
                $sql .= " AND a.fk_element = o.rowid AND a.elementtype = 'order_supplier'";
                if ($filterobj->id) $sql .= " AND a.fk_element = ".$filterobj->id;
            }
            elseif (is_object($filterobj) && get_class($filterobj) == 'Product')
            {
                $sql .= " AND a.fk_element = o.rowid AND a.elementtype = 'product'";
                if ($filterobj->id) $sql .= " AND a.fk_element = ".$filterobj->id;
            }
            elseif (is_object($filterobj) && get_class($filterobj) == 'Ticket')
            {
                $sql .= " AND a.fk_element = o.rowid AND a.elementtype = 'ticket'";
                if ($filterobj->id) $sql .= " AND a.fk_element = ".$filterobj->id;
            }
            elseif (is_object($filterobj) && get_class($filterobj) == 'BOM')
            {
                $sql .= " AND a.fk_element = o.rowid AND a.elementtype = 'bom'";
                if ($filterobj->id) $sql .= " AND a.fk_element = ".$filterobj->id;
            }
            elseif (is_object($filterobj) && get_class($filterobj) == 'Contrat')
            {
                $sql .= " AND a.fk_element = o.rowid AND a.elementtype = 'contract'";
                if ($filterobj->id) $sql .= " AND a.fk_element = ".$filterobj->id;
            }
        }

        // Condition on actioncode
        if (!empty($actioncode))
        {
            if (empty($conf->global->AGENDA_USE_EVENT_TYPE))
            {
                if ($actioncode == 'AC_NON_AUTO') $sql .= " AND c.type != 'systemauto'";
                elseif ($actioncode == 'AC_ALL_AUTO') $sql .= " AND c.type = 'systemauto'";
                else
                {
                    if ($actioncode == 'AC_OTH') $sql .= " AND c.type != 'systemauto'";
                    elseif ($actioncode == 'AC_OTH_AUTO') $sql .= " AND c.type = 'systemauto'";
                }
            }
            else
            {
                if ($actioncode == 'AC_NON_AUTO') $sql .= " AND c.type != 'systemauto'";
                elseif ($actioncode == 'AC_ALL_AUTO') $sql .= " AND c.type = 'systemauto'";
                else $sql .= " AND c.code = '".$db->escape($actioncode)."'";
            }
        }
        if ($donetodo == 'todo') $sql .= " AND ((a.percent >= 0 AND a.percent < 100) OR (a.percent = -1 AND a.datep > '".$db->idate($now)."'))";
        elseif ($donetodo == 'done') $sql .= " AND (a.percent = 100 OR (a.percent = -1 AND a.datep <= '".$db->idate($now)."'))";
        if (is_array($filters) && $filters['search_agenda_label']) $sql .= natural_search('a.label', $filters['search_agenda_label']);
    }

    // Add also event from emailings. TODO This should be replaced by an automatic event ? May be it's too much for very large emailing.
    if (!empty($conf->mailing->enabled) && !empty($objcon->email)
        && (empty($actioncode) || $actioncode == 'AC_OTH_AUTO' || $actioncode == 'AC_EMAILING'))
    {
        $langs->load("mails");

        $sql2 = "SELECT m.rowid as id, m.titre as label, mc.date_envoi as dp, mc.date_envoi as dp2, '100' as percent, 'mailing' as type";
        $sql2 .= ", null as fk_element, '' as elementtype, null as contact_id";
        $sql2 .= ", 'AC_EMAILING' as acode, '' as alabel, '' as apicto";
        $sql2 .= ", u.rowid as user_id, u.login as user_login, u.photo as user_photo, u.firstname as user_firstname, u.lastname as user_lastname"; // User that valid action
        if (is_object($filterobj) && get_class($filterobj) == 'Societe')      $sql2 .= ", '' as lastname, '' as firstname";
        elseif (is_object($filterobj) && get_class($filterobj) == 'Adherent') $sql2 .= ", '' as lastname, '' as firstname";
        elseif (is_object($filterobj) && get_class($filterobj) == 'CommandeFournisseur')  $sql2 .= ", '' as ref";
        elseif (is_object($filterobj) && get_class($filterobj) == 'Product')  $sql2 .= ", '' as ref";
        elseif (is_object($filterobj) && get_class($filterobj) == 'Ticket')   $sql2 .= ", '' as ref";
        $sql2 .= " FROM ".MAIN_DB_PREFIX."mailing as m, ".MAIN_DB_PREFIX."mailing_cibles as mc, ".MAIN_DB_PREFIX."user as u";
        $sql2 .= " WHERE mc.email = '".$db->escape($objcon->email)."'"; // Search is done on email.
        $sql2 .= " AND mc.statut = 1";
        $sql2 .= " AND u.rowid = m.fk_user_valid";
        $sql2 .= " AND mc.fk_mailing=m.rowid";
    }

    if (!empty($sql) && !empty($sql2)) {
        $sql = $sql." UNION ".$sql2;
    } elseif (empty($sql) && !empty($sql2)) {
        $sql = $sql2;
    }

    //TODO Add limit in nb of results
    $sql .= $db->order($sortfield_new, $sortorder);
    dol_syslog("company.lib::show_actions_done", LOG_DEBUG);
    $resql = $db->query($sql);
    if ($resql)
    {
        $i = 0;
        $num = $db->num_rows($resql);

        while ($i < $num)
        {
            $obj = $db->fetch_object($resql);

            if ($obj->type == 'action') {
                $contactaction = new ActionComm($db);
                $contactaction->id = $obj->id;
                $result = $contactaction->fetchResources();
                if ($result < 0) {
                    dol_print_error($db);
                    setEventMessage("company.lib::show_actions_done Error fetch ressource", 'errors');
                }

                //if ($donetodo == 'todo') $sql.= " AND ((a.percent >= 0 AND a.percent < 100) OR (a.percent = -1 AND a.datep > '".$db->idate($now)."'))";
                //elseif ($donetodo == 'done') $sql.= " AND (a.percent = 100 OR (a.percent = -1 AND a.datep <= '".$db->idate($now)."'))";
                $tododone = '';
                if (($obj->percent >= 0 and $obj->percent < 100) || ($obj->percent == -1 && $obj->datep > $now)) $tododone = 'todo';

                $histo[$numaction] = array(
                    'type'=>$obj->type,
                    'tododone'=>$tododone,
                    'id'=>$obj->id,
                    'datestart'=>$db->jdate($obj->dp),
                    'dateend'=>$db->jdate($obj->dp2),
                    'note'=>$obj->label,
                    'message'=>$obj->message,
                    'percent'=>$obj->percent,

                    'userid'=>$obj->user_id,
                    'login'=>$obj->user_login,
                    'userfirstname'=>$obj->user_firstname,
                    'userlastname'=>$obj->user_lastname,
                    'userphoto'=>$obj->user_photo,

                    'contact_id'=>$obj->fk_contact,
                    'socpeopleassigned' => $contactaction->socpeopleassigned,
                    'lastname'=>$obj->lastname,
                    'firstname'=>$obj->firstname,
                    'fk_element'=>$obj->fk_element,
                    'elementtype'=>$obj->elementtype,
                    // Type of event
                    'acode'=>$obj->acode,
                    'alabel'=>$obj->alabel,
                    'libelle'=>$obj->alabel, // deprecated
                    'apicto'=>$obj->apicto
                );
            } else {
                $histo[$numaction] = array(
                    'type'=>$obj->type,
                    'tododone'=>'done',
                    'id'=>$obj->id,
                    'datestart'=>$db->jdate($obj->dp),
                    'dateend'=>$db->jdate($obj->dp2),
                    'note'=>$obj->label,
                    'message'=>$obj->message,
                    'percent'=>$obj->percent,
                    'acode'=>$obj->acode,

                    'userid'=>$obj->user_id,
                    'login'=>$obj->user_login,
                    'userfirstname'=>$obj->user_firstname,
                    'userlastname'=>$obj->user_lastname,
                    'userphoto'=>$obj->user_photo
                );
            }

            $numaction++;
            $i++;
        }
    }
    else
    {
        dol_print_error($db);
    }

    if (!empty($conf->agenda->enabled) || (!empty($conf->mailing->enabled) && !empty($objcon->email)))
    {
        $delay_warning = $conf->global->MAIN_DELAY_ACTIONS_TODO * 24 * 60 * 60;

        require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
        include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
        require_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
        require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';

        $formactions = new FormActions($db);

        $actionstatic = new ActionComm($db);
        $userstatic = new User($db);
        $contactstatic = new Contact($db);
        $userGetNomUrlCache = array();

		$out .= '<div class="filters-container" >';
		$out .= '<form name="listactionsfilter" class="listactionsfilter" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		if ($objcon && get_class($objcon) == 'Contact' &&
			(is_null($filterobj) || get_class($filterobj) == 'Societe'))
		{
			$out .= '<input type="hidden" name="id" value="'.$objcon->id.'" />';
		}
		else
		{
			$out .= '<input type="hidden" name="id" value="'.$filterobj->id.'" />';
		}
		if ($filterobj && get_class($filterobj) == 'Societe') $out .= '<input type="hidden" name="socid" value="'.$filterobj->id.'" />';

		$out .= "\n";

		$out .= '<div class="div-table-responsive-no-min">';
		$out .= '<table class="noborder borderbottom centpercent">';

		$out .= '<tr class="liste_titre">';

		//$out.='<td class="liste_titre">';
		$out .= getTitleFieldOfList('Date', 0, $_SERVER["PHP_SELF"], 'a.datep', '', $param, '', $sortfield, $sortorder, '')."\n";
		//$out.='</td>';

		$out .= '<th class="liste_titre"><strong>'.$langs->trans("Search").' : </strong></th>';
		if ($donetodo)
		{
			$out .= '<th class="liste_titre"></th>';
		}
		$out .= '<th class="liste_titre">'.$langs->trans("Type").' ';
		$out .= $formactions->select_type_actions($actioncode, "actioncode", '', empty($conf->global->AGENDA_USE_EVENT_TYPE) ? 1 : -1, 0, 0, 1);
		$out .= '</th>';
		$out .= '<th class="liste_titre maxwidth100onsmartphone">';
		$out .= $langs->trans("Label").' ';
		$out .= '<input type="text" class="maxwidth100onsmartphone" name="search_agenda_label" value="'.$filters['search_agenda_label'].'">';
		$out .= '</th>';

		$out .= '<th class="liste_titre width50 middle">';
		$searchpicto = $form->showFilterAndCheckAddButtons($massactionbutton ? 1 : 0, 'checkforselect', 1);
		$out .= $searchpicto;
		$out .= '</th>';
		$out .= '</tr>';


		$out .= '</table>';

        $out .= '</form>';
		$out .= '</div>';

        $out .= "\n";

        $out .= '<ul class="timeline">';

        if ($donetodo)
        {
            $tmp = '';
            if (get_class($filterobj) == 'Societe') $tmp .= '<a href="'.DOL_URL_ROOT.'/comm/action/list.php?socid='.$filterobj->id.'&amp;status=done">';
            $tmp .= ($donetodo != 'done' ? $langs->trans("ActionsToDoShort") : '');
            $tmp .= ($donetodo != 'done' && $donetodo != 'todo' ? ' / ' : '');
            $tmp .= ($donetodo != 'todo' ? $langs->trans("ActionsDoneShort") : '');
            //$out.=$langs->trans("ActionsToDoShort").' / '.$langs->trans("ActionsDoneShort");
            if (get_class($filterobj) == 'Societe') $tmp .= '</a>';
            $out .= getTitleFieldOfList($tmp);
        }


        //require_once DOL_DOCUMENT_ROOT.'/comm/action/class/cactioncomm.class.php';
        //$caction=new CActionComm($db);
        //$arraylist=$caction->liste_array(1, 'code', '', (empty($conf->global->AGENDA_USE_EVENT_TYPE)?1:0), '', 1);

        $actualCycleDate = false;

        foreach ($histo as $key=>$value)
        {
            $actionstatic->fetch($histo[$key]['id']); // TODO Do we need this, we already have a lot of data of line into $histo

            $actionstatic->type_picto = $histo[$key]['apicto'];
            $actionstatic->type_code = $histo[$key]['acode'];

            $url = DOL_URL_ROOT.'/comm/action/card.php?id='.$histo[$key]['id'];

            $tmpa = dol_getdate($histo[$key]['datestart'], false);
            if ($actualCycleDate !== $tmpa['year'].'-'.$tmpa['yday']) {
                $actualCycleDate = $tmpa['year'].'-'.$tmpa['yday'];
                $out .= '<!-- timeline time label -->';
                $out .= '<li class="time-label">';
                $out .= '<span class="timeline-badge-date">';
                $out .= dol_print_date($histo[$key]['datestart'], 'daytext', 'tzserver', $langs);
                $out .= '</span>';
                $out .= '</li>';
                $out .= '<!-- /.timeline-label -->';
            }


            $out .= '<!-- timeline item -->'."\n";
            $out .= '<li class="timeline-code-'.strtolower($actionstatic->code).'">';


            $out .= '<!-- timeline icon -->'."\n";
            $iconClass = 'fa fa-comments';
            $img_picto = '';
            $colorClass = '';
            $pictoTitle = '';

            if ($histo[$key]['percent'] == -1) {
                $colorClass = 'timeline-icon-not-applicble';
                $pictoTitle = $langs->trans('StatusNotApplicable');
            }
            elseif ($histo[$key]['percent'] == 0) {
                $colorClass = 'timeline-icon-todo';
                $pictoTitle = $langs->trans('StatusActionToDo').' (0%)';
            }
            elseif ($histo[$key]['percent'] > 0 && $histo[$key]['percent'] < 100) {
                $colorClass = 'timeline-icon-in-progress';
                $pictoTitle = $langs->trans('StatusActionInProcess').' ('.$histo[$key]['percent'].'%)';
            }
            elseif ($histo[$key]['percent'] >= 100) {
                $colorClass = 'timeline-icon-done';
                $pictoTitle = $langs->trans('StatusActionDone').' (100%)';
            }


            if ($actionstatic->code == 'AC_TICKET_CREATE') {
                $iconClass = 'fa fa-ticket';
            }
            elseif ($actionstatic->code == 'AC_TICKET_MODIFY') {
                $iconClass = 'fa fa-pencil';
            }
            elseif ($actionstatic->code == 'TICKET_MSG') {
                $iconClass = 'fa fa-comments';
            }
            elseif ($actionstatic->code == 'TICKET_MSG_PRIVATE') {
                $iconClass = 'fa fa-mask';
            }
            elseif (!empty($conf->global->AGENDA_USE_EVENT_TYPE))
            {
                if ($actionstatic->type_picto) $img_picto = img_picto('', $actionstatic->type_picto);
                else {
                    if ($actionstatic->type_code == 'AC_RDV')       $iconClass = 'fa fa-handshake';
                    elseif ($actionstatic->type_code == 'AC_TEL')   $iconClass = 'fa fa-phone';
                    elseif ($actionstatic->type_code == 'AC_FAX')   $iconClass = 'fa fa-fax';
                    elseif ($actionstatic->type_code == 'AC_EMAIL') $iconClass = 'fa fa-envelope';
                    elseif ($actionstatic->type_code == 'AC_INT')   $iconClass = 'fa fa-shipping-fast';
                    elseif ($actionstatic->type_code == 'AC_OTH_AUTO')   $iconClass = 'fa fa-robot';
                    elseif (!preg_match('/_AUTO/', $actionstatic->type_code)) $iconClass = 'fa fa-robot';
                }
            }



            $out .= '<i class="'.$iconClass.' '.$colorClass.'" title="'.$pictoTitle.'">'.$img_picto.'</i>'."\n";

            $out .= '<div class="timeline-item">'."\n";

            $out .= '<span class="timeline-header-action">';

			if (isset($histo[$key]['type']) && $histo[$key]['type'] == 'mailing') {
				$out .= '<a class="timeline-btn" href="'.DOL_URL_ROOT.'/comm/mailing/card.php?id='.$histo[$key]['id'].'">'.img_object($langs->trans("ShowEMailing"), "email").' ';
				$out .= $histo[$key]['id'];
				$out .= '</a> ';
			} else {
				$out .= $actionstatic->getNomUrl(1, -1, 'valignmiddle').' ';
			}

            //if ($user->rights->agenda->allactions->read || $actionstatic->authorid == $user->id)
            //{
            //	$out.='<a href="'.$url.'" class="timeline-btn" title="'.$langs->trans('Show').'" ><i class="fa fa-calendar" ></i>'.$langs->trans('Show').'</a>';
            //}


            if ($user->rights->agenda->allactions->create ||
                (($actionstatic->authorid == $user->id || $actionstatic->userownerid == $user->id) && $user->rights->agenda->myactions->create))
            {
                $out .= '<a class="timeline-btn" href="'.DOL_MAIN_URL_ROOT.'/comm/action/card.php?action=edit&id='.$actionstatic->id.'"><i class="fa fa-pencil" title="'.$langs->trans("Modify").'" ></i></a>';
            }


            $out .= '</span>';
            // Date
            $out .= '<span class="time"><i class="fa fa-clock-o"></i> ';
            $out .= dol_print_date($histo[$key]['datestart'], 'dayhour');
            if ($histo[$key]['dateend'] && $histo[$key]['dateend'] != $histo[$key]['datestart'])
            {
                $tmpa = dol_getdate($histo[$key]['datestart'], true);
                $tmpb = dol_getdate($histo[$key]['dateend'], true);
                if ($tmpa['mday'] == $tmpb['mday'] && $tmpa['mon'] == $tmpb['mon'] && $tmpa['year'] == $tmpb['year']) $out .= '-'.dol_print_date($histo[$key]['dateend'], 'hour');
                else $out .= '-'.dol_print_date($histo[$key]['dateend'], 'dayhour');
            }
            $late = 0;
            if ($histo[$key]['percent'] == 0 && $histo[$key]['datestart'] && $histo[$key]['datestart'] < ($now - $delay_warning)) $late = 1;
            if ($histo[$key]['percent'] == 0 && !$histo[$key]['datestart'] && $histo[$key]['dateend'] && $histo[$key]['datestart'] < ($now - $delay_warning)) $late = 1;
            if ($histo[$key]['percent'] > 0 && $histo[$key]['percent'] < 100 && $histo[$key]['dateend'] && $histo[$key]['dateend'] < ($now - $delay_warning)) $late = 1;
            if ($histo[$key]['percent'] > 0 && $histo[$key]['percent'] < 100 && !$histo[$key]['dateend'] && $histo[$key]['datestart'] && $histo[$key]['datestart'] < ($now - $delay_warning)) $late = 1;
            if ($late) $out .= img_warning($langs->trans("Late")).' ';
            $out .= "</span>\n";

            // Ref
            $out .= '<h3 class="timeline-header">';

            // Author of event
            $out .= '<span class="messaging-author">';
            if ($histo[$key]['userid'] > 0)
            {
                if (!isset($userGetNomUrlCache[$histo[$key]['userid']])) { // is in cache ?
                    $userstatic->fetch($histo[$key]['userid']);
                    $userGetNomUrlCache[$histo[$key]['userid']] = $userstatic->getNomUrl(-1, '', 0, 0, 16, 0, 'firstelselast', '');
                }
                $out .= $userGetNomUrlCache[$histo[$key]['userid']];
            }
            $out .= '</span>';

            // Title
            $out .= ' <span class="messaging-title">';

			if ($actionstatic->code == 'TICKET_MSG') {
				$out .= $langs->trans('TicketNewMessage');
			}
			elseif ($actionstatic->code == 'TICKET_MSG_PRIVATE') {
				$out .= $langs->trans('TicketNewMessage').' <em>('.$langs->trans('Private').')</em>';
			} else {
                if (isset($histo[$key]['type']) && $histo[$key]['type'] == 'action') {
                    $transcode = $langs->trans("Action".$histo[$key]['acode']);
                    $libelle = ($transcode != "Action".$histo[$key]['acode'] ? $transcode : $histo[$key]['alabel']);
                    $libelle = $histo[$key]['note'];
                    $actionstatic->id = $histo[$key]['id'];
                    $out .= dol_trunc($libelle, 120);
                }
                if (isset($histo[$key]['type']) && $histo[$key]['type'] == 'mailing') {
                    $out .= '<a href="'.DOL_URL_ROOT.'/comm/mailing/card.php?id='.$histo[$key]['id'].'">'.img_object($langs->trans("ShowEMailing"), "email").' ';
                    $transcode = $langs->trans("Action".$histo[$key]['acode']);
                    $libelle = ($transcode != "Action".$histo[$key]['acode'] ? $transcode : 'Send mass mailing');
                    $out .= dol_trunc($libelle, 120);
                }
            }


            $out .= '</span>';

            $out .= '</h3>';

            if (!empty($histo[$key]['message'])
                && $actionstatic->code != 'AC_TICKET_CREATE'
                && $actionstatic->code != 'AC_TICKET_MODIFY'
            )
            {
                $out .= '<div class="timeline-body">';
                $out .= $histo[$key]['message'];
                $out .= '</div>';
            }


            // Timeline footer
            $footer = '';

            // Contact for this action
            if (isset($histo[$key]['socpeopleassigned']) && is_array($histo[$key]['socpeopleassigned']) && count($histo[$key]['socpeopleassigned']) > 0) {
                $contactList = '';
                foreach ($histo[$key]['socpeopleassigned'] as $cid => $Tab) {
                    $contact = new Contact($db);
                    $result = $contact->fetch($cid);

                    if ($result < 0)
                        dol_print_error($db, $contact->error);

                    if ($result > 0) {
                        $contactList .= !empty($contactList) ? ', ' : '';
                        $contactList .= $contact->getNomUrl(1);
                        if (isset($histo[$key]['acode']) && $histo[$key]['acode'] == 'AC_TEL') {
                            if (!empty($contact->phone_pro))
                                $contactList .= '('.dol_print_phone($contact->phone_pro).')';
                        }
                    }
                }

                $footer .= $langs->trans('ActionOnContact').' : '.$contactList;
            }
            elseif (empty($objcon->id) && isset($histo[$key]['contact_id']) && $histo[$key]['contact_id'] > 0)
            {
                $contact = new Contact($db);
                $result = $contact->fetch($histo[$key]['contact_id']);

                if ($result < 0)
                    dol_print_error($db, $contact->error);

                if ($result > 0) {
                    $footer .= $contact->getNomUrl(1);
                    if (isset($histo[$key]['acode']) && $histo[$key]['acode'] == 'AC_TEL') {
                        if (!empty($contact->phone_pro))
                            $footer .= '('.dol_print_phone($contact->phone_pro).')';
                    }
                }
            }

			$documents = getTicketActionCommEcmList($actionstatic);
            if (!empty($documents))
			{
				$footer .= '<div class="timeline-documents-container">';
				foreach ($documents as $doc)
				{
					$footer .= '<span id="document_'.$doc->id.'" class="timeline-documents" ';
					$footer .= ' data-id="'.$doc->id.'" ';
					$footer .= ' data-path="'.$doc->filepath.'"';
					$footer .= ' data-filename="'.dol_escape_htmltag($doc->filename).'" ';
					$footer .= '>';

					$filePath = DOL_DATA_ROOT.'/'.$doc->filepath.'/'.$doc->filename;
					$mime = dol_mimetype($filePath);
					$file = $actionstatic->id.'/'.$doc->filename;
					$thumb = $actionstatic->id.'/thumbs/'.substr($doc->filename, 0, strrpos($doc->filename, '.')).'_mini'.substr($doc->filename, strrpos($doc->filename, '.'));
					$doclink = dol_buildpath('document.php', 1).'?modulepart=actions&attachment=0&file='.urlencode($file).'&entity='.$conf->entity;
					$viewlink = dol_buildpath('viewimage.php', 1).'?modulepart=actions&file='.urlencode($thumb).'&entity='.$conf->entity;

					$mimeAttr = ' mime="'.$mime.'" ';
					$class = '';
					if (in_array($mime, array('image/png', 'image/jpeg', 'application/pdf'))) {
						$class .= ' documentpreview';
					}

					$footer .= '<a href="'.$doclink.'" class="btn-link '.$class.'" target="_blank"  '.$mimeAttr.' >';
					$footer .= img_mime($filePath).' '.$doc->filename;
					$footer .= '</a>';

					$footer .= '</span>';
				}
				$footer .= '</div>';
			}







            if (!empty($footer)) {
                $out .= '<div class="timeline-footer">'.$footer.'</div>';
            }


            $out .= '</div>'."\n"; // end timeline-item

            $out .= '</li>';
            $out .= '<!-- END timeline item -->';

            $i++;
        }
        $out .= "</ul>\n";
    }


    if ($noprint) return $out;
    else print $out;
}


/**
 * getTicketActionCommEcmList
 *
 * @param	ActionComm		$object			Object ActionComm
 * @return 	array							Array of documents in index table
 */
function getTicketActionCommEcmList($object)
{
	global $conf, $db;

	$documents = array();

	$sql = 'SELECT ecm.rowid as id, ecm.src_object_type, ecm.src_object_id, ecm.filepath, ecm.filename';
	$sql .= ' FROM '.MAIN_DB_PREFIX.'ecm_files ecm';
	$sql .= ' WHERE ecm.filepath = \'agenda/'.$object->id.'\'';
	//$sql.= ' ecm.src_object_type = \''.$object->element.'\' AND ecm.src_object_id = '.$object->id; // Actually upload file doesn't add type
	$sql .= ' ORDER BY ecm.position ASC';

	$resql = $db->query($sql);
	if ($resql) {
		if ($db->num_rows($resql)) {
			while ($obj = $db->fetch_object($resql)) {
				$documents[$obj->id] = $obj;
			}
		}
	}

	return $documents;
}
