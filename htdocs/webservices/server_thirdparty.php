<?php
/* Copyright (C) 2006-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
 *       \file       htdocs/webservices/server_thirdparty.php
 *       \brief      File that is entry point to call Dolibarr WebServices
 */

// This is to make Dolibarr working with Plesk
set_include_path($_SERVER['DOCUMENT_ROOT'].'/htdocs');

require_once("../master.inc.php");
require_once(NUSOAP_PATH.'/nusoap.php');        // Include SOAP
require_once(DOL_DOCUMENT_ROOT."/core/lib/ws.lib.php");
require_once(DOL_DOCUMENT_ROOT."/user/class/user.class.php");

require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");


dol_syslog("Call Dolibarr webservices interfaces");

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
$server->configureWSDL('WebServicesDolibarrThirdParty',$ns);
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
    'thirdparty',
    'complexType',
    'struct',
    'all',
    '',
    array(
    	'id' => array('name'=>'id','type'=>'xsd:string'),
        'ref' => array('name'=>'name','type'=>'xsd:string'),
        'ref_ext' => array('name'=>'ref_ext','type'=>'xsd:string'),
        'fk_user_author' => array('name'=>'fk_user_author','type'=>'xsd:string'),
        'client' => array('name'=>'client','type'=>'xsd:string'),
        'supplier' => array('name'=>'supplier','type'=>'xsd:string'),
        'customer_code' => array('name'=>'customer_code','type'=>'xsd:string'),
        'supplier_code' => array('name'=>'supplier_code','type'=>'xsd:string'),
        'customer_code_accountancy' => array('name'=>'customer_code_accountancy','type'=>'xsd:string'),
        'supplier_code_accountancy' => array('name'=>'supplier_code_accountancy','type'=>'xsd:string'),
        'date_creation' => array('name'=>'date_creation','type'=>'xsd:dateTime'),
        'date_modification' => array('name'=>'date_modification','type'=>'xsd:dateTime'),
        'note' => array('name'=>'note','type'=>'xsd:string'),
    	'address' => array('name'=>'address','type'=>'xsd:string'),
    	'zip' => array('name'=>'zip','type'=>'xsd:string'),
    	'town' => array('name'=>'town','type'=>'xsd:string'),
    	'province_id' => array('name'=>'province_id','type'=>'xsd:string'),
    	'country_id' => array('name'=>'country_id','type'=>'xsd:string'),
    	'country_code' => array('name'=>'country_code','type'=>'xsd:string'),
    	'country' => array('name'=>'country','type'=>'xsd:string'),
        'phone' => array('name'=>'phone','type'=>'xsd:string'),
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
    	'vat_used' => array('name'=>'vat_used','type'=>'xsd:string'),
    	'vat_number' => array('name'=>'vat_number','type'=>'xsd:string')
    )
);

// Define other specific objects
$server->wsdl->addComplexType(
    'filterthirdparty',
    'complexType',
    'struct',
    'all',
    '',
    array(
        //'limit' => array('name'=>'limit','type'=>'xsd:string'),
        'client' => array('name'=>'client','type'=>'xsd:string'),
        'supplier' => array('name'=>'supplier','type'=>'xsd:string')
    )
);

