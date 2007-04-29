<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis.houssin@cap-networks.com>
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
 *
 * $Id$
 * $Source$
 *
 */

/**
	\file       htdocs/includes/modules/facture/pluton/pluton.modules.php
	\ingroup    facture
	\brief      Fichier contenant la classe du modèle de numérotation de référence de facture Pluton
	\version    $Revision$
*/

require_once(DOL_DOCUMENT_ROOT ."/includes/modules/facture/modules_facture.php");

/**
	\class      mod_facture_pluton
	\brief      Classe du modèle de numérotation de référence de facture Pluton
*/
class mod_facture_pluton extends ModeleNumRefFactures
{
	var $prefixinvoice='';
	var $prefixcreditnote='';
	var $facturenummatrice='';
	var $error='';

    /**     \brief      Renvoi la description du modele de numérotation
     *      \return     string      Texte descripif
     */
function info()
    {
    	global $conf,$langs;

		$langs->load("bills");
    	
      $texte = $langs->trans('PlutonNumRefModelDesc1')."<br>\n";
      
      $texte.= 'Matrice de disposition des objets (prefix,mois,année,compteur...)';
      if ($conf->global->FACTURE_NUM_MATRICE)
      {
      	$texte.= ' ('.$langs->trans('DefinedAndHasThisValue').' : '.$conf->global->FACTURE_NUM_MATRICE.')<br>';
      }
      else
      {
      	$texte.= ' ('.$langs->trans('IsNotDefined').')<br>';
      }
      
      $texte.= 'Préfix des factures';
      if ($conf->global->FACTURE_NUM_PREFIX)
      {
      	$texte.= ' ('.$langs->trans('DefinedAndHasThisValue').' : '.$conf->global->FACTURE_NUM_PREFIX.')<br>';
      }
      else
      {
      	$texte.= ' ('.$langs->trans('IsNotDefined').')<br>';
      }
      
      $texte.= 'Préfix des avoirs';
      if ($conf->global->AVOIR_NUM_PREFIX)
      {
      	$texte.= ' ('.$langs->trans('DefinedAndHasThisValue').' : '.$conf->global->AVOIR_NUM_PREFIX.')<br>';
      }
      else
      {
      	$texte.= ' ('.$langs->trans('IsNotDefined').')<br>';
      }
      
      $texte.= 'Début année fiscale';
      if ($conf->global->SOCIETE_FISCAL_MONTH_START)
      {
      	$texte.= ' ('.$langs->trans('DefinedAndHasThisValue').' : '.$conf->global->SOCIETE_FISCAL_MONTH_START.')<br>';
      }
      else
      {
      	$texte.= ' ('.$langs->trans('IsNotDefined').')<br>';
      }
      
      $texte.= 'Le compteur se remet à zéro en début d\'année';
      if ($conf->global->FACTURE_NUM_RESTART_BEGIN_YEAR)
      {
      	$texte.= ' ('.$langs->trans('DefinedAndHasThisValue').' : '.$conf->global->FACTURE_NUM_RESTART_BEGIN_YEAR.')<br>';
      }
      else
      {
      	$texte.= ' ('.$langs->trans('IsNotDefined').')<br>';
      }
      
      $texte.= 'Un offset est appliqué sur le compteur';
      if ($conf->global->FACTURE_NUM_DELTA)
      {
      	$texte.= ' ('.$langs->trans('DefinedAndHasThisValue').' : '.$conf->global->FACTURE_NUM_DELTA.')<br>';
      }
      else
      {
      	$texte.= ' ('.$langs->trans('IsNotDefined').')<br>';
      }
      
      $texte.= 'La numérotation des avoirs s\'incrémente avec les factures';
      if ($conf->global->AVOIR_NUM_WITH_INVOICE)
      {
      	$texte.= ' ('.$langs->trans('DefinedAndHasThisValue').' : '.$conf->global->AVOIR_NUM_WITH_INVOICE.')<br>';
      }
      else
      {
      	$texte.= ' ('.$langs->trans('IsNotDefined').')<br>';
      }
      
      return $texte;
    }

