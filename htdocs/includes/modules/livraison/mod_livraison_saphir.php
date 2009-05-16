<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
	\file       htdocs/includes/modules/livraison/mod_livraison_saphir.php
	\ingroup    expedition
	\brief      Fichier contenant la classe du modele de numerotation de reference de livraison Saphir
	\version    $Id$
*/

require_once(DOL_DOCUMENT_ROOT ."/includes/modules/livraison/modules_livraison.php");

/**
	\class      mod_livraison_saphir
	\brief      Classe du mod�le de num�rotation de r�f�rence de livraison Saphir
*/
class mod_livraison_saphir extends ModeleNumRefDeliveryOrder
{
	var $version='dolibarr';		// 'development', 'experimental', 'dolibarr'
	var $error = '';
	var $nom = 'Saphir';


    /**     \brief      Renvoi la description du modele de numerotation
     *      \return     string      Texte descripif
     */
	function info()
	{
    	global $conf,$langs;

		$langs->load("bills");

		$form = new Form($db);

		$texte = $langs->trans('GenericNumRefModelDesc')."<br>\n";
		$texte.= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		$texte.= '<input type="hidden" name="token_level_1" value="'.$_SESSION['newtoken'].'">';
		$texte.= '<input type="hidden" name="action" value="updateMask">';
		$texte.= '<input type="hidden" name="maskconstdelivery" value="LIVRAISON_SAPHIR_MASK">';
		$texte.= '<table class="nobordernopadding" width="100%">';

		$tooltip=$langs->trans("GenericMaskCodes",$langs->transnoentities("Delivery"));
		$tooltip.=$langs->trans("GenericMaskCodes2");
		$tooltip.=$langs->trans("GenericMaskCodes3");
		$tooltip.=$langs->trans("GenericMaskCodes4a",$langs->transnoentities("Delivery"),$langs->transnoentities("Delivery"));
		$tooltip.=$langs->trans("GenericMaskCodes5");

		// Parametrage du prefix
		$texte.= '<tr><td>'.$langs->trans("Mask").':</td>';
		$texte.= '<td align="right">'.$form->textwithpicto('<input type="text" class="flat" size="24" name="maskdelivery" value="'.$conf->global->LIVRAISON_SAPHIR_MASK.'">',$tooltip,1,1).'</td>';

		$texte.= '<td align="left" rowspan="2">&nbsp; <input type="submit" class="button" value="'.$langs->trans("Modify").'" name="Button"></td>';

		$texte.= '</tr>';

		$texte.= '</table>';
		$texte.= '</form>';

		return $texte;
    }

    /**     \brief      Renvoi un exemple de num�rotation
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
	*      	\param      objsoc      Object third party
	*      	\param      livraison	Object delivery
	*      	\return     string      Value if OK, 0 if KO
	*/
    function getNextValue($objsoc=0,$livraison='')
    {
		global $db,$conf;

		require_once(DOL_DOCUMENT_ROOT ."/lib/functions2.lib.php");

		// On d�fini critere recherche compteur
		$mask=$conf->global->LIVRAISON_SAPHIR_MASK;

		if (! $mask)
		{
			$this->error='NotConfigured';
			return 0;
		}

		$numFinal=get_next_value($db,$mask,'livraison','ref','',$objsoc->code_client,$livraison->date_livraison);

		return  $numFinal;
    }


	/**		\brief      Return next free value
    *      	\param      objsoc      Object third party
	* 		\param		objforref	Object for number to search
    *   	\return     string      Next free value
    */
    function getNumRef($objsoc,$objforref)
    {
        return $this->getNextValue($objsoc,$objforref);
    }


	/**     \brief      Renvoie la r�f�rence de commande suivante non utilis�e
     *      \param      objsoc      Objet soci�t�
     *      \param      livraison	Objet livraison
     *      \return     string      Texte descripif
     */
    function livraison_get_num($objsoc=0,$livraison='')
    {
        return $this->getNextValue($objsoc,$livraison);
    }

}

?>