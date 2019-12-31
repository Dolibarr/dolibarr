<?php
/* Copyright (C) 2006-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2012      JF FERRY             <jfefe@aternatik.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/webservices/server_contact.php
 *       \brief      File that is entry point to call Dolibarr WebServices
 */

if (!defined("NOCSRFCHECK"))    define("NOCSRFCHECK", '1');

require "../master.inc.php";
require_once NUSOAP_PATH.'/nusoap.php'; // Include SOAP
require_once DOL_DOCUMENT_ROOT."/core/lib/ws.lib.php";
require_once DOL_DOCUMENT_ROOT."/contact/class/contact.class.php";
require_once DOL_DOCUMENT_ROOT."/core/class/extrafields.class.php";


dol_syslog("Call Contact webservices interfaces");

// Enable and test if module web services is enabled
if (empty($conf->global->MAIN_MODULE_WEBSERVICES))
{
    $langs->load("admin");
    dol_syslog("Call Dolibarr webservices interfaces with module webservices disabled");
    print $langs->trans("WarningModuleNotActive", 'WebServices').'.<br><br>';
    print $langs->trans("ToActivateModule");
    exit;
}

// Create the soap Object
$server = new nusoap_server();
$server->soap_defencoding = 'UTF-8';
$server->decode_utf8 = false;
$ns = 'http://www.dolibarr.org/ns/';
$server->configureWSDL('WebServicesDolibarrContact', $ns);
$server->wsdl->schemaTargetNamespace = $ns;


// Define WSDL Authentication object
$server->wsdl->addComplexType(
    'authentication',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'dolibarrkey' => array('name'=>'dolibarrkey', 'type'=>'xsd:string'),
    	'sourceapplication' => array('name'=>'sourceapplication', 'type'=>'xsd:string'),
    	'login' => array('name'=>'login', 'type'=>'xsd:string'),
    	'password' => array('name'=>'password', 'type'=>'xsd:string'),
        'entity' => array('name'=>'entity', 'type'=>'xsd:string'),
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
        'result_code' => array('name'=>'result_code', 'type'=>'xsd:string'),
        'result_label' => array('name'=>'result_label', 'type'=>'xsd:string'),
    )
);

$contact_fields = array(
	'id' => array('name'=>'id', 'type'=>'xsd:string'),
	'ref_ext' => array('name'=>'ref_ext', 'type'=>'xsd:string'),
	'lastname' => array('name'=>'lastname', 'type'=>'xsd:string'),
	'firstname' => array('name'=>'firstname', 'type'=>'xsd:string'),
	'address' => array('name'=>'address', 'type'=>'xsd:string'),
	'zip' => array('name'=>'zip', 'type'=>'xsd:string'),
	'town' => array('name'=>'town', 'type'=>'xsd:string'),
	'state_id' => array('name'=>'state_id', 'type'=>'xsd:string'),
	'state_code' => array('name'=>'state_code', 'type'=>'xsd:string'),
	'state' => array('name'=>'state', 'type'=>'xsd:string'),
	'country_id' => array('name'=>'country_id', 'type'=>'xsd:string'),
	'country_code' => array('name'=>'country_code', 'type'=>'xsd:string'),
	'country' => array('name'=>'country', 'type'=>'xsd:string'),
	'socid' => array('name'=>'socid', 'type'=>'xsd:string'),
	'status' => array('name'=>'status', 'type'=>'xsd:string'),
	'phone_pro' => array('name'=>'phone_pro', 'type'=>'xsd:string'),
	'fax' => array('name'=>'fax', 'type'=>'xsd:string'),
	'phone_perso' => array('name'=>'phone_perso', 'type'=>'xsd:string'),
	'phone_mobile' => array('name'=>'phone_mobile', 'type'=>'xsd:string'),
	'code' => array('name'=>'code', 'type'=>'xsd:string'),
	'email' => array('name'=>'email', 'type'=>'xsd:string'),
	'birthday' => array('name'=>'birthday', 'type'=>'xsd:string'),
	'default_lang' => array('name'=>'default_lang', 'type'=>'xsd:string'),
	'note' => array('name'=>'note', 'type'=>'xsd:string'),
	'ref_facturation' => array('name'=>'ref_facturation', 'type'=>'xsd:string'),
	'ref_contrat' => array('name'=>'ref_contrat', 'type'=>'xsd:string'),
	'ref_commande' => array('name'=>'ref_commande', 'type'=>'xsd:string'),
	'ref_propal' => array('name'=>'ref_propal', 'type'=>'xsd:string'),
	'user_id' => array('name'=>'user_id', 'type'=>'xsd:string'),
	'user_login' => array('name'=>'user_login', 'type'=>'xsd:string'),
	'civility_id' => array('name'=>'civility_id', 'type'=>'xsd:string'),
	'poste' => array('name'=>'poste', 'type'=>'xsd:string')
	//...
);

