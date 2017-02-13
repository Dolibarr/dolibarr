<?php
/* Copyright (C) 2006-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *       \file       htdocs/webservices/server_user.php
 *       \brief      File that is entry point to call Dolibarr WebServices
 */

if (! defined("NOCSRFCHECK"))    define("NOCSRFCHECK",'1');

require_once '../master.inc.php';
require_once NUSOAP_PATH.'/nusoap.php';		// Include SOAP
require_once DOL_DOCUMENT_ROOT.'/core/lib/ws.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';


dol_syslog("Call User webservices interfaces");

$langs->load("main");

// Enable and test if module web services is enabled
if (empty($conf->global->MAIN_MODULE_WEBSERVICES))
{
    $langs->load("admin");
    dol_syslog("Call Dolibarr webservices interfaces with module webservices disabled");
    print $langs->trans("WarningModuleNotActive",'WebServices').'.<br><br>';
    print $langs->trans("ToActivateModule");
    exit;
}

// Create the soap Object
$server = new nusoap_server();
$server->soap_defencoding='UTF-8';
$server->decode_utf8=false;
$ns='http://www.dolibarr.org/ns/';
$server->configureWSDL('WebServicesDolibarrUser',$ns);
$server->wsdl->schemaTargetNamespace=$ns;


// Define WSDL Authentication object
$server->wsdl->addComplexType(
    'authentication',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'dolibarrkey' => array('name'=>'dolibarrkey','type'=>'xsd:string'),
    	'sourceapplication' => array('name'=>'sourceapplication','type'=>'xsd:string'),
    	'login' => array('name'=>'login','type'=>'xsd:string'),
    	'password' => array('name'=>'password','type'=>'xsd:string'),
        'entity' => array('name'=>'entity','type'=>'xsd:string'),
    )
);

// Define WSDL Return object
$server->wsdl->addComplexType(
    'result',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'result_code' => array('name'=>'result_code','type'=>'xsd:string'),
        'result_label' => array('name'=>'result_label','type'=>'xsd:string'),
    )
);

// Define other specific objects
$server->wsdl->addComplexType(
    'user',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'element' => array('name'=>'element','type'=>'xsd:string'),
        'id' => array('name'=>'id','type'=>'xsd:string'),
        'lastname' => array('name'=>'lastname','type'=>'xsd:string'),
        'firstname' => array('name'=>'firstname','type'=>'xsd:string'),
        'note' => array('name'=>'note','type'=>'xsd:string'),
        'email' => array('name'=>'email','type'=>'xsd:string'),
        'signature' => array('name'=>'signature','type'=>'xsd:string'),
        'office_phone' => array('name'=>'office_phone','type'=>'xsd:string'),
        'office_fax' => array('name'=>'office_fax','type'=>'xsd:string'),
        'user_mobile' => array('name'=>'user_mobile','type'=>'xsd:string'),
        'admin' => array('name'=>'admin','type'=>'xsd:string'),
        'login' => array('name'=>'login','type'=>'xsd:string'),
        'entity' => array('name'=>'entity','type'=>'xsd:string'),
        'pass_indatabase' => array('name'=>'pass_indatabase','type'=>'xsd:string'),
        'pass_indatabase_crypted' => array('name'=>'pass_indatabase_crypted','type'=>'xsd:string'),
        'datec' => array('name'=>'datec','type'=>'xsd:dateTime'),
        'datem' => array('name'=>'datem','type'=>'xsd:dateTime'),
        'fk_thirdparty' => array('name'=>'fk_thirdparty','type'=>'xsd:string'),
        'fk_contact' => array('name'=>'fk_contact','type'=>'xsd:string'),
        'fk_member' => array('name'=>'fk_member','type'=>'xsd:string'),
        'datelastlogin' => array('name'=>'datelastlogin','type'=>'xsd:dateTime'),
        'datepreviouslogin' => array('name'=>'datepreviouslogin','type'=>'xsd:dateTime'),
        'statut' => array('name'=>'statut','type'=>'xsd:string'),
        'photo' => array('name'=>'photo','type'=>'xsd:string'),
        'lang' => array('name'=>'lang','type'=>'xsd:string'),
        'entrepots' => array('name'=>'entrepots','type'=>'xsd:string'),
        //'rights' => array('name'=>'rights','type'=>'xsd:string'),
        'canvas' => array('name'=>'canvas','type'=>'xsd:string')
    )
);

