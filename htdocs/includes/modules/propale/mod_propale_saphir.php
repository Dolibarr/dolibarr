<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * or see http://www.gnu.org/
 */

/**
	\file       htdocs/includes/modules/propale/mod_propale_saphir.php
	\ingroup    propale
	\brief      Fichier contenant la classe du modèle de numérotation de référence de propale Saphir
	\version    $Id$
*/

require_once(DOL_DOCUMENT_ROOT ."/includes/modules/propale/modules_propale.php");


/**
	\class      mod_propale_saphir
	\brief      Classe du modèle de numérotation de référence de propale Saphir
*/
class mod_propale_saphir extends ModeleNumRefPropales
{
	var $version='dolibarr';		// 'development', 'experimental', 'dolibarr'
	var $error = '';
	var $nom = 'Saphir';
	

    /**     \brief      Renvoi la description du modele de numérotation
     *      \return     string      Texte descripif
     */
	function info()
    {
    	global $conf,$langs;

		$langs->load("bills");
		  
		$form = new Form($db);
    	
		$texte = $langs->trans('SaphirNumRefModelDesc1')."<br>\n";
		$texte.= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		$texte.= '<input type="hidden" name="action" value="updateMask">';
		$texte.= '<input type="hidden" name="maskconstpropal" value="PROPALE_SAPHIR_MASK">';
		$texte.= '<table class="nobordernopadding" width="100%">';
		
		// Parametrage du prefix des factures
		$texte.= '<tr><td>'.$langs->trans("Mask").':</td>';
		$texte.= '<td align="right">'.$form->textwithhelp('<input type="text" class="flat" size="24" name="maskpropal" value="'.$conf->global->PROPALE_SAPHIR_MASK.'">',$langs->trans("GenericMaskCodes",$langs->transnoentities("Proposal"),$langs->transnoentities("Proposal"),$langs->transnoentities("Proposal")),1,1).'</td>';

		$texte.= '<td align="left" rowspan="2">&nbsp; <input type="submit" class="button" value="'.$langs->trans("Modify").'" name="Button"></td>';

		$texte.= '</tr>';
		
		$texte.= '</table>';
		$texte.= '</form>';

		return $texte;
    }

    /**     \brief      Renvoi un exemple de numérotation
     *      \return     string      Example
     */
    function getExample()
    {
     	global $conf,$langs,$mysoc;
    	
    	$numExample = $this->getNextValue($mysoc,$propalspecimen);
        
		if (! $numExample)
		{
			$numExample = $langs->trans('NotConfigured');
		}
		return $numExample;
    }

