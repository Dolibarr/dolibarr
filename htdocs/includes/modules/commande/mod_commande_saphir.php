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
	\file       htdocs/includes/modules/commande/mod_commande_saphir.php
	\ingroup    commande
	\brief      Fichier contenant la classe du modèle de numérotation de référence de commande Saphir
	\version    $Revision$
*/

require_once(DOL_DOCUMENT_ROOT ."/includes/modules/commande/modules_commande.php");

/**
	\class      mod_commande_saphir
	\brief      Classe du modèle de numérotation de référence de commande Saphir
*/
class mod_commande_saphir extends ModeleNumRefCommandes
{
	var $prefixorder='';
	var $ordernummatrice='';
	var $error='';
	
	/**   \brief      Constructeur
   */
  function mod_commande_saphir()
  {
    $this->nom = "Saphir";
  }

    /**     \brief      Renvoi la description du modele de numérotation
     *      \return     string      Texte descripif
     */
function info()
    {
    	global $conf,$langs;

		  $langs->load("bills");
		  
		  $form = new Form($db);
    	
      $texte = $langs->trans('SaphirNumRefModelDesc1')."<br>\n";
      $texte.= '<table class="nobordernopadding" width="100%">';
      
      // Paramétrage de la matrice
      $texte.= '<tr><td>Matrice de disposition des objets (prefix,mois,année,compteur...)</td>';
      $texte.= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
      $texte.= '<input type="hidden" name="action" value="updateMatrice">';
      $texte.= '<td align="right"><input type="text" class="flat" size="30" name="matrice" value="'.$conf->global->COMMANDE_NUM_MATRICE.'"></td>';
      $texte.= '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'" name="Button"></td>';
      $texte.= '<td aligne="center">'.$form->textwithhelp('',$langs->trans("MatriceOrderDesc"),1,1).'</td>';
      $texte.= '</tr></form>';
      
      // Paramétrage du prefix des commandes
      $texte.= '<tr><td>Préfix des commandes</td>';
      $texte.= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
      $texte.= '<input type="hidden" name="action" value="updatePrefixCommande">';
      $texte.= '<td align="right"><input type="text" class="flat" size="30" name="prefixcommande" value="'.$conf->global->COMMANDE_NUM_PREFIX.'"></td>';
      $texte.= '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'" name="Button"></td>';
      $texte.= '<td aligne="center">'.$form->textwithhelp('',$langs->trans("PrefixOrderDesc"),1,1).'</td>';
      $texte.= '</tr></form>';
      
      // On détermine un offset sur le compteur
      $texte.= '<tr><td>Appliquer un offset sur le compteur</td>';
      $texte.= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
      $texte.= '<input type="hidden" name="action" value="setOffset">';
      $texte.= '<td align="right"><input type="text" class="flat" size="30" name="offset" value="'.$conf->global->FACTURE_NUM_DELTA.'"></td>';
      $texte.= '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'" name="Button"></td>';
      $texte.= '<td aligne="center">'.$form->textwithhelp('',$langs->trans("OffsetDesc"),1,1).'</td>';
      $texte.= '</tr></form>';
      
      // On défini le debut d'année fiscale
      $texte.= '<tr><td>Début d\'année fiscale</td>';
      $texte.= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
      $texte.= '<input type="hidden" name="action" value="setFiscalMonth">';
      $texte.= '<td align="right">';
      $texte.= $form->select_month($conf->global->SOCIETE_FISCAL_MONTH_START,'fiscalmonth',1);
      $texte.= '</td><td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'" name="Button"></td>';
      $texte.= '<td aligne="center">'.$form->textwithwarning('',$langs->trans("FiscalMonthStartDesc"),1).'</td>';
      $texte.= '</tr></form>';
   
      // On défini si le compteur se remet à zero en debut d'année
      $texte.= '<tr><td>Le compteur se remet à zéro en début d\'année</td>';
      $texte.= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
      $texte.= '<input type="hidden" name="action" value="setNumRestart">';
      $texte.= '<td align="right">';
      $texte.= $form->selectyesno('numrestart',$conf->global->COMMANDE_NUM_RESTART_BEGIN_YEAR,1);
      $texte.= '</td><td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'" name="Button"></td>';
      $texte.= '<td aligne="center">'.$form->textwithhelp('',$langs->trans("NumRestartDesc"),1,1).'</td>';
      $texte.= '</tr></form>';
      
      $texte.= '</table><br>';

      return $texte;
    }

