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

	
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'takepos');

	return $head;
}


/**V20
 *	Return list of prices levels plus Min price product (-1) and Last cost price (-2)
 *
 *	@param  string	$selected       Preselected type
 *	@param  string	$htmlname       Name of field in html form
 *	@param  string	$morehtml       More html form
 * 	@param	int		$hidetext		Do not show label before combo box
 *  @return	string					HTML select string
 */
function select_nivel_precios($selected='',$htmlname='tipo_precio',$morehtml='', $hidetext=0)
{
	global $db,$langs,$conf;
	
	if ($hidetext) $out.= $langs->trans("PriceLevel").': ';
	dol_syslog("Tipos de Niveles de precio");
	
	$out.= '<select class="flat" name="'.$htmlname.'">';
	

	for($i = -2; $i <= $conf->global->PRODUIT_MULTIPRICES_LIMIT; $i++)
	{
		if($i==0)	continue;
		$out.= '<option value="'.$i.'"';
		
		if ($selected!= '' && $selected== $i) $out.=' selected="selected"';			// To preselect a value
		if($i==-1)			$out.='>'.$langs->trans('MinPrice').'</option>';		//formula::MINPRICE_PROD_LEVEL=-1
		elseif($i==-2)		$out.='>'.$langs->trans('CostPrice').'</option>';		//formula::LASTPRICE_PROV_LEVEL=-2
		else{
			$keyforlabel='PRODUIT_MULTIPRICES_LABEL'.$i;
			$out.='>'.trim($langs->trans("PVP")).'_'.$conf->global->$keyforlabel.'</option>';
		}
	}
	
	$out.= '</select>'.$morehtml;
	
	return $out;
}

/**V20
 *	create ticket. Return ID of ticket
 *
 * @param	int		$place		Place of restorant
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
	if($term==0)	$term=$_SESSION['term'];
	if($placelabel=='')	$placelabel=$langs->trans('FreeTicket');
	
	require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
	
	$invoice = new Facture($db);
	$invoice->socid=$conf->global->CASHDESK_ID_THIRDPARTY;
	$invoice->date=dol_now();
	$invoice->ref="(PROV-POS)";		
	$invoice->ref_client=$placelabel;	
	$invoice->ref_int=$place;			
	$invoice->module_source = 'takepos';
	$invoice->pos_source = $term;	//V20: Terminal POS
	
	$placeid=$invoice->create($user);
	$sql="UPDATE ".MAIN_DB_PREFIX."facture set facnumber='(PROV-POS-".$place.")' where rowid=".$placeid;
	if($db->query($sql)){
		dol_syslog("TakePos::create_ticket. Place=".$place."(".$placelabel."), term=".$term, LOG_DEBUG);
		return $placeid;
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
	
	$term=$_SESSION['term'];
	
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
		$placeid=$facid=$invoice->id;
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
	
	dol_syslog("TakePos::load_ticket. term=".$term." Ticket:" .$_SESSION['ticket'], LOG_DEBUG);
	return $ticket;
}

/**
* Return Id of mode paiement
*
* @param   string	$code       'LIQ', 'CB', 'CHQ'
* @param	int		$active		1/0
* @return  string/array
*/
function getPaiementMode($active=1, $code='')
{
	global $db;
	
	$sql = "SELECT id, code, libelle FROM ".MAIN_DB_PREFIX."c_paiement";
	$sql.= " WHERE entity IN (".getEntity('c_paiement').")";
	$sql.= " AND active = ".$active;
	if($code>'')	$sql.= " AND code = '".$code."'";
	$sql.= " ORDER BY libelle";
	$resql = $db->query($sql);
	
	$paiements = array();
	if($resql){
		if($code>''){
			$codes = $db->fetch_array($resql);
			return $codes[0]['id'];
		}else{
			while ($obj = $db->fetch_object($resql)){
				array_push($paiements, $obj);
			}
			return $paiements;
		}
	}
}

/**
	 * Return next value not used or last value used
	 *
     * @param   string		$mode       'next' for next value or 'last' for last value
	 * @return  string       			Value
	 */
	function POS_getNextValue($mode='next')
	{
		global $db;

		$prefix='LA';	//V20: New Format: {prefix}{yy}{00000}   Old Format: {prefix}{yymm}{0000}
		
		// D'abord on recupere la valeur max
		$posindice=8;
		$sql = "SELECT MAX(CAST(SUBSTRING(facnumber FROM ".$posindice.") AS SIGNED)) as max";	// This is standard SQL
		$sql.= " FROM ".MAIN_DB_PREFIX."facture";
		$sql.= " WHERE facnumber LIKE '".$prefix."____%'";
		//$sql.= " AND entity IN (".getEntity('invoicenumber', 1, $invoice).")";
		$sql.= " AND entity=1";

		$resql=$db->query($sql);
		dol_syslog("Takepos::getNextValue", LOG_DEBUG);
		if ($resql)
		{
			$obj = $db->fetch_object($resql);
			if ($obj) $max = intval($obj->max);
			else $max=0;
		}
		else
		{
			return -1;
		}

		if ($mode == 'last')
		{
    		if ($max >= (pow(10, 5) - 1)) $num=$max;	// If counter > 9999, we do not format on 4 chars, we take number as it is. V20: now 5 digits
    		else $num = sprintf("%05s",$max);

            $ref='';
            $sql = "SELECT facnumber as ref";
            $sql.= " FROM ".MAIN_DB_PREFIX."facture";
            $sql.= " WHERE facnumber LIKE '".$prefix."____".$num."'";
            //$sql.= " AND entity IN (".getEntity('invoicenumber', 1, $invoice).")";
            $sql.= " AND entity=1";
            $sql.= " ORDER BY ref DESC";

            dol_syslog("Takepos::getNextValue", LOG_DEBUG);
            $resql=$db->query($sql);
            if ($resql)
            {
                $obj = $db->fetch_object($resql);
                if ($obj) $ref = $obj->ref;
            }
            else dol_print_error($db);

            return $ref;
		}
		else if ($mode == 'next')
		{
			//$date=$invoice->date;	// This is invoice date (not creation date)
    		//$yymm = strftime("%y%m",$date);
    		$yymm=date('y');

    		if ($max >= (pow(10, 5) - 1)) $num=$max+1;	// If counter > 9999, we do not format on 4 chars, we take number as it is
    		else $num = sprintf("%05s",$max+1);

    		dol_syslog("Takepos::getNextValue return ".$prefix.$yymm.$num, LOG_DEBUG);
    		return $prefix.$yymm.$num;
		}
		else dol_print_error('','Bad parameter for getNextValue');
	}