// Define other specific objects
$server->wsdl->addComplexType(
	'group',
	'complexType',
	'struct',
	'all',
	'',
	array(
	'name' => array('name'=>'name','type'=>'xsd:string'),
	'id' => array('name'=>'id','type'=>'xsd:string'),
	'datec' => array('name'=>'datec','type'=>'xsd:string'),
	'nb' => array('name'=>'nb','type'=>'xsd:string')
	)
);

$server->wsdl->addComplexType(
	'GroupsArray',
	'complexType',
	'array',
	'',
	'SOAP-ENC:Array',
	array(),
	array(
	array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:group[]')
	),
	'tns:group'
);

$thirdpartywithuser_fields = array(
	// For thirdparty and contact
	'name' => array('name'=>'name','type'=>'xsd:string'),
	'firstname' => array('name'=>'firstname','type'=>'xsd:string'),
	'name_thirdparty' => array('name'=>'name_thirdparty','type'=>'xsd:string'),
	'ref_ext' => array('name'=>'ref_ext','type'=>'xsd:string'),
	'client' => array('name'=>'client','type'=>'xsd:string'),
	'fournisseur' => array('name'=>'fournisseur','type'=>'xsd:string'),
	'address' => array('name'=>'address','type'=>'xsd:string'),
	'zip' => array('name'=>'zip','type'=>'xsd:string'),
	'town' => array('name'=>'town','type'=>'xsd:string'),
	'country_id' => array('name'=>'country_id','type'=>'xsd:string'),
	'country_code' => array('name'=>'country_code','type'=>'xsd:string'),
	'phone' => array('name'=>'phone','type'=>'xsd:string'),
	'phone_mobile' => array('name'=>'phone_mobile','type'=>'xsd:string'),
	'fax' => array('name'=>'fax','type'=>'xsd:string'),
	'email' => array('name'=>'email','type'=>'xsd:string'),
	'url' => array('name'=>'url','type'=>'xsd:string'),
	'profid1' => array('name'=>'profid1','type'=>'xsd:string'),
	'profid2' => array('name'=>'profid2','type'=>'xsd:string'),
	'profid3' => array('name'=>'profid3','type'=>'xsd:string'),
	'profid4' => array('name'=>'profid4','type'=>'xsd:string'),
	'profid5' => array('name'=>'profid5','type'=>'xsd:string'),
	'profid6' => array('name'=>'profid6','type'=>'xsd:string'),
	'capital' => array('name'=>'capital','type'=>'xsd:string'),
	'tva_assuj' => array('name'=>'tva_assuj','type'=>'xsd:string'),
	'tva_intra' => array('name'=>'tva_intra','type'=>'xsd:string'),
	// 	For user
	'login' => array('name'=>'login','type'=>'xsd:string'),
	'password' => array('name'=>'password','type'=>'xsd:string'),
	'group_id' => array('name'=>'group_id','type'=>'xsd:string')
);

//Retreive all extrafield for contact
// fetch optionals attributes and labels
$extrafields=new ExtraFields($db);
$extralabels=$extrafields->fetch_name_optionals_label('socpeople',true);
if (count($extrafields)>0) {
	$extrafield_array = array();
}
foreach($extrafields->attribute_label as $key=>$label)
{
	$type =$extrafields->attribute_type[$key];
	if ($type=='date' || $type=='datetime') {$type='xsd:dateTime';}
	else {$type='xsd:string';}

	$extrafield_array['contact_options_'.$key]=array('name'=>'contact_options_'.$key,'type'=>$type);
}

$thirdpartywithuser_fields=array_merge($thirdpartywithuser_fields,$extrafield_array);


$server->wsdl->addComplexType(
	'thirdpartywithuser',
	'complexType',
	'struct',
	'all',
	'',
	$thirdpartywithuser_fields
);