    /**     \brief      Renvoi un exemple de numérotation
     *      \return     string      Example
     */
    function getExample()
    {
    	global $conf;
    	
    	$this->prefixinvoice     = $conf->global->FACTURE_NUM_PREFIX;
        $this->prefixcreditnote  = $conf->global->AVOIR_NUM_PREFIX;
        $this->facturenummatrice = $conf->global->FACTURE_NUM_MATRICE;
        
        if ($this->facturenummatrice != '')
        {
        	$resultatMatrice = Array();
        	$numMatrice = '';
        	
        	$matricePrefix   = "PREF|COM"; // PREF : prefix libre (ex: FA pour facture et AV pour avoir), COM : prefix du client
        	$matriceYear     = "[A]{2,4}"; // l'année est sur 2 ou 4 chiffres
        	$matriceMonth    = "[M]{2}"; // le mois est sur 2 chiffres
        	$matriceCounter  = "[C]{1,}"; //le compteur a un nombre de chiffres libre
        	$matriceTiret    = "[-]{1}"; // on recherche si il y a des tirets de séparation
        	
        	$matrice         = Array('prefix'=>$matricePrefix,
        	                         'year'=>$matriceYear,
        	                         'month'=>$matriceMonth,
        	                         'counter'=>$matriceCounter
        	                         );
        	
        	// on détermine l'emplacement des tirets
        	$resultTiret = preg_split('/'.$matriceTiret.'/',$this->facturenummatrice, -1, PREG_SPLIT_OFFSET_CAPTURE);
        	
        	$j = 0;
        	
        	// on détermine les objets de la matrice
        	for ($i = 0; $i < count($resultTiret); $i++)
        	{
        		foreach($resultTiret[$i] as $idResultTiret => $valueResultTiret)
        		{
        			// Ajout des tirets
        		  if ($j != $resultTiret[$i][1])
        		  {
        		  	$numMatrice .= '-';
        		  	$j = $resultTiret[$i][1];
        		  }
        			foreach($matrice as $idMatrice => $valueMatrice)
        			{
        			$resultCount = eregi(''.$valueMatrice.'',$valueResultTiret,$resultatMatrice);
        			if ($resultCount)
        			{
        				// On récupère le préfix utilisé
        				if ($idMatrice == 'prefix' && $resultatMatrice[0] == 'COM')
        				{
        					if ($objsoc->prefix_comm)
        					{
        						$prefix = $objsoc->prefix_comm;
        					}
        					else
        					{
        						$prefix = 'COM';
        					}
        					$numMatrice .= $prefix;
        				}
        				else if ($idMatrice == 'prefix' && $resultatMatrice[0] == 'PREF')
        				{
        					// Les avoirs peuvent suivre la numérotation des factures
        					if (!$conf->global->AVOIR_NUM_WITH_INVOICE && $facture->type == 2)
        					{
        						$prefix = $this->prefixcreditnote;
        					}
        					else
        					{
        						$prefix = $this->prefixinvoice;
        					}
        					$numMatrice .= $prefix;
        				}
        				else if ($idMatrice == 'year')
        				{
        					// On récupère le nombre de chiffres pour l'année
        					$numbityear = $resultCount;
        					// On défini le mois du début d'année fiscale
        					$fiscal_current_month = date("n");
        					
        					if (is_object($facture) && $facture->date)
                  {
        	          $create_month = strftime("%m",$facture->date);
                  }
                  else
                  {
        	          $create_month = $fiscal_current_month;
                  }

                  // On change d'année fiscal si besoin
                  if($conf->global->SOCIETE_FISCAL_MONTH_START && $fiscal_current_month >= $conf->global->SOCIETE_FISCAL_MONTH_START && $create_month >= $conf->global->SOCIETE_FISCAL_MONTH_START)
                  {
        	          $yy = substr(strftime("%Y",mktime(0,0,0,date("m"),date("d"),date("Y")+1)),$numbityear);
                  }
                  else
                  {
        	          $yy = substr(strftime("%Y",time()),$numbityear);
                  }
        					$numMatrice .= $yy;
        				}
        				else if ($idMatrice == 'month')
        				{
        					// On récupère le mois si besoin
        					$mm = strftime("%m",time());
        					$numMatrice .= $mm;
        				}
        				else if ($idMatrice == 'counter')
        				{
        					// On récupère le nombre de chiffres pour le compteur
        					$numbitcounter = $resultCount;
        				}
        			}
        		}
        	}
        }
    	
    	// On récupère le nombre de chiffres du compteur
    	$arg = '%0'.$numbitcounter.'s';
      $num = sprintf($arg,1);
      
      // Construction de l'exemple de numérotation
    	$numExample = $numMatrice.$num;
    	
    	return $numExample;
    }
  }

