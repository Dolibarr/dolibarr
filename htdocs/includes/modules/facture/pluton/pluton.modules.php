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
 *
 * $Id$
 * $Source$
 *
 */

/**
	\file       htdocs/includes/modules/facture/pluton/pluton.modules.php
	\ingroup    facture
	\brief      Fichier contenant la classe du mod�le de num�rotation de r�f�rence de facture Pluton
	\version    $Revision$
*/

require_once(DOL_DOCUMENT_ROOT ."/includes/modules/facture/modules_facture.php");

/**
	\class      mod_facture_pluton
	\brief      Classe du mod�le de num�rotation de r�f�rence de facture Pluton
*/
class mod_facture_pluton extends ModeleNumRefFactures
{
	var $version='development';		// 'development', 'experimental', 'dolibarr'
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

    /**     \brief      Renvoi la description du modele de num�rotation
     *      \return     string      Texte descripif
     */
	function info()
    {
    	global $conf,$langs;

		  $langs->load("bills");
		  
		  $form = new Form($db);
    	
      $texte = $langs->trans('PlutonNumRefModelDesc1')."<br>\n";
      $texte.= '<table class="nobordernopadding" width="100%">';
      
      // Param�trage de la matrice
      $texte.= '<tr><td>Matrice de disposition des objets (prefix,mois,ann�e,compteur...)</td>';
      $texte.= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
      $texte.= '<input type="hidden" name="action" value="updateMatrice">';
      $texte.= '<td align="right"><input type="text" class="flat" size="30" name="matrice" value="'.$conf->global->FACTURE_NUM_MATRICE.'"></td>';
      $texte.= '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'" name="Button"></td>';
      $texte.= '<td aligne="center">'.$form->textwithhelp('',$langs->trans("MatriceInvoiceDesc"),1,1).'</td>';
      $texte.= '</tr></form>';
      
      // Param�trage du prefix des factures
      $texte.= '<tr><td>Pr�fix des factures</td>';
      $texte.= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
      $texte.= '<input type="hidden" name="action" value="updatePrefixFacture">';
      $texte.= '<td align="right"><input type="text" class="flat" size="30" name="prefixfacture" value="'.$conf->global->FACTURE_NUM_PREFIX.'"></td>';
      $texte.= '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'" name="Button"></td>';
      $texte.= '<td aligne="center">'.$form->textwithhelp('',$langs->trans("PrefixInvoiceDesc"),1,1).'</td>';
      $texte.= '</tr></form>';
      
      // Param�trage du prefix des avoirs
      $texte.= '<tr><td>Pr�fix des avoirs</td>';
      $texte.= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
      $texte.= '<input type="hidden" name="action" value="updatePrefixAvoir">';
      $texte.= '<td align="right"><input type="text" class="flat" size="30" name="prefixavoir" value="'.$conf->global->AVOIR_NUM_PREFIX.'"></td>';
      $texte.= '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'" name="Button"></td>';
      $texte.= '<td aligne="center">'.$form->textwithhelp('',$langs->trans("PrefixCreditNoteDesc"),1,1).'</td>';
      $texte.= '</tr></form>';
      
      // On d�termine un offset sur le compteur des factures
      $texte.= '<tr><td>Appliquer un offset sur le compteur des factures</td>';
      $texte.= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
      $texte.= '<input type="hidden" name="action" value="setOffsetInvoice">';
      $texte.= '<td align="right"><input type="text" class="flat" size="30" name="offsetinvoice" value="'.$conf->global->FACTURE_NUM_DELTA.'"></td>';
      $texte.= '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'" name="Button"></td>';
      $texte.= '<td aligne="center">'.$form->textwithhelp('',$langs->trans("OffsetDesc"),1,1).'</td>';
      $texte.= '</tr></form>';
      
      // On d�termine un offset sur le compteur des avoirs
      $texte.= '<tr><td>Appliquer un offset sur le compteur des avoirs</td>';
      $texte.= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
      $texte.= '<input type="hidden" name="action" value="setOffsetCreditNote">';
      $texte.= '<td align="right"><input type="text" class="flat" size="30" name="offsetcreditnote" value="'.$conf->global->AVOIR_NUM_DELTA.'"></td>';
      $texte.= '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'" name="Button"></td>';
      $texte.= '<td aligne="center">'.$form->textwithhelp('',$langs->trans("OffsetDesc"),1,1).'</td>';
      $texte.= '</tr></form>';
   
      // On d�fini si le compteur se remet � zero en debut d'ann�e
      $texte.= '<tr><td>Le compteur se remet � z�ro en d�but d\'ann�e</td>';
      $texte.= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
      $texte.= '<input type="hidden" name="action" value="setNumRestart">';
      $texte.= '<td align="right">';
      $texte.= $form->selectyesno('numrestart',$conf->global->FACTURE_NUM_RESTART_BEGIN_YEAR,1);
      $texte.= '</td><td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'" name="Button"></td>';
      $texte.= '<td aligne="center">'.$form->textwithhelp('',$langs->trans("NumRestartDesc"),1,1).'</td>';
      $texte.= '</tr></form>';
      
      // On d�fini si le compteur des avoirs s'incr�mente avec les factures
      $texte.= '<tr><td>La num�rotation des avoirs s\'incr�mente avec les factures</td>';
      $texte.= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
      $texte.= '<input type="hidden" name="action" value="setNumWithInvoice">';
      $texte.= '<td align="right">';
      $texte.= $form->selectyesno('numwithinvoice',$conf->global->AVOIR_NUM_WITH_INVOICE,1);
      $texte.= '</td><td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'" name="Button"></td>';
      $texte.= '<td aligne="center">'.$form->textwithhelp('',$langs->trans("CreditNoteNumWithInvoiceDesc"),1,1).'</td>';
      $texte.= '</tr></form>';
      
      // On affiche le debut d'ann�e fiscale
      $texte.= '<tr><td colspan="3">D�but d\'ann�e fiscale : '.monthArrayOrSelected($conf->global->SOCIETE_FISCAL_MONTH_START).'</td>';
      $texte.= '</tr>';
      
      $texte.= '</table><br>';

      return $texte;
    }

