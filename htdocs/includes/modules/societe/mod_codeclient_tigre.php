<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007      Regis Houssin        <regis@dolibarr.fr>
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
 */

/**
        \file       htdocs/includes/modules/societe/mod_codeclient_tigre.class.php
        \ingroup    societe
        \brief      Fichier de la classe des gestion tigre des codes clients
        \version    $Revision$
*/

require_once(DOL_DOCUMENT_ROOT."/includes/modules/societe/modules_societe.class.php");


/**
        \class 		mod_codeclient_tigre
        \brief 		Classe permettant la gestion tigre des codes tiers
*/
class mod_codeclient_tigre extends ModeleThirdPartyCode
{
	var $nom;							// Nom du modele
	var $code_modifiable;				// Code modifiable
	var $code_modifiable_invalide;		// Code modifiable si il est invalide
	var $code_modifiable_null;			// Code modifiables si il est null
	var $code_null;						// Code facultatif
	var $version;		// 'development', 'experimental', 'dolibarr'
	var $code_auto; // Numérotation automatique
	
	var $searchcode; // String de recherche
	var $numbitcounter; // Nombre de chiffres du compteur
	var $prefixIsRequired; // Le champ préfix du tiers doit etre renseigné quand on utilise {pre}

	
	/**		\brief      Constructeur classe
	*/
	function mod_codeclient_tigre()
	{
		$this->nom = "Tigre";
		$this->version = "experimental";
		$this->code_modifiable = 0;
		$this->code_modifiable_invalide = 1;
		$this->code_modifiable_null = 1;
		$this->code_null = 0;
		$this->code_auto = 1;
		$this->prefixIsRequired = 0;
	}

	
	/**		\brief      Renvoi la description du module
	*      	\return     string      Texte descripif
	*/
	function info($langs)
	{
		global $conf,$langs;

		  $langs->load("companies");
		  
		  $form = new Form($db);
    	
      $texte = $langs->trans('TigreNumRefModelDesc1')."<br>\n";
      $texte.= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
      $texte.= '<input type="hidden" name="action" value="updateMask">';
      $texte.= '<table class="nobordernopadding" width="100%">';
      
      // Paramétrage du masque
      $texte.= '<tr><td>'.$langs->trans("CustomerCodeModel").'</td>';
      $texte.= '<td align="right">'.$form->textwithhelp('<input type="text" class="flat" size="24" name="maskcustomer" value="'.$conf->global->CODE_TIGRE_MASK_CUSTOMER.'">',$langs->trans("TigreMaskCodes"),1,1).'</td>';
      $texte.= '</tr>';
      
      $texte.= '<tr><td>'.$langs->trans("SupplierCodeModel").'</td>';
      $texte.= '<td align="right">'.$form->textwithhelp('<input type="text" class="flat" size="24" name="masksupplier" value="'.$conf->global->CODE_TIGRE_MASK_SUPPLIER.'">',$langs->trans("TigreMaskCodes"),1,1).'</td>';
      $texte.= '</tr>';
     
      $texte.= '<tr><td>&nbsp;</td><td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'" name="Button"></td></tr>';

      $texte.= '</table>';
      $texte.= '</form>';

      return $texte;
	}


	/**		\brief      Renvoi la description du module
	*     \param      $type       Client ou fournisseur (1:client, 2:fournisseur)
	*     \return     string      Texte descripif
	*/
	function getExample($langs,$objsoc=0,$type=-1)
	{
		if ($type == 0)
		{
			$example = $this->getNextValue($objsoc,0);
		}
		else if ($type == 1)
		{
			$example = $this->getNextValue($objsoc,1);
		}
		else
		{
			$example = $this->getNextValue($objsoc,0)."<br>".$this->getNextValue($objsoc,1);
		}
		return $example;
	}
	
	/**		\brief      Renvoi prochaine valeur attribuée
	*     \param      $type       Client ou fournisseur (1:client, 2:fournisseur)
	*     \return     string      Valeur
	*/
  function getNextValue($objsoc=0,$type=-1)
  {
  	global $db,$conf;
  	
  	$mask = $this->buildMask($objsoc,$type);
  	
  	if ($type == 0)
  	{
  		$field = 'code_client';
  	}
  	else if ($type == 1)
  	{
  		$field = 'code_fournisseur';
  	}

    // On récupère la valeur max (réponse immédiate car champ indéxé)
    $posindice  = $this->numbitcounter;

    $sql = "SELECT MAX(0+SUBSTRING(".$field.",-".$posindice."))";
    $sql.= " FROM ".MAIN_DB_PREFIX."societe";
    $sql.= " WHERE ".$field." REGEXP '^".$this->searchcode."$'";

    $resql=$db->query($sql);

    if ($resql)
    {
    	$row = $db->fetch_row($resql);
      $max = $row[0];
    }
    
    // On applique le nombre de chiffres du compteur
    $arg = '%0'.$this->numbitcounter.'s';

    $num = sprintf($arg,$max+1);
    $mask = eregi_replace('\{0+\}',$num,$mask);
      
    dolibarr_syslog("mod_codeclient_tigre::getNextValue return ".$mask);

    return $mask;
  }
  
