<?php
/* Copyright (C) 2010-2012 Regis Houssin  <regis.houssin@inodbox.com>
 * Copyright (C) 2012      Philippe Grand <philippe.grand@atoo-net.com>
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
 */

/**
 *	\file       htdocs/adherents/canvas/actions_adherentcard_common.class.php
 *	\ingroup    adherent
 *	\brief      Fichier de la classe Adherent card controller (common)
 */

/**
 *	Class to maage members using default canvas
 */
abstract class ActionsAdherentCardCommon
{
    /**
     * @var DoliDB Database handler.
     */
    public $db;

    var $dirmodule;
    var $targetmodule;
    var $canvas;
    var $card;

	//! Template container
	var $tpl = array();
	//! Object container
	var $object;

	/**
	 * @var string Error code (or message)
	 */
	public $error='';

	/**
	 * @var string[] Error codes (or messages)
	 */
	public $errors = array();


	/**
     *  Get object
	 *
     *  @param	int		$id		Object id
     *  @return	object			Object loaded
     */
    function getObject($id)
    {
    	//$ret = $this->getInstanceDao();

    	/*if (is_object($this->object) && method_exists($this->object,'fetch'))
    	{
    		if (! empty($id)) $this->object->fetch($id);
    	}
    	else
    	{*/
    		$object = new Adherent($this->db);
    		if (! empty($id)) $object->fetch($id);
            $this->object = $object;
    	//}
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
    /**
     *  Set content of ->tpl array, to use into template
     *
     *  @param	string		$action    Type of action
     *  @param	int			$id			Id
     *  @return	string					HTML output
     */
    function assign_values(&$action, $id)
    {
        // phpcs:enable
        global $conf, $langs, $user, $canvas;
        global $form, $formcompany, $objsoc;

        if ($action == 'add' || $action == 'update') $this->assign_post();

        foreach($this->object as $key => $value)
        {
            $this->tpl[$key] = $value;
        }

        $this->tpl['error']=$this->error;
        $this->tpl['errors']=$this->errors;

        if ($action == 'create' || $action == 'edit')
        {
        	if ($conf->use_javascript_ajax)
			{
				$this->tpl['ajax_selectcountry'] = "\n".'<script type="text/javascript" language="javascript">
				jQuery(document).ready(function () {
						jQuery("#selectcountry_id").change(function() {
							document.formsoc.action.value="'.$action.'";
							document.formsoc.canvas.value="'.$canvas.'";
							document.formsoc.submit();
						});
					})
				</script>'."\n";
			}

        	if (is_object($objsoc) && $objsoc->id > 0)
        	{
        		$this->tpl['company'] = $objsoc->getNomUrl(1);
        		$this->tpl['company_id'] = $objsoc->id;
        	}
        	else
        	{
        		$this->tpl['company'] = $form->select_company($this->object->socid,'socid','',1);
        	}

        	// Civility
        	$this->tpl['select_civility'] = $formcompany->select_civility($this->object->civility_id);

        	// Predefined with third party
        	if ((isset($objsoc->typent_code) && $objsoc->typent_code == 'TE_PRIVATE'))
        	{
        		if (dol_strlen(trim($this->object->address)) == 0) $this->tpl['address'] = $objsoc->address;
        		if (dol_strlen(trim($this->object->zip)) == 0) $this->object->zip = $objsoc->zip;
        		if (dol_strlen(trim($this->object->town)) == 0) $this->object->town = $objsoc->town;
        		if (dol_strlen(trim($this->object->phone_perso)) == 0) $this->object->phone_perso = $objsoc->phone;
        		if (dol_strlen(trim($this->object->phone_mobile)) == 0) $this->object->phone_mobile = $objsoc->phone_mobile;
        		if (dol_strlen(trim($this->object->email)) == 0) $this->object->email = $objsoc->email;
        	}

            // Zip
            $this->tpl['select_zip'] = $formcompany->select_ziptown($this->object->zip,'zipcode',array('town','selectcountry_id','state_id'),6);

            // Town
            $this->tpl['select_town'] = $formcompany->select_ziptown($this->object->town,'town',array('zipcode','selectcountry_id','state_id'));

            if (dol_strlen(trim($this->object->country_id)) == 0) $this->object->country_id = $objsoc->country_id;

            // Country
            $this->tpl['select_country'] = $form->select_country($this->object->country_id,'country_id');
            $countrynotdefined = $langs->trans("ErrorSetACountryFirst").' ('.$langs->trans("SeeAbove").')';

            if ($user->admin) $this->tpl['info_admin'] = info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);

            // State
            if ($this->object->country_id) $this->tpl['select_state'] = $formcompany->select_state($this->object->state_id,$this->object->country_code);
            else $this->tpl['select_state'] = $countrynotdefined;

            // Physical or Moral
            $selectarray=array('0'=>$langs->trans("Physical"),'1'=>$langs->trans("Moral"));
            $this->tpl['select_morphy'] = $form->selectarray('morphy',$selectarray,$this->object->morphy,0);
        }

        if ($action == 'view' || $action == 'edit' || $action == 'delete')
        {
        	// Emailing
        	if (! empty($conf->mailing->enabled))
			{
				$langs->load("mails");
				$this->tpl['nb_emailing'] = $this->object->getNbOfEMailings();
			}


        	// Dolibarr user
        	if ($this->object->user_id)
			{
				$dolibarr_user=new User($this->db);
				$result=$dolibarr_user->fetch($this->object->user_id);
				$this->tpl['dolibarr_user'] = $dolibarr_user->getLoginUrl(1);
			}
			else $this->tpl['dolibarr_user'] = $langs->trans("NoDolibarrAccess");
        }

        if ($action == 'view' || $action == 'delete')
        {
        	$this->tpl['showrefnav'] = $form->showrefnav($this->object,'id');

        	if ($this->object->socid > 0)
        	{
        		$objsoc = new Societe($this->db);

        		$objsoc->fetch($this->object->socid);
        		$this->tpl['company'] = $objsoc->getNomUrl(1);
        	}
        	else
        	{
        		$this->tpl['company'] = $langs->trans("AdherentNotLinkedToThirdParty");
        	}

        	$this->tpl['civility'] = $this->object->getCivilityLabel();

            $this->tpl['address'] = dol_nl2br($this->object->address);

            $this->tpl['zip'] = ($this->object->zip?$this->object->zip.'&nbsp;':'');

            $img=picto_from_langcode($this->object->country_code);
            $this->tpl['country'] = ($img?$img.' ':'').$this->object->country;

            $this->tpl['phone_perso'] 	= dol_print_phone($this->object->phone_perso,$this->object->country_code,0,$this->object->id,'AC_TEL');
            $this->tpl['phone_mobile'] 	= dol_print_phone($this->object->phone_mobile,$this->object->country_code,0,$this->object->id,'AC_TEL');
            $this->tpl['email'] 		= dol_print_email($this->object->email,0,$this->object->id,'AC_EMAIL');

            $this->tpl['visibility'] = $this->object->getmorphylib($this->object->morphy);

            $this->tpl['note'] = nl2br($this->object->note);
        }

        if ($action == 'create_user')
        {
        	// Full firstname and lastname separated with a dot : firstname.lastname
        	include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
            require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
        	$login=dol_buildlogin($this->object->lastname, $this->object->firstname);

       		$generated_password=getRandomPassword(false);
        	$password=$generated_password;

        	// Create a form array
        	$formquestion=array(
        	array('label' => $langs->trans("LoginToCreate"), 'type' => 'text', 'name' => 'login', 'value' => $login),
        	array('label' => $langs->trans("Password"), 'type' => 'text', 'name' => 'password', 'value' => $password));

        	$this->tpl['action_create_user'] = $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$this->object->id,$langs->trans("CreateDolibarrLogin"),$langs->trans("ConfirmCreateAdherent"),"confirm_create_user",$formquestion,'no');
        }
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *  Assign POST values into object
     *
     *  @return		string					HTML output
     */
    private function assign_post()
    {
        // phpcs:enable
        global $langs, $mysoc;

        $this->object->old_name 			= 	$_POST["old_name"];
        $this->object->old_firstname 		= 	$_POST["old_firstname"];

        $this->object->fk_soc				=	$_POST["fk_soc"];
        $this->object->lastname				=	$_POST["lastname"];
        $this->object->firstname			= 	$_POST["firstname"];
        $this->object->civility_id			= 	$_POST["civility_id"];
        $this->object->address				=	$_POST["address"];
        $this->object->zip					=	$_POST["zipcode"];
        $this->object->town					=	$_POST["town"];
        $this->object->country_id			=	$_POST["country_id"]?$_POST["country_id"]:$mysoc->country_id;
        $this->object->state_id        		=	$_POST["state_id"];
        $this->object->phone_perso			= 	$_POST["phone_perso"];
        $this->object->phone_mobile			= 	$_POST["phone_mobile"];
        $this->object->email				=	$_POST["email"];
        $this->object->note					=	$_POST["note"];
        $this->object->canvas				=	$_POST["canvas"];

        // We set country_id, and country_code label of the chosen country
        if ($this->object->country_id)
        {
            $sql = "SELECT code, label FROM ".MAIN_DB_PREFIX."c_country WHERE rowid = ".$this->object->country_id;
            $resql=$this->db->query($sql);
            if ($resql)
            {
                $obj = $this->db->fetch_object($resql);

                $this->object->country_code	=	$obj->code;
                $this->object->country		=	$langs->trans("Country".$obj->code)?$langs->trans("Country".$obj->code):$obj->libelle;
            }
            else
            {
                dol_print_error($this->db);
            }
        }
    }
}
