<?php
/* Copyright (C) 2007-2008 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2008      Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * $Id$
 */

/**
        \file       htdocs/html.form.class.php
        \brief      Fichier de la classe des fonctions prédéfinie de composants html
        \version    $Revision$
*/


/**
        \class      Form
        \brief      Classe permettant la génération de composants html
*/

class FormBarCode
{
	var $db;
	var $error;


	/**
		\brief     Constructeur
		\param     DB      handler d'accès base de donnée
	*/
	function FormBarCode($DB)
	{
		$this->db = $DB;
		
		return 1;
	}


	/**
	 *    \brief      Liste de sélection du générateur de codes barres
	 *    \param      selected          Id code pré-sélectionné
	 *    \param      code_id           Id du code barre
	 *    \param      idForm            Id du formulaire
	 */
	function setBarcodeEncoder($selected=0,$barcodelist,$code_id,$idForm='formbarcode')
	{
		global $conf, $langs;
		
		$disable = '';
		
		// On vérifie si le code de barre est déjà sélectionné par défaut dans le module produit
		if ($conf->produit->enabled && $conf->global->PRODUIT_DEFAULT_BARCODE_TYPE == $code_id)
		{
			$disable = 'disabled="disabled"';
		}
		
		$select_encoder = '<form action="barcode.php" method="post" id="'.$idForm.'">';
		$select_encoder.= '<input type="hidden" name="action" value="update">';
		$select_encoder.= '<input type="hidden" name="code_id" value="'.$code_id.'">';
		$select_encoder.= '<select class="flat" name="coder" onChange="barcode_coder_save(\''.$idForm.'\')">';
		$select_encoder.= '<option value="0"'.($selected==0?' selected="true"':'').' '.$disable.'>'.$langs->trans('Disable').'</option>';
		$select_encoder.= '<option value="-1" disabled="disabled">--------------------</option>';
		foreach($barcodelist as $key => $value)
		{
			$select_encoder.= '<option value="'.$key.'"'.($selected==$key?' selected="true"':'').'>'.$value.'</option>';
		}
		$select_encoder.= '</select></form>';

		return $select_encoder;
	}
   
   /**
     *    \brief      Retourne la liste des types de codes barres
     *    \param      selected          Id code pré-sélectionné
     *    \param      htmlname          Nom de la zone select
     *    \param      useempty          Affiche valeur vide dans liste
     */
    function select_barcode_type($selected='',$htmlname='coder_id',$useempty=0)
    {
        global $langs;
        
        $sql = "SELECT rowid, code, libelle";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_barcode_type";
        $sql.= " WHERE coder <> '0'";
        $sql.= " ORDER BY rowid";
        $result = $this->db->query($sql);
        if ($result)
        {
            $num = $this->db->num_rows($result);
            $i = 0;
            
            if ($useempty && $num > 0) 
            {
            	print '<select class="flat" name="'.$htmlname.'">';
            	print '<option value="0">&nbsp;</option>';
            }
            else
            {
            	print '<select disabled="disabled" class="flat" name="'.$htmlname.'">';
            	print '<option value="0" selected="true">'.$langs->trans('NoActivatedBarcode').'</option>';
            }
 
            while ($i < $num)
            {
                $obj = $this->db->fetch_object($result);
                if ($selected == $obj->rowid)
                {
                    print '<option value="'.$obj->rowid.'" selected="true">';
                }
                else
                {
                    print '<option value="'.$obj->rowid.'">';
                }
                print $obj->libelle;
                print '</option>';
                $i++;
            }
            print "</select>";
        }
        else {
            dolibarr_print_error($this->db);
        }
    }
    
   /**
     *    	\brief      Affiche formulaire de selection du type de code barre
     *    	\param      page        	Page
     *    	\param      selected    	Id condition pré-sélectionnée
     *    	\param      htmlname    	Nom du formulaire select
     */
    function form_barcode_type($page, $selected='', $htmlname='barcodetype_id')
    {
        global $langs,$conf;
        if ($htmlname != "none")
        {
            print '<form method="post" action="'.$page.'">';
            print '<input type="hidden" name="action" value="setbarcodetype">';
            print '<table class="noborder" cellpadding="0" cellspacing="0">';
            print '<tr><td>';
            $this->select_barcode_type($selected, $htmlname, 1);
            print '</td>';
            print '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'">';
            print '</td></tr></table></form>';
        }
    }

}

?>