$elementtype = 'socpeople';


//Retreive all extrafield for contact
// fetch optionals attributes and labels
$extrafields = new ExtraFields($db);
$extrafields->fetch_name_optionals_label($elementtype, true);
$extrafield_array = null;
if (is_array($extrafields) && count($extrafields) > 0) {
	$extrafield_array = array();
}
if (is_array($extrafields->attributes[$elementtype]['label']) && count($extrafields->attributes[$elementtype]['label']))
{
	foreach ($extrafields->attributes[$elementtype]['label'] as $key=>$label)
	{
		$type = $extrafields->attributes[$elementtype]['type'][$key];
		if ($type == 'date' || $type == 'datetime') {$type = 'xsd:dateTime'; }
		else {$type = 'xsd:string'; }

		$extrafield_array['options_'.$key] = array('name'=>'options_'.$key, 'type'=>$type);
	}
}
if (is_array($extrafield_array)) $contact_fields = array_merge($contact_fields, $extrafield_array);

// Define other specific objects
$server->wsdl->addComplexType(
    'contact',
    'complexType',
    'struct',
    'all',
    '',
	$contact_fields
);

$server->wsdl->addComplexType(
	'ContactsArray2',
	'complexType',
	'array',
	'sequence',
	'',
	array(
		'contact' => array(
		'name' => 'contact',
		'type' => 'tns:contact',
		'minOccurs' => '0',
		'maxOccurs' => 'unbounded'
	)
	)
);




// 5 styles: RPC/encoded, RPC/literal, Document/encoded (not WS-I compliant), Document/literal, Document/literal wrapped
// Style merely dictates how to translate a WSDL binding to a SOAP message. Nothing more. You can use either style with any programming model.
// http://www.ibm.com/developerworks/webservices/library/ws-whichwsdl/
$styledoc = 'rpc'; // rpc/document (document is an extend into SOAP 1.0 to support unstructured messages)
$styleuse = 'encoded'; // encoded/literal/literal wrapped
// Better choice is document/literal wrapped but literal wrapped not supported by nusoap.


// Register WSDL
$server->register(
    'getContact',
    // Entry values
    array('authentication'=>'tns:authentication', 'id'=>'xsd:string', 'ref_ext'=>'xsd:string'),
    // Exit values
    array('result'=>'tns:result', 'contact'=>'tns:contact'),
    $ns,
    $ns.'#getContact',
    $styledoc,
    $styleuse,
    'WS to get a contact'
);

// Register WSDL
$server->register(
	'createContact',
	// Entry values
	array('authentication'=>'tns:authentication', 'contact'=>'tns:contact'),
	// Exit values
	array('result'=>'tns:result', 'id'=>'xsd:string'),
	$ns,
	$ns.'#createContact',
	$styledoc,
	$styleuse,
	'WS to create a contact'
);

$server->register(
	'getContactsForThirdParty',
	// Entry values
	array('authentication'=>'tns:authentication', 'idthirdparty'=>'xsd:string'),
	// Exit values
	array('result'=>'tns:result', 'contacts'=>'tns:ContactsArray2'),
	$ns,
	$ns.'#getContactsForThirdParty',
	$styledoc,
	$styleuse,
	'WS to get all contacts of a third party'
);

