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
	\file       htdocs/includes/modules/facture/mercure/mercure.modules.php
	\ingroup    facture
	\brief      Class filte of Mercure numbering module for invoice
	\version    $Id$
*/

require_once(DOL_DOCUMENT_ROOT ."/includes/modules/facture/modules_facture.php");

/**
	\class      mod_facture_pluton
	\brief      Classe du mod�le de num�rotation de r�f�rence de facture Mercure
*/
class mod_facture_mercure extends ModeleNumRefFactures
{
	var $version='experimental';		// 'development', 'experimental', 'dolibarr'
	var $prefixinvoice;
	var $prefixcreditnote;
	var $matrice;
	var $numMatrice = Array();
	var $yy;
	var $mm;
	var $numbitcounter;
	var $searchLast;
	var $searchLastWithNoYear;
	var $searchLastWithPreviousYear;
	var $error = '';

    /**     \brief      Renvoi la description du modele de numerotation
     *      \return     string      Texte descripif
     */
	function info()
    {
    	global $conf,$langs;

		  $langs->load("bills");
		  
		  $form = new Form($db);
    	
      $texte = $langs->trans('MercureNumRefModelDesc1')."<br>\n";
      $texte.= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
      $texte.= '<input type="hidden" name="action" value="updateMask">';
      $texte.= '<input type="hidden" name="maskconstinvoice" value="FACTURE_MERCURE_MASK_INVOICE">';
      $texte.= '<input type="hidden" name="maskconstcredit" value="FACTURE_MERCURE_MASK_CREDIT">';
      $texte.= '<table class="nobordernopadding" width="100%">';
      
      // Parametrage du prefix des factures
      $texte.= '<tr><td>'.$langs->trans("InvoiceStandard").'</td>';
//      $texte.= '<td align="right"><input type="text" class="flat" size="24" name="prefixfacture" value="'.$conf->global->FACTURE_NUM_PREFIX.'"></td>';
      $texte.= '<td align="right">'.$form->textwithhelp('<input type="text" class="flat" size="24" name="maskinvoice" value="'.$conf->global->FACTURE_MERCURE_MASK_INVOICE.'">',$langs->trans("MercureMaskCodes"),1,1).'</td>';
      $texte.= '</tr>';
      
      // Parametrage du prefix des avoirs
      $texte.= '<tr><td>'.$langs->trans("InvoiceAvoir").'</td>';
      //$texte.= '<td align="right"><input type="text" class="flat" size="24" name="prefixavoir" value="'.$conf->global->AVOIR_NUM_PREFIX.'"></td>';
      $texte.= '<td align="right">'.$form->textwithhelp('<input type="text" class="flat" size="24" name="maskcredit" value="'.$conf->global->FACTURE_MERCURE_MASK_CREDIT.'">',$langs->trans("MercureMaskCodes"),1,1).'</td>';
      $texte.= '</tr>';
     
      $texte.= '<tr><td>&nbsp;</td><td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'" name="Button"></td></tr>';

      $texte.= '</table>';
      $texte.= '</form>';

      return $texte;
    }

    /**     \brief      Renvoi un exemple de numerotation
     *      \return     string      Example
     */
    function getExample()
    {
    	global $conf,$langs,$mysoc;
    	
    	$numExample = $this->getNextValue($mysoc,$facture);
        
		if (! $numExample)
		{
			$numExample = $langs->trans('NotConfigured');
		}
		return $numExample;
    }

	/**		\brief      Renvoi prochaine valeur attribuee
	*      	\param      objsoc      Objet societe
	*      	\param      facture		Objet facture
	*      	\return     string      Valeur
	*/
	function getNextValue($objsoc,$facture)
	{
		global $db,$conf;

		// On défini critere recherche compteur
		if ($facture->type == 2) $mask=$conf->global->FACTURE_MERCURE_MASK_CREDIT;
		else $mask=$conf->global->FACTURE_MERCURE_MASK_INVOICE;
		
		if (! $mask) return 'Error format not defined';

		// Replace all code that ar not {0...0}
		$newmask=$mask;
		$newmask=str_ireplace('{yyyy}','yyyy',$newmask);
		$newmask=str_ireplace('{yy}','yy',$newmask);
		$newmask=str_ireplace('{mm}','mm',$newmask);
		$newmask=str_ireplace('{dd}','dd',$newmask);
		//print "newmask=".$newmask;
		
		$posnumstart=strpos($newmask,'{0');		// Pos of {
		$posnumend  =strpos($newmask,'0}')+1;	// Pos of }
		
		if ($posnumstart <= 0 || $posnumend <= 1) return 'Error in format';
		
		$sqlstring='SUBSTRING(facnumber, '.($posnumstart+1).', '.($posnumend-$posnumstart-1).')';
		//print "x".$sqlstring;
		
		$counter=0;
		
		$sql = "SELECT MAX(".$sqlstring.") as val";
		$sql.= " FROM ".MAIN_DB_PREFIX."facture";
		$sql.= " WHERE facnumber not like '(%'";
		if ($facture->type == 2) $sql.= " AND type = 2";
		else $sql.=" AND type != 2";
		//print $sql;
		$resql=$db->query($sql);
		if ($resql)
		{
			$obj = $db->fetch_object($resql);
			$counter = $obj->val;
		}
		if (eregi('[^0-9]',$counter)) $counter=0;
		$counter++;
		
		$sizeofnum=$posnumend-$posnumstart;
		//print $counter."-".$sizeofnum."-".$posnumstart."-".$posnumend;
		
		// Build numFinal
		$numFinal = $mask;
		
		// We replace special codes
		$numFinal = str_ireplace('{yyyy}',date("Y"),$numFinal);
		$numFinal = str_ireplace('{yy}',date("y"),$numFinal);
		$numFinal = str_ireplace('{mm}',date("m"),$numFinal);
		$numFinal = str_ireplace('{dd}',date("d"),$numFinal);

		// Now we replace the counter
		$nummask='{'.str_pad('',$sizeofnum-1,"0").'}';
		$numcount=str_pad($counter,$sizeofnum-1,"0",STR_PAD_LEFT);
		//print 'x'.$nummask.'-'.$sizeofnum.'y';
		$numFinal = str_ireplace($nummask,$numcount,$numFinal);
		
		dolibarr_syslog("mod_facture_mercure::getNextValue return ".$numFinal);
		return  $numFinal;
	}
    
  
    /**     \brief      Renvoie la reference de commande suivante non utilisee
     *      \param      objsoc      Objet societe
     *      \param      facture		Objet facture
     *      \return     string      Texte descripif
     */
    function getNumRef($objsoc=0,$facture)
    {
        return $this->getNextValue($objsoc,$facture);
    }
    
}    

?>