<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2008 Raphael Bertrand (Resultic)       <raphael.bertrand@resultic.fr>
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
	\file       htdocs/includes/modules/commande/mod_commande_saphir.php
	\ingroup    commande
	\brief      Fichier contenant la classe du modèle de numérotation de référence de commande Saphir
	\version    $Id$
*/

require_once(DOL_DOCUMENT_ROOT ."/includes/modules/commande/modules_commande.php");


/**
	\class      mod_commande_saphir
	\brief      Classe du modèle de numérotation de référence de commande Saphir
*/
class mod_commande_saphir extends ModeleNumRefCommandes
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
    	
		$texte = $langs->trans('GenericNumRefModelDesc')."<br>\n";
		$texte.= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		$texte.= '<input type="hidden" name="action" value="updateMask">';
		$texte.= '<input type="hidden" name="maskconstorder" value="COMMANDE_SAPHIR_MASK">';
		$texte.= '<table class="nobordernopadding" width="100%">';
		
		// Parametrage du prefix des factures
		$texte.= '<tr><td>'.$langs->trans("Mask").':</td>';
		$texte.= '<td align="right">'.$form->textwithhelp('<input type="text" class="flat" size="24" name="maskorder" value="'.$conf->global->COMMANDE_SAPHIR_MASK.'">',$langs->trans("GenericMaskCodes",$langs->transnoentities("Order"),$langs->transnoentities("Order"),$langs->transnoentities("Order")),1,1).'</td>';

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
    	
    	$old_code_client=$mysoc->code_client;
    	$mysoc->code_client='CCCCCCCCCC';
     	$numExample = $this->getNextValue($mysoc,'');
		$mysoc->code_client=$old_code_client;
     	
		if (! $numExample)
		{
			$numExample = $langs->trans('NotConfigured');
		}
		return $numExample;
    }

	/**		\brief      Return next value
	*      	\param      objsoc      Objet third party
	*		\param		commande	Object order
	*      	\return     string      Value if OK, 0 if KO
	*/
    function getNextValue($objsoc,$commande)
    {
		global $db,$conf;

		require_once(DOL_DOCUMENT_ROOT ."/lib/functions2.lib.php");
		
		// On défini critere recherche compteur
		$mask=$conf->global->COMMANDE_SAPHIR_MASK;
		
		if (! $mask) 
		{
			$this->error='NotConfigured';
			return 0;
		}

		$numFinal=get_next_value($db,$mask,'commande','ref','',$objsoc->code_client,$commande->date);
		
		return  $numFinal;
	}
    
  
	/**		\brief      Return next free value
    *      	\param      objsoc      Object third party
	* 		\param		objforref	Object for number to search
    *   	\return     string      Next free value
    */
    function commande_get_num($objsoc,$objforref)
    {
        return $this->getNextValue($objsoc,$objforref);
    }
	
}    

?>