// Register WSDL
$server->register(
	'updateContact',
	// Entry values
	array('authentication'=>'tns:authentication', 'contact'=>'tns:contact'),
	// Exit values
	array('result'=>'tns:result', 'id'=>'xsd:string'),
	$ns,
	$ns.'#updateContact',
	$styledoc,
	$styleuse,
	'WS to update a contact'
);


/**
 * Get Contact
 *
 * @param	array		$authentication		Array of authentication information
 * @param	int			$id					Id of object
 * @param	string		$ref_ext			Ref external of object
 * @return	mixed
 */
function getContact($authentication, $id, $ref_ext)
{
    global $db, $conf, $langs;

    dol_syslog("Function: getContact login=".$authentication['login']." id=".$id." ref_ext=".$ref_ext);

    if ($authentication['entity']) $conf->entity = $authentication['entity'];

    // Init and check authentication
    $objectresp = array();
    $errorcode = ''; $errorlabel = '';
    $error = 0;
    $fuser = check_authentication($authentication, $error, $errorcode, $errorlabel);
    // Check parameters
    if (!$error && ($id && $ref_ext))
    {
        $error++;
        $errorcode = 'BAD_PARAMETERS'; $errorlabel = "Parameter id and ref_ext can't be both provided. You must choose one or other but not both.";
    }

    if (!$error)
    {
        $fuser->getrights();

        $contact = new Contact($db);
        $result = $contact->fetch($id, 0, $ref_ext);
        if ($result > 0)
        {
        	// Only internal user who have contact read permission
        	// Or for external user who have contact read permission, with restrict on societe_id
	        if (
	        	$fuser->rights->societe->contact->lire && !$fuser->societe_id
	        	|| ($fuser->rights->societe->contact->lire && ($fuser->societe_id == $contact->socid))
	        ) {
            	$contact_result_fields = array(
	            	'id' => $contact->id,
	            	'ref_ext' => $contact->ref_ext,
	            	'lastname' => $contact->lastname,
	            	'firstname' => $contact->firstname,
	            	'address' => $contact->address,
	            	'zip' => $contact->zip,
	            	'town' => $contact->town,
	            	'state_id' => $contact->state_id,
	            	'state_code' => $contact->state_code,
	            	'state' => $contact->state,
	            	'country_id' => $contact->country_id,
	            	'country_code' => $contact->country_code,
	            	'country' => $contact->country,
	            	'socid' => $contact->socid,
	            	'status' => $contact->statut,
	            	'phone_pro' => $contact->phone_pro,
	            	'fax' => $contact->fax,
	            	'phone_perso' => $contact->phone_perso,
	            	'phone_mobile' => $contact->phone_mobile,
	            	'code' => $contact->code,
	            	'email' => $contact->email,
	            	'birthday' => $contact->birthday,
	            	'default_lang' => $contact->default_lang,
	            	'note' => $contact->note,
	            	'ref_facturation' => $contact->ref_facturation,
	            	'ref_contrat' => $contact->ref_contrat,
	            	'ref_commande' => $contact->ref_commande,
	            	'ref_propal' => $contact->ref_propal,
	            	'user_id' => $contact->user_id,
	            	'user_login' => $contact->user_login,
	            	'civility_id' => $contact->civility_id,
            		'poste' => $contact->poste
            	);

            	$elementtype = 'socpeople';

            	//Retreive all extrafield for thirdsparty
            	// fetch optionals attributes and labels
            	$extrafields = new ExtraFields($db);
            	$extrafields->fetch_name_optionals_label($elementtype, true);
            	//Get extrafield values
            	$contact->fetch_optionals();

            	if (is_array($extrafields->attributes[$elementtype]['label']) && count($extrafields->attributes[$elementtype]['label']))
            	{
            		foreach ($extrafields->attributes[$elementtype]['label'] as $key=>$label)
	            	{
	            		$contact_result_fields = array_merge($contact_result_fields, array('options_'.$key => $contact->array_options['options_'.$key]));
	            	}
            	}

                // Create
                $objectresp = array(
			    	'result'=>array('result_code'=>'OK', 'result_label'=>''),
			        'contact'=>$contact_result_fields
                );
	        }
	        else
	        {
	            $error++;
	            $errorcode = 'PERMISSION_DENIED'; $errorlabel = 'User does not have permission for this request';
	        }
        }
        else
        {
            $error++;
            $errorcode = 'NOT_FOUND'; $errorlabel = 'Object not found for id='.$id.' nor ref_ext='.$ref_ext;
        }
    }

    if ($error)
    {
        $objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
    }

    return $objectresp;
}


