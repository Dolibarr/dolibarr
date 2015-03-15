<?php
/* Copyright (C) 2006-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *       \file       htdocs/webservices/server_invoice.php
 *       \brief      File that is entry point to call Dolibarr WebServices
 */

// This is to make Dolibarr working with Plesk
set_include_path($_SERVER['DOCUMENT_ROOT'].'/htdocs');

require_once '../master.inc.php';
require_once NUSOAP_PATH.'/nusoap.php';		// Include SOAP
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ws.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';


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
$server->configureWSDL('WebServicesDolibarrInvoice',$ns);
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
        'entity' => array('name'=>'entity','type'=>'xsd:string')
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
    'line',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'id' => array('name'=>'id','type'=>'xsd:string'),
        'type' => array('name'=>'type','type'=>'xsd:int'),
        'desc' => array('name'=>'desc','type'=>'xsd:string'),
        'vat_rate' => array('name'=>'vat_rate','type'=>'xsd:double'),
        'qty' => array('name'=>'qty','type'=>'xsd:double'),
        'unitprice' => array('name'=>'unitprice','type'=>'xsd:double'),
        'total_net' => array('name'=>'total_net','type'=>'xsd:double'),
    	'total_vat' => array('name'=>'total_vat','type'=>'xsd:double'),
    	'total' => array('name'=>'total','type'=>'xsd:double'),
        'date_start' => array('name'=>'date_start','type'=>'xsd:date'),
        'date_end' => array('name'=>'date_end','type'=>'xsd:date'),
        'payment_mode_id' => array('name'=>'payment_mode_id','type'=>'xsd:string'),
        // From product
        'product_id' => array('name'=>'product_id','type'=>'xsd:int'),
        'product_ref' => array('name'=>'product_ref','type'=>'xsd:string'),
        'product_label' => array('name'=>'product_label','type'=>'xsd:string'),
        'product_desc' => array('name'=>'product_desc','type'=>'xsd:string')
    )
);

/*$server->wsdl->addComplexType(
    'LinesArray',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(
        array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:line[]')
    ),
    'tns:line'
);*/
$server->wsdl->addComplexType(
    'LinesArray2',
    'complexType',
    'array',
    'sequence',
    '',
    array(
        'line' => array(
            'name' => 'line',
            'type' => 'tns:line',
            'minOccurs' => '0',
            'maxOccurs' => 'unbounded'
        )
    ),
    null,
    'tns:line'
);


$server->wsdl->addComplexType(
    'invoice',
    'complexType',
    'struct',
    'all',
    '',
    array(
    	'id' => array('name'=>'id','type'=>'xsd:string'),
        'ref' => array('name'=>'ref','type'=>'xsd:string'),
        'ref_ext' => array('name'=>'ref_ext','type'=>'xsd:string'),
        'thirdparty_id' => array('name'=>'thirdparty_id','type'=>'xsd:int'),
        'fk_user_author' => array('name'=>'fk_user_author','type'=>'xsd:string'),
        'fk_user_valid' => array('name'=>'fk_user_valid','type'=>'xsd:string'),
        'date' => array('name'=>'date','type'=>'xsd:date'),
        'date_due' => array('name'=>'date_due','type'=>'xsd:date'),
        'date_creation' => array('name'=>'date_creation','type'=>'xsd:dateTime'),
        'date_validation' => array('name'=>'date_validation','type'=>'xsd:dateTime'),
        'date_modification' => array('name'=>'date_modification','type'=>'xsd:dateTime'),
        'type' => array('name'=>'type','type'=>'xsd:int'),
        'total_net' => array('name'=>'type','type'=>'xsd:double'),
        'total_vat' => array('name'=>'type','type'=>'xsd:double'),
        'total' => array('name'=>'type','type'=>'xsd:double'),
        'note_private' => array('name'=>'note_private','type'=>'xsd:string'),
        'note_public' => array('name'=>'note_public','type'=>'xsd:string'),
        'status' => array('name'=>'status','type'=>'xsd:int'),
        'close_code' => array('name'=>'close_code','type'=>'xsd:string'),
        'close_note' => array('name'=>'close_note','type'=>'xsd:string'),
        'project_id' => array('name'=>'project_id','type'=>'xsd:string'),
        'lines' => array('name'=>'lines','type'=>'tns:LinesArray2')
    )
);
/*
$server->wsdl->addComplexType(
    'InvoicesArray',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(
        array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:invoice[]')
    ),
    'tns:invoice'
);*/
$server->wsdl->addComplexType(
    'InvoicesArray2',
    'complexType',
    'array',
    'sequence',
    '',
    array(
        'invoice' => array(
            'name' => 'invoice',
            'type' => 'tns:invoice',
            'minOccurs' => '0',
            'maxOccurs' => 'unbounded'
        )
    ),
    null,
    'tns:invoice'
);