$server->wsdl->addComplexType(
    'ThirdPartiesArray',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(
        array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:thirdparty[]')
    ),
    'tns:thirdparty'
);
$server->wsdl->addComplexType(
    'ThirdPartiesArray2',
    'complexType',
    'array',
    'sequence',
    '',
    array(
        'thirdparty' => array(
            'name' => 'thirdparty',
            'type' => 'tns:thirdparty',
            'minOccurs' => '0',
            'maxOccurs' => 'unbounded'
        )
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
    'getThirdParty',
    // Entry values
    array('authentication'=>'tns:authentication','id'=>'xsd:string','ref'=>'xsd:string','ref_ext'=>'xsd:string'),
    // Exit values
    array('result'=>'tns:result','thirdparty'=>'tns:thirdparty'),
    $ns,
    $ns.'#getThirdParty',
    $styledoc,
    $styleuse,
    'WS to get a thirdparty from its id, ref or ref_ext'
);

// Register WSDL
$server->register(
    'createThirdParty',
    // Entry values
    array('authentication'=>'tns:authentication','thirdparty'=>'tns:thirdparty'),
    // Exit values
    array('result'=>'tns:result','id'=>'xsd:string'),
    $ns,
    $ns.'#createThirdParty',
    $styledoc,
    $styleuse,
    'WS to create a thirdparty'
);


// Register WSDL
$server->register(
    'getListOfThirdParties',
    // Entry values
    array('authentication'=>'tns:authentication','filterthirdparty'=>'tns:filterthirdparty'),
    // Exit values
    array('result'=>'tns:result','thirdparties'=>'tns:ThirdPartiesArray2'),
    $ns,
    $ns.'#getListOfThirdParties',
    $styledoc,
    $styleuse,
    'WS to get list of thirdparties id and ref'
);


// Full methods code
function getThirdParty($authentication,$id='',$ref='',$ref_ext='')
{
	global $db,$conf,$langs;

	dol_syslog("Function: getThirdParty login=".$authentication['login']." id=".$id." ref=".$ref." ref_ext=".$ref_ext);

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

		if ($fuser->rights->societe->lire)
		{
			$thirdparty=new Societe($db);
			$result=$thirdparty->fetch($id,$ref,$ref_ext);
			if ($result > 0)
			{
			    // Create
			    $objectresp = array(
			    	'result'=>array('result_code'=>'OK', 'result_label'=>''),
			        'thirdparty'=>array(
				    	'id' => $thirdparty->id,
			   			'ref' => $thirdparty->name,
			   			'ref_ext' => $thirdparty->ref_ext,
			   			'status' => $thirdparty->status,
			            'client' => $thirdparty->client,
			            'supplier' => $thirdparty->fournisseur,
				        'customer_code' => $thirdparty->code_client,
			            'supplier_code' => $thirdparty->code_fournisseur,
				        'customer_code_accountancy' => $thirdparty->code_compta,
			            'supplier_code_accountancy' => $thirdparty->code_compta_fournisseur,
			            'fk_user_author' => $thirdparty->fk_user_author,
			    		'date_creation' => dol_print_date($thirdparty->datec,'dayhourrfc'),
			    		'date_modification' => dol_print_date($thirdparty->date_update,'dayhourrfc'),
			            'address' => $thirdparty->address,
				        'zip' => $thirdparty->zip,
				        'town' => $thirdparty->town,
				        'province_id' => $thirdparty->state_id,
				        'country_id' => $thirdparty->country_id,
				        'country_code' => $thirdparty->country_code,
				        'country' => $thirdparty->country,
			            'phone' => $thirdparty->tel,
				        'fax' => $thirdparty->fax,
				        'email' => $thirdparty->email,
				        'url' => $thirdparty->url,
				        'profid1' => $thirdparty->idprof1,
				        'profid2' => $thirdparty->idprof2,
				        'profid3' => $thirdparty->idprof3,
				        'profid4' => $thirdparty->idprof4,
				        'profid5' => $thirdparty->idprof5,
				        'profid6' => $thirdparty->idprof6,
			            'capital' => $thirdparty->capital,
			   			'barcode' => $thirdparty->barcode,
			            'vat_used' => $thirdparty->tva_assuj,
				        'vat_number' => $thirdparty->tva_intra
			    ));
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
 * Create a thirdparty
 *
 * @param	array		$authentication		Array of authentication information
 * @param	Societe		$thirdparty		    Thirdparty
 * @return	array							Array result
 */
function createThirdParty($authentication,$thirdparty)
{
    global $db,$conf,$langs;

    $now=dol_now();

    dol_syslog("Function: createThirdParty login=".$authentication['login']);

    if ($authentication['entity']) $conf->entity=$authentication['entity'];

    // Init and check authentication
    $objectresp=array();
    $errorcode='';$errorlabel='';
    $error=0;
    $fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);
    // Check parameters
    if (empty($thirdparty['ref']))
    {
        $error++; $errorcode='KO'; $errorlabel="Name is mandatory.";
    }


    if (! $error)
    {
        include_once(DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php');

        $newobject=new Societe($db);
        $newobject->ref=$thirdparty['ref'];
        $newobject->name=$thirdparty['ref'];
        $newobject->ref_ext=$thirdparty['ref_ext'];
        $newobject->status=$thirdparty['status'];
        $newobject->client=$thirdparty['client'];
        $newobject->fournisseur=$thirdparty['supplier'];
        $newobject->code_client=$thirdparty['customer_code'];
        $newobject->code_fournisseur=$thirdparty['supplier_code'];
        $newobject->code_compta=$thirdparty['customer_code_accountancy'];
        $newobject->code_compta_fournisseur=$thirdparty['supplier_code_accountancy'];
        $newobject->date_creation=$now;
        $newobject->note=$thirdparty['note'];
        $newobject->address=$thirdparty['address'];
        $newobject->zip=$thirdparty['zip'];
        $newobject->town=$thirdparty['town'];

        $newobject->country_id=$thirdparty['country_id'];
        if ($thirdparty['country_code']) $newobject->country_id=getCountry($thirdparty['country_code'],3);
        $newobject->province_id=$thirdparty['province_id'];
        //if ($thirdparty['province_code']) $newobject->province_code=getCountry($thirdparty['province_code'],3);

        $newobject->phone=$thirdparty['phone'];
        $newobject->fax=$thirdparty['fax'];
        $newobject->email=$thirdparty['email'];
        $newobject->url=$thirdparty['url'];
        $newobject->idprof1=$thirdparty['profid1'];
        $newobject->idprof2=$thirdparty['profid2'];
        $newobject->idprof3=$thirdparty['profid3'];
        $newobject->idprof4=$thirdparty['profid4'];
        $newobject->idprof5=$thirdparty['profid5'];
        $newobject->idprof6=$thirdparty['profid6'];

        $newobject->capital=$thirdparty['capital'];

        $newobject->barcode=$thirdparty['barcode'];
        $newobject->tva_assuj=$thirdparty['vat_used'];
        $newobject->tva_intra=$thirdparty['vat_number'];

        $newobject->canvas=$thirdparty['canvas'];

        $db->begin();

        $result=$newobject->create($fuser);
        if ($result <= 0)
        {
            $error++;
        }

        if (! $error)
        {
            $db->commit();
            $objectresp=array('result'=>array('result_code'=>'OK', 'result_label'=>''),'id'=>$newobject->id,'ref'=>$newobject->ref);
        }
        else
        {
            $db->rollback();
            $error++;
            $errorcode='KO';
            $errorlabel=$newobject->error;
        }
    }

    if ($error)
    {
        $objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
    }

    return $objectresp;
}



/**
 * getListOfThirdParties
 *
 * @param	array		$authentication		Array of authentication information
 * @param	array		$filterthirdparty	Filter fields
 * @return	array							Array result
 */
function getListOfThirdParties($authentication,$filterthirdparty)
{
    global $db,$conf,$langs;

    $now=dol_now();

    dol_syslog("Function: getListOfThirdParties login=".$authentication['login']);

    if ($authentication['entity']) $conf->entity=$authentication['entity'];

    // Init and check authentication
    $objectresp=array();
    $arraythirdparties=array();
    $errorcode='';$errorlabel='';
    $error=0;
    $fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);
    // Check parameters

    if (! $error)
    {
        $sql ="SELECT rowid, nom as ref, ref_ext";
        $sql.=" FROM ".MAIN_DB_PREFIX."societe";
        $sql.=" WHERE entity=".$conf->entity;
        foreach($filterthirdparty as $key => $val)
        {
            if ($key == 'client'   && $val != '')  $sql.=" AND client = ".$db->escape($val);
            if ($key == 'supplier' && $val != '')  $sql.=" AND fournisseur = ".$db->escape($val);
        }
        $resql=$db->query($sql);
        if ($resql)
        {
            $num=$db->num_rows($resql);

            $i=0;
            while ($i < $num)
            {
                $obj=$db->fetch_object($resql);
                $arraythirdparties[]=array('id'=>$obj->rowid,'ref'=>$obj->ref,'ref_ext'=>$obj->ref_ext);
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
            'thirdparties'=>$arraythirdparties
        );
    }
    else
    {
        $objectresp = array(
            'result'=>array('result_code' => 'OK', 'result_label' => ''),
            'thirdparties'=>$arraythirdparties
        );
    }

    return $objectresp;
}



// Return the results.
$server->service($HTTP_RAW_POST_DATA);

?>