    /**     \brief      Renvoi un exemple de numérotation
     *      \return     string      Example
     */
    function getExample()
    {
    	global $conf;
    	
    	$this->prefixorder     = $conf->global->COMMANDE_NUM_PREFIX;
      $this->ordernummatrice = $conf->global->COMMANDE_NUM_MATRICE;
        
        if ($this->ordernummatrice != '')
        {
        	$resultatMatrice = Array();
        	$numMatrice = '';
        	
        	$matricePrefix   = "PREF|COM"; // PREF : prefix libre (ex: C pour commande), COM : prefix du client
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
        	$resultTiret = preg_split('/'.$matriceTiret.'/',$this->ordernummatrice, -1, PREG_SPLIT_OFFSET_CAPTURE);
        	
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
        					$prefix = $this->prefixorder;
        					$numMatrice .= $prefix;
        				}
        				else if ($idMatrice == 'year')
        				{
        					// On récupère le nombre de chiffres pour l'année
        					$numbityear = $resultCount;
        					// On défini le mois du début d'année fiscale
        					$fiscal_current_month = date("n");
        					$create_month = $fiscal_current_month;

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
      $num = sprintf($arg,$conf->global->COMMANDE_NUM_DELTA?$conf->global->COMMANDE_NUM_DELTA:1);
      
      // Construction de l'exemple de numérotation
    	$numExample = $numMatrice.$num;
    	
    	return $numExample;
    }
  }