// Define WSDL user short object
$server->wsdl->addComplexType(
	'shortuser',
	'complexType',
	'struct',
	'all',
	'',
	array(
	'login' => array('name'=>'login','type'=>'xsd:string'),
	'password' => array('name'=>'password','type'=>'xsd:string'),
	'entity' => array('name'=>'entity','type'=>'xsd:string'),
	)
);



// 5 styles: RPC/encoded, RPC/literal, Document/encoded (not WS-I compliant), Document/literal, Document/literal wrapped
// Style merely dictates how to translate a WSDL binding to a SOAP message. Nothing more. You can use either style with any programming model.
// http://www.ibm.com/developerworks/webservices/library/ws-whichwsdl/
$styledoc='rpc';       // rpc/document (document is an extend into SOAP 1.0 to support unstructured messages)
$styleuse='encoded';   // encoded/literal/literal wrapped
// Better choice is document/literal wrapped but literal wrapped not supported by nusoap.


// Register WSDL
$server->register(
    'getUser',
    // Entry values
    array('authentication'=>'tns:authentication','id'=>'xsd:string','ref'=>'xsd:string','ref_ext'=>'xsd:string'),
    // Exit values
    array('result'=>'tns:result','user'=>'tns:user'),
    $ns,
    $ns.'#getUser',
    $styledoc,
    $styleuse,
    'WS to get user'
);

$server->register(
	'getListOfGroups',
	// Entry values
	array('authentication'=>'tns:authentication'),
	// Exit values
	array('result'=>'tns:result','groups'=>'tns:GroupsArray'),
	$ns,
	$ns.'#getListOfGroups',
	$styledoc,
	$styleuse,
	'WS to get list of groups'
);

$server->register(
	'createUserFromThirdparty',
	// Entry values
	array('authentication'=>'tns:authentication','thirdpartywithuser'=>'tns:thirdpartywithuser'),
	// Exit values
	array('result'=>'tns:result','id'=>'xsd:string'),
	$ns,
	$ns.'#createUserFromThirdparty',
	$styledoc,
	$styleuse,
	'WS to create an external user with thirdparty and contact'
);

$server->register(
	'setUserPassword',
	// Entry values
	array('authentication'=>'tns:authentication','shortuser'=>'tns:shortuser'),
	// Exit values
	array('result'=>'tns:result','id'=>'xsd:string'),
	$ns,
	$ns.'#setUserPassword',
	$styledoc,
	$styleuse,
	'WS to change password of an user'
);




/**
 * Get produt or service
 *
 * @param	array		$authentication		Array of authentication information
 * @param	int			$id					Id of object
 * @param	string		$ref				Ref of object
 * @param	string		$ref_ext			Ref external of object
 * @return	mixed
 */
function getUser($authentication,$id,$ref='',$ref_ext='')
{
    global $db,$conf,$langs;

    dol_syslog("Function: getUser login=".$authentication['login']." id=".$id." ref=".$ref." ref_ext=".$ref_ext);

    if ($authentication['entity']) $conf->entity=$authentication['entity'];

    // Init and check authentication
    $objectresp=array();
    $errorcode='';$errorlabel='';
    $error=0;
    $fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);
    // Check parameters
    if (! $error && (($id && $ref) || ($id && $ref_ext) || ($ref && $ref_ext)))
    {
        $error++;
        $errorcode='BAD_PARAMETERS'; $errorlabel="Parameter id, ref and ref_ext can't be both provided. You must choose one or other but not both.";
    }

    if (! $error)
    {
        $fuser->getrights();

        if ($fuser->rights->user->user->lire
        	|| ($fuser->rights->user->self->creer && $id && $id==$fuser->id)
        	|| ($fuser->rights->user->self->creer && $ref && $ref==$fuser->login)
        	|| ($fuser->rights->user->self->creer && $ref_ext && $ref_ext==$fuser->ref_ext))
        {
            $user=new User($db);
            $result=$user->fetch($id,$ref,$ref_ext);
            if ($result > 0)
            {
                // Create
                $objectresp = array(
			    	'result'=>array('result_code'=>'OK', 'result_label'=>''),
			        'user'=>array(
						'id' => $user->id,
						'lastname' => $user->lastname,
						'firstname' => $user->firstname,
						'note' => $user->note,
						'email' => $user->email,
						'signature' => $user->signature,
						'office_phone' => $user->office_phone,
						'office_fax' => $user->office_fax,
						'user_mobile' => $user->user_mobile,
						'admin' => $user->admin,
						'login' => $user->login,
						'entity' => $user->entity,
						'pass_indatabase' => $user->pass_indatabase,
						'pass_indatabase_crypted' => $user->pass_indatabase_crypted,
						'datec' => dol_print_date($user->datec,'dayhourrfc'),
						'datem' => dol_print_date($user->datem,'dayhourrfc'),
						'fk_thirdparty' => $user->societe_id,
						'fk_contact' => $user->contact_id,
						'fk_member' => $user->fk_member,
						'datelastlogin' => dol_print_date($user->datelastlogin,'dayhourrfc'),
						'datepreviouslogin' => dol_print_date($user->datepreviouslogin,'dayhourrfc'),
						'statut' => $user->statut,
						'photo' => $user->photo,
						'lang' => $user->lang,
						//'rights' => $user->rights,
						'canvas' => $user->canvas
                    )
                );
            }
            else
            {
                $error++;
                $errorcode='NOT_FOUND'; $errorlabel='Object not found for id='.$id.' nor ref='.$ref.' nor ref_ext='.$ref_ext;
            }
        }
        else
        {
            $error++;
            $errorcode='PERMISSION_DENIED'; $errorlabel='User does not have permission for this request';
        }
    }

    if ($error)
    {
        $objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
    }

    return $objectresp;
}