// 5 styles: RPC/encoded, RPC/literal, Document/encoded (not WS-I compliant), Document/literal, Document/literal wrapped
// Style merely dictates how to translate a WSDL binding to a SOAP message. Nothing more. You can use either style with any programming model.
// http://www.ibm.com/developerworks/webservices/library/ws-whichwsdl/
$styledoc='rpc';       // rpc/document (document is an extend into SOAP 1.0 to support unstructured messages)
$styleuse='encoded';   // encoded/literal/literal wrapped
// Better choice is document/literal wrapped but literal wrapped not supported by nusoap.

// Register WSDL
$server->register(
    'getInvoice',
    // Entry values
    array('authentication'=>'tns:authentication','id'=>'xsd:string','ref'=>'xsd:string','ref_ext'=>'xsd:string'),
    // Exit values
    array('result'=>'tns:result','invoice'=>'tns:invoice'),
    $ns,
    $ns.'#getInvoice',
    $styledoc,
    $styleuse,
    'WS to get a particular invoice'
);
$server->register(
    'getInvoicesForThirdParty',
    // Entry values
    array('authentication'=>'tns:authentication','idthirdparty'=>'xsd:string'),
    // Exit values
    array('result'=>'tns:result','invoices'=>'tns:InvoicesArray2'),
    $ns,
    $ns.'#getInvoicesForThirdParty',
    $styledoc,
    $styleuse,
    'WS to get all invoices of a third party'
);
$server->register(
    'createInvoice',
    // Entry values
    array('authentication'=>'tns:authentication','invoice'=>'tns:invoice'),
    // Exit values
    array('result'=>'tns:result','id'=>'xsd:string','ref'=>'xsd:string'),
    $ns,
    $ns.'#createInvoice',
    $styledoc,
    $styleuse,
    'WS to create an invoice'
);


/**
 * Get invoice from id, ref or ref_ext.
 *
 * @param	array		$authentication		Array of authentication information
 * @param	int			$id					Id
 * @param	string		$ref				Ref
 * @param	string		$ref_ext			Ref_ext
 * @return	array							Array result
 */
