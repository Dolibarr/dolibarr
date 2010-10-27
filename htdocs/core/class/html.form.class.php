<?php
/* Copyright (c) 2002-2007 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Benoit Mortier        <benoit.mortier@opensides.be>
 * Copyright (C) 2004      Sebastien Di Cintio   <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Eric Seigne           <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2010 Regis Houssin         <regis@dolibarr.fr>
 * Copyright (C) 2006      Andre Cianfarani      <acianfa@free.fr>
 * Copyright (C) 2006      Marc Barilley/Ocebo   <marc@ocebo.com>
 * Copyright (C) 2007      Franky Van Liedekerke <franky.van.liedekerker@telenet.be>
 * Copyright (C) 2007      Patrick Raguin        <patrick.raguin@gmail.com>
 * Copyright (C) 2010      Juanjo Menent         <jmenent@2byte.es>
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
 */

/**
 *	\file       htdocs/core/class/html.form.class.php
 *	\brief      File of class with all html predefined components
 *	\version	$Id$
 */


/**
 *	\class      Form
 *	\brief      Class to manage generation of HTML components
 *	\remarks	Only common components must be here.
 */
class Form
{
    var $db;
    var $error;

    // Cache arrays
    var $cache_types_paiements=array();
    var $cache_conditions_paiements=array();

    var $tva_taux_value;
    var $tva_taux_libelle;


    /**
     * Constructor
     * @param      $DB      Database handler
     */
    function Form($DB)
    {
        $this->db = $DB;
    }

    /**
     * Output key field for an editable field
     * @param      text            Text of label
     * @param      htmlname        Name of select field
     * @param      preselected     Preselected value for parameter
     * @param      paramkey        Key of parameter (unique if there is several parameter to show)
     * @param      paramvalue      Value of parameter
     * @param      perm            Permission to allow button to edit parameter
     * @param      typeofdata      Type of data (string by default, email, ...)
     * @return     string          HTML edit field
     */
    function editfieldkey($text,$htmlname,$preselected,$paramkey,$paramvalue,$perm,$typeofdata='string')
    {
        global $langs;
        $ret='';
        $ret.='<table class="nobordernopadding" width="100%"><tr><td nowrap="nowrap">';
        $ret.=$langs->trans($text);
        $ret.='</td>';
        if ($_GET['action'] != 'edit'.$htmlname && $perm) $ret.='<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=edit'.$htmlname.'&amp;'.$paramkey.'='.$paramvalue.'">'.img_edit($langs->trans('Edit'),1).'</a></td>';
        $ret.='</tr></table>';
        return $ret;
    }

    /**
     *	\brief		Output val field for an editable field
     * 	\param		text			Text of label (not used in this function)
     * 	\param		htmlname		Name of select field
     * 	\param		preselected		Preselected value for parameter
     * 	\param		paramkey		Key of parameter (unique if there is several parameter to show)
     * 	\param		perm			Permission to allow button to edit parameter
     * 	\param		typeofdata		Type of data (string by default, email, ...)
     *  \return     string         HTML edit field
     */
    function editfieldval($text,$htmlname,$preselected,$paramkey,$paramvalue,$perm,$typeofdata='string')
    {
        global $langs;
        $ret='';
        if ($_GET['action'] == 'edit'.$htmlname)
        {
            $ret.="\n";
            $ret.='<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
            $ret.='<input type="hidden" name="action" value="set'.$htmlname.'">';
            $ret.='<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            $ret.='<input type="hidden" name="'.$paramkey.'" value="'.$paramvalue.'">';
            $ret.='<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
            $ret.='<tr><td>';
            $ret.='<input type="text" name="'.$htmlname.'" value="'.$preselected.'">';
            $ret.='</td>';
            $ret.='<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
            $ret.='</tr></table>'."\n";
            $ret.='</form>'."\n";
        }
        else
        {
            if ($typeofdata == 'email') $ret.=dol_print_email($preselected,0,0,0,0,1);
            else $ret.=$preselected;
        }
        return $ret;
    }

    /**
     *	Old version of textwithtooltip. Kept for backward compatibility with modules for 2.6.
     *	@deprecated
     */
    function textwithhelp($text,$htmltext,$tooltipon=1)
    {
        return $this->textwithtooltip($text,$htmltext,$tooltipon);
    }

    /**
     *	Show a text and picto with tooltip on text or picto
     *	@param  text				Text to show
     *	@param  htmltext	    	Content html of tooltip, coded into HTML/UTF8
     *	@param	tooltipon			1=tooltip sur texte, 2=tooltip sur picto, 3=tooltip sur les 2
     *	@param	direction			-1=Le picto est avant, 0=pas de picto, 1=le picto est apres
     *	@param	img					Code img du picto (use img_xxx() function to get it)
     *	@return	string				Code html du tooltip (texte+picto)
     * 	@see	Use function textwithpicto if you can.
     */
    function textwithtooltip($text,$htmltext,$tooltipon=1,$direction=0,$img='')
    {
        global $conf;

        if (! $htmltext) return $text;

        $paramfortooltip ='';

        // Sanitize tooltip
        $htmltext=str_replace("\\","\\\\",$htmltext);
        //$htmltext=str_replace("'","\'",$htmltext);
        //$htmltext=str_replace("&#039;","\'",$htmltext);
        $htmltext=str_replace("\r","",$htmltext);
        $htmltext=str_replace("<br>\n","<br>",$htmltext);
        $htmltext=str_replace("\n","",$htmltext);

       	$htmltext=str_replace('"',"&quot;",$htmltext);
       	$paramfortooltip.=' class="classfortooltip" title="'.$htmltext.'"'; // Attribut to put on td tag to store tooltip

       	$s="";
        $s.='<table class="nobordernopadding" summary=""><tr>';
        if ($direction > 0)
        {
        	if ($text != '')
        	{
        		$s.='<td'.$paramfortooltip.'>'.$text;
				if ($direction) $s.='&nbsp;';
				$s.='</td>';
			}
			if ($direction) $s.='<td'.$paramfortooltip.' valign="top" width="14">'.$img.'</td>';
		}
		else
		{
			if ($direction) $s.='<td'.$paramfortooltip.' valign="top" width="14">'.$img.'</td>';
			if ($text != '')
			{
				$s.='<td'.$paramfortooltip.'>';
				if ($direction) $s.='&nbsp;';
				$s.=$text.'</td>';
			}
		}
		$s.='</tr></table>';

        return $s;
    }

    /**
     *	Show a text with a picto and a tooltip on picto
     *	@param     	text				Text to show
     *	@param   	htmltooltip     	Content of tooltip
     *	@param		direction			1=Icon is after text, -1=Icon is before text
     * 	@param		type				Type of picto (info, help, warning, superadmin...)
     * 	@return		string				HTML code of text, picto, tooltip
     */
    function textwithpicto($text,$htmltext,$direction=1,$type='help')
    {
        global $conf;

        if ("$type" == "0") $type='info';	// For backward compatibility

        $alt='';
        // If info or help with no javascript, show only text
        if (empty($conf->use_javascript_ajax))
        {
            if ($type == 'info' || $type == 'help')	return $text;
            else { $alt=$htmltext; $htmltext=''; }
        }
        // If info or help with smartphone, show only text
        if (! empty($conf->browser->phone))
        {
            if ($type == 'info' || $type == 'help') return $text;
        }
        // Info or help
        if ($type == 'info') 				$img=img_help(0,$alt);
        if ($type == 'help' || $type ==1)	$img=img_help(1,$alt);
        if ($type == 'superadmin') 			$img=img_redstar($alt);
        // Warnings
        if ($type == 'warning') 			$img=img_warning($alt);

        return $this->textwithtooltip($text,$htmltext,2,$direction,$img);
    }

    /**
     *    Return combo list of activated countries, into language of user
     *    @param     selected         Id or Code or Label of preselected country
     *    @param     htmlname         Name of html select object
     *    @param     htmloption       Options html on select object
     *    TODO       trier liste sur noms apres traduction plutot que avant
     */
    function select_pays($selected='',$htmlname='pays_id',$htmloption='')
    {
    	print $this->select_country($selected,$htmlname,$htmloption);
    }

    /**
     *    Return combo list of activated countries, into language of user
     *    @param     selected         Id or Code or Label of preselected country
     *    @param     htmlname         Name of html select object
     *    @param     htmloption       Options html on select object
     *    TODO       trier liste sur noms apres traduction plutot que avant
     */
    function select_country($selected='',$htmlname='pays_id',$htmloption='')
    {
        global $conf,$langs;
        $langs->load("dict");

        $out='';

        $sql = "SELECT rowid, code, libelle, active";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_pays";
        $sql.= " WHERE active = 1";
        $sql.= " ORDER BY code ASC";

        dol_syslog("Form::select_pays sql=".$sql);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $out.= '<select id="select'.$htmlname.'" class="flat selectpays" name="'.$htmlname.'" '.$htmloption.'>';
            $num = $this->db->num_rows($resql);
            $i = 0;
            if ($num)
            {
                $foundselected=false;
                while ($i < $num)
                {
                    $obj = $this->db->fetch_object($resql);
                    if ($selected && $selected != '-1' &&
                    ($selected == $obj->rowid || $selected == $obj->code || $selected == $obj->libelle) )
                    {
                        $foundselected=true;
                        $out.= '<option value="'.$obj->rowid.'" selected="true">';
                    }
                    else
                    {
                        $out.= '<option value="'.$obj->rowid.'">';
                    }
                    // Si traduction existe, on l'utilise, sinon on prend le libelle par defaut
                    if ($obj->code) { $out.= $obj->code . ' - '; }
                    $out.= ($obj->code && $langs->trans("Country".$obj->code)!="Country".$obj->code?$langs->trans("Country".$obj->code):($obj->libelle!='-'?$obj->libelle:'&nbsp;'));
                    $out.= '</option>';
                    $i++;
                }
            }
            $out.= '</select>';
        }
        else
        {
            dol_print_error($this->db);
        }

