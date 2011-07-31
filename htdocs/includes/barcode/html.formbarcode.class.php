<?php
/* Copyright (C) 2007-2009 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2008-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * $Id: html.formbarcode.class.php,v 1.16 2011/07/31 23:29:11 eldy Exp $
 */

/**
        \file       htdocs/includes/barcode/html.formbarcode.class.php
        \brief      Fichier de la classe des fonctions predefinie de composants html
        \version    $Revision: 1.16 $
*/


/**
        \class      Form
        \brief      Classe permettant la generation de composants html
*/
class FormBarCode
{
	var $db;
	var $error;


	/**
		\brief     Constructeur
		\param     DB      handler d'acc�s base de donn�e
	*/
	function FormBarCode($DB)
	{
		$this->db = $DB;

		return 1;
	}


	/**
	 *	Return HTML select with list of bar code generators
	 *  @param      selected        Id code pre-selected
	 *  @param 		barcodelist		Array of barcodes generators
	 *  @param      code_id         Id du code barre
	 *  @param      idForm			Id du formulaire
	 * 	@return		string			HTML select string
	 */
	function setBarcodeEncoder($selected=0,$barcodelist,$code_id,$idForm='formbarcode')
	{
		global $conf, $langs;

		$disable = '';

		if ($conf->use_javascript_ajax)
		{
            print "\n".'<script type="text/javascript" language="javascript">';
            print 'jQuery(document).ready(function () {
                        jQuery("#select'.$idForm.'").change(function() {
                            var formName = document.getElementById("form'.$idForm.'");
                            formName.action.value="setcoder";
                            formName.submit();
                        });
               });';
            print '</script>'."\n";
    		 //onChange="barcode_coder_save(\''.$idForm.'\')
		}

		// We check if barcode is already selected by default
		if ((($conf->product->enabled || $conf->service->enabled) && $conf->global->PRODUIT_DEFAULT_BARCODE_TYPE == $code_id) ||
		    ($conf->societe->enabled && $conf->global->GENBARCODE_BARCODETYPE_THIRDPARTY == $code_id))
		{
			$disable = 'disabled="disabled"';
		}

		$select_encoder = '<form action="'.DOL_URL_ROOT.'/includes/modules/barcode/admin/barcode.php" method="post" id="form'.$idForm.'">';
		$select_encoder.= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		$select_encoder.= '<input type="hidden" name="action" value="update">';
		$select_encoder.= '<input type="hidden" name="code_id" value="'.$code_id.'">';
		$select_encoder.= '<select id="select'.$idForm.'" class="flat" name="coder">';
		$select_encoder.= '<option value="0"'.($selected==0?' selected="selected"':'').' '.$disable.'>'.$langs->trans('Disable').'</option>';
		$select_encoder.= '<option value="-1" disabled="disabled">--------------------</option>';
		foreach($barcodelist as $key => $value)
		{
			$select_encoder.= '<option value="'.$key.'"'.($selected==$key?' selected="selected"':'').'>'.$value.'</option>';
		}
		$select_encoder.= '</select></form>';

		return $select_encoder;
	}

   /**
     *    \brief      Retourne la liste des types de codes barres
     *    \param      selected          Id code pre-selected
     *    \param      htmlname          Nom de la zone select
     *    \param      useempty          Affiche valeur vide dans liste
     */
    function select_barcode_type($selected='',$htmlname='coder_id',$useempty=0)
    {
        global $langs,$conf;

        $sql = "SELECT rowid, code, libelle";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_barcode_type";
        $sql.= " WHERE coder <> '0'";
        $sql.= " AND entity = ".$conf->entity;
        $sql.= " ORDER BY code";

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
            	print '<option value="0" selected="selected">'.$langs->trans('NoActivatedBarcode').'</option>';
            }

            while ($i < $num)
            {
                $obj = $this->db->fetch_object($result);
                if ($selected == $obj->rowid)
                {
                    print '<option value="'.$obj->rowid.'" selected="selected">';
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
            dol_print_error($this->db);
        }
    }

   /**
     *    	\brief      Affiche formulaire de selection du type de code barre
     *    	\param      page        	Page
     *    	\param      selected    	Id condition pr�-s�lectionn�e
     *    	\param      htmlname    	Nom du formulaire select
     */
    function form_barcode_type($page, $selected='', $htmlname='barcodetype_id')
    {
        global $langs,$conf;
        if ($htmlname != "none")
        {
            print '<form method="post" action="'.$page.'">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<input type="hidden" name="action" value="setbarcodetype">';
            print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
            print '<tr><td>';
            $this->select_barcode_type($selected, $htmlname, 1);
            print '</td>';
            print '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'">';
            print '</td></tr></table></form>';
        }
    }

}

?>