function getInvoice($authentication,$id='',$ref='',$ref_ext='')
{
	global $db,$conf,$langs;

	dol_syslog("Function: getInvoice login=".$authentication['login']." id=".$id." ref=".$ref." ref_ext=".$ref_ext);

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

		if ($fuser->rights->facture->lire)
		{
			$invoice=new Facture($db);
			$result=$invoice->fetch($id,$ref,$ref_ext);
			if ($result > 0)
			{
				$linesresp=array();
				$i=0;
				foreach($invoice->lines as $line)
				{
					//var_dump($line); exit;
					$linesresp[]=array(
						'id'=>$line->rowid,
						'type'=>$line->product_type,
                        'desc'=>dol_htmlcleanlastbr($line->desc),
					    'total_net'=>$line->total_ht,
						'total_vat'=>$line->total_tva,
						'total'=>$line->total_ttc,
                        'vat_rate'=>$line->tva_tx,
                        'qty'=>$line->qty,
                        'product_ref'=>$line->product_ref,
                        'product_label'=>$line->product_label,
                        'product_desc'=>$line->product_desc,
					);
					$i++;
				}

			    // Create invoice
			    $objectresp = array(
			    	'result'=>array('result_code'=>'OK', 'result_label'=>''),
			        'invoice'=>array(
				    	'id' => $invoice->id,
			   			'ref' => $invoice->ref,
			        	'ref_ext' => $invoice->ref_ext?$invoice->ref_ext:'',   // If not defined, field is not added into soap
			        	'fk_user_author' => $invoice->user_author?$invoice->user_author:'',
			        	'fk_user_valid' => $invoice->user_valid?$invoice->user_valid:'',
			        	'date' => $invoice->date?dol_print_date($invoice->date,'dayrfc'):'',
			        	'date_creation' => $invoice->date_creation?dol_print_date($invoice->date_creation,'dayhourrfc'):'',
			        	'date_validation' => $invoice->date_validation?dol_print_date($invoice->date_creation,'dayhourrfc'):'',
			        	'date_modification' => $invoice->datem?dol_print_date($invoice->datem,'dayhourrfc'):'',
			        	'type' => $invoice->type,
			        	'total_net' => $invoice->total_ht,
			        	'total_vat' => $invoice->total_tva,
			        	'total' => $invoice->total_ttc,
			        	'note_private' => $invoice->note_private?$invoice->note_private:'',
			        	'note_public' => $invoice->note_public?$invoice->note_public:'',
			        	'status'=> $invoice->statut,
			        	'close_code' => $invoice->close_code?$invoice->close_code:'',
			        	'close_note' => $invoice->close_note?$invoice->close_note:'',
			        	'payment_mode_id' => $invoice->mode_reglement_id?$invoice->mode_reglement_id:'',
			        	'lines' => $linesresp
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
 * Get list of invoices for third party
 *
 * @param	array		$authentication		Array of authentication information
 * @param	int			$idthirdparty		Id thirdparty
 * @return	array							Array result
 */
function getInvoicesForThirdParty($authentication,$idthirdparty)
{
	global $db,$conf,$langs;

	dol_syslog("Function: getInvoicesForThirdParty login=".$authentication['login']." idthirdparty=".$idthirdparty);

	if ($authentication['entity']) $conf->entity=$authentication['entity'];

    // Init and check authentication
    $objectresp=array();
    $errorcode='';$errorlabel='';
    $error=0;
    $fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);

	if ($fuser->societe_id) $socid=$fuser->societe_id;

	// Check parameters
	if (! $error && empty($idthirdparty))
	{
		$error++;
		$errorcode='BAD_PARAMETERS'; $errorlabel='Parameter id is not provided';
	}

	if (! $error)
	{
		$linesinvoice=array();

		$sql.='SELECT f.rowid as facid, facnumber as ref, ref_ext, type, fk_statut as status, total_ttc, total, tva';
		$sql.=' FROM '.MAIN_DB_PREFIX.'facture as f';
		$sql.=" WHERE f.entity = ".$conf->entity;
		if ($idthirdparty != 'all' ) $sql.=" AND f.fk_soc = ".$db->escape($idthirdparty);

		$resql=$db->query($sql);
		if ($resql)
		{
			$num=$db->num_rows($resql);
			$i=0;
			while ($i < $num)
			{
                // En attendant remplissage par boucle
			    $obj=$db->fetch_object($resql);

			    $invoice=new Facture($db);
			    $invoice->fetch($obj->facid);

			    // Sécurité pour utilisateur externe
			    if( $socid && ( $socid != $invoice->socid) )
			    {
			    	$error++;
			    	$errorcode='PERMISSION_DENIED'; $errorlabel=$invoice->socid.' User does not have permission for this request';
			    }

			    if(!$error)
			    {
			    	// Define lines of invoice
			    	$linesresp=array();
			    	foreach($invoice->lines as $line)
			    	{
			    		$linesresp[]=array(
	    					'id'=>$line->rowid,
	    					'type'=>$line->product_type,
	    					'total_net'=>$line->total_ht,
	    					'total_vat'=>$line->total_tva,
	    					'total'=>$line->total_ttc,
			    			'vat_rate'=>$line->tva_tx,
			    			'qty'=>$line->qty,
			    			'product_ref'=>$line->product_ref,
			    			'product_label'=>$line->product_label,
			    			'product_desc'=>$line->product_desc,
			    		);
			    	}

			    	// Now define invoice
			    	$linesinvoice[]=array(
			    		'id' => $invoice->id,
			    		'ref' => $invoice->ref,
			    		'ref_ext' => $invoice->ref_ext?$invoice->ref_ext:'',   // If not defined, field is not added into soap
			    		'fk_user_author' => $invoice->user_author?$invoice->user_author:'',
			    		'fk_user_valid' => $invoice->user_valid?$invoice->user_valid:'',
			    		'date' => $invoice->date?dol_print_date($invoice->date,'dayrfc'):'',
			    		'date_due' => $invoice->date_lim_reglement?dol_print_date($invoice->date_lim_reglement,'dayrfc'):'',
					    'date_creation' => $invoice->date_creation?dol_print_date($invoice->date_creation,'dayhourrfc'):'',
			    		'date_validation' => $invoice->date_validation?dol_print_date($invoice->date_creation,'dayhourrfc'):'',
			    		'date_modification' => $invoice->datem?dol_print_date($invoice->datem,'dayhourrfc'):'',
			    		'type' => $invoice->type,
			    		'total_net' => $invoice->total_ht,
			    		'total_vat' => $invoice->total_tva,
			    		'total' => $invoice->total_ttc,
			    		'note_private' => $invoice->note_private?$invoice->note_private:'',
			    		'note_public' => $invoice->note_public?$invoice->note_public:'',
			    		'status'=> $invoice->statut,
			    		'close_code' => $invoice->close_code?$invoice->close_code:'',
			    		'close_note' => $invoice->close_note?$invoice->close_note:'',
					'payment_mode_id' => $invoice->mode_reglement_id?$invoice->mode_reglement_id:'',
			    		'lines' => $linesresp
			    	);
			    }

			    $i++;
			}

			$objectresp=array(
		    	'result'=>array('result_code'=>'OK', 'result_label'=>''),
		        'invoices'=>$linesinvoice

			);
		}
		else
		{
			$error++;
			$errorcode=$db->lasterrno(); $errorlabel=$db->lasterror();
		}
	}

	if ($error)
	{
		$objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
	}

	return $objectresp;
}


/**
 * Create an invoice
 *
 * @param	array		$authentication		Array of authentication information
 * @param	Facture		$invoice			Invoice
 * @return	array							Array result
 */
function createInvoice($authentication,$invoice)
{
    global $db,$conf,$langs;

    $now=dol_now();

    dol_syslog("Function: createInvoiceForThirdParty login=".$authentication['login']);

    if ($authentication['entity']) $conf->entity=$authentication['entity'];

    // Init and check authentication
    $objectresp=array();
    $errorcode='';$errorlabel='';
    $error=0;
    $fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);

    if (! $error)
    {
        $newobject=new Facture($db);
        $newobject->socid=$invoice['thirdparty_id'];
        $newobject->type=$invoice['type'];
        $newobject->ref_ext=$invoice['ref_ext'];
        $newobject->date=dol_stringtotime($invoice['date'],'dayrfc');
        $newobject->note_private=$invoice['note_private'];
        $newobject->note_public=$invoice['note_public'];
        $newobject->statut= Facture::STATUS_DRAFT;	// We start with status draft
        $newobject->fk_project=$invoice['project_id'];
        $newobject->date_creation=$now;
        
	//take mode_reglement and cond_reglement from thirdparty
        $soc = new Societe($db);
        $res=$soc->fetch($newobject->socid);
        if ($res > 0) {
    	    $newobject->mode_reglement_id = ! empty($invoice['payment_mode_id'])?$invoice['payment_mode_id']:$soc->mode_reglement_id;
            $newobject->cond_reglement_id  = $soc->cond_reglement_id; 
        }
        else $newobject->mode_reglement_id = $invoice['payment_mode_id'];

        // Trick because nusoap does not store data with same structure if there is one or several lines
        $arrayoflines=array();
        if (isset($invoice['lines']['line'][0])) $arrayoflines=$invoice['lines']['line'];
        else $arrayoflines=$invoice['lines'];

        foreach($arrayoflines as $key => $line)
        {
            // $key can be 'line' or '0','1',...
            $newline=new FactureLigne($db);
            $newline->product_type=$line['type'];
            $newline->desc=$line['desc'];
            $newline->fk_product=$line['fk_product'];
            $newline->tva_tx=$line['vat_rate'];
            $newline->qty=$line['qty'];
            $newline->subprice=$line['unitprice'];
            $newline->total_ht=$line['total_net'];
            $newline->total_tva=$line['total_vat'];
            $newline->total_ttc=$line['total'];
            $newline->date_start=dol_stringtotime($line['date_start']);
            $newline->date_end=dol_stringtotime($line['date_end']);
            $newline->fk_product=$line['product_id'];
            $newobject->lines[]=$newline;
        }
        //var_dump($newobject->date_lim_reglement); exit;
        //var_dump($invoice['lines'][0]['type']);

        $db->begin();

        $result=$newobject->create($fuser,0,dol_stringtotime($invoice['date_due'],'dayrfc'));
        if ($result < 0)
        {
            $error++;
        }

        if ($invoice['status'] == 1)   // We want invoice to have status validated
        {
            $result=$newobject->validate($fuser);
            if ($result < 0)
            {
                $error++;
            }
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

// Return the results.
$server->service(file_get_contents("php://input"));