        return $out;
    }


    /**
     *    \brief      Retourne la liste des types de comptes financiers
     *    \param      selected        Type pre-selectionne
     *    \param      htmlname        Nom champ formulaire
     */
    function select_type_comptes_financiers($selected=1,$htmlname='type')
    {
        global $langs;
        $langs->load("banks");

        $type_available=array(0,1,2);

        print '<select class="flat" name="'.$htmlname.'">';
        $num = count($type_available);
        $i = 0;
        if ($num)
        {
            while ($i < $num)
            {
                if ($selected == $type_available[$i])
                {
                    print '<option value="'.$type_available[$i].'" selected="true">'.$langs->trans("BankType".$type_available[$i]).'</option>';
                }
                else
                {
                    print '<option value="'.$type_available[$i].'">'.$langs->trans("BankType".$type_available[$i]).'</option>';
                }
                $i++;
            }
        }
        print '</select>';
    }


    /**
     *		\brief      Return list of social contributions.
     * 		\remarks	Use mysoc->pays_id or mysoc->pays_code so they must be defined.
     *		\param      selected        Preselected type
     *		\param      htmlname        Name of field in form
     * 		\param		useempty		Set to 1 if we want an empty value
     * 		\param		maxlen			Max length of text in combo box
     * 		\param		help			Add or not the admin help picto
     */
    function select_type_socialcontrib($selected='',$htmlname='actioncode', $useempty=0, $maxlen=40, $help=1)
    {
        global $db,$langs,$user,$mysoc;

        if (empty($mysoc->pays_id) && empty($mysoc->pays_code))
        {
            dol_print_error('','Call to select_type_socialcontrib with mysoc country not yet defined');
            exit;
        }

        if (! empty($mysoc->pays_id))
        {
            $sql = "SELECT c.id, c.libelle as type";
            $sql.= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c";
            $sql.= " WHERE c.active = 1";
            $sql.= " AND c.fk_pays = ".$mysoc->pays_id;
            $sql.= " ORDER BY c.libelle ASC";
        }
        else
        {
            $sql = "SELECT c.id, c.libelle as type";
            $sql.= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c, ".MAIN_DB_PREFIX."c_pays as p";
            $sql.= " WHERE c.active = 1 AND c.fk_pays = p.rowid";
            $sql.= " AND p.code = '".$mysoc->pays_code."'";
            $sql.= " ORDER BY c.libelle ASC";
        }

        dol_syslog("Form::select_type_socialcontrib sql=".$sql, LOG_DEBUG);
        $resql=$db->query($sql);
        if ($resql)
        {
            $num = $db->num_rows($resql);
            if ($num)
            {
                print '<select class="flat" name="'.$htmlname.'">';
                $i = 0;

                if ($useempty) print '<option value="0">&nbsp;</option>';
                while ($i < $num)
                {
                    $obj = $db->fetch_object($resql);
                    print '<option value="'.$obj->id.'"';
                    if ($obj->id == $selected) print ' selected="true"';
                    print '>'.dol_trunc($obj->type,$maxlen);
                    $i++;
                }
                print '</select>';
                if ($user->admin && $help) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
            }
            else
            {
                print $langs->trans("ErrorNoSocialContributionForSellerCountry",$mysoc->pays_code);
            }
        }
        else
        {
            dol_print_error($db,$db->lasterror());
        }
    }

    /**
     *		Return list of types of lines (product or service)
     * 		Example: 0=product, 1=service, 9=other (for external module)
     *		@param      selected        Preselected type
     *		@param      htmlname        Name of field in html form
     * 		@param		showempty		Add an empty field
     * 		@param		hidetext		Do not show label before combo box
     * 		@param		forceall		Force to show products and services in combo list, whatever are activated modules
     */
    function select_type_of_lines($selected='',$htmlname='type',$showempty=0,$hidetext=0,$forceall=0)
    {
        global $db,$langs,$user,$conf;

        // If product & services are enabled or both disabled.
        if ($forceall || ($conf->product->enabled && $conf->service->enabled)
        || (empty($conf->product->enabled) && empty($conf->service->enabled)))
        {
            if (empty($hidetext)) print $langs->trans("Type").': ';
            print '<select class="flat" name="'.$htmlname.'">';
            if ($showempty)
            {
                print '<option value="-1"';
                if ($selected == -1) print ' selected="true"';
                print '>&nbsp;</option>';
            }

            print '<option value="0"';
            if (0 == $selected) print ' selected="true"';
            print '>'.$langs->trans("Product");

            print '<option value="1"';
            if (1 == $selected) print ' selected="true"';
            print '>'.$langs->trans("Service");

            print '</select>';
            //if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
        }
        if (! $forceall && empty($conf->product->enabled) && $conf->service->enabled)
        {
            print '<input type="hidden" name="'.$htmlname.'" value="1">';
        }
        if (! $forceall && $conf->product->enabled && empty($conf->service->enabled))
        {
            print '<input type="hidden" name="'.$htmlname.'" value="0">';
        }

    }

    /**
     *		\brief      Return list of types of notes
     *		\param      selected        Preselected type
     *		\param      htmlname        Name of field in form
     * 		\param		showempty		Add an empty field
     */
    function select_type_fees($selected='',$htmlname='type',$showempty=0)
    {
        global $db,$langs,$user;
        $langs->load("trips");

        print '<select class="flat" name="'.$htmlname.'">';
        if ($showempty)
        {
            print '<option value="-1"';
            if ($selected == -1) print ' selected="true"';
            print '>&nbsp;</option>';
        }

        $sql = "SELECT c.code, c.libelle as type FROM ".MAIN_DB_PREFIX."c_type_fees as c";
        $sql.= " ORDER BY lower(c.libelle) ASC";
        $resql=$db->query($sql);
        if ($resql)
        {
            $num = $db->num_rows($resql);
            $i = 0;

            while ($i < $num)
            {
                $obj = $db->fetch_object($resql);
                print '<option value="'.$obj->code.'"';
                if ($obj->code == $selected) print ' selected="true"';
                print '>';
                if ($obj->code != $langs->trans($obj->code)) print $langs->trans($obj->code);
                else print $langs->trans($obj->type);
                $i++;
            }
        }
        print '</select>';
        if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
    }

    /**
     *    	\brief      Output html form to select a third party
     *		\param      selected        Preselected type
     *		\param      htmlname        Name of field in form
     *    	\param      filter          Optionnal filters criteras
     *		\param		showempty		Add an empty field
     * 		\param		showtype		Show third party type in combolist (customer, prospect or supplier)
     * 		\param		forcecombo		Force to use combo box
     */
    function select_societes($selected='',$htmlname='socid',$filter='',$showempty=0, $showtype=0, $forcecombo=0)
    {
        global $conf,$user,$langs;

        // On recherche les societes
        $sql = "SELECT s.rowid, s.nom, s.client, s.fournisseur, s.code_client, s.code_fournisseur";
        $sql.= " FROM ".MAIN_DB_PREFIX ."societe as s";
        if (!$user->rights->societe->client->voir && !$user->societe_id) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
        $sql.= " WHERE s.entity = ".$conf->entity;
        if ($filter) $sql.= " AND ".$filter;
        if (is_numeric($selected) && $conf->use_javascript_ajax && $conf->global->COMPANY_USE_SEARCH_TO_SELECT)	$sql.= " AND s.rowid = ".$selected;
        if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
        $sql.= " ORDER BY nom ASC";

        dol_syslog("Form::select_societes sql=".$sql);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($conf->use_javascript_ajax && $conf->global->COMPANY_USE_SEARCH_TO_SELECT && ! $forcecombo)
            {
                $minLength = (is_numeric($conf->global->COMPANY_USE_SEARCH_TO_SELECT)?$conf->global->COMPANY_USE_SEARCH_TO_SELECT:2);

            	$socid = 0;
                if ($selected)
                {
                    $obj = $this->db->fetch_object($resql);
                    $socid = $obj->rowid?$obj->rowid:'';
                }

                print "\n".'<!-- Input text for third party with Ajax.Autocompleter (select_societes) -->'."\n";
                print '<table class="nobordernopadding"><tr class="nocellnopadd">';
                print '<td class="nobordernopadding">';
                if ($socid == 0)
                {
                	print '<input type="text" size="30" id="search_'.$htmlname.'" name="search_'.$htmlname.'" value="" />';
                }
                else
                {
                    print '<input type="text" size="30" id="search_'.$htmlname.'" name="search_'.$htmlname.'" value="'.$obj->nom.'" />';
                }
                print ajax_autocompleter(($socid?$socid:-1),$htmlname,DOL_URL_ROOT.'/societe/ajaxcompanies.php?filter='.urlencode($filter), '', $minLength);
                print '</td>';
                print '</tr>';
                print '</table>';
            }
            else
            {
                print '<select id="select'.$htmlname.'" class="flat" name="'.$htmlname.'">';
                if ($showempty) print '<option value="-1">&nbsp;</option>';
                $num = $this->db->num_rows($resql);
                $i = 0;
                if ($num)
                {
                    while ($i < $num)
                    {
                        $obj = $this->db->fetch_object($resql);
                        $label=$obj->nom;
                        if ($showtype)
                        {
                            if ($obj->client || $obj->fournisseur) $label.=' (';
                            if ($obj->client == 1 || $obj->client == 3) $label.=$langs->trans("Customer");
                            if ($obj->client == 2 || $obj->client == 3) $label.=($obj->client==3?', ':'').$langs->trans("Prospect");
                            if ($obj->fournisseur) $label.=($obj->client?', ':'').$langs->trans("Supplier");
                            if ($obj->client || $obj->fournisseur) $label.=')';
                        }
                        if ($selected > 0 && $selected == $obj->rowid)
                        {
                            print '<option value="'.$obj->rowid.'" selected="true">'.$label.'</option>';
                        }
                        else
                        {
                            print '<option value="'.$obj->rowid.'">'.$label.'</option>';
                        }
                        $i++;
                    }
                }
                print '</select>';
            }
        }
        else
        {
            dol_print_error($this->db);
        }
    }


    /**
     *    	\brief      Return HTML combo list of absolute discounts
     *    	\param      selected        Id remise fixe pre-selectionnee
     *    	\param      htmlname        Nom champ formulaire
     *    	\param      filter          Criteres optionnels de filtre
     * 		\param		maxvalue		Max value for lines that can be selected
     * 		\return		int				Return number of qualifed lines in list
     */
    function select_remises($selected='',$htmlname='remise_id',$filter='',$socid, $maxvalue=0)
    {
        global $langs,$conf;

        // On recherche les remises
        $sql = "SELECT re.rowid, re.amount_ht, re.amount_tva, re.amount_ttc,";
        $sql.= " re.description, re.fk_facture_source";
        $sql.= " FROM ".MAIN_DB_PREFIX ."societe_remise_except as re";
        $sql.= " WHERE fk_soc = ".$socid;
        if ($filter) $sql.= " AND ".$filter;
        $sql.= " ORDER BY re.description ASC";

        dol_syslog("Form::select_remises sql=".$sql);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            print '<select class="flat" name="'.$htmlname.'">';
            $num = $this->db->num_rows($resql);

            $qualifiedlines=$num;

            $i = 0;
            if ($num)
            {
                print '<option value="0">&nbsp;</option>';
                while ($i < $num)
                {
                    $obj = $this->db->fetch_object($resql);
                    $desc=dol_trunc($obj->description,40);
                    if ($desc=='(CREDIT_NOTE)') $desc=$langs->trans("CreditNote");
                    if ($desc=='(DEPOSIT)')     $desc=$langs->trans("Deposit");

                    $selectstring='';
                    if ($selected > 0 && $selected == $obj->rowid) $selectstring=' selected="true"';

                    $disabled='';
                    if ($maxvalue && $obj->amount_ttc > $maxvalue)
                    {
                        $qualifiedlines--;
                        $disabled=' disabled="true"';
                    }

                    print '<option value="'.$obj->rowid.'"'.$selectstring.$disabled.'>'.$desc.' ('.price($obj->amount_ht).' '.$langs->trans("HT").' - '.price($obj->amount_ttc).' '.$langs->trans("TTC").')</option>';
                    $i++;
                }
            }
            print '</select>';
            return $qualifiedlines;
        }
        else
        {
            dol_print_error($this->db);
            return -1;
        }
    }


    /**
     *    	\brief      Retourne la liste deroulante des contacts d'une societe donnee
     *    	\param      socid      	    Id de la societe
     *    	\param      selected   	    Id contact pre-selectionne
     *    	\param      htmlname  	    Nom champ formulaire ('none' pour champ non editable)
     *      \param      show_empty      0=liste sans valeur nulle, 1=ajoute valeur inconnue
     *      \param      exclude         Liste des id contacts a exclure
     * 		\param		limitto			Disable answers that are not id in this array list
     *		\return		int				<0 if KO, Nb of contact in list if OK
     */
    function select_contacts($socid,$selected='',$htmlname='contactid',$showempty=0,$exclude='',$limitto='')
    {
        // Permettre l'exclusion de contacts
        if (is_array($exclude))
        {
            $excludeContacts = implode("','",$exclude);
        }

        // On recherche les societes
        $sql = "SELECT s.rowid, s.name, s.firstname FROM";
        $sql.= " ".MAIN_DB_PREFIX ."socpeople as s";
        $sql.= " WHERE fk_soc=".$socid;
        if (is_array($exclude) && $excludeContacts) $sql.= " AND s.rowid NOT IN ('".$excludeContacts."')";
        $sql.= " ORDER BY s.name ASC";

        dol_syslog("Form::select_contacts sql=".$sql);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $num=$this->db->num_rows($resql);
            if ($num == 0) return 0;

            if ($htmlname != 'none') print '<select class="flat" name="'.$htmlname.'">';
            if ($showempty) print '<option value="0">&nbsp;</option>';
            $num = $this->db->num_rows($resql);
            $i = 0;
            if ($num)
            {
                while ($i < $num)
                {
                    $obj = $this->db->fetch_object($resql);
                    if ($htmlname != 'none')
                    {
                        $disabled=0;
                        if (is_array($limitto) && sizeof($limitto) && ! in_array($obj->rowid,$limitto)) $disabled=1;
                        if ($selected && $selected == $obj->rowid)
                        {
                            print '<option value="'.$obj->rowid.'"';
                            if ($disabled) print ' disabled="true"';
                            print ' selected="true">'.$obj->name.' '.$obj->firstname.'</option>';
                        }
                        else
                        {
                            print '<option value="'.$obj->rowid.'"';
                            if ($disabled) print ' disabled="true"';
                            print '>'.$obj->name.' '.$obj->firstname.'</option>';
                        }
                    }
                    else
                    {
                        if ($selected == $obj->rowid) print $obj->name.' '.$obj->firstname;
                    }
                    $i++;
                }
            }
            if ($htmlname != 'none')
            {
                print '</select>';
            }
            return $num;
        }
        else
        {
            dol_print_error($this->db);
            return -1;
        }
    }

    /**
     *	\brief      Return select list of users
     *  \param      selected        Id user preselected
     *  \param      htmlname        Field name in form
     *  \param      show_empty      0=liste sans valeur nulle, 1=ajoute valeur inconnue
     *  \param      exclude         Array list of users id to exclude
     * 	\param		disabled		If select list must be disabled
     *  \param      include         Array list of users id to include
     * 	\param		enableonly		Array list of users id to be enabled. All other must be disabled
     */
    function select_users($selected='',$htmlname='userid',$show_empty=0,$exclude='',$disabled=0,$include='',$enableonly='')
    {
    	print $this->select_dolusers($selected,$htmlname,$show_empty,$exclude,$disabled,$include,$enableonly);
    }

    /**
     *	\brief      Return select list of users
     *  \param      selected        Id user preselected
     *  \param      htmlname        Field name in form
     *  \param      show_empty      0=liste sans valeur nulle, 1=ajoute valeur inconnue
     *  \param      exclude         Array list of users id to exclude
     * 	\param		disabled		If select list must be disabled
     *  \param      include         Array list of users id to include
     * 	\param		enableonly		Array list of users id to be enabled. All other must be disabled
     */
    function select_dolusers($selected='',$htmlname='userid',$show_empty=0,$exclude='',$disabled=0,$include='',$enableonly='')
    {
        global $conf;

        // Permettre l'exclusion d'utilisateurs
        if (is_array($exclude))	$excludeUsers = implode("','",$exclude);
        // Permettre l'inclusion d'utilisateurs
        if (is_array($include))	$includeUsers = implode("','",$include);

        $out='';

        // On recherche les utilisateurs
        $sql = "SELECT u.rowid, u.name, u.firstname, u.login, u.admin";
        $sql.= " FROM ".MAIN_DB_PREFIX ."user as u";
        $sql.= " WHERE u.entity IN (0,".$conf->entity.")";
        if (is_array($exclude) && $excludeUsers) $sql.= " AND u.rowid NOT IN ('".$excludeUsers."')";
        if (is_array($include) && $includeUsers) $sql.= " AND u.rowid IN ('".$includeUsers."')";
        $sql.= " ORDER BY u.name ASC";

        dol_syslog("Form::select_users sql=".$sql);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $out.= '<select class="flat" name="'.$htmlname.'"'.($disabled?' disabled="true"':'').'>';
            if ($show_empty) $out.= '<option value="-1"'.($id==-1?' selected="true"':'').'>&nbsp;</option>'."\n";
            $num = $this->db->num_rows($resql);
            $i = 0;
            if ($num)
            {
                while ($i < $num)
                {
                    $obj = $this->db->fetch_object($resql);
                    $disableline=0;
                    if (is_array($enableonly) && sizeof($enableonly) && ! in_array($obj->rowid,$enableonly)) $disableline=1;

                    if ((is_object($selected) && $selected->id == $obj->rowid) || (! is_object($selected) && $selected == $obj->rowid))
                    {
                        $out.= '<option value="'.$obj->rowid.'"';
                        if ($disableline) $out.= ' disabled="true"';
                        $out.= ' selected="true">';
                    }
                    else
                    {
                        $out.= '<option value="'.$obj->rowid.'"';
                        if ($disableline) $out.= ' disabled="true"';
                        $out.= '>';
                    }
                    $out.= $obj->name.($obj->name && $obj->firstname?' ':'').$obj->firstname;
                    //if ($obj->admin) $out.= ' *';
                    if ($conf->global->MAIN_SHOW_LOGIN) $out.= ' ('.$obj->login.')';
                    $out.= '</option>';
                    $i++;
                }
            }
            $out.= '</select>';
        }
        else
        {
            dol_print_error($this->db);
        }

        return $out;
    }


    /**
     *  \brief    Return list of products for customer in Ajax if Ajax activated or go to select_produits_do
     *  \param    selected        Preselected products
     *  \param    htmlname        Name of HTML seletc field (must be unique in page)
     *  \param    filtertype      Filter on product type (''=nofilter, 0=product, 1=service)
     *  \param    limit           Limit sur le nombre de lignes retournees
     *  \param    price_level     Level of price to show
     *  \param	  status		  -1=Return all products, 0=Products not on sell, 1=Products on sell
     *  \param	  finished     	  2=all, 1=finished, 0=raw material
     */
    function select_produits($selected='',$htmlname='productid',$filtertype='',$limit=20,$price_level=0,$status=1,$finished=2)
    {
        global $langs,$conf;

        if ($conf->global->PRODUIT_USE_SEARCH_TO_SELECT)
        {
            // mode=1 means customers products
            print ajax_autocompleter('',$htmlname,DOL_URL_ROOT.'/product/ajaxproducts.php','outjson=1&price_level='.$price_level.'&type='.$filtertype.'&mode=1&status='.$status.'&finished='.$finished);
            print $langs->trans("RefOrLabel").' : <input type="text" size="20" name="search_'.$htmlname.'" id="search_'.$htmlname.'">';
            print '<br>';
        }
        else
        {
            $this->select_produits_do($selected,$htmlname,$filtertype,$limit,$price_level,'',$status,$finished,0);
        }
    }

    /**
     *	\brief      Return list of products for a customer
     *	\param      selected        Preselected product
     *	\param      htmlname        Name of select html
     *  \param		filtertype      Filter on product type (''=nofilter, 0=product, 1=service)
     *	\param      limit           Limite sur le nombre de lignes retournees
     *	\param      price_level     Level of price to show
     * 	\param      filterkey       Filter on product
     *	\param		status          -1=Return all products, 0=Products not on sell, 1=Products on sell
     *  \param      finished        Filter on finished field: 2=No filter
     *  \param      disableout      Disable print output
     *  \return     array           Array of keys for json
     */
    function select_produits_do($selected='',$htmlname='productid',$filtertype='',$limit=20,$price_level=0,$filterkey='',$status=1,$finished=2,$disableout=0)
    {
        global $langs,$conf,$user,$db;

        $sql = "SELECT ";
        $sql.= " p.rowid, p.label, p.ref, p.fk_product_type, p.price, p.price_ttc, p.price_base_type, p.duration, p.stock";
        // Multilang : we add translation
        if ($conf->global->MAIN_MULTILANGS)
        {
            $sql.= ", pl.label as label_translated";
        }
        $sql.= " FROM ".MAIN_DB_PREFIX."product as p";
        // Multilang : we add translation
        if ($conf->global->MAIN_MULTILANGS)
        {
            $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_lang as pl ON pl.fk_product = p.rowid AND pl.lang='". $langs->getDefaultLang() ."'";
        }
        $sql.= " WHERE p.entity = ".$conf->entity;
        if (empty($user->rights->produit->hidden) && empty($user->rights->service->hidden)) $sql.=' AND p.hidden=0';
        else
        {
            if (empty($user->rights->produit->hidden)) $sql.=' AND (p.hidden=0 OR p.fk_product_type != 0)';
            if (empty($user->rights->service->hidden)) $sql.=' AND (p.hidden=0 OR p.fk_product_type != 1)';
        }
        if($finished == 0)
        {
            $sql.= " AND p.finished = ".$finished;
        }
        elseif($finished == 1)
        {
            $sql.= " AND p.finished = ".$finished;
            if ($status >= 0)  $sql.= " AND p.tosell = ".$status;
        }
        elseif($status >= 0)
        {
            $sql.= " AND p.tosell = ".$status;
        }
        if (strval($filtertype) != '') $sql.=" AND p.fk_product_type=".$filtertype;
        // Add criteria on ref/label
        if (! empty($conf->global->PRODUCT_DONOTSEARCH_ANYWHERE))   // Can use index
        {
            if ($filterkey && $filterkey != '')
            {
                $sql.=" AND (p.ref like '".$filterkey."%' OR p.label like '".$filterkey."%'";
                if ($conf->global->MAIN_MULTILANGS) $sql.=" OR pl.label like '".$filterkey."%'";
                $sql.=")";
            }
        }
        else
        {
            if ($filterkey && $filterkey != '')
            {
                $sql.=" AND (p.ref like '%".$filterkey."%' OR p.label like '%".$filterkey."%'";
                if ($conf->global->MAIN_MULTILANGS) $sql.=" OR pl.label like '%".$filterkey."%'";
                $sql.=")";
            }
        }
        $sql.= $db->order("p.ref");
        $sql.= $db->plimit($limit);


        // Build output string
        $outselect='';
        $outjson=array();

        dol_syslog("Form::select_produits_do search product sql=".$sql, LOG_DEBUG);
        $result=$this->db->query($sql);
        if ($result)
        {
            $num = $this->db->num_rows($result);

            $outselect.='<select class="flat" name="'.$htmlname.'"';
			if ($conf->use_javascript_ajax && $num && $conf->global->PRODUIT_USE_SEARCH_TO_SELECT) $outselect.=' onchange="publish_selvalue(this);"';
            $outselect.='>';

            if ($conf->use_javascript_ajax)
            {
                if (! $num)
                {
                    $outselect.='<option value="0">-- '.$langs->trans("NoProductMatching").' --</option>';
                }
                else
                {
                    $outselect.='<option value="0" selected="true">-- '.$langs->trans("MatchingProducts").' --</option>';
                }
            }
            else
            {
                $outselect.='<option value="0" selected="true">&nbsp;</option>';
            }

            $i = 0;
            while ($num && $i < $num)
            {
                $outkey='';
                $outval='';
                $outref='';

                $objp = $this->db->fetch_object($result);

                $label=$objp->label;
                if (! empty($objp->label_translated)) $label=$objp->label_translated;
                if ($filterkey && $filterkey != '') $label=preg_replace('/('.preg_quote($filterkey).')/i','<strong>$1</strong>',$label,1);

                $outkey=$objp->rowid;
                $outref=$objp->ref;

                $opt = '<option value="'.$objp->rowid.'"';
                $opt.= ($objp->rowid == $selected)?' selected="true"':'';
                if ($conf->stock->enabled && $objp->fk_product_type == 0 && isset($objp->stock))
                {
                    if ($objp->stock > 0)
                    {
                        $opt.= ' style="background-color:#32CD32; color:#F5F5F5;"';
                    }
                    else if ($objp->stock <= 0)
                    {
                        $opt.= ' style="background-color:#FF0000; color:#F5F5F5;"';
                    }
                }
                $opt.= '>';
                $opt.= $langs->convToOutputCharset($objp->ref).' - '.$langs->convToOutputCharset(dol_trunc($label,32)).' - ';

                $objRef = $objp->ref;
                if ($filterkey && $filterkey != '') $objRef=preg_replace('/('.preg_quote($filterkey).')/i','<strong>$1</strong>',$objRef,1);
                $outval.=$objRef.' - '.dol_trunc($label,32).' - ';

                $found=0;
                $currencytext=$langs->trans("Currency".$conf->monnaie);
                $currencytextnoent=$langs->transnoentities("Currency".$conf->monnaie);
                if (dol_strlen($currencytext) > 10) $currencytext=$conf->monnaie;	// If text is too long, we use the short code
                if (dol_strlen($currencytextnoent) > 10) $currencytextnoent=$conf->monnaie;   // If text is too long, we use the short code

                // Multiprice
                if ($price_level >= 1)		// If we need a particular price level (from 1 to 6)
                {
                    $sql= "SELECT price, price_ttc, price_base_type ";
                    $sql.= "FROM ".MAIN_DB_PREFIX."product_price ";
                    $sql.= "WHERE fk_product='".$objp->rowid."'";
                    $sql.= " AND price_level=".$price_level;
                    $sql.= " ORDER BY date_price";
                    $sql.= " DESC limit 1";

                    dol_syslog("Form::select_produits_do search price for level '.$price_level.' sql=".$sql);
                    $result2 = $this->db->query($sql);
                    if ($result2)
                    {
                        $objp2 = $this->db->fetch_object($result2);
                        if ($objp2)
                        {
                            $found=1;
                            if ($objp2->price_base_type == 'HT')
                            {
                                $opt.= price($objp2->price,1).' '.$currencytext.' '.$langs->trans("HT");
                                $outval.= price($objp2->price,1).' '.$currencytextnoent.' '.$langs->transnoentities("HT");
                            }
                            else
                            {
                                $opt.= price($objp2->price_ttc,1).' '.$currencytext.' '.$langs->trans("TTC");
                                $outval.= price($objp2->price_ttc,1).' '.$currencytextnoent.' '.$langs->transnoentities("TTC");
                            }
                        }
                    }
                    else
                    {
                        dol_print_error($this->db);
                    }
                }

                // If level no defined or multiprice not found, we used the default price
                if (! $found)
                {
                    if ($objp->price_base_type == 'HT')
                    {
                        $opt.= price($objp->price,1).' '.$currencytext.' '.$langs->trans("HT");
                        $outval.= price($objp->price,1).' '.$currencytextnoent.' '.$langs->transnoentities("HT");
                    }
                    else
                    {
                        $opt.= price($objp->price_ttc,1).' '.$currencytext.' '.$langs->trans("TTC");
                        $outval.= price($objp->price_ttc,1).' '.$currencytextnoent.' '.$langs->transnoentities("TTC");
                    }
                }

                if ($conf->stock->enabled && isset($objp->stock) && $objp->fk_product_type == 0)
                {
                    $opt.= ' - '.$langs->trans("Stock").':'.$objp->stock;
                    $outval.=' - '.$langs->transnoentities("Stock").':'.$objp->stock;
                }

                if ($objp->duration)
                {
                    $duration_value = substr($objp->duration,0,dol_strlen($objp->duration)-1);
                    $duration_unit = substr($objp->duration,-1);
                    if ($duration_value > 1)
                    {
                        $dur=array("h"=>$langs->trans("Hours"),"d"=>$langs->trans("Days"),"w"=>$langs->trans("Weeks"),"m"=>$langs->trans("Months"),"y"=>$langs->trans("Years"));
                    }
                    else
                    {
                        $dur=array("h"=>$langs->trans("Hour"),"d"=>$langs->trans("Day"),"w"=>$langs->trans("Week"),"m"=>$langs->trans("Month"),"y"=>$langs->trans("Year"));
                    }
                    $opt.= ' - '.$duration_value.' '.$langs->trans($dur[$duration_unit]);
                    $outval.=' - '.$duration_value.' '.$langs->transnoentities($dur[$duration_unit]);
                }

                $opt.= "</option>\n";

                // Add new entry
                // "key" value of json key array is used by jQuery automatically as selected value
                // "label" value of json key array is used by jQuery automatically as text for combo box
                $outselect.=$opt;
                array_push($outjson,array('key'=>$outkey,'value'=>$outref,'label'=>$outval));

                $i++;
            }

            $outselect.='</select>';

            $this->db->free($result);

            if (empty($disableout)) print $outselect;
            return $outjson;
        }
        else
        {
            dol_print_error($db);
        }
    }

    /**
     *	\brief     	Return list of products for customer in Ajax if Ajax activated or go to select_produits_fournisseurs_do
     *	\param		socid			Id third party
     *	\param     	selected        Preselected product
     *	\param     	htmlname        Name of HTML Select
     *  \param		filtertype      Filter on product type (''=nofilter, 0=product, 1=service)
     *	\param     	filtre          For a SQL filter
     */
    function select_produits_fournisseurs($socid,$selected='',$htmlname='productid',$filtertype='',$filtre)
    {
        global $langs,$conf;
        if ($conf->global->PRODUIT_USE_SEARCH_TO_SELECT)
        {
            // mode=2 means suppliers products
            print ajax_autocompleter('',$htmlname,DOL_URL_ROOT.'/product/ajaxproducts.php','outjson=1&price_level='.$price_level.'&type='.$filtertype.'&mode=2&status='.$status.'&finished='.$finished);
            print $langs->trans("RefOrLabel").' : <input type="text" size="16" name="search_'.$htmlname.'" id="search_'.$htmlname.'">';
            print '<br>';
        }
        else
        {
            $this->select_produits_fournisseurs_do($socid,$selected,$htmlname,$filtertype,$filtre,'',-1,0);
        }
    }

    /**
     *	\brief      Retourne la liste des produits de fournisseurs
     *	\param		socid   		Id societe fournisseur (0 pour aucun filtre)
     *	\param      selected        Produit pre-selectionne
     *	\param      htmlname        Nom de la zone select
     *  \param		filtertype      Filter on product type (''=nofilter, 0=product, 1=service)
     *	\param      filtre          Pour filtre sql
     *	\param      filterkey       Filtre des produits
     *  \param      status          -1=Return all products, 0=Products not on sell, 1=Products on sell
     *  \param      disableout      Disable print output
     *  \return     array           Array of keys for json
     */
    function select_produits_fournisseurs_do($socid,$selected='',$htmlname='productid',$filtertype='',$filtre='',$filterkey='',$statut=-1,$disableout=0)
    {
        global $langs,$conf;

        $langs->load('stocks');

        $sql = "SELECT p.rowid, p.label, p.ref, p.price, p.duration,";
        $sql.= " pf.ref_fourn,";
        $sql.= " pfp.rowid as idprodfournprice, pfp.price as fprice, pfp.quantity, pfp.unitprice,";
        $sql.= " s.nom";
        $sql.= " FROM ".MAIN_DB_PREFIX."product as p";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur as pf ON p.rowid = pf.fk_product";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON pf.fk_soc = s.rowid";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as pfp ON pf.rowid = pfp.fk_product_fournisseur";
        $sql.= " WHERE p.entity = ".$conf->entity;
        $sql.= " AND p.tobuy = 1";
        if ($socid) $sql.= " AND pf.fk_soc = ".$socid;
        if (strval($filtertype) != '') $sql.=" AND p.fk_product_type=".$filtertype;
        if (! empty($filtre)) $sql.=" ".$filtre;
        // Add criteria on ref/label
        if (! empty($conf->global->PRODUCT_DONOTSEARCH_ANYWHERE))
        {
            if ($filterkey && $filterkey != '') $sql.=" AND (pf.ref_fourn like '%".$filterkey."%' OR p.ref like '%".$filterkey."%' OR p.label like '%".$filterkey."%')";
        }
        else
        {
            if ($filterkey && $filterkey != '') $sql.=" AND (pf.ref_fourn like '".$filterkey."%' OR p.ref like '".$filterkey."%' OR p.label like '".$filterkey."%')";
        }
        $sql.= " ORDER BY pf.ref_fourn DESC";

        // Build output string
        $outselect='';
        $outjson=array();

        dol_syslog("Form::select_produits_fournisseurs_do sql=".$sql,LOG_DEBUG);
        $result=$this->db->query($sql);
        if ($result)
        {

            $num = $this->db->num_rows($result);

            $outselect.='<select class="flat" id="select'.$htmlname.'" name="'.$htmlname.'"';
            if ($conf->use_javascript_ajax && $num) $outselect.=' onchange="publish_selvalue(this);"';
            $outselect.='>';

            if ($conf->use_javascript_ajax)
            {
                if (! $num)
                {
                    $outselect.='<option value="0">-- '.$langs->trans("NoProductMatching").' --</option>';
                }
                else
                {
                    $outselect.='<option value="0" selected="true">-- '.$langs->trans("MatchingProducts").' --</option>';
                }
            }
            else
            {
                if (! $selected) $outselect.='<option value="0" selected="true">&nbsp;</option>';
                else $outselect.='<option value="0">&nbsp;</option>';
            }

            $i = 0;
            while ($i < $num)
            {
                $outkey='';
                $outval='';
                $outref='';

                $objp = $this->db->fetch_object($result);

                $outkey=$objp->idprodfournprice;
                $outref=$objp->ref;

                $opt = '<option value="'.$objp->idprodfournprice.'"';
                if ($selected == $objp->idprodfournprice) $opt.= ' selected="true"';
                if ($objp->fprice == '') $opt.=' disabled="disabled"';
                $opt.= '>';

                $objRef = $objp->ref;
                if ($filterkey && $filterkey != '') $objRef=preg_replace('/('.preg_quote($filterkey).')/i','<strong>$1</strong>',$objRef,1);
                $objRefFourn = $objp->ref_fourn;
                if ($filterkey && $filterkey != '') $objRefFourn=preg_replace('/('.preg_quote($filterkey).')/i','<strong>$1</strong>',$objRefFourn,1);
                $label = $objp->label;
                if ($filterkey && $filterkey != '') $label=preg_replace('/('.preg_quote($filterkey).')/i','<strong>$1</strong>',$label,1);

                $opt.=$langs->convToOutputCharset($objp->ref).' ('.$langs->convToOutputCharset($objp->ref_fourn).') - ';
                $outval.=$objRef.' ('.$objRefFourn.') - ';
                $opt.=$langs->convToOutputCharset(dol_trunc($objp->label,18)).' - ';
                $outval.=dol_trunc($label,18).' - ';

                if ($objp->fprice != '') 	// Keep != ''
                {
                    $currencytext=$langs->trans("Currency".$conf->monnaie);
                    $currencytextnoent=$langs->transnoentities("Currency".$conf->monnaie);
                    if (dol_strlen($currencytext) > 10) $currencytext=$conf->monnaie;   // If text is too long, we use the short code
                    if (dol_strlen($currencytextnoent) > 10) $currencytextnoent=$conf->monnaie;   // If text is too long, we use the short code

                    $opt.= price($objp->fprice).' '.$currencytext."/".$objp->quantity;
                    $outval.= price($objp->fprice).' '.$currencytextnoent."/".$objp->quantity;
                    if ($objp->quantity == 1)
                    {
                        $opt.= strtolower($langs->trans("Unit"));
                        $outval.=strtolower($langs->transnoentities("Unit"));
                    }
                    else
                    {
                        $opt.= strtolower($langs->trans("Units"));
                        $outval.=strtolower($langs->transnoentities("Units"));
                    }
                    if ($objp->quantity >= 1)
                    {
                        $opt.=" (".price($objp->unitprice).' '.$currencytext."/".strtolower($langs->trans("Unit")).")";
                        $outval.=" (".price($objp->unitprice).' '.$currencytextnoent."/".strtolower($langs->transnoentities("Unit")).")";
                    }
                    if ($objp->duration)
                    {
                        $opt .= " - ".$objp->duration;
                        $outval.=" - ".$objp->duration;
                    }
                    if (! $socid)
                    {
                        $opt .= " - ".dol_trunc($objp->nom,8);
                        $outval.=" - ".dol_trunc($objp->nom,8);
                    }
                }
                else
                {
                    $opt.= $langs->trans("NoPriceDefinedForThisSupplier");
                    $outval.=$langs->transnoentities("NoPriceDefinedForThisSupplier");
                }
                $opt .= "</option>\n";

                // Add new entry
                // "key" value of json key array is used by jQuery automatically as selected value
                // "label" value of json key array is used by jQuery automatically as text for combo box
                $outselect.=$opt;
                array_push($outjson,array('key'=>$outkey,'value'=>$outref,'label'=>$outval));

                $i++;
            }
            $outselect.='</select>';

            $this->db->free($result);

            if (empty($disableout)) print $outselect;
            return $outjson;
        }
        else
        {
            dol_print_error($db);
        }
    }

    /**
     *	\brief		Retourn list of suppliers prices for a product
     *	\param		productid   		    Id of product
     */
    function select_product_fourn_price($productid,$htmlname='productfournpriceid')
    {
        global $langs,$conf;

        $langs->load('stocks');

        $sql = "SELECT p.rowid, p.label, p.ref, p.price, p.duration,";
        $sql.= " pf.ref_fourn,";
        $sql.= " pfp.rowid as idprodfournprice, pfp.price as fprice, pfp.quantity, pfp.unitprice,";
        $sql.= " s.nom";
        $sql.= " FROM ".MAIN_DB_PREFIX."product as p";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur as pf ON p.rowid = pf.fk_product";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = pf.fk_soc";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as pfp ON pf.rowid = pfp.fk_product_fournisseur";
        $sql.= " WHERE p.tobuy = 1";
        $sql.= " AND s.fournisseur = 1";
        $sql.= " AND p.rowid = ".$productid;
        $sql.= " ORDER BY s.nom, pf.ref_fourn DESC";

        dol_syslog("Form::select_product_fourn_price sql=".$sql,LOG_DEBUG);
        $result=$this->db->query($sql);

        if ($result)
        {
            $num = $this->db->num_rows($result);

            $form = '<select class="flat" name="'.$htmlname.'">';

            if (! $num)
            {
                $form.= '<option value="0">-- '.$langs->trans("NoSupplierPriceDefinedForThisProduct").' --</option>';
            }
            else
            {
                $form.= '<option value="0">&nbsp;</option>';

                $i = 0;
                while ($i < $num)
                {
                    $objp = $this->db->fetch_object($result);

                    $opt = '<option value="'.$objp->idprodfournprice.'"';
                    $opt.= '>'.$objp->nom.' - '.$objp->ref_fourn.' - ';

                    if ($objp->quantity == 1)
                    {
                        $opt.= price($objp->fprice);
                        $opt.= $langs->trans("Currency".$conf->monnaie)."/";
                    }

                    $opt.= $objp->quantity.' ';

                    if ($objp->quantity == 1)
                    {
                        $opt.= strtolower($langs->trans("Unit"));
                    }
                    else
                    {
                        $opt.= strtolower($langs->trans("Units"));
                    }
                    if ($objp->quantity > 1)
                    {
                        $opt.=" - ";
                        $opt.= price($objp->unitprice).$langs->trans("Currency".$conf->monnaie)."/".strtolower($langs->trans("Unit"));
                    }
                    if ($objp->duration) $opt .= " - ".$objp->duration;
                    $opt .= "</option>\n";

                    $form.= $opt;
                    $i++;
                }
                $form.= '</select>';

                $this->db->free($result);
            }
            return $form;
        }
        else
        {
            dol_print_error($db);
        }
    }

    /**
     *    \brief      Retourne la liste deroulante des adresses
     *    \param      selected        Id contact pre-selectionn
     *    \param      htmlname        Nom champ formulaire
     */
    function select_address($selected='', $socid, $htmlname='address_id',$showempty=0)
    {
        // On recherche les utilisateurs
        $sql = "SELECT a.rowid, a.label";
        $sql .= " FROM ".MAIN_DB_PREFIX ."societe_address as a";
        $sql .= " WHERE a.fk_soc = ".$socid;
        $sql .= " ORDER BY a.label ASC";

        dol_syslog("Form::select_address sql=".$sql);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            print '<select class="flat" name="'.$htmlname.'">';
            if ($showempty) print '<option value="0">&nbsp;</option>';
            $num = $this->db->num_rows($resql);
            $i = 0;
            if ($num)
            {
                while ($i < $num)
                {
                    $obj = $this->db->fetch_object($resql);

                    if ($selected && $selected == $obj->rowid)
                    {
                        print '<option value="'.$obj->rowid.'" selected="true">'.$obj->label.'</option>';
                    }
                    else
                    {
                        print '<option value="'.$obj->rowid.'">'.$obj->label.'</option>';
                    }
                    $i++;
                }
            }
            print '</select>';
            return $num;
        }
        else
        {
            dol_print_error($this->db);
        }
    }


    /**
     *      \brief      Charge dans cache la liste des conditions de paiements possibles
     *      \return     int             Nb lignes chargees, 0 si deja chargees, <0 si ko
     */
    function load_cache_conditions_paiements()
    {
        global $langs;

        if (sizeof($this->cache_conditions_paiements)) return 0;    // Cache deja charge

        $sql = "SELECT rowid, code, libelle";
        $sql.= " FROM ".MAIN_DB_PREFIX.'c_payment_term';
        $sql.= " WHERE active=1";
        $sql.= " ORDER BY sortorder";
        dol_syslog('Form::load_cache_conditions_paiements sql='.$sql,LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num)
            {
                $obj = $this->db->fetch_object($resql);

                // Si traduction existe, on l'utilise, sinon on prend le libelle par defaut
                $libelle=($langs->trans("PaymentConditionShort".$obj->code)!=("PaymentConditionShort".$obj->code)?$langs->trans("PaymentConditionShort".$obj->code):($obj->libelle!='-'?$obj->libelle:''));
                $this->cache_conditions_paiements[$obj->rowid]['code'] =$obj->code;
                $this->cache_conditions_paiements[$obj->rowid]['label']=$libelle;
                $i++;
            }
            return 1;
        }
        else {
            dol_print_error($this->db);
            return -1;
        }
    }

    /**
     *      \brief      Charge dans cache la liste des types de paiements possibles
     *      \return     int             Nb lignes chargees, 0 si deja chargees, <0 si ko
     */
    function load_cache_types_paiements()
    {
        global $langs;

        if (sizeof($this->cache_types_paiements)) return 0;    // Cache deja charge

        $sql = "SELECT id, code, libelle, type";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_paiement";
        $sql.= " WHERE active > 0";
        $sql.= " ORDER BY id";
        dol_syslog('Form::load_cache_types_paiements sql='.$sql,LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num)
            {
                $obj = $this->db->fetch_object($resql);

                // Si traduction existe, on l'utilise, sinon on prend le libelle par defaut
                $libelle=($langs->trans("PaymentTypeShort".$obj->code)!=("PaymentTypeShort".$obj->code)?$langs->trans("PaymentTypeShort".$obj->code):($obj->libelle!='-'?$obj->libelle:''));
                $this->cache_types_paiements[$obj->id]['code'] =$obj->code;
                $this->cache_types_paiements[$obj->id]['label']=$libelle;
                $this->cache_types_paiements[$obj->id]['type'] =$obj->type;
                $i++;
            }
            return $num;
        }
        else {
            dol_print_error($this->db);
            return -1;
        }
    }


    /**
     *      \brief      Retourne la liste des types de paiements possibles
     *      \param      selected        Id du type de paiement pre-selectionne
     *      \param      htmlname        Nom de la zone select
     *      \param      filtertype      Pour filtre
     *		\param		addempty		Ajoute entree vide
     */
    function select_conditions_paiements($selected='',$htmlname='condid',$filtertype=-1,$addempty=0)
    {
        global $langs,$user;

        $this->load_cache_conditions_paiements();

        print '<select class="flat" name="'.$htmlname.'">';
        if ($addempty) print '<option value="0">&nbsp;</option>';
        foreach($this->cache_conditions_paiements as $id => $arrayconditions)
        {
            if ($selected == $id)
            {
                print '<option value="'.$id.'" selected="true">';
            }
            else
            {
                print '<option value="'.$id.'">';
            }
            print $arrayconditions['label'];
            print '</option>';
        }
        print '</select>';
        if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
    }


    /**
     *      \brief      Retourne la liste des modes de paiements possibles
     *      \param      selected        Id du mode de paiement pre-selectionne
     *      \param      htmlname        Nom de la zone select
     *      \param      filtertype      To filter on field type in llx_c_paiement
     *      \param      format          0=id+libelle, 1=code+code, 2=code+libelle
     *      \param      empty			1=peut etre vide, 0 sinon
     * 		\param		noadmininfo		0=Add admin info, 1=Disable admin info
     */
    function select_types_paiements($selected='',$htmlname='paiementtype',$filtertype='',$format=0, $empty=0, $noadmininfo=0)
    {
        global $langs,$user;

        dol_syslog("Form::select_type_paiements $selected, $htmlname, $filtertype, $format",LOG_DEBUG);

        $filterarray=array();
        if ($filtertype == 'CRDT')  	$filterarray=array(0,2);
        elseif ($filtertype == 'DBIT') 	$filterarray=array(1,2);
        elseif ($filtertype != '' && $filtertype != '-1') $filterarray=explode(',',$filtertype);

        $this->load_cache_types_paiements();

        print '<select id="select'.$htmlname.'" class="flat selectpaymenttypes" name="'.$htmlname.'">';
        if ($empty) print '<option value="">&nbsp;</option>';
        foreach($this->cache_types_paiements as $id => $arraytypes)
        {
            // On passe si on a demande de filtrer sur des modes de paiments particuliers
            if (sizeof($filterarray) && ! in_array($arraytypes['type'],$filterarray)) continue;

            // We discard empty line if showempty is on because an empty line has already been output.
            if ($empty && empty($arraytypes['code'])) continue;

            if ($format == 0) print '<option value="'.$id.'"';
            if ($format == 1) print '<option value="'.$arraytypes['code'].'"';
            if ($format == 2) print '<option value="'.$arraytypes['code'].'"';
            // Si selected est text, on compare avec code, sinon avec id
            if (preg_match('/[a-z]/i', $selected) && $selected == $arraytypes['code']) print ' selected="true"';
            elseif ($selected == $id) print ' selected="true"';
            print '>';
            if ($format == 0) $value=$arraytypes['label'];
            if ($format == 1) $value=$arraytypes['code'];
            if ($format == 2) $value=$arraytypes['label'];
            print $value?$value:'&nbsp;';
            print '</option>';
        }
        print '</select>';
        if ($user->admin && ! $noadmininfo) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
    }

    /**
     *      \brief      Selection HT ou TTC
     *      \param      selected        Id pre-selectionne
     *      \param      htmlname        Nom de la zone select
     */
    function select_PriceBaseType($selected='',$htmlname='price_base_type')
    {
        print $this->load_PriceBaseType($selected,$htmlname);
    }

    /**
     *      \brief      Selection HT ou TTC
     *      \param      selected        Id pre-selectionne
     *      \param      htmlname        Nom de la zone select
     */
    function load_PriceBaseType($selected='',$htmlname='price_base_type')
    {
        global $langs;

        $return='';

        $return.= '<select class="flat" name="'.$htmlname.'">';
        $options = array(
					'HT'=>$langs->trans("HT"),
					'TTC'=>$langs->trans("TTC")
        );
        foreach($options as $id => $value)
        {
            if ($selected == $id)
            {
                $return.= '<option value="'.$id.'" selected="true">'.$value;
            }
            else
            {
                $return.= '<option value="'.$id.'">'.$value;
            }
            $return.= '</option>';
        }
        $return.= '</select>';

        return $return;
    }

    /**
     *    \brief      Retourne la liste deroulante des differents etats d'une propal.
     *                Les valeurs de la liste sont les id de la table c_propalst
     *    \param      selected    etat pre-selectionne
     */
    function select_propal_statut($selected='')
    {
        $sql = "SELECT id, code, label, active FROM ".MAIN_DB_PREFIX."c_propalst";
        $sql .= " WHERE active = 1";

        dol_syslog("Form::select_propal_statut sql=".$sql);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            print '<select class="flat" name="propal_statut">';
            print '<option value="">&nbsp;</option>';
            $num = $this->db->num_rows($resql);
            $i = 0;
            if ($num)
            {
                while ($i < $num)
                {
                    $obj = $this->db->fetch_object($resql);
                    if ($selected == $obj->id)
                    {
                        print '<option value="'.$obj->id.'" selected="true">';
                    }
                    else
                    {
                        print '<option value="'.$obj->id.'">';
                    }
                    print $obj->label;
                    print '</option>';
                    $i++;
                }
            }
            print '</select>';
        }
        else
        {
            dol_print_error($this->db);
        }
    }


    /**
     *    Return list of bank accounts
     *    @param      selected          Id account pre-selected
     *    @param      htmlname          Name of select zone
     *    @param      statut            Status of searched accounts (0=open, 1=closed)
     *    @param      filtre            To filter list
     *    @param      useempty          Add an empty value in list
     */
    function select_comptes($selected='',$htmlname='accountid',$statut=0,$filtre='',$useempty=0)
    {
        global $langs, $conf;

        $langs->load("admin");

        $sql = "SELECT rowid, label, bank";
        $sql.= " FROM ".MAIN_DB_PREFIX."bank_account";
        $sql.= " WHERE clos = '".$statut."'";
        $sql.= " AND entity = ".$conf->entity;
        if ($filtre) $sql.=" AND ".$filtre;
        $sql.= " ORDER BY label";

        dol_syslog("Form::select_comptes sql=".$sql);
        $result = $this->db->query($sql);
        if ($result)
        {
            $num = $this->db->num_rows($result);
            $i = 0;
            if ($num)
            {
                print '<select id="select'.$htmlname.'" class="flat selectbankaccount" name="'.$htmlname.'">';
                if ($useempty)
                {
                    print '<option value="'.$obj->rowid.'">&nbsp;</option>';
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
                    print $obj->label;
                    print '</option>';
                    $i++;
                }
                print "</select>";
            }
            else
            {
                print $langs->trans("NoActiveBankAccountDefined");
            }
        }
        else {
            dol_print_error($this->db);
        }
    }

    /**
     *    Return list of categories having choosed type
     *    @param    type			Type de categories (0=product, 1=supplier, 2=customer, 3=member)
     *    @param    selected    	Id of category preselected
     *    @param    select_name		HTML field name
     *    @param    maxlength       Maximum length for labels
     *    @param    excludeafterid  Exclude all categories after this leaf in category tree.
     */
    function select_all_categories($type, $selected='', $select_name="", $maxlength=64, $excludeafterid=0)
    {
        global $langs;
        $langs->load("categories");

        if ($select_name=="") $select_name="catMere";

        $cat = new Categorie($this->db);
        $cate_arbo = $cat->get_full_arbo($type,$excludeafterid);

        $output = '<select class="flat" name="'.$select_name.'">';
        if (is_array($cate_arbo))
        {
            if (! sizeof($cate_arbo)) $output.= '<option value="-1" disabled="true">'.$langs->trans("NoCategoriesDefined").'</option>';
            else
            {
                $output.= '<option value="-1">&nbsp;</option>';
                foreach($cate_arbo as $key => $value)
                {
                    if ($cate_arbo[$key]['id'] == $selected)
                    {
                        $add = 'selected="true" ';
                    }
                    else
                    {
                        $add = '';
                    }
                    $output.= '<option '.$add.'value="'.$cate_arbo[$key]['id'].'">'.dol_trunc($cate_arbo[$key]['fulllabel'],$maxlength,'middle').'</option>';
                }
            }
        }
        $output.= '</select>';
        $output.= "\n";
        return $output;
    }

	/**
     *     Show a confirmation HTML form or AJAX popup
     *     @param  page        	   Url of page to call if confirmation is OK
     *     @param  title       	   title
     *     @param  question    	   question
     *     @param  action      	   action
     *	   @param  formquestion	   an array with forms complementary inputs
     * 	   @param  selectedchoice  "" or "no" or "yes"
     * 	   @param  useajax		   0=No, 1=Yes, 2=Yes but submit page with &confirm=no if choice is No
     *     @return string          'ajax' if a confirm ajax popup is shown, 'html' if it's an html form
     */
    function form_confirm($page, $title, $question, $action, $formquestion='', $selectedchoice="", $useajax=0)
    {
    	print $this->formconfirm($page, $title, $question, $action, $formquestion, $selectedchoice, $useajax);
    }

    /**
     *     Show a confirmation HTML form or AJAX popup
     *     @param  page        	   Url of page to call if confirmation is OK
     *     @param  title       	   title
     *     @param  question    	   question
     *     @param  action      	   action
     *	   @param  formquestion	   an array with forms complementary inputs
     * 	   @param  selectedchoice  "" or "no" or "yes"
     * 	   @param  useajax		   0=No, 1=Yes, 2=Yes but submit page with &confirm=no if choice is No
     *     @return string          'ajax' if a confirm ajax popup is shown, 'html' if it's an html form
     */
    function formconfirm($page, $title, $question, $action, $formquestion='', $selectedchoice="", $useajax=0)
    {
        global $langs,$conf;

        $more='';
        $formconfirm='';

        if ($formquestion)
        {
            $more.='<tr class="valid"><td class="valid" colspan="3">'."\n";
            $more.='<table class="nobordernopadding" width="100%">'."\n";
            $more.='<tr><td colspan="3" valign="top">'.$formquestion['text'].'</td></tr>'."\n";
            foreach ($formquestion as $key => $input)
            {
                if ($input['type'] == 'text')
                {
                    $more.='<tr><td valign="top">'.$input['label'].'</td><td valign="top" colspan="2" align="left"><input type="text" class="flat" name="'.$input['name'].'" size="'.$input['size'].'" value="'.$input['value'].'"></td></tr>'."\n";
                }
                if ($input['type'] == 'select')
                {
                    $more.='<tr><td valign="top">';
                    $more.=$this->selectarray($input['name'],$input['values'],'',1);
                    $more.='</td></tr>'."\n";
                }
                if ($input['type'] == 'checkbox')
                {
                    $more.='<tr>';
                    //$more.='<td valign="top">'.$input['label'].' &nbsp;';
                    $more.='<td valign="top">'.$input['label'].' </td><td valign="top" align="left">';
                    $more.='<input type="checkbox" class="flat" name="'.$input['name'].'"';
                    if (! is_bool($input['value']) && $input['value'] != 'false') $more.=' checked="true"';
                    if (is_bool($input['value']) && $input['value']) $more.=' checked="true"';
                    if ($input['disabled']) $more.=' disabled="true"';
                    $more.='>';
                    $more.='</td>';
                    //$more.='<td valign="top" align="left">&nbsp;</td>';
                    $more.='<td valign="top" align="left">&nbsp;</td>';
                    $more.='</tr>'."\n";
                }
                if ($input['type'] == 'radio')
                {
                    $i=0;
                    foreach($input['values'] as $selkey => $selval)
                    {
                        $more.='<tr>';
                        if ($i==0) $more.='<td valign="top">'.$input['label'].'</td>';
                        else $more.='<td>&nbsp;</td>';
                        $more.='<td valign="top" width="20"><input type="radio" class="flat" name="'.$input['name'].'" value="'.$selkey.'"';
                        if ($input['disabled']) $more.=' disabled="true"';
                        $more.='></td>';
                        $more.='<td valign="top" align="left">';
                        $more.=$selval;
                        $more.='</td></tr>'."\n";
                        $i++;
                    }
                }
            }
            $more.='</table>'."\n";
            $more.='</td></tr>'."\n";
        }

        $formconfirm.= "\n<!-- begin form_confirm -->\n";

        if ($useajax && $conf->use_javascript_ajax && $conf->global->MAIN_CONFIRM_AJAX)
        {
            $pageyes=$page.'&action='.$action.'&confirm=yes';
            $pageno=($useajax == 2?$page.'&confirm=no':'');

            // New code using jQuery only
            $formconfirm.= '<div id="dialog-confirm" title="'.dol_escape_htmltag($title).'">';
            $formconfirm.= img_help('','').' '.$more.$question;
            $formconfirm.= '</div>'."\n";
            $formconfirm.= '<script type="text/javascript">
                var choice=\'ko\';
			    jQuery("#dialog-confirm").dialog({
			        autoOpen: true,
			        resizable: false,
			        height:170,
			        width:590,
			        modal: true,
			        closeOnEscape: false,
			        close: function(event, ui) {
			             if (choice == \'ok\') location.href=\''.$pageyes.'\';
                         '.($pageno?'if (choice == \'ko\') location.href=\''.$pageno.'\';':'').'
		              },
			        buttons: {
			            \''.dol_escape_js($langs->transnoentities("Yes")).'\': function() {
			                 choice=\'ok\';
			                jQuery(this).dialog(\'close\');
			            },
			            \''.dol_escape_js($langs->transnoentities("No")).'\': function() {
			                 choice=\'ko\';
			                jQuery(this).dialog(\'close\');
			            }
			        }
			    });
			</script>';

            $formconfirm.= "\n";
        }
        else
        {
            $formconfirm.= '<form method="post" action="'.$page.'" class="notoptoleftroright">'."\n";
            $formconfirm.= '<input type="hidden" name="action" value="'.$action.'">';
            $formconfirm.= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";

            $formconfirm.= '<table width="100%" class="valid">'."\n";

            // Ligne titre
            $formconfirm.= '<tr class="validtitre"><td class="validtitre" colspan="3">'.img_picto('','recent').' '.$title.'</td></tr>'."\n";

            // Ligne formulaire
            $formconfirm.= $more;

            // Ligne message
            $formconfirm.= '<tr class="valid">';
            $formconfirm.= '<td class="valid">'.$question.'</td>';
            $formconfirm.= '<td class="valid">';
            $newselectedchoice=empty($selectedchoice)?"no":$selectedchoice;
            $formconfirm.= $this->selectyesno("confirm",$newselectedchoice);
            $formconfirm.= '</td>';
            $formconfirm.= '<td class="valid" align="center"><input class="button" type="submit" value="'.$langs->trans("Validate").'"></td>';
            $formconfirm.= '</tr>'."\n";

            $formconfirm.= '</table>'."\n";

            if (is_array($formquestion))
            {
                foreach ($formquestion as $key => $input)
                {
                    if ($input['type'] == 'hidden') $formconfirm.= '<input type="hidden" name="'.$input['name'].'" value="'.$input['value'].'">';
                }
            }

            $formconfirm.= "</form>\n";
            $formconfirm.= '<br>';
        }

        $formconfirm.= "<!-- end form_confirm -->\n";
        return $formconfirm;
    }


    /**
     *    \brief      Affiche formulaire de selection de projet
     *    \param      page        Page
     *    \param      socid       Id societe
     *    \param      selected    Id projet pre-selectionne
     *    \param      htmlname    Nom du formulaire select
     */
    function form_project($page, $socid, $selected='', $htmlname='projectid')
    {
        global $langs;

        require_once(DOL_DOCUMENT_ROOT."/lib/project.lib.php");

        $langs->load("project");
        if ($htmlname != "none")
        {
            print '<form method="post" action="'.$page.'">';
            print '<input type="hidden" name="action" value="classin">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
            print '<tr><td>';
            //print "$socid,$selected,$htmlname";
            select_projects($socid,$selected,$htmlname);
            print '</td>';
            print '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
            print '</tr></table></form>';
        }
        else
        {
            if ($selected) {
                $projet = new Project($this->db);
                $projet->fetch($selected);
                //print '<a href="'.DOL_URL_ROOT.'/projet/fiche.php?id='.$selected.'">'.$projet->title.'</a>';
                print $projet->getNomUrl(0);
            } else {
                print "&nbsp;";
            }
        }
    }

    /**
     *    	\brief      Affiche formulaire de selection de conditions de paiement
     *    	\param      page        	Page
     *    	\param      selected    	Id condition pre-selectionne
     *    	\param      htmlname    	Name of select html field
     *		\param		addempty		Ajoute entree vide
     */
    function form_conditions_reglement($page, $selected='', $htmlname='cond_reglement_id', $addempty=0)
    {
        global $langs;
        if ($htmlname != "none")
        {
            print '<form method="post" action="'.$page.'">';
            print '<input type="hidden" name="action" value="setconditions">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
            print '<tr><td>';
            $this->select_conditions_paiements($selected,$htmlname,-1,$addempty);
            print '</td>';
            print '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
            print '</tr></table></form>';
        }
        else
        {
            if ($selected)
            {
                $this->load_cache_conditions_paiements();
                print $this->cache_conditions_paiements[$selected]['label'];
            } else {
                print "&nbsp;";
            }
        }
    }


    /**
     *    \brief      Affiche formulaire de selection d'une date
     *    \param      page        Page
     *    \param      selected    Date preselected
     *    \param      htmlname    Name of input html field
     */
    function form_date($page, $selected='', $htmlname)
    {
        global $langs;

        if ($htmlname != "none")
        {
            print '<form method="post" action="'.$page.'" name="form'.$htmlname.'">';
            print '<input type="hidden" name="action" value="set'.$htmlname.'">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
            print '<tr><td>';
            print $this->select_date($selected,$htmlname,0,0,1,'form'.$htmlname);
            print '</td>';
            print '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
            print '</tr></table></form>';
        }
        else
        {
            if ($selected)
            {
                $this->load_cache_types_paiements();
                print $this->cache_types_paiements[$selected]['label'];
            } else {
                print "&nbsp;";
            }
        }
    }


    /**
     *    	\brief      Affiche formulaire de selection d'un utilisateur
     *    	\param      page        	Page
     *   	\param      selected    	Id of user preselected
     *    	\param      htmlname    	Name of input html field
     *  	\param      exclude         List of users id to exclude
     *  	\param      include         List of users id to include
     */
    function form_users($page, $selected='', $htmlname='userid', $exclude='', $include='')
    {
        global $langs;

        if ($htmlname != "none")
        {
            print '<form method="POST" action="'.$page.'" name="form'.$htmlname.'">';
            print '<input type="hidden" name="action" value="set'.$htmlname.'">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
            print '<tr><td>';
            print $this->select_users($selected,$htmlname,1,$exclude,0,$include);
            print '</td>';
            print '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
            print '</tr></table></form>';
        }
        else
        {
            if ($selected)
            {
                require_once(DOL_DOCUMENT_ROOT ."/user/class/user.class.php");
                //$this->load_cache_contacts();
                //print $this->cache_contacts[$selected];
                $theuser=new User($this->db);
                $theuser->fetch($selected);
                print $theuser->getNomUrl(1);
            } else {
                print "&nbsp;";
            }
        }
    }


    /**
     *    \brief      Affiche formulaire de selection des modes de reglement
     *    \param      page        Page
     *    \param      selected    Id mode pre-selectionne
     *    \param      htmlname    Name of select html field
     */
    function form_modes_reglement($page, $selected='', $htmlname='mode_reglement_id')
    {
        global $langs;
        if ($htmlname != "none")
        {
            print '<form method="POST" action="'.$page.'">';
            print '<input type="hidden" name="action" value="setmode">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
            print '<tr><td>';
            $this->select_types_paiements($selected,$htmlname);
            print '</td>';
            print '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
            print '</tr></table></form>';
        }
        else
        {
            if ($selected)
            {
                $this->load_cache_types_paiements();
                print $this->cache_types_paiements[$selected]['label'];
            } else {
                print "&nbsp;";
            }
        }
    }


    /**
     *    	\brief      Affiche formulaire de selection de la remise fixe
     *    	\param      page        	Page URL where form is shown
     *    	\param      selected    	Value pre-selected
     *		\param      htmlname    	Nom du formulaire select. Si none, non modifiable
     *		\param		socid			Third party id
     * 		\param		amount			Total amount available
     * 	  	\param		filter			SQL filter on discounts
     * 	  	\param		maxvalue		Max value for lines that can be selected
     */
    function form_remise_dispo($page, $selected='', $htmlname='remise_id',$socid, $amount, $filter='', $maxvalue=0)
    {
        global $conf,$langs;
        if ($htmlname != "none")
        {
            print '<form method="post" action="'.$page.'">';
            print '<input type="hidden" name="action" value="setabsolutediscount">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
            print '<tr><td>';
            if (! $filter || $filter=='fk_facture_source IS NULL') print $langs->trans("CompanyHasAbsoluteDiscount",price($amount),$langs->transnoentities("Currency".$conf->monnaie)).': ';
            else print $langs->trans("CompanyHasCreditNote",price($amount),$langs->transnoentities("Currency".$conf->monnaie)).': ';
            //			print $langs->trans("AvailableGlobalDiscounts").': ';
            $newfilter='fk_facture IS NULL AND fk_facture_line IS NULL';	// Remises disponibles
            if ($filter) $newfilter.=' AND '.$filter;
            $nbqualifiedlines=$this->select_remises('',$htmlname,$newfilter,$socid,$maxvalue);
            print '</td>';
            print '<td align="left">';
            if ($nbqualifiedlines > 0)
            {
                print ' &nbsp; <input type="submit" class="button" value="';
                if (! $filter || $filter=='fk_facture_source IS NULL') print $langs->trans("UseDiscount");
                else print $langs->trans("UseCredit");
                print '" title="'.$langs->trans("UseCreditNoteInInvoicePayment").'">';
            }
            print '</td>';
            print '</tr></table></form>';
        }
        else
        {
            if ($selected)
            {
                print $selected;
            }
            else
            {
                print "0";
            }
        }
    }


    /**
     *    \brief      Affiche formulaire de selection des contacts
     *    \param      page        Page
     *    \param      selected    Id contact pre-selectionne
     *    \param      htmlname    Nom du formulaire select
     */
    function form_contacts($page, $societe, $selected='', $htmlname='contactidp')
    {
        global $langs;
        if ($htmlname != "none")
        {
            print '<form method="post" action="'.$page.'">';
            print '<input type="hidden" name="action" value="set_contact">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
            print '<tr><td>';
            $num=$this->select_contacts($societe->id, $selected, $htmlname);
            if ($num==0)
            {
                print '<font class="error">Cette societe n\'a pas de contact, veuillez en cr�er un avant de faire votre proposition commerciale</font><br>';
                print '<a href="'.DOL_URL_ROOT.'/contact/fiche.php?socid='.$societe->id.'&amp;action=create&amp;backtoreferer=1">'.$langs->trans('AddContact').'</a>';
            }
            print '</td>';
            print '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
            print '</tr></table></form>';
        }
        else
        {
            if ($selected)
            {
                require_once(DOL_DOCUMENT_ROOT ."/contact/class/contact.class.php");
                //$this->load_cache_contacts();
                //print $this->cache_contacts[$selected];
                $contact=new Contact($this->db);
                $contact->fetch($selected);
                print $contact->getFullName($langs);
            } else {
                print "&nbsp;";
            }
        }
    }

    /**
     *    	\brief      Affiche formulaire de selection de l'adresse
     *    	\param      page        	Page
     *    	\param      selected    	Id condition pre-selectionne
     *    	\param      htmlname    	Nom du formulaire select
     *		\param		origin        	Origine de l'appel pour pouvoir creer un retour
     *      \param      originid      	Id de l'origine
     */
    function form_address($page, $selected='', $socid, $htmlname='address_id', $origin='', $originid='')
    {
        global $langs,$conf;
        if ($htmlname != "none")
        {
            print '<form method="post" action="'.$page.'">';
            print '<input type="hidden" name="action" value="setaddress">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
            print '<tr><td>';
            $this->select_address($selected, $socid, $htmlname, 1);
            print '</td>';
            print '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'">';
            $langs->load("companies");
            print ' &nbsp; <a href='.DOL_URL_ROOT.'/comm/address.php?socid='.$socid.'&action=create&origin='.$origin.'&originid='.$originid.'>'.$langs->trans("AddAddress").'</a>';
            print '</td></tr></table></form>';
        }
        else
        {
            if ($selected)
            {
                require_once(DOL_DOCUMENT_ROOT ."/societe/class/address.class.php");
                $address=new Address($this->db);
                $result=$address->fetch_address($selected);
                print '<a href='.DOL_URL_ROOT.'/comm/address.php?socid='.$address->socid.'&id='.$address->id.'&action=edit&origin='.$origin.'&originid='.$originid.'>'.$address->label.'</a>';
            }
            else
            {
                print "&nbsp;";
            }
        }
    }

    /**
     *    Retourne la liste des devises, dans la langue de l'utilisateur
     *    @param     selected    code devise pre-selectionne
     *    @param     htmlname    nom de la liste deroulante
     *    TODO      trier liste sur noms apres traduction plutot que avant
     */
    function select_currency($selected='',$htmlname='currency_id')
    {
    	print $this->selectcurrency($selected,$htmlname);
    }

    /**
     *    \brief     Retourne la liste des devises, dans la langue de l'utilisateur
     *    \param     selected    code devise pre-selectionne
     *    \param     htmlname    nom de la liste deroulante
     *    \todo      trier liste sur noms apres traduction plutot que avant
     */
    function selectcurrency($selected='',$htmlname='currency_id')
    {
        global $conf,$langs,$user;
        $langs->load("dict");

        $out='';

        if ($selected=='euro' || $selected=='euros') $selected='EUR';   // Pour compatibilite

        $sql = "SELECT code, code_iso, label, active";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_currencies";
        $sql.= " WHERE active = 1";
        $sql.= " ORDER BY code_iso ASC";

        $resql=$this->db->query($sql);
        if ($resql)
        {
            $out.= '<select class="flat" name="'.$htmlname.'">';
            $num = $this->db->num_rows($resql);
            $i = 0;
            if ($num)
            {
                $foundselected=false;
                while ($i < $num)
                {
                    $obj = $this->db->fetch_object($resql);
                    if ($selected && $selected == $obj->code_iso)
                    {
                        $foundselected=true;
                        $out.= '<option value="'.$obj->code_iso.'" selected="true">';
                    }
                    else
                    {
                        $out.= '<option value="'.$obj->code_iso.'">';
                    }
                    if ($obj->code_iso) { $out.= $obj->code_iso . ' - '; }
                    // Si traduction existe, on l'utilise, sinon on prend le libelle par defaut
                    $out.= ($obj->code_iso && $langs->trans("Currency".$obj->code_iso)!="Currency".$obj->code_iso?$langs->trans("Currency".$obj->code_iso):($obj->label!='-'?$obj->label:''));
                    $out.= '</option>';
                    $i++;
                }
            }
            $out.= '</select>';
            if ($user->admin) $out.= info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
            return $out;
        }
        else
        {
            dol_print_error($this->db);
        }
    }

    /**
     *      \brief      Output an HTML select vat rate
     *      \param      name                Nom champ html
     *      \param      selectedrate        Forcage du taux tva pre-selectionne. Mettre '' pour aucun forcage.
     *      \param      societe_vendeuse    Objet societe vendeuse
     *      \param      societe_acheteuse   Objet societe acheteuse
     *      \param      idprod             Id product
     *      \param      info_bits           Miscellanous information on line
     *      \param      type               ''=Unknown, 0=Product, 1=Service (Used if idprod not defined)
     *      \remarks    Si vendeur non assujeti a TVA, TVA par defaut=0. Fin de regle.
     *                  Si le (pays vendeur = pays acheteur) alors la TVA par defaut=TVA du produit vendu. Fin de regle.
     *                  Si (vendeur et acheteur dans Communaute europeenne) et bien vendu = moyen de transports neuf (auto, bateau, avion), TVA par defaut=0 (La TVA doit etre paye par l'acheteur au centre d'impots de son pays et non au vendeur). Fin de regle.
     *                  Si (vendeur et acheteur dans Communaute europeenne) et bien vendu autre que transport neuf alors la TVA par defaut=TVA du produit vendu. Fin de regle.
     *                  Sinon la TVA proposee par defaut=0. Fin de regle.
     */
    function select_tva($name='tauxtva', $selectedrate='', $societe_vendeuse='', $societe_acheteuse='', $idprod=0, $info_bits=0, $type='')
    {
        print $this->load_tva($name, $selectedrate, $societe_vendeuse, $societe_acheteuse, $idprod, $info_bits, $type);
    }


    /**
     *      \brief      Output an HTML select vat rate
     *      \param      name               Nom champ html
     *      \param      selectedrate       Forcage du taux tva pre-selectionne. Mettre '' pour aucun forcage.
     *      \param      societe_vendeuse   Objet societe vendeuse
     *      \param      societe_acheteuse  Objet societe acheteuse
     *      \param      idprod             Id product
     *      \param      info_bits          Miscellanous information on line
     *      \param      type               ''=Unknown, 0=Product, 1=Service (Used if idprod not defined)
     *      \remarks    Si vendeur non assujeti a TVA, TVA par defaut=0. Fin de regle.
     *                  Si le (pays vendeur = pays acheteur) alors la TVA par defaut=TVA du produit vendu. Fin de regle.
     *                  Si (vendeur et acheteur dans Communaute europeenne) et bien vendu = moyen de transports neuf (auto, bateau, avion), TVA par defaut=0 (La TVA doit etre paye par l'acheteur au centre d'impots de son pays et non au vendeur). Fin de regle.
     *                  Si (vendeur et acheteur dans Communaute europeenne) et bien vendu autre que transport neuf alors la TVA par defaut=TVA du produit vendu. Fin de regle.
     *                  Sinon la TVA proposee par defaut=0. Fin de regle.
     */
    function load_tva($name='tauxtva', $selectedrate='', $societe_vendeuse='', $societe_acheteuse='', $idprod=0, $info_bits=0, $type='')
    {
        global $langs,$conf,$mysoc;

        $return='';
        $txtva=array();
        $libtva=array();
        $nprtva=array();

        // Define defaultnpr and defaultttx
        $defaultnpr=($info_bits & 0x01);
        $defaultnpr=(preg_match('/\*/',$selectedrate) ? 1 : $defaultnpr);
        $defaulttx=str_replace('*','',$selectedrate);

        // Check parameters
        if (is_object($societe_vendeuse) && ! $societe_vendeuse->pays_code)
        {
            if ($societe_vendeuse->id == $mysoc->id)
            {
                $return.= '<font class="error">'.$langs->trans("ErrorYourCountryIsNotDefined").'</div>';
            }
            else
            {
                $return.= '<font class="error">'.$langs->trans("ErrorSupplierCountryIsNotDefined").'</div>';
            }
            return $return;
        }

        //var_dump($societe_acheteuse);
        //print "name=$name, selectedrate=$selectedrate, seller=".$societe_vendeuse->pays_code." buyer=".$societe_acheteuse->pays_code." buyer is company=".$societe_acheteuse->isACompany()." idprod=$idprod, info_bits=$info_bits type=$type";
        //exit;

        // Get list of all VAT rates to show
        // First we defined code_pays to use to find list
        if (is_object($societe_vendeuse))
        {
            $code_pays="'".$societe_vendeuse->pays_code."'";
        }
        else
        {
            $code_pays="'".$mysoc->pays_code."'";   // Pour compatibilite ascendente
        }
        if (! empty($conf->global->SERVICE_ARE_ECOMMERCE_200238EC))    // If option to have vat for end customer for services is on
        {
            if (! $societe_vendeuse->isInEEC() && $societe_acheteuse->isInEEC() && ! $societe_acheteuse->isACompany())
            {
                // We also add the buyer
                if (is_numeric($type))
                {
                    if ($type == 1) // We know product is a service
                    {
                        $code_pays.=",'".$societe_acheteuse->pays_code."'";
                    }
                }
                else if (! $idprod)  // We don't know type of product
                {
                    $code_pays.=",'".$societe_acheteuse->pays_code."'";
                }
                else
                {
                    $prodstatic=new Product($this->db);
                    $prodstatic->fetch($idprod);
                    if ($prodstatic->type == 1)   // We know product is a service
                    {
                        $code_pays.=",'".$societe_acheteuse->pays_code."'";
                    }
                }
            }
        }
        // Now we get list
        $sql  = "SELECT DISTINCT t.taux, t.recuperableonly";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_tva as t, ".MAIN_DB_PREFIX."c_pays as p";
        $sql.= " WHERE t.fk_pays = p.rowid";
        $sql.= " AND t.active = 1";
        $sql.= " AND p.code in (".$code_pays.")";
        $sql.= " ORDER BY t.taux ASC, t.recuperableonly ASC";

        $resql=$this->db->query($sql);
        if ($resql)
        {
            $num = $this->db->num_rows($resql);
            if ($num)
            {
                for ($i = 0; $i < $num; $i++)
                {
                    $obj = $this->db->fetch_object($resql);
                    $txtva[$i]  = $obj->taux;
                    $libtva[$i] = $obj->taux.'%';
                    $nprtva[$i] = $obj->recuperableonly;
                }
            }
            else
            {
                $return.= '<font class="error">'.$langs->trans("ErrorNoVATRateDefinedForSellerCountry",$code_pays).'</font>';
            }
        }
        else
        {
            $return.= '<font class="error">'.$this->db->error().'</font>';
        }

        // Definition du taux a pre-selectionner (si defaulttx non force et donc vaut -1 ou '')
        if ($defaulttx < 0 || dol_strlen($defaulttx) == 0)
        {
            $defaulttx=get_default_tva($societe_vendeuse,$societe_acheteuse,$idprod);
            $defaultnpr=get_default_npr($societe_vendeuse,$societe_acheteuse,$idprod);
        }
        // Si taux par defaut n'a pu etre determine, on prend dernier de la liste.
        // Comme ils sont tries par ordre croissant, dernier = plus eleve = taux courant
        if ($defaulttx < 0 || dol_strlen($defaulttx) == 0)
        {
            $defaulttx = $txtva[sizeof($txtva)-1];
        }

        $nbdetaux = sizeof($txtva);
        if ($nbdetaux > 0)
        {
            $return.= '<select class="flat" name="'.$name.'">';

            for ($i = 0 ; $i < $nbdetaux ; $i++)
            {
                //print "xxxxx".$txtva[$i]."-".$nprtva[$i];
                $return.= '<option value="'.$txtva[$i];
                $return.= $nprtva[$i] ? '*': '';
                $return.= '"';
                if ($txtva[$i] == $defaulttx && $nprtva[$i] == $defaultnpr)
                {
                    $return.= ' selected="true"';
                }
                $return.= '>'.vatrate($libtva[$i]);
                $return.= $nprtva[$i] ? ' *': '';
                $return.= '</option>';

                $this->tva_taux_value[$i] = $txtva[$i];
                $this->tva_taux_libelle[$i] = $libtva[$i];
                $this->tva_taux_npr[$i] = $nprtva[$i];
            }
            $return.= '</select>';
        }

        return $return;
    }


    /**
     *		Affiche zone de selection de date
     *      Liste deroulante pour les jours, mois, annee et eventuellement heurs et minutes
     *      Les champs sont pre-selectionnes avec:
     *            	- La date set_time (Local PHP server timestamps ou date au format YYYY-MM-DD ou YYYY-MM-DD HH:MM)
     *            	- La date local du server PHP si set_time vaut ''
     *            	- Aucune date (champs vides) si set_time vaut -1 (dans ce cas empty doit valoir 1)
     *		@param	set_time 		Pre-selected date (must be a local PHP server timestamp)
     *		@param	prefix			Prefix pour nom champ
     *		@param	h				1=Affiche aussi les heures
     *		@param	m				1=Affiche aussi les minutes
     *		@param	empty			0=Champ obligatoire, 1=Permet une saisie vide
     *		@param	form_name 		Nom du formulaire de provenance. Utilise pour les dates en popup.
     *		@param	d				1=Affiche aussi les jours, mois, annees
     * 		@param	addnowbutton	Add a button "Now"
     * 		@param	nooutput		Do not output html string but return it
     * 		@param 	disabled		Disable input fields
     * 		@return	nothing or string if nooutput is 1
     */
    function select_date($set_time='', $prefix='re', $h=0, $m=0, $empty=0, $form_name="", $d=1, $addnowbutton=0, $nooutput=0, $disabled=0)
    {
        global $conf,$langs;

        $retstring='';

        if($prefix=='') $prefix='re';
        if($h == '') $h=0;
        if($m == '') $m=0;
        if($empty == '') $empty=0;

        if (! $set_time && $empty == 0) $set_time = time();

        // Analyse de la date de pre-selection
        if (preg_match('/^([0-9]+)\-([0-9]+)\-([0-9]+)\s?([0-9]+)?:?([0-9]+)?/',$set_time,$reg))
        {
            // Date au format 'YYYY-MM-DD' ou 'YYYY-MM-DD HH:MM:SS'
            $syear = $reg[1];
            $smonth = $reg[2];
            $sday = $reg[3];
            $shour = $reg[4];
            $smin = $reg[5];
        }
        elseif (strval($set_time) != '' && $set_time != -1)
        {
            // set_time est un timestamps (0 possible)
            $syear = dol_print_date($set_time, "%Y");
            $smonth = dol_print_date($set_time, "%m");
            $sday = dol_print_date($set_time, "%d");
            $shour = dol_print_date($set_time, "%H");
            $smin = dol_print_date($set_time, "%M");
        }
        else
        {
            // Date est '' ou vaut -1
            $syear = '';
            $smonth = '';
            $sday = '';
            $shour = '';
            $smin = '';
        }

        if ($d)
        {
            /*
             * Affiche date en popup
             */
            if ($conf->use_javascript_ajax && $conf->use_popup_calendar)
            {
                //print "e".$set_time." t ".$conf->format_date_short;
                if (strval($set_time) != '' && $set_time != -1)
                {
                    //$formated_date=dol_print_date($set_time,$conf->format_date_short);
                    $formated_date=dol_print_date($set_time,$langs->trans("FormatDateShort"));  // FormatDateShort for dol_print_date/FormatDateShortJava that is same for javascript
                }

                // Calendrier popup version eldy
                if ("$conf->use_popup_calendar" == "eldy")	// Laisser conf->use_popup_calendar entre quote
                {
                    // Zone de saisie manuelle de la date
                    $retstring.='<input id="'.$prefix.'" name="'.$prefix.'" type="text" size="9" maxlength="11" value="'.$formated_date.'"';
                    $retstring.=($disabled?' disabled="true"':'');
                    $retstring.=' onChange="dpChangeDay(\''.$prefix.'\',\''.$langs->trans("FormatDateShortJava").'\'); "';  // FormatDateShort for dol_print_date/FormatDateShortJava that is same for javascript
                    $retstring.='>';

                    // Icone calendrier
                    if (! $disabled)
                    {
                        $retstring.='<button id="'.$prefix.'Button" type="button" class="dpInvisibleButtons"';
                        $base=DOL_URL_ROOT.'/lib/';
                        $retstring.=' onClick="showDP(\''.$base.'\',\''.$prefix.'\',\''.$langs->trans("FormatDateShortJava").'\',\''.$langs->defaultlang.'\');">'.img_object($langs->trans("SelectDate"),'calendar').'</button>';
                    }

                    $retstring.='<input type="hidden" id="'.$prefix.'day"   name="'.$prefix.'day"   value="'.$sday.'">'."\n";
                    $retstring.='<input type="hidden" id="'.$prefix.'month" name="'.$prefix.'month" value="'.$smonth.'">'."\n";
                    $retstring.='<input type="hidden" id="'.$prefix.'year"  name="'.$prefix.'year"  value="'.$syear.'">'."\n";
                }
                else
                {
                    print "Bad value of calendar";
                    // Calendrier popup version defaut
                    /*
                    if ($langs->defaultlang != "")
                    {
                        $retstring.='<script type="text/javascript">';
                        $retstring.='selectedLanguage = "'.substr($langs->defaultlang,0,2).'"';
                        $retstring.='</script>';
                    }
                    $retstring.='<script type="text/javascript" src="'.DOL_URL_ROOT.'/lib/lib_calendar.js"></script>';
                    $retstring.='<input id="'.$prefix.'" type="text" name="'.$prefix.'" size="9" value="'.$formated_date.'"';
                    $retstring.=' onChange="dpChangeDay(\''.$prefix.'\',\''.$langs->trans("FormatDateShortJava").'\')"';
                    $retstring.='> ';
                    $retstring.='<input type="hidden" id="'.$prefix.'day"   name="'.$prefix.'day"   value="'.$sday.'">'."\n";
                    $retstring.='<input type="hidden" id="'.$prefix.'month" name="'.$prefix.'month" value="'.$smonth.'">'."\n";
                    $retstring.='<input type="hidden" id="'.$prefix.'year"  name="'.$prefix.'year"  value="'.$syear.'">'."\n";
                    if ($form_name =="")
                    {
                        $retstring.='<a href="javascript:showCalendar(document.forms[3].'.$prefix.')">';
                        $retstring.='<img style="vertical-align:middle" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/calendar.png" border="0" alt="" title="">';
                        $retstring.='</a>';
                    }
                    else
                    {
                        $retstring.='<a href="javascript:showCalendar(document.forms[\''.$form_name.'\'].'.$prefix.')">';
                        $retstring.='<img style="vertical-align:middle" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/calendar.png" border="0" alt="" title="">';
                        $retstring.='</a>';
                    }
                    */
                }
            }

            /*
             * Affiche date en select
             */
            if (! $conf->use_javascript_ajax || ! $conf->use_popup_calendar)
            {
                // Jour
                $retstring.='<select'.($disabled?' disabled="true"':'').' class="flat" name="'.$prefix.'day">';

                if ($empty || $set_time == -1)
                {
                    $retstring.='<option value="0" selected="true">&nbsp;</option>';
                }

                for ($day = 1 ; $day <= 31; $day++)
                {
                    if ($day == $sday)
                    {
                        $retstring.="<option value=\"$day\" selected=\"true\">$day";
                    }
                    else
                    {
                        $retstring.="<option value=\"$day\">$day";
                    }
                    $retstring.="</option>";
                }

                $retstring.="</select>";

                $retstring.='<select'.($disabled?' disabled="true"':'').' class="flat" name="'.$prefix.'month">';
                if ($empty || $set_time == -1)
                {
                    $retstring.='<option value="0" selected="true">&nbsp;</option>';
                }

                // Month
                for ($month = 1 ; $month <= 12 ; $month++)
                {
                    $retstring.='<option value="'.$month.'"'.($month == $smonth?' selected="true"':'').'>';
                    $retstring.=dol_print_date(mktime(12,0,0,$month,1,2000),"%b");
                    $retstring.="</option>";
                }
                $retstring.="</select>";

                // Year
                if ($empty || $set_time == -1)
                {
                    $retstring.='<input'.($disabled?' disabled="true"':'').' class="flat" type="text" size="3" maxlength="4" name="'.$prefix.'year" value="'.$syear.'">';
                }
                else
                {
                    $retstring.='<select'.($disabled?' disabled="true"':'').' class="flat" name="'.$prefix.'year">';

                    for ($year = $syear - 5; $year < $syear + 10 ; $year++)
                    {
                        if ($year == $syear)
                        {
                            $retstring.="<option value=\"$year\" selected=\"true\">".$year;
                        }
                        else
                        {
                            $retstring.="<option value=\"$year\">".$year;
                        }
                        $retstring.="</option>";
                    }
                    $retstring.="</select>\n";
                }
            }
        }

        if ($d && $h) $retstring.='&nbsp;';

        if ($h)
        {
            /*
             * Affiche heure en select
             */
            $retstring.='<select'.($disabled?' disabled="true"':'').' class="flat" name="'.$prefix.'hour">';
            if ($empty) $retstring.='<option value="-1">&nbsp;</option>';
            for ($hour = 0; $hour < 24; $hour++)
            {
                if (dol_strlen($hour) < 2)
                {
                    $hour = "0" . $hour;
                }
                if ($hour == $shour)
                {
                    $retstring.="<option value=\"$hour\" selected=\"true\">$hour</option>";
                }
                else
                {
                    $retstring.="<option value=\"$hour\">$hour</option>";
                }
            }
            $retstring.="</select>";
            $retstring.="H\n";
        }

        if ($m)
        {
            /*
             * Affiche min en select
             */
            $retstring.='<select'.($disabled?' disabled="true"':'').' class="flat" name="'.$prefix.'min">';
            if ($empty) $retstring.='<option value="-1">&nbsp;</option>';
            for ($min = 0; $min < 60 ; $min++)
            {
                if (dol_strlen($min) < 2)
                {
                    $min = "0" . $min;
                }
                if ($min == $smin)
                {
                    $retstring.="<option value=\"$min\" selected=\"true\">$min</option>";
                }
                else
                {
                    $retstring.="<option value=\"$min\">$min</option>";
                }
            }
            $retstring.="</select>";
            $retstring.="M\n";
        }

        // Added by Matelli http://matelli.fr/showcases/patchs-dolibarr/update-date-input-in-action-form.html)
        // "Now" button
        if ($conf->use_javascript_ajax && $addnowbutton)
        {
            // Script which will be inserted in the OnClick of the "Now" button
            $reset_scripts = "";

            // Generate the date part, depending on the use or not of the javascript calendar
            if ($conf->use_popup_calendar)
            {
                $base=DOL_URL_ROOT.'/lib/';
                $reset_scripts .= 'resetDP(\''.$base.'\',\''.$prefix.'\',\''.$langs->trans("FormatDateShortJava").'\',\''.$langs->defaultlang.'\');';
            }
            else
            {
                $reset_scripts .= 'this.form.elements[\''.$prefix.'day\'].value=formatDate(new Date(), \'d\'); ';
                $reset_scripts .= 'this.form.elements[\''.$prefix.'month\'].value=formatDate(new Date(), \'M\'); ';
                $reset_scripts .= 'this.form.elements[\''.$prefix.'year\'].value=formatDate(new Date(), \'yyyy\'); ';
            }
            // Generate the hour part
            if ($h)
            {
                $reset_scripts .= 'this.form.elements[\''.$prefix.'hour\'].value=formatDate(new Date(), \'HH\'); ';
            }
            // Generate the minute part
            if ($m)
            {
                $reset_scripts .= 'this.form.elements[\''.$prefix.'min\'].value=formatDate(new Date(), \'mm\'); ';
            }
            // If reset_scripts is not empty, print the button with the reset_scripts in OnClick
            if ($reset_scripts)
            {
                $retstring.='<button class="dpInvisibleButtons" id="'.$prefix.'ButtonNow" type="button" name="_useless" value="Maintenant" onClick="'.$reset_scripts.'">';
                $retstring.=$langs->trans("Now");
                //print img_refresh($langs->trans("Now"));
                $retstring.='</button> ';
            }
        }

        if (! empty($nooutput)) return $retstring;

        print $retstring;
        return;
    }

    /**
     *	\brief  	Function to show a form to select a duration on a page
     *	\param		prefix   	prefix
     *	\param  	iSecond  	Default preselected duration (number of seconds)
     * 	\param		disabled	Disable the combo box
     */
    function select_duration($prefix,$iSecond='',$disabled=0)
    {
        if ($iSecond)
        {
            require_once(DOL_DOCUMENT_ROOT."/lib/date.lib.php");

            $hourSelected = ConvertSecondToTime($iSecond,'hour');
            $minSelected = ConvertSecondToTime($iSecond,'min');
        }

        print '<select class="flat" name="'.$prefix.'hour"'.($disabled?' disabled="true"':'').'>';
        for ($hour = 0; $hour < 24; $hour++)
        {
            print '<option value="'.$hour.'"';
            if ($hourSelected == $hour)
            {
                print " selected=\"true\"";
            }
            print ">".$hour."</option>";
        }
        print "</select>";
        print "H &nbsp;";
        print '<select class="flat" name="'.$prefix.'min"'.($disabled?' disabled="true"':'').'>';
        for ($min = 0; $min <= 55; $min=$min+5)
        {
            print '<option value="'.$min.'"';
            if ($minSelected == $min) print ' selected="true"';
            print '>'.$min.'</option>';
        }
        print "</select>";
        print "M&nbsp;";
    }


    /**
     *	Show a select form from an array
     *	@param	htmlname        Name of html select area
     *	@param	array           Array with key+value
     *	@param	id              Preselected key
     *	@param	show_empty      1 si il faut ajouter une valeur vide dans la liste, 0 sinon
     *	@param	key_in_label    1 pour afficher la key dans la valeur "[key] value"
     *	@param	value_as_key    1 to use value as key
     *	@param  option          Valeur de l'option en fonction du type choisi
     *	@param  translate       Traduire la valeur
     * 	@param	maxlen			Length maximum for labels
     * 	@param	disabled		Html select box is disabled
     * 	@return	string			HTML select string
     */
    function selectarray($htmlname, $array, $id='', $show_empty=0, $key_in_label=0, $value_as_key=0, $option='', $translate=0, $maxlen=0, $disabled=0)
    {
        global $langs;

        $out='<select id="'.$htmlname.'" '.($disabled?'disabled="true" ':'').'class="flat" name="'.$htmlname.'" '.($option != ''?$option:'').'>';

        if ($show_empty)
        {
            $out.='<option value="-1"'.($id==-1?' selected="true"':'').'>&nbsp;</option>'."\n";
        }

        if (is_array($array))
        {
            while (list($key, $value) = each ($array))
            {
                $out.='<option value="'.($value_as_key?$value:$key).'"';
                // Si il faut pre-selectionner une valeur
                if ($id != '' && ($id == $key || $id == $value))
                {
                    $out.=' selected="true"';
                }

                $out.='>';

                if ($key_in_label)
                {
                    $newval=($translate?$langs->trans($value):$value);
                    $selectOptionValue = $key.' - '.($maxlen?dol_trunc($newval,$maxlen):$newval);
                    $out.=$selectOptionValue;
                }
                else
                {
                    $newval=($translate?$langs->trans($value):$value);
                    $selectOptionValue = ($maxlen?dol_trunc($newval,$maxlen):$newval);
                    if ($value == '' || $value == '-') { $selectOptionValue='&nbsp;'; }
                    $out.=$selectOptionValue;
                }
                $out.="</option>\n";
            }
        }

        $out.="</select>";
        return $out;
    }

    /**
     *	Show a select form from an array
     * 	@deprecated				Use selectarray instead
     */
    function select_array($htmlname, $array, $id='', $show_empty=0, $key_in_label=0, $value_as_key=0, $option='', $translate=0, $maxlen=0)
    {
        print $this->selectarray($htmlname, $array, $id, $show_empty, $key_in_label, $value_as_key, $option, $translate, $maxlen);
    }


    /**
     *    	\brief      Return an html string with a select combo box to choose yes or no
     *    	\param      name            Name of html select field
     *    	\param      value           Pre-selected value
     *  	\param      option          0 return yes/no, 1 return 1/0
     * 		\return		int or string	See option
     */
    function selectyesno($htmlname,$value='',$option=0)
    {
        global $langs;

        $yes="yes"; $no="no";

        if ($option)
        {
            $yes="1";
            $no="0";
        }

        $resultyesno = '<select class="flat" name="'.$htmlname.'">'."\n";
        if (("$value" == 'yes') || ($value == 1))
        {
            $resultyesno .= '<option value="'.$yes.'" selected="true">'.$langs->trans("Yes").'</option>'."\n";
            $resultyesno .= '<option value="'.$no.'">'.$langs->trans("No").'</option>'."\n";
        }
        else
        {
            $resultyesno .= '<option value="'.$yes.'">'.$langs->trans("Yes").'</option>'."\n";
            $resultyesno .= '<option value="'.$no.'" selected="true">'.$langs->trans("No").'</option>'."\n";
        }
        $resultyesno .= '</select>'."\n";
        return $resultyesno;
    }



    /**
     *    \brief      Retourne la liste des modeles d'export
     *    \param      selected          Id modele pre-selectionne
     *    \param      htmlname          Nom de la zone select
     *    \param      type              Type des modeles recherches
     *    \param      useempty          Affiche valeur vide dans liste
     */
    function select_export_model($selected='',$htmlname='exportmodelid',$type='',$useempty=0)
    {

        $sql = "SELECT rowid, label";
        $sql.= " FROM ".MAIN_DB_PREFIX."export_model";
        $sql.= " WHERE type = '".$type."'";
        $sql.= " ORDER BY rowid";
        $result = $this->db->query($sql);
        if ($result)
        {
            print '<select class="flat" name="'.$htmlname.'">';
            if ($useempty)
            {
                print '<option value="-1">&nbsp;</option>';
            }

            $num = $this->db->num_rows($result);
            $i = 0;
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
                print $obj->label;
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
     *    	\brief      Return HTML combo list of week
     *    	\param      selected          Preselected value
     *    	\param      htmlname          Nom de la zone select
     *    	\param      useempty          Affiche valeur vide dans liste
     * 		TODO Move into html.formother
     */
    function select_dayofweek($selected='',$htmlname='weekid',$useempty=0)
    {
        global $langs;

        $week = array(	0=>$langs->trans("Day0"),
        1=>$langs->trans("Day1"),
        2=>$langs->trans("Day2"),
        3=>$langs->trans("Day3"),
        4=>$langs->trans("Day4"),
        5=>$langs->trans("Day5"),
        6=>$langs->trans("Day6"));

        $select_week = '<select class="flat" name="'.$htmlname.'">';
        if ($useempty)
        {
            $select_week .= '<option value="-1">&nbsp;</option>';
        }
        foreach ($week as $key => $val)
        {
            if ($selected == $key)
            {
                $select_week .= '<option value="'.$key.'" selected="true">';
            }
            else
            {
                $select_week .= '<option value="'.$key.'">';
            }
            $select_week .= $val;
        }
        $select_week .= '</select>';
        return $select_week;
    }

    /**
     *    	\brief      Return HTML combo list of month
     *    	\param      selected          Preselected value
     *    	\param      htmlname          Nom de la zone select
     *    	\param      useempty          Affiche valeur vide dans liste
     * 		TODO Move into html.formother
     */
    function select_month($selected='',$htmlname='monthid',$useempty=0)
    {
        $month = monthArrayOrSelected(-1);	// Get array

        $select_month = '<select class="flat" name="'.$htmlname.'">';
        if ($useempty)
        {
            $select_month .= '<option value="0">&nbsp;</option>';
        }
        foreach ($month as $key => $val)
        {
            if ($selected == $key)
            {
                $select_month .= '<option value="'.$key.'" selected="true">';
            }
            else
            {
                $select_month .= '<option value="'.$key.'">';
            }
            $select_month .= $val;
        }
        $select_month .= '</select>';
        return $select_month;
    }

    /**
     *    	\brief      Return HTML combo list of years
     *    	\param      selected          Preselected value
     *    	\param      htmlname          Name of HTML select object
     *    	\param      useempty          Affiche valeur vide dans liste
     *    	\param      $min_year         Valeur minimum de l'annee dans la liste (par defaut annee courante -10)
     *    	\param      $max_year         Valeur maximum de l'annee dans la liste (par defaut annee courante + 5)
     * 		TODO Move into html.formother
     */
    function select_year($selected='',$htmlname='yearid',$useempty=0, $min_year='', $max_year='')
    {
        if($max_year == '')
        $max_year = date("Y") +5;
        if($min_year == '')
        $min_year = date("Y") - 10;

        print '<select class="flat" name="' . $htmlname . '">';
        if($useempty)
        {
            if($selected == '')
            $selected_html = 'selected="true"';
            print '<option value="" ' . $selected_html . ' >&nbsp;</option>';
        }
        for ($y = $max_year; $y >= $min_year; $y--)
        {
            $selected_html='';
            if ($y == $selected)
            {
                $selected_html = 'selected="true"';
            }
            print "<option value=\"$y\" $selected_html >$y";
            print "</option>";
        }
        print "</select>\n";
    }

    /**
     *    Return a HTML area with the reference of object and a naviagation bar for a business object
     *    To add a particular filter on select, you must set $object->next_prev_filter to SQL criteria.
     *    @param      object		Object to show
     *    @param      paramid   	Name of parameter to use to name the id into the URL link
     *    @param      morehtml  	More html content to output just before the nav bar
     *    @param	  shownav	  	Show Condition (navigation is shown if value is 1)
     *    @param      fieldid   	Nom du champ en base a utiliser pour select next et previous
     *    @param      fieldref   	Nom du champ objet ref (object->ref) a utiliser pour select next et previous
     *    @param      morehtmlref  	Code html supplementaire a afficher apres ref
     *    @param      moreparam  	More param to add in nav link url.
     * 	  @return     string    	Portion HTML avec ref + boutons nav
     */
    function showrefnav($object,$paramid,$morehtml='',$shownav=1,$fieldid='rowid',$fieldref='ref',$morehtmlref='',$moreparam='')
    {
        $ret='';

        //print "$paramid,$morehtml,$shownav,$fieldid,$fieldref,$morehtmlref,$moreparam";
        $object->load_previous_next_ref($object->next_prev_filter,$fieldid);
        $previous_ref = $object->ref_previous?'<a href="'.$_SERVER["PHP_SELF"].'?'.$paramid.'='.urlencode($object->ref_previous).$moreparam.'">'.img_previous().'</a>':'';
        $next_ref     = $object->ref_next?'<a href="'.$_SERVER["PHP_SELF"].'?'.$paramid.'='.urlencode($object->ref_next).$moreparam.'">'.img_next().'</a>':'';

        //print "xx".$previous_ref."x".$next_ref;
        if ($previous_ref || $next_ref || $morehtml) {
            $ret.='<table class="nobordernopadding" width="100%"><tr class="nobordernopadding"><td class="nobordernopadding">';
        }

        $ret.=$object->$fieldref;
        if ($morehtmlref) {
            $ret.=' '.$morehtmlref;
        }

        if ($morehtml) {
            $ret.='</td><td class="nobordernopadding" align="right">'.$morehtml;
        }
        if ($shownav && ($previous_ref || $next_ref)) {
            $ret.='</td><td class="nobordernopadding" align="center" width="20">'.$previous_ref.'</td>';
            $ret.='<td class="nobordernopadding" align="center" width="20">'.$next_ref;
        }
        if ($previous_ref || $next_ref || $morehtml)
        {
            $ret.='</td></tr></table>';
        }
        return $ret;
    }


    /**
     *    	\brief      Return HTML code to output a photo
     *    	\param      modulepart		Id to define module concerned
     *     	\param      object			Object containing data to retrieve file name
     * 		\param		width			Width of photo
     * 	  	\return     string    		HTML code to output photo
     */
    function showphoto($modulepart,$object,$width=100)
    {
        global $conf;

        $ret='';$dir='';$file='';$email='';

        if ($modulepart=='userphoto')
        {
            $dir=$conf->user->dir_output;
            $file=get_exdir($object->id,2).$object->photo;
            $altfile=$object->id.".jpg";	// For backward compatibility
            $email=$object->email;
        }
        if ($modulepart=='memberphoto')
        {
            $dir=$conf->adherent->dir_output;
            $file=get_exdir($object->id,2).'photos/'.$object->photo;
            $altfile=$object->id.".jpg";	// For backward compatibility
            $email=$object->email;
        }

        if ($dir && $file)
        {
            if (file_exists($dir."/".$file))
            {
                $ret.='<img alt="Photo" width="'.$width.'" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$modulepart.'&file='.urlencode($file).'">';
            }
            else if (file_exists($dir."/".$altfile))
            {
                $ret.='<img alt="Photo" width="'.$width.'" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$modulepart.'&file='.urlencode($altfile).'">';
            }
            else
            {
                if ($conf->gravatar->enabled)
                {
                    global $dolibarr_main_url_root;
                    $ret.='<!-- Put link to gravatar -->';
                    $ret.='<img alt="Photo found on Gravatar" title="Photo Gravatar.com - email '.$email.'" width="'.$width.'" src="http://www.gravatar.com/avatar/'.md5($email).'?s='.$width.'&d='.urlencode($dolibarr_main_url_root.'/theme/common/nophoto.jpg').'">';
                }
                else
                {
                    $ret.='<img alt="No photo" width="'.$width.'" src="'.DOL_URL_ROOT.'/theme/common/nophoto.jpg">';
                }
            }
        }
        else
        {
            dol_print_error('','Call to showrefnav with wrong parameters');
        }

        return $ret;
    }
}

?>
