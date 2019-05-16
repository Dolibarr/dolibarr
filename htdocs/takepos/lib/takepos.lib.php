<?php
/* Copyright (C) 2018 SuperAdmin
 * Copyright (C) 2019	JC Prieto			<jcprieto@virtual20.com>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    takepos/lib/takepos.lib.php
 * \ingroup takepos
 * \brief   Library files with common functions for TakePos
 */

/**
 * Prepare admin pages header
 *
 * @return array
 */
function takeposAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("cashdesk");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/takepos/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;
	$head[$h][0] = dol_buildpath("/takepos/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@takepos:/takepos/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@takepos:/takepos/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'takepos');

	return $head;
}


/**V20
 *	create ticket. Return ID of ticket
 *
 * @param	int		$place		Place of restaurant (ID)
 * @param	int		$term		ID of terminal
 * @param	int		$placelabel	Place name
 *  @return	int					ID of ticket if OK, <0 if KO
 */
function create_ticket($place=0,$term=0, $placelabel='')
{
	global $user,$db,$langs,$conf;
	
	if(is_null($place)){
		dol_syslog("TakePos::create_ticket. Place=NULL, changed to 0",LOG_WARNING);
		$place=0;
	}
	if($term==0)	$term=$_SESSION['takeposterminal'];
	if($placelabel=='')	$placelabel=$langs->trans('FreeTicket');
	
	$defaultsoc=$conf->global->CASHDESK_ID_THIRDPARTY.$term;
	
	require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
	$invoice = new Facture($db);
	$invoice->socid=$defaultsoc;
	$invoice->date=dol_now();
	$invoice->ref="(PROV-POS)";
	$invoice->ref_client=$placelabel;
	$invoice->ref_int=$place;
	$invoice->module_source = 'takepos';
	$invoice->pos_source = $term;	//V20: Terminal POS
	
	$facid=$invoice->create($user);
	$sql="UPDATE ".MAIN_DB_PREFIX."facture set facnumber='(PROV-POS-".$place.")' where rowid=".$facid;
	if($db->query($sql)){
		dol_syslog("TakePos::create_ticket. Place=".$place."(".$placelabel."), term=".$term, LOG_DEBUG);
		return $facid;
	}else{
		dol_syslog("TakePos::create_ticket. ERROR=".$sql, LOG_ERR);
		return -1;
	}
}

/**V20
 *	Load ticket data into SESSION by Json. Return array of data.
 *
 * 	@param	int		$place		Place of restorant
 * 	@param	int		$facid		Id of ticket =(id facture)
 *  @return	array				Data of ticket if OK, <0 if KO
 */


function load_ticket($place=0, $facid=0)
{
	global $db,$langs,$conf;
	
	$term=$_SESSION['takeposterminal'];
	
	if(is_null($place)){
		dol_syslog("TakePos::load_ticket. Place=NULL, changed to 0",LOG_WARNING);
		$place=0;
	}
	
	require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
	
	$invoice = new Facture($db);
	if($facid>0){
		$invoice->fetch($facid);
		$invoice->fetch(0,'(PROV-POS-'.$place.')');
	}
	
	if(empty($invoice->id))		//Just load place. New ticket
	{
		if($place>0)
		{
			$sql="SELECT  t.label, t.floor FROM ".MAIN_DB_PREFIX."takepos_floor_tables as t WHERE t.rowid=".$place;
			$resql = $db->query($sql);
			$row = $db->fetch_array($resql);
			$placelabel=$row['label'];	//V20
			$floor=$row['floor'];
		}else{
			$placelabel=$langs->trans('FreeTicket');
			$floor=0;
		}
		$socid=$conf->global->CASHDESK_ID_THIRDPARTY;
	}
	else{						//Ticket exist
		$facid=$invoice->id;
		$place = ($invoice->ref_int>0 ? $invoice->ref_int : 0);
		$placelabel=$invoice->ref_client;
		$diners=$invoice->array_options['options_diner'];
		$socid=$invoice->socid;
		if($place>0)
		{
			$sql="SELECT  t.floor FROM ".MAIN_DB_PREFIX."takepos_floor_tables as t WHERE t.rowid=".$place;
			$resql = $db->query($sql);
			$row = $db->fetch_array($resql);
			$floor=$row['floor'];
		}else	$floor=0;
		
	}
	
	if($floor==0) $floor=0;		//V20: Before: $floor=$term,
	
	//TODO: replace all single variable with $ticket array.
	$ticket=array();	//V20
	$ticket['floor']=$floor;
	$ticket['diners']=$diners;
	$ticket['facid']=$facid;
	$ticket['place']=$place;
	$ticket['placelabel']=$placelabel;
	$soc = new Societe($db);
	$soc->fetch($socid);
	$ticket['customer']=$soc->name;
	
	$_SESSION['ticket']=json_encode($ticket);
	
	dol_syslog("TakePos::load_ticket. Terminal=".$term." Ticket:" .$_SESSION['ticket'], LOG_DEBUG);
	return $ticket;
}