/**
 * Create Contact
 *
 * @param	array		$authentication		Array of authentication information
 * @param	Contact		$contact		    $contact
 * @return	array							Array result
 */
function createContact($authentication, $contact)
{
	global $db,$conf,$langs;

	$now=dol_now();

	dol_syslog("Function: createContact login=".$authentication['login']);

	if ($authentication['entity']) $conf->entity=$authentication['entity'];

	// Init and check authentication
	$objectresp=array();
	$errorcode='';$errorlabel='';
	$error=0;
	$fuser=check_authentication($authentication, $error, $errorcode, $errorlabel);
	// Check parameters
	if (empty($contact['lastname']))
	{
		$error++; $errorcode='KO'; $errorlabel="Name is mandatory.";
	}

	if (! $error)
	{
		$newobject=new Contact($db);

		$newobject->id=$contact['id'];
		$newobject->ref_ext=$contact['ref_ext'];
		$newobject->civility_id=$contact['civility_id'];
		$newobject->lastname=$contact['lastname'];
		$newobject->firstname=$contact['firstname'];
		$newobject->address=$contact['address'];
		$newobject->zip=$contact['zip'];
		$newobject->town=$contact['town'];
		$newobject->state_id=$contact['state_id'];
		$newobject->state_code=$contact['state_code'];
		$newobject->state=$contact['state'];
		$newobject->country_id=$contact['country_id'];
		$newobject->country_code=$contact['country_code'];
		$newobject->country=$contact['country'];
		$newobject->socid=$contact['socid'];
		$newobject->statut=$contact['status'];
		$newobject->phone_pro=$contact['phone_pro'];
		$newobject->fax=$contact['fax'];
		$newobject->phone_perso=$contact['phone_perso'];
		$newobject->phone_mobile=$contact['phone_mobile'];
		$newobject->code=$contact['code'];
		$newobject->email=$contact['email'];
		$newobject->birthday=$contact['birthday'];
		$newobject->default_lang=$contact['default_lang'];
		$newobject->note=$contact['note'];
		$newobject->ref_facturation=$contact['ref_facturation'];
		$newobject->ref_contrat=$contact['ref_contrat'];
		$newobject->ref_commande=$contact['ref_commande'];
		$newobject->ref_propal=$contact['ref_propal'];
		$newobject->user_id=$contact['user_id'];
		$newobject->user_login=$contact['user_login'];
		$newobject->poste=$contact['poste'];

		$elementtype = 'socpeople';

		//Retreive all extrafield for thirdsparty
		// fetch optionals attributes and labels
		$extrafields=new ExtraFields($db);
		$extrafields->fetch_name_optionals_label($elementtype, true);
		if (is_array($extrafields->attributes[$elementtype]['label']) && count($extrafields->attributes[$elementtype]['label']))
		{
			foreach($extrafields->attributes[$elementtype]['label'] as $key=>$label)
			{
				$key='options_'.$key;
				$newobject->array_options[$key]=$contact[$key];
			}
		}


		//...

		$db->begin();

		$result = $newobject->create($fuser);
		if ($result <= 0)
		{
			$error++;
		}

		if (!$error)
		{
			$db->commit();
			$objectresp = array('result'=>array('result_code'=>'OK', 'result_label'=>''), 'id'=>$newobject->id, 'ref'=>$newobject->ref);
		}
		else
		{
			$db->rollback();
			$error++;
			$errorcode = 'KO';
			$errorlabel = $newobject->error;
		}
	}

	if ($error)
	{
		$objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
	}

	return $objectresp;
}

/**
 * Get list of contacts for third party
 *
 * @param	array		$authentication		Array of authentication information
 * @param	int			$idthirdparty		Id thirdparty
 * @return	array							Array result
 */