/**
 * getListOfGroups
 *
 * @param	array		$authentication		Array of authentication information
 * @return	array							Array result
 */
function getListOfGroups($authentication)
{
	global $db,$conf,$langs;

	$now=dol_now();

	dol_syslog("Function: getListOfGroups login=".$authentication['login']);

	if ($authentication['entity']) $conf->entity=$authentication['entity'];

	// Init and check authentication
	$objectresp=array();
	$arraygroups=array();
	$errorcode='';$errorlabel='';
	$error=0;
	$fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);
	// Check parameters

	if (! $error)
	{
		$sql = "SELECT g.rowid, g.nom as name, g.entity, g.datec, COUNT(DISTINCT ugu.fk_user) as nb";
		$sql.= " FROM ".MAIN_DB_PREFIX."usergroup as g";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."usergroup_user as ugu ON ugu.fk_usergroup = g.rowid";
		if (! empty($conf->multicompany->enabled) && $conf->entity == 1 && ($conf->multicompany->transverse_mode || ($user->admin && ! $user->entity)))
		{
			$sql.= " WHERE g.entity IS NOT NULL";
		}
		else
		{
			$sql.= " WHERE g.entity IN (0,".$conf->entity.")";
		}
		$sql.= " GROUP BY g.rowid, g.nom, g.entity, g.datec";
		$resql=$db->query($sql);
		if ($resql)
		{
			$num=$db->num_rows($resql);

			$i=0;
			while ($i < $num)
			{
				$obj=$db->fetch_object($resql);
				$arraygroups[]=array('id'=>$obj->rowid,'name'=>$obj->name,'datec'=>$obj->datec,'nb'=>$obj->nb);
				$i++;
			}
		}
		else
		{
			$error++;
			$errorcode=$db->lasterrno();
			$errorlabel=$db->lasterror();
		}
	}

	if ($error)
	{
		$objectresp = array(
		'result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel),
		'groups'=>$arraygroups
		);
	}
	else
	{
		$objectresp = array(
		'result'=>array('result_code' => 'OK', 'result_label' => ''),
		'groups'=>$arraygroups
		);
	}

	return $objectresp;
}


/**
 * Create an external user with thirdparty and contact
 *
 * @param	array		$authentication		Array of authentication information
 * @param	array		$thirdpartywithuser Datas
 * @return	mixed
 */