 /**		\brief      Construction du masque de numérotation
 	*     \param      objsoc      Objet société
	*     \param      $type       Client ou fournisseur (1:client, 2:fournisseur)
	*     \return     string      Valeur
	*/
  function buildMask($objsoc=0,$type=-1)
  {
  	global $conf;

  	if ($type==0)
  	{
  		$mask = $conf->global->CODE_TIGRE_MASK_CUSTOMER;
  	}
  	else if ($type==1)
  	{
  		$mask = $conf->global->CODE_TIGRE_MASK_SUPPLIER;
  	}
  	
  	$maskElement = preg_split('/[|]{1}/', $mask);

  	$foundCounter = 0;
  	$substrBegin = 0;
  	$substrEnd = 0;
  	$maskRebuild = '';
  	$error = 0;
  	$this->searchcode = '';

  	for ($i = 0; $i < count($maskElement); $i++)
    {
      	// Ajout du jour en cours
      	if ($maskElement[$i] == '{dd}')
  	    {
  		    $maskRebuild .= strftime("%d",time());
  		    $this->searchcode .= '([0-9]{2})';
  	    }
  	    else if (eregi('\{d+\}',$maskElement[$i]) && (eregi('\{d+\}',$$maskElement[$i]) != '{dd}'))
  	    {
  		    $error++;
  	    }
  	
  	    // Ajout du mois en cours
  	    if ($maskElement[$i] == '{mm}')
  	    {
  		    $maskRebuild .= strftime("%m",time());
  		    $this->searchcode .= '([0-9]{2})';
  	    }
  	    else if (eregi('\{m+\}',$maskElement[$i]) && (eregi('\{m+\}',$maskElement[$i]) != '{mm}'))
  	    {
  		    $error++;
  	    }
  	
  	    // Ajout de l'année en cours
  	    if ($maskElement[$i] == '{aa}')
  	    {
  		    $maskRebuild .= substr(strftime("%Y",time()),2);
  		    $this->searchcode .= '([0-9]{2})';
  	    }
  	    else if ($maskElement[$i] == '{aaaa}')
  	    {
  		    $maskRebuild .= strftime("%Y",time());
  		    $this->searchcode .= '([0-9]{4})';
  	    }
  	    else if (eregi('\{a+\}',$maskElement[$i]) && ((eregi('\{a+\}',$maskElement[$i]) != '{aa}') || (eregi('\{a+\}',$maskElement[$i]) != '{aaa}')))
  	    {
  		    $error++;
  	    }
  	
  	    // Ajout du préfix de la société
  	    if (is_object($objsoc) && $objsoc->prefix_comm && $maskElement[$i] == '{pre}')
  	    {
  		    if ((!$objsoc->fournisseur && !$objsoc->code_fournisseur) || (!$objsoc->client && !$objsoc->code_client))
  		    {
  		    	$maskRebuild .= strtoupper($objsoc->prefix_comm);
  		    	$this->searchcode .= '([0-9A-Z]{1,})';
  		    }
  		    else
  		    {
  	    		$maskRebuild .= 'ABC';
  	    		$this->searchcode .= '([0-9A-Z]{1,})';
  	    	}
  	    }
  	    else if (is_object($objsoc) && !$objsoc->prefix_comm && $maskElement[$i] == '{pre}')
  	    {
  		    $maskRebuild .= 'ABC';
  		    $error++;
  		    $this->prefixIsRequired = 1;
  	    }
  	    else if (!is_object($objsoc) && $maskElement[$i] == '{pre}')
  	    {
  	    	if (is_string($objsoc) && $objsoc)
  	    	{
  	    		$maskRebuild .= $objsoc;
  	    		$this->searchcode .= '([0-9A-Z]{1,})';
  	    	}
  	    	else if ($objsoc === 0)
  	    	{
  	    		$maskRebuild .= 'ABC';
  	    		$this->searchcode .= '([0-9A-Z]{1,})';
  	    	}
  	    	else
  	    	{
  	    		$error++;
  	    		$this->prefixIsRequired = 1;
  	    	}
  	    }
  	    
  	    // Ajout des séparateurs éventuels : \ / -
  	    if (eregi('[\/-]{1}',$maskElement[$i]))
  	    {
  	    	$maskRebuild .= $maskElement[$i];
  	    	$this->searchcode .= '([\/-]{1})';
  	    }
  	    else if (eregi('[\/-]{2,}',$maskElement[$i]))
  	    {
  	    	$error++;
  	    }
  	    
  	    // Ajout des champs libres éventuels
  	    if (eregi('^[0-9A-Z]+$',$maskElement[$i]))
  	    {
  	    	$maskRebuild .= strtoupper($maskElement[$i]);
  	    	$this->searchcode .= '([0-9A-Z]+)';
  	    }
  	
  	    // Définition du compteur
  	    if (eregi('\{0+\}',$maskElement[$i]))
  	    {
  		    // Défini le nombre de chiffres du compteur
  		    $this->numbitcounter = strlen(substr($maskElement[$i],1,-1));
  		    // Permettra d'effectuer une recherche dans la table
  		    $this->searchcode .= '([0-9]{'.$this->numbitcounter.'})';
  		    
  		    $maskRebuild .= $maskElement[$i];
  		    $foundCounter = 1;
  	    }
  	    else if ($i == count($maskElement) && !eregi('\{0+\}',$maskElement[$i]) && $foundCounter == 0)
  	    {
  	    	$error++;
  	    }
  	}

  	return $maskRebuild;
  }
  