function getContactsForThirdParty($authentication, $idthirdparty)
{
	global $db, $conf, $langs;

	dol_syslog("Function: getContactsForThirdParty login=".$authentication['login']." idthirdparty=".$idthirdparty);

	if ($authentication['entity']) $conf->entity = $authentication['entity'];

	// Init and check authentication
	$objectresp = array();
	$errorcode = ''; $errorlabel = '';
	$error = 0;
	$fuser = check_authentication($authentication, $error, $errorcode, $errorlabel);
	// Check parameters
	if (!$error && empty($idthirdparty))
	{
		$error++;
		$errorcode = 'BAD_PARAMETERS'; $errorlabel = 'Parameter id is not provided';
	}

	if (!$error)
	{
		$linesinvoice = array();

		$sql = "SELECT c.rowid, c.fk_soc, c.civility as civility_id, c.lastname, c.firstname, c.statut as status,";
		$sql .= " c.address, c.zip, c.town,";
		$sql .= " c.fk_pays as country_id,";
		$sql .= " c.fk_departement as state_id,";
		$sql .= " c.birthday,";
		$sql .= " c.poste, c.phone, c.phone_perso, c.phone_mobile, c.fax, c.email, c.jabberid,";
		//$sql.= " c.priv, c.note, c.default_lang, c.canvas,";
		$sql .= " co.label as country, co.code as country_code,";
		$sql .= " d.nom as state, d.code_departement as state_code,";
		$sql .= " u.rowid as user_id, u.login as user_login,";
		$sql .= " s.nom as socname, s.address as socaddress, s.zip as soccp, s.town as soccity, s.default_lang as socdefault_lang";
		$sql .= " FROM ".MAIN_DB_PREFIX."socpeople as c";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as co ON c.fk_pays = co.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as d ON c.fk_departement = d.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON c.rowid = u.fk_socpeople";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON c.fk_soc = s.rowid";
		$sql .= " WHERE c.fk_soc = ".$idthirdparty;

		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				// En attendant remplissage par boucle
				$obj = $db->fetch_object($resql);

				$contact = new Contact($db);
				$contact->fetch($obj->rowid);

				// Now define invoice
				$linescontact[] = array(
					'id' => $contact->id,
					'ref' => $contact->ref,
					'civility_id' => $contact->civility_id ? $contact->civility_id : '',
					'lastname' => $contact->lastname ? $contact->lastname : '',
					'firstname' => $contact->firstname ? $contact->firstname : '',
					'address' => $contact->address ? $contact->address : '',
					'zip' => $contact->zip ? $contact->zip : '',
					'town' => $contact->town ? $contact->town : '',

					'state_id' => $contact->state_id ? $contact->state_id : '',
					'state_code' => $contact->state_code ? $contact->state_code : '',
					'state' => $contact->state ? $contact->state : '',

					'country_id' => $contact->country_id ? $contact->country_id : '',
					'country_code' => $contact->country_code ? $contact->country_code : '',
					'country' => $contact->country ? $contact->country : '',

					'socid' => $contact->socid ? $contact->socid : '',
					'socname' => $contact->socname ? $contact->socname : '',
					'poste' => $contact->poste ? $contact->poste : '',

					'phone_pro' => $contact->phone_pro ? $contact->phone_pro : '',
					'fax' => $contact->fax ? $contact->fax : '',
					'phone_perso' => $contact->phone_perso ? $contact->phone_perso : '',
					'phone_mobile' => $contact->phone_mobile ? $contact->phone_mobile : '',

					'email' => $contact->email ? $contact->email : '',
					'jabberid' => $contact->jabberid ? $contact->jabberid : '',
					'priv' => $contact->priv ? $contact->priv : '',
					'mail' => $contact->mail ? $contact->mail : '',

					'birthday' => $contact->birthday ? $contact->birthday : '',
					'default_lang' => $contact->default_lang ? $contact->default_lang : '',
					'note' => $contact->note ? $contact->note : '',
					'ref_facturation' => $contact->ref_facturation ? $contact->ref_facturation : '',
					'ref_contrat' => $contact->ref_contrat ? $contact->ref_contrat : '',
					'ref_commande' => $contact->ref_commande ? $contact->ref_commande : '',
					'ref_propal' => $contact->ref_propal ? $contact->ref_propal : '',
					'user_id' => $contact->user_id ? $contact->user_id : '',
					'user_login' => $contact->user_login ? $contact->user_login : '',
					'status' => $contact->statut ? $contact->statut : ''
				);

				$i++;
			}

			$objectresp = array(
			'result'=>array('result_code'=>'OK', 'result_label'=>''),
			'contacts'=>$linescontact

			);
		}
		else
		{
			$error++;
			$errorcode = $db->lasterrno(); $errorlabel = $db->lasterror();
		}
	}

	if ($error)
	{
		$objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
	}

	return $objectresp;
}