function createUserFromThirdparty($authentication,$thirdpartywithuser)
{
	global $db,$conf,$langs;

	dol_syslog("Function: createUserFromThirdparty login=".$authentication['login']." id=".$id." ref=".$ref." ref_ext=".$ref_ext);

	if ($authentication['entity']) $conf->entity=$authentication['entity'];

	$objectresp=array();
	$errorcode='';$errorlabel='';
	$error=0;

	$fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);

	if ($fuser->societe_id) $socid=$fuser->societe_id;

	if (! $error && ! $thirdpartywithuser)
	{
		$error++;
		$errorcode='BAD_PARAMETERS'; $errorlabel="Parameter thirdparty must be provided.";
	}

	if (! $error)
	{
		$fuser->getrights();

		if ($fuser->rights->societe->creer)
		{
			$thirdparty=new Societe($db);

			// If a contact / company already exists with the email, return the corresponding socid
			$sql = "SELECT s.rowid as societe_id FROM ".MAIN_DB_PREFIX."societe as s";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople as sp ON sp.fk_soc = s.rowid";
			$sql.= " WHERE s.entity=".$conf->entity;
			$sql.= " AND s.email='".$db->escape($thirdpartywithuser['email'])."'";
			$sql.= " OR sp.email='".$db->escape($thirdpartywithuser['email'])."'";
			$sql.= $db->plimit(1);

			$resql = $db->query($sql);
			if ($resql)
			{
				// If a company or contact is found with the same email we return an error
				$row = $db->fetch_object($resql);
				if ($row)
				{
					$error++;
					$errorcode='ALREADY_EXIST'; $errorlabel='Object not create : company or contact exists '.$thirdpartywithuser['email'];
				}
				else
				{
					$db->begin();
					/*
					 * Company creation
					 */
					$thirdparty->name=$thirdpartywithuser['name_thirdparty'];
					$thirdparty->ref_ext=$thirdpartywithuser['ref_ext'];
					$thirdparty->address=$thirdpartywithuser['address'];
					$thirdparty->zip=$thirdpartywithuser['zip'];
					$thirdparty->town=$thirdpartywithuser['town'];
					$thirdparty->country_id=$thirdpartywithuser['country_id'];
					$thirdparty->country_code=$thirdpartywithuser['country_code'];

					// find the country id by code
					$langs->load("dict");

					$sql = "SELECT rowid";
					$sql.= " FROM ".MAIN_DB_PREFIX."c_country";
					$sql.= " WHERE active = 1";
					$sql.= " AND code='".$thirdparty->country_code."'";

					$resql=$db->query($sql);
					if ($resql)
					{
						$num = $db->num_rows($resql);
						if ($num)
						{
							$obj = $db->fetch_object($resql);
							$thirdparty->country_id      = $obj->rowid;
						}
					}
					$thirdparty->phone=$thirdpartywithuser['phone'];
					$thirdparty->fax=$thirdpartywithuser['fax'];
					$thirdparty->email=$thirdpartywithuser['email'];
					$thirdparty->url=$thirdpartywithuser['url'];
					$thirdparty->ape=$thirdpartywithuser['ape'];
					$thirdparty->idprof1=$thirdpartywithuser['prof1'];
					$thirdparty->idprof2=$thirdpartywithuser['prof2'];
					$thirdparty->idprof3=$thirdpartywithuser['prof3'];
					$thirdparty->idprof4=$thirdpartywithuser['prof4'];
					$thirdparty->idprof5=$thirdpartywithuser['prof5'];
					$thirdparty->idprof6=$thirdpartywithuser['prof6'];

					$thirdparty->client=$thirdpartywithuser['client'];
					$thirdparty->fournisseur=$thirdpartywithuser['fournisseur'];

					$socid_return=$thirdparty->create($fuser);

					if ($socid_return > 0)
					{
						$thirdparty->fetch($socid_return);

						/*
						 * Contact creation
						*
						*/
						$contact = new Contact($db);
						$contact->socid = $thirdparty->id;
						$contact->lastname = $thirdpartywithuser['name'];
						$contact->firstname = $thirdpartywithuser['firstname'];
						$contact->civility_id = $thirdparty->civility_id;
						$contact->address = $thirdparty->address;
						$contact->zip = $thirdparty->zip;
						$contact->town = $thirdparty->town;
						$contact->email = $thirdparty->email;
						$contact->phone_pro = $thirdparty->phone;
						$contact->phone_mobile = $thirdpartywithuser['phone_mobile'];
						$contact->fax = $thirdparty->fax;
						$contact->statut = 1;
						$contact->country_id = $thirdparty->country_id;
						$contact->country_code = $thirdparty->country_code;

						//Retreive all extrafield for thirdsparty
						// fetch optionals attributes and labels
						$extrafields=new ExtraFields($db);
						$extralabels=$extrafields->fetch_name_optionals_label('socpeople',true);
						foreach($extrafields->attribute_label as $key=>$label)
						{
							$key='contact_options_'.$key;
							$key=substr($key,8);   // Remove 'contact_' prefix
							$contact->array_options[$key]=$thirdpartywithuser[$key];
						}

						$contact_id =  $contact->create($fuser);

						if ($contact_id > 0)
						{
							/*
							 * User creation
							*
							*/
							$edituser = new User($db);

							$id = $edituser->create_from_contact($contact,$thirdpartywithuser["login"]);
							if ($id > 0)
							{
								$edituser->setPassword($fuser,trim($thirdpartywithuser['password']));

								if($thirdpartywithuser['group_id'] > 0 )
									$edituser->SetInGroup($thirdpartywithuser['group_id'],$conf->entity);
							}
							else
							{
								$error++;
								$errorcode='NOT_CREATE'; $errorlabel='Object not create : '.$edituser->error;
							}
						}
						else
						{
							$error++;
							$errorcode='NOT_CREATE'; $errorlabel='Object not create : '.$contact->error;
						}

						if(!$error) {
							$db->commit();
							$objectresp=array('result'=>array('result_code'=>'OK', 'result_label'=>'SUCCESS'),'id'=>$socid_return);
							$error=0;
						}
					}
					else
					{
						$error++;
						$errors=($thirdparty->error?array($thirdparty->error):$thirdparty->errors);
					}
				}
			}
			else
			{
				// retour creation KO
				$error++;
				$errorcode='NOT_CREATE'; $errorlabel='Object not create';
			}
		}
		else
		{
			$error++;
			$errorcode='PERMISSION_DENIED'; $errorlabel='User does not have permission for this request';
		}
	}

	if ($error)
	{
		$db->rollback();
		$objectresp = array(
		'result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel)
		);
	}

	return $objectresp;
}