	/**		\brief      Renvoi prochaine valeur attribuée
	*      	\param      objsoc      Objet société
	*      	\param      facture		Objet facture
	*      	\return     string      Valeur
	*/
    function getNextValue($objsoc,$facture)
    {
        global $db,$conf;
        
        $this->prefixinvoice     = $conf->global->FACTURE_NUM_PREFIX;
        $this->prefixcreditnote  = $conf->global->AVOIR_NUM_PREFIX;
        $this->facturenummatrice = $conf->global->FACTURE_NUM_MATRICE;
        
        if ($this->facturenummatrice != '')
        {
        	$resultatMatrice = Array();
        	$numMatrice = Array();
        	
        	$matricePrefix   = "PREF|COM"; // PREF : prefix libre (ex: FA pour facture et AV pour avoir), COM : prefix du client
        	$matriceYear     = "[A]{2,4}"; // l'année est sur 2 ou 4 chiffres
        	$matriceMonth    = "[M]{2}"; // le mois est sur 2 chiffres
        	$matriceCounter  = "[C]{1,}"; //le compteur a un nombre de chiffres libre
        	$matriceTiret    = "[-]{1}"; // on recherche si il y a des tirets de séparation
        	
        	$matrice         = Array('prefix'=>$matricePrefix,
        	                         'year'=>$matriceYear,
        	                         'month'=>$matriceMonth,
        	                         'counter'=>$matriceCounter
        	                         );
        	
        	// on détermine l'emplacement des tirets
        	$resultTiret = preg_split('/'.$matriceTiret.'/',$this->facturenummatrice, -1, PREG_SPLIT_OFFSET_CAPTURE);
        	
        	$j = 0;
        	$k = 0;
        	
        	// on détermine les objets de la matrice
        	for ($i = 0; $i < count($resultTiret); $i++)
        	{
        		foreach($resultTiret[$i] as $idResultTiret => $valueResultTiret)
        		{
        			// Ajout des tirets
        		  if ($j != $resultTiret[$i][1])
        		  {
        		  	$numMatrice[$k] = '-';
        		  	$searchLast .= '-';
        		  	$j = $resultTiret[$i][1];
        		  	$k++;
        		  }
        			foreach($matrice as $idMatrice => $valueMatrice)
        			{
        			$resultCount = eregi(''.$valueMatrice.'',$valueResultTiret,$resultatMatrice);
        			if ($resultCount)
        			{
        				// On récupère le préfix utilisé
        				if ($idMatrice == 'prefix' && $resultatMatrice[0] == 'COM')
        				{
        					if ($objsoc->prefix_comm)
        					{
        						$prefix = $objsoc->prefix_comm;
        					}
        					else
        					{
        						$prefix = 'COM';
        					}
        					$numMatrice[$k] = '$prefix';
        					$searchLast .= $prefix;
        					$k++;
        				}
        				else if ($idMatrice == 'prefix' && $resultatMatrice[0] == 'PREF')
        				{
        					// Les avoirs peuvent suivre la numérotation des factures
        					if (!$conf->global->AVOIR_NUM_WITH_INVOICE && $facture->type == 2)
        					{
        						$prefix = $this->prefixcreditnote;
        					}
        					else
        					{
        						$prefix = $this->prefixinvoice;
        					}
        					$numMatrice[$k] = '$prefix';
        					$searchLast .= $prefix;
        					$k++;
        				}
        				else if ($idMatrice == 'year')
        				{
        					// On récupère le nombre de chiffres pour l'année
        					$numbityear = $resultCount;
        					// On défini le mois du début d'année fiscale
        					$fiscal_current_month = date("n");
        					
        					if (is_object($facture) && $facture->date)
                  {
        	          $create_month = strftime("%m",$facture->date);
                  }
                  else
                  {
        	          $create_month = $fiscal_current_month;
                  }

                  // On change d'année fiscal si besoin
                  if($conf->global->SOCIETE_FISCAL_MONTH_START && $fiscal_current_month >= $conf->global->SOCIETE_FISCAL_MONTH_START && $create_month >= $conf->global->SOCIETE_FISCAL_MONTH_START)
                  {
        	          $yy = substr(strftime("%Y",mktime(0,0,0,date("m"),date("d"),date("Y")+1)),$numbityear);
                  }
                  else
                  {
        	          $yy = substr(strftime("%Y",time()),$numbityear);
                  }
        					$numMatrice[$k] = '$yy';
        					$searchLast .= $yy;
        					$k++;
        				}
        				else if ($idMatrice == 'month')
        				{
        					// On récupère le mois si besoin
        					$mm = strftime("%m",time());
        					$numMatrice[$k] = '$mm';
        					$searchLast .= $mm;
        					$k++;
        				}
        				else if ($idMatrice == 'counter')
        				{
        					// On récupère le nombre de chiffres pour le compteur
        					$numbitcounter = $resultCount;
        					$numMatrice[$k] = '$num';
        					$k++;
        				}
        			}
        		}
        	}
        }

        // On récupère la valeur max (réponse immédiate car champ indéxé)
        $posindice  = $numbitcounter;
        $fayy='';
        $sql = "SELECT MAX(facnumber)";
        $sql.= " FROM ".MAIN_DB_PREFIX."facture";
        $sql.= " WHERE facnumber like '${prefix}%'";
        if ($conf->global->FACTURE_NUM_RESTART_BEGIN_YEAR) $sql.= " AND facnumber like '${searchLast}%'";
        $resql=$db->query($sql);
        if ($resql)
        {
            $row = $db->fetch_row($resql);
            if ($row) $fayy = substr($row[0],0,-$posindice);
        }
        
        // Si au moins un champ respectant le modèle a été trouvée
        if (eregi('^'.$searchLast.'',$fayy))
        {
            // Recherche rapide car restreint par un like sur champ indexé
            $sql = "SELECT MAX(0+SUBSTRING(facnumber,$posindice))";
            $sql.= " FROM ".MAIN_DB_PREFIX."facture";
            $sql.= " WHERE facnumber like '${fayy}%'";
            $resql=$db->query($sql);
            if ($resql)
            {
                $row = $db->fetch_row($resql);
                $max = $row[0];
            }
        }
        else
        {
            $max=0;
        }
        
        // On replace le prefix de l'avoir
        if ($conf->global->AVOIR_NUM_WITH_INVOICE && $facture->type == 2)
        {
        	$prefix = $this->prefixcreditnote;
        }
    	  
    	  // On applique le nombre de chiffres du compteur
        $arg = '%0'.$numbitcounter.'s';
        $num = sprintf($arg,$max+1);
        $numFinal = '';
        
        foreach($numMatrice as $objetMatrice)
        {
        	if ($objetMatrice == '-') $numFinal .= $objetMatrice;
        	if ($objetMatrice == '$prefix') $numFinal .= $prefix;
        	if ($objetMatrice == '$yy') $numFinal .= $yy;
        	if ($objetMatrice == '$mm') $numFinal .= $mm;
        	if ($objetMatrice == '$num') $numFinal .= $num;
        } 
        
        dolibarr_syslog("mod_facture_pluton::getNextValue return ".$numFinal);
        return  $numFinal;
    }
    
  
    /**     \brief      Renvoie la référence de commande suivante non utilisée
     *      \param      objsoc      Objet société
     *      \param      facture		Objet facture
     *      \return     string      Texte descripif
     */
    function getNumRef($objsoc=0,$facture)
    {
        return $this->getNextValue($objsoc,$facture);
    }
  } 
}    

?>