	/**		\brief      Return next value
	*      	\param      objsoc      Object third party
	*      	\param      facture		Object invoice
	*      	\return     string      Value if OK, 0 if KO
	*/
	function getNextValue($objsoc,$facture)
	{
		global $db,$conf;

		// On défini critere recherche compteur
		$mask=$conf->global->PROPALE_SAPHIR_MASK;
		
		if (! $mask) 
		{
			$this->error='NotConfigured';
			return 0;
		}

		// Extract value for mask counter, mask raz and mask offset
		if (! eregi('\{(0+)([@\+][0-9]+)?([@\+][0-9]+)?\}',$mask,$reg)) return 'ErrorBadMask';
		$masktri=$reg[1].$reg[2].$reg[3];
		$maskcounter=$reg[1];
		$maskraz=-1;
		$maskoffset=0;
		if (strlen($maskcounter) < 3) return 'CounterMustHaveMoreThan3Digits';
	
		$maskwithonlyymcode=$mask;
		$maskwithonlyymcode=eregi_replace('\{(0+)([@\+][0-9]+)?([@\+][0-9]+)?\}',$maskcounter,$maskwithonlyymcode);
		$maskwithonlyymcode=eregi_replace('\{dd\}','dd',$maskwithonlyymcode);
		$maskwithnocode=$maskwithonlyymcode;
		$maskwithnocode=eregi_replace('\{yyyy\}','yyyy',$maskwithnocode);
		$maskwithnocode=eregi_replace('\{yy\}','yy',$maskwithnocode);
		$maskwithnocode=eregi_replace('\{y\}','y',$maskwithnocode);
		$maskwithnocode=eregi_replace('\{mm\}','mm',$maskwithnocode);
		//print "maskwithonlyymcode=".$maskwithonlyymcode." maskwithnocode=".$maskwithnocode."\n<br>";

		// If an offset is asked
		if (! empty($reg[2]) && eregi('^\+',$reg[2])) $maskoffset=eregi_replace('^\+','',$reg[2]);
		if (! empty($reg[3]) && eregi('^\+',$reg[3])) $maskoffset=eregi_replace('^\+','',$reg[3]);

		// If a restore to zero after a month is asked we check if there is already a value for this year.
		if (! empty($reg[2]) && eregi('^@',$reg[2]))  $maskraz=eregi_replace('^@','',$reg[2]);
		if (! empty($reg[3]) && eregi('^@',$reg[3])) $maskraz=eregi_replace('^@','',$reg[3]);
		if ($maskraz >= 0)
		{
			if ($maskraz > 12) return 'ErrorBadMask';
			if ($maskraz > 1 && ! eregi('^(.*)\{(y+)\}\{(m+)\}',$maskwithonlyymcode,$reg)) return 'ErrorCantUseRazInStartedYearIfNoYearMonthInMask';
			if ($maskraz <= 1 && ! eregi('^(.*)\{(y+)\}',$maskwithonlyymcode,$reg)) return 'ErrorCantUseRazIfNoYearInMask';
			//print "x".$maskwithonlyymcode." ".$maskraz;

			// Define $yearcomp and $monthcomp (that will be use de filter request to search max number)
			$monthcomp=$maskraz;
			$yearoffset=0;
			$yearcomp=0;
			if (date("m") < $maskraz) { $yearoffset=-1; }	// If current month lower that month of return to zero, year is previous year
			if (strlen($reg[2]) == 4) $yearcomp=sprintf("%04d",date("Y")+$yearoffset);
			if (strlen($reg[2]) == 2) $yearcomp=sprintf("%02d",date("y")+$yearoffset);
			if (strlen($reg[2]) == 1) $yearcomp=substr(date("y"),2,1)+$yearoffset;
			
			$sqlwhere='';
			$sqlwhere.='SUBSTRING(facnumber, '.(strlen($reg[1])+1).', '.strlen($reg[2]).') >= '.$yearcomp;
			if ($monthcomp > 1)	// Test useless if monthcomp = 1 (or 0 is same as 1)
			{
				$sqlwhere.=' AND SUBSTRING(facnumber, '.(strlen($reg[1])+strlen($reg[2])+1).', '.strlen($reg[3]).') >= '.$monthcomp;
			}
		}
		//print "masktri=".$masktri." maskcounter=".$maskcounter." maskraz=".$maskraz." maskoffset=".$maskoffset."<br>\n";
		
		$posnumstart=strpos($maskwithnocode,$maskcounter);	// Pos of counter in final string (from 0 to ...)
		if ($posnumstart < 0) return 'ErrorBadMask';
		$sqlstring='SUBSTRING(facnumber, '.($posnumstart+1).', '.strlen($maskcounter).')';
		//print "x".$sqlstring;
		
		// Get counter in database
		$counter=0;
		$sql = "SELECT MAX(".$sqlstring.") as val";
		$sql.= " FROM ".MAIN_DB_PREFIX."facture";
		$sql.= " WHERE facnumber not like '(%'";
		if ($facture->type == 2) $sql.= " AND type = 2";
		else $sql.=" AND type != 2";
		if ($sqlwhere) $sql.=' AND '.$sqlwhere;
		
		//print $sql;
		dolibarr_syslog("mod_facture_mercure::getNextValue sql=".$sql, LOG_DEBUG);
		$resql=$db->query($sql);
		if ($resql)
		{
			$obj = $db->fetch_object($resql);
			$counter = $obj->val;
		}
		else dolibarr_print_error($db);
		if (empty($counter) || eregi('[^0-9]',$counter)) $counter=$maskoffset;
		$counter++;
		
		// Build numFinal
		$numFinal = $mask;
		
		// We replace special codes
		$numFinal = str_ireplace('{yyyy}',date("Y"),$numFinal);
		$numFinal = str_ireplace('{yy}',date("y"),$numFinal);
		$numFinal = str_ireplace('{y}' ,substr(date("y"),2,1),$numFinal);
		$numFinal = str_ireplace('{mm}',date("m"),$numFinal);
		$numFinal = str_ireplace('{dd}',date("d"),$numFinal);

		// Now we replace the counter
		$maskbefore='{'.$masktri.'}';
		$maskafter=str_pad($counter,strlen($maskcounter),"0",STR_PAD_LEFT);
		//print 'x'.$maskbefore.'-'.$maskafter.'y';
		$numFinal = str_ireplace($maskbefore,$maskafter,$numFinal);
		
		dolibarr_syslog("mod_propale_saphir::getNextValue return ".$numFinal);
		return  $numFinal;
	}

}    
?>