    /**     \brief      Renvoi un exemple de num�rotation
     *      \return     string      Example
     */
    function getExample()
    {
    	global $conf,$langs;
    	
    	$numExample = '';
    	
    	$buildResult = $this->buildMatrice();
        
      if ($buildResult == 1)
      {
      	// On r�cup�re le nombre de chiffres du compteur
    	  $arg = '%0'.$this->numbitcounter.'s';
        $num = sprintf($arg,$conf->global->FACTURE_NUM_DELTA?$conf->global->FACTURE_NUM_DELTA:1);
      
        //On construit le num�ro � partir de la matrice
      	foreach($this->numMatrice as $objetMatrice)
        {
        	if ($objetMatrice == '-') $numExample .= $objetMatrice;
        	if ($objetMatrice == '$prefix') $numExample .= $this->prefix;
        	if ($objetMatrice == '$yy') $numExample .= $this->yy;
        	if ($objetMatrice == '$mm') $numExample .= $this->mm;
        	if ($objetMatrice == '$num') $numExample .= $num;
        }
      }
      else
      {
      	$numExample = $langs->trans('NotConfigured');
      }
      return $numExample;
    }

	/**		\brief      Renvoi prochaine valeur attribu�e
	*      	\param      objsoc      Objet soci�t�
	*      	\param      facture		Objet facture
	*      	\return     string      Valeur
	*/
    function getNextValue($objsoc,$facture)
    {
        global $db,$conf;
        
        $buildResult = $this->buildMatrice($objsoc,$facture);
        
        if ($buildResult == 1)
        {

        // On r�cup�re la valeur max (r�ponse imm�diate car champ ind�x�)
        $posindice  = $this->numbitcounter;
        $searchyy='';
        $sql = "SELECT MAX(facnumber)";
        $sql.= " FROM ".MAIN_DB_PREFIX."facture";
        if ($conf->global->FACTURE_NUM_RESTART_BEGIN_YEAR)
        {
        	$sql.= " WHERE facnumber REGEXP '^".$this->searchLast."'";
        }
        else if ($facture->type == 2)
        {
        	$sql.= " WHERE type = 2 AND facnumber REGEXP '^".$this->prefixcreditnote."'";
        }
        $resql=$db->query($sql);
        if ($resql)
        {
        	$row = $db->fetch_row($resql);
          if ($row) $searchyy = substr($row[0],0,-$posindice);
        }
        
        if ($conf->global->FACTURE_NUM_DELTA || $conf->global->AVOIR_NUM_DELTA)
        {
        	//on v�rifie si il y a une ann�e pr�c�dente
          //pour �viter que le delta soit appliqu� de nouveau sur la nouvelle ann�e
          $previousyy='';
          $sql = "SELECT MAX(facnumber)";
          $sql.= " FROM ".MAIN_DB_PREFIX."facture";
          $sql.= " WHERE facnumber REGEXP '^".$this->searchLastWithPreviousYear."'";
          $resql=$db->query($sql);
          if ($resql)
          {
            $row = $db->fetch_row($resql);
            if ($row) $previousyy = substr($row[0],0,-$posindice);
          }
        }

        // Si au moins un champ respectant le mod�le a �t� trouv�e
        if (eregi('^'.$this->searchLastWithNoYear.'',$searchyy))
        {
            // Recherche rapide car restreint par un like sur champ index�
            $sql = "SELECT MAX(0+SUBSTRING(facnumber,-".$posindice."))";
            $sql.= " FROM ".MAIN_DB_PREFIX."facture";
            $sql.= " WHERE facnumber REGEXP '^".$searchyy."'";
            $resql=$db->query($sql);
            if ($resql)
            {
                $row = $db->fetch_row($resql);
                $max = $row[0];
            }
        }
        else if ($facture->type < 2 && $conf->global->FACTURE_NUM_DELTA != '' && !eregi('^'.$this->searchLastWithPreviousYear.'',$previousyy))
        {
        	// on applique le delta une seule fois
        	$max=$conf->global->FACTURE_NUM_DELTA?$conf->global->FACTURE_NUM_DELTA-1:0;
        }
        else if ($facture->type == 2 && $conf->global->AVOIR_NUM_DELTA != '' && !eregi('^'.$this->searchLastWithPreviousYear.'',$previousyy))
        {
        	// on applique le delta une seule fois
        	$max=$conf->global->AVOIR_NUM_DELTA?$conf->global->AVOIR_NUM_DELTA-1:0;
        }
        else
        {
        	$max=0;
        }

        // On replace le prefix de l'avoir
        if ($conf->global->AVOIR_NUM_WITH_INVOICE && $facture->type == 2)
        {
        	$this->prefix = $this->prefixcreditnote;
        }
    	  
    	  // On applique le nombre de chiffres du compteur
        $arg = '%0'.$this->numbitcounter.'s';
        $num = sprintf($arg,$max+1);
        $numFinal = '';
        
        foreach($this->numMatrice as $objetMatrice)
        {
        	if ($objetMatrice == '-') $numFinal .= $objetMatrice;
        	if ($objetMatrice == '$prefix') $numFinal .= $this->prefix;
        	if ($objetMatrice == '$yy') $numFinal .= $this->yy;
        	if ($objetMatrice == '$mm') $numFinal .= $this->mm;
        	if ($objetMatrice == '$num') $numFinal .= $num;
        } 
        
        dolibarr_syslog("mod_facture_pluton::getNextValue return ".$numFinal);
        return  $numFinal;
    }
  }
    
  
    /**     \brief      Renvoie la r�f�rence de commande suivante non utilis�e
     *      \param      objsoc      Objet soci�t�
     *      \param      facture		Objet facture
     *      \return     string      Texte descripif
     */
    function getNumRef($objsoc=0,$facture)
    {
        return $this->getNextValue($objsoc,$facture);
    }
    