	/**		\brief      Renvoi prochaine valeur attribuée
	*      	\param      objsoc      Objet société
	*      	\param      commande		Objet commande
	*      	\return     string      Valeur
	*/
    function getNextValue($objsoc,$commande)
    {
        global $db,$conf;
        
        $this->prefixorder     = $conf->global->COMMANDE_NUM_PREFIX;
        $this->ordernummatrice = $conf->global->COMMANDE_NUM_MATRICE;
        
        if ($this->ordernummatrice != '')
        {
        	$resultatMatrice = Array();
        	$numMatrice = Array();
        	
        	$matricePrefix   = "PREF|COM"; // PREF : prefix libre (ex: C pour commande), COM : prefix du client
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
        	$resultTiret = preg_split('/'.$matriceTiret.'/',$this->ordernummatrice, -1, PREG_SPLIT_OFFSET_CAPTURE);
        	
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
        		  	$searchLastWithNoYear .= '-';
        		  	$searchLastWithPreviousYear .= '-';
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
        					$searchLastWithNoYear .= $prefix;
        					$searchLastWithPreviousYear .= $prefix;
        					$k++;
        				}
        				else if ($idMatrice == 'prefix' && $resultatMatrice[0] == 'PREF')
        				{
        					$prefix = $this->prefixorder;
        					$numMatrice[$k] = '$prefix';
        					$searchLast .= $prefix;
        					$searchLastWithNoYear .= $prefix;
        					$searchLastWithPreviousYear .= $prefix;
        					$k++;
        				}
        				else if ($idMatrice == 'year')
        				{
        					// On récupère le nombre de chiffres pour l'année
        					$numbityear = $resultCount;
        					// On défini le mois du début d'année fiscale
        					$current_month = date("n");
        					
        					if (is_object($commande) && $commande->date)
                  {
        	          $create_month = strftime("%m",$commande->date);
                  }
                  else
                  {
        	          $create_month = $current_month;
                  }

                  // On change d'année fiscal si besoin
                  if($conf->global->SOCIETE_FISCAL_MONTH_START && $current_month >= $conf->global->SOCIETE_FISCAL_MONTH_START && $create_month >= $conf->global->SOCIETE_FISCAL_MONTH_START)
                  {
        	          $yy = substr(strftime("%Y",mktime(0,0,0,date("m"),date("d"),date("Y")+1)),$numbityear);
                  }
                  else
                  {
        	          $yy = substr(strftime("%Y",time()),$numbityear);
                  }
        					$numMatrice[$k] = '$yy';
        					$searchLast .= $yy;
        					for ($l = 1; $l <= $numbityear; $l++)
        					{
        						$searchLastWithNoYear .= '[0-9]';
        					}
        					$previousYear = substr(strftime("%Y",mktime(0,0,0,date("m"),date("d"),date("Y")-1)),$numbityear);
        					$searchLastWithPreviousYear .= $previousYear;
        					$k++;
        				}
        				else if ($idMatrice == 'month')
        				{
        					// On récupère le mois si besoin
        					$mm = strftime("%m",time());
        					$numMatrice[$k] = '$mm';
        					$searchLast .= $mm;
        					$searchLastWithNoYear .= $mm;
        					$searchLastWithPreviousYear .= $mm;
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
        $comyy='';
        $sql = "SELECT MAX(ref)";
        $sql.= " FROM ".MAIN_DB_PREFIX."commande";
        if ($conf->global->COMMANDE_NUM_RESTART_BEGIN_YEAR) $sql.= " WHERE ref like '${searchLast}%'";
        $resql=$db->query($sql);
        if ($resql)
        {
            $row = $db->fetch_row($resql);
            if ($row) $comyy = substr($row[0],0,-$posindice);
        }
        
        if ($conf->global->PROPALE_NUM_DELTA != '')
        {
        	//on vérifie si il y a une année précédente
          //pour éviter que le delta soit appliqué de nouveau sur la nouvelle année
          $lastyy='';
          $sql = "SELECT MAX(ref)";
          $sql.= " FROM ".MAIN_DB_PREFIX."commande";
          $sql.= " WHERE ref like '${searchLastWithPreviousYear}%'";
          $resql=$db->query($sql);
          if ($resql)
          {
            $row = $db->fetch_row($resql);
            if ($row) $lastyy = substr($row[0],0,-$posindice);
          }
        }

        // Si au moins un champ respectant le modèle a été trouvée
        if (eregi('^'.$searchLastWithNoYear.'',$comyy))
        {
            // Recherche rapide car restreint par un like sur champ indexé
            $sql = "SELECT MAX(0+SUBSTRING(ref,-".$posindice."))";
            $sql.= " FROM ".MAIN_DB_PREFIX."commande";
            $sql.= " WHERE ref like '${comyy}%'";
            $resql=$db->query($sql);
            if ($resql)
            {
                $row = $db->fetch_row($resql);
                $max = $row[0];
            }
        }
        else if ($conf->global->PROPALE_NUM_DELTA != '' && !eregi('^'.$searchLastWithPreviousYear.'',$lastyy))
        {
        	// on applique le delta une seule fois
        	$max=$conf->global->COMMANDE_NUM_DELTA?$conf->global->COMMANDE_NUM_DELTA-1:0;
        }
        else
        {
        	$max=0;
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
        
        dolibarr_syslog("mod_commande_saphir::getNextValue return ".$numFinal);
        return  $numFinal;
    }
  }
    
  
    /**     \brief      Renvoie la référence de commande suivante non utilisée
     *      \param      objsoc      Objet société
     *      \param      commande		Objet commande
     *      \return     string      Texte descripif
     */
    function commande_get_num($objsoc=0,$commande)
    {
        return $this->getNextValue($objsoc,$commande);
    } 
}    

?>