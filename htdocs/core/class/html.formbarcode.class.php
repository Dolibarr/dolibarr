<?php
<<<<<<< HEAD
/* Copyright (C) 2007-2012	Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2008-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 3 of the License, or
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
*/
=======
/* Copyright (C) 2007-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2008-2012  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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
 */
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

/**
 *      \file       htdocs/core/class/html.formbarcode.class.php
 *      \brief      Fichier de la classe des fonctions predefinie de composants html
 */


/**
 *      Class to manage barcode HTML
 */
class FormBarCode
{
<<<<<<< HEAD
    var $db;
    var $error;


    /**
     *	Constructor
     *
     *	@param	DoliDB		$db		Database handler
     */
    function __construct($db)
    {
        $this->db = $db;
        return 1;
=======
    /**
     * @var DoliDB Database handler.
     */
    public $db;

    /**
     * @var string Error code (or message)
     */
    public $error='';


    /**
     *  Constructor
     *
     *  @param  DoliDB		$db		Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    }


    /**
     *	Return HTML select with list of bar code generators
     *
     *  @param	int		$selected       Id code pre-selected
     *  @param 	array	$barcodelist	Array of barcodes generators
     *  @param  int		$code_id        Id du code barre
     *  @param  int		$idForm			Id du formulaire
     * 	@return	string					HTML select string
     */
<<<<<<< HEAD
    function setBarcodeEncoder($selected,$barcodelist,$code_id,$idForm='formbarcode')
=======
    public function setBarcodeEncoder($selected, $barcodelist, $code_id, $idForm = 'formbarcode')
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    {
        global $conf, $langs;

        $disable = '';

        if (!empty($conf->use_javascript_ajax))
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
        if (((! empty($conf->product->enabled) || ! empty($conf->service->enabled)) && $conf->global->PRODUIT_DEFAULT_BARCODE_TYPE == $code_id) ||
        (! empty($conf->societe->enabled) && $conf->global->GENBARCODE_BARCODETYPE_THIRDPARTY == $code_id))
        {
            $disable = 'disabled';
        }
<<<<<<< HEAD
        
=======

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        if (!empty($conf->use_javascript_ajax))
        {
            $select_encoder = '<form action="'.DOL_URL_ROOT.'/admin/barcode.php" method="POST" id="form'.$idForm.'">';
            $select_encoder.= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            $select_encoder.= '<input type="hidden" name="action" value="update">';
            $select_encoder.= '<input type="hidden" name="code_id" value="'.$code_id.'">';
        }
<<<<<<< HEAD
        
=======

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        $selectname=(!empty($conf->use_javascript_ajax)?'coder':'coder'.$code_id);
        $select_encoder.= '<select id="select'.$idForm.'" class="flat" name="'.$selectname.'">';
        $select_encoder.= '<option value="0"'.($selected==0?' selected':'').' '.$disable.'>'.$langs->trans('Disable').'</option>';
        $select_encoder.= '<option value="-1" disabled>--------------------</option>';
        foreach($barcodelist as $key => $value)
        {
            $select_encoder.= '<option value="'.$key.'"'.($selected==$key?' selected':'').'>'.$value.'</option>';
        }
        $select_encoder.= '</select>';
<<<<<<< HEAD
        
=======

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        if (!empty($conf->use_javascript_ajax))
        {
            $select_encoder.= '</form>';
        }

        return $select_encoder;
    }

<<<<<<< HEAD
    /**
     *	Return form to select type of barcode
     *
     *	@param	int		$selected          Id code pre-selected
     *  @param	string	$htmlname          Name of HTML select field
     *  @param  int		$useempty          Affiche valeur vide dans liste
     *  @return	void
     */
    function select_barcode_type($selected='',$htmlname='barcodetype_id',$useempty=0)
    {
        global $langs,$conf;
=======
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *  Print form to select type of barcode
     *
     *  @param  int     $selected          Id code pre-selected
     *  @param  string  $htmlname          Name of HTML select field
     *  @param  int     $useempty          Affiche valeur vide dans liste
     *  @return void
     *  @deprecated
     */
    public function select_barcode_type($selected = '', $htmlname = 'barcodetype_id', $useempty = 0)
    {
        // phpcs:enable
        print $this->selectBarcodeType($selected, $htmlname, $useempty);
    }

    /**
     *  Return html form to select type of barcode
     *
     *  @param  int     $selected          Id code pre-selected
     *  @param  string  $htmlname          Name of HTML select field
     *  @param  int     $useempty          Display empty value in select
     *  @return string
     */
    public function selectBarcodeType($selected = '', $htmlname = 'barcodetype_id', $useempty = 0)
    {
        global $langs, $conf;

        $out = '';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

        $sql = "SELECT rowid, code, libelle";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_barcode_type";
        $sql.= " WHERE coder <> '0'";
        $sql.= " AND entity = ".$conf->entity;
        $sql.= " ORDER BY code";

        $result = $this->db->query($sql);
<<<<<<< HEAD
        if ($result)
        {
            $num = $this->db->num_rows($result);
            $i = 0;

            if ($useempty && $num > 0)
            {
                print '<select class="flat minwidth75imp" name="'.$htmlname.'" id="select_'.$htmlname.'">';
                print '<option value="0">&nbsp;</option>';
            }
            else
            {
                $langs->load("errors");
                print '<select disabled class="flat minwidth75imp" name="'.$htmlname.'" id="select_'.$htmlname.'">';
                print '<option value="0" selected>'.$langs->trans('ErrorNoActivatedBarcode').'</option>';
            }

            while ($i < $num)
            {
                $obj = $this->db->fetch_object($result);
                if ($selected == $obj->rowid)
                {
                    print '<option value="'.$obj->rowid.'" selected>';
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
            print ajax_combobox("select_".$htmlname);
=======
        if ($result) {
            $num = $this->db->num_rows($result);
            $i = 0;

            if ($useempty && $num > 0) {
                $out .= '<select class="flat minwidth75imp" name="' . $htmlname . '" id="select_' . $htmlname . '">';
                $out .= '<option value="0">&nbsp;</option>';
            } else {
                $langs->load("errors");
                $out .= '<select disabled class="flat minwidth75imp" name="' . $htmlname . '" id="select_' . $htmlname . '">';
                $out .= '<option value="0" selected>' . $langs->trans('ErrorNoActivatedBarcode') . '</option>';
            }

            while ($i < $num) {
                $obj = $this->db->fetch_object($result);
                if ($selected == $obj->rowid) {
                    $out .= '<option value="' . $obj->rowid . '" selected>';
                } else {
                    $out .= '<option value="' . $obj->rowid . '">';
                }
                $out .= $obj->libelle;
                $out .= '</option>';
                $i++;
            }
            $out .= "</select>";
            $out .= ajax_combobox("select_".$htmlname);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        }
        else {
            dol_print_error($this->db);
        }
<<<<<<< HEAD
    }

=======
        return $out;
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    /**
     *  Show form to select type of barcode
     *
     *  @param  string		$page        	Page
     *  @param  int			$selected    	Id condition preselected
     *  @param  string		$htmlname    	Nom du formulaire select
     *  @return	void
<<<<<<< HEAD
     */
    function form_barcode_type($page, $selected='', $htmlname='barcodetype_id')
    {
        global $langs,$conf;
        if ($htmlname != "none")
        {
            print '<form method="post" action="'.$page.'">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<input type="hidden" name="action" value="set'.$htmlname.'">';
            print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
            print '<tr><td>';
            $this->select_barcode_type($selected, $htmlname, 1);
            print '</td>';
            print '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'">';
            print '</td></tr></table></form>';
        }
    }

}

=======
     *  @deprecated
     */
    public function form_barcode_type($page, $selected = '', $htmlname = 'barcodetype_id')
    {
        // phpcs:enable
        print $this->formBarcodeType($page, $selected, $htmlname);
    }

    /**
     *  Return html form to select type of barcode
     *
     *  @param  string      $page           Page
     *  @param  int         $selected       Id condition preselected
     *  @param  string      $htmlname       Nom du formulaire select
     *  @return string
     */
    public function formBarcodeType($page, $selected = '', $htmlname = 'barcodetype_id')
    {
        global $langs, $conf;
        $out = '';
        if ($htmlname != "none") {
            $out .= '<form method="post" action="' . $page . '">';
            $out .= '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
            $out .= '<input type="hidden" name="action" value="set'.$htmlname.'">';
            $out .= '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
            $out .= '<tr><td>';
            $out .= $this->selectBarcodeType($selected, $htmlname, 1);
            $out .= '</td>';
            $out .= '<td class="left"><input type="submit" class="button" value="' . $langs->trans("Modify") . '">';
            $out .= '</td></tr></table></form>';
        }
        return $out;
    }
}
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