 	/**	
 	* 	\brief      Construction de la matrice de numrotation
	*	\param      objsoc      Objet societe
	*   \return     string      Valeur
	*/
    function buildMatrice($objsoc=0,$facture='')
    {
        global $conf;
        
        $this->prefixinvoice     = $conf->global->FACTURE_NUM_PREFIX;
        $this->prefixcreditnote  = $conf->global->AVOIR_NUM_PREFIX;
        $this->matrice           = $conf->global->FACTURE_NUM_MATRICE;
        $this->searchLast = '';
        $this->searchLastWithNoYear = '';
        $this->searchLastWithPreviousYear = '';
        
        if ($this->matrice != '')
        {
        	$resultatMatrice = Array();
        	
        	$matricePrefix   = "PREF|COM"; // PREF : prefix libre (ex: FA pour facture, AV pour avoir), COM : prefix du client
        	$matriceYear     = "[A]{2,4}"; // l'ann�e est sur 2 ou 4 chiffres
        	$matriceMonth    = "[M]{2}"; // le mois est sur 2 chiffres
        	$matriceCounter  = "[C]{1,}"; //le compteur a un nombre de chiffres libre
        	$matriceTiret    = "[-]{1}"; // on recherche si il y a des tirets de s�paration
        	
        	$matriceSearch   = Array('prefix'=>$matricePrefix,
        	                         'year'=>$matriceYear,
        	                         'month'=>$matriceMonth,
        	                         'counter'=>$matriceCounter
        	                         );
        	
        	// on d�termine l'emplacement des tirets
        	$resultTiret = preg_split('/'.$matriceTiret.'/',$this->matrice, -1, PREG_SPLIT_OFFSET_CAPTURE);
        	
        	$j = 0;
        	$k = 0;
        	
        	// on d�termine les objets de la matrice
        	for ($i = 0; $i < count($resultTiret); $i++)
        	{
        		foreach($resultTiret[$i] as $idResultTiret => $valueResultTiret)
        		{
        			// Ajout des tirets
        		  if ($j != $resultTiret[$i][1])
        		  {
        		  	$this->numMatrice[$k] = '-';
        		  	$this->searchLast .= '-';
        		  	$this->searchLastWithNoYear .= '-';
        		  	$this->searchLastWithPreviousYear .= '-';
        		  	$j = $resultTiret[$i][1];
        		  	$k++;
        		  }
        			foreach($matriceSearch as $idMatrice => $valueMatrice)
        			{
        			$resultCount = eregi(''.$valueMatrice.'',$valueResultTiret,$resultatMatrice);
        			if ($resultCount)
        			{
        				// On r�cup�re le pr�fix utilis�
        				if ($idMatrice == 'prefix')
        				{
        					if ($resultatMatrice[0] == 'COM')
        					{
        						if ($objsoc->prefix_comm)
        						{
        							$this->prefix = $objsoc->prefix_comm;
        						}
        						else
        					  {
        						  $this->prefix = 'COM';
        					  }
        					  $this->numMatrice[$k] = '$prefix';
        					  $this->searchLast .= $this->prefix;
        					  $this->searchLastWithNoYear .= $this->prefix;
        					  $this->searchLastWithPreviousYear .= $this->prefix;
        					  $k++;
        					}
        					else if ($resultatMatrice[0] == 'PREF')
        				  {
        				  	// Les avoirs peuvent suivre la num�rotation des factures
        				  	if (!$conf->global->AVOIR_NUM_WITH_INVOICE && $facture->type == 2)
        					  {
        						  $thisPrefix = $this->prefixcreditnote;
        					  }
        					  else
        					  {
        						  $thisPrefix = $this->prefixinvoice;
        					  }
        					  $this->prefix = $thisPrefix;
        					  $this->numMatrice[$k] = '$prefix';
        					  $this->searchLast .= $this->prefix;
        					  $this->searchLastWithNoYear .= $this->prefix;
        					  $this->searchLastWithPreviousYear .= $this->prefix;
        					  $k++;
        					}
        				}
        				else if ($idMatrice == 'year')
        				{
        					// On r�cup�re le nombre de chiffres pour l'ann�e
        					$numbityear = $resultCount;
        					// On d�fini le mois du d�but d'ann�e fiscale
        					$current_month = date("n");
        					
        					if (is_object($facture) && $facture->date)
                  {
        	          $create_month = strftime("%m",$facture->date);
                  }
                  else
                  {
        	          $create_month = $current_month;
                  }

                  // On change d'ann�e fiscal si besoin
                  if($conf->global->SOCIETE_FISCAL_MONTH_START > 1 && $current_month >= $conf->global->SOCIETE_FISCAL_MONTH_START && $create_month >= $conf->global->SOCIETE_FISCAL_MONTH_START)
                  {
        	          $this->yy = substr(strftime("%Y",mktime(0,0,0,date("m"),date("d"),date("Y")+1)),$numbityear);
                  }
                  else
                  {
        	          $this->yy = substr(strftime("%Y",time()),$numbityear);
                  }
        					$this->numMatrice[$k] = '$yy';
        					$this->searchLast .= $this->yy;
        					for ($l = 1; $l <= $numbityear; $l++)
        					{
        						$this->searchLastWithNoYear .= '[0-9]';
        					}
        					$previousYear = substr(strftime("%Y",mktime(0,0,0,date("m"),date("d"),date("Y")-1)),$numbityear);
        					$this->searchLastWithPreviousYear .= $previousYear;
        					$k++;
        				}
        				else if ($idMatrice == 'month')
        				{
        					// On r�cup�re le mois si besoin
        					$this->mm = strftime("%m",time());
        					$this->numMatrice[$k] = '$mm';
        					$this->searchLast .= '[0-9][0-9]';
        					$this->searchLastWithNoYear .= '[0-9][0-9]';
        					$this->searchLastWithPreviousYear .= '[0-9][0-9]';
        					$k++;
        				}
        				else if ($idMatrice == 'counter')
        				{
        					// On r�cup�re le nombre de chiffres pour le compteur
        					$this->numbitcounter = $resultCount;
        					$this->numMatrice[$k] = '$num';
        					$k++;
        				}
        			}
        		}
        	}
        }
        return 1;
      }
      else
      {
      	return -3;
      }
    }
}    

?>