/**
 * Set password of an user
 *
 * @param	array		$authentication		Array of authentication information
 * @param	array		$shortuser			Array of login/password info
 * @return	mixed
 */
function setUserPassword($authentication,$shortuser) {

	global $db,$conf,$langs;

	dol_syslog("Function: setUserPassword login=".$authentication['login']." id=".$id." ref=".$ref." ref_ext=".$ref_ext);

	if ($authentication['entity']) $conf->entity=$authentication['entity'];

	$objectresp=array();
	$errorcode='';$errorlabel='';
	$error=0;

	$fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);

	if ($fuser->societe_id) $socid=$fuser->societe_id;

	if (! $error && ! $shortuser)
	{
		$error++;
		$errorcode='BAD_PARAMETERS'; $errorlabel="Parameter shortuser must be provided.";
	}

	if (! $error)
	{
		$fuser->getrights();

		if ($fuser->rights->user->user->password || $fuser->rights->user->self->password)
		{
			$userstat=new User($db);
			$res = $userstat->fetch('',$shortuser['login']);
			if($res)
			{
				$res = $userstat->setPassword($userstat,$shortuser['password']);
				if($res)
				{
					$objectresp = array(
						'result'=>array('result_code' => 'OK', 'result_label' => ''),
						'groups'=>$arraygroups
					);
				}
				else
				{
					$error++;
					$errorcode='NOT_MODIFIED'; $errorlabel='Error when changing password';
				}
			}
			else
			{
				$error++;
				$errorcode='NOT_FOUND'; $errorlabel='User not found';
			}

		}
		else
		{
			$error++;
			$errorcode='PERMISSION_DENIED'; $errorlabel='User does not have permission for this request';
		}
	}


	if ($error)
	{
		$objectresp = array(
			'result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel)
		);
	}

	return $objectresp;
}

// Return the results.
$server->service(file_get_contents("php://input"));