/**
 * Update a contact
 *
 * @param	array		$authentication		Array of authentication information
 * @param	Contact		$contact		    Contact
 * @return	array							Array result
 */
function updateContact($authentication, $contact)
{
	global $db,$conf,$langs;

	$now=dol_now();

	dol_syslog("Function: updateContact login=".$authentication['login']);

	if ($authentication['entity']) $conf->entity=$authentication['entity'];

	// Init and check authentication
	$objectresp=array();
	$errorcode='';$errorlabel='';
	$error=0;
	$fuser=check_authentication($authentication, $error, $errorcode, $errorlabel);
	// Check parameters
	if (empty($contact['id']) && empty($contact['ref_ext']))	{
		$error++; $errorcode='KO'; $errorlabel="Contact id or ref_ext is mandatory.";
	}
	// Check parameters
    if (! $error && ($id && $ref_ext))
    {
        $error++;
        $errorcode='BAD_PARAMETERS'; $errorlabel="Parameter id and ref_ext can't be all provided. You must choose one of them.";
    }

	if (! $error)
	{
		$objectfound=false;

		include_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';

		$object=new Contact($db);
		$result=$object->fetch($contact['id'], 0, $contact['ref_ext']);

		if (!empty($object->id)) {
			$objectfound=true;


			$object->firstname=$contact['firstname'];
			$object->lastname=$contact['lastname'];

			$object->address=$contact['address'];
			$object->zip=$contact['zip'];
			$object->town=$contact['town'];

			$object->country_id=$contact['country_id'];
			if ($contact['country_code']) $object->country_id=getCountry($contact['country_code'], 3);
			$object->province_id=$contact['province_id'];


			$object->phone_pro=$contact['phone_pro'];
			$object->phone_perso=$contact['phone_perso'];
			$object->phone_mobile=$contact['phone_mobile'];
			$object->fax=$contact['fax'];
			$object->email=$contact['email'];

			$object->civility_id=$contact['civility_id'];
			$object->poste=$contact['poste'];

			$object->statut=$contact['status'];

			$elementtype = 'socpeople';

			//Retreive all extrafield for contact
			// fetch optionals attributes and labels
			$extrafields=new ExtraFields($db);
			$extrafields->fetch_name_optionals_label($elementtype, true);
			if (is_array($extrafields->attributes[$elementtype]['label']) && count($extrafields->attributes[$elementtype]['label']))
			{
				foreach($extrafields->attributes[$elementtype]['label'] as $key=>$label)
				{
					$key='options_'.$key;
					$object->array_options[$key]=$contact[$key];
				}
			}

			$db->begin();

			$result=$object->update($contact['id'], $fuser);
			if ($result <= 0) {
				$error++;
			}
		}

		if ((! $error) && ($objectfound))
		{
			$db->commit();
			$objectresp=array(
			'result'=>array('result_code'=>'OK', 'result_label'=>''),
			'id'=>$object->id
			);
		}
		elseif ($objectfound)
		{
			$db->rollback();
			$error++;
			$errorcode = 'KO';
			$errorlabel = $object->error;
		} else {
			$error++;
			$errorcode = 'NOT_FOUND';
			$errorlabel = 'Contact id='.$contact['id'].' cannot be found';
		}
	}

	if ($error)
	{
		$objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
	}

	return $objectresp;
}

// Return the results.
$server->service(file_get_contents("php://input"));