 /**
  *   \brief  Vérifie si le mask utilise le préfix
  *
  */
  function verif_prefixIsUsed()
  {
  	global $conf;
  	
  	$mask = $conf->global->CODE_TIGRE_MASK_CUSTOMER;
  	if (eregi('\{pre\}',$mask)) return 1;
  	
  	$mask = $conf->global->CODE_TIGRE_MASK_SUPPLIER;
  	if (eregi('\{pre\}',$mask)) return 1;
  	
  	return 0;
  }


	/**
	* 		\brief		Vérifie la validité du code
	*		\param		$db			Handler acces base
	*		\param		$code		Code a vérifier/corriger
	*		\param		$soc		Objet societe
	*   \param    $type   0 = client/prospect , 1 = fournisseur
	*		\return		int			<0 si KO, 0 si OK
	*/
	function verif($db, &$code, $soc, $type)
	{
		$result=0;
		$code = strtoupper(trim($code));

		if (! $code && $this->code_null) 
		{
			$result=0;
		}
		else
		{
			if ($this->verif_syntax($code) >= 0)
			{	
				$is_dispo = $this->verif_dispo($db, $code, $soc);
				if ($is_dispo <> 0)
				{
					$result=-3;
				}
				else if ($type == 0 && $soc->prefixCustomerIsRequired && !$soc->prefix_comm)
				{
					$result=-4;
				}
				else if ($type == 1 && $soc->prefixSupplierIsRequired && !$soc->prefix_comm)
				{
					$result=-5;
				}
				else
				{
					$result=0;
				}
			}
			else
			{
				if (strlen($code) == 0)
				{
					$result=-2;
				}
				else
				{
					$result=-1;
				}
			}
		}
		dolibarr_syslog("mod_codeclient_tigre::verif result=".$result);
		return $result;
	}

	
	/**
	*		\brief		Renvoi une valeur correcte
	*		\param		$db			Handler acces base
	*		\param		$code		Code reference eventuel
	*		\return		string		Code correct, <0 si KO
	*/
	function get_correct($db, $code)
	{
		
	}

	
	/**
	*		\brief		Renvoi si un code est pris ou non (par autre tiers)
	*		\param		$db			Handler acces base
	*		\param		$code		Code a verifier
	*		\param		$soc		Objet societe
	*		\return		int			0 si dispo, <0 si erreur
	*/
	function verif_dispo($db, $code, $soc)
	{
		$sql = "SELECT code_client FROM ".MAIN_DB_PREFIX."societe";
		$sql.= " WHERE code_client = '".$code."'";
		$sql.= " AND rowid != '".$soc->id."'";

		$resql=$db->query($sql);
		if ($resql)
		{
			if ($db->num_rows($resql) == 0)
			{
				return 0;
			}
			else
			{
				return -1;
			}
		}
		else
		{
			return -2;
		}

	}


	/**
	*	\brief		Renvoi si un code respecte la syntaxe
	*	\param		$code		Code a verifier
	*	\return		int			0 si OK, <0 si KO
	*/
	function verif_syntax($code)
	{
		$res = 0;
		return $res;
	}


	/**
	*	Renvoi 0 si numerique, sinon renvoi nb de car non numerique
	*/
	function is_num($str)
	{
		$ok = 0;
		return $ok;
	}

}

?>
